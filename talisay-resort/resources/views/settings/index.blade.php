@extends('layouts.app')

@section('title', __('Settings').' - Talisay Smart Tourism')

@section('content')
@php
    $tab = request('tab', 'property');
    $tabs = [
        'appearance' => ['Appearance', 'bi-palette', 'You'],
        'property'  => ['Check-in & check-out', 'bi-clock', 'Property'],
        'arrivals'  => ['Arrivals', 'bi-box-arrow-in-right', 'Property'],
        'holds'     => ['Holds & selling', 'bi-hourglass', 'Property'],
        'requests'  => ['Guest requests', 'bi-inboxes', 'Operations'],
        'engine'    => ['Booking engine', 'bi-calendar2-week', 'Operations'],
        'privacy'   => ['Privacy & data', 'bi-shield-lock', 'Operations'],
        'notices'   => ['Notifications', 'bi-bell', 'Operations'],
    ];
    $val = fn (string $key, $default = '') => old('settings.'.$key, $settings[$key] ?? $default);
    $on = fn (string $key, string $default = '0') => (string) $val($key, $default) === '1';
@endphp

<div class="fm-shell">
    <nav class="fm-nav" aria-label="Settings">
        @foreach(['You', 'Property', 'Operations'] as $group)
            <div class="fm-nav-label">{{ __($group) }}</div>
            @foreach($tabs as $key => [$label, $icon, $tabGroup])
                @if($tabGroup === $group)
                    <a href="{{ route('settings.index', ['tab' => $key]) }}" class="{{ $tab === $key ? 'active' : '' }}">
                        <i class="bi {{ $icon }}"></i>
                        <span>{{ __($label) }}</span>
                    </a>
                @endif
            @endforeach
        @endforeach
    </nav>

    <div>
        <form method="POST" action="{{ route('settings.save') }}">
            @csrf
            @method('PUT')

            @if($tab === 'appearance')
                <h1 class="fm-title">{{ __('Appearance') }}</h1>
                <p class="fm-lead">{{ __('Theme and language for the resort system.') }}</p>
                <div class="fm-panel">
                    <div class="fm-row">
                        <label>{{ __('Theme') }}<span class="hint">{{ __('This browser only.') }}</span></label>
                        <select id="adminTheme" class="form-select-clean text-xs" style="max-width: 220px;">
                            <option value="light">{{ __('Light') }}</option>
                            <option value="dark">{{ __('Dark') }}</option>
                            <option value="system">{{ __('System') }}</option>
                        </select>
                    </div>
                    <div class="fm-row">
                        <label>{{ __('Language') }}<span class="hint">{{ __('Changes the words across the admin site and the guest website.') }}</span></label>
                        <select name="settings[ui_locale]" class="form-select-clean text-xs" style="max-width: 280px;">
                            @foreach(\App\Support\UiLocales::all() as $code => $name)
                                <option value="{{ $code }}" @selected($val('ui_locale', 'en') === $code)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @elseif($tab === 'property')
                <h1 class="fm-title">{{ __('Check-in & check-out') }}</h1>
                <p class="fm-lead">{{ __('The times the front desk uses for overnight stays.') }}</p>
                <div class="fm-panel">
                    <div class="fm-row">
                        <label>{{ __('Check-in') }}<span class="hint">{{ __('Guests can arrive from this time.') }}</span></label>
                        <input type="time" name="settings[check_in_time]" value="{{ $val('check_in_time', '14:00') }}" class="form-control-clean text-xs">
                    </div>
                    <div class="fm-row">
                        <label>{{ __('Check-out') }}<span class="hint">{{ __('Rooms and cottages should be clear by this time.') }}</span></label>
                        <input type="time" name="settings[check_out_time]" value="{{ $val('check_out_time', '12:00') }}" class="form-control-clean text-xs">
                    </div>
                </div>
            @elseif($tab === 'arrivals')
                <h1 class="fm-title">{{ __('Arrivals') }}</h1>
                <p class="fm-lead">{{ __('Getting guests checked in before they reach the desk.') }}</p>
                <div class="fm-panel">
                    <div class="fm-row">
                        <label>Pre-check-in links<span class="hint">Guests arriving soon get an online check-in link.</span></label>
                        <label class="flex items-center gap-2 text-sm text-slate-600 mb-0">
                            <input type="hidden" name="settings[precheckin_links]" value="0">
                            <input type="checkbox" name="settings[precheckin_links]" value="1" @checked($on('precheckin_links'))>
                            Send automatically
                        </label>
                    </div>
                    <div class="fm-row">
                        <label>Send links this far ahead</label>
                        <div class="flex items-center gap-2">
                            <input type="number" min="0" max="30" name="settings[precheckin_days]" value="{{ $val('precheckin_days', '2') }}" class="form-control-clean text-xs" style="max-width: 120px;">
                            <span class="text-xs text-slate-400">days</span>
                        </div>
                    </div>
                    <div class="fm-row">
                        <label>Access details<span class="hint">Door notes, parking, and Wi-Fi.</span></label>
                        <label class="flex items-center gap-2 text-sm text-slate-600 mb-0">
                            <input type="hidden" name="settings[precheckin_required]" value="0">
                            <input type="checkbox" name="settings[precheckin_required]" value="1" @checked($on('precheckin_required'))>
                            Release only after pre-check-in
                        </label>
                    </div>
                    <div class="fm-row">
                        <label>Access instructions</label>
                        <textarea name="settings[access_instructions]" rows="3" class="form-control-clean text-xs" placeholder="Door codes, parking, Wi-Fi. Released only after payment and, if set, pre-check-in.">{{ $val('access_instructions') }}</textarea>
                    </div>
                        </div>
            @elseif($tab === 'holds')
                <h1 class="fm-title">{{ __('Holds & selling') }}</h1>
                <p class="fm-lead">{{ __('How long a room stays reserved before the front desk releases it.') }}</p>
                <div class="fm-panel">
                    <div class="fm-row">
                        <label>Hold unconfirmed bookings</label>
                        <div class="flex items-center gap-2">
                            <input type="number" min="0" name="settings[hold_minutes]" value="{{ $val('hold_minutes', '120') }}" class="form-control-clean text-xs" style="max-width: 120px;">
                            <span class="text-xs text-slate-400">min</span>
                        </div>
                    </div>
                    <div class="fm-row">
                        <label>No-show grace<span class="hint">After check-in time, wait this long before marking a no-show.</span></label>
                        <div class="flex items-center gap-2">
                            <input type="number" min="0" name="settings[noshow_grace_minutes]" value="{{ $val('noshow_grace_minutes', '60') }}" class="form-control-clean text-xs" style="max-width: 120px;">
                            <span class="text-xs text-slate-400">min</span>
                        </div>
                    </div>
                </div>
            @elseif($tab === 'requests')
                <h1 class="fm-title">Guest request deadlines</h1>
                <p class="fm-lead">How long a guest request may stay open before it is overdue.</p>
                <div class="fm-panel">
                    @foreach(['request_urgent_min' => ['Urgent', '15'], 'request_high_min' => ['High', '30'], 'request_medium_min' => ['Medium', '60'], 'request_low_min' => ['Low', '240']] as $key => [$label, $default])
                        <div class="fm-row">
                            <label>{{ $label }}</label>
                                <div class="flex items-center gap-2">
                                <input type="number" min="1" name="settings[{{ $key }}]" value="{{ $val($key, $default) }}" class="form-control-clean text-xs" style="max-width: 120px;">
                                <span class="text-xs text-slate-400">min</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif($tab === 'engine')
                <h1 class="fm-title">{{ __('Booking engine') }}</h1>
                <p class="fm-lead">{{ __('How stays can be sold on the website and at the desk.') }}</p>
                <div class="fm-panel">
                    <div class="fm-row">
                        <label>Hourly stays</label>
                        <label class="flex items-center gap-2 text-sm text-slate-600 mb-0">
                            <input type="hidden" name="settings[hourly_stays_enabled]" value="0">
                            <input type="checkbox" name="settings[hourly_stays_enabled]" value="1" @checked($on('hourly_stays_enabled', '1'))>
                            Allow booking by the hour
                        </label>
                    </div>
                    <div class="fm-row">
                        <label>Minimum nights<span class="hint">For overnight stays. Hourly stays are not affected.</span></label>
                        <div class="flex items-center gap-2">
                            <input type="number" min="1" name="settings[min_nights]" value="{{ $val('min_nights', '1') }}" class="form-control-clean text-xs" style="max-width: 120px;">
                            <span class="text-xs text-slate-400">nights</span>
                        </div>
                    </div>
                    <div class="fm-row">
                        <label>Down payment<span class="hint">Percent asked when a method allows a partial payment. 0 means full payment.</span></label>
                        <div class="flex items-center gap-2">
                            <input type="number" min="0" max="100" name="settings[down_payment_percent]" value="{{ $val('down_payment_percent', '50') }}" class="form-control-clean text-xs" style="max-width: 120px;">
                            <span class="text-xs text-slate-400">%</span>
                        </div>
                    </div>
                </div>
            @elseif($tab === 'privacy')
                <h1 class="fm-title">{{ __('Privacy & data') }}</h1>
                <p class="fm-lead">{{ __('How long guest records stay, and what a guest can ask for.') }}</p>
                <div class="fm-panel">
                    <div class="fm-row">
                        <label>Keep booking records</label>
                        <div class="flex items-center gap-2">
                            <input type="number" min="1" name="settings[data_retention_months]" value="{{ $val('data_retention_months', '24') }}" class="form-control-clean text-xs" style="max-width: 120px;">
                            <span class="text-xs text-slate-400">months</span>
                        </div>
                    </div>
                    <div class="fm-row">
                        <label>Guest copy of their data</label>
                        <label class="flex items-center gap-2 text-sm text-slate-600 mb-0">
                            <input type="hidden" name="settings[guest_data_export]" value="0">
                            <input type="checkbox" name="settings[guest_data_export]" value="1" @checked($on('guest_data_export', '1'))>
                            Guests may request a copy of their account and bookings
                        </label>
                    </div>
                </div>
            @else
                <h1 class="fm-title">{{ __('Notifications') }}</h1>
                <p class="fm-lead">{{ __('What the resort desk is told about as it happens.') }}</p>
                <div class="fm-panel">
                    <div class="fm-row">
                        <label>New reservation</label>
                        <label class="flex items-center gap-2 text-sm text-slate-600 mb-0">
                            <input type="hidden" name="settings[notify_new_booking]" value="0">
                            <input type="checkbox" name="settings[notify_new_booking]" value="1" @checked($on('notify_new_booking', '1'))>
                            Notify when a guest books
                        </label>
                    </div>
                    <div class="fm-row">
                        <label>Payment proof</label>
                        <label class="flex items-center gap-2 text-sm text-slate-600 mb-0">
                            <input type="hidden" name="settings[notify_payment_proof]" value="0">
                            <input type="checkbox" name="settings[notify_payment_proof]" value="1" @checked($on('notify_payment_proof', '1'))>
                            Notify when a guest uploads a receipt
                        </label>
                    </div>
                </div>
            @endif

            <div class="mt-4">
                <button type="submit" class="btn-ocean text-xs">{{ __('Save settings') }}</button>
            </div>
                </form>

        <h2 class="fm-title mt-8">{{ __('Managed on their own screens') }}</h2>
        <p class="fm-lead">{{ __('Resort profile, lists, and accounts are kept in File Maintenance.') }}</p>
        <div class="fm-jumps">
            @foreach([
                ['Resort profile', route('maintenance.section', 'resort')],
                ['Booking rules', route('maintenance.section', 'rules')],
                ['Payments', route('maintenance.section', 'payments')],
                ['AI assistant', route('maintenance.section', 'assistant')],
                ['User accounts', route('maintenance.section', 'accounts')],
                ['Activities', route('activities.manage')],
                ['File maintenance', route('maintenance.index')],
            ] as [$label, $url])
                <a href="{{ $url }}" class="fm-jump"><span>{{ __($label) }}</span><i class="bi bi-chevron-right"></i></a>
            @endforeach
        </div>
        @if($tab === 'appearance')
        <script>
            (function () {
                var select = document.getElementById('adminTheme');
                if (!select) return;
                var stored = 'light';
                try { stored = localStorage.getItem('talisayTheme') || 'light'; } catch (e) {}
                select.value = stored;
                function apply(theme) {
                    try { localStorage.setItem('talisayTheme', theme); } catch (e) {}
                    var dark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    if (dark) document.documentElement.setAttribute('data-theme', 'dark');
                    else document.documentElement.removeAttribute('data-theme');
                }
                select.addEventListener('change', function () { apply(select.value); });
                if (stored === 'system') {
                    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () { apply('system'); });
                }
            })();
        </script>
        @endif
    </div>
</div>
@endsection
