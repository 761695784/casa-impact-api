<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationCall;

/**
 * Détermine le statut initial d'une candidature au moment de sa soumission,
 * selon le quota `nombre_places` de l'appel — décision validée le
 * 2026-08-20 : liste d'attente plutôt que blocage 409 ou statut "complet"
 * (voir architecturev1.md §I, 3 options, "question bloquante").
 *
 * `nombre_places` null = illimité, toujours `Nouvelle`. Sinon, si le nombre
 * de candidatures qui "comptent" déjà (tout statut sauf en_liste_attente,
 * voir Application::scopeCountedTowardsCapacity()) a atteint le quota, la
 * nouvelle candidature est mise en liste d'attente plutôt que rejetée.
 *
 * L'appelant (Public\ApplicationController::store()) doit verrouiller la
 * ligne ApplicationCall (`lockForUpdate()`) AVANT d'appeler ce service, à
 * l'intérieur d'une transaction — sinon deux soumissions concurrentes
 * pourraient toutes les deux compter la même place disponible.
 */
class ApplicationCapacityChecker
{
    public function determineStatus(ApplicationCall $applicationCall): ApplicationStatus
    {
        if ($applicationCall->nombre_places === null) {
            return ApplicationStatus::Nouvelle;
        }

        $countedApplications = Application::query()
            ->where('application_call_id', $applicationCall->id)
            ->countedTowardsCapacity()
            ->count();

        return $countedApplications >= $applicationCall->nombre_places
            ? ApplicationStatus::EnListeAttente
            : ApplicationStatus::Nouvelle;
    }
}
