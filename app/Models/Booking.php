<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Booking extends Model
{
    // MassPrunable : la commande "php artisan model:prune" supprime les lignes décrites par prunable()
    // (en une seule requête DELETE, sans charger chaque réservation)
    use MassPrunable;

    // Durée de conservation annoncée dans la politique de confidentialité
    public const RETENTION_MONTHS = 6;
    // course_id absent volontairement : on crée via $course->bookings()->create([...]), qui le remplit
    protected $fillable = [
        'name',
        'email',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class, // la colonne texte devient un BookingStatus
        ];
    }

    // Événement de modèle : s'exécute juste avant chaque INSERT
    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            $booking->cancel_token ??= Str::random(48); // ??= : n'assigne que si pas déjà défini
        });
    }

    // Position dans la file d'attente (1 = la prochaine promue), null si la réservation est confirmée.
    // On trie par id plutôt que created_at : l'id est strictement croissant, created_at peut avoir des ex æquo.
    public function waitingPosition(): ?int // ?int : un entier ou null
    {
        if ($this->status !== BookingStatus::Waiting) {
            return null;
        }

        return self::where('course_id', $this->course_id)
            ->where('status', BookingStatus::Waiting)
            ->where('id', '<=', $this->id)
            ->count();
    }

    // Réservations à supprimer : celles des cours commencés il y a plus de RETENTION_MONTHS mois
    public function prunable(): Builder
    {
        // whereHas : filtre les réservations selon une condition sur leur cours
        return static::whereHas('course', fn (Builder $query) => $query
            ->where('starts_at', '<', now()->subMonths(self::RETENTION_MONTHS)));
    }

    // Relation inverse : une réservation appartient à un cours ($booking->course)
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
