{{-- Extends the primary layout template designed for dashboard panels --}}
@extends('layouts.app')

{{-- Sets the HTML document title for the chatbot management page --}}
@section('title', 'Chatbot Management - Talisay Smart Tourism')

{{-- Defines the main content area section of the template --}}
@section('content')

{{-- Header layout block containing page title, subtitle, and primary add intent action button --}}
<div class="mb-6 flex items-center justify-between">
    <div>
        {{-- Principal title heading for chatbot --}}
        <h1 class="text-2xl font-bold text-gray-800">Chatbot Management</h1>
        {{-- Small help description text --}}
        <p class="text-gray-500 text-sm">Manage automated chatbot response triggers and test replies</p>
    </div>
    {{-- Button triggering the bootstrap modal to create a new intent --}}
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#intentModal" onclick="openIntentForm()">
        {{-- Icon within the add intent button --}}
        <i class="bi bi-plus-circle me-1"></i> Add Intent
    </button>
</div>

{{-- Main body row grid splitting data lists and testing panels --}}
<div class="row g-4">
    
    {{-- Left column carrying intent record tables --}}
    <div class="col-lg-8">
        {{-- Card element storing records --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            {{-- Header card --}}
            <div class="p-4 border-b">
                <h3 class="font-bold text-lg text-gray-800">Intent Triggers ({{ $intents->total() }})</h3>
            </div>
            {{-- Table wrapper ensuring responsiveness --}}
            <div class="table-responsive">
                {{-- Data list table styled with Bootstrap --}}
                <table class="table table-hover text-sm mb-0">
                    {{-- Header columns --}}
                    <thead class="table-light">
                        <tr>
                            <th>Trigger Keyword</th>
                            <th>Category</th>
                            <th>Response Content</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    {{-- Table body tracking entries --}}
                    <tbody>
                        {{-- Loop through each intent record --}}
                        @foreach($intents as $intent)
                        <tr>
                            {{-- Target trigger keyword string cell --}}
                            <td class="font-semibold">{{ $intent->keyword }}</td>
                            {{-- Group category badge label --}}
                            <td><span class="badge bg-sky-100 text-sky-700">{{ ucfirst($intent->category) }}</span></td>
                            {{-- Response text snippet --}}
                            <td class="text-truncate" style="max-width:250px;" title="{{ $intent->response }}">{{ $intent->response }}</td>
                            {{-- Active/Inactive status badge display --}}
                            <td>
                                <span class="badge bg-{{ $intent->is_active ? 'success' : 'secondary' }}">{{ $intent->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            {{-- Actions button group --}}
                            <td>
                                <div class="btn-group btn-group-sm">
                                    {{-- Edit intent action button mapping parameters to modal populator --}}
                                    <button class="btn btn-outline-primary" 
                                            data-id="{{ $intent->id }}"
                                            data-keyword="{{ $intent->keyword }}"
                                            data-category="{{ $intent->category }}"
                                            data-response="{{ $intent->response }}"
                                            onclick="editIntent(this)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    {{-- Delete form trigger pointing to target record ID --}}
                                    <form method="POST" action="{{ route('chatbot.intents.destroy', $intent) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this intent?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- Laravel standard paginator links --}}
            <div class="p-4">{{ $intents->links() }}</div>
        </div>
    </div>

    {{-- Right column carrying active interactive chatbot tester --}}
    <div class="col-lg-4">
        {{-- Card element storing tester --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            {{-- Panel label header --}}
            <h3 class="font-bold text-lg text-gray-800 mb-4">Test Chatbot</h3>
            {{-- Chat logs dialog box --}}
            <div class="border rounded-lg p-4 mb-3 overflow-y-auto" style="min-height:220px; max-height: 220px;" id="testChatBox">
                {{-- Initial robot chat bubble --}}
                <div class="chat-msg bot mb-2 p-2 bg-light rounded text-sm text-gray-700">Hello! Try typing a message below to test the keyword matching engine.</div>
            </div>
            {{-- Trigger action input row --}}
            <div class="flex gap-2">
                {{-- Type area element --}}
                <input type="text" id="testChatInput" class="form-control form-control-sm" placeholder="Type a message..." onkeydown="if(event.key === 'Enter') testChat()">
                {{-- Action trigger button --}}
                <button class="btn btn-primary btn-sm" onclick="testChat()"><i class="bi bi-send"></i></button>
            </div>
        </div>
    </div>
</div>

{{-- Standard modal markup defining the Chatbot Intent Add/Edit form layout --}}
<div class="modal fade" id="intentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            {{-- Modal title header area --}}
            <div class="modal-header bg-sky-500 text-white">
                <h5 class="modal-title" id="intentModalTitle">Add Intent</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{-- The post request action form --}}
            <form id="intentForm" method="POST" action="{{ route('chatbot.intents.store') }}">
                @csrf
                {{-- Form method override placeholder used during updates --}}
                <div id="methodPlaceholder"></div>
                
                <div class="modal-body">
                    {{-- Target trigger keyword input --}}
                    <div class="mb-3">
                        <label class="form-label font-medium text-sm">Keyword Trigger</label>
                        <input type="text" name="keyword" id="intentKeyword" class="form-control" placeholder="e.g. rates" required>
                    </div>
                    {{-- Target category select dropdown --}}
                    <div class="mb-3">
                        <label class="form-label font-medium text-sm">Category</label>
                        <select name="category" id="intentCategory" class="form-select" required>
                            <option value="rates">Rates</option>
                            <option value="hours">Hours</option>
                            <option value="policies">Policies</option>
                            <option value="directions">Directions</option>
                            <option value="facilities">Facilities</option>
                            <option value="booking_help">Booking Help</option>
                            <option value="general">General</option>
                        </select>
                    </div>
                    {{-- Reply response input textarea --}}
                    <div class="mb-3">
                        <label class="form-label font-medium text-sm">Response Reply</label>
                        <textarea name="response" id="intentResponse" class="form-control" rows="4" placeholder="Enter automated response content..." required></textarea>
                    </div>
                </div>
                {{-- Footer modal action controls --}}
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Intent</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

{{-- Script push section --}}
@push('scripts')
<script>
// Open and clear intent modal form inputs for a new entry
function openIntentForm() {
    // Set header text to creation mode
    document.getElementById('intentModalTitle').innerText = 'Add Intent';
    // Clean target action to creation endpoint route
    document.getElementById('intentForm').action = "{{ route('chatbot.intents.store') }}";
    // Clear method override placeholder
    document.getElementById('methodPlaceholder').innerHTML = '';
    // Reset all form inputs to clean default values
    document.getElementById('intentForm').reset();
}

// Populate and configure intent modal form values during updates
function editIntent(button) {
    // Set header text to editing mode
    document.getElementById('intentModalTitle').innerText = 'Edit Intent';
    // Retrieve dataset variables mapped directly onto button element
    const id = button.getAttribute('data-id');
    const keyword = button.getAttribute('data-keyword');
    const category = button.getAttribute('data-category');
    const response = button.getAttribute('data-response');

    // Configure update action endpoint route pointing to target record ID
    document.getElementById('intentForm').action = `/admin/chatbot/intents/${id}`;
    // Insert method override spoofing input to trigger Laravel update controllers
    document.getElementById('methodPlaceholder').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    
    // Fill specific text parameters inside inputs
    document.getElementById('intentKeyword').value = keyword;
    document.getElementById('intentCategory').value = category;
    document.getElementById('intentResponse').value = response;

    // Show modal manually using Bootstrap's triggers
    const modalObj = new bootstrap.Modal(document.getElementById('intentModal'));
    modalObj.show();
}

// Javascript function executing asynchronous chatbot message matching tests
async function testChat() {
    // Get input element
    const input = document.getElementById('testChatInput');
    // Get message content
    const msg = input.value.trim();
    // Return early if message content is empty
    if (!msg) return;

    // Retrieve tester box element
    const box = document.getElementById('testChatBox');
    // Output user text bubble on panel view
    box.innerHTML += `<div class="chat-msg user mb-2 p-2 bg-sky-100 rounded text-sm text-end text-sky-800">${msg}</div>`;
    // Clean input field
    input.value = '';
    
    try {
        // Send POST request trigger matching query message
        const res = await fetch('/api/chatbot/message', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            // Serialize request parameters
            body: JSON.stringify({ message: msg, session_id: 'test-session-admin' })
        });
        // Decode response object
        const data = await res.json();
        // Print reply contents inside panel views
        box.innerHTML += `<div class="chat-msg bot mb-2 p-2 bg-light rounded text-sm text-gray-700">${data.response || 'I am sorry, I do not understand that keyword.'}</div>`;
    } catch(e) {
        // Print error feedbacks
        box.innerHTML += `<div class="chat-msg bot mb-2 p-2 bg-light rounded text-sm text-danger">Error connecting to chatbot engine.</div>`; 
    }
    // Auto scroll down dialog box container
    box.scrollTop = box.scrollHeight;
}
</script>
@endpush