<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Course extends Model
{
    // Constante de classe : la règle métier est écrite une seule fois (Course::CANCELLATION_DEADLINE_TIME)
    // Annulation en ligne jusqu'à midi le jour du cours (heure belge, cf. config/app.php)
    public const CANCELLATION_DEADLINE_TIME = '12:00';

    // Champs dont la modification concerne les inscrits : ils sont prévenus par mail (cf. UpdateCourse)
    public const NOTIFIABLE_FIELDS = ['starts_at', 'duration_minutes', 'location'];

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

    // Dernier moment pour annuler en ligne : midi le jour du cours.
    // min() : garde-fou pour un cours avant midi (le coach n'en donne pas) → jamais après le début du cours
    public function cancellationDeadline(): Carbon
    {
        // copy() : setTimeFromTimeString() modifierait sinon starts_at lui-même (objet Carbon partagé)
        $noon = $this->starts_at->copy()->setTimeFromTimeString(self::CANCELLATION_DEADLINE_TIME);

        return $noon->min($this->starts_at);
    }

    public function cancellationIsOpen(): bool
    {
        return now()->lessThanOrEqualTo($this->cancellationDeadline());
    }

    // Taux de remplissage en %, pour la largeur de la barre (max(1, ...) évite une division par zéro)
    public function fillPercent(): int
    {
        return (int) round(min(100, $this->confirmed_count / max(1, $this->capacity) * 100));
    }
}
