<?php

namespace App\Console\Commands;

use App\Mail\InactiveAccountWarning;
use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

// Lancée chaque jour (routes/console.php) : prévient par mail les clients qui seront supprimés pour inactivité.
// La suppression elle-même est faite plus tard par "model:prune" (Client::prunable), 30 jours après ce mail
class WarnInactiveClients extends Command
{
    // Nom de la commande : php artisan clients:warn-inactive
    protected $signature = 'clients:warn-inactive';

    protected $description = 'Prévient par mail les clients inactifs avant la suppression de leur compte';

    public function handle(): int
    {
        // Inactifs depuis 11 mois (12 mois moins le préavis de 30 jours), confirmés, pas encore avertis
        $limit = now()->subMonths(Client::INACTIVE_RETENTION_MONTHS)->addDays(Client::INACTIVE_WARNING_DAYS);

        $clients = Client::whereNotNull('email_verified_at') // les non confirmés ont leur propre règle (7 jours)
            ->whereNull('inactivity_warned_at')
            ->where('last_active_at', '<', $limit)
            ->get();

        foreach ($clients as $client) {
            // Suppression possible 30 jours après ce mail au plus tôt (cf. Client::prunable)
            $deletionDate = now()->addDays(Client::INACTIVE_WARNING_DAYS);

            Mail::to($client)->send(new InactiveAccountWarning($client, $deletionDate));
            $client->forceFill(['inactivity_warned_at' => now()])->save();
        }

        // info() : message affiché dans le terminal (et dans les logs du planificateur)
        $this->info("{$clients->count()} client(s) averti(s).");

        return self::SUCCESS;
    }
}
