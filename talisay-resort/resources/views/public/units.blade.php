@extends('layouts.public')

@section('title', $pageTitle.' – '.$resortInfo['name'])
@section('meta_description', $pageIntro)

@section('content')
@php
    $isRoom = $unitType === 'room';
@endphp

<section class="lp-banner" style="background-image:url('{{ $bannerImage }}')">
    <div class="lp-banner-box">
        <div class="lp-eyebrow">{{ $pageLabel }}</div>
        <h1>{{ $pageTitle }}</h1>
        <p>{{ $pageIntro }}</p>
    </div>
</section>

<section class="lp-section lp-reveal is-in">
    <div class="lp-list-head">
        <div>
            <div class="lp-label" id="unitCount">{{ $units->count() }} {{ Str::plural($isRoom ? 'room' : 'cottage', $units->count()) }} available</div>
            <h2 class="lp-title">Pick your {{ $isRoom ? 'room' : 'cottage' }}</h2>
        </div>
        <div class="lp-list-meta">
            <div class="lp-style-pick" data-kind="{{ $isRoom ? 'room' : 'cottage' }}">
                <button type="button" class="lp-style-trigger" aria-expanded="false" aria-haspopup="listbox">
                    <span class="lp-style-label">All {{ $isRoom ? 'rooms' : 'cottages' }}</span>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </button>
                <div class="lp-style-menu" role="listbox" hidden>
                    <button type="button" role="option" data-style="all" class="is-on">All {{ $isRoom ? 'rooms' : 'cottages' }}</button>
                    <button type="button" role="option" data-style="normal">Normal</button>
                    <button type="button" role="option" data-style="deluxe">Deluxe</button>
                </div>
            </div>
            <a class="lp-btn lp-btn-outline" href="{{ $isRoom ? route('public.cottages') : route('public.rooms') }}">{{ $isRoom ? 'See cottages' : 'See rooms' }} <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>

    @if($units->isEmpty())
        <div class="lp-slider-empty">{{ $isRoom ? 'Rooms' : 'Cottages' }} will appear here as soon as they are added by the resort.</div>
    @else
        <div class="lp-unit-grid">
            @foreach($units as $unit)
                @php
                    $photos = collect($unit->images ?? [])->map(fn ($p) => '/storage/'.ltrim($p, '/'))->values();
                    $video = $unit->tour_video_path ? '/storage/'.ltrim($unit->tour_video_path, '/') : null;
                    $amenities = collect($unit->amenities ?? [])->filter()->values();
                @endphp
                <article class="lp-unit-card" data-style="{{ $unit->isDeluxeVariant() ? 'deluxe' : 'normal' }}">
                    <a class="lp-unit-media" href="{{ route('public.unit', $unit) }}">
                        @if($photos->isNotEmpty())
                            <img src="{{ $photos->first() }}" alt="{{ $unit->unit_number }}" loading="lazy">
                        @elseif($video)
                            <video data-src="{{ $video }}" muted loop playsinline preload="none" poster="{{ $bannerImage }}"></video>
                            <span class="lp-gallery-badge"><i class="bi bi-badge-vr"></i> 360° video</span>
                        @else
                            <div class="lp-ph"><i class="bi {{ $isRoom ? 'bi-door-open' : 'bi-house-heart' }}"></i></div>
                        @endif
                        <span class="lp-unit-tag {{ $unit->variant === 'premium' ? 'is-premium' : '' }}">{{ $unit->variant_label }}</span>
                    </a>
                    <div class="lp-unit-body">
                        <div class="lp-unit-top">
                            <div>
                                <small>{{ $unit->type_label }}</small>
                                <h3><a href="{{ route('public.unit', $unit) }}">{{ $unit->unit_number }}</a></h3>
                            </div>
                            <div class="lp-unit-price">
                                <strong>₱{{ number_format($unit->price_per_night, 0) }}</strong><span>per night</span>
                                <em>₱{{ number_format($unit->hourlyRate(), 0) }} per hour</em>
                            </div>
                        </div>
                        <ul class="lp-unit-facts">
                            <li><i class="bi bi-people"></i> Up to {{ $unit->max_occupancy }} {{ $unit->max_occupancy == 1 ? 'guest' : 'guests' }}</li>
                            @if($unit->floor_area_sqm)<li><i class="bi bi-aspect-ratio"></i> {{ rtrim(rtrim(number_format((float) $unit->floor_area_sqm, 1), '0'), '.') }} sqm</li>@endif
                            @if($unit->bed_configuration)<li><i class="bi bi-moon"></i> {{ $unit->bed_configuration }}</li>@endif
                            @if($video)<li><i class="bi bi-badge-vr"></i> 360° video</li>@endif
                        </ul>
                        @if($unit->description)
                            <p class="lp-unit-desc">{{ Str::limit($unit->description, 90) }}</p>
                        @endif
                        @if($amenities->isNotEmpty())
                            <div class="lp-chips">
                                @foreach($amenities->take(3) as $amenity)
                                    <span class="lp-chip">{{ $amenity }}</span>
                                @endforeach
                                @if($amenities->count() > 3)
                                    <span class="lp-chip lp-chip-more">+{{ $amenities->count() - 3 }} more</span>
                                @endif
                            </div>
                        @endif
                        <div class="lp-unit-actions">
                            <a class="lp-btn lp-btn-outline" href="{{ route('public.unit', $unit) }}">View details</a>
                            <a class="lp-btn lp-btn-navy" href="{{ route('public.booking', ['unit' => $unit->id]) }}" data-book><i class="bi bi-calendar-check"></i> Book</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <p class="lp-style-empty" id="unitStyleEmpty" hidden></p>
        <p class="lp-form-note lp-center" style="margin-top:22px;">Booking opens the date calendar. After you choose a {{ $isRoom ? 'room' : 'cottage' }}, sign in to confirm the reservation.</p>
    @endif
</section>

@endsection

@push('scripts')
<script>
(function () {
    const pick = document.querySelector('.lp-style-pick');
    if (!pick) return;
    const room = pick.dataset.kind === 'room';
    const plural = room ? 'rooms' : 'cottages';
    const singular = room ? 'room' : 'cottage';
    const trigger = pick.querySelector('.lp-style-trigger');
    const menu = pick.querySelector('.lp-style-menu');
    const label = pick.querySelector('.lp-style-label');
    const cards = Array.from(document.querySelectorAll('.lp-unit-card'));
    const count = document.getElementById('unitCount');
    const empty = document.getElementById('unitStyleEmpty');
    const closeMenu = () => {
        menu.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
    };
    trigger.addEventListener('click', () => {
        const open = menu.hidden;
        menu.hidden = !open;
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', (event) => {
        if (!pick.contains(event.target)) closeMenu();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeMenu();
    });
    menu.querySelectorAll('[data-style]').forEach((item) => {
        item.addEventListener('click', () => {
            const style = item.dataset.style;
            menu.querySelectorAll('[data-style]').forEach((other) => other.classList.toggle('is-on', other === item));
            label.textContent = item.textContent.trim();
            let shown = 0;
            cards.forEach((card) => {
                const match = style === 'all' || card.dataset.style === style;
                card.classList.toggle('is-filtered', !match);
                if (match) shown += 1;
            });
            if (count) count.textContent = shown + ' ' + (shown === 1 ? singular : plural) + ' available';
            if (empty) {
                empty.hidden = shown !== 0;
                empty.textContent = 'No ' + (style === 'deluxe' ? 'deluxe' : 'normal') + ' ' + plural + ' to show.';
            }
            closeMenu();
        });
    });
})();
</script>
@endpush
