<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    // Colonnes remplissables via Course::create([...]) : protège contre l'injection de champs non prévus
    protected $fillable = [
        'title',
        'description',
        'location',
        'starts_at',
        'duration_minutes',
        'capacity',
    ];

    // Conversions automatiques à la lecture : starts_at devient un objet Carbon (date manipulable)
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
        ];
    }

    // Relation : un cours a plusieurs réservations ($course->bookings)
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
