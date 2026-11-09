@extends('layouts.app')

@section('title', 'Reports & Analytics — Talisay Smart Tourism')

@push('head-scripts')
<script src="{{ asset('vendor/chart/chart.umd.min.js') }}"></script>
@endpush

@section('content')

@php
    $periodLabels = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'week' => 'This week',
        'last_week' => 'Last week',
        'month' => 'This month',
        'last_month' => 'Last month',
        'year' => 'This year',
        'custom' => 'Custom range',
        'all' => 'All time',
    ];
    $dateTypeLabels = [
        'payment' => 'Payment date',
        'booking' => 'Booking date',
        'arrival' => 'Arrival (check-in)',
    ];
    $payStatusLabels = ['pending' => 'Pending', 'success' => 'Paid', 'failed' => 'Failed', 'refunded' => 'Refunded'];
    $methodLabel = fn ($code) => $code ? \Illuminate\Support\Str::headline((string) $code) : '—';
    $unitInfo = function ($booking) {
        if (!$booking) {
            return ['type' => '—', 'name' => '—'];
        }
        if (($booking->booking_type ?? null) === 'special_resort') {
            return ['type' => 'Exclusive', 'name' => 'Full resort'];
        }
        $unit = $booking->accommodationUnit ?? null;
        if (!$unit) {
            return ['type' => '—', 'name' => '—'];
        }
        $type = match ($unit->unit_type) {
            'cottage' => 'Cottage',
            'room' => 'Room',
            default => 'Unit',
        };

        return ['type' => $type, 'name' => $unit->unit_number];
    };
    $activeTab = in_array(request('tab'), ['overview', 'sales', 'bookings', 'payments'], true)
        ? request('tab')
        : 'overview';
    $exportQuery = array_filter([
        'period' => $period,
        'date_type' => $dateType,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
    ]);
@endphp

<div class="res-head no-print">
    <div>
        <h1>{{ __('Reports & Analytics') }}</h1>
        <p>{{ $revenueRange }} · {{ strtolower(__($dateTypeLabel)) }}. {{ __('Open a tab to export that report as PDF or CSV.') }}</p>
    </div>
    <div class="res-head-actions" id="rptExportBar">
        <a href="{{ route('reports.export-pdf', $exportQuery + ['section' => 'full']) }}" class="btn-secondary-clean" id="rptPdfLink" data-base="{{ route('reports.export-pdf') }}">
            <i class="bi bi-file-earmark-pdf"></i>
            <span>PDF</span>
        </a>
        <a href="{{ route('reports.export-excel', $exportQuery + ['section' => 'full']) }}" class="btn-ocean" id="rptCsvLink" data-base="{{ route('reports.export-excel') }}">
            <i class="bi bi-filetype-csv"></i>
            <span>CSV</span>
        </a>
    </div>
</div>

<form method="GET" action="{{ route('reports.index') }}" class="rpt-datebar no-print" id="rptDateForm">
    <input type="hidden" name="tab" value="{{ $activeTab }}">

    <div class="rpt-datebar-group">
        <label for="rptPeriod">{{ __('Period') }}</label>
        <select name="period" id="rptPeriod" class="form-control-clean rpt-date-select" onchange="onReportPeriodChange(this)">
            @foreach($periodLabels as $value => $label)
                <option value="{{ $value }}" @selected($period === $value)>{{ __($label) }}</option>
            @endforeach
        </select>
    </div>

    <div class="rpt-datebar-group">
        <label for="rptDateType">{{ __('Date type') }}</label>
        <select name="date_type" id="rptDateType" class="form-control-clean rpt-date-select">
            @foreach($dateTypeLabels as $value => $label)
                <option value="{{ $value }}" @selected($dateType === $value)>{{ __($label) }}</option>
            @endforeach
        </select>
    </div>

    <div class="rpt-datebar-group rpt-date-range {{ $period === 'custom' ? 'is-open' : '' }}" id="rptCustomDates">
        <label for="rptDateFrom">{{ __('From') }}</label>
        <input type="date" name="date_from" id="rptDateFrom" class="form-control-clean rpt-date-input" value="{{ $dateFrom ?? now()->startOfMonth()->toDateString() }}">
    </div>
    <div class="rpt-datebar-group rpt-date-range {{ $period === 'custom' ? 'is-open' : '' }}">
        <label for="rptDateTo">{{ __('To') }}</label>
        <input type="date" name="date_to" id="rptDateTo" class="form-control-clean rpt-date-input" value="{{ $dateTo ?? now()->toDateString() }}">
    </div>

    <button type="submit" class="btn-ocean rpt-date-apply">
        <i class="bi bi-funnel"></i>
        <span>{{ __('Apply') }}</span>
    </button>
    <span class="unit-count rpt-date-hint">{{ $dateTypeLabel }}</span>
</form>

<div class="unit-stats rpt-stats no-print">
    <div>
        <span>{{ __('Confirmed revenue') }}</span>
        <strong class="{{ $totalRevenue > 0 ? 'is-good' : '' }}">₱{{ number_format($totalRevenue, 2) }}</strong>
        <em>{{ $revenueChoice }}</em>
    </div>
    <div>
        <span>{{ __('Pending inflows') }}</span>
        <strong class="{{ $pendingAmount > 0 ? 'is-warn' : '' }}">₱{{ number_format($pendingAmount, 2) }}</strong>
        <em>{{ __('Awaiting confirmation') }}</em>
    </div>
    <div>
        <span>{{ __('Refunded / losses') }}</span>
        <strong class="{{ $lossAmount > 0 ? 'is-bad' : '' }}">₱{{ number_format($lossAmount, 2) }}</strong>
        <em>{{ __('Failed + refunded') }}</em>
    </div>
    <div>
        <span>{{ __('Reservations') }}</span>
        <strong>{{ number_format($totalBookings) }}</strong>
        <em>{{ $cancelledCount }} {{ __('cancelled') }}</em>
    </div>
    <div>
        <span>{{ __('Guest satisfaction') }}</span>
        <strong class="rpt-rating">{{ number_format((float) $avgRating, 1) }} <i class="bi bi-star-fill"></i></strong>
        <em>{{ __('Average out of 5') }}</em>
    </div>
</div>

<div class="staff-tabs rpt-tabs no-print" role="tablist">
    <button type="button" class="staff-tab {{ $activeTab === 'overview' ? 'active' : '' }}" data-rpt-tab="overview" onclick="switchReportTab('overview')">
        <i class="bi bi-graph-up"></i>
        <span>{{ __('Overview') }}</span>
    </button>
    <button type="button" class="staff-tab {{ $activeTab === 'sales' ? 'active' : '' }}" data-rpt-tab="sales" onclick="switchReportTab('sales')">
        <i class="bi bi-cash-stack"></i>
        <span>{{ __('Sales') }}</span>
        <span class="unit-filter-count">{{ $salesPayments->count() }}</span>
    </button>
    <button type="button" class="staff-tab {{ $activeTab === 'bookings' ? 'active' : '' }}" data-rpt-tab="bookings" onclick="switchReportTab('bookings')">
        <i class="bi bi-calendar2-check"></i>
        <span>{{ __('Reservations') }}</span>
        <span class="unit-filter-count">{{ $reservationRows->count() }}</span>
    </button>
    <button type="button" class="staff-tab {{ $activeTab === 'payments' ? 'active' : '' }}" data-rpt-tab="payments" onclick="switchReportTab('payments')">
        <i class="bi bi-wallet2"></i>
        <span>{{ __('Payments & losses') }}</span>
        <span class="unit-filter-count">{{ $paymentDetails->count() }}</span>
    </button>
</div>

{{-- Overview charts --}}
<div class="rpt-tab-panel {{ $activeTab === 'overview' ? 'is-active' : '' }}" data-rpt-panel="overview" id="rpt-overview">
    <div class="rpt-grid">
        <section class="rpt-panel">
            <div class="rpt-panel-head">
                <div>
                    <h2>{{ $revenueChartTitle }}</h2>
                    <p>{{ $revenueRange }}</p>
                </div>
                <span class="rpt-chip">PHP</span>
            </div>
            <div class="rpt-chart">
                @if(count($monthlyRevenue) === 0)
                    <div class="rpt-empty">
                        <i class="bi bi-bar-chart-line"></i>
                        <p>{{ __('No confirmed payments in this period yet.') }}</p>
                    </div>
                @else
                    <canvas id="revenueChart" aria-label="Revenue chart"></canvas>
                @endif
            </div>
        </section>

        <section class="rpt-panel">
            <div class="rpt-panel-head">
                <div>
                    <h2>{{ __('Rating breakdown') }}</h2>
                    <p>{{ __('How many reviews for each star score') }}</p>
                </div>
            </div>
            <div class="rpt-chart">
                @if(count($ratingDist) === 0)
                    <div class="rpt-empty">
                        <i class="bi bi-star"></i>
                        <p>{{ __('No reviews submitted yet.') }}</p>
                    </div>
                @else
                    <canvas id="satisfactionChart" aria-label="Rating breakdown chart"></canvas>
                @endif
            </div>
        </section>

        <section class="rpt-panel">
            <div class="rpt-panel-head">
                <div>
                    <h2>{{ __('Popular rooms') }}</h2>
                    <p>{{ __('Top rooms by paid and completed stays') }}</p>
                </div>
                @if($popularRooms->isNotEmpty())
                    <span class="rpt-chip">{{ $popularRooms->count() }} shown</span>
                @endif
            </div>
            <div class="rpt-chart rpt-chart-bars">
                @if($popularRooms->isEmpty())
                    <div class="rpt-empty">
                        <i class="bi bi-door-closed"></i>
                        <p>{{ __('No paid room stays yet.') }}</p>
                    </div>
                @else
                    <canvas id="roomsChart" aria-label="Popular rooms chart"></canvas>
                @endif
            </div>
        </section>

        <section class="rpt-panel">
            <div class="rpt-panel-head">
                <div>
                    <h2>{{ __('Popular cottages') }}</h2>
                    <p>{{ __('Top cottages by paid and completed stays') }}</p>
                </div>
                @if($popularCottages->isNotEmpty())
                    <span class="rpt-chip">{{ $popularCottages->count() }} shown</span>
                @endif
            </div>
            <div class="rpt-chart rpt-chart-bars">
                @if($popularCottages->isEmpty())
                    <div class="rpt-empty">
                        <i class="bi bi-house-door"></i>
                        <p>{{ __('No paid cottage stays yet.') }}</p>
                    </div>
                @else
                    <canvas id="cottagesChart" aria-label="Popular cottages chart"></canvas>
                @endif
            </div>
        </section>
    </div>
</div>

{{-- Sales --}}
<section class="rpt-tab-panel rpt-section {{ $activeTab === 'sales' ? 'is-active' : '' }}" data-rpt-panel="sales" data-section="sales" id="rpt-sales">
    <div class="rpt-section-head">
        <div>
            <h2>{{ __('Sales report') }}</h2>
            <p>{{ __('Confirmed payments') }} · {{ $revenueRange }}</p>
        </div>
    </div>
    <div class="rpt-mini-stats">
        <div><span>{{ __('Confirmed sales') }}</span><strong class="is-good">₱{{ number_format($totalRevenue, 2) }}</strong></div>
        <div><span>Paid transactions</span><strong>{{ $salesPayments->count() }}</strong></div>
        <div><span>{{ __('Still pending') }}</span><strong class="{{ $pendingAmount > 0 ? 'is-warn' : '' }}">₱{{ number_format($pendingAmount, 2) }}</strong></div>
    </div>
    <div class="table-responsive">
        <table class="table-clean">
            <thead>
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Reference') }}</th>
                    <th>{{ __('Guest') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Room') }} / {{ __('Cottage') }}</th>
                    <th>{{ __('Method') }}</th>
                    <th>{{ __('Amount') }}</th>
                    <th>{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salesPayments as $payment)
                    @php $info = $unitInfo($payment->booking); @endphp
                    <tr>
                        <td>{{ $payment->created_at?->format('M j, Y g:i A') }}</td>
                        <td>{{ $payment->booking?->reference_no ?? '—' }}</td>
                        <td>
                            <strong>{{ $payment->booking?->user?->name ?? 'Guest' }}</strong>
                            @if($payment->booking?->user?->email)
                                <div class="rpt-sub">{{ $payment->booking->user->email }}</div>
                            @endif
                        </td>
                        <td>{{ $info['type'] }}</td>
                        <td>{{ $info['name'] }}</td>
                        <td>{{ $methodLabel($payment->gateway ?: $payment->payment_channel) }}</td>
                        <td><strong>₱{{ number_format((float) $payment->amount, 2) }}</strong></td>
                        <td><span class="res-status pay-status-success">Paid</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="rpt-table-empty">
                                <i class="bi bi-cash-stack"></i>
                                <p>No confirmed sales in this period.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

{{-- Reservations --}}
<section class="rpt-tab-panel rpt-section {{ $activeTab === 'bookings' ? 'is-active' : '' }}" data-rpt-panel="bookings" data-section="bookings" id="rpt-bookings">
    <div class="rpt-section-head">
        <div>
            <h2>{{ __('Reservations report') }}</h2>
            <p>Booking records · {{ $revenueRange }}</p>
        </div>
    </div>
    <div class="rpt-mini-stats">
        <div><span>Reservations</span><strong>{{ $totalBookings }}</strong></div>
        <div><span>Cancelled</span><strong class="{{ $cancelledCount ? 'is-bad' : '' }}">{{ $cancelledCount }}</strong></div>
        <div><span>Cancelled value</span><strong class="{{ $cancelledAmount > 0 ? 'is-bad' : '' }}">₱{{ number_format($cancelledAmount, 2) }}</strong></div>
        <div><span>Pending</span><strong>{{ (int) ($bookingStatusCounts['pending'] ?? 0) }}</strong></div>
        <div><span>Paid / active</span><strong>{{ (int) (($bookingStatusCounts['paid'] ?? 0) + ($bookingStatusCounts['checked_in'] ?? 0)) }}</strong></div>
    </div>
    <div class="table-responsive">
        <table class="table-clean">
            <thead>
                <tr>
                    <th>{{ __('Booked') }}</th>
                    <th>{{ __('Reference') }}</th>
                    <th>{{ __('Guest') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Room') }} / {{ __('Cottage') }}</th>
                    <th>{{ __('Stay') }}</th>
                    <th>{{ __('Guests') }}</th>
                    <th>{{ __('Total') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Payment') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reservationRows as $booking)
                    @php $info = $unitInfo($booking); @endphp
                    <tr>
                        <td>{{ $booking->created_at?->format('M j, Y') }}</td>
                        <td>{{ $booking->reference_no }}</td>
                        <td>
                            <strong>{{ $booking->user?->name ?? 'Guest' }}</strong>
                            @if($booking->user?->email)
                                <div class="rpt-sub">{{ $booking->user->email }}</div>
                            @endif
                        </td>
                        <td>{{ $info['type'] }}</td>
                        <td>{{ $info['name'] }}</td>
                        <td>
                            {{ optional($booking->check_in_date)->format('M j') }}
                            –
                            {{ optional($booking->check_out_date)->format('M j, Y') }}
                        </td>
                        <td>{{ $booking->guests_count }}</td>
                        <td><strong>₱{{ number_format((float) $booking->total_amount, 2) }}</strong></td>
                        <td><span class="res-status res-status-{{ $booking->listStage() }}">{{ $booking->listStageLabel() }}</span></td>
                        <td>
                            @if($booking->payment)
                                <span class="res-status pay-status-{{ $booking->payment->status }}">{{ $payStatusLabels[$booking->payment->status] ?? ucfirst($booking->payment->status) }}</span>
                            @else
                                <span class="text-slate-400">No payment</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">
                            <div class="rpt-table-empty">
                                <i class="bi bi-calendar2-x"></i>
                                <p>No reservations in this period.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

{{-- Payments & losses --}}
<section class="rpt-tab-panel rpt-section {{ $activeTab === 'payments' ? 'is-active' : '' }}" data-rpt-panel="payments" data-section="payments" id="rpt-payments">
    <div class="rpt-section-head">
        <div>
            <h2>{{ __('Payments & losses') }}</h2>
            <p>Pending, failed, refunded, and cancelled payment trail · {{ $revenueRange }}</p>
        </div>
    </div>
    <div class="rpt-mini-stats">
        <div><span>Pending</span><strong class="{{ $pendingAmount > 0 ? 'is-warn' : '' }}">₱{{ number_format($pendingAmount, 2) }}</strong></div>
        <div><span>Failed</span><strong class="{{ $failedAmount > 0 ? 'is-bad' : '' }}">₱{{ number_format($failedAmount, 2) }}</strong></div>
        <div><span>Refunded</span><strong class="{{ $refundedAmount > 0 ? 'is-bad' : '' }}">₱{{ number_format($refundedAmount, 2) }}</strong></div>
        <div><span>Loss total</span><strong class="{{ $lossAmount > 0 ? 'is-bad' : '' }}">₱{{ number_format($lossAmount, 2) }}</strong></div>
        <div><span>Cancelled bookings</span><strong class="{{ $cancelledCount ? 'is-bad' : '' }}">{{ $cancelledCount }} · ₱{{ number_format($cancelledAmount, 2) }}</strong></div>
    </div>
    <div class="table-responsive">
        <table class="table-clean">
            <thead>
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Reference') }}</th>
                    <th>{{ __('Guest') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Room') }} / {{ __('Cottage') }}</th>
                    <th>{{ __('Method') }}</th>
                    <th>{{ __('Amount') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Notes') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($paymentDetails as $payment)
                    @php
                        $info = $unitInfo($payment->booking);
                        $refund = $payment->refundSent();
                        $refundAmt = (float) ($payment->metadata['refund_amount'] ?? 0);
                        $note = $refund['note']
                            ?? ($refundAmt > 0 ? 'Refund ₱'.number_format($refundAmt, 2) : null)
                            ?? ($payment->status === 'failed' ? 'Payment failed / declined' : null)
                            ?? ($payment->status === 'pending' ? 'Awaiting confirmation' : null)
                            ?? ($payment->booking?->status === 'cancelled' ? 'Booking cancelled' : '—');
                    @endphp
                    <tr class="{{ in_array($payment->status, ['failed', 'refunded'], true) || $refundAmt > 0 ? 'rpt-row-loss' : '' }}">
                        <td>{{ $payment->created_at?->format('M j, Y g:i A') }}</td>
                        <td>{{ $payment->booking?->reference_no ?? '—' }}</td>
                        <td>
                            <strong>{{ $payment->booking?->user?->name ?? 'Guest' }}</strong>
                            @if($payment->booking?->user?->email)
                                <div class="rpt-sub">{{ $payment->booking->user->email }}</div>
                            @endif
                        </td>
                        <td>{{ $info['type'] }}</td>
                        <td>{{ $info['name'] }}</td>
                        <td>{{ $methodLabel($payment->gateway ?: $payment->payment_channel) }}</td>
                        <td><strong>₱{{ number_format((float) $payment->amount, 2) }}</strong></td>
                        <td><span class="res-status pay-status-{{ $payment->status }}">{{ $payStatusLabels[$payment->status] ?? ucfirst($payment->status) }}</span></td>
                        <td>{{ $note }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="rpt-table-empty">
                                <i class="bi bi-wallet2"></i>
                                <p>No payment records in this period.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@endsection

@push('scripts')
<script>
const rptPeriod = @json($period);
const rptDateType = @json($dateType);
const rptDateFrom = @json($dateFrom);
const rptDateTo = @json($dateTo);
let rptActiveTab = @json($activeTab);

function reportExportSection(tab) {
    if (tab === 'overview') return 'full';
    return tab;
}

function reportQuery(extra) {
    const params = new URLSearchParams();
    params.set('period', rptPeriod);
    params.set('date_type', rptDateType);
    if (rptDateFrom) params.set('date_from', rptDateFrom);
    if (rptDateTo) params.set('date_to', rptDateTo);
    Object.entries(extra || {}).forEach(([key, value]) => {
        if (value != null && value !== '') params.set(key, value);
    });
    return params.toString();
}

function syncReportExports() {
    const section = reportExportSection(rptActiveTab);
    const qs = reportQuery({ section: section, tab: rptActiveTab });
    const pdf = document.getElementById('rptPdfLink');
    const csv = document.getElementById('rptCsvLink');
    if (pdf) pdf.href = pdf.dataset.base + '?' + qs;
    if (csv) csv.href = csv.dataset.base + '?' + qs;
}

function onReportPeriodChange(select) {
    const custom = document.querySelectorAll('.rpt-date-range');
    custom.forEach((el) => el.classList.toggle('is-open', select.value === 'custom'));
    if (select.value !== 'custom') {
        document.getElementById('rptDateForm').submit();
    }
}

function switchReportTab(tab) {
    rptActiveTab = tab;
    document.querySelectorAll('[data-rpt-tab]').forEach((btn) => {
        btn.classList.toggle('active', btn.getAttribute('data-rpt-tab') === tab);
    });
    document.querySelectorAll('[data-rpt-panel]').forEach((panel) => {
        panel.classList.toggle('is-active', panel.getAttribute('data-rpt-panel') === tab);
    });
    const tabInput = document.querySelector('#rptDateForm input[name="tab"]');
    if (tabInput) tabInput.value = tab;
    syncReportExports();
    const url = new URL(window.location.href);
    url.search = reportQuery({ tab: tab });
    window.history.replaceState({}, '', url);
}

document.addEventListener('DOMContentLoaded', function () {
    syncReportExports();
    const chartDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const chartGrid = chartDark ? '#2c3440' : '#eef2f7';
    const chartTick = chartDark ? '#a3adba' : '#64748b';
    const chartEdge = chartDark ? '#1c232d' : '#ffffff';

    @if(count($monthlyRevenue) > 0)
    const revEl = document.getElementById('revenueChart');
    if (revEl) {
        new Chart(revEl.getContext('2d'), {
            type: 'line',
            data: {
                labels: {{ Js::from(collect($monthlyRevenue)->pluck('month')) }},
                datasets: [{
                    label: 'Revenue (₱)',
                    data: {{ Js::from(collect($monthlyRevenue)->pluck('total')->map(fn ($v) => (float) $v)) }},
                    borderColor: '#1d6fbf',
                    backgroundColor: 'rgba(29, 111, 191, 0.12)',
                    borderWidth: 2.5,
                    tension: 0.35,
                    fill: true,
                    pointBackgroundColor: '#1d6fbf',
                    pointBorderColor: chartEdge,
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: chartGrid },
                        ticks: {
                            color: chartTick,
                            callback: function (v) { return '₱' + Number(v).toLocaleString(); }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: chartTick }
                    }
                }
            }
        });
    }
    @endif

    function columnOptions(maxValue) {
        const ceiling = Math.max(1, Number(maxValue) || 0);
        return {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 8, right: 4 } },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleFont: { family: 'Plus Jakarta Sans', size: 12 },
                    bodyFont: { family: 'Plus Jakarta Sans', size: 12 },
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    suggestedMax: ceiling + (ceiling < 5 ? 1 : Math.ceil(ceiling * 0.15)),
                    ticks: {
                        color: chartTick,
                        precision: 0,
                        stepSize: ceiling <= 5 ? 1 : undefined,
                        font: { family: 'Plus Jakarta Sans', size: 11 }
                    },
                    grid: { color: chartGrid, drawBorder: false }
                },
                x: {
                    ticks: {
                        color: chartTick,
                        maxRotation: 0,
                        autoSkip: true,
                        font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' }
                    },
                    grid: { display: false },
                    border: { display: false }
                }
            }
        };
    }

    @if(count($ratingDist) > 0)
    const satEl = document.getElementById('satisfactionChart');
    if (satEl) {
        const ratingData = [
            {{ (int) ($ratingDist->get(1, 0)) }},
            {{ (int) ($ratingDist->get(2, 0)) }},
            {{ (int) ($ratingDist->get(3, 0)) }},
            {{ (int) ($ratingDist->get(4, 0)) }},
            {{ (int) ($ratingDist->get(5, 0)) }}
        ];
        new Chart(satEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['1★', '2★', '3★', '4★', '5★'],
                datasets: [{
                    label: 'Reviews',
                    data: ratingData,
                    backgroundColor: ['#be123c', '#c2410c', '#b45309', '#1d6fbf', '#15803d'],
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 48,
                    categoryPercentage: 0.7,
                    barPercentage: 0.85
                }]
            },
            options: columnOptions(Math.max(...ratingData))
        });
    }
    @endif

    @if($popularRooms->isNotEmpty())
    const roomsEl = document.getElementById('roomsChart');
    if (roomsEl) {
        const roomData = {{ Js::from($popularRooms->pluck('bookings_count')->map(fn ($v) => (int) $v)) }};
        new Chart(roomsEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: {{ Js::from($popularRooms->pluck('chart_label')) }},
                datasets: [{
                    label: 'Stays',
                    data: roomData,
                    backgroundColor: '#1d6fbf',
                    hoverBackgroundColor: '#155a9c',
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 48,
                    categoryPercentage: 0.7,
                    barPercentage: 0.85
                }]
            },
            options: columnOptions(Math.max(...roomData))
        });
    }
    @endif

    @if($popularCottages->isNotEmpty())
    const cottagesEl = document.getElementById('cottagesChart');
    if (cottagesEl) {
        const cottageData = {{ Js::from($popularCottages->pluck('bookings_count')->map(fn ($v) => (int) $v)) }};
        new Chart(cottagesEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: {{ Js::from($popularCottages->pluck('chart_label')) }},
                datasets: [{
                    label: 'Stays',
                    data: cottageData,
                    backgroundColor: '#0f766e',
                    hoverBackgroundColor: '#0d5f59',
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 48,
                    categoryPercentage: 0.7,
                    barPercentage: 0.85
                }]
            },
            options: columnOptions(Math.max(...cottageData))
        });
    }
    @endif
});
</script>
@endpush
