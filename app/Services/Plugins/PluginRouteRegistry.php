<?php

namespace App\Services\Plugins;

use App\Models\Plugin;
use App\Models\PluginRoute;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PluginRouteRegistry
{
	public function matchOrFail(string $plugin, string $method, string $path): PluginRoute {
        $pluginModel = Plugin::query()
            ->where('slug', $plugin)
            ->where('enabled', true)
            ->first();
        
        if (!$pluginModel) throw new NotFoundHttpException('Plugin not found');
        
        $routes = PluginRoute::query()
            ->where('plugin_id', $pluginModel->id)
            ->where('method', strtoupper($method))
            ->where('enabled', true)
            ->get();
        
        foreach($routes as $route) {
            if ($this->matches($route->uri, $path)) {
                return $route;
            }
        }
        
        throw new NotFoundHttpException('Route not found');
    }
    
    private function matches(string $pattern, string $path): bool {
        $pattern = trim($pattern, "/");
        $path = trim($path, "/");
        
        if ($pattern === $path) return true;
        
        $regex = preg_replace('/\{[^\/]+\}/', '[^/]+', preg_quote($pattern, '#'));
        
        return (bool) preg_match('#^' . $regex . '$#', $path);
    }
}
