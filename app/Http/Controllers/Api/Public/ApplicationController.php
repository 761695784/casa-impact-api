<?php

namespace App\Http\Controllers\Api\Public;

use Dedoc\Scramble\Attributes\Group;
use App\Enums\ApplicationCallStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreApplicationRequest;
use App\Http\Resources\Public\ApplicationConfirmationResource;
use App\Models\Application;
use App\Models\ApplicationCall;
use App\Models\ApplicationDocument;
use App\Notifications\ApplicationSubmitted;
use App\Services\ApplicationCapacityChecker;
use App\Services\ApplicationReferenceGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

#[Group('Candidatures — Public')]
class ApplicationController extends Controller
{
    public function __construct(
        private ApplicationReferenceGenerator $referenceGenerator,
        private ApplicationCapacityChecker $capacityChecker,
    ) {
    }

    /**
     * Aucune authentification requise. Toute la logique métier sensible à
     * la concurrence (comptage des places, génération de référence) tourne
     * dans une seule transaction avec la ligne ApplicationCall verrouillée
     * (`lockForUpdate`) — sans ça, deux soumissions simultanées pourraient
     * toutes les deux se croire éligibles pour la dernière place.
     */
    public function store(StoreApplicationRequest $request)
    {
        $data = $request->validated();

        $application = DB::transaction(function () use ($data, $request) {
            $applicationCall = ApplicationCall::query()
                ->where('id', $data['application_call_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($applicationCall->statut !== ApplicationCallStatus::Publie) {
                abort(404);
            }

            if ($applicationCall->date_limite && now()->startOfDay()->gt($applicationCall->date_limite)) {
                abort(409, 'La date limite de dépôt des candidatures pour cet appel est dépassée.');
            }

            $statut = $this->capacityChecker->determineStatus($applicationCall);
            $reference = $this->referenceGenerator->generate();

            $application = Application::create([
                ...collect($data)->except(['documents'])->all(),
                'reference' => $reference,
                'statut' => $statut->value,
            ]);

            foreach ($request->file('documents', []) as $type => $file) {
                $chemin = $file->store("application-documents/{$application->id}", 'local');

                ApplicationDocument::create([
                    'application_id' => $application->id,
                    'type' => $type,
                    'chemin' => $chemin,
                    'nom_original' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                    'taille' => $file->getSize(),
                ]);
            }

            return $application;
        });

        Notification::route('mail', $application->email)->notify(new ApplicationSubmitted($application));

        return (new ApplicationConfirmationResource($application))
            ->additional(['message' => 'Candidature soumise avec succès.'])
            ->response()
            ->setStatusCode(201);
    }
}
