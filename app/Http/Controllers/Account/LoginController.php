<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

// Même fonctionnement que Coach\LoginController, mais sur le guard "client"
class LoginController extends Controller
{
    public function show(Request $request): View
    {
        // Lien « Se connecter » de l'agenda (?retour=cours) : après connexion, retour aux cours.
        // Valeur comparée à une liste fixe, jamais une adresse libre (sinon un lien piégé pourrait
        // renvoyer vers un autre site après la connexion : faille « open redirect »)
        if ($request->query('retour') === 'cours') {
            redirect()->setIntendedUrl(url('/').'#cours'); // l'adresse qu'utilisera intended() dans store()
        }

        return view('account.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Merci d\'indiquer votre adresse email.',
            'email.email' => 'Cette adresse email n\'est pas valide.',
            'password.required' => 'Merci d\'indiquer votre mot de passe.',
        ]);
        $credentials['email'] = Str::lower(trim($credentials['email']));

        if (! Auth::guard('client')->attempt($credentials, $request->boolean('remember'))) {
            // Message vague : on ne révèle pas si l'adresse a un compte
            return back()
                ->withErrors(['email' => 'Email ou mot de passe incorrect.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        // intended() : retour à la page demandée avant la connexion (ex. /compte), sinon le compte
        return redirect()->intended(route('account.show'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('client')->logout();

        // Session entièrement jetée, comme pour le coach (si le coach était aussi connecté
        // dans le même navigateur, il est déconnecté lui aussi : cas jugé négligeable)
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
