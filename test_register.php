<?php

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => 'http://127.0.0.1:8000/api/v1/auth/register',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'name' => 'Final Test User',
        'phone' => '07799999999',
        'email' => 'finaltest@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]),
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response:\n";
echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
