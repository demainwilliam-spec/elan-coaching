@extends('layouts.app')

@section('title', 'Mentions légales')
@section('description', 'Mentions légales du site JF Coaching : éditeur, hébergeur et conditions de réservation des cours collectifs.')

@section('content')

    {{-- <mark class="todo"> : information à compléter avant la mise en ligne --}}
    <article class="legal-page">
        <h1>Mentions légales</h1>

        <h2>Éditeur du site</h2>
        <p>
            {{ config('coach.business_name') }}<br>
            {{ config('coach.owner') }}<br>
            {{ config('coach.address.street') }}, {{ config('coach.address.postal_code') }} {{ config('coach.address.locality') }}, Belgique
        </p>
        <p>
            Numéro d'entreprise (BCE) : {{ config('coach.company_number') }}<br>
            TVA : {{ config('coach.vat') }}<br>
            Email : <a href="mailto:{{ config('coach.email') }}">{{ config('coach.email') }}</a><br>
            Téléphone : <a href="tel:{{ config('coach.phone.link') }}">{{ config('coach.phone.display') }}</a>
        </p>

        <h2>Lieux des séances</h2>
        <ul>
            <li>Coachings individuels : {{ config('coach.places.individual') }}</li>
            <li>Cours collectifs : {{ config('coach.places.group') }}, sauf autre lieu indiqué dans l'agenda</li>
        </ul>

        <h2>Hébergement</h2>
        <p>
            Le site est hébergé par <mark class="todo">[À COMPLÉTER au déploiement : nom, adresse et site web de l'hébergeur]</mark>.
        </p>

        <h2>Conditions de réservation des cours collectifs</h2>
        <ul>
            <li>La réservation est gratuite et possible en ligne jusqu'au début du cours, avec ou sans compte client. Le compte est facultatif : il évite de ressaisir ses coordonnées et permet de retrouver ses réservations.</li>
            <li>S'il reste de la place, votre réservation est confirmée immédiatement. Sinon, vous êtes inscrit sur la liste d'attente.</li>
            <li>Vous pouvez annuler jusqu'à 24 heures avant le début du cours, grâce au lien reçu par email ou depuis « Mes réservations » si vous avez un compte. Passé ce délai, contactez directement le coach.</li>
            <li>Quand une place se libère, la première personne de la liste d'attente obtient la place et en est prévenue par email.</li>
            <li>Le coach peut modifier ou annuler un cours. Toutes les personnes inscrites sont alors prévenues par email.</li>
        </ul>

        <h2>Propriété intellectuelle</h2>
        <p>
            Le contenu du site (textes, logo, éléments graphiques) est protégé. Toute reproduction sans autorisation préalable est interdite.
        </p>

        <h2>Données personnelles</h2>
        <p>
            Pour savoir quelles données sont collectées et comment elles sont utilisées, consultez la
            <a href="{{ route('legal.privacy') }}">politique de confidentialité</a>.
        </p>
    </article>

@endsection
