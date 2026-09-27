@extends('layouts.app')

@section('title', 'Reports & Analytics — Talisay Smart Tourism')

@section('content')

{{-- Header --}}
<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100/80 text-sky-800 border border-sky-200">
                <i class="bi bi-bar-chart-fill text-[10px] text-sky-600"></i>
                Analytics
            </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-1">
            Reports &amp; Analytics
        </h1>
        <p class="text-sm text-slate-500 mb-0">
            Resort revenue performance, capacity schedules, review analytics, and exportable business summaries.
        </p>
    </div>

    <div class="flex items-center gap-2">
        <a href="{{ route('reports.export-pdf') }}" class="btn-secondary-clean text-xs">
            <i class="bi bi-file-earmark-pdf-fill text-rose-500"></i>
            <span>Export PDF</span>
        </a>
        <a href="{{ route('reports.export-excel') }}" class="btn-ocean text-xs">
            <i class="bi bi-file-earmark-excel-fill text-emerald-300"></i>
            <span>Export CSV</span>
        </a>
    </div>
</div>

{{-- Summary KPI Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
    
    {{-- Total Revenue --}}
    <div class="stat-card-clean flex flex-col justify-between">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Confirmed Revenue</p>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-emerald-600 tracking-tight">
                    ₱{{ number_format($totalRevenue, 2) }}
                </h2>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xl font-bold shadow-sm">
                <i class="bi bi-wallet2"></i>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-500 flex items-center justify-between">
            <span class="text-emerald-600 font-semibold">Confirmed payments</span>
            <span class="text-slate-400">All-time</span>
        </div>
    </div>

    {{-- Pending Payments --}}
    <div class="stat-card-clean flex flex-col justify-between">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Pending Inflows</p>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-amber-600 tracking-tight">
                    ₱{{ number_format($pendingAmount, 2) }}
                </h2>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xl font-bold shadow-sm">
                <i class="bi bi-hourglass-split"></i>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-500 flex items-center justify-between">
            <span class="text-amber-600 font-semibold">Awaiting confirmation</span>
            <span class="text-slate-400">Pending</span>
        </div>
    </div>

    {{-- Total Bookings --}}
    <div class="stat-card-clean flex flex-col justify-between">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Total Reservations</p>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    {{ number_format($totalBookings) }}
                </h2>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 border border-sky-100 flex items-center justify-center text-xl font-bold shadow-sm">
                <i class="bi bi-calendar-check-fill"></i>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-500 flex items-center justify-between">
            <span class="text-sky-600 font-semibold">Resort bookings</span>
            <span class="text-slate-400">Total</span>
        </div>
    </div>

    {{-- Guest Satisfaction --}}
    <div class="stat-card-clean flex flex-col justify-between">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Guest Satisfaction</p>
                <div class="flex items-baseline gap-2">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-amber-500 tracking-tight">
                        {{ $avgRating }}
                    </h2>
                    <span class="text-xs font-bold text-slate-400">/ 5.0 Stars</span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xl font-bold shadow-sm">
                <i class="bi bi-star-fill"></i>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-500 flex items-center justify-between">
            <span class="text-amber-600 font-semibold">Average rating</span>
            <span class="text-slate-400">Reviews</span>
        </div>
    </div>
</div>

{{-- Charts Grid --}}
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">

    {{-- Monthly Revenue Trend --}}
    <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 mb-0.5 flex items-center gap-2">
                    <i class="bi bi-graph-up-arrow text-emerald-500"></i>
                    <span>Monthly Revenue Performance</span>
                </h3>
                <p class="text-xs text-slate-400 mb-0">Confirmed earnings breakdown over time</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/60">PHP</span>
        </div>
        <div class="relative" style="height: 280px;">
            @if(count($monthlyRevenue) === 0)
                <div class="text-center text-slate-400 pt-16">
                    <i class="bi bi-bar-chart-line text-4xl block mb-2 text-slate-300"></i>
                    No confirmed payment records available yet.
                </div>
            @else
                <canvas id="revenueChart"></canvas>
            @endif
        </div>
    </div>

    {{-- Rating Breakdown Doughnut --}}
    <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 mb-0.5 flex items-center gap-2">
                    <i class="bi bi-star-fill text-amber-500"></i>
                    <span>Rating Breakdown</span>
                </h3>
                <p class="text-xs text-slate-400 mb-0">5-star rating distribution</p>
            </div>
        </div>
        <div class="relative" style="height: 280px;">
            @if(count($ratingDist) === 0)
                <div class="text-center text-slate-400 pt-16">
                    <i class="bi bi-star text-4xl block mb-2 text-slate-300"></i>
                    No reviews submitted yet.
                </div>
            @else
                <canvas id="satisfactionChart"></canvas>
            @endif
        </div>
    </div>

    {{-- Daily Occupancy Trend --}}
    <div class="lg:col-span-6 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 mb-0.5 flex items-center gap-2">
                    <i class="bi bi-bar-chart-line text-sky-500"></i>
                    <span>Daily Occupancy (Last 90 Days)</span>
                </h3>
                <p class="text-xs text-slate-400 mb-0">Capacity scheduling and guest check-in trends</p>
            </div>
        </div>
        <div class="relative" style="height: 280px;">
            @if(count($dailyOccupancy) === 0)
                <div class="text-center text-slate-400 pt-16">
                    <i class="bi bi-calendar-x text-4xl block mb-2 text-slate-300"></i>
                    No capacity schedule data available.
                </div>
            @else
                <canvas id="occupancyChart"></canvas>
            @endif
        </div>
    </div>

    {{-- Popular Accommodations --}}
    <div class="lg:col-span-6 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 mb-0.5 flex items-center gap-2">
                    <i class="bi bi-building text-teal-500"></i>
                    <span>Popular Rooms &amp; Cottages</span>
                </h3>
                <p class="text-xs text-slate-400 mb-0">Highest frequency accommodation selections</p>
            </div>
        </div>
        <div class="relative" style="height: 280px;">
            @if(count($popularAccommodations) === 0)
                <div class="text-center text-slate-400 pt-16">
                    <i class="bi bi-building text-4xl block mb-2 text-slate-300"></i>
                    No booking records recorded yet.
                </div>
            @else
                <canvas id="accommodationsChart"></canvas>
            @endif
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Revenue Chart
    @if(count($monthlyRevenue) > 0)
    const revEl = document.getElementById('revenueChart');
    if (revEl) {
        new Chart(revEl.getContext('2d'), {
            type: 'line',
            data: {
                labels: {{ Js::from(collect($monthlyRevenue)->pluck('month')) }},
                datasets: [{
                    label: 'Revenue (₱)',
                    data: {{ Js::from(collect($monthlyRevenue)->pluck('total')->map(fn($v) => (float)$v)) }},
                    borderColor: '#0284c7',
                    backgroundColor: 'rgba(14, 165, 233, 0.15)',
                    borderWidth: 2.5,
                    tension: 0.35,
                    fill: true,
                    pointBackgroundColor: '#0ea5e9',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            callback: function(v) { return '₱' + Number(v).toLocaleString(); }
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
    @endif

    // Rating Breakdown Doughnut
    @if(count($ratingDist) > 0)
    const satEl = document.getElementById('satisfactionChart');
    if (satEl) {
        new Chart(satEl.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
                datasets: [{
                    data: [
                        {{ (int)($ratingDist->get(1, 0)) }},
                        {{ (int)($ratingDist->get(2, 0)) }},
                        {{ (int)($ratingDist->get(3, 0)) }},
                        {{ (int)($ratingDist->get(4, 0)) }},
                        {{ (int)($ratingDist->get(5, 0)) }}
                    ],
                    backgroundColor: ['#f43f5e','#fb923c','#f59e0b','#38bdf8','#10b981'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { family: 'Plus Jakarta Sans', size: 11 } }
                    }
                }
            }
        });
    }
    @endif

    // Occupancy Bar Chart
    @if(count($dailyOccupancy) > 0)
    const occEl = document.getElementById('occupancyChart');
    if (occEl) {
        new Chart(occEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: {{ Js::from(collect($dailyOccupancy)->pluck('date')) }},
                datasets: [{
                    label: 'Utilization %',
                    data: {{ Js::from(collect($dailyOccupancy)->pluck('utilization')) }},
                    backgroundColor: '#0ea5e9',
                    borderRadius: 6,
                    hoverBackgroundColor: '#0284c7'
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
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            callback: function(v) { return v + '%'; }
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
    @endif

    // Popular Accommodations Doughnut
    @if(count($popularAccommodations) > 0)
    const accEl = document.getElementById('accommodationsChart');
    if (accEl) {
        new Chart(accEl.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: {{ Js::from($popularAccommodations->pluck('unit_number')) }},
                datasets: [{
                    data: {{ Js::from($popularAccommodations->pluck('bookings_count')) }},
                    backgroundColor: ['#0284c7','#0ea5e9','#14b8a6','#f59e0b','#8b5cf6','#ec4899','#10b981'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 10, boxWidth: 12, font: { family: 'Plus Jakarta Sans', size: 11 } }
                    }
                }
            }
        });
    }
    @endif

});
</script>
@endpush