<?php

namespace App\Enums;

// Enum "backed" : chaque cas a une valeur texte, celle stockée dans la colonne bookings.status
enum BookingStatus: string
{
    case Confirmed = 'confirmed';
    case Waiting = 'waiting';
}
