<?php

namespace App\Console\Commands\Plugins;

use App\Models\Plugin;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

#[Signature('plugins:enable {slug : Plugin slug to enable} {--skip-validation : Enable without running plugins:validate}')]
#[Description('Enable an installed plugin')]
class EnablePluginCommand extends Command
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
        
        if ($plugin->enabled) {
            $this->info("Plugin [{$slug}] is already enabled.");
            
            return self::SUCCESS;
        }
        
        if (! $this->option('skip-validation')) {
            $this->info("Validating plugin [{$slug}]...");
            
            $exitCode = Artisan::call('plugins:validate', [
                'slug' => $slug,
            ]);
            
            $output = Artisan::output();
            
            if ($output !== '') {
                $this->line($output);
            }
            
            if ($exitCode !== self::SUCCESS) {
                $this->error("Plugin [{$slug}] validation failed.");
                
                return self::FAILURE;
            }
        }
        
        $plugin->update([
            'enabled' => true,
        ]);
        
        $this->info("Plugin [{$slug}] enabled.");
        
        return self::SUCCESS;
    }
}
