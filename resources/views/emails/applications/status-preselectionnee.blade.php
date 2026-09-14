@extends('emails.layout')

@section('subject', 'Votre candidature a été présélectionnée')

@section('content')
    <p style="margin:0 0 16px;">Bonjour {{ $application->prenom }},</p>

    <p style="margin:0 0 16px;">
        Nous avons le plaisir de vous informer que votre candidature (référence <strong>{{ $application->reference }}</strong>)
        pour <strong>{{ $application->applicationCall?->titre ?? "l'appel à candidatures" }}</strong> a été <strong>présélectionnée</strong>.
    </p>

    <p style="margin:0 0 16px;">
        Votre dossier a franchi une première étape de sélection. Notre équipe reviendra vers vous prochainement avec plus d'informations sur la suite du processus.
    </p>

    <p style="margin:0;">Félicitations, et à très bientôt.</p>
@endsection
