<?php

namespace App\Http\Controllers;

use App\Services\Plugins\PluginMiddlewareResolver;
use App\Services\Plugins\PluginRouteRegistry;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class PluginProxyController extends Controller
{
    public function __construct(
        private readonly PluginRouteRegistry $routes,
        private readonly PluginMiddlewareResolver $middlewareResolver,
    ) {
    }
    
    public function __invoke(Request $request, string $plugin, ?string $path = null): Response
    {
        $pluginRoute = $this->routes->matchOrFail(
            plugin: $plugin,
            method: $request->method(),
            path: $path ?? '',
        );
        
        $middlewares = $this->middlewareResolver->resolve($pluginRoute->core_middleware ?? []);
        
        return app(Pipeline::class)
            ->send($request)
            ->through($middlewares)
            ->then(fn (Request $request) => $this->forwardToSandbox($request, $plugin, $path ?? ''));
    }
    
    private function forwardToSandbox(Request $request, string $plugin, string $path): Response
    {
        $url = rtrim(config('plugins.sandbox_url'), '/').'/plugins/'.$plugin;
        
        if ($path !== '') {
            $url .= '/'.$path;
        }
        
        $headers = collect($request->headers->all())
            ->except(['host', 'content-length'])
            ->map(fn (array $values) => implode(',', $values))
            ->all();
        
        $headers['X-Fulgurite-Sandbox-Secret'] = (string) config('plugins.sandbox_secret');
        $headers['X-Fulgurite-User-Id'] = (string) optional($request->user())->id;
        
        try {
            $response = Http::withHeaders($headers)
                ->timeout(config('plugins.timeout'))
                ->send($request->method(), $url, [
                    'query' => $request->query(),
                    'body' => $request->getContent(),
                ]);
        } catch (ConnectionException) {
            abort(502, 'Plugin sandbox is unreachable.');
        }
        
        return response($response->body(), $response->status())
            ->withHeaders(
                collect($response->headers())
                    ->except(['transfer-encoding', 'connection'])
                    ->map(fn (array $values) => implode(',', $values))
                    ->all()
            );
    }
}
