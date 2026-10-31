<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
// Authenticatable : la classe de base des modèles qui peuvent se connecter (comme User pour le coach)
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Client extends Authenticatable
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, Notifiable; // Notifiable : permet d'envoyer les mails de vérification et de mot de passe oublié

    protected $fillable = [
        'name',
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
        ];
    }

    // Relation : un client a plusieurs réservations ($client->bookings)
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
