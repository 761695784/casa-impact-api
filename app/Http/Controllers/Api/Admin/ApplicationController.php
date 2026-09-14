<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateApplicationRequest;
use App\Http\Resources\Admin\ApplicationHistoryResource;
use App\Http\Resources\Admin\ApplicationResource;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ApplicationHistory;
use App\Notifications\ApplicationDecided;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Pas de store() : les candidatures ne sont créées que via la soumission
 * publique (voir Public\ApplicationController) — voir ApplicationPolicy.
 *
 * MIS À JOUR le 2026-08-24 : update() envoyait ApplicationDecided
 * automatiquement quand l'admin faisait passer une candidature à un statut
 * de DÉCISION (Retenue / NonRetenue / EnListeAttente).
 *
 * REVU le 2026-09-14 (2e accord explicite) : cet envoi AUTOMATIQUE a été
 * RETIRÉ — demande explicite de l'utilisateur : "on etudie, on voit si
 * c'est bon, on fait retenue mais on envoie pas encore de mail... si on
 * finit de choisir on envoie les séries d'emails par statut". Changer le
 * statut d'une candidature n'envoie donc plus rien tout seul ; l'email
 * part uniquement quand l'admin le décide explicitement, de deux façons :
 *   1. notify() : bouton "Envoyer" dans la modale d'une candidature —
 *      envoie l'email du statut ACTUEL de CETTE candidature, à tout moment.
 *   2. notifyPending() : bouton "Envoyer les emails en attente" — envoie en
 *      une fois l'email à TOUTES les candidatures (d'un appel donné ou de
 *      tous) dont le statut actuel n'a pas encore été notifié, une fois que
 *      l'admin a fini de trancher chaque dossier.
 * Chaque changement de statut ET chaque email réellement envoyé (les deux
 * cas ci-dessus) est journalisé dans application_history — historique
 * voulu pour présenter aux partenaires ce qui a été fait (history() /
 * exportHistory() plus bas).
 */
class ApplicationController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Application::class);

        $perPage = min((int) $request->integer('per_page', 15), 100);

        $applications = Application::query()
            ->with('applicationCall')
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where(function ($q) use ($request) {
                    $search = $request->string('search');
                    $q->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%");
                })
            )
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('region'), fn ($q) => $q->where('region', $request->string('region')))
            ->when($request->filled('application_call_id'), fn ($q) => $q->where('application_call_id', $request->integer('application_call_id')))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return ApplicationResource::collection($applications);
    }

    public function show(Application $application)
    {
        $this->authorize('view', $application);

        return new ApplicationResource($application->load(['applicationCall', 'documents', 'history.user']));
    }

    /**
     * Seul le statut est modifiable (voir UpdateApplicationRequest) — les
     * données saisies par le candidat ne sont jamais éditées par l'admin.
     *
     * N'ENVOIE PLUS AUCUN EMAIL depuis le 2026-09-14 (voir docblock de
     * classe) — uniquement le changement de statut, journalisé dans
     * application_history. L'email se déclenche séparément et
     * explicitement via notify() ou notifyPending().
     */
    public function update(UpdateApplicationRequest $request, Application $application)
    {
        $this->authorize('update', $application);

        $data = $request->validated();
        $nouveauStatut = ApplicationStatus::from($data['statut']);
        $ancienStatut = $application->statut;

        $application = DB::transaction(function () use ($application, $data) {
            $locked = Application::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
            $locked->update($data);

            return $locked;
        });

        if ($ancienStatut !== $nouveauStatut) {
            ApplicationHistory::create([
                'application_id' => $application->id,
                'type' => 'statut_change',
                'ancien_statut' => $ancienStatut->value,
                'nouveau_statut' => $nouveauStatut->value,
                'user_id' => $request->user()?->id,
            ]);
        }

        return (new ApplicationResource($application->fresh()->load(['applicationCall', 'documents', 'history.user'])))
            ->additional(['message' => 'Statut de la candidature mis à jour avec succès.']);
    }

    /**
     * Envoi MANUEL de l'email contextuel du statut ACTUEL de la
     * candidature (bouton dédié côté admin, accord explicite du
     * 2026-09-14 : "un bouton d'envoi de mail pour chaque statut... au
     * click du bouton envoyer un mail"). Contrairement à l'envoi
     * automatique de update(), fonctionne pour N'IMPORTE QUEL statut
     * (Nouvelle/EnCoursEtude/Preselectionnee compris, pas seulement les 3
     * décisions) et peut être déclenché à tout moment, indépendamment d'un
     * changement de statut — utile pour renvoyer un email, ou notifier un
     * statut intermédiaire. Toujours journalisé (application_history,
     * type email_envoye).
     */
    public function notify(Request $request, Application $application)
    {
        $this->authorize('update', $application);

        $application = $application->fresh()->load('applicationCall');

        Notification::route('mail', $application->email)->notify(new ApplicationDecided($application));

        [$sujet] = ApplicationDecided::subjectAndView($application->statut);
        ApplicationHistory::create([
            'application_id' => $application->id,
            'type' => 'email_envoye',
            'nouveau_statut' => $application->statut->value,
            'sujet_email' => $sujet,
            'user_id' => $request->user()?->id,
        ]);

        return (new ApplicationResource($application->load(['documents', 'history.user'])))
            ->additional(['message' => 'Email envoyé avec succès.']);
    }

    /**
     * Une candidature "attend" une notification si aucun email n'a encore
     * été envoyé pour son statut ACTUEL — soit qu'aucun email n'ait jamais
     * été envoyé, soit que le dernier envoi corresponde à un statut
     * différent (le statut a changé depuis). `history` doit être
     * eager-loadée (déjà triée par date décroissante, voir
     * Application::history()) pour éviter une requête par candidature.
     */
    private function needsNotification(Application $application): bool
    {
        $dernierEmail = $application->history->firstWhere('type', 'email_envoye');

        return ! $dernierEmail || $dernierEmail->nouveau_statut !== $application->statut->value;
    }

    /**
     * Nombre de candidatures en attente d'un email (voir needsNotification),
     * groupé par statut — alimente le badge du bouton "Envoyer les emails en
     * attente" côté admin. `application_call_id` optionnel : sans lui,
     * compte sur TOUTES les candidatures (tous appels confondus).
     */
    public function pendingNotificationsCount(Request $request)
    {
        $this->authorize('viewAny', Application::class);

        $applicationCallId = $request->integer('application_call_id') ?: null;

        $pending = Application::query()
            ->when($applicationCallId, fn ($q) => $q->where('application_call_id', $applicationCallId))
            ->with('history')
            ->get()
            ->filter(fn (Application $a) => $this->needsNotification($a));

        return response()->json([
            'total' => $pending->count(),
            'par_statut' => $pending->groupBy(fn (Application $a) => $a->statut->value)->map->count(),
        ]);
    }

    /**
     * Envoi GROUPÉ — "les séries d'emails par statut" une fois que l'admin a
     * fini de trancher (accord explicite du 2026-09-14). Envoie l'email
     * contextuel à toutes les candidatures en attente (voir
     * needsNotification), scopé à un appel si `application_call_id` est
     * fourni, sinon à toutes. Chaque envoi est journalisé individuellement,
     * exactement comme notify() — un envoi groupé n'est qu'une boucle
     * d'envois manuels.
     */
    public function notifyPending(Request $request)
    {
        $this->authorize('viewAny', Application::class);

        $applicationCallId = $request->integer('application_call_id') ?: null;

        $aNotifier = Application::query()
            ->when($applicationCallId, fn ($q) => $q->where('application_call_id', $applicationCallId))
            ->with(['history', 'applicationCall'])
            ->get()
            ->filter(fn (Application $a) => $this->needsNotification($a));

        $parStatut = [];

        foreach ($aNotifier as $application) {
            // Correctif du 2026-09-14 (bug constaté en prod : un email
            // "Retenue" journalisé comme envoyé mais jamais reçu) : la
            // relation `history` a été eager-loadée juste au-dessus pour
            // needsNotification(), mais ApplicationDecided n'en a pas
            // besoin (seul `applicationCall` compte, rechargé dans
            // toMail()). Or Laravel capture TOUTES les relations chargées
            // sur un modèle passé à une Notification ShouldQueue pour les
            // restaurer côté worker via loadMissing() — si le worker tourne
            // sur un ancien code qui ne connaît pas encore cette relation
            // (process persistant non redémarré après un déploiement), le
            // job échoue silencieusement à ce moment-là (jamais d'exception
            // ici côté contrôleur, donc la journalisation ci-dessous se
            // fait quand même). On retire la relation avant de construire
            // la notification pour ne pas transporter plus que nécessaire.
            $application->unsetRelation('history');

            Notification::route('mail', $application->email)->notify(new ApplicationDecided($application));

            [$sujet] = ApplicationDecided::subjectAndView($application->statut);
            ApplicationHistory::create([
                'application_id' => $application->id,
                'type' => 'email_envoye',
                'nouveau_statut' => $application->statut->value,
                'sujet_email' => $sujet,
                'user_id' => $request->user()?->id,
            ]);

            $cle = $application->statut->value;
            $parStatut[$cle] = ($parStatut[$cle] ?? 0) + 1;
        }

        return response()->json([
            'message' => $aNotifier->isEmpty()
                ? 'Aucun email en attente à envoyer.'
                : "{$aNotifier->count()} email(s) envoyé(s) avec succès.",
            'total' => $aNotifier->count(),
            'par_statut' => $parStatut,
        ]);
    }

    public function destroy(Application $application)
    {
        $this->authorize('delete', $application);

        $application->delete();

        return response()->json(['message' => 'Candidature supprimée avec succès.']);
    }

    /**
     * Fait sortir une candidature de la liste d'attente en un clic (demande
     * explicite du 2026-08-20) : bascule son statut à `Nouvelle`, sans
     * repasser par l'update générique où il faudrait connaître/retaper la
     * valeur exacte de l'enum. C'est une décision manuelle et volontaire de
     * l'admin — le service ne revérifie PAS le quota `nombre_places` de
     * l'appel : l'action même de promouvoir signifie "j'ajoute une place
     * de plus", pas "vérifie s'il en reste".
     *
     * `lockForUpdate` protège uniquement contre un double-clic qui
     * déclencherait deux requêtes de promotion simultanées sur la même
     * candidature (idempotence), pas contre un dépassement de quota — ce
     * n'est pas la même garantie que celle d'ApplicationCapacityChecker à
     * la soumission.
     *
     * `Nouvelle` n'est pas un statut de décision (voir STATUTS_DECISION) :
     * pas d'email envoyé ici, cohérent avec le fait que promote() est un
     * geste administratif interne, pas une décision communiquée au
     * candidat de la même façon qu'un valider/refuser/liste d'attente.
     */
    public function promote(Application $application)
    {
        $this->authorize('update', $application);

        $application = DB::transaction(function () use ($application) {
            $locked = Application::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();

            if ($locked->statut !== ApplicationStatus::EnListeAttente) {
                abort(409, "Cette candidature n'est pas en liste d'attente.");
            }

            $locked->update(['statut' => ApplicationStatus::Nouvelle->value]);

            return $locked;
        });

        return (new ApplicationResource($application->fresh()->load(['applicationCall', 'documents'])))
            ->additional(['message' => "Candidature sortie de la liste d'attente avec succès."]);
    }

    public function downloadDocument(Application $application, ApplicationDocument $document)
    {
        $this->authorize('view', $application);

        abort_unless($document->application_id === $application->id, 404);

        return Storage::disk('local')->download($document->chemin, $document->nom_original);
    }

    /**
     * Export CSV — même filtrage que index(). Contient des données
     * personnelles (nom, email, téléphone) : endpoint admin-only, protégé
     * par la même permission que la liste (`applications.view`), jamais
     * exposé publiquement — cohérent avec le traitement du reste du module
     * (pas de Resource publique pour Application).
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Application::class);

        $applications = Application::query()
            ->with('applicationCall')
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where(function ($q) use ($request) {
                    $search = $request->string('search');
                    $q->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%");
                })
            )
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('region'), fn ($q) => $q->where('region', $request->string('region')))
            ->when($request->filled('application_call_id'), fn ($q) => $q->where('application_call_id', $request->integer('application_call_id')))
            ->orderByDesc('created_at')
            ->cursor();

        return $this->streamCsv(
            $applications,
            ['Référence', 'Nom', 'Prénom', 'Email', 'Téléphone', 'Région', 'Tranche d\'âge', 'Niveau d\'études', 'Statut', 'Appel à candidatures', 'Soumise le'],
            fn (Application $application) => [
                $application->reference,
                $application->nom,
                $application->prenom,
                $application->email,
                $application->telephone,
                $application->region?->value,
                $application->tranche_age,
                $application->niveau_etudes,
                $application->statut->value,
                $application->applicationCall?->titre,
                $application->created_at->toDateString(),
            ],
            'candidatures'
        );
    }

    /**
     * Historique paginé (changements de statut + emails envoyés), toutes
     * candidatures confondues — pour la page admin dédiée (accord du
     * 2026-09-14 : "une page ou un export pour l'historique"). Filtrable par
     * appel et/ou par type d'événement.
     */
    public function history(Request $request)
    {
        $this->authorize('viewAny', Application::class);

        $perPage = min((int) $request->integer('per_page', 20), 100);

        $history = ApplicationHistory::query()
            ->with(['application.applicationCall', 'user'])
            ->when(
                $request->filled('application_call_id'),
                fn ($q) => $q->whereHas(
                    'application',
                    fn ($q2) => $q2->where('application_call_id', $request->integer('application_call_id'))
                )
            )
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return ApplicationHistoryResource::collection($history);
    }

    /**
     * Export CSV de l'historique — même filtrage que history(), pensé pour
     * être partagé tel quel avec un partenaire (accord du 2026-09-14).
     */
    public function exportHistory(Request $request)
    {
        $this->authorize('viewAny', Application::class);

        $history = ApplicationHistory::query()
            ->with(['application.applicationCall', 'user'])
            ->when(
                $request->filled('application_call_id'),
                fn ($q) => $q->whereHas(
                    'application',
                    fn ($q2) => $q2->where('application_call_id', $request->integer('application_call_id'))
                )
            )
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderByDesc('created_at')
            ->cursor();

        return $this->streamCsv(
            $history,
            ['Date', 'Type', 'Candidat', 'Référence', 'Appel à candidatures', 'Ancien statut', 'Nouveau statut', 'Sujet email', 'Effectué par'],
            fn (ApplicationHistory $h) => [
                $h->created_at->format('Y-m-d H:i'),
                $h->type === 'email_envoye' ? 'Email envoyé' : 'Changement de statut',
                trim(($h->application->prenom ?? '') . ' ' . ($h->application->nom ?? '')),
                $h->application->reference ?? '',
                $h->application->applicationCall?->titre ?? '',
                $h->ancien_statut,
                $h->nouveau_statut,
                $h->sujet_email,
                $h->user?->name ?? 'Système',
            ],
            'historique-candidatures'
        );
    }
}
