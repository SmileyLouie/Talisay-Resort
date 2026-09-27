<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $booking = \App\Models\Booking::findOrFail($request->booking_id);

        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['error' => 'You can only review your own bookings.'], 403);
        }

        if ($booking->status !== 'completed') {
            return response()->json(['error' => 'You can only review completed bookings.'], 422);
        }

        $existingReview = Review::where('booking_id', $request->booking_id)->first();
        if ($existingReview) {
            return response()->json(['error' => 'You have already reviewed this booking.'], 422);
        }

        $hasProfanity = Review::containsProfanity($request->comment);

        $review = Review::create([
            'user_id'            => $request->user()->id,
            'booking_id'         => $request->booking_id,
            'rating'             => $request->rating,
            'comment'            => $request->comment,
            'is_approved'        => true,
            'is_comment_blocked' => $hasProfanity,
            'block_reason'       => $hasProfanity ? 'Automatically blocked: Inappropriate language / profanity detected' : null,
            'comment_blocked_at' => $hasProfanity ? now() : null,
        ]);

        $message = $hasProfanity
            ? 'Review rating published. Note: Your comment was hidden due to inappropriate words.'
            : 'Review published successfully.';

        return response()->json(['review' => $review, 'message' => $message], 201);
    }

    public function myReviews(Request $request)
    {
        $reviews = Review::with('booking.package')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($reviews);
    }
}
