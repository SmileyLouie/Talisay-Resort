@extends('layouts.public')

@section('title', 'Guest Reviews – '.$resortInfo['name'])
@section('meta_description', 'What guests say about their stay at '.$resortInfo['name'].'.')

@section('content')
<section class="lp-banner" style="background-image:url('/images/hero-landing.jpg')">
    <div class="lp-banner-box">
        <div class="lp-eyebrow">Testimonials</div>
        <h1>Guest Reviews</h1>
        <p>Honest words from guests who stayed in our rooms and cottages. Reviews are written on My Bookings after a completed stay.</p>
    </div>
</section>

<section class="lp-section lp-reveal is-in">
    <div class="lp-review-layout">
        {{-- Summary / filter --}}
        <aside class="lp-review-side">
            <div class="lp-rating-card">
                <strong>{{ $reviewCount > 0 ? number_format($averageRating, 1) : '—' }}</strong>
                <span class="lp-stars">{!! str_repeat('<i class="bi bi-star-fill"></i>', (int) round($averageRating)) !!}{!! str_repeat('<i class="bi bi-star"></i>', 5 - (int) round($averageRating)) !!}</span>
                <small>{{ $reviewCount }} {{ $reviewCount === 1 ? 'review' : 'reviews' }}</small>
            </div>
            <ul class="lp-rating-bars">
                <li>
                    <a href="{{ route('public.reviews') }}" class="{{ $rating === 0 ? 'is-active' : '' }}">All ratings <span>{{ $reviewCount }}</span></a>
                </li>
                @foreach($ratingCounts as $stars => $count)
                    @php $pct = $reviewCount ? round($count / $reviewCount * 100) : 0; @endphp
                    <li>
                        <a href="{{ route('public.reviews', ['rating' => $stars]) }}" class="{{ $rating === $stars ? 'is-active' : '' }}">
                            {{ $stars }} <i class="bi bi-star-fill"></i>
                            <i class="lp-bar"><b style="width:{{ $pct }}%"></b></i>
                            <span>{{ $count }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <a class="lp-btn lp-btn-navy full" href="{{ route('public.rooms') }}"><i class="bi bi-calendar-check"></i> Book your own stay</a>
        </aside>

        {{-- List --}}
        <div>
            @if($reviews->isEmpty())
                <div class="lp-review-empty" style="color:var(--tbr-ink);background:#fff;border:1px solid var(--tbr-line);">
                    @if($rating)
                        No {{ $rating }}-star reviews yet. <a href="{{ route('public.reviews') }}">Show all reviews</a>.
                    @else
                        Guest reviews appear here automatically after a stay is completed and the guest leaves a comment on My Bookings.
                    @endif
                </div>
            @else
                <div class="lp-review-list">
                    @foreach($reviews as $review)
                        @php
                            $author = trim((string) ($review->user?->name ?? ''));
                            $parts = $author !== '' ? preg_split('/\s+/', $author) : [];
                            $shown = $author === '' ? 'Resort guest' : $parts[0].(count($parts) > 1 ? ' '.mb_substr(end($parts), 0, 1).'.' : '');
                            $avatar = $review->user?->avatar;
                        @endphp
                        <article class="lp-review is-active">
                            <div class="lp-review-head">
                                @if($avatar)
                                    <img class="lp-avatar" src="/storage/{{ ltrim($avatar, '/') }}" alt="">
                                @else
                                    <div class="lp-avatar">{{ mb_strtoupper(mb_substr($shown, 0, 1)) }}</div>
                                @endif
                                <div>
                                    <strong>{{ $shown }}</strong>
                                    <small>{{ $review->created_at?->format('F j, Y') }}</small>
                                </div>
                                <span class="lp-stars" aria-label="{{ $review->rating }} out of 5">{!! str_repeat('<i class="bi bi-star-fill"></i>', (int) $review->rating) !!}</span>
                            </div>
                            <p>{{ $review->comment }}</p>
                            @if($review->booking?->accommodationUnit)
                                <div class="lp-review-unit">Stayed in <a href="{{ route('public.unit', $review->booking->accommodationUnit) }}">{{ $review->booking->accommodationUnit->unit_number }}</a></div>
                            @endif
                        </article>
                    @endforeach
                </div>
                @if($reviews->hasPages())
                    <div class="lp-pager">
                        @if($reviews->onFirstPage())
                            <span class="lp-btn lp-btn-outline is-disabled"><i class="bi bi-chevron-left"></i> Newer</span>
                        @else
                            <a class="lp-btn lp-btn-outline" href="{{ $reviews->previousPageUrl() }}"><i class="bi bi-chevron-left"></i> Newer</a>
                        @endif
                        <span>Page {{ $reviews->currentPage() }} of {{ $reviews->lastPage() }}</span>
                        @if($reviews->hasMorePages())
                            <a class="lp-btn lp-btn-outline" href="{{ $reviews->nextPageUrl() }}">Older <i class="bi bi-chevron-right"></i></a>
                        @else
                            <span class="lp-btn lp-btn-outline is-disabled">Older <i class="bi bi-chevron-right"></i></span>
                        @endif
                    </div>
                @endif
            @endif
        </div>
    </div>
</section>

@endsection
