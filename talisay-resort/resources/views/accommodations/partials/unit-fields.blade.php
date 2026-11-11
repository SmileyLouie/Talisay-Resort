@php
    $p = $prefix;
    $isEdit = $mode === 'edit';
    $useOld = !$isEdit && !old('_method');
    $val = fn ($key, $default = null) => $useOld ? old($key, $default) : $default;
    $variantOptions = $unitVariants->isNotEmpty()
        ? $unitVariants
        : collect([(object) ['slug' => 'normal', 'label' => 'Normal'], (object) ['slug' => 'premium', 'label' => 'Deluxe']]);
    $checkedVariant = $val('variant', $variantOptions->first()->slug);
@endphp

@php
    $oldName = (string) $val('unit_number', '');
    $startNumber = $isEdit ? '' : (preg_match('/(\d+)\s*$/', $oldName, $m) ? (int) $m[1] : $nextNumber);
@endphp
<div class="manual-row">
    <label for="{{ $p }}UnitNo">{{ __('Number') }}</label>
    <div class="unit-inline">
        <div class="unit-affix unit-number {{ $kind === 'cottage' ? 'is-cottage' : '' }}" id="{{ $p }}UnitNoWrap">
            <span>{{ $numberPrefix }}</span>
            <input type="number" id="{{ $p }}UnitNo" class="form-control-clean js-unit-no" min="1" step="1" value="{{ $startNumber }}"
                   data-prefix="{{ $numberPrefix }}" data-units='@json($unitNumberMap)' data-ignore="" required>
        </div>
        <span class="manual-hint unit-number-hint m-0" id="{{ $p }}UnitNoHint" aria-live="polite"></span>
    </div>
    <input type="hidden" name="unit_number" id="{{ $p }}UnitNumber" value="{{ $isEdit ? '' : $numberPrefix.' '.$startNumber }}">
</div>

<input type="hidden" name="unit_type" value="{{ $kind }}">

<div class="manual-row">
    <label>{{ __('Variant') }} <span class="req-star">*</span></label>
    <div class="manual-choice unit-choice-wrap">
        @foreach($variantOptions as $opt)
        <label><input type="radio" name="variant" value="{{ $opt->slug }}" data-night="{{ isset($opt->price_per_night) ? $opt->price_per_night : '' }}" @if(!$isEdit) onchange="applyVariantRate(this, '{{ $p }}Price')" @endif {{ !$isEdit && $checkedVariant === $opt->slug ? 'checked' : '' }}><span>{{ $opt->label }}</span></label>
        @endforeach
    </div>
</div>

<div class="manual-row">
    <label for="{{ $p }}Occupancy">{{ __('Guests') }}</label>
    <div class="unit-inline">
        <div class="unit-affix unit-narrow">
            <input type="number" name="max_occupancy" id="{{ $p }}Occupancy" class="form-control-clean" min="1" value="{{ $val('max_occupancy', 2) }}" required>
            <em>pax</em>
        </div>
        <span class="manual-hint m-0">Most guests allowed per {{ strtolower($Noun) }}.</span>
    </div>
</div>

<div class="manual-row">
    <label for="{{ $p }}Price">{{ __('Rates') }}</label>
    <div class="manual-split">
        <div class="unit-affix">
            <span>₱</span>
            <input type="number" step="0.01" min="0" name="price_per_night" id="{{ $p }}Price" class="form-control-clean" value="{{ $val('price_per_night', '1500.00') }}" aria-label="Rate per night" required>
            <em>per night</em>
        </div>
        <div class="unit-affix">
            <span>₱</span>
            <input type="number" step="0.01" min="0" name="price_per_hour" id="{{ $p }}Hourly" class="form-control-clean" value="{{ $val('price_per_hour', '125.00') }}" aria-label="Rate per hour" required>
            <em>per hour</em>
        </div>
    </div>
</div>

<div class="manual-row">
    <label for="{{ $p }}FloorArea">{{ __('Floor area') }}</label>
    <div class="unit-affix unit-narrow">
        <input type="number" step="0.1" min="1" name="floor_area_sqm" id="{{ $p }}FloorArea" class="form-control-clean" value="{{ $val('floor_area_sqm') }}" placeholder="{{ __('Optional') }}">
        <em>sqm</em>
    </div>
</div>

<div class="manual-row manual-row-top">
    <label for="{{ $p }}Description">{{ __('Description') }}</label>
    <textarea name="description" id="{{ $p }}Description" rows="2" class="form-control-clean" placeholder="A short description guests will see">{{ $val('description') }}</textarea>
</div>

<div class="manual-row manual-row-top">
    <label for="{{ $p }}Amenities">{{ __('Amenities') }}</label>
    <div class="unit-amenities">
        @if($amenityItems->isNotEmpty())
        <div class="amenity-chips">
            @foreach($amenityItems as $item)
                <button type="button" class="amenity-chip" data-amenity="{{ $item->name }}">{{ $item->name }}</button>
            @endforeach
        </div>
        @endif
        <textarea name="amenities" id="{{ $p }}Amenities" rows="3" class="form-control-clean js-amenities" placeholder="One per line, e.g.&#10;Air-conditioning&#10;Free Wi-Fi">{{ $val('amenities') }}</textarea>
        <span class="manual-hint">{{ $amenityItems->isNotEmpty() ? 'Tap a tag to add or remove it, or type your own. One per line.' : 'One per line.' }}</span>
    </div>
</div>

<div class="manual-row manual-row-top">
    <label for="{{ $p }}TourVideo">{{ __('360° tour') }}</label>
    <div>
        <input type="file" name="tour_video" id="{{ $p }}TourVideo" accept="video/mp4,video/webm,video/quicktime,.mov,.m4v" class="form-control-clean unit-file">
        <span class="manual-hint" id="{{ $p }}TourHint">Optional. MP4, WebM, or MOV, up to 200 MB.</span>
    </div>
</div>

<div class="manual-row manual-row-top">
    <label for="{{ $p }}Photos">{{ __('Photos') }}</label>
    <div>
        <input type="file" name="images[]" id="{{ $p }}Photos" multiple accept="{{ $isEdit ? 'image/*' : 'image/jpeg,image/png,image/webp' }}" class="form-control-clean unit-file">
        <span class="manual-hint" id="{{ $p }}PhotoHint">Optional. JPG, PNG, or WebP under 5 MB each. You can pick several.</span>
    </div>
</div>
