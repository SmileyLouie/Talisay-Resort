{{-- Extends the base layout designed for unauthenticated guest sessions --}}
@extends('layouts.guest')

{{-- Sets the browser tab title specifically for this login page --}}
@section('title', 'Login - Talisay Smart Tourism')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&display=swap" rel="stylesheet">
<style>
/* ── Global Auth Styles ────────────────────────────────────────────── */
.auth-split-screen {
    display: flex;
    min-height: 100vh;
    width: 100vw;
    overflow-x: hidden;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: #061430;
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
        linear-gradient(to top, rgba(6, 20, 48, 0.94) 0%, rgba(6, 20, 48, 0.40) 50%, rgba(6, 20, 48, 0.80) 100%),
        rgba(4, 14, 38, 0.22);
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
    color: #38bdf8;
}

/* ── Right Form Panel (Clean Minimalist White Style) ────────────────── */
.auth-form-panel {
    flex: 0 0 480px;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 24px 28px;
    position: relative;
    z-index: 2;
    box-shadow: -12px 0 50px rgba(4, 14, 38, 0.3);
    overflow-y: auto;
}

.auth-form-inner {
    width: 100%;
    max-width: 380px;
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
    font-size: 23px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
    letter-spacing: -0.025em;
    margin-bottom: 4px;
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
    border-color: #0084B4;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(0, 132, 180, 0.12);
}

.auth-field-input:focus + .auth-input-icon,
.auth-input-wrap:focus-within .auth-input-icon {
    color: #0084B4;
}

.auth-input-wrap.password-wrap .auth-field-input {
    padding-right: 42px;
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
    justify-content: space-between;
    margin-bottom: 16px;
    font-size: 12.5px;
}

.auth-remember {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #64748b;
    font-weight: 500;
    cursor: pointer;
    user-select: none;
}

.auth-remember input[type="checkbox"] {
    width: 15px;
    height: 15px;
    accent-color: #0084B4;
    border-radius: 4px;
    cursor: pointer;
}

.auth-forgot {
    color: #0084B4;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.15s;
}

.auth-forgot:hover {
    color: #006b92;
    text-decoration: underline;
}

/* Solid Blue Pill Submit Button */
.auth-submit-pill {
    width: 100%;
    height: 46px;
    border-radius: 9999px;
    background: #0084B4;
    border: none;
    color: #ffffff;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.01em;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 4px 14px rgba(0, 132, 180, 0.28);
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}

.auth-submit-pill:hover {
    background: #00739e;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(0, 132, 180, 0.35);
    color: #ffffff;
}

.auth-submit-pill:active {
    transform: translateY(0);
}

/* Portal Switcher */
.portal-switch-box {
    margin-top: 14px;
    padding: 9px 14px;
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12px;
}

.portal-switch-box span {
    color: #64748b;
    font-weight: 500;
}

.portal-switch-box a {
    color: #0084B4;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: color 0.15s;
}

.portal-switch-box a:hover {
    color: #006b92;
    text-decoration: underline;
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
    height: 42px;
    border-radius: 9999px;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    color: #0f172a;
    font-size: 13.5px;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.auth-register-pill:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
    color: #0f172a;
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

    {{-- ════════════════════════════════════════════
         LEFT PANEL — Hero Background (Same as Landing)
    ════════════════════════════════════════════ --}}
    <div class="auth-hero-panel">
        {{-- High-resolution Sunset Beach Background Image with Cinematic Zoom --}}
        <div class="auth-hero-bg"></div>
        <div class="auth-hero-overlay"></div>

        {{-- Top: Navigation / Back Button --}}
        <div class="auth-hero-top">
            <a href="{{ route('home') }}" class="auth-back-btn">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Home</span>
            </a>
            <div class="auth-hero-brand-pill">
                <img src="{{ asset('images/logo.png') }}" alt="Talisay Beach Resort Logo" class="auth-hero-logo-img">
                <span>Talisay Beach Resort</span>
            </div>
        </div>

        {{-- Bottom: Resort Headline & Badges matching Landing Page --}}
        <div class="auth-hero-content">
            <div class="auth-hero-badge">
                <span class="badge-dot"></span>
                <span>Baybay City, Leyte &bull; Philippines</span>
            </div>
            
            <h1 class="auth-hero-title">
                Talisay <span>Smart Tourism</span> System
            </h1>
            
            <p class="auth-hero-subtitle">
                Your all-in-one digital gateway to discovering, booking, and experiencing the beauty of Talisay Beach Resort.
            </p>

            <div class="auth-hero-features">
                <span class="feature-tag"><i class="bi bi-camera-video"></i> 360&deg; Virtual Tour</span>
                <span class="feature-tag"><i class="bi bi-calendar2-check"></i> Instant Booking</span>
                <span class="feature-tag"><i class="bi bi-shield-check"></i> Verified Guest Access</span>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════
         RIGHT PANEL — Clean Minimalist Login Form
    ════════════════════════════════════════════ --}}
    <div class="auth-form-panel">
        <div class="auth-form-inner">

            @php
                $currentPortal = old('portal', request('portal'));
            @endphp

            {{-- Brand & Header --}}
            <div class="auth-brand-row">
                <img src="{{ asset('logo.png') }}" class="auth-brand-logo" alt="Talisay Beach Resort Logo">
                <div>
                    <div class="auth-brand-name">Talisay Beach Resort</div>
                    <div class="auth-brand-sub">Smart Tourism System</div>
                </div>
            </div>

            @if($currentPortal === 'admin')
                <h2 class="auth-form-title">Admin Log In</h2>
                <p class="auth-form-subtitle">Authorized administrator access to management controls.</p>
            @elseif($currentPortal === 'client')
                <h2 class="auth-form-title">Log In</h2>
                <p class="auth-form-subtitle">Access your bookings, reviews, and front desk services.</p>
            @else
                <h2 class="auth-form-title">Log In</h2>
                <p class="auth-form-subtitle">Enter your account credentials to access the system.</p>
            @endif

            {{-- Error Alert --}}
            @if($errors->any())
            <div class="auth-alert">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0 text-rose-500"></i>
                <span>{{ $errors->first() }}</span>
            </div>
            @endif

            {{-- Success Flash Message --}}
            @if(session('status'))
            <div class="auth-alert auth-alert-success">
                <i class="bi bi-check-circle-fill flex-shrink-0 text-emerald-500"></i>
                <span>{{ session('status') }}</span>
            </div>
            @endif

            {{-- Login Form --}}
            <form id="loginForm" action="{{ route('login.post') }}" method="POST">
                @csrf
                <input type="hidden" name="portal" value="{{ $currentPortal }}">

                {{-- Email / Username Field --}}
                <div class="auth-field-group">
                    <label class="auth-field-label" for="emailInput">
                        Email Address or Username
                    </label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-envelope auth-input-icon"></i>
                        <input
                            type="text"
                            name="email"
                            id="emailInput"
                            class="auth-field-input"
                            placeholder="Email address or username"
                            value="{{ old('email') }}"
                            required
                            autofocus
                        >
                    </div>
                </div>

                {{-- Password Field --}}
                <div class="auth-field-group">
                    <label class="auth-field-label" for="passwordField">
                        Password
                    </label>
                    <div class="auth-input-wrap password-wrap">
                        <i class="bi bi-lock auth-input-icon"></i>
                        <input
                            type="password"
                            name="password"
                            id="passwordField"
                            class="auth-field-input"
                            placeholder="Password"
                            required
                        >
                        <button type="button" class="auth-pw-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                {{-- Remember Me & Forgot Password --}}
                <div class="auth-row-between">
                    <label class="auth-remember">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="auth-forgot">Forgot password?</a>
                </div>

                {{-- Solid Ocean Blue Pill Button --}}
                <button type="submit" class="auth-submit-pill">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Log In</span>
                </button>
            </form>



            {{-- Register Prompt (Only for Client/Tourist, hidden on Admin portal) --}}
            @if($currentPortal !== 'admin')
            <div class="auth-register-footer">
                <span>Don't have an account yet?</span>
                <a href="{{ route('register') }}" class="auth-register-pill">
                    <i class="bi bi-person-plus text-sky-600"></i>
                    <span>Register as Tourist</span>
                </a>
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
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>
@endpush
@endsection

