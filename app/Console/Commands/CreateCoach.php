<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

// Commande artisan maison : Laravel découvre seul les classes de app/Console/Commands
class CreateCoach extends Command
{
    // Nom de la commande à taper : php artisan coach:create
    protected $signature = 'coach:create';

    protected $description = 'Crée un compte coach et lui envoie un lien pour choisir son mot de passe';

    public function handle(): int
    {
        // ask() : pose la question dans le terminal et renvoie la réponse
        $email = strtolower(trim((string) $this->ask('Email du coach')));

        // Les mêmes règles de validation que dans un FormRequest, utilisées ici à la main
        if (Validator::make(['email' => $email], ['email' => 'required|email:rfc,filter|max:254'])->fails()) {
            $this->error('Cette adresse email n\'est pas valide.');

            return self::FAILURE; // code de sortie 1 : la commande a échoué
        }

        $user = User::where('email', $email)->first();

        if ($user !== null && ! $this->confirm("Le compte {$email} existe déjà. Lui envoyer un lien pour changer son mot de passe ?")) {
            return self::SUCCESS;
        }

        // Mot de passe aléatoire que personne ne connaît : le compte ne sert à rien avant que le coach ait choisi le sien.
        // Le mot de passe est hashé automatiquement grâce au cast 'password' => 'hashed' du modèle User
        $user ??= User::create([
            'name' => $this->ask('Nom affiché'),
            'email' => $email,
            'password' => Str::password(32),
        ]);

        // Le même lien que « Mot de passe oublié » ; le mail part par la queue (worker nécessaire)
        if (Password::broker('users')->sendResetLink(['email' => $email]) !== Password::RESET_LINK_SENT) {
            $this->error('Un lien vient déjà d\'être envoyé : patientez une minute avant de recommencer.');

            return self::FAILURE;
        }

        $this->info("Lien envoyé à {$email} (valable ".config('auth.passwords.users.expire').' minutes, ensuite : « Mot de passe oublié ? » sur la page de connexion).');

        return self::SUCCESS;
    }
}
