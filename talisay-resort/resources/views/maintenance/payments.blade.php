@php
    $saved = $rows->keyBy('code');
    $groups = collect(\App\Models\PaymentMethod::presets())->groupBy('group');
@endphp

<h1 class="fm-title">Payments</h1>
<p class="fm-lead">One list for every way a guest can pay. Turn a method on here and the booking forms, receipts, mobile app, and chatbot use it.</p>

@if(isset($errors) && $errors->any())
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        {{ $errors->first() }}
    </div>
@endif

<div class="fm-logic">
    <div>
        <strong>E-wallet</strong>
        <span>GCash, Maya, GrabPay, and the rest. The guest sends to the number and uploads the receipt. GCash still needs the 13-digit reference.</span>
    </div>
    <div>
        <strong>Bank transfer</strong>
        <span>BDO, BPI, and the other banks. The guest transfers with InstaPay or PESONet and uploads the receipt. The card number is not asked.</span>
    </div>
    <div>
        <strong>Card</strong>
        <span>Debit or credit card. The guest types the card in the browser. Only the last 4 digits are saved.</span>
    </div>
    <div>
        <strong>Cash</strong>
        <span>Pay at the front desk. No receipt upload and no down payment.</span>
    </div>
</div>

<div class="fm-panel mb-6">
    @forelse($rows as $row)
        @php
            $ready = $row->offeredToGuests();
            $waiting = $row->is_active && ! $ready;
        @endphp
        <div class="fm-row">
            <div class="fm-k">
                {{ $row->name }}
                <span class="hint">
                    {{ \App\Models\PaymentMethod::kindLabel($row->kind) }}
                    @if($row->receivingNumber() !== '')
                        · {{ $row->receivingNumber() }}
                    @endif
                    @if($waiting)
                        · Add the account number before guests can choose this
                    @endif
                </span>
            </div>
            <div class="fm-actions text-xs">
                <span class="{{ $ready ? 'text-emerald-600' : 'text-slate-400' }} font-semibold">{{ $ready ? 'On booking forms' : ($row->is_active ? 'Needs an account' : 'Hidden') }}</span>
                @if($row->is_system)
                    <span class="text-slate-400">Built-in</span>
                @endif
                <button type="button" class="font-semibold text-sky-700 bg-transparent border-0 p-0" onclick="document.getElementById('edit-payments-{{ $row->id }}').classList.toggle('hidden')">Edit</button>
                @unless($row->is_system)
                    <form action="{{ route('maintenance.destroy', ['payments', $row->id]) }}" method="POST" class="inline" onsubmit="return confirm('Remove this payment method?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="font-semibold text-rose-600 bg-transparent border-0 p-0">Remove</button>
                    </form>
                @endunless
            </div>
        </div>
        <div id="edit-payments-{{ $row->id }}" class="hidden fm-edit">
            <form method="POST" action="{{ route('maintenance.update', ['payments', $row->id]) }}">
                @csrf @method('PUT')
                @include('maintenance.fields', ['row' => $row, 'section' => 'payments'])
                <div class="fm-row">
                    <span></span>
                    <button type="submit" class="btn-ocean text-xs" style="width: auto;">Save changes</button>
                </div>
            </form>
        </div>
    @empty
        <div class="fm-row"><span class="text-sm text-slate-400">No payment methods yet.</span></div>
    @endforelse
</div>

<h2 class="fm-title">Philippine payments</h2>
<p class="fm-lead">Open a group to see every method in it. Check the ones you want, then add them. Set the account name and number on a method that is already in the list.</p>

<form id="pay-install" method="POST" action="{{ route('maintenance.payments.install') }}" class="hidden">
    @csrf
</form>

<div class="fm-pay-menus mb-6">
    @foreach($groups as $group => $presets)
        <div class="fm-pay-drop">
            <button type="button" class="fm-pay-edit" aria-expanded="false" onclick="togglePayGroup(this)">
                {{ $group }} <i class="bi bi-chevron-down"></i>
            </button>
            <div class="fm-pay-menu" hidden>
                <div class="fm-pay-menu-head">
                    <strong>{{ $group }}</strong>
                    @if($group === 'Banks')
                        <button type="submit" form="pay-install" name="group" value="Banks" class="btn-secondary-clean text-xs">Add all banks</button>
                    @elseif($group === 'E-wallets')
                        <button type="submit" form="pay-install" name="group" value="E-wallets" class="btn-secondary-clean text-xs">Add all e-wallets</button>
                    @endif
                </div>
                @foreach($presets as $preset)
                    @php
                        $row = $saved->get($preset['code']);
                        $accountName = $row ? $row->receivingName() : '';
                        $accountNumber = $row ? $row->receivingNumber() : '';
                        $needsAccount = $row && ! in_array($row->kind, ['cash', 'card'], true);
                    @endphp
                    <div class="fm-pay-item {{ $row ? 'is-added' : '' }}">
                        <div class="fm-pay-item-name">
                            @if($row)
                                <i class="bi bi-check2"></i>
                            @else
                                <input form="pay-install" type="checkbox" name="codes[]" value="{{ $preset['code'] }}">
                            @endif
                            <span>
                                <strong>{{ $preset['name'] }}</strong>
                                <em>
                                    @if($row && $accountNumber !== '')
                                        {{ $accountNumber }}@if($accountName !== '') · {{ $accountName }}@endif
                                    @elseif($row)
                                        In the list
                                    @else
                                        {{ \App\Models\PaymentMethod::kindLabel($preset['kind']) }}
                                    @endif
                                </em>
                            </span>
                        </div>
                        @if($needsAccount)
                            <form method="POST" action="{{ route('maintenance.update', ['payments', $row->id]) }}" class="fm-pay-item-form">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="name" value="{{ $row->name }}">
                                <input type="hidden" name="code" value="{{ $row->code }}">
                                <input type="hidden" name="kind" value="{{ $row->kind }}">
                                <input type="hidden" name="instructions" value="{{ $row->instructions }}">
                                <input type="hidden" name="sort_order" value="{{ $row->sort_order }}">
                                <input type="hidden" name="is_active" value="{{ $row->is_active ? 1 : 0 }}">
                                <input type="text" name="account_name" value="{{ $accountName }}" class="form-control-clean text-xs" placeholder="Account name" aria-label="{{ $preset['name'] }} account name">
                                <input type="text" name="account_number" value="{{ $accountNumber }}" class="form-control-clean text-xs" placeholder="{{ $row->kind === 'bank' ? 'Account number' : '09XX XXX XXXX' }}" aria-label="{{ $preset['name'] }} account number">
                                <button type="submit" class="btn-ocean text-xs">Save</button>
                            </form>
                        @endif
                    </div>
                @endforeach
                <div class="fm-pay-menu-foot">
                    <button type="submit" form="pay-install" class="btn-ocean text-xs">Add selected</button>
                </div>
            </div>
        </div>
    @endforeach
</div>

<h2 class="fm-title">Other payment</h2>
<p class="fm-lead">Use this when the wallet or bank is not in the list above. Pick the kind that matches how the guest pays.</p>
<form method="POST" action="{{ route('maintenance.store', 'payments') }}" class="fm-panel mb-6">
    @csrf
    @include('maintenance.fields', ['row' => null, 'section' => 'payments'])
    <div class="fm-row">
        <span></span>
        <button type="submit" class="btn-ocean text-xs" style="width: auto;">Save payment</button>
    </div>
</form>

<script>
function togglePayGroup(button) {
    const menu = button.parentElement.querySelector('.fm-pay-menu');
    const willOpen = menu.hidden;
    document.querySelectorAll('.fm-pay-menu').forEach((panel) => { panel.hidden = true; });
    document.querySelectorAll('.fm-pay-edit').forEach((item) => item.setAttribute('aria-expanded', 'false'));
    menu.hidden = !willOpen;
    button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
}
document.addEventListener('click', (event) => {
    if (event.target.closest('.fm-pay-drop')) return;
    document.querySelectorAll('.fm-pay-menu').forEach((panel) => { panel.hidden = true; });
    document.querySelectorAll('.fm-pay-edit').forEach((item) => item.setAttribute('aria-expanded', 'false'));
});
</script>

<h2 class="fm-title">Card processor</h2>
<p class="fm-lead">Optional Stripe keys for the debit or credit card method. Guests still send only the last 4 digits. Leave the secret blank to keep the one already stored.</p>
<form method="POST" action="{{ route('settings.save') }}" class="fm-panel">
    @csrf
    @method('PUT')
    <div class="fm-row">
        <label>Publishable key</label>
        <input type="text" name="settings[stripe_key]" value="{{ setting('stripe_key', '') }}" class="form-control-clean text-xs font-mono" placeholder="pk_test_..." autocomplete="off">
    </div>
    <div class="fm-row">
        <label>Secret key<span class="hint">{{ setting('stripe_secret') ? 'A secret key is stored.' : 'No secret key stored.' }}</span></label>
        <input type="password" name="settings[stripe_secret]" value="" class="form-control-clean text-xs font-mono" placeholder="Leave blank to keep the current key" autocomplete="new-password">
    </div>
    <div class="fm-row">
        <span></span>
        <button type="submit" class="btn-ocean text-xs" style="width: auto;">Save card processor</button>
    </div>
</form>
