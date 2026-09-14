<?php

namespace App\Console\Commands;

use App\Services\MembershipReferenceGenerator;
use Illuminate\Console\Command;

/**
 * Correctif ponctuel (2026-09-14) : les imports historiques faits AVANT ce
 * correctif n'ont jamais mis à jour `membership_reference_counters` (voir
 * LegacyMembershipImporter::commit(), qui le fait désormais automatiquement
 * à chaque import). Résultat concret observé : après l'import Excel des
 * adhérents historiques (ex. jusqu'à CI-2026-0171), le compteur était resté
 * bloqué à sa valeur de test (ex. 12) — le membre suivant créé depuis le
 * site ou l'admin obtenait donc un numéro (CI-2026-0013) déjà pris par un
 * membre importé, ou en tout cas hors séquence.
 *
 * À lancer UNE FOIS après avoir déployé ce correctif, pour rattraper l'état
 * actuel. Sans argument, sans confirmation : lecture + recalage seulement,
 * aucune suppression, idempotente (peut être relancée sans risque).
 */
class SyncMembershipReferenceCounter extends Command
{
    protected $signature = 'membership:sync-reference-counter';

    protected $description = "Recale le compteur de numéros d'adhésion (CI-{année}-{séquence}) sur le plus grand numéro réellement présent en base, pour chaque année.";

    public function handle(MembershipReferenceGenerator $generator): int
    {
        $changes = $generator->syncFromExisting();

        if (empty($changes)) {
            $this->info("Aucun numéro d'adhésion au format CI-AAAA-NNNN trouvé en base — rien à faire.");

            return self::SUCCESS;
        }

        foreach ($changes as $change) {
            if ($change['apres'] > $change['avant']) {
                $this->info("Année {$change['annee']} : compteur {$change['avant']} -> {$change['apres']}.");
            } else {
                $this->line("Année {$change['annee']} : compteur déjà à jour ({$change['avant']}).");
            }
        }

        $this->newLine();
        $this->info('Terminé — le prochain numéro généré pour chaque année reprendra juste après cette valeur.');

        return self::SUCCESS;
    }
}
