@php
    $hasGoogle = filled(config('services.google.client_id'));
    $hasFacebook = filled(config('services.facebook.client_id'));
    $from = $socialFrom ?? 'login';
    $divider = $socialDivider ?? 'after';
@endphp
@if($hasGoogle || $hasFacebook)
<style>
    .social-auth { display: flex; flex-direction: column; gap: 10px; margin: 14px 0 4px; width: 100%; }
    .social-btn {
        display: flex; align-items: center; justify-content: center; gap: 10px;
        min-height: 46px; padding: 0 16px; border-radius: 999px;
        border: 1.5px solid #d7e6f0; background: #fff; color: #0f172a;
        font-weight: 700; font-size: 14px; text-decoration: none;
    }
    .social-btn:hover { background: #f0f7fb; color: #0f172a; }
    .social-btn.facebook { background: #1877f2; border-color: #1877f2; color: #fff; }
    .social-btn.facebook:hover { background: #166fe5; color: #fff; }
    .social-divider {
        display: flex; align-items: center; gap: 12px; margin: 4px 0;
        color: #64748b; font-size: 11px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase;
    }
    .social-divider::before, .social-divider::after {
        content: ""; flex: 1; height: 1px; background: #e2e8f0;
    }
    .social-auth-footer { margin: 8px 0 2px; }
</style>
<div class="social-auth {{ $divider === 'none' ? 'social-auth-footer' : '' }}">
    @if($divider === 'before')
        <div class="social-divider">or continue with</div>
    @endif
    @if($hasGoogle)
        <a class="social-btn" href="{{ route('auth.social.redirect', ['provider' => 'google', 'from' => $from]) }}">
            <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
                <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 8 3.1l5.7-5.7C34.2 6.1 29.4 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.2-.1-2.3-.4-3.5z"/>
                <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 16 19 12 24 12c3.1 0 5.8 1.2 8 3.1l5.7-5.7C34.2 6.1 29.4 4 24 4 16.3 4 9.6 8.3 6.3 14.7z"/>
                <path fill="#4CAF50" d="M24 44c5.2 0 10-2 13.6-5.2l-6.3-5.3C29.3 35.1 26.8 36 24 36c-5.3 0-9.7-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/>
                <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-1 2.9-3.1 5.2-5.9 6.5l6.3 5.3C38.2 37.3 44 32 44 24c0-1.2-.1-2.3-.4-3.5z"/>
            </svg>
            Continue with Google
        </a>
    @endif
    @if($hasFacebook)
        <a class="social-btn facebook" href="{{ route('auth.social.redirect', ['provider' => 'facebook', 'from' => $from]) }}">
            <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="currentColor" d="M14.5 8.5V6.8c0-.7.5-1 1.2-1H17V3h-2.1C12.2 3 11 4.4 11 6.6v1.9H9v2.8h2V21h3.5v-9.7h2.3l.4-2.8h-2.7z"/>
            </svg>
            Continue with Facebook
        </a>
    @endif
    @if($divider === 'after')
        <div class="social-divider">or</div>
    @endif
</div>
@endif
