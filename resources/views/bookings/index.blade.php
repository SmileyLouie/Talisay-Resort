{{-- Extends the primary layout template designed for dashboard panels --}}
@extends('layouts.app')

{{-- Sets the HTML document title for the bookings list view page --}}
@section('title', 'Bookings - Talisay Smart Tourism')

{{-- Defines the main content area section of the template --}}
@section('content')

{{-- Header layout block --}}
<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100/80 text-sky-800 border border-sky-200">
                <i class="bi bi-calendar-check-fill text-[10px] text-sky-600"></i>
                Reservations
            </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-1">
            Bookings Management
        </h1>
        <p class="text-sm text-slate-500 mb-0">
            View, track, and manage guest stays, front-desk walk-in reservations, and exclusive bookings.
        </p>
    </div>

    <div class="flex items-center gap-3">
        <button type="button" class="btn-ocean text-xs flex items-center gap-1.5 shadow-sm" data-bs-toggle="modal" data-bs-target="#manualBookingModal">
            <i class="bi bi-pencil-square"></i>
            <span>+ Manual Booking</span>
        </button>
        <span class="text-xs font-semibold px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 shadow-sm">
            Total Records: <strong class="text-slate-900">{{ $bookings->total() }}</strong>
        </span>
    </div>
</div>

{{-- Validation Errors Alert --}}
@if(isset($errors) && $errors->any())
<div class="mb-6 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3.5 rounded-2xl shadow-sm">
    <div class="flex items-start gap-3">
        <i class="bi bi-exclamation-triangle-fill text-rose-500 text-lg flex-shrink-0 mt-0.5"></i>
        <div>
            <span class="text-sm font-bold block mb-1">Please correct the following errors:</span>
            <ul class="text-xs list-disc list-inside space-y-0.5 mb-0 font-medium text-rose-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif

{{-- Filters Card --}}
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 sm:p-5 mb-6">
    <form method="GET" action="{{ route('bookings.index') }}" id="filterForm">
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            
            {{-- Status Filter --}}
            <div class="sm:col-span-4">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">
                    Filter by Status
                </label>
                <select name="status" class="w-full form-select-clean text-xs font-medium" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Verification</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid / Confirmed</option>
                    <option value="checked_in" {{ request('status') === 'checked_in' ? 'selected' : '' }}>Checked In</option>
                    <option value="checked_out" {{ request('status') === 'checked_out' ? 'selected' : '' }}>Checked Out</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            {{-- Search Bar --}}
            <div class="sm:col-span-8">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">
                    Search Reservations
                </label>
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="search" class="w-full pl-9 form-control-clean text-xs" placeholder="Search reference number, guest name, contact, or email..." value="{{ request('search') }}">
                    </div>
                    <button type="submit" class="btn-ocean text-xs">
                        <i class="bi bi-search"></i>
                        <span>Search</span>
                    </button>
                    @if(request('status') || request('search'))
                    <a href="{{ route('bookings.index') }}" class="btn-secondary-clean text-xs">
                        <i class="bi bi-x-circle text-slate-400"></i>
                        <span>Clear</span>
                    </a>
                    @endif
                </div>
            </div>

        </div>
    </form>
</div>

{{-- Bookings Table Card --}}
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table-clean">
            <thead>
                <tr>
                    <th>Ref # &amp; Source</th>
                    <th>Guest Details</th>
                    <th>Room &amp; Cottage</th>
                    <th>Scheduled Stay</th>
                    <th>Pax</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $booking)
                <tr class="booking-row" data-id="{{ $booking->id }}">
                    {{-- Ref # & Source --}}
                    <td>
                        <span class="font-mono text-xs font-extrabold text-sky-600 bg-sky-50 px-2.5 py-1 rounded-md border border-sky-200/70 inline-block">
                            {{ $booking->reference_no }}
                        </span>
                        <div class="mt-1 flex flex-wrap items-center gap-1">
                            @if($booking->isManual())
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-800 bg-amber-100/90 border border-amber-300 px-1.5 py-0.5 rounded uppercase">
                                    <i class="bi bi-pencil-square text-amber-600"></i> Manual
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-sky-800 bg-sky-50 border border-sky-200 px-1.5 py-0.5 rounded uppercase">
                                    <i class="bi bi-globe2 text-sky-600"></i> Online
                                </span>
                            @endif

                            @if($booking->booking_type === 'special_resort')
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-purple-800 bg-purple-100/90 border border-purple-300 px-1.5 py-0.5 rounded uppercase">
                                    <i class="bi bi-stars text-purple-600"></i> Full-Resort
                                </span>
                            @endif
                        </div>
                    </td>

                    {{-- Guest --}}
                    <td>
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg {{ $booking->isManual() ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-slate-100 text-slate-700 border-slate-200' }} font-bold flex items-center justify-center text-xs flex-shrink-0 border">
                                {{ strtoupper(substr($booking->guest_name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-slate-900 text-xs flex items-center gap-1.5">
                                    <span>{{ $booking->guest_name }}</span>
                                    @if($booking->isManual() && !$booking->user_id)
                                        <span class="text-[10px] font-medium text-amber-600 bg-amber-50 px-1 rounded border border-amber-200/60">Walk-in</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-400 truncate">{{ $booking->guest_contact }}</div>
                            </div>
                        </div>
                    </td>

                    {{-- Accommodation --}}
                    <td>
                        <span class="font-semibold text-slate-800 text-xs">
                            {{ $booking->booking_type === 'special_resort' ? 'All 20 Rooms & Cottages (Full Resort)' : ($booking->accommodationUnit->unit_number ?? 'Room / Cottage') }}
                        </span>
                        @if($booking->accommodationUnit)
                            <div class="text-[11px] text-slate-400">{{ ucfirst(str_replace('_', ' ', $booking->accommodationUnit->type ?? '')) }}</div>
                        @endif
                        @if($booking->modified_at)
                        <div class="text-[10px] text-amber-600 flex items-center gap-1 font-semibold mt-0.5">
                            <i class="bi bi-arrow-repeat"></i> Modified stay
                        </div>
                        @endif
                    </td>

                    {{-- Dates --}}
                    <td>
                        <div class="text-xs font-semibold text-slate-700">
                            @if($booking->check_in_date && $booking->check_out_date && $booking->check_in_date->ne($booking->check_out_date))
                                {{ $booking->check_in_date->format('M d') }} - {{ $booking->check_out_date->format('M d, Y') }}
                                <span class="text-[10px] text-slate-400 font-normal">({{ $booking->nights_count }}n)</span>
                            @else
                                {{ $booking->booking_date->format('M d, Y') }}
                            @endif
                        </div>
                    </td>

                    {{-- Pax --}}
                    <td>
                        <span class="text-xs font-semibold text-slate-700">{{ $booking->guests_count }} pax</span>
                    </td>

                    {{-- Amount --}}
                    <td>
                        <span class="text-xs font-extrabold text-slate-900">₱{{ number_format($booking->total_amount, 2) }}</span>
                    </td>

                    {{-- Status Badge --}}
                    <td>
                        @php
                            $statusStyles = [
                                'pending'     => 'bg-amber-50 text-amber-700 border-amber-200',
                                'paid'        => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'checked_in'  => 'bg-sky-50 text-sky-700 border-sky-200',
                                'checked_out' => 'bg-slate-100 text-slate-700 border-slate-200',
                                'completed'   => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                'cancelled'   => 'bg-rose-50 text-rose-700 border-rose-200',
                            ];
                        @endphp
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold border inline-flex items-center gap-1 {{ $statusStyles[$booking->status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                            {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                        </span>
                        @if($booking->booking_type === 'special_resort' && $booking->admin_approval_status === 'pending')
                        <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200">
                            Approval Needed
                        </span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="text-end">
                        <button class="btn-secondary-clean text-xs py-1.5 px-2.5" 
                                data-id="{{ $booking->id }}"
                                data-ref="{{ $booking->reference_no }}"
                                data-source="{{ $booking->booking_source ?? 'online' }}"
                                data-type="{{ $booking->booking_type }}"
                                data-approval="{{ $booking->admin_approval_status }}"
                                data-name="{{ $booking->guest_name }}"
                                data-phone="{{ $booking->guest_contact }}"
                                data-email="{{ $booking->user->email ?? 'N/A' }}"
                                data-unit="{{ $booking->booking_type === 'special_resort' ? 'Exclusive Full-Resort Booking (All 20 Units)' : ($booking->accommodationUnit->unit_number ?? 'Accommodation Unit') }}"
                                data-date="{{ $booking->booking_date->format('Y-m-d') }}"
                                data-checkin="{{ $booking->check_in_date ? $booking->check_in_date->format('Y-m-d') : '' }}"
                                data-checkout="{{ $booking->check_out_date ? $booking->check_out_date->format('Y-m-d') : '' }}"
                                data-nights="{{ $booking->nights_count ?? 1 }}"
                                data-guests="{{ $booking->guests_count }}"
                                data-amount="{{ number_format($booking->total_amount, 2) }}"
                                data-status="{{ $booking->status }}"
                                data-requests="{{ $booking->special_requests ?? 'None' }}"
                                data-reason="{{ $booking->cancellation_reason ?? 'None' }}"
                                onclick="viewBooking(this)">
                            <i class="bi bi-eye text-slate-500"></i>
                            <span>Details</span>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-12 text-slate-400">
                        <i class="bi bi-calendar-x text-3xl block mb-2 text-slate-300"></i>
                        <p class="text-xs font-semibold mb-0">No booking records match your criteria.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($bookings->hasPages())
    <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex justify-end">
        {{ $bookings->links() }}
    </div>
    @endif
</div>

{{-- Create Manual Booking Modal --}}
<div class="modal fade" id="manualBookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden">
            {{-- Header --}}
            <div class="p-5 bg-gradient-to-r from-slate-900 via-sky-950 to-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-amber-300 text-lg">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div>
                        <h5 class="font-extrabold text-base mb-0 tracking-tight">Create Manual Reservation</h5>
                        <p class="text-xs text-sky-200/70 mb-0 font-medium">Create a stay on behalf of walk-in or phone-in guests</p>
                    </div>
                </div>
                <button type="button" class="text-white/70 hover:text-white w-8 h-8 rounded-lg flex items-center justify-center hover:bg-white/10 transition" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            {{-- Form Body --}}
            <form method="POST" action="{{ route('bookings.manual-store') }}" id="manualBookingForm">
                @csrf
                <div class="modal-body p-6 bg-slate-50/60 max-h-[75vh] overflow-y-auto space-y-6">

                    {{-- Guest Type Selector --}}
                    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2.5">
                            Guest Account Type
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:border-sky-500 cursor-pointer transition has-[:checked]:border-sky-600 has-[:checked]:bg-sky-50/60">
                                <input type="radio" name="guest_type" value="walk_in" class="text-sky-600 focus:ring-sky-500" checked onchange="toggleGuestTypeFields()">
                                <div>
                                    <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                        <i class="bi bi-person-walking text-sky-600"></i> Walk-in / Phone-in Guest
                                    </div>
                                    <div class="text-[11px] text-slate-400">No registered account required</div>
                                </div>
                            </label>

                            <label class="relative flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:border-sky-500 cursor-pointer transition has-[:checked]:border-sky-600 has-[:checked]:bg-sky-50/60">
                                <input type="radio" name="guest_type" value="registered" class="text-sky-600 focus:ring-sky-500" onchange="toggleGuestTypeFields()">
                                <div>
                                    <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                        <i class="bi bi-person-check-fill text-emerald-600"></i> Registered Tourist
                                    </div>
                                    <div class="text-[11px] text-slate-400">Link to existing user portal</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Walk-in Guest Fields --}}
                    <div id="walkInFields" class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
                        <h6 class="font-bold text-xs uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5">
                            <i class="bi bi-person-badge text-slate-500"></i> Walk-in Guest Information
                        </h6>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Guest Full Name <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="guest_name" id="guestNameInput" class="w-full form-control-clean text-xs" placeholder="Guest full name" value="{{ old('guest_name') }}">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Contact Number / Email <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="guest_contact" id="guestContactInput" class="w-full form-control-clean text-xs" placeholder="Mobile number or email address" value="{{ old('guest_contact') }}">
                            </div>
                        </div>
                    </div>

                    {{-- Registered Tourist Dropdown --}}
                    <div id="registeredFields" class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm" style="display: none;">
                        <h6 class="font-bold text-xs uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5">
                            <i class="bi bi-person-check text-emerald-600"></i> Select Tourist Account
                        </h6>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Registered User <span class="text-rose-500">*</span>
                            </label>
                            <select name="user_id" id="registeredUserSelect" class="w-full form-select-clean text-xs font-medium">
                                <option value="">-- Choose a Registered Tourist --</option>
                                @foreach($tourists as $tourist)
                                    <option value="{{ $tourist->id }}" {{ old('user_id') == $tourist->id ? 'selected' : '' }}>
                                        {{ $tourist->name }} ({{ $tourist->email }}{{ $tourist->phone ? ' · ' . $tourist->phone : '' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Accommodation & Dates --}}
                    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                        <h6 class="font-bold text-xs uppercase tracking-wider text-slate-400 mb-2 flex items-center gap-1.5">
                            <i class="bi bi-door-open-fill text-sky-600"></i> Stay &amp; Unit Selection
                        </h6>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Room &amp; Cottage <span class="text-rose-500">*</span>
                            </label>
                            <select name="accommodation_unit_id" id="manualUnitSelect" class="w-full form-select-clean text-xs font-medium" required onchange="calculateManualTotal()">
                                <option value="">-- Select Room or Cottage --</option>
                                @foreach($accommodationUnits as $unit)
                                    <option value="{{ $unit->id }}" 
                                            data-price="{{ $unit->price_per_night }}" 
                                            data-capacity="{{ $unit->max_occupancy }}"
                                            data-type="{{ $unit->type }}"
                                            {{ old('accommodation_unit_id') == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->unit_number }} ({{ ucfirst(str_replace('_', ' ', $unit->type)) }}) — ₱{{ number_format($unit->price_per_night, 2) }}/night · Max {{ $unit->max_occupancy }} Pax
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Check-in Date <span class="text-rose-500">*</span>
                                </label>
                                <input type="date" name="booking_date" id="manualCheckInDate" 
                                       class="w-full form-control-clean text-xs" 
                                       value="{{ old('booking_date', now()->format('Y-m-d')) }}" 
                                       required onchange="syncCheckoutMin(); calculateManualTotal();">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Check-out Date <span class="text-rose-500">*</span>
                                </label>
                                <input type="date" name="check_out_date" id="manualCheckOutDate" 
                                       class="w-full form-control-clean text-xs" 
                                       value="{{ old('check_out_date', now()->addDay()->format('Y-m-d')) }}" 
                                       required onchange="calculateManualTotal();">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Number of Guests <span class="text-rose-500">*</span>
                                </label>
                                <input type="number" name="guests_count" id="manualGuestsCount" 
                                       class="w-full form-control-clean text-xs" 
                                       min="1" max="50" 
                                       value="{{ old('guests_count', 1) }}" 
                                       required>
                                <span class="text-[10px] text-slate-400 mt-0.5 block" id="unitCapacityHint">Select a unit to see capacity</span>
                            </div>
                        </div>
                    </div>

                    {{-- Payment & Lifecycle Settings --}}
                    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                        <h6 class="font-bold text-xs uppercase tracking-wider text-slate-400 mb-2 flex items-center gap-1.5">
                            <i class="bi bi-cash-stack text-emerald-600"></i> Payment &amp; Arrival Status
                        </h6>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Payment Method <span class="text-rose-500">*</span>
                                </label>
                                <select name="payment_method" class="w-full form-select-clean text-xs font-medium" required>
                                    <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash at Front Desk</option>
                                    <option value="gcash" {{ old('payment_method') === 'gcash' ? 'selected' : '' }}>GCash (Over Counter / QR)</option>
                                    <option value="card" {{ old('payment_method') === 'card' ? 'selected' : '' }}>Credit / Debit Card (POS)</option>
                                    <option value="paypal" {{ old('payment_method') === 'paypal' ? 'selected' : '' }}>PayPal</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Initial Status <span class="text-rose-500">*</span>
                                </label>
                                <select name="initial_status" class="w-full form-select-clean text-xs font-medium" required>
                                    <option value="paid" {{ old('initial_status', 'paid') === 'paid' ? 'selected' : '' }}>Paid / Confirmed (Ready)</option>
                                    <option value="checked_in" {{ old('initial_status') === 'checked_in' ? 'selected' : '' }}>Checked In Immediately</option>
                                    <option value="pending" {{ old('initial_status') === 'pending' ? 'selected' : '' }}>Pending (Payment on Arrival)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Special Requests / Front Desk Notes <span class="text-slate-400 text-[11px] font-normal">(Optional)</span>
                            </label>
                            <textarea name="special_requests" rows="2" class="w-full form-control-clean text-xs" placeholder="Add any special guest preferences, early check-in requests, or front desk reference notes...">{{ old('special_requests') }}</textarea>
                        </div>
                    </div>

                    {{-- Live Calculation Summary Box --}}
                    <div class="bg-gradient-to-br from-sky-50 to-indigo-50/60 p-4 rounded-2xl border border-sky-200/80 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div>
                            <span class="text-xs font-bold text-sky-900 block mb-0.5">Booking Cost Estimate</span>
                            <div class="text-xs text-sky-700 flex items-center gap-3">
                                <span>Rate: <strong id="calcRate">₱0.00</strong>/night</span>
                                <span>·</span>
                                <span>Duration: <strong id="calcNights">1 night</strong></span>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Estimated Total</span>
                            <span class="text-xl font-extrabold text-sky-700" id="calcTotal">₱0.00</span>
                        </div>
                    </div>

                </div>

                {{-- Modal Footer --}}
                <div class="p-4 bg-white border-t border-slate-100 flex items-center justify-between">
                    <button type="button" class="btn-secondary-clean text-xs px-4 py-2" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn-ocean text-xs px-5 py-2 flex items-center gap-1.5 shadow-md">
                        <i class="bi bi-check2-circle"></i>
                        <span>Confirm &amp; Create Booking</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modern Booking Details Modal --}}
<div class="modal fade" id="bookingDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden">
            <div class="p-5 bg-gradient-to-r from-slate-900 to-sky-950 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-sky-300 text-lg">
                        <i class="bi bi-calendar2-check-fill"></i>
                    </div>
                    <div>
                        <h5 class="font-extrabold text-base mb-0 tracking-tight">Booking Details &amp; Lifecycle</h5>
                        <p class="text-xs text-sky-200/70 mb-0 font-medium">Review reservation details, manage stay lifecycle, or approve special requests</p>
                    </div>
                </div>
                <button type="button" class="text-white/70 hover:text-white w-8 h-8 rounded-lg flex items-center justify-center hover:bg-white/10 transition" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="modal-body p-6 bg-slate-50/50">
                <div class="row g-4" id="bookingDetailsContent">
                    {{-- Dynamically populated via JavaScript --}}
                </div>
            </div>

            <div class="p-4 bg-white border-t border-slate-100 flex justify-end">
                <button type="button" class="btn-secondary-clean text-xs px-4 py-2" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function toggleGuestTypeFields() {
    const selected = document.querySelector('input[name="guest_type"]:checked')?.value;
    const walkInFields = document.getElementById('walkInFields');
    const registeredFields = document.getElementById('registeredFields');
    const nameInput = document.getElementById('guestNameInput');
    const contactInput = document.getElementById('guestContactInput');
    const userSelect = document.getElementById('registeredUserSelect');

    if (selected === 'walk_in') {
        walkInFields.style.display = 'block';
        registeredFields.style.display = 'none';
        nameInput.setAttribute('required', 'required');
        contactInput.setAttribute('required', 'required');
        userSelect.removeAttribute('required');
    } else {
        walkInFields.style.display = 'none';
        registeredFields.style.display = 'block';
        nameInput.removeAttribute('required');
        contactInput.removeAttribute('required');
        userSelect.setAttribute('required', 'required');
    }
}

function syncCheckoutMin() {
    const cin = document.getElementById('manualCheckInDate').value;
    const coutInput = document.getElementById('manualCheckOutDate');
    if (cin) {
        const nextDay = new Date(cin);
        nextDay.setDate(nextDay.getDate() + 1);
        const nextDayStr = nextDay.toISOString().split('T')[0];
        coutInput.min = nextDayStr;
        if (!coutInput.value || coutInput.value <= cin) {
            coutInput.value = nextDayStr;
        }
    }
}

function calculateManualTotal() {
    const unitSelect = document.getElementById('manualUnitSelect');
    const selectedOption = unitSelect.options[unitSelect.selectedIndex];
    const price = selectedOption ? parseFloat(selectedOption.getAttribute('data-price') || 0) : 0;
    const capacity = selectedOption ? parseInt(selectedOption.getAttribute('data-capacity') || 1) : 1;

    const cinVal = document.getElementById('manualCheckInDate').value;
    const coutVal = document.getElementById('manualCheckOutDate').value;

    const guestsInput = document.getElementById('manualGuestsCount');
    const hint = document.getElementById('unitCapacityHint');
    if (selectedOption && selectedOption.value) {
        hint.textContent = `Capacity limit: Max ${capacity} pax for this unit.`;
        guestsInput.max = capacity;
    } else {
        hint.textContent = 'Select a unit to see capacity.';
    }

    let nights = 1;
    if (cinVal && coutVal) {
        const d1 = new Date(cinVal);
        const d2 = new Date(coutVal);
        const diffTime = d2 - d1;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        nights = diffDays > 0 ? diffDays : 1;
    }

    const total = price * nights;
    document.getElementById('calcRate').textContent = `₱${price.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    document.getElementById('calcNights').textContent = `${nights} night${nights > 1 ? 's' : ''}`;
    document.getElementById('calcTotal').textContent = `₱${total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
}

// Initialize listeners on DOM ready
document.addEventListener('DOMContentLoaded', function () {
    syncCheckoutMin();
    calculateManualTotal();
    toggleGuestTypeFields();
});

function viewBooking(button) {
    const id = button.getAttribute('data-id');
    const ref = button.getAttribute('data-ref');
    const source = button.getAttribute('data-source') || 'online';
    const type = button.getAttribute('data-type');
    const approval = button.getAttribute('data-approval');
    const name = button.getAttribute('data-name');
    const phone = button.getAttribute('data-phone');
    const email = button.getAttribute('data-email');
    const unit = button.getAttribute('data-unit');
    const date = button.getAttribute('data-date');
    const checkin = button.getAttribute('data-checkin');
    const checkout = button.getAttribute('data-checkout');
    const nights = button.getAttribute('data-nights');
    const guests = button.getAttribute('data-guests');
    const amount = button.getAttribute('data-amount');
    const status = button.getAttribute('data-status');
    const requests = button.getAttribute('data-requests');
    const reason = button.getAttribute('data-reason');

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    const sourceBadge = source === 'manual'
        ? '<span class="inline-flex items-center gap-1 text-xs font-bold text-amber-800 bg-amber-100 px-2.5 py-0.5 rounded-full border border-amber-300"><i class="bi bi-pencil-square text-amber-600"></i> Staff / Front Desk (Manual)</span>'
        : '<span class="inline-flex items-center gap-1 text-xs font-bold text-sky-800 bg-sky-100 px-2.5 py-0.5 rounded-full border border-sky-300"><i class="bi bi-globe2 text-sky-600"></i> Online Self-Service</span>';

    let specialApprovalHtml = '';
    if (type === 'special_resort') {
        if (approval === 'pending') {
            specialApprovalHtml = `
                <div class="col-12 bg-amber-50 border border-amber-200 rounded-2xl p-4 mt-2 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <strong class="text-slate-900 d-block text-sm">
                                <i class="bi bi-exclamation-diamond-fill text-amber-500 me-1"></i> Special Full-Resort Request Awaiting Administrator Approval
                            </strong>
                            <p class="text-xs text-slate-500 mb-0 mt-0.5">Approving will lock all 20 units and confirm this exclusive full-resort booking.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <form method="POST" action="/bookings/${id}/approve-special" class="d-inline">
                                <input type="hidden" name="_token" value="${csrfToken}">
                                <button type="submit" class="btn-ocean text-xs py-1.5 px-3">
                                    <i class="bi bi-check-circle-fill"></i> Approve Full Lock
                                </button>
                            </form>
                            <form method="POST" action="/bookings/${id}/reject-special" class="d-inline" onsubmit="return confirm('Decline this special booking?');">
                                <input type="hidden" name="_token" value="${csrfToken}">
                                <button type="submit" class="btn btn-outline-danger btn-sm text-xs font-bold rounded-xl py-1.5 px-3">
                                    <i class="bi bi-x-circle"></i> Decline
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            `;
        } else if (approval === 'approved') {
            specialApprovalHtml = `
                <div class="col-12 bg-emerald-50 border border-emerald-200 rounded-2xl p-3 text-emerald-800 font-bold text-xs mt-2 flex items-center gap-2">
                    <i class="bi bi-check-circle-fill text-emerald-600 text-sm"></i> Approved Full-Resort Exclusive Reservation
                </div>
            `;
        }
    }

    document.getElementById('bookingDetailsContent').innerHTML = `
        <div class="col-md-6">
            <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Booking Reference</span>
                <span class="font-mono text-sky-600 font-extrabold text-sm">${ref}</span>
            </div>
        </div>
        <div class="col-md-6">
            <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Reservation Channel</span>
                <div>${sourceBadge}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Guest Name</span>
                <span class="font-bold text-slate-800 text-sm">${name}</span>
            </div>
        </div>
        <div class="col-md-6">
            <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Contact Details</span>
                <span class="text-xs font-semibold text-slate-700">${phone || email || 'N/A'}</span>
            </div>
        </div>
        <div class="col-md-6">
            <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Room &amp; Cottage</span>
                <span class="text-xs font-bold text-slate-800">${unit}</span>
            </div>
        </div>
        <div class="col-md-6">
            <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">${checkin ? 'Stay Dates' : 'Scheduled Visit'}</span>
                <span class="text-xs font-bold text-slate-800">${checkin ? `${checkin} to ${checkout} (${nights} Nights)` : date}</span>
            </div>
        </div>
        <div class="col-md-6">
            <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Guests</span>
                <span class="text-xs font-bold text-slate-800">${guests} Guests</span>
            </div>
        </div>
        <div class="col-md-6">
            <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Fee</span>
                <span class="text-sm font-extrabold text-emerald-600">PHP ${amount}</span>
            </div>
        </div>

        ${specialApprovalHtml}

        <div class="col-12">
            <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Special Requests / Front Desk Notes</span>
                <p class="text-xs text-slate-600 mb-0">${requests}</p>
            </div>
        </div>

        ${status === 'cancelled' ? `
        <div class="col-12">
            <div class="bg-rose-50 p-3.5 rounded-2xl border border-rose-200">
                <span class="text-[11px] font-bold text-rose-700 uppercase tracking-wider block mb-1">Cancellation Reason</span>
                <p class="text-xs text-rose-800 mb-0 font-medium">${reason}</p>
            </div>
        </div>` : ''}

        <div class="col-12 mt-2">
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
                <h6 class="font-bold text-xs uppercase tracking-wider text-slate-400 mb-3">Update Lifecycle Status</h6>
                <form method="POST" action="/bookings/${id}/status">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="_method" value="PATCH">
                    <div class="row align-items-end g-3">
                        <div class="col-md-6">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">New Status</label>
                            <select name="status" class="w-full form-select-clean text-xs font-medium" onchange="toggleCancelField(this, ${id})">
                                <option value="pending" ${status === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="paid" ${status === 'paid' ? 'selected' : ''}>Paid</option>
                                <option value="checked_in" ${status === 'checked_in' ? 'selected' : ''}>Checked In</option>
                                <option value="checked_out" ${status === 'checked_out' ? 'selected' : ''}>Checked Out</option>
                                <option value="completed" ${status === 'completed' ? 'selected' : ''}>Completed</option>
                                <option value="cancelled" ${status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="cancelReasonWrapper_${id}" style="display:none;">
                            <label class="block text-xs font-semibold text-rose-600 mb-1">Cancellation Reason</label>
                            <input type="text" name="cancellation_reason" class="w-full form-control-clean text-xs" placeholder="Reason for cancellation...">
                        </div>
                        <div class="col-12 flex justify-end">
                            <button type="submit" class="btn-ocean text-xs">
                                <i class="bi bi-check2-circle"></i>
                                <span>Save Lifecycle Update</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    `;

    const modalObj = new bootstrap.Modal(document.getElementById('bookingDetailModal'));
    modalObj.show();
}

function toggleCancelField(select, id) {
    const div = document.getElementById(`cancelReasonWrapper_${id}`);
    if (div) div.style.display = select.value === 'cancelled' ? 'block' : 'none';
}
</script>
@endpush