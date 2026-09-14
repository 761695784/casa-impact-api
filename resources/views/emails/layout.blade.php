<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('subject', 'Casa Impact')</title>
</head>
<body style="margin:0; padding:0; background-color:#f2f1ec; font-family: Helvetica, Arial, sans-serif;">

<!--
    Layout Blade partagé par TOUS les emails Casa Impact (branded : logo,
    filigrane, signature) — chaque email de contenu utilise le mot-clé
    Blade "extends" pointant vers "emails.layout", puis un bloc "section"
    nommé "content". Choix explicite d'un layout Blade "maison" plutôt que
    le thème markdown par défaut de Laravel (MailMessage::markdown()) :
    donne un contrôle total sur le HTML/CSS.

    MIS À JOUR le 2026-09-10 : les images (logo/arbre/filigrane) étaient
    référencées via asset('images/mail/...'), donc une URL HTTP publique —
    invisibles dès que APP_URL pointe vers une adresse non joignable
    depuis Internet (typiquement localhost en développement), ce qui était
    le cas constaté. Remplacé par $message->embed(public_path(...)) :
    Laravel partage automatiquement la variable $message (le message
    Symfony sous-jacent) à TOUTE vue de mail, Notification comme Mailable
    — embed() joint l'image en pièce jointe "inline" (Content-ID) au lieu
    d'un lien externe, donc s'affiche identiquement en local et en
    production, sans dépendre d'aucune URL. Les fichiers doivent toujours
    exister sur le disque à public/images/mail/ (inchangé — voir README
    de la livraison du 24/08).

    Couleurs alignées sur l'identité visuelle exacte du site (voir
    app/globals.css du frontend) : vert forêt #02542D, ambre #F2A20D
    ("mangue mûre") — plus un fidèle #163a2b approximatif.

    Table-based layout volontaire (pas de flexbox/grid) : compatibilité
    email la plus large possible (Outlook desktop notamment). Le filigrane
    reste en CSS background-image (moins universellement supporté qu'un
    <img>, mais c'est un élément décoratif, pas critique — le logo et
    l'arbre, eux, sont de vraies balises <img> avec CID, fiables partout).
-->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f1ec; padding: 24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius: 8px; overflow:hidden; max-width:600px; width:100%;">

                {{-- En-tête : logo sur fond vert forêt Casa Impact --}}
                <tr>
                    <td style="background-color:#02542D; padding: 24px 32px;" align="left">
                        <img src="{{ $message->embed(public_path('images/mail/Casa-impact.png')) }}" alt="Casa Impact" height="48" style="display:block; height:48px; width:auto;">
                    </td>
                </tr>

                {{-- Liseré ambre — identité visuelle Casa Impact --}}
                <tr>
                    <td style="background-color:#F2A20D; height:4px; line-height:4px; font-size:0;">&nbsp;</td>
                </tr>

                {{-- Corps : filigrane en fond, contenu par-dessus --}}
                <tr>
                    <td style="background-image:url('{{ $message->embed(public_path('images/mail/filigrane-couleur.png')) }}'); background-repeat:no-repeat; background-position: right 20px top 20px; background-size: 220px auto; background-color:#ffffff;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="padding: 32px; color:#2b2b26; font-size:15px; line-height:1.6;">
                                    @yield('content')
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Signature --}}
                <tr>
                    <td style="background-color:#f7f6f2; padding: 24px 32px; border-top: 3px solid #F2A20D;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="56" valign="top">
                                    <img src="{{ $message->embed(public_path('images/mail/Filigrane.png')) }}" alt="" width="40" style="display:block; width:40px; height:auto;">
                                </td>
                                <td valign="top" style="color:#4a4a42; font-size:13px; line-height:1.7;">
                                    <strong style="color:#02542D; font-size:14px;">Casa Impact</strong><br>
                                    {{ config('casaimpact.tagline') }}<br>
                                    @if (config('casaimpact.signature_phone'))
                                        Tél / WhatsApp : {{ config('casaimpact.signature_phone') }}<br>
                                    @endif
                                    Email : {{ config('casaimpact.signature_email') }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%;">
                <tr>
                    <td style="padding: 16px 32px; color:#9b9a90; font-size:11px; text-align:center;">
                        Cet email vous a été envoyé via la plateforme de Casa Impact.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>
