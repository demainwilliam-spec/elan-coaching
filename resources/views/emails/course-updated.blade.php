@extends('emails.layout')

@section('content')
    <p style="margin: 0 0 16px;">Bonjour {{ $booking->name }},</p>

    <p style="margin: 0;">
        <strong>Le cours auquel vous êtes inscrit(e) a été modifié.</strong>
        Voici les informations à jour :
    </p>

    @include('emails.partials.course-summary')

    <p style="margin: 24px 0 0; font-size: 14px; color: #555;">
        @if ($booking->status === \App\Enums\BookingStatus::Confirmed)
            Votre place est confirmée.
        @else
            Vous êtes n°{{ $booking->waitingPosition() }} sur la liste d'attente.
        @endif
    </p>
@endsection
