@extends('layouts.app')

@section('title', 'Mon compte')
@section('noindex', true)

@section('content')

    <section class="message-page">
        @if (session('account_success'))
            <div class="form-alert">{{ session('account_success') }}</div>
        @endif

        <h2>Bonjour {{ $client->name }}</h2>
        <p class="message-text">Connecté avec l'adresse {{ $client->email }}.</p>

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
