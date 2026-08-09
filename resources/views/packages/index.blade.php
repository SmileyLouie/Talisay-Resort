{{-- Extends the primary layout template designed for dashboard panels --}}
@extends('layouts.app')

{{-- Sets the HTML document title for the packages management page --}}
@section('title', 'Packages - Talisay Smart Tourism')

{{-- Defines the main content area section of the template --}}
@section('content')

{{-- Header layout block containing page title, subtitle, and primary add package action button --}}
<div class="mb-6 flex items-center justify-between">
    <div>
        {{-- Principal title heading for packages section --}}
        <h1 class="text-2xl font-bold text-gray-800">Packages Management</h1>
        {{-- Small help description text --}}
        <p class="text-gray-500 text-sm">Manage resort packages, pricing, and availability slots</p>
    </div>
    {{-- Button triggering the bootstrap modal to create a new package --}}
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#packageModal" onclick="openPackageForm()">
        {{-- Icon within the add package button --}}
        <i class="bi bi-plus-circle me-1"></i> Add Package
    </button>
</div>

{{-- Main data listing card container --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    {{-- Header inside card offering list search filtering --}}
    <div class="p-4 border-b flex items-center justify-between">
        {{-- Input text field triggering client-side table filter script --}}
        <input type="text" id="packageSearch" placeholder="Search packages by name..." class="form-control form-control-sm w-64" onkeyup="filterPackages()">
    </div>
    {{-- Table wrapper ensuring responsiveness --}}
    <div class="table-responsive">
        {{-- Dynamic data list table --}}
        <table class="table table-hover text-sm mb-0">
            {{-- Header columns --}}
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Max Capacity</th>
                    <th>Visibility</th>
                    <th>Actions</th>
                </tr>
            </thead>
            {{-- Table body tracking entries --}}
            <tbody id="packageTableBody">
                {{-- Loop through each package record --}}
                @foreach($packages as $package)
                {{-- Data row container matching key parameters --}}
                <tr class="package-row">
                    {{-- Display name cell --}}
                    <td class="font-semibold package-name">{{ $package->name }}</td>
                    {{-- Formatted currency price --}}
                    <td>PHP {{ number_format($package->price, 2) }}</td>
                    {{-- Capacity limit cell --}}
                    <td>{{ $package->max_capacity }} guests</td>
                    {{-- Dynamic badge label indicating package visibility --}}
                    <td>
                        <span class="badge bg-{{ $package->is_visible ? 'success' : 'secondary' }}">
                            {{ $package->is_visible ? 'Visible' : 'Hidden' }}
                        </span>
                    </td>
                    {{-- Actions panel button group --}}
                    <td>
                        <div class="btn-group btn-group-sm">
                            {{-- Edit package action button mapping data attributes to modal populator --}}
                            <button class="btn btn-outline-primary" 
                                    data-id="{{ $package->id }}"
                                    data-name="{{ $package->name }}"
                                    data-price="{{ $package->price }}"
                                    data-capacity="{{ $package->max_capacity }}"
                                    data-description="{{ $package->description }}"
                                    onclick="editPackage(this)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            {{-- Post form trigger to toggle package visibility flag state --}}
                            <form method="POST" action="{{ route('packages.toggle-visibility', $package) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary" title="Toggle visibility">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </form>
                            {{-- Delete form trigger to drop package --}}
                            <form method="POST" action="{{ route('packages.destroy', $package) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this package?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{-- Laravel standard paginator links render cell --}}
    <div class="p-4">{{ $packages->links() }}</div>
</div>

{{-- Standard modal markup defining the Package Add/Edit form layout --}}
<div class="modal fade" id="packageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            {{-- Modal title header area --}}
            <div class="modal-header bg-sky-500 text-white">
                <h5 class="modal-title" id="packageModalTitle">Add Package</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{-- The post request action form --}}
            <form id="packageForm" method="POST" action="{{ route('packages.store') }}">
                @csrf
                {{-- Form method spoofing placeholder used during updates --}}
                <div id="methodPlaceholder"></div>
                
                <div class="modal-body">
                    {{-- Grid spacing inside body --}}
                    <div class="row g-3">
                        {{-- Name input field --}}
                        <div class="col-md-8">
                            <label class="form-label font-medium text-sm">Package Name</label>
                            <input type="text" name="name" id="pkgName" class="form-control" required>
                        </div>
                        {{-- Price input field --}}
                        <div class="col-md-4">
                            <label class="form-label font-medium text-sm">Price (PHP)</label>
                            <input type="number" name="price" id="pkgPrice" class="form-control" step="0.01" min="0" required>
                        </div>
                        {{-- Description textarea field --}}
                        <div class="col-12">
                            <label class="form-label font-medium text-sm">Description</label>
                            <textarea name="description" id="pkgDescription" class="form-control" rows="3" required></textarea>
                        </div>
                        {{-- Max capacity input field --}}
                        <div class="col-md-6">
                            <label class="form-label font-medium text-sm">Max Capacity (guests)</label>
                            <input type="number" name="max_capacity" id="pkgCapacity" class="form-control" min="1" required>
                        </div>
                        {{-- Default check for visibility toggle --}}
                        <div class="col-md-6 flex items-end pb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_visible" value="1" id="pkgVisible" checked>
                                <label class="form-check-label text-sm" for="pkgVisible">Make Visible immediately</label>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Footer modal action controls --}}
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="pkgSubmitBtn">Save Package</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

{{-- Script push section to manage modal triggers --}}
@push('scripts')
<script>
// Open and clear package modal form inputs for a new entry
function openPackageForm() {
    // Set header text to creation mode
    document.getElementById('packageModalTitle').innerText = 'Add Package';
    // Clean target action to creation endpoint route
    document.getElementById('packageForm').action = "{{ route('packages.store') }}";
    // Clear method override placeholder
    document.getElementById('methodPlaceholder').innerHTML = '';
    // Reset all form inputs to clean default values
    document.getElementById('packageForm').reset();
}

// Populate and configuration package modal form values during updates
function editPackage(button) {
    // Set header text to editing mode
    document.getElementById('packageModalTitle').innerText = 'Edit Package';
    // Retrieve dataset variables mapped directly onto button element
    const id = button.getAttribute('data-id');
    const name = button.getAttribute('data-name');
    const price = button.getAttribute('data-price');
    const capacity = button.getAttribute('data-capacity');
    const description = button.getAttribute('data-description');

    // Configure update action endpoint route pointing to target record ID
    document.getElementById('packageForm').action = `/admin/packages/${id}`;
    // Insert method override spoofing input to trigger Laravel update controllers
    document.getElementById('methodPlaceholder').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    
    // Fill specific text parameters inside inputs
    document.getElementById('pkgName').value = name;
    document.getElementById('pkgPrice').value = price;
    document.getElementById('pkgCapacity').value = capacity;
    document.getElementById('pkgDescription').value = description;

    // Show modal manually using Bootstrap's javascript triggers
    const packageModalObj = new bootstrap.Modal(document.getElementById('packageModal'));
    packageModalObj.show();
}

// Search filter implementation matching table list items dynamically
function filterPackages() {
    // Get typed query string
    const query = document.getElementById('packageSearch').value.toLowerCase();
    // Fetch all listing row elements
    const rows = document.querySelectorAll('.package-row');
    // Loop rows matching text queries
    rows.forEach(row => {
        // Retrieve internal text name content
        const nameText = row.querySelector('.package-name').innerText.toLowerCase();
        // Toggle visibility state based on matching result index
        row.style.display = nameText.includes(query) ? '' : 'none';
    });
}
</script>
@endpush
