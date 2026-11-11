@php
    $searchAction = $searchAction ?? route('public.booking');
    $searchFlat = $searchFlat ?? false;
@endphp
<div class="lp-search-wrap {{ $searchFlat ? 'is-flat' : '' }}" id="lpSmartSearch">
    <form class="lp-search" method="GET" action="{{ $searchAction }}" id="lpSmartSearchForm" autocomplete="off">
        <button type="button" class="lp-search-field" data-open="place" aria-expanded="false">
            <i class="bi bi-search" aria-hidden="true"></i>
            <span>
                <em>Stay type</em>
                <strong id="lpSearchPlaceLabel">Rooms &amp; cottages</strong>
            </span>
            <input type="hidden" name="kind" id="lpSearchKind" value="all">
        </button>
        <button type="button" class="lp-search-field" data-open="dates" data-date="in" aria-expanded="false">
            <i class="bi bi-calendar3" aria-hidden="true"></i>
            <span>
                <em>Check-in</em>
                <strong id="lpSearchInLabel">Add date</strong>
            </span>
        </button>
        <button type="button" class="lp-search-field" data-open="dates" data-date="out" aria-expanded="false">
            <i class="bi bi-calendar3" aria-hidden="true"></i>
            <span>
                <em>Check-out</em>
                <strong id="lpSearchOutLabel">Add date</strong>
            </span>
        </button>
        <button type="button" class="lp-search-field" data-open="guests" aria-expanded="false">
            <i class="bi bi-people" aria-hidden="true"></i>
            <span>
                <em>Guests &amp; rooms</em>
                <strong id="lpSearchGuestsLabel">2 adults, 1 room</strong>
            </span>
            <i class="bi bi-chevron-down lp-search-chevron" aria-hidden="true"></i>
        </button>
        <input type="hidden" name="check_in" id="lpSearchIn" value="">
        <input type="hidden" name="check_out" id="lpSearchOut" value="">
        <input type="hidden" name="guests" id="lpSearchGuests" value="2">
        <input type="hidden" name="adults" id="lpSearchAdults" value="2">
        <input type="hidden" name="children" id="lpSearchChildren" value="0">
        <input type="hidden" name="rooms" id="lpSearchRooms" value="1">
        <input type="hidden" name="find" value="1">
        <button type="submit" class="lp-search-go">SEARCH</button>
    </form>

    <div class="lp-search-panel" data-panel="place" hidden>
        <button type="button" class="lp-search-choice is-on" data-kind="all">
            <strong>Rooms &amp; cottages</strong>
            <span>Show every place that fits your dates and guests</span>
        </button>
        <button type="button" class="lp-search-choice" data-kind="room">
            <strong>Rooms only</strong>
            <span>Indoor rooms by the shore</span>
        </button>
        <button type="button" class="lp-search-choice" data-kind="cottage">
            <strong>Cottages only</strong>
            <span>Open-air cottages on the beachfront</span>
        </button>
        <button type="button" class="lp-search-choice" data-kind="exclusive">
            <strong>Exclusive full resort</strong>
            <span>Private stay for one group across every room and cottage</span>
        </button>
    </div>

    <div class="lp-search-panel lp-search-dates" data-panel="dates" hidden>
        <div class="lp-search-tabs">
            <button type="button" class="is-on" data-tab="calendar">Calendar</button>
            <button type="button" data-tab="flex">I'm flexible</button>
        </div>
        <div class="lp-search-tabpane is-on" data-pane="calendar">
            <div class="lp-search-cals">
                <div>
                    <div class="lp-search-cal-head">
                        <button type="button" id="lpSearchPrev" aria-label="Previous month"><i class="bi bi-chevron-left"></i></button>
                        <strong id="lpSearchTitleA"></strong>
                        <span></span>
                    </div>
                    <div class="lp-search-cal-grid" id="lpSearchCalA"></div>
                </div>
                <div>
                    <div class="lp-search-cal-head">
                        <span></span>
                        <strong id="lpSearchTitleB"></strong>
                        <button type="button" id="lpSearchNext" aria-label="Next month"><i class="bi bi-chevron-right"></i></button>
                    </div>
                    <div class="lp-search-cal-grid" id="lpSearchCalB"></div>
                </div>
            </div>
        </div>
        <div class="lp-search-tabpane" data-pane="flex" hidden>
            <p class="lp-search-flex-label">How long do you want to stay?</p>
            <div class="lp-search-pills" id="lpSearchNights">
                <button type="button" data-nights="1">1 night</button>
                <button type="button" data-nights="2" class="is-on">2 nights</button>
                <button type="button" data-nights="3">3 nights</button>
                <button type="button" data-nights="7">1 week</button>
            </div>
            <p class="lp-search-flex-label">When do you want to travel?</p>
            <div class="lp-search-months" id="lpSearchMonths"></div>
            <div class="lp-search-flex-actions">
                <button type="button" id="lpSearchClearFlex">Clear</button>
                <button type="button" class="lp-btn lp-btn-navy" id="lpSearchApplyFlex">Select</button>
            </div>
        </div>
    </div>

    <div class="lp-search-panel lp-search-guests" data-panel="guests" hidden>
        <div class="lp-search-step" data-count="rooms">
            <div>
                <strong>Room</strong>
            </div>
            <div class="lp-search-counter">
                <button type="button" data-minus aria-label="Fewer rooms">−</button>
                <em data-value>1</em>
                <button type="button" data-plus aria-label="More rooms">+</button>
            </div>
        </div>
        <div class="lp-search-step" data-count="adults">
            <div>
                <strong>Adults</strong>
                <span>Ages 18 or above</span>
            </div>
            <div class="lp-search-counter">
                <button type="button" data-minus aria-label="Fewer adults">−</button>
                <em data-value>2</em>
                <button type="button" data-plus aria-label="More adults">+</button>
            </div>
        </div>
        <div class="lp-search-step" data-count="children">
            <div>
                <strong>Children</strong>
                <span>Ages 0–17</span>
            </div>
            <div class="lp-search-counter">
                <button type="button" data-minus aria-label="Fewer children">−</button>
                <em data-value>0</em>
                <button type="button" data-plus aria-label="More children">+</button>
            </div>
        </div>
        <p class="lp-search-note">Adults and children are counted together for occupancy. Each booking reserves one room or cottage; raise Rooms to prefer larger units that can cover that party size, then book extra units separately if you still need more space.</p>
    </div>
</div>

@once
@push('styles')
<style>
.lp-search-wrap { position: relative; z-index: 6; max-width: 1100px; margin: -58px auto 0; padding: 0 20px 28px; }
.lp-search-wrap.is-flat { margin: 0 auto 22px; padding: 0; }
.lp-search {
    display: grid;
    grid-template-columns: 1.35fr 1fr 1fr 1fr auto;
    align-items: stretch;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 18px 50px rgba(8, 47, 73, .22);
    overflow: visible;
    border: 1px solid rgba(15, 23, 42, .06);
}
.lp-search-field {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    min-height: 78px;
    padding: 14px 18px;
    border: 0;
    border-right: 1px solid #e8eef3;
    background: #fff;
    text-align: left;
    cursor: pointer;
    font: inherit;
    color: inherit;
}
.lp-search-field:first-child { border-radius: 18px 0 0 18px; }
.lp-search-field.is-open,
.lp-search-field:hover { background: #f8fafc; }
.lp-search-field > i:first-child { color: #0284c7; font-size: 18px; }
.lp-search-field em {
    display: block;
    font-style: normal;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 2px;
}
.lp-search-field strong {
    display: block;
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 180px;
}
.lp-search-chevron { margin-left: auto; color: #94a3b8; font-size: 12px; }
.lp-search-go {
    border: 0;
    background: #0284c7;
    color: #fff;
    font: inherit;
    font-size: 14px;
    font-weight: 800;
    letter-spacing: .08em;
    padding: 0 28px;
    cursor: pointer;
    border-radius: 0 18px 18px 0;
}
.lp-search-go:hover { background: #0369a1; }
.lp-search-panel {
    position: absolute;
    left: 20px;
    right: 20px;
    top: calc(100% - 18px);
    background: #fff;
    border-radius: 18px;
    border: 1px solid #d7e6f0;
    box-shadow: 0 24px 50px rgba(8, 47, 73, .18);
    padding: 16px;
    z-index: 20;
}
.lp-search-panel[hidden] { display: none !important; }
.lp-search-choice {
    display: block;
    width: 100%;
    text-align: left;
    border: 1px solid transparent;
    background: #fff;
    border-radius: 14px;
    padding: 12px 14px;
    cursor: pointer;
    font: inherit;
}
.lp-search-choice + .lp-search-choice { margin-top: 6px; }
.lp-search-choice strong { display: block; color: #0f172a; font-size: 15px; }
.lp-search-choice span { display: block; color: #64748b; font-size: 12px; margin-top: 2px; }
.lp-search-choice:hover,
.lp-search-choice.is-on { background: #f0f9ff; border-color: #bae6fd; }
.lp-search-tabs { display: flex; gap: 18px; border-bottom: 1px solid #e2e8f0; margin-bottom: 14px; }
.lp-search-tabs button {
    border: 0; background: none; font: inherit; font-weight: 700; color: #64748b;
    padding: 0 0 10px; cursor: pointer; border-bottom: 2px solid transparent;
}
.lp-search-tabs button.is-on { color: #0284c7; border-bottom-color: #0284c7; }
.lp-search-cals { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
.lp-search-cal-head {
    display: grid; grid-template-columns: 36px 1fr 36px; align-items: center;
    margin-bottom: 8px; font-weight: 800; color: #0f172a;
}
.lp-search-cal-head button {
    width: 36px; height: 36px; border-radius: 999px; border: 1px solid #e2e8f0;
    background: #fff; cursor: pointer; color: #0f172a;
}
.lp-search-cal-grid {
    display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; text-align: center;
}
.lp-search-cal-grid .dow { font-size: 11px; font-weight: 700; color: #94a3b8; padding: 6px 0; }
.lp-search-cal-grid button {
    height: 40px; border: 0; border-radius: 999px; background: transparent;
    font: inherit; font-weight: 600; cursor: pointer; color: #0f172a;
}
.lp-search-cal-grid button:hover:not(:disabled) { background: #e0f2fe; }
.lp-search-cal-grid button:disabled { color: #cbd5e1; cursor: default; }
.lp-search-cal-grid button.is-today { box-shadow: inset 0 0 0 1.5px #0284c7; }
.lp-search-cal-grid button.is-in,
.lp-search-cal-grid button.is-out { background: #0284c7; color: #fff; }
.lp-search-cal-grid button.is-mid { background: #e0f2fe; border-radius: 0; }
.lp-search-pills { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.lp-search-pills button,
.lp-search-months button {
    border: 1px solid #cbd5e1; background: #fff; border-radius: 999px;
    padding: 10px 16px; font: inherit; font-weight: 700; cursor: pointer; color: #0f172a;
}
.lp-search-pills button.is-on,
.lp-search-months button.is-on { border-color: #0284c7; background: #f0f9ff; color: #0369a1; }
.lp-search-months { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 6px; }
.lp-search-months button { min-width: 120px; white-space: nowrap; }
.lp-search-flex-label { font-size: 13px; font-weight: 700; color: #334155; margin: 0 0 10px; }
.lp-search-flex-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px; }
.lp-search-flex-actions #lpSearchClearFlex {
    border: 0; background: none; color: #0284c7; font: inherit; font-weight: 700; cursor: pointer;
}
.lp-search-guests { max-width: 360px; left: auto; }
.lp-search-step {
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
    padding: 12px 4px;
    border-bottom: 1px solid #f1f5f9;
}
.lp-search-step:last-of-type { border-bottom: 0; }
.lp-search-step strong { display: block; font-size: 15px; color: #0f172a; }
.lp-search-step span { display: block; font-size: 12px; color: #94a3b8; margin-top: 2px; }
.lp-search-counter { display: inline-flex; align-items: center; gap: 14px; }
.lp-search-counter button {
    width: 34px; height: 34px; border-radius: 999px; border: 1px solid #cbd5e1;
    background: #fff; font-size: 18px; line-height: 1; cursor: pointer; color: #0284c7; font-weight: 700;
}
.lp-search-counter button:disabled { color: #cbd5e1; cursor: default; }
.lp-search-counter em { font-style: normal; font-weight: 800; min-width: 18px; text-align: center; color: #0f172a; }
.lp-search-note { margin: 8px 4px 0; color: #64748b; font-size: 12px; line-height: 1.45; }
@media (max-width: 900px) {
    .lp-search { grid-template-columns: 1fr; border-radius: 16px; }
    .lp-search-field, .lp-search-field:first-child { border-right: 0; border-bottom: 1px solid #e8eef3; border-radius: 0; min-height: 68px; }
    .lp-search-go { border-radius: 0 0 16px 16px; min-height: 54px; }
    .lp-search-cals { grid-template-columns: 1fr; }
    .lp-search-field strong { max-width: none; }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    const root = document.getElementById('lpSmartSearch');
    if (!root || root.dataset.ready === '1') return;
    root.dataset.ready = '1';
    const form = document.getElementById('lpSmartSearchForm');
    const kindInput = document.getElementById('lpSearchKind');
    const inInput = document.getElementById('lpSearchIn');
    const outInput = document.getElementById('lpSearchOut');
    const guestsInput = document.getElementById('lpSearchGuests');
    const adultsInput = document.getElementById('lpSearchAdults');
    const childrenInput = document.getElementById('lpSearchChildren');
    const roomsInput = document.getElementById('lpSearchRooms');
    const placeLabel = document.getElementById('lpSearchPlaceLabel');
    const inLabel = document.getElementById('lpSearchInLabel');
    const outLabel = document.getElementById('lpSearchOutLabel');
    const guestsLabel = document.getElementById('lpSearchGuestsLabel');
    const maxGuests = {{ (int) (\App\Models\CapacitySchedule::visitorCap() ?? 100) }};
    const maxRooms = 10;
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const week = ['Mo','Tu','We','Th','Fr','Sa','Su'];
    const placeNames = { all: 'Rooms & cottages', room: 'Rooms only', cottage: 'Cottages only', exclusive: 'Exclusive full resort' };
    let adults = Math.max(1, Math.min(maxGuests, Number(adultsInput.value || 2)));
    let children = Math.max(0, Math.min(maxGuests, Number(childrenInput.value || 0)));
    let rooms = Math.max(1, Math.min(maxRooms, Number(roomsInput.value || 1)));
    let inn = inInput.value || null;
    let out = outInput.value || null;
    let datePick = 'in';
    let cursor = new Date();
    cursor = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
    let flexNights = 2;
    let flexMonth = null;
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const iso = (date) => {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    };
    const parse = (value) => {
        const parts = String(value).split('-').map(Number);
        return new Date(parts[0], parts[1] - 1, parts[2]);
    };
    const pretty = (value) => {
        if (!value) return 'Add date';
        const date = parse(value);
        const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        return date.getDate() + ' ' + months[date.getMonth()].slice(0, 3) + ' ' + date.getFullYear() + ', ' + days[date.getDay()];
    };
    const partyTotal = () => adults + children;
    const guestSummary = () => {
        const bits = [];
        bits.push(adults + (adults === 1 ? ' adult' : ' adults'));
        if (children > 0) bits.push(children + (children === 1 ? ' child' : ' children'));
        return bits.join(', ') + ', ' + rooms + (rooms === 1 ? ' room' : ' rooms');
    };
    const paintLabels = () => {
        if (partyTotal() > maxGuests) {
            if (children > 0) children = Math.max(0, maxGuests - adults);
            else adults = maxGuests;
        }
        placeLabel.textContent = placeNames[kindInput.value] || placeNames.all;
        inLabel.textContent = pretty(inn);
        outLabel.textContent = pretty(out);
        guestsLabel.textContent = guestSummary();
        guestsInput.value = String(Math.max(1, partyTotal()));
        adultsInput.value = String(adults);
        childrenInput.value = String(children);
        roomsInput.value = String(rooms);
        inInput.value = inn || '';
        outInput.value = out || '';
        const row = (name) => root.querySelector('.lp-search-step[data-count="' + name + '"]');
        const setRow = (name, value, min, max) => {
            const el = row(name);
            if (!el) return;
            el.querySelector('[data-value]').textContent = String(value);
            el.querySelector('[data-minus]').disabled = value <= min;
            el.querySelector('[data-plus]').disabled = value >= max;
        };
        setRow('rooms', rooms, 1, maxRooms);
        setRow('adults', adults, 1, Math.max(1, maxGuests - children));
        setRow('children', children, 0, Math.max(0, maxGuests - adults));
    };
    const closePanels = () => {
        root.querySelectorAll('.lp-search-panel').forEach((panel) => panel.hidden = true);
        root.querySelectorAll('.lp-search-field').forEach((field) => {
            field.classList.remove('is-open');
            field.setAttribute('aria-expanded', 'false');
        });
    };
    const openPanel = (name, field) => {
        const panel = root.querySelector('[data-panel="' + name + '"]');
        const already = panel && !panel.hidden && (!field || field.classList.contains('is-open'));
        closePanels();
        if (already || !panel) return;
        if (name === 'dates') {
            const want = field && field.dataset.date === 'out' ? 'out' : 'in';
            if (want === 'out' && !inn) datePick = 'in';
            else datePick = want;
        }
        panel.hidden = false;
        if (name === 'dates') {
            const activeDate = datePick === 'out' ? 'out' : 'in';
            root.querySelectorAll('.lp-search-field[data-open="dates"]').forEach((btn) => {
                const on = btn.dataset.date === activeDate;
                btn.classList.toggle('is-open', on);
                btn.setAttribute('aria-expanded', on ? 'true' : 'false');
            });
            paintCals();
            return;
        }
        root.querySelectorAll('.lp-search-field[data-open="' + name + '"]').forEach((btn) => {
            btn.classList.add('is-open');
            btn.setAttribute('aria-expanded', 'true');
        });
    };
    const paintMonth = (target, title, year, month) => {
        title.textContent = months[month] + ' ' + year;
        target.innerHTML = '';
        week.forEach((day) => {
            const cell = document.createElement('div');
            cell.className = 'dow';
            cell.textContent = day;
            target.appendChild(cell);
        });
        const first = new Date(year, month, 1);
        let start = (first.getDay() + 6) % 7;
        for (let i = 0; i < start; i++) target.appendChild(document.createElement('div'));
        const days = new Date(year, month + 1, 0).getDate();
        for (let day = 1; day <= days; day++) {
            const date = new Date(year, month, day);
            const key = iso(date);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = String(day);
            const past = date < today;
            const beforeCheckout = datePick === 'out' && inn && key <= inn;
            btn.disabled = past || beforeCheckout;
            if (iso(today) === key) btn.classList.add('is-today');
            if (inn && key === inn) btn.classList.add('is-in');
            if (out && key === out) btn.classList.add('is-out');
            if (inn && out && key > inn && key < out) btn.classList.add('is-mid');
            btn.addEventListener('click', () => {
                if (datePick === 'out' && inn) {
                    if (key <= inn) return;
                    out = key;
                    paintLabels();
                    paintCals();
                    closePanels();
                    return;
                }
                inn = key;
                if (out && out <= inn) out = null;
                datePick = 'out';
                paintLabels();
                paintCals();
                root.querySelectorAll('.lp-search-field[data-open="dates"]').forEach((field) => {
                    const on = field.dataset.date === 'out';
                    field.classList.toggle('is-open', on);
                    field.setAttribute('aria-expanded', on ? 'true' : 'false');
                });
            });
            target.appendChild(btn);
        }
    };
    const paintCals = () => {
        const next = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 1);
        paintMonth(document.getElementById('lpSearchCalA'), document.getElementById('lpSearchTitleA'), cursor.getFullYear(), cursor.getMonth());
        paintMonth(document.getElementById('lpSearchCalB'), document.getElementById('lpSearchTitleB'), next.getFullYear(), next.getMonth());
    };
    const paintFlexMonths = () => {
        const box = document.getElementById('lpSearchMonths');
        box.innerHTML = '';
        for (let i = 0; i < 8; i++) {
            const date = new Date(today.getFullYear(), today.getMonth() + i, 1);
            const key = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = months[date.getMonth()] + ' ' + date.getFullYear();
            btn.dataset.month = key;
            if (flexMonth === key) btn.classList.add('is-on');
            btn.addEventListener('click', () => {
                flexMonth = key;
                paintFlexMonths();
            });
            box.appendChild(btn);
        }
    };

    root.querySelectorAll('.lp-search-field').forEach((field) => {
        field.addEventListener('click', () => openPanel(field.dataset.open, field));
    });
    root.querySelectorAll('.lp-search-choice').forEach((choice) => {
        choice.addEventListener('click', () => {
            kindInput.value = choice.dataset.kind;
            root.querySelectorAll('.lp-search-choice').forEach((btn) => btn.classList.toggle('is-on', btn === choice));
            paintLabels();
            closePanels();
        });
    });
    document.getElementById('lpSearchPrev').addEventListener('click', () => {
        const prev = new Date(cursor.getFullYear(), cursor.getMonth() - 1, 1);
        if (prev >= new Date(today.getFullYear(), today.getMonth(), 1)) {
            cursor = prev;
            paintCals();
        }
    });
    document.getElementById('lpSearchNext').addEventListener('click', () => {
        cursor = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 1);
        paintCals();
    });
    root.querySelectorAll('.lp-search-tabs button').forEach((tab) => {
        tab.addEventListener('click', () => {
            root.querySelectorAll('.lp-search-tabs button').forEach((btn) => btn.classList.toggle('is-on', btn === tab));
            root.querySelectorAll('.lp-search-tabpane').forEach((pane) => {
                const on = pane.dataset.pane === tab.dataset.tab;
                pane.hidden = !on;
                pane.classList.toggle('is-on', on);
            });
            if (tab.dataset.tab === 'flex') paintFlexMonths();
        });
    });
    root.querySelectorAll('#lpSearchNights button').forEach((btn) => {
        btn.addEventListener('click', () => {
            flexNights = Number(btn.dataset.nights);
            root.querySelectorAll('#lpSearchNights button').forEach((item) => item.classList.toggle('is-on', item === btn));
        });
    });
    document.getElementById('lpSearchClearFlex').addEventListener('click', () => {
        flexMonth = null;
        flexNights = 2;
        root.querySelectorAll('#lpSearchNights button').forEach((btn) => btn.classList.toggle('is-on', btn.dataset.nights === '2'));
        paintFlexMonths();
    });
    document.getElementById('lpSearchApplyFlex').addEventListener('click', () => {
        if (!flexMonth) return;
        const parts = flexMonth.split('-').map(Number);
        const start = new Date(parts[0], parts[1] - 1, 1);
        if (start < today) start.setTime(today.getTime());
        const end = new Date(start.getFullYear(), start.getMonth(), start.getDate() + flexNights);
        inn = iso(start);
        out = iso(end);
        paintLabels();
        closePanels();
    });
    root.querySelectorAll('.lp-search-step[data-count]').forEach((step) => {
        const key = step.dataset.count;
        step.querySelector('[data-minus]').addEventListener('click', () => {
            if (key === 'rooms' && rooms > 1) rooms -= 1;
            if (key === 'adults' && adults > 1) adults -= 1;
            if (key === 'children' && children > 0) children -= 1;
            paintLabels();
        });
        step.querySelector('[data-plus]').addEventListener('click', () => {
            if (key === 'rooms' && rooms < maxRooms) rooms += 1;
            if (key === 'adults' && partyTotal() < maxGuests) adults += 1;
            if (key === 'children' && partyTotal() < maxGuests) children += 1;
            paintLabels();
        });
    });
    document.addEventListener('click', (event) => {
        if (!root.contains(event.target)) closePanels();
    });
    form.addEventListener('submit', (event) => {
        paintLabels();
        if (!inn || !out) {
            event.preventDefault();
            openPanel('dates');
            return;
        }
        if (kindInput.value === 'exclusive') {
            event.preventDefault();
            const q = new URLSearchParams({
                check_in: inn,
                check_out: out,
                guests: String(Math.max(1, partyTotal())),
                adults: String(adults),
                children: String(children),
                rooms: String(rooms),
            });
            location.href = @json(route('public.booking.exclusive')) + '?' + q.toString();
        }
    });

    const params = new URLSearchParams(location.search);
    if (params.get('check_in')) inn = params.get('check_in');
    if (params.get('check_out')) out = params.get('check_out');
    if (params.get('adults')) adults = Math.max(1, Math.min(maxGuests, Number(params.get('adults')) || 2));
    if (params.get('children')) children = Math.max(0, Math.min(maxGuests, Number(params.get('children')) || 0));
    if (params.get('rooms')) rooms = Math.max(1, Math.min(maxRooms, Number(params.get('rooms')) || 1));
    if (params.get('guests') && !params.get('adults')) {
        const total = Math.max(1, Math.min(maxGuests, Number(params.get('guests')) || 2));
        adults = Math.max(1, total);
        children = 0;
    }
    if (params.get('kind') && placeNames[params.get('kind')]) {
        kindInput.value = params.get('kind');
        root.querySelectorAll('.lp-search-choice').forEach((btn) => btn.classList.toggle('is-on', btn.dataset.kind === kindInput.value));
    }
    if (inn) cursor = new Date(parse(inn).getFullYear(), parse(inn).getMonth(), 1);
    paintLabels();
    paintCals();
    paintFlexMonths();
})();
</script>
@endpush
@endonce
