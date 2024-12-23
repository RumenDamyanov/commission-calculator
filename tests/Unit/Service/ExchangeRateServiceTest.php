<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Service\ExchangeRateService;
use App\Service\RateLimitService;
use App\Exception\CalculatorException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * ExchangeRateServiceTest class
 */
class ExchangeRateServiceTest extends TestCase
{
    private ExchangeRateService $service;
    private MockObject&HttpClientInterface $httpClient;
    private MockObject&CacheItemPoolInterface $cache;
    private MockObject&CacheItemInterface $cacheItem;
    private MockObject&RateLimitService $rateLimiter;

    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheItem = $this->createMock(CacheItemInterface::class);
        $this->rateLimiter = $this->createMock(RateLimitService::class);

        $this->service = new ExchangeRateService(
            $this->httpClient,
            $this->cache,
            $this->rateLimiter,
            'https://api.example.com',
            'test_api_key',
            5,
            3600
        );
    }

    /**
     * Test convertToEur with EUR currency
     */
    public function testConvertToEurWithEurCurrency(): void
    {
        // Rate limiter should not be called for EUR
        $this->rateLimiter->expects($this->never())
            ->method('checkLimit');

        $result = $this->service->convertToEur(100.00, 'EUR');
        $this->assertEquals(100.00, $result);
    }

    /**
     * Test convertToEur with cached rate
     */
    public function testConvertToEurWithCachedRate(): void
    {
        $this->cache->method('getItem')
            ->with('exchange_rate_USD')
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('isHit')
            ->willReturn(true);

        $this->cacheItem->method('get')
            ->willReturn(1.1);

        $this->rateLimiter->expects($this->once())
            ->method('checkLimit')
            ->with('exchange_rate');

        $result = $this->service->convertToEur(110.00, 'USD');
        // Floating point precision fix.
        $this->assertEqualsWithDelta(100.00, $result, 0.00001, 'Currency conversion should be within 0.00001');
    }

    /**
     * Test getRateFromApi
     */
    public function testGetRateFromApi(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $this->cache->method('getItem')
            ->with('exchange_rate_USD')
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('isHit')
            ->willReturn(false);

        $this->rateLimiter->expects($this->once())
            ->method('checkLimit')
            ->with('exchange_rate');

        $this->httpClient->method('request')
            ->with(
                'GET',
                'https://api.example.com',
                $this->callback(function ($options) {
                    return isset($options['query']['symbols'])
                        && $options['query']['symbols'] === 'USD'
                        && isset($options['query']['apikey'])
                        && $options['query']['apikey'] === 'test_api_key';
                })
            )
            ->willReturn($response);

        $response->method('toArray')
            ->willReturn(['rates' => ['USD' => 1.1]]);

        $this->cacheItem->expects($this->once())
            ->method('set')
            ->with(1.1)
            ->willReturn($this->cacheItem);

        $rate = $this->service->getRate('USD');
        $this->assertEquals(1.1, $rate);
    }

    /**
     * Test getRateThrowsExceptionOnApiError
     */
    public function testGetRateThrowsExceptionOnApiError(): void
    {
        $this->cache->method('getItem')
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('isHit')
            ->willReturn(false);

        $this->rateLimiter->expects($this->once())
            ->method('checkLimit')
            ->with('exchange_rate');

        $this->httpClient->method('request')
            ->willThrowException(new \Exception('API Error'));

        $this->expectException(CalculatorException::class);
        $this->expectExceptionMessage('Failed to get exchange rate: API Error');

        $this->service->getRate('USD');
    }

    /**
     * Test getRateThrowsExceptionOnMissingRate
     */
    public function testGetRateThrowsExceptionOnMissingRate(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $this->cache->method('getItem')
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('isHit')
            ->willReturn(false);

        $this->rateLimiter->expects($this->once())
            ->method('checkLimit')
            ->with('exchange_rate');

        $this->httpClient->method('request')
            ->willReturn($response);

        $response->method('toArray')
            ->willReturn(['rates' => []]);

        $this->expectException(CalculatorException::class);
        $this->expectExceptionMessage('Exchange rate not found for currency: USD');

        $this->service->getRate('USD');
    }

    /**
     * Test getRateReturnsOneForEur
     */
    public function testGetRateReturnsOneForEur(): void
    {
        $this->rateLimiter->expects($this->never())
            ->method('checkLimit');

        $rate = $this->service->getRate('EUR');
        $this->assertEquals(1.0, $rate);
    }

    /**
     * Test getRateWithCacheSaveFailure
     */
    public function testGetRateWithCacheSaveFailure(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $this->cache->method('getItem')
            ->with('exchange_rate_USD')
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('isHit')
            ->willReturn(false);

        $this->rateLimiter->expects($this->once())
            ->method('checkLimit')
            ->with('exchange_rate');

        $this->httpClient->method('request')
            ->willReturn($response);

        $response->method('toArray')
            ->willReturn(['rates' => ['USD' => 1.1]]);

        $this->cacheItem->method('set')
            ->with(1.1)
            ->willReturn($this->cacheItem);

        $this->cache->method('save')
            ->willReturn(false); // Simulate cache save failure

        $rate = $this->service->getRate('USD');
        $this->assertEquals(1.1, $rate);
    }

    /**
     * Test convertToEurWithApiError
     */
    public function testConvertToEurWithApiError(): void
    {
        $this->cache->method('getItem')
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('isHit')
            ->willReturn(false);

        $this->rateLimiter->expects($this->once())
            ->method('checkLimit')
            ->with('exchange_rate');

        $this->httpClient->method('request')
            ->willThrowException(new \Exception('API Error'));

        $this->expectException(CalculatorException::class);
        $this->expectExceptionMessage('Failed to get exchange rate: API Error');

        $this->service->convertToEur(100.00, 'USD');
    }

    /**
     * Test getRateWithInvalidResponse
     */
    public function testGetRateWithInvalidResponse(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $this->cache->method('getItem')
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('isHit')
            ->willReturn(false);

        $this->rateLimiter->expects($this->once())
            ->method('checkLimit')
            ->with('exchange_rate');

        $this->httpClient->method('request')
            ->willReturn($response);

        $response->method('toArray')
            ->willReturn(['invalid' => 'response']);

        $this->expectException(CalculatorException::class);
        $this->expectExceptionMessage('Exchange rate not found for currency: USD');

        $this->service->getRate('USD');
    }

    /**
     * Test getRateWithNonNumericRate
     */
    public function testGetRateWithNonNumericRate(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $this->cache->method('getItem')
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('isHit')
            ->willReturn(false);

        $this->rateLimiter->expects($this->once())
            ->method('checkLimit')
            ->with('exchange_rate');

        $this->httpClient->method('request')
            ->willReturn($response);

        $response->method('toArray')
            ->willReturn(['rates' => ['USD' => 'invalid']]);

        $rate = $this->service->getRate('USD');
        $this->assertEquals(0.0, $rate);
    }
}
