@extends('layouts.public')

@section('title', 'Activities – '.$resortInfo['name'])
@section('meta_description', 'Snorkeling, island hopping, and other activities guests can add to a stay at '.$resortInfo['name'].'.')

@section('content')
<section class="lp-banner" style="background-image:url('{{ $bannerImage }}')">
    <div class="lp-banner-box">
        <div class="lp-eyebrow">Things to do</div>
        <h1>Activities</h1>
        <p>Snorkeling, island hopping, and other activities you can add to a room or cottage booking.</p>
    </div>
</section>

<section class="lp-section lp-reveal is-in">
    <div class="lp-list-head">
        <div>
            <div class="lp-label">{{ ($activities ?? collect())->count() }} {{ Str::plural('activity', ($activities ?? collect())->count()) }} available</div>
            <h2 class="lp-title">Pick an activity</h2>
        </div>
        <div class="lp-list-meta">
            <a class="lp-btn lp-btn-outline" href="{{ route('public.rooms') }}">See rooms <i class="bi bi-arrow-right"></i></a>
            <a class="lp-btn lp-btn-outline" href="{{ route('public.cottages') }}">See cottages <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>

    @if(($activities ?? collect())->isEmpty())
        <div class="lp-slider-empty">Activities will appear here as soon as the resort adds them.</div>
    @else
        <div class="lp-unit-grid">
            @foreach($activities as $activity)
                <article class="lp-unit-card">
                    <div class="lp-unit-media">
                        @if($activity->imageUrl())
                            <img src="{{ $activity->imageUrl() }}" alt="{{ $activity->title }}" loading="lazy">
                        @else
                            <div class="lp-ph"><i class="bi {{ $activity->icon ?: 'bi-water' }}"></i></div>
                        @endif
                        <span class="lp-unit-tag {{ $activity->is_bookable ? '' : 'is-premium' }}">{{ $activity->is_bookable ? 'Bookable' : 'Info' }}</span>
                    </div>
                    <div class="lp-unit-body">
                        <div class="lp-unit-top">
                            <div>
                                <small>Activity</small>
                                <h3>{{ $activity->title }}</h3>
                            </div>
                            <div class="lp-unit-price">
                                @if($activity->price !== null)
                                    <strong>₱{{ number_format($activity->price, 0) }}</strong><span>per guest</span>
                                @else
                                    <strong>Included</strong><span>with stay</span>
                                @endif
                            </div>
                        </div>
                        <ul class="lp-unit-facts">
                            @if($activity->schedule)<li><i class="bi bi-clock"></i> {{ $activity->schedule }}</li>@endif
                            @if($activity->duration)<li><i class="bi bi-hourglass-split"></i> {{ $activity->duration }}</li>@endif
                            @if($activity->max_guests)<li><i class="bi bi-people"></i> Up to {{ $activity->max_guests }} guests</li>@endif
                        </ul>
                        @if($activity->description)
                            <p class="lp-unit-desc">{{ Str::limit($activity->description, 90) }}</p>
                        @endif
                        <div class="lp-unit-actions">
                            @if($activity->is_bookable)
                                <a class="lp-btn lp-btn-outline" href="{{ route('public.booking') }}" data-book>View booking</a>
                                <a class="lp-btn lp-btn-navy" href="{{ route('public.booking', ['activity' => $activity->id]) }}" data-book><i class="bi bi-plus-lg"></i> Add</a>
                            @else
                                <a class="lp-btn lp-btn-outline" href="{{ route('public.contact') }}">Ask resort</a>
                                <a class="lp-btn lp-btn-navy" href="{{ route('public.booking') }}" data-book><i class="bi bi-calendar-check"></i> Book stay</a>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <p class="lp-form-note lp-center" style="margin-top:22px;">Add an activity when you book a room or cottage. Sign in to confirm the reservation.</p>
    @endif
</section>
@endsection
