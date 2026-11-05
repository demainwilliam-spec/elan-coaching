<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;

// Trait : du code partagé entre plusieurs classes (ici Client et Booking), ajouté par "use HasFullName"
trait HasFullName
{
    // Accesseur : $model->name n'est pas une colonne, il est calculé à la lecture ("Jean Dupont").
    // Pour enregistrer, on passe toujours par first_name et last_name.
    protected function name(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->first_name} {$this->last_name}"));
    }
}
