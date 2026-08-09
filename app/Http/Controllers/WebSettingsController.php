<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebSettingsController extends WebControllers
{
    public function index() { return $this->settingsIndex(); }
    public function update(Request $request) { return $this->settingsUpdate($request); }
}
