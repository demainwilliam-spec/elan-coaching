<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Événement Login : déclenché par Laravel à chaque connexion, y compris la reconnexion automatique
        // par le cookie « rester connecté ». Pour un client, c'est une activité (cf. suppression des comptes inactifs)
        Event::listen(function (Login $event) {
            if ($event->guard === 'client') {
                $event->user->markActive();
            }
        });
    }
}
