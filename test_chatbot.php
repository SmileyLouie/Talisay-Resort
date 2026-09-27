<?php
// test_chatbot.php — Test the chatbot API controller end-to-end with database

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$session = $app['session']->driver('array');
$session->start();

$app->instance('request', \Illuminate\Http\Request::create('/'));

$tourist = \App\Models\User::where('role', 'tourist')->first();
\Illuminate\Support\Facades\Auth::login($tourist);

$ctrl = $app->make(\App\Http\Controllers\Api\ChatbotController::class);

$testCases = [
    // Dynamic queries
    ['msg' => 'what are the room rates?',             'expect_keywords' => ['Room', 'Cottage', '₱']],
    ['msg' => 'availability today',                    'expect_keywords' => ['Availability', 'capacity']],
    ['msg' => 'how do I pay via gcash?',               'expect_keywords' => ['GCash', 'Upload Proof']],
    ['msg' => 'virtual tour',                          'expect_keywords' => ['360', 'Virtual Tour']],
    ['msg' => 'emergency help',                        'expect_keywords' => ['Front Desk', 'Hotline', '911']],
    // Intent table lookups
    ['msg' => 'what time do you open?',                'expect_keywords' => ['8:00', 'AM']],
    ['msg' => 'how do I cancel my booking?',           'expect_keywords' => ['cancel', 'refund', '48']],
    ['msg' => 'is there wifi?',                        'expect_keywords' => ['WiFi', 'pavilion']],
    ['msg' => 'how to get there?',                     'expect_keywords' => ['Baybay', 'Leyte', 'coastal']],
    ['msg' => 'hello',                                 'expect_keywords' => ['Welcome', 'Talisay']],
    // Fallback
    ['msg' => 'zxqjklmnopqrs',                        'expect_keywords' => ['Rates', 'Book', 'Tour']],
];

$sessionId = 'test_sess_' . uniqid();
$passed = 0;

foreach ($testCases as $i => $tc) {
    $req = \Illuminate\Http\Request::create('/api/chatbot/message', 'POST', [
        'message'    => $tc['msg'],
        'session_id' => $sessionId,
    ]);
    $req->setLaravelSession($session);
    $app->instance('request', $req);

    $res = $ctrl->message($req);
    $data = json_decode($res->getContent(), true);
    $responseText = $data['response'] ?? $data['reply'] ?? '';

    $matched = false;
    foreach ($tc['expect_keywords'] as $kw) {
        if (stripos($responseText, $kw) !== false) {
            $matched = true;
            break;
        }
    }

    $testNum = $i + 1;
    if ($matched) {
        echo "TEST {$testNum} PASS: \"{$tc['msg']}\" => Intent[{$data['intent']}], Chips: " . implode(', ', $data['chips'] ?? []) . "\n";
        $passed++;
    } else {
        echo "TEST {$testNum} FAIL: \"{$tc['msg']}\" => Response was: \"" . substr($responseText, 0, 120) . "\"\n";
        echo "  Expected one of: " . implode(', ', $tc['expect_keywords']) . "\n";
    }
}

$total = count($testCases);
echo "\n=== CHATBOT TEST RESULTS: {$passed}/{$total} PASSED ===\n";

// Verify logs were written
$logCount = \App\Models\ChatbotLog::where('session_id', $sessionId)->count();
echo "Chatbot logs written to database: {$logCount} / {$total} messages logged\n";
