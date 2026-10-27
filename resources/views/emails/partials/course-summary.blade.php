{{-- Rappel du cours + bouton d'annulation, commun aux mails de réservation. Attend $booking --}}
@php($course = $booking->course)

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 24px 0;">
    <tr>
        <td style="padding: 4px 0 4px 16px; border-left: 3px solid #E8B33D;">
            <p style="margin: 0; font-size: 18px; font-weight: bold;">{{ $course->title }}</p>
            <p style="margin: 4px 0 0; color: #555;">
                {{ ucfirst($course->starts_at->translatedFormat('l j F \à H\hi')) }} · {{ $course->duration_minutes }} min
            </p>
            <p style="margin: 0; color: #555;">{{ $course->location }}</p>
        </td>
    </tr>
</table>

<p style="margin: 24px 0 8px; font-size: 14px; color: #555;">
    Un empêchement ? Annulation possible jusqu'à {{ \App\Models\Course::CANCELLATION_CUTOFF_HOURS }} h avant le début du cours :
</p>
{{-- route() donne une URL absolue, construite avec APP_URL (le worker n'a pas de requête HTTP pour deviner l'adresse) --}}
<a href="{{ route('bookings.cancel', $booking->cancel_token) }}"
   style="display: inline-block; padding: 12px 24px; background-color: #E8B33D; color: #121212; text-decoration: none; font-weight: bold; text-transform: uppercase; font-size: 14px; letter-spacing: 0.05em;">
    Annuler ma réservation
</a>
