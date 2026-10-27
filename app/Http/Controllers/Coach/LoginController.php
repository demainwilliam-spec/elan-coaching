<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('coach.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        $credentials['email'] = Str::lower(trim($credentials['email']));

        // attempt() : cherche l'utilisateur par email et compare le hash du mot de passe.
        // 2e argument : "se souvenir de moi" → cookie longue durée en plus de la session
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            // Message volontairement vague : on ne dit pas si c'est l'email ou le mot de passe qui est faux
            return back()
                ->withErrors(['email' => 'Email ou mot de passe incorrect.'])
                ->onlyInput('email'); // old() gardera l'email, jamais le mot de passe
        }

        // Nouvel identifiant de session après connexion : protège contre la "session fixation"
        $request->session()->regenerate();

        // intended() : retour à la page /coach/... demandée avant la connexion, sinon le tableau de bord
        return redirect()->intended(route('coach.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        // On jette toute la session et le jeton CSRF : rien de la session connectée n'est réutilisable
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
