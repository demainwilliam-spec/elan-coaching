<?php

namespace App\Http\Controllers\Account;

use App\Actions\VerifyClientEmail;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    // 1. Formulaire « Mot de passe oublié » (email seul)
    public function request(): View
    {
        return view('account.forgot-password');
    }

    // 2. Envoi du lien par mail
    public function email(Request $request): RedirectResponse
    {
        $request->validate(
            ['email' => 'required|email'],
            ['email.required' => 'Merci d\'indiquer votre adresse email.', 'email.email' => 'Cette adresse email n\'est pas valide.'],
        );

        // broker('clients') : le « guichet » configuré dans config/auth.php (table client_password_reset_tokens).
        // Il cherche le compte, crée le jeton et appelle $client->sendPasswordResetNotification($token)
        $status = Password::broker('clients')->sendResetLink([
            'email' => Str::lower(trim($request->input('email'))),
        ]);

        // RESET_THROTTLED : un lien a déjà été demandé il y a moins de 60 secondes pour cette adresse
        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput()->withErrors(['email' => 'Un lien vient déjà d\'être envoyé : patientez une minute avant de recommencer.']);
        }

        // Même réponse que le compte existe ou non (RESET_LINK_SENT / INVALID_USER) :
        // on ne révèle pas quelles adresses ont un compte
        return back()->with('account_success', 'Si un compte existe avec cette adresse, un lien de réinitialisation vient de lui être envoyé.');
    }

    // 3. Formulaire du nouveau mot de passe (lien reçu par mail : /compte/reinitialisation/{token}?email=...)
    public function edit(Request $request, string $token): View
    {
        return view('account.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    // 4. Enregistrement du nouveau mot de passe
    public function update(Request $request, VerifyClientEmail $verify): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [
            'password.required' => 'Merci de choisir un mot de passe.',
            'password.confirmed' => 'Les deux mots de passe ne sont pas identiques.',
            'password.min' => 'Le mot de passe doit contenir au moins :min caractères.',
        ]);

        // reset() vérifie le jeton (empreinte, expiration), appelle la fonction si tout est bon,
        // puis supprime le jeton : le lien ne sert qu'une fois
        $status = Password::broker('clients')->reset(
            [
                'email' => Str::lower(trim($request->input('email'))),
                'password' => $request->input('password'),
                'token' => $request->input('token'),
            ],
            function (Client $client, string $password) use ($verify) {
                // forceFill : remplit sans passer par $fillable (remember_token n'y est pas, volontairement)
                $client->forceFill([
                    'password' => $password, // haché par le cast "hashed"
                    // Nouveau jeton « rester connecté » : les cookies des autres appareils ne marchent plus
                    'remember_token' => Str::random(60),
                ])->save();

                // Avoir reçu le lien prouve que l'adresse est à lui : elle est confirmée du même coup
                $verify->handle($client);

                event(new PasswordReset($client));

                Auth::guard('client')->login($client);
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            // INVALID_TOKEN (lien expiré, déjà utilisé ou modifié) ou INVALID_USER (email changé dans l'adresse)
            return redirect()->route('account.password.request')
                ->with('account_error', 'Ce lien de réinitialisation n\'est plus valide (il expire après 60 minutes et ne sert qu\'une fois). Demandez-en un nouveau.');
        }

        $request->session()->regenerate();

        return redirect()->route('account.show')->with('account_success', 'Votre mot de passe a été modifié.');
    }
}
