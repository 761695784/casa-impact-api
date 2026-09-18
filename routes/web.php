<?php

use App\Http\Controllers\WhatsappInviteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Lien WhatsApp à usage unique de l'email de validation d'adhésion
// (accord du 2026-09-18) — voir WhatsappInviteController pour le détail.
// Route publique volontairement HORS de routes/api.php : ouverte
// directement dans un navigateur depuis un client mail, jamais appelée en
// JSON/fetch, donc pas de logique CSRF/Sanctum à y faire transiter.
Route::get('/rejoindre-whatsapp/{token}', [WhatsappInviteController::class, 'redirect'])
    ->name('whatsapp.invite');
