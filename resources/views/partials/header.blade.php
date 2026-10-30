<header>
    {{-- Le logo est aussi le lien vers l'accueil : son alt sert de texte au lien --}}
    <a href="/" class="logo"><img src="{{ Vite::asset('resources/images/logo.webp') }}" alt="JF Coaching — accueil" width="139" height="160"></a>
    {{-- Menu et réseaux regroupés à droite (le header place ses enfants aux deux extrémités) --}}
    <div class="header-end">
        <nav>
            <ul>
                <li><a href="/#services">Services</a></li>
                <li><a href="/#cours">Cours</a></li>
                <li><a href="/#contact">Contact</a></li>
            </ul>
        </nav>
        @include('partials.social-links')
    </div>
</header>