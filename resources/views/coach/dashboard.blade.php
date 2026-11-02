@extends('layouts.coach')

@section('title', 'Tableau de bord')

@section('content')
    {{-- auth('web')->user() : le coach connecté (guard web) --}}
    <h2>Bonjour {{ auth('web')->user()->name }}</h2>

    <p class="coach-summary">{{ $courses->count() }} cours à venir</p>

    {{-- button-row : côte à côte sur ordinateur, empilés sur téléphone --}}
    <div class="button-row coach-new">
        <a href="{{ route('coach.courses.create') }}" class="btn">Nouveau cours</a>
        <a href="{{ route('coach.templates.index') }}" class="btn">Planning type</a>
    </div>

    {{-- Mêmes classes que l'agenda public : même rendu et même responsive --}}
    <ul class="course-list">
        @forelse ($courses as $course)
            @include('coach.partials.course-row', ['course' => $course])
        @empty
            <li class="course-empty">Aucun cours programmé.</li>
        @endforelse
    </ul>
@endsection
