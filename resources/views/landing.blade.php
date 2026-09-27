<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Welcome to Talisay Smart Tourism System – your gateway to discovering, booking, and experiencing Talisay Beach Resort in Baybay City, Leyte.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Welcome – Talisay Smart Tourism System</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* overflow:hidden on body (not html) prevents scroll WITHOUT clipping position:fixed children.
           The CSS spec propagates body overflow to the viewport when html is overflow:visible,
           and position:fixed elements are positioned vs the viewport, not the body box. */
        html { height: 100%; }
        body { height: 100%; font-family: 'Inter', sans-serif; overflow: hidden; }

        /* ── Full-screen Hero ── */
        .hero {
            position: relative; width: 100vw; height: 100vh;
            display: flex; flex-direction: column; align-items: center;
            justify-content: center;
            /* Do NOT use overflow:hidden here — it clips position:fixed children (chatbot FAB) */
        }

        .hero-bg {
            position: absolute; inset: 0;
            background-image: url('/images/hero-landing.jpg');
            background-size: cover; background-position: center 40%;
            transform: scale(1.04);
            /* Use will-change instead of transform parent to avoid new stacking context clipping fixed children */
            will-change: transform;
            animation: slowZoom 18s ease-in-out infinite alternate;
        }
        @keyframes slowZoom {
            from { transform: scale(1.04); }
            to   { transform: scale(1.10); }
        }

        .hero-overlay {
            position: absolute; inset: 0;
            background:
                linear-gradient(to top,    rgba(6,20,48,.90) 0%, transparent 48%),
                linear-gradient(to bottom, rgba(6,20,48,.65) 0%, transparent 28%),
                rgba(4,14,38,.22);
        }

        /* ── Top Bar ── */
        .topbar {
            position: absolute; top: 0; left: 0; right: 0;
            padding: 20px 40px;
            display: flex; align-items: center; justify-content: space-between;
            z-index: 10;
        }
        .topbar-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .topbar-logo  { width: 44px; height: 44px; border-radius: 12px; object-fit: cover;
                         border: 2px solid rgba(255,255,255,.3); box-shadow: 0 4px 12px rgba(0,0,0,.3); }
        .topbar-name  { font-size: 13px; font-weight: 700; color: #fff; letter-spacing: .02em; line-height: 1.2; }
        .topbar-sub   { font-size: 10px; color: rgba(255,255,255,.55); font-weight: 400;
                         letter-spacing: .08em; text-transform: uppercase; }
        .topbar-links { display: flex; align-items: center; gap: 6px; }
        .topbar-link  { color: rgba(255,255,255,.78); font-size: 12px; font-weight: 500;
                         padding: 7px 16px; border-radius: 20px; text-decoration: none;
                         border: 1px solid transparent; transition: all .2s; }
        .topbar-link:hover { color:#fff; background:rgba(255,255,255,.12); border-color:rgba(255,255,255,.2); }

        /* ── Center Card ── */
        .center-card {
            position: relative; z-index: 5;
            display: flex; flex-direction: column; align-items: center; text-align: center;
            max-width: 720px; padding: 0 24px;
            animation: fadeSlideUp .9s cubic-bezier(.22,1,.36,1) both;
        }
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(32px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Location badge */
        .badge-pill {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,.12);
            backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,.22); border-radius: 50px;
            padding: 7px 20px; font-size: 11px; font-weight: 600;
            color: rgba(255,255,255,.9); letter-spacing: .12em;
            text-transform: uppercase; margin-bottom: 26px;
        }
        .badge-dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: #f5b942; box-shadow: 0 0 8px #f5b942;
            animation: blink 2s ease-in-out infinite;
        }
        @keyframes blink {
            0%,100% { opacity: 1; transform: scale(1); }
            50%      { opacity: .55; transform: scale(.8); }
        }

        /* Main title */
        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.5rem, 6.5vw, 4.2rem);
            font-weight: 800; color: #fff;
            line-height: 1.1; letter-spacing: -.015em;
            margin-bottom: 14px;
            text-shadow: 0 4px 30px rgba(0,0,0,.45);
        }
        .hero-title span {
            background: linear-gradient(130deg, #f5b942 0%, #ffe99a 55%, #f5b942 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-welcome {
            font-size: clamp(1rem, 2.4vw, 1.2rem); font-weight: 300;
            color: rgba(255,255,255,.80); margin-bottom: 12px; line-height: 1.5;
            letter-spacing: .01em;
        }

        .hero-desc {
            font-size: 13.5px; color: rgba(255,255,255,.50);
            max-width: 490px; line-height: 1.75; margin-bottom: 46px;
        }

        /* CTA Buttons */
        .btn-row { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; justify-content: center; }

        .btn-login {
            display: inline-flex; align-items: center; gap: 10px;
            background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%);
            color: #fff; font-size: 14.5px; font-weight: 700;
            padding: 15px 42px; border-radius: 50px; text-decoration: none;
            letter-spacing: .04em;
            box-shadow: 0 8px 32px rgba(14,165,233,.50), 0 2px 8px rgba(0,0,0,.3);
            transition: all .28s cubic-bezier(.22,1,.36,1);
            position: relative; overflow: hidden;
        }
        .btn-login::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,.18) 0%, transparent 60%);
            opacity: 0; transition: opacity .28s;
        }
        .btn-login:hover { transform: translateY(-4px) scale(1.04); color:#fff; text-decoration:none;
                           box-shadow: 0 16px 40px rgba(14,165,233,.58), 0 4px 12px rgba(0,0,0,.3); }
        .btn-login:hover::before { opacity: 1; }
        .btn-login:active { transform: translateY(-1px) scale(1.01); }

        .btn-register {
            display: inline-flex; align-items: center; gap: 10px;
            background: rgba(255,255,255,.10);
            backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
            color: #fff; font-size: 14.5px; font-weight: 700;
            padding: 15px 42px; border-radius: 50px; text-decoration: none;
            border: 1.5px solid rgba(255,255,255,.35); letter-spacing: .04em;
            box-shadow: 0 4px 20px rgba(0,0,0,.18);
            transition: all .28s cubic-bezier(.22,1,.36,1);
        }
        .btn-register:hover { background: rgba(255,255,255,.22); border-color:rgba(255,255,255,.60);
                              transform: translateY(-4px) scale(1.04); color:#fff; text-decoration:none;
                              box-shadow: 0 12px 34px rgba(0,0,0,.28); }
        .btn-register:active { transform: translateY(-1px) scale(1.01); }

        /* ── Login Selection Modal (Clean Minimalist White Style) ── */
        .modal-backdrop {
            position: fixed; inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
            z-index: 9999;
            display: flex; align-items: center; justify-content: center;
            padding: 20px;
            opacity: 0; visibility: hidden; pointer-events: none;
            transition: opacity .22s cubic-bezier(.16, 1, .3, 1), visibility .22s cubic-bezier(.16, 1, .3, 1);
        }
        .modal-backdrop.active {
            opacity: 1; visibility: visible; pointer-events: auto;
        }

        .login-dialog {
            background: #ffffff;
            border-radius: 22px;
            width: 100%; max-width: 410px;
            padding: 28px 26px 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(0, 0, 0, 0.05);
            transform: scale(0.95) translateY(8px);
            transition: transform .25s cubic-bezier(.16, 1, .3, 1);
            position: relative;
        }
        .modal-backdrop.active .login-dialog {
            transform: scale(1) translateY(0);
        }

        .login-dialog-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 22px;
        }
        .login-dialog-title {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 24px; font-weight: 800; color: #111827; line-height: 1.2;
            letter-spacing: -0.025em; margin: 0;
        }
        .login-dialog-close {
            background: transparent;
            border: none;
            color: #111827;
            width: 34px; height: 34px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; font-weight: 700; cursor: pointer;
            transition: background .15s ease, color .15s ease;
            flex-shrink: 0;
            padding: 0;
        }
        .login-dialog-close:hover {
            background: #f1f5f9;
            color: #000000;
        }

        .login-dialog-options {
            display: flex; flex-direction: column; gap: 13px;
            margin-bottom: 0;
        }

        /* Clean pill buttons matching example image */
        .login-btn-pill {
            width: 100%;
            height: 52px;
            border-radius: 9999px;
            display: flex; align-items: center; justify-content: center;
            gap: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            text-decoration: none;
            cursor: pointer;
            transition: all .2s cubic-bezier(.16, 1, .3, 1);
            box-sizing: border-box;
            user-select: none;
        }

        /* Top button: White pill with subtle soft blue-tinted border */
        .login-btn-white {
            background: #ffffff;
            border: 1.5px solid #e0e7ff;
            color: #111827;
            font-weight: 600;
        }
        .login-btn-white:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
            transform: translateY(-1px);
            color: #0f172a;
        }
        .login-btn-white:active {
            transform: translateY(0);
        }
        .login-btn-logo {
            width: 22px; height: 22px;
            border-radius: 4px;
            object-fit: contain;
        }

        /* Bottom button: Solid vibrant ocean blue pill matching example image */
        .login-btn-blue {
            background: #0084B4;
            border: 1.5px solid transparent;
            color: #ffffff;
            font-weight: 700;
        }
        .login-btn-blue:hover {
            background: #00739e;
            box-shadow: 0 6px 20px rgba(0, 132, 180, 0.35);
            transform: translateY(-1px);
            color: #ffffff;
        }
        .login-btn-blue:active {
            transform: translateY(0);
        }

        /* Responsive */
        @media (max-width: 640px) {
            .topbar { padding: 16px 20px; }
            .hero-desc { display: none; }
            .btn-login, .btn-register { padding: 13px 28px; font-size: 13px; }
            .login-dialog { padding: 24px 20px 20px; max-width: 90vw; }
            .login-dialog-title { font-size: 22px; }
            .login-btn-pill { height: 48px; font-size: 14.5px; }
        }

        /* ── Floating Chatbot Widget ── */
        .landing-chatbot-fab {
            position: fixed;
            bottom: 28px;
            right: 28px;
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0c4a6e 0%, #075985 60%, #0284c7 100%);
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.35);
            box-shadow: 0 10px 28px rgba(12, 74, 110, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 850;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .landing-chatbot-fab:hover {
            transform: scale(1.08);
            box-shadow: 0 14px 34px rgba(12, 74, 110, 0.6);
        }
        .landing-chatbot-fab i {
            font-size: 24px;
            transition: transform 0.2s;
        }

        .landing-chatbot-card {
            position: fixed;
            bottom: 98px;
            right: 28px;
            width: 380px;
            max-width: calc(100vw - 32px);
            height: 520px;
            max-height: calc(100vh - 120px);
            background: #ffffff;
            border-radius: 22px;
            box-shadow: 0 25px 55px rgba(7, 30, 61, 0.35), 0 0 0 1px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            z-index: 850;
            opacity: 0;
            transform: translateY(20px) scale(0.96);
            pointer-events: none;
            transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .landing-chatbot-card.open {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }
        .lcb-header {
            background: linear-gradient(135deg, #0c4a6e 0%, #075985 60%, #0284c7 100%);
            padding: 15px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #fff;
            flex-shrink: 0;
        }
        .lcb-header-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .lcb-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .lcb-title {
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
        }
        .lcb-subtitle {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: rgba(255, 255, 255, 0.22);
            color: #e0f2fe;
            padding: 2px 8px;
            border-radius: 20px;
            margin-top: 3px;
        }
        .lcb-close-btn {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.8);
            font-size: 18px;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
        }
        .lcb-close-btn:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.15);
        }
        .lcb-body {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 10px;
            scroll-behavior: smooth;
        }
        .lcb-msg {
            max-width: 86%;
            padding: 10px 14px;
            font-size: 13px;
            line-height: 1.5;
            word-wrap: break-word;
        }
        .lcb-msg-bot {
            align-self: flex-start;
            background: #ffffff;
            color: #1e293b;
            border: 1px solid #e2e8f0;
            border-radius: 16px 16px 16px 4px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
        }
        .lcb-msg-user {
            align-self: flex-end;
            background: #0284c7;
            color: #ffffff;
            border-radius: 16px 16px 4px 16px;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.25);
        }
        .lcb-chips-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 4px;
            padding: 0 2px;
        }
        .lcb-chip-btn {
            background: #f0f9ff;
            color: #0369a1;
            border: 1px solid #bae6fd;
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
            font-family: inherit;
        }
        .lcb-chip-btn:hover {
            background: #e0f2fe;
            border-color: #7dd3fc;
            transform: translateY(-1px);
        }
        .lcb-footer {
            padding: 12px 14px;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 8px;
            align-items: center;
            flex-shrink: 0;
        }
        .lcb-input {
            flex: 1;
            padding: 9px 14px;
            font-size: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 24px;
            outline: none;
            font-family: inherit;
            color: #1e293b;
            transition: border-color 0.2s;
        }
        .lcb-input:focus {
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
        }
        .lcb-send-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #0284c7;
            color: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .lcb-send-btn:hover {
            background: #0369a1;
            transform: scale(1.05);
        }
        .lcb-typing {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 10px 14px;
        }
        .lcb-typing-dot {
            width: 6px;
            height: 6px;
            background: #94a3b8;
            border-radius: 50%;
            animation: lcbBlink 1s infinite alternate;
        }
        .lcb-typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .lcb-typing-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes lcbBlink {
            0% { opacity: 0.3; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1.1); }
        }
        @media (max-width: 640px) {
            .landing-chatbot-fab { bottom: 18px; right: 18px; width: 52px; height: 52px; }
            .landing-chatbot-card { bottom: 82px; right: 16px; width: calc(100vw - 32px); height: 460px; }
        }
    </style>
</head>
<body>

<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>

    {{-- Top Navigation --}}
    <nav class="topbar">
        <a href="{{ url('/') }}" class="topbar-brand">
            <img src="{{ asset('logo.png') }}" alt="Talisay Resort Logo" class="topbar-logo">
            <div>
                <div class="topbar-name">Talisay Beach Resort</div>
                <div class="topbar-sub">Smart Tourism System</div>
            </div>
        </a>
        <div class="topbar-links">
            <a href="{{ route('tour.viewer') }}" target="_blank" class="topbar-link">
                <i class="bi bi-camera-video"></i>&nbsp;360&deg; Tour
            </a>
        </div>
    </nav>

    {{-- Center Hero Content --}}
    <div class="center-card">

        <div class="badge-pill">
            <span class="badge-dot"></span>
            Baybay City, Leyte &nbsp;&bull;&nbsp; Philippines
        </div>

        <h1 class="hero-title">
            Talisay <span>Smart Tourism</span><br>System
        </h1>

        <p class="hero-welcome">
            Welcome to Talisay Smart Tourism
        </p>

        <p class="hero-desc">
            Your all-in-one digital gateway to discovering, booking, and experiencing
            the beauty of Talisay Beach Resort. Book accommodations, explore with a
            360&deg; virtual tour, and manage your entire stay seamlessly online.
        </p>

        <div class="btn-row">
            <a href="{{ route('login') }}" class="btn-login" id="btn-landing-login" onclick="openLoginModal(event)">
                <i class="bi bi-box-arrow-in-right" style="font-size:16px;"></i>
                Login
            </a>
            <a href="{{ route('register') }}" class="btn-register" id="btn-landing-register">
                <i class="bi bi-person-plus" style="font-size:16px;"></i>
                Register
            </a>
        </div>

    </div>

</section>

{{-- ── Login Selection Modal ── --}}
<div id="loginModal" class="modal-backdrop" aria-hidden="true" onclick="handleModalOverlayClick(event)">
    <div class="login-dialog" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle">
        <div class="login-dialog-header">
            <h2 id="loginModalTitle" class="login-dialog-title">Log in</h2>
            <button type="button" class="login-dialog-close" onclick="closeLoginModal()" aria-label="Close dialog">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="login-dialog-options">
            {{-- Button 1: Tourist & Staff --}}
            <a href="{{ route('login', ['portal' => 'client']) }}" class="login-btn-pill login-btn-white" id="portal-client-btn">
                <img src="{{ asset('logo.png') }}" class="login-btn-logo" alt="Logo">
                <span>Tourist &amp; Staff log in</span>
            </a>

            {{-- Button 2: Admin Only --}}
            <a href="{{ route('login', ['portal' => 'admin']) }}" class="login-btn-pill login-btn-blue" id="portal-admin-btn">
                <span>Admin log in</span>
            </a>
        </div>
        
    </div>
</div>

<script>
function openLoginModal(e) {
    if (e) e.preventDefault();
    const modal = document.getElementById('loginModal');
    if (modal) {
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
}

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
    }
});
</script>

@php
    $landUser = auth()->user();
    $landRole = $landUser ? $landUser->role : 'guest';
    $landFirstName = $landUser ? first_name($landUser->name) : '';
    $landModeLabel = match($landRole) {
        'admin' => 'Admin Assistant',
        'staff' => 'Staff Assistant',
        'tourist' => 'Tourist Assistant',
        default => 'Resort Assistant',
    };
    $landInitialWelcome = match($landRole) {
        'admin' => "Hello **{$landFirstName}** (Admin)! 👋\nWelcome to Talisay Beach Resort. How can I assist you with resort management today?",
        'staff' => "Hello **{$landFirstName}**! 👋 Staff Assistant ready.\nAsk about today's arrivals, room availability, or reservations.",
        'tourist' => "Hello **{$landFirstName}**! 👋 Welcome back!\nAsk about your bookings, room rates, GCash payments, or 360° virtual tour.",
        default => "Hello! 👋 Welcome to **Talisay Beach Resort**.\nI'm your virtual guide! How can I help you plan your visit today?",
    };
    $landDefaultChips = match($landRole) {
        'admin' => ["Today's Summary", "Pending Bookings", "Revenue Stats", "Occupancy"],
        'staff' => ["Today's Arrivals", "Pending Bookings", "Cottage Availability", "Guest Rules"],
        'tourist' => ["My Bookings", "Room Rates", "GCash Payment", "360° Tour"],
        default => ["Room Rates", "Check Availability", "Operating Hours", "Directions", "360° Tour"],
    };
@endphp

{{-- Floating Chatbot FAB Button --}}
<div class="landing-chatbot-fab" id="landingChatbotFab" onclick="toggleLandingChatbot()" title="Open Resort Assistant">
    <i class="bi bi-chat-dots-fill" id="landingChatbotIcon"></i>
</div>

{{-- Floating Chatbot Panel --}}
<div class="landing-chatbot-card" id="landingChatbotCard">
    <div class="lcb-header">
        <div class="lcb-header-info">
            <div class="lcb-avatar">
                <i class="bi bi-robot"></i>
            </div>
            <div>
                <div class="lcb-title">Talisay Assistant</div>
                <div class="lcb-subtitle" id="lcbModeLabel">{{ $landModeLabel }}</div>
            </div>
        </div>
        <button type="button" class="lcb-close-btn" onclick="toggleLandingChatbot(false)" aria-label="Close Chatbot">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="lcb-body" id="lcbMessages">
        {{-- Initial Bot Message --}}
        <div class="lcb-msg lcb-msg-bot" id="lcbInitialMsg"></div>
        <div class="lcb-chips-wrap" id="lcbChipsContainer"></div>
    </div>

    <form class="lcb-footer" id="lcbForm" onsubmit="handleLandingChatSubmit(event)">
        <input type="text" class="lcb-input" id="lcbInput" placeholder="Ask about rates, hours, 360° tour..." autocomplete="off">
        <button type="submit" class="lcb-send-btn" id="lcbSendBtn" aria-label="Send message">
            <i class="bi bi-send-fill"></i>
        </button>
    </form>
</div>

<script>
let lcbOpen = false;
const lcbSessionId = 'landing_' + Math.random().toString(36).substring(2, 10) + '_' + Date.now();
const lcbInitialWelcome = {!! \Illuminate\Support\Js::from($landInitialWelcome) !!};
const lcbDefaultChips = {!! \Illuminate\Support\Js::from($landDefaultChips) !!};
let lcbHistory = [];

function formatLcbText(text) {
    if (!text) return '';
    let escaped = text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");

    escaped = escaped.replace(/^[•\-\*]\s+(.*)$/gm, '<div class="flex items-start gap-1.5 my-0.5"><span class="text-sky-500 font-bold leading-tight">•</span><span>$1</span></div>');

    return escaped
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\n/g, '<br>');
}

function initLandingChatbot() {
    const initMsgEl = document.getElementById('lcbInitialMsg');
    if (initMsgEl) {
        initMsgEl.innerHTML = formatLcbText(lcbInitialWelcome);
    }
    renderLcbChips(lcbDefaultChips);
}

function toggleLandingChatbot(forceState) {
    const card = document.getElementById('landingChatbotCard');
    const icon = document.getElementById('landingChatbotIcon');
    if (typeof forceState === 'boolean') {
        lcbOpen = forceState;
    } else {
        lcbOpen = !lcbOpen;
    }

    if (lcbOpen) {
        card.classList.add('open');
        icon.className = 'bi bi-x-lg';
        setTimeout(() => {
            const input = document.getElementById('lcbInput');
            if (input) input.focus();
            scrollLcbBottom();
        }, 150);
    } else {
        card.classList.remove('open');
        icon.className = 'bi bi-chat-dots-fill';
    }
}

function renderLcbChips(chips) {
    const container = document.getElementById('lcbChipsContainer');
    if (!container) return;
    container.innerHTML = '';
    if (!chips || !chips.length) return;

    chips.forEach(chip => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'lcb-chip-btn';
        btn.textContent = chip;
        btn.onclick = () => handleLcbChipClick(chip);
        container.appendChild(btn);
    });
}

function handleLcbChipClick(text) {
    if (text === 'Login' || text === 'Log in') {
        openLoginModal();
        return;
    }
    if (text === 'Register' || text === 'Sign up') {
        window.location.href = "{{ route('register') }}";
        return;
    }
    if (text === '360° Virtual Tour' || text === '360° Tour') {
        window.open("{{ route('tour.viewer') }}", '_blank');
        return;
    }
    sendLandingMessage(text);
}

function scrollLcbBottom() {
    const body = document.getElementById('lcbMessages');
    if (body) {
        body.scrollTop = body.scrollHeight;
    }
}

async function sendLandingMessage(text) {
    const message = (text || '').trim();
    if (!message) return;

    const bodyEl = document.getElementById('lcbMessages');
    const chipsContainer = document.getElementById('lcbChipsContainer');

    // Append user message
    const userMsgEl = document.createElement('div');
    userMsgEl.className = 'lcb-msg lcb-msg-user';
    userMsgEl.textContent = message;
    bodyEl.appendChild(userMsgEl);

    // Track in conversation history
    lcbHistory.push({ role: 'user', text: message });

    // Show typing
    const typingEl = document.createElement('div');
    typingEl.className = 'lcb-msg lcb-msg-bot lcb-typing';
    typingEl.id = 'lcbTypingIndicator';
    typingEl.innerHTML = '<span class="lcb-typing-dot"></span><span class="lcb-typing-dot"></span><span class="lcb-typing-dot"></span>';
    bodyEl.appendChild(typingEl);
    scrollLcbBottom();

    // Clear chips while thinking
    if (chipsContainer) chipsContainer.innerHTML = '';

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const res = await fetch('/api/chatbot/message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                message: message,
                session_id: lcbSessionId,
                history: lcbHistory.slice(-6)
            })
        });

        const data = await res.json();
        typingEl.remove();

        const reply = data.response || data.reply || "I'm sorry, I couldn't process that. Please try again or contact our front desk.";
        lcbHistory.push({ role: 'bot', text: reply });

        const botMsgEl = document.createElement('div');
        botMsgEl.className = 'lcb-msg lcb-msg-bot';
        botMsgEl.innerHTML = formatLcbText(reply);
        bodyEl.appendChild(botMsgEl);

        // Put chips container back at the end
        if (chipsContainer) {
            bodyEl.appendChild(chipsContainer);
            renderLcbChips(data.chips || lcbDefaultChips);
        }
    } catch (err) {
        typingEl.remove();
        const errEl = document.createElement('div');
        errEl.className = 'lcb-msg lcb-msg-bot';
        errEl.textContent = "Sorry, I had trouble connecting. Please check your internet connection or try again.";
        bodyEl.appendChild(errEl);
        if (chipsContainer) {
            bodyEl.appendChild(chipsContainer);
            renderLcbChips(lcbDefaultChips);
        }
    }

    scrollLcbBottom();
}

function handleLandingChatSubmit(e) {
    e.preventDefault();
    const input = document.getElementById('lcbInput');
    const text = input ? input.value : '';
    if (input) input.value = '';
    sendLandingMessage(text);
}

document.addEventListener('DOMContentLoaded', initLandingChatbot);
</script>

</body>
</html>