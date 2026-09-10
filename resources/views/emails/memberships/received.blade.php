@extends('emails.layout')

@section('subject', 'Confirmation de votre demande d\'adhésion — Casa Impact')

{{--
    Contenu calé sur l'exemple réel fourni par l'utilisateur le 2026-08-24
    (numéro de dossier, instructions de paiement Wave, envoi de la capture
    par WhatsApp). Vue Blade éditable à tout moment si le texte exact doit
    être ajusté — voir le README de cette livraison.
--}}
@section('content')
    <p style="margin:0 0 16px;">Bonjour {{ $membership->nom_complet }},</p>

    <p style="margin:0 0 16px;">
        Nous vous remercions pour votre demande d'adhésion à <strong>Casa Impact</strong>. Votre dossier a bien été enregistré sous le numéro :
    </p>

    <p style="margin:0 0 16px; font-size:20px; font-weight:bold; color:#02542D;">{{ $membership->numero_membre }}</p>

    <p style="margin:0 0 16px;">
        Pour finaliser votre adhésion, il vous reste une dernière étape : le règlement de la cotisation d'un montant de
        <strong>{{ config('casaimpact.wave_amount') }}</strong> via <strong>Wave</strong>, au numéro suivant :
    </p>

    <p style="margin:0 0 16px; font-size:18px; font-weight:bold; color:#02542D;">{{ config('casaimpact.wave_number') }}</p>

    <p style="margin:0 0 16px;">
        Une fois le paiement effectué, merci de nous envoyer la <strong>capture d'écran de la transaction</strong> par WhatsApp au même numéro
        ({{ config('casaimpact.whatsapp_payment_number') }}), en indiquant votre numéro de dossier <strong>{{ $membership->numero_membre }}</strong>.
    </p>

    <p style="margin:0 0 16px;">
        Dès réception et vérification de votre paiement, vous recevrez un second email confirmant la validation de votre adhésion, accompagné de votre carte de membre officielle.
    </p>

    <p style="margin:0;">Merci pour votre engagement envers Casa Impact.</p>
@endsection
