<?php

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Casa Impact
|--------------------------------------------------------------------------
| Convention (architecturev1.md §F, décision #8) : pas de préfixe de
| version. Deux familles de routes :
|   - /api/admin/*   : authentifiées (Sanctum SPA), équipe Casa Impact
|   - /api/public/*  : sans authentification, ajoutées à partir du Module 4
|                      (Pages/Domaines) — aucune route publique n'existe
|                      encore à ce stade.
|
| Ce fichier ne contient pour l'instant que le Module 1 (Administration).
| Chaque nouveau module ajoutera son groupe de routes ici, sous le même
| préfixe /admin ou /public selon le cas.
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
    });
});
