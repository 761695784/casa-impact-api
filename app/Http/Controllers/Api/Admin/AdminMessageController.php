<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminBroadcastMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Envoi d'un message libre depuis la partie admin (section Messages,
 * bouton "Envoyer un message" — accord du 2026-09-11), au format email
 * officiel Casa Impact. Les destinataires viennent du frontend déjà
 * fusionnés (membres cochés + adresses libres tapées à la main) : ce
 * contrôleur ne distingue pas leur origine, seulement des adresses email
 * valides.
 *
 * Rien n'est persisté en base ici : ce n'est pas un ContactMessage, c'est
 * un envoi direct qui ne laisse aucune trace côté serveur au-delà des logs
 * d'envoi Laravel habituels.
 */
class AdminMessageController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:10000'],
            'recipients' => ['required', 'array', 'min:1', 'max:200'],
            'recipients.*' => ['required', 'email'],
        ]);

        // Dédoublonnage (insensible à la casse) — le frontend déduplique déjà,
        // on se protège quand même d'un appel direct à l'API.
        $recipients = collect($validated['recipients'])
            ->map(fn ($email) => trim($email))
            ->unique(fn ($email) => strtolower($email))
            ->values()
            ->all();

        $bodyHtml = $this->renderMessageHtml($validated['message']);

        $failed = [];
        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new AdminBroadcastMessage($validated['subject'], $bodyHtml));
            } catch (\Throwable $e) {
                $failed[] = $email;
            }
        }

        $sent = count($recipients) - count($failed);

        return response()->json([
            'message' => count($failed) === 0
                ? "Message envoyé avec succès à {$sent} destinataire(s)."
                : "Message envoyé à {$sent} destinataire(s), " . count($failed) . ' échec(s).',
            'sent' => $sent,
            'failed' => $failed,
        ]);
    }

    /**
     * Convertit le texte brut saisi dans la modal (mini-syntaxe : **gras**,
     * *italique*, __souligné__, "- " en début de ligne pour une liste,
     * [texte](url) pour un lien — accord du 2026-09-11, barre d'outils
     * simple plutôt qu'un éditeur WYSIWYG) en HTML sûr pour l'email.
     *
     * Sécurité : chaque fragment de texte est TOUJOURS échappé (e()) avant
     * qu'on y insère nos propres balises autour — le HTML final n'est donc
     * jamais construit à partir de HTML fourni par l'utilisateur, seulement
     * de texte échappé encadré par des balises que nous choisissons nous-
     * mêmes. Un appel direct à l'API (en dehors de la modal) avec du texte
     * contenant p.ex. "<script>" ne peut donc jamais produire de HTML actif.
     */
    private function renderMessageHtml(string $raw): string
    {
        $blocks = preg_split('/\n{2,}/', trim($raw)) ?: [];
        $html = '';

        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }

            $lines = preg_split('/\n/', $block) ?: [$block];
            $isList = collect($lines)->every(
                fn ($l) => trim($l) === '' || preg_match('/^[-*]\s+/', trim($l)) === 1
            );

            if ($isList) {
                $items = collect($lines)
                    ->map(fn ($l) => trim($l))
                    ->filter(fn ($l) => $l !== '')
                    ->map(fn ($l) => preg_replace('/^[-*]\s+/', '', $l))
                    ->map(fn ($l) => '<li style="margin:0 0 6px;">' . $this->renderInline($l) . '</li>')
                    ->implode('');
                $html .= '<ul style="margin:0 0 16px; padding-left:20px; color:#1f2a24;">' . $items . '</ul>';
            } else {
                $inline = collect($lines)
                    ->map(fn ($l) => $this->renderInline(trim($l)))
                    ->implode('<br>');
                $html .= '<p style="margin:0 0 16px;">' . $inline . '</p>';
            }
        }

        return $html !== '' ? $html : '<p style="margin:0;"></p>';
    }

    /**
     * Applique la mise en forme "inline" (gras/italique/souligné/lien) à un
     * fragment de texte déjà brut (jamais du HTML). Échappe d'abord tout le
     * texte, puis insère uniquement des balises whitelistées autour de
     * portions déjà échappées — voir renderMessageHtml() pour le principe
     * de sécurité complet.
     */
    private function renderInline(string $text): string
    {
        $escaped = e($text);

        // Lien [texte](https://...) — http/https uniquement. $m[2] est déjà
        // échappé (issu de $escaped) : pas de nouvel appel à e() ici, sous
        // peine de double-échappement (ex. "&" -> "&amp;amp;").
        $escaped = preg_replace_callback(
            '/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/',
            fn ($m) => '<a href="' . $m[2] . '" style="color:#02542D; text-decoration:underline;">' . $m[1] . '</a>',
            $escaped
        );

        // Gras **texte** — traité avant l'italique pour ne pas laisser les
        // "*" internes se faire capturer par la règle italique ci-dessous.
        $escaped = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $escaped);

        // Souligné __texte__
        $escaped = preg_replace('/__(.+?)__/', '<u>$1</u>', $escaped);

        // Italique *texte*
        $escaped = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $escaped);

        return $escaped;
    }
}
