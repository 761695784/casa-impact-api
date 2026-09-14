@extends('emails.layout')

@section('subject', 'Rappel — Finalisez votre adhésion à Casa Impact')

{{--
    Rappel de paiement (bouton admin "Envoyer un rappel de paiement" —
    accord du 2026-09-11), pour les membres encore `en_attente_paiement`.
    Reprend volontairement le même texte d'instructions de paiement que
    received.blade.php (montant/numéro Wave, numéro WhatsApp) pour rester
    cohérent avec le premier email déjà reçu — seule l'introduction change
    (rappel plutôt que confirmation initiale).
--}}
@section('content')
    <p style="margin:0 0 16px;">Bonjour {{ $membership->nom_complet }},</p>

    <p style="margin:0 0 16px;">
        Nous n'avons pas encore reçu le règlement de votre cotisation pour finaliser votre adhésion à <strong>Casa Impact</strong> (dossier n° <strong>{{ $membership->numero_membre }}</strong>).
    </p>

    <p style="margin:0 0 16px;">
        Pour l'activer, il vous suffit de régler <strong>{{ config('casaimpact.wave_amount') }}</strong> via <strong>Wave</strong>, au numéro suivant :
    </p>

    <p style="margin:0 0 16px; font-size:18px; font-weight:bold; color:#02542D;">{{ config('casaimpact.wave_number') }}</p>

    <p style="margin:0 0 16px;">
        Une fois le paiement effectué, merci de nous envoyer la <strong>capture d'écran de la transaction</strong> par WhatsApp au même numéro
        ({{ config('casaimpact.whatsapp_payment_number') }}), en indiquant votre numéro de dossier <strong>{{ $membership->numero_membre }}</strong>.
    </p>

    <p style="margin:0 0 16px;">
        Dès réception et vérification de votre paiement, vous recevrez un email confirmant la validation de votre adhésion, accompagné de votre carte de membre officielle.
    </p>

    <p style="margin:0;">Merci pour votre engagement envers Casa Impact, nous avons hâte de vous compter officiellement parmi nous !</p>
@endsection
