<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationCall;
use App\Models\ContactMessage;
use App\Models\ImpactIndicator;
use App\Models\News;
use App\Models\Partner;
use App\Models\Program;
use App\Models\Talent;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Module 14 (Dashboard) — agrège des compteurs simples issus de tous les
 * modules de contenu déjà construits, en une seule requête par table
 * (`count`/`selectRaw` groupés) plutôt que de charger des collections
 * complètes en mémoire. Volontairement un simple service PHP (pas de
 * package de reporting) : les besoins actuels sont des compteurs, pas des
 * agrégations complexes multi-dimensionnelles (voir architecturev1.md §L,
 * qui ne demande explicitement qu'une "vue d'ensemble chiffrée").
 */
class DashboardStatsService
{
    public function stats(): array
    {
        return [
            'programmes' => [
                'total' => Program::query()->count(),
                'publies' => Program::query()->published()->count(),
            ],
            'appels_a_candidatures' => [
                'total' => ApplicationCall::query()->count(),
                'publies' => ApplicationCall::query()->published()->count(),
            ],
            'candidatures' => [
                'total' => Application::query()->count(),
                // Requête sur le query builder brut (DB::table) plutôt que
                // sur Eloquent : évite toute ambiguïté sur le cast enum de
                // `statut` lors d'un groupBy — on veut ici les valeurs
                // scalaires brutes de la colonne.
                'par_statut' => DB::table('applications')
                    ->select('statut', DB::raw('count(*) as total'))
                    ->groupBy('statut')
                    ->pluck('total', 'statut'),
                'en_liste_attente' => Application::query()->where('statut', ApplicationStatus::EnListeAttente->value)->count(),
            ],
            'actualites' => [
                'total' => News::query()->count(),
                'publiees' => News::query()->published()->count(),
            ],
            'talents' => [
                'total' => Talent::query()->count(),
                'publies' => Talent::query()->published()->count(),
            ],
            'temoignages' => [
                'total' => Testimonial::query()->count(),
                'publies' => Testimonial::query()->published()->count(),
            ],
            'partenaires' => [
                'total' => Partner::query()->count(),
                'actifs' => Partner::query()->active()->count(),
            ],
            'messages_contact' => [
                'total' => ContactMessage::query()->count(),
                'nouveaux' => ContactMessage::query()->where('statut', 'nouveau')->count(),
            ],
            'indicateurs_impact' => [
                'total' => ImpactIndicator::query()->count(),
            ],
            'utilisateurs' => [
                'total' => User::query()->count(),
            ],
        ];
    }
}
