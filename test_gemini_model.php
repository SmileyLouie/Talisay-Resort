<?php
$key   = getenv('GEMINI_API_KEY') ?: 'YOUR_GEMINI_API_KEY';
$model = 'gemini-3.6-flash';
$url   = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";

$payload = json_encode([
    'system_instruction' => [
        'parts' => [['text' => 'You are a friendly resort assistant for Talisay Beach Resort.']]
    ],
    'contents' => [
        ['role' => 'user', 'parts' => [['text' => 'marunong ka mag tagalog? sagutan mo']]]
    ],
    'generationConfig' => [
        'temperature'     => 0.7,
        'maxOutputTokens' => 300,
    ]
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
$res  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

$data = json_decode($res, true);
echo "HTTP Status: {$code}\n";
if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
    echo "✅ Gemini replied:\n" . $data['candidates'][0]['content']['parts'][0]['text'] . "\n";
} else {
    echo "❌ Full response:\n" . json_encode($data, JSON_PRETTY_PRINT) . "\n";
}
