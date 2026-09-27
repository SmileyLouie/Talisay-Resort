{{-- Extends the base layout designed for unauthenticated guest sessions --}}
@extends('layouts.guest')

{{-- Sets the browser tab title specifically for this registration page --}}
@section('title', 'Sign Up - Talisay Beach Resort')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
<style>
/* ── Reset & Page Setup ───────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; }

body {
    background: #ffffff;
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #1c1e21;
    min-height: 100vh;
    margin: 0;
    padding: 0;
    -webkit-font-smoothing: antialiased;
}

/* ── Fullscreen Centered Container ────────────────────────────── */
.fb-reg-wrapper {
    min-height: 100vh;
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    padding: 24px 20px 48px 20px;
    background: #ffffff;
}

.fb-reg-card {
    width: 100%;
    max-width: 580px;
    margin: 0 auto;
}

/* ── Top Navigation & Back Button ─────────────────────────────── */
.fb-top-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.fb-back-arrow {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #f0f2f5;
    color: #1c1e21;
    text-decoration: none;
    font-size: 18px;
    transition: all 0.2s ease;
}

.fb-back-arrow:hover {
    background: #e4e6eb;
    color: #0084B4;
    transform: translateX(-2px);
}

.fb-brand-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
    font-weight: 700;
    color: #0084B4;
    background: #e0f2fe;
    padding: 6px 14px;
    border-radius: 9999px;
    border: 1px solid #bae6fd;
}

.fb-brand-badge img {
    width: 20px;
    height: 20px;
    object-fit: contain;
}

/* ── Form Header ──────────────────────────────────────────────── */
.fb-header {
    margin-bottom: 24px;
}

.fb-brand-logo-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.fb-brand-logo {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    object-fit: cover;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.fb-brand-title {
    font-size: 14px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.01em;
    line-height: 1.2;
}

.fb-brand-subtitle {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.fb-title {
    font-size: 28px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
    letter-spacing: -0.03em;
    margin: 0 0 8px 0;
}

.fb-subtitle {
    font-size: 14px;
    color: #64748b;
    line-height: 1.5;
    margin: 0;
}

/* ── Error Banner ─────────────────────────────────────────────── */
.fb-error-box {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 20px;
    font-size: 13px;
    display: flex;
    gap: 10px;
    align-items: flex-start;
}

.fb-error-box i {
    font-size: 18px;
    color: #ef4444;
    margin-top: 1px;
    flex-shrink: 0;
}

/* ── Form Fields ──────────────────────────────────────────────── */
.fb-field-group {
    margin-bottom: 18px;
}

.fb-field-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13.5px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
    letter-spacing: -0.01em;
}

.fb-help-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    font-size: 14px;
    cursor: pointer;
    transition: color 0.18s;
}

.fb-help-icon:hover {
    color: #0084B4;
}

/* ── Inputs & Selects ─────────────────────────────────────────── */
.fb-input {
    width: 100%;
    height: 52px;
    padding: 0 16px;
    border: 1.5px solid #cbd5e1;
    border-radius: 14px;
    background: #ffffff;
    font-size: 14.5px;
    color: #0f172a;
    font-family: inherit;
    outline: none;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.fb-input::placeholder {
    color: #94a3b8;
}

.fb-input:hover {
    border-color: #94a3b8;
}

.fb-input:focus {
    border-color: #0084B4;
    box-shadow: 0 0 0 4px rgba(0, 132, 180, 0.12);
}

.fb-select-wrap {
    position: relative;
    width: 100%;
}

.fb-select {
    width: 100%;
    height: 52px;
    padding: 0 36px 0 16px;
    border: 1.5px solid #cbd5e1;
    border-radius: 14px;
    background: #ffffff;
    font-size: 14.5px;
    color: #0f172a;
    font-family: inherit;
    outline: none;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    transition: all 0.2s ease;
}

.fb-select:hover {
    border-color: #94a3b8;
}

.fb-select:focus {
    border-color: #0084B4;
    box-shadow: 0 0 0 4px rgba(0, 132, 180, 0.12);
}

.fb-select-arrow {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: #64748b;
    font-size: 14px;
}

/* ── Grids ────────────────────────────────────────────────────── */
.fb-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.fb-grid-3 {
    display: grid;
    grid-template-columns: 1.3fr 1fr 1.1fr;
    gap: 10px;
}

/* ── Gender Radio Cards (Facebook-style) ──────────────────────── */
.fb-gender-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 10px;
}

.fb-gender-card {
    position: relative;
    border: 1.5px solid #cbd5e1;
    border-radius: 14px;
    background: #ffffff;
    height: 52px;
    padding: 0 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    user-select: none;
    transition: all 0.2s ease;
}

.fb-gender-card:hover {
    border-color: #94a3b8;
    background: #f8fafc;
}

.fb-gender-card.active {
    border-color: #0084B4;
    background: #f0f9ff;
}

.fb-gender-card input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.fb-gender-text {
    font-size: 14px;
    font-weight: 600;
    color: #334155;
}

.fb-gender-card.active .fb-gender-text {
    color: #0084B4;
}

.fb-gender-circle {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 2px solid #94a3b8;
    position: relative;
    transition: all 0.2s ease;
}

.fb-gender-card.active .fb-gender-circle {
    border-color: #0084B4;
}

.fb-gender-card.active .fb-gender-circle::after {
    content: '';
    position: absolute;
    inset: 3px;
    background: #0084B4;
    border-radius: 50%;
}

/* ── Password Field with Show/Hide Toggle ─────────────────────── */
.fb-input-with-eye {
    position: relative;
    display: flex;
    align-items: center;
}

.fb-input-with-eye .fb-input {
    padding-right: 48px;
}

.fb-eye-btn {
    position: absolute;
    right: 14px;
    background: none;
    border: none;
    color: #64748b;
    font-size: 18px;
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.18s;
}

.fb-eye-btn:hover {
    color: #0084B4;
}

/* ── Password Strength Bar ────────────────────────────────────── */
.fb-pw-strength {
    margin-top: 8px;
    display: none;
}

.fb-pw-bars {
    display: flex;
    gap: 4px;
    margin-bottom: 4px;
}

.fb-pw-bar {
    flex: 1;
    height: 4px;
    border-radius: 2px;
    background: #e2e8f0;
    transition: background 0.3s ease;
}

.fb-pw-bar.pw-weak   { background: #ef4444; }
.fb-pw-bar.pw-fair   { background: #f59e0b; }
.fb-pw-bar.pw-good   { background: #3b82f6; }
.fb-pw-bar.pw-strong { background: #10b981; }

.fb-pw-status-row {
    display: flex;
    justify-content: space-between;
    font-size: 11.5px;
    font-weight: 600;
    color: #64748b;
}

/* ── Match Badge ──────────────────────────────────────────────── */
.fb-match-badge {
    position: absolute;
    right: 48px;
    font-size: 15px;
    display: none;
    align-items: center;
}

.fb-match-badge.matched {
    color: #10b981;
    display: flex;
}

.fb-match-badge.mismatch {
    color: #ef4444;
    display: flex;
}


/* ── Disclaimers & Notes ──────────────────────────────────────── */
.fb-disclaimer {
    font-size: 12px;
    color: #64748b;
    line-height: 1.55;
    margin: 8px 0 16px 0;
}

.fb-disclaimer a {
    color: #0084B4;
    text-decoration: none;
    font-weight: 600;
}

.fb-disclaimer a:hover {
    text-decoration: underline;
}

/* ── Big Rounded Submit Button ────────────────────────────────── */
.fb-btn-submit {
    width: 100%;
    height: 52px;
    border-radius: 9999px;
    background: #0084B4;
    border: none;
    color: #ffffff;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 0.01em;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 4px 16px rgba(0, 132, 180, 0.25);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    font-family: inherit;
    margin-top: 10px;
}

.fb-btn-submit:hover {
    background: #00739e;
    box-shadow: 0 6px 20px rgba(0, 132, 180, 0.35);
    transform: translateY(-1px);
    color: #ffffff;
}

.fb-btn-submit:active {
    transform: translateY(0);
}

/* ── Login Switch Footer ──────────────────────────────────────── */
.fb-footer {
    text-align: center;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.fb-footer-text {
    font-size: 13.5px;
    color: #64748b;
    margin-bottom: 12px;
}

.fb-btn-login {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 48px;
    border-radius: 9999px;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    color: #0f172a;
    font-size: 14.5px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s ease;
}

.fb-btn-login:hover {
    background: #f8fafc;
    border-color: #0084B4;
    color: #0084B4;
}

/* ── Responsive adjustments ───────────────────────────────────── */
@media (max-width: 600px) {
    .fb-reg-wrapper {
        padding: 16px 14px 36px 14px;
    }
    .fb-title {
        font-size: 24px;
    }
    .fb-grid-2,
    .fb-grid-3,
    .fb-gender-row {
        grid-template-columns: 1fr;
    }
    .fb-input,
    .fb-select,
    .fb-gender-card {
        height: 48px;
    }
}
</style>
@endpush

@section('content')
<div class="fb-reg-wrapper">
    <div class="fb-reg-card">

        {{-- Top Navigation: Back Button & Resort Badge --}}
        <div class="fb-top-nav">
            <a href="{{ route('home') }}" class="fb-back-arrow" title="Back to Home">
                <i class="bi bi-chevron-left"></i>
            </a>
            <div class="fb-brand-badge">
                <img src="{{ asset('images/logo.png') }}" alt="Talisay Beach Resort">
                <span>Talisay Beach Resort</span>
            </div>
        </div>

        {{-- Header Section --}}
        <div class="fb-header">
            <div class="fb-brand-logo-row">
                <img src="{{ asset('images/logo.png') }}" alt="Talisay Logo" class="fb-brand-logo">
                <div>
                    <div class="fb-brand-title">Talisay Beach Resort</div>
                    <div class="fb-brand-subtitle">Smart Tourism System</div>
                </div>
            </div>
            <h1 class="fb-title">Get started on Talisay Beach Resort</h1>
            <p class="fb-subtitle">An account lets you reserve cottages, track bookings, and explore 360° virtual tours easily and securely.</p>
        </div>

        {{-- Error Alerts --}}
        @if($errors->any())
        <div class="fb-error-box">
            <i class="bi bi-exclamation-circle-fill"></i>
            <div>
                <strong>Please correct the following:</strong>
                <ul class="mb-0 ps-3 mt-1" style="font-size: 12.5px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        {{-- Registration Form --}}
        <form action="{{ route('register.post') }}" method="POST" enctype="multipart/form-data" id="fbRegisterForm">
            @csrf

            {{-- Hidden consolidated name field synced via JS --}}
            <input type="hidden" name="name" id="hiddenFullName" value="{{ old('name') }}">

            {{-- 1. Name: First name & Last name side-by-side --}}
            <div class="fb-field-group">
                <label class="fb-field-label">Name</label>
                <div class="fb-grid-2">
                    <div>
                        <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            class="fb-input"
                            placeholder="First name"
                            value="{{ old('first_name') }}"
                            required
                            autofocus
                        >
                    </div>
                    <div>
                        <input
                            type="text"
                            id="last_name"
                            name="last_name"
                            class="fb-input"
                            placeholder="Last name"
                            value="{{ old('last_name') }}"
                            required
                        >
                    </div>
                </div>
            </div>

            {{-- 2. Birthday: Month, Day, Year Selectors --}}
            <div class="fb-field-group">
                <label class="fb-field-label">
                    Birthday
                    <span class="fb-help-icon" title="Providing your birthday helps verify age requirements and offer special resort birthday perks!">
                        <i class="bi bi-question-circle"></i>
                    </span>
                </label>
                <div class="fb-grid-3">
                    <div class="fb-select-wrap">
                        <select id="birth_month" name="birth_month" class="fb-select">
                            <option value="" disabled selected>Month</option>
                            <option value="1">Jan</option>
                            <option value="2">Feb</option>
                            <option value="3">Mar</option>
                            <option value="4">Apr</option>
                            <option value="5">May</option>
                            <option value="6">Jun</option>
                            <option value="7">Jul</option>
                            <option value="8">Aug</option>
                            <option value="9">Sep</option>
                            <option value="10">Oct</option>
                            <option value="11">Nov</option>
                            <option value="12">Dec</option>
                        </select>
                        <i class="bi bi-chevron-down fb-select-arrow"></i>
                    </div>

                    <div class="fb-select-wrap">
                        <select id="birth_day" name="birth_day" class="fb-select">
                            <option value="" disabled selected>Day</option>
                            @for($d = 1; $d <= 31; $d++)
                                <option value="{{ $d }}">{{ $d }}</option>
                            @endfor
                        </select>
                        <i class="bi bi-chevron-down fb-select-arrow"></i>
                    </div>

                    <div class="fb-select-wrap">
                        <select id="birth_year" name="birth_year" class="fb-select">
                            <option value="" disabled selected>Year</option>
                            @php $currentYear = (int) date('Y'); @endphp
                            @for($y = $currentYear - 12; $y >= $currentYear - 95; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                        <i class="bi bi-chevron-down fb-select-arrow"></i>
                    </div>
                </div>
            </div>

            {{-- 3. Gender --}}
            <div class="fb-field-group">
                <label class="fb-field-label">
                    Gender
                    <span class="fb-help-icon" title="Used for demographic reporting and guest accommodation service.">
                        <i class="bi bi-question-circle"></i>
                    </span>
                </label>
                <div class="fb-gender-row">
                    <label class="fb-gender-card" id="genderFemale">
                        <span class="fb-gender-text">Female</span>
                        <span class="fb-gender-circle"></span>
                        <input type="radio" name="gender" value="female">
                    </label>
                    <label class="fb-gender-card" id="genderMale">
                        <span class="fb-gender-text">Male</span>
                        <span class="fb-gender-circle"></span>
                        <input type="radio" name="gender" value="male">
                    </label>
                    <label class="fb-gender-card" id="genderOther">
                        <span class="fb-gender-text">Custom</span>
                        <span class="fb-gender-circle"></span>
                        <input type="radio" name="gender" value="other">
                    </label>
                </div>
            </div>

            {{-- 4. Mobile number or Email --}}
            <div class="fb-field-group">
                <label class="fb-field-label" for="email">Email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="fb-input"
                    placeholder="Email address"
                    value="{{ old('email') }}"
                    required
                >
            </div>

            <div class="fb-field-group">
                <label class="fb-field-label" for="phone">Mobile number</label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    class="fb-input"
                    placeholder="Mobile number (09xx-xxx-xxxx)"
                    value="{{ old('phone') }}"
                >
                <p class="fb-disclaimer" style="margin-top: 6px; margin-bottom: 0;">
                    You may receive SMS updates about your cottage bookings and check-in confirmation.
                </p>
            </div>

            {{-- 5. Password --}}
            <div class="fb-field-group">
                <label class="fb-field-label" for="password">Password</label>
                <div class="fb-input-with-eye">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="fb-input"
                        placeholder="New password (min. 8 characters)"
                        required
                    >
                    <button type="button" class="fb-eye-btn" id="togglePasswordBtn" title="Show or hide password">
                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>
                {{-- Strength meter --}}
                <div class="fb-pw-strength" id="pwMeter">
                    <div class="fb-pw-bars">
                        <div class="fb-pw-bar" id="pwBar1"></div>
                        <div class="fb-pw-bar" id="pwBar2"></div>
                        <div class="fb-pw-bar" id="pwBar3"></div>
                        <div class="fb-pw-bar" id="pwBar4"></div>
                    </div>
                    <div class="fb-pw-status-row">
                        <span id="pwStatusText">Password strength</span>
                        <span id="pwHintText" style="color: #94a3b8;">Min 8 chars</span>
                    </div>
                </div>
            </div>

            {{-- 6. Confirm Password --}}
            <div class="fb-field-group">
                <label class="fb-field-label" for="password_confirmation">Confirm Password</label>
                <div class="fb-input-with-eye">
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        class="fb-input"
                        placeholder="Re-type password"
                        required
                    >
                    <span class="fb-match-badge" id="matchBadge">
                        <i class="bi bi-check-circle-fill"></i>
                    </span>
                    <button type="button" class="fb-eye-btn" id="toggleConfirmBtn" title="Show or hide password">
                        <i class="bi bi-eye" id="toggleConfirmIcon"></i>
                    </button>
                </div>
            </div>



            {{-- Explanatory notice & Terms --}}
            <p class="fb-disclaimer">
                People who use our service may have uploaded your contact information to Talisay Beach Resort.
                By clicking <strong>Submit</strong>, you agree to our
                <a href="{{ route('home') }}#rules">Terms of Service</a> and
                <a href="{{ route('home') }}#privacy">Privacy Policy</a>.
            </p>

            {{-- Submit Button --}}
            <button type="submit" class="fb-btn-submit" id="submitRegisterBtn">
                <span>Submit</span>
            </button>
        </form>

        {{-- Footer: Switch to Login --}}
        <div class="fb-footer">
            <p class="fb-footer-text">Already have an account?</p>
            <a href="{{ route('login', ['portal' => 'client']) }}" class="fb-btn-login">
                Log in
            </a>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ── 1. Name Synchronization ──────────────────────────────
    const firstNameInput = document.getElementById('first_name');
    const lastNameInput  = document.getElementById('last_name');
    const hiddenName     = document.getElementById('hiddenFullName');

    function syncFullName() {
        const first = (firstNameInput ? firstNameInput.value : '').trim();
        const last  = (lastNameInput ? lastNameInput.value : '').trim();
        if (hiddenName) {
            hiddenName.value = (first + ' ' + last).trim();
        }
    }

    if (firstNameInput) firstNameInput.addEventListener('input', syncFullName);
    if (lastNameInput)  lastNameInput.addEventListener('input', syncFullName);

    // Split old name if flashed back
    if (hiddenName && hiddenName.value && (!firstNameInput.value && !lastNameInput.value)) {
        const parts = hiddenName.value.split(' ');
        if (parts.length > 0) firstNameInput.value = parts[0];
        if (parts.length > 1) lastNameInput.value = parts.slice(1).join(' ');
    }

    // ── 2. Gender Radio Cards ────────────────────────────────
    const genderCards = document.querySelectorAll('.fb-gender-card');
    genderCards.forEach(card => {
        card.addEventListener('click', function () {
            genderCards.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });

    // ── 3. Password Visibility Toggles ───────────────────────
    function bindPwToggle(btnId, inputId, iconId) {
        const btn   = document.getElementById(btnId);
        const input = document.getElementById(inputId);
        const icon  = document.getElementById(iconId);
        if (!btn || !input || !icon) return;

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    }

    bindPwToggle('togglePasswordBtn', 'password', 'togglePasswordIcon');
    bindPwToggle('toggleConfirmBtn', 'password_confirmation', 'toggleConfirmIcon');

    // ── 4. Password Strength Meter & Match ───────────────────
    const pwInput      = document.getElementById('password');
    const pwConfirm    = document.getElementById('password_confirmation');
    const pwMeter      = document.getElementById('pwMeter');
    const pwBars       = [
        document.getElementById('pwBar1'),
        document.getElementById('pwBar2'),
        document.getElementById('pwBar3'),
        document.getElementById('pwBar4')
    ];
    const pwStatusText = document.getElementById('pwStatusText');
    const pwHintText   = document.getElementById('pwHintText');
    const matchBadge   = document.getElementById('matchBadge');

    function evaluateStrength(pw) {
        let score = 0;
        if (pw.length >= 8)  score++;
        if (pw.length >= 12) score++;
        if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
        if (/[0-9]/.test(pw) || /[^A-Za-z0-9]/.test(pw)) score++;
        return score;
    }

    const strengthLevels = [
        { label: 'Very Weak', class: 'pw-weak', color: '#ef4444', hint: 'Add more characters' },
        { label: 'Weak',      class: 'pw-weak', color: '#ef4444', hint: 'Mix uppercase & numbers' },
        { label: 'Medium',    class: 'pw-fair', color: '#f59e0b', hint: 'Add symbols for extra security' },
        { label: 'Strong',    class: 'pw-good', color: '#3b82f6', hint: 'Great password!' },
        { label: 'Very Strong', class: 'pw-strong', color: '#10b981', hint: 'Excellent protection' }
    ];

    if (pwInput) {
        pwInput.addEventListener('input', function () {
            const val = this.value;
            if (!val) {
                pwMeter.style.display = 'none';
                pwBars.forEach(b => b.className = 'fb-pw-bar');
                checkMatch();
                return;
            }

            pwMeter.style.display = 'block';
            const score = evaluateStrength(val);
            const level = strengthLevels[score];

            pwBars.forEach((bar, index) => {
                bar.className = 'fb-pw-bar';
                if (index < score) {
                    bar.classList.add(level.class);
                }
            });

            pwStatusText.textContent = level.label;
            pwStatusText.style.color = level.color;
            pwHintText.textContent   = level.hint;

            checkMatch();
        });
    }

    function checkMatch() {
        if (!pwConfirm || !pwInput || !matchBadge) return;
        const p1 = pwInput.value;
        const p2 = pwConfirm.value;

        if (!p2) {
            matchBadge.className = 'fb-match-badge';
            matchBadge.innerHTML = '';
            return;
        }

        if (p1 === p2 && p1.length >= 8) {
            matchBadge.className = 'fb-match-badge matched';
            matchBadge.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
            matchBadge.title = 'Passwords match!';
        } else {
            matchBadge.className = 'fb-match-badge mismatch';
            matchBadge.innerHTML = '<i class="bi bi-x-circle-fill"></i>';
            matchBadge.title = 'Passwords do not match';
        }
    }

    if (pwConfirm) {
        pwConfirm.addEventListener('input', checkMatch);
    }



    // ── 6. Form Submission Loading State ─────────────────────
    const form = document.getElementById('fbRegisterForm');
    const submitBtn = document.getElementById('submitRegisterBtn');

    if (form) {
        form.addEventListener('submit', function () {
            syncFullName();
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Submitting...';
            }
        });
    }
});
</script>
@endpush
