<?php

declare(strict_types=1);

namespace App\Service;

use App\Interface\BinLookupInterface;
use App\Exception\CalculatorException;
use App\Helper\CountryHelper;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service for looking up BIN (Bank Identification Number) data.
 */
class BinLookupService implements BinLookupInterface
{
    /**
     * @var bool
     */
    private bool $cacheEnabled;

    /**
     * Constructs a new BinLookupService instance.
     *
     * @param HttpClientInterface $client The HTTP client for making requests
     * @param CacheItemPoolInterface $cache The cache item pool for caching lookups
     * @param CountryHelper $countryHelper The country helper for checking EU membership
     * @param RateLimitService $rateLimiter The rate limiter for checking API limits
     * @param string $baseUrl The base URL for the BIN lookup API
     * @param int $timeout The timeout for the API request
     * @param int $cacheTtl The cache TTL for the BIN lookup results
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly CacheItemPoolInterface $cache,
        private readonly CountryHelper $countryHelper,
        private readonly RateLimitService $rateLimiter,
        private readonly string $baseUrl,
        private readonly int $timeout = 5,
        private readonly int $cacheTtl = 3600
    ) {
        $this->cacheEnabled = filter_var($_ENV['CACHE_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Lookup country code by BIN
     *
     * @param string $bin Bank Identification Number
     * @return string ISO 3166-1 alpha-2 country code
     * @throws CalculatorException
     */
    public function lookup(string $bin): string
    {
        if (!$this->cacheEnabled) {
            return $this->fetchFromApi($bin);
        }

        $cacheKey = 'bin_' . $bin;
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $country = $this->fetchFromApi($bin);

        $cacheItem->set($country)
            ->expiresAfter($this->cacheTtl);
        $this->cache->save($cacheItem);

        return $country;
    }

    /**
     * Fetch country code from API
     *
     * @param string $bin Bank Identification Number
     * @return string ISO 3166-1 alpha-2 country code
     * @throws CalculatorException
     */
    private function fetchFromApi(string $bin): string
    {
        try {
            $this->rateLimiter->checkLimit('bin_lookup');

            $response = $this->client->request('GET', "{$this->baseUrl}/{$bin}", [
                'timeout' => $this->timeout,
            ]);

            $data = $response->toArray();

            if (!isset($data['country'])) {
                throw new CalculatorException("Invalid response format for BIN: $bin");
            }

            if (!isset($data['country']['alpha2'])) {
                throw new CalculatorException("Invalid country data received for BIN: $bin");
            }

            return $data['country']['alpha2'];
        } catch (CalculatorException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new CalculatorException('Failed to lookup BIN: ' . $e->getMessage());
        }
    }

    /**
     * Check if BIN belongs to EU country
     *
     * @param string $bin Bank Identification Number
     * @return bool
     */
    public function isEuCountry(string $bin): bool
    {
        $country = $this->lookup($bin);
        return $this->countryHelper->isEu($country);
    }

    /**
     * Set cache enabled status
     *
     * @param bool $enabled
     */
    public function setCacheEnabled(bool $enabled): void
    {
        $this->cacheEnabled = $enabled;
    }
}
