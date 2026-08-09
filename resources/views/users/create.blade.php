@extends('layouts.app')
@section('title', 'Create User - Talisay Smart Tourism')

@section('content')
<div class="mb-5">
    <a href="{{ route('users.index') }}" class="text-sky-600 hover:underline text-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to User Management
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        {{-- Card Header --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-5 bg-gradient-to-r from-sky-500 to-teal-500 text-white">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="bi bi-person-plus-fill text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold mb-0">Create New User</h2>
                        <p class="text-white/70 text-sm mb-0">Add a new admin, staff, or tourist account</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                {{-- Validation Errors --}}
                @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2 ps-3">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                <form method="POST" action="{{ route('users.store') }}" id="createUserForm">
                    @csrf

                    {{-- Basic Information --}}
                    <div class="mb-4">
                        <h6 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">
                            <i class="bi bi-person-fill me-1"></i>Basic Information
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-sm">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" placeholder="e.g. Maria Santos" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-sm">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}" placeholder="user@example.com" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-sm">Phone Number</label>
                                <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone') }}" placeholder="+63-9XX-XXX-XXXX">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-sm">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select @error('role') is-invalid @enderror" required id="roleSelect">
                                    <option value="">— Select Role —</option>
                                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>🛡️ Admin</option>
                                    <option value="staff" {{ old('role') === 'staff' ? 'selected' : '' }}>🏷️ Staff</option>
                                    <option value="tourist" {{ old('role') === 'tourist' ? 'selected' : '' }}>🏖️ Tourist</option>
                                </select>
                                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div id="roleHint" class="text-xs text-gray-400 mt-1"></div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    {{-- Password --}}
                    <div class="mb-4">
                        <h6 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">
                            <i class="bi bi-lock-fill me-1"></i>Set Password
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-sm">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror"
                                           placeholder="Min. 8 characters" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('password','eyeIcon1')">
                                        <i class="bi bi-eye" id="eyeIcon1"></i>
                                    </button>
                                </div>
                                @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                {{-- Strength Bar --}}
                                <div class="mt-2" id="strengthBar" style="display:none;">
                                    <div class="progress" style="height:4px;">
                                        <div class="progress-bar" id="strengthProgress" role="progressbar" style="width:0%"></div>
                                    </div>
                                    <p class="text-xs mt-1" id="strengthLabel"></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-sm">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="password_confirmation" id="password_confirmation"
                                           class="form-control" placeholder="Repeat password" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('password_confirmation','eyeIcon2')">
                                        <i class="bi bi-eye" id="eyeIcon2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-between pt-2">
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-5">
                            <i class="bi bi-person-check-fill me-2"></i>Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const roleHints = {
    admin: '🛡️ Full system access — can manage all features, users, and settings.',
    staff: '🏷️ Limited access — can manage bookings, emergencies, and payments.',
    tourist: '🏖️ Guest account — can book packages and use the tourist app.'
};

document.getElementById('roleSelect').addEventListener('change', function() {
    const hint = document.getElementById('roleHint');
    hint.textContent = roleHints[this.value] || '';
});

function togglePwd(fieldId, iconId) {
    const f = document.getElementById(fieldId);
    const i = document.getElementById(iconId);
    if (f.type === 'password') { f.type = 'text'; i.className = 'bi bi-eye-slash'; }
    else { f.type = 'password'; i.className = 'bi bi-eye'; }
}

document.getElementById('password').addEventListener('input', function() {
    const val = this.value;
    const bar = document.getElementById('strengthBar');
    const prog = document.getElementById('strengthProgress');
    const label = document.getElementById('strengthLabel');
    bar.style.display = val.length ? 'block' : 'none';
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const levels = [
        { pct: '25%', cls: 'bg-danger', txt: '⚠️ Weak' },
        { pct: '50%', cls: 'bg-warning', txt: '🔶 Fair' },
        { pct: '75%', cls: 'bg-info', txt: '🔷 Good' },
        { pct: '100%', cls: 'bg-success', txt: '✅ Strong' },
    ];
    const lvl = levels[score - 1] || levels[0];
    prog.style.width = lvl.pct;
    prog.className = `progress-bar ${lvl.cls}`;
    label.textContent = lvl.txt;
    label.className = `text-xs mt-1 ${lvl.cls.replace('bg-','text-')}`;
});
</script>
@endpush