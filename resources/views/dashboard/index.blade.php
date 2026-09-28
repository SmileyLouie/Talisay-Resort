{{-- Extends the primary layout template designed for dashboard panels --}}
@extends('layouts.app')

{{-- Sets the HTML document title for the dashboard index page --}}
@section('title', 'Dashboard - Talisay Smart Tourism')

{{-- Defines the main content area section of the dashboard template --}}
@section('content')

{{-- ── Top Welcome & Overview Header ────────────────────────────────────────── --}}
<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100/80 text-sky-800 border border-sky-200">
                <i class="bi bi-clock-fill text-[10px] text-sky-600"></i>
                {{ now()->format('l, F j, Y') }}
            </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-1">
            Dashboard Overview
        </h1>
        <p class="text-sm text-slate-500 mb-0">
            Welcome back, <span class="font-semibold text-slate-700">{{ auth()->user()->name }}</span>. Here is the operational summary for Talisay Beach Resort today.
        </p>
    </div>

    {{-- Quick Action CTA Bar --}}
    <div class="flex flex-wrap items-center gap-2.5">
        <a href="{{ route('bookings.index') }}" class="btn-secondary-clean">
            <i class="bi bi-calendar2-range text-slate-500"></i>
            <span>All Bookings</span>
        </a>
        <a href="{{ route('accommodations.index') }}" class="btn-ocean">
            <i class="bi bi-building"></i>
            <span>Manage Units ({{ $totalUnits }})</span>
        </a>
    </div>
</div>

{{-- ── 4 Main Operational Metric Cards ────────────────────────────────────────── --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
    
    {{-- Card 1: Today's Bookings --}}
    <div class="stat-card-clean flex flex-col justify-between">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Today's Bookings</p>
                <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $todaysBookings }}</h2>
            </div>
            <div class="w-10 h-10 rounded-lg bg-ocean-800 text-white flex items-center justify-center">
                <i class="bi bi-calendar-check-fill"></i>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span class="flex items-center gap-1 text-sky-600 font-semibold">
                <i class="bi bi-arrow-up-right"></i> Active arrivals
            </span>
            <span class="text-slate-400">Today</span>
        </div>
    </div>

    {{-- Card 2: Current Capacity --}}
    <div class="stat-card-clean flex flex-col justify-between">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Resort Capacity</p>
                <div class="flex items-baseline gap-2">
                    <h2 class="text-3xl font-extrabold tracking-tight {{ $capacity->getUtilizationPercent() > 90 ? 'text-rose-600' : ($capacity->getUtilizationPercent() > 70 ? 'text-amber-500' : 'text-teal-600') }}">
                        {{ $capacity->getUtilizationPercent() }}%
                    </h2>
                    <span class="text-xs font-semibold text-slate-400">
                        {{ $capacity->current_count }}/{{ $capacity->max_capacity }}
                    </span>
                </div>
            </div>
            <div class="w-10 h-10 rounded-lg {{ $capacity->getUtilizationPercent() > 90 ? 'bg-rose-700' : ($capacity->getUtilizationPercent() > 70 ? 'bg-amber-600' : 'bg-ocean-600') }} text-white flex items-center justify-center">
                <i class="bi bi-pie-chart-fill"></i>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100">
            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                <div class="h-2 rounded-full transition-all duration-500 {{ $capacity->getUtilizationPercent() > 90 ? 'bg-rose-500' : ($capacity->getUtilizationPercent() > 70 ? 'bg-amber-500' : 'bg-teal-500') }}" style="width: {{ min($capacity->getUtilizationPercent(), 100) }}%"></div>
            </div>
        </div>
    </div>

    {{-- Card 3: Monthly Revenue --}}
    <div class="stat-card-clean flex flex-col justify-between">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Monthly Revenue</p>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-emerald-600 tracking-tight">
                    ₱{{ number_format($monthlyRevenue, 2) }}
                </h2>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-700 text-white flex items-center justify-center">
                <i class="bi bi-wallet2"></i>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span class="flex items-center gap-1 text-emerald-600 font-semibold">
                <i class="bi bi-check-circle-fill text-[11px]"></i> Verified payments
            </span>
            <span class="text-slate-400">{{ now()->format('M Y') }}</span>
        </div>
    </div>

    {{-- Card 4: Accommodations Units --}}
    <div class="stat-card-clean flex flex-col justify-between">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Room &amp; Cottage</p>
                <div class="flex items-baseline gap-1.5">
                    <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $activeUnits }}</h2>
                    <span class="text-sm font-semibold text-slate-400">/ {{ $totalUnits }} Available</span>
                </div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-ocean-800 text-white flex items-center justify-center">
                <i class="bi bi-house-door-fill"></i>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span class="font-medium text-slate-600">{{ $roomCount }} Rooms • {{ $cottageCount }} Cottages</span>
            <a href="{{ route('accommodations.index') }}" class="text-sky-600 font-semibold hover:underline no-underline">View</a>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <a href="{{ route('bookings.index', ['status' => 'pending']) }}" class="stat-card-clean no-underline">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Pending Bookings</p>
        <h2 class="text-2xl font-extrabold text-amber-700 mb-0">{{ $quickStats['pendingBookings'] }}</h2>
    </a>
    <div class="stat-card-clean">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Guests Checked In</p>
        <h2 class="text-2xl font-extrabold text-slate-900 mb-0">{{ $quickStats['checkedInGuests'] }}</h2>
    </div>
    <a href="{{ route('reviews.index') }}" class="stat-card-clean no-underline">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Reviews to Check</p>
        <h2 class="text-2xl font-extrabold text-slate-900 mb-0">{{ $quickStats['pendingReviews'] }}</h2>
    </a>
    <div class="stat-card-clean">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Registered Accounts</p>
        <h2 class="text-2xl font-extrabold text-slate-900 mb-0">{{ $quickStats['totalUsers'] }}</h2>
    </div>
</div>

{{-- ── Feed & Side Actions Grid ───────────────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    {{-- Booking Feed (2 cols) --}}
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
                <h3 class="text-base font-bold text-slate-900 mb-0 flex items-center gap-2">
                    <i class="bi bi-calendar-check-fill text-sky-500"></i>
                    <span>Booking Feed</span>
                </h3>
                <p class="text-xs text-slate-400 mb-0">Incoming reservations and status changes</p>
            </div>
            <a href="{{ route('bookings.index') }}" class="text-xs font-bold text-sky-600 hover:text-sky-700 hover:underline flex items-center gap-1 no-underline">
                View All <i class="bi bi-arrow-right text-[10px]"></i>
            </a>
        </div>

        <div class="table-responsive flex-1">
            <table class="table-clean" id="bookingFeedTable">
                <thead>
                    <tr>
                        <th>Ref #</th>
                        <th>Guest</th>
                        <th>Room &amp; Cottage</th>
                        <th>Date</th>
                        <th>Guests</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentBookings as $booking)
                    <tr class="booking-row" data-id="{{ $booking->id }}">
                        <td>
                            <span class="font-mono text-xs font-bold text-sky-600 bg-sky-50 px-2 py-0.5 rounded border border-sky-200/60">
                                {{ $booking->reference_no }}
                            </span>
                            @if($booking->booking_type === 'special_resort')
                            <div class="mt-1">
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-800 bg-amber-100 px-1.5 py-0.5 rounded">
                                    <i class="bi bi-stars"></i> Exclusive
                                </span>
                            </div>
                            @endif
                        </td>
                        <td>
                            <div class="font-semibold text-slate-800">{{ $booking->guest_name }}</div>
                            <div class="text-[11px] text-slate-400">{{ $booking->guest_contact }}</div>
                        </td>
                        <td>
                            <span class="font-medium text-slate-700">
                                {{ $booking->booking_type === 'special_resort' ? 'All 20 Units (Full Resort)' : ($booking->accommodationUnit->unit_number ?? 'Room / Cottage') }}
                            </span>
                        </td>
                        <td>
                            <span class="text-xs font-medium text-slate-600">{{ $booking->booking_date->format('M d, Y') }}</span>
                        </td>
                        <td>
                            <span class="text-xs font-semibold text-slate-700">{{ $booking->guests_count }}</span>
                        </td>
                        <td>
                            @php
                                $badgeStyles = [
                                    'pending'     => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'paid'        => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'checked_in'  => 'bg-sky-50 text-sky-700 border-sky-200',
                                    'checked_out' => 'bg-slate-100 text-slate-700 border-slate-200',
                                    'completed'   => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    'cancelled'   => 'bg-rose-50 text-rose-700 border-rose-200',
                                ];
                            @endphp
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold border inline-flex items-center gap-1 {{ $badgeStyles[$booking->status] ?? 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-10 text-slate-400">
                            <i class="bi bi-calendar-x text-3xl block mb-2 text-slate-300"></i>
                            No recent reservations found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Right Column (Quick Actions & Upcoming) --}}
    <div class="space-y-6">
        
        {{-- Quick Actions Card --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <h3 class="text-base font-bold text-slate-900 mb-3 flex items-center gap-2">
                <i class="bi bi-lightning-charge-fill text-amber-500"></i>
                <span>Quick Operations</span>
            </h3>
            
            <div class="space-y-2">
                @if(auth()->user()->isAdmin())
                <a href="{{ route('accommodations.index') }}" class="w-full flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-sky-50 border border-slate-200/80 hover:border-sky-200 text-slate-700 hover:text-sky-700 transition no-underline text-xs font-semibold">
                    <span class="flex items-center gap-2.5">
                        <i class="bi bi-building text-sky-600 text-base"></i>
                        <span>Manage Room &amp; Cottage</span>
                    </span>
                    <i class="bi bi-chevron-right text-slate-400 text-[10px]"></i>
                </a>

                <a href="{{ route('tasks.staff') }}" class="w-full flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-sky-50 border border-slate-200/80 hover:border-sky-200 text-slate-700 hover:text-sky-700 transition no-underline text-xs font-semibold">
                    <span class="flex items-center gap-2.5">
                        <i class="bi bi-person-lines-fill text-sky-600 text-base"></i>
                        <span>Staff &amp; Role Management</span>
                    </span>
                    <i class="bi bi-chevron-right text-slate-400 text-[10px]"></i>
                </a>
                @endif

                <a href="{{ route('bookings.index') }}" class="w-full flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-sky-50 border border-slate-200/80 hover:border-sky-200 text-slate-700 hover:text-sky-700 transition no-underline text-xs font-semibold">
                    <span class="flex items-center gap-2.5">
                        <i class="bi bi-calendar-check-fill text-sky-600 text-base"></i>
                        <span>Search All Reservations</span>
                    </span>
                    <i class="bi bi-chevron-right text-slate-400 text-[10px]"></i>
                </a>

                <a href="{{ route('payments.index') }}" class="w-full flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-emerald-50 border border-slate-200/80 hover:border-emerald-200 text-slate-700 hover:text-emerald-700 transition no-underline text-xs font-semibold">
                    <span class="flex items-center gap-2.5">
                        <i class="bi bi-credit-card-2-front-fill text-emerald-600 text-base"></i>
                        <span>Verify Guest Payments</span>
                    </span>
                    <i class="bi bi-chevron-right text-slate-400 text-[10px]"></i>
                </a>

                <a href="{{ route('reviews.index') }}" class="w-full flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-amber-50 border border-slate-200/80 hover:border-amber-200 text-slate-700 hover:text-amber-700 transition no-underline text-xs font-semibold">
                    <span class="flex items-center gap-2.5">
                        <i class="bi bi-star-fill text-amber-500 text-base"></i>
                        <span>Moderate Guest Reviews</span>
                    </span>
                    <i class="bi bi-chevron-right text-slate-400 text-[10px]"></i>
                </a>
            </div>
        </div>

        {{-- Upcoming Reservations Card --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <h3 class="text-base font-bold text-slate-900 mb-3 flex items-center gap-2">
                <i class="bi bi-calendar-event text-sky-500"></i>
                <span>Upcoming Check-Ins</span>
            </h3>

            <div class="space-y-3">
                @forelse($upcomingReservations as $res)
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-9 h-9 rounded-lg bg-ocean-800 text-white font-bold flex items-center justify-center text-xs flex-shrink-0">
                            {{ strtoupper(substr($res->guest_name ?: ($res->user->name ?? 'G'), 0, 2)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-900 truncate mb-0">{{ $res->guest_name ?: ($res->user->name ?? 'Guest') }}</p>
                            <p class="text-[11px] text-slate-500 truncate mb-0">
                                {{ $res->accommodationUnit->unit_number ?? 'Exclusive Resort' }} • {{ $res->booking_date->format('M d') }}
                            </p>
                        </div>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $res->status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }} flex-shrink-0">
                        {{ ucfirst($res->status) }}
                    </span>
                </div>
                @empty
                <div class="text-center py-6 text-slate-400">
                    <p class="text-xs font-medium mb-0">No upcoming check-ins in the next 48 hours.</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

{{-- ── Analytics & Trends Grid ────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    
    {{-- Revenue Trends Chart --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 mb-0.5 flex items-center gap-2">
                    <i class="bi bi-graph-up-arrow text-sky-500"></i>
                    <span>Revenue Trends</span>
                </h3>
                <p class="text-xs text-slate-400 mb-0">Monthly earnings summary (Past 6 Months)</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-sky-50 text-sky-700 border border-sky-200/60">PHP</span>
        </div>
        <div class="relative" style="height: 230px;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    {{-- Occupancy Bar Chart --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 mb-0.5 flex items-center gap-2">
                    <i class="bi bi-bar-chart-line-fill text-teal-500"></i>
                    <span>Occupancy Rate</span>
                </h3>
                <p class="text-xs text-slate-400 mb-0">Daily guest utilization percentage (Past 7 Days)</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-teal-50 text-teal-700 border border-teal-200/60">% Rate</span>
        </div>
        <div class="relative" style="height: 230px;">
            <canvas id="occupancyChart"></canvas>
        </div>
    </div>

    {{-- Popular Accommodations Doughnut --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 mb-0.5 flex items-center gap-2">
                    <i class="bi bi-pie-chart-fill text-amber-500"></i>
                    <span>Most Booked Rooms &amp; Cottages</span>
                </h3>
                <p class="text-xs text-slate-400 mb-0">Popularity distribution across rooms & cottages</p>
            </div>
        </div>
        <div class="relative" style="height: 230px;">
            <canvas id="accommodationsChart"></canvas>
        </div>
    </div>

    {{-- Guest Sentiment Analysis --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 mb-0.5 flex items-center gap-2">
                    <i class="bi bi-chat-heart text-emerald-600"></i>
                    <span>Guest Feedback Sentiment</span>
                </h3>
                <p class="text-xs text-slate-400 mb-0">Review ratings ratio and guest satisfaction</p>
            </div>
        </div>
        <div class="relative" style="height: 230px;">
            <canvas id="sentimentChart"></canvas>
        </div>
    </div>
</div>

@endsection

{{-- Push chart scripts --}}
@push('scripts')
<script>
// Occupancy Chart
const occupancyLabels = {{ Js::from($occupancyData->pluck('date')) }};
const occupancyValues = {{ Js::from($occupancyData->pluck('utilization')) }};
const occupancyCtx = document.getElementById('occupancyChart').getContext('2d');
new Chart(occupancyCtx, {
    type: 'bar',
    data: {
        labels: occupancyLabels,
        datasets: [{
            label: 'Occupancy %',
            data: occupancyValues,
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
                ticks: { callback: v => v + '%' },
                grid: { color: '#f1f5f9' }
            },
            x: {
                grid: { display: false }
            }
        }
    }
});

// Revenue Trends Line Chart
const revenueLabels = {{ Js::from(collect($revenueTrends)->pluck('month')) }};
const revenueValues = {{ Js::from(collect($revenueTrends)->pluck('total')) }};
const revenueCtx = document.getElementById('revenueChart').getContext('2d');

const revGradient = revenueCtx.createLinearGradient(0, 0, 0, 200);
revGradient.addColorStop(0, 'rgba(14, 165, 233, 0.35)');
revGradient.addColorStop(1, 'rgba(14, 165, 233, 0.0)');

new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: revenueLabels,
        datasets: [{
            label: 'Revenue (₱)',
            data: revenueValues,
            backgroundColor: revGradient,
            borderColor: '#0284c7',
            borderWidth: 2.5,
            pointBackgroundColor: '#0ea5e9',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 4,
            tension: 0.35,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return '₱' + Number(context.parsed.y).toLocaleString('en-US', { minimumFractionDigits: 2 });
                    }
                }
            }
        },
        scales: {
            y: { 
                beginAtZero: true,
                grid: { color: '#f1f5f9' },
                ticks: {
                    callback: v => '₱' + Number(v).toLocaleString()
                }
            },
            x: {
                grid: { display: false }
            }
        }
    }
});

// Accommodations Popularity Chart
const accommodationLabels = {{ Js::from($popularAccommodations->pluck('unit_number')) }};
const accommodationValues = {{ Js::from($popularAccommodations->pluck('bookings_count')) }};
const accommodationsCtx = document.getElementById('accommodationsChart').getContext('2d');
new Chart(accommodationsCtx, {
    type: 'doughnut',
    data: {
        labels: accommodationLabels,
        datasets: [{
            data: accommodationValues,
            backgroundColor: [
                '#0284c7',
                '#0ea5e9',
                '#14b8a6',
                '#f59e0b',
                '#8b5cf6'
            ],
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, font: { family: 'Plus Jakarta Sans', size: 11 } } }
        }
    }
});

// Sentiment Analysis Chart
const sentimentData = {{ Js::from($sentimentData) }};
const sentimentCtx = document.getElementById('sentimentChart').getContext('2d');
new Chart(sentimentCtx, {
    type: 'pie',
    data: {
        labels: ['Positive (4-5 stars)', 'Neutral (3 stars)', 'Negative (1-2 stars)'],
        datasets: [{
            data: [sentimentData.positive, sentimentData.neutral, sentimentData.negative],
            backgroundColor: [
                '#10b981',
                '#f59e0b',
                '#f43f5e'
            ],
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, font: { family: 'Plus Jakarta Sans', size: 11 } } }
        }
    }
});

// Real-time booking feed (events dispatched by the layout's Pusher subscription)
const feedBadgeStyles = {
    pending: 'bg-amber-50 text-amber-700 border-amber-200',
    paid: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    checked_in: 'bg-sky-50 text-sky-700 border-sky-200',
    checked_out: 'bg-slate-100 text-slate-700 border-slate-200',
    completed: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    cancelled: 'bg-rose-50 text-rose-700 border-rose-200'
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

    row.appendChild(feedCell('font-mono text-xs font-bold text-sky-600 bg-sky-50 px-2 py-0.5 rounded border border-sky-200/60', data.reference_no));

    const guestTd = document.createElement('td');
    const guestDiv = document.createElement('div');
    guestDiv.className = 'font-semibold text-slate-800';
    guestDiv.textContent = data.guest_name || '';
    guestTd.appendChild(guestDiv);
    row.appendChild(guestTd);

    row.appendChild(feedCell('font-medium text-slate-700', data.unit_label || 'Room / Cottage'));
    row.appendChild(feedCell('text-xs font-medium text-slate-600', data.booking_date));
    row.appendChild(feedCell('text-xs font-semibold text-slate-700', data.guests_count));
    row.appendChild(feedCell(
        'px-2.5 py-1 rounded-full text-xs font-bold border inline-flex items-center gap-1 ' + (feedBadgeStyles[data.status] || 'bg-slate-100 text-slate-600 border-slate-200'),
        feedStatusLabel(data.status)
    ));
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
