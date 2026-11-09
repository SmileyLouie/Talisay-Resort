<style>
        /* ── Floating Chatbot Widget ── */
        .landing-chatbot-fab {
            position: fixed;
            bottom: 28px;
            right: 28px;
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0c4a6e 0%, #075985 60%, #0284c7 100%);
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.35);
            box-shadow: 0 10px 28px rgba(12, 74, 110, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0;
            z-index: 850;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .landing-chatbot-fab:hover {
            transform: scale(1.08);
            box-shadow: 0 14px 34px rgba(12, 74, 110, 0.6);
        }
        .landing-chatbot-fab i {
            font-size: 24px;
            transition: transform 0.2s;
        }

        .landing-chatbot-card {
            position: fixed;
            bottom: 98px;
            right: 28px;
            width: 380px;
            max-width: calc(100vw - 32px);
            height: 520px;
            max-height: calc(100vh - 120px);
            background: #ffffff;
            border-radius: 22px;
            box-shadow: 0 25px 55px rgba(7, 30, 61, 0.35), 0 0 0 1px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            z-index: 850;
            opacity: 0;
            transform: translateY(20px) scale(0.96);
            pointer-events: none;
            transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .landing-chatbot-card.open {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }
        .lcb-header {
            background: linear-gradient(135deg, #0c4a6e 0%, #075985 60%, #0284c7 100%);
            padding: 15px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #fff;
            flex-shrink: 0;
        }
        .lcb-header-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .lcb-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .lcb-title {
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
        }
        .lcb-subtitle {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: rgba(255, 255, 255, 0.22);
            color: #e0f2fe;
            padding: 2px 8px;
            border-radius: 20px;
            margin-top: 3px;
        }
        .lcb-close-btn {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.8);
            font-size: 18px;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
        }
        .lcb-close-btn:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.15);
        }
        .lcb-body {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 10px;
            scroll-behavior: smooth;
        }
        .lcb-msg {
            max-width: 86%;
            padding: 10px 14px;
            font-size: 13px;
            line-height: 1.5;
            word-wrap: break-word;
        }
        .lcb-msg-bot {
            align-self: flex-start;
            background: #ffffff;
            color: #1e293b;
            border: 1px solid #e2e8f0;
            border-radius: 16px 16px 16px 4px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
        }
        .lcb-msg-user {
            align-self: flex-end;
            background: #0284c7;
            color: #ffffff;
            border-radius: 16px 16px 4px 16px;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.25);
        }
        .lcb-chips-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 4px;
            padding: 0 2px;
        }
        .lcb-chip-btn {
            background: #f0f9ff;
            color: #0369a1;
            border: 1px solid #bae6fd;
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
            font-family: inherit;
        }
        .lcb-chip-btn:hover {
            background: #e0f2fe;
            border-color: #7dd3fc;
            transform: translateY(-1px);
        }
        .lcb-footer {
            padding: 12px 14px;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 8px;
            align-items: center;
            flex-shrink: 0;
        }
        .lcb-input {
            flex: 1;
            padding: 9px 14px;
            font-size: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 24px;
            outline: none;
            font-family: inherit;
            color: #1e293b;
            transition: border-color 0.2s;
        }
        .lcb-input:focus {
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
        }
        .lcb-send-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #0284c7;
            color: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .lcb-send-btn:hover {
            background: #0369a1;
            transform: scale(1.05);
        }
        .lcb-typing {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 10px 14px;
        }
        .lcb-typing-dot {
            width: 6px;
            height: 6px;
            background: #94a3b8;
            border-radius: 50%;
            animation: lcbBlink 1s infinite alternate;
        }
        .lcb-typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .lcb-typing-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes lcbBlink {
            0% { opacity: 0.3; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1.1); }
        }
        @media (max-width: 640px) {
            .landing-chatbot-fab { bottom: 18px; right: 18px; width: 52px; height: 52px; }
            .landing-chatbot-card { bottom: 82px; right: 16px; width: calc(100vw - 32px); height: 460px; }
        }
</style>
@php
    $landUser = auth()->user();
    $landRole = $landUser ? $landUser->role : 'guest';
    $landFirstName = $landUser ? first_name($landUser->name) : '';
    $landModeLabel = match($landRole) {
        'admin' => 'Admin Assistant',
        'staff' => 'Staff Assistant',
        'tourist' => 'Tourist Assistant',
        default => 'Resort Assistant',
    };
    $landInitialWelcome = match($landRole) {
        'admin' => "Hello **{$landFirstName}** (Admin)!\nWelcome to Talisay Beach Resort. How can I assist you with resort management today?",
        'staff' => "Hello **{$landFirstName}**! Staff Assistant ready.\nAsk about today's arrivals, room availability, or reservations.",
        'tourist' => "Hello **{$landFirstName}**! Welcome back!\nAsk about your bookings, room rates, GCash payments, or 360° virtual tour.",
        default => "Hello! Welcome to **Talisay Beach Resort**.\nI'm your virtual guide! How can I help you plan your visit today?",
    };
    $landDefaultChips = match($landRole) {
        'admin' => ["Today's Summary", "Pending Bookings", "Revenue Stats", "Occupancy"],
        'staff' => ["Today's Arrivals", "Pending Bookings", "Cottage Availability", "Guest Rules"],
        'tourist' => ["My Bookings", "Room Rates", "GCash Payment", "360° Tour"],
        default => ["Room Rates", "Check Availability", "Operating Hours", "Directions", "360° Tour"],
    };
@endphp

{{-- Floating Chatbot FAB Button --}}
<button type="button" class="landing-chatbot-fab" id="landingChatbotFab" onclick="toggleLandingChatbot()" title="Open Resort Assistant" aria-label="Open Resort Assistant" aria-expanded="false" aria-controls="landingChatbotCard">
    <i class="bi bi-chat-dots-fill" id="landingChatbotIcon" aria-hidden="true"></i>
</button>

{{-- Floating Chatbot Panel --}}
<div class="landing-chatbot-card" id="landingChatbotCard" role="dialog" aria-labelledby="lcbTitle" aria-hidden="true">
    <div class="lcb-header">
        <div class="lcb-header-info">
            <div class="lcb-avatar">
                <i class="bi bi-robot" aria-hidden="true"></i>
            </div>
            <div>
                <div class="lcb-title" id="lcbTitle">Talisay Assistant</div>
                <div class="lcb-subtitle" id="lcbModeLabel">{{ $landModeLabel }}</div>
            </div>
        </div>
        <button type="button" class="lcb-close-btn" onclick="toggleLandingChatbot(false)" aria-label="Close Chatbot">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    <div class="lcb-body" id="lcbMessages" aria-live="polite">
        {{-- Initial Bot Message --}}
        <div class="lcb-msg lcb-msg-bot" id="lcbInitialMsg"></div>
        <div class="lcb-chips-wrap" id="lcbChipsContainer"></div>
    </div>

    <form class="lcb-footer" id="lcbForm" onsubmit="handleLandingChatSubmit(event)">
        <label for="lcbInput" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;">Type your message</label>
        <input type="text" class="lcb-input" id="lcbInput" placeholder="Ask about rates, hours, 360° tour..." autocomplete="off">
        <button type="submit" class="lcb-send-btn" id="lcbSendBtn" aria-label="Send message">
            <i class="bi bi-send-fill" aria-hidden="true"></i>
        </button>
    </form>
</div>

<script>
let lcbOpen = false;
const lcbSessionId = 'landing_' + Math.random().toString(36).substring(2, 10) + '_' + Date.now();
const lcbInitialWelcome = {!! \Illuminate\Support\Js::from($landInitialWelcome) !!};
const lcbDefaultChips = {!! \Illuminate\Support\Js::from($landDefaultChips) !!};
let lcbHistory = [];

function formatLcbText(text) {
    if (!text) return '';
    let escaped = String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");

    escaped = escaped.replace(/^[•\-\*]\s+(.*)$/gm, '<div class="flex items-start gap-1.5 my-0.5"><span class="text-sky-500 font-bold leading-tight">•</span><span>$1</span></div>');

    return escaped
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\n/g, '<br>');
}

function initLandingChatbot() {
    const initMsgEl = document.getElementById('lcbInitialMsg');
    if (initMsgEl) {
        initMsgEl.innerHTML = formatLcbText(lcbInitialWelcome);
    }
    renderLcbChips(lcbDefaultChips);
}

function toggleLandingChatbot(forceState) {
    const card = document.getElementById('landingChatbotCard');
    const icon = document.getElementById('landingChatbotIcon');
    const fab = document.getElementById('landingChatbotFab');
    if (!card || !icon) return;
    if (typeof forceState === 'boolean') {
        lcbOpen = forceState;
    } else {
        lcbOpen = !lcbOpen;
    }
    window.lcbOpen = lcbOpen;

    card.setAttribute('aria-hidden', lcbOpen ? 'false' : 'true');
    if (fab) {
        fab.setAttribute('aria-expanded', lcbOpen ? 'true' : 'false');
        fab.setAttribute('aria-label', lcbOpen ? 'Close Resort Assistant' : 'Open Resort Assistant');
    }

    if (lcbOpen) {
        card.classList.add('open');
        icon.className = 'bi bi-x-lg';
        setTimeout(() => {
            const input = document.getElementById('lcbInput');
            if (input) input.focus();
            scrollLcbBottom();
        }, 150);
    } else {
        card.classList.remove('open');
        icon.className = 'bi bi-chat-dots-fill';
    }
}

function renderLcbChips(chips) {
    const container = document.getElementById('lcbChipsContainer');
    if (!container) return;
    container.innerHTML = '';
    if (!chips || !chips.length) return;

    chips.forEach(chip => {
        const label = String(chip);
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'lcb-chip-btn';
        btn.textContent = label;
        btn.onclick = () => handleLcbChipClick(label);
        container.appendChild(btn);
    });
}

function handleLcbChipClick(text) {
    if (text === 'Login' || text === 'Log in') {
        openLoginModal();
        return;
    }
    if (text === 'Register' || text === 'Sign up') {
        window.location.href = "{{ route('register') }}";
        return;
    }
    if (text === '360° Virtual Tour' || text === '360° Tour') {
        window.open("{{ route('tour.viewer') }}", '_blank');
        return;
    }
    sendLandingMessage(text);
}

function scrollLcbBottom() {
    const body = document.getElementById('lcbMessages');
    if (body) {
        body.scrollTop = body.scrollHeight;
    }
}

async function sendLandingMessage(text) {
    const message = (text || '').trim();
    if (!message) return;

    const bodyEl = document.getElementById('lcbMessages');
    const chipsContainer = document.getElementById('lcbChipsContainer');

    // Append user message
    const userMsgEl = document.createElement('div');
    userMsgEl.className = 'lcb-msg lcb-msg-user';
    userMsgEl.textContent = message;
    bodyEl.appendChild(userMsgEl);

    // Track in conversation history
    lcbHistory.push({ role: 'user', text: message });

    // Show typing
    const typingEl = document.createElement('div');
    typingEl.className = 'lcb-msg lcb-msg-bot lcb-typing';
    typingEl.id = 'lcbTypingIndicator';
    typingEl.innerHTML = '<span class="lcb-typing-dot"></span><span class="lcb-typing-dot"></span><span class="lcb-typing-dot"></span>';
    bodyEl.appendChild(typingEl);
    scrollLcbBottom();

    // Clear chips while thinking
    if (chipsContainer) chipsContainer.innerHTML = '';

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const res = await fetch('/api/chatbot/message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                message: message,
                session_id: lcbSessionId,
                history: lcbHistory.slice(-6)
            })
        });

        const data = await res.json();
        typingEl.remove();

        const reply = data.response || data.reply || "I'm sorry, I couldn't process that. Please try again or contact our front desk.";
        lcbHistory.push({ role: 'bot', text: reply });

        const botMsgEl = document.createElement('div');
        botMsgEl.className = 'lcb-msg lcb-msg-bot';
        botMsgEl.innerHTML = formatLcbText(reply);
        bodyEl.appendChild(botMsgEl);

        // Put chips container back at the end
        if (chipsContainer) {
            bodyEl.appendChild(chipsContainer);
            renderLcbChips(Array.isArray(data.chips) && data.chips.length ? data.chips : lcbDefaultChips);
        }
    } catch (err) {
        typingEl.remove();
        const errEl = document.createElement('div');
        errEl.className = 'lcb-msg lcb-msg-bot';
        errEl.textContent = "Sorry, I had trouble connecting. Please check your internet connection or try again.";
        bodyEl.appendChild(errEl);
        if (chipsContainer) {
            bodyEl.appendChild(chipsContainer);
            renderLcbChips(lcbDefaultChips);
        }
    }

    scrollLcbBottom();
}

function handleLandingChatSubmit(e) {
    e.preventDefault();
    const input = document.getElementById('lcbInput');
    const text = input ? input.value : '';
    if (input) input.value = '';
    sendLandingMessage(text);
}

document.addEventListener('DOMContentLoaded', initLandingChatbot);
</script>
