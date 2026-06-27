<?php

namespace App\Console\Commands;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Console\Command;

class FirebaseSetupWebCommand extends Command
{
    protected $signature = 'firebase:setup-web {--name=Iraq Doctors Web : Display name for the Web app}';

    protected $description = 'Create Firebase Web app (if missing) and save apiKey for doctor phone SMS';

    public function handle(): int
    {
        $credentialsPath = config('firebase.credentials');

        if (!$credentialsPath) {
            $this->error('FIREBASE_CREDENTIALS is not set in .env');

            return self::FAILURE;
        }

        $path = base_path($credentialsPath);

        if (!is_file($path)) {
            $this->error("Credentials file not found: {$credentialsPath}");

            return self::FAILURE;
        }

        $json = json_decode((string) file_get_contents($path), true);
        $projectId = $json['project_id'] ?? '';

        if ($projectId === '') {
            $this->error('project_id missing in credentials JSON');

            return self::FAILURE;
        }

        $access = $this->getAccessToken($json);

        if ($access === null) {
            $this->error('Could not authenticate with service account');

            return self::FAILURE;
        }

        $list = $this->firebaseRequest('GET', "https://firebase.googleapis.com/v1beta1/projects/{$projectId}/webApps", $access);

        if ($list['code'] !== 200) {
            $this->error("Firebase API error (HTTP {$list['code']})");
            $this->line($list['body']);
            $this->newLine();
            $this->warn('Enable Firebase Management API:');
            $this->line("https://console.cloud.google.com/apis/library/firebase.googleapis.com?project={$projectId}");

            return self::FAILURE;
        }

        $apps = json_decode($list['body'], true)['apps'] ?? [];

        if ($apps === []) {
            $displayName = (string) $this->option('name');
            $this->info("No Web app found — creating \"{$displayName}\"...");

            $create = $this->firebaseRequest(
                'POST',
                "https://firebase.googleapis.com/v1beta1/projects/{$projectId}/webApps",
                $access,
                ['displayName' => $displayName]
            );

            if (!in_array($create['code'], [200, 201], true)) {
                $this->error("Could not create Web app (HTTP {$create['code']})");
                $this->line($create['body']);

                return self::FAILURE;
            }

            sleep(2);

            $list = $this->firebaseRequest('GET', "https://firebase.googleapis.com/v1beta1/projects/{$projectId}/webApps", $access);
            $apps = json_decode($list['body'], true)['apps'] ?? [];

            if ($apps === []) {
                $this->error('Web app created but not listed yet. Run the command again in a few seconds.');

                return self::FAILURE;
            }

            $this->info('Web app created.');
        }

        $app = $apps[0];
        $appResource = $app['name'] ?? '';
        $appId = $app['appId'] ?? basename($appResource);
        $display = $app['displayName'] ?? $appId;

        $configResp = $this->firebaseRequest(
            'GET',
            "https://firebase.googleapis.com/v1beta1/projects/{$projectId}/webApps/{$appId}/config",
            $access
        );

        if ($configResp['code'] !== 200) {
            $this->error("Could not fetch Web config (HTTP {$configResp['code']})");
            $this->line($configResp['body']);

            return self::FAILURE;
        }

        $config = json_decode($configResp['body'], true);
        $apiKey = $config['apiKey'] ?? '';

        if ($apiKey === '') {
            $this->error('apiKey missing in Firebase response');

            return self::FAILURE;
        }

        $outDir = dirname($path);
        $outFile = $outDir . DIRECTORY_SEPARATOR . 'firebase-web-config.json';

        file_put_contents($outFile, json_encode([
            'apiKey' => $apiKey,
            'authDomain' => $config['authDomain'] ?? '',
            'projectId' => $config['projectId'] ?? $projectId,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        $this->newLine();
        $this->info("Web app: {$display}");
        $this->line("apiKey: {$apiKey}");
        $this->line('authDomain: ' . ($config['authDomain'] ?? ''));
        $this->newLine();
        $this->info("Saved: {$outFile}");
        $this->newLine();
        $this->comment('Add to .env (optional — also read from firebase-web-config.json):');
        $this->line("FIREBASE_WEB_API_KEY={$apiKey}");
        $this->newLine();
        $this->warn('Then run: php artisan config:clear');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $serviceAccount
     */
    private function getAccessToken(array $serviceAccount): ?string
    {
        try {
            $creds = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/firebase',
                $serviceAccount
            );
            $token = $creds->fetchAuthToken();

            return $token['access_token'] ?? null;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return null;
        }
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @return array{code: int, body: string}
     */
    private function firebaseRequest(string $method, string $url, string $access, ?array $body = null): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$access}",
                'Content-Type: application/json',
            ],
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['code' => $code, 'body' => is_string($response) ? $response : ''];
    }
}
