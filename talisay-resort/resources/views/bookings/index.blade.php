{{-- Extends the primary layout template designed for dashboard panels --}}
@extends('layouts.app')

{{-- Sets the HTML document title for the bookings list view page --}}
@section('title', 'Bookings - Talisay Smart Tourism')

{{-- Defines the main content area section of the template --}}
@section('content')

<div class="res-head">
    <div>
        <h1>{{ __('Bookings') }}</h1>
        <p>{{ __('All reservations. Create a walk-in with Manual Booking, or a full-resort stay with Exclusive Booking.') }}</p>
    </div>
    <div class="res-head-actions">
        @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('bookings', 'create'))
        <button type="button" class="btn-secondary-clean" data-bs-toggle="modal" data-bs-target="#exclusiveBookingModal">
            <i class="bi bi-stars"></i>
            <span>{{ __('Exclusive') }}</span>
        </button>
        @endif
        <button type="button" class="btn-ocean" data-bs-toggle="modal" data-bs-target="#manualBookingModal">
            <i class="bi bi-plus-lg"></i>
            <span>{{ __('Manual Booking') }}</span>
        </button>
    </div>
</div>

{{-- Validation Errors Alert --}}
@if(isset($errors) && $errors->any())
<div class="mb-6 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3.5 rounded-2xl shadow-sm">
    <div class="flex items-start gap-3">
        <i class="bi bi-exclamation-triangle text-rose-500 text-lg flex-shrink-0 mt-0.5"></i>
        <div>
            <span class="text-sm font-bold block mb-1">{{ __('Please correct the following errors:') }}</span>
            <ul class="text-xs list-disc list-inside space-y-0.5 mb-0 font-medium text-rose-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <form method="GET" action="{{ route('bookings.index') }}" id="filterForm" class="res-toolbar">
        <div class="res-search">
            <i class="bi bi-search"></i>
            <input type="search" name="search" class="form-control-clean" placeholder="{{ __('Search...') }}" value="{{ request('search') }}" aria-label="{{ __('Search bookings') }}">
        </div>
        <select name="status" class="form-select-clean res-select" aria-label="{{ __('Status') }}" onchange="this.form.submit()">
            <option value="">{{ __('Status: all') }}</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
            <option value="waiting" {{ request('status') === 'waiting' ? 'selected' : '' }}>{{ __('Waiting') }}</option>
            <option value="check_in" {{ request('status') === 'check_in' ? 'selected' : '' }}>{{ __('Check in') }}</option>
            <option value="check_out" {{ request('status') === 'check_out' ? 'selected' : '' }}>{{ __('Check out') }}</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
        </select>
        <select name="type" class="form-select-clean res-select" aria-label="{{ __('Type') }}" onchange="this.form.submit()">
            <option value="">{{ __('Type: all') }}</option>
            <option value="nightly" {{ request('type') === 'nightly' ? 'selected' : '' }}>{{ __('Nightly') }}</option>
            <option value="hourly" {{ request('type') === 'hourly' ? 'selected' : '' }}>{{ __('Hourly') }}</option>
            <option value="exclusive" {{ request('type') === 'exclusive' ? 'selected' : '' }}>{{ __('Exclusive') }}</option>
        </select>
        @if(request()->anyFilled(['status', 'search', 'type']))
        <a href="{{ route('bookings.index') }}" class="res-clear">{{ __('Clear') }}</a>
        @endif
    </form>
    <div class="table-responsive">
        <table class="table-clean">
            <thead>
                <tr>
                    <th>{{ __('Ref') }}</th>
                    <th>{{ __('Guest') }}</th>
                    <th>{{ __('Room/Cottage') }}</th>
                    <th>{{ __('Variant') }}</th>
                    <th>{{ __('In') }}</th>
                    <th>{{ __('Out') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>Type</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @php $paymentMethodNames = \App\Models\PaymentMethod::labels(); @endphp
                @forelse($bookings as $booking)
                <tr class="booking-row" data-id="{{ $booking->id }}">
                    <td><span class="res-ref">{{ $booking->reference_no }}</span></td>
                    <td><span class="res-guest">{{ $booking->guest_name }}</span></td>
                    <td>
                        {{ $booking->booking_type === 'special_resort' ? 'Full resort' : ($booking->accommodationUnit->unit_number ?? '—') }}
                        @if($booking->modified_at)
                        <div class="res-note">Modified</div>
                        @endif
                    </td>
                    <td>{{ $booking->booking_type === 'special_resort' ? '—' : ($booking->accommodationUnit->variant_label ?? '—') }}</td>
                    @php
                        $hourIn = $hourOut = null;
                        if ($booking->isHourly() && $booking->hour_start) {
                            [$hourIn, $hourOut] = $booking->stayWindow();
                        }
                    @endphp
                    <td>
                        {{ ($hourIn ?? $booking->checkInDate())->format('Y-m-d') }}
                        @if($hourIn)
                        <div class="res-note">{{ $hourIn->format('g:i A') }}</div>
                        @endif
                    </td>
                    <td>
                        {{ ($hourOut ?? $booking->checkOutDate())->format('Y-m-d') }}
                        @if($hourOut)
                        <div class="res-note">{{ $hourOut->format('g:i A') }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="res-status res-status-{{ $booking->listStage() }}">{{ $booking->listStageLabel() }}</span>
                        @if($booking->booking_type === 'special_resort' && $booking->admin_approval_status === 'pending')
                        <div class="res-note">Needs approval</div>
                        @endif
                    </td>
                    <td>
                        @if($booking->isSpecialResort())
                        <span class="res-exclusive">Exclusive</span>
                        @else
                        {{ $booking->isHourly() ? 'Hourly' : 'Nightly' }}
                        @endif
                    </td>
                    <td class="res-money">₱{{ number_format($booking->total_amount, 2) }}</td>
                    <td class="text-end">
                        <button class="res-icon-btn" type="button" aria-label="Details" title="Details" 
                                data-id="{{ $booking->id }}"
                                data-ref="{{ $booking->reference_no }}"
                                data-source="{{ $booking->booking_source ?? 'online' }}"
                                data-type="{{ $booking->booking_type }}"
                                data-approval="{{ $booking->admin_approval_status }}"
                                data-name="{{ $booking->guest_name }}"
                                data-phone="{{ $booking->guest_contact }}"
                                data-email="{{ $booking->user->email ?? 'N/A' }}"
                                data-unit="{{ $booking->booking_type === 'special_resort' ? 'Exclusive Full-Resort Booking (All '.$openUnitCount.' '.($openUnitCount === 1 ? 'Unit' : 'Units').')' : trim(($booking->accommodationUnit->unit_number ?? 'Accommodation Unit').($booking->accommodationUnit?->variant_label ? ' · '.$booking->accommodationUnit->variant_label : '')) }}"
                                data-date="{{ $booking->booking_date->format('Y-m-d') }}"
                                data-checkin="{{ $booking->check_in_date ? $booking->check_in_date->format('Y-m-d') : '' }}"
                                data-checkout="{{ $booking->check_out_date ? $booking->check_out_date->format('Y-m-d') : '' }}"
                                data-nights="{{ $booking->nights_count ?? 1 }}"
                                data-guests="{{ $booking->guests_count }}"
                                data-amount="{{ number_format($booking->total_amount, 2) }}"
                                data-status="{{ $booking->status }}"
                                data-pay-id="{{ $booking->payment->id ?? '' }}"
                                data-pay-method="{{ $booking->payment->gateway ?? '' }}"
                                data-pay-ref="{{ $booking->payment?->guestReference() ?? '' }}"
                                data-pay-proof="{{ $booking->payment?->proofUrl() ?? '' }}"
                                data-requests="{{ $booking->special_requests }}"
                                data-guest-manual="{{ $booking->guest_name_manual }}"
                                data-contact-manual="{{ $booking->guest_contact_manual }}"
                                data-reason="{{ $booking->cancellation_reason ?? 'None' }}"
                                data-stage="{{ $booking->listStage() }}"
                                data-stage-label="{{ $booking->listStageLabel() }}"
                                data-kind="{{ $booking->isSpecialResort() ? 'Exclusive' : ($booking->isHourly() ? 'Hourly' : 'Nightly') }}"
                                data-stay-summary="{{ $hourIn
                                    ? $hourIn->format('M j, Y') . ' · ' . $hourIn->format('g:i A') . ' – ' . $hourOut->format('g:i A') . ' (' . (int) $booking->hours_count . ' ' . ((int) $booking->hours_count === 1 ? 'hour' : 'hours') . ')'
                                    : $booking->checkInDate()->format('M j, Y') . ' → ' . $booking->checkOutDate()->format('M j, Y') . ' · ' . ($booking->nights_count ?: 1) . ' ' . (($booking->nights_count ?: 1) == 1 ? 'night' : 'nights') }}"
                                data-pay-label="{{ $booking->payment ? ($paymentMethodNames[$booking->payment->gateway] ?? \Illuminate\Support\Str::headline((string) $booking->payment->gateway)) : '' }}"
                                data-pay-status="{{ $booking->payment->status ?? '' }}"
                                onclick="viewBooking(this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center py-12 text-slate-400">
                        <i class="bi bi-calendar-x text-3xl block mb-2 text-slate-300"></i>
                        <p class="text-xs font-semibold mb-0">No booking records match your criteria.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($bookings->hasPages())
    <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex justify-end">
        {{ $bookings->links() }}
    </div>
    @endif
</div>

{{-- Create Manual Booking Modal --}}
<div class="modal fade" id="manualBookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content manual-modal">
            <div class="manual-head">
                <div>
                    <h5>Manual booking</h5>
                    <p>Create a walk-in or a stay for a registered guest.</p>
                </div>
                <button type="button" class="manual-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            {{-- Form Body --}}
            <form method="POST" action="{{ route('bookings.manual-store') }}" id="manualBookingForm" data-loading>
                @csrf
                <div class="manual-body">
                    <div class="manual-row">
                        <label>Guest</label>
                        <div class="manual-choice">
                            <label>
                                <input type="radio" name="guest_type" value="walk_in" checked onchange="toggleGuestTypeFields()">
                                <span>Walk-in</span>
                            </label>
                            <label>
                                <input type="radio" name="guest_type" value="registered" onchange="toggleGuestTypeFields()">
                                <span>Registered guest</span>
                            </label>
                        </div>
                    </div>

                    @php $manualOld = (bool) old('booking_date'); @endphp
                    <div id="walkInFields">
                        <div class="manual-row">
                            <label>Name <span class="req-star">*</span></label>
                            <div class="manual-split manual-split-3">
                                <input type="text" name="first_name" id="guestFirstName" class="form-control-clean" placeholder="First name" aria-label="First name" maxlength="60" value="{{ $manualOld ? old('first_name') : '' }}" required>
                                <input type="text" name="middle_name" id="guestMiddleName" class="form-control-clean" placeholder="Middle name (optional)" aria-label="Middle name" maxlength="60" value="{{ $manualOld ? old('middle_name') : '' }}">
                                <input type="text" name="last_name" id="guestLastName" class="form-control-clean" placeholder="Last name" aria-label="Last name" maxlength="60" value="{{ $manualOld ? old('last_name') : '' }}" required>
                            </div>
                        </div>
                        <div class="manual-row">
                            <label>Contact <span class="req-star">*</span></label>
                            <div>
                                <div class="manual-split">
                                    <input type="tel" name="guest_phone" id="guestPhone" class="form-control-clean" placeholder="Contact number" aria-label="Contact number" maxlength="20" value="{{ $manualOld ? old('guest_phone') : '' }}" oninput="syncWalkInContact()">
                                    <input type="email" name="guest_email" id="guestEmail" class="form-control-clean" placeholder="Email address" aria-label="Email address" maxlength="120" value="{{ $manualOld ? old('guest_email') : '' }}" oninput="syncWalkInContact()">
                                </div>
                                <span class="manual-hint">Enter a contact number, an email address, or both.</span>
                            </div>
                        </div>
                    </div>

                    <div id="registeredFields" style="display: none;">
                        <div class="manual-row">
                            <label for="registeredUserSearch">Account</label>
                            @php $oldGuest = $tourists->firstWhere('id', (int) old('user_id')); @endphp
                            <div class="guest-lookup">
                                <input type="hidden" name="user_id" id="registeredUserId" value="{{ $oldGuest?->id }}">
                                <input type="text" id="registeredUserSearch" class="form-control-clean" autocomplete="off"
                                       placeholder="Type name, email, or phone number"
                                       value="{{ $oldGuest ? $oldGuest->name.' · '.$oldGuest->email : '' }}">
                                <div id="registeredUserResults" class="guest-lookup-results" hidden></div>                            </div>
                        </div>
                    </div>

                    @php
                        $unitKind = old('unit_kind', 'room');
                        $stayType = old('stay_type', 'night');
                    @endphp
                    <div class="manual-row">
                        <label>Book</label>
                        <div class="manual-choice">
                            <input type="hidden" name="unit_kind" id="manualUnitKind" value="{{ $unitKind }}">
                            <input type="hidden" id="manualUnitVariant" value="">
                            @foreach(['room' => 'Room', 'cottage' => 'Cottage'] as $kind => $kindLabel)
                                <div class="dropdown">
                                    <button type="button" class="manual-kind-btn {{ $unitKind === $kind ? 'is-active' : '' }}" data-kind="{{ $kind }}"
                                            data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                        <span class="manual-kind-text">{{ $kindLabel }}</span>
                                    </button>
                                    <ul class="dropdown-menu manual-kind-menu">
                                        <li><button type="button" class="dropdown-item" data-kind="{{ $kind }}" data-variant="" data-label="">All {{ strtolower($kindLabel) }}s</button></li>
                                        @foreach(\App\Models\UnitVariant::forKind($kind) as $variant)
                                            <li><button type="button" class="dropdown-item" data-kind="{{ $kind }}" data-variant="{{ $variant->slug }}" data-label="{{ $variant->label }}">{{ $variant->label }}</button></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @php $oldUnitIds = collect(old('accommodation_unit_ids', []))->map(fn ($id) => (int) $id)->all(); @endphp
                    <div class="manual-row manual-row-top">
                        <label><span id="manualUnitLabel">{{ $unitKind === 'cottage' ? 'Cottage' : 'Room' }}</span> <span class="req-star">*</span></label>
                        <div>
                            <div class="unit-picker" id="manualUnitPicker">
                                @foreach($accommodationUnits as $unit)
                                    <label class="unit-pick"
                                           data-price="{{ $unit->price_per_night }}"
                                           data-hour-price="{{ $unit->hourlyRate() }}"
                                           data-capacity="{{ $unit->max_occupancy }}"
                                           data-type="{{ $unit->unit_type }}"
                                           data-variant="{{ $unit->variant }}"
                                           data-name="{{ $unit->unit_number }}">
                                        <input type="checkbox" name="accommodation_unit_ids[]" value="{{ $unit->id }}" {{ in_array($unit->id, $oldUnitIds, true) ? 'checked' : '' }} onchange="calculateManualTotal()">
                                        <span class="unit-pick-name">{{ $unit->unit_number }}</span>
                                        <span class="unit-pick-meta">{{ $unit->variant_label }} · {{ $unit->max_occupancy }} pax · <span class="js-unit-rate">₱{{ number_format($unit->price_per_night, 0) }}/night</span></span>
                                        <span class="unit-pick-status" hidden></span>
                                    </label>
                                @endforeach
                                <div class="unit-pick-empty" id="manualUnitEmpty" hidden></div>
                            </div>
                            <span class="manual-hint" id="manualUnitHint">Tick one or more. A guest can book several rooms or cottages at once.</span>
                        </div>
                    </div>
                    <div class="manual-row">
                        <label>Rate</label>
                        <div class="manual-choice">
                            <label>
                                <input type="radio" name="stay_type" value="night" {{ $stayType !== 'hour' ? 'checked' : '' }} onchange="toggleManualStayType()">
                                <span>Per night</span>
                            </label>
                            <label>
                                <input type="radio" name="stay_type" value="hour" {{ $stayType === 'hour' ? 'checked' : '' }} onchange="toggleManualStayType()">
                                <span>Per hour</span>
                            </label>
                        </div>
                    </div>
                    <div class="manual-row">
                        <label id="manualStayLabel">Stay <span class="req-star">*</span></label>
                        <div class="manual-split" id="manualStaySplit">
                            <input type="date" name="booking_date" id="manualCheckInDate" class="form-control-clean" min="{{ now()->format('Y-m-d') }}" value="{{ old('booking_date', now()->format('Y-m-d')) }}" required aria-label="Check-in" onchange="syncCheckoutMin(); syncManualHourOptions(); calculateManualTotal(); refreshManualAvailability();">
                            <input type="date" name="check_out_date" id="manualCheckOutDate" class="form-control-clean" value="{{ old('check_out_date', now()->addDay()->format('Y-m-d')) }}" required aria-label="Check-out" onchange="calculateManualTotal(); refreshManualAvailability();">
                        </div>
                    </div>
                    <div class="manual-row" id="manualHourRow" style="display: none;">
                        <label>Time <span class="req-star">*</span></label>
                        <div class="manual-split">
                            <select name="hour_start" id="manualHourStart" class="form-select-clean" aria-label="Start time" onchange="calculateManualTotal(); refreshManualAvailability();">
                                @foreach (range(8, 20) as $hour)
                                    @php $slot = sprintf('%02d:00', $hour); @endphp
                                    <option value="{{ $slot }}" {{ old('hour_start') === $slot ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromTime($hour)->format('g:i A') }}</option>
                                @endforeach
                            </select>
                            <select name="hours_count" id="manualHoursCount" class="form-select-clean" aria-label="Hours" onchange="calculateManualTotal(); refreshManualAvailability();">
                                @foreach (range(1, 12) as $h)
                                    <option value="{{ $h }}" {{ (int) old('hours_count', 1) === $h ? 'selected' : '' }}>{{ $h }} {{ $h === 1 ? 'hour' : 'hours' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="manual-row">
                        <label for="manualGuestsCount">Guests</label>
                        <div>
                            <input type="number" name="guests_count" id="manualGuestsCount" class="form-control-clean" min="1" max="200" value="{{ old('guests_count', 1) }}" required oninput="calculateManualTotal()">
                            <span class="manual-hint" id="unitCapacityHint">Select a room or cottage to see the guest limit.</span>
                        </div>
                    </div>
                    <div class="manual-row manual-row-top">
                        <label>Activities</label>
                        <div>@include('partials.activity-addon-picks')</div>
                    </div>
                    <div class="manual-row">
                        <label>Payment <span class="req-star">*</span></label>
                        <div class="manual-split">
                            <select name="payment_method" class="form-select-clean" required aria-label="Payment method">
                                @foreach(($paymentMethods ?? \App\Models\PaymentMethod::catalog()) as $method)
                                    <option value="{{ $method->code }}" {{ old('payment_method') === $method->code ? 'selected' : '' }}>{{ $method->name }}</option>
                                @endforeach
                            </select>
                            <select name="initial_status" class="form-select-clean" required aria-label="Status">
                                <option value="pending" {{ old('initial_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="paid" {{ old('initial_status', 'paid') === 'paid' ? 'selected' : '' }}>Approved</option>
                                <option value="checked_in" {{ old('initial_status') === 'checked_in' ? 'selected' : '' }}>Check in now</option>
                            </select>
                        </div>
                    </div>
                    <div class="manual-row manual-row-top">
                        <label for="manualNotes">Notes</label>
                        <textarea name="special_requests" id="manualNotes" rows="2" class="form-control-clean" placeholder="Optional notes for the front desk">{{ old('special_requests') }}</textarea>
                    </div>
                    <div class="manual-total">
                        <span>Rate <strong id="calcRate">₱0</strong> · <strong id="calcNights">1 night</strong></span>
                        <strong id="calcTotal">₱0</strong>
                    </div>
                </div>
                <div class="manual-foot">
                    <button type="button" class="btn-secondary-clean" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-ocean">Create booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Exclusive full-resort booking --}}
<div class="modal fade" id="exclusiveBookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content manual-modal">
            <div class="manual-head">
                <div>
                    <h5>Exclusive booking</h5>
                    <p>Reserve every room and cottage for the selected nights.</p>
                </div>
                <button type="button" class="manual-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            @php
                $exclusiveOld = old('check_in') && !old('booking_date');
                $exclusiveGuest = $exclusiveOld ? $tourists->firstWhere('id', (int) old('user_id')) : null;
                $exclusiveRegisteredOld = $exclusiveOld && old('guest_type') === 'registered';
            @endphp
            <form method="POST" action="{{ route('bookings.exclusive-store') }}" id="exclusiveBookingForm">
                @csrf
                <div class="manual-body">
                    <div class="manual-row">
                        <label>Guest</label>
                        <div class="manual-choice">
                            <label>
                                <input type="radio" name="guest_type" value="walk_in" {{ $exclusiveRegisteredOld ? '' : 'checked' }} onchange="toggleExclusiveGuest()">
                                <span>Walk-in</span>
                            </label>
                            <label>
                                <input type="radio" name="guest_type" value="registered" {{ $exclusiveRegisteredOld ? 'checked' : '' }} onchange="toggleExclusiveGuest()">
                                <span>Registered guest</span>
                            </label>
                        </div>
                    </div>

                    <div id="exclusiveWalkIn">
                        <div class="manual-row">
                            <label>Name <span class="req-star">*</span></label>
                            <div class="manual-split manual-split-3">
                                <input type="text" name="first_name" id="exclusiveFirstName" class="form-control-clean" placeholder="First name" aria-label="First name" maxlength="60" value="{{ $exclusiveOld ? old('first_name') : '' }}" required>
                                <input type="text" name="middle_name" id="exclusiveMiddleName" class="form-control-clean" placeholder="Middle name (optional)" aria-label="Middle name" maxlength="60" value="{{ $exclusiveOld ? old('middle_name') : '' }}">
                                <input type="text" name="last_name" id="exclusiveLastName" class="form-control-clean" placeholder="Last name" aria-label="Last name" maxlength="60" value="{{ $exclusiveOld ? old('last_name') : '' }}" required>
                            </div>
                        </div>
                        <div class="manual-row">
                            <label>Contact <span class="req-star">*</span></label>
                            <div>
                                <div class="manual-split">
                                    <input type="tel" name="guest_phone" id="exclusivePhone" class="form-control-clean" placeholder="Contact number" aria-label="Contact number" maxlength="20" value="{{ $exclusiveOld ? old('guest_phone') : '' }}" oninput="syncWalkInContact('exclusivePhone', 'exclusiveEmail')">
                                    <input type="email" name="guest_email" id="exclusiveEmail" class="form-control-clean" placeholder="Email address" aria-label="Email address" maxlength="120" value="{{ $exclusiveOld ? old('guest_email') : '' }}" oninput="syncWalkInContact('exclusivePhone', 'exclusiveEmail')">
                                </div>
                                <span class="manual-hint">Enter a contact number, an email address, or both.</span>
                            </div>
                        </div>
                    </div>

                    <div id="exclusiveRegistered" style="display: none;">
                        <div class="manual-row">
                            <label for="exclusiveUserSearch">Account</label>
                            <div class="guest-lookup">
                                <input type="hidden" name="user_id" id="exclusiveUserId" value="{{ $exclusiveGuest?->id }}">
                                <input type="text" id="exclusiveUserSearch" class="form-control-clean" autocomplete="off"
                                       placeholder="Type name, email, or phone number"
                                       value="{{ $exclusiveGuest ? $exclusiveGuest->name.' · '.$exclusiveGuest->email : '' }}">
                                <div id="exclusiveUserResults" class="guest-lookup-results" hidden></div>
                            </div>
                        </div>
                    </div>

                    <div class="manual-row">
                        <label>Stay <span class="req-star">*</span></label>
                        <div class="manual-split">
                            <input type="date" name="check_in" id="exclusiveCheckIn" class="form-control-clean" min="{{ now()->format('Y-m-d') }}" value="{{ old('check_in', now()->format('Y-m-d')) }}" required aria-label="Check-in" onchange="syncExclusiveCheckoutMin(); previewExclusiveTotal();">
                            <input type="date" name="check_out" id="exclusiveCheckOut" class="form-control-clean" value="{{ old('check_out', now()->addDay()->format('Y-m-d')) }}" required aria-label="Check-out" onchange="previewExclusiveTotal()">
                        </div>
                    </div>
                    <div class="manual-row">
                        <label for="exclusiveGuestsCount">Guests</label>
                        <div>
                            <input type="number" name="guests_count" id="exclusiveGuestsCount" class="form-control-clean" min="1" max="{{ \App\Models\CapacitySchedule::visitorCap() }}" value="{{ $exclusiveOld ? old('guests_count', 20) : 20 }}" required>
                            <span class="manual-hint">Up to {{ \App\Models\CapacitySchedule::visitorCap() }} guests, the daily visitor cap.</span>
                        </div>
                    </div>
                    <div class="manual-row manual-row-top">
                        <label>Activities</label>
                        <div>@include('partials.activity-addon-picks')</div>
                    </div>
                    <div class="manual-row">
                        <label>Payment <span class="req-star">*</span></label>
                        <div class="manual-split">
                            <select name="payment_method" class="form-select-clean" required aria-label="Payment method">
                                @foreach(($paymentMethods ?? \App\Models\PaymentMethod::catalog()) as $method)
                                    <option value="{{ $method->code }}" {{ $exclusiveOld && old('payment_method') === $method->code ? 'selected' : '' }}>{{ $method->name }}</option>
                                @endforeach
                            </select>
                            @php $exclusiveStatus = $exclusiveOld ? old('initial_status', 'paid') : 'paid'; @endphp
                            <select name="initial_status" class="form-select-clean" required aria-label="Status">
                                <option value="pending" {{ $exclusiveStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="paid" {{ $exclusiveStatus === 'paid' ? 'selected' : '' }}>Approved</option>
                                <option value="checked_in" {{ $exclusiveStatus === 'checked_in' ? 'selected' : '' }}>Check in now</option>
                            </select>
                        </div>
                    </div>
                    <div class="manual-row">
                        <label for="exclusiveOccasion">What is this booking for?</label>
                        <select name="occasion" id="exclusiveOccasion" class="form-select-clean" required>
                            <option value="">Choose the occasion</option>
                            @foreach(\App\Models\Booking::occasions() as $choice)
                                <option value="{{ $choice }}" {{ $exclusiveOld && old('occasion') === $choice ? 'selected' : '' }}>{{ $choice }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="manual-row manual-row-top">
                        <label for="exclusiveNotes">Notes</label>
                        <textarea name="special_requests" id="exclusiveNotes" rows="2" class="form-control-clean" placeholder="Event details or special requests">{{ $exclusiveOld ? old('special_requests') : '' }}</textarea>
                    </div>
                    <div class="manual-total">
                        <span>Rate <strong>₱{{ number_format($exclusiveNightlyRate ?? 0, 2) }}/night</strong> · <strong id="exclusiveCreateNights">1 night</strong></span>
                        <strong id="exclusiveCreateTotal">₱{{ number_format($exclusiveNightlyRate ?? 0, 2) }}</strong>
                    </div>
                </div>
                <div class="manual-foot">
                    <button type="button" class="btn-secondary-clean" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-ocean">Create exclusive booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Booking Details Modal --}}
<div class="modal fade" id="bookingDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content manual-modal">
            <div class="manual-head">
                <div>
                    <h5 class="detail-title"><span id="detailRef">Booking</span> <span class="res-status" id="detailStage"></span></h5>
                    <p id="detailSubtitle"></p>
                </div>
                <button type="button" class="manual-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="manual-body" id="bookingDetailsContent"></div>
            <div class="manual-foot">
                <button type="button" class="btn-secondary-clean" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn-ocean" form="bookingStatusForm">Save status</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function toggleGuestTypeFields() {
    const selected = document.querySelector('#manualBookingForm input[name="guest_type"]:checked')?.value;
    const walkInFields = document.getElementById('walkInFields');
    const registeredFields = document.getElementById('registeredFields');
    const userSelect = document.getElementById('registeredUserSearch');
    const walkIn = selected === 'walk_in';

    walkInFields.style.display = walkIn ? 'block' : 'none';
    registeredFields.style.display = walkIn ? 'none' : 'block';
    walkInFields.querySelectorAll('input').forEach(field => { field.disabled = !walkIn; });
    userSelect.required = !walkIn;
    userSelect.disabled = walkIn;
    syncWalkInContact();
    if (typeof markRequiredStars === 'function') markRequiredStars(document.getElementById('manualBookingForm'));
}

function syncWalkInContact(phoneId = 'guestPhone', emailId = 'guestEmail') {
    const phone = document.getElementById(phoneId);
    const email = document.getElementById(emailId);
    const missing = !phone.value.trim() && !email.value.trim();
    phone.setCustomValidity(missing ? 'Enter a contact number or an email address.' : '');
}

@php $registeredGuestList = $tourists->map->only(['id', 'name', 'email', 'phone'])->values(); @endphp
const registeredGuests = @json($registeredGuestList);

function escapeGuestText(value) {
    return String(value ?? '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
}

function setupGuestLookup(searchId, hiddenId, resultsId) {
    const search = document.getElementById(searchId);
    const hidden = document.getElementById(hiddenId);
    const results = document.getElementById(resultsId);
    if (!search) return;

    const syncValidity = () => search.setCustomValidity(hidden.value ? '' : 'Choose a registered guest from the list.');

    const render = () => {
        const term = search.value.trim().toLowerCase();
        if (!term) {
            results.hidden = true;
            return;
        }
        const digits = term.replace(/\D/g, '');
        const matches = registeredGuests.filter(g =>
            g.name.toLowerCase().includes(term) ||
            g.email.toLowerCase().includes(term) ||
            (digits && (g.phone || '').replace(/\D/g, '').includes(digits))
        ).slice(0, 8);

        if (!matches.length) {
            results.innerHTML = '<div class="guest-lookup-empty">No registered guest found.</div>';
            results.hidden = false;
            return;
        }
        results.innerHTML = matches.map(g => `
            <button type="button" class="guest-lookup-item" data-id="${g.id}">
                <strong>${escapeGuestText(g.name)}</strong>
                <span>${escapeGuestText(g.email)}${g.phone ? ' · ' + escapeGuestText(g.phone) : ''}</span>
            </button>`).join('');
        results.hidden = false;
    };

    search.addEventListener('input', () => {
        hidden.value = '';
        syncValidity();
        render();
    });
    search.addEventListener('focus', render);
    search.addEventListener('blur', () => setTimeout(() => { results.hidden = true; }, 150));
    results.addEventListener('mousedown', e => e.preventDefault());
    results.addEventListener('click', e => {
        const item = e.target.closest('.guest-lookup-item');
        if (!item) return;
        const guest = registeredGuests.find(g => String(g.id) === item.dataset.id);
        hidden.value = guest.id;
        search.value = guest.name + ' · ' + guest.email;
        results.hidden = true;
        syncValidity();
    });
    syncValidity();
}

function syncCheckoutMin() {
    const cin = document.getElementById('manualCheckInDate').value;
    const coutInput = document.getElementById('manualCheckOutDate');
    if (cin) {
        const nextDay = new Date(cin);
        nextDay.setDate(nextDay.getDate() + 1);
        const nextDayStr = nextDay.toISOString().split('T')[0];
        coutInput.min = nextDayStr;
        if (!coutInput.value || coutInput.value <= cin) {
            coutInput.value = nextDayStr;
        }
    }
}

function manualStayType() {
    return document.querySelector('#manualBookingForm input[name="stay_type"]:checked')?.value || 'night';
}

function refreshManualUnitLabels() {
    const hourly = manualStayType() === 'hour';
    document.querySelectorAll('#manualUnitPicker .unit-pick').forEach(pick => {
        const rate = parseFloat(hourly ? pick.dataset.hourPrice : pick.dataset.price) || 0;
        pick.querySelector('.js-unit-rate').textContent = `₱${rate.toLocaleString('en-US', {maximumFractionDigits: 2})}/${hourly ? 'hour' : 'night'}`;
    });
}

function selectedManualUnits() {
    return Array.from(document.querySelectorAll('#manualUnitPicker .unit-pick'))
        .filter(pick => pick.querySelector('input').checked);
}

function chooseManualKind(kind, variant, variantLabel) {
    document.getElementById('manualUnitKind').value = kind;
    document.getElementById('manualUnitVariant').value = variant;
    document.querySelectorAll('#manualBookingForm .manual-kind-btn').forEach(btn => {
        const active = btn.dataset.kind === kind;
        const base = btn.dataset.kind === 'cottage' ? 'Cottage' : 'Room';
        btn.classList.toggle('is-active', active);
        btn.querySelector('.manual-kind-text').textContent = active && variantLabel ? `${base} · ${variantLabel}` : base;
    });
    document.querySelectorAll('#manualBookingForm .manual-kind-menu .dropdown-item').forEach(item => {
        item.classList.toggle('active', item.dataset.kind === kind && item.dataset.variant === variant);
    });
    filterManualUnits();
}

function filterManualUnits() {
    const kind = document.getElementById('manualUnitKind').value || 'room';
    const variant = document.getElementById('manualUnitVariant').value;
    const label = kind === 'cottage' ? 'Cottage' : 'Room';
    document.getElementById('manualUnitLabel').textContent = label;

    let visible = 0;
    document.querySelectorAll('#manualUnitPicker .unit-pick').forEach(pick => {
        const match = pick.dataset.type === kind && (!variant || pick.dataset.variant === variant);
        pick.hidden = !match;
        if (match) visible++;
    });
    const empty = document.getElementById('manualUnitEmpty');
    empty.hidden = visible > 0;
    empty.textContent = `No ${label.toLowerCase()} available for this type.`;
    calculateManualTotal();
}

function syncManualHourOptions() {
    const date = document.getElementById('manualCheckInDate').value;
    const start = document.getElementById('manualHourStart');
    const now = new Date();
    const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    Array.from(start.options).forEach(option => {
        option.disabled = date === today && parseInt(option.value, 10) <= now.getHours();
    });
    if (start.selectedOptions[0]?.disabled) {
        const next = Array.from(start.options).find(option => !option.disabled);
        start.value = next ? next.value : '';
    }
}

function toggleManualStayType() {
    const hourly = manualStayType() === 'hour';
    const checkOut = document.getElementById('manualCheckOutDate');
    const hourRow = document.getElementById('manualHourRow');
    const stayLabel = document.getElementById('manualStayLabel');

    checkOut.style.display = hourly ? 'none' : '';
    checkOut.disabled = hourly;
    checkOut.required = !hourly;
    document.getElementById('manualStaySplit').classList.toggle('manual-split-single', hourly);
    document.getElementById('manualCheckInDate').setAttribute('aria-label', hourly ? 'Date' : 'Check-in');
    stayLabel.firstChild.textContent = hourly ? 'Date ' : 'Stay ';

    hourRow.style.display = hourly ? '' : 'none';
    ['manualHourStart', 'manualHoursCount'].forEach(id => {
        const field = document.getElementById(id);
        field.disabled = !hourly;
        field.required = hourly;
    });

    if (hourly) syncManualHourOptions();
    refreshManualUnitLabels();
    calculateManualTotal();
    refreshManualAvailability();
}

let manualAvailabilityTimer = null;
let manualAvailabilitySeq = 0;

function refreshManualAvailability() {
    clearTimeout(manualAvailabilityTimer);
    manualAvailabilityTimer = setTimeout(loadManualAvailability, 150);
}

async function loadManualAvailability() {
    const date = document.getElementById('manualCheckInDate').value;
    if (!date) return applyManualAvailability({});

    const hourly = manualStayType() === 'hour';
    const params = new URLSearchParams({ date, stay_type: hourly ? 'hour' : 'night' });
    if (hourly) {
        params.set('hour_start', document.getElementById('manualHourStart').value);
        params.set('hours', document.getElementById('manualHoursCount').value);
    } else {
        params.set('check_out', document.getElementById('manualCheckOutDate').value);
    }

    const seq = ++manualAvailabilitySeq;
    const picker = document.getElementById('manualUnitPicker');
    picker.classList.add('is-loading');
    try {
        const res = await fetch(`{{ route('bookings.unit-availability') }}?${params}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) throw new Error(res.status);
        const data = await res.json();
        if (seq === manualAvailabilitySeq) applyManualAvailability(data.unavailable || {});
    } catch (e) {
        if (seq === manualAvailabilitySeq) applyManualAvailability({});
    } finally {
        if (seq === manualAvailabilitySeq) picker.classList.remove('is-loading');
    }
}

function applyManualAvailability(unavailable) {
    const removed = [];
    document.querySelectorAll('#manualUnitPicker .unit-pick').forEach(pick => {
        const box = pick.querySelector('input');
        const reason = unavailable[box.value];
        const status = pick.querySelector('.unit-pick-status');
        pick.classList.toggle('is-booked', !!reason);
        box.disabled = !!reason;
        status.hidden = !reason;
        status.textContent = reason || '';
        pick.title = reason ? `${pick.dataset.name} is not available — ${reason.toLowerCase()}` : '';
        if (reason && box.checked) {
            box.checked = false;
            removed.push(pick.dataset.name);
        }
    });
    calculateManualTotal();
    if (removed.length) {
        const hint = document.getElementById('manualUnitHint');
        hint.textContent = `${removed.join(', ')} ${removed.length > 1 ? 'are' : 'is'} already booked for that time and ${removed.length > 1 ? 'were' : 'was'} unselected.`;
        hint.classList.add('is-error');
    }
}

function calculateManualTotal() {
    const picks = selectedManualUnits();
    const hourly = manualStayType() === 'hour';
    const price = picks.reduce((sum, pick) => sum + (parseFloat(hourly ? pick.dataset.hourPrice : pick.dataset.price) || 0), 0);
    const capacity = picks.reduce((sum, pick) => sum + (parseInt(pick.dataset.capacity, 10) || 0), 0);

    const unitHint = document.getElementById('manualUnitHint');
    unitHint.classList.remove('is-error');
    unitHint.textContent = picks.length
        ? `${picks.length} selected: ${picks.map(pick => pick.dataset.name).join(', ')}`
        : 'Tick one or more. A guest can book several rooms or cottages at once.';

    const cinVal = document.getElementById('manualCheckInDate').value;
    const coutVal = document.getElementById('manualCheckOutDate').value;

    const guestsInput = document.getElementById('manualGuestsCount');
    const hint = document.getElementById('unitCapacityHint');
    const guestCount = parseInt(guestsInput.value, 10);
    if (!picks.length) {
        hint.textContent = 'Select a room or cottage to see the guest limit.';
        guestsInput.setCustomValidity('');
    } else if (Number.isFinite(guestCount) && guestCount > capacity) {
        hint.textContent = `The selected ${picks.length > 1 ? 'units hold' : 'unit holds'} up to ${capacity} guests.`;
        guestsInput.setCustomValidity(`Up to ${capacity} guests for the selected rooms and cottages.`);
    } else if (Number.isFinite(guestCount) && guestCount < picks.length) {
        hint.textContent = 'Enter at least one guest for each room or cottage.';
        guestsInput.setCustomValidity('Enter at least one guest for each room or cottage.');
    } else {
        hint.textContent = `Up to ${capacity} guests${picks.length > 1 ? ' across the selected units' : ''}.`;
        guestsInput.setCustomValidity('');
    }

    let nights = 1;
    if (cinVal && coutVal) {
        const d1 = new Date(cinVal);
        const d2 = new Date(coutVal);
        const diffTime = d2 - d1;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        nights = diffDays > 0 ? diffDays : 1;
    }

    const units = hourly ? (parseInt(document.getElementById('manualHoursCount').value, 10) || 1) : nights;
    const total = price * units;
    document.getElementById('calcRate').textContent = `₱${price.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}${hourly ? '/hour' : '/night'}`;
    document.getElementById('calcNights').textContent = hourly
        ? `${units} hour${units > 1 ? 's' : ''}`
        : `${nights} night${nights > 1 ? 's' : ''}`;
    document.getElementById('calcTotal').textContent = `₱${total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
}

// Initialize listeners on DOM ready
document.addEventListener('DOMContentLoaded', function () {
    syncCheckoutMin();
    document.querySelectorAll('#manualBookingForm .manual-kind-menu .dropdown-item').forEach(item => {
        item.addEventListener('click', () => chooseManualKind(item.dataset.kind, item.dataset.variant, item.dataset.label));
    });
    filterManualUnits();
    toggleManualStayType();
    toggleGuestTypeFields();
    setupGuestLookup('registeredUserSearch', 'registeredUserId', 'registeredUserResults');
    setupGuestLookup('exclusiveUserSearch', 'exclusiveUserId', 'exclusiveUserResults');
    toggleExclusiveGuest();
    syncExclusiveCheckoutMin();
    previewExclusiveTotal();
    @if($errors->any() && old('check_in') && !old('booking_date'))
    const exclusiveModal = document.getElementById('exclusiveBookingModal');
    if (exclusiveModal && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(exclusiveModal).show();
    }
    @endif
    @if(old('booking_date') && ($errors->any() || session('error')))
    const manualModal = document.getElementById('manualBookingModal');
    if (manualModal && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(manualModal).show();
    }
    @endif
    document.getElementById('manualBookingForm').addEventListener('submit', function (event) {
        if (!selectedManualUnits().length) {
            event.preventDefault();
            event.stopImmediatePropagation();
            const hint = document.getElementById('manualUnitHint');
            hint.textContent = 'Choose at least one room or cottage.';
            hint.classList.add('is-error');
            document.getElementById('manualUnitPicker').scrollIntoView({block: 'center', behavior: 'smooth'});
        }
    }, true);
});

function escapeBookingHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function viewBooking(button) {
    const id = escapeBookingHtml(button.getAttribute('data-id'));
    const ref = escapeBookingHtml(button.getAttribute('data-ref'));
    const source = button.getAttribute('data-source') || 'online';
    const type = button.getAttribute('data-type');
    const approval = button.getAttribute('data-approval');
    const name = escapeBookingHtml(button.getAttribute('data-name'));
    const phone = escapeBookingHtml(button.getAttribute('data-phone'));
    const email = escapeBookingHtml(button.getAttribute('data-email'));
    const unit = escapeBookingHtml(button.getAttribute('data-unit'));
    const date = escapeBookingHtml(button.getAttribute('data-date'));
    const checkin = escapeBookingHtml(button.getAttribute('data-checkin'));
    const checkout = escapeBookingHtml(button.getAttribute('data-checkout'));
    const nights = escapeBookingHtml(button.getAttribute('data-nights'));
    const guests = escapeBookingHtml(button.getAttribute('data-guests'));
    const amount = escapeBookingHtml(button.getAttribute('data-amount'));
    const status = escapeBookingHtml(button.getAttribute('data-status'));
    const payId = escapeBookingHtml(button.getAttribute('data-pay-id'));
    const payMethod = escapeBookingHtml(button.getAttribute('data-pay-method'));
    const payRef = escapeBookingHtml(button.getAttribute('data-pay-ref'));
    const payProof = button.getAttribute('data-pay-proof') || '';
    const requests = escapeBookingHtml(button.getAttribute('data-requests') || '');
    const guestManual = escapeBookingHtml(button.getAttribute('data-guest-manual') || '');
    const contactManual = escapeBookingHtml(button.getAttribute('data-contact-manual') || '');
    const reason = escapeBookingHtml(button.getAttribute('data-reason'));

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    const kind = button.getAttribute('data-kind') || '';
    const staySummary = escapeBookingHtml(button.getAttribute('data-stay-summary') || '');
    const payLabel = escapeBookingHtml(button.getAttribute('data-pay-label') || '');
    const payStatusRaw = button.getAttribute('data-pay-status') || '';
    const payPaid = ['success', 'paid', 'completed'].includes(payStatusRaw);
    const payStatus = payPaid ? 'Paid' : payStatusRaw.replace(/_/g, ' ');
    const contactLine = [phone, email !== 'N/A' ? email : ''].filter(Boolean).join(' · ');

    document.getElementById('detailRef').textContent = button.getAttribute('data-ref');
    const stageEl = document.getElementById('detailStage');
    stageEl.className = `res-status res-status-${button.getAttribute('data-stage') || status}`;
    stageEl.textContent = button.getAttribute('data-stage-label') || status;
    document.getElementById('detailSubtitle').textContent = `${kind} booking · ${source === 'manual' ? 'Created at the front desk' : 'Booked online'}`;

    const canApproveSpecialBooking = @json(auth()->user()->isAdmin() || auth()->user()->hasPermission('bookings', 'confirm'));
    const canEditExclusive = @json(auth()->user()->isAdmin() || auth()->user()->hasPermission('bookings', 'edit'));

    let specialApprovalHtml = '';
    if (type === 'special_resort') {
        if (approval === 'pending' && canApproveSpecialBooking) {
            specialApprovalHtml = `
                <div class="manual-row detail-approval">
                    <label>Approval</label>
                    <div class="detail-approval-body">
                        <span class="detail-value">Waiting for approval. Approving reserves the whole resort for these dates.</span>
                        <div class="detail-actions">
                            <form method="POST" action="/bookings/${id}/approve-special">
                                <input type="hidden" name="_token" value="${csrfToken}">
                                <button type="submit" class="btn-ocean">Approve</button>
                            </form>
                            <form method="POST" action="/bookings/${id}/reject-special" onsubmit="return confirm('Decline this exclusive booking?');">
                                <input type="hidden" name="_token" value="${csrfToken}">
                                <button type="submit" class="btn-secondary-clean detail-danger">Decline</button>
                            </form>
                        </div>
                    </div>
                </div>
            `;
        } else if (approval === 'pending') {
            specialApprovalHtml = `
                <div class="manual-row detail-approval">
                    <label>Approval</label>
                    <span class="detail-value">Waiting for a staff member who can confirm bookings.</span>
                </div>
            `;
        } else if (approval === 'approved') {
            specialApprovalHtml = `
                <div class="manual-row">
                    <label>Approval</label>
                    <span class="detail-value detail-ok">Approved. The whole resort is reserved for these dates.</span>
                </div>
            `;
        }
    }

    const needsProof = payMethod === 'gcash' || payMethod === 'paypal';

    document.getElementById('bookingDetailsContent').innerHTML = `
        <div class="manual-row">
            <label>Guest</label>
            <span class="detail-value"><strong>${name}</strong></span>
        </div>
        <div class="manual-row">
            <label>Contact</label>
            <span class="detail-value">${contactLine || '—'}</span>
        </div>
        <div class="manual-row">
            <label>${type === 'special_resort' ? 'Booked' : 'Room'}</label>
            <span class="detail-value">${unit}</span>
        </div>
        <div class="manual-row">
            <label>${kind === 'Hourly' ? 'Date & time' : 'Stay'}</label>
            <span class="detail-value">${staySummary}</span>
        </div>
        <div class="manual-row">
            <label>Guests</label>
            <span class="detail-value">${guests} ${guests === '1' ? 'guest' : 'guests'}</span>
        </div>
        <div class="manual-row manual-row-top">
            <label>Payment</label>
            <div>
                <span class="detail-value">${payLabel || 'Not recorded'}${payStatus ? ` <span class="detail-chip${payPaid ? ' is-paid' : ''}">${escapeBookingHtml(payStatus)}</span>` : ''}</span>
                ${needsProof ? `<span class="manual-hint">${payRef ? 'Reference ' + payRef : 'No reference number yet'}${payProof ? '' : ' · No receipt uploaded'}</span>` : ''}
                ${(payProof || payId) ? `<div class="detail-links">
                    ${payProof ? `<a href="${escapeBookingHtml(payProof)}" target="_blank" rel="noopener">Open receipt</a>` : ''}
                    ${payId ? `<a href="/payments/${payId}">Review payment</a>` : ''}
                </div>` : ''}
            </div>
        </div>

        ${specialApprovalHtml}

        <div class="manual-row manual-row-top">
            <label>Notes</label>
            <span class="detail-value detail-notes">${requests || '<span class="detail-muted">None</span>'}</span>
        </div>

        ${(type === 'special_resort' && canEditExclusive && status !== 'cancelled' && status !== 'checked_out' && status !== 'completed') ? `
        <form method="POST" action="/bookings/${id}/exclusive" class="detail-edit">
            <input type="hidden" name="_token" value="${csrfToken}">
            <input type="hidden" name="_method" value="PUT">
            <div class="detail-section">
                <strong>Edit exclusive stay</strong>
                <span>Change the dates, guests, or notes without cancelling. The total uses the current nightly rates.</span>
            </div>
            <div class="manual-row">
                <label>Stay</label>
                <div class="manual-split">
                    <input type="date" name="check_in" value="${checkin || date}" class="form-control-clean js-ex-in" aria-label="Check-in" required>
                    <input type="date" name="check_out" value="${checkout}" class="form-control-clean js-ex-out" aria-label="Check-out" required>
                </div>
            </div>
            <div class="manual-row">
                <label>Guests</label>
                <input type="number" name="guests_count" min="1" max="{{ \App\Models\CapacitySchedule::visitorCap() }}" value="${guests}" class="form-control-clean detail-narrow" required>
            </div>
            <div class="manual-row">
                <label>Guest</label>
                <div class="manual-split">
                    <input type="text" name="guest_name" value="${guestManual || name}" class="form-control-clean" placeholder="Name on this booking" aria-label="Guest name">
                    <input type="text" name="guest_contact" value="${contactManual || phone}" class="form-control-clean" placeholder="Contact" aria-label="Contact">
                </div>
            </div>
            <div class="manual-row manual-row-top">
                <label>Notes</label>
                <textarea name="special_requests" rows="2" class="form-control-clean">${requests}</textarea>
            </div>
            <div class="manual-row detail-edit-foot">
                <span class="detail-value">New total <strong class="js-ex-total">PHP ${amount}</strong></span>
                <button type="submit" class="btn-secondary-clean">Save stay changes</button>
            </div>
        </form>
        ` : ''}

        ${status === 'cancelled' ? `
        <div class="manual-row">
            <label>Cancel reason</label>
            <span class="detail-value detail-danger-text">${reason}</span>
        </div>` : ''}

        <form method="POST" action="/bookings/${id}/status" id="bookingStatusForm" class="manual-row manual-row-top">
            <input type="hidden" name="_token" value="${csrfToken}">
            <input type="hidden" name="_method" value="PATCH">
            <label for="detailStatus">Status</label>
            <div class="manual-split">
                <select name="status" id="detailStatus" class="form-select-clean" onchange="toggleCancelField(this, ${id})">
                    <option value="pending" ${status === 'pending' ? 'selected' : ''}>Pending</option>
                    <option value="paid" ${status === 'paid' ? 'selected' : ''}>Paid</option>
                    <option value="checked_in" ${status === 'checked_in' ? 'selected' : ''}>Checked in</option>
                    <option value="checked_out" ${status === 'checked_out' ? 'selected' : ''}>Checked out</option>
                    <option value="completed" ${status === 'completed' ? 'selected' : ''}>Completed</option>
                    <option value="cancelled" ${status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                </select>
                <input type="text" name="cancellation_reason" id="cancelReasonWrapper_${id}" class="form-control-clean" placeholder="Reason for cancelling" aria-label="Cancellation reason" style="display:none;">
            </div>
        </form>

        <div class="manual-total">
            <span>${kind} · ${guests} ${guests === '1' ? 'guest' : 'guests'}</span>
            <strong>₱${amount}</strong>
        </div>
    `;

    const modalObj = new bootstrap.Modal(document.getElementById('bookingDetailModal'));
    modalObj.show();
    bindExclusivePreview(document.getElementById('bookingDetailsContent'));
}

function exclusiveNightlyRate() {
    return {{ (float) ($exclusiveNightlyRate ?? 0) }};
}
function exclusiveNights(checkIn, checkOut) {
    const inn = new Date(checkIn);
    const out = new Date(checkOut);
    if (!checkIn || !checkOut || Number.isNaN(inn.getTime()) || Number.isNaN(out.getTime()) || out <= inn) return 1;
    return Math.round((out - inn) / 86400000);
}
function previewExclusiveTotal() {
    const inn = document.getElementById('exclusiveCheckIn')?.value;
    const out = document.getElementById('exclusiveCheckOut')?.value;
    const node = document.getElementById('exclusiveCreateTotal');
    if (!node) return;
    const nights = exclusiveNights(inn, out);
    node.textContent = '₱' + (exclusiveNightlyRate() * nights).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const nightsNode = document.getElementById('exclusiveCreateNights');
    if (nightsNode) nightsNode.textContent = `${nights} night${nights === 1 ? '' : 's'}`;
}
function syncExclusiveCheckoutMin() {
    const inn = document.getElementById('exclusiveCheckIn');
    const out = document.getElementById('exclusiveCheckOut');
    if (!inn?.value || !out) return;
    const next = new Date(inn.value);
    next.setDate(next.getDate() + 1);
    const nextStr = next.toISOString().split('T')[0];
    out.min = nextStr;
    if (!out.value || out.value <= inn.value) out.value = nextStr;
}
function toggleExclusiveGuest() {
    const registered = document.querySelector('#exclusiveBookingForm input[name="guest_type"]:checked')?.value === 'registered';
    const walkIn = document.getElementById('exclusiveWalkIn');
    const account = document.getElementById('exclusiveRegistered');
    if (!walkIn || !account) return;
    walkIn.style.display = registered ? 'none' : '';
    account.style.display = registered ? '' : 'none';
    walkIn.querySelectorAll('input').forEach(field => { field.disabled = registered; });
    syncWalkInContact('exclusivePhone', 'exclusiveEmail');
    const search = document.getElementById('exclusiveUserSearch');
    search.required = registered;
    search.disabled = !registered;
    if (typeof markRequiredStars === 'function') markRequiredStars(document.getElementById('exclusiveBookingForm'));
}
function bindExclusivePreview(root) {
    if (!root) return;
    const inn = root.querySelector('.js-ex-in');
    const out = root.querySelector('.js-ex-out');
    const total = root.querySelector('.js-ex-total');
    const paint = () => {
        if (!inn || !out || !total) return;
        const nights = exclusiveNights(inn.value, out.value);
        total.textContent = 'PHP ' + (exclusiveNightlyRate() * nights).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };
    inn?.addEventListener('change', paint);
    out?.addEventListener('change', paint);
    paint();
}

function toggleCancelField(select, id) {
    const field = document.getElementById(`cancelReasonWrapper_${id}`);
    if (field) field.style.display = select.value === 'cancelled' ? '' : 'none';
}
</script>
@endpush