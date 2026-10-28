@extends('layouts.coach')

@section('title', 'Inscrits')

@section('content')
    <h2>Inscrits</h2>

    <div class="booking-summary coach-course-summary">
        <p class="booking-summary-title">{{ $course->title }}</p>
        <p class="course-meta">
            {{ ucfirst($course->starts_at->translatedFormat('l j F \à H\hi')) }} · {{ $course->duration_minutes }} min · {{ $course->location }}
        </p>
    </div>

    <h3 class="coach-list-title">Confirmés · {{ $confirmed->count() }} / {{ $course->capacity }}</h3>
    <ul class="coach-booking-list">
        @forelse ($confirmed as $booking)
            @include('coach.bookings.row', ['booking' => $booking, 'position' => null])
        @empty
            <li class="course-empty">Personne pour l'instant.</li>
        @endforelse
    </ul>

    @if ($waiting->isNotEmpty())
        <h3 class="coach-list-title">Liste d'attente · {{ $waiting->count() }}</h3>
        <ul class="coach-booking-list">
            {{-- $loop->iteration : numéro du tour de boucle (1, 2, 3...) = position dans la file --}}
            @foreach ($waiting as $booking)
                @include('coach.bookings.row', ['booking' => $booking, 'position' => $loop->iteration])
            @endforeach
        </ul>
    @endif

    <h3 class="coach-list-title">Inscrire quelqu'un</h3>
    <form class="coach-form" method="POST" action="{{ route('coach.bookings.store', $course) }}">
        @csrf

        <div class="form-field @error('name') has-error @enderror">
            <label for="name">Nom</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="100">
            @error('name') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="form-field @error('email') has-error @enderror">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="254">
            @error('email') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <p class="booking-notice">
            Mêmes règles qu'en ligne : place confirmée s'il en reste, sinon liste d'attente.
            La personne reçoit un mail avec son lien d'annulation.
        </p>

        <div class="button-row">
            <button type="submit" class="btn">Inscrire</button>
            <a href="{{ route('coach.dashboard') }}" class="btn btn-secondary">Retour aux cours</a>
        </div>
    </form>
@endsection
