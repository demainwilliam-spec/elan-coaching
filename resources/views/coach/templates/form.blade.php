@extends('layouts.coach')

{{-- $template->exists : vrai en modification, faux en création (comme le formulaire de cours) --}}
@section('title', $template->exists ? 'Modifier un créneau' : 'Nouveau créneau')

@section('content')
    <h2>{{ $template->exists ? 'Modifier le créneau' : 'Nouveau créneau' }}</h2>

    <p class="coach-summary">
        Tous les champs sont obligatoires, sauf la description.
        @if ($template->exists)
            Les cours déjà créés à partir de ce créneau ne changent pas.
        @endif
    </p>

    <form class="coach-form" method="POST"
        action="{{ $template->exists ? route('coach.templates.update', $template) : route('coach.templates.store') }}">
        @csrf
        @if ($template->exists)
            @method('PUT')
        @endif

        <div class="form-row">
            <div class="form-field @error('weekday') has-error @enderror">
                <label for="weekday">Jour</label>
                {{-- <select> : liste déroulante ; chaque <option> envoie le numéro du jour (1 = lundi…) --}}
                <select id="weekday" name="weekday" required>
                    @foreach (\App\Models\CourseTemplate::WEEKDAYS as $number => $name)
                        {{-- @selected : ajoute l'attribut selected si la condition est vraie (jour du créneau ou saisie précédente) --}}
                        <option value="{{ $number }}" @selected((int) old('weekday', $template->weekday ?? 2) === $number)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('weekday') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-field @error('start_time') has-error @enderror">
                <label for="start_time">Heure</label>
                {{-- type="time" : sélecteur d'heure natif, envoie « 18:30 » ; substr retire les secondes de la base --}}
                <input type="time" id="start_time" name="start_time"
                    value="{{ old('start_time', $template->start_time ? substr($template->start_time, 0, 5) : '') }}" required>
                @error('start_time') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="form-field @error('title') has-error @enderror">
            <label for="title">Titre</label>
            <input type="text" id="title" name="title" value="{{ old('title', $template->title) }}" required maxlength="100">
            @error('title') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="form-field @error('description') has-error @enderror">
            <label for="description">Description (facultatif)</label>
            <textarea id="description" name="description" rows="3" maxlength="1000">{{ old('description', $template->description) }}</textarea>
            @error('description') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="form-field @error('location') has-error @enderror">
            <label for="location">Lieu</label>
            <input type="text" id="location" name="location" value="{{ old('location', $template->location) }}" required maxlength="150">
            @error('location') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="form-row">
            <div class="form-field @error('duration_minutes') has-error @enderror">
                <label for="duration_minutes">Durée (minutes)</label>
                <input type="number" id="duration_minutes" name="duration_minutes"
                    value="{{ old('duration_minutes', $template->duration_minutes) }}" required min="15" max="240" step="5">
                @error('duration_minutes') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-field @error('capacity') has-error @enderror">
                <label for="capacity">Nombre de places</label>
                <input type="number" id="capacity" name="capacity"
                    value="{{ old('capacity', $template->capacity) }}" required min="1" max="100">
                @error('capacity') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="button-row">
            <button type="submit" class="btn">{{ $template->exists ? 'Enregistrer' : 'Ajouter le créneau' }}</button>
            <a href="{{ route('coach.templates.index') }}" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
@endsection
