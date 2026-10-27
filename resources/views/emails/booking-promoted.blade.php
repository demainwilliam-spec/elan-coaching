@extends('emails.layout')

@section('content')
    <p style="margin: 0 0 16px;">Bonjour {{ $booking->name }},</p>

    <p style="margin: 0;">
        Bonne nouvelle : une place s'est libérée. Vous quittez la liste d'attente,
        <strong>votre place est confirmée.</strong>
    </p>

    @include('emails.partials.course-summary')
@endsection
