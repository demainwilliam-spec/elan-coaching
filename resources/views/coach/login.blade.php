@extends('layouts.coach')

@section('title', 'Connexion')

@section('content')
    <h2>Espace coach</h2>

    <form class="coach-form" method="POST" action="{{ route('coach.login') }}">
        @csrf

        <div class="form-field @error('email') has-error @enderror">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" autocomplete="username" value="{{ old('email') }}" required autofocus>
            @error('email')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field">
            <label for="password">Mot de passe</label>
            {{-- autocomplete="current-password" : le gestionnaire de mots de passe du téléphone peut le remplir --}}
            <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>

        <label class="form-check">
            <input type="checkbox" name="remember" value="1" checked>
            Rester connecté sur cet appareil
        </label>

        <button type="submit" class="btn">Se connecter</button>
    </form>
@endsection
