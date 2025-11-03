<?php
declare(strict_types=1);

namespace App\Core;

use Closure;
use InvalidArgumentException;

class Container
{
    /**
     * @var array<class-string, Closure|object>
     */
    private array $bindings = [];

    /**
     * @param class-string $id
     * @param Closure|object $concrete
     */
    public function set(string $id, Closure|object $concrete): void
    {
        $this->bindings[$id] = $concrete;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->bindings);
    }

    /**
     * @template T
     * @param class-string<T> $id
     * @return T
     */
    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new InvalidArgumentException("Service {$id} is not bound in the container.");
        }

        $binding = $this->bindings[$id];

        if ($binding instanceof Closure) {
            $instance = $binding($this);
            $this->bindings[$id] = $instance;

            return $instance;
        }

        return $binding;
    }
}
