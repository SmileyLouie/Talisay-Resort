@php
    $methods = $paymentMethods ?? \App\Models\PaymentMethod::catalog();
    $selected = old('payment_method', $methods->first()->code ?? '');
@endphp
<select name="payment_method" class="w-full form-select text-sm rounded-xl" required onchange="if (typeof showPayPanels === 'function') showPayPanels(this.form)">
    @foreach($methods as $method)
        @php $account = \App\Models\PaymentMethod::details($method); @endphp
        <option value="{{ $method->code }}" data-kind="{{ $method->kind }}" data-account-name="{{ $account['name'] }}" data-account-number="{{ $account['number'] }}" data-instructions="{{ $account['instructions'] }}" @selected($selected === $method->code)>{{ $method->name }}</option>
    @endforeach
</select>
