@extends('layouts.app')

@section('title', 'Coach sportif à Éghezée, coaching personnalisé et cours collectifs')

{{-- Données structurées (JSON-LD, vocabulaire schema.org) : fiche d'identité lisible par Google (nom, adresse,
     téléphone, zone), invisible pour le visiteur ; aide à apparaître dans les résultats locaux et sur Maps.
     Vérifiable avec https://search.google.com/test/rich-results une fois en ligne --}}
@push('head')
    @php
        $business = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => config('coach.business_name'),
            'description' => 'Coaching postural, coaching individuel et cours collectifs à Éghezée.',
            'url' => url('/'),
            'logo' => Vite::asset('resources/images/logo.webp'),
            'telephone' => config('coach.phone.link'),
            'email' => config('coach.email'),
            'vatID' => config('coach.vat'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => config('coach.address.street'),
                'postalCode' => config('coach.address.postal_code'),
                'addressLocality' => config('coach.address.locality'),
                'addressRegion' => config('coach.address.region'),
                'addressCountry' => config('coach.address.country'),
            ],
            'areaServed' => 'Éghezée',
        ];
    @endphp
    {{-- JSON_HEX_TAG : écrit < et > en < / >, un texte ne peut donc jamais fermer la balise <script> --}}
    <script type="application/ld+json">{!! json_encode($business, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')

    <section class="hero">
        <h1>Coach sportif à Éghezée</h1>
        <p>
            Ici commence votre chemin vers un corps plus fort et un esprit plus souple.
            La véritable Éducation Physique.
        </p>
        <a href="#contact" class="btn">Prendre contact</a>
    </section>

    @php
        // Photos du carrousel : pour en ajouter une, déposer le .webp dans resources/images et ajouter une ligne.
        // width / height : dimensions réelles du fichier (le navigateur réserve la place avant le chargement).
        // landscape : photo en largeur, affichée entière (avec des bandes) plutôt que recadrée et agrandie
        $photos = [
            ['file' => 'posture.webp', 'width' => 1200, 'height' => 800, 'alt' => 'Le coach corrige la position de la jambe d\'un client allongé sur un tapis', 'landscape' => true],
            ['file' => 'etirements.webp', 'width' => 1080, 'height' => 1101, 'alt' => 'Le coach accompagne deux personnes pendant un exercice d\'étirement avec des briques'],
            ['file' => 'groupe.webp', 'width' => 1200, 'height' => 900, 'alt' => 'Photo de groupe joyeuse après un cours collectif', 'landscape' => true],
            ['file' => 'poids.webp', 'width' => 689, 'height' => 875, 'alt' => 'Le coach soulève un haltère à bout de bras pendant une compétition'],
            ['file' => 'stretch.webp', 'width' => 597, 'height' => 313, 'alt' => 'Le coach guide un client dans un étirement sur ballon', 'landscape' => true],
        ];
    @endphp

    {{-- Présentation : texte à gauche, carrousel à droite (empilés sur téléphone, texte d'abord) --}}
    <section class="about">
        <div class="about-text" data-reveal>
            <h2>Votre coach</h2>
            <p>
                Diplômé en Éducation Physique et curieux du sport et du mouvement depuis mes 10 ans,
                c’est suite à une douleur au dos que je me suis mis en quête de comprendre le corps
                pour comprendre d’où venaient ces douleurs. C’est ainsi que mon chemin en coaching postural a commencé.
            </p>
            <p>
                J’aide aujourd’hui, à Éghezée et dans la région, les personnes désireuses d’améliorer leur santé physique.
                Je vous guide via des coachings individuels et des cours collectifs à prendre soin
                de votre plus bel outil : votre corps.
            </p>
            <p>
                Chaque adaptation mise en place sert à vous aider à progresser et avancer à votre rythme
                afin de comprendre comment votre corps fonctionne et ainsi devenir autonome dans votre développement ;
                c’est ce que je nomme la véritable Éducation Physique.
            </p>
        </div>

        {{-- role="region" + aria-label : une zone nommée « Photos du coach » ; aria-roledescription la fait annoncer comme un « carrousel » --}}
        <div class="photo-carousel" role="region" aria-roledescription="carrousel" aria-label="Photos du coach" data-photo-carousel>
            <div class="photo-track" data-photo-track>
                @foreach ($photos as $photo)
                    <figure class="photo-slide {{ ($photo['landscape'] ?? false) ? 'is-landscape' : '' }}">
                        {{-- Vite::asset : adresse du fichier via Vite (avec empreinte dans le nom en production) --}}
                        {{-- lazy : les photos cachées ne sont téléchargées qu'à l'approche ; la 1re, visible tout de suite, non --}}
                        <img src="{{ Vite::asset('resources/images/'.$photo['file']) }}"
                            alt="{{ $photo['alt'] }}" width="{{ $photo['width'] }}" height="{{ $photo['height'] }}"
                            loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                            {{-- fetchpriority : la 1re photo est le plus gros élément visible au chargement, on la télécharge en priorité --}}
                            @if ($loop->first) fetchpriority="high" @endif>
                    </figure>
                @endforeach
            </div>

            <div class="photo-controls">
                @foreach ($photos as $photo)
                    <button type="button" class="photo-dot" aria-label="Photo {{ $loop->iteration }}" data-photo-dot></button>
                @endforeach
            </div>
        </div>
    </section>

    {{-- data-reveal : élément qui apparaît en glissant quand on arrive dessus (resources/js/reveal.js) --}}
    <section id="services" class="theme-light">
        <h2 data-reveal>Mes services</h2>

        {{-- Cartes = liens (marchent sans JS) ; data-service : le JS préremplit le message du formulaire (service-links.js) --}}
        <div class="services-grid">
            <a href="#contact" class="service-card" data-reveal data-service="le coaching individuel">
                <svg class="service-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"/>
                </svg>
                <h3>Coaching individuel</h3>
                <p>Séances en tête-à-tête, programme entièrement personnalisé, ajusté à chaque rendez-vous.</p>
                <p class="service-place">{{ config('coach.places.individual') }}</p>
            </a>

            {{-- Pas de data-service : cette carte mène à l'agenda, où l'on réserve directement --}}
            <a href="#cours" class="service-card" data-reveal>
                <svg class="service-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="9" cy="7" r="3"/>
                    <circle cx="17" cy="9" r="2.5"/>
                    <path d="M2 20c0-3.5 3-6.5 7-6.5s7 3 7 6.5"/>
                    <path d="M17 13.5c2.5 0.3 4.5 2.5 4.5 6.5"/>
                </svg>
                <h3>Cours collectifs</h3>
                <p>Des séances en groupe encadrées par le coach : consultez l'agenda et réservez votre place en ligne.</p>
                <p class="service-place">{{ config('coach.places.group') }}</p>
            </a>
        </div>

        <a href="#contact" class="btn">Contactez-moi</a>
    </section>

    <section id="cours">
        <h2 data-reveal>Cours collectifs</h2>
        <p data-reveal>
            Réservez votre place en quelques secondes. Cours complet ? Inscrivez-vous sur la liste d'attente :
            vous recevrez un mail si une place se libère.
        </p>

        {{-- booking_success : défini par BookingController après une réservation réussie --}}
        @if (session('booking_success'))
            <div class="form-alert">{{ session('booking_success') }}</div>
        @endif

        {{-- Carrousel : les pages de 4 cours sont posées côte à côte dans le rail (track), qui défile horizontalement --}}
        <div class="course-carousel" data-reveal>
            {{-- data-* : repères pour le JS (carousel.js), indépendants des classes CSS --}}
            <div class="course-track" data-carousel-track>
                {{-- chunk(4) : découpe la collection en groupes de 4 → une <ul> par page --}}
                {{-- @forelse : comme @foreach, avec une branche @empty si $courses est vide --}}
                @forelse ($courses->chunk(4) as $page)
                    <ul class="course-list">
                        @foreach ($page as $course)
                            {{-- @include : insère le partial en lui passant la variable $course --}}
                            @include('partials.course-row', ['course' => $course])
                        @endforeach
                    </ul>
                @empty
                    <ul class="course-list">
                        <li class="course-empty">Aucun cours programmé pour le moment. Revenez bientôt !</li>
                    </ul>
                @endforelse
            </div>

            {{-- Navigation seulement s'il y a plus d'une page --}}
            @if ($courses->count() > 4)
                <nav class="course-pagination" aria-label="Pages des cours">
                    {{-- disabled : on est sur la première page au chargement ; le JS met ensuite l'état à jour --}}
                    <button type="button" class="pagination-link" data-carousel-prev disabled>‹ Précédents</button>
                    {{-- aria-live : un lecteur d'écran annonce le texte quand il change --}}
                    <span class="pagination-status" data-carousel-status aria-live="polite">
                        Page 1 / {{ $courses->chunk(4)->count() }}
                    </span>
                    <button type="button" class="pagination-link" data-carousel-next>Suivants ›</button>
                </nav>
            @endif
        </div>

        {{-- Invitation à créer un compte (le compte sert surtout à réserver : on le propose ici) --}}
        <p class="account-switch">
            {{-- @auth('client') : le client est connecté, auth('client')->user() existe forcément dans ce bloc --}}
            @auth('client')
                Connecté en tant que {{ auth('client')->user()->name }} · <a href="{{ route('account.show') }}">Mon compte</a>
            @else
                Réservez plus vite avec un compte :
                <a href="{{ route('account.register') }}">Créer un compte</a> ·
                <a href="{{ route('account.login', ['retour' => 'cours']) }}">Se connecter</a>
            @endauth
        </p>

        {{-- Modale de réservation : une seule pour tous les cours, remplie par booking-dialog.js --}}
        {{-- data-reopen-course : présent seulement après une erreur → le JS rouvre la modale sur ce cours --}}
        <dialog class="booking-dialog" aria-labelledby="booking-dialog-title" data-booking-dialog
            @if ($errors->hasAny(['booking.first_name', 'booking.last_name', 'booking.email']) || session('booking_error'))
                data-reopen-course="{{ old('booking.course_id') }}"
            @endif
        >
            <div class="booking-dialog-inner">
                {{-- method="dialog" : soumettre ce formulaire ferme la modale, sans JS --}}
                <form method="dialog" class="booking-dialog-close">
                    <button type="submit" aria-label="Fermer">×</button>
                </form>

                <h3 id="booking-dialog-title" data-booking-heading>Réserver</h3>

                <div class="booking-summary">
                    <p class="booking-summary-title" data-booking-title></p>
                    <p class="course-meta" data-booking-when></p>
                    <p class="course-meta" data-booking-location></p>
                    <p class="course-description" data-booking-description></p>
                </div>

                {{-- hidden : affiché seulement si le cours est complet (le JS retire l'attribut) --}}
                <p class="booking-notice" data-booking-full-notice hidden>
                    Ce cours est complet : vous serez inscrit(e) sur la liste d'attente
                    et prévenu(e) par mail si une place se libère.
                </p>

                {{-- booking_error : refus métier (doublon, cours commencé), renvoyé par BookingController --}}
                @if (session('booking_error'))
                    <div class="form-alert form-alert-error">{{ session('booking_error') }}</div>
                @endif

                {{-- action : remplacée par le JS avec l'URL du cours choisi (/cours/{id}/reservations) --}}
                <form class="booking-form" method="POST" action="" data-booking-form>
                    @csrf
                    {{-- Champ caché : l'id du cours revient dans old() après une erreur, pour rouvrir la bonne modale --}}
                    <input type="hidden" name="booking[course_id]" value="{{ old('booking.course_id') }}" data-booking-course-id>

                    {{-- @php(...) : variable locale à la vue ; null pour un visiteur --}}
                    @php($client = auth('client')->user())

                    @if ($client?->hasVerifiedEmail())
                        {{-- Client confirmé : aucun champ, la réservation est faite au nom du compte --}}
                        <p class="booking-account">
                            Au nom de <strong>{{ $client->name }}</strong> ({{ $client->email }})
                        </p>
                    @else
                        @if ($client)
                            {{-- Client pas encore confirmé : formulaire prérempli, rattaché au compte à la confirmation --}}
                            <p class="booking-notice">
                                Confirmez votre adresse email (lien reçu par mail) pour réserver en un clic.
                            </p>
                        @endif

                        <div class="form-field @error('booking.first_name') has-error @enderror">
                            <label for="booking-first-name">Prénom</label>
                            {{-- name="booking[first_name]" : arrive côté serveur dans le tableau "booking" (booking.first_name) --}}
                            {{-- old(champ, défaut) : la saisie précédente après une erreur, sinon le prénom du compte --}}
                            <input type="text" id="booking-first-name" name="booking[first_name]" autocomplete="given-name"
                                value="{{ old('booking.first_name', $client?->first_name) }}" required maxlength="100">
                            @error('booking.first_name')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-field @error('booking.last_name') has-error @enderror">
                            <label for="booking-last-name">Nom</label>
                            <input type="text" id="booking-last-name" name="booking[last_name]" autocomplete="family-name"
                                value="{{ old('booking.last_name', $client?->last_name) }}" required maxlength="100">
                            @error('booking.last_name')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-field @error('booking.email') has-error @enderror">
                            <label for="booking-email">Email</label>
                            <input type="email" id="booking-email" name="booking[email]" autocomplete="email"
                                value="{{ old('booking.email', $client?->email) }}" required maxlength="254">
                            @error('booking.email')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <p class="booking-notice">
                        <strong>Par respect pour les participants en liste d'attente et pour la bonne organisation des cours, les séances réservées et non honorées seront automatiquement déduites. En vous inscrivant, vous marquez votre accord.</strong>
                    </p>

                    <p class="booking-notice">
                        Vos données servent uniquement à gérer votre réservation
                        (<a href="{{ route('legal.privacy') }}">en savoir plus</a>).
                    </p>

                    <button type="submit" class="btn" data-booking-submit>Confirmer la réservation</button>
                </form>

                @guest('client')
                    {{-- Visiteur : le compte évite de retaper nom et email à chaque réservation --}}
                    <p class="account-switch">
                        Vous avez un compte ? <a href="{{ route('account.login', ['retour' => 'cours']) }}">Se connecter</a> ·
                        <a href="{{ route('account.register') }}">Créer un compte</a>
                    </p>
                @endguest
            </div>
        </dialog>
    </section>

    <section id="contact" class="theme-light">
        <h2 data-reveal>Parlons de vos objectifs</h2>
        <p data-reveal>Une question, une envie de commencer ? Écrivez-moi pour voir ensemble quel accompagnement vous convient.</p>

        {{-- session('success') n'existe que juste après la redirection du contrôleur --}}
        @if (session('success'))
            <div class="form-alert">{{ session('success') }}</div>
        @endif

        {{-- session('error') : défini par le contrôleur quand l'envoi du mail a échoué --}}
        @if (session('error'))
            <div class="form-alert form-alert-error">{{ session('error') }}</div>
        @endif

        {{-- route('contact.send') génère l'URL de la route nommée (ici /contact) --}}
        <form class="contact-form" data-reveal method="POST" action="{{ route('contact.send') }}">
            {{-- @csrf ajoute un champ caché contenant le jeton anti-CSRF --}}
            @csrf

            {{-- @error('name') ... @enderror : affiché seulement si le champ "name" a une erreur --}}
            <div class="form-field @error('name') has-error @enderror">
                {{-- for="name" relie le label au champ qui a id="name" --}}
                <label for="name">Nom</label>
                {{-- name="name" : la clé sous laquelle la valeur arrive côté serveur --}}
                {{-- old('name') : la valeur saisie avant l'échec de la validation (vide sinon) --}}
                {{-- required / maxlength : contrôle côté navigateur, pour le confort ; la vraie protection reste ContactRequest --}}
                <input type="text" id="name" name="name" autocomplete="name" value="{{ old('name') }}" required maxlength="100">
                @error('name')
                    {{-- $message : le texte de l'erreur défini dans ContactRequest --}}
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field @error('email') has-error @enderror">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" autocomplete="email" value="{{ old('email') }}" required maxlength="255">
                @error('email')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field @error('message') has-error @enderror">
                <label for="message">Message</label>
                {{-- Un textarea n'a pas d'attribut value : son contenu se place entre les balises --}}
                <textarea id="message" name="message" rows="6" required minlength="10" maxlength="5000">{{ old('message') }}</textarea>
                @error('message')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <p class="form-privacy">
                Vos données servent uniquement à vous répondre
                (<a href="{{ route('legal.privacy') }}">en savoir plus</a>).
            </p>

            <button type="submit" class="btn">Envoyer</button>
        </form>

        <div class="contact-actions" data-reveal>
            <p>Vous préférez appeler ?</p>
            <a href="tel:{{ config('coach.phone.link') }}" class="btn btn-secondary">{{ config('coach.phone.display') }}</a>
        </div>
    </section>

@endsection