<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateApplicationRequest;
use App\Http\Resources\Admin\ApplicationResource;
use App\Models\Application;
use App\Models\ApplicationDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Pas de store() : les candidatures ne sont créées que via la soumission
 * publique (voir Public\ApplicationController) — voir ApplicationPolicy.
 */
class ApplicationController extends Controller
{
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

        return new ApplicationResource($application->load(['applicationCall', 'documents']));
    }

    /**
     * Seul le statut est modifiable (voir UpdateApplicationRequest) — les
     * données saisies par le candidat ne sont jamais éditées par l'admin.
     */
    public function update(UpdateApplicationRequest $request, Application $application)
    {
        $this->authorize('update', $application);

        $application->update($request->validated());

        return (new ApplicationResource($application->fresh()->load(['applicationCall', 'documents'])))
            ->additional(['message' => 'Statut de la candidature mis à jour avec succès.']);
    }

    public function destroy(Application $application)
    {
        $this->authorize('delete', $application);

        $application->delete();

        return response()->json(['message' => 'Candidature supprimée avec succès.']);
    }

    public function downloadDocument(Application $application, ApplicationDocument $document)
    {
        $this->authorize('view', $application);

        abort_unless($document->application_id === $application->id, 404);

        return Storage::disk('local')->download($document->chemin, $document->nom_original);
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


}
