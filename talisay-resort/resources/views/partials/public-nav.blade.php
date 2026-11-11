@php
    $navActive = $navActive ?? '';
    $navGuestIsTourist = auth()->check() && method_exists(auth()->user(), 'isTourist') && auth()->user()->isTourist();
    $navItems = [
        'home'     => ['label' => 'Home',     'url' => url('/')],
        'about'    => ['label' => 'About',    'url' => route('public.about')],
        'tour'     => ['label' => 'Tour',     'url' => route('tour.viewer')],
        'rooms'    => ['label' => 'Rooms',    'url' => route('public.rooms')],
        'cottages' => ['label' => 'Cottages', 'url' => route('public.cottages')],
        'activities' => ['label' => 'Activities', 'url' => route('public.activities')],
        'booking'  => ['label' => 'Booking',  'url' => route('public.booking')],
        'reviews'  => ['label' => 'Reviews',  'url' => route('public.reviews')],
        'contact'  => ['label' => 'Contact',  'url' => route('public.contact')],
    ];
@endphp
{{-- Navigation: transparent over a hero, white after scrolling (or always white with .lp-nav-solid). --}}
<header class="lp-nav {{ ($navSolid ?? false) ? 'lp-nav-solid' : '' }}" id="lpNav">
    <div class="lp-nav-inner">
        <a href="{{ url('/') }}" class="lp-brand">
            <img src="{{ asset('images/logo.png') }}" alt="{{ $resortInfo['name'] }} logo">
            <div>
                <strong>{{ $resortInfo['name'] }}</strong>
                <span>{{ __('Smart Tourism System') }}</span>
            </div>
        </a>
        <button type="button" class="lp-menu-btn" id="lpMenuBtn" aria-label="Open menu" aria-expanded="false" aria-controls="lpLinks">
            <i class="bi bi-list"></i>
        </button>
        <nav class="lp-links" id="lpLinks">
            @foreach($navItems as $key => $item)
                <a href="{{ $item['url'] }}" class="{{ $navActive === $key ? 'is-active' : '' }}">{{ __($item['label']) }}</a>
            @endforeach
        </nav>
        @if($navGuestIsTourist)
            @php
                $navUser = auth()->user();
                $navName = $navUser->name;
                $navInitial = mb_strtoupper(mb_substr($navName, 0, 1));
                $navAvatar = $navUser->avatar ? '/storage/'.ltrim($navUser->avatar, '/') : null;
                $navUnread = $navUser->unreadNotificationCount();
                $navNotifs = $navUser->customNotifications()->latest()->take(8)->get();
            @endphp
            <div class="lp-account">
                <div class="lp-pop" id="lpNotif">
                    <button type="button" class="lp-bell" aria-label="Notifications" aria-expanded="false">
                        <i class="bi bi-bell-fill"></i>
                        @if($navUnread > 0)<span class="lp-badge">{{ $navUnread }}</span>@endif
                    </button>
                    <div class="lp-pop-panel" hidden>
                        <div class="lp-pop-head">
                            <strong>Notifications</strong>
                            @if($navUnread > 0)
                                <button type="button" data-read-all="{{ route('notifications.read-all') }}">Mark all read</button>
                            @endif
                        </div>
                        <div class="lp-notif-scroll">
                            @forelse($navNotifs as $notif)
                                <div class="lp-notif {{ $notif->read_at ? '' : 'is-new' }}">
                                    <button type="button" data-read="{{ route('notifications.read', $notif->id) }}" data-href="{{ route('tourist.bookings') }}">
                                        <strong>{{ $notif->title }}</strong>
                                        <span>{{ $notif->message }}</span>
                                        <small>{{ $notif->created_at?->diffForHumans() }}</small>
                                    </button>
                                </div>
                            @empty
                                <p class="lp-pop-empty">Booking and payment updates from the resort appear here.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="lp-pop" id="lpProfile">
                    <button type="button" class="lp-profile" aria-label="Account menu" aria-expanded="false">
                        @if($navAvatar)
                            <img src="{{ $navAvatar }}" alt="">
                        @else
                            <span>{{ $navInitial }}</span>
                        @endif
                        <em>{{ $navName }}</em>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="lp-pop-panel lp-profile-panel" hidden>
                        <div class="lp-pop-who">
                            <strong>{{ $navName }}</strong>
                            <span>{{ $navUser->email }}</span>
                        </div>
                        <a href="{{ route('tourist.bookings') }}"><i class="bi bi-calendar-check"></i> My bookings</a>
                        <a href="{{ route('profile.show') }}"><i class="bi bi-person"></i> My profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"><i class="bi bi-box-arrow-right"></i> Log out</button>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <button type="button" class="lp-nav-login" onclick="openLoginModal(event)">
                <i class="bi bi-box-arrow-in-right"></i> {{ __('Log in') }}
            </button>
        @endif
    </div>
</header>
@if($navGuestIsTourist)
<script>
(function () {
    document.querySelectorAll('.lp-pop').forEach(function (pop) {
        const btn = pop.querySelector(':scope > button');
        const panel = pop.querySelector('.lp-pop-panel');
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const open = panel.hidden;
            document.querySelectorAll('.lp-pop-panel').forEach(function (p) { p.hidden = true; });
            document.querySelectorAll('.lp-pop > button').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
            panel.hidden = !open;
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });
    document.addEventListener('click', function () {
        document.querySelectorAll('.lp-pop-panel').forEach(function (p) { p.hidden = true; });
        document.querySelectorAll('.lp-pop > button').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
    });
    const token = document.querySelector('meta[name="csrf-token"]');
    const mark = function (url) {
        if (!url || !token) return;
        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': token.content, 'Accept': 'application/json' }, keepalive: true }).catch(function () {});
    };
    document.querySelectorAll('[data-read]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            mark(btn.dataset.read);
            btn.closest('.lp-notif').classList.remove('is-new');
            const badge = document.querySelector('.lp-badge');
            if (badge && !document.querySelector('.lp-notif.is-new')) badge.remove();
            if (btn.dataset.href) window.location = btn.dataset.href;
        });
    });
    const all = document.querySelector('[data-read-all]');
    if (all) {
        all.addEventListener('click', function (e) {
            e.stopPropagation();
            mark(all.dataset.readAll);
            document.querySelectorAll('.lp-notif').forEach(function (n) { n.classList.remove('is-new'); });
            const badge = document.querySelector('.lp-badge');
            if (badge) badge.remove();
            all.remove();
        });
    }
})();
</script>
@endif
