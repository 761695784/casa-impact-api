<?php

namespace App\Enums;

/**
 * Cycle de vie d'une adhésion (voir process décrit par l'utilisateur le
 * 2026-08-24) :
 *   en_attente_paiement -> validee   (paiement Wave vérifié manuellement
 *                                      par l'admin via capture WhatsApp)
 *   en_attente_paiement -> refusee   (ex. paiement jamais reçu, dossier
 *                                      invalide)
 * Une adhésion saisie manuellement par l'admin (source = manuel, membres
 * antérieurs au site) est créée directement au statut `validee`.
 */
enum MembershipStatus: string
{
    case EnAttentePaiement = 'en_attente_paiement';
    case Validee = 'validee';
    case Refusee = 'refusee';
}
