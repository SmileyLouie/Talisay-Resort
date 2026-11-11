@extends('layouts.public')

@section('title', $unit->unit_number.' – '.$unit->type_label.' – '.$resortInfo['name'])
@section('meta_description', Str::limit($unit->description ?: $unit->unit_number.' at '.$resortInfo['name'].': rates, occupancy, amenities, and a 360° look inside.', 160))

@section('content')
@php
    $isRoom = $unit->unit_type === 'room';
    $photos = collect($unit->images ?? [])->map(fn ($p) => '/storage/'.ltrim($p, '/'))->values();
    $video = $unit->tour_video_path ? '/storage/'.ltrim($unit->tour_video_path, '/') : null;
    $amenities = collect($unit->amenities ?? [])->filter()->values();
    $hourly = $unit->hourlyRate();
    $banner = '/images/hero-landing.jpg';
    $backRoute = $isRoom ? route('public.rooms') : route('public.cottages');
@endphp

<section class="lp-banner lp-banner-short" style="background-image:url('{{ $photos->first() ?? $banner }}')">
    <div class="lp-banner-box">
        <div class="lp-eyebrow"><a href="{{ $backRoute }}" style="color:inherit;text-decoration:none;"><i class="bi bi-arrow-left"></i> {{ $isRoom ? 'All rooms' : 'All cottages' }}</a></div>
        <h1>{{ $unit->unit_number }}</h1>
        <p>{{ $unit->type_label }} · {{ $unit->variant_label }} · up to {{ $unit->max_occupancy }} {{ $unit->max_occupancy == 1 ? 'guest' : 'guests' }}</p>
    </div>
</section>

<section class="lp-section lp-reveal is-in">
    <div class="lp-detail">
        <div class="lp-detail-main">
            {{-- Media --}}
            <div class="lp-detail-media">
                @if($video)
                    <video id="lpUnitVideo" src="{{ $video }}" autoplay muted loop playsinline controls preload="metadata" poster="{{ $photos->first() ?? $banner }}"></video>
                @elseif($photos->isNotEmpty())
                    <img src="{{ $photos->first() }}" alt="{{ $unit->unit_number }}">
                @else
                    <div class="lp-ph"><i class="bi {{ $isRoom ? 'bi-door-open' : 'bi-house-heart' }}"></i></div>
                @endif
            </div>
            @if($photos->count() > ($video ? 0 : 1))
                <div class="lp-thumbs">
                    @foreach($photos as $photo)
                        <a href="{{ $photo }}" target="_blank" rel="noopener"><img src="{{ $photo }}" alt="{{ $unit->unit_number }} photo {{ $loop->iteration }}" loading="lazy"></a>
                    @endforeach
                </div>
            @endif

            {{-- Description --}}
            <div class="lp-label" style="margin-top:28px;">About this {{ $isRoom ? 'room' : 'cottage' }}</div>
            <h2 class="lp-title">{{ $unit->unit_number }}</h2>
            <p class="lp-copy">{{ $unit->description ?: ($isRoom
                ? 'A clean, quiet room a short walk from the sand at '.$resortInfo['name'].'. Book it for a night or for a stay of 1 to 12 hours.'
                : 'An open-air cottage on the beachfront at '.$resortInfo['name'].'. Book it for a night or for a few hours of shade and sea.') }}</p>

            {{-- Facts --}}
            <div class="lp-facts">
                <div class="lp-fact"><i class="bi bi-people"></i><div><strong>Up to {{ $unit->max_occupancy }}</strong><span>{{ $unit->max_occupancy == 1 ? 'guest' : 'guests' }}</span></div></div>
                @if($unit->floor_area_sqm)
                    <div class="lp-fact"><i class="bi bi-aspect-ratio"></i><div><strong>{{ rtrim(rtrim(number_format((float) $unit->floor_area_sqm, 1), '0'), '.') }} sqm</strong><span>Floor area</span></div></div>
                @endif
                @if($unit->bed_configuration)
                    <div class="lp-fact"><i class="bi bi-moon"></i><div><strong>{{ $unit->bed_configuration }}</strong><span>Beds</span></div></div>
                @endif
                <div class="lp-fact"><i class="bi bi-stars"></i><div><strong>{{ $unit->variant_label }}</strong><span>{{ $unit->type_label }}</span></div></div>
                <div class="lp-fact"><i class="bi bi-clock-history"></i><div><strong>1 – 12 hours</strong><span>or overnight</span></div></div>
            </div>

            {{-- Amenities --}}
            @if($amenities->isNotEmpty())
                <div class="lp-label" style="margin-top:28px;">Amenities</div>
                <ul class="lp-amenities">
                    @foreach($amenities as $amenity)
                        <li><i class="bi bi-check2-circle"></i> {{ $amenity }}</li>
                    @endforeach
                </ul>
            @endif

            {{-- Good to know --}}
            <div class="lp-label" style="margin-top:28px;">Good to know</div>
            <ul class="lp-amenities lp-amenities-plain">
                <li><i class="bi bi-door-open"></i> Open daily {{ $resortInfo['opens'] }} – {{ $resortInfo['closes'] }}. Hourly stays start between 8:00 AM and 8:00 PM.</li>
                <li><i class="bi bi-wallet2"></i> Pay with GCash or card in full{{ \App\Support\BookingRules::downPaymentEnabled() ? ' or with a ' . \App\Support\BookingRules::downPaymentPercent() . '% down payment' : '' }}, or pay cash in full on arrival.</li>
                <li><i class="bi bi-arrow-counterclockwise"></i> Full refund 48 hours before the stay, {{ $resortInfo['late_refund'] }}% between 24 and 48 hours, {{ $resortInfo['same_day'] > 0 ? $resortInfo['same_day'].'% within 24 hours' : 'no refund within 24 hours' }}.</li>
                <li><i class="bi bi-receipt"></i> Show your booking reference and payment confirmation at reception when you arrive.</li>
            </ul>

            {{-- Reviews for this unit --}}
            @if($reviews->isNotEmpty())
                <div class="lp-label" style="margin-top:28px;">What guests said</div>
                <div class="lp-review-list">
                    @foreach($reviews as $review)
                        @php
                            $author = trim((string) ($review->user?->name ?? ''));
                            $parts = $author !== '' ? preg_split('/\s+/', $author) : [];
                            $shown = $author === '' ? 'Resort guest' : $parts[0].(count($parts) > 1 ? ' '.mb_substr(end($parts), 0, 1).'.' : '');
                        @endphp
                        <article class="lp-review is-active">
                            <div class="lp-review-head">
                                <div class="lp-avatar">{{ mb_strtoupper(mb_substr($shown, 0, 1)) }}</div>
                                <div><strong>{{ $shown }}</strong><small>{{ $review->created_at?->format('F Y') }}</small></div>
                                <span class="lp-stars">{!! str_repeat('<i class="bi bi-star-fill"></i>', (int) $review->rating) !!}</span>
                            </div>
                            <p>{{ $review->comment }}</p>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Booking card --}}
        <aside class="lp-detail-side">
            <div class="lp-book-card">
                <div class="lp-book-price">
                    <strong>₱{{ number_format($unit->price_per_night, 0) }}</strong><span>per night</span>
                </div>
                <div class="lp-book-hour"><i class="bi bi-clock"></i> ₱{{ number_format($hourly, 0) }} per hour · 1 to 12 hours</div>
                <ul class="lp-book-list">
                    <li><i class="bi bi-people"></i> Up to {{ $unit->max_occupancy }} {{ $unit->max_occupancy == 1 ? 'guest' : 'guests' }}</li>
                    <li><i class="bi bi-phone"></i> GCash, card, or cash on arrival</li>
                    <li><i class="bi bi-shield-check"></i> Instant booking reference</li>
                </ul>
                <a class="lp-btn lp-btn-sand full" href="{{ $bookUrl }}" data-book><i class="bi bi-calendar-check"></i> Book or Reserve</a>
                <a class="lp-btn lp-btn-outline full" href="{{ route('tour.viewer') }}" target="_blank" rel="noopener"><i class="bi bi-badge-vr"></i> 360° tour of the resort</a>
                <p class="lp-form-note">This keeps {{ $unit->unit_number }} selected. Choose your dates, then payment.</p>
            </div>
            <div class="lp-contact-card">
                <i class="bi bi-telephone-fill"></i>
                <div><strong>Questions?</strong>
                    @if($resortInfo['phone'])<a href="tel:{{ preg_replace('/[^0-9+]/', '', $resortInfo['phone']) }}">{{ $resortInfo['phone'] }}</a>@endif
                    <a href="mailto:{{ $resortInfo['email'] }}">{{ $resortInfo['email'] }}</a>
                </div>
            </div>
        </aside>
    </div>
</section>

@if($similar->isNotEmpty())
    <section class="lp-section lp-reveal" style="padding-top:0;">
        <div class="lp-label">More {{ $isRoom ? 'rooms' : 'cottages' }}</div>
        <h2 class="lp-title">You may also like</h2>
        <div class="lp-unit-grid lp-unit-grid-3">
            @foreach($similar as $other)
                @php
                    $oPhoto = $other->first_image ? '/storage/'.ltrim($other->first_image, '/') : null;
                    $oVideo = $other->tour_video_path ? '/storage/'.ltrim($other->tour_video_path, '/') : null;
                @endphp
                <article class="lp-unit-card">
                    <a class="lp-unit-media" href="{{ route('public.unit', $other) }}">
                        @if($oPhoto)
                            <img src="{{ $oPhoto }}" alt="{{ $other->unit_number }}" loading="lazy">
                        @elseif($oVideo)
                            <video data-src="{{ $oVideo }}" muted loop playsinline preload="none" poster="{{ $banner }}"></video>
                        @else
                            <div class="lp-ph"><i class="bi {{ $isRoom ? 'bi-door-open' : 'bi-house-heart' }}"></i></div>
                        @endif
                        <span class="lp-unit-tag {{ $other->variant === 'premium' ? 'is-premium' : '' }}">{{ $other->variant_label }}</span>
                    </a>
                    <div class="lp-unit-body">
                        <div class="lp-unit-top">
                            <div><small>{{ $other->type_label }}</small><h3><a href="{{ route('public.unit', $other) }}">{{ $other->unit_number }}</a></h3></div>
                            <div class="lp-unit-price"><strong>₱{{ number_format($other->price_per_night, 0) }}</strong><span>per night</span></div>
                        </div>
                        <div class="lp-unit-actions">
                            <a class="lp-btn lp-btn-outline" href="{{ route('public.unit', $other) }}">View details</a>
                            <a class="lp-btn lp-btn-navy" href="{{ route('public.booking', ['unit' => $other->id]) }}" data-book>Book</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif

@endsection

@push('scripts')
<script>
(function () {
    const vids = document.querySelectorAll('.lp-unit-media video');
    const start = (v) => { if (!v.getAttribute('src') && v.dataset.src) v.setAttribute('src', v.dataset.src); v.play().catch(() => {}); };
    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver(entries => entries.forEach(e => {
            if (e.isIntersecting) start(e.target); else if (!e.target.paused) e.target.pause();
        }), { threshold: .3 });
        vids.forEach(v => io.observe(v));
    } else {
        vids.forEach(start);
    }
})();
</script>
@endpush
