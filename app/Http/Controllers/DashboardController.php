<?php

namespace App\Http\Controllers;

class DashboardController extends WebControllers
{
    public function index()
    {
        return $this->dashboardIndex();
    }
}
