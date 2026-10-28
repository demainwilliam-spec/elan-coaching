@extends('layouts.app')

@section('title', 'Annuler une réservation')

@section('content')

    <section class="message-page">
        {{-- $booking vaut null quand le jeton est inconnu (cf. missing() dans routes/web.php) --}}
        @if ($booking === null)
            <h2>Lien plus valide</h2>
            <p class="message-text">
                Cette réservation n'existe plus : elle a peut-être déjà été annulée.
                En cas de doute, contactez directement le coach.
            </p>
            <a href="/#cours" class="btn">Voir les cours</a>
        @else
            @php($course = $booking->course)

            <h2>Annuler ma réservation</h2>

            <div class="booking-summary">
                <p class="booking-summary-title">{{ $course->title }}</p>
                <p class="course-meta">
                    {{ ucfirst($course->starts_at->translatedFormat('l j F \à H\hi')) }} · {{ $course->duration_minutes }} min
                </p>
                <p class="course-meta">{{ $course->location }}</p>
                <p class="course-description">
                    @if ($booking->status === \App\Enums\BookingStatus::Confirmed)
                        Place confirmée au nom de {{ $booking->name }}.
                    @else
                        {{ $booking->name }}, vous êtes n°{{ $booking->waitingPosition() }} sur la liste d'attente.
                    @endif
                </p>
            </div>

            {{-- cancel_error : refus renvoyé par CancellationController@destroy --}}
            @if (session('cancel_error'))
                <div class="form-alert form-alert-error">{{ session('cancel_error') }}</div>
            @elseif (! $course->cancellationIsOpen())
                <div class="form-alert form-alert-error">
                    Le cours commence dans moins de 24 h : l'annulation en ligne n'est plus possible.
                    Contactez directement le coach.
                </div>
            @endif

            <div class="button-row">
                @if ($course->cancellationIsOpen())
                    {{-- Un vrai formulaire POST (avec @csrf) : seul un clic volontaire annule --}}
                    <form method="POST" action="{{ route('bookings.destroy', $booking->cancel_token) }}">
                        @csrf
                        <button type="submit" class="btn">Confirmer l'annulation</button>
                    </form>
                    <a href="/" class="btn btn-secondary">Garder ma réservation</a>
                @else
                    <a href="/#contact" class="btn">Contacter le coach</a>
                @endif
            </div>
        @endif
    </section>

@endsection
