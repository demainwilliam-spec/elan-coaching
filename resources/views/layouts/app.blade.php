<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        {{-- @hasSection : vrai si la page a défini @section('noindex') (pages du compte client) --}}
        @hasSection('noindex')
            <meta name="robots" content="noindex, nofollow">
        @endif
        <title>JF Coaching — @yield('title')</title>

        {{-- Icônes dans public/ (adresse fixe, sans Vite) : onglet du navigateur, écran d'accueil du téléphone --}}
        <link rel="icon" href="/favicon.ico" sizes="16x16 32x32 48x48">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @php
            // yieldContent : comme @yield, mais renvoie le texte dans une variable au lieu de l'afficher.
            // 2e argument : texte par défaut si la page ne définit pas sa propre @section('description')
            $description = \Illuminate\Support\Facades\View::yieldContent('description', 'Coach sportif à Éghezée (province de Namur) : coaching postural, coaching individuel et cours collectifs à réserver en ligne.');
        @endphp

        {{-- Description affichée par Google sous le titre (~155 caractères max) --}}
        <meta name="description" content="{{ $description }}">
        {{-- L'adresse « officielle » de la page, sans les paramètres (?utm=...) : évite les doublons chez Google --}}
        <link rel="canonical" href="{{ url()->current() }}">

        {{-- Open Graph : la carte d'aperçu quand le lien est partagé (WhatsApp, Facebook, LinkedIn...) --}}
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="JF Coaching">
        <meta property="og:locale" content="fr_BE">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="JF Coaching — @yield('title')">
        <meta property="og:description" content="{{ $description }}">

        {{-- @stack : emplacement où une page peut ajouter des balises avec @push('head') (ex. données structurées de l'accueil) --}}
        @stack('head')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>

        @include('partials.header')

        {{-- <main> : repère du contenu principal, utilisé par les lecteurs d'écran pour y sauter directement --}}
        <main>
            @yield('content')
        </main>

        @include('partials.footer')

    </body>
</html>