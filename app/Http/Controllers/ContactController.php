<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;

class ContactController extends Controller
{
    // $request contient tout ce que le navigateur a envoyé (les champs du formulaire, etc.).
    // Laravel le fournit automatiquement : il suffit de le demander en paramètre.
    // Avec ContactRequest, Laravel valide AVANT d'entrer ici : si on arrive dans send(), les données sont valides.
    public function send(ContactRequest $request)
    {
        // Pour l'instant on ne fait rien des données : le mail viendra ici (étape 4).

        // On redirige vers l'accueil, directement sur la section contact.
        // with() stocke le message en session pour la requête suivante uniquement (message « flash »).
        return redirect('/#contact')->with('success', 'Merci, votre message a bien été reçu.');
    }
}
