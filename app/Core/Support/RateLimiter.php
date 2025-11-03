<?php
declare(strict_types=1);

namespace App\Core\Support;

use App\Core\Env;

class RateLimiter
{
    private Cache $cache;

    public function __construct(?Cache $cache = null)
    {
        $this->cache = $cache ?? new Cache();
    }

    public function tooManyAttempts(string $key): bool
    {
        $state = $this->getState($key);
        $limit = (int) Env::get('RATE_LIMIT_MAX_ATTEMPTS', 5);

        if ($state['expires_at'] !== null && $state['expires_at'] < time()) {
            $this->clear($key);
            return false;
        }

        if ($state['blocked_until'] !== null) {
            if ($state['blocked_until'] <= time()) {
                $this->clear($key);
                return false;
            }

            return true;
        }

        return $state['attempts'] >= $limit;
    }

    public function hit(string $key): void
    {
        $state = $this->getState($key);
        $state['attempts']++;

        $decay = (int) Env::get('RATE_LIMIT_DECAY_SECONDS', 60);
        $state['expires_at'] = time() + $decay;

        $limit = (int) Env::get('RATE_LIMIT_MAX_ATTEMPTS', 5);
        if ($state['attempts'] >= $limit) {
            $overLimit = max(0, $state['attempts'] - $limit);
            $baseDelay = (int) Env::get('RATE_LIMIT_BASE_DELAY', 30);
            $state['blocked_until'] = time() + max($baseDelay, $baseDelay * ($overLimit + 1));
        }

        $this->cache->put($this->cacheKey($key), $state);
    }

    public function clear(string $key): void
    {
        $this->cache->forget($this->cacheKey($key));
    }

    /**
     * @return array{attempts:int, expires_at:int|null, blocked_until:int|null}
     */
    private function getState(string $key): array
    {
        $default = ['attempts' => 0, 'expires_at' => null, 'blocked_until' => null];
        $state = $this->cache->get($this->cacheKey($key), $default);
        if (!is_array($state)) {
            return $default;
        }

        return array_merge($default, $state);
    }

    private function cacheKey(string $key): string
    {
        return 'rate_' . $key;
    }
}
