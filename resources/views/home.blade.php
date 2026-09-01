@extends('layouts.app')

@section('title', 'Accueil')

@section('content')
    <section class="hero">
        <h1>Reprenez votre élan.</h1>
        <p>
            Un accompagnement sportif construit autour de vous : bilan honnête,
            programme réaliste, suivi qui tient dans la durée.
        </p>
        <a href="{{ url('/contact') }}" class="btn">Réserver un bilan gratuit</a>
    </section>
@endsection