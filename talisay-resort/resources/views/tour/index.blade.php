@extends('layouts.app')

@section('title', 'Tour Assets - Talisay Smart Tourism')

@section('content')

@php
    $addOld = old('_form') === 'add_scene';
    $editOld = old('_form') === 'edit_scene';
    $typeLabel = fn ($asset) => $asset->type === 'video' ? 'Video' : ($asset->isPanorama() ? '360° photo' : 'Flat photo');
@endphp

<div class="res-head">
    <div>
        <h1>{{ __('360° Virtual Tour') }}</h1>
        <p>{{ __('Upload 360° photos and tour videos. Guests see the scenes marked as shown, in the order you set.') }}</p>
    </div>
    <div class="res-head-actions">
        <a href="{{ route('tour.viewer') }}" target="_blank" rel="noopener" class="btn-secondary-clean">
            <i class="bi bi-box-arrow-up-right"></i>
            <span>{{ __('Preview tour') }}</span>
        </a>
        <button type="button" class="btn-ocean" onclick="openAddTourModal()">
            <i class="bi bi-plus-lg"></i>
            <span>{{ __('Add scene') }}</span>
        </button>
    </div>
</div>

<div class="unit-stats">
    <div>
        <span>{{ __('Scenes') }}</span>
        <strong>{{ $tourStats['total'] }}</strong>
        <em>{{ __('In the tour list') }}</em>
    </div>
    <div>
        <span>{{ __('Shown to guests') }}</span>
        <strong class="{{ $tourStats['shown'] ? 'is-good' : '' }}">{{ $tourStats['shown'] }}</strong>
        <em>{{ __('Visible in the tour') }}</em>
    </div>
    <div>
        <span>{{ __('Hidden') }}</span>
        <strong>{{ $tourStats['hidden'] }}</strong>
        <em>{{ __('Not shown to guests') }}</em>
    </div>
    <div>
        <span>{{ __('Photos') }}</span>
        <strong class="{{ $tourStats['flat'] ? 'is-warn' : '' }}">{{ $tourStats['photos'] }}</strong>
        <em>{{ $tourStats['flat'] ? $tourStats['flat'] . ' ' . __('shown') : __('All open in 360°') }}</em>
    </div>
    <div>
        <span>{{ __('Videos') }}</span>
        <strong>{{ $tourStats['videos'] }}</strong>
        <em>{{ __('Play in Video tour') }}</em>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="tour-list-head">
        <div>
            <h2>{{ __('Scenes') }}</h2>
            <p>{{ __('Guests move through the scenes from the lowest order number to the highest.') }}</p>
        </div>
        <span class="unit-count">{{ $assets->total() }} {{ $assets->total() === 1 ? __('scene') : __('scenes') }}</span>
    </div>

    <div class="table-responsive">
        <table class="table-clean unit-table tour-table">
            <thead>
                <tr>
                    <th>{{ __('Order') }}</th>
                    <th>{{ __('Scene') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($assets as $asset)
                @php $isFlat = $asset->type === 'image' && ! $asset->isPanorama(); @endphp
                <tr class="{{ $asset->is_active ? '' : 'tour-row-hidden' }}">
                    <td><span class="tour-order">{{ $asset->sort_order }}</span></td>
                    <td>
                        <div class="unit-cell">
                            <span class="tour-thumb">
                                @if($asset->type === 'video' && $asset->mediaUrl())
                                    <video src="{{ $asset->mediaUrl() }}#t=1" muted playsinline preload="metadata" aria-hidden="true"></video>
                                    <i class="bi bi-play-fill tour-thumb-play"></i>
                                @elseif($asset->mediaUrl())
                                    <img src="{{ $asset->mediaUrl() }}" alt="" loading="lazy" onerror="this.outerHTML = '<i class=&quot;bi bi-image&quot;></i>'">
                                @else
                                    <i class="bi bi-image"></i>
                                @endif
                            </span>
                            <div class="min-w-0">
                                <div class="unit-name">{{ $asset->title }}</div>
                                <div class="tour-desc">{{ $asset->description ?: 'No description' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="tour-type {{ $asset->type === 'video' ? 'is-video' : ($isFlat ? 'is-flat' : 'is-pano') }}">
                            <i class="bi bi-{{ $asset->type === 'video' ? 'play-circle' : ($isFlat ? 'image' : 'badge-vr') }}"></i>
                            {{ __($typeLabel($asset)) }}
                        </span>
                        <div class="res-note" title="{{ $asset->panorama_path }}">
                            @if($isFlat)
                                Not 2:1, so guests can't look around
                            @else
                                {{ \Illuminate\Support\Str::limit(basename((string) $asset->panorama_path), 28) }}
                            @endif
                        </div>
                    </td>
                    <td>
                        @if($asset->is_active)
                            <span class="res-status res-status-paid">{{ __('Shown') }}</span>
                        @else
                            <span class="res-status res-status-checked_out">{{ __('Hidden') }}</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="unit-actions">
                            <button type="button" class="res-icon-btn"
                                    data-id="{{ $asset->id }}"
                                    data-title="{{ $asset->title }}"
                                    data-description="{{ $asset->description }}"
                                    data-type="{{ $asset->type }}"
                                    data-order="{{ $asset->sort_order }}"
                                    data-active="{{ $asset->is_active ? '1' : '0' }}"
                                    data-file="{{ basename($asset->panorama_path ?? '') }}"
                                    onclick="openEditTourModal(this)"
                                    title="Edit scene" aria-label="Edit {{ $asset->title }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" action="{{ route('tour.manage.destroy', $asset) }}" class="m-0" onsubmit="return confirm('Delete this scene? Its file is removed too.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="res-icon-btn unit-delete" title="Delete scene" aria-label="Delete {{ $asset->title }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-12 text-slate-400">
                        <i class="bi bi-badge-vr text-3xl block mb-2 text-slate-300"></i>
                        <p class="text-sm mb-0">No scenes yet. Add a 360° photo or a tour video to start the virtual tour.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($assets->hasPages())
    <div class="p-4 border-t border-slate-100 flex justify-end">
        {{ $assets->links() }}
    </div>
    @endif
</div>

{{-- Add scene --}}
<div class="modal fade" id="tourAssetModal" tabindex="-1" aria-hidden="true" aria-labelledby="tourAssetModalTitle" @if($addOld && $errors->any()) data-reopen="1" @endif>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content manual-modal">
            <div class="manual-head">
                <div>
                    <h5 id="tourAssetModalTitle">Add scene</h5>
                    <p>Upload a 360° photo or a tour video for guests to explore.</p>
                </div>
                <button type="button" class="manual-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('tour.manage.store') }}" enctype="multipart/form-data" data-loading>
                @csrf
                <input type="hidden" name="_form" value="add_scene">
                <div class="manual-body">
                    @if($addOld && $errors->any())
                    <div class="unit-modal-error">{{ $errors->first() }}</div>
                    @endif
                    <div class="manual-row">
                        <label for="addTourTitle">Title</label>
                        <input type="text" name="title" id="addTourTitle" class="form-control-clean w-100" placeholder="e.g. Beachfront cottages" value="{{ $addOld ? old('title') : '' }}" maxlength="255" required>
                    </div>
                    <div class="manual-row manual-row-top">
                        <label for="addTourDescription">Description</label>
                        <textarea name="description" id="addTourDescription" rows="2" class="form-control-clean w-100" placeholder="What guests see in this scene (optional)">{{ $addOld ? old('description') : '' }}</textarea>
                    </div>
                    <div class="manual-row">
                        <label for="addTourType">Type</label>
                        <div class="manual-split">
                            <select name="type" id="addTourType" class="form-select-clean w-100" data-file-input="addTourFile" data-file-hint="addTourFileHint" required>
                                <option value="image" @selected($addOld && old('type') === 'image')>360° photo</option>
                                <option value="video" @selected($addOld && old('type') === 'video')>Video</option>
                            </select>
                            <div class="tour-order-field">
                                <span>Order</span>
                                <input type="number" name="sort_order" class="form-control-clean w-100" value="{{ $addOld ? old('sort_order') : ($tourStats['total'] + 1) }}" min="0" required aria-label="Order">
                            </div>
                        </div>
                    </div>
                    <div class="manual-row manual-row-top">
                        <label for="addTourFile">File</label>
                        <div>
                            <input type="file" name="panorama" id="addTourFile" class="form-control-clean w-100" accept="image/jpeg,image/png,image/webp" required>
                            <span class="manual-hint" id="addTourFileHint"></span>
                        </div>
                    </div>
                </div>
                <div class="manual-foot">
                    <button type="button" class="btn-secondary-clean" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-ocean"><i class="bi bi-cloud-arrow-up"></i> Upload scene</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit scene --}}
<div class="modal fade" id="editTourAssetModal" tabindex="-1" aria-hidden="true" aria-labelledby="editTourAssetModalTitle">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content manual-modal">
            <div class="manual-head">
                <div>
                    <h5 id="editTourAssetModalTitle">Edit scene</h5>
                    <p>Change the details, replace the file, or hide the scene from guests.</p>
                </div>
                <button type="button" class="manual-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <form id="editTourAssetForm" method="POST" action="" enctype="multipart/form-data" data-loading>
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit_scene">
                <input type="hidden" name="_asset_id" id="editTourAssetId" value="">
                <div class="manual-body">
                    @if($editOld && $errors->any())
                    <div class="unit-modal-error">{{ $errors->first() }}</div>
                    @endif
                    <div class="manual-row">
                        <label for="editTourTitle">Title</label>
                        <input type="text" name="title" id="editTourTitle" class="form-control-clean w-100" maxlength="255" required>
                    </div>
                    <div class="manual-row manual-row-top">
                        <label for="editTourDescription">Description</label>
                        <textarea name="description" id="editTourDescription" rows="2" class="form-control-clean w-100" placeholder="What guests see in this scene (optional)"></textarea>
                    </div>
                    <div class="manual-row">
                        <label for="editTourType">Type</label>
                        <div class="manual-split">
                            <select name="type" id="editTourType" class="form-select-clean w-100" data-file-input="editTourFile" data-file-hint="editTourFileHint" required>
                                <option value="image">360° photo</option>
                                <option value="video">Video</option>
                            </select>
                            <div class="tour-order-field">
                                <span>Order</span>
                                <input type="number" name="sort_order" id="editTourOrder" class="form-control-clean w-100" min="0" required aria-label="Order">
                            </div>
                        </div>
                    </div>
                    <div class="manual-row">
                        <label for="editTourActive">Status</label>
                        <select name="is_active" id="editTourActive" class="form-select-clean w-100">
                            <option value="1">{{ __('Shown to guests') }}</option>
                            <option value="0">{{ __('Hidden from guests') }}</option>
                        </select>
                    </div>
                    <div class="manual-row manual-row-top">
                        <label for="editTourFile">Replace file</label>
                        <div>
                            <div class="tour-current-file"><i class="bi bi-paperclip"></i> <span id="editTourCurrentFile"></span></div>
                            <input type="file" name="panorama" id="editTourFile" class="form-control-clean w-100" accept="image/jpeg,image/png,image/webp">
                            <span class="manual-hint" id="editTourFileHint"></span>
                        </div>
                    </div>
                </div>
                <div class="manual-foot">
                    <button type="button" class="btn-secondary-clean" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-ocean"><i class="bi bi-check-lg"></i> Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const tourFileRules = {
    image: {
        accept: 'image/jpeg,image/png,image/webp',
        hint: 'JPG, PNG, or WebP up to 50 MB. Use a 2:1 panorama (for example 4000 × 2000) so guests can look around; other sizes show as a flat photo.'
    },
    video: {
        accept: 'video/mp4,video/webm,video/ogg',
        hint: 'MP4, WebM, or OGV up to 50 MB. Videos play in the Video tour tab.'
    }
};

function syncTourFileRules(select) {
    const rule = tourFileRules[select.value] || tourFileRules.image;
    const input = document.getElementById(select.dataset.fileInput);
    const hint = document.getElementById(select.dataset.fileHint);
    if (input) input.accept = rule.accept;
    if (hint) hint.textContent = rule.hint;
}

document.querySelectorAll('[data-file-input]').forEach(function (select) {
    syncTourFileRules(select);
    select.addEventListener('change', function () { syncTourFileRules(select); });
});

function openAddTourModal() {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('tourAssetModal')).show();
}

function openEditTourModal(btn) {
    const id = btn.getAttribute('data-id');
    document.getElementById('editTourAssetForm').action = `/tour-manage/${id}`;
    document.getElementById('editTourAssetId').value = id;
    document.getElementById('editTourTitle').value = btn.getAttribute('data-title');
    document.getElementById('editTourDescription').value = btn.getAttribute('data-description') || '';
    document.getElementById('editTourType').value = btn.getAttribute('data-type');
    document.getElementById('editTourOrder').value = btn.getAttribute('data-order');
    document.getElementById('editTourActive').value = btn.getAttribute('data-active');
    document.getElementById('editTourCurrentFile').textContent = btn.getAttribute('data-file') || 'None';
    document.getElementById('editTourFile').value = '';
    syncTourFileRules(document.getElementById('editTourType'));
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editTourAssetModal')).show();
}

document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('tourAssetModal').dataset.reopen === '1') {
        openAddTourModal();
    }
    @if($editOld && $errors->any())
    const failedEdit = document.querySelector('[data-id="{{ (int) old('_asset_id') }}"]');
    if (failedEdit) {
        openEditTourModal(failedEdit);
        const typed = {{ Js::from(collect(old())->only(['title', 'description', 'type', 'sort_order', 'is_active'])) }};
        document.getElementById('editTourTitle').value = typed.title ?? '';
        document.getElementById('editTourDescription').value = typed.description ?? '';
        document.getElementById('editTourType').value = typed.type ?? 'image';
        document.getElementById('editTourOrder').value = typed.sort_order ?? '';
        document.getElementById('editTourActive').value = typed.is_active ?? '1';
        syncTourFileRules(document.getElementById('editTourType'));
    }
    @endif
});
</script>
@endpush

@endsection
