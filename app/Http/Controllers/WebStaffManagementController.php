<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\StaffPermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class WebStaffManagementController extends Controller
{
    /**
     * Admin Staff Management Roster View with RBAC and Activity Audit Trail.
     */
    public function staffIndex(Request $request)
    {
        $tab = $request->get('tab', 'roster'); // roster | rbac | activity

        $staffQuery = User::where('role', 'staff')
                          ->with(['permissions']);

        if ($request->filled('search')) {
            $search = $request->search;
            $staffQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('staff_id', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department')) {
            $staffQuery->where('department', $request->department);
        }

        if ($request->filled('status')) {
            $staffQuery->where('account_status', $request->status);
        }

        $staffMembers = $staffQuery->orderBy('name')->get();
        $modules      = StaffPermission::MODULES;
        $presets      = StaffPermission::PRESETS;

        // Activity Log query if on the activity tab
        $activityLogs = null;
        if ($tab === 'activity') {
            $logQuery = AuditLog::with('user')
                ->where(function ($q) {
                    $q->whereHas('user', fn($sq) => $sq->where('role', 'staff'))
                      ->orWhereIn('action', [
                          'staff_account_created',
                          'staff_permissions_updated',
                          'staff_duty_status_updated',
                          'staff_account_status_changed',
                          'staff_password_reset_by_admin',
                          'unauthorized_access_attempt',
                      ]);
                });

            if ($request->filled('staff_user_id')) {
                $logQuery->where('user_id', $request->staff_user_id);
            }

            if ($request->filled('action_filter')) {
                $logQuery->where('action', 'like', "%{$request->action_filter}%");
            }

            if ($request->filled('date_from')) {
                $logQuery->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $logQuery->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->filled('activity_search')) {
                $as = $request->activity_search;
                $logQuery->where(function ($q) use ($as) {
                    $q->where('action', 'like', "%{$as}%")
                      ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$as}%")->orWhere('staff_id', 'like', "%{$as}%"));
                });
            }

            $activityLogs = $logQuery->latest()->paginate(15)->withQueryString();
        }

        return view('staff.management', compact(
            'staffMembers',
            'tab',
            'modules',
            'presets',
            'activityLogs'
        ));
    }

    /**
     * Store a newly created staff account with job assignment and RBAC permissions.
     */
    public function staffStore(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|string|email|max:255|unique:users,email',
            'phone'          => 'nullable|string|max:20',
            'staff_id'       => 'nullable|string|max:50|unique:users,staff_id',
            'position'       => 'required|string|max:100',
            'department'     => 'required|string|max:100',
            'account_status' => 'required|in:active,inactive,on_leave,suspended',
            'duty_status'    => 'required|in:available,busy,on_leave,off_duty',
            'password'       => 'required|string|min:8|confirmed',
            'modules'        => 'nullable|array',
        ]);

        // Auto-generate staff_id if empty
        $staffId = !empty($data['staff_id'])
            ? trim($data['staff_id'])
            : ('TBR-STF-' . str_pad((User::where('role', 'staff')->count() + 1), 3, '0', STR_PAD_LEFT));

        $user = DB::transaction(function () use ($data, $staffId, $request) {
            $isActive = in_array($data['account_status'], ['active', 'on_leave']);

            $user = User::create([
                'name'           => $data['name'],
                'email'          => $data['email'],
                'phone'          => $data['phone'] ?? null,
                'staff_id'       => $staffId,
                'position'       => $data['position'],
                'department'     => $data['department'],
                'account_status' => $data['account_status'],
                'duty_status'    => $data['duty_status'],
                'role'           => 'staff',
                'is_active'      => $isActive,
                'password'       => Hash::make($data['password']),
            ]);

            // Assign granted modules & actions
            $modulesInput = $request->input('modules', []);
            foreach ($modulesInput as $moduleKey => $moduleData) {
                if (!empty($moduleData['enabled']) || is_array($moduleData)) {
                    $actions = $moduleData['actions'] ?? (is_array($moduleData) && !isset($moduleData['enabled']) ? $moduleData : []);
                    if (empty($actions) && isset(StaffPermission::MODULES[$moduleKey]['default'])) {
                        $actions = StaffPermission::MODULES[$moduleKey]['default'];
                    }

                    StaffPermission::create([
                        'user_id' => $user->id,
                        'module'  => $moduleKey,
                        'actions' => array_values(array_unique($actions)),
                    ]);
                }
            }

            AuditLog::create([
                'user_id'    => Auth::id(),
                'action'     => 'staff_account_created',
                'model_type' => User::class,
                'model_id'   => $user->id,
                'new_values' => [
                    'name'       => $user->name,
                    'staff_id'   => $user->staff_id,
                    'position'   => $user->position,
                    'department' => $user->department,
                    'modules'    => array_keys($modulesInput),
                ],
            ]);

            return $user;
        });

        return redirect()->route('tasks.staff', ['tab' => 'rbac'])
            ->with('success', "Staff account for {$user->name} ({$user->staff_id}) created successfully with assigned role permissions.");
    }

    /**
     * Update Staff Job Assignments, Department, and RBAC Permissions.
     */
    public function updatePermissions(Request $request, User $user)
    {
        if (!$user->isStaff()) {
            return back()->with('error', 'Permissions can only be assigned to staff accounts.');
        }

        $data = $request->validate([
            'staff_id'       => 'nullable|string|max:50|unique:users,staff_id,' . $user->id,
            'position'       => 'required|string|max:100',
            'department'     => 'required|string|max:100',
            'account_status' => 'required|in:active,inactive,on_leave,suspended',
            'duty_status'    => 'nullable|in:available,busy,on_leave,off_duty',
            'modules'        => 'nullable|array',
        ]);

        $oldValues = [
            'staff_id'       => $user->staff_id,
            'position'       => $user->position,
            'department'     => $user->department,
            'account_status' => $user->account_status,
            'modules'        => $user->assignedModules(),
        ];

        DB::transaction(function () use ($data, $user, $request, $oldValues) {
            $isActive = in_array($data['account_status'], ['active', 'on_leave']);

            $updateData = [
                'staff_id'       => $data['staff_id'] ?: $user->staff_id,
                'position'       => $data['position'],
                'department'     => $data['department'],
                'account_status' => $data['account_status'],
                'is_active'      => $isActive,
            ];

            if (!empty($data['duty_status'])) {
                $updateData['duty_status'] = $data['duty_status'];
            }

            $user->update($updateData);

            // Re-sync permissions
            $user->permissions()->delete();

            $modulesInput = $request->input('modules', []);
            foreach ($modulesInput as $moduleKey => $moduleData) {
                if (!empty($moduleData['enabled']) || is_array($moduleData)) {
                    $actions = $moduleData['actions'] ?? (is_array($moduleData) && !isset($moduleData['enabled']) ? $moduleData : []);
                    if (empty($actions) && isset(StaffPermission::MODULES[$moduleKey]['default'])) {
                        $actions = StaffPermission::MODULES[$moduleKey]['default'];
                    }

                    StaffPermission::create([
                        'user_id' => $user->id,
                        'module'  => $moduleKey,
                        'actions' => array_values(array_unique($actions)),
                    ]);
                }
            }

            AuditLog::create([
                'user_id'    => Auth::id(),
                'action'     => 'staff_permissions_updated',
                'model_type' => User::class,
                'model_id'   => $user->id,
                'old_values' => $oldValues,
                'new_values' => [
                    'position'       => $user->position,
                    'department'     => $user->department,
                    'account_status' => $user->account_status,
                    'modules'        => array_keys($modulesInput),
                ],
            ]);
        });

        return back()->with('success', "Updated job assignments and role-based access control for {$user->name}.");
    }

    /**
     * Quick Update Staff Account Status (Active, Inactive, On Leave, Suspended).
     */
    public function updateAccountStatus(Request $request, User $user)
    {
        $data = $request->validate([
            'account_status' => 'required|in:active,inactive,on_leave,suspended',
        ]);

        $oldStatus = $user->account_status;
        $newStatus = $data['account_status'];
        $isActive  = in_array($newStatus, ['active', 'on_leave']);

        $user->update([
            'account_status' => $newStatus,
            'is_active'      => $isActive,
        ]);

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'staff_account_status_changed',
            'model_type' => User::class,
            'model_id'   => $user->id,
            'old_values' => ['account_status' => $oldStatus],
            'new_values' => ['account_status' => $newStatus, 'is_active' => $isActive],
        ]);

        $label = match ($newStatus) {
            'active'    => 'Activated',
            'inactive'  => 'Deactivated',
            'on_leave'  => 'marked On Leave',
            'suspended' => 'Suspended',
        };

        return back()->with('success', "Staff account for {$user->name} is now {$label}.");
    }

    /**
     * Admin Quick Reset of Staff Password.
     */
    public function resetStaffPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'staff_password_reset_by_admin',
            'model_type' => User::class,
            'model_id'   => $user->id,
            'new_values' => ['staff_email' => $user->email],
        ]);

        return back()->with('success', "Password for {$user->name} ({$user->email}) has been successfully reset.");
    }

    /**
     * Update Staff Duty Status (Available, Busy, On Leave, Off Duty).
     */
    public function updateStaffDuty(Request $request, User $user)
    {
        $data = $request->validate([
            'duty_status' => 'required|in:available,busy,on_leave,off_duty',
            'duty_notes'  => 'nullable|string|max:500',
        ]);

        $oldStatus = $user->duty_status;
        $user->update($data);

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'staff_duty_status_updated',
            'model_type' => User::class,
            'model_id'   => $user->id,
            'old_values' => ['duty_status' => $oldStatus],
            'new_values' => ['duty_status' => $user->duty_status, 'notes' => $data['duty_notes'] ?? null],
        ]);

        return back()->with('success', "Updated duty status for {$user->name} to " . ucfirst(str_replace('_', ' ', $user->duty_status)) . ".");
    }
}
