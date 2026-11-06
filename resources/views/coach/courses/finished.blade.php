@extends('layouts.coach')

@section('title', 'Cours terminés')

@section('content')
    <h2>Cours terminés</h2>

    <p class="coach-summary">
        Quelqu'un n'est pas venu ? Ouvrez les inscrits du cours et retirez cette personne :
        son compteur de cours baisse de 1, sans qu'elle reçoive de mail.
    </p>

    <a href="{{ route('coach.dashboard') }}" class="btn coach-new">Retour aux cours</a>

    @if ($courses->isEmpty())
        <p class="coach-summary">Aucun cours terminé pour l'instant.</p>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th scope="col">Cours</th>
                    <th scope="col">Confirmés</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($courses as $course)
                    <tr>
                        <td data-label="Date">{{ ucfirst($course->starts_at->translatedFormat('l j F \à H\hi')) }}</td>
                        <td data-label="Cours">{{ $course->title }}</td>
                        <td data-label="Confirmés">{{ $course->confirmed_count }}</td>
                        <td class="data-table-action">
                            <div class="course-actions">
                                <a href="{{ route('coach.bookings.index', $course) }}">Inscrits</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
