@extends('layouts.app')

@section('title', 'Mon compte')
@section('noindex', true)

@section('content')

    {{-- account-page : plus large que les autres pages simples, pour le tableau des réservations --}}
    <section class="message-page account-page">
        @include('account.partials.alerts')

        <h2>Bonjour {{ $client->name }}</h2>
        <p class="message-text">Connecté avec l'adresse {{ $client->email }}.</p>

        {{-- Tant que l'adresse n'est pas confirmée : rappel + bouton pour renvoyer le mail --}}
        @unless ($client->hasVerifiedEmail())
            <div class="account-notice">
                <p>
                    <strong>Confirmez votre adresse email</strong> en cliquant sur le lien que nous vous avons envoyé :
                    vous pourrez ensuite réserver vos cours avec votre compte.
                    Sans confirmation, le compte est supprimé après {{ \App\Models\Client::UNVERIFIED_RETENTION_DAYS }} jours.
                </p>
                <form method="POST" action="{{ route('account.verification.send') }}">
                    @csrf
                    <button type="submit" class="btn btn-small btn-secondary">Renvoyer le mail</button>
                </form>
            </div>
        @endunless

        {{-- id="reservations" : cible du lien « Inscrit ✓ » de l'agenda (/compte#reservations) --}}
        <h3 id="reservations" class="account-heading">Mes réservations</h3>

        @if ($bookings->isEmpty())
            <p class="message-text">Aucune réservation à venir.</p>
        @else
            {{-- Tableau de données : une ligne par réservation. Sur téléphone, chaque ligne devient un bloc
                 empilé et data-label affiche le nom de la colonne devant chaque cellule (voir .data-table) --}}
            <table class="data-table">
                <thead>
                    <tr>
                        {{-- scope="col" : en-tête de la colonne, annoncé par les lecteurs d'écran avec chaque cellule --}}
                        <th scope="col">Date</th>
                        <th scope="col">Cours</th>
                        <th scope="col">Lieu</th>
                        <th scope="col">Statut</th>
                        {{-- Colonne sans titre visible, mais nommée pour les lecteurs d'écran --}}
                        <th scope="col"><span class="visually-hidden">Action</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $booking)
                        @php($course = $booking->course)
                        <tr>
                            <td data-label="Date">
                                {{-- <time> : date lisible pour l'humain, date exacte pour les machines --}}
                                <time datetime="{{ $course->starts_at->toIso8601String() }}">
                                    {{ ucfirst($course->starts_at->translatedFormat('D j M')) }} · {{ $course->starts_at->format('H\hi') }}
                                </time>
                            </td>
                            <td data-label="Cours">
                                {{ $course->title }}
                                <span class="data-table-muted">{{ $course->duration_minutes }} min</span>
                            </td>
                            <td data-label="Lieu">{{ $course->location }}</td>
                            <td data-label="Statut">
                                @if ($booking->status === \App\Enums\BookingStatus::Confirmed)
                                    Confirmée
                                @else
                                    Liste d'attente (n°{{ $booking->waitingPosition() }})
                                @endif
                            </td>
                            <td class="data-table-action">
                                {{-- Même règle que la page d'annulation : en ligne jusqu'à 24 h avant le cours --}}
                                @if ($course->cancellationIsOpen())
                                    {{-- La page d'annulation existante (lien des mails) : confirmation, 24 h, promotion... --}}
                                    <a href="{{ route('bookings.cancel', $booking->cancel_token) }}" class="btn btn-small btn-secondary">Annuler</a>
                                @else
                                    <span class="data-table-muted">Moins de 24 h : contactez le coach</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="button-row">
            <a href="/#cours" class="btn">Voir les cours</a>
            {{-- Déconnexion en POST : un simple lien (GET) ne doit rien modifier --}}
            <form method="POST" action="{{ route('account.logout') }}">
                @csrf
                <button type="submit" class="btn btn-secondary">Se déconnecter</button>
            </form>
        </div>
    </section>

@endsection
