{{-- Extends the primary layout template designed for dashboard panels --}}
@extends('layouts.app')

{{-- Sets the HTML document title for the reviews moderation page --}}
@section('title', 'Reviews - Talisay Smart Tourism')

{{-- Defines the main content area section of the template --}}
@section('content')

{{-- Header layout block containing page title and subtitle details --}}
<div class="mb-6">
    {{-- Principal title heading for reviews --}}
    <h1 class="text-2xl font-bold text-gray-800">Reviews & Feedback</h1>
    {{-- Small help description text --}}
    <p class="text-gray-500 text-sm">Moderate and approve guest reviews before public display</p>
</div>

{{-- Main data listing card container --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-4 border-b flex items-center justify-between">
        <form method="GET" action="{{ route('reviews.index') }}" class="flex gap-2">
            <select name="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Reviews</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Moderation</option>
            </select>
        </form>
    </div>
    {{-- Table wrapper ensuring responsiveness --}}
    <div class="table-responsive">
        {{-- Dynamic data list table --}}
        <table class="table table-hover text-sm mb-0">
            {{-- Header columns --}}
            <thead class="table-light">
                <tr>
                    <th>Guest</th>
                    <th>Package Visit</th>
                    <th>Star Rating</th>
                    <th>Comment Content</th>
                    <th>Approval Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            {{-- Table body tracking entries --}}
            <tbody>
                {{-- Loop through each guest review record --}}
                @foreach($reviews as $review)
                <tr>
                    {{-- Guest name reporting the review --}}
                    <td class="font-semibold">{{ $review->user->name ?? 'Anonymous' }}</td>
                    {{-- Booked package title visit cells --}}
                    <td>{{ $review->booking->package->name ?? 'Package visit' }}</td>
                    {{-- Star rating stars graphics display cell --}}
                    <td>
                        {{-- Iterate 1 to 5 to print full or empty stars --}}
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }} text-warning"></i>
                        @endfor
                    </td>
                    {{-- Truncate review comments text for nice fit --}}
                    <td class="text-truncate" style="max-width:250px;" title="{{ $review->comment }}">{{ $review->comment }}</td>
                    {{-- Status badges containing dynamic approval state --}}
                    <td>
                        <span class="badge bg-{{ $review->is_approved ? 'success' : 'warning' }}">{{ $review->is_approved ? 'Approved' : 'Pending' }}</span>
                    </td>
                    {{-- Action buttons panel --}}
                    <td>
                        {{-- Show approve option only if not already approved --}}
                        @if(!$review->is_approved)
                        <form method="POST" action="{{ route('reviews.approve', $review) }}" class="d-inline" onsubmit="return confirm('Approve this review?')">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-check-circle"></i> Approve</button>
                        </form>
                        @endif
                        {{-- Reject action form trigger --}}
                        <form method="POST" action="{{ route('reviews.reject', $review) }}" class="d-inline" onsubmit="return confirm('Reject/hide this review?')">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> Reject</button>
                        </form>
                    </td>
                </tr>
                @endforeach
                {{-- Empty state row display feedback --}}
                @if($reviews->isEmpty())
                <tr>
                    <td colspan="6" class="text-center text-gray-400 py-8">No guest reviews submitted yet.</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    {{-- Laravel standard paginator links --}}
    <div class="p-4">{{ $reviews->links() }}</div>
</div>
@endsection