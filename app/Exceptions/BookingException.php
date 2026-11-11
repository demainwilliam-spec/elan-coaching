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

    // Le compte a déjà une réservation pour ce cours (même si elle a été faite avec une autre adresse)
    public static function alreadyBookedWithAccount(): self
    {
        return new self('Vous êtes déjà inscrit(e) à ce cours.');
    }

    public static function cancellationClosed(): self
    {
        return new self('L\'annulation en ligne n\'est possible que jusqu\'à midi le jour du cours. En cas d\'urgence, contactez le coach au '.config('coach.phone.display').'.');
    }

    public static function alreadyCancelled(): self
    {
        return new self('Cette réservation a déjà été annulée.');
    }
}
