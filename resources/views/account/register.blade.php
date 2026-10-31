@extends('layouts.app')

@section('title', 'Créer un compte')
@section('noindex', true)

@section('content')

    <section class="message-page">
        <h2>Créer un compte</h2>
        <p class="message-text">Réservez vos cours en un clic et retrouvez toutes vos réservations au même endroit.</p>

        <form class="account-form" method="POST" action="{{ route('account.register') }}">
            @csrf

            <div class="form-field @error('name') has-error @enderror">
                <label for="name">Nom</label>
                {{-- autocomplete : aide le navigateur / téléphone à préremplir le bon champ --}}
                <input type="text" id="name" name="name" autocomplete="name" maxlength="100" value="{{ old('name') }}" required autofocus>
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-field @error('email') has-error @enderror">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" autocomplete="email" maxlength="254" value="{{ old('email') }}" required>
                @error('email') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-field @error('password') has-error @enderror">
                <label for="password">Mot de passe (8 caractères minimum)</label>
                {{-- new-password : le gestionnaire de mots de passe propose d'en générer un solide --}}
                <input type="password" id="password" name="password" autocomplete="new-password" minlength="8" required>
                @error('password') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-field">
                <label for="password_confirmation">Confirmer le mot de passe</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn">Créer mon compte</button>

            <p class="form-privacy">
                Vos données servent uniquement à gérer vos réservations
                (<a href="{{ route('legal.privacy') }}">en savoir plus</a>).
            </p>
        </form>

        <p class="account-switch">Déjà un compte ? <a href="{{ route('account.login') }}">Se connecter</a></p>
    </section>

@endsection
