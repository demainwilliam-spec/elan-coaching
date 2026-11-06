@extends('layouts.coach')

@section('title', 'Supprimer un cours')

@section('content')
    <h2>Supprimer le cours</h2>

    <p class="coach-summary">
        @if ($registered > 0)
            {{ $registered }} {{ $registered > 1 ? 'personnes inscrites seront prévenues' : 'personne inscrite sera prévenue' }}
            par mail (confirmés et liste d'attente).
        @else
            Personne n'est inscrit à ce cours.
        @endif
    </p>

    <form class="coach-form" method="POST" action="{{ route('coach.courses.destroy', $course) }}">
        @csrf
        {{-- Champ caché _method=DELETE : Laravel appelle la route DELETE --}}
        @method('DELETE')

        <div class="booking-summary">
            <p class="booking-summary-title">{{ $course->title }}</p>
            <p class="course-meta">
                {{ ucfirst($course->starts_at->translatedFormat('l j F \à H\hi')) }} · {{ $course->duration_minutes }} min
            </p>
            <p class="course-meta">{{ $course->location }}</p>
        </div>

        {{-- Le champ du message n'a de sens que s'il y a des inscrits à prévenir --}}
        @if ($registered > 0)
            <div class="form-field @error('coach_message') has-error @enderror">
                <label for="coach_message">Message pour les inscrits (facultatif)</label>
                <textarea id="coach_message" name="coach_message" rows="4" maxlength="1000"
                    placeholder="Ex. : Désolé, je suis malade. Le cours est reporté à jeudi, même heure.">{{ old('coach_message') }}</textarea>
                @error('coach_message') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        @endif

        <p class="booking-notice">Cette action est définitive.</p>

        <div class="button-row">
            <button type="submit" class="btn btn-danger">Supprimer le cours</button>
            <a href="{{ route('coach.dashboard') }}" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
@endsection
