<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewareDispatcher;
use App\Core\Env;
use InvalidArgumentException;
use Throwable;

class Router
{
    /**
     * @var array<string, array<int, array{
     *     uri: string,
     *     pattern: string,
     *     parameters: array<int, string>,
     *     uses: string,
     *     middleware: array<int, string>,
     *     name: string|null
     * }>>
     */
    private array $routes = [];

    public function __construct(
        private readonly Container $container
    ) {
    }

    public function loadRoutes(array $routes): void
    {
        foreach ($routes as $method => $definitions) {
            foreach ($definitions as $uri => $handler) {
                $uses = $handler;
                $middleware = [];
                $name = null;

                if (is_array($handler)) {
                    $uses = $handler['uses'] ?? null;
                    $middleware = $handler['middleware'] ?? [];
                    $name = $handler['name'] ?? null;
                }

                if (!is_string($uses)) {
                    throw new InvalidArgumentException('Route handler must be a controller@method string.');
                }

                $this->addRoute((string) $method, $uri, $uses, (array) $middleware, $name);
            }
        }
    }

    public function addRoute(string $method, string $uri, string $action, array $middleware = [], ?string $name = null): void
    {
        $method = strtoupper($method);
        $compiled = $this->compileRoute($uri);

        $this->routes[$method][] = [
            'uri' => $uri,
            'pattern' => $compiled['pattern'],
            'parameters' => $compiled['parameters'],
            'uses' => $action,
            'middleware' => $middleware,
            'name' => $name,
        ];
    }

    public function dispatch(string $method, string $uri): Response
    {
        $method = strtoupper($method);
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';

        $route = $this->matchRoute($method, $uri);
        if ($route === null) {
            return Response::json(['message' => 'Not Found'], 404);
        }

        [$controllerName, $controllerMethod] = explode('@', $route['uses']);
        $controllerClass = $this->resolveControllerClass($controllerName);

        if (!class_exists($controllerClass)) {
            return Response::json(['message' => 'Controller not found'], 500);
        }

        $controller = $this->resolveController($controllerClass);

        if (!method_exists($controller, $controllerMethod)) {
            return Response::json(['message' => 'Method not found'], 500);
        }

        $request = Request::fromGlobals();
        foreach ($route['parameters'] as $parameter => $value) {
            $request->setAttribute($parameter, $value);
        }

        $middlewareDispatcher = new MiddlewareDispatcher($this->container, $route['middleware']);
        $handler = function (Request $request) use ($controller, $controllerMethod): Response {
            try {
                return $controller->{$controllerMethod}($request);
            } catch (Throwable $throwable) {
                return Response::json([
                    'message' => 'Server Error',
                    'error' => Env::get('APP_DEBUG') ? $throwable->getMessage() : 'An error occurred.',
                ], 500);
            }
        };

        return $middlewareDispatcher->handle($request, $handler);
    }

    private function resolveControllerClass(string $controller): string
    {
        return 'App\\Controllers\\' . $controller;
    }

    private function resolveController(string $controllerClass): object
    {
        if ($this->container->has($controllerClass)) {
            return $this->container->get($controllerClass);
        }

        return new $controllerClass($this->container);
    }

    /**
     * @return array{pattern: string, parameters: array<int, string>}
     */
    private function compileRoute(string $uri): array
    {
        $parameterNames = [];
        $escaped = preg_quote($uri, '#');
        $pattern = preg_replace_callback('/\\\{([a-zA-Z_][a-zA-Z0-9_-]*)\\\}/', static function (array $matches) use (&$parameterNames) {
            $parameterNames[] = $matches[1];

            return '(?P<' . $matches[1] . '>[^/]+)';
        }, $escaped);

        $pattern = '#^' . ($pattern ?? $escaped) . '$#u';

        return [
            'pattern' => $pattern,
            'parameters' => $parameterNames,
        ];
    }

    /**
     * @return array{
     *     uri: string,
     *     pattern: string,
     *     parameters: array<int, string>,
     *     uses: string,
     *     middleware: array<int, string>,
     *     name: string|null
     * }|null
     */
    private function matchRoute(string $method, string $uri): ?array
    {
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route) {
            if (!preg_match($route['pattern'], $uri, $matches)) {
                continue;
            }

            $parameters = [];
            foreach ($route['parameters'] as $parameter) {
                if (isset($matches[$parameter])) {
                    $parameters[$parameter] = $matches[$parameter];
                }
            }

            $route['parameters'] = $parameters;

            return $route;
        }

        return null;
    }
}
