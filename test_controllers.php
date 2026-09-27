<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$app->instance('request', \Illuminate\Http\Request::create('/'));

$admin = \App\Models\User::where('role', 'admin')->first();
\Illuminate\Support\Facades\Auth::login($admin);

echo "Testing WebControllers methods directly:\n";

$wc = new \App\Http\Controllers\WebControllers();
$ac = new \App\Http\Controllers\WebAccommodationController();
$tc = new \App\Http\Controllers\WebTouristPortalController();

try {
    $view = $wc->dashboardIndex();
    echo "dashboardIndex: SUCCESS (returned " . $view->name() . ")\n";
    $html = $view->render();
    echo "dashboardIndex HTML render: SUCCESS (length " . strlen($html) . ")\n";
} catch (\Throwable $e) {
    echo "dashboardIndex ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}

try {
    $view = $wc->reportsIndex();
    echo "reportsIndex: SUCCESS (returned " . $view->name() . ")\n";
    $html = $view->render();
    echo "reportsIndex HTML render: SUCCESS (length " . strlen($html) . ")\n";
} catch (\Throwable $e) {
    echo "reportsIndex ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}

try {
    $view = $wc->bookingsIndex(new \Illuminate\Http\Request());
    echo "bookingsIndex: SUCCESS\n";
    $html = $view->render();
    echo "bookingsIndex HTML render: SUCCESS\n";
} catch (\Throwable $e) {
    echo "bookingsIndex ERROR: " . $e->getMessage() . "\n";
}

try {
    $view = $ac->index(new \Illuminate\Http\Request());
    echo "accommodationsIndex: SUCCESS\n";
    $html = $view->render();
    echo "accommodationsIndex HTML render: SUCCESS\n";
} catch (\Throwable $e) {
    echo "accommodationsIndex ERROR: " . $e->getMessage() . "\n";
}

try {
    $view = $wc->usersIndex(new \Illuminate\Http\Request());
    echo "usersIndex: SUCCESS\n";
    $html = $view->render();
    echo "usersIndex HTML render: SUCCESS\n";
} catch (\Throwable $e) {
    echo "usersIndex ERROR: " . $e->getMessage() . "\n";
}

try {
    $view = $wc->paymentsIndex(new \Illuminate\Http\Request());
    echo "paymentsIndex: SUCCESS\n";
    $html = $view->render();
    echo "paymentsIndex HTML render: SUCCESS\n";
} catch (\Throwable $e) {
    echo "paymentsIndex ERROR: " . $e->getMessage() . "\n";
}

try {
    $view = $wc->reviewsIndex(new \Illuminate\Http\Request());
    echo "reviewsIndex: SUCCESS\n";
    $html = $view->render();
    echo "reviewsIndex HTML render: SUCCESS\n";
} catch (\Throwable $e) {
    echo "reviewsIndex ERROR: " . $e->getMessage() . "\n";
}

try {
    $view = $wc->settingsIndex();
    echo "settingsIndex: SUCCESS\n";
    $html = $view->render();
    echo "settingsIndex HTML render: SUCCESS\n";
} catch (\Throwable $e) {
    echo "settingsIndex ERROR: " . $e->getMessage() . "\n";
}

echo "\n--- TESTING TOURIST PORTAL CONTROLLER ---\n";
$tourist = \App\Models\User::where('role', 'tourist')->first();
\Illuminate\Support\Facades\Auth::login($tourist);

try {
    $view = $tc->dashboard();
    echo "tourist dashboard: SUCCESS\n";
    $html = $view->render();
    echo "tourist dashboard HTML render: SUCCESS\n";
} catch (\Throwable $e) {
    echo "tourist dashboard ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}

try {
    $view = $tc->bookings();
    echo "tourist bookings: SUCCESS\n";
    $html = $view->render();
    echo "tourist bookings HTML render: SUCCESS\n";
} catch (\Throwable $e) {
    echo "tourist bookings ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}

try {
    $view = $ac->guestIndex(new \Illuminate\Http\Request());
    echo "tourist accommodations (guestIndex): SUCCESS\n";
    $html = $view->render();
    echo "tourist accommodations HTML render: SUCCESS\n";
} catch (\Throwable $e) {
    echo "tourist accommodations ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}

try {
    $unit = \App\Models\AccommodationUnit::first();
    $view = $ac->guestDetail($unit);
    echo "tourist accommodation detail (guestDetail): SUCCESS\n";
    $html = $view->render();
    echo "tourist accommodation detail HTML render: SUCCESS\n";
} catch (\Throwable $e) {
    echo "tourist accommodation detail ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}

try {
    $view = $tc->reviews();
    echo "tourist reviews: SUCCESS\n";
    $html = $view->render();
    echo "tourist reviews HTML render: SUCCESS\n";
} catch (\Throwable $e) {
    echo "tourist reviews ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n--- ALL CONTROLLERS DIRECT TEST COMPLETED ---\n";
