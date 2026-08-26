@extends('emails.layout')

@section('subject', 'Confirmation de votre candidature')

@section('content')
    <p style="margin:0 0 16px;">Bonjour {{ $application->prenom }},</p>

    <p style="margin:0 0 16px;">
        Nous avons bien reçu votre candidature pour
        <strong>{{ $application->applicationCall?->titre ?? "l'appel à candidatures" }}</strong>.
    </p>

    <p style="margin:0 0 16px;">
        Votre référence de candidature est : <strong>{{ $application->reference }}</strong>. Merci de la conserver, elle vous sera utile pour tout échange avec notre équipe.
    </p>

    @if ($application->statut === \App\Enums\ApplicationStatus::EnListeAttente)
        <p style="margin:0 0 16px;">
            Le nombre de places pour cet appel est atteint : votre candidature a été placée en <strong>liste d'attente</strong>. Nous vous recontacterons si une place venait à se libérer.
        </p>
    @else
        <p style="margin:0 0 16px;">
            Votre candidature est en cours d'examen par notre équipe. Vous recevrez un nouvel email dès qu'une décision aura été prise.
        </p>
    @endif

    <p style="margin:0;">Merci de votre intérêt pour Casa Impact.</p>
@endsection
