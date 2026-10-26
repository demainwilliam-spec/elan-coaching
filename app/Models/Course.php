<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Builder;
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

    // Scope local : s'utilise comme Course::upcoming() (le préfixe "scope" disparaît à l'appel)
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('starts_at', '>', now())->orderBy('starts_at');
    }

    // Scope local : ajoute confirmed_count et waiting_count à chaque cours, en une seule requête SQL
    public function scopeWithBookingCounts(Builder $query): void
    {
        $query->withCount([
            'bookings as confirmed_count' => fn (Builder $q) => $q->where('status', BookingStatus::Confirmed),
            'bookings as waiting_count' => fn (Builder $q) => $q->where('status', BookingStatus::Waiting),
        ]);
    }

    // Nécessite withBookingCounts() (sinon confirmed_count n'existe pas)
    public function placesLeft(): int
    {
        return max(0, $this->capacity - $this->confirmed_count);
    }

    public function isFull(): bool
    {
        return $this->placesLeft() === 0;
    }

    // Taux de remplissage en %, pour la largeur de la barre (max(1, ...) évite une division par zéro)
    public function fillPercent(): int
    {
        return (int) round(min(100, $this->confirmed_count / max(1, $this->capacity) * 100));
    }
}
