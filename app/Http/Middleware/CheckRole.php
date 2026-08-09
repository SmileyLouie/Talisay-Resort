<?php

// ============================================================
// CheckRole.php — Role-Based Access Control (RBAC) Middleware
// ============================================================
// This middleware intercepts HTTP requests and checks whether
// the currently authenticated user has one of the allowed roles.
// It is registered in bootstrap/app.php as 'role' and used in
// routes like: Route::middleware('role:admin') or 'role:admin,staff'
// ============================================================

namespace App\Http\Middleware;

// Import the Closure type for the $next callback
use Closure;

// Import the Request class for type-hinting
use Illuminate\Http\Request;

// Import the Response class from Symfony (base of all Laravel responses)
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CheckRole
 *
 * Middleware that enforces role-based access control.
 * Allowed roles are passed as variadic string arguments after 'role:'.
 * Example: Route::middleware('role:admin,staff')
 */
class CheckRole
{
    /**
     * Handle an incoming request and enforce role-based access.
     *
     * @param  Request  $request  The current HTTP request instance
     * @param  Closure  $next     The next middleware or controller handler
     * @param  string   ...$roles One or more allowed roles (e.g., 'admin', 'staff')
     * @return Response           Either a redirect/abort or the next handler's response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Check if the user is not authenticated (guest user)
        // If so, redirect them to the login page
        if (!$request->user()) {
            // Redirect unauthenticated users to the named 'login' route
            return redirect()->route('login');
        }

        // Check if the authenticated user's role is NOT in the list of allowed roles
        // The $roles array contains roles passed as parameters, e.g., ['admin', 'staff']
        if (!in_array($request->user()->role, $roles)) {
            // The user is authenticated but does not have the required role
            // Return HTTP 403 Forbidden with a descriptive message
            abort(403, 'Unauthorized access. You do not have permission to view this page.');
        }

        // The user is authenticated AND has one of the required roles
        // Pass the request to the next middleware or controller
        return $next($request);
    }
}
