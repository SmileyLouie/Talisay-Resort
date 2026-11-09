@php
    $gcashName = setting('gcash_name', 'Talisay Beach Resort');
    $gcashNumber = trim((string) setting('gcash_number', ''));
    $paymentMethods = $paymentMethods ?? \App\Models\PaymentMethod::catalog();
    $methodCodes = $paymentMethods->pluck('code')->all();
@endphp

<input type="hidden" name="card_last_four" value="">

<div class="pay-fields" data-gcash-number="{{ $gcashNumber }}" data-gcash-name="{{ $gcashName !== '' ? $gcashName : 'Talisay Beach Resort' }}">
    @if(!empty($chooseMethod))
    @php $pickedMethod = old('payment_method'); @endphp
    <div class="lp-pay-pick">
        <button type="button" class="lp-pay-trigger" aria-expanded="false" aria-haspopup="listbox">
            <span class="lp-pay-label">Payment method</span>
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </button>
        <div class="lp-pay-menu" role="listbox" hidden>
            @foreach($paymentMethods as $method)
                @php $account = \App\Models\PaymentMethod::details($method); @endphp
                <button type="button" role="option"
                        data-method="{{ $method->code }}"
                        data-kind="{{ $method->kind }}"
                        data-account-name="{{ $account['name'] }}"
                        data-account-number="{{ $account['number'] }}"
                        data-instructions="{{ $account['instructions'] }}">{{ $method->name }}</button>
            @endforeach
        </div>
        <input type="hidden" name="payment_method" value="{{ in_array($pickedMethod, $methodCodes, true) ? $pickedMethod : '' }}">
    </div>
    @php
        $lateRefund = (int) setting('late_cancellation_refund_percent', 50);
        $sameDayRefund = (int) setting('same_day_cancellation_refund', 0);
    @endphp
    <div class="lp-pay-blank">
        <p class="lp-pay-blank-lead">Select payment method first</p>
        <div class="lp-pay-policy">
            <p>If you cancel this reservation</p>
            <ul>
                <li>48 hours or more before the stay: a confirmed payment is refunded in full.</li>
                <li>Between 24 and 48 hours: {{ $lateRefund }}% of the confirmed payment is refunded. The resort keeps the rest.</li>
                <li>Within 24 hours: {{ $sameDayRefund > 0 ? $sameDayRefund.'% of the confirmed payment is refunded.' : 'a confirmed payment is no longer refundable.' }}</li>
                <li>If the payment is still waiting for approval, or nothing has been paid yet, cancelling does not create a refund. The room or cottage is released for other guests.</li>
            </ul>
        </div>
    </div>
    @endif
    <div class="pay-panel pay-panel-plan hidden rounded-2xl border border-slate-200 bg-white p-4 space-y-3">
        <p class="text-xs font-extrabold text-slate-800 mb-0">How much to pay now</p>
        <div class="grid grid-cols-2 gap-2">
            <label class="pay-choice border border-ocean-600 bg-ocean-50/40 rounded-xl p-3 flex items-center gap-2 cursor-pointer text-xs font-bold">
                <input type="radio" name="payment_plan" value="full" checked class="form-check-input text-ocean-600" onchange="refreshPayQuote(this.form)">
                <span>Full payment</span>
            </label>
            <label class="pay-choice border border-slate-200 rounded-xl p-3 flex items-center gap-2 cursor-pointer text-xs font-bold">
                <input type="radio" name="payment_plan" value="down" class="form-check-input text-ocean-600" onchange="refreshPayQuote(this.form)">
                <span>Down payment (50%)</span>
            </label>
        </div>
        <div class="pay-quote rounded-xl bg-slate-50 border border-slate-100 px-3 py-2 text-xs text-slate-600">
            <p class="mb-0">Pay now: <strong class="js-pay-now text-slate-900">₱0.00</strong></p>
            <p class="mb-0">Balance at the resort: <strong class="js-pay-balance text-slate-900">₱0.00</strong></p>
        </div>
    </div>

    <input type="hidden" name="gcash_method" value="qr">

    <div class="pay-panel pay-panel-gcash hidden rounded-2xl border border-sky-200 bg-sky-50 p-4 space-y-1">
        <p class="text-[11px] font-bold uppercase tracking-wider text-sky-700 mb-1 js-pay-account-label">Payment account</p>
        <p class="text-sm font-black text-slate-900 mb-0 js-pay-account-name">{{ $gcashName !== '' ? $gcashName : 'Talisay Beach Resort' }}</p>
        <p class="text-lg font-black tracking-wide text-ocean-800 mb-0 js-pay-account-number">{{ $gcashNumber !== '' ? $gcashNumber : 'Account number will appear here' }}</p>
        <p class="text-[11px] text-sky-800 mb-0 js-pay-account-note">Send the amount shown, then upload the receipt below.</p>
    </div>

    <div class="pay-panel pay-panel-qr hidden rounded-2xl border border-sky-200 bg-white p-4 space-y-3">
        <p class="text-xs font-extrabold text-sky-900 mb-0 js-pay-qr-title">Payment QR</p>
        <div class="pay-qr-row flex flex-col items-stretch gap-4">
            <div class="flex justify-center">
                <canvas class="js-gcash-qr rounded-xl border border-slate-200 bg-white" width="168" height="168"></canvas>
            </div>
            <div class="text-left">
                <p class="text-[11px] uppercase tracking-wider font-bold text-slate-400 mb-1">Fixed amount</p>
                <p class="text-2xl font-black text-ocean-900 mb-1 js-qr-amount">₱0.00</p>
                <p class="text-[11px] uppercase tracking-wider font-bold text-slate-400 mb-1 mt-2 js-pay-qr-number-label">Account</p>
                <p class="text-base font-black tracking-wide text-ocean-800 mb-0 js-pay-qr-number" style="overflow-wrap:anywhere">{{ $gcashNumber !== '' ? $gcashNumber : 'Account number will appear here' }}</p>
                <p class="text-xs font-bold text-slate-700 mb-1 js-pay-qr-name">{{ $gcashName !== '' ? $gcashName : 'Talisay Beach Resort' }}</p>
                <p class="text-[11px] leading-relaxed text-slate-500 mb-0">Scan this code, or send the amount to the account above. The amount follows the full payment or 50% down payment you selected.</p>
            </div>
        </div>
    </div>

    <div class="pay-panel pay-panel-proof hidden rounded-2xl border border-slate-200 bg-white p-4 space-y-3">
        <p class="text-xs font-extrabold text-slate-800 mb-0">Payment proof for the admin</p>
        <p class="text-[11px] text-slate-500 mb-0 js-pay-proof-copy">Both are required. Enter the transaction reference from your receipt and upload a photo of that receipt.</p>
        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Transaction reference number <span class="req-star" aria-hidden="true">*</span></label>
            <input type="text" name="reference_number" maxlength="40" autocomplete="off" class="w-full form-control rounded-xl text-sm" placeholder="Reference from your receipt">
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Receipt screenshot <span class="req-star" aria-hidden="true">*</span></label>
            <input type="file" name="proof_image" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp" class="w-full form-control rounded-xl text-sm">
        </div>
        <p class="js-proof-status pay-lock-note" hidden></p>
    </div>

    <div class="pay-panel pay-panel-card hidden rounded-2xl border border-slate-800 bg-slate-950 text-white p-4 space-y-3">
        <div class="flex items-center gap-2">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-sky-500 bg-slate-900 text-sky-300">
                <i class="bi bi-credit-card"></i>
            </span>
            <div>
                <p class="text-sm font-bold mb-0 leading-tight">Card payment</p>
                <p class="text-[11px] text-slate-400 mb-0">Visa, Mastercard, and other debit or credit cards</p>
            </div>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Card number <span class="req-star" aria-hidden="true">*</span></label>
            <input data-card-number inputmode="numeric" autocomplete="cc-number" maxlength="19" placeholder="1234 1234 1234 1234" class="w-full rounded-xl border border-slate-600 bg-slate-900 px-3 py-3 text-sm text-white outline-none focus:border-sky-500">
        </div>
        <div class="pay-card-row grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Expiration date <span class="req-star" aria-hidden="true">*</span></label>
                <input data-card-exp inputmode="numeric" autocomplete="cc-exp" maxlength="7" placeholder="MM / YY" class="w-full rounded-xl border border-slate-600 bg-slate-900 px-3 py-3 text-sm text-white outline-none focus:border-sky-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Security code <span class="req-star" aria-hidden="true">*</span></label>
                <input data-card-cvc inputmode="numeric" autocomplete="cc-csc" maxlength="4" placeholder="CVC" class="w-full rounded-xl border border-slate-600 bg-slate-900 px-3 py-3 text-sm text-white outline-none focus:border-sky-500">
            </div>
        </div>
        <p class="text-[11px] leading-relaxed text-slate-400 mb-0">The full card number and security code stay in this form. The resort only keeps the last 4 digits.</p>
    </div>
</div>
<script src="/js/qrcode.min.js"></script>
<script>
function peso(amount) {
    return '₱' + Number(amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function readStayTotal(form) {
    const node = form.querySelector('.js-stay-total');
    const raw = (node?.textContent || '').replace(/[^0-9.]/g, '');
    const amount = parseFloat(raw);
    return Number.isFinite(amount) ? amount : 0;
}
function selectedPayMethod(form) {
    const radio = form.querySelector('input[name="payment_method"]:checked');
    if (radio) return radio.value;
    const select = form.querySelector('select[name="payment_method"]');
    if (select) return select.value || '';
    return form.querySelector('input[type="hidden"][name="payment_method"]')?.value || '';
}
function payOption(form) {
    const method = selectedPayMethod(form);
    const marked = form.querySelector('[data-method="' + method + '"]');
    if (marked) return marked;
    const select = form.querySelector('select[name="payment_method"]');
    return select?.selectedOptions?.[0] || null;
}
function payKind(form) {
    const chosen = payOption(form);
    const method = selectedPayMethod(form);
    return chosen?.dataset.kind || (method === 'card' ? 'card' : (method === 'cash' ? 'cash' : (method ? 'ewallet' : '')));
}
function payNeedsProof(form) {
    const kind = payKind(form);
    return kind === 'ewallet' || kind === 'bank' || kind === 'online';
}
function payAllowsDown(form) {
    return payKind(form) !== 'cash' && selectedPayMethod(form) !== '';
}
function payDue(form) {
    const total = readStayTotal(form);
    const plan = form.querySelector('input[name="payment_plan"]:checked')?.value || 'full';
    const online = payAllowsDown(form);
    const due = online && plan === 'down' ? Math.round(total * 50) / 100 : total;
    return { total, due, balance: Math.round((total - due) * 100) / 100, plan: due < total ? 'down' : 'full' };
}
function markPayChoice(input) {
    const group = input?.closest('.grid');
    if (!group) return;
    group.querySelectorAll('.pay-choice').forEach((label) => {
        label.classList.remove('border-ocean-600', 'bg-ocean-50/40');
        label.classList.add('border-slate-200');
    });
    const label = input.closest('.pay-choice');
    if (!label) return;
    label.classList.add('border-ocean-600', 'bg-ocean-50/40');
    label.classList.remove('border-slate-200');
}
function renderGcashQr(form) {
    const canvas = form.querySelector('.js-gcash-qr');
    if (!canvas) return;
    const chosen = payOption(form);
    const quote = payDue(form);
    const number = chosen?.dataset.accountNumber || form.querySelector('.pay-fields')?.dataset.gcashNumber || '';
    const name = chosen?.dataset.accountName || form.querySelector('.pay-fields')?.dataset.gcashName || 'Talisay Beach Resort';
    const label = (chosen?.textContent || 'Payment').trim();
    const text = [
        'Talisay Beach Resort',
        label + ': ' + (number || 'Not set'),
        'Name: ' + name,
        'Amount: PHP ' + quote.due.toFixed(2),
        quote.plan === 'down' ? 'Down payment 50%' : 'Full payment',
        'This amount is fixed.'
    ].join('\n');
    if (window.QRCode) {
        QRCode.toCanvas(canvas, text, { width: 180, margin: 1, errorCorrectionLevel: 'M' }, function () {});
    }
}
function payDigits(value) {
    return (value || '').replace(/\D/g, '');
}
function referenceOk(form) {
    const raw = (form.querySelector('[name="reference_number"]')?.value || '').trim();
    if (selectedPayMethod(form) === 'gcash') return /^\d{13}$/.test(payDigits(raw));
    return raw.length >= 6;
}
function receiptOk(form) {
    const file = form.querySelector('[name="proof_image"]')?.files?.[0];
    if (!file || file.size < 1 || file.size > 5 * 1024 * 1024) return false;
    const type = (file.type || '').toLowerCase();
    const name = (file.name || '').toLowerCase();
    return type === 'image/jpeg' || type === 'image/png' || type === 'image/webp' || /\.(jpe?g|png|webp)$/.test(name);
}
function cardOk(form) {
    const number = payDigits(form.querySelector('[data-card-number]')?.value);
    const expiry = (form.querySelector('[data-card-exp]')?.value || '').trim();
    const cvc = payDigits(form.querySelector('[data-card-cvc]')?.value);
    return number.length >= 13 && number.length <= 19 && /^\d{2}\s*\/\s*\d{2}$/.test(expiry) && cvc.length >= 3 && cvc.length <= 4;
}
function activePayMethod(form) {
    return form.dataset.proofRequired === '1' ? 'gcash' : selectedPayMethod(form);
}
function paymentReady(form) {
    if (form.dataset.proofRequired === '1') return referenceOk(form) && receiptOk(form);
    const kind = payKind(form);
    if (payNeedsProof(form)) return referenceOk(form) && receiptOk(form);
    if (kind === 'card') return cardOk(form);
    return kind === 'cash';
}
function proofMessage(form) {
    const method = selectedPayMethod(form);
    if (form.dataset.proofRequired === '1' || payNeedsProof(form)) {
        if (!referenceOk(form) && !receiptOk(form)) return method === 'gcash'
            ? 'Enter the 13-digit transaction reference and upload the receipt screenshot. Confirm stays off until both are filled.'
            : 'Enter the transaction reference and upload the receipt screenshot. Confirm stays off until both are filled.';
        if (!referenceOk(form)) return method === 'gcash'
            ? 'The transaction reference must be 13 digits, like 1234 567 890123.'
            : 'Enter the transaction reference from your receipt.';
        if (!receiptOk(form)) return 'Upload a receipt screenshot (JPG, PNG, or WEBP, up to 5 MB).';
    }
    if (payKind(form) === 'card' && !cardOk(form)) return 'Enter the card number, expiration date (MM / YY), and security code. Confirm stays off until the card is complete.';
    return '';
}
function syncPaySubmit(form) {
    if (!form) return;
    const kind = form.dataset.proofRequired === '1' ? 'ewallet' : payKind(form);
    const ready = paymentReady(form);
    const message = proofMessage(form);
    const note = form.querySelector('.js-proof-status');
    if (note) {
        note.textContent = message;
        note.hidden = message === '';
    }
    const proof = kind === 'ewallet' || kind === 'bank' || kind === 'online';
    const reference = form.querySelector('[name="reference_number"]');
    const receipt = form.querySelector('[name="proof_image"]');
    if (reference) reference.required = proof;
    if (receipt) receipt.required = proof;
    form.querySelectorAll('[data-card-number],[data-card-exp],[data-card-cvc]').forEach((field) => { field.required = kind === 'card'; });
    form.querySelectorAll('button[type="submit"]').forEach((button) => {
        button.disabled = !ready;
        button.classList.toggle('is-locked', !ready);
        button.setAttribute('aria-disabled', ready ? 'false' : 'true');
    });
}
function refreshPayQuote(form) {
    if (!form) return;
    const quote = payDue(form);
    form.querySelectorAll('.js-pay-now, .js-qr-amount').forEach((node) => { node.textContent = peso(quote.due); });
    const balance = form.querySelector('.js-pay-balance');
    if (balance) balance.textContent = peso(quote.balance);
    form.querySelectorAll('input[name="payment_plan"]:checked').forEach(markPayChoice);
    if (payNeedsProof(form)) renderGcashQr(form);
    syncPaySubmit(form);
}
function showPayPanels(form) {
    if (!form) return;
    const method = selectedPayMethod(form);
    const kind = payKind(form);
    const chosen = payOption(form);
    const online = payAllowsDown(form);
    const blank = form.querySelector('.lp-pay-blank');
    const pick = form.querySelector('.lp-pay-pick');
    if (blank) blank.hidden = method !== '';
    if (pick) {
        pick.querySelector('.lp-pay-trigger')?.classList.toggle('is-on', method !== '');
        pick.querySelectorAll('[data-method]').forEach((item) => item.classList.toggle('is-on', item.dataset.method === method));
        const label = pick.querySelector('.lp-pay-label');
        if (label) label.textContent = chosen ? chosen.textContent.trim() : 'Payment method';
    }
    const accountName = chosen?.dataset.accountName || form.querySelector('.pay-fields')?.dataset.gcashName || '';
    const accountNumber = chosen?.dataset.accountNumber || form.querySelector('.pay-fields')?.dataset.gcashNumber || '';
    const instructions = chosen?.dataset.instructions || '';
    const nameNode = form.querySelector('.js-pay-account-name');
    const numberNode = form.querySelector('.js-pay-account-number');
    const noteNode = form.querySelector('.js-pay-account-note');
    if (nameNode) nameNode.textContent = accountName || chosen?.textContent.trim() || 'Talisay Beach Resort';
    if (numberNode) numberNode.textContent = accountNumber || 'Account number will appear here';
    if (noteNode) noteNode.textContent = instructions || 'Send the amount shown, then upload the receipt below.';
    form.querySelectorAll('.pay-panel').forEach((panel) => panel.classList.add('hidden'));
    form.querySelectorAll('[name="payment_plan"]').forEach((field) => { field.disabled = !online; });
    form.querySelectorAll('[name="gcash_method"]').forEach((field) => { field.disabled = !payNeedsProof(form); });
    if (online) form.querySelector('.pay-panel-plan')?.classList.remove('hidden');
    if (payNeedsProof(form)) {
        form.querySelector('.pay-panel-gcash')?.classList.remove('hidden');
        form.querySelector('.pay-panel-proof')?.classList.remove('hidden');
        const qrNumber = form.querySelector('.js-pay-qr-number');
        const qrName = form.querySelector('.js-pay-qr-name');
        const qrLabel = form.querySelector('.js-pay-qr-number-label');
        const qrTitle = form.querySelector('.js-pay-qr-title');
        if (qrNumber) qrNumber.textContent = accountNumber || 'Account number will appear here';
        if (qrName) qrName.textContent = accountName || 'Talisay Beach Resort';
        if (qrLabel) qrLabel.textContent = method === 'gcash' ? 'GCash number' : 'Account';
        if (qrTitle) qrTitle.textContent = (chosen?.textContent || 'Payment').trim() + ' QR';
        if (kind === 'ewallet' && accountNumber) form.querySelector('.pay-panel-qr')?.classList.remove('hidden');
    }
    if (kind === 'card') form.querySelector('.pay-panel-card')?.classList.remove('hidden');
    refreshPayQuote(form);
}
function bindPayPicker(form) {
    const pick = form.querySelector('.lp-pay-pick');
    if (!pick || pick.dataset.bound === '1') return;
    pick.dataset.bound = '1';
    const trigger = pick.querySelector('.lp-pay-trigger');
    const menu = pick.querySelector('.lp-pay-menu');
    const field = pick.querySelector('input[name="payment_method"]');
    const closeMenu = () => {
        menu.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
    };
    trigger.addEventListener('click', () => {
        const open = menu.hidden;
        menu.hidden = !open;
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    menu.querySelectorAll('[data-method]').forEach((item) => {
        item.addEventListener('click', () => {
            field.value = item.dataset.method;
            closeMenu();
            showPayPanels(form);
        });
    });
    document.addEventListener('click', (event) => {
        if (!pick.contains(event.target)) closeMenu();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeMenu();
    });
}
function prepareResortPayment(form) {
    syncPaySubmit(form);
    if (form.querySelector('.lp-pay-pick') && selectedPayMethod(form) === '') {
        alert('Select a payment method first.');
        return false;
    }
    if (!paymentReady(form)) {
        const message = proofMessage(form);
        if (message) alert(message);
        return false;
    }
    if (payKind(form) !== 'card') return true;
    const number = payDigits(form.querySelector('[data-card-number]')?.value);
    form.querySelector('[name="card_last_four"]').value = number.slice(-4);
    form.querySelectorAll('[data-card-number],[data-card-exp],[data-card-cvc]').forEach((field) => { field.disabled = true; });
    return true;
}
function bindPayForm(form) {
    if (!form || form.dataset.payReady === '1') return;
    const paymentForm = !!form.querySelector('.pay-panel');
    const proofForm = form.dataset.proofRequired === '1';
    if (!paymentForm && !proofForm) return;
    form.dataset.payReady = '1';
    bindPayPicker(form);
    form.addEventListener('input', () => syncPaySubmit(form));
    form.addEventListener('change', () => syncPaySubmit(form));
    if (paymentForm) showPayPanels(form);
    else syncPaySubmit(form);
}
function watchStayTotal(form) {
    const total = form.querySelector('.js-stay-total');
    if (!total || total.dataset.payWatch === '1' || !window.MutationObserver) return;
    total.dataset.payWatch = '1';
    new MutationObserver(() => refreshPayQuote(form)).observe(total, { childList: true, characterData: true, subtree: true });
    refreshPayQuote(form);
}
document.querySelectorAll('form').forEach(bindPayForm);
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form').forEach((form) => {
        bindPayForm(form);
        watchStayTotal(form);
    });
});
</script>
