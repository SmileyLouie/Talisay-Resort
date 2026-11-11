@extends('layouts.app')

@section('title', 'File Maintenance - Talisay Smart Tourism')

@section('content')
@php
    $section = $section ?? 'overview';
    $rows = $rows ?? collect();
    $lists = [
        'overview'   => ['Overview', 'bi-grid-1x2'],
        'variants'   => ['Variants', 'bi-stars'],
        'payments'   => ['Payments', 'bi-wallet2'],
        'amenities'  => ['Amenities', 'bi-check2-square'],
        'replies'    => ['Chat replies', 'bi-chat-left-text'],
        'exclusive'  => ['Exclusive booking', 'bi-gem'],
    ];
    $copy = [
        'variants'   => 'Add a style for rooms only, cottages only, or both, and set the nightly price used on a new unit.',
        'payments'   => 'E-wallets, Philippine banks, cards, and cash. An active method with its account number shows on every booking form.',
        'exclusive'  => 'Edit the exclusive booking guests see: the title, description, information, nightly rate, and occasion choices.',
        'amenities'  => 'Wi-Fi, air-conditioning, and other features offered on a unit.',
        'replies'    => 'Questions the AI chatbot should answer. Separate words with commas. Guests hear the reply when their message matches.',
    ];
    $noun = [
        'variants'   => 'variant',
        'payments'   => 'payment method',
        'amenities'  => 'amenity',
        'replies'    => 'chat reply',
    ];
@endphp

<div class="fm-shell">
    <nav class="fm-nav" aria-label="File maintenance">
        <div class="fm-nav-label">{{ __('Lists') }}</div>
        @foreach($lists as $key => [$label, $icon])
            <a href="{{ $key === 'overview' ? route('maintenance.index') : route('maintenance.section', $key) }}" class="{{ $section === $key ? 'active' : '' }}">
                <i class="bi {{ $icon }}"></i>
                <span>{{ __($label) }}</span>
            </a>
        @endforeach

        <div class="fm-nav-label">{{ __('Resort files') }}</div>
        <a href="{{ route('maintenance.section', 'resort') }}" class="{{ $section === 'resort' ? 'active' : '' }}"><i class="bi bi-building"></i><span>{{ __('Resort profile') }}</span></a>
        <a href="{{ route('maintenance.section', 'rules') }}" class="{{ $section === 'rules' ? 'active' : '' }}"><i class="bi bi-calendar-check"></i><span>{{ __('Booking rules') }}</span></a>
        <a href="{{ route('maintenance.section', 'assistant') }}" class="{{ $section === 'assistant' ? 'active' : '' }}"><i class="bi bi-robot"></i><span>{{ __('AI assistant') }}</span></a>
        <a href="{{ route('maintenance.section', 'accounts') }}" class="{{ $section === 'accounts' ? 'active' : '' }}"><i class="bi bi-people"></i><span>{{ __('User accounts') }}</span></a>
    </nav>

    <div class="fm-main">
        @if(!empty($onlyTab))
            @include('maintenance.records')
        @elseif($section === 'overview')
            <h1 class="fm-title">{{ __('File Maintenance') }}</h1>
            <p class="fm-lead">The lists rooms, cottages, payments, and the public site use.</p>

            <div class="fm-panel mb-6">
                @foreach([
                    ['variants', 'Variants', $variantCount, 'Normal, Deluxe, and extra room or cottage styles.'],
                    ['payments', 'Payments', $paymentCount, 'E-wallets, banks, cards, and cash.'],
                    ['amenities', 'Amenities', $amenityCount, 'Wi-Fi, air-conditioning, and other unit features.'],
                    ['replies', 'Chat replies', $replyCount, 'Saved answers the AI chatbot can give guests.'],
                    ['exclusive', 'Exclusive booking', '—', 'Nightly price and occasion list.'],
                ] as [$key, $title, $count, $text])
                    <a href="{{ route('maintenance.section', $key) }}" class="fm-row no-underline">
                        <span class="fm-k">{{ $title }}<span class="hint">{{ $text }}</span></span>
                        <span class="fm-actions text-sm text-slate-500">
                            <strong class="text-slate-900">{{ $count }}</strong>
                            <i class="bi bi-chevron-right"></i>
                        </span>
                    </a>
                @endforeach
            </div>

            <h2 class="fm-title">Managed on their own screens</h2>
            <p class="fm-lead">These open the page where that part of the resort is edited.</p>
            <div class="fm-jumps">
                @foreach([
                    ['Resort profile', route('maintenance.section', 'resort')],
                    ['Booking rules', route('maintenance.section', 'rules')],
                    ['Payments', route('maintenance.section', 'payments')],
                    ['AI assistant', route('maintenance.section', 'assistant')],
                    ['User accounts', route('maintenance.section', 'accounts')],
                ] as [$label, $url])
                    <a href="{{ $url }}" class="fm-jump">
                        <span>{{ $label }}</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                @endforeach
            </div>
        @elseif($section === 'payments')
            @include('maintenance.payments')
        @elseif($section === 'exclusive')
            <h1 class="fm-title">Exclusive booking</h1>
            <p class="fm-lead">{{ $copy['exclusive'] }}</p>
            <form method="POST" action="{{ route('settings.save') }}" class="fm-panel">
                @csrf
                @method('PUT')
                <div class="fm-row">
                    <label>Title<span class="hint">Shown as the heading on the exclusive booking page and in the booking form.</span></label>
                    <input type="text" name="settings[exclusive_title]" value="{{ old('settings.exclusive_title', $exclusiveTitle) }}" class="form-control-clean text-xs" maxlength="120" required>
                </div>
                <div class="fm-row">
                    <label>Description<span class="hint">The paragraph under the heading. This is what guests read first.</span></label>
                    <textarea name="settings[exclusive_description]" rows="5" class="form-control-clean text-xs" required>{{ old('settings.exclusive_description', $exclusiveDescription) }}</textarea>
                </div>
                <div class="fm-row">
                    <label>Information<span class="hint">The details in “What this booking is.” Rate, dates, and the visitor cap stay live.</span></label>
                    <textarea name="settings[exclusive_information]" rows="4" class="form-control-clean text-xs" required>{{ old('settings.exclusive_information', $exclusiveInformation) }}</textarea>
                </div>
                <div class="fm-row">
                    <label>Nightly price<span class="hint">Leave blank to use the sum of room and cottage nightly rates (₱{{ number_format($exclusiveComputed ?? 0, 2) }} right now).</span></label>
                    <div class="flex items-center gap-2">
                        <input type="number" step="0.01" min="0" name="settings[exclusive_nightly_rate]" value="{{ old('settings.exclusive_nightly_rate', $exclusiveRate) }}" class="form-control-clean text-xs" style="max-width: 180px;" placeholder="Optional">
                        <span class="text-xs text-slate-400">PHP / night</span>
                    </div>
                </div>
                <div class="fm-row">
                    <label>What is this booking for?<span class="hint">One occasion per line. These choices appear on the exclusive booking form.</span></label>
                    <textarea name="settings[exclusive_occasions]" rows="8" class="form-control-clean text-xs">{{ old('settings.exclusive_occasions', $exclusiveOccasions) }}</textarea>
                </div>
                <div class="fm-row">
                    <span></span>
                    <button type="submit" class="btn-ocean text-xs" style="width: auto;">Save exclusive booking</button>
                </div>
            </form>
        @else
            <h1 class="fm-title">{{ $lists[$section][0] }}</h1>
            <p class="fm-lead">{{ $copy[$section] }}</p>

            @if(isset($errors) && $errors->any())
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="fm-panel mb-6">
                @forelse($rows as $row)
                    <div class="fm-row">
                        <div class="fm-k">
                            @if($section === 'activities')
                                {{ $row->title }}
                                <span class="hint">{{ $row->schedule ?: 'No schedule' }}@if($row->price !== null) · ₱{{ number_format($row->price, 0) }}@endif @if($row->is_bookable) · Bookable @endif</span>
                            @elseif($section === 'variants')
                                {{ $row->label }}
                                <span class="hint">{{ $row->applies_to === 'room' ? 'Rooms only' : ($row->applies_to === 'cottage' ? 'Cottages only' : 'Rooms and cottages') }}@if($row->price_per_night !== null) · ₱{{ number_format($row->price_per_night, 0) }}/night @endif</span>
                            @elseif($section === 'payments')
                                {{ $row->name }}
                                <span class="hint">{{ ucfirst($row->kind) }}@if($row->account_number) · {{ $row->account_number }}@endif</span>
                            @elseif($section === 'replies')
                                {{ $row->keyword }}
                                <span class="hint">{{ ucfirst(str_replace('_', ' ', $row->category)) }} · {{ \Illuminate\Support\Str::limit($row->response, 90) }}</span>
                            @else
                                {{ $row->name }}
                            @endif
                        </div>
                        <div class="fm-actions text-xs">
                            <span class="{{ $row->is_active ? 'text-emerald-600' : 'text-slate-400' }} font-semibold">{{ $row->is_active ? 'Active' : 'Hidden' }}</span>
                            @if($row->is_system ?? false)
                                <span class="text-slate-400">Built-in</span>
                            @endif
                            <button type="button" class="font-semibold text-sky-700 bg-transparent border-0 p-0" onclick="document.getElementById('edit-{{ $section }}-{{ $row->id }}').classList.toggle('hidden')">Edit</button>
                            @unless($row->is_system ?? false)
                                <form action="{{ route('maintenance.destroy', [$section, $row->id]) }}" method="POST" class="inline" onsubmit="return confirm('Remove this record?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="font-semibold text-rose-600 bg-transparent border-0 p-0">Remove</button>
                                </form>
                            @endunless
                        </div>
                    </div>
                    <div id="edit-{{ $section }}-{{ $row->id }}" class="hidden fm-edit">
                        <form method="POST" action="{{ route('maintenance.update', [$section, $row->id]) }}" enctype="multipart/form-data">
                            @csrf @method('PUT')
                            @include('maintenance.fields', ['row' => $row, 'section' => $section])
                            <div class="fm-row">
                                <span></span>
                                <button type="submit" class="btn-ocean text-xs" style="width: auto;">Save changes</button>
                            </div>
                        </form>
                    </div>
                @empty
                    <div class="fm-row"><span class="text-sm text-slate-400">Nothing saved here yet.</span></div>
                @endforelse
            </div>

            <h2 class="fm-title">Add {{ $noun[$section] }}</h2>
            <p class="fm-lead">It shows up on the matching page as soon as you save it.</p>
            <form method="POST" action="{{ route('maintenance.store', $section) }}" enctype="multipart/form-data" class="fm-panel">
                @csrf
                @include('maintenance.fields', ['row' => null, 'section' => $section])
                <div class="fm-row">
                    <span></span>
                    <button type="submit" class="btn-ocean text-xs" style="width: auto;">Save</button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
