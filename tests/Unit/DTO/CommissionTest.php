<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use PHPUnit\Framework\TestCase;
use App\DTO\Commission;

/**
 * CommissionTest class
 */
class CommissionTest extends TestCase
{
    /**
     * Test Commission getters
     */
    public function testCommissionGetters(): void
    {
        // Arrange
        $amount = 10.00;
        $currency = 'EUR';
        $rate = 0.01;
        $isEu = true;

        // Act
        $commission = new Commission($amount, $currency, $rate, $isEu);

        // Assert
        $this->assertEquals($amount, $commission->getAmount());
        $this->assertEquals($currency, $commission->getCurrency());
        $this->assertEquals($rate, $commission->getRate());
        $this->assertTrue($commission->isEu());
    }

    public function testCommissionWithNonEuRate(): void
    {
        // Arrange
        $amount = 20.00;
        $currency = 'USD';
        $rate = 0.02;
        $isEu = false;

        // Act
        $commission = new Commission($amount, $currency, $rate, $isEu);

        // Assert
        $this->assertEquals($amount, $commission->getAmount());
        $this->assertEquals($currency, $commission->getCurrency());
        $this->assertEquals($rate, $commission->getRate());
        $this->assertFalse($commission->isEu());
    }
}
