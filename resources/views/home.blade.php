<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Élan — Coaching sportif</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    
    <body>

        @include('partials.header')

        <section class="hero">
            <h1>Reprenez votre élan.</h1>
            <p>
                Un accompagnement sportif construit autour de vous : bilan honnête,
                programme réaliste, suivi qui tient dans la durée.
            </p>
            <a href="{{ url('/contact') }}" class="btn">Réserver un bilan gratuit</a>
        </section>

        @include('partials.footer')
    </body>
</html>