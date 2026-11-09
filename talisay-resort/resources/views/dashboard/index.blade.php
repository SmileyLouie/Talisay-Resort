@extends('layouts.app')

@section('title', __('Dashboard').' - Talisay Smart Tourism')

@push('head-scripts')
<script src="{{ asset('vendor/chart/chart.umd.min.js') }}"></script>
@endpush

@section('content')
@php
    $usePercent = rtrim(rtrim(number_format((float) $capacityPercent, 1), '0'), '.');
    $revenueHasData = collect($revenueTrends)->sum('total') > 0;
    $occupancyHasData = collect($occupancyData)->contains(fn ($day) => (float) $day['utilization'] > 0);
    $popularHasData = $popularAccommodations->isNotEmpty();
    $sentimentHasData = $reviewCount > 0;
@endphp

<div class="res-head">
    <div>
        <h1>{{ __('Dashboard') }}</h1>
        <p>{{ __('Today at Talisay Beach Resort.') }}</p>
    </div>
</div>

<div class="unit-stats dash-stats">
    <div>
        <span>{{ __('Arrivals today') }}</span>
        <strong data-dash="arrivals">{{ $arrivalsToday }}</strong>
        <em>{{ __(':count pending', ['count' => $pendingCount]) }}</em>
    </div>
    <div>
        <span>{{ __('Resort Capacity') }}</span>
        <strong data-dash="capacity">{{ $guestsToday }} / {{ $resortCapacity }}</strong>
        <em>{{ $usePercent }}% · {{ __('Daily visitor cap') }}</em>
    </div>
    <div>
        <span>{{ __('This month') }}</span>
        <strong class="{{ $monthlyRevenue > 0 ? 'is-good' : '' }}" data-dash="revenue">₱{{ number_format($monthlyRevenue, 2) }}</strong>
        <em>{{ __('Confirmed payments') }}</em>
    </div>
    <div>
        <span>{{ __('Pending payments') }}</span>
        <strong class="{{ $pendingPayments > 0 ? 'is-warn' : '' }}" data-dash="pending-payments">₱{{ number_format($pendingPayments, 2) }}</strong>
        <em>{{ __(':count to verify', ['count' => $pendingPaymentCount]) }}</em>
    </div>
    <div>
        <span>{{ __('Need to refund') }}</span>
        <strong class="{{ $refundDueTotal > 0 ? 'is-warn' : '' }}" data-dash="refund-due">₱{{ number_format($refundDueTotal, 2) }}</strong>
        <em><a href="{{ route('payments.index', ['status' => 'refund_due']) }}" class="pay-link">{{ __(':count to refund', ['count' => $refundDueCount]) }}</a></em>
    </div>
    <div>
        <span>{{ __('Refunded') }}</span>
        <strong data-dash="refunded">₱{{ number_format($refundSentTotal, 2) }}</strong>
        <em>{{ __(':count refunded', ['count' => $refundSentCount]) }}</em>
    </div>
</div>

<div class="dash-tables">
    <section class="rpt-panel">
        <div class="rpt-panel-head">
            <div>
                <h2>{{ __('Booking feed') }}</h2>
                <p>{{ __('Latest reservations, including hourly, nightly, and exclusive stays.') }}</p>
            </div>
            <a href="{{ route('bookings.index') }}" class="rpt-chip">{{ __('View all') }}</a>
        </div>
        <div class="table-responsive">
            <table class="table-clean" id="bookingFeedTable">
                <thead>
                    <tr>
                        <th>{{ __('Ref') }}</th>
                        <th>{{ __('Guest') }}</th>
                        <th>{{ __('Room/Cottage') }}</th>
                        <th>{{ __('Variant') }}</th>
                        <th>{{ __('In') }}</th>
                        <th>{{ __('Out') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentBookings as $booking)
                    @php
                        $hourIn = $hourOut = null;
                        if ($booking->isHourly() && $booking->hour_start) {
                            [$hourIn, $hourOut] = $booking->stayWindow();
                        }
                    @endphp
                    <tr class="booking-row" data-id="{{ $booking->id }}">
                        <td><span class="res-ref">{{ $booking->reference_no }}</span></td>
                        <td>
                            <span class="res-guest">{{ $booking->guest_name }}</span>
                            @if($booking->guest_contact)
                            <div class="res-note">{{ $booking->guest_contact }}</div>
                            @endif
                        </td>
                        <td>{{ $booking->isSpecialResort() ? __('Full resort') : ($booking->accommodationUnit->unit_number ?? '—') }}</td>
                        <td>{{ $booking->isSpecialResort() ? '—' : ($booking->accommodationUnit->variant_label ?? '—') }}</td>
                        <td>
                            {{ ($hourIn ?? $booking->checkInDate())->format('Y-m-d') }}
                            @if($hourIn)
                            <div class="res-note">{{ $hourIn->format('g:i A') }}</div>
                            @endif
                        </td>
                        <td>
                            {{ ($hourOut ?? $booking->checkOutDate())->format('Y-m-d') }}
                            @if($hourOut)
                            <div class="res-note">{{ $hourOut->format('g:i A') }}</div>
                            @endif
                        </td>
                        <td><span class="res-status res-status-{{ $booking->listStage() }}">{{ __($booking->listStageLabel()) }}</span></td>
                        <td>
                            @if($booking->isSpecialResort())
                            <span class="res-exclusive">{{ __('Exclusive') }}</span>
                            @else
                            {{ $booking->isHourly() ? __('Hourly') : __('Nightly') }}
                            @endif
                        </td>
                        <td class="res-money">₱{{ number_format($booking->total_amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="dash-empty">{{ __('No reservations yet.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="rpt-panel">
        <div class="rpt-panel-head">
            <div>
                <h2>{{ __('Payments') }}</h2>
                <p>{{ __('Latest receipts, methods, and whether each payment is confirmed.') }}</p>
            </div>
            <a href="{{ route('payments.index') }}" class="rpt-chip">{{ __('View all') }}</a>
        </div>
        <div class="table-responsive">
            <table class="table-clean pay-table">
                <thead>
                    <tr>
                        <th>{{ __('Booking') }}</th>
                        <th>{{ __('Guest') }}</th>
                        <th>{{ __('Amount') }}</th>
                        <th>{{ __('Method') }}</th>
                        <th>{{ __('Receipt') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Logged') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPayments as $payment)
                    @php
                        $payBooking = $payment->booking;
                        $receiptNo = $payment->guestReference();
                        $proof = $payment->proofUrl();
                        $payStatusLabels = ['pending' => __('Pending'), 'success' => __('Paid'), 'failed' => __('Failed'), 'refunded' => __('Refunded')];
                    @endphp
                    <tr class="{{ $payment->status === 'pending' ? 'pay-row-pending' : '' }}">
                        <td>
                            <span class="res-ref">{{ $payBooking->reference_no ?? '—' }}</span>
                            @if($payBooking)
                            <div class="res-note">{{ $payBooking->isSpecialResort() ? __('Full resort') : ($payBooking->accommodationUnit->unit_number ?? '—') }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="res-guest">{{ $payBooking->guest_name ?? $payBooking?->user?->name ?? __('Guest') }}</span>
                            @if($payBooking?->user?->email)
                            <div class="res-note">{{ $payBooking->user->email }}</div>
                            @endif
                        </td>
                        <td class="res-money"><strong>₱{{ number_format($payment->amount, 2) }}</strong></td>
                        <td>
                            <span class="pay-method">
                                <i class="bi {{ $paymentMethodIcons[$payment->gateway] ?? 'bi-credit-card' }}"></i>
                                {{ $paymentMethodNames[$payment->gateway] ?? \Illuminate\Support\Str::headline((string) $payment->gateway) }}
                            </span>
                        </td>
                        <td>
                            @if($receiptNo || $proof)
                                @if($receiptNo)<div class="pay-receipt-no">{{ $receiptNo }}</div>@endif
                                @if($proof)
                                <a href="{{ $proof }}" target="_blank" rel="noopener" class="pay-proof"><i class="bi bi-image"></i> {{ __('View receipt') }}</a>
                                @endif
                            @else
                            <span class="res-note">{{ __('No receipt yet') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($payment->refundState() === 'due')
                            <span class="res-status pay-status-pending">{{ __('Refund due') }}</span>
                            @else
                            <span class="res-status pay-status-{{ $payment->status }}">{{ $payStatusLabels[$payment->status] ?? ucfirst((string) $payment->status) }}</span>
                            @endif
                        </td>
                        <td>
                            {{ $payment->created_at?->format('M j, Y') }}
                            <div class="res-note">{{ $payment->created_at?->format('g:i A') }}</div>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('payments.show', $payment) }}" class="res-icon-btn" title="{{ __('Payment details') }}" aria-label="{{ __('Payment details') }}">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="dash-empty">{{ __('No payments yet.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="rpt-grid">
    <section class="rpt-panel">
        <div class="rpt-panel-head">
            <div>
                <h2>{{ __('Revenue') }}</h2>
                <p>{{ __('Confirmed payments for the last 6 months.') }}</p>
            </div>
            <span class="rpt-chip">PHP</span>
        </div>
        <div class="rpt-chart">
            @if($revenueHasData)
            <canvas id="revenueChart" aria-label="{{ __('Revenue') }}"></canvas>
            @else
            <div class="rpt-empty">
                <i class="bi bi-graph-up-arrow"></i>
                <p>{{ __('No confirmed payments in the last 6 months.') }}</p>
            </div>
            @endif
        </div>
    </section>

    <section class="rpt-panel">
        <div class="rpt-panel-head">
            <div>
                <h2>{{ __('Occupancy') }}</h2>
                <p>{{ __('Share of rooms and cottages in use over the last 7 days.') }}</p>
            </div>
            <span class="rpt-chip">%</span>
        </div>
        <div class="rpt-chart">
            @if($occupancyHasData)
            <canvas id="occupancyChart" aria-label="{{ __('Occupancy') }}"></canvas>
            @else
            <div class="rpt-empty">
                <i class="bi bi-bar-chart-line"></i>
                <p>{{ __('No rooms or cottages are in use this week.') }}</p>
            </div>
            @endif
        </div>
    </section>

    <section class="rpt-panel">
        <div class="rpt-panel-head">
            <div>
                <h2>{{ __('Most booked') }}</h2>
                <p>{{ __('Paid, checked-in, checked-out, and completed stays.') }}</p>
            </div>
        </div>
        <div class="rpt-chart">
            @if($popularHasData)
            <canvas id="accommodationsChart" aria-label="{{ __('Most booked') }}"></canvas>
            @else
            <div class="rpt-empty">
                <i class="bi bi-pie-chart"></i>
                <p>{{ __('No paid stays yet.') }}</p>
            </div>
            @endif
        </div>
    </section>

    <section class="rpt-panel">
        <div class="rpt-panel-head">
            <div>
                <h2>{{ __('Guest feedback') }}</h2>
                <p>{{ __('Approved reviews only.') }}</p>
            </div>
        </div>
        <div class="rpt-chart">
            @if($sentimentHasData)
            <canvas id="sentimentChart" aria-label="{{ __('Guest feedback') }}"></canvas>
            @else
            <div class="rpt-empty">
                <i class="bi bi-chat-heart"></i>
                <p>{{ __('No approved reviews yet.') }}</p>
            </div>
            @endif
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
const chartDark = document.documentElement.getAttribute('data-theme') === 'dark';
const chartGrid = chartDark ? '#2c3440' : '#f1f5f9';
const chartTick = chartDark ? '#a3adba' : '#64748b';
const chartEdge = chartDark ? '#1c232d' : '#ffffff';
const chartLegend = chartDark ? '#e7edf5' : '#334155';

const occupancyCanvas = document.getElementById('occupancyChart');
if (occupancyCanvas) {
    new Chart(occupancyCanvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: {{ Js::from($occupancyData->pluck('date')) }},
            datasets: [{
                label: @json(__('Occupancy')),
                data: {{ Js::from($occupancyData->pluck('utilization')) }},
                backgroundColor: 'rgba(14, 165, 233, 0.75)',
                borderColor: '#0284c7',
                borderWidth: 1.5,
                borderRadius: 8,
                hoverBackgroundColor: '#0ea5e9'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { color: chartTick, callback: v => v + '%' },
                    grid: { color: chartGrid }
                },
                x: { ticks: { color: chartTick }, grid: { display: false } }
            }
        }
    });
}

const revenueCanvas = document.getElementById('revenueChart');
if (revenueCanvas) {
    const revenueCtx = revenueCanvas.getContext('2d');
    const revGradient = revenueCtx.createLinearGradient(0, 0, 0, 200);
    revGradient.addColorStop(0, 'rgba(14, 165, 233, 0.35)');
    revGradient.addColorStop(1, 'rgba(14, 165, 233, 0.0)');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: {{ Js::from(collect($revenueTrends)->pluck('month')) }},
            datasets: [{
                label: @json(__('Revenue')),
                data: {{ Js::from(collect($revenueTrends)->pluck('total')) }},
                backgroundColor: revGradient,
                borderColor: '#0284c7',
                borderWidth: 2.5,
                pointBackgroundColor: '#0ea5e9',
                pointBorderColor: chartEdge,
                pointBorderWidth: 2,
                pointRadius: 4,
                tension: 0.35,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return '₱' + Number(context.parsed.y).toLocaleString('en-US', { minimumFractionDigits: 2 });
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: chartGrid },
                    ticks: { color: chartTick, callback: v => '₱' + Number(v).toLocaleString() }
                },
                x: { ticks: { color: chartTick }, grid: { display: false } }
            }
        }
    });
}

const accommodationsCanvas = document.getElementById('accommodationsChart');
if (accommodationsCanvas) {
    new Chart(accommodationsCanvas.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: {{ Js::from($popularAccommodations->map(fn ($unit) => $unit->unit_number.' · '.\App\Models\UnitVariant::labelFor($unit->variant))) }},
            datasets: [{
                data: {{ Js::from($popularAccommodations->pluck('bookings_count')) }},
                backgroundColor: ['#0284c7', '#0ea5e9', '#14b8a6', '#f59e0b', '#8b5cf6', '#f43f5e'],
                borderWidth: 2,
                borderColor: chartEdge
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { color: chartLegend, boxWidth: 12, font: { size: 11 } } }
            }
        }
    });
}

const sentimentCanvas = document.getElementById('sentimentChart');
if (sentimentCanvas) {
    const sentimentData = {{ Js::from($sentimentData) }};
    new Chart(sentimentCanvas.getContext('2d'), {
        type: 'pie',
        data: {
            labels: [
                @json(__('Positive (4–5 stars)')),
                @json(__('Neutral (3 stars)')),
                @json(__('Negative (1–2 stars)'))
            ],
            datasets: [{
                data: [sentimentData.positive, sentimentData.neutral, sentimentData.negative],
                backgroundColor: ['#10b981', '#f59e0b', '#f43f5e'],
                borderWidth: 2,
                borderColor: chartEdge
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { color: chartLegend, boxWidth: 12, font: { size: 11 } } }
            }
        }
    });
}

const feedBadgeStyles = {
    pending: 'res-status res-status-pending',
    paid: 'res-status res-status-paid',
    checked_in: 'res-status res-status-check_in',
    checked_out: 'res-status res-status-check_out',
    completed: 'res-status res-status-completed',
    cancelled: 'res-status res-status-cancelled'
};

function feedCell(className, text) {
    const td = document.createElement('td');
    const span = document.createElement('span');
    span.className = className;
    span.textContent = text == null ? '' : String(text);
    td.appendChild(span);
    return td;
}

function feedStatusLabel(status) {
    const s = String(status || '').replace(/_/g, ' ');
    return s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
}

function buildBookingFeedRow(data) {
    const row = document.createElement('tr');
    row.className = 'booking-row';
    row.dataset.id = data.id;
    row.appendChild(feedCell('res-ref', data.reference_no));
    row.appendChild(feedCell('res-guest', data.guest_name || ''));
    row.appendChild(feedCell('', data.unit_label || '—'));
    row.appendChild(feedCell('', '—'));
    row.appendChild(feedCell('', data.booking_date || ''));
    row.appendChild(feedCell('', ''));
    row.appendChild(feedCell(feedBadgeStyles[data.status] || 'res-status', feedStatusLabel(data.status)));
    row.appendChild(feedCell('', ''));
    row.appendChild(feedCell('res-money', ''));
    return row;
}

window.addEventListener('booking-created', function (e) {
    const data = e.detail || {};
    const tbody = document.querySelector('#bookingFeedTable tbody');
    if (!tbody || !data.id) return;
    if (tbody.querySelector('.booking-row[data-id="' + String(data.id).replace(/"/g, '') + '"]')) return;
    const emptyRow = tbody.querySelector('tr:not(.booking-row)');
    if (emptyRow && !tbody.querySelector('.booking-row')) emptyRow.remove();
    tbody.insertBefore(buildBookingFeedRow(data), tbody.firstChild);
});

window.addEventListener('booking-updated', function (e) {
    const data = e.detail || {};
    if (!data.id) return;
    const existing = document.querySelector('#bookingFeedTable tbody .booking-row[data-id="' + String(data.id).replace(/"/g, '') + '"]');
    if (!existing) return;
    existing.replaceWith(buildBookingFeedRow(data));
});
</script>
@endpush
