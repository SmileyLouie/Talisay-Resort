@extends('layouts.app')

@section('title', 'System Settings - Talisay Smart Tourism')

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

</style>
@endpush

@section('content')

{{-- Header --}}
<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100/80 text-sky-800 border border-sky-200">
                <i class="bi bi-gear-fill text-[10px] text-sky-600"></i>
                Configuration
            </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-1">
            System Settings
        </h1>
        <p class="text-sm text-slate-500 mb-0">
            Configure resort parameters, visitor limits, cancellation rules, and payment gateways.
        </p>
    </div>

    <span class="text-xs font-bold px-3 py-1.5 rounded-xl bg-slate-900 text-sky-300 border border-slate-800 shadow-sm flex items-center gap-1.5">
        <i class="bi bi-shield-lock-fill text-sky-400"></i>
        <span>Admin Only</span>
    </span>
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
            <button class="settings-tab" id="tab-btn-payment" onclick="switchTab('payment')">
                <i class="bi bi-credit-card tab-icon"></i>
                <span>Payment Gateway</span>
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

            {{-- Payment Section --}}
            <div class="setting-section" id="tab-payment">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-6">
                    <div class="border-b border-slate-100 pb-3">
                        <h3 class="text-base font-extrabold text-slate-900 mb-0.5">Payment Gateway Configuration</h3>
                        <p class="text-xs text-slate-400 mb-0">API keys for Stripe card payments and GCash QR references</p>
                    </div>

                    <div class="p-3 bg-sky-50 border border-sky-200 rounded-xl text-xs text-sky-800 flex items-center gap-2">
                        <i class="bi bi-shield-check text-sky-600 text-base"></i>
                        <span>Keep your API keys confidential. Do not share credentials publicly.</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label for="setting_stripe_key" class="block text-xs font-bold text-slate-600 mb-1">Stripe Publishable Key</label>
                            <input type="text" id="setting_stripe_key" name="settings[stripe_key]" class="w-full form-control-clean text-xs font-mono"
                                   value="{{ $settings['stripe_key'] ?? '' }}" placeholder="pk_test_...">
                        </div>

                        <div class="md:col-span-2">
                            <label for="setting_stripe_secret" class="block text-xs font-bold text-slate-600 mb-1">Stripe Secret Key</label>
                            <input type="password" id="setting_stripe_secret" name="settings[stripe_secret]" class="w-full form-control-clean text-xs font-mono"
                                   value="" autocomplete="new-password" placeholder="Leave blank to keep the current key">
                            <p class="text-[11px] text-slate-400 mt-1 mb-0">{{ !empty($settings['stripe_secret']) ? 'A secret key is stored.' : 'No secret key stored.' }}</p>
                        </div>

                        <div>
                            <label for="setting_gcash_number" class="block text-xs font-bold text-slate-600 mb-1">GCash Account Number</label>
                            <input type="text" id="setting_gcash_number" name="settings[gcash_number]" class="w-full form-control-clean text-xs"
                                   value="{{ $settings['gcash_number'] ?? '' }}" placeholder="09XX-XXX-XXXX">
                        </div>

                        <div>
                            <label for="setting_gcash_name" class="block text-xs font-bold text-slate-600 mb-1">GCash Account Name</label>
                            <input type="text" id="setting_gcash_name" name="settings[gcash_name]" class="w-full form-control-clean text-xs"
                                   value="{{ $settings['gcash_name'] ?? '' }}" placeholder="Talisay Beach Resort">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Save Bar for General, Booking & Payment Settings --}}
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

        {{-- Chatbot Section (Standalone Form) --}}
        <div class="setting-section" id="tab-chatbot">
            @php
                $cfg = $chatbotConfig ?? null;
                $providers = $aiProviders ?? [];
                $currentProvider = $cfg?->provider ?? 'gemini';
                $hasKey = $cfg?->hasApiKey() ?? false;
                $isOperational = $cfg?->isOperational() ?? false;
                $lastTestedAt = $cfg?->last_tested_at;
                $lastTestStatus = $cfg?->last_test_status;
                $currentModel = $cfg?->model ?? 'gemini-1.5-flash';
                $currentTemp = $cfg?->temperature ?? 0.70;
                $currentTokens = $cfg?->max_tokens ?? 600;
                $currentLang = $cfg?->response_language ?? 'en';
                $currentPersonality = $cfg?->personality ?? 'friendly';
                $welcomeMsg = $cfg?->welcome_message ?? ($settings['chatbot_welcome'] ?? 'Welcome to Talisay Beach Resort! How can I help you today?');
                $fallbackMsg = $cfg?->fallback_message ?? ($settings['chatbot_fallback'] ?? "I'm not sure about that. Would you like to speak with our resort front desk staff?");
                $systemPrompt = $cfg?->system_prompt ?? "You are the official Talisay Beach Resort AI tourism assistant in Baybay City, Leyte, Philippines. Help visitors, tourists, and staff with resort information, room rates, cottage availability, facilities, policies, and booking inquiries. Be courteous, concise, and helpful. Never invent information not available in the system.";
            @endphp

            <form method="POST" action="{{ route('settings.chatbot.save') }}" id="chatbotForm" data-loading>
                @csrf

                {{-- Section Header --}}
                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 mb-1">AI Chatbot Configuration</h3>
                            <p class="text-sm text-slate-500 mb-0">Configure the AI assistant that serves tourists and staff on the resort platform.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-semibold text-slate-700">{{ ($cfg?->is_enabled ?? true) ? 'Enabled' : 'Disabled' }}</span>
                            <label class="relative inline-flex items-center cursor-pointer" title="Enable / Disable AI Chatbot">
                                <input type="hidden" name="is_enabled" value="0">
                                <input type="checkbox" name="is_enabled" value="1" class="sr-only peer"
                                       id="chatbotEnabledToggle"
                                       {{ ($cfg?->is_enabled ?? true) ? 'checked' : '' }}
                                       onchange="document.querySelector('[for=chatbotEnabledToggle] ~ span.toggle-label') && (document.querySelector('[for=chatbotEnabledToggle] ~ span.toggle-label').textContent = this.checked ? 'Enabled' : 'Disabled')">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-sky-600"></div>
                            </label>
                        </div>
                    </div>

                    {{-- Status Badge --}}
                    @if($isOperational)
                        <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-semibold text-emerald-700">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Active & Operational &middot; Model: {{ $currentModel }}
                            @if($lastTestedAt) &middot; Verified {{ $lastTestedAt->diffForHumans() }} @endif
                        </div>
                    @elseif($cfg && $cfg->is_enabled && !$hasKey)
                        <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-amber-50 border border-amber-200 rounded-xl text-xs font-semibold text-amber-700">
                            <i class="bi bi-exclamation-triangle-fill"></i> API Key Required to activate chatbot
                        </div>
                    @else
                        <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-500">
                            <i class="bi bi-pause-circle"></i> Chatbot is currently disabled
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    {{-- LEFT: Provider & API Key --}}
                    <div class="space-y-5">

                        {{-- AI Provider --}}
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
                            <h4 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <i class="bi bi-cpu text-sky-600"></i> AI Provider & Model
                            </h4>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="providerSelect">Provider</label>
                                    <select name="provider" id="providerSelect"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
                                            onchange="onProviderChange(this.value)">
                                        @foreach($providers as $pid => $pdata)
                                            <option value="{{ $pid }}" {{ $currentProvider === $pid ? 'selected' : '' }}>
                                                {{ $pdata['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="modelInput">Model</label>
                                    {{-- Per-provider model dropdowns --}}
                                    @foreach($providers as $pid => $pdata)
                                    <div id="modelOptions_{{ $pid }}" class="provider-model-group {{ $currentProvider !== $pid ? 'hidden' : '' }} mb-2">
                                        @if(!empty($pdata['models']))
                                        <select class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-100 mb-2 provider-model-select"
                                                data-provider="{{ $pid }}"
                                                onchange="syncModelInput(this.value, '{{ $pid }}')">
                                            <option value="">Select a model</option>
                                            @foreach($pdata['models'] as $modelId => $modelLabel)
                                            <option value="{{ $modelId }}"
                                                {{ (($cfg?->model ?? $pdata['default_model']) === $modelId && $currentProvider === $pid) ? 'selected' : '' }}>
                                                {{ $modelLabel }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @endif
                                    </div>
                                    @endforeach
                                    <input type="text" name="model" id="modelInput"
                                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-mono text-slate-800 focus:outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
                                           value="{{ $currentModel }}"
                                           placeholder="e.g. gemini-1.5-flash"
                                           oninput="syncModelSelectToInput(this.value)"
                                           required>
                                    <p class="text-xs text-slate-400 mt-1">Exact model identifier sent to the provider.</p>
                                </div>

                                {{-- Custom Endpoint (hidden unless custom provider) --}}
                                <div id="customEndpointRow" class="{{ $currentProvider !== 'custom' ? 'hidden' : '' }}">
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Custom API Base URL</label>
                                    <input type="url" name="api_endpoint" id="apiEndpointInput"
                                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-mono text-slate-800 focus:outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
                                           value="{{ $cfg?->api_endpoint ?? '' }}"
                                           placeholder="https://api.openai.com/v1">
                                </div>
                            </div>
                        </div>

                        {{-- API Key --}}
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
                            <h4 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <i class="bi bi-key-fill text-indigo-600"></i> API Credentials
                                <span class="ml-auto text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full border border-emerald-200">
                                    <i class="bi bi-shield-lock-fill"></i> Encrypted
                                </span>
                            </h4>

                            @if($hasKey)
                            <div id="maskedKeyRow" class="space-y-3">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-mono text-slate-500 flex items-center gap-2">
                                        <i class="bi bi-lock-fill text-emerald-500"></i>
                                        <span>{{ $cfg->maskedApiKey }}</span>
                                    </div>
                                    <button type="button" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors" onclick="showChangeKeyRow()">
                                        <i class="bi bi-pencil-square"></i> Change
                                    </button>
                                </div>
                                <p class="text-xs text-emerald-600 flex items-center gap-1">
                                    <i class="bi bi-check-circle-fill"></i> Stored & server-side encrypted
                                </p>
                            </div>

                            <div id="changeKeyRow" class="hidden space-y-3 p-4 bg-slate-50 rounded-xl border border-slate-200">
                                <div class="flex items-center justify-between mb-1">
                                    <label class="text-xs font-semibold text-slate-700" for="apiKeyInput">New API Key</label>
                                    <button type="button" class="text-xs text-slate-400 hover:text-slate-700" onclick="cancelChangeKey()"><i class="bi bi-arrow-return-left" aria-hidden="true"></i> Keep existing</button>
                                </div>
                                <div class="relative">
                                    <input type="password" name="api_key" id="apiKeyInput"
                                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-mono pr-10 focus:outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
                                           value=""
                                           placeholder="{{ $cfg->maskedApiKey }}"
                                           autocomplete="new-password">
                                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" onclick="toggleApiKeyVis()" aria-label="Show or hide API key">
                                        <i class="bi bi-eye-slash-fill" id="apiKeyEyeIcon" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="remove_api_key" value="1"
                                           class="rounded border-slate-300 text-rose-600" id="removeApiKeyCheck"
                                           onchange="onRemoveKeyToggle(this)">
                                    <span class="text-xs text-rose-600 font-semibold">Remove stored API key from database</span>
                                </label>
                            </div>

                            @else
                            <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-2 mb-3">
                                <i class="bi bi-exclamation-triangle-fill text-amber-500 mt-0.5 flex-shrink-0"></i>
                                <p class="text-xs text-amber-800 mb-0">No API key configured. The chatbot cannot generate responses until a valid key is set.</p>
                            </div>
                            <div class="relative">
                                <label for="apiKeyInput" class="sr-only">API Key</label>
                                <input type="password" name="api_key" id="apiKeyInput"
                                       class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-mono pr-10 focus:outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
                                       value=""
                                       placeholder="Paste your API key (e.g. AIzaSy... or sk-...)"
                                       autocomplete="new-password">
                                <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" onclick="toggleApiKeyVis()" aria-label="Show or hide API key">
                                    <i class="bi bi-eye-slash-fill" id="apiKeyEyeIcon" aria-hidden="true"></i>
                                </button>
                            </div>
                            @endif

                            {{-- Connection Test --}}
                            <div class="mt-4 pt-4 border-t border-slate-100">
                                <button type="button"
                                        id="testConnectionBtn"
                                        onclick="testAIConnection()"
                                        class="w-full bg-slate-900 hover:bg-slate-700 text-white text-xs font-semibold py-2.5 px-4 rounded-xl flex items-center justify-center gap-2 transition-colors">
                                    <i class="bi bi-wifi" id="testConnIcon"></i>
                                    <span id="testConnText">Test API Connection</span>
                                </button>
                                <div id="testConnectionResult" class="hidden mt-3 p-3 rounded-xl border text-xs"></div>
                            </div>
                        </div>

                    </div>

                    {{-- RIGHT: Tuning & Messages --}}
                    <div class="space-y-5">

                        {{-- Generation Parameters --}}
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
                            <h4 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <i class="bi bi-sliders2 text-violet-600"></i> Response Parameters
                            </h4>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="tempSlider">Temperature
                                        <span id="tempVal" class="font-mono font-bold text-sky-700 ml-1">{{ number_format($currentTemp, 2) }}</span>
                                    </label>
                                    <input type="range" name="temperature" id="tempSlider"
                                           min="0" max="2" step="0.05"
                                           value="{{ $currentTemp }}"
                                           class="w-full h-2 rounded-full bg-slate-200 appearance-none cursor-pointer accent-sky-600"
                                           oninput="document.getElementById('tempVal').textContent = parseFloat(this.value).toFixed(2)">
                                    <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                                        <span>Precise</span><span>Creative</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="maxTokensInput">Max Tokens</label>
                                    <input type="number" name="max_tokens" id="maxTokensInput"
                                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
                                           value="{{ $currentTokens }}"
                                           min="50" max="8000" step="50">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="responseLanguageSelect">Language</label>
                                    <select name="response_language" id="responseLanguageSelect"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-100">
                                        <option value="en"   {{ $currentLang === 'en'   ? 'selected' : '' }}>ðŸ‡¬ðŸ‡§ English</option>
                                        <option value="fil"  {{ $currentLang === 'fil'  ? 'selected' : '' }}>ðŸ‡µðŸ‡­ Filipino</option>
                                        <option value="ceb"  {{ $currentLang === 'ceb'  ? 'selected' : '' }}>ðŸŒ´ Cebuano</option>
                                        <option value="auto" {{ $currentLang === 'auto' ? 'selected' : '' }}>ðŸŒ Auto-Detect</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="personalitySelect">Personality</label>
                                    <select name="personality" id="personalitySelect"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-100">
                                        <option value="friendly"     {{ $currentPersonality === 'friendly'     ? 'selected' : '' }}>ðŸ˜Š Friendly</option>
                                        <option value="professional" {{ $currentPersonality === 'professional' ? 'selected' : '' }}>ðŸ‘” Professional</option>
                                        <option value="casual"       {{ $currentPersonality === 'casual'       ? 'selected' : '' }}>â˜• Casual</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- System Prompt --}}
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                    <i class="bi bi-chat-square-text text-sky-600"></i> System Prompt
                                </h4>
                                <button type="button" class="text-xs text-sky-600 hover:text-sky-800 font-semibold" onclick="loadDefaultPrompt()">â†º Reset</button>
                            </div>
                            <textarea name="system_prompt" id="systemPromptInput"
                                      rows="5"
                                      class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-mono text-slate-700 focus:outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-100 leading-relaxed resize-y"
                                      placeholder="You are the official Talisay Beach Resort AI assistant..."
                                      oninput="updatePromptCount()">{{ $systemPrompt }}</textarea>
                            <p class="text-xs text-slate-400 mt-1" id="promptCharCount"></p>
                        </div>

                        {{-- Chat Messages --}}
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
                            <h4 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <i class="bi bi-chat-dots text-emerald-600"></i> Chat Messages
                            </h4>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="chatbotWelcome">Welcome Message</label>
                                    <textarea name="welcome_message" id="chatbotWelcome" rows="3"
                                              class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-100 leading-relaxed resize-none"
                                              placeholder="e.g. Welcome to Talisay Beach Resort! How can I help you today?"
                                              onkeyup="updatePreview()" oninput="updatePreview()">{{ $welcomeMsg }}</textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="fallbackMessageInput">Fallback Message</label>
                                    <textarea name="fallback_message" id="fallbackMessageInput" rows="2"
                                              class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-100 leading-relaxed resize-none"
                                              placeholder="e.g. I'm not sure about that. Would you like to speak with our resort staff?">{{ $fallbackMsg }}</textarea>
                                </div>

                                {{-- Simple Preview --}}
                                <div class="p-3 bg-sky-50 border border-sky-200 rounded-xl">
                                    <p class="text-[10px] font-bold text-sky-700 uppercase tracking-wider mb-2">Preview</p>
                                    <div class="preview-bubble" id="chatPreview">{{ $welcomeMsg }}</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Save Button --}}
                <div class="mt-6 flex justify-end">
                    <button type="submit" class="btn-ocean text-sm font-bold px-8 py-3 rounded-xl shadow-md hover:shadow-lg flex items-center gap-2">
                        <i class="bi bi-check2-circle"></i>
                        Save AI Chatbot Settings
                    </button>
                </div>

            </form>
        </div>
        {{-- User Accounts Section --}}
        <div class="setting-section" id="tab-users">
            {{-- KPI Stats --}}
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Users</span>
                        <div class="w-7 h-7 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center text-xs">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                    <div class="text-xl font-extrabold text-slate-900">{{ $totalUsers ?? 0 }}</div>
                    <span class="text-[11px] text-slate-400">All registered profiles</span>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Admins</span>
                        <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-xs">
                            <i class="bi bi-shield-fill"></i>
                        </div>
                    </div>
                    <div class="text-xl font-extrabold text-rose-600">{{ $adminCount ?? 0 }}</div>
                    <span class="text-[11px] text-slate-400">Full system control</span>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Staff</span>
                        <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">
                            <i class="bi bi-person-badge-fill"></i>
                        </div>
                    </div>
                    <div class="text-xl font-extrabold text-indigo-600">{{ $staffCount ?? 0 }}</div>
                    <span class="text-[11px] text-slate-400">Front desk & operations</span>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Tourists</span>
                        <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                            <i class="bi bi-person-check-fill"></i>
                        </div>
                    </div>
                    <div class="text-xl font-extrabold text-emerald-600">{{ $touristCount ?? 0 }}</div>
                    <span class="text-[11px] text-slate-400">Registered resort guests</span>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm col-span-2 lg:col-span-1">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Active</span>
                        <div class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center text-xs">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                    </div>
                    <div class="text-xl font-extrabold text-teal-600">{{ $activeCount ?? 0 }}</div>
                    <span class="text-[11px] text-slate-400">{{ $inactiveCount ?? 0 }} suspended</span>
                </div>
            </div>

            {{-- Main User Accounts Card --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-6">
                {{-- Header with action button --}}
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 mb-0.5">User Accounts Management</h3>
                        <p class="text-xs text-slate-400 mb-0">View, search, filter, and manage all accounts across the resort system</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('tasks.staff') }}" class="btn-secondary-clean text-xs px-3 py-2 flex items-center gap-1.5" title="Manage Staff Permissions">
                            <i class="bi bi-shield-lock-fill text-indigo-500"></i>
                            <span>Staff RBAC</span>
                        </a>
                        <button type="button" class="btn-ocean text-xs px-4 py-2 flex items-center gap-1.5" onclick="openCreateUserModal()">
                            <i class="bi bi-person-plus-fill"></i>
                            <span>Add New Account</span>
                        </button>
                    </div>
                </div>

                {{-- Search & Filter Bar --}}
                <div class="p-4 bg-slate-50/60 border-b border-slate-100">
                    <form method="GET" action="{{ route('settings.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                        <input type="hidden" name="tab" value="users">
                        
                        <div class="sm:col-span-7">
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Search User</label>
                            <div class="relative">
                                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input type="text" name="search" class="w-full pl-9 form-control-clean text-xs" placeholder="Search by name, email, or phone..." value="{{ request('search') }}">
                            </div>
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Filter Role</label>
                            <select name="role" class="w-full form-select-clean text-xs font-medium" onchange="this.form.submit()">
                                <option value="">All Roles</option>
                                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                                <option value="staff" {{ request('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                                <option value="tourist" {{ request('role') === 'tourist' ? 'selected' : '' }}>Tourist</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2 flex gap-1.5">
                            <button type="submit" class="btn-ocean text-xs flex-1 justify-center py-2">
                                Filter
                            </button>
                            @if(request()->hasAny(['search', 'role']))
                            <a href="{{ route('settings.index', ['tab' => 'users']) }}" class="btn-secondary-clean text-xs px-2.5 py-2 flex items-center justify-center" title="Clear Filters">
                                <i class="bi bi-x-lg"></i>
                            </a>
                            @endif
                        </div>
                    </form>
                </div>

                {{-- Users Table --}}
                <div class="overflow-x-auto">
                    <table class="table-clean">
                        <thead>
                            <tr>
                                <th>Account Holder</th>
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
                                    <div class="flex items-center gap-3">
                                        @if($u->avatar)
                                            <img src="{{ Storage::url($u->avatar) }}" class="w-9 h-9 rounded-xl object-cover border border-slate-200" alt="{{ $u->name }}">
                                        @else
                                            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-sky-600 to-sky-400 text-white font-bold flex items-center justify-center text-xs shadow-sm">
                                                {{ initials($u->name) }}
                                            </div>
                                        @endif
                                        <div>
                                            <div class="font-bold text-slate-900 text-xs flex items-center gap-1.5">
                                                <span>{{ $u->name }}</span>
                                                @if($u->id === auth()->id())
                                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-sky-100 text-sky-700 uppercase">You</span>
                                                @endif
                                            </div>
                                            <div class="text-[11px] text-slate-400 flex items-center gap-1">
                                                <span>{{ $u->email }}</span>
                                                @if($u->email_verified_at)
                                                    <i class="bi bi-patch-check-fill text-sky-500 text-[11px]" title="Email verified"></i>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    @php
                                        $roleStyles = [
                                            'admin'   => 'bg-rose-50 text-rose-700 border-rose-200',
                                            'staff'   => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                            'tourist' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        ];
                                    @endphp
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border uppercase tracking-wider {{ $roleStyles[$u->role] ?? 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                        {{ ucfirst($u->role) }}
                                    </span>
                                </td>

                                <td>
                                    <span class="text-xs text-slate-600 font-mono">{{ $u->phone ?: '—' }}</span>
                                </td>

                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[11px] text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md font-medium" title="Total Bookings">
                                            <i class="bi bi-calendar-check me-1 text-slate-400"></i>{{ $u->bookings_count ?? 0 }}
                                        </span>
                                        <span class="text-[11px] text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md font-medium" title="Total Reviews">
                                            <i class="bi bi-star me-1 text-amber-400"></i>{{ $u->reviews_count ?? 0 }}
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    <span class="text-xs text-slate-500 font-medium">{{ $u->created_at ? $u->created_at->format('M d, Y') : '—' }}</span>
                                </td>

                                <td class="text-end">
                                    <div class="flex items-center justify-end gap-1">
                                        {{-- View Details --}}
                                        <button type="button" class="p-1.5 rounded-lg text-slate-500 hover:text-sky-600 hover:bg-sky-50 transition" title="View Profile Info"
                                                onclick='viewUser(@json($u))'>
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        {{-- Quick Toggle Status --}}
                                        @if($u->id !== auth()->id() && $u->role !== 'admin')
                                        <form method="POST" action="{{ route('users.toggle-status', $u) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="p-1.5 rounded-lg {{ $u->is_active ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }} transition"
                                                    title="{{ $u->is_active ? 'Suspend Account' : 'Activate Account' }}">
                                                <i class="bi {{ $u->is_active ? 'bi-person-slash' : 'bi-person-check' }}"></i>
                                            </button>
                                        </form>
                                        @endif

                                        {{-- Reset Password --}}
                                        <button type="button" class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition" title="Reset Password"
                                                onclick="openResetPasswordModal({{ $u->id }}, {{ Js::from($u->name) }})">
                                            <i class="bi bi-key"></i>
                                        </button>

                                        {{-- Edit Account --}}
                                        <button type="button" class="p-1.5 rounded-lg text-slate-500 hover:text-sky-600 hover:bg-sky-50 transition" title="Edit User"
                                                onclick='openEditUserModal(@json($u))'>
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        {{-- Staff RBAC shortcut --}}
                                        @if($u->isStaff())
                                        <a href="{{ route('tasks.staff', ['tab' => 'rbac', 'search' => $u->email]) }}" class="p-1.5 rounded-lg text-indigo-600 hover:bg-indigo-50 transition" title="Manage Staff RBAC Permissions">
                                            <i class="bi bi-shield-lock"></i>
                                        </a>
                                        @endif

                                        {{-- Delete User --}}
                                        @if($u->id !== auth()->id() && $u->role !== 'admin')
                                        <button type="button" class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 transition" title="Delete Account"
                                                onclick="confirmDeleteUser({{ $u->id }}, {{ Js::from($u->name) }})">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-12 text-slate-400">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-2xl">
                                        <i class="bi bi-people"></i>
                                    </div>
                                    <p class="text-xs font-bold text-slate-600 mb-1">No user accounts found</p>
                                    <p class="text-[11px] text-slate-400 mb-0">Try changing your search terms or filters.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination Links --}}
                @if($users->hasPages())
                <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex justify-end">
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
                        <i class="bi bi-person-plus-fill"></i>
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
                        <i class="bi bi-person-badge-fill text-indigo-600"></i>
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
                            <i class="bi bi-shield-lock-fill"></i>
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
                    <i class="bi bi-key-fill text-lg"></i>
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
                <i class="bi bi-exclamation-triangle-fill"></i>
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

@endsection

@push('scripts')
<script>
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

    if (window.history && window.history.replaceState) {
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
        if (icon) { icon.classList.remove('bi-eye-slash-fill'); icon.classList.add('bi-eye-fill'); }
    } else {
        input.type = 'password';
        if (icon) { icon.classList.remove('bi-eye-fill'); icon.classList.add('bi-eye-slash-fill'); }
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

    btn.disabled = true;
    icon.className = 'bi bi-arrow-repeat animate-spin';
    text.textContent = 'Testing...';
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
            resultBox.innerHTML = `<i class="bi bi-check-circle-fill mr-1"></i> <strong>Connection verified!</strong> ${escHtml(data.message)}${data.latency_ms ? ` (${data.latency_ms}ms)` : ''}${data.sample_reply ? `<div class="mt-1 text-slate-600">"${escHtml(data.sample_reply)}"</div>` : ''}`;
            icon.className = 'bi bi-check-circle-fill text-emerald-500';
            text.textContent = 'Verified';
        } else {
            resultBox.className = 'mt-3 p-3 rounded-xl border text-xs bg-rose-50 border-rose-200 text-rose-800';
            resultBox.innerHTML = `<i class="bi bi-x-circle-fill mr-1"></i> <strong>Failed:</strong> ${escHtml(data.message ?? 'Unknown error')}`;
            icon.className = 'bi bi-x-circle-fill text-rose-500';
            text.textContent = 'Failed';
        }
    } catch (err) {
        resultBox.classList.remove('hidden');
        resultBox.className = 'mt-3 p-3 rounded-xl border text-xs bg-rose-50 border-rose-200 text-rose-800';
        resultBox.innerHTML = `<i class="bi bi-x-circle-fill mr-1"></i> Network error: ${escHtml(err.message)}`;
        icon.className = 'bi bi-x-circle-fill text-rose-500';
        text.textContent = 'Error';
    } finally {
        btn.disabled = false;
        setTimeout(() => {
            icon.className = 'bi bi-wifi';
            text.textContent = 'Test API Connection';
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
    const tabParam = urlParams.get('tab') || 'general';
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

    const currentProvider = document.getElementById('providerSelect')?.value || 'gemini';
    if (currentProvider) {
        onProviderChange(currentProvider);
    }
});
</script>
@endpush
