@extends('layouts.app')

@section('title', 'Politique de confidentialité')
@section('description', 'Quelles données JF Coaching collecte, pourquoi, combien de temps elles sont conservées et comment exercer vos droits.')

@section('content')

    <article class="legal-page">
        <h1>Politique de confidentialité</h1>

        <p class="legal-updated">Dernière mise à jour : 5 novembre 2026</p>

        <p>
            JF Coaching collecte le minimum de données nécessaires pour vous répondre et gérer vos réservations.
            Elles ne sont jamais vendues ni utilisées à des fins publicitaires.
        </p>

        <h2>Responsable du traitement</h2>
        <p>
            {{ config('coach.business_name') }} ({{ config('coach.owner') }}),
            {{ config('coach.address.street') }}, {{ config('coach.address.postal_code') }} {{ config('coach.address.locality') }} :
            <a href="mailto:{{ config('coach.email') }}">{{ config('coach.email') }}</a>.
            Coordonnées complètes dans les <a href="{{ route('legal.notice') }}">mentions légales</a>.
        </p>

        <h2>Quelles données, pourquoi, combien de temps ?</h2>

        <h3>Formulaire de contact</h3>
        <ul>
            <li><strong>Données :</strong> nom, adresse email, message.</li>
            <li><strong>Pourquoi :</strong> vous répondre (base légale : les mesures préalables à un contrat que vous demandez).</li>
            <li><strong>Où :</strong> elles ne sont pas enregistrées sur le site, mais transmises par email au coach.</li>
            <li><strong>Durée :</strong> au maximum 1 an après notre dernier échange, sauf si vous devenez client.</li>
        </ul>

        <h3>Réservation d'un cours collectif</h3>
        <ul>
            <li><strong>Données :</strong> prénom, nom, adresse email, cours choisi, place confirmée ou position sur la liste d'attente.</li>
            <li><strong>Pourquoi :</strong> gérer votre réservation, vous prévenir si une place se libère ou si le cours change (base légale : l'exécution du service que vous demandez).</li>
            <li><strong>Durée :</strong> supprimées immédiatement si vous annulez, et automatiquement 6 mois après la date du cours.</li>
        </ul>

        <h3>Compte client (facultatif)</h3>
        <ul>
            <li><strong>Données :</strong> prénom, nom, adresse email, mot de passe, date de confirmation de l'adresse, la liste de vos réservations et le nombre de cours suivis.</li>
            <li><strong>Pourquoi :</strong> vous permettre de réserver sans ressaisir vos coordonnées et de suivre vos réservations (base légale : l'exécution du service que vous demandez en créant un compte).</li>
            <li><strong>Mot de passe :</strong> jamais enregistré tel quel, seulement sous une forme chiffrée irréversible : personne, pas même le coach, ne peut le lire.</li>
            <li><strong>Durée :</strong> tant que le compte existe. Vous pouvez le supprimer vous-même à tout moment depuis la page « Mon profil ». Un compte sans aucune activité (connexion ou réservation) pendant {{ \App\Models\Client::INACTIVE_RETENTION_MONTHS }} mois est supprimé automatiquement, après un email d'avertissement envoyé {{ \App\Models\Client::INACTIVE_WARNING_DAYS }} jours avant. Un compte dont l'adresse n'est jamais confirmée est supprimé automatiquement après {{ \App\Models\Client::UNVERIFIED_RETENTION_DAYS }} jours.</li>
            <li><strong>À la suppression du compte :</strong> vos réservations à venir sont maintenues (vous restez inscrit et pouvez les annuler par le lien reçu par email) ; elles sont ensuite supprimées comme toutes les réservations, 6 mois après la date du cours.</li>
        </ul>

        <h3>Données techniques</h3>
        <ul>
            <li><strong>Données :</strong> adresse IP et type de navigateur.</li>
            <li><strong>Pourquoi :</strong> sécuriser le site, par exemple limiter les envois répétés de formulaires (base légale : l'intérêt légitime à protéger le site).</li>
            <li><strong>Durée :</strong> quelques heures, le temps de votre visite.</li>
        </ul>

        <h2>Qui a accès à vos données ?</h2>
        <p>
            Uniquement le coach, et les prestataires techniques indispensables au fonctionnement du site :
        </p>
        <ul>
            <li>l'hébergeur du site : <mark class="todo">[À COMPLÉTER au déploiement]</mark> ;</li>
            <li>Google (service Gmail), pour l'envoi des emails. Google peut traiter certaines données en dehors de l'Union européenne, avec les garanties prévues par le RGPD.</li>
        </ul>

        <h2>Cookies</h2>
        <p>
            Le site n'utilise <strong>aucun cookie publicitaire ni de mesure d'audience</strong>.
            Il dépose uniquement des cookies strictement nécessaires à son fonctionnement, qui ne demandent donc pas votre accord :
        </p>
        <ul>
            <li><code>jf-coaching-session</code> : garde les informations de votre visite (par exemple un formulaire mal rempli) ; supprimé après 2 heures d'inactivité ;</li>
            <li><code>XSRF-TOKEN</code> : protège les formulaires contre les envois frauduleux ; même durée ;</li>
            <li><code>remember_client_…</code> : seulement si vous cochez « Rester connecté » en vous connectant à votre compte ; vous garde connecté sur cet appareil pendant 400 jours au maximum, ou jusqu'à ce que vous vous déconnectiez.</li>
        </ul>

        <h2>Vos droits</h2>
        <p>
            Vous pouvez à tout moment demander à consulter, corriger ou supprimer vos données, ou vous opposer à leur utilisation.
            Écrivez à <a href="mailto:{{ config('coach.email') }}">{{ config('coach.email') }}</a> : nous répondons dans un délai d'un mois maximum.
        </p>
        <p>
            Si vous avez un compte, vous pouvez aussi corriger votre nom et votre email, ou supprimer votre compte,
            vous-même depuis la page « Mon profil ».
        </p>
        <p>
            Si vous estimez que vos droits ne sont pas respectés, vous pouvez introduire une plainte auprès de
            l'Autorité de protection des données : rue de la Presse 35, 1000 Bruxelles —
            <a href="https://www.autoriteprotectiondonnees.be" target="_blank" rel="noopener">www.autoriteprotectiondonnees.be</a>.
        </p>
    </article>

@endsection
