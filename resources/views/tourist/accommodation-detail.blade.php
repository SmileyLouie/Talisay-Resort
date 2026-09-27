@extends('layouts.tourist')

@section('title', "{$unit->unit_number} Details & 360° Tour - Talisay Beach Resort")

@section('content')

{{-- Breadcrumbs & Top Bar --}}
<div class="bg-ocean-900 text-white py-6 px-4 border-b border-white/10 shadow-md">
    <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2 text-xs text-sky-200">
            <a href="{{ route('tourist.dashboard') }}" class="hover:text-white transition">Dashboard</a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <a href="{{ route('tourist.accommodations') }}" class="hover:text-white transition">Rooms & Cottages</a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span class="text-white font-bold">{{ $unit->unit_number }}</span>
        </div>

        <a href="{{ route('tourist.accommodations') }}" class="text-xs font-semibold text-sky-200 hover:text-white flex items-center gap-1.5 transition">
            <i class="bi bi-arrow-left"></i> Back to Room &amp; Cottage
        </a>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8 pb-20">

    {{-- Header Banner --}}
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider {{ $unit->variant === 'premium' ? 'bg-amber-500 text-white' : 'bg-ocean-600 text-white' }}">
                    {{ $unit->variant_label }} {{ $unit->type_label }}
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1.5 {{ $unit->is_available ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-red-100 text-red-800 border border-red-200' }}">
                    <i class="bi bi-circle-fill text-[8px] {{ $unit->is_available ? 'text-emerald-500' : 'text-red-500' }}"></i> {{ $unit->is_available ? 'Available for Booking' : 'Currently Reserved' }}
                </span>
            </div>

            <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mb-0">
                {{ $unit->unit_number }}
            </h1>

            <p class="text-xs text-slate-500 max-w-xl mb-0 leading-relaxed">
                Experience exceptional coastal comfort in our {{ strtolower($unit->variant_label) }} {{ strtolower($unit->type_label) }}, located right on the beachfront of Talisay Beach Resort.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row md:flex-col items-start sm:items-center md:items-end justify-between gap-4 border-t md:border-t-0 pt-4 md:pt-0 border-slate-100">
            <div class="text-left md:text-right">
                <span class="text-xs text-slate-400 font-semibold block">Nightly Rate</span>
                <span class="text-3xl font-black text-ocean-900">₱{{ number_format($unit->price_per_night, 2) }}</span>
                <span class="text-[11px] text-slate-400 block">tax & resort access included</span>
            </div>

            <button type="button" 
                @click="$dispatch('open-booking-modal', { unitId: {{ $unit->id }}, unitName: '{{ addslashes($unit->unit_number) }}', unitPrice: {{ $unit->price_per_night }}, maxCap: {{ $unit->max_occupancy }} })" 
                class="bg-ocean-600 hover:bg-ocean-700 text-white font-extrabold text-xs px-6 py-3.5 rounded-2xl shadow-md hover:shadow-lg transition flex items-center gap-2">
                <i class="bi bi-calendar-check-fill"></i> Reserve This Unit
            </button>
        </div>
    </div>

    {{-- 360° Virtual Tour Section --}}
    <div class="bg-slate-900 text-white rounded-3xl overflow-hidden shadow-xl border border-slate-800">
        <div class="p-6 border-b border-slate-800 flex flex-wrap items-center justify-between gap-4 bg-slate-950/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-500/20 text-sky-400 flex items-center justify-center text-lg border border-sky-500/30">
                    <i class="bi bi-camera-video-fill"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-white mb-0">360° Interactive Virtual Video Tour</h2>
                    <p class="text-xs text-slate-400 mb-0">Take a virtual walk inside {{ $unit->unit_number }} before booking</p>
                </div>
            </div>

            <a href="{{ route('tour.viewer') }}" target="_blank" class="bg-white/10 hover:bg-white/20 text-sky-200 hover:text-white px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 border border-white/10">
                <i class="bi bi-fullscreen"></i> Open Full Resort Tour
            </a>
        </div>

        {{-- 360 Video Player / Viewer Container --}}
        <div class="relative bg-black h-[420px] sm:h-[480px] flex items-center justify-center overflow-hidden">
            @if($unit->tour_video_path)
                <video controls autoplay loop muted class="w-full h-full object-cover">
                    <source src="{{ asset('storage/' . $unit->tour_video_path) }}" type="video/mp4">
                    Your browser does not support HTML5 video.
                </video>
            @else
                {{-- Dynamic 360 simulation panorama viewer --}}
                <div class="w-full h-full relative flex items-center justify-center bg-gradient-to-br {{ $unit->unit_type === 'room' ? 'from-sky-950 via-slate-900 to-ocean-950' : 'from-teal-950 via-slate-900 to-emerald-950' }}">
                    
                    {{-- Ambient animated circles to simulate 360 viewer environment --}}
                    <div class="absolute inset-0 opacity-20 bg-[radial-gradient(#38bdf8_1px,transparent_1px)] [background-size:16px_16px]"></div>

                    <div class="text-center p-8 z-10 max-w-lg">
                        <div class="w-20 h-20 rounded-full bg-sky-500/20 text-sky-300 flex items-center justify-center mx-auto mb-4 text-3xl animate-pulse border-2 border-sky-400/40 shadow-[0_0_30px_rgba(56,189,248,0.3)]">
                            <i class="bi bi-eye"></i>
                        </div>
                        <h3 class="text-xl font-extrabold text-white mb-2">{{ $unit->unit_number }} 360° Panorama</h3>
                        <p class="text-xs text-slate-300 mb-6 leading-relaxed">
                            Interact with the full resort virtual panorama viewer to explore beachfront pathways, pool deck, and cottage zones.
                        </p>
                        <a href="{{ route('tour.viewer') }}" target="_blank" class="inline-flex items-center gap-2 bg-gradient-to-r from-sky-500 to-ocean-600 hover:from-sky-400 hover:to-ocean-500 text-white font-bold text-xs px-6 py-3 rounded-xl shadow-lg transition transform hover:scale-105">
                            <i class="bi bi-play-circle-fill text-base"></i> Launch 360° Video Simulation
                        </a>
                    </div>

                    <div class="absolute bottom-4 right-4 bg-black/70 backdrop-blur-md px-3 py-1.5 rounded-lg text-[11px] text-sky-200 border border-white/10 flex items-center gap-2">
                        <i class="bi bi-badge-hd-fill text-sky-400"></i> High-Definition 360° Media
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Specs & Amenities Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- Left: Details & Description --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900 mb-2">Room &amp; Cottage Overview</h3>
                    <p class="text-sm text-slate-600 leading-relaxed mb-0">
                        {{ $unit->description }}
                    </p>
                </div>

                {{-- Key Specifications Table --}}
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Unit Specifications</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center text-base">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] font-semibold">Max Occupancy</span>
                                <span class="font-bold text-slate-800">{{ $unit->max_occupancy }} Guests Maximum</span>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center text-base">
                                <i class="bi bi-aspect-ratio-fill"></i>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] font-semibold">Floor Area</span>
                                <span class="font-bold text-slate-800">{{ $unit->floor_area_sqm ? "{$unit->floor_area_sqm} sqm" : 'Spacious Layout' }}</span>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center text-base">
                                <i class="bi bi-lamp-fill"></i>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] font-semibold">Bed Configuration</span>
                                <span class="font-bold text-slate-800">{{ $unit->bed_configuration ?? 'Comfortable setup' }}</span>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center text-base">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] font-semibold">Location</span>
                                <span class="font-bold text-slate-800">Talisay Beach, Baybay City</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Amenities Checklist --}}
                @if(!empty($unit->amenities) && is_array($unit->amenities))
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Included Amenities & Features</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        @foreach($unit->amenities as $amenity)
                        <div class="flex items-center gap-2.5 text-xs text-slate-700 font-semibold bg-sky-50/50 p-2.5 rounded-xl border border-sky-100">
                            <i class="bi bi-check-circle-fill text-sky-600 text-sm"></i>
                            <span>{{ $amenity }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Right: Reservation Card & Assistance --}}
        <div class="space-y-6">
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm space-y-4 sticky top-24">
                <h3 class="text-base font-extrabold text-slate-900 mb-0">Book {{ $unit->unit_number }}</h3>
                <p class="text-xs text-slate-500">
                    Secure your stay at Talisay Beach Resort. Instant confirmation with multiple convenient payment channels.
                </p>

                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Base Rate</span>
                        <span class="font-bold text-slate-800">₱{{ number_format($unit->price_per_night, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Resort & Beach Access</span>
                        <span class="font-bold text-emerald-600">FREE</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">360° Orientation</span>
                        <span class="font-bold text-sky-600">Included</span>
                    </div>
                </div>

                <button type="button" 
                    @click="$dispatch('open-booking-modal', { unitId: {{ $unit->id }}, unitName: '{{ addslashes($unit->unit_number) }}', unitPrice: {{ $unit->price_per_night }}, maxCap: {{ $unit->max_occupancy }} })" 
                    class="w-full bg-ocean-600 hover:bg-ocean-700 text-white font-extrabold text-xs py-3.5 rounded-2xl text-center block transition shadow-md flex items-center justify-center gap-2">
                    <i class="bi bi-calendar-plus-fill"></i> Reserve {{ $unit->unit_number }} Now
                </button>

                <div class="pt-3 border-t border-slate-100 text-[11px] text-slate-400 flex items-center justify-center gap-3">
                    <span class="flex items-center gap-1"><i class="bi bi-shield-check text-emerald-500"></i> Secure Booking</span>
                    <span class="flex items-center gap-1"><i class="bi bi-wallet2 text-sky-500"></i> GCash / Cash / Card</span>
                </div>
            </div>
        </div>

    </div>

    {{-- Similar Accommodations --}}
    @if(isset($similar) && $similar->count() > 0)
    <div class="space-y-4 pt-6">
        <h3 class="text-xl font-extrabold text-slate-900">Similar {{ $unit->type_label }}s You May Like</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($similar as $sim)
            <a href="{{ route('tourist.accommodations.detail', $sim->id) }}" class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm hover:shadow-md transition block group no-underline">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <span class="text-sm font-extrabold text-slate-900 group-hover:text-ocean-600 transition">{{ $sim->unit_number }}</span>
                    <span class="text-xs font-black text-ocean-700">₱{{ number_format($sim->price_per_night, 2) }}</span>
                </div>
                <p class="text-xs text-slate-500 line-clamp-2 mb-3">{{ $sim->description }}</p>
                <div class="flex items-center justify-between text-[11px] text-slate-400 font-semibold pt-2 border-t border-slate-100">
                    <span>Max {{ $sim->max_occupancy }} Guests</span>
                    <span class="text-sky-600 flex items-center gap-1">View Unit <i class="bi bi-arrow-right"></i></span>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

</div>

{{-- ── Booking Modal (AlpineJS driven with Real-Time Conflict Detection) ── --}}
<div x-data="{
        open: false,
        unitId: {{ $unit->id }},
        unitName: '{{ addslashes($unit->unit_number) }}',
        unitPrice: {{ $unit->price_per_night }},
        maxCap: {{ $unit->max_occupancy }},
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
        unitId = $event.detail.unitId || {{ $unit->id }};
        unitName = $event.detail.unitName || '{{ addslashes($unit->unit_number) }}';
        unitPrice = $event.detail.unitPrice || {{ $unit->price_per_night }};
        maxCap = $event.detail.maxCap || {{ $unit->max_occupancy }};
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
