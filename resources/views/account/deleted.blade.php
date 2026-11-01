@extends('layouts.app')

@section('title', 'Compte supprimé')
@section('noindex', true)

@section('content')

    <section class="message-page">
        <h2>Compte supprimé</h2>
        <p class="message-text">
            Votre compte et ses informations de connexion ont été supprimés.
            Vos réservations à venir sont maintenues : vous pouvez toujours les annuler
            avec le lien reçu par mail lors de chaque réservation.
        </p>
        <a href="/" class="btn">Retour à l'accueil</a>
    </section>

@endsection
