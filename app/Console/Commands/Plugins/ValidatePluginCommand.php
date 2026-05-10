<?php

namespace App\Console\Commands\Plugins;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use JsonException;

#[Signature('plugins:validate {slug? : Plugin slug to validate}')]
#[Description('Validate plugin manifest, routes, migrations, code, and sandbox isolation without modifying anything')]
class ValidatePluginCommand extends Command
{
    private const VALID_HTTP_METHODS = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS',
        'HEAD',
    ];
    
    private array $errors = [];
    private array $warnings = [];
    
    public function handle(): int
    {
        $slug = $this->argument('slug');
        
        $this->validateSandboxIsolation();
        
        $pluginFiles = $slug
            ? [$this->findManifestBySlug($slug)]
            : glob(base_path('plugins/*/plugin.php'));
        
        $pluginFiles = array_filter($pluginFiles);
        
        if ($pluginFiles === []) {
            $this->error($slug ? "Plugin [{$slug}] not found." : 'No plugin manifest found.');
            
            return self::FAILURE;
        }
        
        foreach ($pluginFiles as $manifestFile) {
            if (str_contains($manifestFile, DIRECTORY_SEPARATOR.'Sandbox'.DIRECTORY_SEPARATOR)) {
                continue;
            }
            
            $this->validatePlugin($manifestFile);
        }
        
        foreach ($this->warnings as $warning) {
            $this->warn($warning);
        }
        
        foreach ($this->errors as $error) {
            $this->error($error);
        }
        
        if ($this->errors !== []) {
            $this->newLine();
            $this->error('Plugin validation failed.');
            
            return self::FAILURE;
        }
        
        $this->newLine();
        $this->info('Plugin validation passed.');
        
        return self::SUCCESS;
    }
    
    private function validatePlugin(string $manifestFile): void
    {
        $this->line("Validating {$manifestFile}");
        
        if (! is_file($manifestFile)) {
            $this->failCheck("Manifest does not exist: {$manifestFile}");
            
            return;
        }
        
        $manifest = require $manifestFile;
        
        if (! is_array($manifest)) {
            $this->failCheck("Manifest must return an array: {$manifestFile}");
            
            return;
        }
        
        $pluginPath = dirname($manifestFile);
        
        $slug = $this->validateSlug($manifest, $manifestFile);
        $namespace = $this->validateNamespace($manifest, $manifestFile);
        $provider = $this->validateProvider($manifest, $pluginPath, $namespace, $manifestFile);
        
        if ($slug === null) {
            return;
        }
        
        $this->validateRoutes(
            manifest: $manifest,
            pluginPath: $pluginPath,
            slug: $slug,
            namespace: $namespace,
            manifestFile: $manifestFile,
        );
        
        $this->validateMigrations(
            pluginPath: $pluginPath,
            slug: $slug,
            manifest: $manifest,
        );
        
        $this->validateCode(
            pluginPath: $pluginPath,
            slug: $slug,
            namespace: $namespace,
            provider: $provider,
        );
    }
    
    private function validateSlug(array $manifest, string $manifestFile): ?string
    {
        $slug = $manifest['slug'] ?? null;
        
        if (! is_string($slug) || $slug === '') {
            $this->failCheck("Missing plugin slug in {$manifestFile}");
            
            return null;
        }
        
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $this->failCheck("Invalid plugin slug [{$slug}]. Expected kebab-case lowercase.");
            
            return null;
        }
        
        return $slug;
    }
    
    private function validateNamespace(array $manifest, string $manifestFile): ?string
    {
        $namespace = $manifest['namespace'] ?? null;
        
        if (! is_string($namespace) || $namespace === '') {
            $this->warnCheck("Missing namespace in {$manifestFile}");
            
            return null;
        }
        
        if (! str_starts_with($namespace, 'Plugins\\')) {
            $this->failCheck("Plugin namespace [{$namespace}] must start with Plugins\\.");
        }
        
        return trim($namespace, '\\');
    }
    
    private function validateProvider(array $manifest, string $pluginPath, ?string $namespace, string $manifestFile): ?string
    {
        $provider = $manifest['provider'] ?? null;
        
        if (! is_string($provider) || $provider === '') {
            $this->failCheck("Missing provider in {$manifestFile}");
            
            return null;
        }
        
        if (! str_starts_with($provider, 'Plugins\\')) {
            $this->failCheck("Provider [{$provider}] must start with Plugins\\.");
        }
        
        if ($namespace && ! str_starts_with($provider, $namespace.'\\')) {
            $this->failCheck("Provider [{$provider}] must be inside plugin namespace [{$namespace}].");
        }
        
        $providerFile = $this->classToPluginFile($provider, $pluginPath, $namespace);
        
        if ($providerFile === null || ! is_file($providerFile)) {
            $this->failCheck("Provider file not found for [{$provider}].");
        }
        
        return $provider;
    }
    
    private function validateRoutes(
        array $manifest,
        string $pluginPath,
        string $slug,
        ?string $namespace,
        string $manifestFile,
    ): void {
        $routes = $manifest['routes'] ?? [];
        
        if (! is_array($routes)) {
            $this->failCheck("Manifest routes must be an array in {$manifestFile}");
            
            return;
        }
        
        if ($routes === []) {
            $this->warnCheck("Plugin [{$slug}] declares no routes.");
        }
        
        $routeFile = $pluginPath.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'api.php';
        
        if ($routes !== [] && ! is_file($routeFile)) {
            $this->failCheck("Plugin [{$slug}] declares routes but routes/api.php does not exist.");
            
            return;
        }
        
        $routeSource = is_file($routeFile) ? File::get($routeFile) : '';
        
        if ($routeSource !== '' && ! str_contains($routeSource, "plugins/{$slug}")) {
            $this->failCheck("Plugin route file must stay under prefix [plugins/{$slug}].");
        }
        
        $allowedCoreAliases = array_keys(config('plugins.core_middleware_aliases', []));
        
        foreach ($routes as $index => $route) {
            if (! is_array($route)) {
                $this->failCheck("Route #{$index} in plugin [{$slug}] must be an array.");
                
                continue;
            }
            
            $method = strtoupper((string) ($route['method'] ?? ''));
            
            if (! in_array($method, self::VALID_HTTP_METHODS, true)) {
                $this->failCheck("Route #{$index} in plugin [{$slug}] has invalid HTTP method [{$method}].");
            }
            
            $uri = trim((string) ($route['uri'] ?? ''), '/');
            
            if ($uri === '') {
                $this->failCheck("Route #{$index} in plugin [{$slug}] has an empty uri.");
                
                continue;
            }
            
            if (str_contains($uri, '..') || str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
                $this->failCheck("Route [{$method} {$uri}] in plugin [{$slug}] has an unsafe uri.");
            }
            
            if (str_starts_with($uri, 'plugins/') && ! str_starts_with($uri, "plugins/{$slug}")) {
                $this->failCheck("Route [{$method} {$uri}] exits allowed prefix [plugins/{$slug}].");
            }
            
            $sandboxUri = str_starts_with($uri, 'plugins/')
                ? Str::after($uri, "plugins/{$slug}/")
                : $uri;
            
            if ($routeSource !== '' && ! $this->routeExistsInSandboxRouteFile($routeSource, $sandboxUri)) {
                $this->failCheck("Route [{$method} {$uri}] is declared in plugin.php but not found in routes/api.php.");
            }
            
            $this->validateCoreMiddleware(
                middleware: $route['core_middleware'] ?? [],
                allowedCoreAliases: $allowedCoreAliases,
                namespace: $namespace,
                slug: $slug,
                routeLabel: "{$method} {$uri}",
            );
        }
    }
    
    private function validateCoreMiddleware(
        mixed $middleware,
        array $allowedCoreAliases,
        ?string $namespace,
        string $slug,
        string $routeLabel,
    ): void {
        if (! is_array($middleware)) {
            $this->failCheck("Route [{$routeLabel}] in plugin [{$slug}] has invalid core_middleware. Expected array.");
            
            return;
        }
        
        foreach ($middleware as $entry) {
            if (! is_string($entry) || $entry === '') {
                $this->failCheck("Route [{$routeLabel}] in plugin [{$slug}] has an invalid middleware entry.");
                
                continue;
            }
            
            if (in_array($entry, $allowedCoreAliases, true)) {
                continue;
            }
            
            if ($namespace && str_starts_with($entry, $namespace.'\\')) {
                continue;
            }
            
            $this->failCheck(
                "Middleware [{$entry}] on route [{$routeLabel}] is not an allowed core alias and is not inside plugin namespace."
            );
        }
    }
    
    private function validateMigrations(string $pluginPath, string $slug, array $manifest): void
    {
        $migrationPath = $pluginPath.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';
        
        if (! is_dir($migrationPath)) {
            return;
        }
        
        $tablePrefix = $manifest['table_prefix'] ?? 'plugin_'.str_replace('-', '_', $slug).'_';
        
        if (! is_string($tablePrefix) || $tablePrefix === '') {
            $this->failCheck("Plugin [{$slug}] has an invalid table_prefix.");
            
            return;
        }
        
        foreach (glob($migrationPath.DIRECTORY_SEPARATOR.'*.php') as $migrationFile) {
            $source = File::get($migrationFile);
            
            preg_match_all("/Schema::(?:create|table)\\(['\"]([^'\"]+)['\"]/", $source, $matches);
            
            foreach ($matches[1] ?? [] as $table) {
                if (! str_starts_with($table, $tablePrefix)) {
                    $this->failCheck(
                        "Migration [{$migrationFile}] uses table [{$table}] outside required prefix [{$tablePrefix}]."
                    );
                }
                
                if (str_starts_with($table, 'fg_')) {
                    $this->failCheck("Migration [{$migrationFile}] must not modify core table [{$table}].");
                }
            }
        }
    }
    
    private function validateCode(string $pluginPath, string $slug, ?string $namespace, ?string $provider): void
    {
        $files = collect(File::allFiles($pluginPath))
            ->filter(fn ($file) => in_array($file->getExtension(), ['php', 'json'], true));
        
        foreach ($files as $file) {
            $path = $file->getPathname();
            $source = File::get($path);
            
            if (str_contains($source, '../../../vendor/autoload.php') || str_contains($source, '../../vendor/autoload.php')) {
                $this->failCheck("Plugin [{$slug}] must not require a parent Composer autoload in [{$path}].");
            }
            
            if ($namespace && str_ends_with($path, '.php')) {
                $relative = Str::after($path, $pluginPath.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR);
                
                if ($relative !== $path && str_contains($source, 'namespace ')) {
                    if (! str_contains($source, 'namespace '.$namespace)) {
                        $this->warnCheck("PHP file [{$path}] may be outside expected namespace [{$namespace}].");
                    }
                }
            }
        }
        
        if ($provider && $namespace && ! str_starts_with($provider, $namespace.'\\')) {
            $this->failCheck("Provider [{$provider}] is outside namespace [{$namespace}].");
        }
    }
    
    private function validateSandboxIsolation(): void
    {
        $sandboxPath = base_path('plugins/Sandbox');
        
        $this->validateSandboxComposer($sandboxPath);
        $this->validateSandboxEntrypoint(
            $sandboxPath.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'index.php',
            "require __DIR__.'/../vendor/autoload.php'",
            'Sandbox public index must use plugins/Sandbox/vendor/autoload.php.',
        );
        $this->validateSandboxEntrypoint(
            $sandboxPath.DIRECTORY_SEPARATOR.'artisan.php',
            "require __DIR__.'/vendor/autoload.php'",
            'Sandbox artisan must use plugins/Sandbox/vendor/autoload.php.',
        );
        $this->validateRootComposer();
    }
    
    private function validateSandboxComposer(string $sandboxPath): void
    {
        $composerFile = $sandboxPath.DIRECTORY_SEPARATOR.'composer.json';
        
        if (! is_file($composerFile)) {
            $this->failCheck('plugins/Sandbox/composer.json does not exist.');
            
            return;
        }
        
        try {
            $composer = json_decode(File::get($composerFile), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->failCheck('plugins/Sandbox/composer.json is invalid JSON: '.$exception->getMessage());
            
            return;
        }
        
        $psr4 = $composer['autoload']['psr-4'] ?? [];
        
        if (! is_array($psr4)) {
            $this->failCheck('plugins/Sandbox/composer.json must define autoload.psr-4.');
            
            return;
        }
        
        $appMapping = $psr4['App\\'] ?? null;
        
        if ($appMapping === null) {
            $this->warnCheck('plugins/Sandbox/composer.json has no App\\ namespace. Laravel commands may fail namespace detection.');
            
            return;
        }
        
        $mappings = is_array($appMapping) ? $appMapping : [$appMapping];
        
        foreach ($mappings as $mapping) {
            $normalized = str_replace('\\', '/', (string) $mapping);
            
            if (str_contains($normalized, '../../app') || str_contains($normalized, '../app')) {
                $this->failCheck('plugins/Sandbox/composer.json must not map App\\ to the core app directory.');
            }
        }
    }
    
    private function validateSandboxEntrypoint(string $file, string $expectedRequire, string $message): void
    {
        if (! is_file($file)) {
            $this->failCheck("Missing sandbox entrypoint [{$file}].");
            
            return;
        }
        
        $source = File::get($file);
        
        if (str_contains($source, '../../../vendor/autoload.php') || str_contains($source, '../../vendor/autoload.php')) {
            $this->failCheck($message);
        }
        
        if (! str_contains($source, $expectedRequire)) {
            $this->warnCheck("Entrypoint [{$file}] should contain: {$expectedRequire}");
        }
    }
    
    private function validateRootComposer(): void
    {
        $composerFile = base_path('composer.json');
        
        if (! is_file($composerFile)) {
            $this->failCheck('Root composer.json does not exist.');
            
            return;
        }
        
        try {
            $composer = json_decode(File::get($composerFile), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->failCheck('Root composer.json is invalid JSON: '.$exception->getMessage());
            
            return;
        }
        
        $psr4 = $composer['autoload']['psr-4'] ?? [];
        
        if (array_key_exists('Plugins\\', $psr4)) {
            $this->failCheck('Root composer.json must not map Plugins\\ to plugins/.');
        }
    }
    
    private function routeExistsInSandboxRouteFile(string $source, string $uri): bool
    {
        $uri = trim($uri, '/');
        
        if ($uri === '') {
            return false;
        }
        
        $lastSegment = Str::afterLast($uri, '/');
        
        return str_contains($source, "'/{$uri}'")
            || str_contains($source, "\"/{$uri}\"")
            || str_contains($source, "'{$uri}'")
            || str_contains($source, "\"{$uri}\"")
            || str_contains($source, "'/{$lastSegment}'")
            || str_contains($source, "\"/{$lastSegment}\"");
    }
    
    private function classToPluginFile(string $class, string $pluginPath, ?string $namespace): ?string
    {
        if ($namespace === null || ! str_starts_with($class, $namespace.'\\')) {
            return null;
        }
        
        $relative = Str::after($class, $namespace.'\\');
        
        return $pluginPath
            .DIRECTORY_SEPARATOR.'src'
            .DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $relative)
            .'.php';
    }
    
    private function findManifestBySlug(string $slug): ?string
    {
        foreach (glob(base_path('plugins/*/plugin.php')) as $manifestFile) {
            if (str_contains($manifestFile, DIRECTORY_SEPARATOR.'Sandbox'.DIRECTORY_SEPARATOR)) {
                continue;
            }
            
            $manifest = require $manifestFile;
            
            if (($manifest['slug'] ?? null) === $slug) {
                return $manifestFile;
            }
        }
        
        return null;
    }
    
    private function failCheck(string $message): void
    {
        $this->errors[] = $message;
    }
    
    private function warnCheck(string $message): void
    {
        $this->warnings[] = $message;
    }
}
