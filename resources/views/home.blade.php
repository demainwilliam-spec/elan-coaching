@extends('layouts.app')

@section('title', 'Coach sportif, coaching personnalisé et cours collectifs')

@section('content')

    <section class="hero">
        <h1>Reprenez votre élan.</h1>
        <p>
            Un accompagnement sportif construit autour de vous : bilan honnête,
            programme réaliste, suivi qui tient dans la durée.
        </p>
        <a href="#contact" class="btn">Réserver un bilan gratuit</a>
    </section>

    @php
        // Photos du carrousel : pour en ajouter une, déposer le .webp dans resources/images et ajouter une ligne.
        // width / height : dimensions réelles du fichier (le navigateur réserve la place avant le chargement).
        // landscape : photo en largeur, affichée entière (avec des bandes) plutôt que recadrée et agrandie
        $photos = [
            ['file' => 'corde.webp', 'width' => 444, 'height' => 599, 'alt' => 'Le coach grimpe à la corde dans une salle de cross-training'],
            ['file' => 'poids.webp', 'width' => 689, 'height' => 875, 'alt' => 'Le coach soulève un haltère à bout de bras pendant une compétition'],
            ['file' => 'stretch.webp', 'width' => 597, 'height' => 313, 'alt' => 'Le coach guide un client dans un étirement sur ballon', 'landscape' => true],
        ];
    @endphp

    {{-- aria-roledescription / aria-label : annoncent aux lecteurs d'écran un « carrousel » de photos --}}
    <section class="photo-carousel" aria-roledescription="carrousel" aria-label="Photos du coach" data-photo-carousel>
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
    </section>

    {{-- data-reveal : élément qui apparaît en glissant quand on arrive dessus (resources/js/reveal.js) --}}
    <section id="services" class="theme-light">
        <h2 data-reveal>Mes services</h2>

        {{-- Cartes = liens vers le formulaire (marche sans JS) ; data-service : le JS préremplit le message (service-links.js) --}}
        <div class="services-grid">
            <a href="#contact" class="service-card" data-reveal data-service="le coaching individuel">
                <svg class="service-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"/>
                </svg>
                <h3>Coaching individuel</h3>
                <p>Séances en tête-à-tête, programme entièrement personnalisé, ajusté à chaque rendez-vous.</p>
            </a>

            <a href="#contact" class="service-card" data-reveal data-service="le coaching en petit groupe">
                <svg class="service-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="9" cy="7" r="3"/>
                    <circle cx="17" cy="9" r="2.5"/>
                    <path d="M2 20c0-3.5 3-6.5 7-6.5s7 3 7 6.5"/>
                    <path d="M17 13.5c2.5 0.3 4.5 2.5 4.5 6.5"/>
                </svg>
                <h3>Petit groupe</h3>
                <p>De 2 à 4 personnes, même exigence qu'en individuel, tarif dégressif selon le nombre de participants.</p>
            </a>

            <a href="#contact" class="service-card" data-reveal data-service="le coaching à distance">
                <svg class="service-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="4" width="18" height="12" rx="1"/>
                    <path d="M8 20h8M12 16v4"/>
                </svg>
                <h3>À distance</h3>
                <p>Un programme structuré à réaliser en autonomie, avec un suivi régulier pour ajuster la progression.</p>
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

        {{-- Modale de réservation : une seule pour tous les cours, remplie par booking-dialog.js --}}
        {{-- data-reopen-course : présent seulement après une erreur → le JS rouvre la modale sur ce cours --}}
        <dialog class="booking-dialog" aria-labelledby="booking-dialog-title" data-booking-dialog
            @if ($errors->hasAny(['booking.name', 'booking.email']) || session('booking_error'))
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

                    <div class="form-field @error('booking.name') has-error @enderror">
                        <label for="booking-name">Nom</label>
                        {{-- name="booking[name]" : arrive côté serveur dans le tableau "booking" (booking.name) --}}
                        <input type="text" id="booking-name" name="booking[name]" autocomplete="name"
                            value="{{ old('booking.name') }}" required maxlength="100">
                        @error('booking.name')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field @error('booking.email') has-error @enderror">
                        <label for="booking-email">Email</label>
                        <input type="email" id="booking-email" name="booking[email]" autocomplete="email"
                            value="{{ old('booking.email') }}" required maxlength="254">
                        @error('booking.email')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <p class="booking-notice">
                        Annulation possible jusqu'à 24 h avant le début du cours.
                        Vos données servent uniquement à gérer votre réservation
                        (<a href="{{ route('legal.privacy') }}">en savoir plus</a>).
                    </p>

                    <button type="submit" class="btn" data-booking-submit>Confirmer la réservation</button>
                </form>
            </div>
        </dialog>
    </section>

    <section id="contact" class="theme-light">
        <h2 data-reveal>Un bilan gratuit, sans engagement</h2>
        <p data-reveal>30 minutes pour faire le point sur vos objectifs et voir si JF Coaching est le bon accompagnement pour vous.</p>

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
            <a href="tel:+32476000000" class="btn btn-secondary">+32 476 00 00 00</a>
        </div>

        <p class="contact-note">Prise de rendez-vous en ligne : bientôt disponible</p>
    </section>

@endsection