@php $activities = $activities ?? \App\Models\ResortActivity::bookable(); @endphp
@if($activities->isNotEmpty())
    <div class="js-activity-block">
        <label class="block text-xs font-bold text-slate-700 mb-2">Add an activity</label>
        <p class="text-[11px] text-slate-500 mb-2">Optional. A checked activity is added to this booking and to the total.</p>
        <div class="lp-activity-picks">
            @foreach($activities as $activity)
                <label class="lp-activity-pick">
                    <input type="checkbox" class="js-activity-addon" name="activity_ids[]" value="{{ $activity->id }}" data-price="{{ (float) ($activity->price ?? 0) }}" @checked(in_array((string) $activity->id, array_map('strval', old('activity_ids', [])), true))>
                    <span>
                        <strong>{{ $activity->title }}</strong>
                        <em>{{ $activity->schedule ?: 'By arrangement' }}@if($activity->duration) · {{ $activity->duration }}@endif @if($activity->description) · {{ \Illuminate\Support\Str::limit($activity->description, 70) }}@endif</em>
                    </span>
                    <b>{{ $activity->price !== null ? '₱'.number_format($activity->price, 0) : 'Included' }}</b>
                </label>
            @endforeach
        </div>
    </div>
@endif
