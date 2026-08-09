<?php

namespace App\Http\Controllers;

use App\Models\Emergency;
use Illuminate\Http\Request;

class WebEmergencyController extends WebControllers
{
    public function index(Request $request) { return $this->emergenciesIndex($request); }
    public function updateStatus(Request $request, Emergency $emergency) { return $this->updateEmergencyStatus($request, $emergency); }
}
