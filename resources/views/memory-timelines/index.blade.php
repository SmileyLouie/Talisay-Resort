@extends('layouts.app')
@section('title', 'Memory Timelines - Talisay Smart Tourism')

@push('styles')
<style>
    .timeline-card {
        border-radius: 14px;
        transition: transform .2s ease, box-shadow .2s ease;
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }
    .timeline-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(0,0,0,.1);
    }
    .timeline-header {
        background: linear-gradient(135deg, #0ea5e9 0%, #14b8a6 100%);
        padding: 18px 20px;
        position: relative;
        overflow: hidden;
    }
    .timeline-header::before {
        content: '📷';
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 40px;
        opacity: .25;
    }
    .photo-strip {
        display: flex;
        gap: 4px;
        height: 56px;
        overflow: hidden;
        border-radius: 8px;
        background: #f1f5f9;
    }
    .photo-strip-item {
        flex: 1;
        background: linear-gradient(135deg, #0ea5e9, #14b8a6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
        border-radius: 6px;
    }
    .stat-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        padding: 4px 10px;
        border-radius: 20px;
        background: #f1f5f9;
        color: #64748b;
    }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
</style>
@endpush

@section('content')

{{-- Page Header --}}
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Memory Timelines</h1>
        <p class="text-gray-500 text-sm">Guest photo scrapbooks and memory collections generated after their visit</p>
    </div>
    @php
        $generatedCount = $timelines->where('is_generated', true)->count();
        $pendingCount = $timelines->where('is_generated', false)->count();
    @endphp
</div>

{{-- Stats Summary --}}
<div class="row g-3 mb-5">
    <div class="col-md-4">
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-sky-500">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Timelines</p>
            <h3 class="text-2xl font-bold text-gray-800">{{ $timelines->total() }}</h3>
            <p class="text-xs text-sky-500 mt-1"><i class="bi bi-camera-fill me-1"></i>All guest scrapbooks</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Generated</p>
            <h3 class="text-2xl font-bold text-green-600">{{ \App\Models\MemoryTimeline::where('is_generated', true)->count() }}</h3>
            <p class="text-xs text-green-500 mt-1"><i class="bi bi-check-circle-fill me-1"></i>Ready to download</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-amber-500">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Pending</p>
            <h3 class="text-2xl font-bold text-amber-600">{{ \App\Models\MemoryTimeline::where('is_generated', false)->count() }}</h3>
            <p class="text-xs text-amber-500 mt-1"><i class="bi bi-hourglass-split me-1"></i>Awaiting generation</p>
        </div>
    </div>
</div>

{{-- Timeline Grid/Table Toggle --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-4 border-b flex items-center justify-between">
        <h3 class="font-semibold text-gray-700">
            <i class="bi bi-collection-fill me-2 text-sky-500"></i>
            Guest Memory Scrapbooks
        </h3>
        <div class="btn-group btn-group-sm" id="viewToggle">
            <button class="btn btn-outline-secondary active" id="listViewBtn" onclick="switchView('list')">
                <i class="bi bi-list-ul"></i> List
            </button>
            <button class="btn btn-outline-secondary" id="gridViewBtn" onclick="switchView('grid')">
                <i class="bi bi-grid-3x3-gap"></i> Grid
            </button>
        </div>
    </div>

    {{-- LIST VIEW --}}
    <div id="listView">
        <div class="table-responsive">
            <table class="table table-hover text-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Guest</th>
                        <th>Booking Reference</th>
                        <th>Timeline Title</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($timelines as $timeline)
                    <tr>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-sky-100 flex items-center justify-center text-sky-600 font-bold text-xs">
                                    {{ initials($timeline->user->name ?? 'G') }}
                                </div>
                                <div>
                                    <p class="font-semibold mb-0">{{ $timeline->user->name ?? 'Guest' }}</p>
                                    <p class="text-xs text-gray-400 mb-0">{{ $timeline->user->email ?? '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="font-mono text-sky-600">{{ $timeline->booking->reference_no ?? '—' }}</td>
                        <td class="font-semibold">{{ $timeline->title }}</td>
                        <td>
                            @php
                                $itemCount = \App\Models\MemoryTimelineItem::where('user_id', $timeline->user_id)
                                    ->where('booking_id', $timeline->booking_id)->count();
                                $selectedCount = \App\Models\MemoryTimelineItem::where('user_id', $timeline->user_id)
                                    ->where('booking_id', $timeline->booking_id)->where('is_selected', true)->count();
                            @endphp
                            <span class="stat-chip">
                                <i class="bi bi-images"></i> {{ $itemCount }} items
                            </span>
                            @if($selectedCount > 0)
                            <span class="stat-chip ms-1" style="background:#dbeafe;color:#1d4ed8;">
                                <i class="bi bi-check-circle"></i> {{ $selectedCount }} selected
                            </span>
                            @endif
                        </td>
                        <td>
                            @if($timeline->is_generated)
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Generated</span>
                            @else
                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                            @endif
                        </td>
                        <td class="text-xs text-gray-500">{{ $timeline->created_at->format('M d, Y') }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                @if($timeline->is_generated && $timeline->generated_pdf_path)
                                <a href="{{ Storage::url($timeline->generated_pdf_path) }}" target="_blank"
                                   class="btn btn-outline-success" title="Download PDF">
                                    <i class="bi bi-download"></i>
                                </a>
                                @else
                                <button class="btn btn-outline-secondary" disabled title="Not yet generated">
                                    <i class="bi bi-hourglass"></i>
                                </button>
                                @endif
                                <button class="btn btn-outline-primary"
                                        onclick="viewTimeline({{ $timeline->id }}, '{{ addslashes($timeline->title) }}', '{{ $timeline->user->name ?? '' }}', '{{ $timeline->booking->reference_no ?? '' }}')"
                                        title="View Details">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="bi bi-camera text-5xl text-gray-300 block mb-3"></i>
                                <h5 class="text-gray-500 font-semibold">No Memory Timelines Yet</h5>
                                <p class="text-gray-400 text-sm">Guests can create memory scrapbooks after their visit through the tourist mobile app.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- GRID VIEW --}}
    <div id="gridView" class="p-4" style="display:none;">
        @if($timelines->isEmpty())
        <div class="empty-state">
            <i class="bi bi-camera text-5xl text-gray-300 block mb-3"></i>
            <h5 class="text-gray-500 font-semibold">No Memory Timelines Yet</h5>
            <p class="text-gray-400 text-sm">Guest scrapbooks will appear here after their resort visit.</p>
        </div>
        @else
        <div class="row g-4">
            @foreach($timelines as $timeline)
            <div class="col-md-6 col-lg-4">
                <div class="timeline-card bg-white">
                    <div class="timeline-header">
                        <h6 class="font-bold text-white mb-1 text-truncate">{{ $timeline->title }}</h6>
                        <p class="text-white/70 text-xs mb-0">{{ $timeline->user->name ?? 'Guest' }}</p>
                    </div>
                    <div class="p-4">
                        {{-- Photo Strip Placeholder --}}
                        <div class="photo-strip mb-3">
                            @php $colors = ['#0ea5e9','#14b8a6','#8b5cf6','#f59e0b','#ec4899']; @endphp
                            @for($i = 0; $i < 5; $i++)
                            <div class="photo-strip-item" style="background:{{ $colors[$i % 5] }};">
                                <i class="bi bi-image"></i>
                            </div>
                            @endfor
                        </div>

                        <div class="flex items-center gap-2 mb-3">
                            <span class="stat-chip"><i class="bi bi-bookmark-fill"></i> {{ $timeline->booking->reference_no ?? '—' }}</span>
                            <span class="stat-chip"><i class="bi bi-calendar3"></i> {{ $timeline->created_at->format('M d') }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            @if($timeline->is_generated)
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Generated</span>
                            @else
                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                            @endif

                            <div class="btn-group btn-group-sm">
                                @if($timeline->is_generated && $timeline->generated_pdf_path)
                                <a href="{{ Storage::url($timeline->generated_pdf_path) }}" target="_blank"
                                   class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-download me-1"></i>PDF
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <div class="p-4 border-t">{{ $timelines->links() }}</div>
</div>

{{-- Timeline Detail Modal --}}
<div class="modal fade" id="timelineModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-xl">
            <div class="modal-header bg-gradient-to-r from-sky-500 to-teal-500 text-white border-0">
                <h5 class="modal-title"><i class="bi bi-camera-fill me-2"></i><span id="modalTitle">Memory Timeline</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-5">
                <div id="timelineModalContent" class="text-center">
                    <div class="spinner-border text-sky-500" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function switchView(view) {
    document.getElementById('listView').style.display = view === 'list' ? 'block' : 'none';
    document.getElementById('gridView').style.display = view === 'grid' ? 'block' : 'none';
    document.getElementById('listViewBtn').classList.toggle('active', view === 'list');
    document.getElementById('gridViewBtn').classList.toggle('active', view === 'grid');
}

function viewTimeline(id, title, guest, ref) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('timelineModalContent').innerHTML = `
        <div class="row g-3 text-start">
            <div class="col-md-6">
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Guest</p>
                    <p class="font-semibold text-gray-800 mb-0">${guest || '—'}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Booking Ref</p>
                    <p class="font-semibold font-mono text-sky-600 mb-0">${ref || '—'}</p>
                </div>
            </div>
            <div class="col-12">
                <div class="bg-sky-50 border border-sky-200 rounded-lg p-4 text-center">
                    <i class="bi bi-camera-fill text-sky-400 text-3xl mb-2 block"></i>
                    <p class="text-sky-700 text-sm mb-0">Memory timeline items are managed through the tourist mobile app. Guests can upload, select, and generate their photo scrapbook from there.</p>
                </div>
            </div>
        </div>
    `;
    new bootstrap.Modal(document.getElementById('timelineModal')).show();
}
</script>
@endpush