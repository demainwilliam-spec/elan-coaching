<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Page « Mon compte » (les réservations y seront listées à l'étape 6)
class AccountController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('account.show', [
            // user('client') : le client connecté (la route est protégée par auth:client, il existe forcément)
            'client' => $request->user('client'),
        ]);
    }
}
