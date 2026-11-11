<?php

namespace App\Http\Controllers;

use App\Models\ChatbotIntent;
use Illuminate\Http\Request;

class WebChatbotController extends WebControllers
{
    public function index() { return $this->chatbotIndex(); }
    public function updateSettings(Request $request) { return $this->chatbotUpdateSettings($request); }
    public function clearLogs() { return $this->chatbotClearLogs(); }
    public function destroyLog(\App\Models\ChatbotLog $log) { return $this->chatbotDeleteLog($log); }
    public function store(Request $request) { return $this->chatbotStore($request); }
    public function update(Request $request, ChatbotIntent $intent) { return $this->chatbotUpdate($request, $intent); }
    public function destroy(ChatbotIntent $intent) { return $this->chatbotDestroy($intent); }
}
