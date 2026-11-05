<?php

namespace App\Actions;

use App\Models\Booking;
use App\Models\Client;
use Illuminate\Auth\Events\Verified;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class VerifyClientEmail
{
    /**
     * Marque l'adresse comme confirmée, rattache au compte les réservations faites avec cette adresse
     * et fait passer ses réservations à venir sur cette adresse (cas d'un changement d'email).
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
                // counted_at remis à null : déjà comptée pour un ancien compte supprimé, elle compte pour celui-ci
                ->update(['client_id' => $client->id, 'counted_at' => null]);

            // Adresse changée depuis le profil : les réservations à venir du compte passent sur la nouvelle
            // adresse, pour que leurs mails (promotion, cours modifié...) arrivent au bon endroit.
            // Cours où cette adresse est déjà inscrite : exclus (l'index unique course_id + email refuserait).
            // Liste lue à part : MariaDB interdit un UPDATE de bookings avec une sous-requête sur bookings
            $taken = Booking::where('email', $client->email)->pluck('course_id');

            $client->bookings()
                ->where('email', '!=', $client->email)
                ->whereNotIn('course_id', $taken)
                ->whereHas('course', fn (Builder $query) => $query->where('starts_at', '>', now()))
                ->update(['email' => $client->email]);
        });

        event(new Verified($client)); // événement standard de Laravel, pour d'éventuels besoins futurs
    }
}
