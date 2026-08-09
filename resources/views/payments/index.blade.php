@extends('layouts.app')
@section('title', 'Payments & Financials - Talisay Smart Tourism')

@push('styles')
<style>
    .stat-card-mini {
        border-radius: 12px;
        padding: 16px 20px;
        background: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border-left: 4px solid #0ea5e9;
    }
</style>
@endpush

@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Payments & Financials</h1>
        <p class="text-gray-500 text-sm">Monitor transaction records, verify payments, and approve pending proof of payments</p>
    </div>
</div>

{{-- Financial Quick Stats --}}
@php
    $totalSuccess = \App\Models\Payment::where('status', 'success')->sum('amount');
    $totalPending = \App\Models\Payment::where('status', 'pending')->sum('amount');
    $pendingCount = \App\Models\Payment::where('status', 'pending')->count();
@endphp
<div class="row g-3 mb-5">
    <div class="col-md-4">
        <div class="stat-card-mini border-l-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Revenue Collected</p>
            <h3 class="text-2xl font-bold text-green-600">PHP {{ number_format($totalSuccess, 2) }}</h3>
            <p class="text-xs text-green-500 mt-1"><i class="bi bi-check-circle-fill me-1"></i>Verified successful payments</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card-mini border-l-4 border-amber-500">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Pending Approval Value</p>
            <h3 class="text-2xl font-bold text-amber-600">PHP {{ number_format($totalPending, 2) }}</h3>
            <p class="text-xs text-amber-500 mt-1"><i class="bi bi-hourglass-split me-1"></i>{{ $pendingCount }} transactions awaiting verification</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card-mini border-l-4 border-sky-500">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Transactions</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ $payments->total() }}</h3>
            <p class="text-xs text-sky-500 mt-1"><i class="bi bi-receipt me-1"></i>All logged payment attempts</p>
        </div>
    </div>
</div>

{{-- Search & Filter Form --}}
<div class="bg-white rounded-xl shadow-sm p-4 mb-4">
    <form method="GET" action="{{ route('payments.index') }}" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label text-xs font-semibold text-gray-500 uppercase tracking-wider">Search</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Transaction ID or Booking Ref..." value="{{ request('search') }}">
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</label>
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Success</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label text-xs font-semibold text-gray-500 uppercase tracking-wider">Gateway</label>
            <select name="gateway" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Gateways</option>
                <option value="stripe" {{ request('gateway') === 'stripe' ? 'selected' : '' }}>Stripe</option>
                <option value="gcash" {{ request('gateway') === 'gcash' ? 'selected' : '' }}>GCash</option>
                <option value="cash" {{ request('gateway') === 'cash' ? 'selected' : '' }}>Cash</option>
                <option value="bank_transfer" {{ request('gateway') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
            </select>
        </div>
        <div class="col-md-2 text-end">
            <button type="submit" class="btn btn-sm btn-primary w-full"><i class="bi bi-filter me-1"></i>Filter</button>
            @if(request()->hasAny(['search','status','gateway']))
            <a href="{{ route('payments.index') }}" class="btn btn-sm btn-link text-gray-500 w-full mt-1">Clear</a>
            @endif
        </div>
    </form>
</div>

{{-- Payments Table --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-4 border-b flex items-center justify-between">
        <h3 class="font-semibold text-gray-700">
            <i class="bi bi-credit-card-fill me-2 text-sky-500"></i>
            {{ $payments->total() }} Payment Records
        </h3>
    </div>

    <div class="table-responsive">
        <table class="table table-hover text-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Transaction ID</th>
                    <th>Booking Ref</th>
                    <th>Guest</th>
                    <th>Amount</th>
                    <th>Gateway</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr class="{{ $payment->status === 'pending' ? 'bg-amber-50/50' : '' }}">
                    <td class="font-mono text-xs text-gray-700 fw-bold">{{ $payment->transaction_id ?? 'N/A' }}</td>
                    <td class="font-mono text-sky-600 font-semibold">{{ $payment->booking->reference_no ?? '—' }}</td>
                    <td>{{ $payment->booking->user->name ?? 'Guest User' }}</td>
                    <td class="font-bold text-gray-800">PHP {{ number_format($payment->amount, 2) }}</td>
                    <td>
                        @php
                            $gwIcons = ['stripe' => 'bi-credit-card', 'gcash' => 'bi-phone', 'cash' => 'bi-cash-stack', 'bank_transfer' => 'bi-bank'];
                        @endphp
                        <span class="badge bg-light text-dark border">
                            <i class="bi {{ $gwIcons[$payment->gateway] ?? 'bi-credit-card' }} me-1"></i>
                            {{ ucfirst(str_replace('_', ' ', $payment->gateway)) }}
                        </span>
                    </td>
                    <td>
                        @php
                            $colors = ['pending' => 'warning text-dark', 'success' => 'success', 'failed' => 'danger', 'refunded' => 'info'];
                        @endphp
                        <span class="badge bg-{{ $colors[$payment->status] ?? 'secondary' }}">
                            {{ ucfirst($payment->status) }}
                        </span>
                    </td>
                    <td class="text-xs text-gray-500">{{ $payment->created_at->format('M d, Y H:i') }}</td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('payments.show', $payment) }}" class="btn btn-outline-primary" title="View Details">
                                <i class="bi bi-eye"></i> View
                            </a>
                            @if($payment->status === 'pending')
                            <form method="POST" action="{{ route('payments.approve', $payment) }}" class="d-inline"
                                  onsubmit="return confirm('Approve payment PHP {{ number_format($payment->amount, 2) }}?')">
                                @csrf
                                <button type="submit" class="btn btn-success" title="Approve Payment">
                                    <i class="bi bi-check-lg"></i> Approve
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-8 text-gray-400">
                        <i class="bi bi-credit-card text-4xl block mb-2 text-gray-300"></i>
                        No payment records found matching your filters.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t">{{ $payments->withQueryString()->links() }}</div>
</div>
@endsection