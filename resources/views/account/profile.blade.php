@extends('layouts.app')

@section('title', 'Modifier mon profil')
@section('noindex', true)

@section('content')

    {{-- profile-page : page large et alignée à gauche (champs côte à côte, cf. .form-row) --}}
    <section class="message-page account-page profile-page">
        @include('account.partials.alerts')

        <h2>Mon profil</h2>
        <p class="message-text"><a href="{{ route('account.show') }}" class="account-back">‹ Retour à mon compte</a></p>

        {{-- 1. Nom et email. @error('champ', 'profile') : erreur rangée dans le sac « profile » --}}
        <h3 class="account-heading">Nom et email</h3>
        <form class="account-form account-section" method="POST" action="{{ route('account.profile.update') }}">
            @csrf
            {{-- Les formulaires HTML ne connaissent que GET et POST : @method('PUT') ajoute un champ caché
                 _method=PUT, que Laravel lit pour appeler la route Route::put --}}
            @method('PUT')

            {{-- form-row : les champs deux par deux (empilés sur téléphone) --}}
            <div class="form-row">
                <div class="form-field @error('first_name', 'profile') has-error @enderror">
                    <label for="first_name">Prénom</label>
                    <input type="text" id="first_name" name="first_name" autocomplete="given-name" maxlength="100" value="{{ old('first_name', $client->first_name) }}" required>
                    @error('first_name', 'profile') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-field @error('last_name', 'profile') has-error @enderror">
                    <label for="last_name">Nom</label>
                    <input type="text" id="last_name" name="last_name" autocomplete="family-name" maxlength="100" value="{{ old('last_name', $client->last_name) }}" required>
                    @error('last_name', 'profile') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-field @error('email', 'profile') has-error @enderror">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" autocomplete="email" maxlength="254" value="{{ old('email', $client->email) }}" required>
                    @error('email', 'profile') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-field @error('current_password', 'profile') has-error @enderror">
                    <label for="profile-current-password">Mot de passe actuel</label>
                    {{-- aria-describedby : le lecteur d'écran lit aussi la précision sous le champ --}}
                    <input type="password" id="profile-current-password" name="current_password" autocomplete="current-password"
                        aria-describedby="profile-current-password-hint">
                    <p class="form-hint" id="profile-current-password-hint">Seulement pour changer d'email</p>
                    @error('current_password', 'profile') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <p class="form-privacy">
                Nouvelle adresse : un lien de confirmation lui sera envoyé, et l'ancienne adresse sera prévenue du changement.
            </p>

            <button type="submit" class="btn">Enregistrer</button>
        </form>

        {{-- 2. Mot de passe --}}
        <h3 class="account-heading">Mot de passe</h3>
        {{-- Pas de formulaire de changement ici : un lien est envoyé par mail, comme pour « mot de passe oublié » --}}
        <form class="account-form account-section" method="POST" action="{{ route('account.profile.password') }}">
            @csrf

            <p class="message-text account-warning">
                Pour changer de mot de passe, recevez un lien à l'adresse {{ $client->email }}.
                Les autres appareils où vous aviez coché « Rester connecté » devront ensuite se reconnecter.
            </p>

            <button type="submit" class="btn">Recevoir le lien</button>
        </form>

        {{-- 3. Suppression du compte (droit à l'effacement, RGPD) --}}
        <h3 class="account-heading">Supprimer mon compte</h3>
        <form class="account-form" method="POST" action="{{ route('account.destroy') }}">
            @csrf
            @method('DELETE')

            <p class="message-text account-warning">
                La suppression est définitive : votre nom, votre email et votre mot de passe sont effacés.
                <strong>Vos réservations à venir sont maintenues</strong> : pour les annuler, faites-le avant
                depuis <a href="{{ route('account.show') }}#reservations">Mes réservations</a>, ou plus tard
                avec le lien reçu par mail.
            </p>

            {{-- Même grille que plus haut : le champ a la largeur d'une colonne --}}
            <div class="form-row">
                <div class="form-field @error('password', 'deletion') has-error @enderror">
                    <label for="deletion-password">Mot de passe</label>
                    <input type="password" id="deletion-password" name="password" autocomplete="current-password" required>
                    @error('password', 'deletion') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <button type="submit" class="btn btn-danger">Supprimer mon compte</button>
        </form>
    </section>

@endsection
