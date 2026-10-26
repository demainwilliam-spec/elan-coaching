@extends('layouts.app')

@section('title', 'Accueil')

@section('content')

    <section class="hero">
        <h1>Reprenez votre élan.</h1>
        <p>
            Un accompagnement sportif construit autour de vous : bilan honnête,
            programme réaliste, suivi qui tient dans la durée.
        </p>
        <a href="#contact" class="btn">Réserver un bilan gratuit</a>
    </section>

    <section id="services">
        <h2>Mes services</h2>

        <div class="services-grid">
            <div class="service-card">
                <svg class="service-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"/>
                </svg>
                <h3>Coaching individuel</h3>
                <p>Séances en tête-à-tête, programme entièrement personnalisé, ajusté à chaque rendez-vous.</p>
            </div>

            <div class="service-card">
                <svg class="service-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="9" cy="7" r="3"/>
                    <circle cx="17" cy="9" r="2.5"/>
                    <path d="M2 20c0-3.5 3-6.5 7-6.5s7 3 7 6.5"/>
                    <path d="M17 13.5c2.5 0.3 4.5 2.5 4.5 6.5"/>
                </svg>
                <h3>Petit groupe</h3>
                <p>De 2 à 4 personnes, même exigence qu'en individuel, tarif dégressif selon le nombre de participants.</p>
            </div>

            <div class="service-card">
                <svg class="service-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="4" width="18" height="12" rx="1"/>
                    <path d="M8 20h8M12 16v4"/>
                </svg>
                <h3>À distance</h3>
                <p>Un programme structuré à réaliser en autonomie, avec un suivi régulier pour ajuster la progression.</p>
            </div>
        </div>

        <a href="#contact" class="btn">Contactez-moi</a>
    </section>

    <section id="cours">
        <h2>Cours collectifs</h2>
        <p>
            Réservez votre place en quelques secondes. Cours complet ? Inscrivez-vous sur la liste d'attente :
            vous recevrez un mail si une place se libère.
        </p>

        {{-- Carrousel : les pages de 4 cours sont posées côte à côte dans le rail (track), qui défile horizontalement --}}
        <div class="course-carousel">
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
    </section>

    <section id="contact">
        <h2>Un bilan gratuit, sans engagement</h2>
        <p>30 minutes pour faire le point sur vos objectifs et voir si JF Coaching est le bon accompagnement pour vous.</p>

        {{-- session('success') n'existe que juste après la redirection du contrôleur --}}
        @if (session('success'))
            <div class="form-alert">{{ session('success') }}</div>
        @endif

        {{-- session('error') : défini par le contrôleur quand l'envoi du mail a échoué --}}
        @if (session('error'))
            <div class="form-alert form-alert-error">{{ session('error') }}</div>
        @endif

        {{-- route('contact.send') génère l'URL de la route nommée (ici /contact) --}}
        <form class="contact-form" method="POST" action="{{ route('contact.send') }}">
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

            <button type="submit" class="btn">Envoyer</button>
        </form>

        <div class="contact-actions">
            <p>Vous préférez appeler ?</p>
            <a href="tel:+32476000000" class="btn btn-secondary">+32 476 00 00 00</a>
        </div>

        <p class="contact-note">Prise de rendez-vous en ligne : bientôt disponible</p>
    </section>

@endsection