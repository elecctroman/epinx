<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Security\Csrf;
use App\Services\AuthService;

abstract class ControllerBase
{
    protected AuthService $auth;

    public function __construct(protected Container $container)
    {
        $this->auth = $container->get(AuthService::class);
    }

    protected function view(string $template, array $data = []): Response
    {
        return View::render($template, $data);
    }

    protected function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $url): Response
    {
        return new Response('', 302, ['Location' => $url]);
    }

    protected function guard(): void
    {
        if (!$this->auth->check()) {
            $this->abort(401, 'Unauthorized');
        }
    }

    protected function authorize(string ...$roles): void
    {
        foreach ($roles as $role) {
            if ($this->auth->hasRole($role)) {
                return;
            }
        }

        $this->abort(403, 'Forbidden');
    }

    protected function validateCsrf(Request $request): void
    {
        $token = $request->input('_token');
        if (!Csrf::validate($token)) {
            $this->abort(419, 'Invalid CSRF token.');
        }
    }

    protected function flash(string $key, string $message): void
    {
        $_SESSION['flash'] ??= [];
        $_SESSION['flash'][$key] = $message;
    }

    protected function getFlash(string $key): ?string
    {
        if (!isset($_SESSION['flash'][$key])) {
            return null;
        }

        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);

        return $message;
    }

    protected function abort(int $status, string $message): never
    {
        http_response_code($status);
        echo $message;
        exit;
    }
}
