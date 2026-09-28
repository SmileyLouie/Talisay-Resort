@extends('layouts.app')
@section('title', 'Edit User - Talisay Smart Tourism')

@section('content')
<div class="mb-5">
    <a href="{{ route('users.index') }}" class="text-sky-600 hover:underline text-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to User Management
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            {{-- Card Header --}}
            <div class="p-5 bg-ocean-800 text-white">
                <div class="flex items-center gap-4">
                    @if($user->avatar)
                        <img src="{{ Storage::url($user->avatar) }}" class="w-14 h-14 rounded-xl object-cover border-2 border-white/30" alt="{{ $user->name }}">
                    @else
                        <div class="w-14 h-14 rounded-xl bg-white/20 flex items-center justify-center text-white font-bold text-2xl">
                            {{ initials($user->name) }}
                        </div>
                    @endif
                    <div>
                        <h2 class="text-xl font-bold mb-0">{{ $user->name }}</h2>
                        <p class="text-white/70 text-sm mb-0">{{ ucfirst($user->role) }} · {{ $user->email }}</p>
                    </div>
                    <span class="ms-auto badge bg-{{ $user->is_active ? 'white text-success' : 'white text-secondary' }} fs-6 px-3">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>

            <div class="p-6">
                {{-- Validation Errors --}}
                @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2 ps-3">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                <form method="POST" action="{{ route('users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    {{-- Basic Information --}}
                    <div class="mb-4">
                        <h6 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">
                            <i class="bi bi-person-fill me-1"></i>Basic Information
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-sm">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $user->name) }}" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-sm">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $user->email) }}" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-sm">Phone Number</label>
                                <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $user->phone) }}" placeholder="Phone number">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-medium text-sm">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                                    @if($user->isAdmin())
                                        <option value="admin" selected>Admin (Primary Administrator)</option>
                                    @else
                                        <option value="staff" {{ old('role', $user->role) === 'staff' ? 'selected' : '' }}>Staff</option>
                                        <option value="tourist" {{ old('role', $user->role) === 'tourist' ? 'selected' : '' }}>Tourist</option>
                                    @endif
                                </select>
                                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-medium text-sm">Account Status</label>
                                <select name="is_active" class="form-select @error('is_active') is-invalid @enderror">
                                    <option value="1" {{ old('is_active', $user->is_active) ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ !old('is_active', $user->is_active) ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('is_active') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    {{-- Account Info (read-only) --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="bg-gray-50 rounded-lg p-3 text-sm">
                                <p class="text-gray-400 text-xs mb-1 uppercase tracking-wider">Joined</p>
                                <p class="font-semibold text-gray-700 mb-0">{{ $user->created_at->format('F d, Y') }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-gray-50 rounded-lg p-3 text-sm">
                                <p class="text-gray-400 text-xs mb-1 uppercase tracking-wider">Last Updated</p>
                                <p class="font-semibold text-gray-700 mb-0">{{ $user->updated_at->format('F d, Y') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-between pt-2">
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-5">
                            <i class="bi bi-check-lg me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Danger Zone --}}
        @if($user->id !== auth()->id())
        <div class="bg-white rounded-xl shadow-sm p-6 mt-4 border border-red-100">
            <h6 class="text-sm font-bold text-red-600 mb-2"><i class="bi bi-exclamation-triangle-fill me-1"></i>Danger Zone</h6>
            <p class="text-sm text-gray-500 mb-3">Permanently delete this user account. This cannot be undone.</p>
            <form method="POST" action="{{ route('users.destroy', $user) }}"
                  onsubmit="return confirm('Are you absolutely sure you want to delete ' + {{ Js::from($user->name) }} + '?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash me-1"></i>Delete {{ $user->name }}
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection