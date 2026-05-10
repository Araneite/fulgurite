<?php

namespace App\Console\Commands\Plugins;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

#[Signature('plugins:migrate {slug : Plugin slug to migrate} {--force : Force migrations in production}')]
#[Description('Run migrations for a single plugin')]
class MigratePluginCommand extends Command
{
    public function handle(): int
    {
        $slug = (string) $this->argument('slug');
        $manifestFile = $this->findManifestBySlug($slug);
        
        if (! $manifestFile) {
            $this->error("Plugin [{$slug}] not found.");
            
            return self::FAILURE;
        }
        
        $pluginPath = dirname($manifestFile);
        $migrationPath = $pluginPath.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';
        
        if (! is_dir($migrationPath)) {
            $this->info("Plugin [{$slug}] has no migrations.");
            
            return self::SUCCESS;
        }
        
        $relativePath = str_replace(base_path().DIRECTORY_SEPARATOR, '', $migrationPath);
        
        $this->info("Running migrations for plugin [{$slug}]...");
        
        $exitCode = Artisan::call('migrate', [
            '--path' => $relativePath,
            '--force' => (bool) $this->option('force'),
        ]);
        
        $output = Artisan::output();
        
        if ($output !== '') {
            $this->line($output);
        }
        
        if ($exitCode !== self::SUCCESS) {
            $this->error("Plugin [{$slug}] migrations failed.");
            
            return self::FAILURE;
        }
        
        $this->info("Plugin [{$slug}] migrations completed.");
        
        return self::SUCCESS;
    }
    
    private function findManifestBySlug(string $slug): ?string
    {
        foreach (glob(base_path('plugins/*/plugin.php')) as $manifestFile) {
            if (str_contains($manifestFile, DIRECTORY_SEPARATOR.'Sandbox'.DIRECTORY_SEPARATOR)) {
                continue;
            }
            
            $manifest = require $manifestFile;
            
            if (($manifest['slug'] ?? null) === $slug) {
                return $manifestFile;
            }
        }
        
        return null;
    }
}
