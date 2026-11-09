<?php

namespace App\Providers;

use App\Models\AccommodationUnit;
use App\Models\PaymentMethod;
use App\Support\PortalSites;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null) === 'https') {
            URL::forceScheme('https');
        }

        // A different session cookie keeps an admin login on port 8001
        // from also signing that person in on the client site.
        if (! $this->app->runningInConsole() && PortalSites::current() === 'staff') {
            config(['session.cookie' => 'talisay_staff_session']);
        }

        try {
            if (Schema::hasTable('system_settings')) {
                $locale = (string) SystemSetting::get('ui_locale', 'en');
                if (in_array($locale, ['en', 'fil', 'ceb'], true)) {
                    App::setLocale($locale);
                }
            }
        } catch (\Throwable $e) {
        }

        View::share('clientPortalUrl', PortalSites::clientBase());
        View::share('staffPortalUrl', PortalSites::staffBase());

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

        View::composer('*', function ($view) {
            static $openUnitCount;
            if ($openUnitCount === null) {
                $openUnitCount = Schema::hasTable('accommodation_units')
                    ? AccommodationUnit::available()->count()
                    : 0;
            }
            $view->with('openUnitCount', $openUnitCount);
        });

        View::composer([
            'partials.guest-payment-fields',
            'bookings.index',
            'payments.index',
        ], function ($view) {
            $view->with('paymentMethods', PaymentMethod::catalog());
        });
    }
}
