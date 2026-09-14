@extends('emails.layout')

@section('subject', $subject)

{{--
    Vue générique pour un message libre composé depuis la partie admin
    (bouton "Envoyer un message" — accord du 2026-09-11). Même principe que
    emails/memberships/received.blade.php et validated.blade.php : on ne
    fournit que le contenu, le layout `emails.layout` apporte l'habillage
    Casa Impact (logo, filigrane, couleurs, signature).

    Accord du 2026-09-11 : le sujet doit TOUJOURS apparaître en gras, dans
    une "belle police", en haut du corps du message (pas seulement comme
    objet de l'email dans la boîte de réception). Police serif élégante
    (Georgia/Baskerville) choisie plutôt qu'une police Google Fonts
    importée : la plupart des clients mail (Outlook desktop en tête) ne
    chargent pas les polices web externes et retomberaient sur une police
    par défaut disgracieuse — Georgia est nativement installée sur
    Windows/Mac/Linux et rend un rendu élégant fiable partout.

    {{ $subject }} (et non {!! !!}) : échappement automatique Blade, le
    sujet est un simple champ texte saisi par l'admin.
--}}
@section('content')
    <p style="margin:0 0 20px; font-family: Georgia, 'Times New Roman', Baskerville, serif; font-size:22px; line-height:1.35; font-weight:700; color:#02542D; letter-spacing:0.2px;">
        {{ $subject }}
    </p>

    {!! $bodyHtml !!}
@endsection
