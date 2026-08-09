@extends('layouts.app')
@section('title', 'My Profile - Talisay Smart Tourism')

@push('styles')
<style>
    .avatar-wrapper {
        position: relative;
        width: 110px;
        height: 110px;
        margin: 0 auto 16px;
    }
    .avatar-img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #e0f2fe;
    }
    .avatar-placeholder {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: linear-gradient(135deg, #0ea5e9, #14b8a6);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 36px;
        border: 4px solid #e0f2fe;
    }
    .info-item {
        padding: 12px 16px;
        border-radius: 10px;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        margin-bottom: 8px;
    }
</style>
@endpush

@section('content')

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">My Profile</h1>
    <p class="text-gray-500 text-sm">Manage your personal account settings and security preferences</p>
</div>

<div class="row g-4">
    {{-- Left Profile Card --}}
    <div class="col-lg-4">
        <div class="bg-white rounded-xl shadow-sm p-6 text-center">
            <div class="avatar-wrapper">
                @if(auth()->user()->avatar)
                    <img src="{{ Storage::url(auth()->user()->avatar) }}" id="avatarPreview" class="avatar-img" alt="{{ auth()->user()->name }}">
                @else
                    <div class="avatar-placeholder" id="avatarPlaceholder">
                        {{ initials(auth()->user()->name) }}
                    </div>
                    <img id="avatarPreview" class="avatar-img" style="display:none;" alt="Preview">
                @endif
            </div>

            <h3 class="font-bold text-xl text-gray-800 mb-1">{{ auth()->user()->name }}</h3>
            <p class="text-sm text-gray-500 mb-3">{{ auth()->user()->email }}</p>

            @php
                $roleBadges = [
                    'admin' => ['bg' => 'bg-red-100 text-red-700', 'icon' => 'bi-shield-fill', 'label' => 'Administrator'],
                    'staff' => ['bg' => 'bg-teal-100 text-teal-700', 'icon' => 'bi-person-badge-fill', 'label' => 'Resort Staff'],
                    'tourist' => ['bg' => 'bg-blue-100 text-blue-700', 'icon' => 'bi-person-fill', 'label' => 'Tourist'],
                ];
                $b = $roleBadges[auth()->user()->role] ?? ['bg' => 'bg-gray-100 text-gray-700', 'icon' => 'bi-person', 'label' => ucfirst(auth()->user()->role)];
            @endphp

            <span class="badge {{ $b['bg'] }} px-3 py-2 text-xs font-semibold rounded-full mb-4">
                <i class="bi {{ $b['icon'] }} me-1"></i>{{ $b['label'] }}
            </span>

            <hr class="my-4">

            {{-- Account Information Summary --}}
            <div class="text-start">
                <div class="info-item">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Phone</p>
                    <p class="text-sm font-semibold text-gray-700 mb-0">{{ auth()->user()->phone ?? 'Not set' }}</p>
                </div>
                <div class="info-item">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Status</p>
                    <span class="badge bg-success text-xs">
                        <i class="bi bi-check-circle me-1"></i>Active Account
                    </span>
                </div>
                <div class="info-item">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Member Since</p>
                    <p class="text-sm font-semibold text-gray-700 mb-0">{{ auth()->user()->created_at->format('F d, Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Right Form Area --}}
    <div class="col-lg-8">
        {{-- Edit Details Card --}}
        <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
            <h3 class="font-bold text-lg text-gray-800 mb-4 pb-2 border-b">
                <i class="bi bi-person-lines-fill text-sky-500 me-2"></i>Personal Details
            </h3>

            @if($errors->any() && !$errors->has('current_password'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-medium text-sm">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', auth()->user()->name) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-medium text-sm">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', auth()->user()->email) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-medium text-sm">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" value="{{ old('phone', auth()->user()->phone) }}" placeholder="+63-9XX-XXX-XXXX">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-medium text-sm">Profile Photo</label>
                        <input type="file" name="avatar" class="form-control" accept="image/*" onchange="previewImage(this)">
                        <small class="text-muted text-xs">JPG, PNG or GIF (max 2MB)</small>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-primary px-5">
                        <i class="bi bi-check-lg me-1"></i>Update Profile
                    </button>
                </div>
            </form>
        </div>

        {{-- Security / Change Password Card --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-bold text-lg text-gray-800 mb-4 pb-2 border-b">
                <i class="bi bi-shield-lock-fill text-sky-500 me-2"></i>Security & Password
            </h3>

            @if($errors->has('current_password') || $errors->has('password'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach($errors->get('current_password') as $e) <li>{{ $e }}</li> @endforeach
                    @foreach($errors->get('password') as $e) <li>{{ $e }}</li> @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-medium text-sm">Current Password <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-medium text-sm">New Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Min. 8 characters" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-medium text-sm">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat new password" required>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-outline-sky px-5">
                        <i class="bi bi-key-fill me-1"></i>Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('avatarPreview');
            const placeholder = document.getElementById('avatarPlaceholder');
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush