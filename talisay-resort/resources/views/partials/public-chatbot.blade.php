{{-- ── Public site chatbot widget ── --}}

@php
    $landUser = auth()->user();
    $landRole = $landUser ? $landUser->role : 'guest';
    $landFirstName = $landUser ? first_name($landUser->name) : 'Guest';
    $landModeLabel = match ($landRole) {
        'admin' => 'Admin Assistant',
        'staff' => 'Staff Assistant',
        'tourist' => 'Tourist Assistant',
        default => 'Resort Assistant',
    };
    $landInitialWelcome = match ($landRole) {
        'admin' => "Hello **{$landFirstName}**! Admin Assistant ready.\nI can assist with revenue stats, booking overviews, user accounts, and system operations.",
        'staff' => "Hello **{$landFirstName}**! Staff Assistant ready.\nAsk about today's arrivals, pending bookings, cottage availability, or guest check-ins.",
        'tourist' => "Hello **{$landFirstName}**! Welcome back to Talisay Beach Resort.\nAsk about your bookings, room rates, GCash payment, or virtual tour.",
        default => "Hello! Welcome to **Talisay Beach Resort**.\nI'm your virtual guide! How can I help you plan your visit today?",
    };
    $landDefaultChips = match ($landRole) {
        'admin' => ["Today's Summary", "Pending Bookings", "Revenue Stats", "User Accounts"],
        'staff' => ["Today's Arrivals", "Check Availability", "Check-Out Today", "Guest Lookup"],
        'tourist' => ["My Bookings", "Room Rates", "GCash Payment", "360° Tour"],
        default => ["Room Rates", "Check Availability", "Operating Hours", "Directions", "360° Tour"],
    };
@endphp
<style>
    .landing-chatbot-fab {
        position: fixed;
        bottom: 28px;
        right: 28px;
        width: 58px;
        height: 58px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0c4a6e 0%, #075985 60%, #0284c7 100%);
        color: #ffffff;
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
        border: 1px solid #e2e8f0;
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
        color: #ffffff;
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
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.25);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: #ffffff;
    }
    .lcb-title {
        font-size: 14px;
        font-weight: 700;
        color: #ffffff;
        line-height: 1.2;
    }
    .lcb-subtitle {
        display: inline-block;
        font-size: 9.5px;
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
        color: #ffffff;
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
        line-height: 1.55;
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
        background: #0c4a6e;
        color: #ffffff;
        border-radius: 16px 16px 4px 16px;
        box-shadow: 0 2px 6px rgba(12, 74, 110, 0.25);
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
        transition: border-color 0.15s;
    }
    .lcb-input:focus {
        border-color: #0284c7;
    }
    .lcb-send-btn {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #0284c7;
        color: #ffffff;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 14px;
        transition: background 0.15s;
        flex-shrink: 0;
    }
    .lcb-send-btn:hover {
        background: #0369a1;
    }
    .lcb-typing {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 10px 14px;
        width: fit-content;
    }
    .lcb-typing-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #94a3b8;
        animation: lcbBounce 1.2s infinite ease-in-out;
    }
    .lcb-typing-dot:nth-child(2) { animation-delay: 0.2s; }
    .lcb-typing-dot:nth-child(3) { animation-delay: 0.4s; }
    @keyframes lcbBounce {
        0%, 60%, 100% { transform: translateY(0); }
        30% { transform: translateY(-5px); }
    }
</style>

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
                <div class="lcb-subtitle">{{ $landModeLabel }}</div>
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
        <label for="lcbInput" class="visually-hidden">Type your message</label>
        <input type="text" class="lcb-input" id="lcbInput" placeholder="Ask about rates, hours, 360° tour..." autocomplete="off">
        <button type="submit" class="lcb-send-btn" id="lcbSendBtn" aria-label="Send message">
            <i class="bi bi-send-fill" aria-hidden="true"></i>
        </button>
    </form>
</div>

<script>
(function() {
    let lcbOpen = false;
    window.lcbOpen = false;
    const lcbSessionId = 'landing_' + Math.random().toString(36).substring(2, 10) + '_' + Date.now();
    const lcbDefaultChips = @json($landDefaultChips);
    const lcbInitialWelcome = @json($landInitialWelcome);
    let lcbHistory = [];

    function formatGcbText(text) {
        if (!text) return '';
        let raw = String(text).replace(/\r\n/g, '\n').trim();
        // Close a truncated bold marker so rates like **PHP 1,500 are still readable.
        if ((raw.match(/\*\*/g) || []).length % 2 === 1) raw += '**';
        let escaped = raw
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");

        escaped = escaped.replace(/^[•\-\*]\s+(.*)$/gm, '<div style="display:flex;align-items:flex-start;gap:6px;margin:2px 0;"><span style="color:#0ea5e9;font-weight:700;line-height:1.3;">•</span><span>$1</span></div>');

        return escaped
            .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
    }

    function initLandingChatbot() {
        renderGcbChips(lcbDefaultChips);
    }

    window.toggleLandingChatbot = function(forceState) {
        const card = document.getElementById('landingChatbotCard');
        const icon = document.getElementById('landingChatbotIcon');
        const fab = document.getElementById('landingChatbotFab');
        if (!card || !icon) return;

        if (typeof forceState === 'boolean') {
            lcbOpen = forceState;
            window.lcbOpen = lcbOpen;
        } else {
            lcbOpen = !lcbOpen;
            window.lcbOpen = lcbOpen;
        }

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
                scrollGcbBottom();
            }, 150);
        } else {
            card.classList.remove('open');
            icon.className = 'bi bi-chat-dots-fill';
        }
    };

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && lcbOpen) {
            window.toggleLandingChatbot(false);
        }
    });

    function renderGcbChips(chips) {
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
            btn.onclick = () => handleGcbChipClick(label);
            container.appendChild(btn);
        });
    }

    function handleGcbChipClick(text) {
        if (text === 'Login' || text === 'Log in') {
            if (!window.location.pathname.includes('/login')) {
                window.location.href = "{{ route('login') }}";
                return;
            }
        }
        if (text === 'Register' || text === 'Sign up') {
            if (!window.location.pathname.includes('/register')) {
                window.location.href = "{{ route('register') }}";
                return;
            }
        }
        if (text === '360° Virtual Tour' || text === '360° Tour') {
            window.open("{{ route('tour.viewer') }}", '_blank');
            return;
        }
        sendGuestMessage(text);
    }

    function scrollGcbBottom() {
        const body = document.getElementById('lcbMessages');
        if (body) {
            body.scrollTop = body.scrollHeight;
        }
    }

    async function sendGuestMessage(text) {
        const message = (text || '').trim();
        if (!message) return;

        const bodyEl = document.getElementById('lcbMessages');
        const chipsContainer = document.getElementById('lcbChipsContainer');

        // Append user message
        const userMsgEl = document.createElement('div');
        userMsgEl.className = 'lcb-msg lcb-msg-user';
        userMsgEl.textContent = message;
        bodyEl.appendChild(userMsgEl);

        // Record history
        lcbHistory.push({ role: 'user', text: message });

        // Show typing
        const typingEl = document.createElement('div');
        typingEl.className = 'lcb-msg lcb-msg-bot lcb-typing';
        typingEl.id = 'lcbTypingIndicator';
        typingEl.innerHTML = '<span class="lcb-typing-dot"></span><span class="lcb-typing-dot"></span><span class="lcb-typing-dot"></span>';
        bodyEl.appendChild(typingEl);
        scrollGcbBottom();

        if (chipsContainer) chipsContainer.innerHTML = '';

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
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
            botMsgEl.innerHTML = formatGcbText(reply);
            bodyEl.appendChild(botMsgEl);

            if (chipsContainer) {
                bodyEl.appendChild(chipsContainer);
                renderGcbChips(Array.isArray(data.chips) && data.chips.length ? data.chips : lcbDefaultChips);
            }
        } catch (err) {
            typingEl.remove();
            const errEl = document.createElement('div');
            errEl.className = 'lcb-msg lcb-msg-bot';
            errEl.textContent = "Sorry, I had trouble connecting. Please check your internet connection or try again.";
            bodyEl.appendChild(errEl);
            if (chipsContainer) {
                bodyEl.appendChild(chipsContainer);
                renderGcbChips(lcbDefaultChips);
            }
        }

        scrollGcbBottom();
    }

    window.handleLandingChatSubmit = function(e) {
        e.preventDefault();
        const input = document.getElementById('lcbInput');
        const text = input ? input.value : '';
        if (input) input.value = '';
        sendGuestMessage(text);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLandingChatbot);
    } else {
        initLandingChatbot();
    }
})();
</script>
