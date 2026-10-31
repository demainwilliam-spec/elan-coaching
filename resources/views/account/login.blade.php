@extends('layouts.app')

@section('title', 'Connexion')
@section('noindex', true)

@section('content')

    <section class="message-page">
        <h2>Connexion</h2>
        <p class="message-text">Accédez à votre compte pour réserver et gérer vos cours.</p>

        <form class="account-form" method="POST" action="{{ route('account.login') }}">
            @csrf

            <div class="form-field @error('email') has-error @enderror">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" autocomplete="username" value="{{ old('email') }}" required autofocus>
                @error('email') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-field @error('password') has-error @enderror">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
                @error('password') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <label class="form-check">
                <input type="checkbox" name="remember" value="1">
                Rester connecté sur cet appareil
            </label>

            <button type="submit" class="btn">Se connecter</button>
        </form>

        <p class="account-switch">Pas encore de compte ? <a href="{{ route('account.register') }}">Créer un compte</a></p>
    </section>

@endsection
