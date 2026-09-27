@extends('layouts.app')

@section('title', 'Tour Assets - Talisay Smart Tourism')

@section('content')

{{-- Header --}}
<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100/80 text-sky-800 border border-sky-200">
                <i class="bi bi-camera-video-fill text-[10px] text-sky-600"></i>
                Virtual Experience
            </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-1">
            360° Virtual Tour Scenes
        </h1>
        <p class="text-sm text-slate-500 mb-0">
            Upload and manage panoramic image scenes and video assets used in the interactive 360° virtual tour viewer.
        </p>
    </div>

    <div class="flex items-center gap-2">
        <a href="{{ route('tour.viewer') }}" target="_blank" class="btn-secondary-clean text-xs">
            <i class="bi bi-badge-vr text-sky-500"></i>
            <span>Preview Virtual Tour</span>
        </a>
        <button class="btn-ocean text-xs" data-bs-toggle="modal" data-bs-target="#tourAssetModal">
            <i class="bi bi-cloud-arrow-up-fill"></i>
            <span>Upload Scene Asset</span>
        </button>
    </div>
</div>

{{-- Assets Table Card --}}
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
        <h3 class="font-bold text-sm text-slate-800 mb-0 flex items-center gap-2">
            <i class="bi bi-collection-play text-sky-600"></i>
            <span>Configured Tour Scenes ({{ $assets->total() }})</span>
        </h3>
        <span class="text-xs text-slate-400">Page {{ $assets->currentPage() }} of {{ $assets->lastPage() }}</span>
    </div>

    <div class="table-responsive">
        <table class="table-clean">
            <thead>
                <tr>
                    <th>Scene Type</th>
                    <th>Scene Title</th>
                    <th>Media Type</th>
                    <th>Order</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assets as $asset)
                <tr>
                    <td>
                        <div class="w-12 h-10 rounded-xl bg-sky-50 text-sky-600 border border-sky-100 flex items-center justify-center text-lg shadow-sm">
                            <i class="bi bi-{{ $asset->type === 'video' ? 'play-circle-fill' : 'image-fill' }}"></i>
                        </div>
                    </td>
                    <td>
                        <div class="font-extrabold text-slate-900 text-xs">{{ $asset->title }}</div>
                        <div class="text-[11px] text-slate-400">{{ $asset->panorama_path ?? 'Scene file' }}</div>
                    </td>
                    <td>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-700 border border-slate-200 uppercase">
                            {{ ucfirst($asset->type) }}
                        </span>
                    </td>
                    <td>
                        <span class="font-mono text-xs font-bold text-slate-700">#{{ $asset->sort_order }}</span>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            {{-- Edit button --}}
                            <button type="button"
                                    class="w-9 h-9 inline-flex items-center justify-center rounded-xl text-sky-500 bg-sky-50 hover:bg-sky-100 border border-sky-100 transition shadow-sm"
                                    data-id="{{ $asset->id }}"
                                    data-title="{{ $asset->title }}"
                                    data-description="{{ $asset->description }}"
                                    data-type="{{ $asset->type }}"
                                    data-order="{{ $asset->sort_order }}"
                                    data-active="{{ $asset->is_active ? '1' : '0' }}"
                                    data-file="{{ basename($asset->panorama_path ?? '') }}"
                                    onclick="openEditTourModal(this)"
                                    title="Edit Scene Asset">
                                <i class="bi bi-pencil-fill text-sm"></i>
                            </button>
                            {{-- Delete button --}}
                            <form method="POST" action="{{ route('tour.manage.destroy', $asset) }}" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this scene asset?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-9 h-9 inline-flex items-center justify-center rounded-xl text-rose-500 bg-rose-50 hover:bg-rose-100 border border-rose-100 transition shadow-sm"
                                        title="Delete Scene">
                                    <i class="bi bi-trash-fill text-sm"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-12 text-slate-400">
                        <i class="bi bi-camera-video text-3xl block mb-2 text-slate-300"></i>
                        <p class="text-xs font-semibold mb-0">No 360-degree tour scenes uploaded yet.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($assets->hasPages())
    <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex justify-end">
        {{ $assets->links() }}
    </div>
    @endif
</div>

{{-- Upload Tour Asset Modal --}}
<div class="modal fade" id="tourAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden">
            <div class="p-5 bg-gradient-to-r from-slate-900 to-sky-950 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-sky-300 text-lg">
                        <i class="bi bi-cloud-arrow-up-fill"></i>
                    </div>
                    <div>
                        <h5 class="font-extrabold text-base mb-0 tracking-tight">Upload 360° Scene Asset</h5>
                        <p class="text-xs text-sky-200/70 mb-0 font-medium">Add high-resolution panorama or video tour scene</p>
                    </div>
                </div>
                <button type="button" class="text-white/70 hover:text-white w-8 h-8 rounded-lg flex items-center justify-center hover:bg-white/10 transition" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('tour.manage.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-6 bg-slate-50/50 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Scene Title</label>
                        <input type="text" name="title" class="w-full form-control-clean text-xs" placeholder="Scene title" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Description (Optional)</label>
                        <textarea name="description" rows="2" class="w-full form-control-clean text-xs" placeholder="Brief description of this tour viewpoint..."></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Asset Type</label>
                            <select name="type" class="w-full form-select-clean text-xs font-medium" required>
                                <option value="image">360° Photo (Equirectangular)</option>
                                <option value="video">360° Video</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Sort Order</label>
                            <input type="number" name="sort_order" class="w-full form-control-clean text-xs font-mono" value="0" min="0" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Upload Panorama File (JPG / PNG)</label>
                        <input type="file" name="panorama" class="w-full form-control-clean text-xs" accept="image/*,video/*" required>
                        <span class="text-[10px] text-slate-400 mt-1 block">Equirectangular 2:1 panoramic format recommended (up to 10MB)</span>
                    </div>
                </div>

                <div class="p-4 bg-white border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" class="btn-secondary-clean text-xs px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-ocean text-xs px-5 py-2">Upload Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Tour Asset Modal --}}
<div class="modal fade" id="editTourAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden">
            <div class="p-5 bg-gradient-to-r from-slate-900 to-sky-950 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-sky-300 text-lg">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div>
                        <h5 class="font-extrabold text-base mb-0 tracking-tight">Edit 360° Scene Asset</h5>
                        <p class="text-xs text-sky-200/70 mb-0 font-medium">Update scene title, media file, order, or status</p>
                    </div>
                </div>
                <button type="button" class="text-white/70 hover:text-white w-8 h-8 rounded-lg flex items-center justify-center hover:bg-white/10 transition" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form id="editTourAssetForm" method="POST" action="" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body p-6 bg-slate-50/50 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Scene Title <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" id="editTourTitle" class="w-full form-control-clean text-xs font-semibold" placeholder="Scene title" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Description (Optional)</label>
                        <textarea name="description" id="editTourDescription" rows="2" class="w-full form-control-clean text-xs" placeholder="Brief description of this tour viewpoint..."></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Asset Type <span class="text-rose-500">*</span></label>
                            <select name="type" id="editTourType" class="w-full form-select-clean text-xs font-medium" required>
                                <option value="image">360° Photo (Equirectangular)</option>
                                <option value="video">360° Video</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Sort Order <span class="text-rose-500">*</span></label>
                            <input type="number" name="sort_order" id="editTourOrder" class="w-full form-control-clean text-xs font-mono" min="0" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Status</label>
                        <select name="is_active" id="editTourActive" class="w-full form-select-clean text-xs font-medium">
                            <option value="1">Active (Visible in Virtual Tour)</option>
                            <option value="0">Inactive (Hidden from Guests)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Replace Panorama File (Optional)</label>
                        <input type="file" name="panorama" class="w-full form-control-clean text-xs" accept="image/*,video/*">
                        <div class="mt-1 flex items-center gap-1.5 text-[11px] text-slate-500">
                            <i class="bi bi-paperclip text-sky-600"></i>
                            <span>Current file: <strong id="editTourCurrentFile" class="font-mono text-slate-700"></strong></span>
                        </div>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Leave empty to keep the current file.</span>
                    </div>
                </div>

                <div class="p-4 bg-white border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" class="btn-secondary-clean text-xs px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-ocean text-xs px-5 py-2 flex items-center gap-1.5">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openEditTourModal(btn) {
    const id = btn.getAttribute('data-id');
    const title = btn.getAttribute('data-title');
    const description = btn.getAttribute('data-description') || '';
    const type = btn.getAttribute('data-type');
    const order = btn.getAttribute('data-order');
    const active = btn.getAttribute('data-active');
    const file = btn.getAttribute('data-file') || 'None';

    const form = document.getElementById('editTourAssetForm');
    form.action = `/tour-manage/${id}`;

    document.getElementById('editTourTitle').value = title;
    document.getElementById('editTourDescription').value = description;
    document.getElementById('editTourType').value = type;
    document.getElementById('editTourOrder').value = order;
    document.getElementById('editTourActive').value = active;
    document.getElementById('editTourCurrentFile').textContent = file;

    const modal = new bootstrap.Modal(document.getElementById('editTourAssetModal'));
    modal.show();
}
</script>
@endpush

@endsection