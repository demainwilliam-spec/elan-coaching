@extends('emails.layout')

@section('content')
    <p style="margin: 0 0 16px;">Bonjour {{ $client->first_name }},</p>

    <p style="margin: 0 0 24px;">
        Merci d'avoir créé votre compte. Cliquez sur le bouton ci-dessous pour confirmer votre adresse email :
        vous pourrez ensuite réserver vos cours en un clic.
    </p>

    <a href="{{ $url }}"
       style="display: inline-block; padding: 12px 24px; background-color: #0A0A0A; color: #FFFFFF; text-decoration: none; font-weight: bold; text-transform: uppercase; font-size: 14px; letter-spacing: 0.05em;">
        Confirmer mon adresse
    </a>

    <p style="margin: 24px 0 0; font-size: 14px; color: #555;">
        Ce lien est valable {{ \App\Models\Client::VERIFICATION_LINK_MINUTES }} minutes.
        Si vous n'avez pas créé de compte, ignorez ce message : sans confirmation, le compte sera supprimé
        automatiquement après {{ \App\Models\Client::UNVERIFIED_RETENTION_DAYS }} jours.
    </p>
@endsection
