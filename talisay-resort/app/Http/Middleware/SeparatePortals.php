<?php

namespace App\Http\Middleware;

use App\Support\PortalSites;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps guests on port 8000 and administrators/staff on port 8001.
 */
class SeparatePortals
{
    public function handle(Request $request, Closure $next): Response
    {
        $site = PortalSites::current($request);

        if ($site === null || $request->is('up', 'storage/*', 'css/*', 'js/*', 'images/*', 'vendor/*', 'build/*')) {
            return $next($request);
        }

        if ($site === 'staff') {
            return $this->staffSite($request, $next);
        }

        return $this->clientSite($request, $next);
    }

    private function staffSite(Request $request, Closure $next): Response
    {
        if ($request->is('login') && $request->isMethod('GET')) {
            $request->merge(['portal' => 'admin']);
        }

        $user = $request->user();

        if ($user && $user->isTourist()) {
            return redirect()->away(PortalSites::clientUrl('/'));
        }

        if ($request->is('register', 'tour', 'tourist', 'tourist/*', 'auth/*', 'about', 'rooms', 'cottages', 'activities', 'booking', 'booking/exclusive', 'stay/*', 'guest-reviews', 'contact')) {
            return redirect()->away(PortalSites::clientUrl($request->getRequestUri()));
        }

        return $next($request);
    }

    private function clientSite(Request $request, Closure $next): Response
    {
        if ($request->is('login') && $request->query('portal') === 'admin') {
            return redirect()->away(PortalSites::staffUrl('/login'));
        }

        if ($this->isStaffArea($request)) {
            return redirect()->away(PortalSites::staffUrl($request->getRequestUri()));
        }

        $user = $request->user();

        if ($user && ($user->isAdmin() || $user->isStaff()) && ! $request->is('logout')) {
            return redirect()->away(PortalSites::staffUrl('/'));
        }

        return $next($request);
    }

    private function isStaffArea(Request $request): bool
    {
        return $request->is(
            'admin',
            'admin/*',
            'staff',
            'staff/*',
            'bookings',
            'bookings/*',
            'payments',
            'payments/*',
            'reviews',
            'reviews/*',
            'accommodations',
            'accommodations/*',
            'reports',
            'reports/*',
            'chatbot',
            'chatbot/*',
            'staff-management',
            'staff-management/*',
            'staff-roster',
            'users',
            'users/*',
            'tour-manage',
            'tour-manage/*',
            'settings',
            'settings/*',
            'file-maintenance',
            'file-maintenance/*',
            'tasks',
            'tasks/*',
        );
    }
}
