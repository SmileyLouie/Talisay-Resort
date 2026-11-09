<style>
    /* ── Guest log in / register dialog ── */
    .modal-backdrop {
        position: fixed; inset: 0;
        background: rgba(8, 47, 73, .62);
        backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
        z-index: 9999;
        display: flex; align-items: center; justify-content: center;
        padding: 20px;
        opacity: 0; visibility: hidden; pointer-events: none;
        transition: opacity .22s ease, visibility .22s ease;
    }
    .modal-backdrop.active { opacity: 1; visibility: visible; pointer-events: auto; }

    .login-dialog {
        background: #fff;
        border-radius: 26px;
        width: 100%; max-width: 440px;
        overflow: hidden;
        box-shadow: 0 40px 80px -20px rgba(8, 47, 73, .55);
        transform: scale(.96) translateY(10px);
        transition: transform .28s cubic-bezier(.16, 1, .3, 1);
        position: relative;
    }
    .modal-backdrop.active .login-dialog { transform: none; }

    .login-dialog-cover {
        position: relative;
        height: 160px;
        background: url('/images/hero-landing.jpg') center / cover no-repeat;
        display: flex; align-items: flex-end;
        padding: 20px 26px;
        color: #fff;
    }
    .login-dialog-cover::before {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(180deg, rgba(8, 47, 73, .15), rgba(8, 47, 73, .85));
    }
    .login-dialog-brand {
        position: relative;
        display: flex; align-items: center; gap: 12px;
    }
    .login-dialog-brand img {
        width: 46px; height: 46px; border-radius: 14px; object-fit: cover; background: #fff;
        box-shadow: 0 8px 20px rgba(0, 0, 0, .25);
    }
    .login-dialog-brand strong {
        display: block; font-family: "Playfair Display", Georgia, serif; font-size: 19px; line-height: 1.1;
    }
    .login-dialog-brand span {
        font-size: 10px; letter-spacing: .2em; text-transform: uppercase; color: #cbd5e1;
    }
    .login-dialog-close {
        position: absolute; top: 14px; right: 14px; z-index: 2;
        background: rgba(255, 255, 255, .92);
        border: none;
        color: var(--tbr-navy, #0c4a6e);
        width: 36px; height: 36px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 16px; cursor: pointer;
        transition: background .15s ease, transform .15s ease;
        padding: 0;
    }
    .login-dialog-close:hover { background: #fff; transform: rotate(90deg); }

    .login-dialog-body { padding: 26px 26px 22px; }
    .login-dialog-title {
        font-family: "Playfair Display", Georgia, serif;
        font-size: 26px; font-weight: 700; color: var(--tbr-ink, #0f172a);
        line-height: 1.15; margin: 0 0 6px;
    }
    .login-dialog-sub { font-size: 14px; line-height: 1.6; color: #64748b; margin: 0 0 22px; }

    .login-dialog-options { display: flex; flex-direction: column; gap: 12px; }
    .login-btn-pill {
        width: 100%;
        height: 54px;
        border-radius: 9999px;
        display: flex; align-items: center; justify-content: center;
        gap: 10px;
        font-family: 'Inter', sans-serif;
        font-size: 15px; font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        transition: transform .2s ease, box-shadow .2s ease, background .2s ease, color .2s ease;
        box-sizing: border-box;
        user-select: none;
    }
    .login-btn-pill i { font-size: 18px; }
    .login-btn-primary {
        background: var(--tbr-sand, #f59e0b);
        color: #fff;
        box-shadow: 0 12px 26px rgba(245, 158, 11, .35);
    }
    .login-btn-primary:hover { background: #d97706; color: #fff; transform: translateY(-2px); box-shadow: 0 16px 30px rgba(245, 158, 11, .4); }
    .login-btn-secondary {
        background: #fff;
        color: var(--tbr-navy, #0c4a6e);
        border: 2px solid var(--tbr-navy, #0c4a6e);
    }
    .login-btn-secondary:hover { background: var(--tbr-navy, #0c4a6e); color: #fff; transform: translateY(-2px); }

    .login-dialog-social { margin-top: 18px; }
    .login-dialog-divider {
        display: flex; align-items: center; gap: 12px;
        font-size: 11px; letter-spacing: .16em; text-transform: uppercase; color: #94a3b8;
        margin-bottom: 12px;
    }
    .login-dialog-divider::before, .login-dialog-divider::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }
    .login-social-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .login-social-btn {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        height: 44px; border-radius: 12px;
        border: 1px solid #e2e8f0; background: #fff;
        color: #0f172a; font-size: 13.5px; font-weight: 600; text-decoration: none;
        transition: background .15s ease, border-color .15s ease;
    }
    .login-social-btn:hover { background: #f8fafc; border-color: #cbd5e1; color: #0f172a; }
    .login-social-btn .bi-google { color: #ea4335; }
    .login-social-btn .bi-facebook { color: #1877f2; }

    @media (max-width: 640px) {
        .login-dialog { max-width: 94vw; border-radius: 22px; }
        .login-dialog-cover { height: 132px; padding: 16px 20px; }
        .login-dialog-body { padding: 22px 20px 18px; }
        .login-dialog-title { font-size: 23px; }
        .login-btn-pill { height: 50px; font-size: 14.5px; }
    }
</style>
{{-- ── Guest log in / register dialog ── --}}
@php $socialEnabled = (bool) (config('services.google.client_id') || config('services.facebook.client_id')); @endphp
<div id="loginModal" class="modal-backdrop" aria-hidden="true" onclick="handleModalOverlayClick(event)">
    <div class="login-dialog" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle">
        <button type="button" class="login-dialog-close" onclick="closeLoginModal()" aria-label="Close dialog">
            <i class="bi bi-x-lg"></i>
        </button>
        <div class="login-dialog-cover">
            <div class="login-dialog-brand">
                <img src="{{ asset('images/logo.png') }}" alt="{{ $resortInfo['name'] ?? 'Talisay Beach Resort' }} logo">
                <div>
                    <strong>{{ $resortInfo['name'] ?? 'Talisay Beach Resort' }}</strong>
                    <span>Smart Tourism System</span>
                </div>
            </div>
        </div>

        <div class="login-dialog-body">
            <h2 id="loginModalTitle" class="login-dialog-title">Welcome, guest</h2>
            <p class="login-dialog-sub">Log in to book a room or cottage, or create a free account in under a minute.</p>

            <div class="login-dialog-options">
                <a href="{{ $clientPortalUrl }}/login?portal=client" data-base="{{ $clientPortalUrl }}/login?portal=client" class="login-btn-pill login-btn-primary" id="portal-client-btn">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Log in</span>
                </a>
                <a href="{{ $clientPortalUrl }}/register" data-base="{{ $clientPortalUrl }}/register" class="login-btn-pill login-btn-secondary" id="portal-register-btn">
                    <i class="bi bi-person-plus"></i>
                    <span>Create an account</span>
                </a>
            </div>

            @if($socialEnabled)
                <div class="login-dialog-social">
                    <div class="login-dialog-divider">or continue with</div>
                    <div class="login-social-row">
                        @if(config('services.google.client_id'))
                            <a href="{{ $clientPortalUrl }}/auth/google" class="login-social-btn"><i class="bi bi-google"></i> Google</a>
                        @endif
                        @if(config('services.facebook.client_id'))
                            <a href="{{ $clientPortalUrl }}/auth/facebook" class="login-social-btn"><i class="bi bi-facebook"></i> Facebook</a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function safeBookingReturn(url) {
    try {
        const parsed = new URL(url || '/booking', location.origin);
        if (parsed.origin !== location.origin) return '/booking';
        const path = parsed.pathname;
        const allowed = path === '/booking' || path === '/booking/exclusive' || path === '/rooms' || path === '/cottages' || path.indexOf('/stay/') === 0;
        return allowed ? path + parsed.search : '/booking';
    } catch (err) {
        return '/booking';
    }
}

function openLoginModal(e, returnUrl) {
    if (e && e.preventDefault) e.preventDefault();
    const back = returnUrl ? safeBookingReturn(returnUrl) : '';
    ['portal-client-btn', 'portal-register-btn'].forEach(function (id) {
        const link = document.getElementById(id);
        if (!link) return;
        const target = new URL(link.dataset.base || link.getAttribute('href'), location.origin);
        if (back) target.searchParams.set('redirect', back);
        else target.searchParams.delete('redirect');
        link.href = target.pathname + target.search;
    });
    const title = document.getElementById('loginModalTitle');
    const sub = document.querySelector('.login-dialog-sub');
    if (title) title.textContent = returnUrl ? 'Log in to book' : 'Welcome, guest';
    if (sub) sub.textContent = returnUrl
        ? 'Sign in first. After you log in, you come back to this reservation.'
        : 'Log in to book a room or cottage, or create a free account in under a minute.';
    const modal = document.getElementById('loginModal');
    if (modal) {
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
}

document.addEventListener('click', function (event) {
    if (document.body.dataset.signedIn === '1') return;
    const book = event.target.closest('[data-book]');
    if (!book) return;
    openLoginModal(event, book.getAttribute('href') || '/booking');
});

function closeLoginModal() {
    const modal = document.getElementById('loginModal');
    if (modal) {
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }
}

function handleModalOverlayClick(e) {
    if (e.target === document.getElementById('loginModal')) {
        closeLoginModal();
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLoginModal();
        if (typeof toggleLandingChatbot === 'function' && window.lcbOpen) {
            toggleLandingChatbot(false);
        }
    }
});
</script>
