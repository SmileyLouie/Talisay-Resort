@extends('layouts.app')
@section('title', 'Create User - Talisay Smart Tourism')

@push('styles')
<style>
/* ── Page wrapper ──────────────────────────────────────────── */
.cu-page {
    max-width: 780px;
    margin: 0 auto;
    padding: 0 0 48px;
}

/* ── Breadcrumb row ────────────────────────────────────────── */
.cu-breadcrumb {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: #64748b;
    margin-bottom: 22px;
}
.cu-breadcrumb a {
    color: #0084B4;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: color 0.15s;
}
.cu-breadcrumb a:hover { color: #006b92; }
.cu-breadcrumb .sep { color: #cbd5e1; font-size: 15px; }

/* ── Card ──────────────────────────────────────────────────── */
.cu-card {
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.07), 0 1px 4px rgba(0,0,0,0.04);
    overflow: hidden;
    border: 1px solid #f1f5f9;
}

/* ── Card Header ───────────────────────────────────────────── */
.cu-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #0084B4 100%);
    padding: 28px 32px;
    display: flex;
    align-items: center;
    gap: 18px;
    position: relative;
    overflow: hidden;
}
.cu-header::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(255,255,255,0.05);
}
.cu-header::after {
    content: '';
    position: absolute;
    bottom: -60px; right: 60px;
    width: 140px; height: 140px;
    border-radius: 50%;
    background: rgba(255,255,255,0.04);
}
.cu-header-icon {
    width: 54px;
    height: 54px;
    border-radius: 14px;
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.20);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #ffffff;
    flex-shrink: 0;
    position: relative;
    z-index: 1;
}
.cu-header-text { position: relative; z-index: 1; }
.cu-header-title {
    font-size: 20px;
    font-weight: 800;
    color: #ffffff;
    margin: 0 0 3px;
    letter-spacing: -0.02em;
}
.cu-header-sub {
    font-size: 13px;
    color: rgba(255,255,255,0.65);
    margin: 0;
}

/* ── Card Body ─────────────────────────────────────────────── */
.cu-body {
    padding: 32px 32px 28px;
}

/* ── Error Alert ───────────────────────────────────────────── */
.cu-alert-error {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 24px;
    font-size: 13px;
    color: #b91c1c;
}
.cu-alert-error i { font-size: 16px; color: #ef4444; margin-top: 1px; flex-shrink: 0; }

/* ── Section Labels ────────────────────────────────────────── */
.cu-section-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    font-weight: 800;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 18px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
}
.cu-section-label i { font-size: 13px; color: #0084B4; }

/* ── Form Grid ─────────────────────────────────────────────── */
.cu-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}
.cu-grid.full { grid-template-columns: 1fr; }

/* ── Field Groups ──────────────────────────────────────────── */
.cu-field { display: flex; flex-direction: column; }
.cu-label {
    font-size: 12.5px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 7px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.cu-label .req { color: #ef4444; }

.cu-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.cu-input-icon { display: none; }
.cu-input {
    width: 100%;
    height: 46px;
    padding: 0 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    background: #f8fafc;
    font-size: 14px;
    color: #0f172a;
    font-family: inherit;
    outline: none;
    transition: all 0.2s;
}
.cu-input::placeholder { color: #94a3b8; }
.cu-input:hover { border-color: #94a3b8; }
.cu-input:focus {
    border-color: #0084B4;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(0,132,180,0.12);
}


/* password eye */
.cu-eye-btn {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    font-size: 16px;
    padding: 4px;
    display: flex;
    align-items: center;
    transition: color 0.18s;
}
.cu-eye-btn:hover { color: #0084B4; }

/* Select */
.cu-select-wrap { position: relative; }
.cu-select {
    width: 100%;
    height: 46px;
    padding: 0 36px 0 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    background: #f8fafc;
    font-size: 14px;
    color: #0f172a;
    font-family: inherit;
    outline: none;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    transition: all 0.2s;
}
.cu-select:hover { border-color: #94a3b8; }
.cu-select:focus {
    border-color: #0084B4;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(0,132,180,0.12);
}
.cu-select-arrow {
    position: absolute;
    right: 13px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: #64748b;
    font-size: 13px;
}

/* Role hint badge */
.cu-role-hint {
    display: none;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
    padding: 7px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    line-height: 1.4;
}
.cu-role-hint.show { display: flex; }
.cu-role-hint.admin-hint  { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.cu-role-hint.staff-hint  { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.cu-role-hint.tourist-hint{ background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

/* invalid field */
.cu-input.is-invalid,
.cu-select.is-invalid { border-color: #ef4444; background: #fff5f5; }
.cu-invalid-msg { font-size: 11.5px; color: #ef4444; margin-top: 5px; }

/* ── Password Strength ─────────────────────────────────────── */
.cu-strength { margin-top: 8px; display: none; }
.cu-strength-bars { display: flex; gap: 4px; margin-bottom: 5px; }
.cu-strength-bar {
    flex: 1; height: 4px; border-radius: 2px;
    background: #e2e8f0; transition: background 0.3s;
}
.s-weak   { background: #ef4444; }
.s-fair   { background: #f59e0b; }
.s-good   { background: #3b82f6; }
.s-strong { background: #10b981; }
.cu-strength-row {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
}

/* ── Divider ───────────────────────────────────────────────── */
.cu-divider {
    height: 1px;
    background: #f1f5f9;
    margin: 24px 0;
}

/* ── Action Row ────────────────────────────────────────────── */
.cu-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 8px;
}
.cu-btn-cancel {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    height: 44px;
    padding: 0 22px;
    border-radius: 9999px;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    color: #475569;
    font-size: 13.5px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    font-family: inherit;
}
.cu-btn-cancel:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
}
.cu-btn-submit {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: 44px;
    padding: 0 28px;
    border-radius: 9999px;
    background: linear-gradient(135deg, #0084B4 0%, #00a8d4 100%);
    border: none;
    color: #ffffff;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    box-shadow: 0 4px 14px rgba(0,132,180,0.30);
    transition: all 0.22s;
}
.cu-btn-submit:hover {
    background: linear-gradient(135deg, #006f99 0%, #008fb5 100%);
    box-shadow: 0 6px 20px rgba(0,132,180,0.40);
    transform: translateY(-1px);
    color: #ffffff;
}
.cu-btn-submit:active { transform: translateY(0); }

@media (max-width: 640px) {
    .cu-grid { grid-template-columns: 1fr; }
    .cu-body { padding: 22px 18px 20px; }
    .cu-header { padding: 22px 18px; }
    .cu-actions { flex-direction: column; }
    .cu-btn-cancel, .cu-btn-submit { width: 100%; justify-content: center; }
}
</style>
@endpush

@section('content')
<div class="cu-page">

    {{-- Breadcrumb --}}
    <div class="cu-breadcrumb">
        <a href="{{ route('users.index') }}">
            <i class="bi bi-arrow-left"></i> User Accounts
        </a>
        <span class="sep">/</span>
        <span>Create New User</span>
    </div>

    <div class="cu-card">

        {{-- Header --}}
        <div class="cu-header">
            <div class="cu-header-icon">
                <i class="bi bi-person-plus-fill"></i>
            </div>
            <div class="cu-header-text">
                <h1 class="cu-header-title">Create New User</h1>
                <p class="cu-header-sub">Add a new staff or tourist account to the system</p>
            </div>
        </div>

        {{-- Body --}}
        <div class="cu-body">

            {{-- Validation Errors --}}
            @if($errors->any())
            <div class="cu-alert-error">
                <i class="bi bi-exclamation-circle-fill"></i>
                <div>
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-1 ps-3" style="font-size:12.5px;">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            <form method="POST" action="{{ route('users.store') }}" id="createUserForm">
                @csrf

                {{-- ── Section 1: Basic Information ── --}}
                <div class="cu-section-label">
                    <i class="bi bi-person-fill"></i>
                    Basic Information
                </div>

                <div class="cu-grid">
                    {{-- Full Name --}}
                    <div class="cu-field">
                        <label class="cu-label">Full Name <span class="req">*</span></label>
                        <div class="cu-input-wrap">
                            <input type="text" name="name"
                                   class="cu-input @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}"
                                   placeholder="Enter full name"
                                   required autofocus>
                        </div>
                        @error('name') <span class="cu-invalid-msg">{{ $message }}</span> @enderror
                    </div>

                    {{-- Email --}}
                    <div class="cu-field">
                        <label class="cu-label">Email Address <span class="req">*</span></label>
                        <div class="cu-input-wrap">
                            <input type="email" name="email"
                                   class="cu-input @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}"
                                   placeholder="Enter email address"
                                   required>
                        </div>
                        @error('email') <span class="cu-invalid-msg">{{ $message }}</span> @enderror
                    </div>

                    {{-- Phone --}}
                    <div class="cu-field">
                        <label class="cu-label">Phone Number <span style="color:#94a3b8;font-weight:500;">(optional)</span></label>
                        <div class="cu-input-wrap">
                            <input type="tel" name="phone"
                                   class="cu-input @error('phone') is-invalid @enderror"
                                   value="{{ old('phone') }}"
                                   placeholder="e.g. 09xx-xxx-xxxx">
                        </div>
                        @error('phone') <span class="cu-invalid-msg">{{ $message }}</span> @enderror
                    </div>

                    {{-- Role --}}
                    <div class="cu-field">
                        <label class="cu-label">Role <span class="req">*</span></label>
                        <div class="cu-input-wrap cu-select-wrap">
                            <select name="role" id="roleSelect"
                                    class="cu-select @error('role') is-invalid @enderror"
                                    required>
                                <option value="">— Select Role —</option>
                                <option value="staff"   {{ old('role', 'staff') === 'staff'   ? 'selected' : '' }}>Staff</option>
                                <option value="tourist" {{ old('role') === 'tourist' ? 'selected' : '' }}>Tourist</option>
                            </select>
                            <i class="bi bi-chevron-down cu-select-arrow"></i>
                        </div>
                        <div class="text-[11.5px] text-slate-500 mt-1 flex items-center gap-1.5 font-medium">
                            <i class="bi bi-info-circle text-sky-600"></i>
                            <span>The system permits only 1 primary administrator. New accounts are created as Staff or Tourist.</span>
                        </div>
                        @error('role') <span class="cu-invalid-msg">{{ $message }}</span> @enderror
                        {{-- Role hints --}}
                        <div class="cu-role-hint staff-hint"   id="hintStaff">  <i class="bi bi-person-badge-fill"></i> Limited access — manages bookings, room &amp; cottage, and payments.</div>
                        <div class="cu-role-hint tourist-hint" id="hintTourist"><i class="bi bi-person-walking"></i> Guest account — can book accommodations and use the tourist portal.</div>
                    </div>
                </div>

                <div class="cu-divider"></div>

                {{-- ── Section 2: Password ── --}}
                <div class="cu-section-label">
                    <i class="bi bi-lock-fill"></i>
                    Set Password
                </div>

                <div class="cu-grid">
                    {{-- Password --}}
                    <div class="cu-field">
                        <label class="cu-label">Password <span class="req">*</span></label>
                        <div class="cu-input-wrap">
                            <input type="password" name="password" id="cuPassword"
                                   class="cu-input @error('password') is-invalid @enderror"
                                   placeholder="Min. 8 characters"
                                   style="padding-right:44px;"
                                   required>
                            <button type="button" class="cu-eye-btn" onclick="cuToggle('cuPassword','cuEye1')">
                                <i class="bi bi-eye" id="cuEye1"></i>
                            </button>
                        </div>
                        @error('password') <span class="cu-invalid-msg">{{ $message }}</span> @enderror
                        {{-- Strength meter --}}
                        <div class="cu-strength" id="cuStrength">
                            <div class="cu-strength-bars">
                                <div class="cu-strength-bar" id="sb1"></div>
                                <div class="cu-strength-bar" id="sb2"></div>
                                <div class="cu-strength-bar" id="sb3"></div>
                                <div class="cu-strength-bar" id="sb4"></div>
                            </div>
                            <div class="cu-strength-row">
                                <span id="cuStrengthText">Password strength</span>
                                <span id="cuStrengthHint" style="color:#94a3b8;">Min 8 chars</span>
                            </div>
                        </div>
                    </div>

                    {{-- Confirm Password --}}
                    <div class="cu-field">
                        <label class="cu-label">Confirm Password <span class="req">*</span></label>
                        <div class="cu-input-wrap">
                            <input type="password" name="password_confirmation" id="cuConfirm"
                                   class="cu-input"
                                   placeholder="Re-enter password"
                                   style="padding-right:44px;"
                                   required>
                            <button type="button" class="cu-eye-btn" onclick="cuToggle('cuConfirm','cuEye2')">
                                <i class="bi bi-eye" id="cuEye2"></i>
                            </button>
                        </div>
                        <div id="cuMatchMsg" style="font-size:11.5px;margin-top:5px;display:none;"></div>
                    </div>
                </div>

                <div class="cu-divider"></div>

                {{-- Actions --}}
                <div class="cu-actions">
                    <a href="{{ route('users.index') }}" class="cu-btn-cancel">
                        <i class="bi bi-x-lg"></i> Cancel
                    </a>
                    <button type="submit" class="cu-btn-submit" id="cuSubmitBtn">
                        <i class="bi bi-person-check-fill"></i>
                        <span>Create User</span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Role Hints ──────────────────────────────────────────
    const roleSelect = document.getElementById('roleSelect');
    const hints = {
        admin:   document.getElementById('hintAdmin'),
        staff:   document.getElementById('hintStaff'),
        tourist: document.getElementById('hintTourist'),
    };

    function updateRoleHint() {
        Object.values(hints).forEach(h => h.classList.remove('show'));
        const h = hints[roleSelect.value];
        if (h) h.classList.add('show');
    }

    roleSelect.addEventListener('change', updateRoleHint);
    updateRoleHint(); // in case old() value is pre-selected

    // ── Password Toggle ─────────────────────────────────────
    window.cuToggle = function(fieldId, iconId) {
        const f = document.getElementById(fieldId);
        const i = document.getElementById(iconId);
        if (f.type === 'password') {
            f.type = 'text';
            i.className = 'bi bi-eye-slash';
        } else {
            f.type = 'password';
            i.className = 'bi bi-eye';
        }
    };

    // ── Password Strength ───────────────────────────────────
    const pwInput   = document.getElementById('cuPassword');
    const pwConfirm = document.getElementById('cuConfirm');
    const bars      = [document.getElementById('sb1'),document.getElementById('sb2'),document.getElementById('sb3'),document.getElementById('sb4')];
    const strengthEl   = document.getElementById('cuStrength');
    const strengthText = document.getElementById('cuStrengthText');
    const strengthHint = document.getElementById('cuStrengthHint');
    const matchMsg     = document.getElementById('cuMatchMsg');

    const levels = [
        { cls: 's-weak',   label: 'Weak',      color: '#ef4444', hint: 'Add more characters' },
        { cls: 's-weak',   label: 'Weak',      color: '#ef4444', hint: 'Mix uppercase & numbers' },
        { cls: 's-fair',   label: 'Fair',       color: '#f59e0b', hint: 'Add symbols for extra security' },
        { cls: 's-good',   label: 'Good',       color: '#3b82f6', hint: 'Almost there!' },
        { cls: 's-strong', label: 'Strong',     color: '#10b981', hint: 'Excellent password!' },
    ];

    function evalStrength(v) {
        let s = 0;
        if (v.length >= 8) s++;
        if (v.length >= 12) s++;
        if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++;
        if (/[0-9]/.test(v) || /[^A-Za-z0-9]/.test(v)) s++;
        return s;
    }

    pwInput.addEventListener('input', function () {
        const v = this.value;
        if (!v) { strengthEl.style.display = 'none'; bars.forEach(b => b.className = 'cu-strength-bar'); checkMatch(); return; }
        strengthEl.style.display = 'block';
        const s = evalStrength(v);
        const lvl = levels[s];
        bars.forEach((b, i) => { b.className = 'cu-strength-bar'; if (i < s) b.classList.add(lvl.cls); });
        strengthText.textContent = lvl.label;
        strengthText.style.color = lvl.color;
        strengthHint.textContent = lvl.hint;
        checkMatch();
    });

    pwConfirm.addEventListener('input', checkMatch);

    function checkMatch() {
        const p1 = pwInput.value, p2 = pwConfirm.value;
        if (!p2) { matchMsg.style.display = 'none'; return; }
        matchMsg.style.display = 'block';
        if (p1 === p2 && p1.length >= 8) {
            matchMsg.innerHTML = '<i class="bi bi-check-circle-fill" style="color:#10b981;margin-right:4px;"></i><span style="color:#10b981;font-weight:600;">Passwords match</span>';
        } else {
            matchMsg.innerHTML = '<i class="bi bi-x-circle-fill" style="color:#ef4444;margin-right:4px;"></i><span style="color:#ef4444;font-weight:600;">Passwords do not match</span>';
        }
    }

    // ── Submit loading state ────────────────────────────────
    const form = document.getElementById('createUserForm');
    const submitBtn = document.getElementById('cuSubmitBtn');
    form.addEventListener('submit', function () {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Creating...';
    });
});
</script>
@endpush