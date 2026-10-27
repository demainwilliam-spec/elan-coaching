{{-- Une personne inscrite. Reçoit $booking, et $position (rang dans la liste d'attente, null si confirmée) --}}
<li class="coach-booking">
    <div>
        <p class="coach-booking-name">
            @if ($position)
                <span class="coach-booking-position">n°{{ $position }}</span>
            @endif
            {{ $booking->name }}
        </p>
        {{-- mailto: : un clic ouvre la messagerie du téléphone avec l'adresse déjà remplie --}}
        <p class="course-meta"><a href="mailto:{{ $booking->email }}">{{ $booking->email }}</a></p>
        <p class="course-meta">Inscrit(e) le {{ $booking->created_at->translatedFormat('j F à H\hi') }}</p>
    </div>

    {{-- @js() : échappe le texte pour du JavaScript (un nom contenant une apostrophe ne casse rien) --}}
    <form method="POST" action="{{ route('coach.bookings.destroy', $booking) }}"
        onsubmit="return confirm(@js('Retirer '.$booking->name.' du cours ? Un mail le lui signalera.'))">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger btn-small">Retirer</button>
    </form>
</li>
