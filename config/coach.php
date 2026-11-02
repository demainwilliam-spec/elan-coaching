<?php

// Coordonnées publiques du coach, lues avec config('coach.xxx') : un seul endroit à modifier
// (mentions légales, confidentialité, contact, cartes de services, données structurées, formulaire de cours).
// Pas de secret ici : ces infos sont affichées sur le site, elles n'ont rien à faire dans .env.

return [

    'business_name' => 'JF Coaching',
    'owner' => 'Jean-François Theys',

    // Numéro BCE = numéro de TVA sans « BE »
    'company_number' => '0756.603.958',
    'vat' => 'BE0756.603.958',

    'email' => 'jftheys@hotmail.com',

    // display : tel qu'affiché ; link : format international pour les liens tel: (sans le 0, avec +32)
    'phone' => [
        'display' => '0479 38 51 06',
        'link' => '+32479385106',
    ],

    // Adresse de l'entreprise (mentions légales, données structurées)
    'address' => [
        'street' => 'Rue Es Bois 74',
        'postal_code' => '5310',
        'locality' => 'Éghezée',
        'region' => 'Province de Namur',
        'country' => 'BE',
    ],

    // Lieux des séances (cartes de services ; « group » préremplit le lieu d'un nouveau cours)
    'places' => [
        'individual' => 'Rue Es Bois 74, 5310 Bolinne',
        'group' => 'Chau. de Louvain 92, 5310 Éghezée',
    ],

];
