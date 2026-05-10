<?php

namespace App\Console\Commands\Plugins;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

#[Signature('plugins:make
{type : migration|model|controller|request|resource|collection|policy|seeder|service|job|event|listener|middleware}
{plugin : Plugin name, example SystemInfo}
{name : Class or migration name}')]
#[Description('Generate a file inside a plugin directory')]
class MakeInPluginCommand extends Command
{
    public function handle(): int
    {
        $type = Str::lower($this->argument('type'));
        $plugin = Str::studly($this->argument('plugin'));
        $name = $this->argument('name');
        
        return match ($type) {
            'migration'=> $this->makeMigration($plugin, $name),
            'model'=> $this->makeClass($plugin, "Models", $name, $this->modelStub($plugin, $name)),
            'controller'=> $this->makeClass($plugin, "Http/Controllers", $name, $this->controllerStub($plugin, $name)),
            'request'=> $this->makeClass($plugin, "Http/Requests", $name, $this->requestStub($plugin, $name)),
            'resource'=> $this->makeClass($plugin, "Http/Resources", $name, $this->resourceStub($plugin, $name)),
            'collection'=> $this->makeClass($plugin, "Http/Resources", $name, $this->collectionStub($plugin, $name)),
            'policy'=> $this->makeClass($plugin, "Policies", $name, $this->policyStub($plugin, $name)),
            'seeder'=> $this->makeClass($plugin, "../database/seeders", $name, $this->seederStub($plugin, $name)),
            'service'=> $this->makeClass($plugin, "Services", $name, $this->serviceStub($plugin, $name)),
            'job'=> $this->makeClass($plugin, "Jobs", $name, $this->jobStub($plugin, $name)),
            'event'=> $this->makeClass($plugin, "Events", $name, $this->eventStub($plugin, $name)),
            'listener'=> $this->makeClass($plugin, "Listeners", $name, $this->listenerStub($plugin, $name)),
            'middleware'=> $this->makeClass($plugin, "Http/Middleware", $name, $this->middlewareStub($plugin, $name)),
            default=> $this->failType($type),
        };
    }
    
    private function makeMigration(string $plugin, string $name): int {
        $path = "plugins/{$plugin}/database/migrations";
        
        if (!is_dir(base_path($path))) {
            mkdir(base_path($path), 0755, true);
        }
        
        Artisan::call("make:migration", [
            'name'=> $name,
            '--path'=> $path,
        ]);
        
        $this->line(Artisan::output());
        
        return self::SUCCESS;
    }
    
    private function makeClass(string $plugin, string $folder, string $name, string $content): int {
        $className = Str::studly(class_basename($name));
        
        $basePath = str_starts_with($folder, "../database")
            ? base_path("plugins/{$plugin}/database/seeders")
            : base_path("plugins/{$plugin}/src/{$folder}");
        
        $file = $basePath . DIRECTORY_SEPARATOR . $className . ".php";
        
        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }
        
        if (file_exists($file)) {
            $this->error("File already exists: {$file}");
            return self::FAILURE;
        }
        
        file_put_contents($file, $content);
        
        return self::SUCCESS;
    }
    
    private function namespace(string $plugin, string $folder): string {
        $folder = str_replace('/', '\\', $folder);
        
        return "Plugins\\{$plugin}\\{$folder}";
    }
    
    private function modelStub(string $plugin, string $name): string {
        $class = Str::studly($name);
        $table = Str::snake(Str::pluralStudly($name));
        
        return <<<PHP
<?php

namespace {$this->namespace($plugin, "Models")};

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([])]
class {$class} extends Model
{
    protected \$table = 'table';

    public function casts(): array {
        return [
            'created_at'=> 'datetime',
            'updated_at'=> 'datetime',
        ];
    }
}
PHP;
    }
    
    private function controllerStub(string $plugin, string $name): string
    {
        $class = Str::studly($name);
        
        return <<<PHP
<?php

namespace {$this->namespace($plugin, 'Http/Controllers')};

use App\Http\Controllers\Controller;
use App\Http\Resources\API\Internal\BaseResource;

class {$class} extends Controller
{
    public function index(): BaseResource
    {
        return BaseResource::make([])
            ->success()
            ->setCode(200)
            ->setMessage('OK');
    }
}

PHP;
    }
    
    private function requestStub(string $plugin, string $name): string
    {
        $class = Str::studly($name);
        
        return <<<PHP
<?php

namespace {$this->namespace($plugin, 'Http/Requests')};

use Illuminate\Foundation\Http\FormRequest;

class {$class} extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}

PHP;
    }
    
    private function resourceStub(string $plugin, string $name): string
    {
        $class = Str::studly($name);
        
        return <<<PHP
<?php

namespace {$this->namespace($plugin, 'Http/Resources')};

use App\Http\Resources\API\Internal\BaseResource;
use Illuminate\Http\Request;

class {$class} extends BaseResource
{
    public function toArray(Request \$request): array
    {
        return [
            'id' => \$this->id,
        ];
    }
}

PHP;
    }
    
    private function collectionStub(string $plugin, string $name): string
    {
        $class = Str::studly($name);
        
        return <<<PHP
<?php

namespace {$this->namespace($plugin, 'Http/Resources')};

use App\Http\Resources\API\Internal\BaseCollection;

class {$class} extends BaseCollection
{
    //
}

PHP;
    }
    
    private function policyStub(string $plugin, string $name): string
    {
        $class = Str::studly($name);
        
        return <<<PHP
<?php

namespace {$this->namespace($plugin, 'Policies')};

use App\Models\User;

class {$class}
{
    public function viewAny(User \$user): bool
    {
        return true;
    }

    public function view(User \$user, mixed \$model): bool
    {
        return true;
    }

    public function create(User \$user): bool
    {
        return true;
    }

    public function update(User \$user, mixed \$model): bool
    {
        return true;
    }

    public function delete(User \$user, mixed \$model): bool
    {
        return true;
    }
}

PHP;
    }
    
    private function seederStub(string $plugin, string $name): string
    {
        $class = Str::studly($name);
        
        return <<<PHP
<?php

namespace Plugins\\{$plugin}\\Database\\Seeders;

use Illuminate\Database\Seeder;

class {$class} extends Seeder
{
    public function run(): void
    {
        //
    }
}

PHP;
    }
    
    private function serviceStub(string $plugin, string $name): string
    {
        $class = Str::studly($name);
        
        return <<<PHP
<?php

namespace {$this->namespace($plugin, 'Services')};

class {$class}
{
    //
}

PHP;
    }
    
    private function jobStub(string $plugin, string $name): string
    {
        $class = Str::studly($name);
        
        return <<<PHP
<?php

namespace {$this->namespace($plugin, 'Jobs')};

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class {$class} implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        //
    }
}

PHP;
    }
    
    private function eventStub(string $plugin, string $name): string
    {
        $class = Str::studly($name);
        
        return <<<PHP
<?php

namespace {$this->namespace($plugin, 'Events')};

use Illuminate\Foundation\Events\Dispatchable;

class {$class}
{
    use Dispatchable;
}

PHP;
    }
    
    private function listenerStub(string $plugin, string $name): string
    {
        $class = Str::studly($name);
        
        return <<<PHP
<?php

namespace {$this->namespace($plugin, 'Listeners')};

class {$class}
{
    public function handle(object \$event): void
    {
        //
    }
}

PHP;
    }
    
    private function middlewareStub(string $plugin, string $name): string
    {
        $class = Str::studly($name);
        
        return <<<PHP
<?php

namespace {$this->namespace($plugin, 'Http/Middleware')};

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class {$class}
{
    public function handle(Request \$request, Closure \$next): Response
    {
        return \$next(\$request);
    }
}

PHP;
    }
    
    private function failType(string $type): int
    {
        $this->error("Unknown plugin file type: {$type}");
        
        return self::FAILURE;
    }
}
