{{-- Extends the primary layout template designed for dashboard panels --}}
@extends('layouts.app')

@section('title', 'Emergencies - Talisay Smart Tourism')

@section('content')

{{-- Header --}}
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Emergency Assistance</h1>
        <p class="text-gray-500 text-sm">Monitor and respond to real-time resort emergency alerts</p>
    </div>
    {{-- Stats badges --}}
    @php
        $pending = $emergencies->where('status','pending')->count();
    @endphp
    @if($pending > 0)
    <span class="badge bg-danger badge-emergency fs-6 px-3 py-2">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $pending }} Active Alert{{ $pending > 1 ? 's' : '' }}
    </span>
    @endif
</div>

{{-- Flash messages --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Filters card --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-4 border-b flex items-center justify-between">
        <form method="GET" action="{{ route('emergencies.index') }}" class="flex gap-3">
            <select name="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="pending"      {{ request('status') === 'pending'      ? 'selected' : '' }}>Pending</option>
                <option value="acknowledged" {{ request('status') === 'acknowledged' ? 'selected' : '' }}>Acknowledged</option>
                <option value="responding"   {{ request('status') === 'responding'   ? 'selected' : '' }}>Responding</option>
                <option value="resolved"     {{ request('status') === 'resolved'     ? 'selected' : '' }}>Resolved</option>
            </select>
            <select name="category" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <option value="medical"   {{ request('category') === 'medical'   ? 'selected' : '' }}>Medical</option>
                <option value="security"  {{ request('category') === 'security'  ? 'selected' : '' }}>Security</option>
                <option value="lost_item" {{ request('category') === 'lost_item' ? 'selected' : '' }}>Lost Item</option>
                <option value="other"     {{ request('category') === 'other'     ? 'selected' : '' }}>Other</option>
            </select>
        </form>
        <span class="text-sm text-gray-400">{{ $emergencies->total() }} total</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover text-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tracking #</th>
                    <th>Guest</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Responder</th>
                    <th>Status</th>
                    <th>Time</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($emergencies as $emergency)
                <tr class="{{ $emergency->status === 'pending' ? 'table-danger' : '' }}">
                    <td class="font-mono text-red-600 font-bold">{{ $emergency->tracking_number }}</td>
                    <td>{{ $emergency->user->name ?? 'Guest User' }}</td>
                    <td>
                        @php
                            $catColors = ['medical' => 'danger', 'security' => 'dark', 'lost_item' => 'warning', 'other' => 'secondary'];
                            $catIcons  = ['medical' => 'heart-pulse', 'security' => 'shield-exclamation', 'lost_item' => 'search', 'other' => 'question-circle'];
                        @endphp
                        <span class="badge bg-{{ $catColors[$emergency->category] ?? 'secondary' }}">
                            <i class="bi bi-{{ $catIcons[$emergency->category] ?? 'exclamation' }} me-1"></i>
                            {{ ucfirst(str_replace('_', ' ', $emergency->category)) }}
                        </span>
                    </td>
                    <td class="text-truncate" style="max-width:200px;" title="{{ $emergency->description }}">
                        {{ $emergency->description }}
                    </td>
                    <td class="text-sm text-gray-500">{{ $emergency->responder->name ?? '—' }}</td>
                    <td>
                        @php
                            $statusColors = ['pending' => 'danger', 'acknowledged' => 'warning', 'responding' => 'info', 'resolved' => 'success'];
                        @endphp
                        <span class="badge bg-{{ $statusColors[$emergency->status] ?? 'secondary' }}">
                            {{ ucfirst($emergency->status) }}
                        </span>
                    </td>
                    <td>{{ $emergency->created_at->diffForHumans() }}</td>
                    <td>
                        @if($emergency->status !== 'resolved')
                        <button type="button"
                            class="btn btn-sm btn-outline-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#statusModal"
                            data-id="{{ $emergency->id }}"
                            data-tracking="{{ $emergency->tracking_number }}"
                            data-current="{{ $emergency->status }}"
                            data-next="{{ $emergency->status === 'pending' ? 'acknowledged' : ($emergency->status === 'acknowledged' ? 'responding' : 'resolved') }}"
                            data-next-label="{{ $emergency->status === 'pending' ? 'Acknowledge' : ($emergency->status === 'acknowledged' ? 'Mark Responding' : 'Mark Resolved') }}">
                            <i class="bi bi-arrow-right-circle me-1"></i>
                            {{ $emergency->status === 'pending' ? 'Acknowledge' : ($emergency->status === 'acknowledged' ? 'Responding' : 'Resolve') }}
                        </button>
                        @else
                        <span class="text-success"><i class="bi bi-check-circle"></i> Resolved</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-gray-400 py-10">
                        <i class="bi bi-shield-check text-4xl text-green-300 d-block mb-2"></i>
                        No active emergencies found. All clear!
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $emergencies->links() }}</div>
</div>

{{-- Status Update Modal --}}
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="statusForm">
            @csrf
            @method('PATCH')
            <div class="modal-content">
                <div class="modal-header bg-warning-subtle">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Update Emergency Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-sm text-gray-600 mb-3">
                        Tracking: <strong id="modalTracking"></strong><br>
                        New Status: <strong id="modalNextLabel" class="text-primary"></strong>
                    </p>
                    <input type="hidden" name="status" id="modalStatus">
                    <div class="mb-3">
                        <label class="form-label">Response Notes <span class="text-muted">(optional)</span></label>
                        <textarea name="response_notes" class="form-control" rows="3"
                            placeholder="Describe actions taken, notes for handover..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-lg me-1"></i>Confirm Update
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.getElementById('statusModal').addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    const id          = btn.dataset.id;
    const tracking    = btn.dataset.tracking;
    const nextStatus  = btn.dataset.next;
    const nextLabel   = btn.dataset.nextLabel;

    document.getElementById('modalTracking').textContent  = tracking;
    document.getElementById('modalNextLabel').textContent = nextLabel;
    document.getElementById('modalStatus').value          = nextStatus;
    document.getElementById('statusForm').action          = `/emergencies/${id}/status`;
});
</script>
@endpush