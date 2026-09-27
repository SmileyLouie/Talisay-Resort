<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class WebReviewController extends WebControllers
{
    public function index(Request $request) { return $this->reviewsIndex($request); }
    public function approve(Review $review) { return $this->approveReview($review); }
    public function reject(Review $review) { return $this->rejectReview($review); }
    public function blockComment(Request $request, Review $review) { return $this->blockCommentReview($request, $review); }
    public function unblockComment(Review $review) { return $this->unblockCommentReview($review); }
}
