{{-- Extends the primary layout template designed for dashboard panels --}}
@extends('layouts.app')

{{-- Sets the HTML document title for the bookings list view page --}}
@section('title', 'Bookings - Talisay Smart Tourism')

{{-- Defines the main content area section of the template --}}
@section('content')

{{-- Header layout block containing page title and subtitle info --}}
<div class="mb-6 flex items-center justify-between">
    <div>
        {{-- Principal title heading for bookings management --}}
        <h1 class="text-2xl font-bold text-gray-800">Bookings Management</h1>
        {{-- Small help description text --}}
        <p class="text-gray-500 text-sm">View and manage guest reservations and visits</p>
    </div>
</div>

{{-- Filters card container --}}
<div class="bg-white rounded-xl shadow-sm p-4 mb-4">
    {{-- Form element handling inputs search filters --}}
    <form method="GET" action="{{ route('bookings.index') }}" id="filterForm">
        {{-- Grid spacing for inputs --}}
        <div class="row g-3">
            {{-- Status query parameter selector dropdown --}}
            <div class="col-md-4">
                <label class="form-label text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="checked_in" {{ request('status') === 'checked_in' ? 'selected' : '' }}>Checked In</option>
                    <option value="checked_out" {{ request('status') === 'checked_out' ? 'selected' : '' }}>Checked Out</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            {{-- Text input query parameter filtering search tags --}}
            <div class="col-md-8">
                <label class="form-label text-xs font-semibold text-gray-500 uppercase tracking-wider">Search</label>
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search reference number or guest name..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
                    @if(request('status') || request('search'))
                    <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Clear</a>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>

{{-- List View containing tables --}}
<div id="listView">
    {{-- Card element storing records --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="table-responsive">
            {{-- Dynamic list table styled with Bootstrap --}}
            <table class="table table-hover text-sm mb-0">
                {{-- Header columns --}}
                <thead class="table-light">
                    <tr>
                        <th>Reference #</th>
                        <th>Guest</th>
                        <th>Package</th>
                        <th>Date</th>
                        <th>Guests</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                {{-- Table rows insertion point --}}
                <tbody>
                    {{-- Loop through each booking record --}}
                    @foreach($bookings as $booking)
                    <tr class="booking-row" data-id="{{ $booking->id }}">
                        {{-- Booking unique reference cell --}}
                        <td class="font-mono text-sky-600">{{ $booking->reference_no }}</td>
                        {{-- Guest user details --}}
                        <td>{{ $booking->user->name ?? 'Guest User' }}</td>
                        {{-- Package title cell --}}
                        <td>{{ $booking->package->name ?? 'Package Title' }}</td>
                        {{-- Scheduled date text --}}
                        <td>{{ $booking->booking_date->format('M d, Y') }}</td>
                        {{-- Guest headcount count --}}
                        <td>{{ $booking->guests_count }} pax</td>
                        {{-- Calculated price cells --}}
                        <td class="font-semibold">PHP {{ number_format($booking->total_amount, 2) }}</td>
                        {{-- Colorful badge indicators for status --}}
                        <td>
                            @php
                                $colors = ['pending' => 'warning', 'paid' => 'success', 'checked_in' => 'info', 'checked_out' => 'secondary', 'cancelled' => 'danger', 'completed' => 'primary'];
                            @endphp
                            <span class="badge bg-{{ $colors[$booking->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
                        </td>
                        {{-- Action buttons cell --}}
                        <td>
                            {{-- View details action button triggering JS populator --}}
                            <button class="btn btn-sm btn-outline-sky" 
                                    data-id="{{ $booking->id }}"
                                    data-ref="{{ $booking->reference_no }}"
                                    data-name="{{ $booking->user->name ?? 'Guest User' }}"
                                    data-phone="{{ $booking->user->phone ?? 'N/A' }}"
                                    data-email="{{ $booking->user->email ?? 'N/A' }}"
                                    data-pkg="{{ $booking->package->name ?? 'Package Title' }}"
                                    data-date="{{ $booking->booking_date->format('Y-m-d') }}"
                                    data-guests="{{ $booking->guests_count }}"
                                    data-amount="{{ number_format($booking->total_amount, 2) }}"
                                    data-status="{{ $booking->status }}"
                                    data-requests="{{ $booking->special_requests ?? 'None' }}"
                                    data-reason="{{ $booking->cancellation_reason ?? 'None' }}"
                                    onclick="viewBooking(this)">
                                <i class="bi bi-eye"></i> View Details
                            </button>
                        </td>
                    </tr>
                    @endforeach
                    {{-- Empty block indicator if no records match --}}
                    @if($bookings->isEmpty())
                    <tr>
                        <td colspan="8" class="text-center text-gray-400 py-8">No bookings found matching filters.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        {{-- Paginator links render area --}}
        <div class="p-4">{{ $bookings->links() }}</div>
    </div>
</div>

{{-- Detail Modal --}}
<div class="modal fade" id="bookingDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            {{-- Header card --}}
            <div class="modal-header bg-sky-500 text-white">
                <h5 class="modal-title">Booking Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{-- Detail display panel --}}
            <div class="modal-body">
                <div class="row g-3" id="bookingDetailsContent">
                    {{-- Populated dynamically via javascript --}}
                </div>
            </div>
            {{-- Footer controls --}}
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Script push section --}}
@push('scripts')
<script>
// JavaScript function displaying dynamic details modal contents
function viewBooking(button) {
    // Retrieve attributes mapped on selected list button item
    const id = button.getAttribute('data-id');
    const ref = button.getAttribute('data-ref');
    const name = button.getAttribute('data-name');
    const phone = button.getAttribute('data-phone');
    const email = button.getAttribute('data-email');
    const pkg = button.getAttribute('data-pkg');
    const date = button.getAttribute('data-date');
    const guests = button.getAttribute('data-guests');
    const amount = button.getAttribute('data-amount');
    const status = button.getAttribute('data-status');
    const requests = button.getAttribute('data-requests');
    const reason = button.getAttribute('data-reason');

    // Build raw dynamic modal structure output
    document.getElementById('bookingDetailsContent').innerHTML = `
        <div class="col-md-6"><strong>Reference Number:</strong> <span class="font-mono text-sky-600">${ref}</span></div>
        <div class="col-md-6"><strong>Guest Name:</strong> ${name}</div>
        <div class="col-md-6"><strong>Email:</strong> ${email}</div>
        <div class="col-md-6"><strong>Phone:</strong> ${phone}</div>
        <div class="col-md-6"><strong>Package:</strong> ${pkg}</div>
        <div class="col-md-6"><strong>Visit Date:</strong> ${date}</div>
        <div class="col-md-6"><strong>Guest count:</strong> ${guests} pax</div>
        <div class="col-md-6"><strong>Total Cost:</strong> PHP ${amount}</div>
        <div class="col-md-6"><strong>Current Status:</strong> <span class="badge bg-secondary">${status.toUpperCase()}</span></div>
        <div class="col-12 mt-2"><strong>Special Requests:</strong><p class="bg-light p-2 rounded text-muted">${requests}</p></div>
        ${status === 'cancelled' ? `<div class="col-12"><strong>Cancellation Reason:</strong><p class="bg-light p-2 rounded text-danger">${reason}</p></div>` : ''}
        
        <form method="POST" action="/bookings/${id}/status" class="mt-4 border-t pt-3 w-full">
            <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
            <input type="hidden" name="_method" value="PATCH">
            <div class="row align-items-end g-3">
                <div class="col-md-6">
                    <label class="form-label text-sm font-semibold">Transition Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="toggleCancelField(this, ${id})">
                        <option value="pending" ${status === 'pending' ? 'selected' : ''}>Pending</option>
                        <option value="paid" ${status === 'paid' ? 'selected' : ''}>Paid</option>
                        <option value="checked_in" ${status === 'checked_in' ? 'selected' : ''}>Checked In</option>
                        <option value="checked_out" ${status === 'checked_out' ? 'selected' : ''}>Checked Out</option>
                        <option value="completed" ${status === 'completed' ? 'selected' : ''}>Completed</option>
                        <option value="cancelled" ${status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-6" id="cancelReasonWrapper_${id}" style="display:none;">
                    <label class="form-label text-sm font-semibold text-danger">Cancellation Reason</label>
                    <input type="text" name="cancellation_reason" class="form-control form-control-sm" placeholder="Why is this booking cancelled?">
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-save"></i> Save Updates</button>
                </div>
            </div>
        </form>
    `;

    // Show booking details modal
    const modalObj = new bootstrap.Modal(document.getElementById('bookingDetailModal'));
    modalObj.show();
}

// Toggle showing cancellation input field based on selected options
function toggleCancelField(select, id) {
    const div = document.getElementById(`cancelReasonWrapper_${id}`);
    div.style.display = select.value === 'cancelled' ? 'block' : 'none';
}
</script>
@endpush