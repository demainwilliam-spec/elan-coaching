@extends('emails.layout')

@section('content')
    <p style="margin: 0 0 16px;">Bonjour {{ $booking->name }},</p>

    @if ($booking->status === \App\Enums\BookingStatus::Confirmed)
        <p style="margin: 0;"><strong>Votre place est confirmée.</strong> À bientôt !</p>
    @else
        <p style="margin: 0;">
            Ce cours est complet : vous êtes <strong>n°{{ $booking->waitingPosition() }} sur la liste d'attente</strong>.
            Si une place se libère, vous serez inscrit(e) automatiquement et prévenu(e) par mail.
        </p>
    @endif

    @include('emails.partials.course-summary')
@endsection
