<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Service\CommissionCalculator;
use App\Interface\BinLookupInterface;
use App\Interface\ExchangeRateInterface;
use App\Helper\CountryHelper;
use App\DTO\Transaction;
use App\Exception\CalculatorException;
use Psr\Log\LoggerInterface;

/**
 * CommissionCalculatorTest class
 */
class CommissionCalculatorTest extends TestCase
{
    private CommissionCalculator $calculator;
    private MockObject&BinLookupInterface $binLookup;
    private MockObject&ExchangeRateInterface $exchangeRates;
    private CountryHelper $countryHelper;
    private MockObject&LoggerInterface $logger;

    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        $this->binLookup = $this->createMock(BinLookupInterface::class);
        $this->exchangeRates = $this->createMock(ExchangeRateInterface::class);
        $this->countryHelper = new CountryHelper();
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->calculator = new CommissionCalculator(
            $this->binLookup,
            $this->exchangeRates,
            $this->countryHelper,
            0.01, // EU rate
            0.02, // non-EU rate
            $this->logger
        );
    }

    /**
     * Test calculate with EU transaction
     */
    public function testCalculateWithEuTransaction(): void
    {
        $transaction = new Transaction('45717360', 100.00, 'EUR');

        $this->binLookup->method('lookup')
            ->willReturn('DE');

        $this->exchangeRates->method('convertToEur')
            ->willReturn(100.00);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Commission calculated', $this->anything());

        $commission = $this->calculator->calculate($transaction);

        $this->assertEquals(1.00, $commission->getAmount());
        $this->assertEquals('EUR', $commission->getCurrency());
        $this->assertTrue($commission->isEu());
    }

    /**
     * Test calculate with non-EU transaction
     */
    public function testCalculateWithNonEuTransaction(): void
    {
        $transaction = new Transaction('45717360', 100.00, 'USD');

        $this->binLookup->method('lookup')
            ->willReturn('US');

        $this->exchangeRates->method('convertToEur')
            ->willReturn(90.00);

        $commission = $this->calculator->calculate($transaction);

        $this->assertEquals(1.80, $commission->getAmount());
        $this->assertEquals('EUR', $commission->getCurrency());
        $this->assertFalse($commission->isEu());
    }

    /**
     * Test calculate with API error
     */
    public function testCalculateWithApiError(): void
    {
        $transaction = new Transaction('45717360', 100.00, 'EUR');

        $this->binLookup->method('lookup')
            ->willThrowException(new CalculatorException('API Error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Commission calculation failed', $this->anything());

        $this->expectException(CalculatorException::class);
        $this->expectExceptionMessage('Failed to calculate commission: API Error');

        $this->calculator->calculate($transaction);
    }

    /**
     * Test calculate with zero amount
     */
    public function testCalculateWithZeroAmount(): void
    {
        $this->expectException(CalculatorException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        new Transaction('45717360', 0.00, 'EUR');
    }
}
