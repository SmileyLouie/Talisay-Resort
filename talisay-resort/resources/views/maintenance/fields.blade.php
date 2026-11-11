@if($section === 'activities')
    <div class="fm-row">
        <label>Title</label>
        <input type="text" name="title" value="{{ old('title', $row->title ?? '') }}" class="form-control-clean text-xs" required>
    </div>
    <div class="fm-row">
        <label>Schedule<span class="hint">Shown under the activity on the public site.</span></label>
        <input type="text" name="schedule" value="{{ old('schedule', $row->schedule ?? '') }}" class="form-control-clean text-xs" placeholder="Daily, 8:00 AM – 4:00 PM">
    </div>
    <div class="fm-row">
        <label>Description</label>
        <textarea name="description" rows="2" class="form-control-clean text-xs">{{ old('description', $row->description ?? '') }}</textarea>
    </div>
    <div class="fm-row">
        <label>Price</label>
        <div class="flex items-center gap-2">
            <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $row->price ?? '') }}" class="form-control-clean text-xs" style="max-width: 140px;">
            <span class="text-xs text-slate-400">PHP</span>
        </div>
    </div>
    <div class="fm-row">
        <label>Icon class</label>
        <input type="text" name="icon" value="{{ old('icon', $row->icon ?? 'bi-water') }}" class="form-control-clean text-xs" placeholder="bi-water">
    </div>
    <div class="fm-row">
        <label>Photo</label>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="form-control-clean text-xs">
    </div>
    <div class="fm-row">
        <label>Order</label>
        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $row->sort_order ?? 0) }}" class="form-control-clean text-xs" style="max-width: 120px;">
    </div>
    <div class="fm-row">
        <label>Public site</label>
        <label class="flex items-center gap-2 text-xs font-medium text-slate-600 mb-0">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $row->is_active ?? true) ? 'checked' : '' }}>
            Show this activity
        </label>
    </div>
    <div class="fm-row">
        <label>Booking</label>
        <label class="flex items-center gap-2 text-xs font-medium text-slate-600 mb-0">
            <input type="hidden" name="is_bookable" value="0">
            <input type="checkbox" name="is_bookable" value="1" {{ old('is_bookable', $row->is_bookable ?? true) ? 'checked' : '' }}>
            Guests can add this to a booking
        </label>
    </div>
@elseif($section === 'variants')
    <div class="fm-row">
        <label>Label</label>
        <input type="text" name="label" value="{{ old('label', $row->label ?? '') }}" class="form-control-clean text-xs" required placeholder="Family">
    </div>
    <div class="fm-row">
        <label>Applies to</label>
        <select name="applies_to" class="form-select-clean text-xs">
            @foreach(['room' => 'Rooms only', 'cottage' => 'Cottages only', 'both' => 'Rooms and cottages'] as $value => $label)
                <option value="{{ $value }}" @selected(old('applies_to', $row->applies_to ?? 'both') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="fm-row">
        <label>Nightly price<span class="hint">Filled in automatically when this variant is chosen on a new room or cottage.</span></label>
        <div class="flex items-center gap-2">
            <input type="number" step="0.01" min="0" name="price_per_night" value="{{ old('price_per_night', $row->price_per_night ?? '') }}" class="form-control-clean text-xs" style="max-width: 160px;" placeholder="1500">
            <span class="text-xs text-slate-400">PHP / night</span>
        </div>
    </div>
    <div class="fm-row">
        <label>Status</label>
        <label class="flex items-center gap-2 text-xs font-medium text-slate-600 mb-0">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $row->is_active ?? true) ? 'checked' : '' }}>
            Active
        </label>
    </div>
@elseif($section === 'payments')
    <div class="fm-row">
        <label>Name</label>
        <input type="text" name="name" value="{{ old('name', $row->name ?? '') }}" class="form-control-clean text-xs" required placeholder="Maya">
    </div>
    <div class="fm-row">
        <label>Code</label>
        <input type="text" name="code" value="{{ old('code', $row->code ?? '') }}" class="form-control-clean text-xs" {{ ($row->is_system ?? false) ? 'readonly' : '' }} placeholder="maya">
    </div>
    <div class="fm-row">
        <label>Kind</label>
        <div>
            <select name="kind" class="form-select-clean text-xs" {{ ($row->is_system ?? false) ? 'disabled' : '' }}>
                @foreach(['ewallet' => 'E-wallet (GCash, Maya, GrabPay)', 'bank' => 'Bank transfer (BDO, BPI, and other banks)', 'card' => 'Debit or credit card (last 4 digits only)', 'online' => 'Other online payment (PayPal, QR Ph)', 'cash' => 'Cash on arrival'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('kind', $row->kind ?? 'ewallet') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <span class="hint">The kind decides the guest form. E-wallets and banks ask for a receipt. Cards keep only the last 4 digits. Cash is paid at the desk.</span>
            @if($row->is_system ?? false)
                <input type="hidden" name="kind" value="{{ $row->kind }}">
            @endif
        </div>
    </div>
    @php
        $payCode = is_object($row) ? (string) ($row->code ?? '') : '';
        $payAccountName = old('account_name', is_object($row) ? (string) ($row->account_name ?? '') : '');
        $payAccountNumber = old('account_number', is_object($row) ? (string) ($row->account_number ?? '') : '');
        if ($payAccountName === '' && $payCode === 'gcash') {
            $payAccountName = setting('gcash_name', '');
        }
        if ($payAccountNumber === '' && $payCode === 'gcash') {
            $payAccountNumber = setting('gcash_number', '');
        }
    @endphp
    <div class="fm-row">
        <label>Account name<span class="hint">The name the guest sends the payment to. Leave blank for cash and card.</span></label>
        <input type="text" name="account_name" value="{{ $payAccountName }}" class="form-control-clean text-xs">
    </div>
    <div class="fm-row">
        <label>Account number<span class="hint">Mobile number, bank account, or PayPal email. Guests cannot choose an e-wallet, bank, or online payment until this is saved.</span></label>
        <input type="text" name="account_number" value="{{ $payAccountNumber }}" class="form-control-clean text-xs">
    </div>
    <div class="fm-row">
        <label>Guest instructions</label>
        <textarea name="instructions" rows="2" class="form-control-clean text-xs">{{ old('instructions', $row->instructions ?? '') }}</textarea>
    </div>
    <div class="fm-row">
        <label>Order</label>
        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $row->sort_order ?? 0) }}" class="form-control-clean text-xs" style="max-width: 120px;">
    </div>
    <div class="fm-row">
        <label>Booking page</label>
        <label class="flex items-center gap-2 text-xs font-medium text-slate-600 mb-0">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $row->is_active ?? true) ? 'checked' : '' }}>
            Offer this method to guests
        </label>
    </div>
@elseif($section === 'replies')
    <div class="fm-row">
        <label>Keywords<span class="hint">Words a guest might type. Separate more than one with a comma.</span></label>
        <input type="text" name="keyword" value="{{ old('keyword', $row->keyword ?? '') }}" class="form-control-clean text-xs" required placeholder="pool, swimming, what time does the pool open">
    </div>
    <div class="fm-row">
        <label>Topic</label>
        <select name="category" class="form-select-clean text-xs">
            @foreach(['rates' => 'Rates', 'hours' => 'Hours', 'policies' => 'Policies', 'directions' => 'Directions', 'facilities' => 'Facilities', 'booking_help' => 'Booking help', 'general' => 'General'] as $value => $label)
                <option value="{{ $value }}" @selected(old('category', $row->category ?? 'general') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="fm-row">
        <label>Reply<span class="hint">The answer the chatbot gives when a message matches the keywords.</span></label>
        <textarea name="response" rows="4" class="form-control-clean text-xs" required>{{ old('response', $row->response ?? '') }}</textarea>
    </div>
    <div class="fm-row">
        <label>Chatbot</label>
        <label class="flex items-center gap-2 text-xs font-medium text-slate-600 mb-0">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $row->is_active ?? true) ? 'checked' : '' }}>
            Use this reply
        </label>
    </div>
@else
    <div class="fm-row">
        <label>Amenity</label>
        <input type="text" name="name" value="{{ old('name', $row->name ?? '') }}" class="form-control-clean text-xs" required>
    </div>
    <div class="fm-row">
        <label>Status</label>
        <label class="flex items-center gap-2 text-xs font-medium text-slate-600 mb-0">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $row->is_active ?? true) ? 'checked' : '' }}>
            Active
        </label>
    </div>
@endif
