<?php

namespace App\Http\Controllers;

use App\Models\ChatbotIntent;
use Illuminate\Http\Request;

class WebChatbotController extends WebControllers
{
    public function index() { return $this->chatbotIndex(); }
    public function store(Request $request) { return $this->chatbotStore($request); }
    public function update(Request $request, ChatbotIntent $intent) { return $this->chatbotUpdate($request, $intent); }
    public function destroy(ChatbotIntent $intent) { return $this->chatbotDestroy($intent); }
}
