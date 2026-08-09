{{-- Extends the base layout designed for unauthenticated guest sessions --}}
@extends('layouts.guest')

{{-- Sets the browser tab title specifically for this login page --}}
@section('title', 'Login - Talisay Smart Tourism')

@section('content')

{{-- Full screen split layout wrapper --}}
<div class="auth-split-screen">

    {{-- ════════════════════════════════════════════
         LEFT PANEL — Virtual Tour Video
    ════════════════════════════════════════════ --}}
    <div class="auth-video-panel">

        {{-- Auto-playing, muted, looping virtual tour background video --}}
        <video
            class="auth-video-bg"
            autoplay
            muted
            loop
            playsinline
            poster="{{ asset('images/logo.png') }}"
        >
            <source src="{{ asset('videos/virtual-tour.mp4') }}" type="video/mp4">
        </video>

        {{-- Dark gradient overlay on top of video --}}
        <div class="auth-video-overlay"></div>

        {{-- Content on top of video --}}
        <div class="auth-video-content">
            <div class="auth-video-badge">
                <i class="bi bi-camera-video-fill me-2"></i>Virtual Tour
            </div>
            <h2 class="auth-video-title">Experience Talisay<br>Beach Resort</h2>
            <p class="auth-video-subtitle">Barangay Maslug, Baybay City, Leyte</p>

            {{-- Animated wave indicator --}}
            <div class="auth-wave-indicator">
                <span></span><span></span><span></span><span></span>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════
         RIGHT PANEL — Login Form
    ════════════════════════════════════════════ --}}
    <div class="auth-form-panel">
        <div class="auth-form-inner">

            {{-- Header --}}
            <div class="auth-form-header">
                <div class="auth-logo-wrap">
                    <img src="{{ asset('images/logo.png') }}" alt="Talisay Beach Resort Logo">
                </div>
                <div>
                    <p class="auth-greeting">Hello!</p>
                    <h1 class="auth-form-title">Login <span>your account</span></h1>
                </div>
            </div>

            {{-- Error Alert --}}
            @if($errors->any())
            <div class="auth-alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>{{ $errors->first() }}
            </div>
            @endif

            {{-- Success flash --}}
            @if(session('status'))
            <div class="auth-alert auth-alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
            </div>
            @endif

            {{-- Login Form --}}
            <form id="loginForm" action="{{ route('login.post') }}" method="POST">
                @csrf

                <div class="auth-field-group">
                    <label class="auth-field-label">
                        <i class="bi bi-envelope"></i> Email / Username
                    </label>
                    <input
                        type="email"
                        name="email"
                        class="auth-field-input"
                        placeholder="you@example.com"
                        value="{{ old('email') }}"
                        required
                        autofocus
                    >
                </div>

                <div class="auth-field-group">
                    <label class="auth-field-label">
                        <i class="bi bi-lock"></i> Password
                    </label>
                    <div class="auth-field-password">
                        <input
                            type="password"
                            name="password"
                            id="passwordField"
                            class="auth-field-input"
                            placeholder="Enter your password"
                            required
                        >
                        <button type="button" class="auth-pw-toggle" onclick="togglePassword()">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="auth-row-between">
                    <label class="auth-remember">
                        <input type="checkbox" name="remember"> Remember Me
                    </label>
                    <a href="{{ route('password.request') }}" class="auth-forgot">Forgot password?</a>
                </div>

                <button type="submit" class="auth-submit-btn">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                </button>
            </form>

            <div class="auth-form-footer">
                <p>Don't have an account?</p>
                <a href="{{ route('register') }}" class="auth-link-btn">Create Account</a>
            </div>

            <p class="auth-copyright">&copy;</p>
        </div>
    </div>
</div>

{{-- ── Scoped Styles ─────────────────────────────────────────────── --}}
<style>
/* ── Layout ───────────────────────────────────────── */
.auth-split-screen {
    display: flex;
    min-height: 100vh;
    width: 100%;
    overflow: hidden;
}

/* ── Left: Video Panel ────────────────────────────── */
.auth-video-panel {
    position: relative;
    flex: 1.1;
    min-height: 100vh;
    overflow: hidden;
    display: flex;
    align-items: flex-end;
}

.auth-video-bg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: 0;
}

.auth-video-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to bottom,
        rgba(12,74,110,0.35) 0%,
        rgba(12,74,110,0.65) 60%,
        rgba(12,74,110,0.88) 100%
    );
    z-index: 1;
}

.auth-video-content {
    position: relative;
    z-index: 2;
    padding: 2.5rem;
    color: #fff;
    width: 100%;
}

.auth-video-badge {
    display: inline-flex;
    align-items: center;
    background: rgba(14,165,233,0.30);
    border: 1px solid rgba(14,165,233,0.55);
    backdrop-filter: blur(8px);
    color: #e0f2fe;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    padding: 0.35rem 0.85rem;
    border-radius: 999px;
    margin-bottom: 1rem;
    text-transform: uppercase;
}

.auth-video-title {
    font-size: clamp(1.6rem, 3vw, 2.6rem);
    font-weight: 800;
    line-height: 1.2;
    color: #fff;
    text-shadow: 0 2px 12px rgba(0,0,0,0.4);
    margin-bottom: 0.5rem;
}

.auth-video-subtitle {
    font-size: 0.9rem;
    color: #bae6fd;
    margin-bottom: 1.5rem;
}

/* Wave animation */
.auth-wave-indicator {
    display: flex;
    align-items: flex-end;
    gap: 4px;
    height: 24px;
}
.auth-wave-indicator span {
    display: block;
    width: 4px;
    background: #38bdf8;
    border-radius: 2px;
    animation: wave-bar 1.2s ease-in-out infinite;
}
.auth-wave-indicator span:nth-child(1) { height: 8px;  animation-delay: 0s; }
.auth-wave-indicator span:nth-child(2) { height: 16px; animation-delay: 0.15s; }
.auth-wave-indicator span:nth-child(3) { height: 24px; animation-delay: 0.3s; }
.auth-wave-indicator span:nth-child(4) { height: 14px; animation-delay: 0.45s; }

@keyframes wave-bar {
    0%, 100% { transform: scaleY(0.5); opacity: 0.6; }
    50%       { transform: scaleY(1.0); opacity: 1.0; }
}

/* ── Right: Form Panel ────────────────────────────── */
.auth-form-panel {
    flex: 0 0 420px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    box-shadow: -8px 0 40px rgba(12,74,110,0.12);
}

.auth-form-inner {
    width: 100%;
    max-width: 360px;
    padding: 2.5rem 2rem;
}

/* Header */
.auth-form-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
}

.auth-logo-wrap {
    width: 52px;
    height: 52px;
    flex-shrink: 0;
    border-radius: 14px;
    overflow: hidden;
    border: 2px solid #38bdf8;
    box-shadow: 0 4px 12px rgba(14,165,233,0.2);
}

.auth-logo-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.auth-greeting {
    font-size: 0.78rem;
    color: #0ea5e9;
    font-weight: 600;
    margin-bottom: 0.15rem;
    letter-spacing: 0.03em;
}

.auth-form-title {
    font-size: 1.25rem;
    font-weight: 800;
    color: #1e293b;
    line-height: 1.3;
    margin: 0;
}

.auth-form-title span {
    color: #0c4a6e;
    font-weight: 700;
}

/* Alert */
.auth-alert {
    display: flex;
    align-items: center;
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    font-size: 0.83rem;
    border-radius: 10px;
    padding: 0.6rem 0.9rem;
    margin-bottom: 1.2rem;
}

.auth-alert-success {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #16a34a;
}

/* Fields */
.auth-field-group {
    margin-bottom: 1rem;
}

.auth-field-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.4rem;
}

.auth-field-label i {
    color: #0ea5e9;
}

.auth-field-input {
    width: 100%;
    padding: 0.65rem 0.85rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.9rem;
    color: #1e293b;
    background: #f8fafc;
    transition: border-color 0.2s, box-shadow 0.2s;
    outline: none;
    box-sizing: border-box;
}

.auth-field-input:focus {
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(14,165,233,0.12);
    background: #fff;
}

.auth-field-password {
    position: relative;
}

.auth-field-password .auth-field-input {
    padding-right: 2.8rem;
}

.auth-pw-toggle {
    position: absolute;
    right: 0.7rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 0;
    font-size: 1rem;
    transition: color 0.2s;
}

.auth-pw-toggle:hover { color: #0ea5e9; }

/* Row between */
.auth-row-between {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    font-size: 0.82rem;
}

.auth-remember {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    color: #64748b;
    cursor: pointer;
}

.auth-forgot {
    color: #0ea5e9;
    text-decoration: none;
    font-weight: 600;
}

.auth-forgot:hover { text-decoration: underline; }

/* Submit */
.auth-submit-btn {
    width: 100%;
    background: linear-gradient(135deg, #0ea5e9, #0c4a6e);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 0.8rem 1rem;
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    cursor: pointer;
    transition: opacity 0.2s, transform 0.15s, box-shadow 0.2s;
    box-shadow: 0 4px 16px rgba(14,165,233,0.3);
}

.auth-submit-btn:hover {
    opacity: 0.92;
    transform: translateY(-1px);
    box-shadow: 0 8px 24px rgba(14,165,233,0.4);
}

.auth-submit-btn:active {
    transform: translateY(0);
}

/* Footer */
.auth-form-footer {
    text-align: center;
    margin-top: 1.5rem;
}

.auth-form-footer p {
    font-size: 0.82rem;
    color: #64748b;
    margin-bottom: 0.4rem;
}

.auth-link-btn {
    display: inline-block;
    color: #0ea5e9;
    font-weight: 700;
    font-size: 0.9rem;
    text-decoration: none;
    border: 1.5px solid #0ea5e9;
    border-radius: 8px;
    padding: 0.4rem 1.2rem;
    transition: background 0.2s, color 0.2s;
}

.auth-link-btn:hover {
    background: #0ea5e9;
    color: #fff;
}

.auth-copyright {
    text-align: center;
    font-size: 0.72rem;
    color: #cbd5e1;
    margin-top: 1.5rem;
}

/* ── Responsive ───────────────────────────────────── */
@media (max-width: 768px) {
    .auth-split-screen { flex-direction: column; }
    .auth-video-panel  { min-height: 260px; flex: none; }
    .auth-form-panel   { flex: none; min-height: auto; }
    .auth-form-inner   { padding: 2rem 1.5rem; }
}
</style>

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
@endsection
