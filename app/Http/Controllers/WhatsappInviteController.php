<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * Lien WhatsApp à usage unique (accord du 2026-09-18) — voir la migration
 * 2026_09_18_120000_add_whatsapp_invite_to_memberships_table pour le
 * détail du mécanisme et sa limite assumée. Contrôleur volontairement PAS
 * dans Api/Admin : cette route est publique (ouverte directement depuis un
 * client mail, sans authentification ni JSON) et vit dans routes/web.php.
 */
class WhatsappInviteController extends Controller
{
    public function redirect(string $token): RedirectResponse|Response
    {
        $membership = Membership::query()->where('whatsapp_invite_token', $token)->first();

        if (! $membership) {
            return response()->view('whatsapp.invalide', [], 404);
        }

        if ($membership->whatsapp_invite_used_at) {
            return response()->view('whatsapp.deja-utilise', [], 410);
        }

        // forceFill : mêmes raisons que Membership::ensureWhatsappInviteToken()
        // — champ jamais dans $fillable, toujours fixé par le serveur.
        $membership->forceFill(['whatsapp_invite_used_at' => now()])->save();

        return redirect()->away(config('casaimpact.whatsapp_group_url'));
    }
}
