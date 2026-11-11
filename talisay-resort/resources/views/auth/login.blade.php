{{-- Extends the base layout designed for unauthenticated guest sessions --}}
@extends('layouts.guest')

@php
    $currentPortal = old('portal', request('portal', 'client'));
    if (!in_array($currentPortal, ['admin', 'client'], true)) {
        $currentPortal = 'client';
    }
    $isStaffPortal = $currentPortal === 'admin';
@endphp

{{-- Sets the browser tab title specifically for this login page --}}
@section('title', $isStaffPortal ? 'Login - Talisay Smart Tourism' : 'Log in – Talisay Beach Resort')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
<style>
:root {
    --auth-primary: {{ $isStaffPortal ? '#0284c7' : '#0c4a6e' }};
    --auth-primary-hover: {{ $isStaffPortal ? '#0369a1' : '#082f49' }};
    --auth-primary-ring: {{ $isStaffPortal ? 'rgba(2, 132, 199, 0.12)' : 'rgba(12, 74, 110, 0.12)' }};
    --auth-accent: #f59e0b;
    --auth-font: 'Inter', 'Segoe UI', sans-serif;
}

/* ── Global Auth Styles ────────────────────────────────────────────── */
.auth-split-screen {
    display: flex;
    min-height: 100vh;
    width: 100vw;
    overflow-x: hidden;
    font-family: var(--auth-font);
    background: #082f49;
}

/* ── Left Hero Panel (Same background as Landing Page) ─────────────── */
.auth-hero-panel {
    position: relative;
    flex: 1.2;
    min-height: 100vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 30px 36px;
    z-index: 1;
}

.auth-hero-bg {
    position: absolute;
    inset: 0;
    background-image: url('/images/hero-landing.jpg');
    background-size: cover;
    background-position: center 40%;
    transform: scale(1.04);
    animation: slowZoom 18s ease-in-out infinite alternate;
    z-index: 0;
}

@keyframes slowZoom {
    from { transform: scale(1.04); }
    to   { transform: scale(1.10); }
}

.auth-hero-overlay {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(to top, rgba(8, 47, 73, 0.94) 0%, rgba(8, 47, 73, 0.38) 50%, rgba(8, 47, 73, 0.78) 100%),
        rgba(8, 47, 73, 0.18);
    z-index: 1;
}

.auth-hero-top {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.auth-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    color: #ffffff;
    font-size: 13px;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 9999px;
    text-decoration: none;
    border: 1px solid rgba(255, 255, 255, 0.25);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.auth-back-btn:hover {
    background: rgba(255, 255, 255, 0.22);
    border-color: rgba(255, 255, 255, 0.45);
    color: #ffffff;
    transform: translateX(-3px);
}

.auth-hero-brand-pill {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    padding: 6px 16px 6px 12px;
    border-radius: 9999px;
    color: #ffffff;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    user-select: none;
}

.auth-hero-logo-img {
    width: 26px;
    height: 26px;
    object-fit: contain;
    background: transparent;
    border: none;
    border-radius: 0;
    padding: 0;
    box-shadow: none;
    display: block;
    filter: drop-shadow(0 1px 3px rgba(0, 0, 0, 0.5));
}

.auth-hero-content {
    position: relative;
    z-index: 2;
    max-width: 620px;
    color: #ffffff;
    padding-bottom: 6px;
}

.auth-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.10);
    border: 1px solid rgba(255, 255, 255, 0.22);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    color: #e2e8f0;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.04em;
    padding: 5px 14px;
    border-radius: 9999px;
    margin-bottom: 12px;
}

.auth-hero-badge .badge-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #f5b942;
    box-shadow: 0 0 8px #f5b942;
}

.auth-hero-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(2rem, 3.2vw, 3rem);
    font-weight: 700;
    line-height: 1.15;
    color: #ffffff;
    letter-spacing: -0.015em;
    margin-bottom: 10px;
    text-shadow: 0 2px 16px rgba(0, 0, 0, 0.4);
}

.auth-hero-title span {
    background: linear-gradient(130deg, #f5b942 0%, #ffe99a 50%, #f5b942 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.auth-hero-subtitle {
    font-size: 14.5px;
    color: rgba(255, 255, 255, 0.78);
    line-height: 1.55;
    margin-bottom: 18px;
    max-width: 520px;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
}

.auth-hero-features {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.feature-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.16);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    color: #e2e8f0;
    font-size: 11.5px;
    font-weight: 500;
    padding: 5px 12px;
    border-radius: 8px;
}

.feature-tag i {
    color: #f59e0b;
}

/* ── Right Form Panel (Clean Minimalist White Style) ────────────────── */
.auth-form-panel {
    flex: 0 0 520px;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 32px 40px;
    position: relative;
    z-index: 2;
    box-shadow: -12px 0 50px rgba(4, 14, 38, 0.3);
    overflow-y: auto;
}

.auth-form-inner {
    width: 100%;
    max-width: 400px;
    padding: 8px 0;
}

/* Header */
.auth-brand-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
}

.auth-brand-logo {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    object-fit: cover;
    border: 1.5px solid #e2e8f0;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.06);
}

.auth-brand-name {
    font-size: 13.5px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
    letter-spacing: -0.01em;
}

.auth-brand-sub {
    font-size: 10.5px;
    font-weight: 500;
    color: #64748b;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

/* Portal Indicator Pills */
.portal-badge-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 4px 12px;
    border-radius: 9999px;
    margin-bottom: 8px;
}

.portal-badge-pill.client {
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
}

.portal-badge-pill.admin {
    background: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
}

.auth-form-title {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 30px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.15;
    letter-spacing: -0.02em;
    margin-bottom: 6px;
}

.auth-form-subtitle {
    font-size: 13px;
    color: #64748b;
    line-height: 1.45;
    margin-bottom: 18px;
}

/* Alert Boxes */
.auth-alert {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 10px;
    padding: 8px 12px;
    margin-bottom: 14px;
}

.auth-alert-success {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #16a34a;
}

/* Input Fields */
.auth-field-group {
    margin-bottom: 13px;
}

.auth-field-label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 5px;
    letter-spacing: -0.005em;
}

.auth-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.auth-input-icon {
    position: absolute;
    left: 13px;
    color: #94a3b8;
    font-size: 14px;
    pointer-events: none;
    transition: color 0.2s;
}

.auth-field-input {
    width: 100%;
    height: 44px;
    padding: 0 14px 0 38px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 13.5px;
    color: #0f172a;
    background: #f8fafc;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    outline: none;
    box-sizing: border-box;
    font-family: inherit;
}

.auth-field-input:focus {
    border-color: var(--auth-primary);
    background: #ffffff;
    box-shadow: 0 0 0 3px var(--auth-primary-ring);
}

.auth-field-input:focus + .auth-input-icon,
.auth-input-wrap:focus-within .auth-input-icon {
    color: var(--auth-primary);
}

.auth-input-wrap.password-wrap .auth-field-input {
    padding-right: 42px;
}

.auth-input-wrap.password-wrap input::-ms-reveal,
.auth-input-wrap.password-wrap input::-ms-clear {
    display: none;
}

.auth-pw-toggle {
    position: absolute;
    right: 10px;
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 6px;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s;
    border-radius: 6px;
}

.auth-pw-toggle:hover {
    color: #0f172a;
}

/* Row Between */
.auth-row-between {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    margin-bottom: 16px;
    font-size: 12.5px;
}

.auth-forgot {
    color: var(--auth-primary);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.15s;
}

.auth-forgot:hover {
    color: var(--auth-primary-hover);
    text-decoration: underline;
}

/* Submit button: sand for guests, azure for staff */
.auth-submit-pill {
    width: 100%;
    height: 50px;
    border-radius: 9999px;
    background: {{ $isStaffPortal ? 'var(--auth-primary)' : 'var(--auth-accent)' }};
    border: none;
    color: #ffffff;
    font-size: 15px;
    font-weight: 700;
    letter-spacing: 0.01em;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 10px 24px {{ $isStaffPortal ? 'rgba(2, 132, 199, 0.28)' : 'rgba(245, 158, 11, 0.35)' }};
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    font-family: inherit;
}

.auth-submit-pill:hover {
    background: {{ $isStaffPortal ? 'var(--auth-primary-hover)' : '#d97706' }};
    transform: translateY(-1px);
    box-shadow: 0 14px 28px {{ $isStaffPortal ? 'rgba(2, 132, 199, 0.35)' : 'rgba(245, 158, 11, 0.4)' }};
    color: #ffffff;
}

.auth-submit-pill:active {
    transform: translateY(0);
}

.auth-submit-pill:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

/* Register Footer */
.auth-register-footer {
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid #f1f5f9;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.auth-register-footer span {
    font-size: 12.5px;
    color: #64748b;
}

/* White Pill Button with Subtle Border for Register */
.auth-register-pill {
    width: 100%;
    height: 50px;
    border-radius: 9999px;
    background: #ffffff;
    border: 2px solid #0c4a6e;
    color: #0c4a6e;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.auth-register-pill:hover {
    background: #0c4a6e;
    border-color: #0c4a6e;
    box-shadow: 0 8px 18px rgba(12, 74, 110, 0.18);
    color: #ffffff;
    transform: translateY(-1px);
}

/* Responsive Styles */
@media (max-width: 960px) {
    .auth-split-screen {
        flex-direction: column;
    }
    .auth-hero-panel {
        min-height: 280px;
        flex: none;
        padding: 24px;
    }
    .auth-hero-subtitle,
    .auth-hero-features {
        display: none;
    }
    .auth-hero-title {
        font-size: 1.8rem;
    }
    .auth-form-panel {
        flex: none;
        min-height: auto;
        padding: 36px 20px 48px;
        box-shadow: none;
    }
}
</style>
@endpush

@section('content')

<div class="auth-split-screen">

    @include('auth.partials.auth-hero')

    {{-- ════════════════════════════════════════════
         RIGHT PANEL — Clean Minimalist Login Form
    ════════════════════════════════════════════ --}}
    <div class="auth-form-panel">
        <div class="auth-form-inner">

            {{-- Brand & Header --}}
            <div class="auth-brand-row">
                <img src="{{ asset('images/logo.png') }}" class="auth-brand-logo" alt="Talisay Beach Resort Logo">
                <div>
                    <div class="auth-brand-name">Talisay Beach Resort</div>
                    <div class="auth-brand-sub">Smart Tourism System</div>
                </div>
            </div>

            @if($isStaffPortal)
                <h2 class="auth-form-title">{{ __('Login') }}</h2>
                <p class="auth-form-subtitle">{{ __('For administrators and front-desk staff only.') }}</p>
            @else
                <h2 class="auth-form-title">{{ __('Log in') }}</h2>
                <p class="auth-form-subtitle">Use your guest account to book a room or cottage, or create one in under a minute.</p>
            @endif

            {{-- Error Alert --}}
            @if($errors->any())
            <div class="auth-alert" role="alert" id="loginErrorAlert">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0 text-rose-500" aria-hidden="true"></i>
                <span>{{ $errors->first('email') ?: $errors->first() }}</span>
            </div>
            @endif

            {{-- Success Flash Message --}}
            @if(session('status'))
            <div class="auth-alert auth-alert-success" role="status">
                <i class="bi bi-check-circle-fill flex-shrink-0 text-emerald-500" aria-hidden="true"></i>
                <span>{{ session('status') }}</span>
            </div>
            @endif

            {{-- Login Form --}}
            <form id="loginForm" action="{{ route('login.post') }}" method="POST" @if($isStaffPortal) data-no-required-stars @endif>
                @csrf
                <input type="hidden" name="portal" value="{{ $currentPortal }}">
                <input type="hidden" name="redirect" value="{{ old('redirect', request('redirect')) }}">

                {{-- Email / Username Field --}}
                <div class="auth-field-group">
                    <label class="auth-field-label" for="emailInput">
                        {{ $isStaffPortal ? __('Email') : __('Email Address') }}
                    </label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-envelope auth-input-icon" aria-hidden="true"></i>
                        <input
                            type="text"
                            name="email"
                            id="emailInput"
                            class="auth-field-input"
                            placeholder="{{ $isStaffPortal ? 'Email' : 'Email address' }}"
                            value="{{ old('email') }}"
                            autocomplete="username"
                            @if($errors->has('email')) aria-invalid="true" aria-describedby="loginErrorAlert" @endif
                            required
                            autofocus
                        >
                    </div>
                </div>

                {{-- Password Field --}}
                <div class="auth-field-group">
                    <label class="auth-field-label" for="passwordField">
                        {{ __('Password') }}
                    </label>
                    <div class="auth-input-wrap password-wrap">
                        <i class="bi bi-lock auth-input-icon" aria-hidden="true"></i>
                        <input
                            type="password"
                            name="password"
                            id="passwordField"
                            class="auth-field-input"
                            placeholder="Password"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="auth-pw-toggle" onclick="togglePassword()" aria-label="Toggle password visibility" aria-controls="passwordField" aria-pressed="false" id="togglePasswordBtn">
                            <i class="bi bi-eye" id="toggleIcon" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="auth-row-between">
                    <a href="{{ route('password.request') }}" class="auth-forgot">{{ __('Forgot password?') }}</a>
                </div>

                {{-- Solid Ocean Blue Pill Button --}}
                <button type="submit" class="auth-submit-pill" id="loginSubmitBtn">
                    <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                    <span>{{ __('Log In') }}</span>
                </button>
            </form>

            @if(!$isStaffPortal)
            <div class="auth-register-footer">
                <span>New to the resort?</span>
                <a href="{{ route('register') }}" class="auth-register-pill">
                    <i class="bi bi-person-plus"></i>
                    <span>Create an account</span>
                </a>
                @include('auth.partials.social-buttons', ['socialFrom' => 'login', 'socialDivider' => 'before'])
            </div>
            @endif

        </div>
    </div>

</div>

@push('scripts')
<script>
function togglePassword() {
    const field = document.getElementById('passwordField');
    const icon  = document.getElementById('toggleIcon');
    const btn   = document.getElementById('togglePasswordBtn');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
        if (btn) btn.setAttribute('aria-pressed', 'true');
    } else {
        field.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
        if (btn) btn.setAttribute('aria-pressed', 'false');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const form      = document.getElementById('loginForm');
    const submitBtn = document.getElementById('loginSubmitBtn');
    if (form && submitBtn) {
        form.addEventListener('submit', function () {
            submitBtn.disabled = true;
            submitBtn.setAttribute('aria-busy', 'true');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Logging in...';
        });
    }
});
</script>
@endpush
@endsection

