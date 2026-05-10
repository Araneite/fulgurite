<?php

namespace Plugins\Sandbox\Console\Commands;

use App\Models\Plugin;
use Illuminate\Console\Command;

class SyncPluginsCommand extends Command
{
	protected $signature = 'plugins:sync';
    protected $description = 'Synchronize plugins from disk into fg_plugins';
    
    public function handle(): int {
        $diskSlugs = [];
        $pluginPath = dirname(__DIR__, 4);
        
        foreach (glob($pluginPath . "/*/plugin.php") as $pluginFile) {
            if (str_contains($pluginFile, DIRECTORY_SEPARATOR . "Sandbox" . DIRECTORY_SEPARATOR)) {
                continue;
            }
            
            $manifest = require $pluginFile;
            $diskSlugs[] = $manifest["slug"];
            
            Plugin::updateOrCreate(
                ["slug" => $manifest["slug"]],
                [
                    "name" => $manifest["name"],
                    "version" => $manifest["version"] ?? null,
                    "path"=> dirname($pluginFile),
                    "namespace"=> $manifest["namespace"] ?? null,
                    "provider"=> $manifest["provider"] ?? null,
                    "settings"=> $manifest["settings"] ?? null,
                    "installed_at"=> now()
                ]
            );
            
            $this->info("Plugins synchronized: {$manifest["slug"]}");
        }
        
        return self::SUCCESS;
    }
}
