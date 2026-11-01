@extends('emails.layout')

@section('content')
    <p style="margin: 0 0 16px;">Bonjour {{ $client->name }},</p>

    <p style="margin: 0 0 24px;">
        Vous avez demandé à réinitialiser le mot de passe de votre compte.
        Cliquez sur le bouton ci-dessous pour en choisir un nouveau :
    </p>

    <a href="{{ $url }}"
       style="display: inline-block; padding: 12px 24px; background-color: #0A0A0A; color: #FFFFFF; text-decoration: none; font-weight: bold; text-transform: uppercase; font-size: 14px; letter-spacing: 0.05em;">
        Choisir un nouveau mot de passe
    </a>

    <p style="margin: 24px 0 0; font-size: 14px; color: #555;">
        Ce lien est valable {{ $minutes }} minutes et ne fonctionne qu'une fois.
        Si vous n'êtes pas à l'origine de cette demande, ignorez ce message : votre mot de passe reste inchangé.
    </p>
@endsection
