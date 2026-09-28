<?php

namespace App\Exceptions;

use App\Models\Booking;
use RuntimeException;

/**
 * Thrown when a reservation cannot be created or reinstated because the
 * requested unit/dates overlap an existing active booking, or because the
 * daily visitor capacity would be exceeded.
 */
class BookingConflictException extends RuntimeException
{
    public function __construct(string $message, public readonly ?Booking $conflict = null)
    {
        parent::__construct($message);
    }
}
