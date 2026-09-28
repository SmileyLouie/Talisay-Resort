<?php

namespace App\Http\Controllers;

use App\Models\TourAsset;
use Illuminate\Http\Request;

class WebTourController extends WebControllers
{
    public function index() { return $this->tourIndex(); }
    public function viewer() { return $this->tourViewer(); }
    public function store(Request $request) { return $this->tourStore($request); }
    public function update(Request $request, TourAsset $asset) { return $this->tourUpdate($request, $asset); }
    public function destroy(TourAsset $asset) { return $this->tourDestroy($asset); }
}
