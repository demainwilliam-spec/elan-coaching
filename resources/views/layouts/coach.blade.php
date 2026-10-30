<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        {{-- noindex : demande aux moteurs de recherche de ne pas afficher ces pages dans leurs résultats --}}
        <meta name="robots" content="noindex, nofollow">
        <title>Espace coach — @yield('title')</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>

        <header>
            <a href="{{ route('coach.dashboard') }}" class="logo"><img src="{{ Vite::asset('resources/images/logo.webp') }}" alt="JF Coaching — tableau de bord" width="208" height="240"></a>
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
