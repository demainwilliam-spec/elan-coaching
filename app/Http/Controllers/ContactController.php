<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMessage;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    // $request contient tout ce que le navigateur a envoyé (les champs du formulaire, etc.).
    // Laravel le fournit automatiquement : il suffit de le demander en paramètre.
    // Avec ContactRequest, Laravel valide AVANT d'entrer ici : si on arrive dans send(), les données sont valides.
    public function send(ContactRequest $request)
    {
        // validated() ne renvoie que les champs validés par ContactRequest (les champs inventés sont écartés)
        $data = $request->validated();

        // config() lit config/mail.php, qui lit lui-même MAIL_CONTACT_ADDRESS dans .env
        Mail::to(config('mail.contact_address'))->send(
            new ContactMessage($data['name'], $data['email'], $data['message'])
        );

        // On redirige vers l'accueil, directement sur la section contact.
        // with() stocke le message en session pour la requête suivante uniquement (message « flash »).
        return redirect('/#contact')->with('success', 'Merci, votre message a bien été envoyé. Je vous réponds au plus vite.');
    }
}
