<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Talisay Smart Tourism')</title>

    {{-- Universal Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    {{-- Tailwind CSS & Bootstrap --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        ocean: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            800: '#075985',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    {{-- Chart.js & Alpine.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('styles')

    <style>
        :root {
            --brand-ocean-950: #082f49;
            --brand-ocean-900: #0c4a6e;
            --brand-ocean-800: #075985;
            --brand-ocean-700: #0369a1;
            --brand-ocean-600: #0284c7;
            --brand-ocean-500: #0ea5e9;
            --brand-ocean-100: #e0f2fe;
            --brand-ocean-50: #f0f9ff;
            --brand-azure: #0ea5e9;
            --brand-azure-dark: #0284c7;
            --brand-azure-light: #e0f2fe;
            --brand-sand: #f59e0b;
            --brand-sand-light: #fef3c7;
            --brand-surface: #f8fafc;
            --sidebar-width: 268px;
            --sidebar-collapsed-width: 72px;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--brand-surface);
            color: #1e293b;
            letter-spacing: -0.01em;
        }

        /* ── Sidebar Styling (Unified Talisay Ocean Palette) ── */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            max-height: 100vh;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            background: linear-gradient(180deg, #0c4a6e 0%, #075985 55%, #064566 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 4px 0 24px rgba(12, 74, 110, 0.22);
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1040;
            overflow: hidden;
        }
        .sidebar-nav {
            flex: 1 1 auto;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
        }
        .sidebar-nav::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-nav::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 9999px;
        }
        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        .sidebar.collapsed .sidebar-text,
        .sidebar.collapsed .sidebar-header-details,
        .sidebar.collapsed .sidebar-section-title,
        .sidebar.collapsed .sidebar-badge {
            display: none !important;
        }
        .sidebar.collapsed .sidebar-link {
            justify-content: center;
            padding: 9px;
            margin: 3px 8px;
        }
        .sidebar.collapsed .sidebar-link i {
            margin: 0 !important;
            font-size: 1.2rem;
        }

        .sidebar-section-title {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(224, 242, 254, 0.55);
            padding: 10px 18px 4px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 7px 14px;
            color: rgba(224, 242, 254, 0.85);
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 9px;
            margin: 1.5px 10px;
            font-size: 0.84rem;
            font-weight: 500;
        }
        .sidebar-link i {
            font-size: 1.05rem;
            opacity: 0.9;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }
        .sidebar-link:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            transform: translateX(2px);
        }
        .sidebar-link:hover i {
            opacity: 1;
            transform: scale(1.1);
            color: #38bdf8;
        }
        .sidebar-link.active {
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
            font-weight: 700;
            border-left: 3px solid #38bdf8;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }
        .sidebar-link.active i {
            color: #38bdf8;
            opacity: 1;
        }

        /* ── Main Layout ── */
        .main-content {
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 100vh;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
        }
        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* ── Modern Topbar ── */
        .topbar-nav {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        }

        /* ── Card & Component Polish ── */
        .stat-card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .stat-card-clean:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(14, 165, 233, 0.1), 0 8px 10px -6px rgba(14, 165, 233, 0.05);
            border-color: #cbd5e1;
        }

        /* ── Modern Tables ── */
        .table-clean {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .table-clean thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 14px 18px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        .table-clean tbody td {
            padding: 14px 18px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-size: 0.875rem;
            background: #ffffff;
            transition: background-color 0.15s ease;
        }
        .table-clean tbody tr:hover td {
            background: #f0f9ff;
        }
        .table-clean tbody tr:last-child td {
            border-bottom: none;
        }

        /* ── Unified Buttons ── */
        .btn-ocean {
            background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%);
            color: #ffffff !important;
            font-weight: 600;
            border-radius: 12px;
            padding: 8px 18px;
            font-size: 0.875rem;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(14, 165, 233, 0.28);
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .btn-ocean:hover {
            background: linear-gradient(135deg, #0369a1 0%, #0284c7 100%);
            box-shadow: 0 4px 14px rgba(14, 165, 233, 0.4);
            transform: translateY(-1px);
        }

        .btn-secondary-clean {
            background: #ffffff;
            color: #475569 !important;
            font-weight: 600;
            border-radius: 12px;
            padding: 8px 16px;
            font-size: 0.875rem;
            border: 1px solid #cbd5e1;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .btn-secondary-clean:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: #1e293b !important;
        }

        /* ── Form Controls ── */
        .form-control-clean, .form-select-clean {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 0.875rem;
            color: #1e293b;
            background-color: #ffffff;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .form-control-clean:focus, .form-select-clean:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
        }

        /* ── Modern Chatbot Widget ── */
        .chatbot-bubble {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0c4a6e 0%, #075985 60%, #0284c7 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1050;
            border: 2px solid rgba(255, 255, 255, 0.35);
            box-shadow: 0 10px 28px rgba(12, 74, 110, 0.45);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .chatbot-bubble:hover {
            transform: scale(1.08) translateY(-2px);
            box-shadow: 0 14px 34px rgba(12, 74, 110, 0.6);
        }
        .chatbot-panel {
            position: fixed;
            bottom: 94px;
            right: 24px;
            width: 385px;
            max-width: calc(100vw - 32px);
            height: 530px;
            max-height: calc(100vh - 120px);
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(7, 30, 61, 0.3), 0 0 0 1px rgba(0, 0, 0, 0.06);
            z-index: 1050;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            opacity: 0;
            transform: translateY(16px) scale(0.95);
            pointer-events: none;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .chatbot-panel.open {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }
        .chatbot-header {
            padding: 15px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #ffffff;
            flex-shrink: 0;
            background: linear-gradient(135deg, #0c4a6e 0%, #075985 60%, #0284c7 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .chatbot-header.role-admin,
        .chatbot-header.role-staff,
        .chatbot-header.role-tourist {
            background: linear-gradient(135deg, #0c4a6e 0%, #075985 60%, #0284c7 100%);
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 10px;
            scroll-behavior: smooth;
        }
        .chat-msg {
            max-width: 86%;
            padding: 10px 14px;
            border-radius: 16px;
            font-size: 13px;
            line-height: 1.55;
            word-wrap: break-word;
        }
        .chat-msg.bot {
            align-self: flex-start;
            background: #ffffff;
            color: #1e293b;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
        }
        .chat-msg.user {
            align-self: flex-end;
            background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%);
            color: #ffffff;
            border-radius: 16px 16px 4px 16px;
            box-shadow: 0 2px 8px rgba(14, 165, 233, 0.28);
            margin-left: auto;
        }
        .chatbot-chips-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 10px;
        }
        .chatbot-chip-btn {
            background: #f0f9ff;
            color: #0369a1;
            border: 1px solid #bae6fd;
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            font-family: inherit;
        }
        .chatbot-chip-btn:hover {
            background: #e0f2fe;
            border-color: #7dd3fc;
            transform: translateY(-1px);
        }

        /* ── Responsive Mobile ── */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                transform: translateX(-100%);
            }
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0 !important;
            }
            .chatbot-panel {
                width: calc(100vw - 32px);
                right: 16px;
                bottom: 84px;
            }
        }
    </style>
</head>
@php
    $cbUser = auth()->user();
    $cbRole = $cbUser ? $cbUser->role : 'guest';
    $cbFirstName = $cbUser ? first_name($cbUser->name) : 'Guest';
    $cbModeLabel = match($cbRole) {
        'admin' => 'Admin Assistant',
        'staff' => 'Staff Assistant',
        'tourist' => 'Tourist Assistant',
        default => 'Resort Assistant',
    };
    $cbModeBg = match($cbRole) {
        'admin' => 'from-slate-900 to-indigo-800',
        'staff' => 'from-teal-800 to-cyan-800',
        'tourist' => 'from-sky-700 to-cyan-700',
        default => 'from-sky-700 to-sky-600',
    };
    $cbInitialWelcome = match($cbRole) {
        'admin' => "Hello **{$cbFirstName}**! 👋 Admin Assistant ready.\nI can assist with revenue stats, booking overviews, user accounts, and system operations.",
        'staff' => "Hello **{$cbFirstName}**! 👋 Staff Assistant ready.\nAsk about today's arrivals, pending bookings, cottage availability, or guest check-ins.",
        'tourist' => "Hello **{$cbFirstName}**! 👋 Welcome back to Talisay Beach Resort.\nAsk about your bookings, room rates, GCash payment, or virtual tour.",
        default => "Hello! 👋 Welcome to Talisay Beach Resort.\nHow can I help you plan your visit today?",
    };
    $cbDefaultChips = match($cbRole) {
        'admin' => ["Today's Summary", "Pending Bookings", "Revenue Stats", "User Accounts"],
        'staff' => ["Today's Arrivals", "Check Availability", "Check-Out Today", "Guest Lookup"],
        'tourist' => ["My Bookings", "Room Rates", "GCash Payment", "360° Tour"],
        default => ["Room Rates", "Check Availability", "Directions", "360° Tour"],
    };
@endphp
<body class="antialiased" x-data="{
    sidebarOpen: true,
    mobileSidebar: false,
    chatOpen: false,
    chatRole: '{{ $cbRole }}',
    chatModeLabel: '{{ $cbModeLabel }}',
    chatModeBg: '{{ $cbModeBg }}',
    chatChips: {{ \Illuminate\Support\Js::from($cbDefaultChips) }},
    chatMessages: [{ id: 1, type: 'bot', text: {{ \Illuminate\Support\Js::from($cbInitialWelcome) }} }],
    chatInput: '',
    chatTyping: false,
    chatSessionId: 'web-' + Date.now()
}">

@if(auth()->check())
    {{-- Sidebar Navigation --}}
    <aside class="sidebar" :class="{ 'collapsed': !sidebarOpen, 'mobile-open': mobileSidebar }">
        
        {{-- Brand Header --}}
        <div class="px-4 py-3 flex items-center gap-3 border-b border-white/10 flex-shrink-0">
            <div class="relative flex-shrink-0">
                <img src="{{ asset('images/logo.png') }}" alt="Talisay Beach Resort Logo" class="w-11 h-11 rounded-xl object-cover border-2 border-sky-400 shadow-md">
            </div>
            <div class="sidebar-header-details min-w-0">
                <h2 class="text-white font-extrabold text-sm tracking-tight truncate mb-0 leading-tight">Talisay Beach Resort</h2>
                <div class="flex items-center gap-1.5 mt-0.5">
                    @if(auth()->user()->isAdmin())
                        <span class="text-[10px] font-semibold text-sky-300 tracking-wider uppercase block">
                            Admin Portal
                        </span>
                    @else
                        <span class="text-[10px] font-semibold text-sky-300 tracking-wider uppercase block truncate max-w-[170px]" title="{{ auth()->user()->position ?? 'Staff Portal' }}">
                            {{ auth()->user()->position ?? 'Staff Portal' }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Navigation Menu --}}
        <nav class="sidebar-nav py-2 space-y-0.5">
            @php $authUser = auth()->user(); @endphp
            
            {{-- MAIN SECTION --}}
            <div class="sidebar-section-title">Main</div>
            
            @if($authUser->isAdmin() || $authUser->isStaff())
            <a href="{{ route($authUser->isAdmin() ? 'admin.dashboard' : 'staff.dashboard') }}" class="sidebar-link {{ request()->routeIs('*.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill me-3"></i>
                <span class="sidebar-text">Dashboard</span>
            </a>
            @endif

            {{-- OPERATIONS SECTION --}}
            @php
                $canSeeOperations = $authUser->isAdmin() ||
                    $authUser->hasModuleAccess('bookings') ||
                    $authUser->hasModuleAccess('accommodations') ||
                    $authUser->hasModuleAccess('housekeeping') ||
                    $authUser->hasModuleAccess('payments');
            @endphp

            @if($canSeeOperations)
            <div class="sidebar-section-title">Operations</div>

            @if($authUser->isAdmin() || $authUser->hasModuleAccess('bookings'))
            <a href="{{ route('bookings.index') }}" class="sidebar-link {{ request()->routeIs('bookings.*') ? 'active' : '' }}">
                <i class="bi bi-calendar-check-fill me-3"></i>
                <span class="sidebar-text">Bookings</span>
            </a>
            @endif

            @if($authUser->isAdmin() || $authUser->hasModuleAccess('accommodations') || $authUser->hasModuleAccess('housekeeping'))
            <a href="{{ route('accommodations.index') }}" class="sidebar-link {{ request()->routeIs('accommodations.*') ? 'active' : '' }}">
                <i class="bi bi-building me-3"></i>
                <span class="sidebar-text">Room &amp; Cottage</span>
                <span class="sidebar-badge ms-auto text-[10px] font-bold bg-white/10 px-1.5 py-0.5 rounded-full text-white/80">20</span>
            </a>
            @endif

            @if($authUser->isAdmin() || $authUser->hasModuleAccess('payments'))
            <a href="{{ route('payments.index') }}" class="sidebar-link {{ request()->routeIs('payments.*') ? 'active' : '' }}">
                <i class="bi bi-credit-card-fill me-3"></i>
                <span class="sidebar-text">Payments</span>
            </a>
            @endif
            @endif

            {{-- ADMIN STAFF & ROLE MANAGEMENT --}}
            @if($authUser->isAdmin())
            <a href="{{ route('tasks.staff') }}" class="sidebar-link {{ request()->routeIs('tasks.staff*') ? 'active' : '' }}">
                <i class="bi bi-person-lines-fill me-3 text-sky-400"></i>
                <span class="sidebar-text">Staff Management</span>
            </a>
            @endif

            {{-- GUEST EXPERIENCE SECTION --}}
            @php
                $canSeeGuestExp = $authUser->isAdmin() ||
                    $authUser->hasModuleAccess('reviews') ||
                    $authUser->hasModuleAccess('tour');
            @endphp

            @if($canSeeGuestExp)
            <div class="sidebar-section-title">Guest Experience</div>

            @if($authUser->isAdmin() || $authUser->hasModuleAccess('reviews'))
            <a href="{{ route('reviews.index') }}" class="sidebar-link {{ request()->routeIs('reviews.*') ? 'active' : '' }}">
                <i class="bi bi-star-fill me-3"></i>
                <span class="sidebar-text">Reviews & Feedback</span>
            </a>
            @endif

            @if($authUser->isAdmin() || $authUser->hasModuleAccess('tour'))
            <a href="{{ route('tour.manage') }}" class="sidebar-link {{ request()->routeIs('tour.*') ? 'active' : '' }}">
                <i class="bi bi-camera-video-fill me-3"></i>
                <span class="sidebar-text">360° Virtual Tour</span>
            </a>
            @endif
            @endif

            {{-- MANAGEMENT SECTION --}}
            @php
                $canSeeManagement = $authUser->isAdmin() ||
                    $authUser->hasModuleAccess('reports') ||
                    $authUser->hasModuleAccess('chatbot');
            @endphp

            @if($canSeeManagement)
            <div class="sidebar-section-title">Management</div>



            @if($authUser->isAdmin() || $authUser->hasModuleAccess('reports'))
            <a href="{{ route('reports.index') }}" class="sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-fill me-3"></i>
                <span class="sidebar-text">Reports & Analytics</span>
            </a>
            @endif

            @if($authUser->isAdmin() || $authUser->hasModuleAccess('chatbot'))
            <a href="{{ route('chatbot.index') }}" class="sidebar-link {{ request()->routeIs('chatbot.*') ? 'active' : '' }}">
                <i class="bi bi-chat-dots-fill me-3"></i>
                <span class="sidebar-text">AI Chatbot</span>
            </a>
            @endif

            @if($authUser->isAdmin())
            <a href="{{ route('settings.index') }}" class="sidebar-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <i class="bi bi-gear-fill me-3"></i>
                <span class="sidebar-text">System Settings</span>
            </a>
            @endif
            @endif

        </nav>
    </aside>

    {{-- Main Content Area --}}
    <div class="main-content" :class="{ 'expanded': !sidebarOpen }">
        
        {{-- Topbar Header --}}
        <header class="topbar-nav sticky top-0 z-30 px-4 sm:px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button @click="window.innerWidth < 768 ? mobileSidebar = !mobileSidebar : sidebarOpen = !sidebarOpen" class="w-10 h-10 rounded-xl flex items-center justify-center text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                    <i class="bi bi-list text-2xl"></i>
                </button>
                <div class="hidden sm:flex items-center gap-2 text-xs font-semibold text-slate-400">
                    <span class="text-slate-800 font-bold">Talisay Smart Tourism</span>
                    <i class="bi bi-chevron-right text-[10px]"></i>
                    <span class="text-sky-600 font-medium capitalize">{{ ucwords(str_replace('-', ' ', request()->segment(1) ?? 'Dashboard')) }}</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                
                {{-- Quick Link to Tourist Portal --}}
                <a href="{{ route('tourist.dashboard') }}" target="_blank" class="hidden md:inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-sky-600 bg-slate-100 hover:bg-sky-50 px-3 py-1.5 rounded-xl transition border border-slate-200/60 no-underline">
                    <i class="bi bi-globe2 text-sky-500"></i>
                    <span>Guest View</span>
                </a>

                {{-- Notification Dropdown --}}
                <div class="relative" x-data="{
                    notifOpen: false,
                    unread: {{ auth()->user()->unreadNotificationCount() }},
                    async markAllRead() {
                        try {
                            await fetch('{{ route('notifications.read-all') }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    'Accept': 'application/json'
                                }
                            });
                            this.unread = 0;
                            document.querySelectorAll('.notif-item-unread').forEach(el => {
                                el.classList.remove('bg-sky-50/70', 'notif-item-unread');
                                el.classList.add('bg-white');
                            });
                        } catch (e) {}
                    },
                    async markSingleRead(id, el) {
                        try {
                            await fetch(`/notifications/${id}/read`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    'Accept': 'application/json'
                                }
                            });
                            if (this.unread > 0) this.unread--;
                            el.classList.remove('bg-sky-50/70', 'notif-item-unread');
                            el.classList.add('bg-white');
                        } catch (e) {}
                    }
                }">
                    <button @click="notifOpen = !notifOpen" class="w-10 h-10 rounded-xl flex items-center justify-center text-slate-600 hover:text-sky-600 hover:bg-slate-100 transition relative">
                        <i class="bi bi-bell-fill text-lg"></i>
                        <span x-show="unread > 0" class="absolute top-1.5 right-1.5 bg-rose-500 text-white text-[10px] font-black rounded-full w-4 h-4 flex items-center justify-center shadow-sm" x-text="unread"></span>
                    </button>

                    <div x-show="notifOpen" @click.away="notifOpen = false" x-transition class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden" x-cloak>
                        <div class="p-3.5 bg-ocean-900 text-white flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="bi bi-bell-fill text-sky-400"></i>
                                <span class="font-extrabold text-xs tracking-wider uppercase">Notifications</span>
                                <span x-show="unread > 0" class="bg-rose-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full" x-text="unread + ' new'"></span>
                            </div>
                            <button @click="markAllRead()" class="text-[11px] text-sky-300 hover:text-white font-semibold transition">
                                Mark all read
                            </button>
                        </div>

                        <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                            @php $notifs = auth()->user()->customNotifications()->latest()->take(10)->get(); @endphp
                            @forelse($notifs as $notif)
                            @php
                                $typeIcons = [
                                    'booking'   => ['icon' => 'bi-calendar-check', 'bg' => 'bg-sky-50 text-sky-600 border-sky-200'],
                                    'payment'   => ['icon' => 'bi-credit-card-2-front', 'bg' => 'bg-emerald-50 text-emerald-600 border-emerald-200'],
                                    'review'    => ['icon' => 'bi-star-fill', 'bg' => 'bg-amber-50 text-amber-600 border-amber-200'],
                                    'system'    => ['icon' => 'bi-info-circle-fill', 'bg' => 'bg-purple-50 text-purple-600 border-purple-200'],
                                ];
                                $meta = $typeIcons[$notif->type] ?? ['icon' => 'bi-bell-fill', 'bg' => 'bg-slate-50 text-slate-600 border-slate-200'];
                            @endphp
                            <div @click="markSingleRead({{ $notif->id }}, $el)" class="p-3.5 flex gap-3 hover:bg-slate-50 transition cursor-pointer {{ $notif->read_at ? 'bg-white' : 'bg-sky-50/70 notif-item-unread' }}">
                                <div class="w-8 h-8 rounded-xl border flex items-center justify-center flex-shrink-0 text-sm {{ $meta['bg'] }}">
                                    <i class="bi {{ $meta['icon'] }}"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-1 mb-0.5">
                                        <p class="text-xs font-bold text-slate-900 truncate mb-0">{{ $notif->title }}</p>
                                        @if(!$notif->read_at)
                                        <span class="w-2 h-2 rounded-full bg-sky-500 flex-shrink-0"></span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-slate-600 mb-1 leading-snug">{{ $notif->message }}</p>
                                    <span class="text-[10px] text-slate-400 font-medium">{{ $notif->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            @empty
                            <div class="p-8 text-center text-slate-400">
                                <i class="bi bi-bell-slash text-3xl block mb-2 text-slate-300"></i>
                                <p class="text-xs font-semibold mb-0">No notifications yet</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- User Profile Pill Dropdown --}}
                <div class="relative" x-data="{ userMenu: false }">
                    <button @click="userMenu = !userMenu" class="flex items-center gap-2.5 p-1.5 sm:px-3 sm:py-1.5 rounded-xl hover:bg-slate-100 transition border border-transparent hover:border-slate-200">
                        @if(auth()->user()->avatar)
                            <img src="{{ Storage::url(auth()->user()->avatar) }}" class="w-8 h-8 rounded-lg object-cover border border-slate-200" alt="Avatar">
                        @else
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-ocean-800 to-sky-500 flex items-center justify-center text-white font-bold text-xs shadow-sm">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="hidden sm:block text-left">
                            <div class="text-xs font-bold text-slate-800 leading-tight">{{ auth()->user()->name }}</div>
                            <div class="text-[10px] font-semibold text-slate-400">
                                @if(auth()->user()->isAdmin())
                                    <span class="text-sky-600 font-bold uppercase">Administrator</span>
                                @elseif(auth()->user()->isStaff())
                                    <span class="text-sky-600 font-bold">{{ auth()->user()->staff_id ?? 'Staff' }}</span> • {{ auth()->user()->position ?? 'Resort Staff' }}
                                @else
                                    <span class="capitalize">{{ auth()->user()->role }}</span>
                                @endif
                            </div>
                        </div>
                        <i class="bi bi-chevron-down text-[10px] text-slate-400 ms-1"></i>
                    </button>

                    <div x-show="userMenu" @click.away="userMenu = false" x-transition class="absolute right-0 mt-2 w-52 bg-white rounded-2xl shadow-xl border border-slate-200 z-50 overflow-hidden py-1" x-cloak>
                        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/50">
                            <p class="text-xs font-bold text-slate-900 mb-0.5">{{ auth()->user()->name }}</p>
                            <p class="text-[11px] text-slate-500 truncate mb-0">{{ auth()->user()->email }}</p>
                        </div>
                        <a href="{{ route('profile.show') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-sky-50 hover:text-sky-700 transition no-underline">
                            <i class="bi bi-person-circle text-slate-400"></i>My Profile
                        </a>
                        @if(auth()->user()->isAdmin())
                        <a href="{{ route('settings.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-sky-50 hover:text-sky-700 transition no-underline">
                            <i class="bi bi-gear text-slate-400"></i>Settings
                        </a>
                        @endif
                        <div class="border-t border-slate-100 my-1"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition border-0 bg-transparent text-left">
                                <i class="bi bi-box-arrow-right text-rose-500"></i>Sign Out
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </header>

        {{-- Main Content Page Injection --}}
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
            
            {{-- Toast Flash Alerts --}}
            @if(session('success'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3.5 rounded-2xl flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <i class="bi bi-check-circle-fill text-emerald-500 text-lg"></i>
                    <span class="text-sm font-semibold">{{ session('success') }}</span>
                </div>
                <button type="button" class="btn-close text-xs" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3.5 rounded-2xl flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <i class="bi bi-exclamation-triangle-fill text-rose-500 text-lg"></i>
                    <span class="text-sm font-semibold">{{ session('error') }}</span>
                </div>
                <button type="button" class="btn-close text-xs" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @yield('content')
        </main>

        {{-- Footer --}}
        <footer class="mt-auto py-4 px-6 border-t border-slate-200/80 bg-white text-center sm:flex sm:items-center sm:justify-between text-xs text-slate-400">
            <div>
                © {{ date('Y') }} <span class="font-bold text-slate-700">Talisay Beach Resort</span>. Smart Tourism System.
            </div>
            <div class="mt-1 sm:mt-0 font-medium text-slate-400">
                Baybay City, Leyte, Philippines
            </div>
        </footer>
    </div>

    {{-- Floating Chatbot Widget (Role-Based) --}}
    <div class="chatbot-bubble" @click="chatOpen = !chatOpen" title="Open Resort Assistant" id="chatbot-bubble">
        <i class="bi" :class="chatOpen ? 'bi-x-lg text-xl' : 'bi-chat-dots-fill text-2xl'"></i>
    </div>

    <div class="chatbot-panel" :class="{ 'open': chatOpen }" id="chatbot-panel">
        {{-- Header (dynamic bg per role) --}}
        <div class="chatbot-header" :class="'role-' + chatRole">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-white/15 border border-white/20 flex items-center justify-center flex-shrink-0 text-white shadow-sm">
                    <i class="bi bi-robot text-lg"></i>
                </div>
                <div>
                    <h4 class="font-bold text-sm mb-0 text-white tracking-tight">Talisay Assistant</h4>
                    <span class="text-[9px] font-extrabold uppercase tracking-wider bg-white/20 text-white px-2 py-0.5 rounded-full inline-block mt-0.5" x-text="chatModeLabel"></span>
                </div>
            </div>
            <button @click="chatOpen = false" type="button" class="text-white/75 hover:text-white w-8 h-8 rounded-lg flex items-center justify-center hover:bg-white/15 transition flex-shrink-0" aria-label="Close Chatbot">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>

        {{-- Messages --}}
        <div class="chat-messages" x-ref="chatBox">
            <template x-for="msg in chatMessages" :key="msg.id">
                <div class="chat-msg" :class="msg.type" x-html="formatChatMessage(msg.text)"></div>
            </template>
            <div x-show="chatTyping" class="chat-msg bot flex items-center gap-1.5 py-2.5 px-3.5" style="display:none;">
                <span class="inline-block w-2 h-2 rounded-full bg-slate-400 animate-bounce"></span>
                <span class="inline-block w-2 h-2 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 0.2s"></span>
                <span class="inline-block w-2 h-2 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 0.4s"></span>
            </div>
        </div>

        {{-- Quick Chips + Input --}}
        <div class="p-3 bg-white border-t border-slate-200/90 flex-shrink-0">
            <div class="chatbot-chips-wrap" x-show="chatChips && chatChips.length > 0">
                <template x-for="chip in chatChips" :key="chip">
                    <button @click="sendChip(chip)" type="button" class="chatbot-chip-btn" x-text="chip"></button>
                </template>
            </div>
            <div class="flex gap-2 items-center">
                <input type="text" x-model="chatInput" @keydown.enter.prevent="sendChat()" placeholder="Type a message..." class="flex-1 form-control-clean text-xs rounded-full py-2 px-3.5 border border-slate-300 focus:border-sky-500" id="chat-input" autocomplete="off">
                <button @click="sendChat()" type="button" class="w-9 h-9 rounded-full bg-sky-600 hover:bg-sky-700 text-white flex items-center justify-center transition flex-shrink-0 shadow-sm" id="chat-send-btn" aria-label="Send Message">
                    <i class="bi bi-send-fill text-xs"></i>
                </button>
            </div>
        </div>
    </div>
@else
    @yield('content')
@endif

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    // Pusher setup for real-time features
    @if(auth()->check() && config('broadcasting.default') === 'pusher')
    var pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {
        cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
        wsHost: '{{ config('broadcasting.connections.pusher.options.host') }}',
        wsPort: {{ config('broadcasting.connections.pusher.options.port') }},
        wssPort: {{ config('broadcasting.connections.pusher.options.port') }},
        forceTLS: {{ config('broadcasting.connections.pusher.options.scheme') === 'https' ? 'true' : 'false' }},
        disableStats: true,
        enabledTransports: ['ws', 'wss']
    });

    @if(auth()->user()->isAdmin() || auth()->user()->isStaff())
    var staffBookingChannel = pusher.subscribe('staff-bookings');
    staffBookingChannel.bind('booking.created', function(data) {
        window.dispatchEvent(new CustomEvent('booking-created', { detail: data }));
    });

    @if(auth()->user()->isAdmin())
    var adminPaymentChannel = pusher.subscribe('admin-payments');
    adminPaymentChannel.bind('payment.received', function(data) {
        window.dispatchEvent(new CustomEvent('payment-received', { detail: data }));
    });
    @endif
    @endif
    @endif

    // Chatbot functionality
    function formatChatMessage(text) {
        if (!text) return '';
        let escaped = text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");

        // Bullet points formatting
        escaped = escaped.replace(/^[•\-\*]\s+(.*)$/gm, '<div class="flex items-start gap-1.5 my-0.5"><span class="text-sky-500 font-bold leading-tight">•</span><span>$1</span></div>');

        return escaped
            .replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-slate-900">$1</strong>')
            .replace(/\n/g, '<br>');
    }

    function sendChip(text) {
        const alpine = Alpine.$data(document.querySelector('body'));
        if (!alpine) return;
        alpine.chatInput = text;
        sendChat();
    }

    async function sendChat() {
        const bodyEl = document.querySelector('body');
        const alpine = Alpine.$data(bodyEl);
        if (!alpine) return;

        const message = (alpine.chatInput || '').trim();
        if (!message || alpine.chatTyping) return;

        const sessionId = alpine.chatSessionId || ('web-' + Date.now());
        alpine.chatMessages.push({ id: Date.now(), type: 'user', text: message });
        alpine.chatInput = '';
        alpine.chatTyping = true;

        setTimeout(() => {
            const chatBox = document.querySelector('[x-ref="chatBox"]');
            if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
        }, 50);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const history = alpine.chatMessages.map(m => ({
                role: m.type === 'user' ? 'user' : 'bot',
                text: m.text
            })).slice(-6);

            const res = await fetch('/api/chatbot/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    session_id: sessionId,
                    message: message,
                    history: history
                })
            });
            const data = await res.json();

            alpine.chatTyping = false;
            const reply = data.response || data.reply || 'I am not sure about that. Would you like to speak with our staff?';
            alpine.chatMessages.push({ id: Date.now() + 1, type: 'bot', text: reply });
            if (data.chips && Array.isArray(data.chips) && data.chips.length > 0) {
                alpine.chatChips = data.chips;
            }
        } catch (e) {
            alpine.chatTyping = false;
            alpine.chatMessages.push({ id: Date.now() + 1, type: 'bot', text: 'Sorry, I am having trouble connecting. Please try again.' });
        }

        setTimeout(() => {
            const chatBox = document.querySelector('[x-ref="chatBox"]');
            if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
        }, 100);
    }
</script>
{{-- Bootstrap 5 JS Bundle (needed for modal triggers and popovers) --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
