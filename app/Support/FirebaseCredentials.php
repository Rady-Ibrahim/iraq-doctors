<?php

namespace App\Support;

class FirebaseCredentials
{
    /** @var array<string, mixed>|null */
    private static ?array $cache = null;

    /**
     * @return array<string, mixed>
     */
    public static function read(?string $credentialsRelativePath = null): array
    {
        if (self::$cache !== null && $credentialsRelativePath === null) {
            return self::$cache;
        }

        $relative = $credentialsRelativePath ?? (string) env('FIREBASE_CREDENTIALS', '');

        if ($relative === '') {
            return $credentialsRelativePath === null ? (self::$cache = []) : [];
        }

        $path = base_path($relative);

        if (!is_file($path)) {
            return $credentialsRelativePath === null ? (self::$cache = []) : [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        $data = is_array($decoded) ? $decoded : [];

        if ($credentialsRelativePath === null) {
            self::$cache = $data;
        }

        return $data;
    }

    public static function projectId(?string $credentialsRelativePath = null): ?string
    {
        $credentials = self::read($credentialsRelativePath);

        $projectId = $credentials['project_id'] ?? null;

        return is_string($projectId) && $projectId !== '' ? $projectId : null;
    }

    public static function authDomain(?string $credentialsRelativePath = null): ?string
    {
        $credentials = self::read($credentialsRelativePath);

        if (!empty($credentials['auth_domain']) && is_string($credentials['auth_domain'])) {
            return $credentials['auth_domain'];
        }

        $projectId = self::projectId();

        return $projectId ? "{$projectId}.firebaseapp.com" : null;
    }

    /**
     * Web API Key is NOT in the service-account JSON — only in Firebase Web app config.
     */
    public static function webApiKey(?string $credentialsRelativePath = null): ?string
    {
        $credentials = self::read($credentialsRelativePath);

        foreach (['web_api_key', 'api_key', 'apiKey'] as $key) {
            if (!empty($credentials[$key]) && is_string($credentials[$key])) {
                return $credentials[$key];
            }
        }

        $webConfigPath = self::webConfigPath($credentialsRelativePath);

        if ($webConfigPath && is_file($webConfigPath)) {
            $web = json_decode((string) file_get_contents($webConfigPath), true);

            if (is_array($web)) {
                foreach (['api_key', 'apiKey'] as $key) {
                    if (!empty($web[$key]) && is_string($web[$key])) {
                        return $web[$key];
                    }
                }
            }
        }

        return null;
    }

    private static function webConfigPath(?string $credentialsRelativePath = null): ?string
    {
        $relative = $credentialsRelativePath ?? (string) env('FIREBASE_CREDENTIALS', '');

        if ($relative === '') {
            return null;
        }

        $dir = dirname(base_path($relative));

        foreach (['firebase-web-config.json', 'web-config.json'] as $filename) {
            $path = $dir . DIRECTORY_SEPARATOR . $filename;

            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
