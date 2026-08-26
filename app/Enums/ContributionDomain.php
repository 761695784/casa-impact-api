<?php

namespace App\Enums;

/**
 * Reprend exactement les 7 choix de la question "Dans quels domaines
 * souhaitez-vous contribuer ?" du formulaire Google Forms officiel
 * d'adhésion (capture fournie par l'utilisateur le 2026-08-24). Champ
 * non obligatoire côté formulaire — voir StoreMembershipRequest.
 */
enum ContributionDomain: string
{
    case PoleCapitalHumain = 'pole_capital_humain';
    case PoleEconomieAgricultureAttractivite = 'pole_economie_agriculture_attractivite';
    case PoleCultureCommunication = 'pole_culture_communication';
    case PoleSupport = 'pole_support';
    case CommissionScientifique = 'commission_scientifique';
    case CoordinationRegionale = 'coordination_regionale';
    case ComiteDesSages = 'comite_des_sages';
}
