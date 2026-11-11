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
        'six_months' => 'Last 6 months',
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
        <span>{{ __('Revenue received') }}</span>
        <strong class="{{ $totalRevenue > 0 ? 'is-good' : '' }}">₱{{ number_format($totalRevenue, 2) }}</strong>
        <em>{{ $revenueChoice }}</em>
    </div>
    <div>
        <span>{{ __('Pending inflows') }}</span>
        <strong class="{{ $pendingAmount > 0 ? 'is-warn' : '' }}">₱{{ number_format($pendingAmount, 2) }}</strong>
        <em>{{ __('Awaiting confirmation') }}</em>
    </div>
    <div>
        <span>{{ __('Refunded') }}</span>
        <strong class="{{ $lossAmount > 0 ? 'is-bad' : '' }}">₱{{ number_format($lossAmount, 2) }}</strong>
        <em>{{ __('Money returned to guests') }}</em>
    </div>
    <div>
        <span>{{ __('Reservations') }}</span>
        <strong>{{ number_format($totalBookings) }}</strong>
        <em>{{ __(':rate% cancelled (:count)', ['rate' => rtrim(rtrim(number_format($stayStats['cancel_rate'], 1), '0'), '.'), 'count' => $cancelledCount]) }}</em>
    </div>
    <div>
        <span>{{ __('Average stay') }}</span>
        <strong>
            @if($stayStats['nightly_count'] > 0)
                {{ trans_choice(':count night|:count nights', $stayStats['avg_nights'] == 1 ? 1 : 2, ['count' => rtrim(rtrim(number_format($stayStats['avg_nights'], 1), '0'), '.')]) }}
            @elseif($stayStats['hourly_count'] > 0)
                {{ trans_choice(':count hour|:count hours', $stayStats['avg_hours'] == 1 ? 1 : 2, ['count' => rtrim(rtrim(number_format($stayStats['avg_hours'], 1), '0'), '.')]) }}
            @else
                —
            @endif
        </strong>
        <em>
            @if($stayStats['avg_rate'] > 0)
                ₱{{ number_format($stayStats['avg_rate'], 2) }} {{ __('a night on average') }}
            @else
                {{ __('No overnight stays yet') }}
            @endif
        </em>
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
                    <p>{{ $period === 'six_months' ? __('Money received in the last 6 months, by the day it was confirmed.') : $revenueRange }}</p>
                </div>
                <span class="rpt-chip">PHP</span>
            </div>
            <div class="rpt-chart">
                @if(empty($revenueHasData))
                    <div class="rpt-empty">
                        <i class="bi bi-graph-up-arrow"></i>
                        <p>{{ __('No confirmed payments in this period.') }}</p>
                        @if(! in_array($period, ['six_months', 'all', 'year'], true))
                            <p>{{ __('Confirmed sales are in an earlier month. Choose Last 6 months to match the dashboard.') }}</p>
                        @endif
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

        <section class="rpt-panel rpt-panel-wide">
            <div class="rpt-panel-head">
                <div>
                    <h2>{{ __('Popular rooms & cottages') }}</h2>
                    <p>{{ __('Paid and completed stays. Click a slice to open that room or cottage.') }}</p>
                </div>
                <div class="rpt-switch" role="group" aria-label="{{ __('Show rooms or cottages') }}">
                    <button type="button" data-unit-type="room" aria-pressed="true">{{ __('Rooms') }}</button>
                    <button type="button" data-unit-type="cottage" aria-pressed="false">{{ __('Cottages') }}</button>
                </div>
            </div>
            <div class="rpt-chart rpt-chart-ring">
                <canvas id="popularUnitsChart" aria-label="{{ __('Popular rooms & cottages') }}"></canvas>
                <div class="rpt-empty" id="popularUnitsEmpty" hidden>
                    <i class="bi bi-pie-chart"></i>
                    <p></p>
                </div>
            </div>
            <ul class="rpt-legend" id="popularUnitsLegend"></ul>
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
                        <td>{{ ($payment->paid_at ?? $payment->created_at)?->format('M j, Y g:i A') }}</td>
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
        <div><span>Failed (never received)</span><strong>₱{{ number_format($failedAmount, 2) }}</strong></div>
        <div><span>Refunded to guests</span><strong class="{{ $lossAmount > 0 ? 'is-bad' : '' }}">₱{{ number_format($lossAmount, 2) }}</strong></div>
        <div><span>Cancellation rate</span><strong class="{{ $stayStats['cancel_rate'] > 0 ? 'is-warn' : '' }}">{{ rtrim(rtrim(number_format($stayStats['cancel_rate'], 1), '0'), '.') }}%</strong></div>
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

    @if(! empty($revenueHasData))
    const revEl = document.getElementById('revenueChart');
    if (revEl) {
        const revenueCtx = revEl.getContext('2d');
        const revGradient = revenueCtx.createLinearGradient(0, 0, 0, 200);
        revGradient.addColorStop(0, 'rgba(14, 165, 233, 0.35)');
        revGradient.addColorStop(1, 'rgba(14, 165, 233, 0.0)');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: {{ Js::from(collect($monthlyRevenue)->pluck('month')) }},
                datasets: [{
                    label: 'Revenue (₱)',
                    data: {{ Js::from(collect($monthlyRevenue)->pluck('total')->map(fn ($v) => (float) $v)) }},
                    borderColor: '#0284c7',
                    backgroundColor: revGradient,
                    borderWidth: 2.5,
                    tension: 0.35,
                    fill: true,
                    pointBackgroundColor: '#0ea5e9',
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

    const unitPalette = ['#0284c7', '#f97316', '#10b981', '#8b5cf6', '#ec4899', '#06b6d4', '#eab308', '#ef4444',
        '#84cc16', '#6366f1', '#f59e0b', '#14b8a6', '#f43f5e', '#a855f7', '#22c55e', '#3b82f6'];
    const unitColor = i => unitPalette[i] || 'hsl(' + ((i * 47) % 360) + ', 65%, 52%)';

    const popularUnits = { room: {{ Js::from($popularRooms) }}, cottage: {{ Js::from($popularCottages) }} };
    const popularEmptyText = { room: @json(__('No paid room stays in this period.')), cottage: @json(__('No paid cottage stays in this period.')) };
    const popularCanvas = document.getElementById('popularUnitsChart');
    const popularEmpty = document.getElementById('popularUnitsEmpty');
    const popularSwitch = document.querySelectorAll('.rpt-switch [data-unit-type]');
    let popularType = localStorage.getItem('reportsUnitType') === 'cottage' ? 'cottage' : 'room';
    let slices = [];

    const popularChart = new Chart(popularCanvas.getContext('2d'), {
        type: 'pie',
        data: { labels: [], datasets: [{ data: [], backgroundColor: [], borderColor: chartEdge }] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: items => slices[items[0].dataIndex].label + ' · ' + slices[items[0].dataIndex].variant,
                        label: context => context.parsed + (context.parsed === 1 ? ' stay' : ' stays'),
                        footer: () => @json(__('Click to open'))
                    }
                }
            },
            onHover: (event, elements) => {
                event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
            },
            onClick: (event, elements) => {
                const unit = elements.length ? slices[elements[0].index] : null;
                if (unit) window.location.href = unit.url;
            }
        }
    });

    function renderPopular() {
        popularSwitch.forEach(btn => btn.setAttribute('aria-pressed', String(btn.dataset.unitType === popularType)));
        const units = popularUnits[popularType].map((u, i) => ({ ...u, color: unitColor(i) }));
        slices = units.filter(u => u.total > 0);

        const dataset = popularChart.data.datasets[0];
        popularChart.data.labels = slices.map(u => u.label);
        dataset.data = slices.map(u => u.total);
        dataset.backgroundColor = slices.map(u => u.color);
        dataset.borderWidth = slices.length > 1 ? 2 : 0;
        popularChart.update();

        popularCanvas.hidden = slices.length === 0;
        popularEmpty.hidden = slices.length > 0;
        popularEmpty.querySelector('p').textContent = popularEmptyText[popularType];

        document.getElementById('popularUnitsLegend').replaceChildren(...units.map(u => {
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
    }

    popularSwitch.forEach(btn => btn.addEventListener('click', () => {
        popularType = btn.dataset.unitType;
        localStorage.setItem('reportsUnitType', popularType);
        renderPopular();
    }));
    renderPopular();
});
</script>
@endpush
