<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class Env
{
    /**
     * @var array<string, mixed>
     */
    private static array $variables = [];

    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            $path = __DIR__ . '/../../env.sample.php';
        }

        $variables = require $path;

        if (!is_array($variables)) {
            throw new RuntimeException('.env.php must return an array of key/value pairs.');
        }

        self::$variables = $variables;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$variables[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::$variables[$key] = $value;
    }

    public static function all(): array
    {
        return self::$variables;
    }
}
