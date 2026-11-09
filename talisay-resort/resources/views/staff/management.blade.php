@extends('layouts.app')

@section('title', 'Staff Role Management – Talisay Beach Smart Tourism')

@push('styles')
<style>
/* Tabs */
.smtab-panel { display: none; }
.smtab-panel.active { display: block; }

/* Modals */
.sm-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    z-index: 3000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.sm-modal-backdrop.open { display: flex; }
.sm-modal-box {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 12px 32px -12px rgba(15, 23, 42, 0.2);
    width: 100%;
    max-width: 620px;
    max-height: 92vh;
    overflow-y: auto;
    padding: 32px;
    position: relative;
    animation: modalScaleIn 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    border: 1px solid #f1f5f9;
}
.sm-modal-box.wide { max-width: 820px; }
@keyframes modalScaleIn {
    from { opacity: 0; transform: translateY(-16px) scale(0.96); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* Preset buttons */
.preset-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: border-color .15s, background .15s, color .15s;
    border: 1px solid #e6e8ec;
    background: #ffffff;
    color: #334155;
}
.preset-btn:hover {
    border-color: #1d6fbf;
    color: #155a9c;
}
.preset-btn.selected {
    border-color: #1d6fbf;
    background: #e8f2fb;
    color: #155a9c;
}

/* Custom Radio Tile */
.duty-radio-tile {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border-radius: 16px;
    border: 2px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #ffffff;
}
.duty-radio-tile:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}
/* Generic checked state */
.duty-radio-tile:has(:checked) {
    border-color: #0284c7;
    background: #f0f9ff;
}
/* On Duty — green when checked */
.duty-radio-tile:has([value="available"]:checked) {
    border-color: #86efac;
    background: #f0fdf4;
}
/* Busy — amber when checked */
.duty-radio-tile:has([value="busy"]:checked) {
    border-color: #fcd34d;
    background: #fffbeb;
}
/* On Leave — orange when checked */
.duty-radio-tile:has([value="on_leave"]:checked) {
    border-color: #fdba74;
    background: #fff7ed;
}
/* Off Duty — slate when checked */
.duty-radio-tile:has([value="off_duty"]:checked) {
    border-color: #cbd5e1;
    background: #f8fafc;
}
/* Hide the native radio but keep it focusable */
.duty-radio-tile input[type="radio"] {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}
</style>
@endpush

@section('content')

@php
    $totalStaff = $staffMembers->count();
    $dutyCounts = $staffMembers->countBy(fn ($s) => $s->duty_status ?? 'available');
    $awayCount = ($dutyCounts['on_leave'] ?? 0) + ($dutyCounts['off_duty'] ?? 0);
    $accountIssues = $staffMembers->whereIn('account_status', ['inactive', 'suspended'])->count();
    $dutyLabels = ['available' => 'On duty', 'busy' => 'Busy', 'on_leave' => 'On leave', 'off_duty' => 'Off duty'];
    $accountLabels = ['active' => 'Active', 'inactive' => 'Inactive', 'on_leave' => 'On leave', 'suspended' => 'Suspended'];
    $initials = fn ($name) => collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $moduleNames = fn ($staff) => $staff->permissions->map(fn ($p) => $modules[$p->module]['name'] ?? ucfirst($p->module));
    $logBadge = fn ($action) => match (true) {
        str_contains($action, 'created') => 'res-status-paid',
        str_contains($action, 'unauthorized') => 'res-status-cancelled',
        str_contains($action, 'reset') || str_contains($action, 'password') => 'res-status-waiting',
        str_contains($action, 'status') => 'res-status-pending',
        default => 'res-status-check_in',
    };
@endphp

<div class="res-head">
    <div>
        <h1>{{ __('Staff Role Management') }}</h1>
        <p>Add staff accounts, choose what each person can open, and keep duty status current. Guest accounts are in File Maintenance and System Settings.</p>
    </div>
    <div class="res-head-actions">
        <button type="button" onclick="openAddStaffModal()" class="btn-ocean">
            <i class="bi bi-person-plus"></i>
            <span>{{ __('Add staff') }}</span>
        </button>
    </div>
</div>

<div class="unit-stats" data-live-region="staff-stats">
    <div>
        <span>{{ __('Staff') }}</span>
        <strong>{{ $totalStaff }}</strong>
        <em>No limit on accounts</em>
    </div>
    <div>
        <span>{{ __('On duty') }}</span>
        <strong class="{{ ($dutyCounts['available'] ?? 0) ? 'is-good' : '' }}">{{ $dutyCounts['available'] ?? 0 }}</strong>
        <em>Available for tasks</em>
    </div>
    <div>
        <span>{{ __('Busy') }}</span>
        <strong class="{{ ($dutyCounts['busy'] ?? 0) ? 'is-warn' : '' }}">{{ $dutyCounts['busy'] ?? 0 }}</strong>
        <em>Handling a task</em>
    </div>
    <div>
        <span>{{ __('Away') }}</span>
        <strong>{{ $awayCount }}</strong>
        <em>{{ $dutyCounts['on_leave'] ?? 0 }} on leave · {{ $dutyCounts['off_duty'] ?? 0 }} off duty</em>
    </div>
    <div>
        <span>{{ __('Account issues') }}</span>
        <strong class="{{ $accountIssues ? 'is-bad' : '' }}">{{ $accountIssues }}</strong>
        <em>Inactive or suspended</em>
    </div>
</div>

<div class="staff-tabs" role="tablist">
    <button type="button" class="staff-tab smtab-btn" id="btn-tab-roster" onclick="switchTab('roster')">
        <i class="bi bi-people"></i>
        <span>{{ __('Staff Directory') }}</span>
        <span class="unit-filter-count">{{ $totalStaff }}</span>
    </button>
    <button type="button" class="staff-tab smtab-btn" id="btn-tab-rbac" onclick="switchTab('rbac')">
        <i class="bi bi-shield-lock"></i>
        <span>{{ __('Job & Role Assignments') }}</span>
    </button>
    <button type="button" class="staff-tab smtab-btn" id="btn-tab-activity" onclick="gotoActivity()">
        <i class="bi bi-clock-history"></i>
        <span>{{ __('Activity Log') }}</span>
    </button>
</div>

{{-- Staff directory --}}
<div class="smtab-panel" id="panel-roster">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="res-toolbar">
            <div class="res-search unit-search">
                <i class="bi bi-search"></i>
                <input type="search" id="quickStaffSearch" class="form-control-clean" placeholder="Find staff by name, ID, or position..." aria-label="Find staff" data-live-pause-if-filled>
            </div>
            <span class="unit-count" id="staffCount" data-total="{{ $totalStaff }}" data-label="{{ $totalStaff }} {{ $totalStaff === 1 ? 'staff member' : 'staff members' }}" data-live-region="staff-count">{{ $totalStaff }} {{ $totalStaff === 1 ? 'staff member' : 'staff members' }}</span>
        </div>
        <div class="table-responsive" data-live-region="staff-list">
            <table class="table-clean staff-table">
                <thead>
                    <tr>
                        <th>Staff</th>
                        <th>Position</th>
                        <th>Duty</th>
                        <th>Account</th>
                        <th>Access</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staffMembers as $staff)
                    @php
                        $ds = $staff->duty_status ?? 'available';
                        $acct = $staff->account_status ?? 'active';
                        $names = $moduleNames($staff);
                    @endphp
                    <tr class="staff-row" data-search="{{ strtolower($staff->name.' '.$staff->email.' '.$staff->staff_id.' '.$staff->position.' '.$staff->department) }}">
                        <td>
                            <div class="staff-person">
                                <span class="pay-avatar staff-avatar">
                                    @if($staff->avatar)
                                        <img src="{{ '/storage/' . ltrim($staff->avatar, '/') }}" alt="">
                                    @else
                                        {{ $initials($staff->name) }}
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <span class="res-guest">{{ $staff->name }}</span>
                                    <div class="res-note">{{ $staff->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            {{ $staff->position ?? 'Resort staff' }}
                            <div class="res-note">{{ $staff->department ?? 'Operations' }}@if($staff->staff_id) · <span class="staff-id">{{ $staff->staff_id }}</span>@endif</div>
                        </td>
                        <td>
                            <span class="staff-duty is-{{ $ds }}">{{ $dutyLabels[$ds] ?? ucfirst(str_replace('_', ' ', $ds)) }}</span>
                            @if($staff->duty_notes)
                            <div class="res-note staff-note" title="{{ $staff->duty_notes }}">{{ \Illuminate\Support\Str::limit($staff->duty_notes, 32) }}</div>
                            @endif
                        </td>
                        <td><span class="res-status staff-account-{{ $acct }}">{{ $accountLabels[$acct] ?? ucfirst($acct) }}</span></td>
                        <td>
                            @if($names->isEmpty())
                                <span class="res-note m-0">No access yet</span>
                            @else
                                {{ $names->count() }} {{ $names->count() === 1 ? 'module' : 'modules' }}
                                <div class="res-note staff-note" title="{{ $names->implode(', ') }}">{{ $names->take(2)->implode(', ') }}{{ $names->count() > 2 ? ' +'.($names->count() - 2) : '' }}</div>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="unit-actions">
                                <button type="button" class="res-icon-btn" title="Change duty status" aria-label="Change duty status for {{ $staff->name }}"
                                        onclick="openDutyModal({{ $staff->id }}, {{ Js::from($staff->name) }}, {{ Js::from($ds) }}, {{ Js::from($staff->duty_notes ?? '') }})">
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>
                                <button type="button" class="res-icon-btn" title="Edit access" aria-label="Edit access for {{ $staff->name }}"
                                        onclick="openPermissionsModal({{ $staff->id }}, {{ Js::from($staff->name) }}, {{ Js::from($staff->permissions) }}, {{ Js::from($staff->staff_id ?? '') }}, {{ Js::from($staff->position ?? '') }}, {{ Js::from($staff->department ?? '') }}, {{ Js::from($acct) }})">
                                    <i class="bi bi-shield-lock"></i>
                                </button>
                                <div class="dropdown">
                                    <button type="button" class="res-icon-btn" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="More actions for {{ $staff->name }}">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end manual-kind-menu staff-menu">
                                        <li><button type="button" class="dropdown-item" onclick="quickStatus({{ $staff->id }}, 'active')"><i class="bi bi-check-circle"></i> Activate account</button></li>
                                        <li><button type="button" class="dropdown-item" onclick="quickStatus({{ $staff->id }}, 'on_leave')"><i class="bi bi-calendar-x"></i> Set on leave</button></li>
                                        <li><button type="button" class="dropdown-item" onclick="quickStatus({{ $staff->id }}, 'inactive')"><i class="bi bi-slash-circle"></i> Deactivate</button></li>
                                        <li><button type="button" class="dropdown-item" onclick="openResetPasswordModal({{ $staff->id }}, {{ Js::from($staff->name) }})"><i class="bi bi-key"></i> Reset password</button></li>
                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li><button type="button" class="dropdown-item is-danger" onclick="quickStatus({{ $staff->id }}, 'suspended')"><i class="bi bi-ban"></i> Suspend account</button></li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-12">
                            <p class="text-sm text-slate-500 mb-3">No staff accounts yet. Add as many as you need.</p>
                            <button type="button" onclick="openAddStaffModal()" class="btn-ocean">
                                <i class="bi bi-person-plus"></i>
                                <span>{{ __('Add staff') }}</span>
                            </button>
                        </td>
                    </tr>
                    @endforelse
                    @if($staffMembers->isNotEmpty())
                    <tr id="staffNoMatch" hidden>
                        <td colspan="6" class="text-center py-10 text-slate-400">
                            <p class="text-sm mb-0">No staff match that search.</p>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Job & role assignments --}}
<div class="smtab-panel" id="panel-rbac">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-4">
        <form method="GET" action="{{ route('tasks.staff') }}" class="res-toolbar">
            <input type="hidden" name="tab" value="rbac">
            <div class="res-search">
                <i class="bi bi-search"></i>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control-clean" placeholder="Name, email, or staff ID" aria-label="Search staff">
            </div>
            <div class="res-search">
                <i class="bi bi-building"></i>
                <input type="search" name="department" value="{{ request('department') }}" class="form-control-clean" placeholder="Department" aria-label="Department">
            </div>
            <button type="submit" class="btn-secondary-clean staff-filter-btn">Filter</button>
            @if(request()->hasAny(['search', 'department']))
            <a href="{{ route('tasks.staff', ['tab' => 'rbac']) }}" class="res-clear">Clear</a>
            @endif
            <span class="unit-count ms-auto">Admins always have full access</span>
        </form>
        <div class="table-responsive">
            <table class="table-clean staff-table">
                <thead>
                    <tr>
                        <th>Staff</th>
                        <th>Staff ID</th>
                        <th>Position</th>
                        <th>Can open</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staffMembers as $staff)
                    @php $names = $moduleNames($staff); @endphp
                    <tr>
                        <td>
                            <span class="res-guest">{{ $staff->name }}</span>
                            <div class="res-note">{{ $staff->email }}</div>
                        </td>
                        <td><span class="staff-id">{{ $staff->staff_id ?? '—' }}</span></td>
                        <td>
                            {{ $staff->position ?? '—' }}
                            <div class="res-note">{{ $staff->department ?? '—' }}</div>
                        </td>
                        <td class="staff-modules">
                            @if($names->isEmpty())
                                <span class="res-note m-0">No access yet</span>
                            @else
                                {{ $names->implode(', ') }}
                            @endif
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn-secondary-clean staff-edit-btn"
                                    onclick="openPermissionsModal({{ $staff->id }}, {{ Js::from($staff->name) }}, {{ Js::from($staff->permissions) }}, {{ Js::from($staff->staff_id ?? '') }}, {{ Js::from($staff->position ?? '') }}, {{ Js::from($staff->department ?? '') }}, {{ Js::from($staff->account_status ?? 'active') }})">
                                <i class="bi bi-shield-lock"></i>
                                <span>Edit access</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-12 text-slate-400">
                            <p class="text-sm mb-0">{{ request()->hasAny(['search', 'department']) ? 'No staff match these filters.' : 'No staff accounts yet.' }}</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="staff-card-head">
            <h2>Role presets</h2>
            <p>Pick one of these in the Add staff or Edit access window to fill in the usual access for that job.</p>
        </div>
        <div class="staff-presets">
            @foreach($presets as $key => $preset)
            <div class="staff-preset">
                <strong>{{ $preset['name'] ?? $preset['label'] ?? ucfirst(str_replace('_', ' ', $key)) }}</strong>
                <span>{{ $preset['position'] ?? '' }}{{ !empty($preset['department']) ? ' · '.$preset['department'] : '' }} · {{ count($preset['modules']) }} modules</span>
                <p>{{ collect(array_keys($preset['modules']))->map(fn ($m) => $modules[$m]['name'] ?? ucfirst($m))->implode(', ') }}</p>
                @if(isset($takenPresets[$key]))
                    <em class="staff-preset-status is-taken">Filled by {{ $takenPresets[$key]['name'] }}</em>
                @else
                    <em class="staff-preset-status is-open">Open</em>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Activity log --}}
<div class="smtab-panel" id="panel-activity">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="staff-card-head">
            <h2>Staff Activity Log</h2>
            <p>Every account, access, duty, and password change, plus blocked attempts to open a page without access.</p>
        </div>
        <form method="GET" action="{{ route('tasks.staff') }}" class="res-toolbar">
            <input type="hidden" name="tab" value="activity">
            <div class="res-search">
                <i class="bi bi-search"></i>
                <input type="search" name="activity_search" value="{{ request('activity_search') }}" class="form-control-clean" placeholder="Staff name or action" aria-label="Search activity">
            </div>
            <select name="staff_user_id" class="form-select-clean res-select" aria-label="Staff member">
                <option value="">All staff</option>
                @foreach($staffMembers as $s)
                <option value="{{ $s->id }}" {{ request('staff_user_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
            <select name="action_filter" class="form-select-clean res-select" aria-label="Action">
                <option value="">All actions</option>
                <option value="staff_account_created" {{ request('action_filter') === 'staff_account_created' ? 'selected' : '' }}>Account created</option>
                <option value="staff_permissions_updated" {{ request('action_filter') === 'staff_permissions_updated' ? 'selected' : '' }}>Access updated</option>
                <option value="staff_account_status_changed" {{ request('action_filter') === 'staff_account_status_changed' ? 'selected' : '' }}>Account status</option>
                <option value="staff_duty_status_updated" {{ request('action_filter') === 'staff_duty_status_updated' ? 'selected' : '' }}>Duty status</option>
                <option value="staff_password_reset_by_admin" {{ request('action_filter') === 'staff_password_reset_by_admin' ? 'selected' : '' }}>Password reset</option>
                <option value="unauthorized_access_attempt" {{ request('action_filter') === 'unauthorized_access_attempt' ? 'selected' : '' }}>Blocked access</option>
            </select>
            <label class="res-dates">From <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control-clean"></label>
            <label class="res-dates">To <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control-clean"></label>
            <button type="submit" class="btn-secondary-clean staff-filter-btn">Filter</button>
            @if(request()->hasAny(['activity_search', 'staff_user_id', 'action_filter', 'date_from', 'date_to']))
            <a href="{{ route('tasks.staff', ['tab' => 'activity']) }}" class="res-clear">Clear</a>
            @endif
        </form>
        <div class="table-responsive">
            <table class="table-clean staff-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>By</th>
                        <th>Action</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activityLogs ?? [] as $log)
                    <tr>
                        <td>
                            {{ $log->created_at->format('M j, Y') }}
                            <div class="res-note">{{ $log->created_at->format('g:i A') }}</div>
                        </td>
                        <td>
                            @if($log->user)
                                <span class="res-guest">{{ $log->user->name }}</span>
                                <div class="res-note">{{ ucfirst($log->user->role ?? 'staff') }}</div>
                            @else
                                <span class="res-note m-0">System</span>
                            @endif
                        </td>
                        <td><span class="res-status {{ $logBadge($log->action) }}">{{ ucfirst(strtolower(str_replace(['staff_', '_'], ['', ' '], $log->action))) }}</span></td>
                        <td class="staff-log-details">
                            @if($log->new_values)
                                @foreach(array_slice((array) $log->new_values, 0, 4) as $k => $v)
                                    @if(!is_array($v))
                                    <div><b>{{ ucfirst(str_replace('_', ' ', $k)) }}:</b> {{ \Illuminate\Support\Str::limit((string) $v, 65) }}</div>
                                    @elseif(count($v))
                                    <div><b>{{ ucfirst(str_replace('_', ' ', $k)) }}:</b> {{ implode(', ', array_slice($v, 0, 4)) }}{{ count($v) > 4 ? '…' : '' }}</div>
                                    @endif
                                @endforeach
                            @else
                                <span class="res-note m-0">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-12 text-slate-400">
                            <p class="text-sm mb-0">No activity yet. Staff changes are recorded here automatically.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($activityLogs && $activityLogs->hasPages())
        <div class="p-4 border-t border-slate-100 flex justify-end">
            {{ $activityLogs->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MODAL 1: DUTY STATUS UPDATE
══════════════════════════════════════════════════════ --}}
<div class="sm-modal-backdrop" id="duty-modal">
    <div class="sm-modal-box">
        <button type="button" onclick="closeDutyModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 text-xl transition">
            <i class="bi bi-x-lg"></i>
        </button>

        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center text-lg">
                <i class="bi bi-arrow-repeat"></i>
            </div>
            <div>
                <h3 class="text-lg font-extrabold text-slate-900 mb-0">Update Duty Status</h3>
                <p class="text-xs text-slate-500 mb-0">Configure availability for <strong id="duty-staff-name" class="text-slate-800">—</strong></p>
            </div>
        </div>

        <form id="duty-form" method="POST" class="mt-5">
            @csrf @method('PATCH')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                <label class="duty-radio-tile">
                    <input type="radio" name="duty_status" value="available" class="accent-sky-600">
                    <div>
                        <div class="flex items-center gap-1.5 font-bold text-slate-900 text-xs mb-0.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> On Duty &amp; Available
                        </div>
                        <p class="text-[11px] text-slate-400 mb-0">Ready for guest assignments</p>
                    </div>
                </label>

                <label class="duty-radio-tile">
                    <input type="radio" name="duty_status" value="busy" class="accent-sky-600">
                    <div>
                        <div class="flex items-center gap-1.5 font-bold text-slate-900 text-xs mb-0.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span> Busy / In Task
                        </div>
                        <p class="text-[11px] text-slate-400 mb-0">Assigned to an active duty</p>
                    </div>
                </label>

                <label class="duty-radio-tile">
                    <input type="radio" name="duty_status" value="on_leave" class="accent-sky-600">
                    <div>
                        <div class="flex items-center gap-1.5 font-bold text-slate-900 text-xs mb-0.5">
                            <span class="w-2 h-2 rounded-full bg-orange-500"></span> On Leave
                        </div>
                        <p class="text-[11px] text-slate-400 mb-0">Authorized vacation or sick leave</p>
                    </div>
                </label>

                <label class="duty-radio-tile">
                    <input type="radio" name="duty_status" value="off_duty" class="accent-sky-600">
                    <div>
                        <div class="flex items-center gap-1.5 font-bold text-slate-900 text-xs mb-0.5">
                            <span class="w-2 h-2 rounded-full bg-slate-400"></span> Off Duty
                        </div>
                        <p class="text-[11px] text-slate-400 mb-0">Shift concluded</p>
                    </div>
                </label>
            </div>

            <div class="mb-5">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Duty Notes &amp; Location <span class="text-slate-400 font-normal">(optional)</span></label>
                <textarea
                    name="duty_notes"
                    id="duty-notes-input"
                    rows="2"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-sky-300 focus:border-sky-500 outline-none resize-none shadow-sm"
                    placeholder="e.g. Front desk coverage until 6 PM, roving cottage area..."
                ></textarea>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closeDutyModal()" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button type="submit" class="flex-1 py-2.5 rounded-lg bg-ocean-800 hover:bg-ocean-900 text-white font-semibold text-xs transition">
                    Update Status
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MODAL 2: ADD NEW STAFF
══════════════════════════════════════════════════════ --}}
@php
    $addOld = old('_form') === 'add_staff';
    $addOldModules = $addOld ? (array) old('modules', []) : [];
@endphp
<div class="sm-modal-backdrop" id="add-staff-modal" @if($addOld && $errors->any()) data-reopen="1" @endif>
    <div class="sm-modal-box staff-modal" role="dialog" aria-modal="true" aria-labelledby="addStaffTitle">
        <div class="manual-head">
            <div>
                <h5 id="addStaffTitle">Add staff</h5>
                <p>Create a login for a team member and choose which pages they can open.</p>
            </div>
            <button type="button" class="manual-close" onclick="closeAddStaffModal()" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('tasks.staff.store') }}" data-loading>
            @csrf
            <input type="hidden" name="_form" value="add_staff">
            <div class="manual-body">
                @if($addOld && $errors->any())
                    <div class="staff-form-error" role="alert">
                        <i class="bi bi-exclamation-circle"></i>
                        <div>
                            <strong>The account was not created.</strong>
                            @foreach($errors->all() as $message)
                                <span>{{ $message }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @php
                    $addPreset = $addOld && isset($presets[old('preset')]) && ! isset($takenPresets[old('preset')]) ? old('preset') : '';
                    $openJobs = count($presets) - count($takenPresets);
                @endphp
                <div class="manual-row manual-row-top">
                    <label for="add-preset-toggle">Job <span class="req-star">*</span></label>
                    <div>
                        <input type="hidden" name="preset" id="add-preset" value="{{ $addPreset }}">
                        <div class="dropdown" id="add-preset-btns">
                            <button type="button" class="manual-kind-btn staff-preset-toggle {{ $addPreset ? 'is-active' : '' }}" id="add-preset-toggle"
                                    data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                <span id="add-preset-label">{{ $addPreset ? $presets[$addPreset]['name'] : 'Choose a job' }}</span>
                            </button>
                            <ul class="dropdown-menu manual-kind-menu staff-preset-menu">
                                @foreach($presets as $key => $preset)
                                    @php $holder = $takenPresets[$key] ?? null; @endphp
                                    <li>
                                        <button type="button" class="dropdown-item add-preset-btn {{ $addPreset === $key ? 'active' : '' }}" data-preset="{{ $key }}" @disabled($holder)>
                                            <span class="staff-preset-name">{{ $preset['name'] }}</span>
                                            <small>{{ $holder ? 'Taken by '.$holder['name'] : $preset['position'].' · '.$preset['department'] }}</small>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <span class="manual-hint" id="add-preset-hint">
                            @if($openJobs === 0)
                                Every job already has a staff member.
                            @else
                                Sets their position, department, and access. Jobs that already have someone can't be picked.
                            @endif
                        </span>
                    </div>
                </div>

                <div class="manual-row">
                    <label for="add-first-name">Name <span class="req-star">*</span></label>
                    <div class="manual-split manual-split-3">
                        <input type="text" name="first_name" id="add-first-name" class="form-control-clean" value="{{ $addOld ? old('first_name') : '' }}" maxlength="80" placeholder="First name" aria-label="First name" autocomplete="off" required>
                        <input type="text" name="middle_name" id="add-middle-name" class="form-control-clean" value="{{ $addOld ? old('middle_name') : '' }}" maxlength="80" placeholder="Middle name (optional)" aria-label="Middle name" autocomplete="off">
                        <input type="text" name="last_name" id="add-last-name" class="form-control-clean" value="{{ $addOld ? old('last_name') : '' }}" maxlength="80" placeholder="Last name" aria-label="Last name" autocomplete="off" required>
                    </div>
                </div>
                <div class="manual-row">
                    <label for="add-email">Email <span class="req-star">*</span></label>
                    <div>
                        <input type="email" name="email" id="add-email" class="form-control-clean w-100" value="{{ $addOld ? old('email') : '' }}" maxlength="255" placeholder="staff@talisayresort.com" required>
                        <span class="manual-hint">They log in with this email.</span>
                    </div>
                </div>
                <div class="manual-row">
                    <label for="add-phone">Phone</label>
                    <input type="tel" name="phone" id="add-phone" class="form-control-clean" value="{{ $addOld ? old('phone') : '' }}" maxlength="20" placeholder="+63 9xx xxx xxxx">
                </div>
                <div class="manual-row">
                    <label for="add-staff-id">Staff ID</label>
                    <div>
                        <input type="text" id="add-staff-id" class="form-control-clean w-100 staff-locked" value="{{ \App\Models\User::nextStaffId() }}" readonly tabindex="-1">
                        <span class="manual-hint">Assigned automatically when the account is created.</span>
                    </div>
                </div>
                <div class="manual-row manual-row-top">
                    <label for="add-password">Password</label>
                    <div>
                        <div class="staff-password">
                            <input type="text" name="password" id="add-password" class="form-control-clean staff-locked" minlength="8" autocomplete="off" spellcheck="false" readonly>
                            <button type="button" class="btn-secondary-clean" id="add-password-new"><i class="bi bi-arrow-repeat"></i> New</button>
                            <button type="button" class="btn-secondary-clean" id="add-password-copy"><i class="bi bi-clipboard"></i> <span>Copy</span></button>
                        </div>
                        <span class="manual-hint">Made for you automatically. Copy it and give it to them privately; they can change it after logging in.</span>
                    </div>
                </div>

                <div class="staff-access">
                    <div class="staff-access-head">
                        <div>
                            <strong>Access</strong>
                            <span>Tick each page they can open, then choose what they can do there.</span>
                        </div>
                        <em id="add-access-count">0 of {{ count($modules) }} pages</em>
                    </div>
                    <div class="staff-module-grid" id="add-modules-grid">
                        @foreach($modules as $key => $mod)
                            @php
                                $moduleOn = $addOld && ! empty($addOldModules[$key]['enabled']);
                                $checkedActions = $addOld ? (array) ($addOldModules[$key]['actions'] ?? []) : $mod['default'];
                            @endphp
                            <div class="staff-module {{ $moduleOn ? 'is-on' : '' }}" id="add-card-{{ $key }}">
                                <label class="staff-module-head">
                                    <input type="checkbox" name="modules[{{ $key }}][enabled]" value="1" class="add-mod-cb" data-module="{{ $key }}" @checked($moduleOn)>
                                    <span>
                                        <strong><i class="{{ $mod['icon'] }}"></i> {{ $mod['name'] }}</strong>
                                        <small>{{ $mod['description'] }}</small>
                                    </span>
                                </label>
                                <div class="staff-module-actions add-actions-row" @if(! $moduleOn) hidden @endif>
                                    @foreach($mod['actions'] as $action)
                                        <label>
                                            <input type="checkbox" name="modules[{{ $key }}][actions][]" value="{{ $action }}" @checked(in_array($action, $checkedActions, true)) @disabled(! $moduleOn)>
                                            <span>{{ ucwords(str_replace('_', ' ', $action)) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="manual-foot">
                <button type="button" class="btn-secondary-clean" onclick="closeAddStaffModal()">Cancel</button>
                <button type="submit" class="btn-ocean" @disabled($openJobs === 0)><i class="bi bi-person-plus"></i> Create staff account</button>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MODAL 3: EDIT PERMISSIONS (RBAC)
══════════════════════════════════════════════════════ --}}
<div class="sm-modal-backdrop" id="perm-modal">
    <div class="sm-modal-box staff-modal" role="dialog" aria-modal="true" aria-labelledby="permStaffTitle">
        <div class="manual-head">
            <div>
                <h5 id="permStaffTitle">Edit access</h5>
                <p>Update the job and pages for <strong id="perm-staff-name">—</strong>.</p>
            </div>
            <button type="button" class="manual-close" onclick="closePermissionsModal()" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="perm-form" method="POST" data-loading>
            @csrf @method('PUT')
            <input type="hidden" name="position" id="perm-position" value="">
            <input type="hidden" name="department" id="perm-department" value="">
            <div class="manual-body">
                <div class="manual-row manual-row-top">
                    <label for="perm-preset-toggle">Job <span class="req-star">*</span></label>
                    <div>
                        <div class="dropdown" id="perm-preset-btns">
                            <button type="button" class="manual-kind-btn staff-preset-toggle" id="perm-preset-toggle"
                                    data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                <span id="perm-preset-label">Choose a job</span>
                            </button>
                            <ul class="dropdown-menu manual-kind-menu staff-preset-menu">
                                @foreach($presets as $key => $preset)
                                    @php $holder = $takenPresets[$key] ?? null; @endphp
                                    <li>
                                        <button type="button" class="dropdown-item perm-preset-btn" data-preset="{{ $key }}"
                                                data-holder-id="{{ $holder['id'] ?? '' }}" data-holder-name="{{ $holder['name'] ?? '' }}"
                                                @disabled($holder)>
                                            <span class="staff-preset-name">{{ $preset['name'] }}</span>
                                            <small class="perm-job-note">{{ $holder ? 'Taken by '.$holder['name'] : $preset['position'].' · '.$preset['department'] }}</small>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <span class="manual-hint">Sets their position, department, and access. Jobs taken by someone else can't be picked.</span>
                    </div>
                </div>

                <div class="manual-row">
                    <label for="perm-staff-id">Staff ID</label>
                    <div>
                        <input type="text" name="staff_id" id="perm-staff-id" class="form-control-clean w-100 staff-locked" readonly tabindex="-1">
                        <span class="manual-hint">Assigned when the account was created.</span>
                    </div>
                </div>

                <div class="manual-row">
                    <label for="perm-account-status">Account status</label>
                    <select name="account_status" id="perm-account-status" class="form-select-clean">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="on_leave">On leave</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>

                <div class="staff-access">
                    <div class="staff-access-head">
                        <div>
                            <strong>Access</strong>
                            <span>Tick each page they can open, then choose what they can do there.</span>
                        </div>
                        <em id="perm-access-count">0 of {{ count($modules) }} pages</em>
                    </div>
                    <div class="staff-module-grid" id="perm-modules-grid">
                        @foreach($modules as $key => $mod)
                            <div class="staff-module" id="perm-card-{{ $key }}">
                                <label class="staff-module-head">
                                    <input type="checkbox" name="modules[{{ $key }}][enabled]" value="1" class="perm-mod-cb" data-module="{{ $key }}">
                                    <span>
                                        <strong><i class="{{ $mod['icon'] }}"></i> {{ $mod['name'] }}</strong>
                                        <small>{{ $mod['description'] }}</small>
                                    </span>
                                </label>
                                <div class="staff-module-actions perm-actions-row" hidden>
                                    @foreach($mod['actions'] as $action)
                                        <label>
                                            <input type="checkbox" name="modules[{{ $key }}][actions][]" value="{{ $action }}" class="perm-act-cb" data-module="{{ $key }}" data-action="{{ $action }}" disabled>
                                            <span>{{ ucwords(str_replace('_', ' ', $action)) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="manual-foot">
                <button type="button" class="btn-secondary-clean" onclick="closePermissionsModal()">Cancel</button>
                <button type="submit" class="btn-ocean"><i class="bi bi-shield-check"></i> Save access</button>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MODAL 4: RESET PASSWORD
══════════════════════════════════════════════════════ --}}
<div class="sm-modal-backdrop" id="reset-pwd-modal">
    <div class="sm-modal-box" style="max-width:440px">
        <button type="button" onclick="closeResetPasswordModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 text-xl transition">
            <i class="bi bi-x-lg"></i>
        </button>

        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center text-lg">
                <i class="bi bi-key"></i>
            </div>
            <div>
                <h3 class="text-lg font-extrabold text-slate-900 mb-0">Reset Staff Password</h3>
                <p class="text-xs text-slate-500 mb-0">Set a new password for <strong id="reset-pwd-name" class="text-slate-800">—</strong></p>
            </div>
        </div>

        <form id="reset-pwd-form" method="POST" class="mt-5">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">New Password <span class="req-star">*</span></label>
                <input type="password" name="password" required minlength="8" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-sky-300 focus:border-sky-500 outline-none shadow-sm" placeholder="Min. 8 characters">
            </div>
            <div class="mb-5">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Confirm New Password <span class="req-star">*</span></label>
                <input type="password" name="password_confirmation" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-sky-300 focus:border-sky-500 outline-none shadow-sm" placeholder="Repeat password">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeResetPasswordModal()" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs transition shadow-md shadow-red-600/20 flex items-center justify-center gap-1">
                    <i class="bi bi-key"></i> Reset Password
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Hidden Quick Status Forms --}}
<div class="hidden">
    @foreach($staffMembers as $staff)
    <form id="qsf-{{ $staff->id }}" method="POST" action="{{ route('tasks.staff.account-status', $staff->id) }}">
        @csrf @method('PATCH')
        <input type="hidden" name="account_status" id="qsf-status-{{ $staff->id }}">
    </form>
    @endforeach
</div>

@endsection

@push('scripts')
<script>
const MODULE_KEYS = @json(array_keys($modules));
const PRESETS_DATA = @json($presets);
const TAKEN_PRESETS = @json($takenPresets);
let permEditingUserId = null;

// Tab switching
function switchTab(tab) {
    ['roster', 'rbac', 'activity'].forEach(t => {
        const panel = document.getElementById('panel-' + t);
        const btn = document.getElementById('btn-tab-' + t);
        if (panel) panel.classList.toggle('active', t === tab);
        if (btn) {
            btn.classList.toggle('active', t === tab);
            btn.setAttribute('aria-selected', t === tab ? 'true' : 'false');
        }
    });
}

function gotoActivity() {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', 'activity');
    window.location.href = url.toString();
}

// Initialize Active Tab
(function(){
    const t = new URLSearchParams(window.location.search).get('tab') || 'roster';
    switchTab(t);
})();

// Staff directory search
document.getElementById('quickStaffSearch')?.addEventListener('input', function () {
    const term = this.value.toLowerCase().trim();
    const count = document.getElementById('staffCount');
    const noMatch = document.getElementById('staffNoMatch');
    let shown = 0;
    document.querySelectorAll('.staff-row').forEach(row => {
        const match = !term || row.dataset.search.includes(term);
        row.hidden = !match;
        if (match) shown++;
    });
    if (noMatch) noMatch.hidden = !term || shown > 0;
    if (count) count.textContent = term ? `${shown} of ${count.dataset.total} shown` : count.dataset.label;
});

// Duty Modal
function openDutyModal(id, name, status, notes) {
    document.getElementById('duty-staff-name').textContent = name;
    document.getElementById('duty-form').action = '/staff-management/' + id + '/duty-status';
    document.getElementById('duty-notes-input').value = notes || '';
    document.querySelectorAll('#duty-form input[name="duty_status"]').forEach(r => {
        r.checked = (r.value === status);
    });
    document.getElementById('duty-modal').classList.add('open');
}
function closeDutyModal() {
    document.getElementById('duty-modal').classList.remove('open');
}

// Add Staff Modal
function openAddStaffModal() {
    const password = document.getElementById('add-password');
    if (password && !password.value) password.value = generateStaffPassword();
    document.getElementById('add-staff-modal').classList.add('open');
    setTimeout(() => document.getElementById('add-first-name')?.focus(), 50);
}

function generateStaffPassword() {
    const sets = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnpqrstuvwxyz', '23456789'];
    const all = sets.join('');
    const pick = chars => chars[crypto.getRandomValues(new Uint32Array(1))[0] % chars.length];
    const chars = sets.map(pick);
    while (chars.length < 12) chars.push(pick(all));
    for (let i = chars.length - 1; i > 0; i--) {
        const j = crypto.getRandomValues(new Uint32Array(1))[0] % (i + 1);
        [chars[i], chars[j]] = [chars[j], chars[i]];
    }
    return chars.join('');
}
document.getElementById('add-password-new')?.addEventListener('click', () => {
    document.getElementById('add-password').value = generateStaffPassword();
});
document.getElementById('add-password-copy')?.addEventListener('click', function () {
    const input = document.getElementById('add-password');
    const label = this.querySelector('span');
    const done = () => { label.textContent = 'Copied'; setTimeout(() => { label.textContent = 'Copy'; }, 1500); };
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(input.value).then(done);
    } else {
        input.select();
        document.execCommand('copy');
        input.setSelectionRange(0, 0);
        done();
    }
});
function closeAddStaffModal() {
    document.getElementById('add-staff-modal').classList.remove('open');
}

// Module toggle for add form
function updateAddAccessCount() {
    const count = document.getElementById('add-access-count');
    if (!count) return;
    const on = document.querySelectorAll('#add-staff-modal .add-mod-cb:checked').length;
    count.textContent = on + ' of ' + MODULE_KEYS.length + ' pages';
}
function toggleAddModule(moduleKey, enable) {
    const card = document.getElementById('add-card-' + moduleKey);
    if (!card) return;
    card.classList.toggle('is-on', enable);
    const row = card.querySelector('.add-actions-row');
    if (row) {
        row.hidden = !enable;
        row.querySelectorAll('input').forEach(input => { input.disabled = !enable; });
    }
    updateAddAccessCount();
}
document.querySelectorAll('.add-mod-cb').forEach(cb => {
    cb.addEventListener('change', function() {
        toggleAddModule(this.dataset.module, this.checked);
    });
});
updateAddAccessCount();
if (document.getElementById('add-staff-modal')?.hasAttribute('data-reopen')) openAddStaffModal();

// Add preset buttons
document.querySelectorAll('.add-preset-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const pKey = this.dataset.preset;
        const preset = PRESETS_DATA[pKey];
        if (!preset || this.disabled) return;
        document.getElementById('add-preset').value = pKey;
        const presetMods = preset.modules || {};
        MODULE_KEYS.forEach(m => {
            const cb = document.querySelector('#add-staff-modal .add-mod-cb[data-module="' + m + '"]');
            if (!cb) return;
            const isEnabled = Boolean(presetMods[m]);
            cb.checked = isEnabled;
            toggleAddModule(m, isEnabled);
            if (isEnabled && Array.isArray(presetMods[m])) {
                document.querySelectorAll('#add-card-' + m + ' input[name="modules[' + m + '][actions][]"]').forEach(acb => {
                    acb.checked = presetMods[m].includes(acb.value);
                });
            }
        });
        document.querySelectorAll('#add-preset-btns .dropdown-item').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('add-preset-label').textContent = this.querySelector('.staff-preset-name').textContent.trim();
        const toggle = document.getElementById('add-preset-toggle');
        toggle.classList.add('is-active');
        toggle.classList.remove('is-invalid');
    });
});

document.querySelector('#add-staff-modal form')?.addEventListener('submit', function (e) {
    if (document.getElementById('add-preset').value) return;
    e.preventDefault();
    const toggle = document.getElementById('add-preset-toggle');
    toggle.classList.add('is-invalid');
    toggle.scrollIntoView({ block: 'center', behavior: 'smooth' });
    bootstrap.Dropdown.getOrCreateInstance(toggle).show();
});

// Permissions modal
function updatePermAccessCount() {
    const count = document.getElementById('perm-access-count');
    if (!count) return;
    const on = document.querySelectorAll('#perm-modal .perm-mod-cb:checked').length;
    count.textContent = on + ' of ' + MODULE_KEYS.length + ' pages';
}

function togglePermModule(moduleKey, enable) {
    const card = document.getElementById('perm-card-' + moduleKey);
    if (!card) return;
    card.classList.toggle('is-on', enable);
    const row = card.querySelector('.perm-actions-row');
    if (row) {
        row.hidden = !enable;
        row.querySelectorAll('input').forEach(input => { input.disabled = !enable; });
    }
    updatePermAccessCount();
}

function findPresetKeyByPosition(position) {
    const needle = String(position || '').trim().toLowerCase();
    if (!needle) return '';
    return Object.keys(PRESETS_DATA).find(key => String(PRESETS_DATA[key].position || '').trim().toLowerCase() === needle) || '';
}

function refreshPermJobAvailability(currentUserId) {
    document.querySelectorAll('#perm-modal .perm-preset-btn').forEach(btn => {
        const key = btn.dataset.preset;
        const holder = TAKEN_PRESETS[key] || null;
        const holderId = holder ? Number(holder.id) : 0;
        const isMine = holderId && Number(currentUserId) === holderId;
        const takenByOther = holder && !isMine;
        btn.disabled = Boolean(takenByOther);
        const note = btn.querySelector('.perm-job-note');
        if (!note) return;
        if (isMine) note.textContent = 'Current job';
        else if (holder) note.textContent = 'Taken by ' + holder.name;
        else note.textContent = (PRESETS_DATA[key].position || '') + ' · ' + (PRESETS_DATA[key].department || '');
    });
}

function selectPermJob(presetKey, fillModules) {
    const preset = PRESETS_DATA[presetKey];
    if (!preset) return;
    document.getElementById('perm-position').value = preset.position || '';
    document.getElementById('perm-department').value = preset.department || '';
    document.getElementById('perm-preset-label').textContent = preset.name || presetKey;
    document.getElementById('perm-preset-toggle').classList.add('is-active');
    document.querySelectorAll('#perm-modal .perm-preset-btn').forEach(b => b.classList.toggle('active', b.dataset.preset === presetKey));

    if (!fillModules) return;
    const presetMods = preset.modules || {};
    MODULE_KEYS.forEach(m => {
        const cb = document.querySelector('#perm-modal .perm-mod-cb[data-module="' + m + '"]');
        if (!cb) return;
        const isEnabled = Boolean(presetMods[m]);
        cb.checked = isEnabled;
        togglePermModule(m, isEnabled);
        if (isEnabled && Array.isArray(presetMods[m])) {
            document.querySelectorAll('#perm-modal .perm-act-cb[data-module="' + m + '"]').forEach(a => {
                a.checked = presetMods[m].includes(a.dataset.action);
            });
        }
    });
}

function openPermissionsModal(userId, name, perms, staffId, position, department, accountStatus) {
    permEditingUserId = userId;
    document.getElementById('perm-staff-name').textContent = name;
    document.getElementById('perm-form').action = '/staff-management/' + userId + '/permissions';
    document.getElementById('perm-staff-id').value = staffId || '';
    document.getElementById('perm-position').value = position || '';
    document.getElementById('perm-department').value = department || '';
    document.getElementById('perm-account-status').value = accountStatus || 'active';

    MODULE_KEYS.forEach(m => {
        const cb = document.querySelector('#perm-modal .perm-mod-cb[data-module="' + m + '"]');
        if (cb) {
            cb.checked = false;
            togglePermModule(m, false);
        }
        document.querySelectorAll('#perm-modal .perm-act-cb[data-module="' + m + '"]').forEach(a => { a.checked = false; });
    });

    (perms || []).forEach(p => {
        const cb = document.querySelector('#perm-modal .perm-mod-cb[data-module="' + p.module + '"]');
        if (!cb) return;
        cb.checked = true;
        togglePermModule(p.module, true);
        (p.actions || []).forEach(action => {
            const a = document.querySelector('#perm-modal .perm-act-cb[data-module="' + p.module + '"][data-action="' + action + '"]');
            if (a) a.checked = true;
        });
    });

    refreshPermJobAvailability(userId);
    const matched = findPresetKeyByPosition(position);
    document.querySelectorAll('#perm-modal .perm-preset-btn').forEach(b => b.classList.remove('active'));
    if (matched) {
        selectPermJob(matched, false);
    } else {
        document.getElementById('perm-preset-label').textContent = position ? ('Custom · ' + position) : 'Choose a job';
        document.getElementById('perm-preset-toggle').classList.toggle('is-active', Boolean(position));
    }

    updatePermAccessCount();
    document.getElementById('perm-modal').classList.add('open');
}

function closePermissionsModal() {
    document.getElementById('perm-modal').classList.remove('open');
    permEditingUserId = null;
}

document.querySelectorAll('.perm-mod-cb').forEach(cb => {
    cb.addEventListener('change', function() {
        togglePermModule(this.dataset.module, this.checked);
    });
});

document.querySelectorAll('.perm-preset-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        if (this.disabled) return;
        selectPermJob(this.dataset.preset, true);
    });
});

document.querySelector('#perm-form')?.addEventListener('submit', function (e) {
    if (document.getElementById('perm-position').value.trim()) return;
    e.preventDefault();
    const toggle = document.getElementById('perm-preset-toggle');
    toggle.classList.add('is-invalid');
    bootstrap.Dropdown.getOrCreateInstance(toggle).show();
});

// Reset password modal
function openResetPasswordModal(userId, name) {
    document.getElementById('reset-pwd-name').textContent = name;
    document.getElementById('reset-pwd-form').action = '/staff-management/' + userId + '/reset-password';
    document.getElementById('reset-pwd-modal').classList.add('open');
}
function closeResetPasswordModal() {
    document.getElementById('reset-pwd-modal').classList.remove('open');
}

// Quick status change
function quickStatus(userId, status) {
    const labels = {
        active: 'activate',
        inactive: 'deactivate',
        suspended: 'suspend',
        on_leave: 'mark as On Leave'
    };
    if (!confirm('Are you sure you want to ' + (labels[status] || status) + ' this staff account?')) return;
    document.getElementById('qsf-status-' + userId).value = status;
    document.getElementById('qsf-' + userId).submit();
}

// Close on backdrop click
document.querySelectorAll('.sm-modal-backdrop').forEach(bd => {
    bd.addEventListener('click', e => {
        if (e.target === bd) bd.classList.remove('open');
    });
});
</script>
@endpush
