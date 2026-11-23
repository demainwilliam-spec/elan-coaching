<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BookingRequest extends FormRequest
{
    /**
     * Réservation réservée aux comptes à l'adresse confirmée : le prénom, le nom et l'email viennent du compte.
     * (Le coach peut toujours inscrire quelqu'un sans compte depuis son espace, avec une autre route.)
     */
    public function authorize(): bool
    {
        return $this->bookingClient() !== null;
    }

    /**
     * authorize() a répondu false : au lieu de la page 403 par défaut, retour à l'agenda avec une explication.
     * HttpResponseException : une exception qui transporte une réponse toute faite, renvoyée telle quelle au navigateur.
     */
    protected function failedAuthorization(): void
    {
        $message = $this->user('client')
            ? 'Confirmez d\'abord votre adresse email avec le lien reçu par mail, puis réservez.'
            : 'Connectez-vous ou créez un compte pour réserver un cours.';

        // withInput() : renvoie l'id du cours (booking[course_id]) pour que la modale se rouvre sur ce cours
        throw new HttpResponseException(redirect('/#cours')->withInput()->with('booking_error', $message));
    }

    /**
     * Aucun champ à valider : tout vient du compte.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Le compte qui réserve : le client connecté, seulement si son adresse est confirmée (sinon null).
     */
    public function bookingClient(): ?Client
    {
        // user('client') : le client connecté (guard "client"), ou null
        $client = $this->user('client');

        return $client?->hasVerifiedEmail() ? $client : null;
    }
}
