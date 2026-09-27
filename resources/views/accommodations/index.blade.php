@extends('layouts.app')

@section('title', 'Rooms & Cottages Management - Talisay Beach Resort')

@section('content')

{{-- ── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100/80 text-sky-800 border border-sky-200">
                <i class="bi bi-building text-[10px] text-sky-600"></i>
                Resort Units
            </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-1">
            Room &amp; Cottage Management
        </h1>
        <p class="text-sm text-slate-500 mb-0">
            Manage the 20 resort rooms and cottages, 360° virtual video tours, amenities, and real-time availability.
        </p>
    </div>

    <button class="btn-ocean" data-bs-toggle="modal" data-bs-target="#createUnitModal">
        <i class="bi bi-plus-circle-fill"></i>
        <span>Add New Unit</span>
    </button>
</div>

{{-- ── Stats KPI Cards ─────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
    
    {{-- Total Units --}}
    <div class="stat-card-clean flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 border border-sky-100 flex items-center justify-center text-xl font-bold flex-shrink-0">
            <i class="bi bi-building"></i>
        </div>
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">Total Units</p>
            <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mb-0">{{ $stats['total'] }}</h3>
        </div>
    </div>

    {{-- Available Units --}}
    <div class="stat-card-clean flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xl font-bold flex-shrink-0">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">Available Now</p>
            <h3 class="text-2xl sm:text-3xl font-extrabold text-emerald-600 mb-0">{{ $stats['available'] }}</h3>
        </div>
    </div>

    {{-- Rooms --}}
    <div class="stat-card-clean flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xl font-bold flex-shrink-0">
            <i class="bi bi-door-closed-fill"></i>
        </div>
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">Rooms (10)</p>
            <h3 class="text-2xl sm:text-3xl font-extrabold text-indigo-900 mb-0">{{ $stats['rooms'] }}</h3>
        </div>
    </div>

    {{-- Cottages --}}
    <div class="stat-card-clean flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xl font-bold flex-shrink-0">
            <i class="bi bi-house-door-fill"></i>
        </div>
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">Cottages (10)</p>
            <h3 class="text-2xl sm:text-3xl font-extrabold text-amber-600 mb-0">{{ $stats['cottages'] }}</h3>
        </div>
    </div>
</div>

{{-- ── Filter Tabs ────────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-3 sm:p-4 mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap gap-1.5">
        <a href="{{ route('accommodations.index', ['filter' => 'all']) }}" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition no-underline {{ ($filter ?? 'all') === 'all' ? 'bg-sky-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            All Units ({{ $stats['total'] }})
        </a>
        <a href="{{ route('accommodations.index', ['filter' => 'rooms']) }}" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition no-underline {{ ($filter ?? '') === 'rooms' ? 'bg-sky-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            <i class="bi bi-door-closed me-1"></i>Rooms ({{ $stats['rooms'] }})
        </a>
        <a href="{{ route('accommodations.index', ['filter' => 'cottages']) }}" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition no-underline {{ ($filter ?? '') === 'cottages' ? 'bg-sky-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            <i class="bi bi-house-door me-1"></i>Cottages ({{ $stats['cottages'] }})
        </a>
        <a href="{{ route('accommodations.index', ['filter' => 'normal']) }}" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition no-underline {{ ($filter ?? '') === 'normal' ? 'bg-sky-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Normal
        </a>
        <a href="{{ route('accommodations.index', ['filter' => 'premium']) }}" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition no-underline {{ ($filter ?? '') === 'premium' ? 'bg-amber-500 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            <i class="bi bi-star-fill text-[10px] me-1"></i>Premium
        </a>
    </div>

    <span class="text-xs text-slate-400 font-semibold px-2">
        Showing {{ $units->count() }} of {{ $stats['total'] }} units
    </span>
</div>

{{-- ── Accommodations Table Card ───────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table-clean">
            <thead>
                <tr>
                    <th>Unit Details</th>
                    <th>Category &amp; Variant</th>
                    <th>Max Guests</th>
                    <th>Nightly Rate</th>
                    <th>360° Video Tour</th>
                    <th>Availability Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($units as $unit)
                <tr>
                    {{-- Unit Details --}}
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg flex-shrink-0 {{ $unit->unit_type === 'room' ? 'bg-sky-100 text-sky-700 border border-sky-200' : 'bg-amber-100 text-amber-700 border border-amber-200' }}">
                                <i class="bi {{ $unit->unit_type === 'room' ? 'bi-door-closed-fill' : 'bi-house-door-fill' }}"></i>
                            </div>
                            <div>
                                <div class="font-extrabold text-slate-900 text-sm">{{ $unit->unit_number }}</div>
                                <div class="text-[11px] text-slate-400">{{ $unit->bed_configuration ?? ($unit->floor_area_sqm ? $unit->floor_area_sqm . ' sqm' : 'Resort accommodation') }}</div>
                            </div>
                        </div>
                    </td>

                    {{-- Category & Variant --}}
                    <td>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            @if($unit->variant === 'premium')
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300 inline-flex items-center gap-1">
                                <i class="bi bi-stars text-amber-600"></i> Premium
                            </span>
                            @else
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                Normal
                            </span>
                            @endif
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-sky-50 text-sky-700 border border-sky-200/60 uppercase">
                                {{ $unit->type_label }}
                            </span>
                        </div>
                    </td>

                    {{-- Max Guests --}}
                    <td>
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1">
                            <i class="bi bi-people-fill text-sky-500"></i>
                            {{ $unit->max_occupancy }} Pax
                        </span>
                    </td>

                    {{-- Nightly Rate --}}
                    <td>
                        <span class="text-xs font-extrabold text-slate-900">₱{{ number_format($unit->price_per_night, 2) }}</span>
                    </td>

                    {{-- 360 Tour --}}
                    <td>
                        @if($unit->tour_video_path)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <i class="bi bi-camera-video-fill text-emerald-500"></i> Video Active
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500 border border-slate-200">
                            <i class="bi bi-badge-vr"></i> 360° Simulation
                        </span>
                        @endif
                    </td>

                    {{-- Availability Toggle --}}
                    <td>
                        <form action="{{ route('accommodations.toggle-availability', $unit->id) }}" method="POST" class="inline-block">
                            @csrf
                            <button type="submit" class="px-2.5 py-1 rounded-full text-xs font-bold border transition flex items-center gap-1.5 {{ $unit->is_available ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $unit->is_available ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                <span>{{ $unit->is_available ? 'Available' : 'Booked / Off' }}</span>
                            </button>
                        </form>
                    </td>

                    {{-- Actions --}}
                    <td class="text-end">
                        <div class="flex items-center justify-end gap-1.5">
                            <button class="btn-secondary-clean text-xs py-1 px-2.5" 
                                    onclick="openEditModal({{ json_encode($unit) }})">
                                <i class="bi bi-pencil-square text-slate-500"></i>
                                <span>Edit</span>
                            </button>
                            <form action="{{ route('accommodations.destroy', $unit->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete {{ $unit->unit_number }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 rounded-xl text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-200 transition" title="Delete unit">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-12 text-slate-400">
                        <i class="bi bi-inbox text-3xl block mb-2 text-slate-300"></i>
                        <p class="text-xs font-semibold mb-0">No accommodation units found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Create Unit Modal ────────────────────────────────────────────────── --}}
<div class="modal fade" id="createUnitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden">
            <div class="p-5 bg-gradient-to-r from-slate-900 to-sky-950 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-sky-300 text-lg">
                        <i class="bi bi-building-add"></i>
                    </div>
                    <div>
                        <h5 class="font-extrabold text-base mb-0 tracking-tight">Add New Room / Cottage</h5>
                        <p class="text-xs text-sky-200/70 mb-0 font-medium">Create a new room or cottage with pricing and 360° tour support</p>
                    </div>
                </div>
                <button type="button" class="text-white/70 hover:text-white w-8 h-8 rounded-lg flex items-center justify-center hover:bg-white/10 transition" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form action="{{ route('accommodations.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-6 bg-slate-50/50 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                        <div class="md:col-span-6">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Unit Name / Identifier</label>
                            <input type="text" name="unit_number" class="w-full form-control-clean text-xs" placeholder="Unit name or number" required>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Category</label>
                            <select name="unit_type" class="w-full form-select-clean text-xs font-medium" required>
                                <option value="room">Room</option>
                                <option value="cottage">Cottage</option>
                            </select>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Variant</label>
                            <select name="variant" class="w-full form-select-clean text-xs font-medium" required>
                                <option value="normal">Normal</option>
                                <option value="premium">Premium</option>
                            </select>
                        </div>

                        <div class="md:col-span-4">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Nightly Rate (PHP)</label>
                            <input type="number" step="0.01" name="price_per_night" class="w-full form-control-clean text-xs" value="1500.00" required>
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Max Occupancy (Pax)</label>
                            <input type="number" name="max_occupancy" class="w-full form-control-clean text-xs" value="2" min="1" required>
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Floor Area (sqm)</label>
                            <input type="number" step="0.1" name="floor_area_sqm" class="w-full form-control-clean text-xs" placeholder="Floor area (sqm)">
                        </div>

                        <div class="md:col-span-12">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Bed Configuration</label>
                            <input type="text" name="bed_configuration" class="w-full form-control-clean text-xs" placeholder="Bed arrangement">
                        </div>

                        <div class="md:col-span-12">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Description</label>
                            <textarea name="description" rows="3" class="w-full form-control-clean text-xs" placeholder="Detailed overview of the room/cottage..."></textarea>
                        </div>

                        <div class="md:col-span-12">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Amenities (1 per line)</label>
                            <textarea name="amenities" rows="3" class="w-full form-control-clean text-xs" placeholder="Air-conditioning&#10;Private bathroom with hot shower&#10;Free Wi-Fi&#10;Smart TV"></textarea>
                        </div>

                        <div class="md:col-span-6">
                            <label class="block text-xs font-bold text-slate-600 mb-1">360° Virtual Tour Video (MP4 / WebM)</label>
                            <input type="file" name="tour_video" accept="video/mp4,video/webm" class="w-full form-control-clean text-xs">
                        </div>
                        <div class="md:col-span-6">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Photo Gallery Images</label>
                            <input type="file" name="images[]" multiple accept="image/*" class="w-full form-control-clean text-xs">
                        </div>
                    </div>
                </div>
                <div class="p-4 bg-white border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" class="btn-secondary-clean text-xs px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-ocean text-xs px-5 py-2">Save New Unit</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Edit Unit Modal ──────────────────────────────────────────────────── --}}
<div class="modal fade" id="editUnitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden">
            <div class="p-5 bg-gradient-to-r from-slate-900 to-sky-950 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-sky-300 text-lg">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div>
                        <h5 class="font-extrabold text-base mb-0 tracking-tight" id="editModalTitle">Edit Room / Cottage</h5>
                        <p class="text-xs text-sky-200/70 mb-0 font-medium">Update pricing, amenities, occupancy, or media</p>
                    </div>
                </div>
                <button type="button" class="text-white/70 hover:text-white w-8 h-8 rounded-lg flex items-center justify-center hover:bg-white/10 transition" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form id="editUnitForm" action="" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body p-6 bg-slate-50/50 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                        <div class="md:col-span-6">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Unit Name / Identifier</label>
                            <input type="text" name="unit_number" id="editUnitNumber" class="w-full form-control-clean text-xs" required>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Category</label>
                            <select name="unit_type" id="editUnitType" class="w-full form-select-clean text-xs font-medium" required>
                                <option value="room">Room</option>
                                <option value="cottage">Cottage</option>
                            </select>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Variant</label>
                            <select name="variant" id="editVariant" class="w-full form-select-clean text-xs font-medium" required>
                                <option value="normal">Normal</option>
                                <option value="premium">Premium</option>
                            </select>
                        </div>

                        <div class="md:col-span-4">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Nightly Rate (PHP)</label>
                            <input type="number" step="0.01" name="price_per_night" id="editPrice" class="w-full form-control-clean text-xs" required>
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Max Occupancy (Pax)</label>
                            <input type="number" name="max_occupancy" id="editOccupancy" class="w-full form-control-clean text-xs" min="1" required>
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Floor Area (sqm)</label>
                            <input type="number" step="0.1" name="floor_area_sqm" id="editFloorArea" class="w-full form-control-clean text-xs">
                        </div>

                        <div class="md:col-span-12">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Bed Configuration</label>
                            <input type="text" name="bed_configuration" id="editBedConfig" class="w-full form-control-clean text-xs">
                        </div>

                        <div class="md:col-span-12">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Description</label>
                            <textarea name="description" id="editDescription" rows="3" class="w-full form-control-clean text-xs"></textarea>
                        </div>

                        <div class="md:col-span-12">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Amenities (1 per line)</label>
                            <textarea name="amenities" id="editAmenities" rows="3" class="w-full form-control-clean text-xs"></textarea>
                        </div>

                        <div class="md:col-span-6">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Upload New 360° Tour Video (optional)</label>
                            <input type="file" name="tour_video" accept="video/mp4,video/webm" class="w-full form-control-clean text-xs">
                        </div>
                        <div class="md:col-span-6">
                            <label class="block text-xs font-bold text-slate-600 mb-1">Add More Photos</label>
                            <input type="file" name="images[]" multiple accept="image/*" class="w-full form-control-clean text-xs">
                        </div>
                    </div>
                </div>
                <div class="p-4 bg-white border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" class="btn-secondary-clean text-xs px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-ocean text-xs px-5 py-2">Update Unit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditModal(unit) {
    document.getElementById('editModalTitle').textContent = 'Edit ' + unit.unit_number;
    document.getElementById('editUnitForm').action = '/accommodations/' + unit.id;
    document.getElementById('editUnitNumber').value = unit.unit_number;
    document.getElementById('editUnitType').value = unit.unit_type;
    document.getElementById('editVariant').value = unit.variant;
    document.getElementById('editPrice').value = unit.price_per_night;
    document.getElementById('editOccupancy').value = unit.max_occupancy;
    document.getElementById('editFloorArea').value = unit.floor_area_sqm || '';
    document.getElementById('editBedConfig').value = unit.bed_configuration || '';
    document.getElementById('editDescription').value = unit.description || '';
    
    if (Array.isArray(unit.amenities)) {
        document.getElementById('editAmenities').value = unit.amenities.join('\n');
    } else {
        document.getElementById('editAmenities').value = '';
    }

    new bootstrap.Modal(document.getElementById('editUnitModal')).show();
}
</script>

@endsection
