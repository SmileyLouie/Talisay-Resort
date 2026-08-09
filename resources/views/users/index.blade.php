@extends('layouts.app')
@section('title', 'User Management - Talisay Smart Tourism')

@push('styles')
<style>
    .user-avatar { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0; }
    .role-pill { font-size: 11px; font-weight: 600; padding: 2px 10px; border-radius: 20px; }
    .stat-mini { border-radius: 12px; padding: 16px 20px; transition: transform .15s ease; }
    .stat-mini:hover { transform: translateY(-2px); }
    .user-row:hover { background: #f0f9ff; }
    .filter-bar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
</style>
@endpush

@section('content')

{{-- Page Header --}}
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">User Management</h1>
        <p class="text-gray-500 text-sm">Manage staff accounts and tourist registrations</p>
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-primary">
        <i class="bi bi-person-plus-fill me-1"></i> Add User
    </a>
</div>

{{-- Stats Summary Cards --}}
@php
    $totalUsers = $users->total();
    $adminCount = \App\Models\User::where('role','admin')->count();
    $staffCount = \App\Models\User::where('role','staff')->count();
    $touristCount = \App\Models\User::where('role','tourist')->count();
    $activeCount = \App\Models\User::where('is_active', true)->count();
@endphp
<div class="row g-3 mb-5">
    <div class="col-6 col-md-3">
        <div class="stat-mini bg-white shadow-sm border-l-4 border-sky-500">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Users</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ $totalUsers }}</h3>
            <p class="text-xs text-sky-500 mt-1"><i class="bi bi-people-fill me-1"></i>All accounts</p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-mini bg-white shadow-sm border-l-4 border-red-500">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Admins</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ $adminCount }}</h3>
            <p class="text-xs text-red-500 mt-1"><i class="bi bi-shield-fill me-1"></i>Full access</p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-mini bg-white shadow-sm border-l-4 border-teal-500">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Staff</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ $staffCount }}</h3>
            <p class="text-xs text-teal-500 mt-1"><i class="bi bi-person-badge me-1"></i>Resort staff</p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-mini bg-white shadow-sm border-l-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Tourists</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ $touristCount }}</h3>
            <p class="text-xs text-green-500 mt-1"><i class="bi bi-person-check me-1"></i>Registered guests</p>
        </div>
    </div>
</div>

{{-- Search & Filter Bar --}}
<div class="bg-white rounded-xl shadow-sm p-4 mb-4">
    <form method="GET" action="{{ route('users.index') }}" class="filter-bar">
        <div class="flex-1" style="min-width: 220px;">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="{{ request('search') }}">
            </div>
        </div>
        <select name="role" class="form-select form-select-sm" style="width:160px;" onchange="this.form.submit()">
            <option value="">All Roles</option>
            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            <option value="staff" {{ request('role') === 'staff' ? 'selected' : '' }}>Staff</option>
            <option value="tourist" {{ request('role') === 'tourist' ? 'selected' : '' }}>Tourist</option>
        </select>
        <select name="status" class="form-select form-select-sm" style="width:150px;" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Search</button>
        @if(request()->hasAny(['search','role','status']))
        <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>Clear</a>
        @endif
    </form>
</div>

{{-- Users Table --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-4 border-b flex items-center justify-between">
        <h3 class="font-semibold text-gray-700">
            <i class="bi bi-list-ul me-2 text-sky-500"></i>
            {{ $users->total() }} {{ Str::plural('User', $users->total()) }}
            @if(request()->hasAny(['search','role','status']))
            <span class="badge bg-sky-100 text-sky-700 text-xs ms-2">Filtered</span>
            @endif
        </h3>
        <span class="text-xs text-gray-400">Page {{ $users->currentPage() }} of {{ $users->lastPage() }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover text-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:40%">User</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr class="user-row">
                    <td>
                        <div class="flex items-center gap-3">
                            @if($user->avatar)
                                <img src="{{ Storage::url($user->avatar) }}" class="user-avatar object-cover" alt="{{ $user->name }}">
                            @else
                                @php
                                    $colors = ['admin'=>['bg'=>'#fee2e2','color'=>'#b91c1c'], 'staff'=>['bg'=>'#ccfbf1','color'=>'#0f766e'], 'tourist'=>['bg'=>'#dbeafe','color'=>'#1d4ed8']];
                                    $c = $colors[$user->role] ?? ['bg'=>'#e2e8f0','color'=>'#475569'];
                                @endphp
                                <div class="user-avatar" style="background:{{ $c['bg'] }};color:{{ $c['color'] }};">
                                    {{ initials($user->name) }}
                                </div>
                            @endif
                            <div>
                                <p class="font-semibold text-gray-800 mb-0">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500 mb-0">{{ $user->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="text-gray-600">{{ $user->phone ?? '—' }}</td>
                    <td>
                        @php
                            $roleStyles = ['admin'=>'background:#fee2e2;color:#b91c1c;', 'staff'=>'background:#ccfbf1;color:#0f766e;', 'tourist'=>'background:#dbeafe;color:#1d4ed8;'];
                        @endphp
                        <span class="role-pill" style="{{ $roleStyles[$user->role] ?? '' }}">
                            <i class="bi bi-{{ $user->role === 'admin' ? 'shield-fill' : ($user->role === 'staff' ? 'person-badge-fill' : 'person-fill') }} me-1"></i>
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-{{ $user->is_active ? 'success' : 'secondary' }}">
                            <i class="bi bi-{{ $user->is_active ? 'check-circle' : 'dash-circle' }} me-1"></i>
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-gray-500 text-xs">{{ $user->created_at->format('M d, Y') }}</td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-primary" title="Edit User">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                            @if($user->id !== auth()->id())
                            <button type="button" class="btn btn-outline-danger" title="Delete User"
                                    onclick="confirmDelete({{ $user->id }}, '{{ addslashes($user->name) }}')">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                            @else
                            <button class="btn btn-outline-secondary" disabled title="Cannot delete yourself">
                                <i class="bi bi-lock-fill"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-10">
                        <i class="bi bi-people text-4xl text-gray-300 block mb-2"></i>
                        <p class="text-gray-400">No users found matching your filters.</p>
                        <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary mt-2">Clear filters</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t">{{ $users->withQueryString()->links() }}</div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-xl">
            <div class="modal-body text-center p-5">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="bi bi-exclamation-triangle-fill text-red-500 text-2xl"></i>
                </div>
                <h5 class="font-bold text-gray-800 mb-2">Delete User?</h5>
                <p class="text-gray-500 text-sm mb-0">Are you sure you want to delete <strong id="deleteUserName"></strong>? This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center gap-2 pb-4">
                <button class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteUserForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="bi bi-trash me-1"></i>Yes, Delete
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