<?php

namespace App\Console\Commands;

use App\Models\Plugin;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('plugins:sync')]
#[Description("Synchronize plugin on disk with the database")]
class SyncPluginsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $diskSlugs = [];

        foreach (glob(base_path('plugins/*/plugin.php')) as $pluginFile) {
            $manifest = require $pluginFile;

            $diskSlugs[] = $manifest["slug"];

            Plugin::updateOrCreate(
                ["slug"=> $manifest["slug"]],
                [
                    "name"=> $manifest["name"],
                    "version"=> $manifest["version"],
                    "provider"=> $manifest["provider"],
                ]
            );
            
            $this->info("Plugin synchronized : {$manifest['slug']}");
        }

        $deletedPlugins = Plugin::query()
            ->when(
                ! empty($diskSlugs),
                fn ($query) => $query->whereNotIn("slug", $diskSlugs)
            )
            ->get();

        foreach ($deletedPlugins as $plugin) {
            $plugin->delete();

            $this->info("Plugin deleted : {$plugin->slug}");
        }
        
        return self::SUCCESS;
    }
}
