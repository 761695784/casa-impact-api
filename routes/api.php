<?php

use App\Http\Controllers\Api\Admin\ApplicationCallController as AdminApplicationCallController;
use App\Http\Controllers\Api\Admin\ApplicationController as AdminApplicationController;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\DomainController as AdminDomainController;
use App\Http\Controllers\Api\Admin\ImpactIndicatorController as AdminImpactIndicatorController;
use App\Http\Controllers\Api\Admin\ImpactValueController as AdminImpactValueController;
use App\Http\Controllers\Api\Admin\LocationController as AdminLocationController;
use App\Http\Controllers\Api\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Api\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Api\Admin\PageController as AdminPageController;
use App\Http\Controllers\Api\Admin\PartnerController as AdminPartnerController;
use App\Http\Controllers\Api\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Api\Admin\ProgramTypeController as AdminProgramTypeController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\TalentController as AdminTalentController;
use App\Http\Controllers\Api\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Public\ApplicationCallController as PublicApplicationCallController;
use App\Http\Controllers\Api\Public\ApplicationController as PublicApplicationController;
use App\Http\Controllers\Api\Public\ContactMessageController as PublicContactMessageController;
use App\Http\Controllers\Api\Public\DomainController as PublicDomainController;
use App\Http\Controllers\Api\Public\ImpactIndicatorController as PublicImpactIndicatorController;
use App\Http\Controllers\Api\Public\MapController;
use App\Http\Controllers\Api\Public\NewsController as PublicNewsController;
use App\Http\Controllers\Api\Public\PageController as PublicPageController;
use App\Http\Controllers\Api\Public\PartnerController as PublicPartnerController;
use App\Http\Controllers\Api\Public\ProgramController as PublicProgramController;
use App\Http\Controllers\Api\Public\ProgramTypeController as PublicProgramTypeController;
use App\Http\Controllers\Api\Public\TalentController as PublicTalentController;
use App\Http\Controllers\Api\Public\TestimonialController as PublicTestimonialController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Casa Impact
|--------------------------------------------------------------------------
| Convention (architecturev1.md §F, décision #8) : pas de préfixe de
| version. Deux familles de routes :
|   - /api/admin/*   : authentifiées (Sanctum SPA), équipe Casa Impact
|   - /api/public/*  : sans authentification
|
| Modules 1 à 16 fusionnés ici (Administration, Pages/Domaines, Programmes,
| Appels à candidatures, Candidatures, Actualités, Médiathèque,
| Cartographie, Talents, Témoignages, Partenaires, Impact, Dashboard,
| Contact). Module 17 (tests/sécurisation/doc finale) ne modifie pas ce
| fichier.
|
| IMPORTANT — ordre des routes : les routes littérales ('export', 'values',
| 'contact', etc.) sont déclarées AVANT tout apiResource() dont le
| paramètre {model} pourrait autrement tenter de résoudre ce même segment
| comme un identifiant. C'est pourquoi chaque export()/nested-route
| ci-dessous précède son apiResource() correspondant.
*/

Route::prefix('admin')->name('admin.')->group(function () {
    // --- Authentification (non protégée par auth:sanctum, évidemment) ---
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1') // 6 tentatives / minute — anti brute-force
        ->name('login');

    // --- Routes protégées ---
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');

        Route::apiResource('users', UserController::class);
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');

        Route::apiResource('pages', AdminPageController::class);

        // Pas d'apiResource ici : domaines = référentiel fixe, seulement
        // index/show/update exposés (voir DomainController, DomainPolicy).
        Route::get('domains', [AdminDomainController::class, 'index'])->name('domains.index');
        Route::get('domains/{domain}', [AdminDomainController::class, 'show'])->name('domains.show');
        Route::put('domains/{domain}', [AdminDomainController::class, 'update'])->name('domains.update');

        // Programmes : CRUD complet + export CSV.
        Route::get('programs/export', [AdminProgramController::class, 'export'])->name('programs.export');
        Route::apiResource('programs', AdminProgramController::class);

        // Types de programme : table éditable — CRUD complet.
        Route::apiResource('program-types', AdminProgramTypeController::class);

        // Appels à candidatures : CRUD complet + export CSV.
        Route::get('application-calls/export', [AdminApplicationCallController::class, 'export'])->name('application-calls.export');
        Route::apiResource('application-calls', AdminApplicationCallController::class);

        // Candidatures : pas de store() (soumission publique uniquement) + export CSV.
        Route::get('applications/export', [AdminApplicationController::class, 'export'])->name('applications.export');
        Route::apiResource('applications', AdminApplicationController::class)
            ->only(['index', 'show', 'update', 'destroy']);
        Route::get('applications/{application}/documents/{document}/download', [AdminApplicationController::class, 'downloadDocument'])
            ->name('applications.documents.download');
        // Sortie de liste d'attente en un clic.
        Route::post('applications/{application}/promote', [AdminApplicationController::class, 'promote'])
            ->name('applications.promote');

        // Actualités : CRUD complet + export CSV.
        Route::get('news/export', [AdminNewsController::class, 'export'])->name('news.export');
        Route::apiResource('news', AdminNewsController::class);

        // Médiathèque (Module 12, refondue le 2026-09-10 en bibliothèque
        // partagée réutilisable) — index/show listent la bibliothèque,
        // store upload (avec attachement immédiat optionnel), update ne
        // touche qu'aux métadonnées, destroy supprime partout, attach/detach
        // gèrent le rattachement à une fiche indépendamment du fichier lui
        // -même. Voir MediaController::MEDIABLE_MAP pour la liste des types
        // de fiches autorisés.
        Route::get('media', [AdminMediaController::class, 'index'])->name('media.index');
        Route::get('media/{media}', [AdminMediaController::class, 'show'])->name('media.show');
        Route::post('media', [AdminMediaController::class, 'store'])->name('media.store');
        Route::put('media/{media}', [AdminMediaController::class, 'update'])->name('media.update');
        Route::delete('media/{media}', [AdminMediaController::class, 'destroy'])->name('media.destroy');
        Route::post('media/{media}/attach', [AdminMediaController::class, 'attach'])->name('media.attach');
        Route::delete('media/{media}/detach', [AdminMediaController::class, 'detach'])->name('media.detach');

        // Cartographie (Module 15) — un seul upsert idempotent, voir
        // LocationController::LOCATABLE_MAP pour la liste des types autorisés.
        Route::put('locations', [AdminLocationController::class, 'upsert'])->name('locations.upsert');
        Route::delete('locations/{location}', [AdminLocationController::class, 'destroy'])->name('locations.destroy');

        // Talents / Success Stories (Module 9) : CRUD complet + export CSV.
        Route::get('talents/export', [AdminTalentController::class, 'export'])->name('talents.export');
        Route::apiResource('talents', AdminTalentController::class);

        // Témoignages (Module 10) : CRUD complet (pas de slug, voir modèle) + export CSV.
        Route::get('testimonials/export', [AdminTestimonialController::class, 'export'])->name('testimonials.export');
        Route::apiResource('testimonials', AdminTestimonialController::class);

        // Partenaires (Module 11) : CRUD complet (logo géré via Médiathèque) + export CSV.
        Route::get('partners/export', [AdminPartnerController::class, 'export'])->name('partners.export');
        Route::apiResource('partners', AdminPartnerController::class);

        // Impact (Module 13) : CRUD indicateurs + export CSV + sous-ressource
        // "values" imbriquée (une valeur n'existe jamais hors de son
        // indicateur, pas de endpoint /impact-values à plat).
        Route::get('impact-indicators/export', [AdminImpactIndicatorController::class, 'export'])->name('impact-indicators.export');
        Route::apiResource('impact-indicators', AdminImpactIndicatorController::class);
        Route::post('impact-indicators/{impactIndicator}/values', [AdminImpactValueController::class, 'store'])
            ->name('impact-indicators.values.store');
        Route::put('impact-values/{impactValue}', [AdminImpactValueController::class, 'update'])
            ->name('impact-values.update');
        Route::delete('impact-values/{impactValue}', [AdminImpactValueController::class, 'destroy'])
            ->name('impact-values.destroy');

        // Messages de contact (Module 16) : jamais de store() côté admin,
        // voir ContactMessagePolicy (aucune méthode create()) + export CSV.
        Route::get('contact-messages/export', [AdminContactMessageController::class, 'export'])->name('contact-messages.export');
        Route::apiResource('contact-messages', AdminContactMessageController::class)
            ->only(['index', 'show', 'update', 'destroy']);

        // Dashboard (Module 14) : compteurs agrégés tous modules confondus.
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    });
});

Route::prefix('public')->name('public.')->group(function () {
    Route::get('pages', [PublicPageController::class, 'index'])->name('pages.index');
    Route::get('pages/{slug}', [PublicPageController::class, 'show'])->name('pages.show');

    Route::get('domains', [PublicDomainController::class, 'index'])->name('domains.index');
    Route::get('domains/{slug}', [PublicDomainController::class, 'show'])->name('domains.show');

    Route::get('programs', [PublicProgramController::class, 'index'])->name('programs.index');
    Route::get('programs/{slug}', [PublicProgramController::class, 'show'])->name('programs.show');

    Route::get('program-types', [PublicProgramTypeController::class, 'index'])->name('program-types.index');

    Route::get('application-calls', [PublicApplicationCallController::class, 'index'])->name('application-calls.index');
    Route::get('application-calls/{slug}', [PublicApplicationCallController::class, 'show'])->name('application-calls.show');

    // Soumission de candidature — jamais de liste/détail public (données
    // personnelles, voir Admin\ApplicationResource). Throttle anti-abus.
    Route::post('applications', [PublicApplicationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('applications.store');

    Route::get('news', [PublicNewsController::class, 'index'])->name('news.index');
    Route::get('news/{slug}', [PublicNewsController::class, 'show'])->name('news.show');

    // Talents / Success Stories (Module 9) — page de détail publique.
    Route::get('talents', [PublicTalentController::class, 'index'])->name('talents.index');
    Route::get('talents/{slug}', [PublicTalentController::class, 'show'])->name('talents.show');

    // Témoignages (Module 10) — volontairement pas de show() (pas de slug).
    Route::get('testimonials', [PublicTestimonialController::class, 'index'])->name('testimonials.index');

    // Partenaires (Module 11) — volontairement pas de show() (pas de page
    // de détail individuelle dans cette version).
    Route::get('partners', [PublicPartnerController::class, 'index'])->name('partners.index');

    // Impact (Module 13) — lecture seule, aucun cycle brouillon/publié.
    Route::get('impact-indicators', [PublicImpactIndicatorController::class, 'index'])->name('impact-indicators.index');

    // Cartographie (Module 15) — vue agrégée en lecture seule sur toutes
    // les entités géolocalisées et publiées.
    Route::get('map', [MapController::class, 'index'])->name('map.index');

    // Contact (Module 16) — écriture publique, throttle anti-spam/abus.
    Route::post('contact', [PublicContactMessageController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('contact.store');
});
