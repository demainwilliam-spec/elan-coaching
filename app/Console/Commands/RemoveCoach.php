<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;

class RemoveCoach extends Command
{
    protected $signature = 'coach:remove';

    protected $description = 'Supprime un compte coach (les cours et les réservations restent)';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->ask('Email du coach à supprimer')));
        $user = User::where('email', $email)->first();

        if ($user === null) {
            $this->error("Aucun compte coach pour {$email}.");

            return self::FAILURE;
        }

        // Garde-fou : sans aucun compte, plus personne ne pourrait gérer les cours
        if (User::count() === 1) {
            $this->error('C\'est le dernier compte coach : créez-en un autre avant de le supprimer.');

            return self::FAILURE;
        }

        if (! $this->confirm("Supprimer le compte de {$user->name} ({$email}) ?")) {
            return self::SUCCESS;
        }

        // Le lien de mot de passe éventuel disparaît aussi. Une session encore ouverte ne survit pas :
        // à la requête suivante, Laravel ne retrouve plus le compte et renvoie vers la connexion
        Password::broker('users')->deleteToken($user);
        $user->delete();

        $this->info("Compte {$email} supprimé.");

        return self::SUCCESS;
    }
}
