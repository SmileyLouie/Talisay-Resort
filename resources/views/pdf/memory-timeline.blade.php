<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Memory Timeline - {{ $timeline->title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; color: #1e293b; background: #fff; }

        /* Cover Page */
        .cover {
            min-height: 100vh;
            background: linear-gradient(135deg, #0c4a6e 0%, #0ea5e9 40%, #14b8a6 70%, #f5e8c7 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: #fff;
            padding: 60px 40px;
            page-break-after: always;
        }
        .cover h1 { font-size: 42px; margin-bottom: 8px; text-shadow: 2px 2px 4px rgba(0,0,0,0.2); }
        .cover h2 { font-size: 28px; font-weight: 300; margin-bottom: 30px; }
        .cover .resort-name { font-size: 18px; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 10px; opacity: 0.9; }
        .cover .guest-name { font-size: 24px; margin-top: 20px; padding: 12px 30px; border: 2px solid rgba(255,255,255,0.6); border-radius: 30px; }
        .cover .date { font-size: 14px; margin-top: 15px; opacity: 0.8; }
        .cover .decorative-line { width: 120px; height: 3px; background: rgba(255,255,255,0.6); margin: 20px auto; border-radius: 2px; }

        /* Content Pages */
        .content { padding: 40px; }
        .page-break { page-break-before: always; }

        /* Section Header */
        .section-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #0ea5e9;
        }
        .section-header h2 { color: #0c4a6e; font-size: 24px; }
        .section-header p { color: #64748b; font-size: 12px; margin-top: 5px; }

        /* Trip Summary */
        .trip-summary {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: #f0f9ff;
            border-left: 4px solid #0ea5e9;
            padding: 15px;
            border-radius: 0 8px 8px 0;
        }
        .summary-card .label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }
        .summary-card .value { font-size: 16px; color: #0c4a6e; font-weight: 600; margin-top: 4px; }

        /* Memory Items */
        .memory-item {
            margin-bottom: 30px;
            padding: 20px;
            background: #fafafa;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            break-inside: avoid;
        }
        .memory-item .item-type {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .type-photo { background: #dbeafe; color: #1e40af; }
        .type-video { background: #dcfce7; color: #166534; }
        .type-note { background: #fef3c7; color: #92400e; }
        .memory-item .caption {
            font-size: 14px;
            color: #334155;
            line-height: 1.6;
            margin-top: 8px;
        }
        .memory-item .photo-placeholder {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #e0f2fe, #ccfbf1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0ea5e9;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .memory-item .timestamp {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 8px;
        }

        /* Timeline connector */
        .timeline-connector {
            width: 2px;
            height: 20px;
            background: linear-gradient(to bottom, #0ea5e9, #14b8a6);
            margin: 0 auto;
        }

        /* Footer */
        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px 40px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }

        /* Closing Page */
        .closing {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5e8c7 0%, #14b8a6 50%, #0c4a6e 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: #fff;
            padding: 60px 40px;
        }
        .closing h2 { font-size: 32px; margin-bottom: 10px; }
        .closing p { font-size: 16px; opacity: 0.9; max-width: 400px; line-height: 1.6; }
        .closing .resort-info { margin-top: 30px; font-size: 12px; opacity: 0.7; }
    </style>
</head>
<body>

    {{-- Cover Page --}}
    <div class="cover">
        <div class="resort-name">Talisay Beach Resort</div>
        <div class="decorative-line"></div>
        <h1>Memory Timeline</h1>
        <h2>{{ $timeline->title }}</h2>
        <div class="guest-name">{{ $guest->name }}</div>
        <div class="date">{{ $booking->booking_date->format('F d, Y') }}</div>
        <div class="decorative-line" style="margin-top:30px;"></div>
        <p style="margin-top:15px;font-size:12px;opacity:0.7;">Barangay Maslug, Baybay City, Leyte</p>
    </div>

    {{-- Trip Summary Page --}}
    <div class="content page-break">
        <div class="section-header">
            <h2>Trip Summary</h2>
            <p>Your beach getaway at a glance</p>
        </div>

        <div class="trip-summary">
            <div class="summary-card">
                <div class="label">Guest Name</div>
                <div class="value">{{ $guest->name }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Package</div>
                <div class="value">{{ $package->name }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Visit Date</div>
                <div class="value">{{ $booking->booking_date->format('F d, Y') }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Number of Guests</div>
                <div class="value">{{ $booking->guests_count }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Time Slot</div>
                <div class="value">{{ $booking->time_slot ?? 'Full Day' }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Booking Reference</div>
                <div class="value">{{ $booking->reference_no }}</div>
            </div>
        </div>

        @if($booking->special_requests)
        <div class="summary-card" style="grid-column: 1 / -1;">
            <div class="label">Special Requests</div>
            <div class="value" style="font-weight:400;font-size:14px;">{{ $booking->special_requests }}</div>
        </div>
        @endif
    </div>

    {{-- Memory Items Pages --}}
    @if($items->count() > 0)
    <div class="content page-break">
        <div class="section-header">
            <h2>Your Memories</h2>
            <p>Moments captured during your stay</p>
        </div>

        @foreach($items as $index => $item)
            @if($index > 0 && $index % 3 === 0)
    </div>
    <div class="content page-break">
            @endif

            <div class="memory-item">
                <span class="item-type type-{{ $item->type }}">{{ $item->type }}</span>

                @if($item->type === 'photo' && $item->file_path)
                    <div class="photo-placeholder">
                        [Photo: {{ basename($item->file_path) }}]
                    </div>
                @elseif($item->type === 'video' && $item->file_path)
                    <div class="photo-placeholder" style="background:linear-gradient(135deg,#dcfce7,#d1fae5);">
                        [Video: {{ basename($item->file_path) }}]
                    </div>
                @endif

                @if($item->caption)
                    <div class="caption">{{ $item->caption }}</div>
                @endif

                <div class="timestamp">{{ $item->created_at->format('F d, Y \a\t h:i A') }}</div>
            </div>

            @if(!$loop->last)
                <div class="timeline-connector"></div>
            @endif
        @endforeach
    </div>
    @endif

    {{-- Closing Page --}}
    <div class="closing page-break">
        <h2>Until We Meet Again</h2>
        <div class="decorative-line" style="width:80px;height:3px;background:rgba(255,255,255,0.6);margin:15px auto;border-radius:2px;"></div>
        <p>Thank you for choosing Talisay Beach Resort. We hope these memories bring a smile to your face until your next visit.</p>
        <div class="resort-info">
            <p>Talisay Beach Resort</p>
            <p>Barangay Maslug, Baybay City, Leyte</p>
            <p>info@talisayresort.com</p>
        </div>
    </div>

</body>
</html>
