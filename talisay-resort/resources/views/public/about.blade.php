@extends('layouts.public')

@section('title', 'About '.$resortInfo['name'])
@section('meta_description', 'Learn about '.$resortInfo['name'].' in '.$resortInfo['address'].': our story, vision, mission, and photos of the resort.')

@section('content')
<section class="lp-banner" style="background-image:url('{{ $bannerImage }}')">
    <div class="lp-banner-box lp-reveal is-in">
        <div class="lp-eyebrow">{{ $resortInfo['address'] }}</div>
        <h1>About {{ $resortInfo['name'] }}</h1>
        <p>Our story, what we stand for, and a look around the resort before you arrive.</p>
    </div>
</section>

{{-- Story --}}
<section class="lp-section lp-about-page lp-reveal" id="story">
    <div class="lp-split">
        <div class="lp-split-text">
            <div class="lp-label">Our story</div>
            <h2 class="lp-title">A beach stay the way it should be</h2>
            <p class="lp-copy">{{ $about['story'] }}</p>
            <div class="lp-stats lp-stats-left">
                <div class="lp-stat"><strong>{{ $roomCount }}</strong><span>{{ $roomCount === 1 ? 'Room' : 'Rooms' }}</span></div>
                <div class="lp-stat"><strong>{{ $cottageCount }}</strong><span>{{ $cottageCount === 1 ? 'Cottage' : 'Cottages' }}</span></div>
                <div class="lp-stat"><strong>{{ $tourScenes->count() }}</strong><span>360° scenes</span></div>
            </div>
            <div class="lp-hero-actions">
                <a class="lp-btn lp-btn-navy" href="{{ route('public.rooms') }}"><i class="bi bi-door-open"></i> See the rooms</a>
                <a class="lp-btn lp-btn-outline" href="{{ route('public.cottages') }}"><i class="bi bi-house-heart"></i> See the cottages</a>
            </div>
        </div>
        <div class="lp-about-media">
            @if($tourVideoUrl)
                <video src="{{ $tourVideoUrl }}" autoplay muted loop playsinline preload="metadata" poster="{{ $bannerImage }}"></video>
            @else
                <img src="{{ $bannerImage }}" alt="{{ $resortInfo['name'] }}">
            @endif
        </div>
    </div>
</section>

{{-- Vision and gallery --}}
<section class="lp-section lp-vm lp-reveal" id="vision" style="padding-top:0;">
    <div class="lp-vm-grid">
        <div class="lp-vm-card">
            <div class="lp-info-icon"><i class="bi bi-eye"></i></div>
            <h2 class="lp-title">Vision</h2>
            <p class="lp-copy">{{ $about['vision'] }}</p>
        </div>
        <div class="lp-vm-card">
            <div class="lp-info-icon"><i class="bi bi-compass"></i></div>
            <h2 class="lp-title">Mission</h2>
            <p class="lp-copy">{{ $about['mission'] }}</p>
        </div>
    </div>
</section>

{{-- Views and attractions. Room and cottage tours stay on their own pages. --}}
<section class="lp-section lp-reveal" id="gallery" style="padding-top:0;">
    <div class="lp-center">
        <div class="lp-label">Gallery</div>
        <h2 class="lp-title">Around the resort</h2>
        <p class="lp-copy">The shore, the sunset, and the places guests come to see. These are the views and attractions of the resort.</p>
    </div>
    @if($galleryPhotos->isEmpty())
        <p class="lp-gallery-empty">Photos of the views and attractions will appear here once the resort adds them.</p>
    @else
        <div class="lp-gallery">
            @foreach($galleryPhotos as $photo)
                <button type="button" class="lp-gallery-item {{ $loop->first ? 'lp-gallery-wide' : '' }}" data-gallery-open data-src="{{ $photo->imageUrl() }}" data-title="{{ $photo->title }}" data-caption="{{ $photo->caption }}">
                    <img src="{{ $photo->imageUrl() }}" alt="{{ $photo->title ?: 'Resort view' }}" loading="lazy">
                    @if($photo->title || $photo->caption)
                        <span class="lp-gallery-cap">
                            @if($photo->title)<strong>{{ $photo->title }}</strong>@endif
                            @if($photo->caption)<small>{{ $photo->caption }}</small>@endif
                        </span>
                    @endif
                </button>
            @endforeach
        </div>
        <dialog class="lp-gallery-view" id="galleryView">
            <figure>
                <img alt="" id="galleryViewImage">
                <figcaption id="galleryViewText" hidden></figcaption>
            </figure>
            <button type="button" class="lp-gallery-close" id="galleryViewClose" aria-label="Close photo">×</button>
        </dialog>
    @endif
</section>

@if(($activities ?? collect())->isNotEmpty())
<section class="lp-section lp-reveal" id="activities" style="padding-top:0;">
    <div class="lp-center">
        <div class="lp-label">Activities</div>
        <h2 class="lp-title">What you can do here</h2>
        <p class="lp-copy">Arranged at the resort. Ask the front desk to book a slot.</p>
    </div>
    <div class="lp-acts">
        @foreach($activities as $activity)
            <article class="lp-act">
                @if($activity->imageUrl())
                    <img src="{{ $activity->imageUrl() }}" alt="{{ $activity->title }}" loading="lazy">
                @else
                    <div class="lp-act-icon"><i class="bi {{ $activity->icon ?: 'bi-water' }}"></i></div>
                @endif
                <div class="lp-act-body">
                    <h3>{{ $activity->title }}</h3>
                    @if($activity->description)<p>{{ $activity->description }}</p>@endif
                    <div class="lp-act-meta">
                        @if($activity->schedule)<span>{{ $activity->schedule }}</span>@endif
                        @if($activity->price !== null)<strong>₱{{ number_format($activity->price, 0) }}</strong>@endif
                    </div>
                    @if($activity->is_bookable)
                        <a class="lp-btn lp-btn-outline" href="{{ route('public.booking', ['activity' => $activity->id]) }}" style="margin-top:12px;">Add to a booking</a>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
</section>
@endif

{{-- 360 tour callout --}}
<section class="lp-video lp-video-short" id="tour">
    @if($tourVideoUrl)
        <video src="{{ $tourVideoUrl }}" autoplay muted loop playsinline preload="metadata" poster="{{ $bannerImage }}"></video>
    @else
        <div class="lp-slide is-active" style="position:absolute;inset:0;background-image:url('{{ $bannerImage }}');opacity:1;transform:none;"></div>
    @endif
    <div class="lp-video-overlay">
        <div class="lp-label">Virtual tour</div>
        <h2>Look around before you book</h2>
        <p>Walk the beach, the cottages, and the rooms in 360°. Drag to look around each scene, or let it play on its own.</p>
        <div class="lp-video-actions">
            <a class="lp-btn lp-btn-sand" href="{{ route('tour.viewer') }}" target="_blank" rel="noopener"><i class="bi bi-badge-vr"></i> Open the 360° tour</a>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-gallery-open]').forEach((button) => {
    button.addEventListener('click', () => {
        const dialog = document.getElementById('galleryView');
        const image = document.getElementById('galleryViewImage');
        const text = document.getElementById('galleryViewText');
        if (!dialog || !image) return;
        image.src = button.dataset.src || '';
        image.alt = button.dataset.title || 'Resort view';
        const lines = [button.dataset.title, button.dataset.caption].filter(Boolean);
        text.hidden = lines.length === 0;
        text.textContent = lines.join(' — ');
        dialog.showModal();
    });
});
document.getElementById('galleryViewClose')?.addEventListener('click', () => document.getElementById('galleryView')?.close());
document.getElementById('galleryView')?.addEventListener('click', (event) => {
    if (event.target === event.currentTarget) event.currentTarget.close();
});
</script>
@endpush
