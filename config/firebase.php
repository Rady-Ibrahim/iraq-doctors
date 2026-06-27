<?php

use App\Support\FirebaseCredentials;

$credentialsPath = env('FIREBASE_CREDENTIALS', '');
$projectId = env('FIREBASE_PROJECT_ID') ?: FirebaseCredentials::projectId($credentialsPath ?: null);
$authDomain = env('FIREBASE_AUTH_DOMAIN') ?: FirebaseCredentials::authDomain($credentialsPath ?: null);
$webApiKey = env('FIREBASE_WEB_API_KEY') ?: FirebaseCredentials::webApiKey($credentialsPath ?: null);

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase credentials (service account JSON)
    |--------------------------------------------------------------------------
    |
    | project_id و auth_domain يُقرآن تلقائياً من هذا الملف.
    | Web API Key غير موجود في service account — ضعه في .env أو
    | storage/app/firebase/firebase-web-config.json بجانب ملف credentials.
    |
    */

    'credentials' => env('FIREBASE_CREDENTIALS', ''),

    'auth_test_mode' => (bool) env('FIREBASE_AUTH_TEST_MODE', false),

    'auth_test_key' => env('FIREBASE_AUTH_TEST_KEY', ''),

    'web_api_key' => $webApiKey ?: '',
    'auth_domain' => $authDomain ?: '',
    'project_id' => $projectId ?: '',

];
