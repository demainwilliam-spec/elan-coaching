{{-- Une personne inscrite. Reçoit $booking, et $position (rang dans la liste d'attente, null si confirmée) --}}
<li class="coach-booking">
    <div>
        <p class="coach-booking-name">
            @if ($position)
                <span class="coach-booking-position">n°{{ $position }}</span>
            @endif
            {{ $booking->last_name }} {{ $booking->first_name }}
        </p>
        {{-- mailto: : un clic ouvre la messagerie du téléphone avec l'adresse déjà remplie --}}
        <p class="course-meta"><a href="mailto:{{ $booking->email }}">{{ $booking->email }}</a></p>
        <p class="course-meta">Inscrit(e) le {{ $booking->created_at->translatedFormat('j F à H\hi') }}</p>
    </div>

    {{-- data-confirm : question posée avant l'envoi par resources/js/confirm-forms.js ; {{ }} échappe le texte (nom avec apostrophe compris) --}}
    <form method="POST" action="{{ route('coach.bookings.destroy', $booking) }}"
        data-confirm="{{ 'Retirer '.$booking->name.' du cours ? '.($course->hasEnded() ? 'Son compteur de cours baissera, sans mail.' : 'Un mail le lui signalera.') }}">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger btn-small">Retirer</button>
    </form>
</li>
