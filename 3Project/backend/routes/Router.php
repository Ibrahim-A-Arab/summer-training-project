<?php

declare(strict_types=1);

namespace App\Routes;

class Router{

    private static array $routes = [];

    public static function get(string $path, array $handler): void
    {
        self::$routes[] = [
            'method' => 'GET',
            'path' => $path,
            'handler' => $handler,
        ];
    }
    public static function post(string $path, array $handler): void
    {
        self::$routes[] = [
            'method' => 'POST',
            'path' => $path,
            'handler' => $handler,
        ];
    }

    public static function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $basePath = '/newSummerTraining/3Project/backend';

        if (str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }


        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            // Convert "/api/questions/{id}" into a matching pattern.
            $pattern = preg_replace(
                '#\{[^/]+\}#',
                '([^/]+)',
                $route['path']
            );

            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);

                [$controllerName, $methodName] = $route['handler'];

                $controllerClass = 'App\\Controllers\\' . $controllerName;
                $controller = new $controllerClass();

                // Captured URL values are passed to the controller method.
                $result = $controller->$methodName(...$matches);

                if ($result instanceof \App\Utils\ViewModel) {
                    $result->render();
                }

                return;
            }
        }

        http_response_code(404);
        echo '404 - Route not found';
    }


}