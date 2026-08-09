<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Talisay Smart Tourism')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('styles')
    <style>
        :root {
            --primary: #0ea5e9;
            --accent: #14b8a6;
            --sand: #f5e8c7;
            --navy: #1e293b;
            --sidebar-width: 260px;
        }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: linear-gradient(180deg, #0c4a6e 0%, #164e63 100%);
            transition: all 0.3s ease;
            z-index: 1040;
        }
        .sidebar.collapsed { width: 70px; }
        .sidebar.collapsed .sidebar-text { display: none; }
        .sidebar.collapsed .sidebar-logo-text { display: none; }
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.2s ease;
            border-radius: 8px;
            margin: 2px 8px;
        }
        .sidebar-link:hover, .sidebar-link.active {
            background: rgba(255,255,255,0.12);
            color: #fff;
        }
        .sidebar-link.active { border-left: 3px solid var(--accent); }
        .main-content {
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
            min-height: 100vh;
            background: #f1f5f9;
        }
        .main-content.expanded { margin-left: 70px; }
        .stat-card {
            border-radius: 12px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .badge-emergency {
            animation: pulse-red 2s infinite;
        }
        @keyframes pulse-red {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .capacity-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
        }
        .chatbot-bubble {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1050;
            box-shadow: 0 4px 15px rgba(14,165,233,0.4);
            transition: transform 0.2s;
        }
        .chatbot-bubble:hover { transform: scale(1.1); }
        .chatbot-panel {
            position: fixed;
            bottom: 90px;
            right: 24px;
            width: 360px;
            max-height: 500px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            z-index: 1050;
            display: none;
            flex-direction: column;
            overflow: hidden;
        }
        .chatbot-panel.open { display: flex; }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            max-height: 350px;
        }
        .chat-msg {
            margin-bottom: 12px;
            padding: 10px 14px;
            border-radius: 12px;
            max-width: 85%;
            font-size: 14px;
            line-height: 1.4;
        }
        .chat-msg.bot {
            background: #f0f9ff;
            color: var(--navy);
            border-bottom-left-radius: 4px;
        }
        .chat-msg.user {
            background: var(--primary);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); }
            .sidebar.mobile-open { transform: translateX(0); }
            .main-content { margin-left: 0 !important; }
            .chatbot-panel { width: calc(100vw - 48px); right: 24px; }
        }
    </style>
</head>
<body class="bg-gray-100" x-data="{ sidebarOpen: true, mobileSidebar: false, chatOpen: false, chatMessages: [], chatInput: '', chatSessionId: 'web-' + Date.now() }">

@if(auth()->check())
    {{-- Sidebar --}}
    <aside class="sidebar fixed top-0 left-0 overflow-y-auto" :class="{ 'collapsed': !sidebarOpen, 'mobile-open': mobileSidebar }">
        <div class="p-4 flex items-center gap-3 border-b border-white/10">
            <img src="{{ asset('images/logo.png') }}" alt="Talisay Beach Resort Logo" class="w-10 h-10 rounded-lg object-cover flex-shrink-0 border border-white/20 shadow-sm">
            <span class="sidebar-text sidebar-logo-text text-white font-bold text-lg">Talisay Smart Tourism</span>
        </div>

        <nav class="mt-4 pb-4">
            {{-- Dashboard - visible to admin & staff --}}
            @if(auth()->user()->isAdmin() || auth()->user()->isStaff())
            <a href="{{ route(auth()->user()->isAdmin() ? 'admin.dashboard' : 'staff.dashboard') }}" class="sidebar-link {{ request()->routeIs('*.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill me-3"></i>
                <span class="sidebar-text">Dashboard</span>
            </a>
            @endif

            {{-- Bookings - admin & staff --}}
            @if(auth()->user()->isAdmin() || auth()->user()->isStaff())
            <a href="{{ route('bookings.index') }}" class="sidebar-link {{ request()->routeIs('bookings.*') ? 'active' : '' }}">
                <i class="bi bi-calendar-check-fill me-3"></i>
                <span class="sidebar-text">Bookings</span>
            </a>
            @endif

            {{-- Packages - admin only --}}
            @if(auth()->user()->isAdmin())
            <a href="{{ route('packages.index') }}" class="sidebar-link {{ request()->routeIs('packages.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam-fill me-3"></i>
                <span class="sidebar-text">Packages</span>
            </a>
            @endif

            {{-- Users - admin only --}}
            @if(auth()->user()->isAdmin())
            <a href="{{ route('users.index') }}" class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="bi bi-people-fill me-3"></i>
                <span class="sidebar-text">Users</span>
            </a>
            @endif

            {{-- Payments - admin & staff --}}
            @if(auth()->user()->isAdmin() || auth()->user()->isStaff())
            <a href="{{ route('payments.index') }}" class="sidebar-link {{ request()->routeIs('payments.*') ? 'active' : '' }}">
                <i class="bi bi-credit-card-fill me-3"></i>
                <span class="sidebar-text">Payments</span>
            </a>
            @endif

            {{-- Emergencies - admin & staff --}}
            @if(auth()->user()->isAdmin() || auth()->user()->isStaff())
            <a href="{{ route('emergencies.index') }}" class="sidebar-link {{ request()->routeIs('emergencies.*') ? 'active' : '' }}">
                <i class="bi bi-exclamation-triangle-fill me-3"></i>
                <span class="sidebar-text">Emergencies</span>
                @php $emergCount = \App\Models\Emergency::unresolved()->count(); @endphp
                @if($emergCount > 0)
                <span class="badge bg-danger ms-auto sidebar-text badge-emergency">{{ $emergCount }}</span>
                @endif
            </a>
            @endif

            {{-- Chatbot - admin only --}}
            @if(auth()->user()->isAdmin())
            <a href="{{ route('chatbot.index') }}" class="sidebar-link {{ request()->routeIs('chatbot.*') ? 'active' : '' }}">
                <i class="bi bi-chat-dots-fill me-3"></i>
                <span class="sidebar-text">Chatbot</span>
            </a>
            @endif

            {{-- Memory Timelines - admin only --}}
            @if(auth()->user()->isAdmin())
            <a href="{{ route('memory-timelines.index') }}" class="sidebar-link {{ request()->routeIs('memory-timelines.*') ? 'active' : '' }}">
                <i class="bi bi-camera-fill me-3"></i>
                <span class="sidebar-text">Memory Timelines</span>
            </a>
            @endif

            {{-- Virtual Tour - admin only --}}
            @if(auth()->user()->isAdmin())
            <a href="{{ route('tour.manage') }}" class="sidebar-link {{ request()->routeIs('tour.*') ? 'active' : '' }}">
                <i class="bi bi-geo-alt-fill me-3"></i>
                <span class="sidebar-text">Virtual Tour</span>
            </a>
            @endif

            {{-- Reviews - admin only --}}
            @if(auth()->user()->isAdmin())
            <a href="{{ route('reviews.index') }}" class="sidebar-link {{ request()->routeIs('reviews.*') ? 'active' : '' }}">
                <i class="bi bi-star-fill me-3"></i>
                <span class="sidebar-text">Reviews</span>
            </a>
            @endif

            {{-- Reports - admin only --}}
            @if(auth()->user()->isAdmin())
            <a href="{{ route('reports.index') }}" class="sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-fill me-3"></i>
                <span class="sidebar-text">Reports</span>
            </a>
            @endif

            {{-- Settings - admin only --}}
            @if(auth()->user()->isAdmin())
            <a href="{{ route('settings.index') }}" class="sidebar-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <i class="bi bi-gear-fill me-3"></i>
                <span class="sidebar-text">Settings</span>
            </a>
            @endif
        </nav>
    </aside>

    {{-- Top Navbar --}}
    <div class="main-content" :class="{ 'expanded': !sidebarOpen }">
        <nav class="bg-white shadow-sm px-4 py-3 flex items-center justify-between sticky top-0 z-30">
            <div class="flex items-center gap-3">
                <button @click="window.innerWidth < 768 ? mobileSidebar = !mobileSidebar : sidebarOpen = !sidebarOpen" class="text-gray-600 hover:text-gray-900">
                    <i class="bi bi-list text-2xl"></i>
                </button>
                <div class="relative hidden md:block">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" placeholder="Search bookings, guests..." class="pl-10 pr-4 py-2 bg-gray-100 rounded-lg border-0 focus:ring-2 focus:ring-sky-400 text-sm w-72">
                </div>
            </div>

            <div class="flex items-center gap-4">
                {{-- Notification Bell --}}
                <div class="relative" x-data="{ notifOpen: false }">
                    <button @click="notifOpen = !notifOpen" class="text-gray-600 hover:text-sky-600 relative">
                        <i class="bi bi-bell text-xl"></i>
                        @php $unreadCount = auth()->user()->unreadNotificationCount(); @endphp
                        @if($unreadCount > 0)
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">{{ $unreadCount }}</span>
                        @endif
                    </button>
                    <div x-show="notifOpen" @click.away="notifOpen = false" x-transition class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border z-50">
                        <div class="p-3 border-b font-semibold text-sm">Notifications</div>
                        <div class="max-h-64 overflow-y-auto">
                            @php $notifs = auth()->user()->customNotifications()->latest()->take(5)->get(); @endphp
                            @foreach($notifs as $notif)
                            <div class="p-3 border-b hover:bg-gray-50 {{ $notif->read_at ? '' : 'bg-sky-50' }}">
                                <p class="text-sm font-medium">{{ $notif->title }}</p>
                                <p class="text-xs text-gray-500">{{ $notif->message }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ $notif->created_at->diffForHumans() }}</p>
                            </div>
                            @endforeach
                            @if($notifs->isEmpty())
                            <div class="p-4 text-center text-gray-400 text-sm">No notifications</div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- User Dropdown --}}
                <div class="relative" x-data="{ userMenu: false }">
                    <button @click="userMenu = !userMenu" class="flex items-center gap-2 hover:bg-gray-100 rounded-lg px-3 py-2">
                        @if(auth()->user()->avatar)
                            <img src="{{ Storage::url(auth()->user()->avatar) }}" class="w-8 h-8 rounded-full object-cover" alt="Avatar">
                        @else
                            <div class="w-8 h-8 rounded-full bg-sky-500 flex items-center justify-center text-white font-bold text-sm">
                                {{ strtoupper(auth()->user()->name[0]) }}
                            </div>
                        @endif
                        <span class="text-sm font-medium hidden md:block">{{ auth()->user()->name }}</span>
                        <i class="bi bi-chevron-down text-xs"></i>
                    </button>
                    <div x-show="userMenu" @click.away="userMenu = false" x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border z-50">
                        <div class="px-4 py-2 border-b">
                            <p class="text-sm font-semibold">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500">{{ ucfirst(auth()->user()->role) }}</p>
                        </div>
                        <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm hover:bg-gray-50"><i class="bi bi-person me-2"></i>Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            @method('POST')
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 text-red-600"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        {{-- Main Content Area --}}
        <main class="p-6">
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @yield('content')
        </main>
    </div>

    {{-- Chatbot Widget --}}
    <div class="chatbot-bubble" @click="chatOpen = !chatOpen">
        <i class="bi bi-chat-dots-fill text-2xl"></i>
    </div>
    <div class="chatbot-panel" :class="{ 'open': chatOpen }">
        <div class="bg-sky-500 text-white p-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="bi bi-robot text-xl"></i>
                <span class="font-semibold">Talisay Assistant</span>
            </div>
            <button @click="chatOpen = false" class="text-white/80 hover:text-white"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="chat-messages" x-ref="chatBox">
            <div class="chat-msg bot">
                Welcome to Talisay Beach Resort! How can I help you today?
            </div>
            <template x-for="msg in chatMessages" :key="msg.id">
                <div class="chat-msg" :class="msg.type" x-text="msg.text"></div>
            </template>
        </div>
        <div class="p-3 border-t">
            <div class="flex gap-2 mb-2 flex-wrap" x-show="chatMessages.length === 0">
                <button @click="sendChip('Rates')" class="text-xs bg-sky-100 text-sky-700 px-3 py-1 rounded-full hover:bg-sky-200">Rates</button>
                <button @click="sendChip('Hours')" class="text-xs bg-sky-100 text-sky-700 px-3 py-1 rounded-full hover:bg-sky-200">Hours</button>
                <button @click="sendChip('Directions')" class="text-xs bg-sky-100 text-sky-700 px-3 py-1 rounded-full hover:bg-sky-200">Directions</button>
                <button @click="sendChip('Book Now')" class="text-xs bg-sky-100 text-sky-700 px-3 py-1 rounded-full hover:bg-sky-200">Book Now</button>
            </div>
            <div class="flex gap-2">
                <input type="text" x-model="chatInput" @keydown.enter="sendChat()" placeholder="Type a message..." class="flex-1 border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-400">
                <button @click="sendChat()" class="bg-sky-500 text-white px-4 py-2 rounded-lg hover:bg-sky-600"><i class="bi bi-send"></i></button>
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
    // Staff channel - bookings
    var staffBookingChannel = pusher.subscribe('staff-bookings');
    staffBookingChannel.bind('booking.created', function(data) {
        console.log('New booking:', data);
        // Dispatch custom event for pages to listen to
        window.dispatchEvent(new CustomEvent('booking-created', { detail: data }));
    });

    // Staff channel - emergencies
    var staffEmergencyChannel = pusher.subscribe('staff-emergencies');
    staffEmergencyChannel.bind('emergency.new', function(data) {
        console.log('New emergency:', data);
        window.dispatchEvent(new CustomEvent('emergency-created', { detail: data }));
    });

    @if(auth()->user()->isAdmin())
    // Admin channel - payments
    var adminPaymentChannel = pusher.subscribe('admin-payments');
    adminPaymentChannel.bind('payment.received', function(data) {
        console.log('Payment received:', data);
        window.dispatchEvent(new CustomEvent('payment-received', { detail: data }));
    });
    @endif
    @endif
    @endif

    // Chatbot functions
    function sendChip(text) {
        Alpine.store('chatInput', text);
        document.querySelector('[x-model="chatInput"]').value = text;
        sendChat();
    }

    async function sendChat() {
        const input = document.querySelector('[x-model="chatInput"]');
        const message = input ? input.value.trim() : '';
        if (!message) return;

        const sessionId = Date.now().toString();
        const chatMessages = Alpine.$data(document.querySelector('body')).chatMessages;

        // Add user message
        chatMessages.push({ id: Date.now(), type: 'user', text: message });
        input.value = '';

        try {
            const res = await fetch('/api/chatbot/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ session_id: sessionId, message: message })
            });
            const data = await res.json();

            // Add bot response
            chatMessages.push({ id: Date.now() + 1, type: 'bot', text: data.response || 'I am not sure about that. Would you like to speak with our staff?' });

            // Auto-scroll
            setTimeout(() => {
                const chatBox = document.querySelector('[x-ref="chatBox"]');
                if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
            }, 100);
        } catch (e) {
            chatMessages.push({ id: Date.now() + 1, type: 'bot', text: 'Sorry, I am having trouble connecting. Please try again.' });
        }
    }
</script>
@stack('scripts')
</body>
</html>
