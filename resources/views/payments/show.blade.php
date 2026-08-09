@extends('layouts.app')
@section('title', 'Payment Detail — Talisay Smart Tourism')

@section('content')

<div class="mb-5">
    <a href="{{ route('payments.index') }}" class="text-sky-600 hover:underline text-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Payments
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4">
    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-4">
    <i class="bi bi-x-circle me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">

    {{-- Payment Details Card --}}
    <div class="col-lg-8">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-800">Payment Details</h2>
                @php
                    $sc = ['success' => 'success', 'pending' => 'warning', 'failed' => 'danger', 'refunded' => 'secondary'];
                @endphp
                <span class="badge bg-{{ $sc[$payment->status] ?? 'secondary' }} fs-6 px-3 py-2">
                    <i class="bi bi-{{ $payment->status === 'success' ? 'check-circle' : ($payment->status === 'failed' ? 'x-circle' : 'clock') }} me-1"></i>
                    {{ ucfirst($payment->status) }}
                </span>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Transaction ID</label>
                    <p class="font-mono text-gray-700 mt-1">{{ $payment->transaction_id ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Amount</label>
                    <p class="text-xl font-bold text-green-600 mt-1">₱{{ number_format($payment->amount, 2) }}</p>
                </div>
                <div class="col-md-6">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Payment Gateway</label>
                    <p class="text-gray-700 mt-1">
                        <i class="bi bi-credit-card me-1"></i>{{ ucfirst($payment->gateway ?? 'N/A') }}
                    </p>
                </div>
                <div class="col-md-6">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Payment Date</label>
                    <p class="text-gray-700 mt-1">{{ $payment->created_at->format('M d, Y — h:i A') }}</p>
                </div>
            </div>

            <hr class="my-4">

            <h3 class="font-semibold text-gray-700 mb-3">Booking Information</h3>
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Booking Reference</label>
                    <p class="font-mono text-sky-600 font-bold mt-1">{{ $payment->booking->reference_no ?? '—' }}</p>
                </div>
                <div class="col-md-6">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Guest Name</label>
                    <p class="text-gray-700 mt-1">{{ $payment->booking->user->name ?? '—' }}</p>
                </div>
                <div class="col-md-6">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Package</label>
                    <p class="text-gray-700 mt-1">{{ $payment->booking->package->name ?? '—' }}</p>
                </div>
                <div class="col-md-6">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Booking Date</label>
                    <p class="text-gray-700 mt-1">
                        {{ $payment->booking->booking_date
                            ? \Carbon\Carbon::parse($payment->booking->booking_date)->format('M d, Y')
                            : '—' }}
                    </p>
                </div>
                <div class="col-md-6">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Guests</label>
                    <p class="text-gray-700 mt-1">{{ $payment->booking->guests_count ?? '—' }} pax</p>
                </div>
                <div class="col-md-6">
                    <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Booking Status</label>
                    <p class="mt-1">
                        <span class="badge bg-{{ $payment->booking->status === 'paid' ? 'success' : 'warning' }}">
                            {{ ucfirst($payment->booking->status ?? '—') }}
                        </span>
                    </p>
                </div>
            </div>

            {{-- Proof of Payment --}}
            @if($payment->proof_path)
            <hr class="my-4">
            <h3 class="font-semibold text-gray-700 mb-3">Proof of Payment</h3>
            <img src="{{ Storage::url($payment->proof_path) }}"
                 class="img-fluid rounded-xl shadow-sm"
                 style="max-height: 360px; object-fit: contain;"
                 alt="Proof of Payment">
            @endif
        </div>
    </div>

    {{-- Actions Sidebar --}}
    <div class="col-lg-4">

        {{-- Actions Card --}}
        @if($payment->status === 'pending')
        <div class="bg-white rounded-xl shadow-sm p-5 mb-4">
            <h3 class="font-semibold text-gray-700 mb-3"><i class="bi bi-sliders me-2"></i>Actions</h3>

            <form method="POST" action="{{ route('payments.approve', $payment) }}" class="mb-3"
                  onsubmit="return confirm('Approve this payment and mark the booking as paid?')">
                @csrf
                <button class="btn btn-success w-100">
                    <i class="bi bi-check-lg me-1"></i>Approve Payment
                </button>
            </form>

            <form method="POST" action="{{ route('payments.reject', $payment) }}"
                  onsubmit="return confirm('Reject this payment? The booking will remain pending.')">
                @csrf
                <button class="btn btn-outline-danger w-100">
                    <i class="bi bi-x-lg me-1"></i>Reject Payment
                </button>
            </form>
        </div>
        @endif

        {{-- Guest Info Card --}}
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="font-semibold text-gray-700 mb-3"><i class="bi bi-person-circle me-2"></i>Guest Info</h3>
            @php $guest = $payment->booking?->user; @endphp
            @if($guest)
            <div class="flex items-center gap-3 mb-3">
                <div class="w-12 h-12 rounded-full bg-sky-100 flex items-center justify-center">
                    <span class="font-bold text-sky-600 text-lg">{{ strtoupper(substr($guest->name, 0, 1)) }}</span>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">{{ $guest->name }}</p>
                    <p class="text-xs text-gray-400">{{ $guest->email }}</p>
                </div>
            </div>
            <p class="text-sm text-gray-500">
                <i class="bi bi-telephone me-1"></i>{{ $guest->phone ?? 'No phone on record' }}
            </p>
            @else
            <p class="text-sm text-gray-400">No guest data found.</p>
            @endif
        </div>

    </div>
</div>

@endsection