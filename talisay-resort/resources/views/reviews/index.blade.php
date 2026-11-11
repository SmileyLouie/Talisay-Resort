@extends('layouts.app')

@section('title', 'Guest Reviews & Feedback - Talisay Smart Tourism')

@section('content')

@php
    $commentStatus = (string) request('comment_status');
    $rating = (string) request('rating');
    $commentLabels = ['active' => 'Visible', 'blocked' => 'Blocked'];
    $commentCounts = ['active' => $activeCommentsCount, 'blocked' => $blockedCommentsCount];
    $ratingLabel = fn ($value) => $value === 'low' ? '1–2 stars' : $value . ' ' . ($value === '1' ? 'star' : 'stars');
    $initials = fn ($name) => collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: 'G';
    $filtered = request()->anyFilled(['search', 'comment_status', 'rating']);
@endphp

<div class="res-head">
    <div>
        <h1>{{ __('Reviews & Feedback') }}</h1>
        <p>Every review is published right away. Star ratings can't be changed or removed. Offensive words are blocked automatically, in English, Filipino, Cebuano, and other languages. The rating still counts.</p>
    </div>
</div>

<div class="unit-stats" data-live-region="review-stats">
    <div>
        <span>Average rating</span>
        <strong class="rev-average">{{ number_format((float) $averageRating, 1) }} <i class="bi bi-star-fill"></i></strong>
        <em>{{ $totalReviews ? 'From ' . $totalReviews . ' ' . ($totalReviews === 1 ? 'review' : 'reviews') : 'No reviews yet' }}</em>
    </div>
    <div>
        <span>Reviews</span>
        <strong>{{ $totalReviews }}</strong>
        <em>{{ $thisMonthCount }} this month</em>
    </div>
    <div>
        <span>Visible comments</span>
        <strong class="{{ $activeCommentsCount ? 'is-good' : '' }}">{{ $activeCommentsCount }}</strong>
        <em>Shown to guests</em>
    </div>
    <div>
        <span>Blocked comments</span>
        <strong class="{{ $blockedCommentsCount ? 'is-bad' : '' }}">{{ $blockedCommentsCount }}</strong>
        <em>
            @if($blockedCommentsCount)
                <a href="{{ route('reviews.index', ['comment_status' => 'blocked']) }}" class="pay-link">Rating still counted</a>
            @else
                Rating still counted
            @endif
        </em>
    </div>
    <div>
        <span>Low ratings</span>
        <strong class="{{ $lowRatingCount ? 'is-warn' : '' }}">{{ $lowRatingCount }}</strong>
        <em>
            @if($lowRatingCount)
                <a href="{{ route('reviews.index', ['rating' => 'low']) }}" class="pay-link">1–2 stars to follow up</a>
            @else
                No 1–2 star reviews
            @endif
        </em>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <form method="GET" action="{{ route('reviews.index') }}" class="res-toolbar">
        <input type="hidden" name="comment_status" value="{{ $commentStatus }}">
        <input type="hidden" name="rating" value="{{ $rating }}">

        <div class="dropdown" data-live-region="review-comment-filter">
            <button type="button" class="manual-kind-btn {{ $commentStatus !== '' ? 'is-active' : '' }}" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Filter by comment status">
                <span>{{ $commentStatus !== '' ? ($commentLabels[$commentStatus] ?? 'All comments') : 'All comments' }}</span>
                <span class="unit-filter-count">{{ $commentStatus !== '' ? ($commentCounts[$commentStatus] ?? 0) : $totalReviews }}</span>
            </button>
            <ul class="dropdown-menu manual-kind-menu unit-filter-menu">
                <li>
                    <a class="dropdown-item {{ $commentStatus === '' ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['comment_status' => null, 'page' => null]) }}">
                        <span>All comments</span><span class="unit-filter-count">{{ $totalReviews }}</span>
                    </a>
                </li>
                @foreach($commentLabels as $code => $label)
                <li>
                    <a class="dropdown-item {{ $commentStatus === $code ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['comment_status' => $code, 'page' => null]) }}">
                        <span>{{ $label }}</span><span class="unit-filter-count">{{ $commentCounts[$code] }}</span>
                    </a>
                </li>
                @endforeach
            </ul>
        </div>

        <div class="dropdown" data-live-region="review-rating-filter">
            <button type="button" class="manual-kind-btn {{ $rating !== '' ? 'is-active' : '' }}" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Filter by star rating">
                <span>{{ $rating !== '' ? $ratingLabel($rating) : 'All ratings' }}</span>
            </button>
            <ul class="dropdown-menu manual-kind-menu unit-filter-menu">
                <li>
                    <a class="dropdown-item {{ $rating === '' ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['rating' => null, 'page' => null]) }}">
                        <span>All ratings</span><span class="unit-filter-count">{{ $totalReviews }}</span>
                    </a>
                </li>
                @foreach(['5', '4', '3', '2', '1'] as $stars)
                <li>
                    <a class="dropdown-item {{ $rating === $stars ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['rating' => $stars, 'page' => null]) }}">
                        <span class="rev-menu-stars" aria-hidden="true">
                            @for($i = 1; $i <= 5; $i++)<i class="bi bi-star{{ $i <= (int) $stars ? '-fill' : '' }}"></i>@endfor
                        </span>
                        <span class="visually-hidden">{{ $stars }} {{ $stars === '1' ? 'star' : 'stars' }}</span>
                        <span class="unit-filter-count">{{ $ratingCounts[(int) $stars] ?? 0 }}</span>
                    </a>
                </li>
                @endforeach
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item {{ $rating === 'low' ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['rating' => 'low', 'page' => null]) }}">
                        <span>Low ratings (1–2 stars)</span><span class="unit-filter-count">{{ $lowRatingCount }}</span>
                    </a>
                </li>
            </ul>
        </div>

        @if($filtered)
        <a href="{{ route('reviews.index') }}" class="res-clear">Clear</a>
        @endif

        <div class="res-search unit-search">
            <i class="bi bi-search"></i>
            <input type="search" name="search" class="form-control-clean" placeholder="Guest, comment, or booking ref" value="{{ request('search') }}" aria-label="Search reviews" data-live-pause-if-filled>
        </div>
        <span class="unit-count" data-live-region="review-count">{{ $reviews->total() }} {{ $reviews->total() === 1 ? 'review' : 'reviews' }}</span>
    </form>

    <div class="table-responsive" data-live-region="review-list">
        <table class="table-clean rev-table">
            <thead>
                <tr>
                    <th>{{ __('Guest') }}</th>
                    <th>{{ __('Rating') }}</th>
                    <th>{{ __('Comment') }}</th>
                    <th>{{ __('Comment status') }}</th>
                    <th>{{ __('Posted') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $review)
                @php
                    $guestName = $review->user->name ?? $review->booking->guest_name ?? 'Guest';
                    $booking = $review->booking;
                    $stay = $booking ? ($booking->booking_type === 'special_resort' ? 'Full resort' : ($booking->accommodationUnit->unit_number ?? 'Resort stay')) : 'Resort stay';
                @endphp
                <tr class="{{ $review->is_comment_blocked ? 'rev-row-blocked' : '' }}">
                    <td>
                        <div class="staff-person">
                            <span class="pay-avatar staff-avatar">{{ $initials($guestName) }}</span>
                            <div class="min-w-0">
                                <span class="res-guest">{{ $guestName }}</span>
                                <div class="res-note">{{ $stay }}@if($booking) · <span class="res-ref rev-ref">{{ $booking->reference_no }}</span>@endif</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="rev-stars" title="{{ $review->rating }} out of 5 stars" aria-label="{{ $review->rating }} out of 5 stars">
                            @for($i = 1; $i <= 5; $i++)<i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>@endfor
                        </span>
                        <div class="res-note">{{ $review->rating }}.0 out of 5</div>
                    </td>
                    <td>
                        @if(filled($review->comment))
                            <p class="rev-comment {{ $review->is_comment_blocked ? 'is-blocked' : '' }}" title="{{ $review->comment }}">{{ $review->comment }}</p>
                        @else
                            <span class="res-note m-0">Rating only, no comment</span>
                        @endif
                        @if($review->is_comment_blocked)
                            <div class="rev-block-reason"><i class="bi bi-shield-x"></i> {{ $review->block_reason ?? 'Hidden for breaking the review rules' }}</div>
                        @endif
                    </td>
                    <td>
                        @if($review->is_comment_blocked)
                            <span class="res-status res-status-cancelled">Blocked</span>
                            @if($review->comment_blocked_at)
                            <div class="res-note">Since {{ $review->comment_blocked_at->format('M j, Y') }}</div>
                            @endif
                        @else
                            <span class="res-status res-status-paid">Visible</span>
                        @endif
                    </td>
                    <td>
                        {{ $review->created_at->format('M j, Y') }}
                        <div class="res-note">{{ $review->created_at->diffForHumans() }}</div>
                    </td>
                    <td class="text-end">
                        <div class="unit-actions">
                            @if($review->is_comment_blocked)
                                <form method="POST" action="{{ route('reviews.unblock-comment', $review) }}" class="m-0" onsubmit="return confirm('Show this comment to guests again?')">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="rev-action" title="Show the comment again">
                                        <i class="bi bi-eye"></i>
                                        <span>Unblock</span>
                                    </button>
                                </form>
                            @elseif(filled($review->comment))
                                <button type="button" class="rev-action is-danger"
                                        onclick="openBlockModal({{ $review->id }}, {{ Js::from($guestName) }}, {{ Js::from($review->comment) }}, {{ (int) $review->rating }})">
                                    <i class="bi bi-slash-circle"></i>
                                    <span>Block</span>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-12 text-slate-400">
                        <i class="bi bi-star text-3xl block mb-2 text-slate-300"></i>
                        <p class="text-sm mb-0">{{ $filtered ? 'No reviews match these filters.' : 'No guest reviews yet. They appear here as soon as a guest rates their stay.' }}</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div data-live-region="review-pages">
        @if($reviews->hasPages())
        <div class="p-4 border-t border-slate-100 flex justify-end">
            {{ $reviews->links() }}
        </div>
        @endif
    </div>
</div>

<div class="modal fade" id="blockModal" tabindex="-1" aria-hidden="true" aria-labelledby="blockModalTitle">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content manual-modal">
            <div class="manual-head">
                <div>
                    <h5 id="blockModalTitle">Block comment</h5>
                    <p>Hide what <strong id="blockGuestName">the guest</strong> wrote. Their star rating stays and still counts.</p>
                </div>
                <button type="button" class="manual-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <form id="blockForm" method="POST" action="" data-loading>
                @csrf
                @method('PUT')
                <div class="manual-body">
                    <div class="manual-row manual-row-top">
                        <label>Comment</label>
                        <div>
                            <span class="rev-stars" id="blockStars"></span>
                            <p class="rev-quote" id="blockComment"></p>
                        </div>
                    </div>
                    <div class="manual-row">
                        <label for="blockReason">Reason</label>
                        <select name="reason" id="blockReason" class="form-select-clean w-100" required>
                            <option value="Inappropriate language / bad words">Inappropriate language or bad words</option>
                            <option value="Profanity or vulgar language">Profanity or vulgar language</option>
                            <option value="Harassment or personal attack">Harassment or a personal attack</option>
                            <option value="Spam or advertising content">Spam or advertising</option>
                            <option value="Violates community conduct guidelines">Breaks the review rules</option>
                        </select>
                    </div>
                </div>
                <div class="manual-foot">
                    <button type="button" class="btn-secondary-clean" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="rev-block-submit"><i class="bi bi-slash-circle"></i> Block comment</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openBlockModal(reviewId, guestName, comment, rating) {
    document.getElementById('blockForm').action = `/reviews/${reviewId}/block-comment`;
    document.getElementById('blockGuestName').textContent = guestName;
    document.getElementById('blockComment').textContent = comment;
    document.getElementById('blockStars').innerHTML = Array.from({ length: 5 }, (_, i) =>
        `<i class="bi bi-star${i < rating ? '-fill' : ''}"></i>`).join('');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('blockModal')).show();
}
</script>
@endpush

@endsection
