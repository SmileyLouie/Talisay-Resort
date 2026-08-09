<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebReportController extends WebControllers
{
    public function index() { return $this->reportsIndex(); }
    public function exportPdf(Request $request) { return parent::exportPdf($request); }
    public function exportExcel(Request $request) { return parent::exportExcel($request); }
}
