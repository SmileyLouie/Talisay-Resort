<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $certificate = storage_path('certs/cacert.pem');
        if (is_file($certificate)) {
            Http::globalOptions(['verify' => $certificate]);
        }

        // Brute-force protection: per account+IP so one attacker cannot lock out everyone.
        RateLimiter::for('login', function (Request $request) {
            $key = strtolower((string) $request->input('email')) . '|' . $request->ip();

            return Limit::perMinute(5)->by($key)->response(function () {
                return back()
                    ->withInput(request()->only('email', 'portal'))
                    ->withErrors(['email' => 'Too many login attempts. Please wait a minute and try again.']);
            });
        });

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        // The chatbot may call a paid AI provider; keep anonymous traffic bounded.
        RateLimiter::for('chatbot', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()));
    }
}
