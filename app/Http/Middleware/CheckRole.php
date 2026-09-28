<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role-based access control. Registered as 'role' in bootstrap/app.php and
 * used as Route::middleware('role:admin') or 'role:admin,staff'.
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->guest(route('login'));
        }

        $staffBlocked = $user->isStaff() && in_array($user->account_status, ['inactive', 'suspended'], true);

        // Deactivated or suspended accounts lose access immediately, even with a live session.
        if (!$user->is_active || $staffBlocked) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your account has been deactivated.'], 403);
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $staffBlocked
                ? 'Your staff account is currently ' . $user->account_status . '. Please contact the resort administrator.'
                : 'Your account has been deactivated. Please contact support.';

            return redirect()->route('login', $user->isTourist() ? [] : ['portal' => 'admin'])
                ->withErrors(['email' => $message]);
        }

        if (!in_array($user->role, $roles, true)) {
            abort(403, 'Unauthorized access. You do not have permission to view this page.');
        }

        return $next($request);
    }
}
