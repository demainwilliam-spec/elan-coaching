<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Middleware "auth" : un visiteur non connecté est envoyé vers la page de connexion du coach
        $middleware->redirectGuestsTo(fn () => route('coach.login'));
        // Middleware "guest" : le coach déjà connecté qui ouvre la page de connexion va au tableau de bord
        $middleware->redirectUsersTo(fn () => route('coach.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
