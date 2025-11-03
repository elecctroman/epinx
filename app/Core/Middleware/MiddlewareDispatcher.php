<?php
declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use Closure;

class MiddlewareDispatcher
{
    /**
     * @param array<int, string> $middleware
     */
    public function __construct(
        private readonly Container $container,
        private readonly array $middleware = []
    ) {
    }

    /**
     * @param Closure(Request): Response $handler
     */
    public function handle(Request $request, Closure $handler): Response
    {
        $middlewareStack = array_reverse($this->middleware);

        $next = $handler;
        foreach ($middlewareStack as $middleware) {
            $next = $this->wrapMiddleware($middleware, $next);
        }

        return $next($request);
    }

    /**
     * @param Closure(Request): Response $next
     * @return Closure(Request): Response
     */
    private function wrapMiddleware(string $middleware, Closure $next): Closure
    {
        $args = null;
        if (str_contains($middleware, ':')) {
            [$middleware, $args] = explode(':', $middleware, 2);
        }

        $middlewareClass = $this->resolveMiddlewareClass($middleware);

        return function (Request $request) use ($middlewareClass, $args, $next): Response {
            $instance = $this->container->has($middlewareClass)
                ? $this->container->get($middlewareClass)
                : new $middlewareClass($this->container);

            if ($args !== null) {
                $request->setAttribute('middleware_args', $args);
            }

            return $instance->handle($request, $next);
        };
    }

    private function resolveMiddlewareClass(string $middleware): string
    {
        return 'App\\Core\\Middleware\\' . ucfirst($middleware) . 'Middleware';
    }
}
