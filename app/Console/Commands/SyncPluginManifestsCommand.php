<?php

namespace App\Console\Commands;

use App\Models\Plugin;
use App\Models\PluginRoute;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('plugins:sync-manifests {--enable : Enable synchronized plugins}')]
#[Description('Synchronize plugin manifests without loading plugin code')]
class SyncPluginManifestsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        foreach (glob(base_path("plugins/*/plugin.php")) as $manifestFile) {
            if (str_contains($manifestFile, DIRECTORY_SEPARATOR . "Sandbox" . DIRECTORY_SEPARATOR)) {
                continue;
            }
            
            $manifest = require $manifestFile;
            
            DB::transaction(function () use ($manifest, $manifestFile) {
                $plugin = Plugin::updateOrCreate(
                    ["slug"=> $manifest["slug"]],
                    [
                        "name"=> $manifest["name"],
                        "version"=> $manifest["version"] ?? null,
                        "path"=> dirname($manifestFile),
                        "namespace"=> $manifest["namespace"] ?? null,
                        "provider"=> $manifest["provider"] ?? null,
                        "enabled"=> $this->option('enable') ? true : DB::raw('enabled'),
                        "settings"=> $manifest["settings"] ?? null,
                        "installed_at"=> now(),
                    ]
                );
                
                $seen = [];
                
                foreach($manifest["routes"] ?? [] as $route) {
                    $method = strtoupper($route["method"]);
                    $uri = trim($route["uri"], "/");
                    $seen[] = $method . " " . $uri;
                    
                    PluginRoute::updateOrCreate(
                        [
                            "plugin_id" => $plugin->id,
                            "method" => $method,
                            "uri" => $uri,
                        ],
                        [
                            "core_middleware"=> $route["core_middleware"] ?? ["auth"],
                            "enabled"=> $route["enabled"] ?? true,
                        ]
                    );
                }
                
                PluginRoute::query()
                    ->where("plugin_id", $plugin->id)
                    ->get()
                    ->each(function (PluginRoute $route) use ($seen) {
                        if (!in_array($route->method . " " . $route->uri, $seen, true)) {
                            $route->delete();
                        }
                    });
            });
            
            $this->info("Plugin manifests synchroized: {$manifest["slug"]}");
        }
        
        return self::SUCCESS;
    }
}
