@extends('layouts.app')

@section('title', ($kind === 'cottage' ? 'Cottage' : 'Room').' Management - Talisay Beach Resort')

@push('styles')
<style>
    .modal-dialog.unit-modal-dialog { max-width: 760px; max-height: calc(100vh - 2.5rem); margin: 1.25rem auto; }
    .unit-modal-content {
        max-height: calc(100vh - 2rem);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .unit-modal-form {
        display: flex;
        flex-direction: column;
        min-height: 0;
        flex: 1 1 auto;
        overflow: hidden;
    }
    .unit-modal-form .modal-body { overflow-y: auto; min-height: 0; }
    .amenity-chip { border: 1px solid #dbe3ec; background: #fff; color: #334155; border-radius: 999px; font-size: 12px; font-weight: 500; padding: 3px 10px; }
    .amenity-chip:hover { border-color: #1d6fbf; background: #e8f2fb; color: #155a9c; }
</style>
@endpush

@section('content')

@php
    $variant = $variant ?? 'all';
    $listRoute = $kind === 'cottage' ? 'accommodations.cottages' : 'accommodations.index';
    $noun = $kind === 'cottage' ? 'cottages' : 'rooms';
    $Noun = $kind === 'cottage' ? 'Cottage' : 'Room';
    $unitVariants = $unitVariants ?? collect();
    $amenityItems = $amenityItems ?? collect();
    $variantCards = $variantCards ?? collect();
    $variantTabs = collect([
        ['slug' => 'all', 'label' => 'All', 'count' => $stats['total']],
    ])->concat($variantCards);
@endphp

<div class="res-head">
    <div>
        <h1>{{ $kind === 'cottage' ? __('Cottages') : __('Rooms') }}</h1>
        <p>{{ $kind === 'cottage' ? __('Rates, guest limits, photos, 360° tours, and whether each cottage can be booked.') : __('Rates, guest limits, photos, 360° tours, and whether each room can be booked.') }}</p>
    </div>
    <div class="res-head-actions">
        <button type="button" class="btn-ocean" data-bs-toggle="modal" data-bs-target="#createUnitModal">
            <i class="bi bi-plus-lg"></i>
            <span>{{ $kind === 'cottage' ? __('Add cottage') : __('Add room') }}</span>
        </button>
    </div>
</div>

@if($errors->any())
<div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800" role="alert">
    {{ $errors->first() }}
</div>
@endif

<div class="unit-stats unit-stats-variants" data-live-region="unit-stats">
    <div><span>{{ $kind === 'cottage' ? __('Cottages') : __('Rooms') }}</span><strong>{{ $stats['total'] }}</strong></div>
    <div><span>{{ __('Available') }}</span><strong class="is-good">{{ $stats['available'] }}</strong></div>
    <div><span>{{ __('Unavailable') }}</span><strong class="{{ $stats['total'] - $stats['available'] > 0 ? 'is-bad' : '' }}">{{ $stats['total'] - $stats['available'] }}</strong></div>
    @foreach($variantCards as $card)
        <div><span>{{ __($card['label']) }}</span><strong>{{ $card['count'] }}</strong></div>
    @endforeach
</div>

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="res-toolbar">
        @php $currentTab = $variantTabs->firstWhere('slug', $variant) ?? $variantTabs->first(); @endphp
        <div class="dropdown" data-live-region="unit-variant-filter">
            <button type="button" class="manual-kind-btn {{ $variant !== 'all' ? 'is-active' : '' }}" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Filter by variant') }}">
                <span>{{ $currentTab['slug'] === 'all' ? ($kind === 'cottage' ? __('All cottages') : __('All rooms')) : __($currentTab['label']) }}</span>
                @if($currentTab['count'] !== null)<span class="unit-filter-count">{{ $currentTab['count'] }}</span>@endif
            </button>
            <ul class="dropdown-menu manual-kind-menu unit-filter-menu">
                @foreach($variantTabs as $tab)
                <li>
                    <a class="dropdown-item {{ $variant === $tab['slug'] ? 'active' : '' }}"
                       href="{{ $tab['slug'] === 'all' ? route($listRoute) : route($listRoute, ['variant' => $tab['slug']]) }}">
                        <span>{{ $tab['slug'] === 'all' ? ($kind === 'cottage' ? __('All cottages') : __('All rooms')) : __($tab['label']) }}</span>
                        @if($tab['count'] !== null)<span class="unit-filter-count">{{ $tab['count'] }}</span>@endif
                    </a>
                </li>
                @endforeach
            </ul>
        </div>
        <div class="res-search unit-search">
            <i class="bi bi-search"></i>
            <input type="search" id="unitSearch" class="form-control-clean" placeholder="{{ $kind === 'cottage' ? __('Find a cottage...') : __('Find a room...') }}" aria-label="{{ $kind === 'cottage' ? __('Find a cottage') : __('Find a room') }}" data-live-pause-if-filled>
        </div>
        <span class="unit-count" id="unitCount" data-total="{{ $units->count() }}" data-label="{{ __($kind === 'cottage' ? ':shown of :total cottages' : ':shown of :total rooms', ['shown' => $units->count(), 'total' => $stats['total']]) }}" data-live-region="unit-count">{{ __($kind === 'cottage' ? ':shown of :total cottages' : ':shown of :total rooms', ['shown' => $units->count(), 'total' => $stats['total']]) }}</span>
    </div>
    <div class="table-responsive" data-live-region="unit-list">
        <table class="table-clean unit-table">
            <thead>
                <tr>
                    <th>{{ $kind === 'cottage' ? __('Cottage') : __('Room') }}</th>
                    <th>{{ __('Variant') }}</th>
                    <th>{{ __('Guests') }}</th>
                    <th>{{ __('Per night') }}</th>
                    <th>{{ __('Per hour') }}</th>
                    <th>{{ __('360° tour') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($units as $unit)
                @php
                    $photo = $unit->first_image;
                    if ($photo && !str_starts_with($photo, 'http')) {
                        $photo = '/storage/'.ltrim($photo, '/');
                    }
                    $photoCount = is_array($unit->images) ? count($unit->images) : 0;
                @endphp
                <tr class="unit-row" data-unit-id="{{ $unit->id }}" data-search="{{ strtolower($unit->unit_number.' '.$unit->variant_label) }}">
                    <td>
                        <div class="unit-cell">
                            <div class="unit-thumb">
                                @if($photo)
                                    <img src="{{ $photo }}" alt="" loading="lazy">
                                @else
                                    <i class="bi {{ $kind === 'cottage' ? 'bi-house-door' : 'bi-door-closed' }}"></i>
                                @endif
                            </div>
                            <div>
                                <div class="unit-name">{{ $unit->unit_number }}</div>
                                <div class="res-note">
                                    {{ $unit->floor_area_sqm ? rtrim(rtrim(number_format($unit->floor_area_sqm, 1), '0'), '.').' sqm' : __('No floor area') }}
                                    · {{ $photoCount }} {{ $photoCount === 1 ? __('photo') : __('photos') }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="unit-variant">{{ $unit->variant_label }}</span>
                    </td>
                    <td>{{ $unit->max_occupancy }} {{ $unit->max_occupancy == 1 ? __('guest') : __('guests') }}</td>
                    <td class="res-money"><strong class="unit-price">₱{{ number_format($unit->price_per_night, 2) }}</strong></td>
                    <td class="res-money">₱{{ number_format($unit->hourlyRate(), 2) }}</td>
                    <td>
                        @if($unit->tour_video_path)
                            <span class="unit-tour is-on">{{ __('Video') }}</span>
                        @else
                            <span class="unit-tour">{{ __('Not uploaded') }}</span>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('accommodations.toggle-availability', $unit->id) }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="unit-switch {{ $unit->is_available ? 'is-on' : '' }}"
                                    role="switch" aria-checked="{{ $unit->is_available ? 'true' : 'false' }}"
                                    title="{{ $unit->is_available ? 'Click to mark unavailable' : 'Click to make bookable' }}">
                                <span class="unit-switch-track"><span></span></span>
                                <span>{{ $unit->is_available ? __('Available') : __('Unavailable') }}</span>
                            </button>
                        </form>
                    </td>
                    <td class="text-end">
                        <div class="unit-actions">
                            <button type="button" class="res-icon-btn" title="Edit {{ $unit->unit_number }}" aria-label="Edit {{ $unit->unit_number }}"
                                    onclick="openEditModal({{ json_encode($unit) }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="{{ route('accommodations.destroy', $unit->id) }}" method="POST" class="m-0" onsubmit="return confirm('Delete {{ $unit->unit_number }}? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="res-icon-btn unit-delete" title="Delete {{ $unit->unit_number }}" aria-label="Delete {{ $unit->unit_number }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-12 text-slate-400">
                        <p class="text-sm mb-0">{{ $kind === 'cottage' ? __('No cottages yet.') : __('No rooms yet.') }}</p>
                    </td>
                </tr>
                @endforelse
                @if($units->isNotEmpty())
                <tr id="unitNoMatch" hidden>
                    <td colspan="8" class="text-center py-10 text-slate-400">
                        <p class="text-sm mb-0">{{ $kind === 'cottage' ? __('No cottages match that search.') : __('No rooms match that search.') }}</p>
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- ── Create Unit Modal ────────────────────────────────────────────────── --}}
<div class="modal fade" id="createUnitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered unit-modal-dialog">
        <div class="modal-content manual-modal unit-modal-content">
            <form action="{{ route('accommodations.store') }}" method="POST" enctype="multipart/form-data" class="unit-modal-form">
                @csrf
            <div class="manual-head">
                <div>
                    <h5>Add {{ strtolower($Noun) }}</h5>
                    <p>Set the name, rates, guest limit, photos, and an optional 360° tour.</p>
                </div>
                <button type="button" class="manual-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

                <div class="modal-body unit-modal-body">
                    @if($errors->any() && !old('_method'))
                    <div class="unit-modal-error" role="alert">
                        {{ $errors->first() }}
                    </div>
                    @endif
                    @include('accommodations.partials.unit-fields', ['prefix' => 'create', 'mode' => 'create'])
                </div>
                <div class="manual-foot">
                    <button type="button" class="btn-secondary-clean" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-ocean">Add {{ strtolower($Noun) }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Edit Unit Modal ──────────────────────────────────────────────────── --}}
<div class="modal fade" id="editUnitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered unit-modal-dialog">
        <div class="modal-content manual-modal unit-modal-content">
            <div class="manual-head">
                <div>
                    <h5 id="editModalTitle">Edit {{ strtolower($Noun) }}</h5>
                    <p>Update the rates, guest limit, amenities, or media.</p>
                </div>
                <button type="button" class="manual-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form id="editUnitForm" action="" method="POST" enctype="multipart/form-data" class="unit-modal-form">
                @csrf
                @method('PUT')
                <div class="modal-body unit-modal-body">
                    @include('accommodations.partials.unit-fields', ['prefix' => 'edit', 'mode' => 'edit'])
                </div>
                <div class="manual-foot">
                    <button type="button" class="btn-secondary-clean" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-ocean">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    @if($errors->any() && !old('_method'))
    const createModal = document.getElementById('createUnitModal');
    if (createModal && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(createModal).show();
    }
    @endif
});
function applyVariantRate(radio, priceId) {
    const price = radio.dataset.night;
    if (price === undefined || price === '') return;
    const field = document.getElementById(priceId);
    if (field) field.value = Number(price).toFixed(2);
}
function openEditModal(unit) {
    document.getElementById('editModalTitle').textContent = 'Edit ' + unit.unit_number;
    document.getElementById('editUnitForm').action = '/accommodations/' + unit.id;
    const numberInput = document.getElementById('editUnitNo');
    const ownNumber = String(unit.unit_number || '').match(/(\d+)\s*$/);
    numberInput.dataset.ignore = unit.id;
    numberInput.value = ownNumber ? Number(ownNumber[1]) : '';
    checkUnitNumber(numberInput);
    const form = document.getElementById('editUnitForm');
    const variantRadios = Array.from(form.querySelectorAll('input[name="variant"]'));
    (variantRadios.find(r => r.value === unit.variant) || variantRadios[0]).checked = true;
    document.getElementById('editPrice').value = unit.price_per_night;
    const storedHourly = unit.price_per_hour;
    document.getElementById('editHourly').value = storedHourly === null || storedHourly === ''
        ? (Math.round(Number(unit.price_per_night) / 12 * 100) / 100).toFixed(2)
        : storedHourly;
    document.getElementById('editOccupancy').value = unit.max_occupancy;
    document.getElementById('editFloorArea').value = unit.floor_area_sqm || '';
    document.getElementById('editDescription').value = unit.description || '';
    
    const amenities = document.getElementById('editAmenities');
    amenities.value = Array.isArray(unit.amenities) ? unit.amenities.join('\n') : '';
    syncAmenityChips(amenities);

    const photoCount = Array.isArray(unit.images) ? unit.images.length : 0;
    document.getElementById('editTourHint').textContent = unit.tour_video_path
        ? 'A video is already uploaded. Choose a new file only to replace it.'
        : 'No video yet. MP4, WebM, or MOV, up to 200 MB.';
    document.getElementById('editPhotoHint').textContent = photoCount
        ? `${photoCount} ${photoCount === 1 ? 'photo' : 'photos'} uploaded. New photos are added to them.`
        : 'No photos yet. JPG, PNG, or WebP under 5 MB each.';
    form.querySelectorAll('input[type="file"]').forEach(input => { input.value = ''; });

    bootstrap.Modal.getOrCreateInstance(document.getElementById('editUnitModal')).show();
}
function amenityLines(box) {
    return box.value.split('\n').map((line) => line.trim()).filter(Boolean);
}
function syncAmenityChips(box) {
    const lines = amenityLines(box).map(line => line.toLowerCase());
    box.closest('.unit-amenities')?.querySelectorAll('.amenity-chip').forEach(chip => {
        chip.classList.toggle('is-on', lines.includes(chip.dataset.amenity.toLowerCase()));
    });
}
document.querySelectorAll('.unit-amenities').forEach((wrap) => {
    const box = wrap.querySelector('.js-amenities');
    wrap.querySelectorAll('.amenity-chip').forEach((chip) => {
        chip.addEventListener('click', () => {
            const name = chip.dataset.amenity;
            const lines = amenityLines(box);
            const at = lines.findIndex(line => line.toLowerCase() === name.toLowerCase());
            if (at >= 0) lines.splice(at, 1); else lines.push(name);
            box.value = lines.join('\n');
            syncAmenityChips(box);
        });
    });
    box.addEventListener('input', () => syncAmenityChips(box));
    syncAmenityChips(box);
});
function checkUnitNumber(input) {
    const p = input.id.replace(/UnitNo$/, '');
    const wrap = document.getElementById(p + 'UnitNoWrap');
    const hint = document.getElementById(p + 'UnitNoHint');
    const hidden = document.getElementById(p + 'UnitNumber');
    const prefix = input.dataset.prefix;
    const ignore = Number(input.dataset.ignore || 0);
    const taken = JSON.parse(input.dataset.units || '[]')
        .filter(unit => unit.id !== ignore)
        .map(unit => unit.number);
    let next = 1;
    while (taken.includes(next)) next++;

    const n = Number(input.value);
    const valid = input.value !== '' && Number.isInteger(n) && n > 0;
    const isTaken = valid && taken.includes(n);
    hidden.value = valid ? `${prefix} ${n}` : '';
    wrap.classList.toggle('is-taken', isTaken);
    hint.classList.toggle('is-taken', isTaken);
    input.setCustomValidity(isTaken ? `${prefix} ${n} is already taken. Use ${prefix} ${next}.` : '');
    input.dataset.next = next;
    if (isTaken) {
        hint.innerHTML = `${prefix} ${n} is already taken. <button type="button">Use ${prefix} ${next}</button>`;
    } else {
        hint.textContent = valid ? `Saved as ${prefix} ${n}.` : `Type a number, e.g. ${next}.`;
    }
}
document.querySelectorAll('.js-unit-no').forEach((input) => {
    const hint = document.getElementById(input.id.replace(/UnitNo$/, '') + 'UnitNoHint');
    hint.addEventListener('click', (event) => {
        if (!event.target.closest('button')) return;
        input.value = input.dataset.next;
        checkUnitNumber(input);
        input.focus();
    });
    input.addEventListener('input', () => checkUnitNumber(input));
    checkUnitNumber(input);
});
(function () {
    const search = document.getElementById('unitSearch');
    if (!search) return;
    search.addEventListener('input', () => {
        const count = document.getElementById('unitCount');
        const noMatch = document.getElementById('unitNoMatch');
        const term = search.value.trim().toLowerCase();
        let shown = 0;
        document.querySelectorAll('.unit-row').forEach(row => {
            const match = !term || row.dataset.search.includes(term);
            row.hidden = !match;
            if (match) shown++;
        });
        if (noMatch) noMatch.hidden = !term || shown > 0;
        if (count) count.textContent = term ? `${shown} of ${count.dataset.total} shown` : count.dataset.label;
    });
})();
(function () {
    const unitId = new URLSearchParams(window.location.search).get('unit');
    const row = unitId && document.querySelector('.unit-row[data-unit-id="' + CSS.escape(unitId) + '"]');
    if (!row) return;
    row.classList.add('unit-row-focus');
    row.scrollIntoView({ block: 'center', behavior: 'smooth' });
})();
</script>

@endsection
