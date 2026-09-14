@extends('emails.layout')

@section('subject', 'Votre candidature est en cours d\'étude')

@section('content')
    <p style="margin:0 0 16px;">Bonjour {{ $application->prenom }},</p>

    <p style="margin:0 0 16px;">
        Nous vous confirmons que votre candidature (référence <strong>{{ $application->reference }}</strong>)
        pour <strong>{{ $application->applicationCall?->titre ?? "l'appel à candidatures" }}</strong> est actuellement <strong>en cours d'étude</strong> par notre équipe.
    </p>

    <p style="margin:0 0 16px;">
        Nous reviendrons vers vous dès qu'une décision aura été prise. Merci pour votre patience.
    </p>

    <p style="margin:0;">Merci pour votre intérêt envers Casa Impact.</p>
@endsection
