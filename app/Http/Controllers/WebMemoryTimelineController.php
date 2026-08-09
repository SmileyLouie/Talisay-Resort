<?php

namespace App\Http\Controllers;

class WebMemoryTimelineController extends WebControllers
{
    public function index() { return $this->memoryTimelineIndex(); }
}
