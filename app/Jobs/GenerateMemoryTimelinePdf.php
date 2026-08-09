<?php

namespace App\Jobs;

use App\Models\MemoryTimeline;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;

class GenerateMemoryTimelinePdf implements ShouldQueue
{
    use Queueable;

    public function __construct(public MemoryTimeline $timeline) {}

    public function handle(): void
    {
        $timeline->load(['user', 'booking.package']);

        $items = \App\Models\MemoryTimelineItem::where('user_id', $timeline->user_id)
            ->where('booking_id', $timeline->booking_id)
            ->where('is_selected', true)
            ->get();

        $data = [
            'timeline' => $timeline,
            'items' => $items,
            'booking' => $timeline->booking,
            'package' => $timeline->booking->package,
            'guest' => $timeline->user,
        ];

        $pdf = Pdf::loadView('pdf.memory-timeline', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = "memory-timelines/{$timeline->id}-" . now()->format('YmdHis') . '.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        $timeline->update([
            'generated_pdf_path' => $filename,
            'is_generated' => true,
        ]);

        \App\Models\NotificationModel::create([
            'user_id' => $timeline->user_id,
            'type' => 'memory_timeline',
            'title' => 'Memory Timeline Ready',
            'message' => 'Your Talisay Beach memory timeline has been generated and is ready for download!',
            'data' => ['timeline_id' => $timeline->id],
        ]);
    }
}
