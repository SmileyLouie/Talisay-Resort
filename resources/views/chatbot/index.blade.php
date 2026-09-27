@extends('layouts.app')

@section('title', 'Chatbot Management - Talisay Smart Tourism')

@section('content')

{{-- Header --}}
<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100/80 text-sky-800 border border-sky-200">
                <i class="bi bi-robot text-[10px] text-sky-600"></i>
                AI Assistant
            </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mb-1">
            Chatbot Management
        </h1>
        <p class="text-sm text-slate-500 mb-0">
            Automated guest assistance, visitor conversation logs, and knowledge base settings.
        </p>
    </div>

    <div class="flex items-center gap-2">
        <a href="#simulatorSection" class="btn-ocean text-xs py-2 px-3.5">
            <i class="bi bi-chat-dots-fill"></i>
            <span>Test Playground</span>
        </a>
        @if($totalInteractions > 0)
        <form method="POST" action="{{ route('chatbot.logs.clear') }}" class="inline" onsubmit="return confirm('Are you sure you want to clear all recorded chatbot conversation logs?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-secondary-clean text-xs py-2 px-3.5 text-rose-600 hover:text-rose-700 hover:border-rose-300">
                <i class="bi bi-trash"></i>
                <span>Clear Logs</span>
            </button>
        </form>
        @endif
    </div>
</div>

@if(session('success'))
<div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-xs">
    <div class="flex items-center gap-2">
        <i class="bi bi-check-circle-fill text-emerald-500"></i>
        <span>{{ session('success') }}</span>
    </div>
    <button type="button" class="btn-close text-xs" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

{{-- Main Workspace Grid --}}
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

    {{-- Left: Tabbed Operations (Audit Logs & Knowledge Base & Context) --}}
    <div class="lg:col-span-7 flex flex-col gap-6">

        {{-- Nav Tabs --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="border-b border-slate-200/80 px-4 pt-3 bg-slate-50/50">
                <ul class="nav nav-tabs border-0 flex flex-wrap gap-2 -mb-px" id="chatbotTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active font-bold text-xs py-2.5 px-4 rounded-t-xl border-0 border-b-2 text-slate-600 hover:text-sky-600" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logsPane" type="button" role="tab">
                            <i class="bi bi-clock-history me-1.5 text-sky-600"></i>
                            Conversation Audit Logs ({{ $logs->total() }})
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link font-bold text-xs py-2.5 px-4 rounded-t-xl border-0 border-b-2 text-slate-600 hover:text-sky-600" id="persona-tab" data-bs-toggle="tab" data-bs-target="#personaPane" type="button" role="tab">
                            <i class="bi bi-sliders me-1.5 text-sky-600"></i>
                            AI Knowledge & Instructions
                        </button>
                    </li>

                </ul>
            </div>

            <div class="tab-content p-5">
                {{-- TAB 1: CONVERSATION LOGS --}}
                <div class="tab-pane fade show active" id="logsPane" role="tabpanel">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900 mb-0">Visitor Conversation History</h3>
                            <p class="text-xs text-slate-500 mb-0">Real-time audit log of inquiries and automated answers.</p>
                        </div>
                    </div>

                    <div class="table-responsive border border-slate-100 rounded-xl overflow-hidden">
                        <table class="table-clean w-full">
                            <thead>
                                <tr class="bg-slate-50/80">
                                    <th class="py-2.5 px-3 text-[11px]">Time / Visitor</th>
                                    <th class="py-2.5 px-3 text-[11px]">Inquiry</th>
                                    <th class="py-2.5 px-3 text-[11px]">Chatbot Response</th>
                                    <th class="py-2.5 px-3 text-[11px] text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($logs as $log)
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="py-3 px-3 align-top whitespace-nowrap">
                                        <div class="text-[11px] font-bold text-slate-800">{{ $log->created_at->diffForHumans() }}</div>
                                        <div class="text-[10px] text-slate-400 mb-1">{{ $log->created_at->format('M d, g:i A') }}</div>
                                        @if($log->user)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200/70">
                                                <i class="bi bi-person-fill text-[9px]"></i> {{ Str::limit($log->user->name, 12) }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                                <i class="bi bi-globe2 text-[9px]"></i> Guest
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 align-top">
                                        <p class="text-xs font-semibold text-slate-800 mb-0 max-w-xs leading-snug">
                                            {{ $log->message }}
                                        </p>
                                    </td>
                                    <td class="py-3 px-3 align-top">
                                        <div class="text-xs text-slate-600 mb-0 line-clamp-2 leading-relaxed">
                                            {{ Str::limit(strip_tags($log->response), 110) }}
                                        </div>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-medium text-slate-400 mt-1">
                                            <i class="bi bi-check2-all text-[11px] text-sky-600"></i> Answered
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 align-top text-end whitespace-nowrap">
                                        <button type="button" 
                                                onclick="showLogDetail(@js($log->message), @js($log->response), @js($log->created_at->format('M d, Y g:i A')), @js($log->user ? $log->user->name : 'Public Guest'))"
                                                class="btn-secondary-clean text-[11px] py-1 px-2.5" 
                                                title="View Full Conversation">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-12 text-slate-400">
                                        <i class="bi bi-chat-square-dots text-3xl block mb-2 text-slate-300"></i>
                                        <p class="text-xs font-semibold mb-1">No recorded visitor conversations yet.</p>
                                        <p class="text-[11px] text-slate-400 mb-0">Use the simulator on the right to test your chatbot!</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($logs->hasPages())
                    <div class="mt-4">
                        {{ $logs->links() }}
                    </div>
                    @endif
                </div>

                {{-- TAB 2: AI KNOWLEDGE & INSTRUCTIONS STUDIO --}}
                <div class="tab-pane fade" id="personaPane" role="tabpanel">
                    <form method="POST" action="{{ route('chatbot.settings.update') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                AI Assistant Persona & Greeting Style
                            </label>
                            <p class="text-xs text-slate-400 mb-1.5">Define how the AI introduces itself, its tone of voice, and hospitality demeanor.</p>
                            <textarea name="chatbot_persona" rows="3" class="form-input text-xs w-full" placeholder="e.g. You are the friendly, welcoming, and knowledgeable resort concierge assistant for Talisay Beach Resort...">{{ old('chatbot_persona', $persona) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Resort Policies & Operational Rules (Injected into AI Context)
                            </label>
                            <p class="text-xs text-slate-400 mb-1.5">Specific rules the AI must enforce (e.g. pool hours, pet rules, dining schedule, cancellation policies).</p>
                            <textarea name="chatbot_custom_rules" rows="4" class="form-input text-xs w-full font-mono" placeholder="Check-in time is 2:00 PM. Check-out is 12:00 PM. Day tour hours are 7:00 AM to 5:00 PM...">{{ old('chatbot_custom_rules', $customRules) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Active Announcement / Seasonal Notice
                            </label>
                            <p class="text-xs text-slate-400 mb-1.5">Special notices or announcements the AI can share with visitors (e.g. promos, maintenance, upcoming events).</p>
                            <input type="text" name="chatbot_announcement" value="{{ old('chatbot_announcement', $announcement) }}" class="form-input text-xs w-full" placeholder="e.g. Summer Promo 2026 is now ongoing! Free snorkeling gear with any cottage booking.">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Suggested Prompt Chips for Public Guests
                            </label>
                            <p class="text-xs text-slate-400 mb-1.5">Comma-separated list of quick clickable starter questions presented to visitors.</p>
                            <input type="text" name="chatbot_chips" value="{{ old('chatbot_chips', $quickChips) }}" class="form-input text-xs w-full" placeholder="Room Rates, Cottage Rates, Day Tour Slots, Check-in Times, Amenities, How to Book">
                        </div>

                        <div class="pt-2 flex justify-end">
                            <button type="submit" class="btn-ocean text-xs py-2 px-4">
                                <i class="bi bi-check2-circle"></i>
                                <span>Save AI Knowledge Base</span>
                            </button>
                        </div>
                    </form>
                </div>


            </div>
        </div>

    </div>

    {{-- Right: Live AI Simulator & Playground --}}
    <div class="lg:col-span-5" id="simulatorSection">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col h-[680px]">
            
            {{-- Simulator Header --}}
            <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-sm text-slate-900 mb-0">Chatbot Simulator</h3>
                </div>

                <div class="flex items-center gap-2">
                    <select id="simRoleSelect" class="form-select text-[11px] font-semibold py-1 px-2 border-slate-200 rounded-lg bg-white" onchange="resetSimulatorChat()">
                        <option value="guest" selected>Role: Guest (Public)</option>
                        <option value="tourist">Role: Tourist (Logged-in)</option>
                        <option value="staff">Role: Staff Assistant</option>
                        <option value="admin">Role: Admin Concierge</option>
                    </select>

                    <button type="button" onclick="resetSimulatorChat()" class="btn-secondary-clean text-xs py-1 px-2 text-slate-500 hover:text-slate-700" title="Reset chat">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>

            {{-- Chat Conversation Stream --}}
            <div id="testChatBox" class="p-4 flex-1 overflow-y-auto space-y-3 bg-slate-50/60 flex flex-col text-xs">
                {{-- Initial bot message injected by JS --}}
            </div>

            {{-- Preset Quick Prompts --}}
            <div class="px-4 py-2 border-t border-slate-100 bg-white">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Suggested Prompts</div>
                <div class="flex flex-wrap gap-1.5" id="quickTestPrompts">
                    <button type="button" onclick="testChipClick('What are your room rates?')" class="text-[11px] font-medium bg-slate-100 hover:bg-sky-50 hover:text-sky-700 border border-slate-200/80 px-2.5 py-0.5 rounded-full transition">Room Rates</button>
                    <button type="button" onclick="testChipClick('Tell me about your cottages and amenities')" class="text-[11px] font-medium bg-slate-100 hover:bg-sky-50 hover:text-sky-700 border border-slate-200/80 px-2.5 py-0.5 rounded-full transition">Cottages & Amenities</button>
                    <button type="button" onclick="testChipClick('What time is check in and check out?')" class="text-[11px] font-medium bg-slate-100 hover:bg-sky-50 hover:text-sky-700 border border-slate-200/80 px-2.5 py-0.5 rounded-full transition">Check-in / Check-out</button>
                    <button type="button" onclick="testChipClick('Can we hold an event or full-resort booking?')" class="text-[11px] font-medium bg-slate-100 hover:bg-sky-50 hover:text-sky-700 border border-slate-200/80 px-2.5 py-0.5 rounded-full transition">Full Resort Booking</button>
                </div>
            </div>

            {{-- Chat Input Bar --}}
            <div class="p-3 border-t border-slate-100 bg-white flex items-center gap-2">
                <input type="text" 
                       id="testChatInput" 
                       placeholder="Ask Gemini AI anything naturally..." 
                       class="form-input flex-1 text-xs py-2 px-3 border-slate-200 rounded-xl focus:border-sky-500 focus:ring-sky-500" 
                       onkeydown="if(event.key==='Enter') testChat()">
                <button type="button" 
                        id="testChatSendBtn"
                        onclick="testChat()" 
                        class="btn-ocean py-2 px-3 rounded-xl text-xs shrink-0 flex items-center justify-center">
                    <i class="bi bi-send-fill text-xs"></i>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal for Viewing Full Conversation Log --}}
<div class="modal fade" id="logDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-2xl border-0 shadow-lg">
            <div class="modal-header border-b border-slate-100 bg-slate-50/50 p-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center">
                        <i class="bi bi-chat-left-text-fill"></i>
                    </div>
                    <div>
                        <h5 class="modal-title font-extrabold text-sm text-slate-800 mb-0">Conversation Details</h5>
                        <span id="logModalMeta" class="text-[11px] text-slate-400"></span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-6 space-y-4">
                <div>
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Visitor Question</label>
                    <div id="logModalQuestion" class="p-3 rounded-xl bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-800"></div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Chatbot Response</label>
                    <div id="logModalResponse" class="p-4 rounded-xl bg-white border border-slate-200/90 text-xs text-slate-800 shadow-xs leading-relaxed space-y-2 whitespace-pre-line"></div>
                </div>
            </div>
            <div class="modal-footer border-t border-slate-100 p-3">
                <button type="button" class="btn-secondary-clean text-xs py-1.5 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let conversationHistory = [];

function formatSimulatorMessage(text) {
    if (!text) return '';
    let escaped = text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");

    // Bullet points
    escaped = escaped.replace(/^[•\-\*]\s+(.*)$/gm, '<div class="flex items-start gap-1.5 my-0.5"><span class="text-sky-500 font-bold leading-tight">•</span><span>$1</span></div>');

    // Bold text
    return escaped
        .replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-slate-900">$1</strong>')
        .replace(/\n/g, '<br>');
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
        greeting = `👋 <strong>Hello, Visitor!</strong> I am your AI resort concierge for Talisay Beach Resort. Ask me anything naturally about room rates, cottages, check-in rules, or amenities!`;
    } else if (role === 'tourist') {
        greeting = `👋 <strong>Hello, Tourist!</strong> I am your personalized vacation assistant. Ask me about your bookings, payments, or activities at the resort.`;
    } else {
        greeting = `👋 <strong>Hello, Staff/Admin!</strong> I am your resort operational AI. Ask me about today's capacity, schedules, or facility statuses.`;
    }

    if (!box) return;
    box.innerHTML = `
        <div class="bg-white border border-slate-200/90 p-3 rounded-2xl rounded-bl-sm text-xs text-slate-800 shadow-xs leading-relaxed">
            ${greeting}
        </div>
    `;
}

async function testChat() {
    const input = document.getElementById('testChatInput');
    const sendBtn = document.getElementById('testChatSendBtn');
    const msg = input.value.trim();
    if (!msg) return;

    const roleSelect = document.getElementById('simRoleSelect');
    const simulatedRole = roleSelect ? roleSelect.value : 'guest';

    const box = document.getElementById('testChatBox');
    
    // User message bubble
    box.innerHTML += `<div class="bg-gradient-to-r from-sky-600 to-sky-500 text-white p-2.5 px-3 rounded-2xl rounded-br-sm text-xs ms-auto max-w-[85%] shadow-xs leading-relaxed">${msg}</div>`;
    input.value = '';
    input.disabled = true;
    if (sendBtn) sendBtn.disabled = true;

    // Typing dots
    const typingId = 'simTyping_' + Date.now();
    box.innerHTML += `
        <div id="${typingId}" class="bg-white border border-slate-200/80 p-2.5 px-3 rounded-2xl rounded-bl-sm text-xs text-slate-500 shadow-xs max-w-[85%] flex items-center gap-1.5">
            <span class="inline-block w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce"></span>
            <span class="inline-block w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 0.2s"></span>
            <span class="inline-block w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 0.4s"></span>
            <span class="text-[10px] text-slate-400 ms-1">Typing...</span>
        </div>
    `;
    box.scrollTop = box.scrollHeight;
    
    try {
        const res = await fetch('/api/chatbot/message', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'Accept': 'application/json'
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
        
        // Append bot message bubble with AI badge
        box.innerHTML += `
            <div class="bg-white border border-slate-200/90 p-3 rounded-2xl rounded-bl-sm text-xs text-slate-800 shadow-xs max-w-[85%] leading-relaxed">
                ${formatSimulatorMessage(reply)}
            </div>
        `;

        // Update history
        conversationHistory.push({ role: 'user', text: msg });
        conversationHistory.push({ role: 'bot', text: reply });
        if (conversationHistory.length > 8) conversationHistory = conversationHistory.slice(-8);

        // Render dynamic chips if returned
        if (data.chips && Array.isArray(data.chips) && data.chips.length > 0) {
            let chipsHtml = `<div class="flex flex-wrap gap-1.5 mt-1" id="simulatorChips">`;
            data.chips.forEach(c => {
                chipsHtml += `<button type="button" onclick="testChipClick('${c.replace(/'/g, "\\'")}')" class="text-[11px] font-semibold bg-sky-50 text-sky-700 border border-sky-200 px-2.5 py-1 rounded-full hover:bg-sky-100 transition">${c}</button>`;
            });
            chipsHtml += `</div>`;
            box.innerHTML += chipsHtml;
        }
    } catch(e) {
        document.getElementById(typingId)?.remove();
        box.innerHTML += `<div class="bg-rose-50 border border-rose-200 text-rose-700 p-2.5 rounded-2xl rounded-bl-sm text-xs">Error communicating with Gemini AI service. Check logs.</div>`; 
    } finally {
        input.disabled = false;
        if (sendBtn) sendBtn.disabled = false;
        input.focus();
        box.scrollTop = box.scrollHeight;
    }
}

function showLogDetail(question, response, timestamp, visitor) {
    document.getElementById('logModalQuestion').textContent = question;
    document.getElementById('logModalResponse').innerHTML = formatSimulatorMessage(response);
    document.getElementById('logModalMeta').textContent = `${visitor} • ${timestamp}`;
    
    const modalEl = document.getElementById('logDetailModal');
    if (modalEl && window.bootstrap) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    resetSimulatorChat();
});
</script>
@endpush