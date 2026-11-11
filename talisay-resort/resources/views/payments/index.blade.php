@extends('layouts.app')

@section('title', 'Payments & Financials - Talisay Smart Tourism')

@section('content')

@php
    $statusLabels = ['pending' => 'Pending', 'success' => 'Paid', 'failed' => 'Failed', 'refunded' => 'Refunded'];
    $status = (string) request('status');
    $gateway = (string) request('gateway');
    $methodNames = $paymentMethods->pluck('name', 'code')->all();
    $methodIcons = $paymentMethods->pluck('icon', 'code')->all();
    $allCount = $statusCounts->sum();
    $canVerify = auth()->user()->isAdmin() || auth()->user()->hasPermission('payments', 'verify');
    $methodLabel = fn ($code) => $methodNames[$code] ?? \Illuminate\Support\Str::headline((string) $code);
@endphp

<div class="res-head">
    <div>
        <h1>{{ __('Payments') }}</h1>
        <p>Check guest receipts, approve payments, and track what has been collected.</p>
    </div>
    @if(auth()->user()->isAdmin())
    <div class="res-head-actions">
        <a href="{{ route('maintenance.section', 'payments') }}" class="btn-secondary-clean">
            <i class="bi bi-wallet2"></i>
            <span>{{ __('Payment methods') }}</span>
        </a>
    </div>
    @endif
</div>

<div class="unit-stats pay-stats" data-live-region="payment-stats">
    <div>
        <span>{{ __('Collected') }}</span>
        <strong class="is-good">₱{{ number_format($totalSuccess, 2) }}</strong>
        <em>
            @if($statusCounts['success'] ?? 0)
                {{ $statusCounts['success'] }} paid{{ $refundSentCount ? ' · net of refunds' : '' }}
            @else
                No approved payments yet
            @endif
        </em>
    </div>
    <div>
        <span>{{ __('Awaiting approval') }}</span>
        <strong class="{{ $pendingCount ? 'is-warn' : '' }}">₱{{ number_format($totalPending, 2) }}</strong>
        <em>
            @if($pendingCount)
                <a href="{{ route('payments.index', ['status' => 'pending']) }}" class="pay-link">{{ $pendingCount }} to review</a>
            @else
                0 to review
            @endif
            @if($awaitingReceiptCount)
                · {{ $awaitingReceiptCount }} waiting for a receipt
            @endif
        </em>
    </div>
    <div>
        <span>{{ __('Refunds to send') }}</span>
        <strong class="{{ $refundDueCount ? 'is-warn' : '' }}">₱{{ number_format($refundDueTotal, 2) }}</strong>
        <em>
            @if($refundDueCount)
                <a href="{{ route('payments.index', ['status' => 'refund_due']) }}" class="pay-link">{{ $refundDueCount }} cancelled {{ $refundDueCount === 1 ? 'booking' : 'bookings' }}</a>
            @elseif($refundSentCount)
                ₱{{ number_format($refundSentTotal, 2) }} already refunded
            @else
                Nothing owed to guests
            @endif
        </em>
    </div>
    <div>
        <span>{{ __('Failed') }}</span>
        <strong class="{{ ($statusCounts['failed'] ?? 0) ? 'is-bad' : '' }}">{{ $statusCounts['failed'] ?? 0 }}</strong>
        <em>
            @if($statusCounts['failed'] ?? 0)
                <a href="{{ route('payments.index', ['status' => 'failed']) }}" class="pay-link">Declined or not completed</a>
            @else
                Declined or not completed
            @endif
        </em>
    </div>
    <div>
        <span>{{ __('All payments') }}</span>
        <strong>{{ $allCount }}</strong>
        <em>{{ $refundSentCount ? $refundSentCount.' refunded' : 'Every attempt is kept' }}</em>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <form method="GET" action="{{ route('payments.index') }}" class="res-toolbar">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="hidden" name="gateway" value="{{ $gateway }}">

        <div class="dropdown" data-live-region="payment-status-filter">
            <button type="button" class="manual-kind-btn {{ $status !== '' ? 'is-active' : '' }}" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Filter by status">
                <span>{{ $status === 'refund_due' ? 'Refund due' : ($status !== '' ? ($statusLabels[$status] ?? ucfirst($status)) : 'All statuses') }}</span>
                <span class="unit-filter-count">{{ $status === 'refund_due' ? $refundDueCount : ($status !== '' ? ($statusCounts[$status] ?? 0) : $allCount) }}</span>
            </button>
            <ul class="dropdown-menu manual-kind-menu unit-filter-menu">
                <li>
                    <a class="dropdown-item {{ $status === '' ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}">
                        <span>All statuses</span><span class="unit-filter-count">{{ $allCount }}</span>
                    </a>
                </li>
                @foreach($statusLabels as $code => $label)
                <li>
                    <a class="dropdown-item {{ $status === $code ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['status' => $code, 'page' => null]) }}">
                        <span>{{ $label }}</span><span class="unit-filter-count">{{ $statusCounts[$code] ?? 0 }}</span>
                    </a>
                </li>
                @endforeach
            </ul>
        </div>

        <div class="dropdown" data-live-region="payment-method-filter">
            <button type="button" class="manual-kind-btn {{ $gateway !== '' ? 'is-active' : '' }}" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Filter by payment method">
                <span>{{ $gateway !== '' ? $methodLabel($gateway) : 'All methods' }}</span>
            </button>
            <ul class="dropdown-menu manual-kind-menu unit-filter-menu">
                <li>
                    <a class="dropdown-item {{ $gateway === '' ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['gateway' => null, 'page' => null]) }}">
                        <span>All methods</span><span class="unit-filter-count">{{ $allCount }}</span>
                    </a>
                </li>
                @foreach($paymentMethods as $method)
                <li>
                    <a class="dropdown-item {{ $gateway === $method->code ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['gateway' => $method->code, 'page' => null]) }}">
                        <span>{{ $method->name }}</span><span class="unit-filter-count">{{ $gatewayCounts[$method->code] ?? 0 }}</span>
                    </a>
                </li>
                @endforeach
            </ul>
        </div>

        @if(request()->anyFilled(['search', 'status', 'gateway']))
        <a href="{{ route('payments.index') }}" class="res-clear">Clear</a>
        @endif

        <div class="res-search unit-search">
            <i class="bi bi-search"></i>
            <input type="search" name="search" class="form-control-clean" placeholder="Booking ref or receipt no." value="{{ request('search') }}" aria-label="Search payments">
        </div>
        <span class="unit-count" data-live-region="payment-count">{{ $payments->total() }} {{ $payments->total() === 1 ? 'payment' : 'payments' }}</span>
    </form>

    <div class="table-responsive" data-live-region="payment-list">
        <table class="table-clean pay-table">
            <thead>
                <tr>
                    <th>{{ __('Booking') }}</th>
                    <th>{{ __('Guest') }}</th>
                    <th>{{ __('Amount') }}</th>
                    <th>{{ __('Method') }}</th>
                    <th>{{ __('Receipt') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Logged') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                @php
                    $booking = $payment->booking;
                    $receiptNo = $payment->guestReference();
                    $proof = $payment->proofUrl();
                @endphp
                <tr class="{{ $payment->status === 'pending' ? 'pay-row-pending' : '' }}">
                    <td>
                        <span class="res-ref">{{ $booking->reference_no ?? '—' }}</span>
                        @if($booking)
                        <div class="res-note">{{ $booking->booking_type === 'special_resort' ? 'Full resort' : ($booking->accommodationUnit->unit_number ?? '—') }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="res-guest">{{ $booking->guest_name ?? $booking?->user?->name ?? 'Guest' }}</span>
                        @if($booking?->user?->email)
                        <div class="res-note pay-guest-email" title="{{ $booking->user->email }}">{{ $booking->user->email }}</div>
                        @endif
                    </td>
                    <td class="res-money">
                        <strong class="pay-amount">₱{{ number_format($payment->amount, 2) }}</strong>
                        @if(($payment->metadata['payment_plan'] ?? null) === 'down')
                        <div class="res-note">{{ (int) ($payment->metadata['down_payment_percent'] ?? 50) }}% down payment</div>
                        @endif
                    </td>
                    <td>
                        <span class="pay-method">
                            <i class="bi {{ $methodIcons[$payment->gateway] ?? 'bi-credit-card' }}"></i>
                            {{ $methodLabel($payment->gateway) }}
                        </span>
                    </td>
                    <td>
                        @if($receiptNo || $proof)
                            @if($receiptNo)<div class="pay-receipt-no">{{ $receiptNo }}</div>@endif
                            @if($proof)
                            <a href="{{ $proof }}" target="_blank" rel="noopener" class="pay-proof"><i class="bi bi-image"></i> View receipt</a>
                            @endif
                        @else
                            <span class="res-note m-0">No receipt yet</span>
                        @endif
                    </td>
                    <td>
                        @if($payment->refundState() === 'due')
                        <span class="res-status pay-status-pending">Refund due</span>
                        @else
                        <span class="res-status pay-status-{{ $payment->status }}">{{ $statusLabels[$payment->status] ?? ucfirst($payment->status) }}</span>
                        @endif
                        @switch($payment->refundState())
                            @case('due')
                                <div class="res-note pay-refund-due">Refund due ₱{{ number_format($payment->suggestedRefund(), 2) }}</div>
                                @break
                            @case('sent')
                                <div class="res-note">Refunded ₱{{ number_format($payment->refundSent()['amount'], 2) }}</div>
                                @break
                        @endswitch
                    </td>
                    <td>
                        {{ $payment->created_at->format('M j, Y') }}
                        <div class="res-note">{{ $payment->created_at->format('g:i A') }}</div>
                    </td>
                    <td class="text-end">
                        <div class="unit-actions">
                            @if($payment->status === 'pending' && $canVerify)
                            <form method="POST" action="{{ route('payments.approve', $payment) }}" class="m-0"
                                  onsubmit="return confirm('Approve ₱{{ number_format($payment->amount, 2) }} for {{ $booking->reference_no ?? 'this booking' }}?')">
                                @csrf
                                <button type="submit" class="pay-approve" title="Approve payment">
                                    <i class="bi bi-check-lg"></i>
                                    <span>Approve</span>
                                </button>
                            </form>
                            @endif
                            <a href="{{ route('payments.show', $payment) }}" class="res-icon-btn" title="Payment details" aria-label="Payment details for {{ $booking->reference_no ?? 'booking' }}">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-12 text-slate-400">
                        <p class="text-sm mb-0">{{ request()->anyFilled(['search', 'status', 'gateway']) ? 'No payments match these filters.' : 'No payments yet.' }}</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div data-live-region="payment-pages">
        @if($payments->hasPages())
        <div class="p-4 border-t border-slate-100 flex justify-end">
            {{ $payments->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

@endsection
