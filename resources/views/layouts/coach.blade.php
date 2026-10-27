<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        {{-- noindex : demande aux moteurs de recherche de ne pas afficher ces pages dans leurs résultats --}}
        <meta name="robots" content="noindex, nofollow">
        <title>Espace coach — @yield('title')</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600&family=Pinyon+Script&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>

        <header>
            <div class="logo"><a href="{{ route('coach.dashboard') }}">JF Coaching</a></div>
            {{-- @auth : affiché seulement si quelqu'un est connecté (pas sur la page de connexion) --}}
            @auth
                <nav>
                    <ul>
                        <li><a href="{{ route('coach.dashboard') }}">Cours</a></li>
                        <li><a href="/" target="_blank">Voir le site</a></li>
                        <li>
                            {{-- Déconnexion en POST : un simple lien (GET) ne doit rien modifier --}}
                            <form method="POST" action="{{ route('coach.logout') }}">
                                @csrf
                                <button type="submit" class="nav-button">Déconnexion</button>
                            </form>
                        </li>
                    </ul>
                </nav>
            @endauth
        </header>

        <main class="coach-page">
            {{-- status : message de confirmation après une action du coach ("Cours créé", ...) --}}
            @if (session('status'))
                <div class="form-alert">{{ session('status') }}</div>
            @endif

            @yield('content')
        </main>

    </body>
</html>
