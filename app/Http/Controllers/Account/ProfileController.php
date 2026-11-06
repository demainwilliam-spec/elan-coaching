<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Mail\EmailChanged;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// Page « Modifier mon profil » : formulaires indépendants (profil, lien mot de passe, suppression).
// Chacun range ses erreurs dans son propre « sac » (error bag) : validateWithBag('profile', ...),
// lu dans la vue par @error('champ', 'profile'). Sinon une erreur sur « password » dans un formulaire
// s'afficherait aussi sous le champ « password » d'un autre.
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.profile', ['client' => $request->user('client')]);
    }

    // Prénom, nom et email
    public function update(Request $request): RedirectResponse
    {
        $client = $request->user('client');
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $emailChanged = $request->input('email') !== $client->email;

        $data = $request->validateWithBag('profile', [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            // ignore($client->id) : son propre compte ne compte pas comme un doublon
            'email' => ['required', 'email:rfc,filter', 'max:254', Rule::unique('clients', 'email')->ignore($client->id)],
            // Mot de passe actuel exigé seulement si l'email change (protège contre le vol du compte).
            // current_password:client : compare avec le mot de passe du client connecté (guard client)
            'current_password' => $emailChanged ? 'required|current_password:client' : 'nullable',
        ], [
            'first_name.required' => 'Merci d\'indiquer votre prénom.',
            'first_name.max' => 'Votre prénom ne peut pas dépasser :max caractères.',
            'last_name.required' => 'Merci d\'indiquer votre nom de famille.',
            'last_name.max' => 'Votre nom ne peut pas dépasser :max caractères.',
            'email.required' => 'Merci d\'indiquer votre adresse email.',
            'email.email' => 'Cette adresse email n\'est pas valide.',
            'email.unique' => 'Un autre compte utilise déjà cette adresse.',
            'current_password.required' => 'Pour changer d\'adresse, confirmez avec votre mot de passe actuel.',
            'current_password.current_password' => 'Mot de passe incorrect.',
        ]);

        $oldEmail = $client->email;

        DB::transaction(function () use ($client, $data, $emailChanged) {
            $client->first_name = $data['first_name'];
            $client->last_name = $data['last_name'];

            if ($emailChanged) {
                $client->email = $data['email'];
                $client->email_verified_at = null; // la nouvelle adresse doit être confirmée à son tour
            }

            $client->save();

            // Le coach voit le nom des inscrits : on le met à jour sur les réservations à venir
            $client->bookings()
                ->whereHas('course', fn (Builder $query) => $query->where('starts_at', '>', now()))
                ->update(['first_name' => $client->first_name, 'last_name' => $client->last_name]);
        });

        if (! $emailChanged) {
            return back()->with('account_success', 'Votre profil est enregistré.');
        }

        // Confirmation de la nouvelle adresse (même mail qu'à l'inscription) + alerte à l'ancienne
        $client->sendEmailVerificationNotification();
        Mail::to($oldEmail)->send(new EmailChanged($client->first_name, $client->email));

        return back()->with('account_success', 'Votre profil est enregistré. Un lien de confirmation vient d\'être envoyé à votre nouvelle adresse.');
    }

    // Mot de passe : pas de formulaire ici, on passe par le lien « mot de passe oublié » envoyé à l'adresse du compte
    // (prouve l'accès à la boîte mail, même si quelqu'un trouve la session ouverte sur un appareil)
    public function sendPasswordLink(Request $request): RedirectResponse
    {
        $status = Password::broker('clients')->sendResetLink(['email' => $request->user('client')->email]);

        // RESET_THROTTLED : un lien a déjà été demandé il y a moins de 60 secondes
        if ($status === Password::RESET_THROTTLED) {
            return back()->with('account_error', 'Un lien vient déjà d\'être envoyé : patientez une minute avant de recommencer.');
        }

        return back()->with('account_success', 'Un lien pour changer votre mot de passe vient d\'être envoyé à votre adresse email.');
    }

    // Suppression du compte (droit à l'effacement, RGPD)
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('deletion', [
            'password' => 'required|current_password:client',
        ], [
            'password.required' => 'Confirmez avec votre mot de passe.',
            'password.current_password' => 'Mot de passe incorrect.',
        ]);

        $client = $request->user('client');

        Auth::guard('client')->logout();

        // Jeton « mot de passe oublié » éventuel : plus rien ne doit rester lié au compte
        Password::broker('clients')->deleteToken($client);

        // Les réservations restent (client_id remis à null par la clé étrangère, cf. migration) :
        // le client reste inscrit à ses cours et peut toujours annuler par le lien reçu par mail
        $client->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('account.deleted');
    }
}
