@extends('layouts.coach')

@section('title', 'Tableau de bord')

@section('content')
    {{-- auth()->user() : l'utilisateur connecté (le coach) --}}
    <h2>Bonjour {{ auth()->user()->name }}</h2>
    <p class="cancel-text">Le tableau de bord des cours arrive à l'étape suivante.</p>
@endsection
