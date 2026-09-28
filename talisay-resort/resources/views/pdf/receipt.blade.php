<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - {{ $booking->reference_no }}</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; color: #1e293b; margin: 40px; }
        .header { text-align: center; border-bottom: 3px solid #0ea5e9; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { color: #0c4a6e; margin: 0; font-size: 24px; }
        .header p { color: #64748b; margin: 5px 0 0; font-size: 12px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .info-item { font-size: 13px; }
        .info-item strong { color: #0c4a6e; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #f0f9ff; color: #0c4a6e; padding: 10px; text-align: left; font-size: 13px; }
        td { padding: 10px; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
        .total-row { font-weight: bold; font-size: 16px; background: #f0f9ff; }
        .footer { margin-top: 40px; text-align: center; color: #94a3b8; font-size: 11px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef9c3; color: #854d0e; }
    </style>
</head>
<body>
    <div class="header">
        <h1>TALISAY BEACH RESORT</h1>
        <p>Barangay Maslug, Baybay City, Leyte</p>
        <p>Email: {{ setting('resort_email', 'info@talisayresort.com') }} | Phone: {{ setting('resort_phone', '+63 (053) 563-7000') }}</p>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;">
        <div>
            <h2 style="margin:0;color:#0c4a6e;font-size:18px;">PAYMENT RECEIPT</h2>
            <p style="margin:5px 0 0;color:#64748b;font-size:12px;">Reference: {{ $booking->reference_no }}</p>
        </div>
        <div class="status-badge status-{{ $booking->status }}">{{ strtoupper($booking->status) }}</div>
    </div>

    <div class="info-grid">
        <div class="info-item"><strong>Guest:</strong> {{ $booking->guest_name }}</div>
        <div class="info-item"><strong>Contact / Email:</strong> {{ $booking->guest_contact }}</div>
        <div class="info-item"><strong>Booking Date:</strong> {{ $booking->booking_date->format('F d, Y') }}</div>
        <div class="info-item"><strong>Room &amp; Cottage:</strong> {{ $booking->accommodationUnit->unit_number ?? 'Exclusive Full Resort' }}</div>
        <div class="info-item"><strong>Guests:</strong> {{ $booking->guests_count }}</div>
        @if($booking->payment)
        <div class="info-item"><strong>Payment Method:</strong> {{ ucfirst($booking->payment->gateway) }}</div>
        <div class="info-item"><strong>Transaction ID:</strong> {{ $booking->payment->transaction_id ?? 'N/A' }}</div>
        @endif
    </div>

    <table>
        <thead>
            <tr><th>Description</th><th>Qty</th><th style="text-align:right;">Unit Price</th><th style="text-align:right;">Amount</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $booking->accommodationUnit->unit_number ?? 'Full Resort Exclusive' }}
                    @if($booking->accommodationUnit)
                    <br><small style="color:#64748b;">{{ $booking->accommodationUnit->variant_label }} {{ $booking->accommodationUnit->type_label }}</small>
                    @endif
                </td>
                <td>{{ $booking->nights_count ?? 1 }} Night(s)</td>
                <td style="text-align:right;">PHP {{ number_format($booking->accommodationUnit->price_per_night ?? $booking->total_amount, 2) }}/night</td>
                <td style="text-align:right;">PHP {{ number_format($booking->total_amount, 2) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" style="text-align:right;">TOTAL:</td>
                <td style="text-align:right;">PHP {{ number_format($booking->total_amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    @if($booking->special_requests)
    <div style="margin-top:20px;">
        <strong>Special Requests:</strong>
        <p style="color:#64748b;font-size:12px;">{{ $booking->special_requests }}</p>
    </div>
    @endif

    <div class="footer">
        <p>Thank you for choosing Talisay Beach Resort!</p>
        <p>This receipt was generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
        <p>&copy; 2026 Talisay Beach Resort. All rights reserved.</p>
    </div>
</body>
</html>