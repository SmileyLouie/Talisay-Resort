@extends('layouts.app')

@section('title', 'User Management - Talisay Smart Tourism')

@section('content')

{{-- Header --}}
<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100/80 text-sky-800 border border-sky-200">
                <i class="bi bi-people-fill text-[10px] text-sky-600"></i>
                Accounts
            </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-1">
            User Accounts Management
        </h1>
        <p class="text-sm text-slate-500 mb-0">
            Manage system administrators, front desk staff accounts, and registered resort guests.
        </p>
    </div>

    <a href="{{ route('users.create') }}" class="btn-ocean">
        <i class="bi bi-person-plus-fill"></i>
        <span>Add New Account</span>
    </a>
</div>

{{-- KPI Summary Stats --}}
@php
    $totalUsers = $users->total();
    $adminCount = \App\Models\User::where('role','admin')->count();
    $staffCount = \App\Models\User::where('role','staff')->count();
    $touristCount = \App\Models\User::where('role','tourist')->count();
@endphp
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
    
    {{-- Total Users --}}
    <div class="stat-card-clean flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 border border-sky-100 flex items-center justify-center text-xl font-bold flex-shrink-0">
            <i class="bi bi-people-fill"></i>
        </div>
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">Total Accounts</p>
            <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mb-0">{{ $totalUsers }}</h3>
        </div>
    </div>

    {{-- Admins --}}
    <div class="stat-card-clean flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-xl font-bold flex-shrink-0">
            <i class="bi bi-shield-fill"></i>
        </div>
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">Admins</p>
            <h3 class="text-2xl sm:text-3xl font-extrabold text-rose-600 mb-0">{{ $adminCount }}</h3>
        </div>
    </div>

    {{-- Staff --}}
    <div class="stat-card-clean flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center text-xl font-bold flex-shrink-0">
            <i class="bi bi-person-badge-fill"></i>
        </div>
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">Resort Staff</p>
            <h3 class="text-2xl sm:text-3xl font-extrabold text-teal-600 mb-0">{{ $staffCount }}</h3>
        </div>
    </div>

    {{-- Tourists --}}
    <div class="stat-card-clean flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xl font-bold flex-shrink-0">
            <i class="bi bi-person-check-fill"></i>
        </div>
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">Guests / Tourists</p>
            <h3 class="text-2xl sm:text-3xl font-extrabold text-emerald-600 mb-0">{{ $touristCount }}</h3>
        </div>
    </div>
</div>

{{-- Filters Card --}}
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 sm:p-5 mb-6">
    <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
        <div class="sm:col-span-6">
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Search Users</label>
            <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" class="w-full pl-9 form-control-clean text-xs" placeholder="Search by name, email, or phone..." value="{{ request('search') }}">
            </div>
        </div>

        <div class="sm:col-span-4">
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Role</label>
            <select name="role" class="w-full form-select-clean text-xs font-medium" onchange="this.form.submit()">
                <option value="">All Roles</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="staff" {{ request('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                <option value="tourist" {{ request('role') === 'tourist' ? 'selected' : '' }}>Tourist</option>
            </select>
        </div>

        <div class="sm:col-span-2 flex gap-2">
            <button type="submit" class="btn-ocean text-xs flex-1 justify-center">
                Filter
            </button>
            @if(request()->hasAny(['search','role']))
            <a href="{{ route('users.index') }}" class="btn-secondary-clean text-xs">
                Clear
            </a>
            @endif
        </div>
    </form>
</div>

{{-- Users Table Card --}}
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table-clean">
            <thead>
                <tr>
                    <th>Account Holder</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Date Registered</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            @if($user->avatar)
                                <img src="{{ Storage::url($user->avatar) }}" class="w-9 h-9 rounded-xl object-cover border border-slate-200" alt="{{ $user->name }}">
                            @else
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-sky-600 to-sky-400 text-white font-black flex items-center justify-center text-xs shadow-sm">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <div class="font-bold text-slate-900 text-xs">{{ $user->name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>

                    <td>
                        <span class="text-xs font-medium text-slate-600">{{ $user->phone ?? '—' }}</span>
                    </td>

                    <td>
                        @php
                            $roleBadges = [
                                'admin'   => 'bg-rose-50 text-rose-700 border-rose-200',
                                'staff'   => 'bg-teal-50 text-teal-700 border-teal-200',
                                'tourist' => 'bg-sky-50 text-sky-700 border-sky-200',
                            ];
                        @endphp
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border uppercase tracking-wider {{ $roleBadges[$user->role] ?? 'bg-slate-100 text-slate-600 border-slate-200' }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>

                    <td>
                        <span class="text-xs text-slate-500 font-medium">{{ $user->created_at->format('M d, Y') }}</span>
                    </td>

                    <td class="text-end">
                        <div class="flex items-center justify-end gap-1.5">
                            @if($user->isStaff())
                            <a href="{{ route('tasks.staff', ['tab' => 'rbac', 'search' => $user->email]) }}" class="btn-secondary-clean text-xs py-1 px-2.5 text-indigo-700 bg-indigo-50 border-indigo-200 hover:bg-indigo-100" title="Manage Staff Roles & Permissions">
                                <i class="bi bi-shield-lock-fill"></i>
                                <span>RBAC</span>
                            </a>
                            @endif
                            <a href="{{ route('users.edit', $user) }}" class="btn-secondary-clean text-xs py-1 px-2.5" title="Edit User">
                                <i class="bi bi-pencil-fill text-slate-500"></i>
                                <span>Edit</span>
                            </a>
                            @if($user->id !== auth()->id())
                            <button type="button" class="p-1.5 rounded-xl text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-200 transition" title="Delete User"
                                    onclick="confirmDelete({{ $user->id }}, '{{ addslashes($user->name) }}')">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-12 text-slate-400">
                        <i class="bi bi-people text-3xl block mb-2 text-slate-300"></i>
                        <p class="text-xs font-semibold mb-0">No users found matching your filters.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex justify-end">
        {{ $users->withQueryString()->links() }}
    </div>
    @endif
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden p-6 text-center">
            <div class="w-14 h-14 bg-rose-50 border border-rose-200 text-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <h5 class="font-extrabold text-slate-900 text-base mb-1">Delete User Account?</h5>
            <p class="text-xs text-slate-500 mb-6">Are you sure you want to remove <strong id="deleteUserName" class="text-slate-800"></strong>? This action cannot be reversed.</p>
            <div class="flex items-center justify-center gap-2">
                <button class="btn-secondary-clean text-xs px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteUserForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm rounded-xl text-xs font-bold px-4 py-2">
                        Yes, Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function confirmDelete(userId, userName) {
    document.getElementById('deleteUserName').textContent = userName;
    document.getElementById('deleteUserForm').action = `/users/${userId}`;
    new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
}
</script>
@endpush