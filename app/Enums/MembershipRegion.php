<?php

namespace App\Enums;

/**
 * Enum DÉDIÉ à l'adhésion — volontairement distinct de App\Enums\Region
 * (qui reste limité aux 3 régions de la zone d'intervention Casa Impact,
 * utilisé par ApplicationCall/Application/etc., non modifié). Le
 * formulaire Google Forms officiel d'adhésion ("Région de résidence")
 * propose 5 choix, dont 2 hors zone d'intervention (Dakar, Diaspora) —
 * un adhérent peut résider n'importe où, contrairement à un candidat à
 * une formation. Réutiliser Region cassait cette distinction et aurait
 * forcé à modifier un enum partagé déjà testé par d'autres modules.
 */
enum MembershipRegion: string
{
    case Ziguinchor = 'ziguinchor';
    case Sedhiou = 'sedhiou';
    case Kolda = 'kolda';
    case Dakar = 'dakar';
    case Diaspora = 'diaspora';

    /**
     * Utilisé par StoreMembershipRequest : le champ "département" du
     * formulaire n'a de sens que pour les 3 régions de Casamance.
     */
    public function estEnCasamance(): bool
    {
        return in_array($this, [self::Ziguinchor, self::Sedhiou, self::Kolda], true);
    }
}
