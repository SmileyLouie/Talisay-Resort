@extends('layouts.app')
@section('title', 'Reports & Analytics — Talisay Smart Tourism')

@section('content')

{{-- Header --}}
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Reports & Analytics</h1>
        <p class="text-gray-500 text-sm">Business performance overview and downloadable summaries</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('reports.export-pdf') }}" class="btn btn-sm btn-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
        </a>
        <a href="{{ route('reports.export-excel') }}" class="btn btn-sm btn-success">
            <i class="bi bi-file-earmark-excel me-1"></i>Export CSV
        </a>
    </div>
</div>

{{-- Summary KPI Cards --}}
<div class="row g-4 mb-6">
    <div class="col-md-6 col-lg-3">
        <div class="stat-card bg-white rounded-xl p-5 shadow-sm border-l-4 border-green-500">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Revenue</p>
            <h2 class="text-2xl font-bold text-green-600 mt-1">₱{{ number_format($totalRevenue, 2) }}</h2>
            <p class="text-xs text-gray-400 mt-1">Confirmed payments</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card bg-white rounded-xl p-5 shadow-sm border-l-4 border-yellow-400">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Pending Payments</p>
            <h2 class="text-2xl font-bold text-yellow-500 mt-1">₱{{ number_format($pendingAmount, 2) }}</h2>
            <p class="text-xs text-gray-400 mt-1">Awaiting confirmation</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card bg-white rounded-xl p-5 shadow-sm border-l-4 border-sky-500">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Bookings</p>
            <h2 class="text-2xl font-bold text-sky-600 mt-1">{{ number_format($totalBookings) }}</h2>
            <p class="text-xs text-gray-400 mt-1">All-time reservations</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card bg-white rounded-xl p-5 shadow-sm border-l-4 border-amber-400">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Guest Satisfaction</p>
            <h2 class="text-2xl font-bold text-amber-500 mt-1">{{ $avgRating }}/5.0 ⭐</h2>
            <p class="text-xs text-gray-400 mt-1">Average rating</p>
        </div>
    </div>
</div>

{{-- Charts Grid --}}
<div class="row g-4">

    {{-- Revenue Trend --}}
    <div class="col-lg-8">
        <div class="bg-white rounded-xl shadow-sm p-6 h-100">
            <h3 class="font-bold text-gray-800 mb-4">
                <i class="bi bi-graph-up-arrow text-green-500 me-2"></i>Monthly Revenue Trend
            </h3>
            @if($monthlyRevenue->isEmpty())
                <div class="text-center text-gray-400 py-10">
                    <i class="bi bi-bar-chart-line text-4xl d-block mb-2"></i>No revenue data yet.
                </div>
            @else
            <canvas id="revenueChart" height="200"></canvas>
            @endif
        </div>
    </div>

    {{-- Guest Satisfaction Pie --}}
    <div class="col-lg-4">
        <div class="bg-white rounded-xl shadow-sm p-6 h-100">
            <h3 class="font-bold text-gray-800 mb-4">
                <i class="bi bi-star-fill text-warning me-2"></i>Rating Breakdown
            </h3>
            @if($ratingDist->isEmpty())
                <div class="text-center text-gray-400 py-10">
                    <i class="bi bi-star text-4xl d-block mb-2"></i>No reviews yet.
                </div>
            @else
            <canvas id="satisfactionChart" height="200"></canvas>
            @endif
        </div>
    </div>

    {{-- Occupancy Trend --}}
    <div class="col-lg-6">
        <div class="bg-white rounded-xl shadow-sm p-6 h-100">
            <h3 class="font-bold text-gray-800 mb-4">
                <i class="bi bi-bar-chart text-sky-500 me-2"></i>Daily Occupancy (Last 30 Days)
            </h3>
            @if($dailyOccupancy->isEmpty())
                <div class="text-center text-gray-400 py-10">
                    <i class="bi bi-calendar-x text-4xl d-block mb-2"></i>No occupancy data yet.
                </div>
            @else
            <canvas id="occupancyChart" height="200"></canvas>
            @endif
        </div>
    </div>

    {{-- Popular Packages --}}
    <div class="col-lg-6">
        <div class="bg-white rounded-xl shadow-sm p-6 h-100">
            <h3 class="font-bold text-gray-800 mb-4">
                <i class="bi bi-box-seam text-teal-500 me-2"></i>Popular Packages
            </h3>
            @if($popularPackages->isEmpty())
                <div class="text-center text-gray-400 py-10">
                    <i class="bi bi-box-seam text-4xl d-block mb-2"></i>No package data yet.
                </div>
            @else
            <canvas id="packagesChart" height="200"></canvas>
            @endif
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
// ─── Revenue Chart ───────────────────────────────────────────
@if(!$monthlyRevenue->isEmpty())
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: {!! $monthlyRevenue->pluck('month')->map(fn($m) => "'" . $m . "'")->implode(',') !!},
        datasets: [{
            label: 'Revenue (₱)',
            data: [{{ $monthlyRevenue->pluck('total')->implode(',') }}],
            borderColor: '#22c55e',
            backgroundColor: 'rgba(34,197,94,0.1)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#22c55e',
            pointRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => '₱' + v.toLocaleString() } } }
    }
});
@endif

// ─── Satisfaction Chart ──────────────────────────────────────
@if(!$ratingDist->isEmpty())
new Chart(document.getElementById('satisfactionChart'), {
    type: 'doughnut',
    data: {
        labels: ['1 ★','2 ★','3 ★','4 ★','5 ★'],
        datasets: [{
            data: [
                {{ $ratingDist->get(1, 0) }},
                {{ $ratingDist->get(2, 0) }},
                {{ $ratingDist->get(3, 0) }},
                {{ $ratingDist->get(4, 0) }},
                {{ $ratingDist->get(5, 0) }}
            ],
            backgroundColor: ['#ef4444','#f97316','#eab308','#84cc16','#22c55e']
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
@endif

// ─── Occupancy Chart ─────────────────────────────────────────
@if(!$dailyOccupancy->isEmpty())
new Chart(document.getElementById('occupancyChart'), {
    type: 'bar',
    data: {
        labels: {!! $dailyOccupancy->pluck('date')->map(fn($d) => "'" . $d . "'")->implode(',') !!},
        datasets: [{
            label: 'Utilization %',
            data: [{{ $dailyOccupancy->pluck('utilization')->implode(',') }}],
            backgroundColor: '#0ea5e9',
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } } }
    }
});
@endif

// ─── Packages Chart ──────────────────────────────────────────
@if(!$popularPackages->isEmpty())
new Chart(document.getElementById('packagesChart'), {
    type: 'doughnut',
    data: {
        labels: {!! $popularPackages->pluck('name')->map(fn($n) => "'" . addslashes($n) . "'")->implode(',') !!},
        datasets: [{
            data: [{{ $popularPackages->pluck('bookings_count')->implode(',') }}],
            backgroundColor: ['#14b8a6','#0ea5e9','#f59e0b','#8b5cf6','#ec4899','#f43f5e','#10b981']
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
@endif
</script>
@endpush