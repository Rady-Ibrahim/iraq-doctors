<?php

require __DIR__ . '/../vendor/autoload.php';

use Google\Auth\Credentials\ServiceAccountCredentials;

$path = __DIR__ . '/../storage/app/firebase/firebase-credentials.json';

if (!is_file($path)) {
    fwrite(STDERR, "Missing credentials: {$path}\n");
    exit(1);
}

$json = json_decode((string) file_get_contents($path), true);
$projectId = $json['project_id'] ?? '';

if ($projectId === '') {
    fwrite(STDERR, "No project_id in credentials file\n");
    exit(1);
}

$scope = 'https://www.googleapis.com/auth/firebase';
$creds = new ServiceAccountCredentials($scope, $json);
$token = $creds->fetchAuthToken();
$access = $token['access_token'] ?? '';

if ($access === '') {
    fwrite(STDERR, "Failed to get access token\n");
    exit(1);
}

function firebaseRequest(string $method, string $url, string $access, ?array $body = null): array
{
    $ch = curl_init($url);
    $headers = ["Authorization: Bearer {$access}", 'Content-Type: application/json'];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $code, 'body' => $response ?: ''];
}

$list = firebaseRequest('GET', "https://firebase.googleapis.com/v1beta1/projects/{$projectId}/webApps", $access);
$data = json_decode($list['body'], true);

if ($list['code'] !== 200) {
    fwrite(STDERR, "List web apps failed (HTTP {$list['code']}):\n{$list['body']}\n");
    exit(1);
}

$apps = $data['apps'] ?? [];

if ($apps === []) {
    echo "No Web app found — creating \"Iraq Doctors Web\"...\n";

    $create = firebaseRequest(
        'POST',
        "https://firebase.googleapis.com/v1beta1/projects/{$projectId}/webApps",
        $access,
        ['displayName' => 'Iraq Doctors Web']
    );

    if ($create['code'] !== 200) {
        fwrite(STDERR, "Create web app failed (HTTP {$create['code']}):\n{$create['body']}\n\n");
        echo "Enable Firebase Management API:\n";
        echo "https://console.cloud.google.com/apis/library/firebase.googleapis.com?project={$projectId}\n\n";
        echo "Or create Web app manually:\n";
        echo "https://console.firebase.google.com/project/{$projectId}/overview\n";
        exit(1);
    }

    $created = json_decode($create['body'], true);
    $apps = [$created];
    echo "Web app created.\n\n";
}

foreach ($apps as $app) {
    $appId = basename($app['name'] ?? '');
    $display = $app['displayName'] ?? $appId;

    $configResp = firebaseRequest(
        'GET',
        "https://firebase.googleapis.com/v1beta1/projects/{$projectId}/webApps/{$appId}/config",
        $access
    );

    if ($configResp['code'] !== 200) {
        fwrite(STDERR, "Config failed for {$display}: {$configResp['body']}\n");
        continue;
    }

    $config = json_decode($configResp['body'], true);

    echo "Web app: {$display}\n";
    echo "apiKey: " . ($config['apiKey'] ?? '(missing)') . "\n";
    echo "authDomain: " . ($config['authDomain'] ?? '') . "\n";
    echo "projectId: " . ($config['projectId'] ?? '') . "\n\n";

    $outPath = __DIR__ . '/../storage/app/firebase/firebase-web-config.json';
    $toSave = [
        'apiKey' => $config['apiKey'] ?? '',
        'authDomain' => $config['authDomain'] ?? '',
        'projectId' => $config['projectId'] ?? '',
    ];

    if ($toSave['apiKey'] !== '') {
        file_put_contents($outPath, json_encode($toSave, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        echo "Saved: storage/app/firebase/firebase-web-config.json\n";
        echo "Add to .env:\n";
        echo "FIREBASE_WEB_API_KEY={$toSave['apiKey']}\n";
    }
}
