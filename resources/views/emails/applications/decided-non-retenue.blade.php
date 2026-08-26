@extends('emails.layout')

@section('subject', 'Suite à votre candidature')

@section('content')
    <p style="margin:0 0 16px;">Bonjour {{ $application->prenom }},</p>

    <p style="margin:0 0 16px;">
        Nous vous remercions pour l'intérêt que vous avez porté à
        <strong>{{ $application->applicationCall?->titre ?? "cet appel à candidatures" }}</strong> (référence <strong>{{ $application->reference }}</strong>).
    </p>

    <p style="margin:0 0 16px;">
        Après examen attentif de votre dossier, nous sommes au regret de vous informer que votre candidature n'a pas été retenue pour cette édition.
    </p>

    <p style="margin:0;">
        Nous vous encourageons à suivre nos prochains appels à candidatures et vous remercions pour votre intérêt envers Casa Impact.
    </p>
@endsection
