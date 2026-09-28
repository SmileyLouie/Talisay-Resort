<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebSettingsController extends WebControllers
{
    public function index(Request $request) { return $this->settingsIndex($request); }
    public function update(Request $request) { return $this->settingsUpdate($request); }
    public function updateChatbot(Request $request) { return $this->settingsUpdateChatbot($request); }
    public function testAiConnection(Request $request) { return $this->settingsTestAiConnection($request); }
}
