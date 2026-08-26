<?php

/**
 * Constantes métier Casa Impact utilisées par le module Adhésion et par
 * les emails de marque (logo/filigrane/signature). Regroupées ici plutôt
 * que codées en dur dans les Notifications/vues, pour rester modifiables
 * sans toucher au code (ex. si le numéro Wave ou le montant de la
 * cotisation change).
 *
 * Toutes les valeurs peuvent être surchargées via .env — voir le README
 * de cette livraison pour les entrées à ajouter.
 */
return [

    // Paiement de la cotisation d'adhésion (process décrit par
    // l'utilisateur le 2026-08-24).
    'wave_number' => env('CASAIMPACT_WAVE_NUMBER', '+221 78 326 73 78'),
    'wave_amount' => env('CASAIMPACT_WAVE_AMOUNT', '1.000 FCFA'),

    // Même numéro que le Wave dans le process actuel, mais gardé comme
    // clé séparée : rien n'empêche l'association d'utiliser un numéro
    // WhatsApp différent du numéro Wave à l'avenir.
    'whatsapp_payment_number' => env('CASAIMPACT_WHATSAPP_PAYMENT_NUMBER', '+221 78 326 73 78'),

    'whatsapp_group_url' => env('CASAIMPACT_WHATSAPP_GROUP_URL', 'https://chat.whatsapp.com/HrJEoGUspvo5G8nLdrnINs'),

    'social_links_url' => env('CASAIMPACT_SOCIAL_LINKS_URL', 'https://lnk.bio/casa_impact'),

    // Nullable tant que le numéro officiel n'a pas été communiqué (voir
    // message de l'utilisateur du 2026-08-24 : "je te donnerai le numero
    // officiel... quand je l'aurai reçu") — la signature d'email gère déjà
    // son absence (voir resources/views/emails/layout.blade.php), aucun
    // blocage à livrer sans cette valeur.
    'signature_phone' => env('CASAIMPACT_SIGNATURE_PHONE'),

    'signature_email' => env('CASAIMPACT_SIGNATURE_EMAIL', 'casaimpactF0rt@gmail.com'),

    'tagline' => 'Trois Régions • Une vision • Un impact',
];
