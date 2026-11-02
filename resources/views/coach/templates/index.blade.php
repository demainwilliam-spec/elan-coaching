@extends('layouts.coach')

@section('title', 'Planning type')

@section('content')
    <h2>Planning type</h2>

    <p class="coach-summary">
        Vos créneaux habituels de la semaine. Ils servent de modèle pour créer les cours ;
        les modifier ne change pas les cours déjà créés.
    </p>

    <a href="{{ route('coach.templates.create') }}" class="btn coach-new">Ajouter un créneau</a>

    @if ($templates->isEmpty())
        <p class="coach-summary">Aucun créneau pour l'instant.</p>
    @else
        {{-- Même tableau que « Mes réservations » côté client : empilé sur téléphone grâce à data-label --}}
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Jour</th>
                    <th scope="col">Cours</th>
                    <th scope="col">Lieu</th>
                    <th scope="col">Places</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($templates as $template)
                    <tr>
                        <td data-label="Jour">{{ $template->weekdayName() }} · {{ $template->startTimeLabel() }}</td>
                        <td data-label="Cours">
                            {{ $template->title }}
                            <span class="data-table-muted">{{ $template->duration_minutes }} min</span>
                        </td>
                        <td data-label="Lieu">{{ $template->location }}</td>
                        <td data-label="Places">{{ $template->capacity }}</td>
                        <td class="data-table-action">
                            <div class="course-actions">
                                <a href="{{ route('coach.templates.edit', $template) }}">Modifier</a>
                                {{-- Suppression en DELETE (formulaire) : un simple lien (GET) ne doit rien modifier --}}
                                <form method="POST" action="{{ route('coach.templates.destroy', $template) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="link-button is-danger">Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
