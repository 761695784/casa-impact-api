<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Services\LegacyMembershipImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Bouton "Importer l'historique (Excel)" du panneau admin — accord
 * explicite du 2026-09-11 (en complément de la commande Artisan
 * ponctuelle, utile pour un import fait directement depuis le terminal).
 * Réutilise EXACTEMENT la même logique de rapprochement des onglets que la
 * commande CLI via LegacyMembershipImporter — voir ce service pour le
 * détail des décisions actées avec l'utilisateur.
 *
 * Flux en 2 temps (aperçu puis confirmation, accord explicite) :
 *   1. POST /api/admin/memberships/import-legacy/preview (fichier Excel en
 *      multipart) -> stocke le fichier temporairement (disque `local`,
 *      jamais public) et renvoie un résumé chiffré (aucune donnée
 *      personnelle) + un `import_token`.
 *   2. POST /api/admin/memberships/import-legacy/commit (`import_token`)
 *      -> relit le MÊME fichier temporaire, recalcule un plan FRAIS (pour
 *      rester correct même si d'autres écritures ont eu lieu entre-temps),
 *      écrit réellement en base, puis supprime le fichier temporaire.
 * DELETE /api/admin/memberships/import-legacy/{token} permet d'annuler
 * explicitement (nettoyage immédiat) si l'admin renonce après l'aperçu.
 *
 * Confidentialité : le fichier ne quitte jamais le disque `local` (jamais
 * exposé publiquement), est supprimé dès la confirmation ou l'annulation,
 * et un nettoyage best-effort supprime aussi tout fichier de plus de 30
 * minutes au début de chaque preview() (aperçu abandonné sans confirmation
 * ni annulation explicite).
 */
class MembershipImportController extends Controller
{
    private const TEMP_DIR = 'private/imports';

    public function __construct(private readonly LegacyMembershipImporter $importer)
    {
    }

    public function preview(Request $request)
    {
        $this->authorize('create', Membership::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:20480'],
        ]);

        $this->cleanupStaleTempFiles();

        $token = (string) Str::uuid();
        $filename = "{$token}.xlsx";
        $request->file('file')->storeAs(self::TEMP_DIR, $filename, 'local');

        $absolutePath = Storage::disk('local')->path(self::TEMP_DIR.'/'.$filename);

        try {
            $plan = $this->importer->plan($absolutePath);
        } catch (\RuntimeException $e) {
            Storage::disk('local')->delete(self::TEMP_DIR.'/'.$filename);

            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        // Volontairement PAS $plan['to_create'] dans la réponse : même si
        // ce sous-tableau ne contient déjà aucune donnée ultra-sensible au
        // sens strict, autant limiter au strict nécessaire ce qui transite
        // vers le navigateur pour un fichier explicitement signalé
        // confidentiel par l'utilisateur.
        return response()->json([
            'import_token' => $token,
            'counts' => $plan['counts'],
            'warnings' => $plan['warnings'],
        ]);
    }

    public function commit(Request $request)
    {
        $this->authorize('create', Membership::class);

        $validated = $request->validate([
            'import_token' => ['required', 'uuid'],
        ]);

        $relativePath = self::TEMP_DIR.'/'.$validated['import_token'].'.xlsx';

        if (! Storage::disk('local')->exists($relativePath)) {
            throw ValidationException::withMessages([
                'import_token' => "Fichier introuvable ou expiré (plus de 30 minutes depuis l'aperçu) — veuillez réimporter le fichier.",
            ]);
        }

        $absolutePath = Storage::disk('local')->path($relativePath);

        try {
            // Recalcul frais (pas le plan mis en cache côté navigateur) :
            // reste correct même si d'autres membres ont été ajoutés entre
            // l'aperçu et cette confirmation.
            $plan = $this->importer->plan($absolutePath);
            $result = $this->importer->commit($plan['to_create']);
        } finally {
            Storage::disk('local')->delete($relativePath);
        }

        return response()->json([
            'created' => $result['created'],
            'failed' => $result['failed'],
        ]);
    }

    public function cancel(string $token)
    {
        $this->authorize('create', Membership::class);

        if (! Str::isUuid($token)) {
            abort(404);
        }

        Storage::disk('local')->delete(self::TEMP_DIR.'/'.$token.'.xlsx');

        return response()->json(['message' => 'Import annulé, fichier supprimé.']);
    }

    /**
     * Supprime les fichiers temporaires de plus de 30 minutes — filet de
     * sécurité pour un aperçu commencé puis jamais confirmé ni annulé
     * (onglet fermé, navigation ailleurs...). Best-effort, appelé à chaque
     * nouveau preview() plutôt que via une tâche planifiée dédiée : le
     * volume attendu (imports ponctuels, un admin à la fois) ne justifie
     * pas plus.
     */
    private function cleanupStaleTempFiles(): void
    {
        $disk = Storage::disk('local');

        if (! $disk->exists(self::TEMP_DIR)) {
            return;
        }

        foreach ($disk->files(self::TEMP_DIR) as $file) {
            if ($disk->lastModified($file) < now()->subMinutes(30)->timestamp) {
                $disk->delete($file);
            }
        }
    }
}
