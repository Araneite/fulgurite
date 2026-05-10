<?php

namespace App\Console\Commands\Plugins;

use App\Models\Plugin;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

#[Signature('plugins:uninstall {slug : Plugin slug to uninstall} {--drop-tables : Drop plugin tables matching the plugin prefix} {--force : Do not ask for confirmation}')]
#[Description('Uninstall a plugin from the core registry')]
class UninstallPluginCommand extends Command
{
    public function handle(): int
    {
        $slug = (string) $this->argument('slug');
        
        $plugin = Plugin::query()
            ->where('slug', $slug)
            ->first();
        
        if (! $plugin) {
            $this->error("Plugin [{$slug}] is not installed.");
            
            return self::FAILURE;
        }
        
        if (! $this->option('force')) {
            $confirmed = $this->confirm(
                $this->option('drop-tables')
                    ? "Uninstall plugin [{$slug}] and drop its plugin tables?"
                    : "Uninstall plugin [{$slug}] without dropping plugin tables?"
            );
            
            if (! $confirmed) {
                $this->info('Uninstall cancelled.');
                
                return self::SUCCESS;
            }
        }
        
        try {
            DB::transaction(function () use ($plugin): void {
                $plugin->routes()->delete();
                
                $plugin->update([
                    'enabled' => false,
                ]);
                
                $plugin->delete();
            });
            
            if ($this->option('drop-tables')) {
                $this->dropPluginTables($this->tablePrefix($slug));
            }
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            
            return self::FAILURE;
        }
        
        $this->info("Plugin [{$slug}] uninstalled.");
        
        return self::SUCCESS;
    }
    
    private function dropPluginTables(string $tablePrefix): void
    {
        $tables = $this->detectPluginTables($tablePrefix);
        
        foreach ($tables as $table) {
            if (! str_starts_with($table, $tablePrefix)) {
                throw new \RuntimeException("Refusing to drop table [{$table}] outside prefix [{$tablePrefix}].");
            }
            
            Schema::dropIfExists($table);
            $this->line("Dropped table: {$table}");
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
