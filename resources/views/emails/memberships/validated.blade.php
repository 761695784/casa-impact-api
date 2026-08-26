@extends('emails.layout')

@section('subject', 'Bienvenue chez Casa Impact — votre adhésion est validée')

{{--
    Contenu calé sur l'exemple réel fourni par l'utilisateur le 2026-08-24
    (carte de membre en pièce jointe, lien du groupe WhatsApp, lien des
    réseaux sociaux). Vue Blade éditable à tout moment — voir le README.
--}}
@section('content')
    <p style="margin:0 0 16px;">Bonjour {{ $membership->nom_complet }},</p>

    <p style="margin:0 0 16px;">
        Nous avons le plaisir de vous confirmer que votre adhésion à <strong>Casa Impact</strong> (dossier n° <strong>{{ $membership->numero_membre }}</strong>) est désormais <strong>validée</strong>. Bienvenue officiellement dans la famille Casa Impact !
    </p>

    <p style="margin:0 0 16px;">
        Vous trouverez votre <strong>carte de membre</strong> en pièce jointe de cet email.
    </p>

    <p style="margin:0 0 16px;">
        Rejoignez dès à présent notre communauté WhatsApp pour rester informé(e) de toutes nos actualités et activités :<br>
        <a href="{{ config('casaimpact.whatsapp_group_url') }}" style="color:#163a2b;">{{ config('casaimpact.whatsapp_group_url') }}</a>
    </p>

    <p style="margin:0 0 16px;">
        Retrouvez également l'ensemble de nos réseaux sociaux ici :<br>
        <a href="{{ config('casaimpact.social_links_url') }}" style="color:#163a2b;">{{ config('casaimpact.social_links_url') }}</a>
    </p>

    <p style="margin:0;">
        Nous sommes ravis de vous compter parmi nous et avons hâte de construire ensemble l'impact de demain pour nos trois régions.
    </p>
@endsection
