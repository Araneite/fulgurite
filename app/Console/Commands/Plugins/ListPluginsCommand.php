<?php

namespace App\Console\Commands\Plugins;

use App\Models\Plugin;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('plugins:list')]
#[Description('List installed plugins')]
class ListPluginsCommand extends Command
{
    public function handle(): int
    {
        $plugins = Plugin::query()
            ->withCount('routes')
            ->orderBy('slug')
            ->get();

        if ($plugins->isEmpty()) {
            $this->info('No plugins installed.');

            return self::SUCCESS;
        }

        $rows = $plugins->map(function (Plugin $plugin): array {
            return [
                'slug' => $plugin->slug,
                'name' => $plugin->name,
                'version' => $plugin->version ?: 'n/a',
                'enabled' => $plugin->enabled ? 'yes' : 'no',
                'routes' => (string) $plugin->routes_count,
                'tables' => (string) count($this->detectPluginTables($this->tablePrefix($plugin->slug))),
                'installed_at' => optional($plugin->installed_at)->toDateTimeString() ?: 'n/a',
            ];
        })->all();

        $this->table(
            ['Slug', 'Name', 'Version', 'Enabled', 'Routes', 'Tables', 'Installed At'],
            $rows,
        );

        return self::SUCCESS;
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

            'sqlite' => collect($connection->select(
                "select name from sqlite_master where type = 'table' and name like ?",
                [$tablePrefix.'%'],
            ))->pluck('name')->values()->all(),

            default => [],
        };
    }

    private function tablePrefix(string $slug): string
    {
        return 'plugin_'.str_replace('-', '_', $slug).'_';
    }
}
