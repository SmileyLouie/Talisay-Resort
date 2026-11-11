@php
    $activity = $activity ?? null;
    $icons = \App\Models\ResortActivity::icons();
    $currentIcon = old('icon', $activity->icon ?? 'bi-water');
@endphp
<div class="act-grid">
    <div class="span-2">
        <label>Title</label>
        <input type="text" name="title" value="{{ old('title', $activity->title ?? '') }}" class="form-control-clean" required placeholder="Island hopping">
    </div>
    <div class="span-2">
        <label>What guests do<span class="hint">This is the description on the public Activities page.</span></label>
        <textarea name="description" rows="3" class="form-control-clean" placeholder="A boat ride to nearby islands, with time to swim and take photos.">{{ old('description', $activity->description ?? '') }}</textarea>
    </div>
    <div>
        <label>Schedule<span class="hint">When it runs.</span></label>
        <input type="text" name="schedule" value="{{ old('schedule', $activity->schedule ?? '') }}" class="form-control-clean" placeholder="Daily, 8:00 AM – 4:00 PM">
    </div>
    <div>
        <label>How long<span class="hint">For example, 2 hours.</span></label>
        <input type="text" name="duration" value="{{ old('duration', $activity->duration ?? '') }}" class="form-control-clean" placeholder="2 hours">
    </div>
    <div>
        <label>Price</label>
        <div class="act-price">
            <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $activity->price ?? '') }}" class="form-control-clean" placeholder="350">
            <span>PHP</span>
        </div>
    </div>
    <div>
        <label>Group size<span class="hint">Leave blank if there is no limit.</span></label>
        <input type="number" min="1" name="max_guests" value="{{ old('max_guests', $activity->max_guests ?? '') }}" class="form-control-clean" placeholder="8">
    </div>
    <div>
        <label>Icon</label>
        <select name="icon" class="form-select-clean">
            @if($currentIcon && ! isset($icons[$currentIcon]))
                <option value="{{ $currentIcon }}" selected>{{ $currentIcon }}</option>
            @endif
            @foreach($icons as $class => $label)
                <option value="{{ $class }}" @selected($currentIcon === $class)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Photo</label>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="form-control-clean">
    </div>
    <div>
        <label class="act-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(filter_var(old('is_active', $activity->is_active ?? true), FILTER_VALIDATE_BOOLEAN))>
            <span>Show on the public site</span>
        </label>
    </div>
    <div>
        <label class="act-check">
            <input type="hidden" name="is_bookable" value="0">
            <input type="checkbox" name="is_bookable" value="1" @checked(filter_var(old('is_bookable', $activity->is_bookable ?? true), FILTER_VALIDATE_BOOLEAN))>
            <span>Guests can add this to a booking</span>
        </label>
    </div>
</div>
