<?php

namespace App\Actions;

use App\Models\Booking;
use App\Models\Client;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;

class VerifyClientEmail
{
    /**
     * Marque l'adresse comme confirmée et rattache au compte les réservations faites avec cette adresse.
     * Le rattachement n'a lieu qu'ici, après la preuve que l'adresse appartient bien au client :
     * sinon n'importe qui pourrait créer un compte avec l'adresse d'un autre et voir ses réservations.
     */
    public function handle(Client $client): void
    {
        if ($client->hasVerifiedEmail()) {
            return; // lien cliqué deux fois : rien à refaire
        }

        DB::transaction(function () use ($client) {
            $client->markEmailAsVerified(); // remplit email_verified_at

            // Réservations faites sans compte avec la même adresse (les emails sont stockés en minuscules)
            Booking::whereNull('client_id')
                ->where('email', $client->email)
                ->update(['client_id' => $client->id]);
        });

        event(new Verified($client)); // événement standard de Laravel, pour d'éventuels besoins futurs
    }
}
