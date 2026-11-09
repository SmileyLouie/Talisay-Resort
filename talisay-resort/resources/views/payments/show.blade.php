@extends('layouts.app')

@section('title', 'Payment Detail — Talisay Smart Tourism')

@section('content')

@php
    $booking = $payment->booking;
    $meta = $payment->metadata ?? [];
    $statusLabels = ['pending' => 'Pending', 'success' => 'Paid', 'failed' => 'Failed', 'refunded' => 'Refunded'];
    $method = \App\Models\PaymentMethod::lookup($payment->gateway);
    $methodName = $method->name ?? \Illuminate\Support\Str::headline((string) ($payment->gateway ?: 'Unknown'));
    $methodIcon = $method->icon ?? 'bi-credit-card';
    $receiptNo = $payment->guestReference();
    $proof = $payment->proofUrl();
    $canVerify = auth()->user()->isAdmin() || auth()->user()->hasPermission('payments', 'verify');
    $plan = $meta['payment_plan'] ?? null;
    $paidAmount = $payment->paidAmount();
    $refundState = $payment->refundState();
    $refundSent = $payment->refundSent();
    $canRefund = auth()->user()->isAdmin() || auth()->user()->hasPermission('payments', 'refund');

    $stay = null;
    if ($booking) {
        if ($booking->isHourly() && $booking->hour_start) {
            [$in, $out] = $booking->stayWindow();
            $hours = max(1, (int) $booking->hours_count);
            $stay = $in->format('M j, Y').' · '.$in->format('g:i A').' – '.$out->format('g:i A').' ('.$hours.' '.($hours === 1 ? 'hour' : 'hours').')';
        } else {
            $nights = max(1, (int) $booking->nights_count);
            $stay = $booking->checkInDate()->format('M j, Y').' → '.$booking->checkOutDate()->format('M j, Y').' · '.$nights.' '.($nights === 1 ? 'night' : 'nights');
        }
    }
    $guestName = $booking?->guest_name ?: ($booking?->user?->name ?? 'Guest');
    $guestEmail = $booking?->user?->email;
    $guestPhone = $booking?->guest_contact_manual ?: $booking?->user?->phone;
    $initials = collect(preg_split('/\s+/', trim($guestName)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
@endphp

<a href="{{ route('payments.index') }}" class="pay-back"><i class="bi bi-arrow-left"></i> Payments</a>

<div class="res-head">
    <div>
        <h1 class="detail-title">
            Payment for {{ $booking->reference_no ?? 'booking' }}
            @if($refundState === 'due')
            <span class="res-status pay-status-pending">Refund due</span>
            @else
            <span class="res-status pay-status-{{ $payment->status }}">{{ $statusLabels[$payment->status] ?? ucfirst($payment->status) }}</span>
            @endif
        </h1>
        <p>{{ $methodName }} · logged {{ $payment->created_at->format('M j, Y \a\t g:i A') }}</p>
    </div>
</div>

<div class="pay-detail">
    <div class="pay-detail-main">
        <section class="pay-card">
            <div class="pay-amount-box">
                <span>{{ $plan === 'down' ? 'Down payment' : 'Amount' }}</span>
                <strong>₱{{ number_format($paidAmount, 2) }}</strong>
                @if($plan === 'down')
                    <em>50% down payment. Balance of ₱{{ number_format((float) ($meta['balance'] ?? 0), 2) }} is paid at the resort.</em>
                @elseif($plan === 'full')
                    <em>Full payment{{ ($meta['gcash_method'] ?? null) === 'qr' ? ' · guest paid through the GCash QR' : '' }}</em>
                @elseif(($meta['gcash_method'] ?? null) === 'qr')
                    <em>Guest paid through the GCash QR</em>
                @endif
            </div>

            <div class="manual-row">
                <label>Method</label>
                <div class="detail-value">
                    <span class="pay-method"><i class="bi {{ $methodIcon }}"></i> {{ $methodName }}</span>
                    @if($method && \App\Models\PaymentMethod::needsProof($payment->gateway) && $method->receivingNumber() !== '')
                    <span class="manual-hint">
                        Paid to {{ $method->receivingName() !== '' ? $method->receivingName() : $method->name }}
                        · {{ $method->receivingNumber() }}
                    </span>
                    @endif
                </div>
            </div>
            <div class="manual-row">
                <label>Receipt no.</label>
                <div class="detail-value">
                    @if($receiptNo)
                        <strong class="pay-receipt-no">{{ $receiptNo }}</strong>
                    @else
                        <span class="detail-muted">The guest has not entered one yet</span>
                    @endif
                </div>
            </div>
            <div class="manual-row">
                <label>Logged</label>
                <div class="detail-value">{{ $payment->created_at->format('M j, Y · g:i A') }}</div>
            </div>
            @if($refundState || isset($meta['refund_rule']))
            <div class="manual-row manual-row-top">
                <label>Refund</label>
                <div class="detail-value">
                    @if($refundState === 'sent')
                        <strong class="detail-ok">₱{{ number_format($refundSent['amount'], 2) }} refunded</strong>
                        <span class="manual-hint">
                            {{ \Carbon\Carbon::parse($refundSent['at'])->timezone(config('app.timezone'))->format('M j, Y · g:i A') }}{{ $refundSent['by'] ? ' by '.$refundSent['by'] : '' }}{{ $refundSent['reference'] ? ' · ref '.$refundSent['reference'] : '' }}
                            @if((float) ($meta['kept_amount'] ?? 0) > 0)
                                · the resort keeps ₱{{ number_format((float) $meta['kept_amount'], 2) }}
                            @endif
                        </span>
                        @if($refundSent['note'])<span class="manual-hint detail-notes">{{ $refundSent['note'] }}</span>@endif
                    @elseif($refundState === 'due')
                        <strong class="pay-refund-due">₱{{ number_format($payment->suggestedRefund(), 2) }} owed to the guest</strong>
                        <span class="manual-hint">The booking was cancelled. The policy refunds {{ (int) ($meta['refund_percent'] ?? 0) }}% of the ₱{{ number_format($paidAmount, 2) }} paid. Record it here once you send the money.</span>
                    @elseif($refundState === 'available' && $payment->status === 'failed')
                        Declined. If the money did reach the resort, you can refund it to the guest.
                    @elseif(($meta['refund_rule'] ?? '') === 'unpaid')
                        Cancelled before the payment was confirmed. Nothing was refunded.
                    @elseif(isset($meta['refund_rule']))
                        Cancelled within 24 hours of the stay, so the policy keeps the payment.
                        @if($refundState === 'available')<span class="manual-hint">You can still refund it if you decide to.</span>@endif
                    @endif
                </div>
            </div>
            @endif
        </section>

        <section class="pay-card">
            <div class="detail-section">
                <strong>Booking</strong>
                <span>The reservation this payment is for.</span>
            </div>
            @if($booking)
            <div class="manual-row">
                <label>Reference</label>
                <div class="detail-value">
                    <a href="{{ route('bookings.index', ['search' => $booking->reference_no]) }}" class="pay-link">{{ $booking->reference_no }}</a>
                    <span class="res-status res-status-{{ $booking->listStage() }} ms-1">{{ $booking->listStageLabel() }}</span>
                </div>
            </div>
            <div class="manual-row">
                <label>{{ $booking->isSpecialResort() ? 'Booking' : 'Room' }}</label>
                <div class="detail-value">
                    @if($booking->isSpecialResort())
                        Exclusive full-resort booking
                    @else
                        {{ $booking->accommodationUnit->unit_number ?? '—' }}
                        @if($booking->accommodationUnit?->variant_label)
                        <span class="detail-muted">· {{ $booking->accommodationUnit->variant_label }}</span>
                        @endif
                    @endif
                </div>
            </div>
            <div class="manual-row">
                <label>{{ $booking->isHourly() ? 'Date & time' : 'Stay' }}</label>
                <div class="detail-value">{{ $stay }}</div>
            </div>
            <div class="manual-row">
                <label>Guests</label>
                <div class="detail-value">{{ $booking->guests_count }} {{ (int) $booking->guests_count === 1 ? 'guest' : 'guests' }}</div>
            </div>
            <div class="manual-total">
                <span>Booking total</span>
                <strong>₱{{ number_format((float) $booking->total_amount, 2) }}</strong>
            </div>
            @else
            <div class="manual-row"><div class="detail-value detail-muted">This payment is not linked to a booking.</div></div>
            @endif
        </section>

        <section class="pay-card">
            <div class="detail-section">
                <strong>Receipt</strong>
                <span>The screenshot the guest uploaded.</span>
            </div>
            <div class="pay-proof-wrap">
                @if($proof)
                    <a href="{{ $proof }}" target="_blank" rel="noopener" class="pay-proof-frame">
                        <img src="{{ $proof }}" alt="Payment receipt for {{ $booking->reference_no ?? 'this booking' }}">
                    </a>
                    <a href="{{ $proof }}" target="_blank" rel="noopener" class="pay-proof"><i class="bi bi-box-arrow-up-right"></i> Open full size</a>
                @else
                    <p class="detail-muted mb-0">
                        No receipt was uploaded.
                        @if(\App\Models\PaymentMethod::needsProof($payment->gateway) && $receiptNo)
                            Check the receipt number above before approving.
                        @endif
                    </p>
                @endif
            </div>
        </section>
    </div>

    <aside class="pay-detail-side">
        <section class="pay-card">
            <div class="detail-section">
                <strong>{{ $payment->status === 'pending' ? 'Review this payment' : 'Verification' }}</strong>
                <span>
                    @switch($payment->status)
                        @case('pending') Match the amount and receipt number with your {{ $methodName }} records. @break
                        @case('success') {{ $refundState === 'sent' ? 'This payment was approved, then partly refunded after the booking was cancelled.' : ($refundState === 'due' ? 'This payment was approved, but the booking was cancelled and part of it is owed back.' : 'This payment was approved.') }} @break
                        @case('failed') This payment was declined or not completed. @break
                        @case('refunded') {{ $refundState === 'sent' ? 'The money was returned to the guest.' : 'A full refund is owed to the guest.' }} @break
                        @default Status: {{ ucfirst($payment->status) }}.
                    @endswitch
                </span>
            </div>
            @if($payment->status === 'pending')
            <div class="pay-actions">
                @if($canVerify)
                <form method="POST" action="{{ route('payments.approve', $payment) }}"
                      onsubmit="return confirm('Approve ₱{{ number_format($payment->amount, 2) }} and mark {{ $booking->reference_no ?? 'the booking' }} as paid?')">
                    @csrf
                    <button type="submit" class="btn-ocean w-100 justify-content-center">
                        <i class="bi bi-check2-circle"></i>
                        <span>Approve payment</span>
                    </button>
                </form>
                <form method="POST" action="{{ route('payments.reject', $payment) }}"
                      onsubmit="return confirm('Decline this payment? The booking stays pending.')">
                    @csrf
                    <button type="submit" class="btn-secondary-clean detail-danger w-100 justify-content-center">
                        <i class="bi bi-x-circle"></i>
                        <span>Decline</span>
                    </button>
                </form>
                <span class="manual-hint m-0">Approving confirms the booking and notifies the guest.</span>
                @else
                <p class="detail-muted mb-0">You can view this payment. Someone with payment verification access has to approve it.</p>
                @endif
            </div>
            @endif
        </section>

        @if(in_array($refundState, ['due', 'available'], true) && $canRefund)
        <section class="pay-card" id="refund">
            <div class="detail-section">
                <strong>Refund the guest</strong>
                <span>
                    @if($payment->status === 'failed')
                        Use this only if the guest's money actually reached the resort.
                    @else
                        The booking was cancelled after the guest paid.
                    @endif
                    Send the money first through {{ $methodName }} or cash, then record it here.
                </span>
            </div>
            <form method="POST" action="{{ route('payments.refund', $payment) }}" class="pay-actions"
                  onsubmit="return confirm('Record a refund of ₱' + Number(this.refund_amount.value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) + ' to the guest? This cannot be undone.')">
                @csrf
                @if($errors->has('refund_amount'))
                <div class="unit-modal-error m-0">{{ $errors->first('refund_amount') }}</div>
                @endif
                <div class="pay-field">
                    <label for="refundAmount">Amount</label>
                    <span class="unit-affix">
                        <span>₱</span>
                        <input type="number" id="refundAmount" name="refund_amount" class="form-control-clean" step="0.01" min="0.01" max="{{ $paidAmount }}"
                               value="{{ old('refund_amount', number_format($payment->suggestedRefund() ?: $paidAmount, 2, '.', '')) }}" required>
                        <em>of ₱{{ number_format($paidAmount, 2) }}</em>
                    </span>
                </div>
                <label class="pay-field">
                    <span>Refund reference <small>(optional)</small></span>
                    <input type="text" name="refund_reference" class="form-control-clean" maxlength="100" value="{{ old('refund_reference') }}" placeholder="GCash reference of the refund">
                </label>
                <label class="pay-field">
                    <span>Note <small>(optional)</small></span>
                    <textarea name="refund_note" rows="2" class="form-control-clean" maxlength="500" placeholder="e.g. Room was unavailable">{{ old('refund_note') }}</textarea>
                </label>
                <button type="submit" class="btn-ocean w-100 justify-content-center">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    <span>Record refund</span>
                </button>
                <span class="manual-hint m-0">The guest gets a notification with the amount and reference.</span>
            </form>
        </section>
        @endif

        <section class="pay-card">
            <div class="detail-section">
                <strong>Guest</strong>
                <span>{{ $booking?->user ? 'Registered account' : 'Walk-in guest' }}</span>
            </div>
            <div class="pay-guest">
                <div class="pay-avatar">{{ $initials ?: 'G' }}</div>
                <div class="min-w-0">
                    <div class="pay-guest-name">{{ $guestName }}</div>
                    @if($guestEmail)<div class="res-note text-truncate">{{ $guestEmail }}</div>@endif
                </div>
            </div>
            <div class="manual-row">
                <label>Phone</label>
                <div class="detail-value">
                    @if($guestPhone)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $guestPhone) }}" class="pay-link">{{ $guestPhone }}</a>
                    @else
                        <span class="detail-muted">Not recorded</span>
                    @endif
                </div>
            </div>
        </section>
    </aside>
</div>

@endsection
