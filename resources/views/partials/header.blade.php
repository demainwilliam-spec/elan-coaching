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
        {{-- Compte client : une icône plutôt qu'un texte, pour tenir dans le header sur téléphone.
             @auth('client') : le lien change selon que le client est connecté ou non --}}
        @auth('client')
            <a href="{{ route('account.show') }}" class="account-link" aria-label="Mon compte">
        @else
            <a href="{{ route('account.login') }}" class="account-link" aria-label="Se connecter à mon compte">
        @endauth
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/>
                </svg>
            </a>
        @include('partials.social-links')
    </div>
</header>