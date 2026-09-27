@extends('layouts.tourist')

@section('title', 'Tourist Dashboard - Talisay Beach Resort')

@section('content')

{{-- ── Hero Welcome Banner ────────────────────────────────────────────── --}}
<div class="bg-ocean-gradient text-white py-12 px-4 sm:px-6 lg:px-8 shadow-xl mb-8 relative overflow-hidden">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-6 relative z-10">
        <div>
            <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/20 px-3.5 py-1.5 rounded-full text-xs font-semibold text-sky-200 mb-3">
                <i class="bi bi-brightness-high-fill text-amber-400"></i>
                <span>Welcome to Paradise</span>
            </div>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-2">
                Hello, {{ auth()->user()->name }}!
            </h1>
            <p class="text-sky-100 text-sm sm:text-base max-w-2xl mb-0">
                Manage your resort bookings, browse rooms & cottages, explore 360° virtual tours, and enjoy an unforgettable beach experience.
            </p>
        </div>

        {{-- Quick CTA Buttons --}}
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('tourist.accommodations') }}" class="bg-white text-ocean-900 hover:bg-sky-50 font-extrabold px-5 py-3 rounded-2xl shadow-lg transition no-underline text-sm flex items-center gap-2">
                <i class="bi bi-building"></i>Browse Room &amp; Cottage
            </a>
            <a href="{{ route('tour.viewer') }}" target="_blank" class="bg-sky-500/30 hover:bg-sky-500/40 text-white border border-white/30 backdrop-blur-md font-bold px-5 py-3 rounded-2xl shadow transition no-underline text-sm flex items-center gap-2">
                <i class="bi bi-badge-vr"></i>360° Virtual Tour
            </a>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">

    {{-- ── Quick Stats Grid ─────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-ocean-50 text-ocean-600 flex items-center justify-center text-xl font-bold">
                <i class="bi bi-calendar2-check-fill"></i>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mb-0.5">Active Stays</p>
                <h3 class="text-2xl font-black text-slate-800 mb-0">{{ $activeBookings->count() }}</h3>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold">
                <i class="bi bi-check2-circle"></i>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mb-0.5">Completed</p>
                <h3 class="text-2xl font-black text-slate-800 mb-0">{{ $completedCount }}</h3>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold">
                <i class="bi bi-star-fill"></i>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mb-0.5">My Reviews</p>
                <h3 class="text-2xl font-black text-slate-800 mb-0">{{ $reviewsCount }}</h3>
            </div>
        </div>

        <a href="{{ route('tour.viewer') }}" target="_blank" class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex items-center gap-4 hover:border-sky-300 hover:shadow-md transition no-underline group">
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl font-bold group-hover:scale-110 transition">
                <i class="bi bi-camera-video"></i>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mb-0.5">Virtual Tour</p>
                <span class="text-xs font-extrabold text-sky-600 flex items-center gap-1">360° Explore <i class="bi bi-arrow-up-right text-[10px]"></i></span>
            </div>
        </a>
    </div>

    {{-- ── Active Reservations Card ─────────────────────────────────────── --}}
    @if($activeBookings->count() > 0)
    <div class="bg-white rounded-3xl p-6 shadow-md border border-sky-100">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-ocean-50 text-ocean-600 flex items-center justify-center text-lg font-bold">
                    <i class="bi bi-ticket-perforated-fill"></i>
                </div>
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900 mb-0">Current &amp; Upcoming Reservations</h3>
                    <p class="text-xs text-slate-500 mb-0">Show this reference to resort staff upon check-in</p>
                </div>
            </div>
            <a href="{{ route('tourist.bookings') }}" class="text-xs font-bold text-ocean-600 hover:text-ocean-800 no-underline">
                View All <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            @foreach($activeBookings as $booking)
            <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200/80 hover:border-sky-300 transition">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-mono font-extrabold text-slate-700 bg-white px-2.5 py-1 rounded-md border border-slate-200">
                        {{ $booking->reference_no }}
                    </span>
                    @php
                        $badgeClasses = [
                            'pending' => 'bg-amber-100 text-amber-800 border-amber-200',
                            'paid' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                            'checked_in' => 'bg-sky-100 text-sky-800 border-sky-200',
                        ];
                    @endphp
                    <span class="text-xs font-bold px-3 py-1 rounded-full border uppercase tracking-wider {{ $badgeClasses[$booking->status] ?? 'bg-slate-100 text-slate-800' }}">
                        {{ str_replace('_', ' ', $booking->status) }}
                    </span>
                </div>

                <h4 class="text-base font-bold text-slate-900 mb-1">{{ $booking->accommodationUnit->unit_number ?? 'Exclusive Full Resort' }}</h4>

                <div class="text-xs text-slate-600 space-y-1 mb-4">
                    <div class="flex items-center gap-2">
                        <i class="bi bi-calendar-event text-sky-600"></i>
                        <span>Check-in: <strong>{{ \Carbon\Carbon::parse($booking->booking_date)->format('M d, Y') }}</strong></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="bi bi-people text-sky-600"></i>
                        <span>Guests: <strong>{{ $booking->guests_count }} pax</strong></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="bi bi-cash-stack text-sky-600"></i>
                        <span>Total: <strong>₱{{ number_format($booking->total_amount, 2) }}</strong></span>
                    </div>
                </div>

                {{-- Action buttons depending on status --}}
                @if($booking->status === 'pending')
                <div class="bg-amber-50 rounded-xl p-3 border border-amber-200 text-xs text-amber-900 mb-3">
                    <p class="font-bold mb-1"><i class="bi bi-clock-history me-1"></i>Payment Pending</p>
                    <p class="mb-2 text-slate-600">Please send payment via GCash or pay at front desk.</p>
                    <a href="{{ route('tourist.bookings') }}" class="btn btn-warning btn-sm font-bold text-xs rounded-lg w-full">
                        View Payment &amp; Receipt
                    </a>
                </div>
                @elseif($booking->status === 'paid')
                <div class="bg-emerald-50 rounded-xl p-3 border border-emerald-200 text-xs text-emerald-900">
                    <i class="bi bi-check-circle-fill me-1 text-emerald-600"></i>Booking Confirmed! Present your reference <strong>{{ $booking->reference_no }}</strong> at reception.
                </div>
                @elseif($booking->status === 'checked_in')
                <div class="bg-sky-50 rounded-xl p-3 border border-sky-200 text-xs text-sky-900">
                    <i class="bi bi-brightness-high-fill me-1 text-sky-600"></i>Enjoy your stay at Talisay Beach Resort!
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Featured Accommodations Showcase ───────────────────────────── --}}
    <div id="accommodations" class="scroll-mt-24">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900 mb-1">Featured Rooms &amp; Cottages</h2>
                <p class="text-sm text-slate-500 mb-0">Choose your accommodation and reserve your stay online instantly</p>
            </div>
            <a href="{{ route('tourist.accommodations') }}" class="text-xs font-bold text-ocean-600 hover:text-ocean-800 no-underline flex items-center gap-1">
                View All 20 Units <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            @foreach($accommodationUnits as $unit)
            <div class="bg-white rounded-3xl shadow-md border border-slate-100 overflow-hidden flex flex-col hover:shadow-xl transition group">
                {{-- Accommodation Image Header --}}
                <div class="h-48 bg-slate-200 relative overflow-hidden">
                    <img src="{{ !empty($unit->images) ? asset('storage/' . $unit->images[0]) : 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800&q=80' }}" alt="{{ $unit->unit_number }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 to-transparent"></div>
                    <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-md px-3 py-1 rounded-full text-xs font-bold text-ocean-900 shadow">
                        Max {{ $unit->max_occupancy }} Pax
                    </div>
                    <div class="absolute top-4 left-4">
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider {{ $unit->variant === 'premium' ? 'bg-amber-500 text-white' : 'bg-ocean-600 text-white' }}">
                            {{ $unit->variant_label }} {{ $unit->type_label }}
                        </span>
                    </div>
                    <div class="absolute bottom-4 left-4 text-white">
                        <span class="text-xs font-bold text-sky-300 block">NIGHTLY RATE</span>
                        <span class="text-2xl font-extrabold">₱{{ number_format($unit->price_per_night, 2) }}</span>
                    </div>
                </div>

                {{-- Content Body --}}
                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-slate-900 mb-2">{{ $unit->unit_number }}</h3>
                        <p class="text-xs text-slate-600 line-clamp-3 leading-relaxed mb-4">
                            {{ $unit->description }}
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('tourist.accommodations.detail', $unit->id) }}" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs py-2.5 rounded-xl text-center no-underline transition flex items-center justify-center gap-1">
                            <i class="bi bi-eye"></i>Details
                        </a>
                        <button @click="$dispatch('open-booking-modal', { unitId: {{ $unit->id }}, unitName: '{{ addslashes($unit->unit_number) }}', unitPrice: {{ $unit->price_per_night }}, maxCap: {{ $unit->max_occupancy }} })" class="flex-1 bg-ocean-600 hover:bg-ocean-800 text-white font-bold text-xs py-2.5 rounded-xl shadow-md transition flex items-center justify-center gap-1.5">
                            <i class="bi bi-calendar-plus"></i>Book Now
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── 360° Virtual Tour Banner ────────────────────────────────────── --}}
    <div class="bg-slate-900 rounded-3xl p-8 text-white relative overflow-hidden shadow-2xl flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="relative z-10 max-w-xl">
            <span class="inline-block bg-sky-500/20 text-sky-300 border border-sky-400/30 text-xs font-bold px-3 py-1 rounded-full mb-3 uppercase tracking-wider">
                <i class="bi bi-badge-vr me-1"></i>360° Interactive Tour
            </span>
            <h3 class="text-2xl sm:text-3xl font-extrabold mb-2 text-white">Explore Talisay Resort Before You Visit</h3>
            <p class="text-xs sm:text-sm text-slate-300 mb-0 leading-relaxed">
                Take an immersive virtual tour of our cottages, beachfront cabins, snorkeling reef, and restaurant area right from your browser.
            </p>
        </div>
        <div class="relative z-10">
            <a href="{{ route('tour.viewer') }}" target="_blank" class="bg-sky-500 hover:bg-sky-400 text-white font-extrabold px-6 py-3.5 rounded-2xl shadow-lg transition no-underline text-sm inline-flex items-center gap-2">
                <i class="bi bi-play-circle-fill text-lg"></i>Launch 360° Tour
            </a>
        </div>
    </div>

</div>

{{-- ── Booking Modal (AlpineJS driven with Real-Time Conflict Detection) ── --}}
<div x-data="{
        open: false,
        unitId: '',
        unitName: '',
        unitPrice: 0,
        maxCap: 10,
        guests: 1,
        date: '{{ date('Y-m-d') }}',
        checkOut: '{{ date('Y-m-d', strtotime('+1 day')) }}',
        nights: 1,
        isAvailable: true,
        checkingAvailability: false,
        conflictMessage: '',

        init() {
            this.recalculate();
        },

        recalculate() {
            if (!this.date) this.date = '{{ date('Y-m-d') }}';
            if (!this.checkOut || this.checkOut <= this.date) {
                const d = new Date(this.date);
                d.setDate(d.getDate() + 1);
                this.checkOut = d.toISOString().split('T')[0];
            }
            const d1 = new Date(this.date);
            const d2 = new Date(this.checkOut);
            const diffTime = Math.abs(d2 - d1);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            this.nights = diffDays > 0 ? diffDays : 1;

            this.verifyAvailability();
        },

        async verifyAvailability() {
            if (!this.unitId || !this.date || !this.checkOut) return;
            this.checkingAvailability = true;
            try {
                const res = await fetch(`/tourist/accommodations/${this.unitId}/check-availability?check_in=${this.date}&check_out=${this.checkOut}`);
                const data = await res.json();
                this.isAvailable = data.available;
                this.conflictMessage = data.available ? '' : (data.conflict_message || 'This unit is already booked on the selected dates. Please choose different dates.');
            } catch (e) {
                this.isAvailable = true;
                this.conflictMessage = '';
            } finally {
                this.checkingAvailability = false;
            }
        }
    }"
    @open-booking-modal.window="
        open = true;
        unitId = $event.detail.unitId;
        unitName = $event.detail.unitName;
        unitPrice = $event.detail.unitPrice;
        maxCap = $event.detail.maxCap;
        guests = 1;
        recalculate();
    "
    x-show="open"
    class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    x-cloak>

    <div @click.outside="open = false" class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 space-y-4 max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <span class="text-xs font-bold text-ocean-600 block">RESERVATION</span>
                <h3 class="text-lg font-extrabold text-slate-900 mb-0" x-text="unitName"></h3>
            </div>
            <button @click="open = false" class="text-slate-400 hover:text-slate-700"><i class="bi bi-x-lg"></i></button>
        </div>

        <form action="{{ route('tourist.bookings.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="accommodation_unit_id" :value="unitId">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Check-in Date</label>
                    <input type="date" name="booking_date" x-model="date" @change="recalculate()" min="{{ date('Y-m-d') }}" class="w-full form-control rounded-xl text-sm" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Check-out Date</label>
                    <input type="date" name="check_out_date" x-model="checkOut" @change="recalculate()" :min="date" class="w-full form-control rounded-xl text-sm" required>
                </div>
            </div>

            {{-- Real-time Availability Feedback Badge --}}
            <div x-show="checkingAvailability" class="text-xs text-slate-500 flex items-center gap-1.5 py-1">
                <i class="bi bi-hourglass-split animate-spin text-ocean-600"></i> Checking date availability...
            </div>
            <div x-show="!checkingAvailability && !isAvailable" class="bg-red-50 border border-red-200 text-red-700 p-3 rounded-xl text-xs flex items-start gap-2">
                <i class="bi bi-exclamation-octagon-fill text-red-500 text-sm mt-0.5"></i>
                <div>
                    <strong class="block font-bold">Dates Unavailable / Conflict</strong>
                    <span x-text="conflictMessage"></span>
                </div>
            </div>
            <div x-show="!checkingAvailability && isAvailable" class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-2.5 rounded-xl text-xs flex items-center gap-2">
                <i class="bi bi-check-circle-fill text-emerald-600 text-sm"></i>
                <span>Unit is <strong>available</strong> for your <span x-text="nights"></span>-night stay!</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Number of Guests (Pax)</label>
                <input type="number" name="guests_count" x-model="guests" min="1" :max="maxCap" class="w-full form-control rounded-xl text-sm" required>
                <p class="text-xs text-slate-400 mt-1">Maximum occupancy for this unit is <span x-text="maxCap"></span> guests.</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Special Requests (optional)</label>
                <textarea name="special_requests" rows="2" class="w-full form-control rounded-xl text-sm" placeholder="e.g. Extra pillows, early arrival..."></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Payment Method</label>
                <select name="payment_method" class="w-full form-select text-sm rounded-xl" required>
                    <option value="gcash">GCash (Online Receipt Verification)</option>
                    <option value="cash">Cash on Arrival (Pay at Front Desk)</option>
                    <option value="paypal">PayPal</option>
                    <option value="card">Credit / Debit Card</option>
                </select>
            </div>

            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-600 block">Total Stay Cost (<span x-text="nights + ' Night' + (nights > 1 ? 's' : '')"></span>):</span>
                    <span class="text-[11px] text-slate-400" x-text="'₱' + unitPrice.toLocaleString(undefined, {minimumFractionDigits: 2}) + ' / night'"></span>
                </div>
                <span class="text-xl font-black text-ocean-900" x-text="'₱' + (unitPrice * nights).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="button" @click="open = false" class="flex-1 btn btn-light rounded-xl font-bold text-sm">Cancel</button>
                <button type="submit" :disabled="!isAvailable || checkingAvailability" class="flex-1 bg-ocean-600 hover:bg-ocean-800 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-sm py-2.5 rounded-xl shadow-md transition">Confirm Booking</button>
            </div>
        </form>
    </div>
</div>

@endsection
