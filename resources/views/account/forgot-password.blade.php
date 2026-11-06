@extends('layouts.app')

@section('title', 'Mot de passe oublié')
@section('noindex', true)

@section('content')

    <section class="message-page">
        @include('account.partials.alerts')

        <h2>Mot de passe oublié</h2>
        <p class="message-text">Indiquez l'adresse de votre compte : nous vous enverrons un lien pour choisir un nouveau mot de passe.</p>

        <form class="account-form" method="POST" action="{{ route('account.password.request') }}">
            @csrf

            <div class="form-field @error('email') has-error @enderror">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" autocomplete="email" value="{{ old('email') }}" required autofocus>
                @error('email') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn">Recevoir le lien</button>
        </form>

        <p class="account-switch"><a href="{{ route('account.login') }}">Retour à la connexion</a></p>
    </section>

@endsection
