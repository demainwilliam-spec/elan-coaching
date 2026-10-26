<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    /**
     * En cas d'erreur, Laravel redirige ici (par défaut : la page précédente, sans l'ancre).
     */
    protected $redirect = '/#contact';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Formulaire public : tout visiteur a le droit de l'envoyer.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Chaque règle est séparée par | et toutes doivent passer.
        return [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'message' => 'required|string|min:10|max:5000',
        ];
    }

    /**
     * Messages d'erreur en français (aucun fichier de traduction lang/fr installé).
     * Clé = "champ.règle" ; :min et :max sont remplacés par les valeurs des règles.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Merci d\'indiquer votre nom.',
            'name.max' => 'Votre nom ne peut pas dépasser :max caractères.',
            'email.required' => 'Merci d\'indiquer votre adresse email.',
            'email.email' => 'Cette adresse email n\'est pas valide.',
            'email.max' => 'Votre adresse email ne peut pas dépasser :max caractères.',
            'message.required' => 'Merci d\'écrire votre message.',
            'message.min' => 'Votre message doit contenir au moins :min caractères.',
            'message.max' => 'Votre message ne peut pas dépasser :max caractères.',
        ];
    }
}
