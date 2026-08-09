{{-- Extends the base layout designed for unauthenticated guest sessions --}}
@extends('layouts.guest')

{{-- Sets the browser tab title specifically for this registration page --}}
@section('title', 'Register - Talisay Smart Tourism')

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

        {{-- Dark gradient overlay --}}
        <div class="auth-video-overlay"></div>

        {{-- Content on top of video --}}
        <div class="auth-video-content">
            <div class="auth-video-badge">
                <i class="bi bi-camera-video-fill me-2"></i>Virtual Tour
            </div>
            <h2 class="auth-video-title">Discover Paradise<br>at Talisay Resort</h2>
            <p class="auth-video-subtitle">Barangay Maslug, Baybay City, Leyte</p>
            <div class="auth-wave-indicator">
                <span></span><span></span><span></span><span></span>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════
         RIGHT PANEL — Register Form
    ════════════════════════════════════════════ --}}
    <div class="auth-form-panel">
        <div class="auth-form-inner">

            {{-- Header --}}
            <div class="auth-form-header">
                <div class="auth-logo-wrap">
                    <img src="{{ asset('images/logo.png') }}" alt="Talisay Beach Resort Logo">
                </div>
                <div>
                    <p class="auth-greeting">Welcome aboard! 🌊</p>
                    <h1 class="auth-form-title">Create <span>your account</span></h1>
                </div>
            </div>

            {{-- Error Alert --}}
            @if($errors->any())
            <div class="auth-alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <div>
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Register Form --}}
            <form action="{{ route('register.post') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Full Name --}}
                <div class="auth-field-group">
                    <label class="auth-field-label">
                        <i class="bi bi-person"></i> Full Name
                    </label>
                    <input
                        type="text"
                        name="name"
                        class="auth-field-input"
                        placeholder="Juan Dela Cruz"
                        value="{{ old('name') }}"
                        required
                        autofocus
                    >
                </div>

                {{-- Email + Phone side by side --}}
                <div class="auth-field-row">
                    <div class="auth-field-group">
                        <label class="auth-field-label">
                            <i class="bi bi-envelope"></i> Email
                        </label>
                        <input
                            type="email"
                            name="email"
                            class="auth-field-input"
                            placeholder="you@email.com"
                            value="{{ old('email') }}"
                            required
                        >
                    </div>
                    <div class="auth-field-group">
                        <label class="auth-field-label">
                            <i class="bi bi-telephone"></i> Phone
                        </label>
                        <input
                            type="tel"
                            name="phone"
                            class="auth-field-input"
                            placeholder="+63 9xx xxx xxxx"
                            value="{{ old('phone') }}"
                        >
                    </div>
                </div>

                {{-- Password + Confirm side by side --}}
                <div class="auth-field-row">
                    <div class="auth-field-group">
                        <label class="auth-field-label">
                            <i class="bi bi-lock"></i> Password
                        </label>
                        <input
                            type="password"
                            name="password"
                            class="auth-field-input"
                            placeholder="Min. 8 characters"
                            required
                        >
                    </div>
                    <div class="auth-field-group">
                        <label class="auth-field-label">
                            <i class="bi bi-lock-fill"></i> Confirm
                        </label>
                        <input
                            type="password"
                            name="password_confirmation"
                            class="auth-field-input"
                            placeholder="Repeat password"
                            required
                        >
                    </div>
                </div>

                {{-- Profile Photo --}}
                <div class="auth-field-group">
                    <label class="auth-field-label">
                        <i class="bi bi-image"></i> Profile Photo <span class="auth-optional">(optional)</span>
                    </label>
                    <input
                        type="file"
                        name="avatar"
                        class="auth-field-input auth-file-input"
                        accept="image/*"
                    >
                </div>

                {{-- Terms --}}
                <div class="auth-terms-row">
                    <label class="auth-remember">
                        <input type="checkbox" name="terms" id="terms" required>
                        I agree to the <a href="#" class="auth-forgot">Terms &amp; Conditions</a>
                    </label>
                </div>

                <button type="submit" class="auth-submit-btn">
                    <i class="bi bi-person-plus me-2"></i>Create Account
                </button>
            </form>

            <div class="auth-form-footer">
                <p>Already have an account?</p>
                <a href="{{ route('login') }}" class="auth-link-btn">Login</a>
            </div>

            <p class="auth-copyright">&copy; 2026 Talisay Beach Resort. All rights reserved.</p>
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
    flex: 0 0 480px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    box-shadow: -8px 0 40px rgba(12,74,110,0.12);
    overflow-y: auto;
}

.auth-form-inner {
    width: 100%;
    max-width: 420px;
    padding: 2.5rem 2rem;
}

/* Header */
.auth-form-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
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
    align-items: flex-start;
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    font-size: 0.83rem;
    border-radius: 10px;
    padding: 0.6rem 0.9rem;
    margin-bottom: 1.2rem;
    gap: 0.4rem;
}

/* Fields */
.auth-field-group {
    margin-bottom: 0.85rem;
}

.auth-field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}

.auth-field-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.35rem;
}

.auth-field-label i {
    color: #0ea5e9;
}

.auth-optional {
    font-weight: 400;
    color: #94a3b8;
    font-size: 0.74rem;
}

.auth-field-input {
    width: 100%;
    padding: 0.6rem 0.8rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.875rem;
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

.auth-file-input {
    padding: 0.45rem 0.8rem;
    cursor: pointer;
}

/* Terms */
.auth-terms-row {
    margin-bottom: 1.2rem;
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

.auth-submit-btn:active { transform: translateY(0); }

/* Footer */
.auth-form-footer {
    text-align: center;
    margin-top: 1.2rem;
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
    margin-top: 1.2rem;
}

/* ── Responsive ───────────────────────────────────── */
@media (max-width: 900px) {
    .auth-form-panel { flex: 0 0 380px; }
}

@media (max-width: 768px) {
    .auth-split-screen  { flex-direction: column; }
    .auth-video-panel   { min-height: 240px; flex: none; }
    .auth-form-panel    { flex: none; min-height: auto; }
    .auth-form-inner    { padding: 2rem 1.5rem; }
    .auth-field-row     { grid-template-columns: 1fr; }
}
</style>

@endsection
