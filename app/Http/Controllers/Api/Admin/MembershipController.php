<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ContributionDomain;
use App\Enums\ContributionType;
use App\Enums\MembershipRegion;
use App\Enums\MembershipStatus;
use App\Http\Controllers\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMembershipManualRequest;
use App\Http\Requests\Admin\UpdateMembershipRequest;
use App\Http\Resources\Admin\MembershipResource;
use App\Mail\MembershipPaymentReminder;
use App\Models\Membership;
use App\Notifications\MembershipValidated;
use App\Services\MembershipCardService;
use App\Services\MembershipReferenceGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
            // Tri "plus récent au plus ancien" (accord du 2026-09-14) : trier
            // uniquement par created_at ne suffit pas dès que plusieurs
            // membres partagent EXACTEMENT le même created_at (cas courant
            // d'un import Excel en masse, où beaucoup de lignes reçoivent le
            // même horodatage à la seconde près) — dans ce cas MySQL ne
            // garantit AUCUN ordre pour les lignes ex æquo, ce qui pouvait
            // donner l'impression d'un tri quasi aléatoire/croissant plutôt
            // que du plus récent au plus ancien. `id` étant strictement
            // croissant à l'insertion et jamais ex æquo, il sert de
            // départage déterministe : entre deux membres créés à la même
            // seconde, celui inséré en dernier (id le plus grand) apparaît
            // en premier.
            ->orderByDesc('created_at')
            ->orderByDesc('id')
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
     * (défaut false — accord explicite du 2026-09-11, un ajout manuel
     * concerne presque toujours un adhérent historique) permet de sauter
     * l'email de bienvenue "vous venez de rejoindre Casa Impact aujourd'hui"
     * — voir StoreMembershipManualRequest. `numero_membre` (optionnel,
     * accord du 2026-09-11) : si fourni (membre ayant déjà une carte
     * imprimée), conservé tel quel après vérification d'unicité par la
     * FormRequest ; sinon généré comme avant via
     * MembershipReferenceGenerator. La carte de membre est toujours
     * générée (accessible ensuite via downloadCard()), qu'un email soit
     * envoyé ou non.
     */
    public function store(StoreMembershipManualRequest $request)
    {
        $this->authorize('create', Membership::class);

        $data = $request->validated();
        $sendWelcomeEmail = (bool) ($data['send_welcome_email'] ?? false);
        unset($data['send_welcome_email']);

        $numeroMembre = $data['numero_membre'] ?? null;
        unset($data['numero_membre']);

        $photoPath = $request->file('photo')->store('membership-photos', 'public');

        $membership = Membership::create([
            ...collect($data)->except(['photo'])->all(),
            'numero_membre' => $numeroMembre ?: $this->referenceGenerator->generate(),
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

    /**
     * Ajoute ou remplace la photo d'un membre déjà existant — pensé pour
     * les membres importés depuis l'historique Excel (`source =
     * import_excel`), créés sans photo (seulement un lien Google Drive
     * dans le fichier source, jamais téléchargé automatiquement — accord
     * du 2026-09-11). Peut aussi remplacer la photo de n'importe quel
     * autre membre (même règle d'autorisation que update()). L'ancienne
     * photo, si elle existe, est supprimée du disque pour éviter
     * d'accumuler des fichiers orphelins.
     */
    public function updatePhoto(Request $request, Membership $membership)
    {
        $this->authorize('update', $membership);

        $request->validate([
            'photo' => ['required', 'image', 'max:10240'],
        ]);

        if ($membership->photo_path) {
            Storage::disk('public')->delete($membership->photo_path);
        }

        $photoPath = $request->file('photo')->store('membership-photos', 'public');
        $membership->update(['photo_path' => $photoPath]);

        return (new MembershipResource($membership->fresh()))
            ->additional(['message' => 'Photo mise à jour avec succès.']);
    }

    /**
     * Bouton "Envoyer un rappel de paiement" de la page admin Adhésions
     * (accord du 2026-09-11) — relance par email tous les membres dont
     * l'adhésion est encore `en_attente_paiement` (ils n'ont pas finalisé :
     * paiement Wave pas encore reçu/vérifié), avec les mêmes instructions
     * de paiement que l'email de confirmation initial (MembershipReceived),
     * pour qu'ils n'aient pas à retrouver ce premier email.
     *
     * `membership_ids` est optionnel : si fourni, ne relance QUE ces
     * membres précis (utile pour un futur bouton "rappel" ligne par ligne)
     * — s'il est absent ou vide, cible TOUS les membres en attente de
     * paiement (c'est le comportement du bouton actuel côté admin). Dans
     * les deux cas, seuls les membres réellement `en_attente_paiement`
     * sont retenus : impossible de relancer par erreur un membre déjà
     * validé ou refusé, même en passant son id explicitement.
     */
    public function sendPaymentReminders(Request $request)
    {
        $this->authorize('viewAny', Membership::class);

        $validated = $request->validate([
            'membership_ids' => ['sometimes', 'array'],
            'membership_ids.*' => ['integer'],
        ]);

        $memberships = Membership::query()
            ->where('statut', MembershipStatus::EnAttentePaiement->value)
            ->when(
                !empty($validated['membership_ids']),
                fn ($q) => $q->whereIn('id', $validated['membership_ids'])
            )
            ->get();

        if ($memberships->isEmpty()) {
            return response()->json([
                'message' => 'Aucun membre en attente de paiement à relancer.',
                'sent' => 0,
                'failed' => [],
            ]);
        }

        $failed = [];
        foreach ($memberships as $membership) {
            try {
                Mail::to($membership->email)->send(new MembershipPaymentReminder($membership));
            } catch (\Throwable $e) {
                $failed[] = $membership->numero_membre;
            }
        }

        $sent = $memberships->count() - count($failed);

        return response()->json([
            'message' => count($failed) === 0
                ? "Rappel envoyé avec succès à {$sent} membre(s)."
                : "Rappel envoyé à {$sent} membre(s), " . count($failed) . ' échec(s).',
            'sent' => $sent,
            'failed' => $failed,
        ]);
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
            ->orderByDesc('id')
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

    /**
     * Export PDF "pro" de la liste des adhérents (accord du 2026-09-16, en
     * complément de l'export CSV ci-dessus) — mêmes filtres exacts
     * (search/statut/region/source, appliqués via les mêmes query params
     * que index()/export()), mais rendu paysage avec la charte graphique
     * (logo, couleurs, filigrane — voir resources/views/pdf/
     * memberships-list.blade.php) via DomPDF, déjà utilisé par
     * MembershipCardService pour les cartes de membre. Pas de curseur
     * mémoire-minimal ici (contrairement à export()) : le rendu HTML du
     * PDF a besoin de la collection complète, ce qui reste largement
     * raisonnable pour un volume associatif.
     */
    public function exportPdf(Request $request)
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
            ->orderByDesc('id')
            ->get();

        // Libellés français alignés sur types/enums.ts côté front (mêmes
        // valeurs affichées dans le panneau admin), pour que le PDF parle
        // le même langage que l'écran d'où il est généré.
        $statusLabels = [
            MembershipStatus::EnAttentePaiement->value => 'En attente de paiement',
            MembershipStatus::Validee->value => 'Validée',
            MembershipStatus::Refusee->value => 'Refusée',
        ];
        $regionLabels = [
            MembershipRegion::Ziguinchor->value => 'Ziguinchor',
            MembershipRegion::Sedhiou->value => 'Sédhiou',
            MembershipRegion::Kolda->value => 'Kolda',
            MembershipRegion::Dakar->value => 'Dakar',
            MembershipRegion::Diaspora->value => 'Diaspora',
        ];
        $contributionTypeLabels = [
            ContributionType::MembreActif->value => 'Membre actif',
            ContributionType::BenevolePonctuel->value => 'Bénévole ponctuel',
            ContributionType::ExpertConseillerTechnique->value => 'Expert / Conseiller technique',
        ];
        $contributionDomainLabels = [
            ContributionDomain::PoleCapitalHumain->value => 'Pôle Capital Humain',
            ContributionDomain::PoleEconomieAgricultureAttractivite->value => 'Pôle Économie, Agriculture & Attractivité',
            ContributionDomain::PoleCultureCommunication->value => 'Pôle Culture & Communication',
            ContributionDomain::PoleSupport->value => 'Pôle Support',
            ContributionDomain::CommissionScientifique->value => 'Commission scientifique',
            ContributionDomain::CoordinationRegionale->value => 'Coordination régionale',
            ContributionDomain::ComiteDesSages->value => 'Comité des Sages',
        ];

        // Titre du document déterminé par le filtre `statut` (accord du
        // 2026-09-16 : "un titre comme liste officielle des membres
        // (validées)... de même que la liste des non validés") — le PDF
        // doit annoncer clairement QUEL sous-ensemble il contient, pas
        // juste "liste des adhérents" pour tout et n'importe quoi.
        $title = match ($request->filled('statut') ? $request->string('statut')->toString() : null) {
            MembershipStatus::Validee->value => 'Liste officielle des membres validés',
            MembershipStatus::EnAttentePaiement->value => 'Liste des adhérents en attente de paiement',
            MembershipStatus::Refusee->value => 'Liste des demandes refusées / sans suite',
            default => 'Liste officielle des adhérents',
        };

        // Filtres secondaires (recherche/région) affichés sous le titre —
        // le statut n'y figure plus puisqu'il est déjà porté par le titre
        // lui-même.
        $filterParts = [];
        if ($request->filled('search')) {
            $filterParts[] = 'Recherche : "'.$request->string('search').'"';
        }
        if ($request->filled('region')) {
            $filterParts[] = 'Région : '.($regionLabels[$request->string('region')->toString()] ?? $request->string('region'));
        }
        $filterSummary = empty($filterParts) ? null : implode(' — ', $filterParts);

        $pdf = Pdf::loadView('pdf.memberships-list', [
            'memberships' => $memberships,
            'title' => $title,
            'generatedAt' => now(),
            'filterSummary' => $filterSummary,
            'statusLabels' => $statusLabels,
            'regionLabels' => $regionLabels,
            'contributionTypeLabels' => $contributionTypeLabels,
            'contributionDomainLabels' => $contributionDomainLabels,
        ]);
        $pdf->setPaper('a4', 'landscape');

        $filename = sprintf('adherents-casa-impact-%s.pdf', now()->format('Y-m-d-His'));

        // Cache-Control explicite (accord implicite : un PDF avec des
        // données personnelles ne doit de toute façon jamais être caché) —
        // ajouté le 2026-09-16 après un cas où le rendu semblait "figé" sur
        // une ancienne version malgré un déploiement backend confirmé
        // (code à jour, tous les caches Laravel vidés) : suspicion que le
        // proxy/CDN de Hostinger devant api.casaimpact.org (déjà identifié
        // comme bloquant certaines requêtes POST avec fichier — voir
        // MembershipImportController) mette aussi en cache cette réponse
        // GET par URL, sans tenir compte du cookie de session. Sans effet
        // si ce n'était pas la cause, mais nécessaire dans tous les cas.
        return $pdf->download($filename)->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            'Pragma' => 'no-cache',
        ]);
    }
}
