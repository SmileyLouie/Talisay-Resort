{{-- Shared left panel for guest login and register. --}}
<div class="auth-hero-panel">
    <div class="auth-hero-bg"></div>
    <div class="auth-hero-overlay"></div>

    <div class="auth-hero-top">
        <a href="{{ route('home') }}" class="auth-back-btn">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Home</span>
        </a>
        <div class="auth-hero-brand-pill">
            <img src="{{ asset('images/logo.png') }}" alt="Talisay Beach Resort Logo" class="auth-hero-logo-img">
            <span>{{ setting('resort_name', 'Talisay Beach Resort') }}</span>
        </div>
    </div>

    <div class="auth-hero-content">
        <div class="auth-hero-badge">
            <span class="badge-dot"></span>
            <span>{{ setting('resort_address', 'Baybay City, Leyte') }}</span>
        </div>
        <h1 class="auth-hero-title">
            {{ setting('resort_name', 'Talisay Beach Resort') }}<br>
            <span>Your shore, your stay.</span>
        </h1>
        <p class="auth-hero-subtitle">
            Book a room or cottage by the night or by the hour, take the 360° tour, and manage your stay from this site or the Android app.
        </p>
        <div class="auth-hero-features">
            <span class="feature-tag"><i class="bi bi-badge-vr"></i> 360° tour</span>
            <span class="feature-tag"><i class="bi bi-calendar2-check"></i> Book or reserve</span>
            <span class="feature-tag"><i class="bi bi-wallet2"></i> GCash, card, or cash</span>
        </div>
    </div>
</div>
