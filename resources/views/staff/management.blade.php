@extends('layouts.app')

@section('title', 'Staff Management – Talisay Beach Smart Tourism')

@push('styles')
<style>
/* ── Modern Staff Management Design System ──────────────────────── */
.sm-page-header {
    background: #ffffff;
    border: 1px solid #e2e8f0;
}

/* Glassmorphism Tab Bar */
.smtab-bar {
    display: inline-flex;
    gap: 6px;
    background: #f1f5f9;
    border-radius: 8px;
    padding: 4px;
    border: 1px solid #e2e8f0;
    max-width: 100%;
    overflow-x: auto;
}
.smtab-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 6px;
    border: none;
    background: transparent;
    font-size: 0.82rem;
    font-weight: 700;
    color: #64748b;
    cursor: pointer;
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    white-space: nowrap;
}
.smtab-btn:hover {
    color: #0f172a;
    background: rgba(255, 255, 255, 0.6);
}
.smtab-btn.active {
    background: #ffffff;
    color: #0c4a6e;
    border: 1px solid #e2e8f0;
}
.smtab-panel { display: none; }
.smtab-panel.active { display: block; }
@keyframes tabFadeSlide {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* KPI Stat Cards */
.kpi-stat-card {
    background: #ffffff;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    padding: 20px;
    position: relative;
    overflow: hidden;
}
.kpi-stat-card:hover {
    border-color: #cbd5e1;
}
.kpi-accent-bar {
    display: none;
}

/* Staff Card */
.staff-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    padding: 22px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: visible;
    transition: border-color 0.15s ease;
    box-shadow: none;
}
.staff-card:hover {
    border-color: #cbd5e1;
}
.staff-card-top-bar {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}

/* Avatar styling */
.staff-avatar-wrap {
    position: relative;
    width: 52px;
    height: 52px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.05rem;
    color: #ffffff;
    flex-shrink: 0;
    background: #0c4a6e;
}
.avatar-status-beacon {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2.5px solid #ffffff;
}

/* Pulsing beacon */
.beacon-pulse {
    animation: none;
}
@keyframes beaconPulse {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6); }
    70% { box-shadow: 0 0 0 7px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

/* Status Badges */
.pill-duty {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.pill-duty.available {
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}
.pill-duty.busy {
    background: #fffbeb;
    color: #92400e;
    border: 1px solid #fde68a;
}
.pill-duty.on_leave {
    background: #fff7ed;
    color: #9a3412;
    border: 1px solid #fed7aa;
}
.pill-duty.off_duty {
    background: #f8fafc;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.pill-account {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 0.66rem;
    font-weight: 700;
}
.pill-account.active { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.pill-account.inactive { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }
.pill-account.suspended { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.pill-account.on_leave { background: #fff7ed; color: #9a3412; border: 1px solid #fed7aa; }

/* Module Chips */
.mod-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 9px;
    font-size: 0.68rem;
    font-weight: 700;
    background: #f0f9ff;
    color: #0284c7;
    border: 1px solid #bae6fd;
    transition: all 0.15s ease;
}
.mod-chip:hover {
    background: #e0f2fe;
    border-color: #7dd3fc;
}

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
    padding: 7px 15px;
    border-radius: 11px;
    font-size: 0.76rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    border: 1.5px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
}
.preset-btn:hover {
    border-color: #38bdf8;
    background: #f0f9ff;
    color: #0284c7;
    transform: translateY(-1px);
}
.preset-btn.selected {
    border-color: #0284c7;
    background: #0284c7;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.28);
}

/* Action Badges in Log */
.log-action-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 8px;
    font-size: 0.68rem;
    font-weight: 700;
    background: #f1f5f9;
    color: #475569;
}
.log-action-badge.create  { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.log-action-badge.update  { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.log-action-badge.status  { background: #fff7ed; color: #9a3412; border: 1px solid #fed7aa; }
.log-action-badge.danger  { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.log-action-badge.access  { background: #fef9c3; color: #854d0e; border: 1px solid #fef08a; }

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
    border-color: #7dd3fc;
    background: #f8fafc;
}
.duty-radio-tile:has(:checked) {
    border-color: #0284c7;
    background: #f0f9ff;
    box-shadow: none;
}
</style>
@endpush

@section('content')

{{-- ══════════════════════════════════════════════════════
     PAGE HEADER — Modern Resort Operations Banner
══════════════════════════════════════════════════════ --}}
<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100 text-sky-800 border border-sky-200">
                <i class="bi bi-people-fill text-[10px] text-sky-600"></i>
                Staff Directory
            </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-1">Staff Management</h1>
        <p class="text-sm text-slate-500 mb-0">
            Oversee staff duty, role assignments, module permissions, and the activity log.
        </p>
    </div>
    <div class="flex items-center gap-3">
        <button type="button" onclick="openAddStaffModal()" class="btn-ocean text-xs">
            <i class="bi bi-person-plus"></i>
            <span>Add Staff</span>
        </button>
    </div>
</div>

{{-- Success Flash Alert --}}
@if(session('success'))
<div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl px-5 py-3.5 text-sm font-semibold shadow-sm">
    <div class="w-7 h-7 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
        <i class="bi bi-check-circle-fill text-emerald-600"></i>
    </div>
    <div class="flex-1">{{ session('success') }}</div>
    <button class="text-emerald-400 hover:text-emerald-700 text-base" onclick="this.closest('.mb-6').remove()">
        <i class="bi bi-x-lg"></i>
    </button>
</div>
@endif

{{-- Error Flash Alert --}}
@if(session('error'))
<div class="mb-6 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 rounded-2xl px-5 py-3.5 text-sm font-semibold shadow-sm">
    <div class="w-7 h-7 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
        <i class="bi bi-exclamation-triangle-fill text-red-600"></i>
    </div>
    <div class="flex-1">{{ session('error') }}</div>
    <button class="text-red-400 hover:text-red-700 text-base" onclick="this.closest('.mb-6').remove()">
        <i class="bi bi-x-lg"></i>
    </button>
</div>
@endif

{{-- ══════════════════════════════════════════════════════
     TOP KPI METRICS CARDS
══════════════════════════════════════════════════════ --}}
@php
    $totalStaff     = $staffMembers->count();
    $availableStaff = $staffMembers->where('duty_status','available')->count();
    $busyStaff      = $staffMembers->where('duty_status','busy')->count();
    $onLeaveStaff   = $staffMembers->where('duty_status','on_leave')->count();
@endphp
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5 mb-6">
    {{-- Card 1: Total Staff --}}
    <div class="kpi-stat-card">
        <div class="kpi-accent-bar bg-gradient-to-r from-sky-400 to-blue-600"></div>
        <div class="flex items-center justify-between gap-3 mb-3">
            <span class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Total Staff</span>
            <div class="w-10 h-10 rounded-lg bg-ocean-800 text-white flex items-center justify-center">
                <i class="bi bi-people-fill text-base"></i>
            </div>
        </div>
        <div class="flex items-baseline gap-2">
            <span class="text-3xl font-black text-slate-900 leading-none">{{ $totalStaff }}</span>
            <span class="text-xs font-bold text-slate-400">Members</span>
        </div>
        <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
            <span>Roster capacity</span>
            <span class="font-extrabold text-sky-700">100% Configured</span>
        </div>
    </div>

    {{-- Card 2: On Duty / Ready --}}
    <div class="kpi-stat-card">
        <div class="kpi-accent-bar bg-gradient-to-r from-cyan-400 to-teal-500"></div>
        <div class="flex items-center justify-between gap-3 mb-3">
            <span class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">On Duty</span>
            <div class="w-10 h-10 rounded-lg bg-ocean-600 text-white flex items-center justify-center">
                <i class="bi bi-person-check-fill text-base"></i>
            </div>
        </div>
        <div class="flex items-baseline gap-2">
            <span class="text-3xl font-black text-teal-700 leading-none">{{ $availableStaff }}</span>
            <span class="inline-flex items-center gap-1 text-[11px] font-extrabold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 beacon-pulse"></span> Ready
            </span>
        </div>
        <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
            <span>Available right now</span>
            <span class="font-extrabold text-teal-700">Open for tasks</span>
        </div>
    </div>

    {{-- Card 4: Busy / In Task --}}
    <div class="kpi-stat-card">
        <div class="kpi-accent-bar bg-gradient-to-r from-amber-400 to-orange-500"></div>
        <div class="flex items-center justify-between gap-3 mb-3">
            <span class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Busy / In Task</span>
            <div class="w-10 h-10 rounded-lg bg-amber-600 text-white flex items-center justify-center">
                <i class="bi bi-hourglass-split text-base"></i>
            </div>
        </div>
        <div class="flex items-baseline gap-2">
            <span class="text-3xl font-black text-amber-700 leading-none">{{ $busyStaff }}</span>
            <span class="text-xs font-bold text-amber-600">Handling Tasks</span>
        </div>
        <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
            <span>Active assignments</span>
            <span class="font-extrabold text-amber-700">{{ $onLeaveStaff }} On Leave</span>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MODERN SEGMENTED TAB BAR
══════════════════════════════════════════════════════ --}}
<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div class="smtab-bar">
        <button type="button" class="smtab-btn" id="btn-tab-roster" onclick="switchTab('roster')">
            <i class="bi bi-people-fill text-sm"></i>
            <span>Staff Directory</span>
            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-200 text-slate-700">{{ $totalStaff }}</span>
        </button>
        <button type="button" class="smtab-btn" id="btn-tab-rbac" onclick="switchTab('rbac')">
            <i class="bi bi-shield-lock-fill text-sm"></i>
            <span>Job &amp; Role Assignments</span>
            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-800">RBAC</span>
        </button>
        <button type="button" class="smtab-btn" id="btn-tab-activity" onclick="gotoActivity()">
            <i class="bi bi-clock-history text-sm"></i>
            <span>Activity Log</span>
        </button>
    </div>

    {{-- Instant Directory Search for Staff Cards --}}
    <div class="relative w-full sm:w-72" id="directorySearchBox">
        <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
        <input
            type="text"
            id="quickStaffSearch"
            placeholder="Search staff, position, ID..."
            class="w-full pl-9 pr-8 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 placeholder-slate-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-sky-300 focus:border-sky-500 transition"
        >
        <button type="button" id="clearQuickSearch" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">
            <i class="bi bi-x-circle-fill"></i>
        </button>
    </div>
</div>

{{-- ======================================================
     TAB 1: STAFF DIRECTORY ROSTER
====================================================== --}}
<div class="smtab-panel" id="panel-roster">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5" id="staffCardsContainer">
        @forelse($staffMembers as $staff)
        @php
            $ds = $staff->duty_status ?? 'available';
            $initial = strtoupper(substr($staff->name, 0, 1));
            $dutyAccent = match($ds) {
                'available' => 'bg-emerald-500',
                'busy'      => 'bg-amber-500',
                'on_leave'  => 'bg-orange-500',
                default     => 'bg-slate-400',
            };
        @endphp
        <div class="staff-card group" data-name="{{ strtolower($staff->name) }}" data-email="{{ strtolower($staff->email) }}" data-id="{{ strtolower($staff->staff_id ?? '') }}" data-pos="{{ strtolower($staff->position ?? '') }}" data-dept="{{ strtolower($staff->department ?? '') }}">
            <div>
                {{-- Staff Header Row --}}
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3 min-w-0">
                        {{-- Avatar with duty beacon --}}
                        <div class="staff-avatar-wrap">
                            @if($staff->avatar)
                                <img src="{{ asset('storage/'.$staff->avatar) }}" alt="{{ $staff->name }}" class="w-full h-full object-cover rounded-2xl">
                            @else
                                <span>{{ $initial }}</span>
                            @endif
                            <span class="avatar-status-beacon {{ $dutyAccent }} {{ $ds==='available' ? 'beacon-pulse' : '' }}"></span>
                        </div>

                        <div class="min-w-0">
                            <h3 class="text-sm font-extrabold text-slate-900 leading-snug group-hover:text-sky-700 transition truncate">{{ $staff->name }}</h3>
                            <div class="flex items-center gap-1.5 flex-wrap mt-0.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-sky-50 text-sky-800 border border-sky-100 truncate">
                                    {{ $staff->position ?? 'Resort Staff' }}
                                </span>
                            </div>
                            <span class="text-[11px] font-medium text-slate-400 flex items-center gap-1 mt-0.5">
                                <i class="bi bi-building text-[10px]"></i> {{ $staff->department ?? 'Operations' }}
                            </span>
                        </div>
                    </div>

                    {{-- Badges Column --}}
                    <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
                        <span class="pill-duty {{ $ds }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $dutyAccent }} {{ $ds==='available' ? 'beacon-pulse' : '' }}"></span>
                            {{ ucfirst(str_replace('_', ' ', $ds)) }}
                        </span>
                    </div>
                </div>

                {{-- Contact & Credentials Box --}}
                <div class="p-3.5 rounded-xl bg-slate-50/90 border border-slate-100 text-xs space-y-2 mb-4">
                    @if($staff->staff_id)
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 text-slate-600">
                            <i class="bi bi-upc-scan text-sky-500 text-xs"></i>
                            <span class="font-mono font-bold text-sky-900 bg-sky-100/70 px-2 py-0.5 rounded text-[11px]">{{ $staff->staff_id }}</span>
                        </div>
                        <button type="button" onclick="navigator.clipboard.writeText('{{ $staff->staff_id }}'); this.textContent='Copied!'; setTimeout(()=>this.textContent='Copy', 1500)" class="text-[10px] font-bold text-slate-400 hover:text-sky-600 transition">
                            Copy
                        </button>
                    </div>
                    @endif

                    <div class="flex items-center gap-2 text-slate-600 truncate">
                        <i class="bi bi-envelope-fill text-slate-400 text-xs flex-shrink-0"></i>
                        <a href="mailto:{{ $staff->email }}" class="text-slate-700 hover:text-sky-600 hover:underline truncate">{{ $staff->email }}</a>
                    </div>

                    <div class="flex items-center gap-2 text-slate-600">
                        <i class="bi bi-telephone-fill text-slate-400 text-xs flex-shrink-0"></i>
                        <span class="text-slate-700">{{ $staff->phone ?? 'No phone recorded' }}</span>
                    </div>

                    @if($staff->duty_notes)
                    <div class="flex items-start gap-2 text-amber-800 bg-amber-50/80 p-2 rounded-lg border border-amber-200/60 text-[11px] leading-snug">
                        <i class="bi bi-pin-angle-fill text-amber-500 mt-0.5 flex-shrink-0"></i>
                        <span>{{ $staff->duty_notes }}</span>
                    </div>
                    @endif
                </div>

                {{-- Modules Chips Section --}}
                <div class="mb-4">
                    <div class="flex items-center justify-between text-[11px] font-extrabold text-slate-400 uppercase tracking-wider mb-2">
                        <span>Assigned Modules</span>
                        <span class="text-sky-700 font-black">{{ $staff->permissions->count() }} Access Keys</span>
                    </div>
                    @if($staff->permissions->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($staff->permissions->take(4) as $perm)
                        <span class="mod-chip">
                            <i class="{{ $modules[$perm->module]['icon'] ?? 'bi-puzzle' }} text-[10px]"></i>
                            {{ $modules[$perm->module]['name'] ?? ucfirst($perm->module) }}
                        </span>
                        @endforeach
                        @if($staff->permissions->count() > 4)
                        <span class="mod-chip" style="background:#f1f5f9;color:#64748b;border-color:#e2e8f0;">
                            +{{ $staff->permissions->count() - 4 }} more
                        </span>
                        @endif
                    </div>
                    @else
                    <p class="text-[11px] text-slate-400 italic mb-0">No system modules assigned yet</p>
                    @endif
                </div>
            </div>

            {{-- Card Footer Action Buttons --}}
            <div class="pt-3.5 border-t border-slate-100 flex items-center gap-2">
                <button
                    type="button"
                    onclick="openDutyModal({{ $staff->id }}, {{ Js::from($staff->name) }}, {{ Js::from($staff->duty_status ?? 'available') }}, {{ Js::from($staff->duty_notes ?? '') }})"
                    class="flex-1 py-2.5 px-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm hover:border-slate-300"
                >
                    <i class="bi bi-arrow-repeat text-sky-600"></i>
                    <span>Duty Status</span>
                </button>

                <button
                    type="button"
                    onclick="openPermissionsModal({{ $staff->id }}, {{ Js::from($staff->name) }}, {{ Js::from($staff->permissions) }}, {{ Js::from($staff->staff_id ?? '') }}, {{ Js::from($staff->position ?? '') }}, {{ Js::from($staff->department ?? '') }}, {{ Js::from($staff->account_status ?? 'active') }})"
                    class="flex-1 py-2.5 px-3 rounded-lg bg-ocean-800 hover:bg-ocean-900 text-white text-xs font-semibold transition flex items-center justify-center gap-1.5"
                >
                    <i class="bi bi-shield-lock-fill"></i>
                    <span>Permissions</span>
                </button>

                {{-- Action Menu Dropdown --}}
                <div class="relative" x-data="{ open: false }">
                    <button
                        @click="open = !open"
                        type="button"
                        class="p-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 hover:text-slate-800 transition flex items-center justify-center text-xs"
                    >
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <div
                        x-show="open"
                        @click.outside="open = false"
                        x-transition
                        class="absolute right-0 top-full mt-2 z-[80] bg-white border border-slate-200 rounded-xl shadow-xl w-52 py-1.5"
                    >
                        <div class="px-3 py-1 text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Account Actions</div>
                        <button @click="open=false; quickStatus({{ $staff->id }},'active')" class="flex items-center gap-2 w-full px-4 py-2 text-xs font-bold text-emerald-700 hover:bg-emerald-50">
                            <i class="bi bi-check-circle-fill text-emerald-500"></i> Activate Account
                        </button>
                        <button @click="open=false; quickStatus({{ $staff->id }},'on_leave')" class="flex items-center gap-2 w-full px-4 py-2 text-xs font-bold text-amber-700 hover:bg-amber-50">
                            <i class="bi bi-calendar-x-fill text-amber-500"></i> Set On Leave
                        </button>
                        <button @click="open=false; quickStatus({{ $staff->id }},'inactive')" class="flex items-center gap-2 w-full px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                            <i class="bi bi-slash-circle-fill text-slate-400"></i> Deactivate
                        </button>
                        <div class="border-t border-slate-100 my-1"></div>
                        <button @click="open=false; quickStatus({{ $staff->id }},'suspended')" class="flex items-center gap-2 w-full px-4 py-2 text-xs font-bold text-red-700 hover:bg-red-50">
                            <i class="bi bi-ban-fill text-red-500"></i> Suspend Account
                        </button>
                        <button @click="open=false; openResetPasswordModal({{ $staff->id }}, {{ Js::from($staff->name) }})" class="flex items-center gap-2 w-full px-4 py-2 text-xs font-bold text-indigo-700 hover:bg-indigo-50">
                            <i class="bi bi-key-fill text-indigo-500"></i> Reset Password
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-400 shadow-sm">
            <div class="w-16 h-16 rounded-2xl bg-sky-50 text-sky-500 flex items-center justify-center text-3xl mx-auto mb-4">
                <i class="bi bi-people"></i>
            </div>
            <h3 class="text-base font-extrabold text-slate-800 mb-1">No Staff Members Found</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto mb-4">There are currently no staff registered matching the selected criteria.</p>
            <button onclick="openAddStaffModal()" class="btn-ocean text-xs">
                <i class="bi bi-person-plus-fill me-1"></i> Add First Staff Member
            </button>
        </div>
        @endforelse
    </div>
</div>

{{-- ======================================================
     TAB 2: JOB & ROLE ASSIGNMENTS (RBAC Table)
====================================================== --}}
<div class="smtab-panel" id="panel-rbac">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm mb-6">
        <div class="px-6 py-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4 bg-slate-50">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 mb-0.5 flex items-center gap-2">
                    <i class="bi bi-shield-check text-sky-600"></i> Role-Based Access Control (RBAC)
                </h2>
                <p class="text-xs text-slate-500 mb-0">Granular permission matrix mapping staff members to resort operation modules.</p>
            </div>
            <div class="flex items-center gap-2 px-3.5 py-1.5 rounded-lg bg-sky-50 border border-sky-200 text-sky-800 font-semibold text-xs">
                <i class="bi bi-shield-check text-sky-700"></i> Admin role maintains master oversight
            </div>
        </div>

        {{-- Filter Bar --}}
        <form method="GET" action="{{ route('tasks.staff') }}" class="px-6 py-4 border-b border-slate-100 bg-slate-50/40 flex flex-wrap gap-3 items-end">
            <input type="hidden" name="tab" value="rbac">
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, email, staff ID..." class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium focus:ring-2 focus:ring-sky-300 focus:border-sky-500 outline-none w-60 shadow-sm">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Department</label>
                <input type="text" name="department" value="{{ request('department') }}" placeholder="e.g. Front Office" class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium focus:ring-2 focus:ring-sky-300 focus:border-sky-500 outline-none w-48 shadow-sm">
            </div>
            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                <i class="bi bi-funnel-fill"></i> Filter
            </button>
            @if(request()->hasAny(['search','department']))
            <a href="{{ route('tasks.staff',['tab'=>'rbac']) }}" class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-xs font-bold transition shadow-sm">
                Clear
            </a>
            @endif
        </form>

        {{-- Table Container --}}
        <div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70">
                        <th class="text-left text-[11px] font-extrabold text-slate-400 uppercase tracking-wider px-6 py-3.5">Staff Member</th>
                        <th class="text-left text-[11px] font-extrabold text-slate-400 uppercase tracking-wider px-4 py-3.5">Staff ID</th>
                        <th class="text-left text-[11px] font-extrabold text-slate-400 uppercase tracking-wider px-4 py-3.5">Position / Dept.</th>
                        <th class="text-left text-[11px] font-extrabold text-slate-400 uppercase tracking-wider px-4 py-3.5">Assigned Modules</th>
                        <th class="text-right text-[11px] font-extrabold text-slate-400 uppercase tracking-wider px-6 py-3.5">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($staffMembers as $staff)
                    <tr class="hover:bg-sky-50/30 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-ocean-800 text-white flex items-center justify-center font-black text-xs shadow-sm flex-shrink-0">
                                    {{ strtoupper(substr($staff->name,0,1)) }}
                                </div>
                                <div>
                                    <p class="font-extrabold text-slate-900 text-xs mb-0">{{ $staff->name }}</p>
                                    <p class="text-[11px] text-slate-400 truncate max-w-[180px]">{{ $staff->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <span class="font-mono font-bold text-sky-800 text-xs bg-sky-50 px-2.5 py-1 rounded-lg border border-sky-200">
                                {{ $staff->staff_id ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <p class="font-bold text-slate-800 text-xs mb-0.5">{{ $staff->position ?? '—' }}</p>
                            <p class="text-[11px] text-slate-400">{{ $staff->department ?? '—' }}</p>
                        </td>
                        <td class="px-4 py-4">
                            @if($staff->permissions->isEmpty())
                            <span class="text-[11px] text-slate-400 italic">No modules assigned</span>
                            @else
                            <div class="flex flex-wrap gap-1">
                                @foreach($staff->permissions->take(3) as $perm)
                                <span class="mod-chip">
                                    {{ $modules[$perm->module]['name'] ?? ucfirst($perm->module) }}
                                </span>
                                @endforeach
                                @if($staff->permissions->count() > 3)
                                <span class="mod-chip" style="background:#f1f5f9;color:#64748b;border-color:#e2e8f0;">
                                    +{{ $staff->permissions->count() - 3 }}
                                </span>
                                @endif
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button
                                    type="button"
                                    onclick="openPermissionsModal({{ $staff->id }}, {{ Js::from($staff->name) }}, {{ Js::from($staff->permissions) }}, {{ Js::from($staff->staff_id ?? '') }}, {{ Js::from($staff->position ?? '') }}, {{ Js::from($staff->department ?? '') }}, {{ Js::from($staff->account_status ?? 'active') }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-sky-50 border border-sky-200 text-sky-800 text-xs font-bold hover:bg-sky-100 transition shadow-sm"
                                >
                                    <i class="bi bi-shield-lock-fill text-sky-600"></i> Edit Roles
                                </button>
                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open" type="button" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-slate-100 border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-200 transition">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 top-9 z-50 bg-white border border-slate-200 rounded-2xl shadow-xl w-52 py-1.5 text-left">
                                        <button @click="open=false; quickStatus({{ $staff->id }},'active')" class="flex items-center gap-2 w-full px-4 py-2 text-xs font-bold text-emerald-700 hover:bg-emerald-50">
                                            <i class="bi bi-check-circle-fill text-emerald-500"></i> Activate Account
                                        </button>
                                        <button @click="open=false; quickStatus({{ $staff->id }},'on_leave')" class="flex items-center gap-2 w-full px-4 py-2 text-xs font-bold text-amber-700 hover:bg-amber-50">
                                            <i class="bi bi-calendar-x-fill text-amber-500"></i> Set On Leave
                                        </button>
                                        <button @click="open=false; quickStatus({{ $staff->id }},'inactive')" class="flex items-center gap-2 w-full px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                            <i class="bi bi-slash-circle-fill text-slate-400"></i> Deactivate
                                        </button>
                                        <div class="border-t border-slate-100 my-1"></div>
                                        <button @click="open=false; quickStatus({{ $staff->id }},'suspended')" class="flex items-center gap-2 w-full px-4 py-2 text-xs font-bold text-red-700 hover:bg-red-50">
                                            <i class="bi bi-ban-fill text-red-500"></i> Suspend Account
                                        </button>
                                        <button @click="open=false; openResetPasswordModal({{ $staff->id }}, {{ Js::from($staff->name) }})" class="flex items-center gap-2 w-full px-4 py-2 text-xs font-bold text-indigo-700 hover:bg-indigo-50">
                                            <i class="bi bi-key-fill text-indigo-500"></i> Reset Password
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-16 text-slate-400">
                            <i class="bi bi-person-x text-4xl mb-2 block"></i>
                            <p class="font-bold text-slate-600">No staff members found matching criteria</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Role Presets Reference Cards --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2 mb-0">
                <i class="bi bi-bookmark-star-fill text-sky-600"></i> Standard Staff Role Presets
            </h3>
            <span class="text-xs text-slate-400 font-medium">Click on any role in Edit Modal for 1-click assignment</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($presets as $key => $preset)
            <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/60 hover:bg-white hover:border-sky-200 transition-all duration-200 shadow-sm">
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <p class="text-xs font-extrabold text-slate-900 mb-0">{{ $preset['name'] ?? $preset['label'] ?? ucfirst(str_replace('_',' ',$key)) }}</p>
                    <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-100">
                        {{ count($preset['modules']) }} modules
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 font-semibold mb-2">
                    <i class="bi bi-briefcase text-slate-400"></i> {{ $preset['position'] ?? '' }} &bull; {{ $preset['department'] ?? '' }}
                </p>
                <div class="flex flex-wrap gap-1">
                    @foreach($preset['modules'] as $mKey => $actions)
                    <span class="mod-chip text-[10px]" title="{{ implode(', ', (array)$actions) }}">
                        <i class="{{ $modules[$mKey]['icon'] ?? 'bi-puzzle' }} text-[9px]"></i>
                        {{ $modules[$mKey]['name'] ?? ucfirst($mKey) }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ======================================================
     TAB 3: ACTIVITY AUDIT LOG
====================================================== --}}
<div class="smtab-panel" id="panel-activity">
    <form method="GET" action="{{ route('tasks.staff') }}" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm px-6 py-5 mb-6 flex flex-wrap gap-3 items-end">
        <input type="hidden" name="tab" value="activity">
        <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Search Events</label>
            <input type="text" name="activity_search" value="{{ request('activity_search') }}" placeholder="Staff name, action..." class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-sky-300 outline-none w-52 shadow-sm">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Staff Member</label>
            <select name="staff_user_id" class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-sky-300 outline-none w-48 shadow-sm">
                <option value="">All Staff</option>
                @foreach($staffMembers as $s)
                <option value="{{ $s->id }}" {{ request('staff_user_id')==$s->id?'selected':'' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Action Type</label>
            <select name="action_filter" class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-sky-300 outline-none shadow-sm">
                <option value="">All Actions</option>
                <option value="staff_account_created" {{ request('action_filter')==='staff_account_created'?'selected':'' }}>Account Created</option>
                <option value="staff_permissions_updated" {{ request('action_filter')==='staff_permissions_updated'?'selected':'' }}>Permissions Updated</option>
                <option value="staff_account_status_changed" {{ request('action_filter')==='staff_account_status_changed'?'selected':'' }}>Status Changed</option>
                <option value="staff_duty_status_updated" {{ request('action_filter')==='staff_duty_status_updated'?'selected':'' }}>Duty Status</option>
                <option value="staff_password_reset_by_admin" {{ request('action_filter')==='staff_password_reset_by_admin'?'selected':'' }}>Password Reset</option>
                <option value="unauthorized_access_attempt" {{ request('action_filter')==='unauthorized_access_attempt'?'selected':'' }}>Unauthorized Access</option>
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Date From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-sky-300 outline-none shadow-sm">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Date To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-medium focus:ring-2 focus:ring-sky-300 outline-none shadow-sm">
        </div>
        <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
            <i class="bi bi-funnel-fill"></i> Filter
        </button>
        @if(request()->hasAny(['activity_search','staff_user_id','action_filter','date_from','date_to']))
        <a href="{{ route('tasks.staff',['tab'=>'activity']) }}" class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-xs font-bold transition shadow-sm">
            Clear
        </a>
        @endif
    </form>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 mb-0.5">Staff Activity Log</h2>
                <p class="text-xs text-slate-400 mb-0">Immutable system records of all staff permissions, duty changes, and logins</p>
            </div>
        </div>
        @if($activityLogs && $activityLogs->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="w-full text-sm" style="min-width:750px">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70">
                        <th class="text-left text-[11px] font-extrabold text-slate-400 uppercase tracking-wider px-6 py-3.5">Timestamp</th>
                        <th class="text-left text-[11px] font-extrabold text-slate-400 uppercase tracking-wider px-4 py-3.5">Performed By</th>
                        <th class="text-left text-[11px] font-extrabold text-slate-400 uppercase tracking-wider px-4 py-3.5">Action</th>
                        <th class="text-left text-[11px] font-extrabold text-slate-400 uppercase tracking-wider px-4 py-3.5">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($activityLogs as $log)
                    @php
                        $bc = match(true) {
                            str_contains($log->action,'created')  => 'create',
                            str_contains($log->action,'permissions') || str_contains($log->action,'updated') => 'update',
                            str_contains($log->action,'status')   => 'status',
                            str_contains($log->action,'reset') || str_contains($log->action,'password') => 'danger',
                            str_contains($log->action,'unauthorized') => 'access',
                            default => '',
                        };
                        $al = ucwords(str_replace(['staff_','_'],['',' '],$log->action));
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="text-xs font-bold text-slate-900 mb-0">{{ $log->created_at->format('M d, Y') }}</p>
                            <p class="text-[11px] text-slate-400 mb-0">{{ $log->created_at->format('g:i:s A') }}</p>
                        </td>
                        <td class="px-4 py-4">
                            @if($log->user)
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-ocean-800 text-white flex items-center justify-center text-xs font-black flex-shrink-0 shadow-sm">
                                    {{ strtoupper(substr($log->user->name,0,1)) }}
                                </div>
                                <div>
                                    <p class="text-xs font-extrabold text-slate-900 mb-0">{{ $log->user->name }}</p>
                                    <p class="text-[11px] text-slate-400 capitalize mb-0">{{ $log->user->role ?? 'Staff' }}</p>
                                </div>
                            </div>
                            @else
                            <span class="inline-flex items-center gap-1 text-xs text-slate-400 font-semibold">
                                <i class="bi bi-cpu"></i> System Auto
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <span class="log-action-badge {{ $bc }}">{{ $al }}</span>
                        </td>
                        <td class="px-4 py-4">
                            @if($log->new_values)
                            <div class="text-[11px] text-slate-600 space-y-1">
                                @foreach(array_slice((array)$log->new_values,0,4) as $k => $v)
                                    @if(!is_array($v))
                                    <div><span class="font-bold text-slate-400">{{ ucfirst(str_replace('_',' ',$k)) }}:</span> <span class="text-slate-800 font-semibold">{{ Str::limit((string)$v,65) }}</span></div>
                                    @elseif(count($v))
                                    <div><span class="font-bold text-slate-400">{{ ucfirst(str_replace('_',' ',$k)) }}:</span> <span class="text-slate-800 font-semibold">{{ implode(', ',array_slice($v,0,4)) }}{{ count($v)>4?'...':'' }}</span></div>
                                    @endif
                                @endforeach
                            </div>
                            @else
                            <span class="text-[11px] text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/40">
            {{ $activityLogs->withQueryString()->links() }}
        </div>
        @else
        <div class="text-center py-16 text-slate-400">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mx-auto mb-3">
                <i class="bi bi-clock-history"></i>
            </div>
            <p class="font-extrabold text-slate-700 mb-1">No Activity Logs Found</p>
            <p class="text-xs text-slate-400">Staff actions will automatically record in this audit trail.</p>
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
<div class="sm-modal-backdrop" id="add-staff-modal">
    <div class="sm-modal-box wide">
        <button type="button" onclick="closeAddStaffModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 text-xl transition">
            <i class="bi bi-x-lg"></i>
        </button>

        <div class="flex items-center gap-3 mb-1">
            <div class="w-10 h-10 rounded-xl bg-ocean-800 text-white flex items-center justify-center text-lg shadow-sm">
                <i class="bi bi-person-plus-fill"></i>
            </div>
            <div>
                <h3 class="text-lg font-extrabold text-slate-900 mb-0">Create New Staff Account</h3>
                <p class="text-xs text-slate-500 mb-0">Provision credentials, job assignment, and module permissions.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('tasks.staff.store') }}" class="mt-5">
            @csrf

            {{-- Quick Presets Banner --}}
            <div class="mb-5 p-4 bg-sky-50 rounded-2xl border border-sky-200/80">
                <p class="text-xs font-extrabold text-sky-900 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                    <i class="bi bi-magic text-sky-600"></i> One-Click Role Presets
                </p>
                <div class="flex flex-wrap gap-2" id="add-preset-btns">
                    @foreach($presets as $key => $preset)
                    <button type="button" class="preset-btn add-preset-btn" data-preset="{{ $key }}">
                        {{ $preset['name'] ?? $preset['label'] ?? ucfirst(str_replace('_',' ',$key)) }}
                    </button>
                    @endforeach
                    <button type="button" class="preset-btn" id="add-clear-btn">
                        <i class="bi bi-arrow-counterclockwise"></i> Custom / Clear
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-sky-300 focus:border-sky-500 outline-none shadow-sm" placeholder="e.g. Maria Santos">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="email" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-sky-300 focus:border-sky-500 outline-none shadow-sm" placeholder="staff@talisayresort.com">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Contact Number</label>
                    <input type="text" name="phone" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-sky-300 focus:border-sky-500 outline-none shadow-sm" placeholder="+63 9xx xxx xxxx">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Staff ID <span class="text-slate-400 font-normal">(auto-generated if blank)</span></label>
                    <input type="text" name="staff_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono font-bold text-slate-800 focus:ring-2 focus:ring-sky-300 focus:border-sky-500 outline-none shadow-sm" placeholder="EMP-003">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Position / Job Title <span class="text-red-500">*</span></label>
                    <input type="text" name="position" id="add-position" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-sky-300 focus:border-sky-500 outline-none shadow-sm" placeholder="e.g. Front Desk Officer">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Department <span class="text-red-500">*</span></label>
                    <input type="text" name="department" id="add-department" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-sky-300 focus:border-sky-500 outline-none shadow-sm" placeholder="e.g. Front Office">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Account Status</label>
                    <select name="account_status" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-sky-300 focus:border-sky-500 outline-none shadow-sm">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="on_leave">On Leave</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Duty Status</label>
                    <select name="duty_status" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-sky-300 focus:border-sky-500 outline-none shadow-sm">
                        <option value="available">Available (On Duty)</option>
                        <option value="busy">Busy (In Task)</option>
                        <option value="on_leave">On Leave</option>
                        <option value="off_duty">Off Duty</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password" required minlength="8" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-sky-300 focus:border-sky-500 outline-none shadow-sm" placeholder="Min. 8 characters">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Confirm Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password_confirmation" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-sky-300 focus:border-sky-500 outline-none shadow-sm" placeholder="Repeat password">
                </div>
            </div>

            <div class="border-t border-slate-200 pt-5 mb-6">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-extrabold text-slate-800 uppercase tracking-wider mb-0 flex items-center gap-1.5">
                        <i class="bi bi-shield-lock-fill text-indigo-600"></i> System Module Access
                    </p>
                    <span class="text-[11px] text-slate-400">Toggle modules and configure granular action permissions</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3" id="add-modules-grid">
                    @foreach($modules as $key => $mod)
                    <div class="p-3.5 rounded-2xl border-2 border-slate-200 transition-all duration-200 hover:border-slate-300 bg-white" id="add-card-{{ $key }}">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="modules[{{ $key }}][enabled]" value="1" class="mt-1 accent-indigo-600 add-mod-cb" data-module="{{ $key }}">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-extrabold text-slate-800 mb-0.5 flex items-center gap-1.5">
                                    <i class="{{ $mod['icon'] }} text-indigo-500 text-sm"></i> {{ $mod['name'] }}
                                </p>
                                <p class="text-[11px] text-slate-400 leading-snug mb-0">{{ $mod['description'] }}</p>
                                <div class="add-actions-row hidden mt-2.5 pt-2 border-t border-slate-100 flex flex-wrap gap-1.5">
                                    @foreach($mod['actions'] as $action)
                                    <label class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10.5px] cursor-pointer font-bold text-slate-600 bg-slate-50 hover:bg-slate-100 border border-slate-200">
                                        <input type="checkbox" name="modules[{{ $key }}][actions][]" value="{{ $action }}" class="accent-indigo-500" {{ in_array($action,$mod['default'])?'checked':'' }}>
                                        <span>{{ ucwords(str_replace('_',' ',$action)) }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closeAddStaffModal()" class="flex-1 py-3 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button type="submit" class="flex-1 py-3 rounded-lg bg-ocean-800 hover:bg-ocean-900 text-white font-semibold text-xs transition flex items-center justify-center gap-1.5">
                    <i class="bi bi-person-plus-fill"></i> Create Staff Account
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MODAL 3: EDIT PERMISSIONS (RBAC)
══════════════════════════════════════════════════════ --}}
<div class="sm-modal-backdrop" id="perm-modal">
    <div class="sm-modal-box wide">
        <button type="button" onclick="closePermissionsModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 text-xl transition">
            <i class="bi bi-x-lg"></i>
        </button>

        <div class="flex items-center gap-3 mb-1">
            <div class="w-10 h-10 rounded-xl bg-ocean-800 text-white flex items-center justify-center text-lg shadow-sm">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <div>
                <h3 class="text-lg font-extrabold text-slate-900 mb-0">Edit Role &amp; Permissions</h3>
                <p class="text-xs text-slate-500 mb-0">Configuring access matrix for: <strong id="perm-staff-name" class="text-slate-800">—</strong></p>
            </div>
        </div>

        <form id="perm-form" method="POST" class="mt-5">
            @csrf @method('PUT')

            <div class="mb-5 p-4 bg-sky-50 rounded-2xl border border-sky-200/80">
                <p class="text-xs font-extrabold text-sky-900 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                    <i class="bi bi-magic text-sky-600"></i> Role Preset Auto-Fill
                </p>
                <div class="flex flex-wrap gap-2">
                    @foreach($presets as $key => $preset)
                    <button type="button" class="preset-btn perm-preset-btn" data-preset="{{ $key }}">
                        {{ $preset['name'] ?? $preset['label'] ?? ucfirst(str_replace('_',' ',$key)) }}
                    </button>
                    @endforeach
                    <button type="button" class="preset-btn" id="perm-clear-btn">
                        <i class="bi bi-arrow-counterclockwise"></i> Custom / Clear
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Staff ID</label>
                    <input type="text" name="staff_id" id="perm-staff-id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono font-bold text-slate-800 focus:ring-2 focus:ring-sky-300 focus:border-sky-500 outline-none shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Position / Job Title <span class="text-red-500">*</span></label>
                    <input type="text" name="position" id="perm-position" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-sky-300 focus:border-sky-500 outline-none shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Department <span class="text-red-500">*</span></label>
                    <input type="text" name="department" id="perm-department" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-sky-300 focus:border-sky-500 outline-none shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Account Status</label>
                    <select name="account_status" id="perm-account-status" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-sky-300 focus:border-sky-500 outline-none shadow-sm">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="on_leave">On Leave</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
            </div>

            <div class="border-t border-slate-200 pt-5 mb-6">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-extrabold text-slate-800 uppercase tracking-wider mb-0 flex items-center gap-1.5">
                        <i class="bi bi-puzzle-fill text-indigo-500"></i> System Module Access
                    </p>
                    <span class="text-[11px] text-slate-400">Checked modules will be granted to this staff member</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($modules as $key => $mod)
                    <div class="p-3.5 rounded-2xl border-2 border-slate-200 transition-all duration-200 hover:border-slate-300 bg-white" id="perm-card-{{ $key }}">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="modules[{{ $key }}][enabled]" value="1" class="mt-1 accent-indigo-600 perm-mod-cb" data-module="{{ $key }}">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-extrabold text-slate-800 mb-0.5 flex items-center gap-1.5">
                                    <i class="{{ $mod['icon'] }} text-indigo-500 text-sm"></i> {{ $mod['name'] }}
                                </p>
                                <p class="text-[11px] text-slate-400 leading-snug mb-2">{{ $mod['description'] }}</p>
                                <div class="perm-actions-row hidden flex flex-wrap gap-1.5 pt-2 border-t border-slate-100">
                                    @foreach($mod['actions'] as $action)
                                    <label class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10.5px] cursor-pointer font-bold text-slate-600 bg-slate-50 hover:bg-slate-100 border border-slate-200">
                                        <input type="checkbox" name="modules[{{ $key }}][actions][]" value="{{ $action }}" class="accent-indigo-500 perm-act-cb" data-module="{{ $key }}" data-action="{{ $action }}">
                                        <span>{{ ucwords(str_replace('_',' ',$action)) }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closePermissionsModal()" class="flex-1 py-3 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button type="submit" class="flex-1 py-3 rounded-lg bg-ocean-800 hover:bg-ocean-900 text-white font-bold text-xs transition flex items-center justify-center gap-1.5">
                    <i class="bi bi-shield-check-fill"></i> Save Role &amp; Permissions
                </button>
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
                <i class="bi bi-key-fill"></i>
            </div>
            <div>
                <h3 class="text-lg font-extrabold text-slate-900 mb-0">Reset Staff Password</h3>
                <p class="text-xs text-slate-500 mb-0">Set a new password for <strong id="reset-pwd-name" class="text-slate-800">—</strong></p>
            </div>
        </div>

        <form id="reset-pwd-form" method="POST" class="mt-5">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">New Password <span class="text-red-500">*</span></label>
                <input type="password" name="password" required minlength="8" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-sky-300 focus:border-sky-500 outline-none shadow-sm" placeholder="Min. 8 characters">
            </div>
            <div class="mb-5">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Confirm New Password <span class="text-red-500">*</span></label>
                <input type="password" name="password_confirmation" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-sky-300 focus:border-sky-500 outline-none shadow-sm" placeholder="Repeat password">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeResetPasswordModal()" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs transition shadow-md shadow-red-600/20 flex items-center justify-center gap-1">
                    <i class="bi bi-key-fill"></i> Reset Password
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

// Tab switching
function switchTab(tab) {
    ['roster', 'rbac', 'activity'].forEach(t => {
        const panel = document.getElementById('panel-' + t);
        const btn = document.getElementById('btn-tab-' + t);
        if (panel) panel.classList.toggle('active', t === tab);
        if (btn) btn.classList.toggle('active', t === tab);
    });

    const dirSearch = document.getElementById('directorySearchBox');
    if (dirSearch) {
        dirSearch.style.display = (tab === 'roster') ? 'block' : 'none';
    }
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

// Real-time Staff Search Filter on Directory Cards
const searchInput = document.getElementById('quickStaffSearch');
const clearSearchBtn = document.getElementById('clearQuickSearch');

if (searchInput) {
    searchInput.addEventListener('input', function() {
        const val = this.value.toLowerCase().trim();
        if (clearSearchBtn) clearSearchBtn.classList.toggle('hidden', val.length === 0);

        document.querySelectorAll('#staffCardsContainer .staff-card').forEach(card => {
            const name = card.dataset.name || '';
            const email = card.dataset.email || '';
            const staffId = card.dataset.id || '';
            const pos = card.dataset.pos || '';
            const dept = card.dataset.dept || '';

            const match = !val || name.includes(val) || email.includes(val) || staffId.includes(val) || pos.includes(val) || dept.includes(val);
            card.style.display = match ? 'flex' : 'none';
        });
    });
}

if (clearSearchBtn) {
    clearSearchBtn.addEventListener('click', function() {
        if (searchInput) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.focus();
        }
    });
}

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
    document.getElementById('add-staff-modal').classList.add('open');
}
function closeAddStaffModal() {
    document.getElementById('add-staff-modal').classList.remove('open');
}

// Module toggle for add form
function toggleAddModule(moduleKey, enable) {
    const card = document.getElementById('add-card-' + moduleKey);
    const row = card?.querySelector('.add-actions-row');
    if (row) row.classList.toggle('hidden', !enable);
    if (card) {
        card.style.borderColor = enable ? '#6366f1' : '';
        card.style.background = enable ? '#f5f7ff' : '';
    }
}
document.querySelectorAll('.add-mod-cb').forEach(cb => {
    cb.addEventListener('change', function() {
        toggleAddModule(this.dataset.module, this.checked);
    });
});

// Add preset buttons
document.querySelectorAll('.add-preset-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const pKey = this.dataset.preset;
        const preset = PRESETS_DATA[pKey];
        if (!preset) return;
        if (preset.position) document.getElementById('add-position').value = preset.position;
        if (preset.department) document.getElementById('add-department').value = preset.department;
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
        document.querySelectorAll('#add-preset-btns .preset-btn').forEach(b => b.classList.remove('selected'));
        this.classList.add('selected');
    });
});

document.getElementById('add-clear-btn')?.addEventListener('click', function() {
    MODULE_KEYS.forEach(m => {
        const cb = document.querySelector('#add-staff-modal .add-mod-cb[data-module="' + m + '"]');
        if (cb) {
            cb.checked = false;
            toggleAddModule(m, false);
        }
    });
    document.querySelectorAll('#add-preset-btns .preset-btn').forEach(b => b.classList.remove('selected'));
});

// Permissions modal
function openPermissionsModal(userId, name, perms, staffId, position, department, accountStatus) {
    document.getElementById('perm-staff-name').textContent = name;
    document.getElementById('perm-form').action = '/staff-management/' + userId + '/permissions';
    document.getElementById('perm-staff-id').value = staffId || '';
    document.getElementById('perm-position').value = position || '';
    document.getElementById('perm-department').value = department || '';
    document.getElementById('perm-account-status').value = accountStatus || 'active';

    // Reset all
    MODULE_KEYS.forEach(m => {
        const cb = document.querySelector('#perm-modal .perm-mod-cb[data-module="' + m + '"]');
        if (cb) {
            cb.checked = false;
            togglePermModule(m, false);
        }
        document.querySelectorAll('#perm-modal .perm-act-cb[data-module="' + m + '"]').forEach(a => { a.checked = false; });
    });

    // Apply existing permissions
    perms.forEach(p => {
        const cb = document.querySelector('#perm-modal .perm-mod-cb[data-module="' + p.module + '"]');
        if (cb) {
            cb.checked = true;
            togglePermModule(p.module, true);
            (p.actions || []).forEach(action => {
                const a = document.querySelector('#perm-modal .perm-act-cb[data-module="' + p.module + '"][data-action="' + action + '"]');
                if (a) a.checked = true;
            });
        }
    });

    document.querySelectorAll('#perm-modal .preset-btn').forEach(b => b.classList.remove('selected'));
    document.getElementById('perm-modal').classList.add('open');
}

function closePermissionsModal() {
    document.getElementById('perm-modal').classList.remove('open');
}

function togglePermModule(module, show) {
    const card = document.getElementById('perm-card-' + module);
    const row = card?.querySelector('.perm-actions-row');
    if (row) row.classList.toggle('hidden', !show);
    if (card) {
        card.style.borderColor = show ? '#6366f1' : '';
        card.style.background = show ? '#f5f7ff' : '';
    }
}

document.querySelectorAll('.perm-mod-cb').forEach(cb => {
    cb.addEventListener('change', function() {
        togglePermModule(this.dataset.module, this.checked);
    });
});

// Perm preset buttons
document.querySelectorAll('.perm-preset-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const pKey = this.dataset.preset;
        const preset = PRESETS_DATA[pKey];
        if (!preset) return;
        if (preset.position) document.getElementById('perm-position').value = preset.position;
        if (preset.department) document.getElementById('perm-department').value = preset.department;
        const presetMods = preset.modules || {};
        MODULE_KEYS.forEach(m => {
            const cb = document.querySelector('#perm-modal .perm-mod-cb[data-module="' + m + '"]');
            if (!cb) return;
            const isEnabled = Boolean(presetMods[m]);
            cb.checked = isEnabled;
            togglePermModule(m, isEnabled);
            document.querySelectorAll('#perm-modal .perm-act-cb[data-module="' + m + '"]').forEach(a => {
                a.checked = isEnabled && Array.isArray(presetMods[m]) && presetMods[m].includes(a.dataset.action);
            });
        });
        document.querySelectorAll('#perm-modal .preset-btn').forEach(b => b.classList.remove('selected'));
        this.classList.add('selected');
    });
});

document.getElementById('perm-clear-btn')?.addEventListener('click', function() {
    MODULE_KEYS.forEach(m => {
        const cb = document.querySelector('#perm-modal .perm-mod-cb[data-module="' + m + '"]');
        if (cb) {
            cb.checked = false;
            togglePermModule(m, false);
        }
        document.querySelectorAll('#perm-modal .perm-act-cb[data-module="' + m + '"]').forEach(a => { a.checked = false; });
    });
    document.querySelectorAll('#perm-modal .preset-btn').forEach(b => b.classList.remove('selected'));
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
