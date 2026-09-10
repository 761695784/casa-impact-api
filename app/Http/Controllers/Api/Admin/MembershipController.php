<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\MembershipStatus;
use App\Http\Controllers\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMembershipManualRequest;
use App\Http\Requests\Admin\UpdateMembershipRequest;
use App\Http\Resources\Admin\MembershipResource;
use App\Models\Membership;
use App\Notifications\MembershipValidated;
use App\Services\MembershipCardService;
use App\Services\MembershipReferenceGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * store() ici est la saisie MANUELLE réservée à l'admin (membres
 * antérieurs au site, accord explicite du 2026-08-24) — la soumission
 * "normale" d'une nouvelle adhésion reste publique, voir
 * Public\MembershipController::store(). Toutes les actions sont
 * protégées par MembershipPolicy (permissions memberships.*, assignées au
 * seul rôle administrateur-principal — voir RolesAndPermissionsSeeder).
 */
class MembershipController extends Controller
{
    use ExportsCsv;

    public function __construct(
        private MembershipReferenceGenerator $referenceGenerator,
        private MembershipCardService $cardService,
    ) {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Membership::class);

        $perPage = min((int) $request->integer('per_page', 15), 100);

        $memberships = Membership::query()
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where(function ($q) use ($request) {
                    $search = $request->string('search');
                    $q->where('nom_complet', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('numero_membre', 'like', "%{$search}%");
                })
            )
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('region'), fn ($q) => $q->where('region', $request->string('region')))
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->string('source')))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return MembershipResource::collection($memberships);
    }

    public function show(Membership $membership)
    {
        $this->authorize('view', $membership);

        return new MembershipResource($membership);
    }

    /**
     * Saisie manuelle par l'admin — créée DIRECTEMENT au statut `validee`
     * (pas de cycle "en attente de paiement" : l'admin ne saisit que des
     * adhésions déjà réglées/actées en dehors du site). `send_welcome_email`
     * (défaut true) permet de sauter l'email de bienvenue "vous venez de
     * rejoindre Casa Impact aujourd'hui" pour un adhérent historique — voir
     * StoreMembershipManualRequest. La carte de membre est toujours
     * générée (accessible ensuite via downloadCard()), qu'un email soit
     * envoyé ou non.
     */
    public function store(StoreMembershipManualRequest $request)
    {
        $this->authorize('create', Membership::class);

        $data = $request->validated();
        $sendWelcomeEmail = (bool) ($data['send_welcome_email'] ?? true);
        unset($data['send_welcome_email']);

        $photoPath = $request->file('photo')->store('membership-photos', 'public');

        $membership = Membership::create([
            ...collect($data)->except(['photo'])->all(),
            'numero_membre' => $this->referenceGenerator->generate(),
            'photo_path' => $photoPath,
            'statut' => MembershipStatus::Validee->value,
            'source' => 'manuel',
            'validated_at' => now(),
            'validated_by' => $request->user()->id,
        ]);

        if ($sendWelcomeEmail) {
            // La carte n'est PAS générée ici : voir MembershipValidated, qui
            // la régénère lui-même au moment de l'envoi pour éviter de
            // faire transiter du PDF binaire par la file d'attente.
            Notification::route('mail', $membership->email)->notify(new MembershipValidated($membership));
        }

        return (new MembershipResource($membership))
            ->additional(['message' => 'Adhésion créée avec succès.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Seuls `statut` et `admin_note` sont modifiables. Le passage à
     * `validee` (et uniquement ce passage — pas si l'adhésion était déjà
     * validée) déclenche la génération de la carte + l'envoi de
     * MembershipValidated, verrouillé (`lockForUpdate`) pour éviter un
     * double-envoi en cas de double-clic, même logique que
     * ApplicationController::promote().
     */
    public function update(UpdateMembershipRequest $request, Membership $membership)
    {
        $this->authorize('update', $membership);

        $data = $request->validated();

        // La décision "est-ce une transition VERS validee" est prise à
        // l'intérieur de la transaction, sur la ligne verrouillée
        // (lockForUpdate) — pas sur la copie de $membership chargée avant
        // le verrou. Sans ça, deux requêtes concurrentes pourraient
        // toutes les deux lire l'ancien statut et toutes les deux décider
        // d'envoyer MembershipValidated (double email + double carte).
        $devientValidee = false;

        $membership = DB::transaction(function () use ($membership, $data, $request, &$devientValidee) {
            $locked = Membership::query()->whereKey($membership->id)->lockForUpdate()->firstOrFail();

            $devientValidee = $data['statut'] === MembershipStatus::Validee->value
                && $locked->statut !== MembershipStatus::Validee;

            $updates = $data;
            if ($devientValidee) {
                $updates['validated_at'] = now();
                $updates['validated_by'] = $request->user()->id;
            }

            $locked->update($updates);

            return $locked;
        });

        if ($devientValidee) {
            // Idem : pas de génération de carte ici, voir MembershipValidated.
            Notification::route('mail', $membership->email)->notify(new MembershipValidated($membership->fresh()));
        }

        return (new MembershipResource($membership->fresh()))
            ->additional(['message' => 'Adhésion mise à jour avec succès.']);
    }

    public function destroy(Membership $membership)
    {
        $this->authorize('delete', $membership);

        if ($membership->photo_path) {
            Storage::disk('public')->delete($membership->photo_path);
        }

        $membership->delete();

        return response()->json(['message' => 'Adhésion supprimée avec succès.']);
    }

    /**
     * Permet à l'admin de (re)télécharger la carte de membre PDF d'une
     * adhésion validée à tout moment (ex. pour impression), sans repasser
     * par l'envoi d'un email — la carte n'est jamais stockée sur disque,
     * elle est régénérée à la demande depuis les données de l'adhésion
     * (voir MembershipCardService).
     */
    public function downloadCard(Membership $membership)
    {
        $this->authorize('view', $membership);

        abort_unless($membership->statut === MembershipStatus::Validee, 409, "Cette adhésion n'est pas encore validée.");

        $pdf = $this->cardService->generate($membership);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"carte-membre-{$membership->numero_membre}.pdf\"",
        ]);
    }

    /**
     * Export CSV — données personnelles, endpoint admin-only (memberships.view).
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Membership::class);

        $memberships = Membership::query()
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where(function ($q) use ($request) {
                    $search = $request->string('search');
                    $q->where('nom_complet', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('numero_membre', 'like', "%{$search}%");
                })
            )
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('region'), fn ($q) => $q->where('region', $request->string('region')))
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->string('source')))
            ->orderByDesc('created_at')
            ->cursor();

        return $this->streamCsv(
            $memberships,
            ['Numéro membre', 'Nom complet', 'Email', 'Téléphone', 'Région', 'Département', 'Statut', 'Source', 'Validée le', 'Créée le'],
            fn (Membership $membership) => [
                $membership->numero_membre,
                $membership->nom_complet,
                $membership->email,
                $membership->telephone,
                $membership->region?->value,
                $membership->departement,
                $membership->statut->value,
                $membership->source,
                $membership->validated_at?->toDateString(),
                $membership->created_at->toDateString(),
            ],
            'adhesions'
        );
    }
}
