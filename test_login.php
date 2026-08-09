<?php
// Test login functionality
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8900/login');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'email' => 'admin@talisayresort.com',
    'password' => 'password',
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_COOKIEJAR, sys_get_temp_dir() . '/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, sys_get_temp_dir() . '/cookies.txt');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";

if ($httpCode === 302 || $httpCode === 301) {
    preg_match('/Location: (.*)/i', $response, $matches);
    echo "Redirect to: " . trim($matches[1] ?? 'unknown') . "\n";
}

echo "Login test completed.\n";
