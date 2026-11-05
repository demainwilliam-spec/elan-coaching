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

// Supprime les jetons « mot de passe oublié » des clients expirés (plus de 60 min)
Schedule::command('auth:clear-resets clients')->daily();
Schedule::command('auth:clear-resets users')->daily();

// Ajoute les cours terminés au compteur des clients. withoutOverlapping : jamais deux exécutions en même temps
Schedule::command('clients:count-courses')->hourly()->withoutOverlapping();

// Prévient par mail les clients inactifs depuis 11 mois ; leur compte est supprimé 30 jours plus tard
// par model:prune s'ils ne se sont pas reconnectés (cf. Client::prunable)
Schedule::command('clients:warn-inactive')->daily();
