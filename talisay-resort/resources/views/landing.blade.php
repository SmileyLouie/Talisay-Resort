@extends('layouts.public')

@section('title', $resortInfo['name'].' – Baybay City, Leyte')
@section('meta_description', 'Book rooms and cottages at Talisay Beach Resort in Baybay City, Leyte. Night or hourly stays, GCash payment, and a 360° virtual tour before you arrive.')

@section('content')
@php
    $roomUnits = $roomUnits ?? collect();
    $cottageUnits = $cottageUnits ?? collect();
    $heroSlides = ($heroSlides ?? collect())->values();
    $landingReviews = $landingReviews ?? collect();
    $allUnits = $roomUnits->concat($cottageUnits);
    $lowestNight = $allUnits->map(fn ($u) => (float) $u->price_per_night)->filter(fn ($p) => $p > 0)->min();
    $lowestHour = $allUnits->map(fn ($u) => $u->hourlyRate())->filter(fn ($p) => $p > 0)->min();
    $unitMedia = function ($unit) {
        if ($unit->first_image) {
            return ['type' => 'image', 'src' => '/storage/'.ltrim($unit->first_image, '/')];
        }
        if ($unit->tour_video_path) {
            return ['type' => 'video', 'src' => '/storage/'.ltrim($unit->tour_video_path, '/')];
        }
        return ['type' => 'none', 'src' => null];
    };
    // Slides with a photo or video come first so a slideshow never opens on a placeholder.
    $slideOrder = fn ($units) => $units->sortBy(fn ($u) => $unitMedia($u)['type'] === 'none' ? 1 : 0)->values();
    $roomSlides = $slideOrder($roomUnits);
    $cottageSlides = $slideOrder($cottageUnits);
@endphp
<section class="lp-hero" id="top">
    <div class="lp-slides" id="lpSlides">
        @forelse($heroSlides as $i => $slide)
            @if($slide['type'] === 'video')
                <div class="lp-slide lp-slide-video {{ $i === 0 ? 'is-active' : '' }}">
                    <video data-src="{{ $slide['src'] }}" muted loop playsinline preload="none"></video>
                </div>
            @else
                <div class="lp-slide {{ $i === 0 ? 'is-active' : '' }}"
                     @if($i === 0) style="background-image:url('{{ $slide['src'] }}')" @else data-bg="{{ $slide['src'] }}" @endif></div>
            @endif
        @empty
            <div class="lp-slide is-active" style="background-image:url('/images/hero-landing.jpg')"></div>
        @endforelse
    </div>
    <div class="lp-hero-content">
        <div class="lp-eyebrow">Baybay City, Leyte</div>
        <h1>{{ $resortInfo['name'] }}<br><em>Your shore, your stay.</em></h1>
        <p>Rooms and cottages by the beach, a 360° look at the grounds before you arrive, and booking by the night or by the hour with GCash, card, or cash on arrival.</p>
        <div class="lp-hero-actions">
            <a class="lp-btn lp-btn-sand" href="{{ route('public.booking') }}" data-book><i class="bi bi-calendar-check"></i> Book a stay</a>
            <a class="lp-btn lp-btn-ghost" href="{{ route('tour.viewer') }}"><i class="bi bi-badge-vr"></i> Open 360° tour</a>
        </div>
        @if($heroSlides->count() > 1)
            <div class="lp-dots" id="lpHeroDots" aria-label="Slideshow">
                @foreach($heroSlides as $i => $slide)
                    <button type="button" class="lp-dot {{ $i === 0 ? 'is-active' : '' }}" data-go="{{ $i }}" aria-label="Photo {{ $i + 1 }}"></button>
                @endforeach
            </div>
        @endif
        </div>
</section>

@include('partials.public-smart-search')

{{-- ── Resort information ── --}}
<section class="lp-section lp-about lp-center lp-reveal" id="about">
    <div class="lp-label">About the resort</div>
    <p class="lp-lead">{{ $resortInfo['name'] }} sits on the shore of {{ $resortInfo['address'] }}. Guests can stay overnight in a room or cottage, spend a few hours by the water, and take a 360° tour of the grounds before they ever arrive.</p>
    <a class="lp-btn lp-btn-navy" href="{{ route('public.about') }}">More about the resort <i class="bi bi-arrow-right"></i></a>
</section>

{{-- ── Full-width tour video ── --}}
<section class="lp-video" id="tour">
    @if($tourVideoUrl)
        <video id="lpTourVideo" src="{{ $tourVideoUrl }}" autoplay muted loop playsinline preload="metadata" poster="/images/hero-landing.jpg"></video>
        <button type="button" class="lp-video-sound" id="lpSoundBtn" aria-label="Turn sound on" title="Sound"><i class="bi bi-volume-mute-fill"></i></button>
    @else
        <div class="lp-slide is-active" style="position:absolute;inset:0;background-image:url('/images/hero-landing.jpg');opacity:1;transform:none;"></div>
    @endif
    <div class="lp-video-overlay">
        <div class="lp-label">Tour the resort</div>
        <h2>{{ $tourVideoTitle }}</h2>
        <p>Walk the beach, the cottages, and the rooms from your screen. The full 360° tour lets you look around every scene before you reserve.</p>
        <div class="lp-video-actions">
            <a class="lp-btn lp-btn-sand" href="{{ route('public.booking') }}" data-book><i class="bi bi-calendar-check"></i> Book a stay</a>
            <a class="lp-btn lp-btn-ghost" href="{{ route('tour.viewer') }}"><i class="bi bi-badge-vr"></i> Full 360° tour</a>
        </div>
    </div>
</section>

{{-- ── Rooms slideshow ── --}}
<section class="lp-section lp-reveal" id="rooms">
    <div class="lp-split">
        <div class="lp-split-text">
            <div class="lp-label">Time to reconnect</div>
            <h2 class="lp-title">Our Rooms</h2>
            <p class="lp-copy">Clean, quiet rooms a short walk from the sand. Every room shows its rate, occupancy, and a 360° look inside, and can be booked for a night or for a stay of 1 to 12 hours.</p>
            @if($roomUnits->isNotEmpty())
                <ul class="lp-unit-list">
                    @foreach($roomUnits as $unit)
                        <li>{{ $unit->unit_number }} · {{ $unit->variant_label }}</li>
                    @endforeach
                </ul>
            @endif
            <a class="lp-btn lp-btn-navy" href="{{ route('public.rooms') }}">See all rooms <i class="bi bi-arrow-right"></i></a>
        </div>
        @if($roomUnits->isNotEmpty())
            <div class="lp-slider" data-slider data-interval="5000" aria-label="Room photos">
                @foreach($roomSlides as $i => $unit)
                    @php $media = $unitMedia($unit); @endphp
                    <div class="lp-slider-item {{ $i === 0 ? 'is-active' : '' }}">
                        @if($media['type'] === 'image')
                            <img src="{{ $media['src'] }}" alt="{{ $unit->unit_number }}" loading="lazy">
                        @elseif($media['type'] === 'video')
                            <video data-src="{{ $media['src'] }}" muted loop playsinline preload="none"></video>
                        @else
                            <div class="lp-ph"><i class="bi bi-door-open"></i></div>
                        @endif
                        <div class="lp-slider-cap">
                            <div>
                                <small>{{ $unit->type_label }} · {{ $unit->variant_label }} · up to {{ $unit->max_occupancy }} {{ $unit->max_occupancy == 1 ? 'guest' : 'guests' }}</small>
                                <h3><a href="{{ route('public.unit', $unit) }}">{{ $unit->unit_number }} <i class="bi bi-arrow-up-right"></i></a></h3>
                            </div>
                            <div class="lp-rate">
                                <strong>₱{{ number_format($unit->price_per_night, 0) }}</strong>
                                <span>per night · ₱{{ number_format($unit->hourlyRate(), 0) }} per hour</span>
                            </div>
                        </div>
                    </div>
                @endforeach
                @if($roomUnits->count() > 1)
                    <button type="button" class="lp-arrow prev" data-prev aria-label="Previous room"><i class="bi bi-chevron-left"></i></button>
                    <button type="button" class="lp-arrow next" data-next aria-label="Next room"><i class="bi bi-chevron-right"></i></button>
                    <div class="lp-slider-dots" data-dots></div>
                @endif
            </div>
        @else
            <div class="lp-slider-empty">Rooms will appear here as soon as they are added by the resort.</div>
        @endif
    </div>
</section>

{{-- ── Cottages slideshow ── --}}
<section class="lp-section lp-reveal" id="cottages" style="padding-top:0;">
    <div class="lp-split reverse">
        <div class="lp-split-text">
            <div class="lp-label">By the water</div>
            <h2 class="lp-title">Our Cottages</h2>
            <p class="lp-copy">Open-air cottages for families and groups, right on the beachfront. Choose a nightly stay or a few hours of shade and sea, and pay online with GCash, by card, or in cash when you arrive.</p>
            @if($cottageUnits->isNotEmpty())
                <ul class="lp-unit-list">
                    @foreach($cottageUnits as $unit)
                        <li>{{ $unit->unit_number }} · {{ $unit->variant_label }}</li>
                    @endforeach
                </ul>
            @endif
            <a class="lp-btn lp-btn-navy" href="{{ route('public.cottages') }}">See all cottages <i class="bi bi-arrow-right"></i></a>
        </div>
        @if($cottageUnits->isNotEmpty())
            <div class="lp-slider" data-slider data-interval="5600" aria-label="Cottage photos">
                @foreach($cottageSlides as $i => $unit)
                    @php $media = $unitMedia($unit); @endphp
                    <div class="lp-slider-item {{ $i === 0 ? 'is-active' : '' }}">
                        @if($media['type'] === 'image')
                            <img src="{{ $media['src'] }}" alt="{{ $unit->unit_number }}" loading="lazy">
                        @elseif($media['type'] === 'video')
                            <video data-src="{{ $media['src'] }}" muted loop playsinline preload="none"></video>
                        @else
                            <div class="lp-ph"><i class="bi bi-house-heart"></i></div>
                        @endif
                        <div class="lp-slider-cap">
                            <div>
                                <small>{{ $unit->type_label }} · {{ $unit->variant_label }} · up to {{ $unit->max_occupancy }} {{ $unit->max_occupancy == 1 ? 'guest' : 'guests' }}</small>
                                <h3><a href="{{ route('public.unit', $unit) }}">{{ $unit->unit_number }} <i class="bi bi-arrow-up-right"></i></a></h3>
                            </div>
                            <div class="lp-rate">
                                <strong>₱{{ number_format($unit->price_per_night, 0) }}</strong>
                                <span>per night · ₱{{ number_format($unit->hourlyRate(), 0) }} per hour</span>
                            </div>
                        </div>
                    </div>
                @endforeach
                @if($cottageUnits->count() > 1)
                    <button type="button" class="lp-arrow prev" data-prev aria-label="Previous cottage"><i class="bi bi-chevron-left"></i></button>
                    <button type="button" class="lp-arrow next" data-next aria-label="Next cottage"><i class="bi bi-chevron-right"></i></button>
                    <div class="lp-slider-dots" data-dots></div>
                @endif
            </div>
        @else
            <div class="lp-slider-empty">Cottages will appear here as soon as they are added by the resort.</div>
        @endif
        </div>
</section>

@if(($activities ?? collect())->isNotEmpty())
<section class="lp-section lp-reveal" id="activities" style="padding-top:0;">
    <div class="lp-split">
        <div class="lp-split-text">
            <div class="lp-label">Things to do</div>
            <h2 class="lp-title">Resort activities</h2>
            <p class="lp-copy">Snorkeling, island hopping, and other activities arranged at the resort. Add one when you book a room or cottage.</p>
            <ul class="lp-unit-list">
                @foreach($activities as $activity)
                    <li>{{ $activity->title }}@if($activity->price !== null) · ₱{{ number_format($activity->price, 0) }}@endif</li>
                @endforeach
            </ul>
            <a class="lp-btn lp-btn-navy" href="{{ route('public.activities') }}">See all activities <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="lp-slider" data-slider data-interval="5200" aria-label="Activity photos">
            @foreach($activities as $i => $activity)
                <div class="lp-slider-item {{ $i === 0 ? 'is-active' : '' }}">
                    @if($activity->imageUrl())
                        <img src="{{ $activity->imageUrl() }}" alt="{{ $activity->title }}" loading="lazy">
                    @else
                        <div class="lp-ph"><i class="bi {{ $activity->icon ?: 'bi-water' }}"></i></div>
                    @endif
                    <div class="lp-slider-cap">
                        <div>
                            <small>Activity{{ $activity->is_bookable ? ' · Bookable' : '' }}@if($activity->schedule) · {{ $activity->schedule }}@endif</small>
                            <h3><a href="{{ route('public.activities') }}">{{ $activity->title }} <i class="bi bi-arrow-up-right"></i></a></h3>
                        </div>
                        <div class="lp-rate">
                            @if($activity->price !== null)
                                <strong>₱{{ number_format($activity->price, 0) }}</strong>
                                <span>per guest</span>
                            @else
                                <strong>Included</strong>
                                <span>with stay</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
            @if($activities->count() > 1)
                <button type="button" class="lp-arrow prev" data-prev aria-label="Previous activity"><i class="bi bi-chevron-left"></i></button>
                <button type="button" class="lp-arrow next" data-next aria-label="Next activity"><i class="bi bi-chevron-right"></i></button>
                <div class="lp-slider-dots" data-dots></div>
            @endif
        </div>
    </div>
</section>
@endif

{{-- ── Arrival and registration information ── --}}
<section class="lp-info" id="info">
    <div class="lp-section lp-reveal">
        <div class="lp-label">Convenience</div>
        <h2 class="lp-title">Arrival and Registration at the Resort</h2>
        <div class="lp-info-grid">
            <div class="lp-info-item">
                <div class="lp-info-icon"><i class="bi bi-receipt"></i></div>
                <div>
                    <h4>Bring your booking reference</h4>
                    <p>Show your reservation reference and your GCash receipt or payment confirmation at the reception area when you arrive. Your My Bookings page keeps both ready on your phone.</p>
                </div>
            </div>
            <div class="lp-info-item">
                <div class="lp-info-icon"><i class="bi bi-clock-history"></i></div>
                <div>
                    <h4>Opening hours</h4>
                    <p>The resort welcomes guests from {{ $resortInfo['opens'] }} to {{ $resortInfo['closes'] }} daily. Hourly stays can start between 8:00 AM and 8:00 PM and run from 1 to 12 hours.</p>
                </div>
            </div>
            <div class="lp-info-item">
                <div class="lp-info-icon"><i class="bi bi-wallet2"></i></div>
                <div>
                    <h4>Payment at the resort</h4>
                    <p>Pay online with GCash or a card, {{ \App\Support\BookingRules::downPaymentEnabled() ? 'either in full or with a ' . \App\Support\BookingRules::downPaymentPercent() . '% down payment, and settle' : 'in full, and settle' }} the balance at the desk. Cash on arrival is paid in full when you check in.</p>
                </div>
            </div>
            <div class="lp-info-item">
                <div class="lp-info-icon"><i class="bi bi-arrow-counterclockwise"></i></div>
            <div>
                    <h4>Changes and cancellations</h4>
                    <p>Cancel 48 hours or more before your stay for a full refund of a confirmed payment. Between 24 and 48 hours, {{ $resortInfo['late_refund'] }}% is refunded. Within 24 hours, {{ $resortInfo['same_day'] > 0 ? $resortInfo['same_day'].'% is refunded' : 'the payment is no longer refundable' }}.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── Guest reviews slideshow ── --}}
<section class="lp-reviews" id="reviews">
    <div class="lp-section lp-reveal">
        <div class="lp-label">Testimonials</div>
        <h2 class="lp-title">Guest reviews</h2>
        @if($reviewCount > 0)
            <div class="lp-review-summary">
                <span class="lp-stars">{!! str_repeat('<i class="bi bi-star-fill"></i>', (int) round($averageRating)) !!}</span>
                {{ number_format($averageRating, 1) }} / 5 from {{ $reviewCount }} {{ $reviewCount === 1 ? 'guest' : 'guests' }}
            </div>
        @endif
        <div class="lp-review-track" id="lpReviewTrack" data-per-view="2" data-interval="6000">
            @forelse($landingReviews as $review)
                @php
                    $author = trim((string) ($review->user?->name ?? ''));
                    $parts = $author !== '' ? preg_split('/\s+/', $author) : [];
                    $shown = $author === '' ? 'Resort guest' : $parts[0].(count($parts) > 1 ? ' '.mb_substr(end($parts), 0, 1).'.' : '');
                    $author = $author !== '' ? $author : 'Resort guest';
                    $avatar = $review->user?->avatar;
                @endphp
                <article class="lp-review">
                    <div class="lp-review-head">
                        @if($avatar)
                            <img class="lp-avatar" src="/storage/{{ ltrim($avatar, '/') }}" alt="">
                        @else
                            <div class="lp-avatar">{{ mb_strtoupper(mb_substr($author, 0, 1)) }}</div>
                        @endif
                        <div>
                            <strong>{{ $shown }}</strong>
                            <small>{{ $review->created_at?->format('F Y') }}</small>
                        </div>
                        <span class="lp-stars" aria-label="{{ $review->rating }} out of 5">{!! str_repeat('<i class="bi bi-star-fill"></i>', (int) $review->rating) !!}</span>
                    </div>
                    <p>{{ $review->comment }}</p>
                    @if($review->booking?->accommodationUnit)
                        <div class="lp-review-unit">Stayed in {{ $review->booking->accommodationUnit->unit_number }}</div>
                    @endif
                </article>
            @empty
                <div class="lp-review-empty">
                    Guest reviews appear here automatically after a stay is completed and the guest leaves a comment on My Bookings.
                </div>
            @endforelse
        </div>
        @if($landingReviews->count() > 2)
            <div class="lp-review-dots" id="lpReviewDots"></div>
        @endif
        <div class="lp-center" style="margin-top:28px;">
            <a class="lp-btn lp-btn-ghost" href="{{ route('public.reviews') }}">Read all reviews <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
</section>

{{-- ── Contact ── --}}
<section class="lp-section lp-reveal" id="contact">
    <div class="lp-contact-grid">
        <div>
            <div class="lp-label">Contact us</div>
            <h2 class="lp-title">Reach us</h2>
            <p class="lp-copy">Questions about a stay, a group booking, or the resort itself? Call or email the front desk. To reserve a room or the whole resort, use Booking.</p>
            <div class="lp-contact-card">
                <i class="bi bi-geo-alt-fill"></i>
                <div><strong>Address</strong><span>{{ $resortInfo['address'] }}</span></div>
            </div>
            @if($resortInfo['phone'])
                <div class="lp-contact-card">
                    <i class="bi bi-telephone-fill"></i>
                    <div><strong>Phone</strong><a href="tel:{{ preg_replace('/[^0-9+]/', '', $resortInfo['phone']) }}">{{ $resortInfo['phone'] }}</a></div>
                </div>
            @endif
            <div class="lp-contact-card">
                <i class="bi bi-envelope-fill"></i>
                <div><strong>Email</strong><a href="mailto:{{ $resortInfo['email'] }}">{{ $resortInfo['email'] }}</a></div>
            </div>
            <div class="lp-contact-card">
                <i class="bi bi-clock-fill"></i>
                <div><strong>Open daily</strong><span>{{ $resortInfo['opens'] }} – {{ $resortInfo['closes'] }}</span></div>
            </div>
        </div>
</div>
</section>

@endsection

@push('scripts')
<script>
(function () {
    // Hero slideshow (photos and short video clips of the resort).
    const slides = Array.from(document.querySelectorAll('#lpSlides .lp-slide'));
    const heroDots = Array.from(document.querySelectorAll('#lpHeroDots .lp-dot'));
    let heroIndex = 0, heroTimer = null;
    const loadSlide = (el) => {
        if (!el) return;
        if (el.dataset.bg) { el.style.backgroundImage = "url('" + el.dataset.bg + "')"; delete el.dataset.bg; }
        const v = el.querySelector('video');
        if (v && !v.getAttribute('src') && v.dataset.src) { v.setAttribute('src', v.dataset.src); v.load(); }
    };
    const showHero = (n) => {
        heroIndex = (n + slides.length) % slides.length;
        slides.forEach((s, i) => {
            const active = i === heroIndex;
            s.classList.toggle('is-active', active);
            const v = s.querySelector('video');
            if (!v) return;
            if (active) { loadSlide(s); v.currentTime = 0; v.play().catch(() => {}); }
            else if (!v.paused) { v.pause(); }
        });
        heroDots.forEach((d, i) => d.classList.toggle('is-active', i === heroIndex));
        loadSlide(slides[(heroIndex + 1) % slides.length]);
    };
    const startHero = () => {
        if (slides.length < 2) return;
        clearInterval(heroTimer);
        heroTimer = setInterval(() => showHero(heroIndex + 1), 6000);
    };
    if (slides.length > 1) {
        loadSlide(slides[1]);
        heroDots.forEach(d => d.addEventListener('click', () => { showHero(Number(d.dataset.go)); startHero(); }));
        startHero();
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) { clearInterval(heroTimer); } else { startHero(); }
        });
    } else if (slides.length === 1) {
        showHero(0);
    }

    // Review slideshow: two cards at a time, auto-advancing.
    const track = document.getElementById('lpReviewTrack');
    if (track) {
        const cards = Array.from(track.querySelectorAll('.lp-review'));
        const dotsWrap = document.getElementById('lpReviewDots');
        const perView = () => window.matchMedia('(max-width: 960px)').matches ? 1 : (Number(track.dataset.perView) || 2);
        let page = 0, timer = null;
        const pages = () => Math.max(1, Math.ceil(cards.length / perView()));
        const render = () => {
            const n = perView();
            page = (page + pages()) % pages();
            cards.forEach((c, i) => c.classList.toggle('is-active', i >= page * n && i < page * n + n));
            if (dotsWrap) {
                dotsWrap.innerHTML = '';
                for (let i = 0; i < pages(); i++) {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'lp-dot' + (i === page ? ' is-active' : '');
                    b.setAttribute('aria-label', 'Reviews page ' + (i + 1));
                    b.addEventListener('click', () => { page = i; render(); restart(); });
                    dotsWrap.appendChild(b);
                }
            }
        };
        const restart = () => {
            clearInterval(timer);
            if (pages() > 1) timer = setInterval(() => { page++; render(); }, Number(track.dataset.interval) || 6000);
        };
        if (cards.length) { render(); restart(); window.addEventListener('resize', render); }
    }

    // Tour video sound toggle.
    const video = document.getElementById('lpTourVideo');
    const soundBtn = document.getElementById('lpSoundBtn');
    if (video && soundBtn) {
        soundBtn.addEventListener('click', () => {
            video.muted = !video.muted;
            soundBtn.innerHTML = video.muted ? '<i class="bi bi-volume-mute-fill"></i>' : '<i class="bi bi-volume-up-fill"></i>';
            soundBtn.setAttribute('aria-label', video.muted ? 'Turn sound on' : 'Turn sound off');
            if (video.paused) video.play().catch(() => {});
        });
    }
})();
</script>
@endpush
