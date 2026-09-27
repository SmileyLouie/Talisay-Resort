{{-- ── Floating Guest Chatbot Widget (Login, Register & Public Auth) ── --}}
<style>
    .guest-chatbot-fab {
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
        z-index: 1050;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .guest-chatbot-fab:hover {
        transform: scale(1.08);
        box-shadow: 0 14px 34px rgba(12, 74, 110, 0.6);
    }
    .guest-chatbot-fab i {
        font-size: 24px;
        transition: transform 0.2s;
    }

    .guest-chatbot-card {
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
        z-index: 1050;
        opacity: 0;
        transform: translateY(20px) scale(0.96);
        pointer-events: none;
        transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
        border: 1px solid #e2e8f0;
    }
    .guest-chatbot-card.open {
        opacity: 1;
        transform: translateY(0) scale(1);
        pointer-events: auto;
    }
    .gcb-header {
        background: linear-gradient(135deg, #0c4a6e 0%, #075985 60%, #0284c7 100%);
        padding: 15px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #ffffff;
        flex-shrink: 0;
    }
    .gcb-header-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .gcb-avatar {
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
    .gcb-title {
        font-size: 14px;
        font-weight: 700;
        color: #ffffff;
        line-height: 1.2;
    }
    .gcb-subtitle {
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
    .gcb-close-btn {
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
    .gcb-close-btn:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.15);
    }
    .gcb-body {
        flex: 1;
        padding: 16px;
        overflow-y: auto;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        gap: 10px;
        scroll-behavior: smooth;
    }
    .gcb-msg {
        max-width: 86%;
        padding: 10px 14px;
        font-size: 13px;
        line-height: 1.55;
        word-wrap: break-word;
    }
    .gcb-msg-bot {
        align-self: flex-start;
        background: #ffffff;
        color: #1e293b;
        border: 1px solid #e2e8f0;
        border-radius: 16px 16px 16px 4px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
    }
    .gcb-msg-user {
        align-self: flex-end;
        background: #0c4a6e;
        color: #ffffff;
        border-radius: 16px 16px 4px 16px;
        box-shadow: 0 2px 6px rgba(12, 74, 110, 0.25);
    }
    .gcb-chips-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 4px;
        padding: 0 2px;
    }
    .gcb-chip-btn {
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
    .gcb-chip-btn:hover {
        background: #e0f2fe;
        border-color: #7dd3fc;
        transform: translateY(-1px);
    }
    .gcb-footer {
        padding: 12px 14px;
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 8px;
        align-items: center;
        flex-shrink: 0;
    }
    .gcb-input {
        flex: 1;
        padding: 9px 14px;
        font-size: 13px;
        border: 1px solid #cbd5e1;
        border-radius: 24px;
        outline: none;
        transition: border-color 0.15s;
    }
    .gcb-input:focus {
        border-color: #0284c7;
    }
    .gcb-send-btn {
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
    .gcb-send-btn:hover {
        background: #0369a1;
    }
    .gcb-typing {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 10px 14px;
        width: fit-content;
    }
    .gcb-typing-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #94a3b8;
        animation: gcbBounce 1.2s infinite ease-in-out;
    }
    .gcb-typing-dot:nth-child(2) { animation-delay: 0.2s; }
    .gcb-typing-dot:nth-child(3) { animation-delay: 0.4s; }
    @keyframes gcbBounce {
        0%, 60%, 100% { transform: translateY(0); }
        30% { transform: translateY(-5px); }
    }
</style>

{{-- Floating Chatbot FAB Button --}}
<div class="guest-chatbot-fab" id="guestChatbotFab" onclick="toggleGuestChatbot()" title="Open Resort Assistant">
    <i class="bi bi-chat-dots-fill" id="guestChatbotIcon"></i>
</div>

{{-- Floating Chatbot Panel --}}
<div class="guest-chatbot-card" id="guestChatbotCard">
    <div class="gcb-header">
        <div class="gcb-header-info">
            <div class="gcb-avatar">
                <i class="bi bi-robot"></i>
            </div>
            <div>
                <div class="gcb-title">Talisay Assistant</div>
                <div class="gcb-subtitle">Resort Assistant</div>
            </div>
        </div>
        <button type="button" class="gcb-close-btn" onclick="toggleGuestChatbot(false)" aria-label="Close Chatbot">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="gcb-body" id="gcbMessages">
        {{-- Initial Bot Message --}}
        <div class="gcb-msg gcb-msg-bot" id="gcbInitialMsg">
            Hello! 👋 Welcome to Talisay Beach Resort. How can I assist you today? You can ask about room rates, amenities, check-in policies, or account registration.
        </div>
        <div class="gcb-chips-wrap" id="gcbChipsContainer"></div>
    </div>

    <form class="gcb-footer" id="gcbForm" onsubmit="handleGuestChatSubmit(event)">
        <input type="text" class="gcb-input" id="gcbInput" placeholder="Ask about rates, hours, 360° tour..." autocomplete="off">
        <button type="submit" class="gcb-send-btn" id="gcbSendBtn" aria-label="Send message">
            <i class="bi bi-send-fill"></i>
        </button>
    </form>
</div>

<script>
(function() {
    let gcbOpen = false;
    const gcbSessionId = 'guest_' + Math.random().toString(36).substring(2, 10) + '_' + Date.now();
    const gcbDefaultChips = ["Room Rates", "Check Availability", "Operating Hours", "Directions", "360° Tour"];
    let gcbHistory = [];

    function formatGcbText(text) {
        if (!text) return '';
        let escaped = text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");

        escaped = escaped.replace(/^[•\-\*]\s+(.*)$/gm, '<div class="flex items-start gap-1.5 my-0.5"><span class="text-sky-500 font-bold leading-tight">•</span><span>$1</span></div>');

        return escaped
            .replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-slate-900">$1</strong>')
            .replace(/\n/g, '<br>');
    }

    function initGuestChatbot() {
        renderGcbChips(gcbDefaultChips);
    }

    window.toggleGuestChatbot = function(forceState) {
        const card = document.getElementById('guestChatbotCard');
        const icon = document.getElementById('guestChatbotIcon');
        if (!card || !icon) return;

        if (typeof forceState === 'boolean') {
            gcbOpen = forceState;
        } else {
            gcbOpen = !gcbOpen;
        }

        if (gcbOpen) {
            card.classList.add('open');
            icon.className = 'bi bi-x-lg';
            setTimeout(() => {
                const input = document.getElementById('gcbInput');
                if (input) input.focus();
                scrollGcbBottom();
            }, 150);
        } else {
            card.classList.remove('open');
            icon.className = 'bi bi-chat-dots-fill';
        }
    };

    function renderGcbChips(chips) {
        const container = document.getElementById('gcbChipsContainer');
        if (!container) return;
        container.innerHTML = '';
        if (!chips || !chips.length) return;

        chips.forEach(chip => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'gcb-chip-btn';
            btn.textContent = chip;
            btn.onclick = () => handleGcbChipClick(chip);
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
        const body = document.getElementById('gcbMessages');
        if (body) {
            body.scrollTop = body.scrollHeight;
        }
    }

    async function sendGuestMessage(text) {
        const message = (text || '').trim();
        if (!message) return;

        const bodyEl = document.getElementById('gcbMessages');
        const chipsContainer = document.getElementById('gcbChipsContainer');

        // Append user message
        const userMsgEl = document.createElement('div');
        userMsgEl.className = 'gcb-msg gcb-msg-user';
        userMsgEl.textContent = message;
        bodyEl.appendChild(userMsgEl);

        // Record history
        gcbHistory.push({ role: 'user', text: message });

        // Show typing
        const typingEl = document.createElement('div');
        typingEl.className = 'gcb-msg gcb-msg-bot gcb-typing';
        typingEl.id = 'gcbTypingIndicator';
        typingEl.innerHTML = '<span class="gcb-typing-dot"></span><span class="gcb-typing-dot"></span><span class="gcb-typing-dot"></span>';
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
                    session_id: gcbSessionId,
                    history: gcbHistory.slice(-6)
                })
            });

            const data = await res.json();
            typingEl.remove();

            const reply = data.response || data.reply || "I'm sorry, I couldn't process that. Please try again or contact our front desk.";
            gcbHistory.push({ role: 'bot', text: reply });

            const botMsgEl = document.createElement('div');
            botMsgEl.className = 'gcb-msg gcb-msg-bot';
            botMsgEl.innerHTML = formatGcbText(reply);
            bodyEl.appendChild(botMsgEl);

            if (chipsContainer) {
                bodyEl.appendChild(chipsContainer);
                renderGcbChips(data.chips || gcbDefaultChips);
            }
        } catch (err) {
            typingEl.remove();
            const errEl = document.createElement('div');
            errEl.className = 'gcb-msg gcb-msg-bot';
            errEl.textContent = "Sorry, I had trouble connecting. Please check your internet connection or try again.";
            bodyEl.appendChild(errEl);
            if (chipsContainer) {
                bodyEl.appendChild(chipsContainer);
                renderGcbChips(gcbDefaultChips);
            }
        }

        scrollGcbBottom();
    }

    window.handleGuestChatSubmit = function(e) {
        e.preventDefault();
        const input = document.getElementById('gcbInput');
        const text = input ? input.value : '';
        if (input) input.value = '';
        sendGuestMessage(text);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGuestChatbot);
    } else {
        initGuestChatbot();
    }
})();
</script>
