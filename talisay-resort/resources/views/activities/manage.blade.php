@extends('layouts.app')

@section('title', 'Activities - Talisay Smart Tourism')

@section('content')
<div class="res-head">
    <div>
        <h1>{{ __('Activities') }}</h1>
        <p>Add what guests can do, then set the schedule, price, meeting point, and what they should bring.</p>
    </div>
    <div class="res-head-actions">
        <a href="#add-activity" class="btn-ocean"><i class="bi bi-plus-lg"></i><span>{{ __('Add activity') }}</span></a>
    </div>
</div>

@if(session('success'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif
@if(isset($errors) && $errors->any())
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
@endif

<div class="unit-stats">
    <div><span>{{ __('Activities') }}</span><strong>{{ $activities->count() }}</strong></div>
    <div><span>{{ __('On the public site') }}</span><strong class="is-good">{{ $showing }}</strong></div>
    <div><span>{{ __('Can be booked') }}</span><strong>{{ $bookable }}</strong></div>
    <div><span>{{ __('Hidden') }}</span><strong class="{{ $activities->count() - $showing > 0 ? 'is-warn' : '' }}">{{ $activities->count() - $showing }}</strong></div>
</div>

<div class="fm-panel mb-6">
    @forelse($activities as $activity)
        <div class="act-row">
            <div class="acct-person">
                @if($activity->imageUrl())
                    <img src="{{ $activity->imageUrl() }}" class="acct-avatar" alt="">
                @else
                    <span class="acct-avatar"><i class="bi {{ $activity->icon ?: 'bi-water' }}"></i></span>
                @endif
                <span>
                    <span class="acct-name">
                        {{ $activity->title }}
                        @unless($activity->is_active)<em class="is-off">Hidden</em>@endunless
                    </span>
                    <span class="acct-email">
                        {{ $activity->schedule ?: 'No schedule' }}
                        @if($activity->duration) · {{ $activity->duration }}@endif
                        @if($activity->price !== null) · ₱{{ number_format($activity->price, 0) }}@endif
                        @if($activity->is_bookable) · Bookable @endif
                    </span>
                </span>
            </div>
            <div class="fm-actions text-sm">
                <button type="button" class="font-semibold text-sky-700 bg-transparent border-0 p-0" onclick="document.getElementById('edit-activity-{{ $activity->id }}').classList.toggle('hidden')">Edit</button>
                <form action="{{ route('activities.destroy', $activity) }}" method="POST" class="inline" onsubmit="return confirm('Remove this activity?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="font-semibold text-rose-600 bg-transparent border-0 p-0">Remove</button>
                </form>
            </div>
        </div>
        <div id="edit-activity-{{ $activity->id }}" class="hidden fm-edit">
            <form method="POST" action="{{ route('activities.update', $activity) }}" enctype="multipart/form-data">
                @csrf @method('PUT')
                @include('activities.fields', ['activity' => $activity])
                <div class="act-save"><button type="submit" class="btn-ocean">Save changes</button></div>
            </form>
        </div>
    @empty
        <div class="fm-row"><span class="text-sm text-slate-400">No activities yet. Add the first one below.</span></div>
    @endforelse
</div>

<h2 class="fm-title" id="add-activity">Add activity</h2>
<p class="fm-lead">It appears on the public Activities page as soon as you save it.</p>
<form method="POST" action="{{ route('activities.store') }}" enctype="multipart/form-data" class="fm-panel">
    @csrf
    @include('activities.fields', ['activity' => null])
    <div class="act-save"><button type="submit" class="btn-ocean">Save activity</button></div>
</form>
@endsection
