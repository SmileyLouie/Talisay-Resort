@extends('layouts.guest')

@section('title', 'Reset Password - Talisay Smart Tourism')

@push('styles')
<style>
:root { --auth-primary: #0284c7; --auth-primary-hover: #0369a1; }
.fp-screen { min-height: 100vh; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; }
.fp-bg { position: absolute; inset: 0; background-image: url('{{ asset('images/hero-landing.jpg') }}'); background-size: cover; background-position: center; }
.fp-overlay { position: absolute; inset: 0; background: rgba(6, 20, 48, 0.55); }
.fp-card { position: relative; z-index: 2; width: 100%; max-width: 440px; background: #fff; border-radius: 24px; padding: 40px 36px 32px; box-shadow: 0 24px 60px rgba(6, 20, 48, 0.28); margin: 20px; font-family: 'Plus Jakarta Sans', sans-serif; }
.fp-title { font-size: 24px; font-weight: 800; color: #0f172a; margin: 0 0 8px; text-align: center; }
.fp-subtitle { font-size: 13px; color: #64748b; text-align: center; margin-bottom: 22px; }
.fp-alert { display: flex; align-items: center; gap: 10px; border-radius: 12px; padding: 11px 14px; font-size: 13px; font-weight: 500; margin-bottom: 18px; }
.fp-alert-error { background: #fff1f2; border: 1px solid #fecdd3; color: #9f1239; }
.fp-label { display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 7px; }
.fp-input { width: 100%; height: 46px; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 0 14px; font-size: 14px; margin-bottom: 14px; }
.fp-input:focus { outline: none; border-color: var(--auth-primary); box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12); }
.fp-btn-submit { width: 100%; height: 46px; border: none; border-radius: 9999px; background: var(--auth-primary); color: #fff; font-weight: 700; cursor: pointer; }
.fp-btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }
.fp-back { display: block; text-align: center; margin-top: 16px; color: var(--auth-primary); font-size: 13px; font-weight: 600; text-decoration: none; }
</style>
@endpush

@section('content')
<div class="fp-screen">
    <div class="fp-bg"></div>
    <div class="fp-overlay"></div>
    <div class="fp-card">
        <h1 class="fp-title">Reset Password</h1>
        <p class="fp-subtitle">Choose a new password for your Talisay Beach Resort account.</p>

        @if($errors->any())
        <div class="fp-alert fp-alert-error" role="alert">
            <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
            <span>{{ $errors->first('email') ?: $errors->first('password') ?: $errors->first('token') ?: $errors->first() }}</span>
        </div>
        @endif

        <form action="{{ route('password.update') }}" method="POST" id="resetPasswordForm">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') ?? $request->token }}">

            <label class="fp-label" for="resetEmail">Email Address</label>
            <input type="email" name="email" id="resetEmail" class="fp-input" value="{{ old('email', $request->email ?? request('email')) }}" required autocomplete="username">

            <label class="fp-label" for="resetPassword">New Password</label>
            <input type="password" name="password" id="resetPassword" class="fp-input" required minlength="8" autocomplete="new-password">

            <label class="fp-label" for="resetPasswordConfirm">Confirm Password</label>
            <input type="password" name="password_confirmation" id="resetPasswordConfirm" class="fp-input" required minlength="8" autocomplete="new-password">

            <button type="submit" class="fp-btn-submit" id="resetSubmitBtn">Reset Password</button>
        </form>

        <a href="{{ route('login') }}" class="fp-back">Back to login</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('resetPasswordForm')?.addEventListener('submit', function () {
    const btn = document.getElementById('resetSubmitBtn');
    if (!btn) return;
    btn.disabled = true;
    btn.setAttribute('aria-busy', 'true');
    btn.textContent = 'Updating...';
});
</script>
@endpush
