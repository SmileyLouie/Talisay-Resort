<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $sectionTitle }} — Talisay Beach Resort</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #1e293b; font-size: 12px; line-height: 1.4; }
        @page { margin: 36px; }
        .header { text-align: center; border-bottom: 2px solid #0ea5e9; padding-bottom: 10px; margin-bottom: 18px; }
        .title { font-size: 22px; font-weight: bold; color: #0c4a6e; margin: 0; }
        .subtitle { font-size: 12px; color: #64748b; margin-top: 4px; }
        .meta { width: 100%; margin-bottom: 16px; }
        .meta td { font-size: 11px; padding: 2px 0; }
        .section-title { font-size: 15px; font-weight: bold; color: #164e63; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; margin: 18px 0 10px; }
        .summary { background: #f0f9ff; border: 1px solid #bae6fd; padding: 10px 12px; margin-bottom: 12px; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { background: #0ea5e9; color: #fff; text-align: left; padding: 7px; font-size: 10px; text-transform: uppercase; }
        .data-table td { padding: 7px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        .data-table tr:nth-child(even) { background: #f8fafc; }
        .text-right { text-align: right; }
        .muted { color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">Talisay Beach Resort</h1>
        <div class="subtitle">{{ $sectionTitle }}</div>
    </div>

    <table class="meta">
        <tr>
            <td><strong>Period:</strong> {{ $revenueRange }}</td>
            <td class="text-right"><strong>Generated:</strong> {{ now()->format('F d, Y h:i A') }}</td>
        </tr>
        <tr>
            <td><strong>Prepared by:</strong> {{ auth()->user()->name ?? 'Staff' }}</td>
            <td class="text-right"><strong>Location:</strong> Baybay City, Leyte</td>
        </tr>
    </table>

    @if($section === 'sales')
        <div class="summary">
            Revenue received: <strong>PHP {{ number_format($totalRevenue, 2) }}</strong>
            · {{ $salesPayments->count() }} payment{{ $salesPayments->count() === 1 ? '' : 's' }} listed
        </div>
        <div class="section-title">Confirmed sales</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Guest</th>
                    <th>Unit</th>
                    <th>Method</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salesPayments as $payment)
                    <tr>
                        <td>{{ ($payment->paid_at ?? $payment->created_at)?->format('M j, Y g:i A') }}</td>
                        <td>{{ $payment->booking?->reference_no ?? '—' }}</td>
                        <td>{{ $payment->booking?->user?->name ?? 'Guest' }}</td>
                        <td>{{ $payment->booking?->accommodationUnit?->unit_number ?? '—' }}</td>
                        <td>{{ \Illuminate\Support\Str::headline((string) ($payment->gateway ?: $payment->payment_channel ?: 'other')) }}</td>
                        <td class="text-right">PHP {{ number_format((float) $payment->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No confirmed sales in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    @if($section === 'bookings')
        <div class="summary">
            Reservations: <strong>{{ $reservationRows->count() }}</strong>
            · Cancelled: <strong>{{ $cancelledCount }}</strong> (PHP {{ number_format($cancelledAmount, 2) }})
        </div>
        <div class="section-title">Reservations</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Booked</th>
                    <th>Reference</th>
                    <th>Guest</th>
                    <th>Unit</th>
                    <th>Stay</th>
                    <th class="text-right">Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reservationRows as $booking)
                    <tr>
                        <td>{{ $booking->created_at?->format('M j, Y') }}</td>
                        <td>{{ $booking->reference_no }}</td>
                        <td>{{ $booking->user?->name ?? 'Guest' }}</td>
                        <td>{{ $booking->accommodationUnit?->unit_number ?? ($booking->booking_type === 'special_resort' ? 'Exclusive' : '—') }}</td>
                        <td>
                            {{ optional($booking->check_in_date)->format('M j') }}
                            –
                            {{ optional($booking->check_out_date)->format('M j, Y') }}
                        </td>
                        <td class="text-right">PHP {{ number_format((float) $booking->total_amount, 2) }}</td>
                        <td>{{ \Illuminate\Support\Str::headline($booking->status) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No reservations in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    @if($section === 'payments')
        <div class="summary">
            Pending: <strong>PHP {{ number_format($pendingAmount, 2) }}</strong>
            · Failed (never received): <strong>PHP {{ number_format($failedAmount, 2) }}</strong>
            · Refunded to guests: <strong>PHP {{ number_format($lossAmount, 2) }}</strong>
            · Cancellation rate: <strong>{{ rtrim(rtrim(number_format($stayStats['cancel_rate'], 1), '0'), '.') }}%</strong>
        </div>
        <div class="section-title">Payment details</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Guest</th>
                    <th>Method</th>
                    <th class="text-right">Amount</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($paymentDetails as $payment)
                    @php
                        $refund = $payment->refundSent();
                        $note = $refund['note'] ?? (isset($payment->metadata['refund_amount']) ? 'Refund PHP '.number_format((float) $payment->metadata['refund_amount'], 2) : '—');
                    @endphp
                    <tr>
                        <td>{{ $payment->created_at?->format('M j, Y g:i A') }}</td>
                        <td>{{ $payment->booking?->reference_no ?? '—' }}</td>
                        <td>{{ $payment->booking?->user?->name ?? 'Guest' }}</td>
                        <td>{{ \Illuminate\Support\Str::headline((string) ($payment->gateway ?: $payment->payment_channel ?: 'other')) }}</td>
                        <td class="text-right">PHP {{ number_format((float) $payment->amount, 2) }}</td>
                        <td>{{ \Illuminate\Support\Str::headline($payment->status) }}</td>
                        <td>{{ $note }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No payment records in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif
</body>
</html>
