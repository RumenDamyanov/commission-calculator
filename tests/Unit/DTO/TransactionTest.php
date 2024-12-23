<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use PHPUnit\Framework\TestCase;
use App\DTO\Transaction;
use App\Exception\CalculatorException;

/**
 * TransactionTest class
 */
class TransactionTest extends TestCase
{
    /**
     * Test valid transaction
     */
    public function testValidTransaction(): void
    {
        $transaction = new Transaction('45717360', 100.00, 'EUR');

        $this->assertEquals('45717360', $transaction->getBin());
        $this->assertEquals(100.00, $transaction->getAmount());
        $this->assertEquals('EUR', $transaction->getCurrency());
    }

    /**
     * Test invalid BIN
     */
    public function testInvalidBin(): void
    {
        $this->expectException(CalculatorException::class);
        $this->expectExceptionMessage('Invalid BIN number');

        new Transaction('12345', 100.00, 'EUR'); // Too short
    }

    /**
     * Test invalid amount
     */
    public function testInvalidAmount(): void
    {
        $this->expectException(CalculatorException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        new Transaction('45717360', 0.00, 'EUR');
    }

    /**
     * Test negative amount
     */
    public function testNegativeAmount(): void
    {
        $this->expectException(CalculatorException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        new Transaction('45717360', -100.00, 'EUR');
    }

    /**
     * Test invalid currency
     */
    public function testInvalidCurrency(): void
    {
        $this->expectException(CalculatorException::class);
        $this->expectExceptionMessage('Unsupported currency: XXX');

        new Transaction('45717360', 100.00, 'XXX');
    }

    /**
     * Test currency normalization
     */
    public function testCurrencyNormalization(): void
    {
        $transaction = new Transaction('45717360', 100.00, 'eur');
        $this->assertEquals('EUR', $transaction->getCurrency());
    }
}
