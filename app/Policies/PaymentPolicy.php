<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isStaff()) {
            return $user->hasPermission('payments', 'view');
        }

        return (int) $payment->booking?->user_id === (int) $user->id;
    }

    public function uploadProof(User $user, Payment $payment): bool
    {
        return (int) $payment->booking?->user_id === (int) $user->id;
    }

    public function verify(User $user, Payment $payment): bool
    {
        return $user->isAdmin() || ($user->isStaff() && $user->hasPermission('payments', 'verify'));
    }
}
