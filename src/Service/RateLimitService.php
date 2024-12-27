<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use App\Exception\CalculatorException;

/**
 * RateLimitService class
 */
class RateLimitService
{
    /**
     * Constructs a new RateLimitService instance.
     *
     * @param CacheItemPoolInterface $cache Cache implementation for storing request counts
     * @param string $namespace Unique namespace for the rate limit (e.g., 'bin_lookup', 'exchange_rate')
     * @param int $limit Maximum number of requests allowed within the time window
     * @param int $window Time window in seconds (default: 60)
     * @param bool $enabled Whether rate limiting is enabled
     */
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly string $namespace,
        private readonly int $limit,
        private readonly int $window = 60, // window in seconds
        private readonly bool $enabled = true
    ) {}

    /**
     * Check if the current request is within rate limits.
     *
     * @param string $key Unique identifier for the rate limit check
     * @throws CalculatorException If rate limit is exceeded
     */
    public function checkLimit(string $key): void
    {
        if (!$this->enabled) {
            return;
        }

        $cacheKey = sprintf('%s_ratelimit_%s', $this->namespace, $key);
        $cacheItem = $this->cache->getItem($cacheKey);

        $requests = $cacheItem->get() ?? [];
        $now = time();

        // Remove old requests
        $requests = array_filter($requests, fn($timestamp) => $timestamp > $now - $this->window);

        if (count($requests) >= $this->limit) {
            throw new CalculatorException(
                sprintf('Rate limit exceeded. Maximum %d requests per %d seconds.', $this->limit, $this->window)
            );
        }

        $requests[] = $now;
        $cacheItem->set($requests);
        $cacheItem->expiresAfter($this->window);

        $this->cache->save($cacheItem);
    }

    /**
     * Check if rate limit is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
