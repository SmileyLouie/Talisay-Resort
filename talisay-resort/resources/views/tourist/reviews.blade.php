@extends('layouts.tourist')

@section('title', 'My Reviews - Talisay Beach Resort')

@section('content')

<div class="bg-ocean-gradient text-white py-10 px-4 shadow-lg mb-8">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white mb-1">My Reviews &amp; Feedback</h1>
            <p class="text-xs sm:text-sm text-sky-100 mb-0">Share your resort experience and rate your stay</p>
        </div>
        @if(!$myBookings->isEmpty())
        <button onclick="document.getElementById('addReviewModal').classList.remove('hidden')" class="bg-white text-ocean-900 hover:bg-sky-50 font-bold px-4 py-2.5 rounded-xl text-xs shadow transition flex items-center gap-1.5">
            <i class="bi bi-star-fill text-amber-500"></i>Write a Review
        </button>
        @endif
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    @if($myReviews->isEmpty())
    <div class="bg-white rounded-3xl p-12 text-center border border-slate-200 shadow-sm">
        <div class="w-16 h-16 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold">
            <i class="bi bi-star"></i>
        </div>
        <h3 class="text-lg font-bold text-slate-800 mb-1">No Reviews Written Yet</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto mb-4">Have you visited Talisay Beach Resort? Leave a review to share your feedback with us!</p>
        @if(!$myBookings->isEmpty())
        <button onclick="document.getElementById('addReviewModal').classList.remove('hidden')" class="bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow transition">
            <i class="bi bi-star-fill me-1.5"></i>Write Your First Review
        </button>
        @else
        <a href="{{ route('tourist.accommodations') }}" class="bg-ocean-600 text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow no-underline inline-block">
            Book a Room / Cottage First
        </a>
        @endif
    </div>
    @else

    <div class="grid md:grid-cols-2 gap-6">
        @foreach($myReviews as $review)
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    {{-- Star Rating --}}
                    <div class="flex items-center text-amber-400 gap-1 text-base">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi {{ $i <= $review->rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                        @endfor
                        <span class="text-xs font-bold text-slate-700 ms-1">({{ $review->rating }}/5)</span>
                    </div>

                    {{-- Status Badge --}}
                    @if($review->is_comment_blocked)
                    <span class="text-xs font-bold bg-rose-100 text-rose-800 px-3 py-1 rounded-full border border-rose-200" title="Comment hidden for inappropriate words; star rating is active">
                        <i class="bi bi-eye-slash-fill me-1"></i>Comment Hidden
                    </span>
                    @else
                    <span class="text-xs font-bold bg-emerald-100 text-emerald-800 px-3 py-1 rounded-full border border-emerald-200">
                        <i class="bi bi-check-circle-fill me-1"></i>Published
                    </span>
                    @endif
                </div>

                @if($review->is_comment_blocked)
                <div class="bg-rose-50 border border-rose-200 rounded-xl p-3 text-xs text-rose-700 italic flex items-start gap-2 mb-3">
                    <i class="bi bi-slash-circle-fill text-rose-500 flex-shrink-0 mt-0.5"></i>
                    <span>This comment was hidden by the moderator due to inappropriate language. Your star rating ({{ $review->rating }}/5 stars) remains permanently recorded.</span>
                </div>
                @else
                <p class="text-sm text-slate-700 mb-3 leading-relaxed">
                    "{{ $review->comment }}"
                </p>
                @endif
            </div>

            <div class="pt-3 border-t border-slate-100 text-xs text-slate-400 flex items-center justify-between">
                <span><i class="bi bi-building me-1"></i>{{ $review->booking->accommodationUnit->unit_number ?? 'Resort Stay' }}</span>
                <span>{{ $review->created_at->format('M d, Y') }}</span>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>

{{-- ── Write Review Modal ────────────────────────────────────────────── --}}
<div id="addReviewModal" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-lg font-extrabold text-slate-900 mb-0">Write Resort Review</h3>
            <button onclick="document.getElementById('addReviewModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-700"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="bg-sky-50 border border-sky-200 rounded-xl p-2.5 text-xs text-sky-800 flex items-start gap-2">
            <i class="bi bi-info-circle-fill text-sky-600 flex-shrink-0 mt-0.5"></i>
            <span>Your review and star rating are published automatically. Please keep written comments respectful and free of offensive language.</span>
        </div>

        <form action="{{ route('tourist.reviews.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Select Stay / Booking</label>
                <select name="booking_id" class="w-full form-select text-sm rounded-xl" required>
                    @foreach($myBookings as $b)
                    <option value="{{ $b->id }}">{{ $b->reference_no }} — {{ $b->accommodationUnit->unit_number ?? 'Stay' }}</option>
                    @endforeach
                </select>
            </div>

            <div x-data="{ rating: 5 }">
                <label class="block text-xs font-bold text-slate-700 mb-2">Star Rating</label>
                <input type="hidden" name="rating" :value="rating">
                <div class="flex items-center gap-1" role="radiogroup" aria-label="Star rating">
                    @for($i = 1; $i <= 5; $i++)
                    <button
                        type="button"
                        @click="rating = {{ $i }}"
                        class="text-3xl leading-none p-0 border-0 bg-transparent"
                        :class="rating >= {{ $i }} ? 'text-amber-400' : 'text-slate-300'"
                        :aria-checked="rating === {{ $i }}"
                        role="radio"
                        aria-label="{{ $i }} {{ $i === 1 ? 'star' : 'stars' }}"
                    >
                        <i class="bi" :class="rating >= {{ $i }} ? 'bi-star-fill' : 'bi-star'"></i>
                    </button>
                    @endfor
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Your Feedback / Review</label>
                <textarea name="comment" rows="4" class="w-full form-control rounded-xl text-sm" placeholder="Tell us about your experience, staff service, cleanliness, resort amenities..." required></textarea>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="button" onclick="document.getElementById('addReviewModal').classList.add('hidden')" class="flex-1 btn btn-light rounded-xl font-bold text-sm">Cancel</button>
                <button type="submit" class="flex-1 bg-amber-500 hover:bg-amber-600 text-white font-bold text-sm py-2.5 rounded-xl shadow">Submit Review</button>
            </div>
        </form>
    </div>
</div>

@endsection
