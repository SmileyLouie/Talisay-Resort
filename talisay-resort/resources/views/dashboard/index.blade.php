@extends('layouts.app')

@section('title', __('Dashboard').' - Talisay Smart Tourism')

@push('head-scripts')
<script src="{{ asset('vendor/chart/chart.umd.min.js') }}"></script>
@endpush

@section('content')
@php
    $revenueHasData = collect($revenueTrends)->sum('total') > 0;
    $occupancyHasData = collect($occupancyData)->contains(fn ($day) => (float) $day['utilization'] > 0);
    $sentimentHasData = $reviewCount > 0;
    $ratingColors = [5 => '#10b981', 4 => '#0ea5e9', 3 => '#f59e0b', 2 => '#f97316', 1 => '#f43f5e'];
@endphp

<div class="res-head">
    <div>
        <h1>{{ __('Dashboard') }}</h1>
        <p>{{ __('Today at Talisay Beach Resort.') }}</p>
    </div>
</div>

<div class="dash-group" data-live-region="dash-today">
    <h2 class="dash-group-title">{{ __('Today') }} <small>{{ ph_date(now(), 'l, M j') }}</small></h2>
    <div class="unit-stats dash-stats">
        <div>
            <span>{{ __('Arrivals') }}</span>
            <strong data-dash="arrivals">{{ $todayStats['arrivals'] }}</strong>
            <em>{{ __(':in checked in · :left to arrive', ['in' => $todayStats['arrived'], 'left' => $todayStats['arrivals'] - $todayStats['arrived']]) }}</em>
        </div>
        <div>
            <span>{{ __('Departures') }}</span>
            <strong data-dash="departures">{{ $todayStats['departures'] }}</strong>
            <em>{{ __(':out checked out · :left to leave', ['out' => $todayStats['departed'], 'left' => $todayStats['departures'] - $todayStats['departed']]) }}</em>
        </div>
        <div>
            <span>{{ __('In-house now') }}</span>
            <strong data-dash="in-house">{{ $todayStats['in_house'] }}</strong>
            <em>{{ trans_choice(':count guest|:count guests', $todayStats['in_house_guests'], ['count' => $todayStats['in_house_guests']]) }} · {{ __('cap :cap a day', ['cap' => $resortCapacity]) }}</em>
        </div>
        <div>
            <span>{{ __('New bookings') }}</span>
            <strong data-dash="new-bookings">{{ $todayStats['new'] }}</strong>
            <em><a href="{{ route('bookings.index', ['status' => 'pending']) }}" class="pay-link">{{ __(':count waiting for approval', ['count' => $pendingCount]) }}</a></em>
        </div>
    </div>
</div>

<div class="dash-group" data-live-region="dash-money">
    <h2 class="dash-group-title">{{ __('Money') }}</h2>
    <div class="unit-stats dash-stats">
        <div>
            <span>{{ __('Revenue this month') }}</span>
            <strong class="{{ $monthlyRevenue > 0 ? 'is-good' : '' }}" data-dash="revenue">₱{{ number_format($monthlyRevenue, 2) }}</strong>
            <em>{{ __('Last month ₱:amount', ['amount' => number_format($lastMonthRevenue, 2)]) }}</em>
        </div>
        <div>
            <span>{{ __('Payments to verify') }}</span>
            <strong class="{{ $toVerifyCount > 0 ? 'is-warn' : '' }}" data-dash="to-verify">{{ $toVerifyCount }}</strong>
            <em><a href="{{ route('payments.index', ['status' => 'pending']) }}" class="pay-link">₱{{ number_format($toVerifyTotal, 2) }} {{ __('waiting') }}</a></em>
        </div>
        <div>
            <span>{{ __('Balance due') }}</span>
            <strong class="{{ $balanceDueTotal > 0 ? 'is-warn' : '' }}" data-dash="balance-due">₱{{ number_format($balanceDueTotal, 2) }}</strong>
            <em>{{ trans_choice(':count stay still owes|:count stays still owe', $balanceDueCount, ['count' => $balanceDueCount]) }}</em>
        </div>
        <div>
            <span>{{ __('Refunds due') }}</span>
            <strong class="{{ $refundDueTotal > 0 ? 'is-warn' : '' }}" data-dash="refund-due">₱{{ number_format($refundDueTotal, 2) }}</strong>
            <em><a href="{{ route('payments.index', ['status' => 'refund_due']) }}" class="pay-link">{{ __(':count to refund', ['count' => $refundDueCount]) }}</a> · {{ __('₱:amount refunded so far', ['amount' => number_format($refundSentTotal, 2)]) }}</em>
        </div>
    </div>
</div>

<div class="dash-tables">
    <section class="rpt-panel">
        <div class="rpt-panel-head">
            <div>
                <h2>{{ __('Bookings') }} & {{ __('Payments') }}</h2>
                <p>{{ __('Latest stays with the payment recorded for each one.') }}</p>
            </div>
            <div class="res-head-actions">
                <a href="{{ route('bookings.index') }}" class="rpt-chip">{{ __('Bookings') }}</a>
                <a href="{{ route('payments.index') }}" class="rpt-chip">{{ __('Payments') }}</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table-clean" id="bookingFeedTable">
                <thead>
                    <tr>
                        <th>{{ __('Ref') }}</th>
                        <th>{{ __('Guest') }}</th>
                        <th>{{ __('Room/Cottage') }}</th>
                        <th>{{ __('In') }}</th>
                        <th>{{ __('Out') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Amount') }}</th>
                        <th>{{ __('Method') }}</th>
                        <th>{{ __('Payment') }}</th>
                        <th>{{ __('Receipt') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentBookings as $booking)
                    @php
                        $hourIn = $hourOut = null;
                        if ($booking->isHourly() && $booking->hour_start) {
                            [$hourIn, $hourOut] = $booking->stayWindow();
                        }
                        $payment = $booking->payment;
                        $payStatusLabels = ['pending' => __('Pending'), 'success' => __('Paid'), 'failed' => __('Failed'), 'refunded' => __('Refunded')];
                        $receiptNo = $payment?->guestReference();
                        $proof = $payment?->proofUrl();
                        $balanceDue = $booking->balanceDue();
                    @endphp
                    <tr class="booking-row {{ $payment && $payment->status === 'pending' ? 'pay-row-pending' : '' }}" data-id="{{ $booking->id }}">
                        <td><span class="res-ref">{{ $booking->reference_no }}</span></td>
                        <td>
                            <span class="res-guest">{{ $booking->guest_name }}</span>
                            @if($booking->guest_contact)
                            <div class="res-note">{{ $booking->guest_contact }}</div>
                            @elseif($booking->user?->email)
                            <div class="res-note">{{ $booking->user->email }}</div>
                            @endif
                        </td>
                        <td>
                            {{ $booking->isSpecialResort() ? __('Full resort') : ($booking->accommodationUnit->unit_number ?? '—') }}
                            @unless($booking->isSpecialResort())
                            <div class="res-note">{{ $booking->accommodationUnit->variant_label ?? '—' }}</div>
                            @endunless
                        </td>
                        <td>
                            {{ ph_date($hourIn ?? $booking->checkInDate()) }}
                            @if($hourIn)
                            <div class="res-note">{{ ph_date($hourIn, 'g:i A') }}</div>
                            @endif
                        </td>
                        <td>
                            {{ ph_date($hourOut ?? $booking->checkOutDate()) }}
                            @if($hourOut)
                            <div class="res-note">{{ ph_date($hourOut, 'g:i A') }}</div>
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
                        <td class="res-money">
                            ₱{{ number_format($booking->total_amount, 2) }}
                            @if($balanceDue > 0 && $booking->amountReceived() > 0)
                            <div class="res-note">₱{{ number_format($booking->amountReceived(), 2) }} {{ __('paid') }} · <span class="res-due">₱{{ number_format($balanceDue, 2) }} {{ __('due') }}</span></div>
                            @endif
                        </td>
                        <td>
                            @if($payment)
                            <span class="pay-method">
                                <i class="bi {{ $paymentMethodIcons[$payment->gateway] ?? 'bi-credit-card' }}"></i>
                                {{ $paymentMethodNames[$payment->gateway] ?? \Illuminate\Support\Str::headline((string) $payment->gateway) }}
                            </span>
                            @else
                            <span class="res-note">—</span>
                            @endif
                        </td>
                        <td>
                            @if($payment && $payment->refundState() === 'due')
                            <span class="res-status pay-status-pending">{{ __('Refund due') }}</span>
                            @elseif($payment && $payment->status === 'success' && $balanceDue > 0)
                            <span class="res-status pay-status-partial">{{ __('Partially paid') }}</span>
                            @elseif($payment && $payment->status === 'pending' && $payment->is_cash_on_arrival && $booking->status !== \App\Models\Booking::STATUS_CANCELLED)
                            <span class="res-status pay-status-pending">{{ __('Pay at arrival') }}</span>
                            @elseif($payment)
                            <span class="res-status pay-status-{{ $payment->status }}">{{ $payStatusLabels[$payment->status] ?? ucfirst((string) $payment->status) }}</span>
                            @else
                            <span class="res-note">—</span>
                            @endif
                        </td>
                        <td>
                            @if($payment && ($receiptNo || $proof))
                                @if($receiptNo)<div class="pay-receipt-no">{{ $receiptNo }}</div>@endif
                                @if($proof)
                                <a href="{{ $proof }}" target="_blank" rel="noopener" class="pay-proof"><i class="bi bi-image"></i> {{ __('View receipt') }}</a>
                                @endif
                                <a href="{{ route('payments.show', $payment) }}" class="res-note">{{ __('Payment details') }}</a>
                            @elseif($payment)
                            <span class="res-note">{{ __('No receipt yet') }}</span>
                            @else
                            <span class="res-note">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="dash-empty">{{ __('No reservations yet.') }}</td>
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
                <p>{{ __('Money received in the last 6 months, by the day it was confirmed.') }}</p>
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
                <p>{{ __('Share of rooms and cottages booked for the next 7 days.') }}</p>
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
                <h2>{{ __('Bookings per room & cottage') }}</h2>
                <p>{{ __('Upcoming and completed stays. Click a slice to open that room or cottage.') }}</p>
            </div>
            <div class="rpt-switch" role="group" aria-label="{{ __('Show rooms or cottages') }}">
                <button type="button" data-unit-type="room" aria-pressed="true">{{ __('Rooms') }}</button>
                <button type="button" data-unit-type="cottage" aria-pressed="false">{{ __('Cottages') }}</button>
            </div>
        </div>
        <script type="application/json" id="unitChartData" data-live-region="unit-chart">{!! json_encode($unitChart, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
        <div class="rpt-chart rpt-chart-ring">
            <canvas id="accommodationsChart" aria-label="{{ __('Bookings per room & cottage') }}"></canvas>
            <div class="rpt-empty" id="unitChartEmpty" hidden>
                <i class="bi bi-pie-chart"></i>
                <p>{{ __('No bookings yet.') }}</p>
            </div>
        </div>
        <ul class="rpt-legend" id="unitChartLegend"></ul>
    </section>

    <section class="rpt-panel">
        <div class="rpt-panel-head">
            <div>
                <h2>{{ __('Guest feedback') }}</h2>
                <p>{{ __('Approved reviews only.') }}</p>
            </div>
        </div>
        <div class="rpt-chart rpt-chart-ring">
            @if($sentimentHasData)
            <canvas id="sentimentChart" aria-label="{{ __('Guest feedback') }}"></canvas>
            @else
            <div class="rpt-empty">
                <i class="bi bi-chat-heart"></i>
                <p>{{ __('No approved reviews yet.') }}</p>
            </div>
            @endif
        </div>
        @if($sentimentHasData)
        <ul class="rpt-legend">
            @foreach($ratingColors as $stars => $color)
            <li><i style="background:{{ $color }}"></i><span>{{ $stars }} {{ $stars === 1 ? __('star') : __('stars') }} ({{ $sentimentData[$stars] ?? 0 }})</span></li>
            @endforeach
        </ul>
        @endif
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
    const unitPalette = ['#0284c7', '#f97316', '#10b981', '#8b5cf6', '#ec4899', '#06b6d4', '#eab308', '#ef4444',
        '#84cc16', '#6366f1', '#f59e0b', '#14b8a6', '#f43f5e', '#a855f7', '#22c55e', '#3b82f6'];
    const unitColor = i => unitPalette[i] || 'hsl(' + ((i * 47) % 360) + ', 65%, 52%)';
    const unitLegend = document.getElementById('unitChartLegend');
    const unitEmpty = document.getElementById('unitChartEmpty');
    const unitSwitch = document.querySelectorAll('.rpt-switch [data-unit-type]');
    const emptyText = { room: @json(__('No room bookings yet.')), cottage: @json(__('No cottage bookings yet.')) };
    let unitType = localStorage.getItem('dashboardUnitType') === 'cottage' ? 'cottage' : 'room';
    let units = [];
    let slices = [];

    const unitChart = new Chart(accommodationsCanvas.getContext('2d'), {
        type: 'pie',
        data: { labels: [], datasets: [{ data: [], backgroundColor: [], borderColor: chartEdge }] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: items => {
                            const unit = slices[items[0].dataIndex];
                            return unit ? unit.label + ' · ' + unit.variant : '';
                        },
                        label: context => {
                            const unit = slices[context.dataIndex];
                            return [
                                @json(__('Upcoming')) + ': ' + unit.upcoming,
                                @json(__('Stayed')) + ': ' + unit.stayed,
                                @json(__('Cancelled')) + ': ' + unit.cancelled
                            ];
                        },
                        footer: () => @json(__('Click to open'))
                    }
                }
            },
            onHover: (event, elements) => {
                event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
            },
            onClick: (event, elements) => {
                const unit = elements.length ? slices[elements[0].index] : null;
                if (unit && unit.url) window.location.href = unit.url;
            }
        }
    });

    const renderUnits = () => {
        unitSwitch.forEach(btn => btn.setAttribute('aria-pressed', String(btn.dataset.unitType === unitType)));
        unitEmpty.querySelector('p').textContent = emptyText[unitType];
        units = JSON.parse(document.getElementById('unitChartData')?.textContent || '[]')
            .filter(u => u.type === unitType)
            .map((u, i) => ({ ...u, total: u.upcoming + u.stayed, color: unitColor(i) }));
        slices = units.filter(u => u.total > 0);

        const dataset = unitChart.data.datasets[0];
        unitChart.data.labels = slices.map(u => u.label);
        dataset.data = slices.map(u => u.total);
        dataset.backgroundColor = slices.map(u => u.color);
        dataset.borderWidth = slices.length > 1 ? 2 : 0;
        unitChart.update();

        accommodationsCanvas.hidden = slices.length === 0;
        unitEmpty.hidden = slices.length > 0;

        unitLegend.replaceChildren(...units.map(u => {
            const li = document.createElement('li');
            const link = document.createElement('a');
            const swatch = document.createElement('i');
            const text = document.createElement('span');
            link.href = u.url;
            link.title = u.label + ' · ' + u.variant;
            swatch.style.background = u.color;
            text.textContent = u.label + ' (' + u.total + ')';
            link.append(swatch, text);
            li.append(link);
            return li;
        }));
    };

    unitSwitch.forEach(btn => btn.addEventListener('click', () => {
        unitType = btn.dataset.unitType;
        localStorage.setItem('dashboardUnitType', unitType);
        renderUnits();
    }));

    renderUnits();
    document.addEventListener('live-region-updated', (event) => {
        if (event.detail?.name === 'unit-chart') renderUnits();
    });
}

const sentimentCanvas = document.getElementById('sentimentChart');
if (sentimentCanvas) {
    const sentimentData = {{ Js::from($sentimentData) }};
    const ratingColors = {{ Js::from($ratingColors) }};
    const sentimentSlices = [5, 4, 3, 2, 1].map(function (stars) {
        return {
            label: stars + (stars === 1 ? ' star' : ' stars'),
            value: Number(sentimentData[stars] || 0),
            color: ratingColors[stars]
        };
    }).filter(function (slice) { return slice.value > 0; });
    new Chart(sentimentCanvas.getContext('2d'), {
        type: 'pie',
        data: {
            labels: sentimentSlices.map(function (slice) { return slice.label; }),
            datasets: [{
                data: sentimentSlices.map(function (slice) { return slice.value; }),
                backgroundColor: sentimentSlices.map(function (slice) { return slice.color; }),
                borderWidth: sentimentSlices.length > 1 ? 2 : 0,
                borderColor: chartEdge
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
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
    row.appendChild(feedCell('res-note', '—'));
    row.appendChild(feedCell('res-note', '—'));
    row.appendChild(feedCell('res-note', '—'));
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
