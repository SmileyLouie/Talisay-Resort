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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/client-resort.css') }}?v=40">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Playfair+Display:wght@400;600&family=Julius+Sans+One&display=swap" rel="stylesheet">

    {{-- Alpine.js --}}
    <script defer src="{{ asset('vendor/alpine/alpine.min.js') }}"></script>

    <style>
        a, a:hover { text-decoration: none; }
        [x-cloak] { display: none !important; }
        body { font-family: "Plus Jakarta Sans", sans-serif; }
        .bg-ocean-gradient { background: #0c4a6e; }
        .bg-ocean-600,
        .bg-ocean-700,
        .bg-ocean-800,
        .hover\:bg-ocean-700:hover,
        .hover\:bg-ocean-800:hover {
            background-color: #0284c7 !important;
        }
        .glass-card { background: #ffffff; }
        .rounded-3xl { border-radius: 12px !important; }
        .rounded-2xl { border-radius: 8px !important; }
        .chat-widget-box { height: 420px; }
        .chat-msg { max-width: 85%; padding: 10px 14px; border-radius: 16px; font-size: 0.88rem; margin-bottom: 10px; }
        .chat-msg.bot { background: #e0f2fe; color: #075985; border-bottom-left-radius: 4px; align-self: flex-start; }
        .chat-msg.user { background: #0284c7; color: #ffffff; border-bottom-right-radius: 4px; align-self: flex-end; margin-left: auto; }
    </style>
</head>
<body class="h-full flex flex-col text-slate-800 antialiased client-resort" x-data="{ chatOpen: false, mobileMenuOpen: false }">

    {{-- â”€â”€ Top Navigation Bar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
    <nav class="resort-top sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                {{-- Brand Logo + Title --}}
                <a href="{{ url('/') }}" class="resort-brand flex items-center gap-3 no-underline">
                    <img src="{{ asset('images/logo.png') }}" alt="Talisay Logo" class="w-10 h-10 rounded-lg">
                    <div>
                        <span class="resort-brand-name block leading-tight">Talisay Beach Resort</span>
                        <span class="resort-brand-sub block">Smart Tourism System</span>
                    </div>
                </a>

                <div class="hidden md:flex items-center gap-5">
                    <a href="{{ url('/') }}" class="resort-menu-link {{ request()->is('/') ? 'is-active' : '' }}">Home</a>
                    <a href="{{ route('tourist.accommodations') }}" class="resort-menu-link {{ request()->routeIs('tourist.accommodations*') ? 'is-active' : '' }}">Rooms</a>
                    <a href="{{ route('tourist.bookings') }}" class="resort-menu-link {{ request()->routeIs('tourist.bookings') ? 'is-active' : '' }}">Booking</a>
                    <a href="{{ route('tour.viewer') }}" target="_blank" class="resort-menu-link">360Â° Tour</a>
                    <a href="{{ route('tourist.reviews') }}" class="resort-menu-link {{ request()->routeIs('tourist.reviews') ? 'is-active' : '' }}">Reviews</a>
                    <a href="{{ route('tourist.accommodations') }}" class="resort-book">Book Now</a>
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
                        <button type="button" @click="notifOpen = !notifOpen" :aria-expanded="notifOpen.toString()" aria-controls="touristNotifPanel" aria-label="Notifications" class="resort-icon relative p-2 rounded-full transition">
                            <i class="bi bi-bell-fill text-base" aria-hidden="true"></i>
                            <span x-show="unread > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-extrabold rounded-full w-5 h-5 flex items-center justify-center shadow" x-text="unread"></span>
                        </button>

                        <div id="touristNotifPanel" x-show="notifOpen" @click.outside="notifOpen = false" @keydown.escape.window="notifOpen = false" x-transition class="absolute right-0 mt-2 w-[calc(100vw-4rem)] sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden text-slate-800" x-cloak>
                            <div class="p-3.5 bg-ocean-900 text-white flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i class="bi bi-bell-fill text-sky-400" aria-hidden="true"></i>
                                    <span class="font-extrabold text-xs tracking-wider uppercase">Notifications</span>
                                    <span x-show="unread > 0" class="bg-red-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full" x-text="unread + ' new'"></span>
                                </div>
                                <button type="button" @click="markAllRead()" class="text-[11px] text-sky-300 hover:text-white font-semibold transition">
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
                                    <i class="bi bi-bell-slash text-3xl block mb-2 text-slate-300" aria-hidden="true"></i>
                                    <p class="text-xs font-semibold mb-1">No notifications yet</p>
                                    <p class="text-[11px] text-slate-400 mb-0">Booking updates and payment confirmations will appear here.</p>
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- Profile / Logout Dropdown --}}
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-controls="touristUserMenu" aria-label="Account menu" class="resort-icon flex items-center gap-2 p-1.5 pr-3 rounded-full transition border">
                            <div class="w-8 h-8 rounded-full bg-sky-500 text-white font-bold text-xs flex items-center justify-center border border-white/30">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <span class="text-xs font-semibold text-white hidden sm:inline">{{ auth()->user()->name }}</span>
                            <i class="bi bi-chevron-down text-xs text-sky-200" aria-hidden="true"></i>
                        </button>

                        <div id="touristUserMenu" x-show="open" @click.outside="open = false" @keydown.escape.window="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-slate-100 py-1 text-slate-700 z-50" x-cloak>
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
                    <button type="button" @click="mobileMenuOpen = !mobileMenuOpen" :aria-expanded="mobileMenuOpen.toString()" aria-controls="touristMobileMenu" aria-label="Toggle navigation menu" class="resort-icon md:hidden p-2 rounded-lg text-lg">
                        <i class="bi" :class="mobileMenuOpen ? 'bi-x-lg' : 'bi-list'" aria-hidden="true"></i>
                    </button>
                </div>

            </div>
        </div>

        {{-- Mobile Dropdown Menu --}}
        <div id="touristMobileMenu" x-show="mobileMenuOpen" x-transition x-cloak class="md:hidden bg-white border-t border-slate-200 px-4 pt-3 pb-4 space-y-1">
            <a href="{{ url('/') }}" class="block px-3 py-2.5 text-sm text-slate-800 no-underline">Home</a>
            <a href="{{ route('tourist.accommodations') }}" class="block px-3 py-2.5 text-sm text-slate-800 no-underline">Rooms</a>
            <a href="{{ route('tourist.bookings') }}" class="block px-3 py-2.5 text-sm text-slate-800 no-underline">Booking</a>
            <a href="{{ route('tour.viewer') }}" target="_blank" class="block px-3 py-2.5 text-sm text-slate-800 no-underline">360Â° Tour</a>
            <a href="{{ route('tourist.reviews') }}" class="block px-3 py-2.5 text-sm text-slate-800 no-underline">Reviews</a>
        </div>
    </nav>

    {{-- Flash Notifications --}}
    @if(session('success'))
    <div class="max-w-7xl mx-auto px-4 mt-4 w-full" id="touristFlashSuccess" role="status">
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <i class="bi bi-check-circle-fill text-emerald-600 text-lg" aria-hidden="true"></i>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.closest('#touristFlashSuccess').remove()" class="text-emerald-500 hover:text-emerald-800" aria-label="Dismiss message"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
    </div>
    <script>
        setTimeout(function () {
            var el = document.getElementById('touristFlashSuccess');
            if (el) el.remove();
        }, 6000);
    </script>
    @endif

    @if(session('error'))
    <div class="max-w-7xl mx-auto px-4 mt-4 w-full" id="touristFlashError" role="alert">
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-rose-600 text-lg" aria-hidden="true"></i>
                <span class="text-sm font-semibold">{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.closest('#touristFlashError').remove()" class="text-rose-500 hover:text-rose-800" aria-label="Dismiss message"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="max-w-7xl mx-auto px-4 mt-4 w-full" id="touristFlashErrors" role="alert">
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl flex items-start justify-between gap-3 shadow-sm">
            <div class="flex items-start gap-2">
                <i class="bi bi-exclamation-circle-fill text-rose-600 text-lg" aria-hidden="true"></i>
                <div>
                    <p class="text-sm font-bold mb-1">Please correct the following:</p>
                    <ul class="mb-0 ps-3 text-xs space-y-0.5">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" onclick="this.closest('#touristFlashErrors').remove()" class="text-rose-500 hover:text-rose-800" aria-label="Dismiss message"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
    </div>
    @endif

    {{-- Main Content --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- â”€â”€ Footer â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
    <footer class="bg-slate-900 text-slate-400 py-8 border-t border-slate-800 mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <div class="flex items-center justify-center gap-2 mb-3">
                <img src="{{ asset('images/logo.png') }}" alt="Talisay Beach Resort logo" class="w-6 h-6 rounded-md">
                <span class="text-sm font-bold text-white">Talisay Beach Resort Smart Tourism</span>
            </div>
            <p class="text-xs text-slate-500 mb-0">&copy; Talisay Beach Resort. {{ setting('resort_address', 'Barangay Maslug, Baybay City, Leyte') }}. All rights reserved.</p>
        </div>
    </footer>

    {{-- â”€â”€ Floating AI Chatbot Widget â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
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
    <div class="fixed bottom-6 right-6 z-50" x-data="touristChatbot()" @keydown.escape.window="open = false">
        {{-- Chat Trigger Floating Button --}}
        <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-controls="touristChatWindow" aria-label="Open Talisay AI Assistant" style="background: linear-gradient(135deg, #0c4a6e 0%, #075985 60%, #0284c7 100%); box-shadow: 0 10px 28px rgba(12, 74, 110, 0.45);" class="w-14 h-14 text-white rounded-full flex items-center justify-center text-2xl transition transform hover:scale-108 border-2 border-white/40">
            <i class="bi" :class="open ? 'bi-x-lg' : 'bi-chat-dots-fill'" aria-hidden="true"></i>
        </button>

        {{-- Chat Box Window --}}
        <div id="touristChatWindow" x-show="open" x-transition role="dialog" aria-labelledby="touristChatTitle" class="absolute bottom-16 right-0 w-[calc(100vw-3rem)] sm:w-96 bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col chat-widget-box" x-cloak>
            {{-- Header --}}
            <div style="background: linear-gradient(135deg, #0c4a6e 0%, #075985 60%, #0284c7 100%);" class="text-white p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-500/30 flex items-center justify-center border border-sky-400">
                    <i class="bi bi-robot text-lg text-sky-200" aria-hidden="true"></i>
                </div>
                <div class="flex-1">
                    <h4 id="touristChatTitle" class="text-sm font-extrabold mb-0">Talisay AI Assistant</h4>
                    <p class="text-xs text-sky-300 mb-0">Online â€¢ Real-time Resort Info</p>
                </div>
                <button type="button" @click="open = false" class="text-white/60 hover:text-white text-lg" aria-label="Close chat">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>

            {{-- Messages Area --}}
            <div class="flex-1 p-4 overflow-y-auto flex flex-col gap-2 bg-slate-50" style="max-height:380px;" x-ref="messagesBox" aria-live="polite">
                <div class="chat-msg-bot">
                    Hello {{ first_name(auth()->user()->name) }}! Welcome back to Talisay Beach Resort.
                    How can I help you today? You can check your bookings, inquire about room rates, payment methods, or explore the 360Â° virtual tour!
                </div>
                <div class="flex flex-wrap gap-1 mt-1">
                    <button type="button" @click="sendChip('My Bookings')" class="chat-chip">My Bookings</button>
                    <button type="button" @click="sendChip('Room Rates')" class="chat-chip">Room Rates</button>
                    <button type="button" @click="sendChip('GCash payment')" class="chat-chip">GCash Guide</button>
                    <button type="button" @click="sendChip('virtual tour')" class="chat-chip">360Â° Tour</button>
                    <button type="button" @click="sendChip('Check Availability')" class="chat-chip">Availability</button>
                </div>

                <template x-for="(msg, idx) in messages" :key="idx">
                    <div>
                        <div :class="msg.sender === 'user' ? 'chat-msg-user' : 'chat-msg-bot'" x-html="formatText(msg.text)"></div>
                        {{-- Quick chips after bot message --}}
                        <template x-if="msg.sender === 'bot' && msg.chips && msg.chips.length">
                            <div class="flex flex-wrap gap-1 mt-1 ml-1">
                                <template x-for="chip in msg.chips" :key="chip">
                                    <button type="button" @click="sendChip(chip)" class="chat-chip" x-text="chip"></button>
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
                <label for="touristChatInput" class="visually-hidden">Type your message</label>
                <input type="text" id="touristChatInput" x-model="inputText"
                    placeholder="Ask about rates, booking, directions..."
                    class="flex-1 text-xs border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-ocean-500">
                <button type="submit" class="bg-ocean-600 text-white px-3.5 py-2 rounded-xl text-xs font-bold hover:bg-ocean-800 transition" aria-label="Send message">
                    <i class="bi bi-send-fill" aria-hidden="true"></i>
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
                    let escaped = String(text)
                        .replace(/&/g, "&amp;")
                        .replace(/</g, "&lt;")
                        .replace(/>/g, "&gt;")
                        .replace(/"/g, "&quot;");

                    escaped = escaped.replace(/^[â€¢\-\*]\s+(.*)$/gm, '<div class="flex items-start gap-1.5 my-0.5"><span class="text-sky-500 font-bold leading-tight">â€¢</span><span>$1</span></div>');

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
                        const reply = data.reply || data.response || 'I am here to help! You can ask about room rates, how to book, directions, payment methods, or the 360Â° virtual tour.';
                        const chips = Array.isArray(data.chips) ? data.chips.map(c => String(c)) : [];
                        this.messages.push({ sender: 'bot', text: reply, chips: chips });
                    } catch (e) {
                        this.typing = false;
                        this.messages.push({
                            sender: 'bot',
                            text: 'Sorry, I had trouble connecting. Please try again, or call us at ' + @json(setting('contact_phone') ?: setting('resort_phone', '+63 (053) 563-7000')) + '.',
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
    <script>
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-loading')) return;
        if (e.defaultPrevented) return;
        var btn = form.querySelector('button[type="submit"]');
        if (!btn || btn.disabled) return;
        btn.disabled = true;
        btn.setAttribute('aria-busy', 'true');
        var spinner = document.createElement('span');
        spinner.className = 'spinner-border spinner-border-sm me-2';
        spinner.setAttribute('role', 'status');
        spinner.setAttribute('aria-hidden', 'true');
        btn.insertBefore(spinner, btn.firstChild);
    });
    </script>
    {{-- Bootstrap JS Bundle --}}
    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    @include('partials.required-stars')
    @stack('scripts')
</body>
</html>

