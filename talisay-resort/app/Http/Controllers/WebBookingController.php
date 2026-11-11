<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class WebBookingController extends WebControllers
{
    public function index(Request $request) { return $this->bookingsIndex($request); }
    public function manualStore(Request $request) { return $this->manualBookingStore($request); }
    public function unitAvailability(Request $request) { return $this->manualUnitAvailability($request); }
    public function updateStatus(Request $request, Booking $booking) { return $this->updateBookingStatus($request, $booking); }
    public function approveSpecialBooking(Request $request, Booking $booking) { return $this->approveSpecialResortBooking($request, $booking); }
    public function rejectSpecialBooking(Request $request, Booking $booking) { return $this->rejectSpecialResortBooking($request, $booking); }
    public function storeExclusive(Request $request) { return $this->storeExclusiveBooking($request); }
    public function updateExclusive(Request $request, Booking $booking) { return $this->updateExclusiveBooking($request, $booking); }
    public function update(Request $request, Booking $booking) { return $this->updateRegularBooking($request, $booking); }
    public function destroy(Booking $booking) { return $this->destroyBooking($booking); }
}
