@extends('layouts.coach')

{{-- $course->exists : vrai si le cours est déjà en base → modification ; faux → création (ou duplication) --}}
@section('title', $course->exists ? 'Modifier un cours' : 'Nouveau cours')

@section('content')
    <h2>{{ $course->exists ? 'Modifier le cours' : 'Nouveau cours' }}</h2>

    @if ($course->exists && ($registered = $course->bookings()->count()) > 0)
        <p class="coach-summary">
            {{ $registered }} {{ $registered > 1 ? 'personnes inscrites' : 'personne inscrite' }} :
            si la date, l'heure, la durée ou le lieu change, {{ $registered > 1 ? 'elles seront prévenues' : 'elle sera prévenue' }} par mail.
        </p>
    @else
        <p class="coach-summary">Tous les champs sont obligatoires, sauf la description.</p>
    @endif

    <form class="coach-form" method="POST"
        action="{{ $course->exists ? route('coach.courses.update', $course) : route('coach.courses.store') }}">
        @csrf
        {{-- Les formulaires HTML ne connaissent que GET et POST : @method ajoute un champ caché _method=PUT
             que Laravel lit pour appeler la route PUT --}}
        @if ($course->exists)
            @method('PUT')
        @endif

        <div class="form-field @error('title') has-error @enderror">
            <label for="title">Titre</label>
            {{-- old('title', $course->title) : la saisie après une erreur, sinon la valeur du cours --}}
            <input type="text" id="title" name="title" value="{{ old('title', $course->title) }}" required maxlength="100">
            @error('title') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="form-field @error('description') has-error @enderror">
            <label for="description">Description (facultatif)</label>
            <textarea id="description" name="description" rows="3" maxlength="1000">{{ old('description', $course->description) }}</textarea>
            @error('description') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="form-field @error('location') has-error @enderror">
            <label for="location">Lieu</label>
            <input type="text" id="location" name="location" value="{{ old('location', $course->location) }}" required maxlength="150">
            @error('location') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="form-field @error('starts_at') has-error @enderror">
            <label for="starts_at">Date et heure</label>
            {{-- datetime-local : sélecteur date + heure natif (calendrier sur téléphone), au format 2026-11-03T18:30 --}}
            <input type="datetime-local" id="starts_at" name="starts_at"
                value="{{ old('starts_at', $course->starts_at?->format('Y-m-d\TH:i')) }}" required>
            @error('starts_at') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="form-row">
            <div class="form-field @error('duration_minutes') has-error @enderror">
                <label for="duration_minutes">Durée (minutes)</label>
                {{-- step="5" : les flèches du champ avancent de 5 en 5 --}}
                <input type="number" id="duration_minutes" name="duration_minutes"
                    value="{{ old('duration_minutes', $course->duration_minutes) }}" required min="15" max="240" step="5">
                @error('duration_minutes') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-field @error('capacity') has-error @enderror">
                <label for="capacity">Nombre de places</label>
                <input type="number" id="capacity" name="capacity"
                    value="{{ old('capacity', $course->capacity) }}" required min="1" max="100">
                @error('capacity') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="cancel-actions">
            <button type="submit" class="btn">{{ $course->exists ? 'Enregistrer' : 'Créer le cours' }}</button>
            <a href="{{ route('coach.dashboard') }}" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
@endsection
