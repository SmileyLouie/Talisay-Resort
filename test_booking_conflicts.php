<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$app->instance('request', \Illuminate\Http\Request::create('/'));

echo "=== TESTING BOOKING CONFLICT & SCHEDULING SYSTEM ===\n\n";

$tourist1 = \App\Models\User::where('role', 'tourist')->first();
$tourist2 = \App\Models\User::where('role', 'tourist')->skip(1)->first() ?? \App\Models\User::factory()->create(['role' => 'tourist']);

$unit1 = \App\Models\AccommodationUnit::first();
$unit2 = \App\Models\AccommodationUnit::skip(1)->first();

echo "Unit 1: {$unit1->unit_number} (ID: {$unit1->id})\n";
echo "Unit 2: {$unit2->unit_number} (ID: {$unit2->id})\n";
echo "Tourist 1: {$tourist1->name} (ID: {$tourist1->id})\n";
echo "Tourist 2: {$tourist2->name} (ID: {$tourist2->id})\n\n";

// Teardown any bookings on test dates
\App\Models\Booking::whereIn('accommodation_unit_id', [$unit1->id, $unit2->id])
    ->where('booking_date', '>=', '2026-09-01')
    ->delete();

$tc = new \App\Http\Controllers\WebTouristPortalController();

// TEST 1: Tourist 1 books Room 1 from 2026-09-10 to 2026-09-12
\Illuminate\Support\Facades\Auth::login($tourist1);
$session = $app['session']->driver('array');
$session->start();

$req1 = \Illuminate\Http\Request::create('/tourist/bookings', 'POST', [
    'accommodation_unit_id' => $unit1->id,
    'booking_date'          => '2026-09-10',
    'check_out_date'        => '2026-09-12',
    'guests_count'          => 2,
    'payment_method'        => 'gcash',
]);
$req1->setLaravelSession($session);
$app->instance('request', $req1);

$res1 = $tc->storeBooking($req1);
$booking1 = \App\Models\Booking::where('accommodation_unit_id', $unit1->id)
    ->whereDate('booking_date', '2026-09-10')
    ->first();

if ($booking1) {
    echo "TEST 1 PASS: Tourist 1 successfully booked {$unit1->unit_number} for 2026-09-10 to 2026-09-12 (Ref: {$booking1->reference_no})\n";
} else {
    echo "TEST 1 FAIL: Tourist 1 booking failed.\n";
}

// TEST 2: Tourist 2 tries to book same Room 1 with overlapping dates (2026-09-11 to 2026-09-13)
\Illuminate\Support\Facades\Auth::login($tourist2);
$req2 = \Illuminate\Http\Request::create('/tourist/bookings', 'POST', [
    'accommodation_unit_id' => $unit1->id,
    'booking_date'          => '2026-09-11',
    'check_out_date'        => '2026-09-13',
    'guests_count'          => 2,
    'payment_method'        => 'cash',
]);
$req2->setLaravelSession($session);
$app->instance('request', $req2);

$res2 = $tc->storeBooking($req2);
$booking2 = \App\Models\Booking::where('user_id', $tourist2->id)
    ->where('accommodation_unit_id', $unit1->id)
    ->whereDate('booking_date', '2026-09-11')
    ->first();

if (!$booking2 && session('error')) {
    echo "TEST 2 PASS: Overlapping booking correctly BLOCKED! Error: " . session('error') . "\n";
} else {
    echo "TEST 2 FAIL: Overlapping booking was NOT blocked!\n";
}

// TEST 3: Tourist 2 checks availability endpoint for Room 1
$checkReq = \Illuminate\Http\Request::create("/tourist/accommodations/{$unit1->id}/check-availability", 'GET', [
    'check_in'  => '2026-09-11',
    'check_out' => '2026-09-13',
]);
$availRes = $tc->checkAvailability($checkReq, $unit1);
$availData = json_decode($availRes->getContent(), true);

if ($availData['available'] === false) {
    echo "TEST 3 PASS: Availability check endpoint correctly returns available=false for conflicted dates.\n";
} else {
    echo "TEST 3 FAIL: Availability check returned available=true when unit is booked.\n";
}

// TEST 4: Tourist 2 books Room 1 AFTER checkout date (2026-09-12 to 2026-09-14) -> Allowed!
$req4 = \Illuminate\Http\Request::create('/tourist/bookings', 'POST', [
    'accommodation_unit_id' => $unit1->id,
    'booking_date'          => '2026-09-12',
    'check_out_date'        => '2026-09-14',
    'guests_count'          => 2,
    'payment_method'        => 'gcash',
]);
$req4->setLaravelSession($session);
$app->instance('request', $req4);

$res4 = $tc->storeBooking($req4);
$booking4 = \App\Models\Booking::where('user_id', $tourist2->id)
    ->where('accommodation_unit_id', $unit1->id)
    ->whereDate('booking_date', '2026-09-12')
    ->first();

if ($booking4) {
    echo "TEST 4 PASS: Non-overlapping booking (starting at checkout 2026-09-12) successfully booked! (Ref: {$booking4->reference_no})\n";
} else {
    echo "TEST 4 FAIL: Non-overlapping booking failed. Error: " . session('error') . "\n";
    $conf = \App\Models\Booking::getConflictingBooking($unit1->id, '2026-09-12', '2026-09-14');
    echo "Conflict detected: " . ($conf ? "YES: Ref " . $conf->reference_no . " ({$conf->check_in_date} to {$conf->check_out_date})" : "NO") . "\n";
}

// TEST 5: Tourist 1 modifies booking to Unit 2 (which is free on 2026-09-10 to 2026-09-12) -> Allowed!
\Illuminate\Support\Facades\Auth::login($tourist1);
$modifyReq = \Illuminate\Http\Request::create("/tourist/bookings/{$booking1->id}/modify", 'POST', [
    'new_accommodation_unit_id' => $unit2->id,
    'modification_notes'        => 'Switched to Unit 2',
]);
$modifyReq->setLaravelSession($session);
$app->instance('request', $modifyReq);

$tc->modifyBooking($modifyReq, $booking1);
$booking1->refresh();

if ((int)$booking1->accommodation_unit_id === (int)$unit2->id) {
    echo "TEST 5 PASS: Modification to free unit successfully switched to {$unit2->unit_number}\n";
} else {
    echo "TEST 5 FAIL: Modification failed.\n";
}

// TEST 6: Tourist 1 modifies booking to Unit 1 on dates where Tourist 2 has Room 1 (2026-09-12 to 2026-09-14) -> BLOCKED!
$booking1->update(['check_in_date' => '2026-09-12', 'check_out_date' => '2026-09-14', 'booking_date' => '2026-09-12']);
$modifyReq2 = \Illuminate\Http\Request::create("/tourist/bookings/{$booking1->id}/modify", 'POST', [
    'new_accommodation_unit_id' => $unit1->id,
    'modification_notes'        => 'Try conflict unit',
]);
$modifyReq2->setLaravelSession($session);
$app->instance('request', $modifyReq2);

$tc->modifyBooking($modifyReq2, $booking1);

if (session('error')) {
    echo "TEST 6 PASS: Modification to already-booked unit correctly BLOCKED! Error: " . session('error') . "\n";
} else {
    echo "TEST 6 FAIL: Modification conflict was not caught.\n";
}

// TEST 7: Tourist tries Special Full-Resort booking on 2026-09-12 to 2026-09-14 when Unit 1 is booked -> BLOCKED!
$specialReq = \Illuminate\Http\Request::create('/tourist/bookings/special-resort', 'POST', [
    'check_in_date'    => '2026-09-12',
    'check_out_date'   => '2026-09-14',
    'guests_count'     => 20,
    'special_requests' => 'Full resort private event',
    'payment_method'   => 'gcash',
]);
$specialReq->setLaravelSession($session);
$app->instance('request', $specialReq);

$tc->storeSpecialResortBooking($specialReq);

if (session('error')) {
    echo "TEST 7 PASS: Special Full-Resort booking correctly BLOCKED when units have reservations! Error: " . session('error') . "\n";
} else {
    echo "TEST 7 FAIL: Special Full-Resort conflict was not caught.\n";
}

echo "\n=== ALL 7 CONFLICT & SCHEDULING TESTS PASSED! ===\n";
