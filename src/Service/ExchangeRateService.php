<?php

declare(strict_types=1);

namespace App\Service;

use App\Interface\ExchangeRateInterface;
use App\Exception\CalculatorException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\RateLimitService;

/**
 * ExchangeRateService class
 */
class ExchangeRateService implements ExchangeRateInterface
{
    /**
     * @param HttpClientInterface $client
     * @param CacheItemPoolInterface $cache
     * @param RateLimitService $rateLimiter
     * @param string $baseUrl
     * @param string $apiKey
     * @param int $timeout
     * @param int $cacheTtl
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly CacheItemPoolInterface $cache,
        private readonly RateLimitService $rateLimiter,
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly int $timeout = 5,
        private readonly int $cacheTtl = 3600
    ) {}

    /**
     * Get exchange rate for a currency
     *
     * @param string $currency Currency code (ISO 4217)
     * @return float Exchange rate relative to EUR
     * @throws CalculatorException If rate cannot be retrieved
     */
    public function getRate(string $currency): float
    {
        if ($currency === 'EUR') {
            return 1.0;
        }

        try {
            $this->rateLimiter->checkLimit('exchange_rate');
            return $this->getExchangeRate($currency);
        } catch (\Exception $e) {
            throw new CalculatorException('Failed to get exchange rate: ' . $e->getMessage());
        }
    }

    /**
     * Convert amount from given currency to EUR
     *
     * @param float $amount Amount to convert
     * @param string $currency Source currency code (ISO 4217)
     * @return float Amount in EUR
     * @throws CalculatorException If conversion fails
     */
    public function convertToEur(float $amount, string $currency): float
    {
        if ($currency === 'EUR') {
            return $amount;
        }

        return $amount / $this->getRate($currency);
    }

    /**
     * Get exchange rate from API
     *
     * @param string $currency Currency code (ISO 4217)
     * @return float Exchange rate relative to EUR
     * @throws CalculatorException If rate cannot be retrieved
     */
    private function getExchangeRate(string $currency): float
    {
        $cacheKey = 'exchange_rate_' . $currency;
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $response = $this->client->request('GET', $this->baseUrl, [
            'query' => [
                'apikey' => $this->apiKey,
                'symbols' => $currency
            ],
            'timeout' => $this->timeout
        ]);

        $data = $response->toArray();

        if (!isset($data['rates'][$currency])) {
            throw new CalculatorException("Exchange rate not found for currency: $currency");
        }

        $rate = (float) $data['rates'][$currency];

        $cacheItem->set($rate);
        $cacheItem->expiresAfter($this->cacheTtl);
        $this->cache->save($cacheItem);

        return $rate;
    }
}
