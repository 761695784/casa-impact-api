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
    donne un contrôle total sur le
    HTML/CSS, nécessaire pour respecter fidèlement le rendu demandé par
    l'utilisateur le 2026-08-24 (logo + filigranes + signature).

    Table-based layout volontaire (pas de flexbox/grid) : compatibilité
    email la plus large possible (Outlook desktop notamment). Les images
    (logo/filigrane) sont référencées via asset('images/mail/...') — donc
    servies depuis public/images/mail/, PAS depuis storage — un client
    mail ne peut pas lire un chemin disque local, il lui faut une URL
    HTTP publique accessible depuis Internet (APP_URL doit donc être une
    URL réellement joignable en production, pas localhost).
-->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f1ec; padding: 24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius: 8px; overflow:hidden; max-width:600px; width:100%;">

                {{-- En-tête : logo --}}
                <tr>
                    <td style="background-color:#163a2b; padding: 24px 32px;" align="left">
                        <img src="{{ asset('images/mail/logo-couleur.png') }}" alt="Casa Impact" height="48" style="display:block; height:48px; width:auto;">
                    </td>
                </tr>

                {{-- Corps : filigrane en fond, contenu par-dessus --}}
                <tr>
                    <td style="background-image:url('{{ asset('images/mail/filigrane-couleur.png') }}'); background-repeat:no-repeat; background-position: right 20px top 20px; background-size: 220px auto; background-color:#ffffff;">
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
                    <td style="background-color:#f7f6f2; padding: 24px 32px; border-top: 1px solid #e5e3da;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="56" valign="top">
                                    <img src="{{ asset('images/mail/arbre-couleur.png') }}" alt="" width="40" style="display:block; width:40px; height:auto;">
                                </td>
                                <td valign="top" style="color:#4a4a42; font-size:13px; line-height:1.7;">
                                    <strong style="color:#163a2b; font-size:14px;">Casa Impact</strong><br>
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
                        Cet email vous a été envoyé automatiquement par la plateforme Casa Impact.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>
