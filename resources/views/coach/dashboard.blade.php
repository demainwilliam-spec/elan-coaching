@extends('layouts.coach')

@section('title', 'Tableau de bord')

@section('content')
    {{-- auth()->user() : l'utilisateur connecté (le coach) --}}
    <h2>Bonjour {{ auth()->user()->name }}</h2>

    <p class="coach-summary">{{ $courses->count() }} cours à venir</p>

    {{-- Mêmes classes que l'agenda public : même rendu et même responsive --}}
    <ul class="course-list">
        @forelse ($courses as $course)
            @include('coach.partials.course-row', ['course' => $course])
        @empty
            <li class="course-empty">Aucun cours programmé.</li>
        @endforelse
    </ul>
@endsection
