@extends('layouts.app')

@section('title', 'My Profile - Talisay Smart Tourism')

@push('styles')
<style>
    .avatar-wrapper {
        position: relative;
        width: 110px;
        height: 110px;
        margin: 0 auto 10px;
    }
    .avatar-img {
        width: 100%;
        height: 100%;
        border-radius: 24px;
        object-fit: cover;
        border: 4px solid #e0f2fe;
        box-shadow: 0 4px 14px rgba(14, 165, 233, 0.15);
    }
    .avatar-placeholder {
        width: 100%;
        height: 100%;
        border-radius: 24px;
        background: linear-gradient(135deg, #0284c7, #0ea5e9);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 36px;
        border: 4px solid #e0f2fe;
        box-shadow: 0 4px 14px rgba(14, 165, 233, 0.2);
    }
    .btn-remove-avatar {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        font-weight: 700;
        color: #e11d48;
        background: #fff1f2;
        border: 1px solid #fecdd3;
        border-radius: 10px;
        padding: 5px 12px;
        cursor: pointer;
        transition: background 0.18s, color 0.18s;
        text-decoration: none;
    }
    .btn-remove-avatar:hover {
        background: #ffe4e6;
        color: #be123c;
    }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="mb-8">
    <div class="flex items-center gap-2 mb-1">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100/80 text-sky-800 border border-sky-200">
            <i class="bi bi-person-fill text-[10px] text-sky-600"></i>
            Account Profile
        </span>
    </div>
    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-1">
        My Profile Settings
    </h1>
    <p class="text-sm text-slate-500 mb-0">
        Manage your personal profile details, account avatar, and security passwords.
    </p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    
    {{-- Left Profile Summary Card --}}
    <div class="lg:col-span-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 text-center">

            {{-- Flash messages --}}
            @if(session('success'))
            <div class="mb-4 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-2">
                <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            </div>
            @endif

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

            {{-- Remove avatar button (only if user has an avatar) --}}
            @if(auth()->user()->avatar)
            <form method="POST" action="{{ route('profile.avatar.destroy') }}" class="mb-3"
                  onsubmit="return confirm('Remove your profile picture?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-remove-avatar">
                    <i class="bi bi-trash3-fill"></i>
                    Remove Photo
                </button>
            </form>
            @endif

            <h3 class="font-extrabold text-lg text-slate-900 mb-0.5">{{ auth()->user()->name }}</h3>
            <p class="text-xs text-slate-400 mb-3">{{ auth()->user()->email }}</p>

            @php
                $roleBadges = [
                    'admin'   => ['bg' => 'bg-rose-50 text-rose-700 border-rose-200', 'icon' => 'bi-shield-fill', 'label' => 'Administrator'],
                    'staff'   => ['bg' => 'bg-teal-50 text-teal-700 border-teal-200', 'icon' => 'bi-person-badge-fill', 'label' => 'Resort Staff'],
                    'tourist' => ['bg' => 'bg-sky-50 text-sky-700 border-sky-200', 'icon' => 'bi-person-fill', 'label' => 'Tourist Guest'],
                ];
                $b = $roleBadges[auth()->user()->role] ?? ['bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'bi-person', 'label' => ucfirst(auth()->user()->role)];
            @endphp

            <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold rounded-full border {{ $b['bg'] }} mb-6">
                <i class="bi {{ $b['icon'] }}"></i>
                <span>{{ $b['label'] }}</span>
            </span>

            <div class="space-y-2.5 text-start pt-4 border-t border-slate-100">
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Phone Number</span>
                    <span class="text-xs font-bold text-slate-700">{{ auth()->user()->phone ?? 'Not set' }}</span>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Account Status</span>
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Active &amp; Verified
                    </span>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Member Since</span>
                    <span class="text-xs font-bold text-slate-700">{{ auth()->user()->created_at->format('F d, Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Right Form Area --}}
    <div class="lg:col-span-8 space-y-6">
        
        {{-- Personal Details Form --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-6">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 mb-0.5">Personal Information</h3>
                    <p class="text-xs text-slate-400 mb-0">Update your account name, email address, and profile photo</p>
                </div>
            </div>

            @if($errors->any() && !$errors->has('current_password'))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 p-3.5 rounded-2xl mb-4 text-xs">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Full Name</label>
                        <input type="text" name="name" class="w-full form-control-clean text-xs font-semibold" value="{{ old('name', auth()->user()->name) }}" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Email Address</label>
                        <input type="email" name="email" class="w-full form-control-clean text-xs font-semibold" value="{{ old('email', auth()->user()->email) }}" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Phone Number</label>
                        <input type="tel" name="phone" class="w-full form-control-clean text-xs" value="{{ old('phone', auth()->user()->phone) }}" placeholder="+63-9XX-XXX-XXXX">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Upload New Avatar</label>
                        <input type="file" name="avatar" class="w-full form-control-clean text-xs" accept="image/*" onchange="previewImage(this)">
                        <span class="text-[10px] text-slate-400 mt-1 block">JPG, PNG or GIF (max 2MB)</span>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="btn-ocean text-xs px-5 py-2.5">
                        <i class="bi bi-check2-circle text-base"></i>
                        <span>Save Profile Details</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Security & Password Card --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-6">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 mb-0.5">Security &amp; Password</h3>
                    <p class="text-xs text-slate-400 mb-0">Ensure your account uses a strong, unique password</p>
                </div>
            </div>

            @if($errors->has('current_password') || $errors->has('password'))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 p-3.5 rounded-2xl mb-4 text-xs">
                <ul class="mb-0 ps-3">
                    @foreach($errors->get('current_password') as $e) <li>{{ $e }}</li> @endforeach
                    @foreach($errors->get('password') as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Current Password</label>
                        <input type="password" name="current_password" class="w-full form-control-clean text-xs" placeholder="Enter your current password" required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">New Password</label>
                            <input type="password" name="password" class="w-full form-control-clean text-xs" placeholder="Minimum 8 characters" required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="w-full form-control-clean text-xs" placeholder="Repeat new password" required>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="btn-secondary-clean text-xs px-5 py-2.5">
                        <i class="bi bi-shield-lock text-base"></i>
                        <span>Update Password</span>
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