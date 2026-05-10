<?php

namespace App\Console\Commands\Plugins;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

#[Signature('plugins:grants-refresh {slug : Plugin slug to refresh grants for}')]
#[Description('Refresh database grants for a plugin sandbox runtime user')]
class RefreshPluginGrantsCommand extends Command
{
    public function handle(): int
    {
        $slug = (string) $this->argument('slug');
        
        if (! $this->findManifestBySlug($slug)) {
            $this->error("Plugin [{$slug}] not found.");
            
            return self::FAILURE;
        }
        
        $runtimeUser = env('PLUGINS_DB_USERNAME');
        
        if (! is_string($runtimeUser) || $runtimeUser === '') {
            $this->error('Missing PLUGINS_DB_USERNAME.');
            
            return self::FAILURE;
        }
        
        $tablePrefix = $this->tablePrefix($slug);
        $pluginTables = $this->detectPluginTables($tablePrefix);
        
        try {
            $this->refreshGrants($runtimeUser, $pluginTables);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());
            
            return self::FAILURE;
        }
        
        $this->info("Grants refreshed for plugin [{$slug}].");
        
        return self::SUCCESS;
    }
    
    private function refreshGrants(string $runtimeUser, array $pluginTables): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        
        match ($driver) {
            'mysql', 'mariadb' => $this->refreshMysqlGrants($connection, $runtimeUser, $pluginTables),
            'pgsql' => $this->refreshPostgresGrants($connection, $runtimeUser, $pluginTables),
            'sqlite' => $this->warn('SQLite does not support grants. Skipping.'),
            default => throw new RuntimeException("Unsupported DB driver [{$driver}] for grants."),
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
            
            default => throw new RuntimeException("Unsupported DB driver [{$driver}] for plugin table detection."),
        };
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
