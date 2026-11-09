@php
    $cfg = $chatbotConfig ?? null;
    $providers = $aiProviders ?? [];
    $currentProvider = $cfg?->provider ?? 'gemini';
    $hasKey = $cfg?->hasApiKey() ?? false;
    $currentModel = $cfg?->model ?? 'gemini-3.6-flash';
    $endpoint = $cfg?->api_endpoint ?: match ($currentProvider) {
        'openai' => 'https://api.openai.com/v1',
        'anthropic' => 'https://api.anthropic.com',
        'gemini' => 'https://generativelanguage.googleapis.com/v1beta',
        default => '',
    };
    $hideContacts = \App\Models\SystemSetting::get('ai_hide_contacts', '1') !== '0';
@endphp

<form method="POST" action="{{ route('settings.chatbot.save') }}" id="chatbotForm" class="fm-ai">
    @csrf
    <input type="hidden" name="temperature" value="{{ $cfg?->temperature ?? 0.7 }}">
    <input type="hidden" name="max_tokens" value="{{ $cfg?->max_tokens ?? 600 }}">
    <input type="hidden" name="response_language" value="{{ $cfg?->response_language ?? 'en' }}">
    <input type="hidden" name="personality" value="{{ $cfg?->personality ?? 'friendly' }}">
    <input type="hidden" name="welcome_message" value="{{ $cfg?->welcome_message ?? '' }}">
    <input type="hidden" name="fallback_message" value="{{ $cfg?->fallback_message ?? '' }}">

    <div class="fm-panel">
        <div class="fm-ai-head">
            <div>
                <h2>AI assistant (bring your own key)</h2>
                <p>Used by the guest chatbot and the staff test chat. The key stays on this server.</p>
            </div>
        </div>

        <label class="fm-ai-check">
            <input type="hidden" name="is_enabled" value="0">
            <input type="checkbox" name="is_enabled" value="1" {{ ($cfg?->is_enabled ?? true) ? 'checked' : '' }}>
            <span>Enabled</span>
        </label>

        <label class="fm-ai-field">
            <span>Provider</span>
            <select name="provider" id="providerSelect" class="form-select-clean" onchange="onProviderChange(this.value)">
                @foreach($providers as $pid => $pdata)
                    <option value="{{ $pid }}" @selected($currentProvider === $pid)>{{ $pdata['name'] }}</option>
                @endforeach
            </select>
        </label>

        <div class="fm-ai-split">
            <label class="fm-ai-field">
                <span>Base URL</span>
                <input type="url" name="api_endpoint" id="apiEndpointInput" class="form-control-clean" value="{{ $endpoint }}" placeholder="https://api.openai.com/v1">
            </label>
            <label class="fm-ai-field">
                <span>Model</span>
                <input type="text" name="model" id="modelInput" class="form-control-clean" value="{{ $currentModel }}" required>
            </label>
        </div>

        <label class="fm-ai-field">
            <span>API key</span>
            <input type="password" name="api_key" id="apiKeyInput" class="form-control-clean" value="" autocomplete="new-password" placeholder="{{ $hasKey ? 'unchanged' : 'Paste an API key' }}">
        </label>
        @if($hasKey)
            <p class="fm-ai-note">A key is already saved ({{ $cfg->maskedApiKey }}). Leave this blank to keep it.</p>
        @endif

        <label class="fm-ai-field">
            <span>Extra instructions</span>
            <textarea name="system_prompt" id="systemPromptInput" rows="4" class="form-control-clean" placeholder="Resort tone, what to mention, what to avoid.">{{ $cfg?->system_prompt }}</textarea>
        </label>

        <label class="fm-ai-check">
            <input type="hidden" name="hide_contacts" value="0">
            <input type="checkbox" name="hide_contacts" value="1" {{ $hideContacts ? 'checked' : '' }}>
            <span>Never send contact details to the AI provider<span class="hint">Phone and ID numbers are shortened before they leave the resort.</span></span>
        </label>

        <div class="fm-ai-actions">
            <button type="submit" class="btn-ocean">Save</button>
            <button type="button" class="btn-secondary-clean" id="testConnectionBtn" onclick="testAIConnection()">Test connection</button>
        </div>
        <div id="testConnectionResult" class="hidden"></div>
    </div>
</form>
