@extends('layouts.public')

@section('title', 'My bookings – '.$resortInfo['name'])

@push('styles')
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
<style>
    .lp-stay-banner .lp-banner-box p { margin-bottom: 8px; }
    .lp-stay-actions { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin: 0 0 22px; }
    .lp-stay-page .rounded-3xl { border-radius: 26px; }
    #newBookingModal, #specialBookingModal, #modifyBookingModal, #uploadProofModal, #cancelBookingModal { z-index: 1100; }
</style>
@endpush

@section('content')

<section class="lp-banner lp-banner-short lp-stay-banner" style="background-image:url('/images/hero-landing.jpg')">
    <div class="lp-banner-box">
        <div class="lp-eyebrow">Your stays</div>
        <h1>My bookings</h1>
        <p>Track a reservation, send a GCash receipt, or change the room. These are the same bookings the resort desk sees.</p>
    </div>
</section>

<section class="lp-account-wrap lp-stay-page">
<div class="lp-section">

    @if(session('success'))
        <div class="lp-note lp-note-ok"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="lp-note lp-note-bad"><i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}</div>
    @endif

<div class="lp-stay-actions">
    <a class="lp-btn lp-btn-sand" href="{{ route('public.booking') }}"><i class="bi bi-plus-circle-fill"></i> New reservation</a>
    <a class="lp-btn lp-btn-outline" href="{{ route('public.booking', ['exclusive' => 1]) }}"><i class="bi bi-stars"></i> Full-resort booking</a>
</div>

<div class="space-y-6">

    {{-- Bookings List --}}
    @if($bookings->isEmpty())
    <div class="bg-white rounded-3xl p-12 text-center border border-slate-200 shadow-sm">
        <div class="w-16 h-16 bg-sky-50 text-ocean-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold">
            <i class="bi bi-calendar2-x"></i>
        </div>
        <h3 class="text-lg font-bold text-slate-800 mb-1">No Reservations Found</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto mb-6">You haven't made any resort reservations yet. Reserve your favorite room or cottage today!</p>
        <a class="lp-btn lp-btn-sand" href="{{ route('public.booking') }}"><i class="bi bi-plus-circle-fill"></i> Book a room or cottage</a>
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
                        @if($booking->activities->isNotEmpty())
                            <span class="block text-xs font-semibold text-slate-500 mt-1">Activities: {{ $booking->activities->pluck('title')->join(', ') }}</span>
                        @endif
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
                    @if($booking->isHourly())
                    <span><i class="bi bi-clock me-1 text-ocean-600"></i>{{ $booking->check_in_date?->format('M d, Y') }} · {{ $booking->time_slot }}</span>
                    @elseif($booking->check_in_date && $booking->check_out_date)
                    <span><i class="bi bi-calendar-range me-1 text-ocean-600"></i>{{ $booking->check_in_date->format('M d, Y') }} to {{ $booking->check_out_date->format('M d, Y') }} ({{ $booking->nights_count }} Nights)</span>
                    @else
                    <span><i class="bi bi-calendar-event me-1 text-ocean-600"></i>{{ \Carbon\Carbon::parse($booking->booking_date)->format('M d, Y') }}</span>
                    @endif
                    <span><i class="bi bi-people me-1 text-ocean-600"></i>{{ $booking->guests_count }} Guests</span>
                    @unless($booking->isHourly())
                    <span><i class="bi bi-clock me-1 text-ocean-600"></i>{{ $booking->time_slot }}</span>
                    @endunless
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
                    @php $payMethod = $booking->payment ? \App\Models\PaymentMethod::lookup($booking->payment->gateway) : null; @endphp
                    @if($booking->status === 'pending' && $payMethod && \App\Models\PaymentMethod::needsProof($booking->payment->gateway) && $payMethod->receivingNumber() !== '')
                    <div class="mt-2 rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-left md:text-right">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-sky-700 block">Send {{ $payMethod->name }} to</span>
                        <span class="text-xs font-bold text-slate-800 block">{{ $payMethod->receivingName() !== '' ? $payMethod->receivingName() : 'Talisay Beach Resort' }}</span>
                        <span class="text-sm font-black text-ocean-800 block">{{ $payMethod->receivingNumber() }}</span>
                    </div>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    {{-- Modification button for active bookings --}}
                    @if(in_array($booking->status, ['pending', 'paid', 'checked_in']) && $booking->booking_type !== 'special_resort')
                    <button type="button" 
                            onclick="openModifyModal({{ $booking->id }}, {{ Js::from($booking->reference_no) }}, {{ $booking->accommodation_unit_id ?? 'null' }}, {{ Js::from($booking->accommodationUnit->unit_number ?? '') }}, {{ $booking->guests_count }}, {{ $booking->nights_count ?? 1 }}, {{ $booking->total_amount }})" 
                            class="bg-sky-50 hover:bg-sky-100 text-sky-800 text-xs font-bold px-3 py-1.5 rounded-xl border border-sky-200 transition flex items-center gap-1">
                        <i class="bi bi-arrow-left-right text-sky-600"></i>Change Unit
                    </button>
                    @endif

                    {{-- Online payment proof --}}
                    @if($booking->status === 'pending' && $booking->payment && \App\Models\PaymentMethod::needsProof($booking->payment->gateway))
                        @php
                            $guestReference = $booking->payment->transaction_id;
                            $hasGuestReference = $guestReference && !preg_match('/^(TXN|CASH|MNL)-/', $guestReference);
                            $hasProof = $booking->payment->proof_path || $hasGuestReference;
                        @endphp
                        @if(!$hasProof)
                        <button type="button" onclick="openUploadModal({{ $booking->payment->id }})" class="bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold px-3 py-1.5 rounded-xl shadow-sm transition flex items-center gap-1">
                            <i class="bi bi-upload"></i>Send payment proof
                        </button>
                        @else
                        <span class="inline-flex items-center text-xs font-semibold text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">
                            <i class="bi bi-hourglass-split me-1"></i>Waiting for admin approval
                        </span>
                        @endif
                    @elseif($booking->payment && $booking->payment->is_cash_on_arrival)
                        <span class="inline-flex items-center text-xs font-semibold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200">
                            <i class="bi bi-cash-coin me-1"></i>Pay on Arrival
                        </span>
                    @endif

                    {{-- Cancel Stay Option --}}
                    @if(in_array($booking->status, ['pending', 'paid']))
                    @php $cancelPreview = app(\App\Services\BookingService::class)->previewCancellation($booking); @endphp
                    <button type="button" onclick="openCancelModal({{ $booking->id }}, {{ Js::from($booking->reference_no) }}, {{ Js::from($cancelPreview['message']) }})" class="bg-slate-100 hover:bg-red-50 text-slate-500 hover:text-red-600 text-xs font-bold px-2.5 py-1.5 rounded-xl border border-slate-200 transition flex items-center gap-1">
                        <i class="bi bi-x-circle" aria-hidden="true"></i>Cancel
                    </button>
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
</div>
</section>

{{-- ── 1. New Booking Modal (with Multi-Channel Payment) ────────────────── --}}
<style>
    #newBookingModal,
    #specialBookingModal,
    #modifyBookingModal,
    #uploadProofModal,
    #cancelBookingModal { z-index: 1100; }
    #newBookingModal .reservation-sheet,
    #specialBookingModal .reservation-sheet {
        max-height: calc(100vh - 1.5rem);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    #newBookingModal .reservation-form,
    #specialBookingModal .reservation-form {
        min-height: 0;
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
    }
    #newBookingModal .reservation-scroll,
    #specialBookingModal .reservation-scroll {
        min-height: 0;
        flex: 1 1 auto;
        overflow-y: auto;
    }
    #newBookingModal .reservation-actions,
    #specialBookingModal .reservation-actions { flex-shrink: 0; }
    #newBookingModal .pay-choices {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    @media (min-width: 640px) {
        #newBookingModal .pay-choices { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
</style>
<div id="newBookingModal" class="hidden fixed inset-0 flex items-center justify-center bg-slate-900/80 p-3 sm:p-6">
    <div class="reservation-sheet bg-white rounded-3xl shadow-2xl w-full max-w-xl">
        <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 sm:px-6 py-4 shrink-0">
            <div>
                <h3 class="text-xl font-extrabold text-slate-900 mb-0">Create New Reservation</h3>
                <p class="text-xs text-slate-400 mb-0">Choose a room or cottage, the stay, and how to pay</p>
            </div>
            <button type="button" onclick="document.getElementById('newBookingModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-700" aria-label="Close"><i class="bi bi-x-lg"></i></button>
        </div>

        <form action="{{ route('tourist.bookings.store') }}" method="POST" enctype="multipart/form-data" class="reservation-form" data-loading onsubmit="return bookingGuestsAllowed() && prepareResortPayment(this)">
            @csrf
            <div class="reservation-scroll px-5 sm:px-6 py-4 space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Select Accommodation (Room / Cottage)</label>
                <select name="accommodation_unit_id" id="bookingUnitSelect" class="w-full form-select text-sm rounded-xl" onchange="calculateBookingTotal()" required>
                    @foreach($accommodationUnits as $unit)
                    <option value="{{ $unit->id }}" data-price="{{ $unit->price_per_night }}" data-hour="{{ $unit->hourlyRate() }}" data-cap="{{ $unit->max_occupancy }}">
                        {{ $unit->unit_number }} — {{ $unit->variant_label }} {{ $unit->type_label }} (₱{{ number_format($unit->price_per_night, 2) }}/night · max {{ $unit->max_occupancy }} pax)
                    </option>
                    @endforeach
                </select>
            </div>

            <input type="hidden" name="stay_type" id="bookingStayType" value="night">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">Book this room</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="border border-ocean-600 bg-ocean-50/40 rounded-xl p-3 flex items-center gap-2 cursor-pointer text-xs font-bold" onclick="setBookingStay('night', this)">
                        <input type="radio" name="stay_type_choice" value="night" checked class="form-check-input text-ocean-600">
                        <span>Per night</span>
                    </label>
                    <label class="border border-slate-200 rounded-xl p-3 flex items-center gap-2 cursor-pointer text-xs font-bold" onclick="setBookingStay('hour', this)">
                        <input type="radio" name="stay_type_choice" value="hour" class="form-check-input text-ocean-600">
                        <span>Per hour</span>
                    </label>
                </div>
                <p id="bookingHourHint" class="hidden text-[11px] text-slate-500 mt-2 mb-0">The hourly rate is the amount saved for this room. A stay can be 1 to 12 hours.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1" id="bookingDateLabel">Check-in Date</label>
                    <input type="date" name="booking_date" id="bookingDateInput" min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}" class="w-full form-control rounded-xl text-sm" onchange="calculateBookingTotal()" required>
                </div>
                <div id="bookingCheckOutWrap">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Check-out Date</label>
                    <input type="date" name="check_out_date" id="bookingCheckOutInput" min="{{ date('Y-m-d', strtotime('+1 day')) }}" value="{{ date('Y-m-d', strtotime('+1 day')) }}" class="w-full form-control rounded-xl text-sm" onchange="calculateBookingTotal()" required>
                </div>
            </div>
            <div id="bookingHourFields" class="hidden grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Start time</label>
                    <select name="hour_start" id="bookingHourStart" class="w-full form-select rounded-xl text-sm" onchange="calculateBookingTotal()" disabled>
                        @foreach (range(8, 20) as $hour)
                        <option value="{{ sprintf('%02d:00', $hour) }}" @selected($hour === 14)>{{ \Carbon\Carbon::createFromTime($hour)->format('g:i A') }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Hours</label>
                    <input type="number" name="hours_count" id="bookingHours" min="1" max="12" value="1" class="w-full form-control rounded-xl text-sm" oninput="calculateBookingTotal()" disabled>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Number of Guests (Pax)</label>
                <input type="number" name="guests_count" id="bookingGuestsCount" min="1" value="1" class="w-full form-control rounded-xl text-sm" required oninput="clampBookingGuests()">
                <p class="text-xs text-slate-400 mt-1" id="bookingGuestsHint">This room or cottage allows a limited number of guests.</p>
                <p class="hidden text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-3 py-2 mt-2 mb-0" id="bookingGuestsNotice"></p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Special Requests (optional)</label>
                <textarea name="special_requests" rows="2" class="w-full form-control rounded-xl text-sm" placeholder="e.g. Extra pillows, late arrival, cottage preferences..."></textarea>
            </div>

            @include('partials.activity-addon-picks')

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">Select Payment Channel</label>
                @include('partials.payment-method-radios')
                @include('partials.guest-payment-fields')
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
                <span class="text-xl font-extrabold text-ocean-900 js-stay-total" id="bookingEstimatedTotal">₱0.00</span>
            </div>

            </div>
            <div class="reservation-actions flex gap-2 border-t border-slate-100 px-5 sm:px-6 py-4">
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

        <form id="modifyBookingForm" action="" method="POST" class="space-y-4" data-loading onsubmit="return confirm('Change this reservation to the selected room or cottage?');">
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
<div id="specialBookingModal" class="hidden fixed inset-0 flex items-center justify-center bg-slate-900/80 p-3 sm:p-6">
    <div class="reservation-sheet bg-white rounded-3xl shadow-2xl w-full max-w-xl">
        <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 sm:px-6 py-4 shrink-0">
            <div>
                <div class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-wider text-amber-700 bg-amber-100 px-2.5 py-0.5 rounded-md mb-1">
                    <i class="bi bi-stars"></i> Exclusive Reservation
                </div>
                <h3 class="text-xl font-extrabold text-slate-900 mb-0">Special Full-Resort Booking</h3>
                <p class="text-xs text-slate-400 mb-0">Book the entire Talisay Beach Resort for a private event</p>
            </div>
            <button type="button" onclick="document.getElementById('specialBookingModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-700" aria-label="Close"><i class="bi bi-x-lg"></i></button>
        </div>

        <form action="{{ route('tourist.bookings.special-resort') }}" method="POST" enctype="multipart/form-data" class="reservation-form" data-loading onsubmit="return prepareResortPayment(this)">
            @csrf
            <div class="reservation-scroll px-5 sm:px-6 py-4 space-y-4">

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
                <label class="block text-xs font-bold text-slate-700 mb-1">Estimated Guest Count (Up to {{ \App\Models\CapacitySchedule::visitorCap() }} guests)</label>
                <input type="number" name="guests_count" min="1" max="{{ \App\Models\CapacitySchedule::visitorCap() }}" value="{{ min(30, \App\Models\CapacitySchedule::visitorCap()) }}" class="w-full form-control rounded-xl text-sm" required>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">What is this booking for?</label>
                <select name="occasion" class="w-full form-select rounded-xl text-sm" required>
                    <option value="">Choose the occasion</option>
                    @foreach(\App\Models\Booking::occasions() as $choice)
                        <option value="{{ $choice }}">{{ $choice }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Event details</label>
                <textarea name="special_requests" rows="3" class="w-full form-control rounded-xl text-sm" placeholder="Tell us about the schedule, setup, or anything the group needs." required></textarea>
            </div>

            @include('partials.activity-addon-picks')

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">Preferred Payment Method</label>
                @include('partials.payment-method-radios')
                @include('partials.guest-payment-fields')
            </div>

            <div class="bg-amber-50 p-4 rounded-2xl border border-amber-200 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-amber-900 block">Exclusive Rate (<span id="specialDurationText">1 Night</span>)</span>
                    <span class="text-[10px] text-amber-700">Includes all {{ $openUnitCount }} open {{ $openUnitCount === 1 ? 'room or cottage' : 'rooms and cottages' }}</span>
                </div>
                <span class="text-xl font-extrabold text-amber-900 js-stay-total" id="specialCalculatedTotal">₱0.00</span>
            </div>

            </div>
            <div class="reservation-actions flex gap-2 border-t border-slate-100 px-5 sm:px-6 py-4">
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

        <form id="uploadProofForm" action="" method="POST" enctype="multipart/form-data" class="space-y-4" data-proof-required="1">
            @csrf
            @if(trim((string) setting('gcash_number', '')) !== '')
            <div class="rounded-2xl border border-sky-200 bg-sky-50 p-3">
                <p class="text-[11px] font-bold text-sky-900 mb-1">Send GCash to</p>
                <p class="text-sm font-black text-slate-900 mb-0">{{ setting('gcash_name', 'Talisay Beach Resort') }}</p>
                <p class="text-base font-black text-ocean-800 mb-0">{{ setting('gcash_number') }}</p>
            </div>
            @endif
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">GCash reference number <span class="req-star" aria-hidden="true">*</span></label>
                <input type="text" name="reference_number" inputmode="numeric" maxlength="16" class="w-full form-control rounded-xl text-sm" placeholder="1234 567 890123">
            </div>
            <div class="border-2 border-dashed border-slate-200 rounded-2xl p-6 text-center hover:border-ocean-400 transition">
                <i class="bi bi-cloud-arrow-up text-3xl text-ocean-600 block mb-2"></i>
                <label class="block text-xs font-bold text-slate-700 mb-1 cursor-pointer">
                    Receipt screenshot <span class="req-star" aria-hidden="true">*</span>
                    <input type="file" name="proof_image" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp" class="hidden" onchange="previewUpload(event)">
                </label>
                <p class="text-[10px] text-slate-400 mb-0">Both the 13-digit reference and a JPG, PNG, or WEBP screenshot (up to 5 MB) are required.</p>
                <p class="js-proof-status pay-lock-note" hidden></p>
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

<div id="cancelBookingModal" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="cancelBookingTitle">
    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 id="cancelBookingTitle" class="text-lg font-extrabold text-slate-900 mb-0">Cancel Reservation</h3>
            <button type="button" onclick="document.getElementById('cancelBookingModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-700" aria-label="Close"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
        <p class="text-sm text-slate-600 mb-0">Cancel reservation <strong id="cancelBookingRef"></strong>? This releases the room or cottage for other guests.</p>
        <p id="cancelRefundNote" class="text-sm font-semibold text-slate-800 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 mb-0"></p>
        <form id="cancelBookingForm" method="POST" class="space-y-4" data-loading onsubmit="return confirm('Confirm cancellation of this reservation?');">
            @csrf
            <div>
                <label for="cancellation_reason" class="block text-xs font-bold text-slate-700 mb-1">Reason (optional)</label>
                <textarea id="cancellation_reason" name="cancellation_reason" rows="3" maxlength="500" class="w-full form-control rounded-xl text-sm" placeholder="Change of plans, weather, etc."></textarea>
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="document.getElementById('cancelBookingModal').classList.add('hidden')" class="flex-1 btn btn-light rounded-xl font-bold text-sm">Keep Booking</button>
                <button type="submit" class="flex-1 bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm py-2.5 rounded-xl shadow">Cancel Reservation</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
const unitTotalSum = {{ $accommodationUnits->sum('price_per_night') }};
const exclusiveNightly = {{ (float) app(\App\Services\BookingService::class)->exclusiveNightlyRate() }};
function activityAddonTotal(root) {
    let extra = 0;
    (root || document).querySelectorAll('.js-activity-addon:checked').forEach((box) => {
        extra += Number(box.dataset.price || 0);
    });
    return extra;
}
document.addEventListener('change', (event) => {
    if (!event.target.classList.contains('js-activity-addon')) return;
    if (event.target.closest('#specialBookingModal')) calculateSpecialTotal();
    else calculateBookingTotal();
});
let modifyCurrentCost = 0;
let modifyNightsCount = 1;

function selectPaymentMethod(el) {
    (el.closest('form') || document).querySelectorAll('.payment-option').forEach(opt => {
        opt.classList.remove('border-ocean-600', 'bg-ocean-50/40');
        opt.classList.add('border-slate-200');
    });
    el.classList.add('border-ocean-600', 'bg-ocean-50/40');
    el.classList.remove('border-slate-200');
    el.querySelector('input[type="radio"]').checked = true;
    if (typeof showPayPanels === 'function') showPayPanels(el.closest('form'));
}

function bookingGuestCap() {
    const select = document.getElementById('bookingUnitSelect');
    return Math.max(1, parseInt(select?.options[select.selectedIndex]?.dataset?.cap || '1', 10) || 1);
}
function showBookingGuestNotice(cap) {
    const notice = document.getElementById('bookingGuestsNotice');
    if (!notice) return;
    notice.textContent = 'This room only allows ' + cap + ' guests.';
    notice.classList.remove('hidden');
}
function clampBookingGuests() {
    const input = document.getElementById('bookingGuestsCount');
    const hint = document.getElementById('bookingGuestsHint');
    const notice = document.getElementById('bookingGuestsNotice');
    if (!input) return;
    const cap = bookingGuestCap();
    const typed = parseInt(input.value, 10);
    if (Number.isFinite(typed) && typed > cap) {
        showBookingGuestNotice(cap);
    } else if (notice) {
        notice.classList.add('hidden');
    }
    if (hint) hint.textContent = 'This room or cottage allows up to ' + cap + ' guests.';
}
function bookingGuestsAllowed() {
    clampBookingGuests();
    const input = document.getElementById('bookingGuestsCount');
    const typed = parseInt(input?.value, 10);
    if (Number.isFinite(typed) && typed > bookingGuestCap()) {
        showBookingGuestNotice(bookingGuestCap());
        return false;
    }
    return true;
}

function setBookingStay(type, label) {
    document.getElementById('bookingStayType').value = type;
    const hourly = type === 'hour';
    document.getElementById('bookingHourFields').classList.toggle('hidden', !hourly);
    document.getElementById('bookingHourHint').classList.toggle('hidden', !hourly);
    document.getElementById('bookingCheckOutWrap').classList.toggle('hidden', hourly);
    const checkout = document.getElementById('bookingCheckOutInput');
    checkout.required = !hourly;
    checkout.disabled = hourly;
    document.getElementById('bookingHourStart').disabled = !hourly;
    document.getElementById('bookingHours').disabled = !hourly;
    document.getElementById('bookingDateLabel').textContent = hourly ? 'Date' : 'Check-in Date';
    const group = label.closest('.grid');
    group.querySelectorAll('label').forEach((item) => {
        item.classList.remove('border-ocean-600', 'bg-ocean-50/40');
        item.classList.add('border-slate-200');
    });
    label.classList.add('border-ocean-600', 'bg-ocean-50/40');
    label.classList.remove('border-slate-200');
    calculateBookingTotal();
}

async function calculateBookingTotal() {
    clampBookingGuests();
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

    const hourly = document.getElementById('bookingStayType')?.value === 'hour';
    const bookedHours = Math.max(1, Math.min(12, parseInt(document.getElementById('bookingHours')?.value || '1', 10) || 1));
    const hourRate = parseFloat(select.options[select.selectedIndex]?.dataset?.hour || 0) || Math.round(price / 12 * 100) / 100;
    document.getElementById('bookingNightsText').textContent = hourly
        ? (bookedHours + (bookedHours === 1 ? ' Hour' : ' Hours'))
        : (nights + (nights === 1 ? ' Night' : ' Nights'));
    const stayTotal = hourly ? hourRate * bookedHours : price * nights;
    const total = stayTotal + activityAddonTotal(document.getElementById('newBookingModal'));
    document.getElementById('bookingEstimatedTotal').textContent = '₱' + total.toLocaleString('en-US', { minimumFractionDigits: 2 });

    // Live conflict verification
    const feedback = document.getElementById('bookingAvailabilityFeedback');
    const icon = document.getElementById('bookingAvailIcon');
    const text = document.getElementById('bookingAvailText');
    const submitBtn = document.getElementById('bookingSubmitBtn');

    if (unitId && checkInInput.value && checkOutInput.value) {
        try {
            const hourlyStay = document.getElementById('bookingStayType')?.value === 'hour';
            const hourStart = document.getElementById('bookingHourStart')?.value || '14:00';
            const hourCount = document.getElementById('bookingHours')?.value || '1';
            const stayQuery = hourlyStay
                ? `check_in=${checkInInput.value}&stay_type=hour&hour_start=${hourStart}&hours=${hourCount}`
                : `check_in=${checkInInput.value}&check_out=${checkOutInput.value}`;
            const res = await fetch(`/tourist/accommodations/${unitId}/check-availability?${stayQuery}`);
            const data = await res.json();
            feedback.classList.remove('hidden');
            if (data.available) {
                feedback.className = 'p-3 rounded-xl text-xs flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-800';
                icon.className = 'bi bi-check-circle-fill text-emerald-600 text-sm';
                text.textContent = 'Available. No conflicting reservations for these dates.';
                submitBtn.disabled = false;
            } else {
                feedback.className = 'p-3 rounded-xl text-xs flex items-center gap-2 bg-red-50 border border-red-200 text-red-700';
                icon.className = 'bi bi-exclamation-octagon-fill text-red-500 text-sm';
                text.textContent = data.conflict_message || 'This room or cottage is already booked for these dates.';
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
    const nightly = exclusiveNightly > 0 ? exclusiveNightly : unitTotalSum;
    const total = nightly * days + activityAddonTotal(document.getElementById('specialBookingModal'));
    document.getElementById('specialCalculatedTotal').textContent = '₱' + total.toLocaleString('en-US', { minimumFractionDigits: 2 });
}

function openCancelModal(bookingId, refNo, refundNote) {
    const form = document.getElementById('cancelBookingForm');
    form.action = '/tourist/bookings/' + bookingId + '/cancel';
    document.getElementById('cancelBookingRef').textContent = refNo || '';
    document.getElementById('cancelRefundNote').textContent = refundNote || 'The room or cottage is released for other guests.';
    document.getElementById('cancellation_reason').value = '';
    document.getElementById('cancelBookingModal').classList.remove('hidden');
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
