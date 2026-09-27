{{-- Extends the base layout designed for unauthenticated guest sessions --}}
@extends('layouts.guest')

{{-- Sets the browser tab title specifically for this password reset request page --}}
@section('title', 'Forgot Password - Talisay Beach Resort')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<style>
/* ── Reset ──────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; }

body {
    margin: 0;
    padding: 0;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    -webkit-font-smoothing: antialiased;
}

/* ── Full-screen Hero Wrapper ───────────────────────────────────── */
.fp-screen {
    min-height: 100vh;
    width: 100%;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: #061430;
}

/* ── Background — same hero image as Landing & Login ───────────── */
.fp-bg {
    position: absolute;
    inset: 0;
    background-image: url('/images/hero-landing.jpg');
    background-size: cover;
    background-position: center 40%;
    transform: scale(1.05);
    animation: fpSlowZoom 18s ease-in-out infinite alternate;
    z-index: 0;
}

@keyframes fpSlowZoom {
    from { transform: scale(1.05); }
    to   { transform: scale(1.12); }
}

/* ── Dark Cinematic Overlay (same gradient as Login page) ───────── */
.fp-overlay {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(to top, rgba(6, 20, 48, 0.96) 0%, rgba(6, 20, 48, 0.45) 50%, rgba(6, 20, 48, 0.82) 100%);
    z-index: 1;
}



/* ── White Card ─────────────────────────────────────────────────── */
.fp-card {
    position: relative;
    z-index: 5;
    width: 100%;
    max-width: 440px;
    margin: 0 20px;
    background: #ffffff;
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
    border: none;
    border-radius: 24px;
    padding: 44px 40px 40px;
    box-shadow: 0 32px 72px rgba(0, 0, 0, 0.50);
    animation: fpCardIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes fpCardIn {
    from {
        opacity: 0;
        transform: translateY(24px) scale(0.97);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* ── Icon Badge ─────────────────────────────────────────────────── */
.fp-icon-badge {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    background: linear-gradient(135deg, #0084B4 0%, #00b4d8 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px auto;
    box-shadow: 0 8px 24px rgba(0, 132, 180, 0.45);
    font-size: 28px;
    color: #ffffff;
    animation: fpIconPulse 3s ease-in-out infinite;
}

@keyframes fpIconPulse {
    0%, 100% { box-shadow: 0 8px 24px rgba(0, 132, 180, 0.45); }
    50%       { box-shadow: 0 8px 32px rgba(0, 132, 180, 0.70); }
}

/* ── Heading & Sub ──────────────────────────────────────────────── */
.fp-title {
    font-family: 'Inter', sans-serif;
    font-size: 26px;
    font-weight: 800;
    color: #0f172a;
    text-align: center;
    margin: 0 0 6px 0;
    letter-spacing: -0.025em;
    text-shadow: none;
}

.fp-subtitle {
    font-size: 13.5px;
    color: #64748b;
    text-align: center;
    margin: 0 0 28px 0;
    line-height: 1.5;
}

/* ── Alert Boxes ────────────────────────────────────────────────── */
.fp-alert {
    display: flex;
    align-items: center;
    gap: 10px;
    border-radius: 12px;
    padding: 11px 14px;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 18px;
}

.fp-alert-error {
    background: rgba(239, 68, 68, 0.18);
    border: 1px solid rgba(239, 68, 68, 0.35);
    color: #fca5a5;
}

.fp-alert-success {
    background: rgba(16, 185, 129, 0.18);
    border: 1px solid rgba(16, 185, 129, 0.35);
    color: #6ee7b7;
}

/* ── Label ──────────────────────────────────────────────────────── */
.fp-label {
    display: block;
    font-size: 12.5px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 7px;
    letter-spacing: 0.01em;
}

/* ── Input Wrapper ──────────────────────────────────────────────── */
.fp-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
    margin-bottom: 22px;
}

.fp-input-icon { display: none; }

.fp-input {
    width: 100%;
    height: 50px;
    padding: 0 16px;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    background: #f8fafc;
    color: #0f172a;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    outline: none;
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}

.fp-input::placeholder {
    color: #94a3b8;
}

.fp-input:hover {
    border-color: #94a3b8;
    background: #ffffff;
}

.fp-input:focus {
    border-color: #0084B4;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(0, 132, 180, 0.12);
}



/* ── Submit Button ──────────────────────────────────────────────── */
.fp-btn-submit {
    width: 100%;
    height: 52px;
    border-radius: 9999px;
    background: linear-gradient(135deg, #0084B4 0%, #00b4d8 100%);
    border: none;
    color: #ffffff;
    font-size: 15px;
    font-weight: 700;
    letter-spacing: 0.02em;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    box-shadow: 0 6px 20px rgba(0, 132, 180, 0.45);
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    font-family: 'Inter', sans-serif;
}

.fp-btn-submit:hover {
    background: linear-gradient(135deg, #006f99 0%, #0097b5 100%);
    box-shadow: 0 8px 28px rgba(0, 132, 180, 0.60);
    transform: translateY(-2px);
    color: #ffffff;
}

.fp-btn-submit:active {
    transform: translateY(0);
}

/* ── Divider ────────────────────────────────────────────────────── */
.fp-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 22px 0;
}

.fp-divider-line {
    flex: 1;
    height: 1px;
    background: #e2e8f0;
}

.fp-divider-text {
    font-size: 11.5px;
    color: #94a3b8;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

/* ── Back to Login Button ───────────────────────────────────────── */
.fp-btn-login {
    width: 100%;
    height: 46px;
    border-radius: 9999px;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    color: #0f172a;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}

.fp-btn-login:hover {
    background: #f8fafc;
    border-color: #0084B4;
    color: #0084B4;
    transform: translateY(-1px);
}

/* ── Bottom Info Footer ─────────────────────────────────────────── */
.fp-info-text {
    text-align: center;
    margin-top: 22px;
    font-size: 11.5px;
    color: #94a3b8;
    line-height: 1.6;
}

/* ── Responsive ─────────────────────────────────────────────────── */
@media (max-width: 520px) {
    .fp-card {
        padding: 36px 24px 32px;
        margin: 0 14px;
    }
    .fp-title { font-size: 22px; }
    .fp-back-btn span { display: none; }
    .fp-brand-pill span { display: none; }
}
</style>
@endpush

@section('content')

{{-- Full-screen hero background --}}
<div class="fp-screen">

    {{-- Sunset beach background image (same as landing/login) --}}
    <div class="fp-bg"></div>
    <div class="fp-overlay"></div>

    {{-- Center white card --}}
    <div class="fp-card">

        {{-- Icon --}}
        <div class="fp-icon-badge">
            <i class="bi bi-shield-lock"></i>
        </div>

        {{-- Heading --}}
        <h1 class="fp-title">Forgot Password?</h1>
        <p class="fp-subtitle">
            No worries! Enter your registered email address and we'll send you a secure reset link.
        </p>

        {{-- Error Alert --}}
        @if($errors->any())
        <div class="fp-alert fp-alert-error">
            <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
            <span>{{ $errors->first() }}</span>
        </div>
        @endif

        {{-- Success / Status Flash --}}
        @if(session('status'))
        <div class="fp-alert fp-alert-success">
            <i class="bi bi-check-circle-fill flex-shrink-0"></i>
            <span>{{ session('status') }}</span>
        </div>
        @endif

        {{-- Form --}}
        <form action="{{ route('password.email') }}" method="POST" id="fpForm">
            @csrf

            <label class="fp-label" for="fpEmail">Email Address</label>
            <div class="fp-input-wrap">
                <input
                    type="email"
                    name="email"
                    id="fpEmail"
                    class="fp-input"
                    placeholder="Enter your registered email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                >
            </div>

            <button type="submit" class="fp-btn-submit" id="fpSubmitBtn">
                <i class="bi bi-send"></i>
                <span>Send Reset Link</span>
            </button>
        </form>

        {{-- Divider --}}
        <div class="fp-divider">
            <div class="fp-divider-line"></div>
            <span class="fp-divider-text">or</span>
            <div class="fp-divider-line"></div>
        </div>

        {{-- Back to login --}}
        <a href="{{ route('login') }}" class="fp-btn-login">
            <i class="bi bi-box-arrow-in-right"></i>
            <span>Back to Log In</span>
        </a>

        {{-- Info note --}}
        <p class="fp-info-text">
            Check your spam folder if you don't see the email within a few minutes.
        </p>

    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form      = document.getElementById('fpForm');
    const submitBtn = document.getElementById('fpSubmitBtn');

    if (form && submitBtn) {
        form.addEventListener('submit', function () {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Sending...';
        });
    }
});
</script>
@endpush

@endsection
