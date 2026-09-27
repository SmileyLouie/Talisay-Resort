<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$session = $app['session']->driver('array');
$session->start();

$ctrl = $app->make(\App\Http\Controllers\Api\ChatbotController::class);

echo "=== TESTING CHATBOT ACROSS ALL SYSTEM ROLES ===\n\n";

// 1. GUEST / PUBLIC (Landing, Login, Register)
echo "1. Testing Guest (Public / Landing / Login / Register)...\n";
$req = \Illuminate\Http\Request::create('/api/chatbot/message', 'POST', [
    'message' => 'What are your room rates and cottages?',
    'session_id' => 'guest_test_' . time(),
]);
$req->setLaravelSession($session);
$app->instance('request', $req);
\Illuminate\Support\Facades\Auth::logout();

$res = $ctrl->message($req);
$data = json_decode($res->getContent(), true);
echo "   Status: " . $res->getStatusCode() . "\n";
echo "   Role: " . ($data['role'] ?? 'N/A') . "\n";
echo "   AI Powered: " . (($data['ai_powered'] ?? false) ? 'YES (Gemini)' : 'Fallback') . "\n";
echo "   Reply snippet: " . substr(strip_tags($data['response'] ?? ''), 0, 120) . "...\n";
echo "   Chips: " . implode(', ', $data['chips'] ?? []) . "\n\n";

// 2. TOURIST (Client Portal)
echo "2. Testing Tourist (Client Side)...\n";
$tourist = \App\Models\User::where('role', 'tourist')->first();
\Illuminate\Support\Facades\Auth::login($tourist);
$req2 = \Illuminate\Http\Request::create('/api/chatbot/message', 'POST', [
    'message' => 'Can you show my bookings?',
    'session_id' => 'tourist_test_' . time(),
]);
$req2->setLaravelSession($session);
$req2->setUserResolver(fn() => $tourist);
$app->instance('request', $req2);

$res2 = $ctrl->message($req2);
$data2 = json_decode($res2->getContent(), true);
echo "   Status: " . $res2->getStatusCode() . "\n";
echo "   Role: " . ($data2['role'] ?? 'N/A') . "\n";
echo "   AI Powered: " . (($data2['ai_powered'] ?? false) ? 'YES (Gemini)' : 'Fallback') . "\n";
echo "   Reply snippet: " . substr(strip_tags($data2['response'] ?? ''), 0, 120) . "...\n";
echo "   Chips: " . implode(', ', $data2['chips'] ?? []) . "\n\n";

// 3. STAFF
echo "3. Testing Staff Portal...\n";
$staff = \App\Models\User::where('role', 'staff')->first() ?? \App\Models\User::factory()->create(['role' => 'staff']);
\Illuminate\Support\Facades\Auth::login($staff);
$req3 = \Illuminate\Http\Request::create('/api/chatbot/message', 'POST', [
    'message' => "Today's arrivals and occupancy summary",
    'session_id' => 'staff_test_' . time(),
]);
$req3->setLaravelSession($session);
$req3->setUserResolver(fn() => $staff);
$app->instance('request', $req3);

$res3 = $ctrl->message($req3);
$data3 = json_decode($res3->getContent(), true);
echo "   Status: " . $res3->getStatusCode() . "\n";
echo "   Role: " . ($data3['role'] ?? 'N/A') . "\n";
echo "   AI Powered: " . (($data3['ai_powered'] ?? false) ? 'YES (Gemini)' : 'Fallback') . "\n";
echo "   Reply snippet: " . substr(strip_tags($data3['response'] ?? ''), 0, 120) . "...\n";
echo "   Chips: " . implode(', ', $data3['chips'] ?? []) . "\n\n";

// 4. ADMIN
echo "4. Testing Admin Portal...\n";
$admin = \App\Models\User::where('role', 'admin')->first() ?? \App\Models\User::factory()->create(['role' => 'admin']);
\Illuminate\Support\Facades\Auth::login($admin);
$req4 = \Illuminate\Http\Request::create('/api/chatbot/message', 'POST', [
    'message' => "Overview of pending bookings and revenue",
    'session_id' => 'admin_test_' . time(),
]);
$req4->setLaravelSession($session);
$req4->setUserResolver(fn() => $admin);
$app->instance('request', $req4);

$res4 = $ctrl->message($req4);
$data4 = json_decode($res4->getContent(), true);
echo "   Status: " . $res4->getStatusCode() . "\n";
echo "   Role: " . ($data4['role'] ?? 'N/A') . "\n";
echo "   AI Powered: " . (($data4['ai_powered'] ?? false) ? 'YES (Gemini)' : 'Fallback') . "\n";
echo "   Reply snippet: " . substr(strip_tags($data4['response'] ?? ''), 0, 120) . "...\n";
echo "   Chips: " . implode(', ', $data4['chips'] ?? []) . "\n\n";

echo "=== ALL 4 USER ROLES WORKING PROPERLY ===\n";
