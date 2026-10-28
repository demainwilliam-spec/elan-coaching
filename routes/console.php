<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tâches planifiées : exécutées par "php artisan schedule:run", qu'un cron lance chaque minute en ligne
// (en local : "php artisan schedule:work" dans un terminal)

// Supprime les réservations des cours passés depuis plus de 6 mois (cf. Booking::prunable)
Schedule::command('model:prune')->daily();

// Vide les mails en échec (table failed_jobs) de plus de 7 jours, par exemple ceux d'une réservation
// supprimée avec son cours avant l'envoi
Schedule::command('queue:prune-failed --hours=168')->daily();
