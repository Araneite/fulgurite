<?php

namespace App\Console\Commands\Plugins;

use App\Models\Plugin;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('plugins:disable {slug : Plugin slug to disable}')]
#[Description('Disable a plugin without deleting its data')]
class DisablePluginCommand extends Command
{
    public function handle(): int
    {
        $slug = (string) $this->argument('slug');
        
        $plugin = Plugin::query()
            ->where('slug', $slug)
            ->first();
        
        if (! $plugin) {
            $this->error("Plugin [{$slug}] not found.");
            
            return self::FAILURE;
        }
        
        if (! $plugin->enabled) {
            $this->info("Plugin [{$slug}] is already disabled.");
            
            return self::SUCCESS;
        }
        
        $plugin->update([
            'enabled' => false,
        ]);
        
        $this->info("Plugin [{$slug}] disabled.");
        
        return self::SUCCESS;
    }
}
