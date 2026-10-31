<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\Client;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View
    {
        return view('account.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        // validated() : uniquement les champs validés (name, email, password), jamais un champ ajouté en douce
        $client = Client::create($request->validated());

        // Événement "compte créé" : Laravel y répond en appelant $client->sendEmailVerificationNotification()
        // (car Client implémente MustVerifyEmail), qui envoie notre mail VerifyAccountEmail
        event(new Registered($client));

        // Connecté directement après l'inscription, sans retaper son mot de passe
        Auth::guard('client')->login($client);
        $request->session()->regenerate();

        return redirect()->route('account.show')
            ->with('account_success', 'Bienvenue ! Votre compte est créé : un mail de confirmation vient de vous être envoyé.');
    }
}
