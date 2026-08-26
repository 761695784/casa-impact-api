@extends('emails.layout')

@section('subject', 'Votre candidature est en liste d\'attente')

@section('content')
    <p style="margin:0 0 16px;">Bonjour {{ $application->prenom }},</p>

    <p style="margin:0 0 16px;">
        Concernant votre candidature (référence <strong>{{ $application->reference }}</strong>) pour
        <strong>{{ $application->applicationCall?->titre ?? "l'appel à candidatures" }}</strong>, nous vous informons qu'elle a été placée en <strong>liste d'attente</strong>.
    </p>

    <p style="margin:0 0 16px;">
        Cela signifie que votre dossier est solide, mais que le nombre de places disponibles est actuellement atteint. Nous vous recontacterons sans délai si une place venait à se libérer.
    </p>

    <p style="margin:0;">Merci pour votre patience et votre intérêt pour Casa Impact.</p>
@endsection
