<?php
declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\AuthService;
use Closure;

class RoleMiddleware
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
        $required = (string) $request->getAttribute('middleware_args', '');
        $requiredRoles = array_filter(array_map('trim', explode(',', $required)));

        foreach ($requiredRoles as $role) {
            if ($this->auth->hasRole($role)) {
                $request->setAttribute('middleware_args', null);
                return $next($request);
            }
        }

        return Response::json(['message' => 'Forbidden'], 403);
    }
}
