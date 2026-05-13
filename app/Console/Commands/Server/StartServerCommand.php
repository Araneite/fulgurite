<?php

namespace App\Console\Commands\Server;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

#[Signature('fulgurite:serve 
{--host=127.0.0.1 : Server host Laravel}
{--port=8000 : Server port Laravel}
{--skip-build : Skip the build of frontend assets}')]
#[Description('Start the server in production mode with all assets build.')]
class StartServerCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Preparing Laravel cache...');
        
        $this->call("config:clear");
        $this->call("route:clear");
        $this->call("view:clear");
        $this->call("cache:clear");
        
        if (!$this->option('slip-build')) {
            $this->info("Build assets frontend and SSR...");
            
            $build = Process::timeout(300)->run("npm run build");
            
            if ($build->failed()) {
                $this->error($build->errorOutpu() ?: $build->output());
                return self::FAILURE;
            }
            
            $this->line($build->output());
        }
        
        $this->info("Optimize Laravel...");
        
        $this->call("config:cache");
        $this->call("route:cache");
        $this->call("view:cache");
        
        $this->info("Restart Intertia server SSR...");
        
        $this->call("inertia:stop-ssr");
        
        Process::path(base_path())
            ->start("php artisan inertia:start-ssr");
        
        sleep(2);
        
        $this->call("inertia:check-ssr");
        
        $host = $this->option('host');
        $port = $this->option('port');
        
        return $this->call("serve", [
            "--host"=> $host,
            "--port"=> $port,
        ]);
    }
}
