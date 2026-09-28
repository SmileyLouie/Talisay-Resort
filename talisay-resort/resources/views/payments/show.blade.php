@extends('layouts.app')

@section('title', 'Payment Detail — Talisay Smart Tourism')

@section('content')

<div class="mb-6">
    <a href="{{ route('payments.index') }}" class="btn-secondary-clean text-xs">
        <i class="bi bi-arrow-left"></i>
        <span>Back to Payments</span>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

    {{-- Payment Details Main Card --}}
    <div class="lg:col-span-8 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900 tracking-tight mb-0.5">Transaction Overview</h2>
                    <p class="text-xs text-slate-400 mb-0">Record logged in the resort payment gateway</p>
                </div>
                @php
                    $sc = [
                        'success'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'pending'  => 'bg-amber-50 text-amber-700 border-amber-200',
                        'failed'   => 'bg-rose-50 text-rose-700 border-rose-200',
                        'refunded' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                    ];
                @endphp
                <span class="px-3 py-1 rounded-full text-xs font-bold border inline-flex items-center gap-1.5 {{ $sc[$payment->status] ?? 'bg-slate-100 text-slate-700' }}">
                    <i class="bi bi-{{ $payment->status === 'success' ? 'check-circle-fill text-emerald-500' : ($payment->status === 'failed' ? 'x-circle-fill text-rose-500' : 'clock-fill text-amber-500') }}"></i>
                    {{ ucfirst($payment->status) }}
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Transaction ID</span>
                    <span class="font-mono text-xs font-extrabold text-slate-800">{{ $payment->transaction_id ?? 'N/A' }}</span>
                </div>
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Amount Paid</span>
                    <span class="text-xl font-extrabold text-emerald-600">₱{{ number_format($payment->amount, 2) }}</span>
                </div>
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Gateway Method</span>
                    <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="bi bi-credit-card text-sky-600"></i>
                        {{ ucfirst(str_replace('_', ' ', $payment->gateway ?? 'N/A')) }}
                    </span>
                </div>
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Timestamp</span>
                    <span class="text-xs font-bold text-slate-800">{{ $payment->created_at->format('M d, Y • h:i A') }}</span>
                </div>
            </div>

            {{-- Booking Details Group --}}
            <div class="pt-4 border-t border-slate-100">
                <h3 class="text-sm font-bold text-slate-800 mb-3 flex items-center gap-2">
                    <i class="bi bi-calendar2-check text-sky-600"></i>
                    <span>Linked Reservation</span>
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                        <span class="text-slate-400 block mb-0.5">Booking Reference</span>
                        <span class="font-mono font-extrabold text-sky-600 text-sm">{{ $payment->booking->reference_no ?? '—' }}</span>
                    </div>
                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                        <span class="text-slate-400 block mb-0.5">Room &amp; Cottage</span>
                        <span class="font-bold text-slate-800">{{ $payment->booking->accommodationUnit->unit_number ?? 'Exclusive Full Resort' }}</span>
                    </div>
                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                        <span class="text-slate-400 block mb-0.5">Scheduled Date</span>
                        <span class="font-semibold text-slate-800">
                            {{ $payment->booking->booking_date ? \Carbon\Carbon::parse($payment->booking->booking_date)->format('M d, Y') : '—' }}
                        </span>
                    </div>
                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                        <span class="text-slate-400 block mb-0.5">Headcount</span>
                        <span class="font-semibold text-slate-800">{{ $payment->booking->guests_count ?? '—' }} Guests</span>
                    </div>
                </div>
            </div>

            {{-- Proof of Payment Image --}}
            @if($payment->proof_path)
            <div class="pt-6 border-t border-slate-100 mt-6">
                <h3 class="text-sm font-bold text-slate-800 mb-3 flex items-center gap-2">
                    <i class="bi bi-file-earmark-image text-emerald-600"></i>
                    <span>Uploaded Proof of Payment</span>
                </h3>
                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 inline-block">
                    <img src="{{ Storage::url($payment->proof_path) }}"
                         class="rounded-xl shadow-sm max-h-96 object-contain"
                         alt="Proof of Payment">
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Side Column Actions & Guest --}}
    <div class="lg:col-span-4 space-y-6">

        {{-- Verification Actions Card --}}
        @if($payment->status === 'pending')
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
            <h3 class="text-sm font-bold text-slate-900 mb-2 flex items-center gap-2">
                <i class="bi bi-shield-check text-amber-500"></i>
                <span>Payment Verification</span>
            </h3>
            <p class="text-xs text-slate-400 mb-4">Review proof of payment and verify funds transfer before confirming.</p>

            <form method="POST" action="{{ route('payments.approve', $payment) }}" class="mb-2"
                  onsubmit="return confirm('Approve this payment and mark the booking as paid?')">
                @csrf
                <button type="submit" class="btn-ocean w-full justify-center text-xs py-2.5">
                    <i class="bi bi-check2-circle text-base"></i>
                    <span>Approve &amp; Confirm Booking</span>
                </button>
            </form>

            <form method="POST" action="{{ route('payments.reject', $payment) }}"
                  onsubmit="return confirm('Reject this payment? The booking will remain pending.')">
                @csrf
                <button type="submit" class="w-full btn btn-outline-danger btn-sm text-xs font-bold rounded-xl py-2">
                    <i class="bi bi-x-circle me-1"></i> Decline Payment
                </button>
            </form>
        </div>
        @endif

        {{-- Guest Profile Card --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
            <h3 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i class="bi bi-person-circle text-sky-600"></i>
                <span>Guest Information</span>
            </h3>
            @php $guest = $payment->booking?->user; @endphp
            @if($guest)
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-sky-600 to-sky-400 text-white font-black flex items-center justify-center text-base shadow-sm">
                    {{ strtoupper(substr($guest->name, 0, 2)) }}
                </div>
                <div>
                    <h4 class="font-bold text-slate-800 text-sm mb-0.5">{{ $guest->name }}</h4>
                    <p class="text-xs text-slate-400 mb-0">{{ $guest->email }}</p>
                </div>
            </div>
            <div class="text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100">
                <i class="bi bi-telephone-fill text-slate-400 me-1.5"></i>
                <span>{{ $guest->phone ?? 'No phone recorded' }}</span>
            </div>
            @else
            <p class="text-xs text-slate-400 mb-0">No registered guest record attached.</p>
            @endif
        </div>

    </div>
</div>

@endsection