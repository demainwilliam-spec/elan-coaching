<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Validation du formulaire de cours du coach (création et modification)
class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // l'accès est déjà protégé par le middleware auth des routes /coach
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'location' => 'required|string|max:150',
            // after:now : la date doit être dans le futur (le champ datetime-local envoie "2026-11-03T18:30")
            'starts_at' => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:15|max:240',
            'capacity' => 'required|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Indiquez le titre du cours.',
            'title.max' => 'Le titre ne doit pas dépasser 100 caractères.',
            'description.max' => 'La description ne doit pas dépasser 1000 caractères.',
            'location.required' => 'Indiquez le lieu du cours.',
            'location.max' => 'Le lieu ne doit pas dépasser 150 caractères.',
            'starts_at.required' => 'Indiquez la date et l\'heure du cours.',
            'starts_at.date' => 'La date n\'est pas valide.',
            'starts_at.after' => 'La date doit être dans le futur.',
            'duration_minutes.required' => 'Indiquez la durée du cours.',
            'duration_minutes.integer' => 'La durée doit être un nombre de minutes.',
            'duration_minutes.min' => 'La durée minimum est de 15 minutes.',
            'duration_minutes.max' => 'La durée maximum est de 240 minutes.',
            'capacity.required' => 'Indiquez le nombre de places.',
            'capacity.integer' => 'Le nombre de places doit être un nombre entier.',
            'capacity.min' => 'Il faut au moins 1 place.',
            'capacity.max' => 'Maximum 100 places.',
        ];
    }
}
