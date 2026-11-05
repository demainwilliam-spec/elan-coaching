<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

// Lancée chaque heure (routes/console.php) : ajoute au compteur de chaque client les cours qu'il vient de terminer
class CountFinishedCourses extends Command
{
    protected $signature = 'clients:count-courses';

    protected $description = 'Ajoute les cours terminés au compteur de chaque client';

    public function handle(): int
    {
        // La fin du cours dépend de sa durée : le filtre SQL garde les cours commencés, filter() ceux qui sont terminés
        $bookings = Booking::with('course')
            ->whereNotNull('client_id')
            ->whereNull('counted_at')
            ->where('status', BookingStatus::Confirmed) // une personne restée en attente n'a pas suivi le cours
            ->whereHas('course', fn (Builder $query) => $query->where('starts_at', '<=', now()))
            ->get()
            ->filter(fn (Booking $booking) => $booking->course->hasEnded());

        foreach ($bookings as $booking) {
            // Transaction : la réservation marquée et le compteur augmenté vont toujours ensemble
            DB::transaction(function () use ($booking) {
                $booking->forceFill(['counted_at' => now()])->save();
                // increment() : "courses_done = courses_done + 1" en une seule requête SQL
                Client::whereKey($booking->client_id)->increment('courses_done');
            });
        }

        $this->info("{$bookings->count()} cours compté(s).");

        return self::SUCCESS;
    }
}
