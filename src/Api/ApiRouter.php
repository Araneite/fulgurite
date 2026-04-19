<?php
// =============================================================================
// ApiRouter.php — Mini routeur REST a base of patterns "/repos/{id}/snapshots"
// =============================================================================

class ApiRouter {

    /** @var array<int, array{method:string, pattern:string, regex:string, params:array, handler:callable}> */
    private array $routes = [];

    public function add(string $method, string $pattern, array|callable $handler): void {
        $params = [];
        $regex = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function ($m) use (&$params) {
            $params[] = $m[1];
            return '([^/]+)';
        }, $pattern);
        $regex = '#^' . $regex . '$#';
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'regex' => $regex,
            'params' => $params,
            'handler' => $handler,
        ];
    }

    public function get(string $pattern, callable $handler): void    { $this->add('GET', $pattern, $handler); }
    public function post(string $pattern, callable $handler): void   { $this->add('POST', $pattern, $handler); }
    public function put(string $pattern, callable $handler): void    { $this->add('PUT', $pattern, $handler); }
    public function patch(string $pattern, callable $handler): void  { $this->add('PATCH', $pattern, $handler); }
    public function delete(string $pattern, callable $handler): void { $this->add('DELETE', $pattern, $handler); }

    public function dispatch(string $method, string $path): void {
        $method = strtoupper($method);
        $methodAllowed = false;

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                if ($route['method'] !== $method) {
                    $methodAllowed = true;
                    continue;
                }
                array_shift($matches);
                $args = array_combine($route['params'], $matches) ?: [];
                ($route['handler'])($args);
                return;
            }
        }

        if ($methodAllowed) {
            ApiResponse::error(405, 'method_not_allowed', "Methode $method non autorisee pour $path");
        }
        ApiResponse::error(404, 'not_found', "Endpoint inconnu : $method $path");
    }
}
