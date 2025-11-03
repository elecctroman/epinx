<?php
declare(strict_types=1);

namespace App\Core\Support;

use App\Core\Env;

class Cache
{
    private string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? (string) Env::get('CACHE_PATH', __DIR__ . '/../../../cache');
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    public function remember(string $key, int $seconds, callable $callback): mixed
    {
        $file = $this->filePath($key);
        if (file_exists($file) && (filemtime($file) + $seconds) > time()) {
            return unserialize((string) file_get_contents($file));
        }

        $value = $callback();
        file_put_contents($file, serialize($value));

        return $value;
    }

    public function put(string $key, mixed $value): void
    {
        file_put_contents($this->filePath($key), serialize($value));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->filePath($key);
        if (!file_exists($file)) {
            return $default;
        }

        return unserialize((string) file_get_contents($file));
    }

    public function forget(string $key): void
    {
        $file = $this->filePath($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    private function filePath(string $key): string
    {
        return rtrim($this->path, '/\\') . '/' . md5($key) . '.cache.php';
    }
}
