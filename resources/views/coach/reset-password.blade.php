@extends('layouts.coach')

@section('title', 'Nouveau mot de passe')

@section('content')
    <h2>Nouveau mot de passe</h2>

    <p class="coach-summary">Choisissez le mot de passe du compte {{ $email }}.</p>

    <form class="coach-form" method="POST" action="{{ route('coach.password.update') }}">
        @csrf
        {{-- Champs cachés : le jeton et l'adresse venus du lien du mail, renvoyés avec le formulaire --}}
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">
        {{-- Champ invisible lu par les gestionnaires de mots de passe, pour enregistrer le bon compte --}}
        <input type="email" value="{{ $email }}" autocomplete="username" hidden>

        <div class="form-field @error('password') has-error @enderror">
            <label for="password">Nouveau mot de passe (12 caractères minimum)</label>
            <input type="password" id="password" name="password" autocomplete="new-password" minlength="12" required autofocus>
            @error('password')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field">
            <label for="password_confirmation">Confirmer le mot de passe</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
        </div>

        <button type="submit" class="btn">Enregistrer</button>
    </form>
@endsection
