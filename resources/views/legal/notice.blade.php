@extends('layouts.app')

@section('title', 'Mentions légales')
@section('description', 'Mentions légales du site JF Coaching : éditeur, hébergeur et conditions de réservation des cours collectifs.')

@section('content')

    {{-- <mark class="todo"> : information à compléter avant la mise en ligne --}}
    <article class="legal-page">
        <h1>Mentions légales</h1>

        <h2>Éditeur du site</h2>
        <p>
            JF Coaching<br>
            <mark class="todo">[À COMPLÉTER : nom et prénom du coach, ou dénomination de la société]</mark><br>
            <mark class="todo">[À COMPLÉTER : statut — indépendant en personne physique, SRL…]</mark><br>
            <mark class="todo">[À COMPLÉTER : adresse (rue, numéro, code postal, commune)]</mark>, Belgique
        </p>
        <p>
            Numéro d'entreprise (BCE) : <mark class="todo">[À COMPLÉTER]</mark><br>
            TVA : <mark class="todo">[À COMPLÉTER : numéro de TVA, ou « non assujetti »]</mark><br>
            Email : <mark class="todo">[À COMPLÉTER]</mark><br>
            Téléphone : <mark class="todo">[À COMPLÉTER]</mark>
        </p>

        <h2>Hébergement</h2>
        <p>
            Le site est hébergé par <mark class="todo">[À COMPLÉTER au déploiement : nom, adresse et site web de l'hébergeur]</mark>.
        </p>

        <h2>Conditions de réservation des cours collectifs</h2>
        <ul>
            <li>La réservation est gratuite et possible en ligne jusqu'au début du cours.</li>
            <li>S'il reste de la place, votre réservation est confirmée immédiatement. Sinon, vous êtes inscrit sur la liste d'attente.</li>
            <li>Vous pouvez annuler jusqu'à 24 heures avant le début du cours, grâce au lien reçu par email. Passé ce délai, contactez directement le coach.</li>
            <li>Quand une place se libère, la première personne de la liste d'attente obtient la place et en est prévenue par email.</li>
            <li>Le coach peut modifier ou annuler un cours. Toutes les personnes inscrites sont alors prévenues par email.</li>
        </ul>

        <h2>Propriété intellectuelle</h2>
        <p>
            Les textes, le logo et la présentation de ce site appartiennent à JF Coaching. Toute reproduction sans autorisation est interdite.
        </p>

        <h2>Données personnelles</h2>
        <p>
            Pour savoir quelles données sont collectées et comment elles sont utilisées, consultez la
            <a href="{{ route('legal.privacy') }}">politique de confidentialité</a>.
        </p>
    </article>

@endsection
