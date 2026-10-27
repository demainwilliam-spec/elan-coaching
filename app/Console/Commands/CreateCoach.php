<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

// Commande artisan maison : Laravel découvre seul les classes de app/Console/Commands
class CreateCoach extends Command
{
    // Nom de la commande à taper : php artisan coach:create
    protected $signature = 'coach:create';

    protected $description = 'Crée le compte du coach, ou change son mot de passe s\'il existe déjà';

    public function handle(): int
    {
        // ask() : pose la question dans le terminal et renvoie la réponse
        $email = strtolower(trim($this->ask('Email du coach')));
        $user = User::where('email', $email)->first();

        if ($user !== null && ! $this->confirm("Le compte {$email} existe déjà. Changer son mot de passe ?")) {
            return self::SUCCESS;
        }

        $name = $user?->name ?? $this->ask('Nom affiché', 'JF');
        // secret() : comme ask(), mais la saisie n'apparaît pas à l'écran
        $password = $this->secret('Mot de passe (12 caractères minimum)');
        $confirmation = $this->secret('Retapez le mot de passe');

        // Les mêmes règles de validation que dans un FormRequest, utilisées ici à la main
        $validator = Validator::make(
            ['email' => $email, 'password' => $password, 'password_confirmation' => $confirmation],
            ['email' => 'required|email:rfc,filter|max:254', 'password' => 'required|string|min:12|confirmed'],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE; // code de sortie 1 : la commande a échoué
        }

        // updateOrCreate : met à jour la ligne qui a cet email, ou la crée.
        // Le mot de passe est hashé automatiquement grâce au cast 'password' => 'hashed' du modèle User
        User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => $password]);

        $this->info($user === null ? "Compte coach créé pour {$email}." : "Mot de passe de {$email} mis à jour.");

        return self::SUCCESS;
    }
}
