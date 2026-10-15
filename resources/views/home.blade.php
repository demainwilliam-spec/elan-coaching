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
                <svg class="service-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"/>
                </svg>
                <h3>Coaching individuel</h3>
                <p>Séances en tête-à-tête, programme entièrement personnalisé, ajusté à chaque rendez-vous.</p>
            </div>

            <div class="service-card">
                <svg class="service-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="9" cy="7" r="3"/>
                    <circle cx="17" cy="9" r="2.5"/>
                    <path d="M2 20c0-3.5 3-6.5 7-6.5s7 3 7 6.5"/>
                    <path d="M17 13.5c2.5 0.3 4.5 2.5 4.5 6.5"/>
                </svg>
                <h3>Petit groupe</h3>
                <p>De 2 à 4 personnes, même exigence qu'en individuel, tarif dégressif selon le nombre de participants.</p>
            </div>

            <div class="service-card">
                <svg class="service-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="4" width="18" height="12" rx="1"/>
                    <path d="M8 20h8M12 16v4"/>
                </svg>
                <h3>À distance</h3>
                <p>Un programme structuré à réaliser en autonomie, avec un suivi régulier pour ajuster la progression.</p>
            </div>
        </div>

        <a href="#contact" class="btn">Contactez-moi</a>
    </section>

    <section id="contact">
        <h2>Un bilan gratuit, sans engagement</h2>
        <p>30 minutes pour faire le point sur vos objectifs et voir si JF Coaching est le bon accompagnement pour vous.</p>

        <div class="contact-actions">
            <a href="mailto:contact@elan-coaching.fr" class="btn">contact@elan-coaching.fr</a>
            <a href="tel:+32476000000" class="btn btn-secondary">+32 476 00 00 00</a>
        </div>

        <p class="contact-note">Prise de rendez-vous en ligne : bientôt disponible</p>
    </section>

@endsection