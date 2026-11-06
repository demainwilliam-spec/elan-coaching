{{-- Gabarit commun des pages d'erreur : chaque page (404, 419...) ne remplit que code, heading et message.
     Héritage à deux niveaux : layouts.app → errors.layout → errors.404 --}}
@extends('layouts.app')

@section('content')

    <section class="message-page">
        <p class="error-code">@yield('code')</p>
        <h2>@yield('heading')</h2>
        <p class="message-text">@yield('message')</p>

        <div class="button-row">
            <a href="/" class="btn">Retour à l'accueil</a>
        </div>
    </section>

@endsection
