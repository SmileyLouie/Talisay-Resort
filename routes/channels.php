<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Staff-only channel for booking updates
Broadcast::channel('staff-bookings', function ($user) {
    return $user->isAdmin() || $user->isStaff();
});

// Admin-only channel for payment notifications
Broadcast::channel('admin-payments', function ($user) {
    return $user->isAdmin();
});

// Public capacity channel
Broadcast::channel('capacity', function ($user) {
    return true;
});

// Guest-specific booking channel
Broadcast::channel('guest-booking-{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
