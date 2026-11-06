<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

// Les comptes clients, côté coach (consultation seulement)
class ClientController extends Controller
{
    public function __invoke(): View
    {
        return view('coach.clients.index', [
            'clients' => Client::query()
                // withCount : ajoute une colonne calculée sans charger les réservations ;
                // "as upcoming_count" : son nom, lu dans la vue avec $client->upcoming_count
                ->withCount(['bookings as upcoming_count' => fn (Builder $query) => $query
                    ->whereHas('course', fn (Builder $course) => $course->where('starts_at', '>', now()))])
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
        ]);
    }
}
