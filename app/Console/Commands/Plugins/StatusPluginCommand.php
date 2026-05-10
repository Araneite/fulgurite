<?php

namespace App\Console\Commands\Plugins;

use App\Models\Plugin;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

#[Signature('plugins:status {slug : Plugin slug to inspect}')]
#[Description('Display plugin installation, routing, database, and sandbox status')]
class StatusPluginCommand extends Command
{
    public function handle(): int
    {
        $slug = (string) $this->argument('slug');
        
        $plugin = Plugin::query()
            ->with('routes')
            ->where('slug', $slug)
            ->first();
        
        if (! $plugin) {
            $this->error("Plugin [{$slug}] is not installed.");
            
            return self::FAILURE;
        }
        
        $this->info("Plugin: {$plugin->name}");
        $this->line("Slug: {$plugin->slug}");
        $this->line('Enabled: '.($plugin->enabled ? 'yes' : 'no'));
        $this->line("Version: ".($plugin->version ?: 'n/a'));
        $this->line("Provider: ".($plugin->provider ?: 'n/a'));
        $this->line("Path: ".($plugin->path ?: 'n/a'));
        
        $this->newLine();
        $this->info('Routes');
        
        if ($plugin->routes->isEmpty()) {
            $this->warn('No routes registered.');
        } else {
            foreach ($plugin->routes as $route) {
                $this->line(sprintf(
                    '%s /plugins/%s/%s [%s] %s',
                    $route->method,
                    $plugin->slug,
                    $route->uri,
                    implode(',', $route->core_middleware ?? []),
                    $route->enabled ? 'enabled' : 'disabled',
                ));
            }
        }
        
        $this->newLine();
        $this->info('Plugin tables');
        
        $tables = $this->detectPluginTables($this->tablePrefix($slug));
        
        if ($tables === []) {
            $this->line('No plugin tables detected.');
        } else {
            foreach ($tables as $table) {
                $this->line($table);
            }
        }
        
        $this->newLine();
        $this->info('Sandbox');
        
        $this->checkSandboxHealth();
        
        return self::SUCCESS;
    }
    
    private function checkSandboxHealth(): void
    {
        $url = rtrim((string) config('plugins.sandbox_url'), '/').'/api/sandbox/health';
        
        try {
            $response = Http::withHeaders([
                'X-Fulgurite-Sandbox-Secret' => (string) config('plugins.sandbox_secret'),
            ])->timeout((int) config('plugins.timeout', 10))->get($url);
            
            $this->line("Sandbox URL: {$url}");
            $this->line("Sandbox status: {$response->status()}");
            
            if (! $response->successful()) {
                $this->warn('Sandbox health check failed.');
            }
        } catch (Throwable $exception) {
            $this->warn('Sandbox unreachable: '.$exception->getMessage());
        }
    }
    
    private function detectPluginTables(string $tablePrefix): array
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $database = $connection->getDatabaseName();
        
        return match ($driver) {
            'mysql', 'mariadb' => collect($connection->select(
                'select table_name as name from information_schema.tables where table_schema = ? and table_name like ?',
                [$database, $tablePrefix.'%'],
            ))->pluck('name')->values()->all(),
            
            'pgsql' => collect($connection->select(
                "select tablename as name from pg_tables where schemaname = current_schema() and tablename like ?",
                [$tablePrefix.'%'],
            ))->pluck('name')->values()->all(),
            
            'sqlite' => [],
            
            default => [],
        };
    }
    
    private function tablePrefix(string $slug): string
    {
        return 'plugin_'.str_replace('-', '_', $slug).'_';
    }
}
