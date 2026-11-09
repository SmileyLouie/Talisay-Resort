<input type="hidden" name="stay_type" :value="stayType">
<input type="hidden" name="hours_count" :value="stayType === 'hour' ? hours : ''">
<input type="hidden" name="hour_start" :value="stayType === 'hour' ? hourStart : ''">

<div>
    <label class="block text-xs font-bold text-slate-700 mb-2">Book this room</label>
    <div class="grid grid-cols-2 gap-2">
        <label class="border rounded-xl p-3 flex items-center gap-2 cursor-pointer text-xs font-bold" :class="stayType === 'night' ? 'border-ocean-600 bg-ocean-50/40' : 'border-slate-200'">
            <input type="radio" value="night" x-model="stayType" @change="recalculate()" class="form-check-input text-ocean-600">
            <span>Per night</span>
        </label>
        <label class="border rounded-xl p-3 flex items-center gap-2 cursor-pointer text-xs font-bold" :class="stayType === 'hour' ? 'border-ocean-600 bg-ocean-50/40' : 'border-slate-200'">
            <input type="radio" value="hour" x-model="stayType" @change="recalculate()" class="form-check-input text-ocean-600">
            <span>Per hour</span>
        </label>
    </div>
    <p x-show="stayType === 'hour'" class="text-[11px] text-slate-500 mt-2 mb-0">The hourly rate is the amount saved for this room. A stay can be 1 to 12 hours.</p>
</div>

<div class="grid grid-cols-2 gap-3">
    <div :class="stayType === 'hour' ? 'col-span-2' : ''">
        <label class="block text-xs font-bold text-slate-700 mb-1" x-text="stayType === 'hour' ? 'Date' : 'Check-in Date'"></label>
        <input type="date" name="booking_date" x-model="date" @change="recalculate()" min="{{ date('Y-m-d') }}" class="w-full form-control rounded-xl text-sm" required>
    </div>
    <div x-show="stayType !== 'hour'">
        <label class="block text-xs font-bold text-slate-700 mb-1">Check-out Date</label>
        <input type="date" name="check_out_date" x-model="checkOut" @change="recalculate()" :min="date" :required="stayType !== 'hour'" :disabled="stayType === 'hour'" class="w-full form-control rounded-xl text-sm">
    </div>
</div>

<div x-show="stayType === 'hour'" class="grid grid-cols-2 gap-3">
    <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Start time</label>
        <select name="hour_start_choice" x-model="hourStart" @change="recalculate()" class="w-full form-select rounded-xl text-sm">
            @foreach (range(8, 20) as $hour)
            <option value="{{ sprintf('%02d:00', $hour) }}">{{ \Carbon\Carbon::createFromTime($hour)->format('g:i A') }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Hours</label>
        <input type="number" min="1" max="12" x-model.number="hours" @input="recalculate()" class="w-full form-control rounded-xl text-sm">
    </div>
</div>
