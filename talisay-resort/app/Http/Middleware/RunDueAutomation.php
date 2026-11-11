<?php

namespace App\Http\Middleware;

use App\Services\AutomationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * After the response is sent, runs any automation that is due, so holds,
 * no-shows and check-outs still happen when no scheduler process is running.
 */
class RunDueAutomation
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        app(AutomationService::class)->runIfDue();
    }
}
