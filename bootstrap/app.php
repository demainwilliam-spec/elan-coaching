<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // append : middleware global, donc aussi sur les pages d'erreur (une 404 ne passe par aucune route)
        $middleware->append(SecurityHeaders::class);

        // Deux espaces connectés : on choisit la destination selon l'adresse demandée.
        // $request->is('coach', 'coach/*') : vrai pour /coach et tout ce qui commence par /coach/

        // Middleware "auth" : un visiteur non connecté est envoyé vers la bonne page de connexion
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('coach', 'coach/*')
            ? route('coach.login')
            : route('account.login'));
        // Middleware "guest" : une personne déjà connectée qui ouvre une page de connexion va à son espace
        $middleware->redirectUsersTo(fn (Request $request) => $request->is('coach', 'coach/*')
            ? route('coach.dashboard')
            : route('account.show'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
