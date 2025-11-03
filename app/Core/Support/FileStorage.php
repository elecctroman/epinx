<?php
declare(strict_types=1);

namespace App\Core\Support;

use RuntimeException;

class FileStorage
{
    public function __construct(private readonly string $disk)
    {
        if (!is_dir($this->disk)) {
            mkdir($this->disk, 0755, true);
        }
    }

    public function put(string $path, string $contents): string
    {
        $fullPath = $this->fullPath($path);
        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        if (file_put_contents($fullPath, $contents) === false) {
            throw new RuntimeException('Failed to write file to storage.');
        }

        return $fullPath;
    }

    public function delete(string $path): void
    {
        $fullPath = $this->fullPath($path);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    public function exists(string $path): bool
    {
        return file_exists($this->fullPath($path));
    }

    private function fullPath(string $path): string
    {
        return rtrim($this->disk, '/\\') . '/' . ltrim($path, '/\\');
    }
}
