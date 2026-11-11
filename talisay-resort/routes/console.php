<?php

use App\Services\AutomationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('resort:automate {group=all : frequent, hourly, daily, or all}', function () {
    $service = app(AutomationService::class);
    $group = (string) $this->argument('group');
    $groups = $group === 'all' ? ['frequent', 'hourly', 'daily'] : [$group];

    foreach ($groups as $name) {
        if (! in_array($name, ['frequent', 'hourly', 'daily'], true)) {
            $this->error("Unknown group {$name}. Use frequent, hourly, daily, or all.");

            return 1;
        }
        foreach ($service->{'run' . ucfirst($name)}() as $job => $count) {
            $this->line(str_pad(str_replace('_', ' ', $job), 22) . $count);
        }
    }

    return 0;
})->purpose('Run the resort automation jobs (holds, no-shows, check-outs, reminders, cleanup)');

Artisan::command('bookings:expire-unpaid', function () {
    $released = app(AutomationService::class)->releaseUnpaidHolds();
    $this->info("Released {$released} unpaid reservation(s).");
})->purpose('Release rooms held by unpaid online bookings (uses the hold time in System Settings)');

Schedule::command('resort:automate frequent')->everyFiveMinutes()->withoutOverlapping(10);
Schedule::command('resort:automate hourly')->hourly()->withoutOverlapping(10);
Schedule::command('resort:automate daily')->dailyAt('03:00')->withoutOverlapping(10);

Artisan::command('integration:retry', function () {
    $done = app(\App\Services\SupabaseSyncService::class)->retryPending();
    $this->info("Retried outbox. Completed {$done}.");
})->purpose('Retry failed Supabase synchronization jobs');

Schedule::command('integration:retry')->everyFiveMinutes();
