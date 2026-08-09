<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class WebBookingController extends WebControllers
{
    public function index(Request $request) { return $this->bookingsIndex($request); }
    public function updateStatus(Request $request, Booking $booking) { return $this->updateBookingStatus($request, $booking); }
}
