<?php

use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('bookings:expire-unpaid {--hours=48}', function () {
    $hours = (int) $this->option('hours');
    $cutoff = now()->subHours(max(1, $hours));
    $service = app(BookingService::class);

    $expired = Booking::where('status', Booking::STATUS_PENDING)
        ->where('booking_type', 'regular')
        ->where('created_at', '<', $cutoff)
        ->whereDoesntHave('payment', function ($q) {
            $q->where(function ($inner) {
                $inner->where('status', 'success')->orWhereNotNull('proof_path');
            });
        })
        ->get();

    foreach ($expired as $booking) {
        $service->cancel($booking, 'Automatically released after unpaid hold expired.', 'booking_expired_unpaid');
    }

    $this->info("Released {$expired->count()} unpaid reservation(s).");
})->purpose('Release rooms held by unpaid pending bookings');

Schedule::command('bookings:expire-unpaid')->hourly();

Artisan::command('integration:retry', function () {
    $done = app(\App\Services\SupabaseSyncService::class)->retryPending();
    $this->info("Retried outbox. Completed {$done}.");
})->purpose('Retry failed Supabase synchronization jobs');

Schedule::command('integration:retry')->everyFiveMinutes();
