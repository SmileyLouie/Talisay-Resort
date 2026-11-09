@php
    $methods = $paymentMethods ?? \App\Models\PaymentMethod::catalog();
    $selected = old('payment_method', $methods->first()->code ?? '');
@endphp
<div class="pay-choices">
    @foreach($methods as $method)
        @php $account = \App\Models\PaymentMethod::details($method); @endphp
        <label class="payment-option border {{ $selected === $method->code ? 'border-ocean-600 bg-ocean-50/40' : 'border-slate-200' }} rounded-xl p-3 flex items-center gap-2 cursor-pointer transition text-xs font-bold"
               data-method="{{ $method->code }}"
               data-kind="{{ $method->kind }}"
               data-account-name="{{ $account['name'] }}"
               data-account-number="{{ $account['number'] }}"
               data-instructions="{{ $account['instructions'] }}"
               onclick="selectPaymentMethod(this)">
            <input type="radio" name="payment_method" value="{{ $method->code }}" data-kind="{{ $method->kind }}" class="form-check-input text-ocean-600" @checked($selected === $method->code) onchange="if (typeof showPayPanels === 'function') showPayPanels(this.form)">
            <span>{{ $method->name }}</span>
        </label>
    @endforeach
</div>
