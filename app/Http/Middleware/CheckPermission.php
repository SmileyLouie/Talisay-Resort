<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\StaffPermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request and enforce granular RBAC permissions.
     *
     * Usage examples in routes:
     *   Route::middleware('permission:bookings,view')
     *   Route::middleware('permission:payments,verify')
     *   Route::middleware('permission:accommodations') // defaults to action 'view'
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @param  string   $module   Module key (e.g., 'bookings', 'payments')
     * @param  string   $action   Specific action required (default: 'view')
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $module, string $action = 'view'): Response
    {
        $user = $request->user();

        // 1. Ensure user is authenticated
        if (!$user) {
            return redirect()->route('login');
        }

        // 2. Administrators have unrestricted system-wide access
        if ($user->isAdmin()) {
            return $next($request);
        }

        // 3. Verify account is not suspended or inactive
        if (!$user->is_active || in_array($user->account_status, ['inactive', 'suspended'])) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your staff account is currently ' . ($user->account_status ?? 'inactive') . '. Please contact the resort administrator.',
            ]);
        }

        // 4. Verify staff role
        if (!$user->isStaff()) {
            abort(403, 'Unauthorized access. Only resort personnel may access this operational area.');
        }

        // 5. Enforce specific module and action permission
        if (!$user->hasPermission($module, $action)) {
            $moduleName = StaffPermission::MODULES[$module]['name'] ?? ucfirst($module);

            // Log unauthorized access attempt for Admin audit trail
            AuditLog::log('unauthorized_access_attempt', null, null, [
                'user_id'     => $user->id,
                'staff_name'  => $user->name,
                'staff_id'    => $user->staff_id,
                'module'      => $module,
                'action'      => $action,
                'attempted_url' => $request->fullUrl(),
                'ip'          => $request->ip(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'Forbidden',
                    'message' => "Access denied. You do not have permission to perform '{$action}' on {$moduleName}.",
                ], 403);
            }

            // Redirect to staff dashboard with user-friendly alert
            return redirect()->route('staff.dashboard')->with('error', "Access Denied: You do not have permission to access {$moduleName}. If you need access, please contact the resort administrator.");
        }

        return $next($request);
    }
}
