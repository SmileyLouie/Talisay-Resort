{{-- Extends the primary layout template designed for dashboard panels --}}
@extends('layouts.app')

{{-- Sets the HTML document title for the dashboard index page --}}
@section('title', 'Dashboard - Talisay Smart Tourism')

{{-- Defines the main content area section of the dashboard template --}}
@section('content')

{{-- Outer wrapper section for header title --}}
<div class="mb-6">
    {{-- Main dashboard header title --}}
    <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
    {{-- Subtitle containing name of logged-in user --}}
    <p class="text-gray-500 text-sm">Welcome back, {{ auth()->user()->name }}</p>
</div>

{{-- Stat cards grid container displaying top high-level metrics --}}
<div class="row g-4 mb-6">
    
    {{-- Card for today's total booking count --}}
    <div class="col-md-6 col-lg-3">
        {{-- Card element styled with white background and sky blue border --}}
        <div class="stat-card bg-white rounded-xl p-5 shadow-sm border-l-4 border-sky-500">
            {{-- Flex layout grouping labels and icons --}}
            <div class="flex items-center justify-between">
                <div>
                    {{-- Text label indicating card category --}}
                    <p class="text-sm text-gray-500">Today's Bookings</p>
                    {{-- Large metric counter number --}}
                    <h2 class="text-3xl font-bold text-gray-800 mt-1">{{ $todaysBookings }}</h2>
                </div>
                {{-- Decorative icon badge on the right --}}
                <div class="w-12 h-12 rounded-lg bg-sky-100 flex items-center justify-center">
                    {{-- Calendar check icon matching the category --}}
                    <i class="bi bi-calendar-check text-sky-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Card for capacity utilisation state of the current date --}}
    <div class="col-md-6 col-lg-3">
        {{-- Card element styled with white background and teal border --}}
        <div class="stat-card bg-white rounded-xl p-5 shadow-sm border-l-4 border-teal-500">
            {{-- Flex layout grouping labels and progress indicator --}}
            <div class="flex items-center justify-between">
                <div>
                    {{-- Text label indicating card category --}}
                    <p class="text-sm text-gray-500">Current Capacity</p>
                    {{-- Progress and values section --}}
                    <div class="flex items-center gap-3 mt-1">
                        {{-- Large percentage text dynamically colored by capacity utilization --}}
                        <h2 class="text-3xl font-bold {{ $capacity->getUtilizationPercent() > 90 ? 'text-red-600' : ($capacity->getUtilizationPercent() > 70 ? 'text-orange-500' : 'text-teal-600') }}">
                            {{ $capacity->getUtilizationPercent() }}%
                        </h2>
                        {{-- Fraction displaying booked slots versus total capacity --}}
                        <span class="text-xs text-gray-400">{{ $capacity->current_count }}/{{ $capacity->max_capacity }}</span>
                    </div>
                </div>
                {{-- Visual status circle representing the percentage utilization --}}
                <div class="capacity-circle {{ $capacity->getUtilizationPercent() > 90 ? 'bg-red-100 text-red-600' : ($capacity->getUtilizationPercent() > 70 ? 'bg-orange-100 text-orange-500' : 'bg-teal-100 text-teal-600') }}">
                    {{ $capacity->getUtilizationPercent() }}%
                </div>
            </div>
        </div>
    </div>

    {{-- Card for tracking current month's cumulative revenue --}}
    <div class="col-md-6 col-lg-3">
        {{-- Card element styled with white background and green border --}}
        <div class="stat-card bg-white rounded-xl p-5 shadow-sm border-l-4 border-green-500">
            {{-- Flex layout grouping labels and icons --}}
            <div class="flex items-center justify-between">
                <div>
                    {{-- Text label indicating card category --}}
                    <p class="text-sm text-gray-500">Revenue This Month</p>
                    {{-- Large currency metric text --}}
                    <h2 class="text-3xl font-bold text-green-600 mt-1">PHP {{ number_format($monthlyRevenue, 2) }}</h2>
                </div>
                {{-- Decorative icon badge on the right --}}
                <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                    {{-- Peso/Dollar sign currency icon --}}
                    <i class="bi bi-currency-dollar text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Card for active emergencies check --}}
    <div class="col-md-6 col-lg-3">
        {{-- Card element styled with white background and dynamic warning border --}}
        <div class="stat-card bg-white rounded-xl p-5 shadow-sm border-l-4 {{ $activeEmergencies > 0 ? 'border-red-500' : 'border-gray-300' }}">
            {{-- Flex layout grouping labels and icons --}}
            <div class="flex items-center justify-between">
                <div>
                    {{-- Text label indicating card category --}}
                    <p class="text-sm text-gray-500">Active Emergencies</p>
                    {{-- Large metric counter, highlighted in red if > 0 --}}
                    <h2 class="text-3xl font-bold {{ $activeEmergencies > 0 ? 'text-red-600' : 'text-gray-400' }} mt-1">{{ $activeEmergencies }}</h2>
                </div>
                {{-- Decorative alarm badge, highlighted/animated in red if > 0 --}}
                <div class="w-12 h-12 rounded-lg {{ $activeEmergencies > 0 ? 'bg-red-100' : 'bg-gray-100' }} flex items-center justify-center">
                    {{-- Warning alert triangle icon --}}
                    <i class="bi bi-exclamation-triangle {{ $activeEmergencies > 0 ? 'text-red-600' : 'text-gray-400' }} text-2xl {{ $activeEmergencies > 0 ? 'badge-emergency' : '' }}"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Analytics Dashboard Charts Section --}}
<div class="row g-4 mb-6">
    
    {{-- Column for Revenue Trends Line Chart --}}
    <div class="col-lg-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="bi bi-graph-up text-sky-500 me-2"></i>Revenue Trends (6 Months)
            </h3>
            <canvas id="revenueChart" height="220"></canvas>
        </div>
    </div>

    {{-- Column for Occupancy Bar Chart --}}
    <div class="col-lg-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="bi bi-bar-chart text-teal-500 me-2"></i>Occupancy (7 Days)
            </h3>
            <canvas id="occupancyChart" height="220"></canvas>
        </div>
    </div>

    {{-- Column for Popular Packages Doughnut Chart --}}
    <div class="col-lg-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="bi bi-pie-chart text-orange-500 me-2"></i>Popular Packages
            </h3>
            <canvas id="packagesChart" height="220"></canvas>
        </div>
    </div>

    {{-- Column for Sentiment Analysis Pie Chart --}}
    <div class="col-lg-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="bi bi-emoji-smile text-yellow-500 me-2"></i>Guest Sentiment Analysis
            </h3>
            <canvas id="sentimentChart" height="220"></canvas>
        </div>
    </div>
</div>

{{-- Main body row grid --}}
<div class="row g-4">
    
    {{-- Left column carrying live booking feeds --}}
    <div class="col-lg-8">
        {{-- Booking table wrapper card --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            {{-- Header within the booking card --}}
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">
                    <i class="bi bi-broadcast text-sky-500 me-2"></i>Live Booking Feed
                </h3>
                {{-- Small subtext label for feed update indicator --}}
                <span class="text-xs text-gray-400">Auto-updates</span>
            </div>
            {{-- Table container for responsiveness --}}
            <div class="table-responsive">
                {{-- Feed data table styled with Bootstrap --}}
                <table class="table table-hover text-sm" id="bookingFeedTable">
                    {{-- Header columns of the feed --}}
                    <thead class="table-light">
                        <tr>
                            <th>Ref #</th>
                            <th>Guest</th>
                            <th>Package</th>
                            <th>Date</th>
                            <th>Guests</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    {{-- Body block displaying loaded bookings --}}
                    <tbody>
                        {{-- Iterate through recent bookings --}}
                        @foreach($recentBookings as $booking)
                        {{-- Feed row styled with custom tracking class --}}
                        <tr class="booking-row" data-id="{{ $booking->id }}">
                            {{-- Unique reference string cell --}}
                            <td class="font-mono text-sky-600">{{ $booking->reference_no }}</td>
                            {{-- Guest name display cell --}}
                            <td>{{ $booking->user->name }}</td>
                            {{-- Booked package title cell --}}
                            <td>{{ $booking->package->name }}</td>
                            {{-- Booked date calendar text --}}
                            <td>{{ $booking->booking_date->format('M d, Y') }}</td>
                            {{-- Total guest headcount cell --}}
                            <td>{{ $booking->guests_count }}</td>
                            {{-- Status badges containing dynamic color labels --}}
                            <td>
                                @php
                                    // Local map array matching statuses to Bootstrap colors
                                    $colors = ['pending' => 'warning', 'paid' => 'success', 'checked_in' => 'info', 'checked_out' => 'secondary', 'cancelled' => 'danger', 'completed' => 'primary'];
                                @endphp
                                {{-- Styled status badge output --}}
                                <span class="badge bg-{{ $colors[$booking->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Right column carrying sidebar elements --}}
    <div class="col-lg-4">
        
        {{-- Quick action dashboard navigation card --}}
        <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
            {{-- Header card --}}
            <h3 class="text-lg font-bold text-gray-800 mb-3">Quick Actions</h3>
            {{-- Grid stack layout for buttons --}}
            <div class="d-grid gap-2">
                {{-- Check if authenticated user is admin --}}
                @can('role', 'admin')
                {{-- Button directing to packages list creation --}}
                <a href="{{ route('packages.index') }}" class="btn btn-outline-sky btn-sm"><i class="bi bi-plus-circle me-1"></i> Add Package</a>
                {{-- Button directing to user creation view --}}
                <a href="{{ route('users.create') }}" class="btn btn-outline-teal btn-sm"><i class="bi bi-person-plus me-1"></i> Add Staff</a>
                @endcan
                {{-- Button directing to all bookings lists --}}
                <a href="{{ route('bookings.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-calendar me-1"></i> View Bookings</a>
                {{-- Button directing to emergencies dashboard --}}
                <a href="{{ route('emergencies.index') }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-exclamation-triangle me-1"></i> Emergencies</a>
            </div>
        </div>

        {{-- Upcoming reservations card --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            {{-- Header upcoming reservation label --}}
            <h3 class="text-lg font-bold text-gray-800 mb-3">Upcoming Reservations</h3>
            {{-- Forelse loops matching future records --}}
            @forelse($upcomingReservations as $res)
            {{-- Display card flexbox layout for individual guest item --}}
            <div class="flex items-center gap-3 mb-3 p-2 rounded-lg hover:bg-gray-50">
                {{-- Circular icon --}}
                <div class="w-10 h-10 rounded-full bg-sky-100 flex items-center justify-center">
                    <i class="bi bi-person text-sky-600"></i>
                </div>
                {{-- Name and package title area --}}
                <div class="flex-1">
                    <p class="text-sm font-medium">{{ $res->user->name }}</p>
                    <p class="text-xs text-gray-500">{{ $res->package->name }} - {{ $res->booking_date->format('M d') }}</p>
                </div>
                {{-- Small badge indicating booking status --}}
                <span class="badge bg-{{ $res->status === 'paid' ? 'success' : 'warning' }}">{{ ucfirst($res->status) }}</span>
            </div>
            {{-- Alternative block shown if collection list is empty --}}
            @empty
            <p class="text-gray-400 text-sm">No upcoming reservations</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

{{-- Push additional chart scripts to base app layouts --}}
@push('scripts')
<script>
// Load occupancy data labels from controller passed array
const occupancyLabels = {{ Js::from($occupancyData->pluck('date')) }};
// Load occupancy utilization data numbers
const occupancyValues = {{ Js::from($occupancyData->pluck('utilization')) }};

// Render the 7-day occupancy statistics chart
const occupancyCtx = document.getElementById('occupancyChart').getContext('2d');
new Chart(occupancyCtx, {
    type: 'bar',
    data: {
        labels: occupancyLabels,
        datasets: [{
            label: 'Occupancy %',
            data: occupancyValues,
            backgroundColor: 'rgba(20, 184, 166, 0.6)',
            borderColor: 'rgb(20, 184, 166)',
            borderWidth: 1,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } }
        }
    }
});

// Load monthly revenue trend labels
const revenueLabels = {{ Js::from(collect($revenueTrends)->pluck('month')) }};
// Load monthly revenue totals
const revenueValues = {{ Js::from(collect($revenueTrends)->pluck('total')) }};

// Render the 6-month revenue trends chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: revenueLabels,
        datasets: [{
            label: 'Revenue (PHP)',
            data: revenueValues,
            backgroundColor: 'rgba(14, 165, 233, 0.1)',
            borderColor: 'rgb(14, 165, 233)',
            borderWidth: 2,
            tension: 0.3,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Load popular package names
const packageLabels = {{ Js::from($popularPackages->pluck('name')) }};
// Load popular package booking counts
const packageValues = {{ Js::from($popularPackages->pluck('count')) }};

// Render the popular packages doughnut chart
const packagesCtx = document.getElementById('packagesChart').getContext('2d');
new Chart(packagesCtx, {
    type: 'doughnut',
    data: {
        labels: packageLabels,
        datasets: [{
            data: packageValues,
            backgroundColor: [
                '#0ea5e9',
                '#14b8a6',
                '#f59e0b',
                '#ef4444',
                '#8b5cf6'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Load sentiment count data
const sentimentData = {{ Js::from($sentimentData) }};

// Render review sentiment pie chart
const sentimentCtx = document.getElementById('sentimentChart').getContext('2d');
new Chart(sentimentCtx, {
    type: 'pie',
    data: {
        labels: ['Positive (4-5 Stars)', 'Neutral (3 Stars)', 'Negative (1-2 Stars)'],
        datasets: [{
            data: [sentimentData.positive, sentimentData.neutral, sentimentData.negative],
            backgroundColor: [
                '#10b981',
                '#f59e0b',
                '#ef4444'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Real-time booking updates via Pusher connections
const bookingPusher = new Pusher('{{ config("broadcasting.connections.pusher.key", "talisay-key") }}', {
    cluster: '{{ config("broadcasting.connections.pusher.options.cluster", "mt1") }}',
    wsHost: '{{ config("broadcasting.connections.pusher.options.host", "127.0.0.1") }}',
    wsPort: {{ config("broadcasting.connections.pusher.options.port", 6001) }},
    forceTLS: false,
    disableStats: true,
});

// Subscribe to staff-bookings channel
const bookingChannel = bookingPusher.subscribe('staff-bookings');
bookingChannel.bind('App\\Events\\BookingCreated', function(data) {
    // Get table body DOM element
    const tbody = document.querySelector('#bookingFeedTable tbody');
    // Define bootstrap status class mappings
    const statusMap = { pending: 'warning', paid: 'success', checked_in: 'info', cancelled: 'danger', completed: 'primary' };
    // Create new table row
    const row = document.createElement('tr');
    // Add styling classes
    row.className = 'booking-row';
    row.dataset.id = data.id;
    row.style.animation = 'fadeIn 0.5s ease';
    // Fill dynamic details into the row cells
    row.innerHTML = `
        <td class="font-mono text-sky-600">${data.reference_no}</td>
        <td>${data.guest_name}</td>
        <td>${data.package_name}</td>
        <td>${data.booking_date}</td>
        <td>${data.guests_count}</td>
        <td><span class="badge bg-${statusMap[data.status] || 'secondary'}">${data.status.charAt(0).toUpperCase() + data.status.slice(1)}</span></td>
    `;
    // Insert the row at the top of the booking feed list
    tbody.insertBefore(row, tbody.firstChild);
});
</script>
@endpush
