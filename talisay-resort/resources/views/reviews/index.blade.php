@extends('layouts.app')

@section('title', 'Guest Reviews & Feedback - Talisay Smart Tourism')

@section('content')

{{-- Header --}}
<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100/80 text-amber-800 border border-amber-200">
                <i class="bi bi-star-fill text-[10px] text-amber-500"></i>
                Authentic Guest Feedback
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100/80 text-emerald-800 border border-emerald-200">
                <i class="bi bi-check-circle-fill text-[10px] text-emerald-500"></i>
                Auto-Published
            </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-1">
            Reviews &amp; Feedback Moderation
        </h1>
        <p class="text-sm text-slate-500 mb-0">
            All reviews and star ratings are published automatically. Star ratings are authentic and permanent. Admins can block offensive comments containing bad words while preserving the star rating.
        </p>
    </div>

    <div class="flex items-center gap-3 flex-shrink-0">
        <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl px-4 py-2 shadow-sm">
            <i class="bi bi-star-fill text-amber-400 text-sm"></i>
            <span class="text-sm text-slate-500">Average:</span>
            <span class="text-sm font-bold text-slate-800">{{ $averageRating }} / 5.0</span>
        </div>
        <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl px-4 py-2 shadow-sm">
            <span class="text-sm text-slate-500">Total Reviews:</span>
            <span class="text-sm font-bold text-slate-800">{{ $totalReviews }}</span>
        </div>
    </div>
</div>

{{-- Star Integrity Notice --}}
<div class="bg-sky-50 border border-sky-200/90 rounded-2xl p-4 mb-6 flex items-start gap-3 text-sky-900 text-xs">
    <i class="bi bi-shield-check text-sky-600 text-lg flex-shrink-0 mt-0.5"></i>
    <div>
        <strong class="font-bold text-sky-950">Star Rating Transparency Policy:</strong>
        Guest star ratings (1 to 5 stars) are permanently recorded and cannot be altered or removed to protect genuine guest trust. If a review comment contains bad words or offensive language, use the <strong>Block Comment</strong> button to censor the written text. The star rating will remain intact and counted.
    </div>
</div>

{{-- Filters Card --}}
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 mb-6 flex flex-wrap items-center justify-between gap-3">
    <form method="GET" action="{{ route('reviews.index') }}" class="flex flex-wrap items-center gap-3">
        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Filter:</label>
        
        <select name="comment_status" class="form-select-clean text-xs font-medium" onchange="this.form.submit()">
            <option value="">All Comments ({{ $totalReviews }})</option>
            <option value="active" {{ request('comment_status') === 'active' ? 'selected' : '' }}>Visible Comments ({{ $activeCommentsCount }})</option>
            <option value="blocked" {{ request('comment_status') === 'blocked' ? 'selected' : '' }}>Blocked Comments ({{ $blockedCommentsCount }})</option>
        </select>

        <select name="rating" class="form-select-clean text-xs font-medium" onchange="this.form.submit()">
            <option value="">All Star Ratings</option>
            <option value="5" {{ request('rating') == '5' ? 'selected' : '' }}>5 Stars</option>
            <option value="4" {{ request('rating') == '4' ? 'selected' : '' }}>4 Stars</option>
            <option value="3" {{ request('rating') == '3' ? 'selected' : '' }}>3 Stars</option>
            <option value="2" {{ request('rating') == '2' ? 'selected' : '' }}>2 Stars</option>
            <option value="1" {{ request('rating') == '1' ? 'selected' : '' }}>1 Star</option>
        </select>

        @if(request('comment_status') || request('rating'))
        <a href="{{ route('reviews.index') }}" class="btn-secondary-clean text-xs py-1 px-2.5">Reset</a>
        @endif
    </form>

    <div class="flex items-center gap-2 text-xs font-bold text-slate-500">
        <span class="inline-flex items-center gap-1 text-emerald-600">
            <i class="bi bi-check-circle-fill text-xs"></i> {{ $activeCommentsCount }} Visible
        </span>
        <span>•</span>
        <span class="inline-flex items-center gap-1 text-rose-600">
            <i class="bi bi-slash-circle-fill text-xs"></i> {{ $blockedCommentsCount }} Blocked
        </span>
    </div>
</div>

{{-- Reviews Table Card --}}
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table-clean">
            <thead>
                <tr>
                    <th>Guest</th>
                    <th>Stay Unit</th>
                    <th>Star Rating</th>
                    <th>Comment Content</th>
                    <th>Comment Status</th>
                    <th class="text-end">Comment Moderation</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $review)
                <tr class="{{ $review->is_comment_blocked ? 'bg-rose-50/20' : '' }}">
                    <td>
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-xs flex-shrink-0 border border-slate-200">
                                {{ strtoupper(substr($review->user->name ?? 'A', 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-slate-900 text-xs">{{ $review->user->name ?? 'Anonymous' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $review->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </td>

                    <td>
                        <span class="font-semibold text-slate-800 text-xs">
                            {{ $review->booking->accommodationUnit->unit_number ?? 'Resort Stay' }}
                        </span>
                        @if($review->booking)
                        <div class="text-[10px] text-slate-400 font-mono">{{ $review->booking->reference_no }}</div>
                        @endif
                    </td>

                    <td>
                        <div class="flex items-center gap-0.5">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="bi bi-star{{ $i <= $review->rating ? '-fill text-amber-400' : ' text-slate-200' }} text-xs"></i>
                            @endfor
                            <span class="text-xs font-extrabold text-slate-700 ms-1.5">{{ $review->rating }}.0</span>
                        </div>
                        <span class="text-[10px] text-slate-400 font-medium">Permanent</span>
                    </td>

                    <td style="max-width: 320px;">
                        @if($review->is_comment_blocked)
                        <div class="space-y-1">
                            <p class="text-xs text-rose-800 mb-0 font-mono line-through bg-rose-50 p-1.5 rounded border border-rose-200" title="{{ $review->comment }}">
                                {{ $review->comment }}
                            </p>
                            <div class="text-[11px] text-rose-600 flex items-center gap-1 font-semibold">
                                <i class="bi bi-shield-x"></i>
                                <span>{{ $review->block_reason ?? 'Hidden due to bad words / policy violation' }}</span>
                            </div>
                        </div>
                        @else
                        <p class="text-xs text-slate-700 mb-0 leading-relaxed" title="{{ $review->comment }}">
                            {{ $review->comment }}
                        </p>
                        @endif
                    </td>

                    <td>
                        @if($review->is_comment_blocked)
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold border inline-flex items-center gap-1 bg-rose-50 text-rose-700 border-rose-200">
                            <i class="bi bi-eye-slash-fill text-[10px]"></i> Comment Blocked
                        </span>
                        @else
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold border inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 border-emerald-200">
                            <i class="bi bi-eye-fill text-[10px]"></i> Comment Visible
                        </span>
                        @endif
                    </td>

                    <td class="text-end">
                        <div class="flex items-center justify-end gap-1.5">
                            @if($review->is_comment_blocked)
                            {{-- Unblock comment --}}
                            <form method="POST" action="{{ route('reviews.unblock-comment', $review) }}" class="inline-block" onsubmit="return confirm('Restore and unblock this comment for public viewing?')">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn-ocean text-xs py-1 px-2.5 font-bold" title="Restore comment text">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                    <span>Unblock Comment</span>
                                </button>
                            </form>
                            @else
                            {{-- Block comment --}}
                            <button type="button" 
                                    class="btn btn-outline-danger btn-sm text-xs font-bold rounded-xl py-1 px-2.5"
                                    onclick="openBlockModal({{ $review->id }}, {{ Js::from($review->user->name ?? 'Guest') }})">
                                <i class="bi bi-slash-circle me-1"></i>
                                <span>Block Comment</span>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-12 text-slate-400">
                        <i class="bi bi-star text-3xl block mb-2 text-slate-300"></i>
                        <p class="text-xs font-semibold mb-0">No guest reviews found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($reviews->hasPages())
    <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex justify-end">
        {{ $reviews->links() }}
    </div>
    @endif
</div>

{{-- ── Block Comment Modal ────────────────────────────────────────────── --}}
<div id="blockModal" class="hidden fixed inset-0 bg-slate-900/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
                <i class="bi bi-slash-circle-fill text-rose-500 text-lg"></i>
                <h3 class="text-base font-extrabold text-slate-900 mb-0">Block Review Comment</h3>
            </div>
            <button onclick="closeBlockModal()" class="text-slate-400 hover:text-slate-700">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <p class="text-xs text-slate-600 mb-0 leading-relaxed">
            You are blocking the comment by <strong id="blockGuestName">Guest</strong>. 
            <span class="text-slate-800 font-semibold block mt-1">
                <i class="bi bi-star-fill text-amber-500"></i> The guest's star rating cannot be removed and will remain preserved.
            </span>
        </p>

        <form id="blockForm" method="POST" action="" class="space-y-3">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Reason for Blocking Comment:</label>
                <select name="reason" class="form-select text-xs rounded-xl w-full" required>
                    <option value="Inappropriate language / bad words">Inappropriate language / bad words</option>
                    <option value="Profanity or vulgar language">Profanity or vulgar language</option>
                    <option value="Harassment or personal attack">Harassment or personal attack</option>
                    <option value="Spam or advertising content">Spam or advertising content</option>
                    <option value="Violates community conduct guidelines">Violates community conduct guidelines</option>
                </select>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="button" onclick="closeBlockModal()" class="flex-1 btn btn-light rounded-xl font-bold text-xs">
                    Cancel
                </button>
                <button type="submit" class="flex-1 btn btn-danger rounded-xl font-bold text-xs py-2">
                    Confirm Block Comment
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openBlockModal(reviewId, guestName) {
    const modal = document.getElementById('blockModal');
    const form  = document.getElementById('blockForm');
    const nameEl = document.getElementById('blockGuestName');

    form.action = `/reviews/${reviewId}/block-comment`;
    nameEl.textContent = guestName;
    modal.classList.remove('hidden');
}

function closeBlockModal() {
    document.getElementById('blockModal').classList.add('hidden');
}
</script>
@endpush

@endsection