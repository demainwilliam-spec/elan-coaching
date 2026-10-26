<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
{
    /**
     * En cas d'erreur, Laravel redirige ici (par défaut : la page précédente, sans l'ancre).
     */
    protected $redirect = '/#cours';

    /**
     * Formulaire public : tout visiteur a le droit de réserver.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Les champs s'appellent booking[name] / booking[email] dans le HTML (pour ne pas se mélanger
     * avec ceux du formulaire de contact) : Laravel y accède avec un point, booking.name.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'booking.name' => 'required|string|max:100',
            'booking.email' => 'required|email:rfc,filter|max:254', // filter : exige un domaine avec un point (refuse "a@b")
        ];
    }

    /**
     * Messages d'erreur en français. Clé = "champ.règle".
     */
    public function messages(): array
    {
        return [
            'booking.name.required' => 'Merci d\'indiquer votre nom.',
            'booking.name.max' => 'Votre nom ne peut pas dépasser :max caractères.',
            'booking.email.required' => 'Merci d\'indiquer votre adresse email.',
            'booking.email.email' => 'Cette adresse email n\'est pas valide.',
            'booking.email.max' => 'Votre adresse email ne peut pas dépasser :max caractères.',
        ];
    }
}
