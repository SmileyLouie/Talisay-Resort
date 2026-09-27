<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Guest Portal - Talisay Beach Resort')</title>

    {{-- Fonts & Icons --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Tailwind CSS CDN --}}
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

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .bg-ocean-gradient { background: linear-gradient(135deg, #0c4a6e 0%, #0284c7 60%, #0ea5e9 100%); }
        .glass-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(12px); }
        .chat-widget-box { height: 420px; }
        .chat-msg { max-width: 85%; padding: 10px 14px; border-radius: 16px; font-size: 0.88rem; margin-bottom: 10px; }
        .chat-msg.bot { background: #e0f2fe; color: #075985; border-bottom-left-radius: 4px; align-self: flex-start; }
        .chat-msg.user { background: #0284c7; color: #ffffff; border-bottom-right-radius: 4px; align-self: flex-end; margin-left: auto; }
    </style>
</head>
<body class="h-full flex flex-col text-slate-800 antialiased" x-data="{ chatOpen: false, mobileMenuOpen: false }">

    {{-- ── Top Navigation Bar ─────────────────────────────────────────── --}}
    <nav class="bg-ocean-900 text-white sticky top-0 z-40 shadow-lg border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">

                {{-- Brand Logo + Title --}}
                <a href="{{ route('tourist.dashboard') }}" class="flex items-center gap-3 no-underline group">
                    <img src="{{ asset('images/logo.png') }}" alt="Talisay Logo" class="w-11 h-11 rounded-xl border-2 border-sky-400 shadow-md group-hover:scale-105 transition-transform">
                    <div>
                        <span class="text-lg font-extrabold tracking-tight text-white block leading-tight">Talisay Beach Resort</span>
                        <span class="text-xs font-semibold text-sky-300 tracking-wider uppercase block">Guest Web Portal</span>
                    </div>
                </a>

                {{-- Navigation Links (Desktop) --}}
                <div class="hidden md:flex items-center space-x-1">
                    <a href="{{ route('tourist.dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('tourist.dashboard') ? 'bg-white/15 text-white' : 'text-sky-100 hover:bg-white/10 hover:text-white' }} no-underline">
                        <i class="bi bi-house-door me-1.5"></i>Home
                    </a>
                    <a href="{{ route('tourist.bookings') }}" class="px-3 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('tourist.bookings') ? 'bg-white/15 text-white' : 'text-sky-100 hover:bg-white/10 hover:text-white' }} no-underline">
                        <i class="bi bi-calendar-check me-1.5"></i>My Bookings
                    </a>
                    <a href="{{ route('tourist.accommodations') }}" class="px-3 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('tourist.accommodations*') ? 'bg-white/15 text-white' : 'text-sky-100 hover:bg-white/10 hover:text-white' }} no-underline">
                        <i class="bi bi-building me-1.5"></i>Rooms & Cottages
                    </a>
                    <a href="{{ route('tour.viewer') }}" target="_blank" class="px-3 py-2 rounded-lg text-sm font-semibold text-sky-100 hover:bg-white/10 hover:text-white transition no-underline">
                        <i class="bi bi-camera-video me-1.5"></i>360° Virtual Tour
                    </a>
                    <a href="{{ route('tourist.reviews') }}" class="px-3 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('tourist.reviews') ? 'bg-white/15 text-white' : 'text-sky-100 hover:bg-white/10 hover:text-white' }} no-underline">
                        <i class="bi bi-star me-1.5"></i>My Reviews
                    </a>
                </div>

                {{-- Right Buttons: Notifications + User Menu + Mobile Hamburger --}}
                <div class="flex items-center gap-2.5">
                    @auth
                    {{-- Notification Bell --}}
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
                                document.querySelectorAll('.tourist-notif-unread').forEach(el => {
                                    el.classList.remove('bg-sky-50/70', 'tourist-notif-unread');
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
                                el.classList.remove('bg-sky-50/70', 'tourist-notif-unread');
                                el.classList.add('bg-white');
                            } catch (e) {}
                        }
                    }">
                        <button @click="notifOpen = !notifOpen" class="relative p-2 rounded-full bg-white/10 hover:bg-white/20 text-white transition">
                            <i class="bi bi-bell-fill text-base"></i>
                            <span x-show="unread > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-extrabold rounded-full w-5 h-5 flex items-center justify-center shadow" x-text="unread"></span>
                        </button>

                        <div x-show="notifOpen" @click.outside="notifOpen = false" x-transition class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden text-slate-800" style="width: 22rem;" x-cloak>
                            <div class="p-3.5 bg-ocean-900 text-white flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i class="bi bi-bell-fill text-sky-400"></i>
                                    <span class="font-extrabold text-xs tracking-wider uppercase">Notifications</span>
                                    <span x-show="unread > 0" class="bg-red-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full" x-text="unread + ' new'"></span>
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
                                <div @click="markSingleRead({{ $notif->id }}, $el)" class="p-3.5 flex gap-3 hover:bg-slate-50 transition cursor-pointer {{ $notif->read_at ? 'bg-white' : 'bg-sky-50/70 tourist-notif-unread' }}">
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

                    {{-- Profile / Logout Dropdown --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center gap-2 bg-white/10 hover:bg-white/20 p-1.5 pr-3 rounded-full transition border border-white/10">
                            <div class="w-8 h-8 rounded-full bg-sky-500 text-white font-bold text-xs flex items-center justify-center border border-white/30">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <span class="text-xs font-semibold text-white hidden sm:inline">{{ auth()->user()->name }}</span>
                            <i class="bi bi-chevron-down text-xs text-sky-200"></i>
                        </button>

                        <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-slate-100 py-1 text-slate-700 z-50">
                            <div class="px-4 py-2 border-b border-slate-100">
                                <p class="text-xs font-bold text-slate-800 mb-0">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-500 mb-0 truncate">{{ auth()->user()->email }}</p>
                            </div>
                            <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 no-underline">
                                <i class="bi bi-person me-2 text-sky-600"></i>My Profile
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 flex items-center">
                                    <i class="bi bi-box-arrow-right me-2"></i>Log Out
                                </button>
                            </form>
                        </div>
                    </div>
                    @else
                    <a href="{{ route('login') }}" class="px-3.5 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold no-underline transition border border-white/20">Log In</a>
                    <a href="{{ route('register') }}" class="px-3.5 py-1.5 rounded-xl bg-sky-500 hover:bg-sky-400 text-white text-xs font-bold no-underline transition shadow">Register</a>
                    @endauth

                    {{-- Mobile Hamburger Toggle Button --}}
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-white p-2 rounded-lg bg-white/10 hover:bg-white/20 text-lg">
                        <i class="bi" :class="mobileMenuOpen ? 'bi-x-lg' : 'bi-list'"></i>
                    </button>
                </div>

            </div>
        </div>

        {{-- Mobile Dropdown Menu --}}
        <div x-show="mobileMenuOpen" x-transition class="md:hidden bg-ocean-950 border-t border-white/10 px-4 pt-3 pb-4 space-y-1">
            <a href="{{ route('tourist.dashboard') }}" class="block px-3 py-2.5 rounded-lg text-sm font-semibold text-white hover:bg-white/10 no-underline">
                <i class="bi bi-house-door me-2"></i>Home
            </a>
            <a href="{{ route('tourist.bookings') }}" class="block px-3 py-2.5 rounded-lg text-sm font-semibold text-white hover:bg-white/10 no-underline">
                <i class="bi bi-calendar-check me-2"></i>My Bookings
            </a>
            <a href="{{ route('tourist.accommodations') }}" class="block px-3 py-2.5 rounded-lg text-sm font-semibold text-white hover:bg-white/10 no-underline">
                <i class="bi bi-building me-2"></i>Rooms & Cottages
            </a>
            <a href="{{ route('tour.viewer') }}" target="_blank" class="block px-3 py-2.5 rounded-lg text-sm font-semibold text-sky-200 hover:bg-white/10 no-underline">
                <i class="bi bi-camera-video me-2"></i>360° Virtual Tour
            </a>
            <a href="{{ route('tourist.reviews') }}" class="block px-3 py-2.5 rounded-lg text-sm font-semibold text-white hover:bg-white/10 no-underline">
                <i class="bi bi-star me-2"></i>My Reviews
            </a>
        </div>
    </nav>

    {{-- Flash Notifications --}}
    @if(session('success'))
    <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <i class="bi bi-check-circle-fill text-emerald-600 text-lg"></i>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800"><i class="bi bi-x-lg"></i></button>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-red-600 text-lg"></i>
                <span class="text-sm font-semibold">{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-800"><i class="bi bi-x-lg"></i></button>
        </div>
    </div>
    @endif

    {{-- Main Content --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- ── Footer ─────────────────────────────────────────────────────── --}}
    <footer class="bg-slate-900 text-slate-400 py-8 border-t border-slate-800 mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <div class="flex items-center justify-center gap-2 mb-3">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-6 h-6 rounded-md">
                <span class="text-sm font-bold text-white">Talisay Beach Resort Smart Tourism</span>
            </div>
            <p class="text-xs text-slate-500 mb-0">&copy; 2026 Talisay Beach Resort. Barangay Maslug, Baybay City, Leyte. All rights reserved.</p>
        </div>
    </footer>

    {{-- ── Floating AI Chatbot Widget ──────────────────────────────────── --}}
    <style>
        .chat-msg-user  { align-self:flex-end; background:#0c4a6e; color:#fff; border-radius:18px 18px 4px 18px; padding:8px 14px; max-width:82%; font-size:13px; line-height:1.5; }
        .chat-msg-bot   { align-self:flex-start; background:#fff; color:#1e293b; border:1px solid #e2e8f0; border-radius:18px 18px 18px 4px; padding:8px 14px; max-width:88%; font-size:13px; line-height:1.6; white-space:pre-wrap; }
        .chat-chip      { display:inline-block; padding:4px 12px; border:1px solid #bae6fd; background:#f0f9ff; color:#0369a1; border-radius:99px; font-size:11px; cursor:pointer; transition:all .15s; margin:2px; }
        .chat-chip:hover{ background:#bae6fd; }
        .typing-dot     { width:7px; height:7px; background:#94a3b8; border-radius:50%; display:inline-block; animation:bounce .9s infinite; }
        .typing-dot:nth-child(2){ animation-delay:.2s; }
        .typing-dot:nth-child(3){ animation-delay:.4s; }
        @keyframes bounce{ 0%,80%,100%{ transform:translateY(0); } 40%{ transform:translateY(-7px); } }
    </style>
    <div class="fixed bottom-6 right-6 z-50" x-data="touristChatbot()">
        {{-- Chat Trigger Floating Button --}}
        <button @click="open = !open" style="background: linear-gradient(135deg, #0c4a6e 0%, #075985 60%, #0284c7 100%); box-shadow: 0 10px 28px rgba(12, 74, 110, 0.45);" class="w-14 h-14 text-white rounded-full flex items-center justify-center text-2xl transition transform hover:scale-108 border-2 border-white/40">
            <i class="bi" :class="open ? 'bi-x-lg' : 'bi-chat-dots-fill'"></i>
        </button>

        {{-- Chat Box Window --}}
        <div x-show="open" x-transition class="absolute bottom-16 right-0 w-80 sm:w-96 bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col chat-widget-box" x-cloak>
            {{-- Header --}}
            <div style="background: linear-gradient(135deg, #0c4a6e 0%, #075985 60%, #0284c7 100%);" class="text-white p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-500/30 flex items-center justify-center border border-sky-400">
                    <i class="bi bi-robot text-lg text-sky-200"></i>
                </div>
                <div class="flex-1">
                    <h4 class="text-sm font-extrabold mb-0">Talisay AI Assistant</h4>
                    <p class="text-xs text-sky-300 mb-0">Online • Real-time Resort Info</p>
                </div>
                <button @click="open = false" class="text-white/60 hover:text-white text-lg">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            {{-- Messages Area --}}
            <div class="flex-1 p-4 overflow-y-auto flex flex-col gap-2 bg-slate-50" style="max-height:380px;" x-ref="messagesBox">
                <div class="chat-msg-bot">
                    Hello {{ first_name(auth()->user()->name) }}! 👋 Welcome back to Talisay Beach Resort.
                    How can I help you today? You can check your bookings, inquire about room rates, payment methods, or explore the 360° virtual tour!
                </div>
                <div class="flex flex-wrap gap-1 mt-1">
                    <button @click="sendChip('My Bookings')" class="chat-chip">My Bookings</button>
                    <button @click="sendChip('Room Rates')" class="chat-chip">Room Rates</button>
                    <button @click="sendChip('GCash payment')" class="chat-chip">GCash Guide</button>
                    <button @click="sendChip('virtual tour')" class="chat-chip">360° Tour</button>
                    <button @click="sendChip('Check Availability')" class="chat-chip">Availability</button>
                </div>

                <template x-for="(msg, idx) in messages" :key="idx">
                    <div>
                        <div :class="msg.sender === 'user' ? 'chat-msg-user' : 'chat-msg-bot'" x-html="formatText(msg.text)"></div>
                        {{-- Quick chips after bot message --}}
                        <template x-if="msg.sender === 'bot' && msg.chips && msg.chips.length">
                            <div class="flex flex-wrap gap-1 mt-1 ml-1">
                                <template x-for="chip in msg.chips" :key="chip">
                                    <button @click="sendChip(chip)" class="chat-chip" x-text="chip"></button>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Typing indicator --}}
                <div x-show="typing" class="chat-msg-bot flex gap-1 items-center py-2 px-3">
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                </div>
            </div>

            {{-- Input Area --}}
            <form @submit.prevent="sendMessage()" class="p-3 bg-white border-t border-slate-100 flex gap-2">
                <input type="text" x-model="inputText"
                    placeholder="Ask about rates, booking, directions..."
                    class="flex-1 text-xs border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-ocean-500">
                <button type="submit" class="bg-ocean-600 text-white px-3.5 py-2 rounded-xl text-xs font-bold hover:bg-ocean-800 transition">
                    <i class="bi bi-send-fill"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        function touristChatbot() {
            const sessionId = 'tbs_' + Math.random().toString(36).substr(2, 12) + '_' + Date.now();
            return {
                open: false,
                inputText: '',
                messages: [],
                typing: false,
                sessionId: sessionId,

                formatText(text) {
                    if (!text) return '';
                    let escaped = text
                        .replace(/&/g, "&amp;")
                        .replace(/</g, "&lt;")
                        .replace(/>/g, "&gt;");

                    escaped = escaped.replace(/^[•\-\*]\s+(.*)$/gm, '<div class="flex items-start gap-1.5 my-0.5"><span class="text-sky-500 font-bold leading-tight">•</span><span>$1</span></div>');

                    return escaped
                        .replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-slate-900">$1</strong>')
                        .replace(/\n/g, '<br>');
                },

                sendChip(text) {
                    this.inputText = text;
                    this.sendMessage();
                },

                async sendMessage() {
                    const text = this.inputText.trim();
                    if (!text) return;

                    const history = this.messages.map(m => ({
                        role: m.sender === 'user' ? 'user' : 'bot',
                        text: m.text
                    })).slice(-6);

                    this.messages.push({ sender: 'user', text: text, chips: [] });
                    this.inputText = '';
                    this.typing = true;
                    this.scrollToBottom();

                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/chatbot/message', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                message: text,
                                session_id: this.sessionId,
                                history: history
                            })
                        });

                        const data = await res.json();
                        this.typing = false;
                        const reply = data.reply || data.response || 'I am here to help! You can ask about room rates, how to book, directions, payment methods, or the 360° virtual tour.';
                        const chips = data.chips || [];
                        this.messages.push({ sender: 'bot', text: reply, chips: chips });
                    } catch (e) {
                        this.typing = false;
                        this.messages.push({
                            sender: 'bot',
                            text: 'Sorry, I had trouble connecting. Please try again, or call us at +63 (053) 563-7000.',
                            chips: ['Room Rates', 'How to Book', 'Directions']
                        });
                    }

                    this.scrollToBottom();
                },

                scrollToBottom() {
                    this.$nextTick(() => {
                        const box = this.$refs.messagesBox;
                        if (box) box.scrollTop = box.scrollHeight;
                    });
                }
            }
        }
    </script>
    {{-- Bootstrap JS Bundle --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>

