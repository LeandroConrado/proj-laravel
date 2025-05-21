<?php

namespace App\Core;

class Router
{
    private static array $routes = [];
    private static array $groupStack = [];

    public static function group(array $attributes, callable $callback): void
    {
        self::$groupStack[] = $attributes;
        call_user_func($callback);
        array_pop(self::$groupStack);
    }

    private static function applyGroupContext(string $uri, array $options = []): array
    {
        $prefix = '';
        $middleware = $options['middleware'] ?? null;
        $role = $options['role'] ?? null;
        $module = $options['module'] ?? null;

        foreach (self::$groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= rtrim($group['prefix'], '/');
            }
            if (isset($group['middleware'])) {
                $middleware = $group['middleware'];
            }
            if (isset($group['role'])) {
                $role = $group['role'];
            }
            if (isset($group['module'])) {
                $module = $group['module'];
            }
        }

        $uri = $prefix . '/' . ltrim($uri, '/');
        return [$uri, ['middleware' => $middleware, 'role' => $role, 'module' => $module]];
    }

    public static function get(string $uri, $controller, array $options = []): void
    {
        [$uri, $options] = self::applyGroupContext($uri, $options);
        self::$routes['GET'][] = [
            'uri' => $uri,
            'controller' => $controller,
            'middleware' => $options['middleware'] ?? null,
            'role' => $options['role'] ?? null,
            'module' => $options['module'] ?? null
        ];
    }

    public static function post(string $uri, $controller, array $options = []): void
    {
        [$uri, $options] = self::applyGroupContext($uri, $options);
        self::$routes['POST'][] = [
            'uri' => $uri,
            'controller' => $controller,
            'middleware' => $options['middleware'] ?? null,
            'role' => $options['role'] ?? null,
            'module' => $options['module'] ?? null
        ];
    }

    public static function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $rawPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = is_string($rawPath) ? $rawPath : '/'; // ✅ Corrigido aqui

        $routes = self::$routes[$method] ?? [];

        foreach ($routes as $route) {
            $pattern = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([a-zA-Z0-9_-]+)', $route['uri']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);

                if (!empty($route['middleware']) && $route['middleware'] === 'auth') {
                    \App\Core\Middleware\AuthMiddleware::handle();
                }

                if (!empty($route['role'])) {
                    \App\Core\Middleware\RoleMiddleware::handle($route['role']);
                }

                if (!empty($route['module'])) {
                    \App\Core\Middleware\ModuleMiddleware::handle($route['module']);
                }

                $controller = $route['controller'];

                if (is_array($controller) && count($controller) === 2) {
                    [$class, $methodName] = $controller;
                } elseif (is_string($controller) && strpos($controller, '@') !== false) {
                    [$class, $methodName] = explode('@', $controller);
                    $class = 'App\\Http\\Controllers\\' . str_replace('/', '\\', $class);
                } else {
                    http_response_code(500);
                    echo "Formato de controller inválido.";
                    return;
                }

                if (!class_exists($class)) {
                    http_response_code(500);
                    echo "Controller {$class} não encontrado.";
                    return;
                }

                $instance = new $class(); // instanciamento direto
                call_user_func_array([$instance, $methodName], $matches);
                return;
            }
        }

        http_response_code(404);
        echo "Página não encontrada: {$path}";
    }
}
