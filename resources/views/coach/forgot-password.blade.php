@extends('layouts.coach')

@section('title', 'Mot de passe oublié')

@section('content')
    <h2>Mot de passe oublié</h2>

    <p class="coach-summary">Indiquez votre adresse : nous vous enverrons un lien pour choisir un nouveau mot de passe.</p>

    <form class="coach-form" method="POST" action="{{ route('coach.password.request') }}">
        @csrf

        <div class="form-field @error('email') has-error @enderror">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" autocomplete="username" value="{{ old('email') }}" required autofocus>
            @error('email')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn">Recevoir le lien</button>
    </form>

    <p class="account-switch"><a href="{{ route('coach.login') }}">Retour à la connexion</a></p>
@endsection
