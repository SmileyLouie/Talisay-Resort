@extends('layouts.app')

@section('title', 'Chatbot Management - Talisay Smart Tourism')

@section('content')

@php
    $initials = fn ($name) => collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: 'G';
@endphp

<div class="res-head bot-page-head">
    <div>
        <h1>{{ __('AI Chatbot') }}</h1>
        <p>
            {{ __('Review visitor chats and try answers before guests see them.') }}
            @if($geminiConfigured)
                <span class="bot-status is-ready">{{ __('AI ready') }} · {{ $geminiModel }}</span>
            @else
                <span class="bot-status is-off">{{ __('AI not set up') }}</span>
            @endif
        </p>
    </div>
    <div class="res-head-actions">
        <button type="button" class="btn-secondary-clean" id="botOpenKnowledge">
            <i class="bi bi-sliders"></i>
            <span>{{ __('Knowledge') }}</span>
        </button>
        @if($totalInteractions > 0)
        <form method="POST" action="{{ route('chatbot.logs.clear') }}" onsubmit="return confirm(@json(__('Clear all chatbot conversation logs?')))">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-secondary-clean bot-clear-btn">
                <i class="bi bi-trash"></i>
                <span>{{ __('Clear logs') }}</span>
            </button>
        </form>
        @endif
    </div>
</div>

<div class="bot-workspace">
    <aside class="bot-rail">
        <button type="button" class="btn-secondary-clean bot-new-chat" id="botNewChat">
            <i class="bi bi-plus-lg"></i>
            <span>{{ __('New chat') }}</span>
        </button>

        <div class="bot-rail-label">{{ __('Recent chats') }} <em>{{ $logs->total() }}</em></div>

        <div class="bot-rail-list" id="botChatList">
            @forelse($logs as $log)
                @php
                    $visitor = $log->user?->name ?? 'Guest';
                    $preview = Str::limit($log->message, 64);
                    $payload = [
                        'visitor' => $visitor,
                        'time' => $log->created_at->format('M j, g:i A'),
                        'question' => (string) $log->message,
                        'answer' => (string) $log->response,
                    ];
                @endphp
                <button type="button"
                        class="bot-rail-item"
                        data-log-id="{{ $log->id }}"
                        data-payload='@json($payload)'>
                    <span class="pay-avatar staff-avatar">{{ $initials($visitor) }}</span>
                    <span class="bot-rail-copy">
                        <strong>{{ $visitor }}</strong>
                        <em>{{ $preview }}</em>
                        <small>{{ $log->created_at->diffForHumans() }}</small>
                    </span>
                </button>
            @empty
                <div class="bot-rail-empty">
                    <i class="bi bi-chat-left-dots"></i>
                    <p>{{ __('Your chats will show up here.') }}</p>
                </div>
            @endforelse
        </div>

        @if($logs->hasPages())
        <div class="bot-rail-pages">
            {{ $logs->links() }}
        </div>
        @endif
    </aside>

    <section class="bot-stage" id="botStage">
        {{-- Simulator (default / New chat) --}}
        <div class="bot-stage-view is-active" id="botSimView">
            <div class="bot-stage-head">
                <div>
                    <h2>{{ __('Test chat') }}</h2>
                    <p>{{ __('Try answers before guests see them.') }}</p>
                </div>
                <div class="bot-sim-tools">
                    <select id="simRoleSelect" class="form-select-clean bot-role" onchange="resetSimulatorChat()" aria-label="{{ __('Guest (public)') }}">
                        <option value="guest" selected>{{ __('Guest (public)') }}</option>
                        <option value="tourist">{{ __('Tourist (logged in)') }}</option>
                        <option value="staff">{{ __('Staff') }}</option>
                        <option value="admin">{{ __('Admin') }}</option>
                    </select>
                    <button type="button" onclick="resetSimulatorChat()" class="res-icon-btn" title="{{ __('Reset chat') }}">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>

            <div id="testChatBox" class="bot-sim-stream" aria-live="polite"></div>

            <div class="bot-sim-chips">
                <div id="quickTestPrompts">
                    <button type="button" data-chip="What are your room rates?">{{ __('Room rates') }}</button>
                    <button type="button" data-chip="Tell me about your cottages and amenities">{{ __('Cottages & amenities') }}</button>
                    <button type="button" data-chip="What time is check in and check out?">{{ __('Check-in / check-out') }}</button>
                    <button type="button" data-chip="Can we hold an event or full-resort booking?">{{ __('Full resort booking') }}</button>
                </div>
            </div>

            <div class="bot-sim-compose">
                <input type="text"
                       id="testChatInput"
                       placeholder="{{ $geminiConfigured ? __('Ask about rates, hours, or amenities...') : __('Add an AI key in System Settings to start chatting.') }}"
                       class="form-control-clean"
                       @disabled(! $geminiConfigured)
                       onkeydown="if(event.key==='Enter') testChat()">
                <button type="button" id="testChatSendBtn" onclick="testChat()" class="btn-ocean bot-send" aria-label="{{ __('Send message') }}" @disabled(! $geminiConfigured)>
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
        </div>

        {{-- Selected log conversation --}}
        <div class="bot-stage-view" id="botLogView" hidden>
            <div class="bot-stage-head">
                <div>
                    <h2 id="botLogTitle">{{ __('Conversation') }}</h2>
                    <p id="botLogMeta">—</p>
                </div>
                <button type="button" class="btn-secondary-clean" id="botBackToSim">
                    <i class="bi bi-plus-lg"></i>
                    <span>{{ __('New chat') }}</span>
                </button>
            </div>
            <div class="bot-sim-stream bot-log-stream" id="botLogStream"></div>
        </div>

        {{-- Knowledge form --}}
        <div class="bot-stage-view" id="botKnowledgeView" hidden>
            <div class="bot-stage-head">
                <div>
                    <h2>{{ __('Knowledge & instructions') }}</h2>
                    <p>Tone, resort rules, announcements, and suggestion chips.</p>
                </div>
                <button type="button" class="btn-secondary-clean" id="botCloseKnowledge">
                    <i class="bi bi-x-lg"></i>
                    <span>{{ __('Close') }}</span>
                </button>
            </div>
            <form method="POST" action="{{ route('chatbot.settings.update') }}" class="bot-form" data-loading>
                @csrf
                <div class="manual-row manual-row-top">
                    <label for="chatbot_persona">{{ __('Persona') }}</label>
                    <div>
                        <textarea id="chatbot_persona" name="chatbot_persona" rows="3" class="form-control-clean w-100" placeholder="Friendly resort concierge for Talisay Beach Resort...">{{ old('chatbot_persona', $persona) }}</textarea>
                        <span class="manual-hint">How the assistant introduces itself and speaks to guests.</span>
                    </div>
                </div>
                <div class="manual-row manual-row-top">
                    <label for="chatbot_custom_rules">{{ __('Resort rules') }}</label>
                    <div>
                        <textarea id="chatbot_custom_rules" name="chatbot_custom_rules" rows="4" class="form-control-clean w-100 bot-mono" placeholder="Check-in 2:00 PM. Check-out 12:00 PM...">{{ old('chatbot_custom_rules', $customRules) }}</textarea>
                        <span class="manual-hint">Policies the assistant should follow.</span>
                    </div>
                </div>
                <div class="manual-row">
                    <label for="chatbot_announcement">{{ __('Announcement') }}</label>
                    <div>
                        <input type="text" id="chatbot_announcement" name="chatbot_announcement" value="{{ old('chatbot_announcement', $announcement) }}" class="form-control-clean w-100" placeholder="Promo, maintenance notice, or event...">
                    </div>
                </div>
                <div class="manual-row">
                    <label for="chatbot_chips">Suggestion chips</label>
                    <div>
                        <input type="text" id="chatbot_chips" name="chatbot_chips" value="{{ old('chatbot_chips', $quickChips) }}" class="form-control-clean w-100" placeholder="Room Rates, Cottage Rates, Check-in Times">
                        <span class="manual-hint">Comma-separated quick questions for public guests.</span>
                    </div>
                </div>
                <div class="manual-foot bot-form-foot">
                    <button type="submit" class="btn-ocean">
                        <i class="bi bi-check2-circle"></i>
                        <span>Save knowledge</span>
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>

@endsection

@push('scripts')
<script>
let conversationHistory = [];

function escapeHtml(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatSimulatorMessage(text) {
    if (!text) return '';
    let escaped = String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    escaped = escaped.replace(/^[•\-\*]\s+(.*)$/gm, '<div class="bot-bullet"><span>•</span><span>$1</span></div>');

    return escaped
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\n/g, '<br>');
}

function showBotView(viewId) {
    document.querySelectorAll('.bot-stage-view').forEach(view => {
        const on = view.id === viewId;
        view.classList.toggle('is-active', on);
        view.hidden = !on;
    });
    if (viewId !== 'botLogView') {
        document.querySelectorAll('.bot-rail-item').forEach(item => item.classList.remove('is-active'));
    }
}

function testChipClick(chipText) {
    const input = document.getElementById('testChatInput');
    if (input) input.value = chipText;
    testChat();
}

function resetSimulatorChat() {
    conversationHistory = [];
    const box = document.getElementById('testChatBox');
    const roleSelect = document.getElementById('simRoleSelect');
    const role = roleSelect ? roleSelect.value : 'guest';

    let greeting = '';
    if (role === 'guest') {
        greeting = '<strong>Hello!</strong> I am the AI resort concierge for Talisay Beach Resort. Ask me about room rates, cottages, check-in rules, or amenities.';
    } else if (role === 'tourist') {
        greeting = '<strong>Hello!</strong> I can help with your bookings, payments, or activities at the resort.';
    } else {
        greeting = '<strong>Hello!</strong> Ask me about today’s capacity, schedules, or facility status.';
    }

    if (!box) return;
    box.innerHTML = `<div class="bot-bubble bot-bubble-bot">${greeting}</div>`;
}

async function testChat() {
    const input = document.getElementById('testChatInput');
    const sendBtn = document.getElementById('testChatSendBtn');
    if (!input || input.disabled) return;
    const msg = input.value.trim();
    if (!msg) return;

    const roleSelect = document.getElementById('simRoleSelect');
    const simulatedRole = roleSelect ? roleSelect.value : 'guest';
    const box = document.getElementById('testChatBox');

    box.innerHTML += `<div class="bot-bubble bot-bubble-user">${escapeHtml(msg)}</div>`;
    input.value = '';
    input.disabled = true;
    if (sendBtn) sendBtn.disabled = true;

    const typingId = 'simTyping_' + Date.now();
    box.innerHTML += `
        <div id="${typingId}" class="bot-bubble bot-bubble-bot bot-typing">
            <span></span><span></span><span></span>
            <em>Typing…</em>
        </div>
    `;
    box.scrollTop = box.scrollHeight;

    try {
        const res = await fetch('/api/chatbot/message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                message: msg,
                session_id: 'playground_' + simulatedRole,
                simulated_role: simulatedRole,
                history: conversationHistory
            })
        });
        const data = await res.json();

        document.getElementById(typingId)?.remove();

        const reply = data.response || data.reply || 'I am sorry, I could not process your inquiry right now.';
        box.innerHTML += `<div class="bot-bubble bot-bubble-bot">${formatSimulatorMessage(reply)}</div>`;

        conversationHistory.push({ role: 'user', text: msg });
        conversationHistory.push({ role: 'bot', text: reply });
        if (conversationHistory.length > 8) conversationHistory = conversationHistory.slice(-8);

        if (data.chips && Array.isArray(data.chips) && data.chips.length > 0) {
            let chipsHtml = '<div class="bot-inline-chips">';
            data.chips.forEach(c => {
                const label = escapeHtml(c);
                chipsHtml += `<button type="button" data-chip="${label}">${label}</button>`;
            });
            chipsHtml += '</div>';
            box.innerHTML += chipsHtml;
        }
    } catch (e) {
        document.getElementById(typingId)?.remove();
        box.innerHTML += '<div class="bot-bubble bot-bubble-error">Could not reach the AI service. Check the logs and try again.</div>';
    } finally {
        input.disabled = false;
        if (sendBtn) sendBtn.disabled = false;
        input.focus();
        box.scrollTop = box.scrollHeight;
    }
}

function openLogConversation(btn) {
    let payload = {};
    try {
        const raw = btn.getAttribute('data-payload') || '{}';
        payload = JSON.parse(raw);
    } catch (e) { payload = {}; }
    const visitor = payload.visitor || 'Guest';
    const time = payload.time || '';
    const question = payload.question || '';
    const answer = payload.answer || '';

    document.querySelectorAll('.bot-rail-item').forEach(item => item.classList.toggle('is-active', item === btn));
    document.getElementById('botLogTitle').textContent = visitor;
    document.getElementById('botLogMeta').textContent = time;
    document.getElementById('botLogStream').innerHTML = `
        <div class="bot-bubble bot-bubble-user">${escapeHtml(question)}</div>
        <div class="bot-bubble bot-bubble-bot">${formatSimulatorMessage(answer)}</div>
    `;
    showBotView('botLogView');
}

document.getElementById('botNewChat')?.addEventListener('click', () => {
    showBotView('botSimView');
    resetSimulatorChat();
    document.getElementById('testChatInput')?.focus();
});

document.getElementById('botBackToSim')?.addEventListener('click', () => {
    showBotView('botSimView');
});

document.getElementById('botOpenKnowledge')?.addEventListener('click', () => {
    showBotView('botKnowledgeView');
});

document.getElementById('botCloseKnowledge')?.addEventListener('click', () => {
    showBotView('botSimView');
});

document.querySelectorAll('.bot-rail-item').forEach(btn => {
    btn.addEventListener('click', () => openLogConversation(btn));
});

document.addEventListener('click', (e) => {
    const chip = e.target.closest('[data-chip]');
    if (!chip) return;
    const testBox = document.getElementById('testChatBox');
    const quickPrompts = document.getElementById('quickTestPrompts');
    if (!(testBox && testBox.contains(chip)) && !(quickPrompts && quickPrompts.contains(chip))) return;
    showBotView('botSimView');
    testChipClick(chip.getAttribute('data-chip') || chip.textContent.trim());
});

document.addEventListener('DOMContentLoaded', () => {
    resetSimulatorChat();
    @if($errors->any())
    showBotView('botKnowledgeView');
    @endif
});
</script>
@endpush
