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
        <a href="{{ config('casaimpact.whatsapp_group_url') }}" style="color:#02542D;">{{ config('casaimpact.whatsapp_group_url') }}</a>
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px; background-color:#fdf6e8; border-left:4px solid #F2A20D; border-radius:6px;">
        <tr>
            <td style="padding:16px 18px;">
                <p style="margin:0 0 10px; font-weight:bold; color:#02542D; font-size:15px;">
                    Un point important à votre attention
                </p>
                <p style="margin:0 0 12px;">
                    Ce lien de groupe est strictement réservé aux personnes ayant déjà finalisé leur adhésion. Nous vous remercions de ne pas le partager directement, afin de garantir une bonne gestion des adhésions et de préserver l'intégrité de notre base de données.
                </p>
                <p style="margin:0;">
                    Si vous souhaitez inviter une personne à rejoindre Casa Impact, nous vous invitons simplement à l'orienter vers notre formulaire d'adhésion, afin qu'elle puisse soumettre sa demande dans les conditions prévues :<br>
                    <a href="https://docs.google.com/forms/d/e/1FAIpQLSd_wTaXTsb-yJ2v8qpgXHQRQutMjBGQqXBIZsSkiDgSDVjnrw/viewform?usp=publish-editor" style="color:#02542D;">Formulaire d'adhésion Casa Impact</a>
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px;">
        Retrouvez également l'ensemble de nos réseaux sociaux ici :<br>
        <a href="{{ config('casaimpact.social_links_url') }}" style="color:#02542D;">{{ config('casaimpact.social_links_url') }}</a>
    </p>

    <p style="margin:0;">
        Nous sommes ravis de vous compter parmi nous et avons hâte de construire ensemble l'impact de demain pour nos trois régions.
    </p>
@endsection
