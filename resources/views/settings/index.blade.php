@extends('layouts.app')
@section('title', 'System Settings - Talisay Smart Tourism')

@push('styles')
<style>
    .settings-tab {
        border-radius: 10px;
        padding: 10px 18px;
        font-size: 14px;
        font-weight: 500;
        color: #64748b;
        cursor: pointer;
        border: none;
        background: transparent;
        transition: all .2s;
        text-align: left;
        width: 100%;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .settings-tab:hover { background: #f1f5f9; color: #0f172a; }
    .settings-tab.active { background: #e0f2fe; color: #0284c7; font-weight: 600; }
    .settings-tab.active .tab-icon { color: #0284c7; }
    .tab-icon { width: 20px; text-align: center; }
    .setting-section { display: none; }
    .setting-section.active { display: block; }
    .setting-group-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #94a3b8;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 1px solid #e2e8f0;
    }
    .preview-bubble {
        border-radius: 16px 16px 16px 4px;
        background: #f0f9ff;
        color: #0c4a6e;
        font-size: 14px;
        padding: 10px 14px;
        max-width: 280px;
        line-height: 1.4;
        margin-top: 8px;
    }
</style>
@endpush

@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">System Settings</h1>
        <p class="text-gray-500 text-sm">Configure resort-wide parameters and integrations</p>
    </div>
    <span class="badge bg-sky-100 text-sky-700 px-3 py-2">
        <i class="bi bi-gear-fill me-1"></i>Admin Only
    </span>
</div>

<div class="row g-4">
    {{-- Left Sidebar Navigation --}}
    <div class="col-lg-3">
        <div class="bg-white rounded-xl shadow-sm p-3 sticky-top" style="top: 80px;">
            <p class="text-xs text-gray-400 uppercase tracking-widest px-2 mb-2">Settings Categories</p>
            <button class="settings-tab active" onclick="switchTab('general')">
                <i class="bi bi-building tab-icon"></i> General
            </button>
            <button class="settings-tab" onclick="switchTab('booking')">
                <i class="bi bi-calendar-check tab-icon"></i> Booking Rules
            </button>
            <button class="settings-tab" onclick="switchTab('chatbot')">
                <i class="bi bi-chat-dots tab-icon"></i> Chatbot
            </button>
            <button class="settings-tab" onclick="switchTab('payment')">
                <i class="bi bi-credit-card tab-icon"></i> Payment
            </button>
        </div>
    </div>

    {{-- Right Settings Panel --}}
    <div class="col-lg-9">
        <form method="POST" action="{{ route('settings.save') }}" id="settingsForm">
            @csrf
            @method('PUT')

            {{-- General Settings --}}
            <div class="setting-section active" id="tab-general">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <p class="setting-group-title"><i class="bi bi-building me-2"></i>Resort General Information</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium text-sm">Resort Name</label>
                            <input type="text" name="settings[resort_name]" class="form-control"
                                   value="{{ $settings['resort_name'] ?? 'Talisay Beach Resort' }}"
                                   placeholder="Resort Name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium text-sm">Contact Email</label>
                            <input type="email" name="settings[contact_email]" class="form-control"
                                   value="{{ $settings['contact_email'] ?? 'info@talisayresort.com' }}"
                                   placeholder="contact@example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium text-sm">Contact Phone</label>
                            <input type="text" name="settings[contact_phone]" class="form-control"
                                   value="{{ $settings['contact_phone'] ?? '' }}"
                                   placeholder="+63-9XX-XXX-XXXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium text-sm">Resort Address</label>
                            <input type="text" name="settings[resort_address]" class="form-control"
                                   value="{{ $settings['resort_address'] ?? '' }}"
                                   placeholder="Talisay City, Cebu, Philippines">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium text-sm">Opening Time</label>
                            <input type="time" name="settings[opening_time]" class="form-control"
                                   value="{{ $settings['opening_time'] ?? '06:00' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium text-sm">Closing Time</label>
                            <input type="time" name="settings[closing_time]" class="form-control"
                                   value="{{ $settings['closing_time'] ?? '22:00' }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Booking Settings --}}
            <div class="setting-section" id="tab-booking">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <p class="setting-group-title"><i class="bi bi-calendar-check me-2"></i>Booking & Capacity Rules</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium text-sm">Daily Visitor Cap</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-people-fill"></i></span>
                                <input type="number" name="settings[daily_visitor_cap]" class="form-control"
                                       value="{{ $settings['daily_visitor_cap'] ?? 100 }}" min="1" max="1000">
                                <span class="input-group-text">guests/day</span>
                            </div>
                            <small class="text-muted">Maximum number of visitors allowed per day</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium text-sm">Cancellation Window</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-clock-history"></i></span>
                                <input type="number" name="settings[cancellation_window_hours]" class="form-control"
                                       value="{{ $settings['cancellation_window_hours'] ?? 24 }}" min="0">
                                <span class="input-group-text">hours before visit</span>
                            </div>
                            <small class="text-muted">Guests can cancel up to this many hours before their booking</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium text-sm">Max Guests Per Booking</label>
                            <input type="number" name="settings[max_guests_per_booking]" class="form-control"
                                   value="{{ $settings['max_guests_per_booking'] ?? 20 }}" min="1">
                            <small class="text-muted">Maximum headcount allowed in a single booking</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium text-sm">Advance Booking Days</label>
                            <input type="number" name="settings[advance_booking_days]" class="form-control"
                                   value="{{ $settings['advance_booking_days'] ?? 30 }}" min="1">
                            <small class="text-muted">How many days in advance guests can book</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Chatbot Settings --}}
            <div class="setting-section" id="tab-chatbot">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <p class="setting-group-title"><i class="bi bi-chat-dots me-2"></i>Chatbot Configuration</p>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-medium text-sm">Welcome Message</label>
                            <textarea name="settings[chatbot_welcome]" class="form-control" rows="3"
                                      id="chatbotWelcome"
                                      onkeyup="updatePreview()"
                                      placeholder="Type the greeting message...">{{ $settings['chatbot_welcome'] ?? 'Welcome to Talisay Beach Resort! How can I help you today?' }}</textarea>
                            <small class="text-muted">This is the first message guests see when they open the chatbot.</small>
                            {{-- Live Preview --}}
                            <div class="mt-3">
                                <p class="text-xs text-gray-400 mb-1">Live Preview:</p>
                                <div class="preview-bubble" id="chatPreview">
                                    {{ $settings['chatbot_welcome'] ?? 'Welcome to Talisay Beach Resort! How can I help you today?' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium text-sm">Fallback Message</label>
                            <textarea name="settings[chatbot_fallback]" class="form-control" rows="2"
                                      placeholder="Message when no intent matches...">{{ $settings['chatbot_fallback'] ?? "I'm not sure about that. Would you like to speak with our staff?" }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payment Settings --}}
            <div class="setting-section" id="tab-payment">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <p class="setting-group-title"><i class="bi bi-credit-card me-2"></i>Payment Gateway Configuration</p>
                    <div class="alert alert-info border-0 bg-blue-50 text-blue-800 mb-4">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Keep your API keys secure. Do not share these values publicly.
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-medium text-sm">Stripe Publishable Key</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                <input type="text" name="settings[stripe_key]" class="form-control"
                                       value="{{ $settings['stripe_key'] ?? '' }}" placeholder="pk_test_...">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium text-sm">Stripe Secret Key</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                <input type="password" name="settings[stripe_secret]" class="form-control"
                                       value="{{ $settings['stripe_secret'] ?? '' }}" placeholder="sk_test_...">
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="toggleField(this)"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium text-sm">GCash Reference</label>
                            <input type="text" name="settings[gcash_number]" class="form-control"
                                   value="{{ $settings['gcash_number'] ?? '' }}" placeholder="09XX-XXX-XXXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium text-sm">GCash Account Name</label>
                            <input type="text" name="settings[gcash_name]" class="form-control"
                                   value="{{ $settings['gcash_name'] ?? '' }}" placeholder="Account Name">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Save Button --}}
            <div class="bg-white rounded-xl shadow-sm p-4 mt-4 flex items-center justify-between">
                <p class="text-sm text-gray-400 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Changes are saved immediately and take effect right away.
                </p>
                <button type="submit" class="btn btn-primary px-6">
                    <i class="bi bi-check-lg me-2"></i>Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function switchTab(tab) {
    // Hide all sections
    document.querySelectorAll('.setting-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
    // Show selected
    document.getElementById('tab-' + tab).classList.add('active');
    event.currentTarget.classList.add('active');
}

function updatePreview() {
    const val = document.getElementById('chatbotWelcome').value;
    document.getElementById('chatPreview').textContent = val || 'Type a message above...';
}

function toggleField(btn) {
    const input = btn.previousElementSibling;
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
    } else {
        input.type = 'password';
        btn.innerHTML = '<i class="bi bi-eye"></i>';
    }
}
</script>
@endpush