<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

// Même parcours que Account\PasswordResetController, avec le broker des coachs (table password_reset_tokens)
class PasswordResetController extends Controller
{
    // 1. Formulaire « Mot de passe oublié » (email seul)
    public function request(): View
    {
        return view('coach.forgot-password');
    }

    // 2. Envoi du lien par mail
    public function email(Request $request): RedirectResponse
    {
        $request->validate(
            ['email' => 'required|email'],
            ['email.required' => 'Merci d\'indiquer votre adresse email.', 'email.email' => 'Cette adresse email n\'est pas valide.'],
        );

        $status = Password::broker('users')->sendResetLink([
            'email' => Str::lower(trim($request->input('email'))),
        ]);

        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput()->withErrors(['email' => 'Un lien vient déjà d\'être envoyé : patientez une minute avant de recommencer.']);
        }

        // Même réponse que le compte existe ou non : on ne révèle pas quelles adresses sont des coachs
        return back()->with('status', 'Si un compte coach existe avec cette adresse, un lien vient de lui être envoyé.');
    }

    // 3. Formulaire du nouveau mot de passe (lien reçu par mail)
    public function edit(Request $request, string $token): View
    {
        return view('coach.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    // 4. Enregistrement du nouveau mot de passe
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            // 12 caractères, comme php artisan coach:create : ce compte donne accès aux données de tous les clients
            'password' => ['required', 'confirmed', PasswordRule::min(12)],
        ], [
            'password.required' => 'Merci de choisir un mot de passe.',
            'password.confirmed' => 'Les deux mots de passe ne sont pas identiques.',
            'password.min' => 'Le mot de passe doit contenir au moins :min caractères.',
        ]);

        $status = Password::broker('users')->reset(
            [
                'email' => Str::lower(trim($request->input('email'))),
                'password' => $request->input('password'),
                'token' => $request->input('token'),
            ],
            function (User $coach, string $password) {
                $coach->forceFill([
                    'password' => $password, // haché par le cast "hashed"
                    // Nouveau jeton « rester connecté » : les cookies des autres appareils ne marchent plus
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($coach));

                Auth::guard('web')->login($coach);
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return redirect()->route('coach.password.request')
                ->withErrors(['email' => 'Ce lien n\'est plus valide (il expire après 60 minutes et ne sert qu\'une fois). Demandez-en un nouveau.']);
        }

        $request->session()->regenerate();

        return redirect()->route('coach.dashboard')->with('status', 'Votre mot de passe a été enregistré.');
    }
}
