@extends('layouts.tourist')

@section('title', 'My Bookings - Talisay Beach Resort')

@section('content')

{{-- Header Banner --}}
<div class="bg-ocean-gradient text-white py-10 px-4 shadow-lg mb-8">
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white mb-1">My Reservations</h1>
            <p class="text-xs sm:text-sm text-sky-100 mb-0">Track bookings, manage check-in dates, and upload payment proofs</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button onclick="document.getElementById('newBookingModal').classList.remove('hidden')" class="bg-white text-ocean-900 hover:bg-sky-50 font-bold px-4 py-2.5 rounded-xl text-xs shadow transition flex items-center gap-1.5">
                <i class="bi bi-plus-circle-fill text-ocean-600"></i>New Reservation
            </button>
            <button onclick="openSpecialBookingModal()" class="bg-amber-500 hover:bg-amber-400 text-white font-extrabold px-4 py-2.5 rounded-xl text-xs shadow transition flex items-center gap-1.5 border border-amber-300/40">
                <i class="bi bi-stars"></i>Special Full-Resort Booking
            </button>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- Flash Notifications --}}
    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center gap-3 text-sm shadow-sm">
        <i class="bi bi-check-circle-fill text-emerald-500 text-lg"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-2xl flex items-center gap-3 text-sm shadow-sm">
        <i class="bi bi-exclamation-triangle-fill text-red-500 text-lg"></i>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    {{-- Bookings List --}}
    @if($bookings->isEmpty())
    <div class="bg-white rounded-3xl p-12 text-center border border-slate-200 shadow-sm">
        <div class="w-16 h-16 bg-sky-50 text-ocean-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold">
            <i class="bi bi-calendar2-x"></i>
        </div>
        <h3 class="text-lg font-bold text-slate-800 mb-1">No Reservations Found</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto mb-6">You haven't made any resort reservations yet. Reserve your favorite room or cottage today!</p>
        <button onclick="document.getElementById('newBookingModal').classList.remove('hidden')" class="bg-ocean-600 hover:bg-ocean-700 text-white font-bold text-xs px-5 py-3 rounded-xl shadow transition">
            <i class="bi bi-plus-circle me-1"></i>Book a Room or Cottage Now
        </button>
    </div>
    @else

    <div class="space-y-4">
        @foreach($bookings as $booking)
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 hover:border-sky-300 transition flex flex-col md:flex-row md:items-center justify-between gap-6">
            
            <div class="space-y-2 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-mono font-extrabold text-ocean-900 bg-ocean-50 px-2.5 py-1 rounded-md border border-ocean-200">
                        {{ $booking->reference_no }}
                    </span>

                    {{-- Admin Approval Badge for Special Bookings --}}
                    @if($booking->booking_type === 'special_resort')
                        @if($booking->admin_approval_status === 'pending')
                        <span class="text-xs font-bold px-3 py-1 rounded-full bg-amber-100 text-amber-800 border border-amber-200 inline-flex items-center">
                            <i class="bi bi-clock-history me-1"></i> Pending Admin Review
                        </span>
                        @elseif($booking->admin_approval_status === 'approved')
                        <span class="text-xs font-bold px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center">
                            <i class="bi bi-check-circle me-1"></i> Admin Approved
                        </span>
                        @elseif($booking->admin_approval_status === 'rejected')
                        <span class="text-xs font-bold px-3 py-1 rounded-full bg-red-100 text-red-800 border border-red-200 inline-flex items-center">
                            <i class="bi bi-x-circle me-1"></i> Declined by Management
                        </span>
                        @endif
                    @endif

                    {{-- Lifecycle Status Badge --}}
                    @php
                        $badgeClasses = [
                            'pending' => 'bg-amber-100 text-amber-800 border-amber-200',
                            'paid' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                            'checked_in' => 'bg-sky-100 text-sky-800 border-sky-200',
                            'checked_out' => 'bg-purple-100 text-purple-800 border-purple-200',
                            'completed' => 'bg-slate-100 text-slate-700 border-slate-200',
                            'cancelled' => 'bg-red-100 text-red-800 border-red-200',
                        ];
                    @endphp
                    <span class="text-xs font-bold px-3 py-1 rounded-full border uppercase tracking-wider {{ $badgeClasses[$booking->status] ?? 'bg-slate-100 text-slate-800' }}">
                        {{ str_replace('_', ' ', $booking->status) }}
                    </span>

                    {{-- Payment Channel Pill --}}
                    @if($booking->payment)
                    <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-600 border border-slate-200 flex items-center gap-1">
                        <i class="bi bi-credit-card-2-front text-ocean-600"></i>
                        {{ strtoupper($booking->payment->payment_channel ?? $booking->payment->gateway) }}
                    </span>
                    @endif
                </div>

                <div>
                    <h3 class="text-lg font-extrabold text-slate-900 mb-0">
                        {{ $booking->booking_type === 'special_resort' ? 'Exclusive Full-Resort Private Reservation' : ($booking->accommodationUnit->unit_number ?? 'Room / Cottage Reservation') }}
                        @if($booking->accommodationUnit && $booking->booking_type !== 'special_resort')
                        <span class="text-xs font-semibold text-slate-500 font-normal">({{ $booking->accommodationUnit->variant_label }} {{ $booking->accommodationUnit->type_label }})</span>
                        @endif
                    </h3>

                    @if($booking->modified_at)
                    <p class="text-[11px] text-amber-700 font-semibold mb-0 flex items-center gap-1 mt-0.5">
                        <i class="bi bi-arrow-repeat"></i> Modified on {{ $booking->modified_at->format('M d, Y') }}
                        @if($booking->modification_notes)
                        — "{{ $booking->modification_notes }}"
                        @endif
                    </p>
                    @endif
                </div>

                <div class="flex flex-wrap gap-4 text-xs text-slate-500 pt-1">
                    @if($booking->check_in_date && $booking->check_out_date)
                    <span><i class="bi bi-calendar-range me-1 text-ocean-600"></i>{{ $booking->check_in_date->format('M d, Y') }} to {{ $booking->check_out_date->format('M d, Y') }} ({{ $booking->nights_count }} Nights)</span>
                    @else
                    <span><i class="bi bi-calendar-event me-1 text-ocean-600"></i>{{ \Carbon\Carbon::parse($booking->booking_date)->format('M d, Y') }}</span>
                    @endif
                    <span><i class="bi bi-people me-1 text-ocean-600"></i>{{ $booking->guests_count }} Guests</span>
                    <span><i class="bi bi-clock me-1 text-ocean-600"></i>{{ $booking->time_slot }}</span>
                </div>

                @if($booking->special_requests)
                <p class="text-xs text-slate-500 bg-slate-50 p-2.5 rounded-xl border border-slate-100 mb-0">
                    <strong>Special Requests:</strong> {{ $booking->special_requests }}
                </p>
                @endif
            </div>

            <div class="text-left md:text-right space-y-2 border-t md:border-t-0 pt-4 md:pt-0 border-slate-100 flex flex-col md:items-end justify-between">
                <div>
                    <span class="text-xs text-slate-400 block font-semibold">Total Cost</span>
                    <span class="text-2xl font-extrabold text-ocean-900 block">₱{{ number_format($booking->total_amount, 2) }}</span>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    {{-- Modification button for active bookings --}}
                    @if(in_array($booking->status, ['pending', 'paid', 'checked_in']) && $booking->booking_type !== 'special_resort')
                    <button type="button" 
                            onclick="openModifyModal({{ $booking->id }}, '{{ $booking->reference_no }}', {{ $booking->accommodation_unit_id }}, '{{ addslashes($booking->accommodationUnit->unit_number ?? '') }}', {{ $booking->guests_count }}, {{ $booking->nights_count ?? 1 }}, {{ $booking->total_amount }})" 
                            class="bg-sky-50 hover:bg-sky-100 text-sky-800 text-xs font-bold px-3 py-1.5 rounded-xl border border-sky-200 transition flex items-center gap-1">
                        <i class="bi bi-arrow-left-right text-sky-600"></i>Change Unit
                    </button>
                    @endif

                    {{-- GCash Upload Button --}}
                    @if($booking->status === 'pending' && $booking->payment)
                        @if(!$booking->payment->proof_path)
                        <button type="button" onclick="openUploadModal({{ $booking->payment->id }})" class="bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold px-3 py-1.5 rounded-xl shadow-sm transition flex items-center gap-1">
                            <i class="bi bi-upload"></i>Upload GCash Proof
                        </button>
                        @else
                        <button type="button" onclick="openUploadModal({{ $booking->payment->id }})" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold px-3 py-1.5 rounded-xl border border-emerald-200 transition flex items-center gap-1">
                            <i class="bi bi-check2-circle text-emerald-600"></i>Proof Uploaded (Replace)
                        </button>
                        @endif
                    @elseif($booking->payment && $booking->payment->is_cash_on_arrival)
                        <span class="inline-flex items-center text-xs font-semibold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200">
                            <i class="bi bi-cash-coin me-1"></i>Pay on Arrival
                        </span>
                    @endif

                    {{-- Cancel Stay Option --}}
                    @if(in_array($booking->status, ['pending', 'paid']))
                    <form action="{{ route('tourist.bookings.cancel', $booking->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to cancel reservation {{ $booking->reference_no }}?');">
                        @csrf
                        <button type="submit" class="bg-slate-100 hover:bg-red-50 text-slate-500 hover:text-red-600 text-xs font-bold px-2.5 py-1.5 rounded-xl border border-slate-200 transition flex items-center gap-1">
                            <i class="bi bi-x-circle"></i>Cancel
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="pt-4">
        {{ $bookings->links() }}
    </div>

    @endif
</div>

{{-- ── 1. New Booking Modal (with Multi-Channel Payment) ────────────────── --}}
<div id="newBookingModal" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-8 space-y-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-xl font-extrabold text-slate-900 mb-0">Create New Reservation</h3>
                <p class="text-xs text-slate-400 mb-0">Choose room or cottage, dates, and preferred payment method</p>
            </div>
            <button onclick="document.getElementById('newBookingModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-700"><i class="bi bi-x-lg"></i></button>
        </div>

        <form action="{{ route('tourist.bookings.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Select Accommodation (Room / Cottage)</label>
                <select name="accommodation_unit_id" id="bookingUnitSelect" class="w-full form-select text-sm rounded-xl" onchange="calculateBookingTotal()" required>
                    @foreach($accommodationUnits as $unit)
                    <option value="{{ $unit->id }}" data-price="{{ $unit->price_per_night }}" data-cap="{{ $unit->max_occupancy }}">
                        {{ $unit->unit_number }} — {{ $unit->variant_label }} {{ $unit->type_label }} (₱{{ number_format($unit->price_per_night, 2) }}/night · max {{ $unit->max_occupancy }} pax)
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Check-in Date</label>
                    <input type="date" name="booking_date" id="bookingDateInput" min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}" class="w-full form-control rounded-xl text-sm" onchange="calculateBookingTotal()" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Check-out Date</label>
                    <input type="date" name="check_out_date" id="bookingCheckOutInput" min="{{ date('Y-m-d', strtotime('+1 day')) }}" value="{{ date('Y-m-d', strtotime('+1 day')) }}" class="w-full form-control rounded-xl text-sm" onchange="calculateBookingTotal()" required>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Number of Guests (Pax)</label>
                <input type="number" name="guests_count" id="bookingGuestsCount" min="1" value="2" class="w-full form-control rounded-xl text-sm" required>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Special Requests (optional)</label>
                <textarea name="special_requests" rows="2" class="w-full form-control rounded-xl text-sm" placeholder="e.g. Extra pillows, late arrival, cottage preferences..."></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">Select Payment Channel</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="payment-option border border-ocean-600 bg-ocean-50/40 rounded-xl p-3 flex items-center gap-2 cursor-pointer transition text-xs font-bold" onclick="selectPaymentMethod(this)">
                        <input type="radio" name="payment_method" value="gcash" checked class="form-check-input text-ocean-600">
                        <span>GCash QR</span>
                    </label>
                    <label class="payment-option border border-slate-200 hover:border-ocean-300 rounded-xl p-3 flex items-center gap-2 cursor-pointer transition text-xs font-bold" onclick="selectPaymentMethod(this)">
                        <input type="radio" name="payment_method" value="cash" class="form-check-input text-ocean-600">
                        <span>Cash on Arrival</span>
                    </label>
                    <label class="payment-option border border-slate-200 hover:border-ocean-300 rounded-xl p-3 flex items-center gap-2 cursor-pointer transition text-xs font-bold" onclick="selectPaymentMethod(this)">
                        <input type="radio" name="payment_method" value="paypal" class="form-check-input text-ocean-600">
                        <span>PayPal Express</span>
                    </label>
                    <label class="payment-option border border-slate-200 hover:border-ocean-300 rounded-xl p-3 flex items-center gap-2 cursor-pointer transition text-xs font-bold" onclick="selectPaymentMethod(this)">
                        <input type="radio" name="payment_method" value="card" class="form-check-input text-ocean-600">
                        <span>Credit / Debit Card</span>
                    </label>
                </div>
            </div>

            {{-- Real-time Availability Feedback Box --}}
            <div id="bookingAvailabilityFeedback" class="hidden p-3 rounded-xl text-xs flex items-center gap-2">
                <i id="bookingAvailIcon" class="bi bi-check-circle-fill text-sm"></i>
                <span id="bookingAvailText"></span>
            </div>

            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-600 block">Total Reservation Cost</span>
                    <span class="text-[11px] text-slate-400" id="bookingNightsText">1 Night</span>
                </div>
                <span class="text-xl font-extrabold text-ocean-900" id="bookingEstimatedTotal">₱0.00</span>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="button" onclick="document.getElementById('newBookingModal').classList.add('hidden')" class="flex-1 btn btn-light rounded-xl font-bold text-sm">Cancel</button>
                <button type="submit" id="bookingSubmitBtn" class="flex-1 bg-ocean-600 hover:bg-ocean-800 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-sm py-2.5 rounded-xl shadow">Confirm &amp; Proceed</button>
            </div>
        </form>
    </div>
</div>

{{-- ── 2. Modify / Upgrade Booking Modal ──────────────────────────────── --}}
<div id="modifyBookingModal" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-8 space-y-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-xl font-extrabold text-slate-900 mb-0">Modify / Change Unit</h3>
                <p class="text-xs text-slate-400 mb-0" id="modifyModalRefHeader">Reference TBR-XXXX</p>
            </div>
            <button onclick="document.getElementById('modifyBookingModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-700"><i class="bi bi-x-lg"></i></button>
        </div>

        <form id="modifyBookingForm" action="" method="POST" class="space-y-4">
            @csrf
            
            <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100 space-y-1">
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Current Reservation</span>
                <p class="text-sm font-bold text-slate-800 mb-0" id="modifyCurrentUnitName">Room 01</p>
                <span class="text-xs text-slate-500" id="modifyCurrentTotal">Current Total: ₱0.00</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Select New Room / Cottage</label>
                <select name="new_accommodation_unit_id" id="modifyNewUnitSelect" class="w-full form-select text-sm rounded-xl" onchange="calculateModifyDiff()" required>
                    @foreach($accommodationUnits as $unit)
                    <option value="{{ $unit->id }}" data-price="{{ $unit->price_per_night }}" data-cap="{{ $unit->max_occupancy }}">
                        {{ $unit->unit_number }} — {{ $unit->variant_label }} {{ $unit->type_label }} (₱{{ number_format($unit->price_per_night, 2) }}/night)
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Modification Note (optional)</label>
                <textarea name="modification_notes" rows="2" class="w-full form-control rounded-xl text-sm" placeholder="e.g. Upgraded to beachfront cottage for more space..."></textarea>
            </div>

            {{-- Real-time Price Difference Calculation Box --}}
            <div id="modifyPriceDiffBox" class="p-4 rounded-2xl border flex items-center justify-between bg-sky-50 border-sky-200">
                <div>
                    <span class="text-xs font-bold text-slate-700 block">Recalculated Total</span>
                    <span class="text-xs text-slate-500" id="modifyNewTotalDisplay">₱0.00</span>
                </div>
                <div class="text-right">
                    <span class="text-[10px] text-slate-400 uppercase tracking-wider font-bold block" id="modifyDiffLabel">Price Difference</span>
                    <span class="text-base font-extrabold text-ocean-900" id="modifyDiffAmount">₱0.00</span>
                </div>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modifyBookingModal').classList.add('hidden')" class="flex-1 btn btn-light rounded-xl font-bold text-sm">Cancel</button>
                <button type="submit" class="flex-1 bg-ocean-600 hover:bg-ocean-800 text-white font-bold text-sm py-2.5 rounded-xl shadow">Confirm Change</button>
            </div>
        </form>
    </div>
</div>

{{-- ── 3. Special Full-Resort Exclusive Booking Modal ──────────────────── --}}
<div id="specialBookingModal" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full p-6 sm:p-8 space-y-4 my-8">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <div class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-wider text-amber-700 bg-amber-100 px-2.5 py-0.5 rounded-md mb-1">
                    <i class="bi bi-stars"></i> Exclusive Reservation
                </div>
                <h3 class="text-xl font-extrabold text-slate-900 mb-0">Special Full-Resort Booking</h3>
                <p class="text-xs text-slate-400 mb-0">Book the entire Talisay Beach Resort (all 20 units) for private events</p>
            </div>
            <button onclick="document.getElementById('specialBookingModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-700"><i class="bi bi-x-lg"></i></button>
        </div>

        <form action="{{ route('tourist.bookings.special-resort') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Check-in Date</label>
                    <input type="date" name="check_in_date" id="specialCheckIn" min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}" class="w-full form-control rounded-xl text-sm" onchange="calculateSpecialTotal()" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Check-out Date</label>
                    <input type="date" name="check_out_date" id="specialCheckOut" min="{{ date('Y-m-d', strtotime('+1 day')) }}" value="{{ date('Y-m-d', strtotime('+1 day')) }}" class="w-full form-control rounded-xl text-sm" onchange="calculateSpecialTotal()" required>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Estimated Guest Count (Up to 100 Pax)</label>
                <input type="number" name="guests_count" min="1" max="200" value="30" class="w-full form-control rounded-xl text-sm" required>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Event Type &amp; Special Requirements</label>
                <textarea name="special_requests" rows="3" class="w-full form-control rounded-xl text-sm" placeholder="Tell us about your event (e.g. Wedding reception, corporate team building, catering requests, sound setup)..." required></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">Preferred Payment Method</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="payment-option border border-ocean-600 bg-ocean-50/40 rounded-xl p-3 flex items-center gap-2 cursor-pointer text-xs font-bold">
                        <input type="radio" name="payment_method" value="gcash" checked class="form-check-input text-ocean-600">
                        <span>GCash</span>
                    </label>
                    <label class="payment-option border border-slate-200 rounded-xl p-3 flex items-center gap-2 cursor-pointer text-xs font-bold">
                        <input type="radio" name="payment_method" value="cash" class="form-check-input text-ocean-600">
                        <span>Cash / Bank Deposit</span>
                    </label>
                    <label class="payment-option border border-slate-200 rounded-xl p-3 flex items-center gap-2 cursor-pointer text-xs font-bold">
                        <input type="radio" name="payment_method" value="paypal" class="form-check-input text-ocean-600">
                        <span>PayPal</span>
                    </label>
                    <label class="payment-option border border-slate-200 rounded-xl p-3 flex items-center gap-2 cursor-pointer text-xs font-bold">
                        <input type="radio" name="payment_method" value="card" class="form-check-input text-ocean-600">
                        <span>Credit Card</span>
                    </label>
                </div>
            </div>

            <div class="bg-amber-50 p-4 rounded-2xl border border-amber-200 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-amber-900 block">Exclusive Rate (<span id="specialDurationText">1 Night</span>)</span>
                    <span class="text-[10px] text-amber-700">Includes all 20 rooms &amp; beachfront cottages</span>
                </div>
                <span class="text-xl font-extrabold text-amber-900" id="specialCalculatedTotal">₱0.00</span>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="button" onclick="document.getElementById('specialBookingModal').classList.add('hidden')" class="flex-1 btn btn-light rounded-xl font-bold text-sm">Cancel</button>
                <button type="submit" class="flex-1 bg-amber-500 hover:bg-amber-600 text-white font-bold text-sm py-2.5 rounded-xl shadow">Request Reservation</button>
            </div>
        </form>
    </div>
</div>

{{-- ── 4. Upload GCash Proof Modal ────────────────────────────────────── --}}
<div id="uploadProofModal" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-lg font-extrabold text-slate-900 mb-0">Upload Payment Proof</h3>
            <button onclick="document.getElementById('uploadProofModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-700"><i class="bi bi-x-lg"></i></button>
        </div>

        <form id="uploadProofForm" action="" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div class="border-2 border-dashed border-slate-200 rounded-2xl p-6 text-center hover:border-ocean-400 transition">
                <i class="bi bi-cloud-arrow-up text-3xl text-ocean-600 block mb-2"></i>
                <label class="block text-xs font-bold text-slate-700 mb-1 cursor-pointer">
                    Click to select payment screenshot
                    <input type="file" name="proof_image" accept="image/*" class="hidden" onchange="previewUpload(event)" required>
                </label>
                <p class="text-[10px] text-slate-400 mb-0">PNG, JPG, or JPEG up to 5MB</p>
                <div id="uploadPreviewWrap" class="hidden mt-3">
                    <img id="uploadPreview" src="" alt="Proof Preview" class="max-h-40 mx-auto rounded-lg shadow-sm">
                </div>
            </div>

            <div class="flex gap-2">
                <button type="button" onclick="document.getElementById('uploadProofModal').classList.add('hidden')" class="flex-1 btn btn-light rounded-xl font-bold text-sm">Cancel</button>
                <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm py-2.5 rounded-xl shadow">Submit Receipt</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
const unitTotalSum = {{ $accommodationUnits->sum('price_per_night') }};
let modifyCurrentCost = 0;
let modifyNightsCount = 1;

function selectPaymentMethod(el) {
    document.querySelectorAll('.payment-option').forEach(opt => {
        opt.classList.remove('border-ocean-600', 'bg-ocean-50/40');
        opt.classList.add('border-slate-200');
    });
    el.classList.add('border-ocean-600', 'bg-ocean-50/40');
    el.classList.remove('border-slate-200');
    el.querySelector('input[type="radio"]').checked = true;
}

async function calculateBookingTotal() {
    const select = document.getElementById('bookingUnitSelect');
    const unitId = select.value;
    const price = parseFloat(select.options[select.selectedIndex]?.dataset?.price || 0);
    
    const checkInInput = document.getElementById('bookingDateInput');
    const checkOutInput = document.getElementById('bookingCheckOutInput');

    if (!checkInInput.value) checkInInput.value = '{{ date('Y-m-d') }}';
    if (!checkOutInput.value || checkOutInput.value <= checkInInput.value) {
        const d = new Date(checkInInput.value);
        d.setDate(d.getDate() + 1);
        checkOutInput.value = d.toISOString().split('T')[0];
    }
    checkOutInput.min = checkInInput.value;
    
    const checkIn = new Date(checkInInput.value);
    const checkOut = new Date(checkOutInput.value);
    
    let nights = Math.round((checkOut - checkIn) / (1000 * 60 * 60 * 24));
    if (isNaN(nights) || nights < 1) nights = 1;

    document.getElementById('bookingNightsText').textContent = nights + (nights === 1 ? ' Night' : ' Nights');
    const total = price * nights;
    document.getElementById('bookingEstimatedTotal').textContent = '₱' + total.toLocaleString('en-US', { minimumFractionDigits: 2 });

    // Live conflict verification
    const feedback = document.getElementById('bookingAvailabilityFeedback');
    const icon = document.getElementById('bookingAvailIcon');
    const text = document.getElementById('bookingAvailText');
    const submitBtn = document.getElementById('bookingSubmitBtn');

    if (unitId && checkInInput.value && checkOutInput.value) {
        try {
            const res = await fetch(`/tourist/accommodations/${unitId}/check-availability?check_in=${checkInInput.value}&check_out=${checkOutInput.value}`);
            const data = await res.json();
            feedback.classList.remove('hidden');
            if (data.available) {
                feedback.className = 'p-3 rounded-xl text-xs flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-800';
                icon.className = 'bi bi-check-circle-fill text-emerald-600 text-sm';
                text.innerHTML = `<strong>Available!</strong> No conflicting reservations for these dates.`;
                submitBtn.disabled = false;
            } else {
                feedback.className = 'p-3 rounded-xl text-xs flex items-center gap-2 bg-red-50 border border-red-200 text-red-700';
                icon.className = 'bi bi-exclamation-octagon-fill text-red-500 text-sm';
                text.innerHTML = `<strong>Conflict:</strong> ${data.conflict_message || 'Unit is already booked for these dates.'}`;
                submitBtn.disabled = true;
            }
        } catch (e) {
            submitBtn.disabled = false;
        }
    }
}

function openModifyModal(bookingId, refNo, currentUnitId, currentUnitName, guestsCount, nightsCount, totalAmount) {
    document.getElementById('modifyModalRefHeader').textContent = 'Reference ' + refNo;
    document.getElementById('modifyCurrentUnitName').textContent = currentUnitName;
    document.getElementById('modifyCurrentTotal').textContent = 'Current Total: ₱' + parseFloat(totalAmount).toLocaleString('en-US', { minimumFractionDigits: 2 });
    document.getElementById('modifyBookingForm').action = `/tourist/bookings/${bookingId}/modify`;
    
    modifyCurrentCost = parseFloat(totalAmount);
    modifyNightsCount = parseInt(nightsCount) > 0 ? parseInt(nightsCount) : 1;

    const select = document.getElementById('modifyNewUnitSelect');
    select.value = currentUnitId;
    calculateModifyDiff();

    document.getElementById('modifyBookingModal').classList.remove('hidden');
}

function calculateModifyDiff() {
    const select = document.getElementById('modifyNewUnitSelect');
    const newPrice = parseFloat(select.options[select.selectedIndex]?.dataset?.price || 0);
    const newTotal = newPrice * modifyNightsCount;
    const diff = newTotal - modifyCurrentCost;

    document.getElementById('modifyNewTotalDisplay').textContent = '₱' + newTotal.toLocaleString('en-US', { minimumFractionDigits: 2 });
    
    const diffEl = document.getElementById('modifyDiffAmount');
    const labelEl = document.getElementById('modifyDiffLabel');
    const box = document.getElementById('modifyPriceDiffBox');

    if (diff > 0) {
        labelEl.textContent = 'Upgrade Fee Due';
        diffEl.textContent = '+₱' + diff.toLocaleString('en-US', { minimumFractionDigits: 2 });
        diffEl.className = 'text-base font-extrabold text-amber-600';
        box.className = 'p-4 rounded-2xl border flex items-center justify-between bg-amber-50 border-amber-200';
    } else if (diff < 0) {
        labelEl.textContent = 'Refund Amount';
        diffEl.textContent = '-₱' + Math.abs(diff).toLocaleString('en-US', { minimumFractionDigits: 2 });
        diffEl.className = 'text-base font-extrabold text-emerald-600';
        box.className = 'p-4 rounded-2xl border flex items-center justify-between bg-emerald-50 border-emerald-200';
    } else {
        labelEl.textContent = 'Price Difference';
        diffEl.textContent = '₱0.00 (No change)';
        diffEl.className = 'text-base font-extrabold text-slate-600';
        box.className = 'p-4 rounded-2xl border flex items-center justify-between bg-slate-50 border-slate-200';
    }
}

function openSpecialBookingModal() {
    calculateSpecialTotal();
    document.getElementById('specialBookingModal').classList.remove('hidden');
}

function calculateSpecialTotal() {
    const checkIn = new Date(document.getElementById('specialCheckIn').value);
    const checkOut = new Date(document.getElementById('specialCheckOut').value);
    
    let days = Math.round((checkOut - checkIn) / (1000 * 60 * 60 * 24));
    if (isNaN(days) || days < 1) days = 1;

    document.getElementById('specialDurationText').textContent = days + (days === 1 ? ' Night' : ' Nights');
    const total = unitTotalSum * days;
    document.getElementById('specialCalculatedTotal').textContent = '₱' + total.toLocaleString('en-US', { minimumFractionDigits: 2 });
}

function openUploadModal(paymentId) {
    const form = document.getElementById('uploadProofForm');
    form.action = `/tourist/payments/${paymentId}/upload-proof`;
    document.getElementById('uploadProofModal').classList.remove('hidden');
}

function previewUpload(event) {
    const reader = new FileReader();
    reader.onload = function(){
        const preview = document.getElementById('uploadPreview');
        preview.src = reader.result;
        document.getElementById('uploadPreviewWrap').classList.remove('hidden');
    };
    reader.readAsDataURL(event.target.files[0]);
}

document.addEventListener('DOMContentLoaded', function() {
    calculateBookingTotal();
});
</script>
@endpush
