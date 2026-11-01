@extends('emails.layout')

@section('content')
    <p style="margin: 0 0 16px;">Bonjour {{ $client->name }},</p>

    <p style="margin: 0 0 24px;">
        Vous ne vous êtes pas connecté(e) à votre compte JF Coaching depuis près d'un an.
        Pour protéger vos données, les comptes inactifs sont supprimés :
        <strong>le vôtre le sera à partir du {{ $deletionDate->translatedFormat('j F Y') }}.</strong>
        Pour le garder, il suffit de vous connecter avant cette date.
    </p>

    <a href="{{ route('account.login') }}"
       style="display: inline-block; padding: 12px 24px; background-color: #0A0A0A; color: #FFFFFF; text-decoration: none; font-weight: bold; text-transform: uppercase; font-size: 14px; letter-spacing: 0.05em;">
        Me connecter
    </a>

    <p style="margin: 24px 0 0; font-size: 14px; color: #555;">
        Vous n'en avez plus besoin ? Ne faites rien : le compte sera supprimé automatiquement.
        Vous pourrez toujours réserver un cours sans compte, ou en recréer un à tout moment.
    </p>
@endsection
