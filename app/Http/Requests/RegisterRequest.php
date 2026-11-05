<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Exécutée AVANT les règles : l'email est comparé à la base sous sa forme normalisée,
     * sinon "Alice@Mail.com" passerait la règle unique alors que "alice@mail.com" existe déjà
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            // unique:clients,email : refuse une adresse qui a déjà un compte
            'email' => 'required|email:rfc,filter|max:254|unique:clients,email',
            // confirmed : exige un champ password_confirmation identique (faute de frappe impossible à voir)
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Merci d\'indiquer votre prénom.',
            'first_name.max' => 'Votre prénom ne peut pas dépasser :max caractères.',
            'last_name.required' => 'Merci d\'indiquer votre nom de famille.',
            'last_name.max' => 'Votre nom ne peut pas dépasser :max caractères.',
            'email.required' => 'Merci d\'indiquer votre adresse email.',
            'email.email' => 'Cette adresse email n\'est pas valide.',
            'email.max' => 'Votre adresse email ne peut pas dépasser :max caractères.',
            'email.unique' => 'Un compte existe déjà avec cette adresse : connectez-vous.',
            'password.required' => 'Merci de choisir un mot de passe.',
            'password.confirmed' => 'Les deux mots de passe ne sont pas identiques.',
            'password.min' => 'Le mot de passe doit contenir au moins :min caractères.',
        ];
    }
}
