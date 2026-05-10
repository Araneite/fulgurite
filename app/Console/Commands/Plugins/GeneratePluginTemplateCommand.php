<?php

namespace App\Console\Commands\Plugins;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

#[Signature('plugins:template {name : Plugin name, for example System Info} {--slug : Plugin slug} {--force : Overwrite existing plugin files}')]
#[Description('Create a new plugin template')]
class GeneratePluginTemplateCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = trim((string) $this->argument('name'));
        $className = Str::studly($name);
        $slug = Str::slug((string) $this->option("slug") ?: $name);
        $pluginPath = base_path("plugins/{$className}/");
        
        if (File::exists($pluginPath) && !$this->option("force")) {
            $this->error("Plugin already exists: {$className}");
            $this->line('Use --force to overwrite existing plugin files or change the name.');
            
            return self::FAILURE;
        }
        
        File::ensureDirectoryExists($pluginPath . "/database/migrations");
        File::ensureDirectoryExists($pluginPath . "/routes");
        File::ensureDirectoryExists($pluginPath . "/src/Http/Controllers");
        File::ensureDirectoryExists($pluginPath . "/src/Http/Middleware");
        File::ensureDirectoryExists($pluginPath . "/src/Http/Requests");
        File::ensureDirectoryExists($pluginPath . "/src/Http/Resources");
        File::ensureDirectoryExists($pluginPath . "/src/Console/Commands");
        File::ensureDirectoryExists($pluginPath . "/src/Exceptions");
        File::ensureDirectoryExists($pluginPath . "/src/Models");
        File::ensureDirectoryExists($pluginPath . "/src/Policies");
        File::ensureDirectoryExists($pluginPath . "/src/Traits");
        
        $providerClass = $className."ServiceProvider";
        $controllerClass = $className."Controller";
        $routeName = str_replace("-", ".", $slug);
        
        $this->put($pluginPath . "/plugin.php", <<<PHP
<?php

return [
    "slug"=> $slug,
    "name"=> $name,
    "version"=> "1.0.0",
    "provider"=> Plugins\\$className\\$providerClass::class,
    
    // --- Each route of your plugin must be defined below ---
    "routes"=> [
        [
            "method" => "GET",
            "uri"=> "health",
            "core_middleware"=> []
        ]
    ]
];
PHP);
        
        $this->put($pluginPath . "/src/$providerClass.php", <<<PHP
<?php

namespace Plugins\\$className;

use Illuminate\\Support\\ServiceProvider;

class $providerClass extends ServiceProvider
{
    public function boot(): void
    {
        \$this->loadRoutesFrom(__DIR__ . "/routes/api.php");
        \$this->loadRoutesFrom(__DIR__ . "/routes/web.php");
    }
}
PHP);
        
        $this->put($pluginPath . "/routes/api.php", <<<PHP
<?php

use Illuminate\\Support\\Facades\\Route;
use Plugins\\{$className}\\Http\\Controllers\\{$controllerClass};

Route::prefix("api/plugins/$slug")
    ->name("plugins.$routeName.")
    ->middleware([])
    ->group(function () {
        Route::get("/health", [$controllerClass::class, "health"])
            ->name("health");
    });

PHP);
        
        $this->put($pluginPath . "/routes/web.php", <<<PHP
<?php

use Illuminate\\Support\\Facades\\Route;
use Plugins\\{$className}\\Http\\Controllers\\{$controllerClass};

Route::prefix("plugins/$slug")
    ->name("plugins.$routeName.")
    ->middleware([])
    ->group(function () {
        Route::get("/health", [$controllerClass::class, "health"])
            ->name("health");
    });
PHP);
        
        $this->put($pluginPath . "/src/Http/Controllers/$controllerClass.php", <<<PHP
<?php

namespace Plugins\\$className\\Http\\Controllers;

use App\\Http\\Controllers\\Controller;
use Illuminate\Http\JsonResponse;

class $controllerClass extends Controller
{
    public function health(): JsonResponse {
        return response()->json([
            "plugin"=> $slug,
            "success" => true,
            "checked_at"=> now()
        ]);
    }
}

PHP
        );
        
        // TODO : Create README to document how to build a plugin
        $this->put($pluginPath . "/README.md", <<<MD

MD);
        
        $this->alert("Plugin template created: plugin/$className");
        $this->info("Run php artisan plugins:sync-manifests to synchronize it with database.");
        
        return self::SUCCESS;
    }
    
    private function put(string $path, string $contents): void {
        File::put($path, str_replace("\r\n", "\n", $contents));
    }
}
