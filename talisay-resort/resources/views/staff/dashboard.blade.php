@extends('layouts.app')

@section('title', 'Staff Dashboard - Talisay Smart Tourism')

@section('content')

<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div class="flex items-start gap-4">
        @if($staff->avatar)
            <img src="{{ Storage::url($staff->avatar) }}" alt="{{ $staff->name }}" class="w-14 h-14 rounded-lg object-cover border border-slate-200 flex-shrink-0">
        @else
            <div class="w-14 h-14 rounded-lg bg-ocean-800 text-white flex items-center justify-center font-bold text-xl flex-shrink-0">
                {{ strtoupper(substr($staff->name, 0, 1)) }}
            </div>
        @endif
        <div>
            <div class="flex flex-wrap items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100 text-sky-800 border border-sky-200">
                    <i class="bi bi-person-badge text-[11px]"></i>
                    {{ $staff->staff_id ?? 'STAFF' }}
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white text-slate-600 border border-slate-200">
                    {{ $staff->department ?? 'Resort Operations' }}
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $staff->accountStatusBadgeClass() }}">
                    {{ $staff->accountStatusLabel() }}
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-1">
                Welcome back, {{ $staff->name }}
            </h1>
            <p class="text-sm text-slate-500 mb-0">
                {{ $staff->position ?? 'Resort Staff Member' }}
                · Duty: {{ ucfirst(str_replace('_', ' ', $staff->duty_status ?? 'available')) }}
            </p>
        </div>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        @if($staff->hasModuleAccess('bookings'))
        <a href="{{ route('bookings.index') }}" class="btn-ocean text-xs">
            <i class="bi bi-calendar-check"></i>
            <span>Bookings</span>
        </a>
        @endif
        @if($staff->hasModuleAccess('payments'))
        <a href="{{ route('payments.index') }}" class="px-4 py-2 rounded-lg bg-white text-slate-700 border border-slate-200 text-xs font-semibold flex items-center gap-2 no-underline">
            <i class="bi bi-credit-card"></i>
            <span>Payments</span>
        </a>
        @endif
    </div>
</div>

<div class="mb-6 flex items-center gap-2 flex-wrap">
    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Assigned modules</span>
    @forelse($assignedModules as $modKey)
        @php $modInfo = $moduleRegistry[$modKey] ?? ['name' => ucfirst($modKey), 'icon' => 'bi-check-circle']; @endphp
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white text-slate-700 border border-slate-200">
            <i class="bi {{ $modInfo['icon'] }} text-sky-700"></i>
            <span>{{ $modInfo['name'] }}</span>
        </span>
    @empty
        <span class="text-xs text-slate-500">No modules assigned. Access is limited to staff tasks and profile.</span>
    @endforelse
</div>

{{-- Success / Error Notifications --}}
@if(session('success'))
<div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl px-4 py-3.5 text-sm font-semibold shadow-sm">
    <i class="bi bi-check-circle-fill text-emerald-500 text-lg flex-shrink-0"></i>
    <div class="flex-1">{{ session('success') }}</div>
    <button class="text-emerald-400 hover:text-emerald-700" onclick="this.parentElement.remove()"><i class="bi bi-x-lg"></i></button>
</div>
@endif

@if(session('error'))
<div class="mb-6 flex items-center gap-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl px-4 py-3.5 text-sm font-semibold shadow-sm">
    <i class="bi bi-shield-x text-rose-500 text-lg flex-shrink-0"></i>
    <div class="flex-1">{{ session('error') }}</div>
    <button class="text-rose-400 hover:text-rose-700" onclick="this.parentElement.remove()"><i class="bi bi-x-lg"></i></button>
</div>
@endif

{{-- ── SECTION 1: BOOKING MANAGEMENT (IF ASSIGNED) ── --}}
@if($staff->hasModuleAccess('bookings'))
<div class="mb-8">
    <div class="flex items-center justify-between gap-4 mb-4">
        <div>
            <h2 class="text-lg font-black text-slate-900 tracking-tight flex items-center gap-2 mb-0">
                <i class="bi bi-calendar-check-fill text-sky-600"></i>
                <span>Booking Operations Overview</span>
            </h2>
            <p class="text-xs text-slate-500 mb-0">Real-time reservations, expected arrivals, and today's schedule.</p>
        </div>
        <div class="flex items-center gap-2">
            @if($staff->hasPermission('bookings', 'create'))
            <a href="{{ route('bookings.index') }}" class="btn-ocean text-xs">
                <i class="bi bi-plus-circle-fill"></i>
                <span>New Booking</span>
            </a>
            @endif
            <a href="{{ route('bookings.index') }}" class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition">
                View All
            </a>
        </div>
    </div>

    {{-- Booking Metrics --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat-card-clean flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl font-bold flex-shrink-0">
                <i class="bi bi-calendar2-event-fill"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Today's Bookings</p>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-0">{{ $bookingStats['today_count'] }}</h3>
            </div>
        </div>

        <div class="stat-card-clean flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold flex-shrink-0">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Pending Confirm</p>
                <h3 class="text-xl sm:text-2xl font-black text-amber-600 mb-0">{{ $bookingStats['pending_count'] }}</h3>
            </div>
        </div>

        <div class="stat-card-clean flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold flex-shrink-0">
                <i class="bi bi-box-arrow-in-right"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Checked In</p>
                <h3 class="text-xl sm:text-2xl font-black text-emerald-600 mb-0">{{ $bookingStats['checked_in'] }}</h3>
            </div>
        </div>

        <div class="stat-card-clean flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold flex-shrink-0">
                <i class="bi bi-people-fill"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Guests Today</p>
                <h3 class="text-xl sm:text-2xl font-black text-indigo-600 mb-0">{{ $bookingStats['total_guests'] }}</h3>
            </div>
        </div>
    </div>

    {{-- Today's Reservations Table --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
            <span class="text-xs font-extrabold uppercase tracking-wider text-slate-600">Today's Schedule &amp; Recent Bookings</span>
            <span class="text-xs text-slate-400">{{ now()->format('l, F j, Y') }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/60 text-slate-500 uppercase tracking-wider font-bold border-b border-slate-100">
                    <tr>
                        <th class="py-3 px-4">Ref #</th>
                        <th class="py-3 px-4">Guest</th>
                        <th class="py-3 px-4">Accommodation</th>
                        <th class="py-3 px-4">Booking Date</th>
                        <th class="py-3 px-4">Guests</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentReservations as $b)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-3 px-4 font-mono font-bold text-sky-700">{{ $b->reference_no }}</td>
                        <td class="py-3 px-4 font-semibold text-slate-900">{{ $b->guest_name ?? $b->user?->name }}</td>
                        <td class="py-3 px-4 text-slate-600">{{ $b->accommodationUnit?->type_label ?? 'General Resort Access' }}</td>
                        <td class="py-3 px-4 text-slate-500">{{ \Carbon\Carbon::parse($b->booking_date)->format('M d, Y') }}</td>
                        <td class="py-3 px-4 font-semibold">{{ $b->guests_count }}</td>
                        <td class="py-3 px-4">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase border {{ $b->getStatusBadgeClass() }}">
                                {{ str_replace('_', ' ', $b->status) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <a href="{{ route('bookings.index', ['search' => $b->reference_no]) }}" class="text-sky-600 hover:text-sky-800 font-bold text-xs">
                                View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400 italic">No bookings recorded for today yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- ── SECTION 2: PAYMENT VERIFICATION (IF ASSIGNED) ── --}}
@if($staff->hasModuleAccess('payments'))
<div class="mb-8">
    <div class="flex items-center justify-between gap-4 mb-4">
        <div>
            <h2 class="text-lg font-black text-slate-900 tracking-tight flex items-center gap-2 mb-0">
                <i class="bi bi-credit-card-fill text-emerald-600"></i>
                <span>Payment Verification Queue</span>
            </h2>
            <p class="text-xs text-slate-500 mb-0">Verify GCash receipts, update payment status, and process guest accounts.</p>
        </div>
        <a href="{{ route('payments.index') }}" class="px-3.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition">
            View All Payments
        </a>
    </div>

    {{-- Payment Metrics --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="stat-card-clean flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold flex-shrink-0">
                <i class="bi bi-receipt"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Pending Proofs</p>
                <h3 class="text-xl sm:text-2xl font-black text-amber-600 mb-0">{{ $paymentStats['pending_count'] }}</h3>
            </div>
        </div>

        <div class="stat-card-clean flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold flex-shrink-0">
                <i class="bi bi-check2-circle"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Verified Today</p>
                <h3 class="text-xl sm:text-2xl font-black text-emerald-600 mb-0">{{ $paymentStats['verified_today'] }}</h3>
            </div>
        </div>

        <div class="stat-card-clean flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl font-bold flex-shrink-0">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Today's Collections</p>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-0">₱{{ number_format($paymentStats['today_collected'], 2) }}</h3>
            </div>
        </div>
    </div>

    {{-- Pending Payments Table --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
            <span class="text-xs font-extrabold uppercase tracking-wider text-slate-600">Pending GCash / Cash Proofs Awaiting Verification</span>
            <span class="text-xs text-amber-600 font-bold">{{ $paymentStats['pending_count'] }} Awaiting Action</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/60 text-slate-500 uppercase tracking-wider font-bold border-b border-slate-100">
                    <tr>
                        <th class="py-3 px-4">Booking Ref</th>
                        <th class="py-3 px-4">Payer / Guest</th>
                        <th class="py-3 px-4">Amount</th>
                        <th class="py-3 px-4">Method</th>
                        <th class="py-3 px-4">Receipt Proof</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($pendingPayments as $p)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-3 px-4 font-mono font-bold text-sky-700">{{ $p->booking?->reference_no }}</td>
                        <td class="py-3 px-4 font-semibold text-slate-900">{{ $p->booking?->user?->name ?? 'Guest' }}</td>
                        <td class="py-3 px-4 font-black text-slate-900">₱{{ number_format($p->amount, 2) }}</td>
                        <td class="py-3 px-4 font-bold text-slate-600 uppercase">{{ $p->payment_method ?? 'GCash' }}</td>
                        <td class="py-3 px-4">
                            @if($p->proof_file)
                                <a href="{{ Storage::url($p->proof_file) }}" target="_blank" class="inline-flex items-center gap-1 text-sky-600 hover:text-sky-800 font-bold">
                                    <i class="bi bi-image"></i> View Receipt
                                </a>
                            @else
                                <span class="text-slate-400 italic">No receipt attached</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right">
                            <a href="{{ route('payments.show', $p->id) }}" class="px-3 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold">
                                Verify &amp; Review
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400 italic">No pending payments in queue. All accounts are up to date!</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif



{{-- ── SECTION 4: ROOM & COTTAGE TURNOVER (IF ASSIGNED) ── --}}
@if($staff->hasModuleAccess('accommodations') || $staff->hasModuleAccess('housekeeping'))
<div class="mb-8">
    <div class="flex items-center justify-between gap-4 mb-4">
        <div>
            <h2 class="text-lg font-black text-slate-900 tracking-tight flex items-center gap-2 mb-0">
                <i class="bi bi-building text-sky-600"></i>
                <span>Room &amp; Cottage Availability Glance</span>
            </h2>
            <p class="text-xs text-slate-500 mb-0">Occupancy status, cleaning readiness, and guest check-ins.</p>
        </div>
        <a href="{{ route('accommodations.index') }}" class="px-3.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition">
            View All Units
        </a>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
        @foreach($units as $unit)
        <div class="bg-white rounded-2xl border border-slate-200 p-3 text-center shadow-sm">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">#{{ $unit->unit_number }}</span>
            <h4 class="text-xs font-black text-slate-900 truncate mb-1" title="{{ $unit->name }}">{{ $unit->name }}</h4>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase border {{ $unit->is_available ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                {{ $unit->is_available ? 'Available' : 'Occupied / Maint' }}
            </span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ── SECTION 5: REVIEWS MODERATION (IF ASSIGNED) ── --}}
@if($staff->hasModuleAccess('reviews') && isset($pendingReviews))
<div class="mb-8">
    <div class="flex items-center justify-between gap-4 mb-4">
        <div>
            <h2 class="text-lg font-black text-slate-900 tracking-tight flex items-center gap-2 mb-0">
                <i class="bi bi-star-fill text-amber-500"></i>
                <span>Recent Guest Reviews</span>
            </h2>
            <p class="text-xs text-slate-500 mb-0">Latest guest ratings. Open the feedback hub to hide inappropriate comments.</p>
        </div>
        <a href="{{ route('reviews.index') }}" class="px-3.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition">
            Open Feedback Hub
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="divide-y divide-slate-100">
            @forelse($pendingReviews as $rev)
            <div class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 hover:bg-slate-50/70 transition">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-extrabold text-slate-900 text-xs">{{ $rev->user?->name ?? 'Guest' }}</span>
                        <div class="flex text-amber-400 text-xs">
                            @for($i=1; $i<=5; $i++)
                                <i class="bi bi-star{{ $i <= $rev->rating ? '-fill' : '' }}"></i>
                            @endfor
                        </div>
                        <span class="text-[10px] text-slate-400">• {{ $rev->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-xs text-slate-600 mb-0">{{ $rev->comment }}</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <form method="POST" action="{{ route('reviews.approve', $rev->id) }}">
                        @csrf @method('PUT')
                        <button type="submit" class="px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('reviews.reject', $rev->id) }}">
                        @csrf @method('PUT')
                        <button type="submit" class="px-3 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs">Reject</button>
                    </form>
                </div>
            </div>
            @empty
            <div class="p-6 text-center text-slate-400 italic">No reviews currently pending moderation.</div>
            @endforelse
        </div>
    </div>
</div>
@endif

@endsection
