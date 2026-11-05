<?php

namespace App\Models;

use App\Mail\ResetPasswordEmail;
use App\Mail\VerifyAccountEmail;
use App\Models\Concerns\HasFullName;
use Database\Factories\ClientFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Relations\HasMany;
// Authenticatable : la classe de base des modèles qui peuvent se connecter (comme User pour le coach)
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;

// implements MustVerifyEmail : Laravel envoie le mail de vérification à l'inscription (événement Registered).
// Les méthodes hasVerifiedEmail(), markEmailAsVerified()... sont déjà fournies par Authenticatable
class Client extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, HasFullName, MassPrunable, Notifiable; // Notifiable : permet d'envoyer les mails de vérification et de mot de passe oublié

    // Durée de validité du lien de confirmation reçu par mail
    public const VERIFICATION_LINK_MINUTES = 60;

    // Un compte jamais confirmé est supprimé après ce délai (faute de frappe, robot, adresse d'autrui)
    public const UNVERIFIED_RETENTION_DAYS = 7;

    // Compte sans activité (connexion, réservation) depuis ce délai : supprimé (RGPD, durée limitée)...
    public const INACTIVE_RETENTION_MONTHS = 12;

    // ... après un mail d'avertissement envoyé ce nombre de jours avant (cf. commande clients:warn-inactive)
    public const INACTIVE_WARNING_DAYS = 30;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
    ];

    // Jamais inclus si le modèle est converti en tableau / JSON
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // le mot de passe est haché automatiquement à l'enregistrement
            'last_active_at' => 'datetime',
            'inactivity_warned_at' => 'datetime',
        ];
    }

    // Événement de modèle : à la création, le compte est actif depuis maintenant
    protected static function booted(): void
    {
        static::creating(function (Client $client) {
            $client->last_active_at ??= now();
        });
    }

    // Activité du client (connexion, réservation) : repousse la suppression pour inactivité
    // et annule un éventuel avertissement déjà envoyé
    public function markActive(): void
    {
        $this->forceFill([
            'last_active_at' => now(),
            'inactivity_warned_at' => null,
        ])->save();
    }

    // Remplace le mail de vérification de Laravel (en anglais, avec son propre gabarit) par le nôtre
    public function sendEmailVerificationNotification(): void
    {
        Mail::to($this)->send(new VerifyAccountEmail($this));
    }

    // Même principe pour « mot de passe oublié » : appelée par le broker avec le jeton à mettre dans le lien.
    // #[\SensitiveParameter] : en cas d'erreur, PHP masque cette valeur dans les traces (logs, page de debug)
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        Mail::to($this)->send(new ResetPasswordEmail($this, $token));
    }

    // Comptes à supprimer par "php artisan model:prune" (chaque jour), deux cas.
    // Chaque cas est une fonction (groupe entre parenthèses en SQL) : sinon les AND et le OR se mélangeraient
    public function prunable(): Builder
    {
        return static::query()
            // 1. Jamais confirmés et créés il y a plus de 7 jours
            ->where(fn (Builder $query) => $query
                ->whereNull('email_verified_at')
                ->where('created_at', '<', now()->subDays(self::UNVERIFIED_RETENTION_DAYS)))
            // 2. Inactifs depuis plus de 12 mois ET avertis par mail depuis au moins 30 jours
            //    (le préavis de 30 jours est garanti même pour un compte déjà très ancien)
            ->orWhere(fn (Builder $query) => $query
                ->where('last_active_at', '<', now()->subMonths(self::INACTIVE_RETENTION_MONTHS))
                ->where('inactivity_warned_at', '<=', now()->subDays(self::INACTIVE_WARNING_DAYS)));
    }

    // Relation : un client a plusieurs réservations ($client->bookings)
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
