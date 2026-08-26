@extends('emails.layout')

@section('subject', 'Nous avons bien reçu votre message')

@section('content')
    <p style="margin:0 0 16px;">Bonjour {{ $contactMessage->nom }},</p>

    <p style="margin:0 0 16px;">
        Nous vous confirmons la bonne réception de votre message{{ $contactMessage->sujet ? " concernant « {$contactMessage->sujet} »" : '' }}.
    </p>

    <p style="margin:0 0 16px;">
        Notre équipe le traitera dans les meilleurs délais et reviendra vers vous à l'adresse {{ $contactMessage->email }}.
    </p>

    <p style="margin:0;">Merci de votre confiance et de votre intérêt pour Casa Impact.</p>
@endsection
