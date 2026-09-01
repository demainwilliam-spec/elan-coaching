<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Élan — Méthode</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    
    <body>

        <header>
            <div class="logo">Élan</div>
            <nav>
                <ul>
                    <li><a href="{{ url('/') }}">Accueil</a></li>
                    <li><a href="{{ url('/methode') }}">Méthode</a></li>
                    <li><a href="{{ url('/services') }}">Services</a></li>
                    <li><a href="{{ url('/coach') }}">Le coach</a></li>
                    <li><a href="{{ url('/avis') }}">Avis</a></li>
                    <li><a href="{{ url('/contact') }}">Contact</a></li>
                </ul>
            </nav>
        </header>

        <h2>Page Méthode — à compléter</h2>

    </body>
</html>