<?php

namespace App\Console\Commands\Plugins;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use JsonException;
use Throwable;

#[Signature('plugins:doctor')]
#[Description('Diagnose plugin sandbox configuration, connectivity, autoload isolation, and DB access')]
class DoctorPluginsCommand extends Command
{
    private array $errors = [];
    private array $warnings = [];

    public function handle(): int
    {
        $this->info('Running plugin system diagnostics...');

        $this->checkCoreConfig();
        $this->checkSandboxFiles();
        $this->checkSandboxAutoloadIsolation();
        $this->checkSandboxStorage();
        $this->checkSandboxHealth();
        $this->checkCoreDatabase();
        $this->checkSandboxDatabaseConfig();

        foreach ($this->warnings as $warning) {
            $this->warn($warning);
        }

        foreach ($this->errors as $error) {
            $this->error($error);
        }

        if ($this->errors !== []) {
            $this->newLine();
            $this->error('Plugin diagnostics failed.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Plugin diagnostics passed.');

        return self::SUCCESS;
    }

    private function checkCoreConfig(): void
    {
        $this->line('Checking core plugin config...');

        if (! is_file(config_path('plugins.php'))) {
            $this->failCheck('Missing config/plugins.php.');
        }

        if (! config('plugins.sandbox_url')) {
            $this->failCheck('Missing plugins.sandbox_url.');
        }

        if (! config('plugins.sandbox_secret')) {
            $this->failCheck('Missing plugins.sandbox_secret.');
        }

        if (! is_array(config('plugins.core_middleware_aliases'))) {
            $this->failCheck('plugins.core_middleware_aliases must be an array.');
        }
    }

    private function checkSandboxFiles(): void
    {
        $this->line('Checking sandbox files...');

        $requiredFiles = [
            base_path('plugins/Sandbox/composer.json'),
            base_path('plugins/Sandbox/vendor/autoload.php'),
            base_path('plugins/Sandbox/public/index.php'),
            base_path('plugins/Sandbox/artisan.php'),
            base_path('plugins/Sandbox/bootstrap/app.php'),
            base_path('plugins/Sandbox/bootstrap/providers.php'),
            base_path('plugins/Sandbox/config/database.php'),
            base_path('plugins/Sandbox/config/plugins.php'),
            base_path('plugins/Sandbox/.env'),
        ];

        foreach ($requiredFiles as $file) {
            if (! is_file($file)) {
                $this->failCheck("Missing sandbox file: {$file}");
            }
        }
    }

    private function checkSandboxAutoloadIsolation(): void
    {
        $this->line('Checking sandbox autoload isolation...');

        $sandboxComposerFile = base_path('plugins/Sandbox/composer.json');

        if (is_file($sandboxComposerFile)) {
            try {
                $composer = json_decode(File::get($sandboxComposerFile), true, 512, JSON_THROW_ON_ERROR);
                $psr4 = $composer['autoload']['psr-4'] ?? [];

                $appMapping = $psr4['App\\'] ?? null;

                if ($appMapping === null) {
                    $this->warnCheck('Sandbox composer.json has no App\\ mapping. Some Laravel commands may fail namespace detection.');
                } else {
                    $mappings = is_array($appMapping) ? $appMapping : [$appMapping];

                    foreach ($mappings as $mapping) {
                        $normalized = str_replace('\\', '/', (string) $mapping);

                        if (str_contains($normalized, '../../app') || str_contains($normalized, '../app')) {
                            $this->failCheck('Sandbox App\\ namespace must not point to the core app directory.');
                        }
                    }
                }
            } catch (JsonException $exception) {
                $this->failCheck('Invalid plugins/Sandbox/composer.json: '.$exception->getMessage());
            }
        }

        $rootComposerFile = base_path('composer.json');

        if (is_file($rootComposerFile)) {
            try {
                $composer = json_decode(File::get($rootComposerFile), true, 512, JSON_THROW_ON_ERROR);
                $psr4 = $composer['autoload']['psr-4'] ?? [];

                if (array_key_exists('Plugins\\', $psr4)) {
                    $this->failCheck('Root composer.json must not map Plugins\\ to plugins/.');
                }
            } catch (JsonException $exception) {
                $this->failCheck('Invalid root composer.json: '.$exception->getMessage());
            }
        }

        $publicIndex = base_path('plugins/Sandbox/public/index.php');

        if (is_file($publicIndex)) {
            $source = File::get($publicIndex);

            if (str_contains($source, '../../../vendor/autoload.php')) {
                $this->failCheck('Sandbox public/index.php must not require the root vendor/autoload.php.');
            }
        }

        $artisan = base_path('plugins/Sandbox/artisan.php');

        if (is_file($artisan)) {
            $source = File::get($artisan);

            if (str_contains($source, '../../vendor/autoload.php')) {
                $this->failCheck('Sandbox artisan.php must not require the root vendor/autoload.php.');
            }
        }
    }

    private function checkSandboxStorage(): void
    {
        $this->line('Checking sandbox storage/cache paths...');

        $directories = [
            base_path('plugins/Sandbox/storage/logs'),
            base_path('plugins/Sandbox/storage/framework'),
            base_path('plugins/Sandbox/storage/framework/cache'),
            base_path('plugins/Sandbox/storage/framework/sessions'),
            base_path('plugins/Sandbox/storage/framework/views'),
            base_path('plugins/Sandbox/bootstrap/cache'),
        ];

        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                $this->failCheck("Missing sandbox directory: {$directory}");

                continue;
            }

            if (! is_writable($directory)) {
                $this->failCheck("Sandbox directory is not writable: {$directory}");
            }
        }
    }

    private function checkSandboxHealth(): void
    {
        $this->line('Checking sandbox HTTP health...');

        $url = rtrim((string) config('plugins.sandbox_url'), '/').'/api/sandbox/health';

        try {
            $response = Http::withHeaders([
                'X-Fulgurite-Sandbox-Secret' => (string) config('plugins.sandbox_secret'),
            ])->timeout((int) config('plugins.timeout', 10))->get($url);

            if (! $response->successful()) {
                $this->failCheck("Sandbox health endpoint returned HTTP {$response->status()}.");
            }
        } catch (Throwable $exception) {
            $this->failCheck('Sandbox health endpoint is unreachable: '.$exception->getMessage());
        }
    }

    private function checkCoreDatabase(): void
    {
        $this->line('Checking core database...');

        try {
            DB::connection()->select('select 1');
        } catch (Throwable $exception) {
            $this->failCheck('Core database connection failed: '.$exception->getMessage());

            return;
        }

        foreach (['fg_plugins', 'fg_plugin_routes'] as $table) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                $this->failCheck("Missing core plugin table: {$table}");
            }
        }
    }

    private function checkSandboxDatabaseConfig(): void
    {
        $this->line('Checking sandbox database env...');

        $sandboxEnv = base_path('plugins/Sandbox/.env');

        if (! is_file($sandboxEnv)) {
            $this->failCheck('Missing plugins/Sandbox/.env.');

            return;
        }

        $content = File::get($sandboxEnv);

        foreach ([
                     'PLUGINS_DB_CONNECTION',
                     'PLUGINS_DB_HOST',
                     'PLUGINS_DB_PORT',
                     'PLUGINS_DB_DATABASE',
                     'PLUGINS_DB_USERNAME',
                     'PLUGINS_DB_PASSWORD',
                     'PLUGINS_SANDBOX_SECRET',
                 ] as $key) {
            if (! preg_match('/^'.$key.'=/m', $content)) {
                $this->failCheck("Missing {$key} in plugins/Sandbox/.env.");
            }
        }
    }

    private function failCheck(string $message): void
    {
        $this->errors[] = $message;
    }

    private function warnCheck(string $message): void
    {
        $this->warnings[] = $message;
    }
}
