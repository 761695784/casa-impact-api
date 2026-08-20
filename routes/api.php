<?php

use App\Http\Controllers\Api\Admin\ApplicationCallController as AdminApplicationCallController;
use App\Http\Controllers\Api\Admin\ApplicationController as AdminApplicationController;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\DomainController as AdminDomainController;
use App\Http\Controllers\Api\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Api\Admin\PageController as AdminPageController;
use App\Http\Controllers\Api\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Api\Admin\ProgramTypeController as AdminProgramTypeController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Public\ApplicationCallController as PublicApplicationCallController;
use App\Http\Controllers\Api\Public\ApplicationController as PublicApplicationController;
use App\Http\Controllers\Api\Public\DomainController as PublicDomainController;
use App\Http\Controllers\Api\Public\NewsController as PublicNewsController;
use App\Http\Controllers\Api\Public\PageController as PublicPageController;
use App\Http\Controllers\Api\Public\ProgramController as PublicProgramController;
use App\Http\Controllers\Api\Public\ProgramTypeController as PublicProgramTypeController;
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
| Module 1 (Administration) + Module 2 (Pages/Domaines) + Module 3
| (Programmes) + Module 4 (Appels à candidatures) + Module 5 (Candidatures)
| + Module 6 (Actualités). Chaque nouveau module ajoutera son groupe de
| routes ici.
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

        // Programmes : CRUD complet (contrairement à Domaines).
        Route::apiResource('programs', AdminProgramController::class);

        // Types de programme : table éditable — CRUD complet.
        Route::apiResource('program-types', AdminProgramTypeController::class);

        // Appels à candidatures : CRUD complet.
        Route::apiResource('application-calls', AdminApplicationCallController::class);

        // Candidatures : pas de store() (soumission publique uniquement).
        Route::apiResource('applications', AdminApplicationController::class)
            ->only(['index', 'show', 'update', 'destroy']);
        Route::get('applications/{application}/documents/{document}/download', [AdminApplicationController::class, 'downloadDocument'])
            ->name('applications.documents.download');
        // Sortie de liste d'attente en un clic.
        Route::post('applications/{application}/promote', [AdminApplicationController::class, 'promote'])
            ->name('applications.promote');

        // Actualités : CRUD complet.
        Route::apiResource('news', AdminNewsController::class);
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
});
