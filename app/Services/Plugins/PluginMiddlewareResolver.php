<?php

namespace App\Services\Plugins;

use App\Models\Plugin;
use Illuminate\Routing\MiddlewareNameResolver;
use InvalidArgumentException;

class PluginMiddlewareResolver
{
	public function resolve(array $aliases): array {
        $resolved = [];
        $availableAliases = config('plugins.core_middleware_aliases');
        
        foreach ($aliases as $alias) {
            if (!array_key_exists($alias, $availableAliases)) {
                throw new InvalidArgumentException("Plugin middleware alias [{$alias}] is not allowed.");
            }
            
            foreach ($availableAliases[$alias] as $middleware) {
                $resolved[] = MiddlewareNameResolver::resolve(
                    $middleware,
                    app('router')->getMiddleware(),
                    app('router')->getMiddlewareGroups()
                );
            }
        }
        
        return array_values(array_filter($resolved));
    }
}
