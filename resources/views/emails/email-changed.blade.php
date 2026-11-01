@extends('emails.layout')

@section('content')
    <p style="margin: 0 0 16px;">Bonjour {{ $name }},</p>

    <p style="margin: 0 0 16px;">
        L'adresse email de votre compte JF Coaching vient d'être remplacée par <strong>{{ $newEmail }}</strong>.
        Les prochains messages concernant votre compte et vos réservations seront envoyés à cette adresse.
    </p>

    <p style="margin: 0; font-size: 14px; color: #555;">
        Si vous n'êtes pas à l'origine de ce changement, quelqu'un utilise peut-être votre compte :
        <a href="{{ url('/#contact') }}" style="color: #0A0A0A;">contactez le coach</a> au plus vite.
    </p>
@endsection
