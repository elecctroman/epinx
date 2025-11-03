<?php
declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\AuthService;
use Closure;

class AuthMiddleware
{
    private AuthService $auth;

    public function __construct(Container $container)
    {
        $this->auth = $container->get(AuthService::class);
    }

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->auth->check()) {
            return Response::json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
