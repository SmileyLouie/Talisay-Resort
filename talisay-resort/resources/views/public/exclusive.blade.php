@extends('layouts.public')

@section('title', 'Exclusive full resort – '.$resortInfo['name'])
@section('meta_description', 'Book every room and cottage at '.$resortInfo['name'].' for a private stay. Add the event details, then pay.')

@section('content')
@php
    $peso = fn ($n) => '₱'.number_format((float) $n, 2);
    $rooms = $units->where('unit_type', 'room');
    $cottages = $units->where('unit_type', 'cottage');
    $changeUrl = $checkIn
        ? route('public.booking', ['check_in' => $checkIn, 'check_out' => $checkOut, 'guests' => $guests, 'exclusive' => 1, 'edit' => 1])
        : route('public.booking');
    $savedNote = old('special_requests', $eventNote);
    if (is_string($savedNote) && str_starts_with($savedNote, 'Occasion:')) {
        $savedNote = trim((string) preg_replace('/^Occasion:[^\n]*\n+/', '', $savedNote));
    }
@endphp
<section class="lp-book-page">
    <div class="lp-section">
        @if(session('success'))
            <div class="lp-note lp-note-ok"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="lp-note lp-note-bad"><i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="lp-note lp-note-bad"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        @if($doneRef)
            <div class="lp-confirm">
                <i class="bi bi-check-circle"></i>
                <h2>Request received</h2>
                <p class="lp-ref">{{ $doneRef }}</p>
                <p style="color:#475569;max-width:520px;margin:12px auto 22px;">Management will review this exclusive request before the whole resort is reserved for your dates. Keep the reference.</p>
                <a class="lp-btn lp-btn-sand" href="{{ route('tourist.bookings') }}">My bookings</a>
                <a class="lp-btn lp-btn-outline" href="{{ url('/') }}" style="margin-left:8px;">Back to home</a>
            </div>
        @else
            <div class="lp-ex-head">
                <div class="lp-label">Private stay</div>
                <h1>{{ $exclusive['title'] }}</h1>
                <p>{{ $exclusive['description'] }}</p>
            </div>

            @if(!$checkIn)
                <div class="lp-ex-card">
                    <h2>Choose your dates first</h2>
                    <p style="color:#475569;">{{ $dateMessage }}</p>
                    <a class="lp-btn lp-btn-sand" href="{{ $changeUrl }}" style="margin-top:16px;"><i class="bi bi-calendar-check"></i> Select dates</a>
                </div>
            @else
                <div class="lp-ex-layout">
                    <div>
                        <article class="lp-ex-card">
                            <h2>What this booking is</h2>
                            <p style="color:#475569;margin:0 0 14px;">{{ $exclusive['information'] }}</p>
                            <dl class="lp-ex-facts">
                                <div><dt>Type</dt><dd>Full resort, overnight</dd></div>
                                <div><dt>What is included</dt><dd>{{ $units->count() }} rooms and cottages</dd></div>
                                <div><dt>Who can stay</dt><dd>Up to {{ $visitorCap }} guests, the resort’s daily visitor cap</dd></div>
                                <div><dt>How the price works</dt><dd>{{ $peso($nightSum) }} per night, times {{ $nights }} {{ $nights === 1 ? 'night' : 'nights' }}</dd></div>
                                <div><dt>After you pay</dt><dd>Pending until management confirms</dd></div>
                            </dl>
                        </article>

                        <article class="lp-ex-card">
                            <h2>Rooms and cottages included</h2>
                            <table class="lp-ex-units">
                                <thead><tr><th>Place</th><th>Kind</th><th>Sleeps</th><th>Per night</th></tr></thead>
                                <tbody>
                                    @foreach($rooms->concat($cottages) as $unit)
                                        <tr>
                                            <td>{{ $unit->unit_number }}</td>
                                            <td>{{ $unit->type_label }} · {{ $unit->variant_label }}</td>
                                            <td>{{ (int) $unit->max_occupancy }}</td>
                                            <td>{{ $peso($unit->price_per_night) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </article>

                        @if($blocked)
                            <article class="lp-ex-card">
                                <h2>These dates are already in use</h2>
                                <p style="color:#475569;">Another reservation uses a room or cottage on these nights, so the whole resort cannot be booked then.</p>
                                <a class="lp-btn lp-btn-outline" href="{{ $changeUrl }}" style="margin-top:16px;">Choose different dates</a>
                            </article>
                        @else
                            <form class="lp-ex-card" id="exclusiveForm" method="POST" action="{{ route('tourist.bookings.special-resort') }}" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="wizard" value="1">
                                <input type="hidden" name="check_in_date" value="{{ $checkIn }}">
                                <input type="hidden" name="check_out_date" value="{{ $checkOut }}">
                                <span class="js-stay-total" hidden>{{ $peso($total) }}</span>
                                <h2>Your event</h2>
                                <p style="color:#475569;margin:-4px 0 14px;">Add what the stay is for. The payment button appears after these details are filled in.</p>
                                <div class="lp-ex-form">
                                    <label>What is this booking for?
                                        <select name="occasion" id="occasion" required>
                                            <option value="">Choose the occasion</option>
                                            @foreach(\App\Models\Booking::occasions() as $choice)
                                                <option value="{{ $choice }}" @selected(old('occasion', $occasion) === $choice)>{{ $choice }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label>Number of guests
                                        <input type="number" name="guests_count" id="exclusiveGuests" min="1" max="{{ $visitorCap }}" value="{{ old('guests_count', $guests) }}" required>
                                    </label>
                                    <label>Details for the resort
                                        <textarea name="special_requests" id="eventNote" maxlength="1000" required placeholder="Tell the resort about the event: the schedule, setup, food, or anything the group needs.">{{ $savedNote }}</textarea>
                                    </label>
                                </div>

                                <h2 style="margin-top:18px;">Add an activity</h2>
                                <p style="color:#475569;margin:-4px 0 12px;">Optional. Checked activities are added to this exclusive booking.</p>
                                <div class="lp-activity-picks" id="activityPicks">
                                    @forelse(($bookableActivities ?? collect()) as $activity)
                                        <label class="lp-activity-pick">
                                            <input type="checkbox" name="activity_ids[]" value="{{ $activity->id }}" data-price="{{ (float) ($activity->price ?? 0) }}">
                                            <span>
                                                <strong>{{ $activity->title }}</strong>
                                                <em>{{ $activity->schedule ?: 'By arrangement' }}</em>
                                            </span>
                                            <b>{{ $activity->price !== null ? '₱'.number_format($activity->price, 0) : 'Included' }}</b>
                                        </label>
                                    @empty
                                        <p style="color:#94a3b8;margin:0;">No bookable activities yet.</p>
                                    @endforelse
                                </div>
                                <div id="payBlock" hidden style="margin-top:22px;">
                                    <h2>Payment</h2>
                                    @include('partials.guest-payment-fields', ['chooseMethod' => true])
                                    @if($guestReady)
                                        <button type="submit" class="lp-btn lp-btn-sand" id="payBtn" style="margin-top:16px;"><i class="bi bi-wallet2"></i> Pay and request the resort</button>
                                    @else
                                        <button type="button" class="lp-btn lp-btn-sand" id="payBtn" style="margin-top:16px;"><i class="bi bi-box-arrow-in-right"></i> Log in to pay</button>
                                        <p class="lp-form-note" style="text-align:left;margin-top:10px;">Your dates and event details stay with this request. After you log in, you return here to pay.</p>
                                    @endif
                                </div>
                                <p id="payWait" style="color:#475569;margin:16px 0 0;">Fill in the occasion, the number of guests, and the event details. The payment button shows after that.</p>
                            </form>
                        @endif
                    </div>

                    <aside class="lp-ex-side">
                        <h2>Your dates</h2>
                        <div class="lp-book-field"><span>Check in</span><strong>{{ \Carbon\Carbon::parse($checkIn)->format('M j, Y') }}</strong></div>
                        <div class="lp-book-field"><span>Check out</span><strong>{{ \Carbon\Carbon::parse($checkOut)->format('M j, Y') }}</strong></div>
                        <div class="lp-book-field"><span>Nights</span><strong>{{ $nights }}</strong></div>
                        <div class="lp-book-field"><span>Total</span><strong class="lp-ex-total" id="payTotal">{{ $peso($total) }}</strong></div>
                        <a class="lp-btn lp-btn-outline" href="{{ $changeUrl }}" style="margin-top:16px;width:100%;">Change dates</a>
                    </aside>
                </div>
            @endif
        @endif
    </div>
</section>
@endsection

@if($checkIn && !$blocked && !$doneRef)
@push('scripts')
<script>
(function () {
    const form = document.getElementById('exclusiveForm');
    if (!form) return;
    const occasion = document.getElementById('occasion');
    const guests = document.getElementById('exclusiveGuests');
    const note = document.getElementById('eventNote');
    const pay = document.getElementById('payBlock');
    const wait = document.getElementById('payWait');
    const button = document.getElementById('payBtn');
    const signedIn = document.body.dataset.signedIn === '1';

    function ready() {
        const count = Number(guests.value);
        return occasion.value !== '' && count >= 1 && count <= 200 && note.value.trim().length >= 10;
    }
    const stayBase = {{ (float) $total }};
    function activityExtra() {
        let extra = 0;
        document.querySelectorAll('#activityPicks input:checked').forEach((box) => {
            extra += Number(box.dataset.price || 0);
        });
        return extra;
    }
    function paintTotal() {
        const amount = stayBase + activityExtra();
        const label = '₱' + amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const shown = document.getElementById('payTotal');
        const hidden = form.querySelector('.js-stay-total');
        if (shown) shown.textContent = label;
        if (hidden) hidden.textContent = label;
        if (typeof refreshPayQuote === 'function') refreshPayQuote(form);
    }
    function paint() {
        const open = ready();
        pay.hidden = !open;
        wait.hidden = open;
        paintTotal();
        if (open && typeof showPayPanels === 'function') showPayPanels(form);
    }
    document.getElementById('activityPicks')?.addEventListener('change', paintTotal);
    [occasion, guests, note].forEach((field) => field.addEventListener('input', paint));
    occasion.addEventListener('change', paint);

    form.addEventListener('submit', (event) => {
        if (!ready()) { event.preventDefault(); paint(); return; }
        if (typeof prepareResortPayment === 'function' && prepareResortPayment(form) === false) {
            event.preventDefault();
            return;
        }
        const details = note.value.trim().replace(/^Occasion:[^\n]*\n+/, '');
        note.value = 'Occasion: ' + occasion.value + '\n\n' + details;
    });

    if (!signedIn && button) {
        button.addEventListener('click', () => {
            if (!ready()) { paint(); return; }
            const q = new URLSearchParams({
                check_in: form.check_in_date.value,
                check_out: form.check_out_date.value,
                guests: guests.value,
                occasion: occasion.value,
                note: note.value.trim().slice(0, 500)
            });
            openLoginModal(null, '/booking/exclusive?' + q.toString());
        });
    }
    paint();
})();
</script>
@endpush
@endif
