<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>JF Coaching — @yield('title')</title>

        @php
            // yieldContent : comme @yield, mais renvoie le texte dans une variable au lieu de l'afficher.
            // 2e argument : texte par défaut si la page ne définit pas sa propre @section('description')
            $description = \Illuminate\Support\Facades\View::yieldContent('description', 'Coaching sportif individuel, en petit groupe ou à distance, et cours collectifs à réserver en ligne. Bilan gratuit pour reprendre votre élan.');
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

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600&family=Pinyon+Script&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>

        @include('partials.header')

        @yield('content')

        @include('partials.footer')

    </body>
</html>