@extends('layouts.public')

@section('title', 'My profile – '.$resortInfo['name'])
@section('meta_description', 'Update your name, phone, photo, and password for your '.$resortInfo['name'].' guest account.')

@section('content')
@php
    $account = auth()->user();
    $avatar = $account->avatar ? '/storage/'.ltrim($account->avatar, '/') : null;
@endphp

<section class="lp-banner lp-banner-short" style="background-image:url('/images/hero-landing.jpg')">
    <div class="lp-banner-box">
        <div class="lp-eyebrow">Your account</div>
        <h1>My profile</h1>
        <p>Keep your name, phone, and photo up to date. Bookings and payments stay on this same account.</p>
    </div>
</section>

<section class="lp-account-wrap">
    <div class="lp-section">
        @if(session('success'))
            <div class="lp-note lp-note-ok"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
        @endif

        <div class="lp-profile-grid">
            <aside class="lp-id-card">
                @if($avatar)
                    <img src="{{ $avatar }}" id="avatarPreview" class="lp-avatar" alt="{{ $account->name }}">
                @else
                    <div class="lp-avatar" id="avatarPlaceholder">{{ initials($account->name) }}</div>
                    <img id="avatarPreview" class="lp-avatar" alt="" hidden>
                @endif

                <h2>{{ $account->name }}</h2>
                <p class="lp-mail">{{ $account->email }}</p>
                <span class="lp-guest-pill"><i class="bi bi-person-fill"></i> Guest</span>

                <div class="lp-id-facts">
                    <div class="lp-id-fact">
                        <strong>Phone</strong>
                        <span>{{ $account->phone ?: 'Not set yet' }}</span>
                    </div>
                    <div class="lp-id-fact">
                        <strong>Account</strong>
                        @if($account->is_active)
                            <span class="is-live"><i class="bi bi-circle-fill"></i> Active</span>
                        @else
                            <span>Deactivated</span>
                        @endif
                    </div>
                    <div class="lp-id-fact">
                        <strong>Member since</strong>
                        <span>{{ $account->created_at?->format('F j, Y') }}</span>
                    </div>
                </div>

                @if($account->avatar)
                    <form method="POST" action="{{ route('profile.avatar.destroy') }}" onsubmit="return confirm('Remove your profile photo?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="lp-remove-photo"><i class="bi bi-trash3"></i> Remove photo</button>
                    </form>
                @endif

                <a class="lp-bookings-link" href="{{ route('tourist.bookings') }}"><i class="bi bi-calendar-check"></i> My bookings</a>
            </aside>

            <div class="lp-account-stack">
                <form class="lp-account-card" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <h3>Personal information</h3>
                    <p>This name and phone appear on your reservations and on receipts.</p>

                    @if($errors->any() && ! $errors->has('current_password') && ! $errors->has('password'))
                        <div class="lp-note lp-note-bad">
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="lp-fields">
                        <div class="lp-field">
                            <label for="profile-name">Full name</label>
                            <input id="profile-name" type="text" name="name" value="{{ old('name', $account->name) }}" required>
                        </div>
                        <div class="lp-field">
                            <label for="profile-email">Email address</label>
                            <input id="profile-email" type="email" name="email" value="{{ old('email', $account->email) }}" required>
                        </div>
                        <div class="lp-field">
                            <label for="profile-phone">Phone number</label>
                            <input id="profile-phone" type="tel" name="phone" value="{{ old('phone', $account->phone) }}" placeholder="09XXXXXXXXX">
                        </div>
                        <div class="lp-field">
                            <label for="profile-avatar">Profile photo</label>
                            <label class="lp-file">
                                <input id="profile-avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewAvatar(this)">
                                <span><i class="bi bi-image"></i> Choose a photo</span>
                                <em id="avatarFileName">JPG, PNG, or GIF, up to 2MB</em>
                            </label>
                        </div>
                    </div>
                    <div class="lp-account-actions">
                        <button type="submit" class="lp-btn lp-btn-sand"><i class="bi bi-check2-circle"></i> Save profile</button>
                    </div>
                </form>

                <form class="lp-account-card" method="POST" action="{{ route('profile.password') }}">
                    @csrf
                    @method('PUT')
                    <h3>Password</h3>
                    <p>Use a password you do not use on other sites. It must be at least 8 characters.</p>

                    @if($errors->has('current_password') || $errors->has('password'))
                        <div class="lp-note lp-note-bad">
                            <ul>
                                @foreach($errors->get('current_password') as $error)<li>{{ $error }}</li>@endforeach
                                @foreach($errors->get('password') as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="lp-fields">
                        <div class="lp-field full">
                            <label for="current-password">Current password</label>
                            <input id="current-password" type="password" name="current_password" autocomplete="current-password" placeholder="Enter your current password" required>
                        </div>
                        <div class="lp-field">
                            <label for="new-password">New password</label>
                            <input id="new-password" type="password" name="password" autocomplete="new-password" placeholder="At least 8 characters" required>
                        </div>
                        <div class="lp-field">
                            <label for="confirm-password">Confirm new password</label>
                            <input id="confirm-password" type="password" name="password_confirmation" autocomplete="new-password" placeholder="Repeat the new password" required>
                        </div>
                    </div>
                    <div class="lp-account-actions">
                        <button type="submit" class="lp-btn lp-btn-navy"><i class="bi bi-shield-lock"></i> Update password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
function previewAvatar(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const name = document.getElementById('avatarFileName');
    if (name) name.textContent = file.name;
    const reader = new FileReader();
    reader.onload = function (e) {
        const preview = document.getElementById('avatarPreview');
        const placeholder = document.getElementById('avatarPlaceholder');
        if (!preview) return;
        preview.src = e.target.result;
        preview.hidden = false;
        if (placeholder) placeholder.hidden = true;
    };
    reader.readAsDataURL(file);
}
</script>
@endpush
