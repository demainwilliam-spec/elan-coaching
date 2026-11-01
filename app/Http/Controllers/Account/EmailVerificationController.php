<?php

namespace App\Http\Controllers\Account;

use App\Actions\VerifyClientEmail;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    /**
     * Lien reçu par mail. Il fonctionne même sans être connecté (mail ouvert sur un autre appareil) :
     * la signature prouve déjà que la personne a reçu le mail.
     */
    public function verify(Request $request, Client $client, string $hash, VerifyClientEmail $verify): RedirectResponse
    {
        // hasValidSignature() : signature intacte ET date d'expiration non dépassée.
        // Vérifiée ici plutôt qu'avec le middleware "signed", qui afficherait une page 403 sans explication
        // hash_equals : compare deux chaînes en temps constant (ne laisse rien deviner par la durée de comparaison)
        if (! $request->hasValidSignature() || ! hash_equals(sha1($client->email), $hash)) {
            return $this->redirectFor($client)
                ->with('account_error', 'Ce lien de confirmation n\'est plus valide (il expire après '
                    .Client::VERIFICATION_LINK_MINUTES.' minutes). Connectez-vous pour en recevoir un nouveau.');
        }

        $verify->handle($client);

        return $this->redirectFor($client)
            ->with('account_success', 'Votre adresse email est confirmée.');
    }

    /**
     * Bouton « Renvoyer le mail » de la page Mon compte
     */
    public function send(Request $request): RedirectResponse
    {
        $client = $request->user('client');

        if (! $client->hasVerifiedEmail()) {
            $client->sendEmailVerificationNotification();
        }

        return back()->with('account_success', 'Un nouveau lien de confirmation vient de vous être envoyé.');
    }

    // Le client connecté (sur ce compte) va sur son compte, sinon vers la page de connexion
    private function redirectFor(Client $client): RedirectResponse
    {
        return Auth::guard('client')->id() === $client->id
            ? redirect()->route('account.show')
            : redirect()->route('account.login');
    }
}
