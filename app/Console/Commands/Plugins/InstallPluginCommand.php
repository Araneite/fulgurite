<?php

namespace App\Console\Commands\Plugins;

use App\Models\Plugin;
use App\Models\PluginRoute;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('plugins:install {slug : Plugin slug to install} {--force : Continue after validation warnings} {--skip-migrations : Skip plugin migrations} {--skip-grants : Skip DB grants refresh}')]
#[Description('Validate, migrate, grant, sync, and activate a plugin')]
class InstallPluginCommand extends Command
{
    public function handle(): int
    {
        $slug = (string) $this->argument('slug');
        
        $manifestFile = $this->findManifestBySlug($slug);
        
        if (! $manifestFile) {
            $this->error("Plugin [{$slug}] not found.");
            
            return self::FAILURE;
        }
        
        $manifest = require $manifestFile;
        $pluginPath = dirname($manifestFile);
        $tablePrefix = $this->tablePrefix($slug);
        
        $this->info("Installing plugin [{$slug}]...");
        
        if (! $this->runValidation($slug)) {
            return self::FAILURE;
        }
        
        try {
            DB::transaction(function () use ($manifest, $manifestFile, $pluginPath, $slug): void {
                $this->syncManifestDisabled($manifest, $manifestFile);
                
                if (! $this->option('skip-migrations')) {
                    $this->runPluginMigrations($pluginPath, $slug);
                }
            });
            
            $pluginTables = $this->detectPluginTables($tablePrefix);
            
            if (! $this->option('skip-grants')) {
                $this->refreshGrants($pluginTables);
                $this->testSandboxPermissions($pluginTables);
            }
            
            $this->activatePlugin($slug);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            $this->error('Plugin installation failed. Plugin was not activated.');
            
            return self::FAILURE;
        }
        
        $this->info("Plugin [{$slug}] installed and activated.");
        
        return self::SUCCESS;
    }
    
    private function runValidation(string $slug): bool
    {
        $this->info('Running validation...');
        
        $exitCode = Artisan::call('plugins:validate', [
            'slug' => $slug,
        ]);
        
        $output = Artisan::output();
        
        if ($output !== '') {
            $this->line($output);
        }
        
        if ($exitCode !== self::SUCCESS) {
            $this->error('Validation failed.');
            
            return false;
        }
        
        return true;
    }
    
    private function syncManifestDisabled(array $manifest, string $manifestFile): Plugin
    {
        $this->info('Syncing manifest with plugin disabled...');
        
        $plugin = Plugin::updateOrCreate(
            [
                'slug' => $manifest['slug'],
            ],
            [
                'name' => $manifest['name'],
                'version' => $manifest['version'] ?? null,
                'path' => dirname($manifestFile),
                'namespace' => $manifest['namespace'] ?? null,
                'provider' => $manifest['provider'] ?? null,
                'enabled' => false,
                'settings' => $manifest['settings'] ?? null,
                'installed_at' => now(),
            ],
        );
        
        $seen = [];
        
        foreach ($manifest['routes'] ?? [] as $route) {
            $method = strtoupper($route['method']);
            $uri = trim($route['uri'], '/');
            $seen[] = $method.' '.$uri;
            
            PluginRoute::updateOrCreate(
                [
                    'plugin_id' => $plugin->id,
                    'method' => $method,
                    'uri' => $uri,
                ],
                [
                    'core_middleware' => $route['core_middleware'] ?? ['auth'],
                    'enabled' => true,
                ],
            );
        }
        
        PluginRoute::query()
            ->where('plugin_id', $plugin->id)
            ->get()
            ->each(function (PluginRoute $route) use ($seen): void {
                if (! in_array($route->method.' '.$route->uri, $seen, true)) {
                    $route->delete();
                }
            });
        
        return $plugin;
    }
    
    private function runPluginMigrations(string $pluginPath, string $slug): void
    {
        $migrationPath = $pluginPath.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';
        
        if (! is_dir($migrationPath)) {
            $this->line('No plugin migrations found.');
            
            return;
        }
        
        $this->info('Running plugin migrations...');
        
        $exitCode = Artisan::call('migrate', [
            '--path' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $migrationPath),
            '--realpath' => false,
            '--force' => true,
        ]);
        
        $output = Artisan::output();
        
        if ($output !== '') {
            $this->line($output);
        }
        
        if ($exitCode !== self::SUCCESS) {
            throw new \RuntimeException("Plugin [{$slug}] migrations failed.");
        }
    }
    
    private function detectPluginTables(string $tablePrefix): array
    {
        $this->info("Detecting plugin tables with prefix [{$tablePrefix}]...");
        
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $database = $connection->getDatabaseName();
        
        $tables = match ($driver) {
            'mysql', 'mariadb' => collect($connection->select(
                'select table_name as name from information_schema.tables where table_schema = ? and table_name like ?',
                [$database, $tablePrefix.'%'],
            ))->pluck('name')->values()->all(),
            
            'pgsql' => collect($connection->select(
                "select tablename as name from pg_tables where schemaname = current_schema() and tablename like ?",
                [$tablePrefix.'%'],
            ))->pluck('name')->values()->all(),
            
            'sqlite' => collect($connection->select(
                "select name from sqlite_master where type = 'table' and name like ?",
                [$tablePrefix.'%'],
            ))->pluck('name')->values()->all(),
            
            default => throw new \RuntimeException("Unsupported DB driver [{$driver}] for plugin table detection."),
        };
        
        foreach ($tables as $table) {
            if (! str_starts_with($table, $tablePrefix)) {
                throw new \RuntimeException("Detected plugin table [{$table}] does not respect prefix [{$tablePrefix}].");
            }
        }
        
        if ($tables === []) {
            $this->line('No plugin tables detected.');
        } else {
            foreach ($tables as $table) {
                $this->line("Detected table: {$table}");
            }
        }
        
        return $tables;
    }
    
    private function refreshGrants(array $pluginTables): void
    {
        $runtimeUser = env('PLUGINS_DB_USERNAME');
        
        if (! is_string($runtimeUser) || $runtimeUser === '') {
            throw new \RuntimeException('Missing PLUGINS_DB_USERNAME.');
        }
        
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        
        $this->info("Refreshing DB grants for sandbox user [{$runtimeUser}]...");
        
        match ($driver) {
            'mysql', 'mariadb' => $this->refreshMysqlGrants($connection, $runtimeUser, $pluginTables),
            'pgsql' => $this->refreshPostgresGrants($connection, $runtimeUser, $pluginTables),
            'sqlite' => $this->warn('SQLite does not support per-table grants. Skipping grants.'),
            default => throw new \RuntimeException("Unsupported DB driver [{$driver}] for grants."),
        };
    }
    
    private function refreshMysqlGrants(ConnectionInterface $connection, string $runtimeUser, array $pluginTables): void
    {
        $database = $connection->getDatabaseName();
        $quotedDatabase = $this->quoteMysqlIdentifier($database);
        $quotedUser = $this->quoteMysqlString($runtimeUser);
        
        foreach ($this->coreReadTables() as $table) {
            if (! $this->tableExists($table)) {
                continue;
            }
            
            $connection->statement(
                'GRANT SELECT ON '.$quotedDatabase.'.'.$this->quoteMysqlIdentifier($table).' TO '.$quotedUser."@'%'"
            );
        }
        
        foreach ($pluginTables as $table) {
            $connection->statement(
                'GRANT SELECT, INSERT, UPDATE, DELETE ON '.$quotedDatabase.'.'.$this->quoteMysqlIdentifier($table).' TO '.$quotedUser."@'%'"
            );
        }
    }
    
    private function refreshPostgresGrants(ConnectionInterface $connection, string $runtimeUser, array $pluginTables): void
    {
        foreach ($this->coreReadTables() as $table) {
            if (! $this->tableExists($table)) {
                continue;
            }
            
            $connection->statement(
                'GRANT SELECT ON TABLE '.$this->quotePostgresIdentifier($table).' TO '.$this->quotePostgresIdentifier($runtimeUser)
            );
        }
        
        foreach ($pluginTables as $table) {
            $connection->statement(
                'GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE '.$this->quotePostgresIdentifier($table).' TO '.$this->quotePostgresIdentifier($runtimeUser)
            );
        }
    }
    
    private function testSandboxPermissions(array $pluginTables): void
    {
        $this->info('Testing sandbox DB permissions...');
        
        $connection = $this->sandboxConnection();
        
        $connection->select('select 1');
        
        foreach ($this->coreReadTables() as $table) {
            if (! $this->tableExists($table)) {
                continue;
            }
            
            $connection->table($table)->limit(1)->get();
            
            try {
                $connection->table($table)->whereRaw('1 = 0')->update(['id' => DB::raw('id')]);
                
                throw new \RuntimeException("Sandbox user can write to core table [{$table}], expected read-only.");
            } catch (Throwable) {
                //
            }
        }
        
        foreach ($pluginTables as $table) {
            $connection->table($table)->limit(1)->get();
        }
    }
    
    private function sandboxConnection(): ConnectionInterface
    {
        $config = config('database.connections.'.config('database.default'));
        
        $config['username'] = env('PLUGINS_DB_USERNAME');
        $config['password'] = env('PLUGINS_DB_PASSWORD');
        
        if (env('PLUGINS_DB_CONNECTION')) {
            $config['driver'] = env('PLUGINS_DB_CONNECTION');
        }
        
        if (env('PLUGINS_DB_HOST')) {
            $config['host'] = env('PLUGINS_DB_HOST');
        }
        
        if (env('PLUGINS_DB_PORT')) {
            $config['port'] = env('PLUGINS_DB_PORT');
        }
        
        if (env('PLUGINS_DB_DATABASE')) {
            $config['database'] = env('PLUGINS_DB_DATABASE');
        }
        
        config(['database.connections.plugin_sandbox_validation' => $config]);
        
        DB::purge('plugin_sandbox_validation');
        
        return DB::connection('plugin_sandbox_validation');
    }
    
    private function activatePlugin(string $slug): void
    {
        $this->info('Activating plugin...');
        
        Plugin::query()
            ->where('slug', $slug)
            ->update([
                'enabled' => true,
            ]);
    }
    
    private function coreReadTables(): array
    {
        return config('plugins.core_read_tables', [
            'fg_plugins',
            'fg_plugin_routes',
            'fg_users',
            'fg_roles',
            'fg_customers',
        ]);
    }
    
    private function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }
    
    private function tablePrefix(string $slug): string
    {
        return 'plugin_'.str_replace('-', '_', $slug).'_';
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
    
    private function quoteMysqlIdentifier(string $value): string
    {
        return '`'.str_replace('`', '``', $value).'`';
    }
    
    private function quoteMysqlString(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }
    
    private function quotePostgresIdentifier(string $value): string
    {
        return '"'.str_replace('"', '""', $value).'"';
    }
}
