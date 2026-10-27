{{-- Une ligne du tableau de bord du coach. Même structure que partials/course-row (agenda public),
     mais la colonne de droite montre les chiffres au lieu du bouton "Réserver". Reçoit $course (avec withBookingCounts) --}}
<li @class(['course-row', 'is-full' => $course->isFull()])>

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
    </div>

    <div class="course-booking">
        <p class="course-places">
            {{ $course->confirmed_count }} / {{ $course->capacity }} confirmés
            @if ($course->waiting_count > 0)
                · {{ $course->waiting_count }} en attente
            @endif
        </p>

        <div class="course-bar" aria-hidden="true">
            <span style="width: {{ $course->fillPercent() }}%"></span>
        </div>

        <p class="course-actions">
            <a href="{{ route('coach.courses.edit', $course) }}">Modifier</a>
            <a href="{{ route('coach.courses.duplicate', $course) }}">Dupliquer</a>
            <a href="{{ route('coach.courses.delete', $course) }}" class="is-danger">Supprimer</a>
        </p>
    </div>
</li>
