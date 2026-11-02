<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

// Créneau du planning type (ex. « mardi 18h30, Cardio training ») : sert de modèle pour générer les cours
class CourseTemplate extends Model
{
    // Noms des jours, indexés par le numéro ISO (1 = lundi … 7 = dimanche) : formulaires et affichage
    public const WEEKDAYS = [
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
        7 => 'Dimanche',
    ];

    protected $fillable = [
        'weekday',
        'start_time',
        'title',
        'description',
        'location',
        'duration_minutes',
        'capacity',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
        ];
    }

    // Mutateur : à l'enregistrement, « 18:30 » (champ du formulaire) devient « 18:30:00 », le format de la base.
    // Toutes les heures sont ainsi stockées pareil, ce qui permet de les comparer (règle unique, tri)
    protected function startTime(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strlen($value) === 5 ? $value.':00' : $value,
        );
    }

    // Ordre de la semaine : lundi d'abord, puis par heure ($query->ordered() → CourseTemplate::ordered()->get())
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('weekday')->orderBy('start_time');
    }

    public function weekdayName(): string
    {
        return self::WEEKDAYS[$this->weekday];
    }

    // « 18:30:00 » (format de la base) → « 18h30 », comme dans l'agenda
    public function startTimeLabel(): string
    {
        return str_replace(':', 'h', substr($this->start_time, 0, 5));
    }
}
