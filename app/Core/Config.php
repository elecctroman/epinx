<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class Config
{
    /**
     * @var array<string, mixed>
     */
    private static array $items = [];

    public static function load(string $directory): void
    {
        if (!is_dir($directory)) {
            throw new RuntimeException('Config directory not found.');
        }

        $files = glob(rtrim($directory, '/\\') . '/*.php');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $key = basename($file, '.php');
            $value = require $file;
            if (is_array($value)) {
                self::$items[$key] = $value;
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
