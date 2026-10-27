<?php

namespace App\Exceptions;

use Exception;

// Exception "métier" : une réservation refusée pour une raison normale (pas un bug).
// Son message est destiné au visiteur.
class BookingException extends Exception
{
    // Constructeurs nommés : "static" = appelé sur la classe, sans instance (BookingException::courseStarted())
    public static function courseStarted(): self
    {
        return new self('Ce cours a déjà commencé, il n\'est plus possible de réserver.');
    }

    public static function alreadyBooked(): self
    {
        return new self('Cette adresse email est déjà inscrite à ce cours.');
    }

    public static function cancellationClosed(): self
    {
        return new self('Le cours commence dans moins de 24 h : l\'annulation en ligne n\'est plus possible. Contactez directement le coach.');
    }

    public static function alreadyCancelled(): self
    {
        return new self('Cette réservation a déjà été annulée.');
    }
}
