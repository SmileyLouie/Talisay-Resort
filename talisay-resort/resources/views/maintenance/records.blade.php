@push('styles')
<style>
    .settings-tab {
        border-radius: 12px;
        padding: 11px 16px;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        border: 1px solid transparent;
        background: transparent;
        transition: all .2s cubic-bezier(0.4, 0, 0.2, 1);
        text-align: left;
        width: 100%;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .settings-tab:hover {
        background: #f8fafc;
        color: #0f172a;
    }
    .settings-tab.active {
        background: #f0f9ff;
        color: #0284c7;
        border-color: #bae6fd;
        box-shadow: 0 1px 2px rgba(14, 165, 233, 0.05);
    }
    .settings-tab.active .tab-icon {
        color: #0284c7;
    }
    .tab-icon {
        width: 20px;
        text-align: center;
        font-size: 15px;
    }
    .setting-section { display: none; }
    .setting-section.active { display: block; }
    .preview-bubble {
        border-radius: 18px 18px 18px 4px;
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        color: #0c4a6e;
        font-size: 13.5px;
        padding: 12px 16px;
        max-width: 320px;
        line-height: 1.45;
        box-shadow: 0 2px 6px rgba(14, 165, 233, 0.08);
    }
    .fm-records .fm-records-head,
    .fm-records .lg\:col-span-3 { display: none !important; }
    .fm-records .lg\:col-span-9 { grid-column: 1 / -1; width: 100%; }
    @if(!empty($onlyTab))
    .fm-records .setting-section { display: none !important; }
    .fm-records #tab-{{ $onlyTab }} { display: block !important; }
    @if(in_array($onlyTab, ['users', 'chatbot'], true))
    .fm-records #settingsSaveBar { display: none !important; }
    @endif
    @endif

</style>
@endpush

<div class="fm-records">
{{-- Header --}}
<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4 fm-records-head">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100/80 text-sky-800 border border-sky-200">
                <i class="bi bi-gear text-[10px] text-sky-600"></i>
                Configuration
            </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-1">
            Settings
        </h1>
        <p class="text-sm text-slate-500 mb-0">
            Configure resort parameters, visitor limits, cancellation rules, and payment gateways.
        </p>
    </div>

    <div class="flex items-center gap-2">
        <a href="{{ route('maintenance.index') }}" class="btn-secondary-clean text-xs">
            <i class="bi bi-folder2-open text-sky-600"></i>
            <span>File Maintenance</span>
        </a>
        <span class="text-xs font-bold px-3 py-1.5 rounded-xl bg-slate-900 text-sky-300 border border-slate-800 shadow-sm flex items-center gap-1.5">
            <i class="bi bi-shield-lock text-sky-400"></i>
            <span>Admin Only</span>
        </span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    
    {{-- Left Navigation Tabs --}}
    <div class="lg:col-span-3">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-3 sticky top-24 space-y-1">
            <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest px-3 pt-2 pb-1">Categories</p>
            <button class="settings-tab active" id="tab-btn-general" onclick="switchTab('general')">
                <i class="bi bi-building tab-icon"></i>
                <span>General Resort</span>
            </button>
            <button class="settings-tab" id="tab-btn-booking" onclick="switchTab('booking')">
                <i class="bi bi-calendar-check tab-icon"></i>
                <span>Booking Rules</span>
            </button>
            <button class="settings-tab" id="tab-btn-chatbot" onclick="switchTab('chatbot')">
                <i class="bi bi-robot tab-icon"></i>
                <span>AI Chatbot</span>
            </button>
            <button class="settings-tab" id="tab-btn-users" onclick="switchTab('users')">
                <i class="bi bi-people tab-icon"></i>
                <span>User Accounts</span>
                <span class="ms-auto text-[10px] font-extrabold bg-sky-100 text-sky-800 px-2 py-0.5 rounded-full">{{ $totalUsers ?? 0 }}</span>
            </button>
        </div>
    </div>

    {{-- Right Settings Panels Form --}}
    <div class="lg:col-span-9">
        <form method="POST" action="{{ route('settings.save') }}" id="settingsForm" data-loading>
            @csrf
            @method('PUT')

            {{-- General Section --}}
            <div class="setting-section active" id="tab-general">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-6">
                    <div class="border-b border-slate-100 pb-3">
                        <h3 class="text-base font-extrabold text-slate-900 mb-0.5">Resort Information</h3>
                        <p class="text-xs text-slate-400 mb-0">Public branding, contact points, and operating hours</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Resort Name</label>
                            <input type="text" name="settings[resort_name]" class="w-full form-control-clean text-xs"
                                   value="{{ $settings['resort_name'] ?? 'Talisay Beach Resort' }}">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Contact Email</label>
                            <input type="email" name="settings[contact_email]" class="w-full form-control-clean text-xs"
                                   value="{{ $settings['contact_email'] ?? 'info@talisayresort.com' }}">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Contact Phone / Hotline</label>
                            <input type="text" name="settings[contact_phone]" class="w-full form-control-clean text-xs"
                                   value="{{ $settings['contact_phone'] ?? '' }}" placeholder="+63-9XX-XXX-XXXX">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Resort Address</label>
                            <input type="text" name="settings[resort_address]" class="w-full form-control-clean text-xs"
                                   value="{{ $settings['resort_address'] ?? '' }}" placeholder="Barangay Maslug, Baybay City, Leyte">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Daily Opening Time</label>
                            <input type="time" name="settings[opening_time]" class="w-full form-control-clean text-xs"
                                   value="{{ $settings['opening_time'] ?? '06:00' }}">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Daily Closing Time</label>
                            <input type="time" name="settings[closing_time]" class="w-full form-control-clean text-xs"
                                   value="{{ $settings['closing_time'] ?? '22:00' }}">
                        </div>
                    </div>

                    <div class="border-b border-slate-100 pb-3 pt-2">
                        <h3 class="text-base font-extrabold text-slate-900 mb-0.5">About Page</h3>
                        <p class="text-xs text-slate-400 mb-0">Shown on the public About page of the client site. Leave blank to use the default text.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Our Story</label>
                            <textarea name="settings[about_story]" rows="4" class="w-full form-control-clean text-xs"
                                      placeholder="A short paragraph about the resort, its location, and what guests can expect.">{{ $settings['about_story'] ?? '' }}</textarea>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Vision</label>
                                <textarea name="settings[about_vision]" rows="4" class="w-full form-control-clean text-xs"
                                          placeholder="Where the resort wants to be.">{{ $settings['about_vision'] ?? '' }}</textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Mission</label>
                                <textarea name="settings[about_mission]" rows="4" class="w-full form-control-clean text-xs"
                                          placeholder="What the resort commits to for every guest.">{{ $settings['about_mission'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="border-b border-slate-100 pb-3 pt-2">
                        <h3 class="text-base font-extrabold text-slate-900 mb-0.5">Resort gallery</h3>
                        <p class="text-xs text-slate-400 mb-0">Photos of the views and attractions on the public About page. Room and cottage photos stay on their own pages.</p>
                    </div>

                    @if($errors->has('photos') || $errors->has('photos.*') || $errors->has('title') || $errors->has('caption'))
                        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700">
                            {{ $errors->first('photos') ?: $errors->first('photos.*') ?: $errors->first('title') ?: $errors->first('caption') }}
                        </div>
                    @endif

                    @if($galleryPhotos->isEmpty())
                        <p class="text-xs text-slate-500 mb-0">No photos yet. The About gallery stays empty until you add a view or attraction.</p>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($galleryPhotos as $photo)
                                <div class="border border-slate-200 rounded-xl p-3 flex gap-3">
                                    <img src="{{ $photo->imageUrl() }}" alt="{{ $photo->title ?: 'Gallery photo' }}" class="w-24 h-24 rounded-lg object-cover bg-slate-100 shrink-0">
                                    <div class="flex-1 min-w-0 space-y-2">
                                        <input type="text" name="title" form="gallery-update-{{ $photo->id }}" value="{{ $photo->title }}" maxlength="80" class="w-full form-control-clean text-xs" placeholder="Title, such as Sunset shore">
                                        <input type="text" name="caption" form="gallery-update-{{ $photo->id }}" value="{{ $photo->caption }}" maxlength="160" class="w-full form-control-clean text-xs" placeholder="Short caption">
                                        <div class="flex items-center gap-2">
                                            <button type="submit" form="gallery-update-{{ $photo->id }}" class="text-xs font-bold text-sky-700">Save</button>
                                            <button type="submit" form="gallery-delete-{{ $photo->id }}" class="text-xs font-bold text-rose-600">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-600 mb-1" for="galleryPhotos">Add photos</label>
                            <input id="galleryPhotos" type="file" name="photos[]" form="gallery-upload" accept="image/jpeg,image/png,image/webp" multiple class="w-full text-xs text-slate-600">
                            <span class="text-[11px] text-slate-400 mt-1 block">JPG, PNG, or WebP. Up to 20 photos, 8 MB each. A title applies when you upload one photo.</span>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1" for="galleryTitle">Title</label>
                            <input id="galleryTitle" type="text" name="title" form="gallery-upload" maxlength="80" class="w-full form-control-clean text-xs" placeholder="Sunset shore">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1" for="galleryCaption">Caption</label>
                            <input id="galleryCaption" type="text" name="caption" form="gallery-upload" maxlength="160" class="w-full form-control-clean text-xs" placeholder="The view from the beach at dusk">
                        </div>
                    </div>
                    <div>
                        <button type="submit" form="gallery-upload" class="btn-ocean text-xs px-5 py-2.5">
                            <i class="bi bi-image"></i>
                            <span>Upload to gallery</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Booking Rules Section --}}
            <div class="setting-section" id="tab-booking">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-6">
                    <div class="border-b border-slate-100 pb-3">
                        <h3 class="text-base font-extrabold text-slate-900 mb-0.5">Booking &amp; Capacity Rules</h3>
                        <p class="text-xs text-slate-400 mb-0">Controls for maximum daily tourists, headcounts, and cancellation windows</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Daily Visitor Cap (Guests/Day)</label>
                            <input type="number" name="settings[daily_visitor_cap]" class="w-full form-control-clean text-xs"
                                   value="{{ $settings['daily_visitor_cap'] ?? 100 }}" min="1" max="1000">
                            <span class="text-[11px] text-slate-400 mt-1 block">Maximum total day visitors allowed</span>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Cancellation Window (Hours Before Check-in)</label>
                            <input type="number" name="settings[cancellation_window_hours]" class="w-full form-control-clean text-xs"
                                   value="{{ $settings['cancellation_window_hours'] ?? 24 }}" min="0">
                            <span class="text-[11px] text-slate-400 mt-1 block">Hours before booking that guests may cancel</span>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Max Guests Per Single Booking</label>
                            <input type="number" name="settings[max_guests_per_booking]" class="w-full form-control-clean text-xs"
                                   value="{{ $settings['max_guests_per_booking'] ?? 20 }}" min="1">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Advance Booking Days Limit</label>
                            <input type="number" name="settings[advance_booking_days]" class="w-full form-control-clean text-xs"
                                   value="{{ $settings['advance_booking_days'] ?? 30 }}" min="1">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Save Bar for General and Booking Settings --}}
            <div id="settingsSaveBar" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 mt-6 flex items-center justify-between">
                <span class="text-xs text-slate-400 flex items-center gap-1.5">
                    <i class="bi bi-info-circle text-sky-500"></i>
                    Updates take effect across all portal sessions immediately.
                </span>
                <button type="submit" class="btn-ocean text-xs px-6 py-2.5">
                    <i class="bi bi-check2-circle text-base"></i>
                    <span>Save All Settings</span>
                </button>
            </div>
        </form>

        <form id="gallery-upload" method="POST" action="{{ route('settings.gallery.store') }}" enctype="multipart/form-data" class="hidden">
            @csrf
        </form>
        @foreach($galleryPhotos as $photo)
            <form id="gallery-update-{{ $photo->id }}" method="POST" action="{{ route('settings.gallery.update', $photo) }}" class="hidden">
                @csrf
                @method('PUT')
            </form>
            <form id="gallery-delete-{{ $photo->id }}" method="POST" action="{{ route('settings.gallery.destroy', $photo) }}" class="hidden" onsubmit="return confirm('Remove this photo from the gallery?')">
                @csrf
                @method('DELETE')
            </form>
        @endforeach

        {{-- Chatbot Section (Standalone Form) --}}
        <div class="setting-section" id="tab-chatbot">
            @include('maintenance.ai-assistant')
        </div>

        {{-- User Accounts Section --}}
        <div class="setting-section" id="tab-users">
            <div class="res-head">
                <div>
                    <h1>User accounts</h1>
                    <p>Guest, staff, and admin profiles used across the resort.</p>
                </div>
                <div class="res-head-actions">
                    <a href="{{ route('tasks.staff') }}" class="btn-secondary-clean" title="Manage staff permissions">
                        <i class="bi bi-shield-lock"></i>
                        <span>Staff permissions</span>
                    </a>
                    <button type="button" class="btn-ocean" onclick="openCreateUserModal()">
                        <i class="bi bi-plus-lg"></i>
                        <span>Add account</span>
                    </button>
                </div>
            </div>

            <div class="unit-stats">
                <div><span>Total users</span><strong>{{ $totalUsers ?? 0 }}</strong></div>
                <div><span>Admins</span><strong>{{ $adminCount ?? 0 }}</strong></div>
                <div><span>Staff</span><strong>{{ $staffCount ?? 0 }}</strong></div>
                <div><span>Tourists</span><strong>{{ $touristCount ?? 0 }}</strong></div>
                <div><span>Active</span><strong class="is-good">{{ $activeCount ?? 0 }}</strong><em>{{ $inactiveCount ?? 0 }} suspended</em></div>
            </div>

            <div class="fm-panel acct-panel">
                <form method="GET" action="{{ route('maintenance.section', $section ?? 'accounts') }}" class="res-toolbar">
                    <label class="res-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" class="form-control-clean" placeholder="Name, email, or phone" value="{{ request('search') }}" aria-label="Search user">
                    </label>
                    <select name="role" class="form-select-clean res-select" aria-label="Filter role" onchange="this.form.submit()">
                        <option value="">All roles</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="staff" {{ request('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                        <option value="tourist" {{ request('role') === 'tourist' ? 'selected' : '' }}>Tourist</option>
                    </select>
                    <button type="submit" class="btn-ocean">Search</button>
                    @if(request()->hasAny(['search', 'role']))
                        <a href="{{ route('maintenance.section', 'accounts') }}" class="res-clear">Clear</a>
                    @endif
                </form>

                <div class="table-responsive">
                    <table class="table-clean acct-table">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th>Role</th>
                                <th>Phone</th>
                                <th>Activity</th>
                                <th>Registered</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $u)
                            <tr>
                                <td>
                                    <div class="acct-person">
                                        @if($u->avatar)
                                            <img src="{{ Storage::url($u->avatar) }}" class="acct-avatar" alt="">
                                        @else
                                            <span class="acct-avatar acct-avatar-{{ $u->role }}">{{ initials($u->name) }}</span>
                                        @endif
                                        <span>
                                            <span class="acct-name">
                                                {{ $u->name }}
                                                @if($u->id === auth()->id())
                                                    <em>You</em>
                                                @endif
                                                @unless($u->is_active)
                                                    <em class="is-off">Suspended</em>
                                                @endunless
                                            </span>
                                            <span class="acct-email">
                                                {{ $u->email }}
                                                @if($u->email_verified_at)
                                                    <i class="bi bi-patch-check" title="Email verified"></i>
                                                @endif
                                            </span>
                                        </span>
                                    </div>
                                </td>
                                <td><span class="acct-role acct-role-{{ $u->role }}">{{ ucfirst($u->role) }}</span></td>
                                <td>{{ $u->phone ?: '—' }}</td>
                                <td class="acct-activity">{{ $u->bookings_count ?? 0 }} bookings · {{ $u->reviews_count ?? 0 }} reviews</td>
                                <td>{{ $u->created_at ? $u->created_at->format('M d, Y') : '—' }}</td>
                                <td class="text-end">
                                    <div class="acct-actions">
                                        <button type="button" class="res-icon-btn" title="View" onclick='viewUser(@json($u))'><i class="bi bi-eye"></i></button>
                                        <button type="button" class="res-icon-btn" title="Edit" onclick='openEditUserModal(@json($u))'><i class="bi bi-pencil"></i></button>
                                        <button type="button" class="res-icon-btn" title="Reset password" onclick="openResetPasswordModal({{ $u->id }}, {{ Js::from($u->name) }})"><i class="bi bi-key"></i></button>
                                        @if($u->isStaff())
                                            <a href="{{ route('tasks.staff', ['tab' => 'rbac', 'search' => $u->email]) }}" class="res-icon-btn" title="Staff permissions"><i class="bi bi-shield-lock"></i></a>
                                        @endif
                                        @if($u->id !== auth()->id() && $u->role !== 'admin')
                                            <form method="POST" action="{{ route('users.toggle-status', $u) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="res-icon-btn" title="{{ $u->is_active ? 'Suspend' : 'Activate' }}">
                                                    <i class="bi {{ $u->is_active ? 'bi-person-slash' : 'bi-person-check' }}"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="res-icon-btn is-danger" title="Delete" onclick="confirmDeleteUser({{ $u->id }}, {{ Js::from($u->name) }})"><i class="bi bi-trash"></i></button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="acct-empty">
                                    <i class="bi bi-people"></i>
                                    <strong>No user accounts found</strong>
                                    <span>Try a different name, email, or role.</span>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($users->hasPages())
                <div class="acct-pages">
                    {{ $users->withQueryString()->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- Create User Modal --}}
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden">
            <div class="p-5 bg-ocean-700 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-lg">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <div>
                        <h5 class="text-base font-extrabold mb-0">Create User Account</h5>
                        <p class="text-[11px] text-white/80 mb-0">Add a new tourist, staff, or admin account</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal"></button>
            </div>
            
            <form method="POST" action="{{ route('users.store') }}" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="tab" value="users">

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" class="w-full form-control-clean text-xs" required placeholder="e.g. Maria Santos" value="{{ old('name') }}">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Email Address <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" class="w-full form-control-clean text-xs" required placeholder="e.g. maria@example.com" value="{{ old('email') }}">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Phone Number</label>
                        <input type="text" name="phone" class="w-full form-control-clean text-xs" placeholder="09XX-XXX-XXXX" value="{{ old('phone') }}">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Account Role <span class="text-rose-500">*</span></label>
                        <select name="role" class="w-full form-select-clean text-xs font-medium" required>
                            <option value="tourist" {{ old('role') === 'tourist' ? 'selected' : '' }}>Tourist (Guest)</option>
                            <option value="staff" {{ old('role') === 'staff' ? 'selected' : '' }}>Staff (Frontdesk)</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Password <span class="text-rose-500">*</span></label>
                        <input type="password" name="password" class="w-full form-control-clean text-xs font-mono" required placeholder="Min 8 characters">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Confirm Password <span class="text-rose-500">*</span></label>
                        <input type="password" name="password_confirmation" class="w-full form-control-clean text-xs font-mono" required placeholder="Re-enter password">
                    </div>
                </div>

                <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button" class="btn-secondary-clean text-xs px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-ocean text-xs px-5 py-2">
                        <i class="bi bi-check2-circle"></i>
                        <span>Create Account</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit User Modal --}}
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden">
            <div class="p-5 bg-ocean-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-lg">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div>
                        <h5 class="text-base font-extrabold mb-0">Edit User Account</h5>
                        <p class="text-[11px] text-sky-200 mb-0" id="editModalSub">Update account information</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="editUserForm" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="tab" value="users">

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="edit_name" class="w-full form-control-clean text-xs" required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Email Address <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" id="edit_email" class="w-full form-control-clean text-xs" required>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Phone Number</label>
                        <input type="text" name="phone" id="edit_phone" class="w-full form-control-clean text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Role <span class="text-rose-500">*</span></label>
                        <select name="role" id="edit_role" class="w-full form-select-clean text-xs font-medium" required>
                            <option value="tourist">Tourist</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Account Status</label>
                    <select name="is_active" id="edit_is_active" class="w-full form-select-clean text-xs font-medium">
                        <option value="1">Active (Permit Login)</option>
                        <option value="0">Suspended / Inactive (Block Login)</option>
                    </select>
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <span class="text-[11px] font-bold text-slate-600 block">Optional: Set New Password</span>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="password" name="password" class="w-full form-control-clean text-xs font-mono" placeholder="New password (optional)">
                        <input type="password" name="password_confirmation" class="w-full form-control-clean text-xs font-mono" placeholder="Confirm password">
                    </div>
                </div>

                <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button" class="btn-secondary-clean text-xs px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-ocean text-xs px-5 py-2">
                        <i class="bi bi-check2"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View User Details Modal --}}
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden">
            <div class="p-6 bg-ocean-900 text-white relative">
                <button type="button" class="btn-close btn-close-white text-xs absolute top-5 right-5" data-bs-dismiss="modal"></button>
                <div class="flex items-center gap-4">
                    <div id="viewUserAvatar" class="w-14 h-14 rounded-2xl bg-sky-500/20 text-sky-300 font-extrabold flex items-center justify-center text-xl border border-sky-400/30">
                    </div>
                    <div>
                        <h4 class="text-lg font-extrabold mb-0.5 text-white" id="viewUserName"></h4>
                        <p class="text-xs text-sky-200/80 mb-1" id="viewUserEmail"></p>
                        <div class="flex items-center gap-2">
                            <span id="viewUserRoleBadge" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"></span>
                            <span id="viewUserStatusBadge" class="px-2 py-0.5 rounded-full text-[10px] font-bold"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6 space-y-4 text-xs">
                <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <div>
                        <span class="text-[10px] font-bold uppercase text-slate-400 block mb-0.5">Phone Number</span>
                        <span class="font-bold text-slate-800 text-xs" id="viewUserPhone">—</span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase text-slate-400 block mb-0.5">Registration Date</span>
                        <span class="font-bold text-slate-800 text-xs" id="viewUserCreated">—</span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase text-slate-400 block mb-0.5">Total Bookings</span>
                        <span class="font-bold text-slate-800 text-xs" id="viewUserBookings">0</span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase text-slate-400 block mb-0.5">Total Reviews</span>
                        <span class="font-bold text-slate-800 text-xs" id="viewUserReviews">0</span>
                    </div>
                </div>

                {{-- Staff Specific Section --}}
                <div id="viewUserStaffDetails" class="p-4 bg-indigo-50/60 rounded-2xl border border-indigo-100 space-y-2 hidden">
                    <span class="text-[11px] font-bold text-indigo-900 block flex items-center gap-1.5">
                        <i class="bi bi-person-badge text-indigo-600"></i>
                        Staff Job Information
                    </span>
                    <div class="grid grid-cols-2 gap-2 text-[11px]">
                        <div>
                            <span class="text-slate-400 block">Position:</span>
                            <strong class="text-slate-800" id="viewUserPosition">—</strong>
                        </div>
                        <div>
                            <span class="text-slate-400 block">Department:</span>
                            <strong class="text-slate-800" id="viewUserDepartment">—</strong>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-indigo-100/60">
                        <a id="viewUserRbacLink" href="#" class="text-indigo-600 font-bold hover:underline inline-flex items-center gap-1 text-[11px]">
                            <i class="bi bi-shield-lock"></i>
                            <span>Configure Granular RBAC Permissions</span>
                        </a>
                    </div>
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="button" class="btn-secondary-clean text-xs px-4 py-2" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Reset Password Modal --}}
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden">
            <div class="p-5 bg-ocean-800 text-white flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <i class="bi bi-key text-lg"></i>
                    <h5 class="text-sm font-extrabold mb-0">Reset Password</h5>
                </div>
                <button type="button" class="btn-close btn-close-white text-xs" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="resetPasswordForm" method="POST" class="p-6 space-y-4">
                @csrf
                <p class="text-xs text-slate-500 mb-0">Enter a new secure password for <strong id="resetPasswordUserName" class="text-slate-800"></strong>.</p>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">New Password</label>
                    <input type="password" name="password" class="w-full form-control-clean text-xs font-mono" required placeholder="Min 8 characters">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Confirm New Password</label>
                    <input type="password" name="password_confirmation" class="w-full form-control-clean text-xs font-mono" required placeholder="Re-enter password">
                </div>

                <div class="pt-2 flex items-center justify-end gap-2">
                    <button type="button" class="btn-secondary-clean text-xs px-3 py-1.5" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-ocean text-xs px-4 py-1.5">
                        <span>Save Password</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete User Confirmation Modal --}}
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden p-6 text-center">
            <div class="w-14 h-14 bg-rose-50 border border-rose-200 text-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <h5 class="font-extrabold text-slate-900 text-base mb-1">Delete User Account?</h5>
            <p class="text-xs text-slate-500 mb-6">Are you sure you want to remove <strong id="deleteUserName" class="text-slate-800"></strong>? This action cannot be reversed.</p>
            <div class="flex items-center justify-center gap-2">
                <button class="btn-secondary-clean text-xs px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteUserForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm rounded-xl text-xs font-bold px-4 py-2">
                        Yes, Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

</div>

@push('scripts')
<script>
const maintenanceTab = @json($onlyTab ?? null);
function switchTab(tab) {
    document.querySelectorAll('.setting-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
    
    const targetSection = document.getElementById('tab-' + tab);
    if (targetSection) targetSection.classList.add('active');

    const targetBtn = document.getElementById('tab-btn-' + tab);
    if (targetBtn) targetBtn.classList.add('active');

    const saveBar = document.getElementById('settingsSaveBar');
    if (saveBar) {
        saveBar.style.display = (tab === 'users' || tab === 'chatbot') ? 'none' : 'flex';
    }

    if (!maintenanceTab && window.history && window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    }
}

function updatePreview() {
    const val = document.getElementById('chatbotWelcome')?.value;
    const prev = document.getElementById('chatPreview');
    if (prev) prev.textContent = val || 'Welcome to Talisay Beach Resort! How can I help you today?';
}

// â”€â”€ Provider & Model Switching â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const providerDefaultModels = @json(collect($aiProviders ?? [])->mapWithKeys(fn($p, $id) => [$id => $p['default_model']])->toArray());

function selectProvider(providerId) {
    onProviderChange(providerId);
}

function onProviderChange(providerId) {
    document.querySelectorAll('.provider-model-group').forEach(el => el.classList.add('hidden'));
    const target = document.getElementById('modelOptions_' + providerId);
    if (target) target.classList.remove('hidden');

    const endpointRow = document.getElementById('customEndpointRow');
    if (endpointRow) endpointRow.classList.toggle('hidden', providerId !== 'custom');

    const modelInput = document.getElementById('modelInput');
    if (modelInput && providerDefaultModels[providerId]) {
        modelInput.value = providerDefaultModels[providerId];
    }

    const selectEl = document.querySelector(`#modelOptions_${providerId} .provider-model-select`);
    if (selectEl && modelInput) {
        const match = Array.from(selectEl.options).find(o => o.value === modelInput.value);
        selectEl.value = match ? match.value : '';
    }
}

function syncModelInput(value, providerId) {
    const modelInput = document.getElementById('modelInput');
    if (modelInput && value) modelInput.value = value;
}

function syncModelSelectToInput(val) {
    const provider = document.getElementById('providerSelect')?.value;
    if (!provider) return;
    const selectEl = document.querySelector(`#modelOptions_${provider} .provider-model-select`);
    if (selectEl) {
        const match = Array.from(selectEl.options).find(o => o.value === val);
        if (match) selectEl.value = match.value;
    }
}

// â”€â”€ API Key Management â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function showChangeKeyRow() {
    document.getElementById('maskedKeyRow')?.classList.add('hidden');
    document.getElementById('changeKeyRow')?.classList.remove('hidden');
    document.getElementById('apiKeyInput')?.focus();
}

function cancelChangeKey() {
    document.getElementById('maskedKeyRow')?.classList.remove('hidden');
    document.getElementById('changeKeyRow')?.classList.add('hidden');
    const keyInput = document.getElementById('apiKeyInput');
    if (keyInput) keyInput.value = '';
    const removeCheck = document.getElementById('removeApiKeyCheck');
    if (removeCheck) removeCheck.checked = false;
}

function toggleApiKeyVis() {
    const input = document.getElementById('apiKeyInput');
    const icon = document.getElementById('apiKeyEyeIcon');
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) { icon.classList.remove('bi-eye-slash'); icon.classList.add('bi-eye'); }
    } else {
        input.type = 'password';
        if (icon) { icon.classList.remove('bi-eye'); icon.classList.add('bi-eye-slash'); }
    }
}

function onRemoveKeyToggle(checkbox) {
    const apiInput = document.getElementById('apiKeyInput');
    if (apiInput) {
        apiInput.disabled = checkbox.checked;
        apiInput.value = '';
    }
}

// â”€â”€ Live Connection Test â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
async function testAIConnection() {
    const btn = document.getElementById('testConnectionBtn');
    const icon = document.getElementById('testConnIcon');
    const text = document.getElementById('testConnText');
    const resultBox = document.getElementById('testConnectionResult');
    if (!btn || !resultBox) return;

    btn.disabled = true;
    if (icon) icon.className = 'bi bi-arrow-repeat animate-spin';
    if (text) text.textContent = 'Testing...';
    resultBox.classList.add('hidden');

    const provider = document.getElementById('providerSelect')?.value ?? 'gemini';
    const model = document.getElementById('modelInput')?.value ?? '';
    const apiKeyInput = document.getElementById('apiKeyInput');
    const apiKey = apiKeyInput ? apiKeyInput.value : '';
    const endpoint = document.getElementById('apiEndpointInput')?.value ?? '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                   ?? document.querySelector('input[name="_token"]')?.value ?? '';

    try {
        const resp = await fetch('{{ route("settings.chatbot.test") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ provider, model, api_key: apiKey, api_endpoint: endpoint }),
        });
        const data = await resp.json();
        resultBox.classList.remove('hidden');
        if (data.success) {
            resultBox.className = 'mt-3 p-3 rounded-xl border text-xs bg-emerald-50 border-emerald-200 text-emerald-800';
            resultBox.innerHTML = `<i class="bi bi-check-circle mr-1"></i> <strong>Connection verified!</strong> ${escHtml(data.message)}${data.latency_ms ? ` (${data.latency_ms}ms)` : ''}${data.sample_reply ? `<div class="mt-1 text-slate-600">"${escHtml(data.sample_reply)}"</div>` : ''}`;
            if (icon) icon.className = 'bi bi-check-circle text-emerald-500';
            if (text) text.textContent = 'Verified';
        } else {
            resultBox.className = 'mt-3 p-3 rounded-xl border text-xs bg-rose-50 border-rose-200 text-rose-800';
            resultBox.innerHTML = `<i class="bi bi-x-circle mr-1"></i> <strong>Failed:</strong> ${escHtml(data.message ?? 'Unknown error')}`;
            if (icon) icon.className = 'bi bi-x-circle text-rose-500';
            if (text) text.textContent = 'Failed';
        }
    } catch (err) {
        resultBox.classList.remove('hidden');
        resultBox.className = 'mt-3 p-3 rounded-xl border text-xs bg-rose-50 border-rose-200 text-rose-800';
        resultBox.innerHTML = `<i class="bi bi-x-circle mr-1"></i> Network error: ${escHtml(err.message)}`;
        if (icon) icon.className = 'bi bi-x-circle text-rose-500';
        if (text) text.textContent = 'Error';
    } finally {
        btn.disabled = false;
        setTimeout(() => {
            if (icon) icon.className = 'bi bi-wifi';
            if (text) text.textContent = 'Test connection';
            btn.textContent = 'Test connection';
        }, 6000);
    }
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// â”€â”€ System Prompt Helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const PROMPT_TEMPLATES = {
    concierge: "You are the official Talisay Beach Resort AI tourism assistant in Baybay City, Leyte, Philippines. Help visitors, tourists, and staff with resort information, room rates, cottage availability, facilities, policies, and booking inquiries. Be courteous, concise, and helpful. Never invent information not available in the system.",
    direct: "You are the fast frontdesk AI assistant for Talisay Beach Resort. Deliver instant, bullet-pointed, and precise answers about room bookings, cottage rates, entrance fees, swimming pool guidelines, and resort policies. Be concise, professional, and efficient.",
    bilingual: "You are the official bilingual AI concierge for Talisay Beach Resort in Baybay City, Leyte. You warmly communicate in English, Tagalog, and Cebuano/Bisaya. Assist guests with cottage reservations, overnight suites, day-tour schedules, amenities, and payment options with genuine Filipino hospitality."
};

function applyPromptPreset(templateKey) {
    const el = document.getElementById('systemPromptInput');
    if (el && PROMPT_TEMPLATES[templateKey]) {
        el.value = PROMPT_TEMPLATES[templateKey];
        updatePromptCount();
    }
}

function loadDefaultPrompt() {
    applyPromptPreset('concierge');
}

function updatePromptCount() {
    const el = document.getElementById('systemPromptInput');
    const counter = document.getElementById('promptCharCount');
    if (el && counter) {
        const chars = el.value.length;
        const approxTokens = Math.round(chars / 4);
        counter.textContent = `${chars.toLocaleString()} chars Â· ~${approxTokens} tokens`;
    }
}

function openCreateUserModal() {
    new bootstrap.Modal(document.getElementById('createUserModal')).show();
}

function openEditUserModal(user) {
    document.getElementById('edit_name').value = user.name || '';
    document.getElementById('edit_email').value = user.email || '';
    document.getElementById('edit_phone').value = user.phone || '';
    document.getElementById('edit_role').value = user.role || 'tourist';
    document.getElementById('edit_is_active').value = user.is_active ? '1' : '0';
    document.getElementById('editModalSub').textContent = 'Editing user: ' + user.name;
    document.getElementById('editUserForm').action = '/users/' + user.id;
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

function viewUser(user) {
    document.getElementById('viewUserName').textContent = user.name || 'Unnamed';
    document.getElementById('viewUserEmail').textContent = user.email || 'No email';
        document.getElementById('viewUserPhone').textContent = user.phone || '—';
    document.getElementById('viewUserBookings').textContent = user.bookings_count !== undefined ? user.bookings_count : '0';
    document.getElementById('viewUserReviews').textContent = user.reviews_count !== undefined ? user.reviews_count : '0';

    if (user.created_at) {
        const d = new Date(user.created_at);
        document.getElementById('viewUserCreated').textContent = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    } else {
        document.getElementById('viewUserCreated').textContent = '—';
    }

    const avatarBox = document.getElementById('viewUserAvatar');
    avatarBox.replaceChildren();
    if (user.avatar) {
        const img = document.createElement('img');
        img.src = '/storage/' + String(user.avatar).replace(/^\/+/, '');
        img.className = 'w-full h-full object-cover rounded-2xl';
        img.alt = user.name || 'User photo';
        avatarBox.appendChild(img);
    } else {
        avatarBox.textContent = user.name ? user.name.charAt(0).toUpperCase() : 'U';
    }

    const roleBadge = document.getElementById('viewUserRoleBadge');
    roleBadge.textContent = (user.role || 'user').toUpperCase();
    if (user.role === 'admin') {
        roleBadge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800';
    } else if (user.role === 'staff') {
        roleBadge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800';
    } else {
        roleBadge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800';
    }

    const statusBadge = document.getElementById('viewUserStatusBadge');
    if (user.is_active) {
        statusBadge.textContent = 'ACTIVE';
        statusBadge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30';
    } else {
        statusBadge.textContent = 'SUSPENDED';
        statusBadge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-400/30';
    }

    const staffBox = document.getElementById('viewUserStaffDetails');
    if (user.role === 'staff') {
        staffBox.classList.remove('hidden');
        document.getElementById('viewUserPosition').textContent = user.position || 'Frontdesk Staff';
        document.getElementById('viewUserDepartment').textContent = user.department || 'Operations';
        document.getElementById('viewUserRbacLink').href = `/staff-management?tab=rbac&search=${encodeURIComponent(user.email)}`;
    } else {
        staffBox.classList.add('hidden');
    }

    new bootstrap.Modal(document.getElementById('viewUserModal')).show();
}

function openResetPasswordModal(userId, userName) {
    document.getElementById('resetPasswordUserName').textContent = userName;
    document.getElementById('resetPasswordForm').action = `/users/${userId}/reset-password`;
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}

function confirmDeleteUser(userId, userName) {
    document.getElementById('deleteUserName').textContent = userName;
    document.getElementById('deleteUserForm').action = `/users/${userId}`;
    new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = maintenanceTab || urlParams.get('tab') || 'general';
    switchTab(tabParam);

    if (urlParams.get('action') === 'create') {
        openCreateUserModal();
    }

    // Chatbot tab initialisation
    updatePreview();

    const promptEl = document.getElementById('systemPromptInput');
    if (promptEl) {
        updatePromptCount();
        promptEl.addEventListener('input', updatePromptCount);
    }

});
</script>
@endpush
