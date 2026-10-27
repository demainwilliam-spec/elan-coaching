@extends('emails.layout')

@section('content')
    <p style="margin: 0 0 16px;">Bonjour {{ $name }},</p>

    <p style="margin: 0;"><strong>Le cours suivant est annulé</strong>, votre inscription l'est donc aussi :</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 24px 0;">
        <tr>
            <td style="padding: 4px 0 4px 16px; border-left: 3px solid #E8B33D;">
                <p style="margin: 0; font-size: 18px; font-weight: bold; text-decoration: line-through;">{{ $courseTitle }}</p>
                <p style="margin: 4px 0 0; color: #555;">{{ $courseWhen }}</p>
                <p style="margin: 0; color: #555;">{{ $courseLocation }}</p>
            </td>
        </tr>
    </table>

    @if ($coachMessage)
        <p style="margin: 0 0 8px; font-size: 14px; color: #555;">Le mot du coach :</p>
        {{-- e() échappe le texte, nl2br() garde les retours à la ligne (même méthode que le mail de contact) --}}
        <p style="margin: 0; padding: 12px 16px; background-color: #F4F2ED;">{!! nl2br(e($coachMessage)) !!}</p>
    @endif

    <p style="margin: 24px 0 0;">
        Vous n'avez rien à faire. Retrouvez les autres cours sur
        <a href="{{ url('/#cours') }}" style="color: #121212;">le site</a>.
    </p>
@endsection
