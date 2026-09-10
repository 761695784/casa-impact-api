@extends('emails.layout')

@section('subject', 'Nouveau message reçu — Formulaire de contact')

@section('content')
    <p style="margin:0 0 16px;">Un nouveau message vient d'être déposé via le formulaire de contact du site.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px; border:1px solid #e5e3da; border-radius:8px; overflow:hidden;">
        <tr>
            <td style="padding:10px 16px; background-color:#f7f6f2; width:140px; font-size:12px; font-weight:bold; color:#4a4a42; border-bottom:1px solid #e5e3da;">Catégorie</td>
            <td style="padding:10px 16px; font-size:14px; color:#2b2b26; border-bottom:1px solid #e5e3da;">{{ $categorieLabel }}</td>
        </tr>
        <tr>
            <td style="padding:10px 16px; background-color:#f7f6f2; font-size:12px; font-weight:bold; color:#4a4a42; border-bottom:1px solid #e5e3da;">Nom</td>
            <td style="padding:10px 16px; font-size:14px; color:#2b2b26; border-bottom:1px solid #e5e3da;">{{ $contactMessage->nom }}</td>
        </tr>
        <tr>
            <td style="padding:10px 16px; background-color:#f7f6f2; font-size:12px; font-weight:bold; color:#4a4a42; border-bottom:1px solid #e5e3da;">Email</td>
            <td style="padding:10px 16px; font-size:14px; color:#2b2b26; border-bottom:1px solid #e5e3da;">
                <a href="mailto:{{ $contactMessage->email }}" style="color:#163a2b;">{{ $contactMessage->email }}</a>
            </td>
        </tr>
        @if ($contactMessage->telephone)
            <tr>
                <td style="padding:10px 16px; background-color:#f7f6f2; font-size:12px; font-weight:bold; color:#4a4a42; border-bottom:1px solid #e5e3da;">Téléphone</td>
                <td style="padding:10px 16px; font-size:14px; color:#2b2b26; border-bottom:1px solid #e5e3da;">{{ $contactMessage->telephone }}</td>
            </tr>
        @endif
        @if ($contactMessage->sujet)
            <tr>
                <td style="padding:10px 16px; background-color:#f7f6f2; font-size:12px; font-weight:bold; color:#4a4a42;">Sujet</td>
                <td style="padding:10px 16px; font-size:14px; color:#2b2b26;">{{ $contactMessage->sujet }}</td>
            </tr>
        @endif
    </table>

    <p style="margin:0 0 8px; font-size:12px; font-weight:bold; color:#4a4a42; text-transform:uppercase; letter-spacing:0.03em;">Message</p>
    <p style="margin:0 0 20px; padding:14px 16px; background-color:#f7f6f2; border-radius:8px; font-size:14px; line-height:1.6; color:#2b2b26; white-space:pre-line;">{{ $contactMessage->message }}</p>

    <p style="margin:0; font-size:13px; color:#6b6a60;">
        Consultez et traitez ce message depuis l'onglet <strong>Messages</strong> du panneau d'administration Casa Impact.
    </p>
@endsection
