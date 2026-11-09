<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ApplyUiLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (Schema::hasTable('system_settings')) {
                $locale = (string) SystemSetting::get('ui_locale', 'en');
                if (in_array($locale, ['en', 'fil', 'ceb'], true)) {
                    App::setLocale($locale);
                }
            }
        } catch (\Throwable $e) {
        }

        return $next($request);
    }
}
