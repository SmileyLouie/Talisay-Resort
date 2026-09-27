@extends('layouts.app')

@section('title', 'Payments & Financials - Talisay Smart Tourism')

@section('content')

{{-- Header --}}
<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100/80 text-sky-800 border border-sky-200">
                <i class="bi bi-wallet2 text-[10px] text-sky-600"></i>
                Financials
            </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-1">
            Payments &amp; Transactions
        </h1>
        <p class="text-sm text-slate-500 mb-0">
            Monitor transaction records, verify guest payments, and review proof of payment uploads.
        </p>
    </div>

    <div class="flex items-center gap-3">
        <span class="text-xs font-semibold px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 shadow-sm">
            Total Transactions: <strong class="text-slate-900">{{ $payments->total() }}</strong>
        </span>
    </div>
</div>

{{-- Financial Summary KPI Cards --}}
@php
    $totalSuccess = \App\Models\Payment::where('status', 'success')->sum('amount');
    $totalPending = \App\Models\Payment::where('status', 'pending')->sum('amount');
    $pendingCount = \App\Models\Payment::where('status', 'pending')->count();
@endphp
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6 mb-8">
    
    {{-- Total Revenue --}}
    <div class="stat-card-clean flex flex-col justify-between">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Total Revenue Collected</p>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-emerald-600 tracking-tight">
                    ₱{{ number_format($totalSuccess, 2) }}
                </h2>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xl font-bold shadow-sm">
                <i class="bi bi-cash-stack"></i>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center text-xs text-emerald-600 font-semibold">
            <i class="bi bi-check-circle-fill me-1.5 text-xs"></i> Verified successful payments
        </div>
    </div>

    {{-- Pending Approval Value --}}
    <div class="stat-card-clean flex flex-col justify-between">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Pending Approval Value</p>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-amber-600 tracking-tight">
                    ₱{{ number_format($totalPending, 2) }}
                </h2>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xl font-bold shadow-sm">
                <i class="bi bi-hourglass-split"></i>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center text-xs text-amber-600 font-semibold">
            <i class="bi bi-clock-history me-1.5 text-xs"></i> {{ $pendingCount }} transactions awaiting verification
        </div>
    </div>

    {{-- Total Logged --}}
    <div class="stat-card-clean flex flex-col justify-between">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Logged Payment Attempts</p>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    {{ $payments->total() }}
                </h2>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 border border-sky-100 flex items-center justify-center text-xl font-bold shadow-sm">
                <i class="bi bi-receipt"></i>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center text-xs text-sky-600 font-semibold">
            <i class="bi bi-shield-check me-1.5 text-xs"></i> Full audit trail available
        </div>
    </div>
</div>

{{-- Search & Filter Form --}}
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 sm:p-5 mb-6">
    <form method="GET" action="{{ route('payments.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
        <div class="sm:col-span-5">
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Search</label>
            <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" class="w-full pl-9 form-control-clean text-xs" placeholder="Transaction ID or Booking Reference..." value="{{ request('search') }}">
            </div>
        </div>

        <div class="sm:col-span-3">
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Status</label>
            <select name="status" class="w-full form-select-clean text-xs font-medium" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Success / Verified</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
            </select>
        </div>

        <div class="sm:col-span-2">
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Gateway</label>
            <select name="gateway" class="w-full form-select-clean text-xs font-medium" onchange="this.form.submit()">
                <option value="">All Gateways</option>
                <option value="stripe" {{ request('gateway') === 'stripe' ? 'selected' : '' }}>Stripe</option>
                <option value="gcash" {{ request('gateway') === 'gcash' ? 'selected' : '' }}>GCash</option>
                <option value="cash" {{ request('gateway') === 'cash' ? 'selected' : '' }}>Cash</option>
                <option value="bank_transfer" {{ request('gateway') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
            </select>
        </div>

        <div class="sm:col-span-2 flex gap-2">
            <button type="submit" class="btn-ocean text-xs flex-1 justify-center">
                <i class="bi bi-funnel-fill"></i> Filter
            </button>
            @if(request()->hasAny(['search','status','gateway']))
            <a href="{{ route('payments.index') }}" class="btn-secondary-clean text-xs">
                Clear
            </a>
            @endif
        </div>
    </form>
</div>

{{-- Payments Table Card --}}
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table-clean">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Booking Ref</th>
                    <th>Guest Details</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Date Logged</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr class="{{ $payment->status === 'pending' ? 'bg-amber-50/30' : '' }}">
                    <td>
                        <span class="font-mono text-xs font-extrabold text-slate-800">{{ $payment->transaction_id ?? 'N/A' }}</span>
                    </td>
                    <td>
                        <span class="font-mono text-xs font-bold text-sky-600 bg-sky-50 px-2 py-0.5 rounded border border-sky-200/60">
                            {{ $payment->booking->reference_no ?? '—' }}
                        </span>
                    </td>
                    <td>
                        <div class="font-bold text-slate-900 text-xs">{{ $payment->booking->user->name ?? 'Guest User' }}</div>
                        <div class="text-[11px] text-slate-400">{{ $payment->booking->user->email ?? '' }}</div>
                    </td>
                    <td>
                        <span class="text-xs font-black text-slate-900">₱{{ number_format($payment->amount, 2) }}</span>
                    </td>
                    <td>
                        @php
                            $gwIcons = ['stripe' => 'bi-credit-card-fill', 'gcash' => 'bi-phone-fill', 'cash' => 'bi-cash-stack', 'bank_transfer' => 'bi-bank'];
                        @endphp
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-700 border border-slate-200">
                            <i class="bi {{ $gwIcons[$payment->gateway] ?? 'bi-credit-card' }} text-sky-600"></i>
                            {{ ucfirst(str_replace('_', ' ', $payment->gateway)) }}
                        </span>
                    </td>
                    <td>
                        @php
                            $statusMap = [
                                'pending'  => 'bg-amber-50 text-amber-700 border-amber-200',
                                'success'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'failed'   => 'bg-rose-50 text-rose-700 border-rose-200',
                                'refunded' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                            ];
                        @endphp
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold border inline-flex items-center gap-1 {{ $statusMap[$payment->status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                            {{ ucfirst($payment->status) }}
                        </span>
                    </td>
                    <td>
                        <span class="text-xs text-slate-500 font-medium">{{ $payment->created_at->format('M d, Y • h:i A') }}</span>
                    </td>
                    <td class="text-end">
                        <div class="flex items-center justify-end gap-1.5">
                            <a href="{{ route('payments.show', $payment) }}" class="btn-secondary-clean text-xs py-1 px-2.5" title="View Details">
                                <i class="bi bi-eye text-slate-500"></i>
                                <span>Details</span>
                            </a>
                            @if($payment->status === 'pending')
                            <form method="POST" action="{{ route('payments.approve', $payment) }}" class="inline-block"
                                  onsubmit="return confirm('Approve payment PHP {{ number_format($payment->amount, 2) }}?')">
                                @csrf
                                <button type="submit" class="btn-ocean text-xs py-1 px-2.5" title="Approve Payment">
                                    <i class="bi bi-check-lg"></i>
                                    <span>Approve</span>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-12 text-slate-400">
                        <i class="bi bi-credit-card text-3xl block mb-2 text-slate-300"></i>
                        <p class="text-xs font-semibold mb-0">No payment records found matching your filters.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
    <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex justify-end">
        {{ $payments->withQueryString()->links() }}
    </div>
    @endif
</div>

@endsection