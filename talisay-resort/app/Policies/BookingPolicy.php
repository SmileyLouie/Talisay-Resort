<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isStaff()) {
            return $user->hasPermission('bookings', 'view');
        }

        return (int) $booking->user_id === (int) $user->id;
    }

    /** Staff/admin status management. */
    public function manage(User $user, Booking $booking): bool
    {
        return $user->isAdmin() || ($user->isStaff() && $user->hasPermission('bookings', 'confirm'));
    }

    /** Guest-side changes (modify / cancel own reservation). */
    public function cancel(User $user, Booking $booking): bool
    {
        if ($user->isAdmin() || ($user->isStaff() && $user->hasPermission('bookings', 'cancel'))) {
            return true;
        }

        return (int) $booking->user_id === (int) $user->id;
    }

    public function modify(User $user, Booking $booking): bool
    {
        return (int) $booking->user_id === (int) $user->id;
    }
}
