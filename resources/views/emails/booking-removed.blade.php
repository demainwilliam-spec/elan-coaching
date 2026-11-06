@extends('emails.layout')

@section('content')
    <p style="margin: 0 0 16px;">Bonjour {{ $name }},</p>

    <p style="margin: 0;">Le coach a annulé votre inscription au cours suivant :</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 24px 0;">
        <tr>
            <td style="padding: 4px 0 4px 16px; border-left: 3px solid #0A0A0A;">
                <p style="margin: 0; font-size: 18px; font-weight: bold;">{{ $courseTitle }}</p>
                <p style="margin: 4px 0 0; color: #555;">{{ $courseWhen }}</p>
                <p style="margin: 0; color: #555;">{{ $courseLocation }}</p>
            </td>
        </tr>
    </table>

    <p style="margin: 0;">
        Pour toute question, contactez le coach depuis
        <a href="{{ url('/#contact') }}" style="color: #0A0A0A;">le site</a>.
    </p>
@endsection
