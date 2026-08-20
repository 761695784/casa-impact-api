<?php

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\DomainController as AdminDomainController;
use App\Http\Controllers\Api\Admin\PageController as AdminPageController;
use App\Http\Controllers\Api\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Api\Admin\ProgramTypeController as AdminProgramTypeController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Public\DomainController as PublicDomainController;
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
| (Programmes). Chaque nouveau module ajoutera son groupe de routes ici,
| sous le même préfixe /admin ou /public selon le cas.
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

        // Types de programme : table éditable (décision validée le
        // 2026-08-19, contrairement à Domain) — CRUD complet.
        Route::apiResource('program-types', AdminProgramTypeController::class);
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
});
