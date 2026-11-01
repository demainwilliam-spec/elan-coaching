<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Page « Mon compte » : infos du compte et réservations à venir
class AccountController extends Controller
{
    public function __invoke(Request $request): View
    {
        // user('client') : le client connecté (la route est protégée par auth:client, il existe forcément)
        $client = $request->user('client');

        $bookings = $client->bookings()
            // whereHas : seulement les réservations dont le cours n'a pas encore commencé
            ->whereHas('course', fn (Builder $query) => $query->where('starts_at', '>', now()))
            // with('course') : charge tous les cours en une requête (sinon une requête par réservation dans la vue)
            ->with('course')
            ->get()
            // sortBy sur la collection : tri par date du cours (le cours est dans une autre table)
            ->sortBy('course.starts_at');

        return view('account.show', [
            'client' => $client,
            'bookings' => $bookings,
        ]);
    }
}
