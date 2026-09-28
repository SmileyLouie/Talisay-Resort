@extends('layouts.tourist')

@section('title', 'Rooms & Cottages - Talisay Beach Resort')

@section('content')

{{-- Header Banner --}}
<div class="bg-ocean-gradient text-white py-12 px-4 shadow-lg mb-8">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-3 py-1 rounded-full text-xs font-bold text-sky-200 uppercase tracking-wider mb-2">
                <i class="bi bi-building"></i> 20 Total Resort Units
            </div>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-white mb-2 tracking-tight">Rooms & Beachfront Cottages</h1>
            <p class="text-sm text-sky-100 max-w-2xl mb-0">
                Explore our curated selection of 10 modern rooms and 10 scenic cottages. Experience 360° virtual video tours before you reserve!
            </p>
        </div>
        <a href="{{ route('tour.viewer') }}" target="_blank" class="bg-white/15 hover:bg-white/25 text-white border border-white/20 px-4 py-2.5 rounded-2xl text-xs font-bold transition flex items-center gap-2 shadow-sm">
            <i class="bi bi-camera-video text-sky-300 text-base"></i>Full 360° Virtual Tour
        </a>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 pb-16">

    {{-- Filter Tabs --}}
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-3 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('tourist.accommodations', ['filter' => 'all']) }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition {{ ($filter ?? 'all') === 'all' ? 'bg-ocean-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                All Room &amp; Cottage ({{ $units->count() }})
            </a>
            <a href="{{ route('tourist.accommodations', ['filter' => 'rooms']) }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition {{ ($filter ?? '') === 'rooms' ? 'bg-ocean-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                <i class="bi bi-door-closed me-1"></i>Rooms (10)
            </a>
            <a href="{{ route('tourist.accommodations', ['filter' => 'cottages']) }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition {{ ($filter ?? '') === 'cottages' ? 'bg-ocean-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                <i class="bi bi-house-door me-1"></i>Cottages (10)
            </a>
            <a href="{{ route('tourist.accommodations', ['filter' => 'normal']) }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition {{ ($filter ?? '') === 'normal' ? 'bg-ocean-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                Normal Units
            </a>
            <a href="{{ route('tourist.accommodations', ['filter' => 'premium']) }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold transition {{ ($filter ?? '') === 'premium' ? 'bg-amber-500 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                <i class="bi bi-star-fill me-1 text-xs"></i>Premium Units
            </a>
        </div>
        <div class="text-xs text-slate-500 font-semibold px-2">
            Showing {{ $units->count() }} unit{{ $units->count() === 1 ? '' : 's' }}
        </div>
    </div>

    {{-- Grid of Units --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($units as $unit)
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col group">
            
            {{-- Unit Image / Visual Banner --}}
            <div class="relative h-56 bg-slate-100 overflow-hidden">
                @if($unit->first_image)
                    <img src="{{ asset('storage/' . $unit->first_image) }}" alt="{{ $unit->unit_number }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                @else
                    {{-- Default placeholder styled as luxurious room/cottage preview --}}
                    <div class="w-full h-full bg-gradient-to-br {{ $unit->unit_type === 'room' ? 'from-sky-800 to-ocean-950' : 'from-teal-800 to-emerald-950' }} flex flex-col items-center justify-center text-white p-6 text-center">
                        <i class="bi {{ $unit->unit_type === 'room' ? 'bi-door-open-fill' : 'bi-house-heart-fill' }} text-4xl text-white/80 mb-2"></i>
                        <span class="text-lg font-black tracking-wide">{{ $unit->unit_number }}</span>
                        <span class="text-xs text-sky-200 uppercase tracking-widest font-semibold">{{ $unit->variant_label }} {{ $unit->type_label }}</span>
                    </div>
                @endif

                {{-- Badges on top of card --}}
                <div class="absolute top-3 left-3 flex flex-wrap gap-1.5">
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider backdrop-blur-md shadow-sm {{ $unit->variant === 'premium' ? 'bg-amber-500/90 text-white' : 'bg-sky-600/90 text-white' }}">
                        {{ $unit->variant_label }}
                    </span>
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-slate-900/80 backdrop-blur-md text-white shadow-sm">
                        {{ $unit->type_label }}
                    </span>
                </div>

                <div class="absolute top-3 right-3">
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold backdrop-blur-md shadow-sm inline-flex items-center gap-1 {{ $unit->is_available ? 'bg-emerald-500/90 text-white' : 'bg-red-500/90 text-white' }}">
                        <i class="bi bi-circle-fill text-[8px]"></i> {{ $unit->is_available ? 'Available' : 'Booked' }}
                    </span>
                </div>

                {{-- 360 Tour Tag --}}
                <div class="absolute bottom-3 left-3 bg-black/60 backdrop-blur-md text-white text-[11px] font-semibold px-2.5 py-1 rounded-lg flex items-center gap-1.5">
                    <i class="bi bi-camera-video-fill text-sky-400"></i> 360° Video Tour
                </div>
            </div>

            {{-- Content --}}
            <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-1.5">
                        <h3 class="text-xl font-extrabold text-slate-900">{{ $unit->unit_number }}</h3>
                        <div class="text-right">
                            <span class="text-xs text-slate-400 block font-semibold">Rate</span>
                            <span class="text-lg font-black text-ocean-700">₱{{ number_format($unit->price_per_night, 2) }}</span>
                        </div>
                    </div>

                    <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed mb-3">
                        {{ $unit->description }}
                    </p>

                    {{-- Specs Pill Bar --}}
                    <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                        <div class="flex items-center gap-1.5">
                            <i class="bi bi-people-fill text-ocean-600"></i>
                            <span>Max {{ $unit->max_occupancy }} Guests</span>
                        </div>
                        @if($unit->floor_area_sqm)
                        <div class="flex items-center gap-1.5">
                            <i class="bi bi-aspect-ratio text-ocean-600"></i>
                            <span>{{ $unit->floor_area_sqm }} sqm</span>
                        </div>
                        @endif
                        @if($unit->bed_configuration)
                        <div class="col-span-2 flex items-center gap-1.5 truncate">
                            <i class="bi bi-lamp-fill text-ocean-600"></i>
                            <span class="truncate">{{ $unit->bed_configuration }}</span>
                        </div>
                        @endif
                    </div>

                    {{-- Amenities Preview --}}
                    @if(!empty($unit->amenities) && is_array($unit->amenities))
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach(array_slice($unit->amenities, 0, 3) as $amenity)
                        <span class="text-[10px] font-semibold bg-sky-50 text-sky-800 px-2 py-0.5 rounded-md border border-sky-100 flex items-center gap-1">
                            <i class="bi bi-check2 text-sky-600"></i> {{ $amenity }}
                        </span>
                        @endforeach
                        @if(count($unit->amenities) > 3)
                        <span class="text-[10px] font-bold text-slate-400 px-1 py-0.5">
                            +{{ count($unit->amenities) - 3 }} more
                        </span>
                        @endif
                    </div>
                    @endif
                </div>

                {{-- Action Buttons --}}
                <div class="pt-3 border-t border-slate-100 flex items-center gap-2">
                    <a href="{{ route('tourist.accommodations.detail', $unit->id) }}" 
                       class="flex-1 bg-slate-100 hover:bg-ocean-50 text-ocean-900 font-bold text-xs py-2.5 rounded-xl text-center transition flex items-center justify-center gap-1.5 border border-slate-200">
                        <i class="bi bi-eye-fill text-ocean-600"></i>Details & 360°
                    </a>
                    <button type="button" 
                       @click="$dispatch('open-booking-modal', { unitId: {{ $unit->id }}, unitName: {{ Js::from($unit->unit_number) }}, unitPrice: {{ $unit->price_per_night }}, maxCap: {{ $unit->max_occupancy }} })" 
                       class="flex-1 bg-ocean-600 hover:bg-ocean-700 text-white font-bold text-xs py-2.5 rounded-xl text-center transition shadow-sm flex items-center justify-center gap-1">
                        <i class="bi bi-calendar-plus"></i>Book Now
                    </button>
                </div>
            </div>

        </div>
        @endforeach
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
            const urlParams = new URLSearchParams(window.location.search);
            const bookId = urlParams.get('book');
            if (bookId) {
                // Find matching unit from page
                const targetBtn = document.querySelector(`[data-unit-id='${bookId}']`);
                if (targetBtn) {
                    targetBtn.click();
                }
            }
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

        <form action="{{ route('tourist.bookings.store') }}" method="POST" class="space-y-4" data-loading>
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
