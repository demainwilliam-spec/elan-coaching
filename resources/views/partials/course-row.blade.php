{{-- Une ligne de l'agenda des cours. Reçoit $course (avec withBookingCounts) --}}
{{-- @class : n'ajoute chaque classe que si sa condition est vraie --}}
<li @class(['course-row', 'is-full' => $course->isFull(), 'is-last' => $course->placesLeft() === 1])>

    {{-- <time> : texte lisible pour l'humain, date exacte (ISO 8601) pour les machines --}}
    {{-- translatedFormat : comme format(), mais en français ; str_replace retire le point de "mer." --}}
    <time class="course-date" datetime="{{ $course->starts_at->toIso8601String() }}">
        <span class="course-weekday">{{ str_replace('.', '', $course->starts_at->translatedFormat('D')) }}</span>
        <span class="course-day">{{ $course->starts_at->format('d') }}</span>
        <span class="course-month">{{ str_replace('.', '', $course->starts_at->translatedFormat('M')) }}</span>
    </time>

    <div class="course-info">
        <h3>{{ $course->title }}</h3>
        <p class="course-meta">
            {{ $course->starts_at->format('H\hi') }} · {{ $course->duration_minutes }} min · {{ $course->location }}
        </p>
        @if ($course->description)
            <p class="course-description">{{ $course->description }}</p>
        @endif
    </div>

    <div class="course-booking">
        <p class="course-places">
            @if ($course->isFull())
                Complet
                @if ($course->waiting_count > 0)
                    · {{ $course->waiting_count }} en attente
                @endif
            @elseif ($course->placesLeft() === 1)
                Dernière place !
            @else
                {{ $course->placesLeft() }} places restantes
            @endif
        </p>

        {{-- aria-hidden : ignorée par les lecteurs d'écran, le texte au-dessus suffit --}}
        <div class="course-bar" aria-hidden="true">
            <span style="width: {{ $course->fillPercent() }}%"></span>
        </div>

        {{-- type="button" : ne fait rien pour l'instant, branché au formulaire au lot 2 --}}
        <button type="button" @class(['btn', 'btn-secondary' => $course->isFull()])>
            {{ $course->isFull() ? "Liste d'attente" : 'Réserver' }}
        </button>
    </div>
</li>
