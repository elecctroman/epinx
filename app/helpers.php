<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\Env;
use App\Core\Security\Csrf;

if (!function_exists('escape')) {
    function escape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $token = Csrf::token();
        return '<input type="hidden" name="_token" value="' . escape($token) . '">';
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path): string
    {
        $base = (string) Env::get('APP_URL', '');
        if ($base === '') {
            return '/' . ltrim($path, '/');
        }

        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('route')) {
    /**
     * @param array<string, bool|float|int|string> $parameters
     */
    function route(string $name, array $parameters = []): string
    {
        $routes = Config::get('routes', []);
        foreach ($routes as $methodRoutes) {
            foreach ($methodRoutes as $uri => $action) {
                if (!is_array($action)) {
                    continue;
                }

                if (($action['name'] ?? null) !== $name) {
                    continue;
                }

                $resolved = (string) $uri;
                foreach ($parameters as $key => $value) {
                    $resolved = str_replace('{' . $key . '}', rawurlencode((string) $value), $resolved);
                }

                return $resolved;
            }
        }

        return '#';
    }
}
