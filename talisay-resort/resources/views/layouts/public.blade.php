<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta_description', 'Talisay Beach Resort in Baybay City, Leyte. Rooms, cottages, a 360° tour, and online booking by the night or by the hour.')">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $resortInfo['name'])</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/client-resort.css') }}?v=47">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { height: 100%; }
        body { min-height: 100%; font-family: 'Inter', sans-serif; }
    </style>
    @stack('styles')
</head>
<body class="resort-page @yield('body_class')" @if(auth()->check() && method_exists(auth()->user(), 'isTourist') && auth()->user()->isTourist()) data-signed-in="1" @endif>

@include('partials.public-nav')

@yield('content')

@include('partials.public-footer')
@include('partials.public-login-modal')
@include('partials.public-chatbot')

<script>
(function () {
    // Navigation: solid after scrolling, mobile menu.
    const nav = document.getElementById('lpNav');
    const links = document.getElementById('lpLinks');
    const menuBtn = document.getElementById('lpMenuBtn');
    if (!nav) return;
    const solid = nav.classList.contains('lp-nav-solid');
    const onScroll = () => nav.classList.toggle('scrolled', solid || window.scrollY > 40);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    menuBtn.addEventListener('click', () => {
        const open = links.classList.toggle('open');
        nav.classList.toggle('menu-open', open);
        menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    links.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
        links.classList.remove('open');
        nav.classList.remove('menu-open');
        menuBtn.setAttribute('aria-expanded', 'false');
    }));

    // Scroll reveal.
    if ('IntersectionObserver' in window) {
        const reveal = new IntersectionObserver(entries => {
            entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('is-in'); reveal.unobserve(e.target); } });
        }, { threshold: .12 });
        document.querySelectorAll('.lp-reveal').forEach(el => reveal.observe(el));
    } else {
        document.querySelectorAll('.lp-reveal').forEach(el => el.classList.add('is-in'));
    }

    // Video tiles (gallery and unit cards): load and play only while on screen.
    const tiles = document.querySelectorAll('.lp-gallery-item video, .lp-unit-media video');
    if (tiles.length) {
        const startTile = (v) => { if (!v.getAttribute('src') && v.dataset.src) v.setAttribute('src', v.dataset.src); v.play().catch(() => {}); };
        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver(entries => entries.forEach(e => {
                if (e.isIntersecting) startTile(e.target); else if (!e.target.paused) e.target.pause();
            }), { threshold: .3 });
            tiles.forEach(v => io.observe(v));
        } else {
            tiles.forEach(startTile);
        }
    }

    // Generic slideshows: any element with [data-slider].
    document.querySelectorAll('[data-slider]').forEach(slider => {
        const items = Array.from(slider.querySelectorAll('.lp-slider-item'));
        if (!items.length) return;
        const dotsWrap = slider.querySelector('[data-dots]');
        const interval = Number(slider.dataset.interval) || 5000;
        let index = 0, timer = null;
        const dots = [];
        if (dotsWrap && items.length > 1) {
            items.forEach((_, i) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'lp-dot' + (i === 0 ? ' is-active' : '');
                b.setAttribute('aria-label', 'Slide ' + (i + 1));
                b.addEventListener('click', () => { show(i); restart(); });
                dotsWrap.appendChild(b);
                dots.push(b);
            });
        }
        const playVideo = (item) => {
            const v = item.querySelector('video');
            if (!v) return;
            if (!v.getAttribute('src') && v.dataset.src) v.setAttribute('src', v.dataset.src);
            v.play().catch(() => {});
        };
        const pauseVideo = (item) => { const v = item.querySelector('video'); if (v && !v.paused) v.pause(); };
        const show = (n) => {
            index = (n + items.length) % items.length;
            items.forEach((it, i) => {
                const active = i === index;
                it.classList.toggle('is-active', active);
                active ? playVideo(it) : pauseVideo(it);
            });
            dots.forEach((d, i) => d.classList.toggle('is-active', i === index));
        };
        const restart = () => {
            clearInterval(timer);
            if (items.length > 1) timer = setInterval(() => show(index + 1), interval);
        };
        slider.querySelector('[data-prev]')?.addEventListener('click', () => { show(index - 1); restart(); });
        slider.querySelector('[data-next]')?.addEventListener('click', () => { show(index + 1); restart(); });
        slider.addEventListener('mouseenter', () => clearInterval(timer));
        slider.addEventListener('mouseleave', restart);
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(entries => {
                entries.forEach(e => { if (e.isIntersecting) { show(index); restart(); } else { clearInterval(timer); pauseVideo(items[index]); } });
            }, { threshold: .25 }).observe(slider);
        } else {
            show(0); restart();
        }
    });
})();

</script>
@include('partials.required-stars')
@stack('scripts')

</body>
</html>
