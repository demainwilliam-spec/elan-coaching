@extends('layouts.app')

@section('title', 'Accueil')

@section('content')

    <section class="hero">
        <h1>Reprenez votre élan.</h1>
        <p>
            Un accompagnement sportif construit autour de vous : bilan honnête,
            programme réaliste, suivi qui tient dans la durée.
        </p>
        <a href="#contact" class="btn">Réserver un bilan gratuit</a>
    </section>

    <section id="services">
        <h2>Mes services</h2>

        <div class="services-grid">
            <div class="service-card">
                <h3>Coaching individuel</h3>
                <p>Séances en tête-à-tête, programme entièrement personnalisé, ajusté à chaque rendez-vous.</p>
            </div>

            <div class="service-card">
                <h3>Petit groupe</h3>
                <p>De 2 à 4 personnes, même exigence qu'en individuel, tarif dégressif selon le nombre de participants.</p>
            </div>

            <div class="service-card">
                <h3>À distance</h3>
                <p>Un programme structuré à réaliser en autonomie, avec un suivi régulier pour ajuster la progression.</p>
            </div>
        </div>

        <a href="#contact" class="btn">Contactez-moi</a>
    </section>

    <section id="contact">
        <h2>Un bilan gratuit, sans engagement</h2>
        <p>30 minutes pour faire le point sur vos objectifs et voir si Élan est le bon accompagnement pour vous.</p>

        <div class="contact-actions">
            <a href="mailto:contact@elan-coaching.fr" class="btn">contact@elan-coaching.fr</a>
            <a href="tel:+32476000000" class="btn btn-secondary">+32 476 00 00 00</a>
        </div>

        <p class="contact-note">Prise de rendez-vous en ligne : bientôt disponible</p>
    </section>

@endsection