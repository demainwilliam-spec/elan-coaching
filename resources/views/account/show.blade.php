@extends('layouts.app')

@section('title', 'Mon compte')
@section('noindex', true)

@section('content')

    <section class="message-page">
        @include('account.partials.alerts')

        <h2>Bonjour {{ $client->name }}</h2>
        <p class="message-text">Connecté avec l'adresse {{ $client->email }}.</p>

        {{-- Tant que l'adresse n'est pas confirmée : rappel + bouton pour renvoyer le mail --}}
        @unless ($client->hasVerifiedEmail())
            <div class="account-notice">
                <p>
                    <strong>Confirmez votre adresse email</strong> en cliquant sur le lien que nous vous avons envoyé :
                    vous pourrez ensuite réserver vos cours avec votre compte.
                    Sans confirmation, le compte est supprimé après {{ \App\Models\Client::UNVERIFIED_RETENTION_DAYS }} jours.
                </p>
                <form method="POST" action="{{ route('account.verification.send') }}">
                    @csrf
                    <button type="submit" class="btn btn-small btn-secondary">Renvoyer le mail</button>
                </form>
            </div>
        @endunless

        <div class="button-row">
            <a href="/#cours" class="btn">Voir les cours</a>
            {{-- Déconnexion en POST : un simple lien (GET) ne doit rien modifier --}}
            <form method="POST" action="{{ route('account.logout') }}">
                @csrf
                <button type="submit" class="btn btn-secondary">Se déconnecter</button>
            </form>
        </div>
    </section>

@endsection
