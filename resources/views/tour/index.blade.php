{{-- Extends the primary layout template designed for dashboard panels --}}
@extends('layouts.app')

{{-- Sets the HTML document title for the tour asset management page --}}
@section('title', 'Tour Assets - Talisay Smart Tourism')

{{-- Defines the main content area section of the template --}}
@section('content')

{{-- Header layout block containing page title, subtitle, and primary upload action button --}}
<div class="mb-6 flex items-center justify-between">
    <div>
        {{-- Principal title heading for tour assets --}}
        <h1 class="text-2xl font-bold text-gray-800">Tour Assets Management</h1>
        {{-- Small help description text --}}
        <p class="text-gray-500 text-sm">Upload and manage equirectangular panoramic image files used in the 360 virtual tour viewer</p>
    </div>
    {{-- Button triggering the bootstrap modal to upload a new asset --}}
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tourAssetModal">
        {{-- Icon within the upload button --}}
        <i class="bi bi-plus-circle me-1"></i> Upload Asset
    </button>
</div>

{{-- Main data listing card container --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    {{-- Table wrapper ensuring responsiveness --}}
    <div class="table-responsive">
        {{-- Dynamic data list table --}}
        <table class="table table-hover text-sm mb-0">
            {{-- Header columns --}}
            <thead class="table-light">
                <tr>
                    <th>Preview Type</th>
                    <th>Scene Title</th>
                    <th>Asset Type</th>
                    <th>Sequence Order</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            {{-- Table body tracking entries --}}
            <tbody>
                {{-- Loop through each tour asset record --}}
                @foreach($assets as $asset)
                <tr>
                    {{-- Graphic media preview type cell icon --}}
                    <td>
                        <div class="w-16 h-10 rounded bg-sky-100 flex items-center justify-center">
                            <i class="bi bi-{{ $asset->type === 'video' ? 'play-circle' : 'image' }} text-sky-500 text-lg"></i>
                        </div>
                    </td>
                    {{-- Display title cell --}}
                    <td class="font-semibold">{{ $asset->title }}</td>
                    {{-- Media type label --}}
                    <td><span class="badge bg-light text-dark">{{ ucfirst($asset->type) }}</span></td>
                    {{-- Sort sequence order cell --}}
                    <td>{{ $asset->sort_order }}</td>
                    {{-- Active status badge display --}}
                    <td>
                        <span class="badge bg-{{ $asset->is_active ? 'success' : 'secondary' }}">{{ $asset->is_active ? 'Active' : 'Inactive' }}</span>
                    </td>
                    {{-- Delete action controls --}}
                    <td>
                        {{-- Delete form trigger pointing to target record ID --}}
                        <form method="POST" action="{{ route('tour.manage.destroy', $asset) }}" onsubmit="return confirm('Are you sure you want to delete this scene asset?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
                {{-- Empty state row display feedback --}}
                @if($assets->isEmpty())
                <tr>
                    <td colspan="6" class="text-center text-gray-400 py-8">No 360-degree tour scenes uploaded yet.</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    {{-- Laravel standard paginator links --}}
    <div class="p-4">{{ $assets->links() }}</div>
</div>

{{-- Standard modal markup defining the Tour Asset Upload form layout --}}
<div class="modal fade" id="tourAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            {{-- Modal title header area --}}
            <div class="modal-header bg-sky-500 text-white">
                <h5 class="modal-title">Upload Tour Asset</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{-- The post request upload action form --}}
            <form method="POST" action="{{ route('tour.manage.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    {{-- Title input field --}}
                    <div class="mb-3">
                        <label class="form-label font-medium text-sm">Scene Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Main Entrance" required>
                    </div>
                    {{-- Description textarea field --}}
                    <div class="mb-3">
                        <label class="form-label font-medium text-sm">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Describe the scene viewpoint details..."></textarea>
                    </div>
                    {{-- File upload input field --}}
                    <div class="mb-3">
                        <label class="form-label font-medium text-sm">Panorama Image File</label>
                        <input type="file" name="panorama" class="form-control" accept="image/*" required>
                        <small class="text-muted text-xs">Please upload a 360 equirectangular image file (max 10MB)</small>
                    </div>
                    {{-- Media type selector --}}
                    <div class="mb-3">
                        <label class="form-label font-medium text-sm">Type</label>
                        <select name="type" class="form-select">
                            <option value="image">Image (360 Photo)</option>
                            <option value="video">Video (360 Video)</option>
                        </select>
                    </div>
                    {{-- Sort sequence order input field --}}
                    <div class="mb-3">
                        <label class="form-label font-medium text-sm">Sequence Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="1" min="1" required>
                    </div>
                </div>
                {{-- Footer modal action controls --}}
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection