<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Service\BinLookupService;
use App\Service\RateLimitService;
use App\Exception\CalculatorException;
use App\Helper\CountryHelper;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * BinLookupServiceTest class
 */
class BinLookupServiceTest extends TestCase
{
    private BinLookupService $service;
    private MockObject&HttpClientInterface $httpClient;
    private MockObject&CacheItemPoolInterface $cache;
    private MockObject&CacheItemInterface $cacheItem;
    private MockObject&RateLimitService $rateLimiter;
    private CountryHelper $countryHelper;

    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        $this->httpClient = $this->getMockBuilder(HttpClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cache = $this->getMockBuilder(CacheItemPoolInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cacheItem = $this->getMockBuilder(CacheItemInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->rateLimiter = $this->getMockBuilder(RateLimitService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->countryHelper = new CountryHelper();

        $this->service = new BinLookupService(
            $this->httpClient,
            $this->cache,
            $this->countryHelper,
            $this->rateLimiter,
            'https://api.example.com',
            5,
            3600
        );
    }

    /**
     * Test lookup with cached country
     */
    public function testLookupWithCachedCountry(): void
    {
        // Arrange
        $bin = '45717360';
        $country = 'DE';

        $this->cache->method('getItem')
            ->with('bin_' . $bin)
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('isHit')
            ->willReturn(true);

        $this->cacheItem->method('get')
            ->willReturn($country);

        // The rate limiter should not be called for cached responses
        $this->rateLimiter->expects($this->never())
            ->method('checkLimit');

        // Act
        $result = $this->service->lookup($bin);

        // Assert
        $this->assertEquals($country, $result);
    }

    /**
     * Test lookup from API
     */
    public function testLookupFromApi(): void
    {
        // Arrange
        $bin = '45717360';
        $country = 'DE';

        $response = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cache->method('getItem')
            ->with('bin_' . $bin)
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('isHit')
            ->willReturn(false);

        $this->rateLimiter->expects($this->once())
            ->method('checkLimit')
            ->with('bin_lookup');

        $this->httpClient->method('request')
            ->with(
                'GET',
                'https://api.example.com/' . $bin,
                ['timeout' => 5]
            )
            ->willReturn($response);

        $response->method('toArray')
            ->willReturn(['country' => ['alpha2' => $country]]);

        $this->cacheItem->method('set')
            ->with($country)
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('expiresAfter')
            ->with(3600)
            ->willReturn($this->cacheItem);

        $this->cache->expects($this->once())
            ->method('save')
            ->with($this->cacheItem)
            ->willReturn(true);

        // Act
        $result = $this->service->lookup($bin);

        // Assert
        $this->assertEquals($country, $result);
    }

    /**
     * Test lookup throws exception on API error
     */
    public function testLookupThrowsExceptionOnApiError(): void
    {
        // Arrange
        $bin = 'invalid';

        $this->cache->method('getItem')
            ->with('bin_' . $bin)
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('isHit')
            ->willReturn(false);

        $this->httpClient->method('request')
            ->willThrowException(new \Exception('API Error'));

        // Assert
        $this->expectException(CalculatorException::class);
        $this->expectExceptionMessage('Failed to lookup BIN: API Error');

        // Act
        $this->service->lookup($bin);
    }

    /**
     * Test lookup throws exception on missing country data
     */
    public function testLookupThrowsExceptionOnMissingCountryData(): void
    {
        // Arrange
        $bin = '45717360';

        $response = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cache->method('getItem')
            ->with('bin_' . $bin)
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('isHit')
            ->willReturn(false);

        $this->httpClient->method('request')
            ->willReturn($response);

        $response->method('toArray')
            ->willReturn(['country' => []]);  // Missing alpha2 field

        // Assert
        $this->expectException(CalculatorException::class);
        $this->expectExceptionMessage('Invalid country data received for BIN: 45717360');

        // Act
        $this->service->lookup($bin);
    }

    /**
     * Test lookup throws exception on invalid API response
     */
    public function testLookupThrowsExceptionOnInvalidApiResponse(): void
    {
        // Arrange
        $bin = '45717360';

        $response = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cache->method('getItem')
            ->with('bin_' . $bin)
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('isHit')
            ->willReturn(false);

        $this->httpClient->method('request')
            ->willReturn($response);

        $response->method('toArray')
            ->willReturn([]); // Empty response

        // Assert
        $this->expectException(CalculatorException::class);
        $this->expectExceptionMessage('Invalid response format for BIN: 45717360');

        // Act
        $this->service->lookup($bin);
    }

    /**
     * Test isEuCountry with EU BIN
     */
    public function testIsEuCountryWithEuBin(): void
    {
        // Arrange
        $bin = '45717360';
        $country = 'DE';

        $this->cache->method('getItem')
            ->with('bin_' . $bin)
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('isHit')
            ->willReturn(true);

        $this->cacheItem->method('get')
            ->willReturn($country);

        // Act
        $result = $this->service->isEuCountry($bin);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test isEuCountry with non-EU BIN
     */
    public function testIsEuCountryWithNonEuBin(): void
    {
        // Arrange
        $bin = '45717360';
        $country = 'US';

        $this->cache->method('getItem')
            ->with('bin_' . $bin)
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('isHit')
            ->willReturn(true);

        $this->cacheItem->method('get')
            ->willReturn($country);

        // Act
        $result = $this->service->isEuCountry($bin);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Test isEuCountry with empty country code
     */
    public function testIsEuCountryWithEmptyCountryCode(): void
    {
        // Arrange
        $bin = '45717360';
        $country = '';

        $this->cache->method('getItem')
            ->with('bin_' . $bin)
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('isHit')
            ->willReturn(true);

        $this->cacheItem->method('get')
            ->willReturn($country);

        // Act
        $result = $this->service->isEuCountry($bin);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Test lookup with cache disabled
     */
    public function testLookupWithCacheDisabled(): void
    {
        // Arrange
        $bin = '45717360';
        $country = 'DE';

        $response = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Disable cache
        $this->service->setCacheEnabled(false);

        // Cache should not be used at all
        $this->cache->expects($this->never())
            ->method('getItem');
        $this->cache->expects($this->never())
            ->method('save');

        $this->rateLimiter->expects($this->once())
            ->method('checkLimit')
            ->with('bin_lookup');

        $this->httpClient->method('request')
            ->with(
                'GET',
                'https://api.example.com/' . $bin,
                ['timeout' => 5]
            )
            ->willReturn($response);

        $response->method('toArray')
            ->willReturn(['country' => ['alpha2' => $country]]);

        // Act
        $result = $this->service->lookup($bin);

        // Assert
        $this->assertEquals($country, $result);
    }

    /**
     * Test lookup with cache save failure
     */
    public function testLookupWithCacheSaveFailure(): void
    {
        // Arrange
        $bin = '45717360';
        $country = 'DE';

        $response = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cache->method('getItem')
            ->with('bin_' . $bin)
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('isHit')
            ->willReturn(false);

        $this->rateLimiter->expects($this->once())
            ->method('checkLimit')
            ->with('bin_lookup');

        $this->httpClient->method('request')
            ->willReturn($response);

        $response->method('toArray')
            ->willReturn(['country' => ['alpha2' => $country]]);

        $this->cacheItem->method('set')
            ->with($country)
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('expiresAfter')
            ->with(3600)
            ->willReturn($this->cacheItem);

        $this->cache->method('save')
            ->willReturn(false); // Simulate cache save failure

        // Act
        $result = $this->service->lookup($bin);

        // Assert
        $this->assertEquals($country, $result);
    }

    /**
     * Test lookup with rate limit exceeded
     */
    public function testLookupWithRateLimitExceeded(): void
    {
        // Arrange
        $bin = '45717360';

        $this->cache->method('getItem')
            ->with('bin_' . $bin)
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('isHit')
            ->willReturn(false);

        $this->rateLimiter->method('checkLimit')
            ->willThrowException(new CalculatorException('Rate limit exceeded'));

        // Assert
        $this->expectException(CalculatorException::class);
        $this->expectExceptionMessage('Rate limit exceeded');

        // Act
        $this->service->lookup($bin);
    }

    /**
     * Tear down the test environment
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('CACHE_ENABLED'); // Ensure we clean up any environment changes
    }
}
