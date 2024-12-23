<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Service\RateLimitService;
use App\Exception\CalculatorException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;

/**
 * RateLimitServiceTest class
 */
class RateLimitServiceTest extends TestCase
{
    private RateLimitService $service;
    private MockObject&CacheItemPoolInterface $cache;
    private MockObject&CacheItemInterface $cacheItem;

    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheItem = $this->createMock(CacheItemInterface::class);

        $this->service = new RateLimitService(
            $this->cache,
            'test',
            2, // limit
            60  // window
        );
    }

    /**
     * Test checkLimitWithinLimits
     */
    public function testCheckLimitWithinLimits(): void
    {
        $this->cache->method('getItem')
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('get')
            ->willReturn([time() - 30]); // One recent request

        $this->cacheItem->expects($this->once())
            ->method('set')
            ->willReturn($this->cacheItem);

        $this->cacheItem->expects($this->once())
            ->method('expiresAfter')
            ->with(60)
            ->willReturn($this->cacheItem);

        $this->cache->expects($this->once())
            ->method('save')
            ->with($this->cacheItem);

        $this->service->checkLimit('test_key');
    }

    /**
     * Test checkLimitExceeded
     */
    public function testCheckLimitExceeded(): void
    {
        $now = time();
        $this->cache->method('getItem')
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('get')
            ->willReturn([$now - 30, $now - 20]); // Two recent requests

        $this->expectException(CalculatorException::class);
        $this->expectExceptionMessage('Rate limit exceeded. Maximum 2 requests per 60 seconds.');

        $this->service->checkLimit('test_key');
    }

    /**
     * Test checkLimitWithOldRequests
     */
    public function testCheckLimitWithOldRequests(): void
    {
        $now = time();
        $this->cache->method('getItem')
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('get')
            ->willReturn([$now - 120, $now - 30]); // One old, one recent

        $this->cacheItem->expects($this->once())
            ->method('set')
            ->willReturn($this->cacheItem);

        $this->service->checkLimit('test_key');
    }

    /**
     * Test checkLimitWithNoExistingRequests
     */
    public function testCheckLimitWithNoExistingRequests(): void
    {
        $this->cache->method('getItem')
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('get')
            ->willReturn(null); // No existing requests

        $this->cacheItem->expects($this->once())
            ->method('set')
            ->willReturn($this->cacheItem);

        $this->service->checkLimit('test_key');
    }

    /**
     * Test checkLimitWithRateLimitDisabled
     */
    public function testCheckLimitWithRateLimitDisabled(): void
    {
        // Arrange
        $this->service->setEnabled(false);

        // Cache should not be used at all when disabled
        $this->cache->expects($this->never())
            ->method('getItem');
        $this->cache->expects($this->never())
            ->method('save');

        // Act
        $this->service->checkLimit('test_key');

        // Assert
        $this->assertFalse($this->service->isEnabled());
    }

    /**
     * Test checkLimitWithCacheSaveFailure
     */
    public function testCheckLimitWithCacheSaveFailure(): void
    {
        // Arrange
        $now = time();
        $this->cache->method('getItem')
            ->willReturn($this->cacheItem);

        $this->cacheItem->method('get')
            ->willReturn([time() - 30]); // One recent request

        $this->cacheItem->method('set')
            ->willReturn($this->cacheItem);

        $this->cache->method('save')
            ->willReturn(false); // Simulate cache save failure

        // Act & Assert
        try {
            $this->service->checkLimit('test_key');
            $this->assertTrue(true, 'No exception should be thrown on cache save failure');
        } catch (\Exception $e) {
            $this->fail('No exception should be thrown on cache save failure');
        }
    }

    /**
     * Tear down the test environment
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('RATE_LIMIT_ENABLED'); // Clean up environment changes
    }
}
