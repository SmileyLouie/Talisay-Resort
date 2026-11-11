@extends('layouts.public')

@section('title', 'Booking – '.$resortInfo['name'])
@section('meta_description', 'Choose your dates, a room or cottage, and how to pay at '.$resortInfo['name'].'.')

@section('content')
<section class="lp-book-page">
    <div class="lp-section">
        @if(session('success'))
            <div class="lp-note lp-note-ok"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="lp-note lp-note-bad"><i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="lp-note lp-note-bad">
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @include('partials.public-smart-search', ['searchFlat' => true])

        <div class="lp-steps" id="lpSteps">
            <button type="button" class="lp-step is-on" data-goto="1">1. Select date</button>
            <button type="button" class="lp-step" data-goto="2">2. Select Room/Cottage</button>
            <button type="button" class="lp-step" data-goto="3">3. Payment</button>
            <button type="button" class="lp-step" data-goto="4">4. Confirmation</button>
        </div>

        <div class="lp-book-grid" data-panel="1">
            <aside class="lp-book-side">
                <h2>Your reservation</h2>
                <div class="lp-pinned" id="pinnedStay" hidden>
                    <span>Selected</span>
                    <strong id="pinnedName"></strong>
                    <p id="pinnedMeta"></p>
                </div>
                <div class="lp-stay-toggle" id="stayToggle">
                    <button type="button" data-stay="night" class="is-on">Per night</button>
                    @if(\App\Support\BookingRules::hourlyStaysEnabled())
                    <button type="button" data-stay="hour">Per hour</button>
                    @endif
                </div>
                <button type="button" class="lp-exclusive" id="exclusiveStart"><i class="bi bi-stars"></i> {{ $exclusiveTitle }}</button>
                <p class="lp-form-note" id="exclusiveHint" hidden>This reserves every room and cottage. Choose check-in and check-out, then check availability. That opens the full-resort page.</p>
                <div class="lp-book-field"><span>Check in</span><strong id="labelIn">Select a date</strong></div>
                <div class="lp-book-field" id="labelOutWrap"><span>Check out</span><strong id="labelOut">Select a date</strong></div>
                <div class="lp-book-field" id="hourBox" hidden>
                    <label for="hourStart">Start time</label>
                    <select id="hourStart">
                        @foreach(range(8, 20) as $hour)
                            <option value="{{ sprintf('%02d:00', $hour) }}" @selected($hour === 14)>{{ \Carbon\Carbon::createFromTime($hour)->format('g:i A') }}</option>
                        @endforeach
                    </select>
                    <label for="hourCount" style="margin-top:12px;">Hours</label>
                    <input id="hourCount" type="number" min="1" max="12" value="1">
                </div>
                <div class="lp-book-field">
                    <label for="guestCount">Guests</label>
                    <select id="guestCount">
                        @for($n = 1; $n <= 30; $n++)
                            <option value="{{ $n }}" @selected($n === 2)>{{ $n }}</option>
                        @endfor
                    </select>
                </div>
                <button type="button" class="lp-btn lp-btn-sand" id="checkStay"><i class="bi bi-calendar-check"></i> Check availability</button>
            </aside>
            <div class="lp-book-main">
                <div class="lp-cals">
                    <div>
                        <div class="lp-cal-head"><button type="button" id="calPrev" aria-label="Previous month">&lsaquo;</button><strong id="calTitleA"></strong><span></span></div>
                        <div class="lp-cal-grid" id="calA"></div>
                    </div>
                    <div>
                        <div class="lp-cal-head"><span></span><strong id="calTitleB"></strong><button type="button" id="calNext" aria-label="Next month">&rsaquo;</button></div>
                        <div class="lp-cal-grid" id="calB"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lp-book-grid" data-panel="2" hidden>
            <aside class="lp-book-side">
                <h2>Booking details</h2>
                <div class="lp-book-field"><span>Check in</span><strong id="sumIn"></strong></div>
                <div class="lp-book-field"><span>Check out</span><strong id="sumOut"></strong></div>
                <div class="lp-book-field"><span>Guests</span><strong id="sumGuests"></strong></div>
                <button type="button" class="lp-btn lp-btn-outline" id="editDates">Edit dates</button>
            </aside>
            <div class="lp-book-main">
                <div class="lp-book-filters" id="bookFilters">
                    <div class="lp-style-pick" data-kind-pick="room">
                        <button type="button" class="lp-style-trigger" aria-expanded="false" aria-haspopup="listbox">
                            <span>Rooms</span>
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        </button>
                        <div class="lp-style-menu" role="listbox" hidden>
                            <button type="button" role="option" data-kind="room" data-style="all">All rooms</button>
                            <button type="button" role="option" data-kind="room" data-style="normal">Normal</button>
                            <button type="button" role="option" data-kind="room" data-style="deluxe">Deluxe</button>
                        </div>
                    </div>
                    <div class="lp-style-pick" data-kind-pick="cottage">
                        <button type="button" class="lp-style-trigger" aria-expanded="false" aria-haspopup="listbox">
                            <span>Cottages</span>
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        </button>
                        <div class="lp-style-menu" role="listbox" hidden>
                            <button type="button" role="option" data-kind="cottage" data-style="all">All cottages</button>
                            <button type="button" role="option" data-kind="cottage" data-style="normal">Normal</button>
                            <button type="button" role="option" data-kind="cottage" data-style="deluxe">Deluxe</button>
                        </div>
                    </div>
                </div>
                <p id="stayHint" style="color:#475569;margin:0 0 8px;"></p>
                <div id="roomList"></div>
            </div>
        </div>

        <form class="lp-book-grid" data-panel="3" hidden method="POST" action="{{ route('tourist.bookings.store') }}" enctype="multipart/form-data" id="lpBookForm" onsubmit="return prepareResortPayment(this)">
            @csrf
            <input type="hidden" name="wizard" value="1">
            <input type="hidden" name="accommodation_unit_id" id="bookUnitId">
            <input type="hidden" name="booking_date" id="bookDate">
            <input type="hidden" name="check_in_date" id="bookCheckIn" disabled>
            <input type="hidden" name="check_out_date" id="bookOut">
            <input type="hidden" name="stay_type" id="bookStay" value="night">
            <input type="hidden" name="guests_count" id="bookGuests" value="2">
            <input type="hidden" name="hours_count" id="bookHours" value="1">
            <input type="hidden" name="hour_start" id="bookHourStart" value="14:00">
            <aside class="lp-book-side">
                <h2>Booking details</h2>
                <div class="lp-book-field"><span>Stay</span><strong id="payStay"></strong></div>
                <div class="lp-book-field"><span id="payRoomLabel">Room</span><strong id="payRoom"></strong></div>
                <div class="lp-book-field"><span>Guests</span><strong id="payGuests"></strong></div>
                <div class="lp-book-field"><span>Total</span><strong class="js-stay-total" id="payTotal">₱0.00</strong></div>
                <button type="button" class="lp-btn lp-btn-outline" id="editRoom">Choose another room</button>
            </aside>
            <div class="lp-book-main">
                <h2>Add an activity</h2>
                <p style="color:#475569;margin:-4px 0 12px;">Optional. Checked activities are added to this booking and to the total.</p>
                <div class="lp-activity-picks" id="activityPicks">
                    @forelse(($bookableActivities ?? collect()) as $activity)
                        <label class="lp-activity-pick">
                            <input type="checkbox" name="activity_ids[]" value="{{ $activity->id }}" data-price="{{ (float) ($activity->price ?? 0) }}">
                            <span>
                                <strong>{{ $activity->title }}</strong>
                                <em>{{ $activity->schedule ?: 'By arrangement' }}@if($activity->description) · {{ \Illuminate\Support\Str::limit($activity->description, 80) }}@endif</em>
                            </span>
                            <b>{{ $activity->price !== null ? '₱'.number_format($activity->price, 0) : 'Included' }}</b>
                        </label>
                    @empty
                        <p style="color:#94a3b8;margin:0 0 12px;">No bookable activities yet. Add them in File Maintenance.</p>
                    @endforelse
                </div>
                <h2>Payment</h2>
                @include('partials.guest-payment-fields', ['chooseMethod' => true])
                <div class="lp-book-field" style="margin-top:14px;">
                    <label for="bookNote">Special request (optional)</label>
                    <textarea id="bookNote" name="special_requests" maxlength="1000" placeholder="Late arrival, extra pillows..."></textarea>
                </div>
                @if($guestReady)
                    <button type="submit" class="lp-btn lp-btn-sand"><i class="bi bi-check2-circle"></i> Confirm reservation</button>
                @else
                    <button type="button" class="lp-btn lp-btn-sand" id="loginToBook"><i class="bi bi-box-arrow-in-right"></i> Log in to confirm</button>
                    <p class="lp-form-note" style="text-align:left;margin-top:10px;">Your dates and room stay selected. After you log in, you return here to confirm.</p>
                @endif
            </div>
        </form>

        <div data-panel="4" hidden>
            <div class="lp-confirm">
                <i class="bi bi-check-circle"></i>
                <h2>Reservation received</h2>
                <p class="lp-ref" id="doneRef">{{ $doneRef }}</p>
                <p id="doneNote" style="color:#475569;max-width:520px;margin:12px auto 22px;">The resort desk can see this booking. Keep the reference for check-in.</p>
                <a class="lp-btn lp-btn-sand" href="{{ route('tourist.bookings') }}">My bookings</a>
                <a class="lp-btn lp-btn-outline" href="{{ url('/') }}" style="margin-left:8px;">Back to home</a>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    const UNITS = @json($bookingUnits);
    const RESUME = @json($resumeUrl);
    const DONE = @json($doneRef);
    const STORE_URL = @json(route('tourist.bookings.store'));
    const EXCLUSIVE_URL = @json(route('tourist.bookings.special-resort'));
    const EXCLUSIVE_NIGHT = @json((float) ($exclusiveNightlyRate ?? 0));
    const EXCLUSIVE_TITLE = @json($exclusiveTitle ?? 'Exclusive full resort');
    const EXCLUSIVE_INFO = @json($exclusiveInformation ?? '');
    const VISITOR_CAP = @json((int) ($visitorCap ?? 100));
    const text = (value) => String(value).replace(/[&<>"']/g, (ch) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
    const params = new URLSearchParams(location.search);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const state = { cursor: new Date(today.getFullYear(), today.getMonth(), 1), inn: null, out: null, stay: 'night', unit: params.get('unit') || '', pinned: false, kind: 'room', kindPicked: false, roomStyle: 'all', cottageStyle: 'all', party: '', adults: Number(params.get('adults') || 0), children: Number(params.get('children') || 0), rooms: Math.max(1, Number(params.get('rooms') || 1)) };
    const $ = (id) => document.getElementById(id);
    const iso = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    const parse = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
    const pretty = (s) => s ? parse(s).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'Select a date';
    const peso = (n) => '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const nightsBetween = (a, b) => Math.round((parse(b) - parse(a)) / 86400000);

    function overlaps(unit, start, end) {
        return (unit.ranges || []).some((r) => r.check_in < end && r.check_out > start);
    }
    function lockedUnit() {
        if (!state.pinned || !state.unit) return null;
        return UNITS.find((unit) => String(unit.id) === String(state.unit)) || null;
    }
    function paintPinned() {
        const unit = lockedUnit();
        const box = $('pinnedStay');
        const select = $('guestCount');
        if (!unit) {
            box.hidden = true;
            Array.from(select.options).forEach((opt) => { opt.hidden = false; opt.disabled = false; });
            $('checkStay').innerHTML = '<i class="bi bi-calendar-check"></i> Check availability';
            return;
        }
        box.hidden = false;
        $('pinnedName').textContent = unit.name;
        $('pinnedMeta').textContent = unit.variant + ' ' + unit.type + ' · up to ' + unit.occupancy + ' guests. Choose your dates, then payment.';
        Array.from(select.options).forEach((opt) => {
            const over = Number(opt.value) > unit.occupancy;
            opt.hidden = over;
            opt.disabled = over;
        });
        if (Number(select.value) > unit.occupancy) select.value = String(unit.occupancy);
        $('checkStay').innerHTML = '<i class="bi bi-calendar-check"></i> Continue to payment';
    }
    function nightClosed(date) {
        const start = iso(date);
        const endDate = new Date(date); endDate.setDate(endDate.getDate() + 1);
        const end = iso(endDate);
        const pinned = lockedUnit();
        if (pinned) return overlaps(pinned, start, end);
        return UNITS.length > 0 && UNITS.every((unit) => overlaps(unit, start, end));
    }
    function show(step) {
        document.querySelectorAll('[data-panel]').forEach((panel) => { panel.hidden = String(panel.dataset.panel) !== String(step); });
        document.querySelectorAll('.lp-step').forEach((btn) => {
            const n = Number(btn.dataset.goto);
            btn.classList.toggle('is-on', n === step);
            btn.classList.toggle('is-done', n < step);
        });
        paintModes();
    }
    function paintModes() {
        const exclusive = state.kind === 'exclusive';
        $('exclusiveStart').classList.toggle('is-on', exclusive);
        $('exclusiveHint').hidden = !exclusive;
        $('stayToggle').querySelectorAll('button').forEach((item) => {
            item.classList.toggle('is-on', !exclusive && item.dataset.stay === state.stay);
        });
    }
    function paint() {
        $('labelIn').textContent = pretty(state.inn);
        $('labelOut').textContent = state.stay === 'hour' ? 'Same day' : pretty(state.out);
        draw($('calA'), $('calTitleA'), state.cursor);
        draw($('calB'), $('calTitleB'), new Date(state.cursor.getFullYear(), state.cursor.getMonth() + 1, 1));
    }
    function draw(grid, title, month) {
        title.textContent = month.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        const names = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
        grid.innerHTML = names.map((n) => '<div class="lp-cal-dow">' + n + '</div>').join('');
        const first = new Date(month.getFullYear(), month.getMonth(), 1);
        const days = new Date(month.getFullYear(), month.getMonth() + 1, 0).getDate();
        for (let i = 0; i < first.getDay(); i++) grid.insertAdjacentHTML('beforeend', '<span></span>');
        for (let day = 1; day <= days; day++) {
            const date = new Date(month.getFullYear(), month.getMonth(), day);
            const key = iso(date);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'lp-day';
            btn.textContent = String(day);
            const past = date < today;
            const closed = nightClosed(date);
            if (past || closed) btn.classList.add(past ? 'is-past' : 'is-closed');
            if (state.inn && key === state.inn) btn.classList.add('is-start');
            if (state.stay === 'night' && state.out && key === state.out) btn.classList.add('is-end');
            if (state.stay === 'night' && state.inn && state.out && key > state.inn && key < state.out) btn.classList.add('is-in');
            if (!past && !closed) btn.addEventListener('click', () => pick(key));
            grid.appendChild(btn);
        }
    }
    function pick(key) {
        if (state.stay === 'hour') { state.inn = key; state.out = null; }
        else if (!state.inn || (state.inn && state.out) || key <= state.inn) { state.inn = key; state.out = null; }
        else state.out = key;
        paint();
    }
    function catalogUnits() {
        const guests = Number($('guestCount').value || 1);
        const needRooms = Math.max(1, Number(state.rooms || 1));
        const needPerUnit = needRooms > 1 ? Math.max(1, Math.ceil(guests / needRooms)) : guests;
        const hours = Math.max(1, Math.min(12, Number($('hourCount').value || 1)));
        let start = null;
        let end = null;
        if (state.stay === 'hour' && state.inn) {
            start = state.inn;
            end = iso(new Date(parse(state.inn).getTime() + 86400000));
        } else if (state.inn && state.out) {
            start = state.inn;
            end = state.out;
        }
        return UNITS.filter((unit) => unit.occupancy >= needPerUnit).map((unit) => {
            const total = state.stay === 'hour' ? unit.hour * hours : (start && end ? unit.night * nightsBetween(start, end) : Number(unit.night || 0));
            const open = !!(start && end) && !overlaps(unit, start, end);
            return Object.assign({}, unit, { total, open });
        });
    }
    function availableUnits() {
        return catalogUnits().filter((unit) => unit.open).sort((a, b) => (String(a.id) === String(state.unit) ? -1 : 0) - (String(b.id) === String(state.unit) ? -1 : 0));
    }
    const isDeluxe = (unit) => unit.deluxe === true || /deluxe/i.test(unit.variant || '');
    function resortQuote() {
        const nights = state.stay === 'night' && state.inn && state.out ? nightsBetween(state.inn, state.out) : 0;
        const summed = UNITS.reduce((sum, unit) => sum + Number(unit.night || 0), 0);
        const nightSum = EXCLUSIVE_NIGHT > 0 ? EXCLUSIVE_NIGHT : summed;
        const capacity = UNITS.reduce((sum, unit) => sum + Number(unit.occupancy || 0), 0);
        const blocked = nights > 0 && UNITS.some((unit) => overlaps(unit, state.inn, state.out));
        return { nights, total: nightSum * nights, capacity, blocked, units: UNITS.length };
    }
    function partySize() {
        const raw = state.party || ($('exclusiveGuests') ? $('exclusiveGuests').value : '') || $('guestCount').value || 2;
        return Math.max(1, Math.min(VISITOR_CAP, Number(raw) || 1));
    }
    function renderExclusive() {
        const quote = resortQuote();
        $('roomList').innerHTML = '';
        if (state.stay === 'hour') {
            $('stayHint').textContent = 'An exclusive booking covers the whole resort overnight. Switch to Per night and choose your dates.';
            $('roomList').innerHTML = '<p class="lp-room-empty">Exclusive full-resort booking is available by the night only.</p>';
            return;
        }
        if (quote.blocked || !quote.units) {
            $('stayHint').textContent = 'The whole resort is free only when every room and cottage is open.';
            $('roomList').innerHTML = '<p class="lp-room-empty">Another reservation already uses one of these dates, so the full resort cannot be booked then. Try different dates.</p>';
            return;
        }
        $('stayHint').textContent = 'This reserves every room and cottage for your group. Management confirms the request before the dates are locked in.';
        const row = document.createElement('article');
        row.className = 'lp-room-row';
        row.innerHTML = '<div class="lp-room-ph"><i class="bi bi-stars"></i></div>'
            + '<div><h3>' + text(EXCLUSIVE_TITLE) + '</h3>'
            + '<p>' + text(EXCLUSIVE_INFO || ('All ' + quote.units + ' rooms and cottages, private for your stay.')) + '</p>'
            + '<p class="lp-exclusive-note">Up to ' + VISITOR_CAP + ' guests, the resort’s daily visitor cap.</p>'
            + '<label class="lp-exclusive-guests">Guests <input type="number" id="exclusiveGuests" min="1" max="' + VISITOR_CAP + '" value="' + partySize() + '"></label>'
            + '<p class="lp-price">' + peso(quote.total) + ' · ' + quote.nights + (quote.nights === 1 ? ' night' : ' nights') + '</p></div>';
        row.querySelector('#exclusiveGuests').addEventListener('input', (event) => {
            state.party = event.target.value;
            $('sumGuests').textContent = String(partySize());
        });
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'lp-btn lp-btn-navy';
        button.textContent = 'Select';
        button.addEventListener('click', () => {
            state.party = $('exclusiveGuests').value;
            if (document.body.dataset.signedIn === '1') { openExclusivePay(); return; }
            openLoginModal(null, bookingReturn('exclusive'));
        });
        row.appendChild(button);
        $('roomList').appendChild(row);
    }
    function renderRooms() {
        const catalog = catalogUnits();
        const all = catalog.filter((unit) => unit.open);
        const rooms = all.filter((unit) => unit.kind !== 'cottage');
        const cottages = all.filter((unit) => unit.kind === 'cottage');
        const mixed = state.kind === 'all';
        const kindPool = mixed
            ? catalog
            : catalog.filter((unit) => state.kind === 'cottage' ? unit.kind === 'cottage' : unit.kind !== 'cottage');
        const kindList = state.kind === 'cottage' ? cottages : (mixed ? all : rooms);
        const style = state.kind === 'cottage' ? state.cottageStyle : state.roomStyle;
        const party = Number($('guestCount').value || 1);
        const needRooms = Math.max(1, Number(state.rooms || 1));
        const perRoom = Math.max(1, Math.ceil(party / needRooms));
        const fitScore = (unit) => {
            const cap = Number(unit.occupancy || 0);
            const spareWhole = cap - party;
            const spareSplit = cap - perRoom;
            if (needRooms > 1) {
                if (spareSplit < 0) return 9000;
                return spareSplit * 10 + Number(unit.total || unit.night || 0) / 1000;
            }
            if (spareWhole < 0) return 9000;
            return spareWhole * 10 + Number(unit.total || unit.night || 0) / 1000;
        };
        const shown = kindPool
            .filter((unit) => mixed || style === 'all' || (style === 'deluxe' ? isDeluxe(unit) : !isDeluxe(unit)))
            .sort((a, b) => Number(b.open) - Number(a.open) || fitScore(a) - fitScore(b) || String(a.name).localeCompare(String(b.name)));
        const list = shown.filter((unit) => unit.open);
        const bestId = list.length ? String(list[0].id) : '';
        if (state.kind === 'exclusive') {
            $('bookFilters').hidden = true;
            renderExclusive();
            return;
        }
        $('bookFilters').hidden = mixed;
        if (!mixed) {
            document.querySelectorAll('#bookFilters .lp-style-pick').forEach((pick) => {
                const kind = pick.dataset.kindPick;
                const active = state.kind === kind;
                const pool = kind === 'cottage' ? cottages : rooms;
                const chosen = kind === 'cottage' ? state.cottageStyle : state.roomStyle;
                const countFor = (choice) => pool.filter((unit) => choice === 'deluxe' ? isDeluxe(unit) : (choice === 'normal' ? !isDeluxe(unit) : true)).length;
                const group = kind === 'cottage' ? 'cottages' : 'rooms';
                const labelFor = (choice, count) => (choice === 'all' ? 'All ' + group : (choice === 'deluxe' ? 'Deluxe' : 'Normal')) + ' (' + count + ')';
                const buttonLabel = chosen === 'all'
                    ? labelFor(chosen, countFor(chosen))
                    : ((kind === 'cottage' ? 'Cottages' : 'Rooms') + ' · ' + (chosen === 'deluxe' ? 'Deluxe' : 'Normal') + ' (' + countFor(chosen) + ')');
                pick.querySelector('.lp-style-trigger').classList.toggle('is-on', active);
                pick.querySelector('span').textContent = buttonLabel;
                pick.querySelectorAll('[data-style]').forEach((btn) => {
                    btn.classList.toggle('is-on', active && btn.dataset.style === chosen);
                    btn.textContent = labelFor(btn.dataset.style, countFor(btn.dataset.style));
                });
            });
        }
        const kindLabel = state.kind === 'cottage' ? 'cottages' : (mixed ? 'rooms and cottages' : 'rooms');
        const variantLabel = style === 'deluxe' ? 'deluxe' : 'normal';
        const partyBits = [];
        if (state.adults > 0) partyBits.push(state.adults + (state.adults === 1 ? ' adult' : ' adults'));
        if (state.children > 0) partyBits.push(state.children + (state.children === 1 ? ' child' : ' children'));
        const partyText = partyBits.length ? partyBits.join(', ') : (party + (party === 1 ? ' guest' : ' guests'));
        const roomsText = needRooms > 1 ? (' across ' + needRooms + ' rooms') : '';
        $('stayHint').textContent = list.length
            ? ('Found ' + list.length + ' open ' + kindLabel + ' for ' + partyText + roomsText + '. The top result is the closest fit' + (needRooms > 1 ? ' for about ' + perRoom + ' guests per room' : '') + '.')
            : (mixed || style === 'all'
                ? (all.length && !mixed ? 'No ' + kindLabel + ' are free for those dates. Try the other button.' : 'Nothing is free for those dates. Try another day.')
                : (kindList.length ? 'No ' + variantLabel + ' ' + kindLabel + ' are free for those dates. Try another choice.' : (all.length ? 'No ' + kindLabel + ' are free for those dates. Try the other button.' : 'Nothing is free for those dates. Try another day.')));
        $('roomList').innerHTML = '';
        if (!shown.length) {
            $('roomList').innerHTML = '<p class="lp-room-empty">' + (style === 'all' || !kindList.length ? 'Nothing in this group is free for those dates.' : 'Choose All ' + kindLabel + ' or the other style to see what is still open.') + '</p>';
            return;
        }
        shown.forEach((unit) => {
            const row = document.createElement('article');
            row.className = 'lp-room-row' + (unit.open ? '' : ' is-unavailable') + (unit.open && String(unit.id) === bestId ? ' is-best' : '');
            row.innerHTML = (unit.image ? '<img src="' + unit.image + '" alt="">' : '<div class="lp-room-ph"><i class="bi bi-image"></i></div>')
                + '<div>'
                + (unit.open && String(unit.id) === bestId ? '<span class="lp-best">Best match</span>' : '')
                + '<h3>' + unit.name + '</h3><p>' + unit.variant + ' ' + unit.type + '</p>'
                + '<p><i class="bi bi-people"></i> Up to ' + unit.occupancy + ' guests</p>'
                + (unit.beds ? '<p><i class="bi bi-moon"></i> ' + unit.beds + '</p>' : '')
                + '<p class="lp-price">' + peso(unit.total) + (state.stay === 'hour' ? ' · hourly' : '') + '</p></div>';
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'lp-btn lp-btn-navy';
            if (unit.open) {
                button.textContent = 'Select';
                button.addEventListener('click', () => {
                    if (document.body.dataset.signedIn === '1') { openPay(unit); return; }
                    openLoginModal(null, bookingReturn(unit.id));
                });
            } else {
                button.textContent = 'Unavailable';
                button.disabled = true;
            }
            row.appendChild(button);
            $('roomList').appendChild(row);
        });
    }
    function exclusivePageUrl() {
        const guests = Math.max(1, Math.min(VISITOR_CAP, Number(state.party || $('guestCount').value || 2)));
        const q = new URLSearchParams({ check_in: state.inn || '', check_out: state.out || '', guests: String(guests) });
        return '/booking/exclusive?' + q.toString();
    }
    function goExclusive() {
        if (state.stay === 'hour') { alert('An exclusive booking is by the night. Choose Per night, then pick check-in and check-out.'); return; }
        if (!state.inn || !state.out) { alert('Select your check-in and check-out dates on the calendar.'); return; }
        location.href = exclusivePageUrl();
    }
    function continueFromDates() {
        if (state.kind === 'exclusive') { goExclusive(); return; }
        if (!state.inn || (state.stay === 'night' && !state.out)) { alert('Select your check-in and check-out dates on the calendar.'); return; }
        const pinned = lockedUnit();
        if (!pinned) { openRooms(); return; }
        const match = catalogUnits().find((unit) => String(unit.id) === String(pinned.id));
        if (!match) {
            alert(pinned.name + ' fits up to ' + pinned.occupancy + ' guests. Lower the guest count, or choose another room.');
            return;
        }
        if (!match.open) {
            alert(pinned.name + ' is already booked for those dates. Pick different dates, or choose another room.');
            return;
        }
        state.kind = pinned.kind === 'cottage' ? 'cottage' : 'room';
        state.kindPicked = true;
        if (document.body.dataset.signedIn === '1') { openPay(match); return; }
        openLoginModal(null, bookingReturn(match.id));
    }
    function openRooms() {
        if (state.kind === 'exclusive') { goExclusive(); return; }
        if (!state.inn || (state.stay === 'night' && !state.out)) { alert('Select your check-in and check-out dates on the calendar.'); return; }
        const hours = Math.max(1, Math.min(12, Number($('hourCount').value || 1)));
        if (!state.kindPicked) {
            const all = availableUnits();
            const preferred = all.find((unit) => String(unit.id) === String(state.unit));
            if (preferred) state.kind = preferred.kind === 'cottage' ? 'cottage' : 'room';
            else if (params.get('find') === '1' || params.get('kind') === 'all') state.kind = 'all';
            else if (!all.some((unit) => unit.kind !== 'cottage') && all.some((unit) => unit.kind === 'cottage')) state.kind = 'cottage';
            else state.kind = 'room';
        }
        $('sumIn').textContent = pretty(state.inn);
        $('sumOut').textContent = state.stay === 'hour' ? ($('hourStart').selectedOptions[0].textContent + ' · ' + hours + (hours === 1 ? ' hour' : ' hours')) : pretty(state.out);
        if (state.kind === 'exclusive') {
            $('sumGuests').textContent = String(partySize());
        } else if (state.adults || state.children) {
            const bits = [state.adults + (state.adults === 1 ? ' adult' : ' adults')];
            if (state.children > 0) bits.push(state.children + (state.children === 1 ? ' child' : ' children'));
            bits.push(state.rooms + (state.rooms === 1 ? ' room' : ' rooms'));
            $('sumGuests').textContent = bits.join(', ');
        } else {
            $('sumGuests').textContent = $('guestCount').value;
        }
        renderRooms();
        show(2);
    }
    function useExclusiveForm(on) {
        const form = $('lpBookForm');
        form.action = on ? EXCLUSIVE_URL : STORE_URL;
        ['bookUnitId', 'bookDate', 'bookStay', 'bookHours', 'bookHourStart'].forEach((id) => { $(id).disabled = on; });
        $('bookCheckIn').disabled = !on;
        $('payRoomLabel').textContent = on ? 'Resort' : 'Room';
        $('editRoom').textContent = on ? 'Change this booking' : 'Choose another room';
        const note = $('bookNote');
        if (note) note.placeholder = on ? 'Wedding, reunion, or other plans for the whole resort.' : 'Late arrival, extra pillows...';
    }
    function refreshActivityTotal() {
        const total = $('payTotal');
        if (!total) return;
        const base = Number(total.dataset.base || 0);
        let extra = 0;
        document.querySelectorAll('#activityPicks input:checked').forEach((box) => {
            extra += Number(box.dataset.price || 0);
        });
        total.textContent = peso(base + extra);
        if (typeof refreshPayQuote === 'function') refreshPayQuote($('lpBookForm'));
    }
    document.getElementById('activityPicks')?.addEventListener('change', refreshActivityTotal);
    const presetActivity = new URLSearchParams(window.location.search).get('activity');
    if (presetActivity) {
        const preset = document.querySelector('#activityPicks input[value="' + presetActivity + '"]');
        if (preset) preset.checked = true;
    }
    function openPay(unit) {
        const hours = Math.max(1, Math.min(12, Number($('hourCount').value || 1)));
        useExclusiveForm(false);
        state.unit = String(unit.id);
        $('bookUnitId').value = unit.id;
        $('bookDate').value = state.inn;
        $('bookOut').value = state.stay === 'night' ? state.out : '';
        $('bookStay').value = state.stay;
        $('bookGuests').value = $('guestCount').value;
        $('bookHours').value = hours;
        $('bookHourStart').value = $('hourStart').value;
        $('payStay').textContent = pretty(state.inn) + (state.stay === 'night' ? ' – ' + pretty(state.out) : ' · ' + hours + 'h');
        $('payRoom').textContent = unit.name + ' · ' + unit.variant + ' ' + unit.type;
        $('payGuests').textContent = $('guestCount').value;
        $('payTotal').textContent = peso(unit.total);
        $('payTotal').dataset.base = String(unit.total);
        refreshActivityTotal();
        show(3);
        if (typeof showPayPanels === 'function') showPayPanels($('lpBookForm'));
    }
    function openExclusivePay() {
        const quote = resortQuote();
        if (state.stay !== 'night' || !state.out || quote.blocked || !quote.units) return;
        const guests = partySize();
        state.party = String(guests);
        useExclusiveForm(true);
        $('bookCheckIn').value = state.inn;
        $('bookOut').value = state.out;
        $('bookGuests').value = guests;
        $('payStay').textContent = pretty(state.inn) + ' – ' + pretty(state.out);
        $('payRoom').textContent = 'All rooms and cottages';
        $('payGuests').textContent = String(guests);
        $('payTotal').textContent = peso(quote.total);
        $('payTotal').dataset.base = String(quote.total);
        refreshActivityTotal();
        show(3);
        if (typeof showPayPanels === 'function') showPayPanels($('lpBookForm'));
    }
    $('stayToggle').addEventListener('click', (event) => {
        const button = event.target.closest('[data-stay]');
        if (!button) return;
        state.stay = button.dataset.stay;
        state.out = null;
        if (state.kind === 'exclusive') {
            state.kind = state.pinned && lockedUnit() ? (lockedUnit().kind === 'cottage' ? 'cottage' : 'room') : 'room';
            state.kindPicked = state.pinned;
        }
        paintModes();
        $('hourBox').hidden = state.stay !== 'hour';
        $('labelOutWrap').hidden = state.stay === 'hour';
        paint();
    });
    $('calPrev').addEventListener('click', () => { state.cursor = new Date(state.cursor.getFullYear(), state.cursor.getMonth() - 1, 1); paint(); });
    $('calNext').addEventListener('click', () => { state.cursor = new Date(state.cursor.getFullYear(), state.cursor.getMonth() + 1, 1); paint(); });
    function closeBookMenus() {
        document.querySelectorAll('#bookFilters .lp-style-menu').forEach((menu) => { menu.hidden = true; });
        document.querySelectorAll('#bookFilters .lp-style-trigger').forEach((trigger) => trigger.setAttribute('aria-expanded', 'false'));
    }
    document.querySelectorAll('#bookFilters .lp-style-pick').forEach((pick) => {
        const trigger = pick.querySelector('.lp-style-trigger');
        const menu = pick.querySelector('.lp-style-menu');
        trigger.addEventListener('click', (event) => {
            event.stopPropagation();
            const open = menu.hidden;
            closeBookMenus();
            if (open) {
                menu.hidden = false;
                trigger.setAttribute('aria-expanded', 'true');
            }
        });
    });
    document.addEventListener('click', (event) => {
        if (!event.target.closest('#bookFilters')) closeBookMenus();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeBookMenus();
    });
    document.querySelectorAll('#bookFilters [data-style]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            if (!state.inn) return;
            const kind = button.dataset.kind;
            const style = button.dataset.style;
            if (kind === 'cottage') state.cottageStyle = style;
            else state.roomStyle = style;
            state.kind = kind;
            state.kindPicked = true;
            closeBookMenus();
            renderRooms();
        });
    });
    $('exclusiveStart').addEventListener('click', () => {
        state.kind = 'exclusive';
        state.kindPicked = true;
        state.pinned = false;
        paintPinned();
        if (state.stay === 'hour') {
            state.stay = 'night';
            $('hourBox').hidden = true;
            $('labelOutWrap').hidden = false;
            paint();
        }
        paintModes();
        if (state.inn && state.out) openRooms();
    });
    $('checkStay').addEventListener('click', continueFromDates);
    $('editDates').addEventListener('click', () => show(1));
    $('editRoom').addEventListener('click', () => {
        state.pinned = false;
        paintPinned();
        openRooms();
    });
    function bookingReturn(unitId) {
        const exclusive = state.kind === 'exclusive' || unitId === 'exclusive';
        const q = new URLSearchParams({
            check_in: state.inn || '', check_out: state.out || '', guests: exclusive ? String(partySize()) : $('guestCount').value,
            unit: exclusive ? '' : (unitId || state.unit || ''), stay: state.stay, hours: $('hourCount').value, hour_start: $('hourStart').value, step: 'pay'
        });
        if (exclusive) q.set('exclusive', '1');
        return '/booking?' + q.toString();
    }
    const login = $('loginToBook');
    if (login) login.addEventListener('click', () => openLoginModal(null, bookingReturn(state.kind === 'exclusive' ? 'exclusive' : state.unit)));
    document.querySelectorAll('.lp-step').forEach((btn) => btn.addEventListener('click', () => {
        if (!btn.classList.contains('is-done')) return;
        const step = Number(btn.dataset.goto);
        if (step === 2 && state.pinned) {
            state.pinned = false;
            paintPinned();
            if (state.inn && (state.stay === 'hour' || state.out)) { openRooms(); return; }
        }
        show(step);
    }));

    if (params.get('check_in')) state.inn = params.get('check_in');
    if (params.get('check_out')) state.out = params.get('check_out');
    if (params.get('adults') || params.get('children')) {
        state.adults = Math.max(1, Number(params.get('adults') || 1));
        state.children = Math.max(0, Number(params.get('children') || 0));
        $('guestCount').value = String(Math.max(1, state.adults + state.children));
    } else if (params.get('guests')) {
        $('guestCount').value = params.get('guests');
        state.adults = Math.max(1, Number(params.get('guests') || 2));
        state.children = 0;
    }
    if (params.get('rooms')) state.rooms = Math.max(1, Number(params.get('rooms') || 1));
    if (params.get('kind') === 'exclusive' || params.get('exclusive') === '1') {
        state.kind = 'exclusive';
        state.kindPicked = true;
        state.party = params.get('guests') || '';
    } else if (params.get('kind') === 'room' || params.get('kind') === 'cottage') {
        state.kind = params.get('kind');
        state.kindPicked = true;
    } else if (params.get('unit') && UNITS.some((unit) => String(unit.id) === String(params.get('unit')))) {
        state.pinned = true;
        const chosen = UNITS.find((unit) => String(unit.id) === String(params.get('unit')));
        state.kind = chosen.kind === 'cottage' ? 'cottage' : 'room';
        state.kindPicked = true;
    } else if (params.get('find') === '1' || params.get('kind') === 'all') {
        state.kind = 'all';
        state.kindPicked = true;
    }
    paintPinned();
    if (params.get('stay') === 'hour') $('stayToggle').querySelector('[data-stay="hour"]')?.click();
    if (params.get('hours')) $('hourCount').value = params.get('hours');
    if (params.get('hour_start')) $('hourStart').value = params.get('hour_start');
    @if(old('booking_date'))
        state.inn = @json(old('booking_date'));
        state.out = @json(old('check_out_date'));
        state.unit = @json((string) old('accommodation_unit_id'));
    @endif
    @if(old('check_in_date') && !old('booking_date'))
        state.inn = @json(old('check_in_date'));
        state.out = @json(old('check_out_date'));
        state.kind = 'exclusive';
        state.kindPicked = true;
        state.party = @json((string) old('guests_count'));
    @endif
    if (state.inn) state.cursor = new Date(parse(state.inn).getFullYear(), parse(state.inn).getMonth(), 1);
    paint();
    paintModes();
    if (!DONE && params.get('exclusive') === '1' && params.get('edit') !== '1' && state.inn && state.out && state.stay !== 'hour') {
        location.replace(exclusivePageUrl());
        return;
    }
    if (DONE) {
        $('doneRef').textContent = DONE;
        if (String(DONE).indexOf('TBRS') === 0) {
            $('doneNote').textContent = 'Management will review this exclusive request before the whole resort is reserved for your dates. Keep the reference.';
        }
        show(4);
    }
    else if (params.get('edit') === '1') { /* stay on the calendar so the dates can be changed */ }
    else if (state.inn && (state.stay === 'hour' || state.out)) {
        if (state.pinned) {
            continueFromDates();
        } else {
            openRooms();
            const resumePay = params.get('step') === 'pay' || @json((bool) old('check_in_date'));
            if (resumePay && state.kind === 'exclusive' && state.stay !== 'hour') openExclusivePay();
            else if (params.get('step') === 'pay' && state.unit) {
                const chosen = availableUnits().find((unit) => String(unit.id) === String(state.unit));
                if (chosen) openPay(chosen);
            }
        }
    }
})();
</script>
@endpush
