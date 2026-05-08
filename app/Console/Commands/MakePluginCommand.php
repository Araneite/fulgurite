<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

#[Signature('plugins:make {name : Plugin name, for example Restic Health} {--slug= : Plugin slug} {--force : Overwrite an existing plugin}')]
#[Description('Create a new plugin template')]
class MakePluginCommand extends Command
{
    public function handle(): int
    {
        $name = trim((string) $this->argument('name'));
        $className = Str::studly($name);
        $slug = Str::slug((string) ($this->option('slug') ?: $name));
        $pluginPath = base_path("plugins/{$className}");

        if (File::exists($pluginPath) && ! $this->option('force')) {
            $this->error("Plugin already exists: {$className}");
            $this->line('Use --force to overwrite it.');

            return self::FAILURE;
        }

        File::ensureDirectoryExists("{$pluginPath}/routes");
        File::ensureDirectoryExists("{$pluginPath}/src/Http/Controllers");

        $providerClass = "{$className}ServiceProvider";
        $controllerClass = "{$className}Controller";
        $routeName = str_replace('-', '.', $slug);

        $this->put("{$pluginPath}/plugin.php", <<<PHP
<?php

return [
    "slug" => "{$slug}",
    "name" => "{$name}",
    "version" => "1.0.0",
    "provider" => Plugins\\{$className}\\{$providerClass}::class,
];

PHP);

        $this->put("{$pluginPath}/src/{$providerClass}.php", <<<PHP
<?php

namespace Plugins\\{$className};

use Illuminate\\Support\\ServiceProvider;

class {$providerClass} extends ServiceProvider
{
    public function boot(): void
    {
        \$this->loadRoutesFrom(__DIR__ . "/../routes/api.php");
    }
}

PHP);

        $this->put("{$pluginPath}/routes/api.php", <<<PHP
<?php

use App\\Http\\Middleware\\IsUserAccountActive;
use Illuminate\\Support\\Facades\\Route;
use Plugins\\{$className}\\Http\\Controllers\\{$controllerClass};

Route::prefix("api/internal/plugins/{$slug}")
    ->name("plugins.{$routeName}.")
    ->middleware(["auth:sanctum", IsUserAccountActive::class])
    ->group(function () {
        Route::get("/status", [{$controllerClass}::class, "status"])
            ->name("status");
    });

PHP);

        $this->put("{$pluginPath}/src/Http/Controllers/{$controllerClass}.php", <<<PHP
<?php

namespace Plugins\\{$className}\\Http\\Controllers;

use App\\Http\\Controllers\\Controller;
use App\\Http\\Resources\\API\\Internal\\BaseResource;

class {$controllerClass} extends Controller
{
    public function status(): BaseResource
    {
        return BaseResource::make([
            "plugin" => "{$slug}",
            "status" => "ok",
            "checked_at" => now()->toIso8601String(),
        ])
            ->success()
            ->setMessage("Plugin {$name} loaded.");
    }
}

PHP);

        $this->info("Plugin template created: plugins/{$className}");
        $this->line("Run php artisan plugins:sync to synchronize it with the database.");

        return self::SUCCESS;
    }

    private function put(string $path, string $contents): void
    {
        File::put($path, str_replace("\r\n", "\n", $contents));
    }
}
