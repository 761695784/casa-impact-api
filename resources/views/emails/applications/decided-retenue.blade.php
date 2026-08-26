@extends('emails.layout')

@section('subject', 'Votre candidature a été retenue')

@section('content')
    <p style="margin:0 0 16px;">Bonjour {{ $application->prenom }},</p>

    <p style="margin:0 0 16px;">
        Nous avons le plaisir de vous informer que votre candidature (référence <strong>{{ $application->reference }}</strong>)
        pour <strong>{{ $application->applicationCall?->titre ?? "l'appel à candidatures" }}</strong> a été <strong>retenue</strong>.
    </p>

    <p style="margin:0 0 16px;">
        Toute l'équipe Casa Impact vous félicite. Notre équipe reviendra vers vous prochainement avec les prochaines étapes et les modalités pratiques.
    </p>

    <p style="margin:0;">Encore félicitations, et à très bientôt.</p>
@endsection
