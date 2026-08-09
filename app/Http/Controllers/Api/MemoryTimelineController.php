<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemoryTimeline;
use App\Models\MemoryTimelineItem;
use App\Jobs\GenerateMemoryTimelinePdf;
use Illuminate\Http\Request;

class MemoryTimelineController extends Controller
{
    public function addItem(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'type' => 'required|in:photo,video,note',
            'file' => 'required_if:type,photo,video|file|max:20480',
            'caption' => 'nullable|string|max:255',
        ]);

        $booking = \App\Models\Booking::findOrFail($request->booking_id);

        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('memory_items', 'public');
        }

        $item = MemoryTimelineItem::create([
            'user_id' => $request->user()->id,
            'booking_id' => $request->booking_id,
            'type' => $request->type,
            'file_path' => $filePath,
            'caption' => $request->caption,
            'is_selected' => false,
        ]);

        return response()->json(['item' => $item, 'message' => 'Item added to timeline.'], 201);
    }

    public function removeItem($id)
    {
        $item = MemoryTimelineItem::findOrFail($id);

        if ($item->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($item->file_path) {
            \Storage::disk('public')->delete($item->file_path);
        }

        $item->delete();
        return response()->json(['message' => 'Item removed.']);
    }

    public function toggleSelect($id)
    {
        $item = MemoryTimelineItem::findOrFail($id);

        if ($item->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $item->update(['is_selected' => !$item->is_selected]);

        return response()->json(['item' => $item->fresh(), 'message' => 'Selection updated.']);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'title' => 'nullable|string|max:255',
        ]);

        $booking = \App\Models\Booking::findOrFail($request->booking_id);

        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $timeline = MemoryTimeline::updateOrCreate(
            ['user_id' => $request->user()->id, 'booking_id' => $request->booking_id],
            ['title' => $request->title ?? "My Talisay Beach Memory - {$booking->reference_no}", 'is_generated' => false]
        );

        GenerateMemoryTimelinePdf::dispatch($timeline);

        return response()->json(['timeline' => $timeline, 'message' => 'Timeline is being generated. You will be notified when it is ready.']);
    }

    public function download($id)
    {
        $timeline = MemoryTimeline::findOrFail($id);

        if ($timeline->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$timeline->is_generated || !$timeline->generated_pdf_path) {
            return response()->json(['error' => 'Timeline not yet generated'], 422);
        }

        return response()->download(storage_path('app/public/' . $timeline->generated_pdf_path));
    }
}
