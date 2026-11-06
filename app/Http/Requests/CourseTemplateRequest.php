<?php

namespace App\Http\Requests;

use App\Models\CourseTemplate;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

// Validation d'un créneau du planning type. Hérite de CourseRequest (extends) : mêmes règles et messages
// pour le titre, la description, le lieu, la durée et les places ; la date précise est remplacée par jour + heure
class CourseTemplateRequest extends CourseRequest
{
    public function rules(): array
    {
        // parent::rules() : les règles de CourseRequest ; Arr::except retire celle de la date précise
        $rules = Arr::except(parent::rules(), 'starts_at');

        return $rules + [
            'weekday' => [
                'required',
                // in:1,2,...,7 : un des jours de CourseTemplate::WEEKDAYS
                Rule::in(array_keys(CourseTemplate::WEEKDAYS)),
                // Un seul créneau par jour et par heure (le coach ne donne pas deux cours en même temps).
                // unique compare la colonne weekday au jour saisi, where() ajoute « et à la même heure »
                // (« :00 » : la base stocke les secondes, 18:30:00) ; ignore() : en modification,
                // le créneau lui-même ne compte pas comme un doublon
                Rule::unique('course_templates', 'weekday')
                    ->where('start_time', $this->input('start_time').':00')
                    ->ignore($this->route('template')),
            ],
            'start_time' => 'required|date_format:H:i', // le champ <input type="time"> envoie « 18:30 »
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'weekday.required' => 'Choisissez le jour du cours.',
            'weekday.in' => 'Ce jour n\'est pas valide.',
            'start_time.required' => 'Indiquez l\'heure du cours.',
            'start_time.date_format' => 'L\'heure n\'est pas valide.',
            'weekday.unique' => 'Il y a déjà un créneau ce jour-là à cette heure.',
        ];
    }
}
