<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContactController extends Controller
{
    // $request contient tout ce que le navigateur a envoyé (les champs du formulaire, etc.).
    // Laravel le fournit automatiquement : il suffit de le demander en paramètre.
    public function send(Request $request)
    {
        // Pour l'instant on ne fait rien des données : validation (étape 3) et mail (étape 4) viendront ici.

        // On redirige vers l'accueil, directement sur la section contact.
        // with() stocke le message en session pour la requête suivante uniquement (message « flash »).
        return redirect('/#contact')->with('success', 'Merci, votre message a bien été reçu.');
    }
}
