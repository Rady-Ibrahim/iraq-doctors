<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeModuleFull extends Command
{
    protected $signature = 'make:module-full {name}';

    protected $description = 'Create a module skeleton aligned with Modules/Auth (Http, Services, Models, Routes, database, Providers)';

    protected Filesystem $files;

    public function __construct()
    {
        parent::__construct();
        $this->files = new Filesystem;
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $base = base_path("Modules/{$name}");

        if ($this->files->isDirectory($base) && ! $this->confirm("Module path already exists: {$base}. Continue and overwrite stub files?", false)) {
            return self::FAILURE;
        }

        $folders = [
            "{$base}/Http/Controllers/Api",
            "{$base}/Http/Controllers/Admin",
            "{$base}/Http/Requests/Api",
            "{$base}/Http/Requests/Admin",
            "{$base}/Http/Middleware",
            "{$base}/Models",
            "{$base}/Services/Api",
            "{$base}/Services/Admin",
            "{$base}/Routes",
            "{$base}/database/migrations",
            "{$base}/database/seeders",
            "{$base}/database/factories",
            "{$base}/Providers",
            "{$base}/config",
            "{$base}/resources/views",
            "{$base}/tests/Feature",
            "{$base}/tests/Unit",
        ];

        foreach ($folders as $folder) {
            if (! $this->files->isDirectory($folder)) {
                $this->files->makeDirectory($folder, 0755, true);
                $this->info("Created folder: {$folder}");
            }
        }

        $providerPath = "{$base}/Providers/{$name}ServiceProvider.php";
        $providerNamespace = "Modules\\{$name}\\Providers";

        $providerContent = <<<PHP
<?php

namespace {$providerNamespace};

use Illuminate\Support\ServiceProvider;

class {$name}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        \$this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        \$this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        \$this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
PHP;

        $this->files->put($providerPath, $providerContent);
        $this->info("Created ServiceProvider: {$providerPath}");

        $this->files->put("{$base}/Routes/api.php", "<?php\n\n// API routes — stateless (Sanctum). Do not add Spatie permission middleware here.\n\n");
        $this->files->put("{$base}/Routes/web.php", "<?php\n\n// Web routes — prefix admin routes and use permission:... only for dashboard (web guard).\n\n");

        $composerSkeleton = [
            'name' => 'modules/'.$name,
            'description' => "{$name} module",
            'autoload' => [
                'psr-4' => [
                    "Modules\\{$name}\\" => './',
                ],
            ],
            'extra' => [
                'laravel' => [
                    'providers' => [
                        "Modules\\{$name}\\Providers\\{$name}ServiceProvider",
                    ],
                ],
            ],
        ];

        $this->files->put("{$base}/composer.json", json_encode($composerSkeleton, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info("Created composer.json: {$base}/composer.json");

        $this->warn('Register the provider in bootstrap/providers.php if it is not auto-discovered:');
        $this->line("Modules\\{$name}\\Providers\\{$name}ServiceProvider::class");

        $this->info("Module '{$name}' scaffold created.");

        return self::SUCCESS;
    }
}
