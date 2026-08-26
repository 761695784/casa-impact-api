<?php

namespace App\Enums;

/**
 * Reprend exactement les 3 choix de la question "Type de contribution
 * souhaitée" du formulaire Google Forms officiel d'adhésion. Champ non
 * obligatoire côté formulaire — voir StoreMembershipRequest.
 */
enum ContributionType: string
{
    case MembreActif = 'membre_actif';
    case BenevolePonctuel = 'benevole_ponctuel';
    case ExpertConseillerTechnique = 'expert_conseiller_technique';
}
