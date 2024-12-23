<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Represents a commission with an amount, currency, rate, and EU status.
 */
class Commission
{
    /**
     * Constructs a new Commission instance.
     *
     * @param float $amount The commission amount
     * @param string $currency The commission currency
     * @param float $rate The commission rate
     * @param bool $isEu Whether the transaction is from the EU
     */
    public function __construct(
        private readonly float $amount,
        private readonly string $currency,
        private readonly float $rate,
        private readonly bool $isEu
    ) {}

    /**
     * Returns the commission amount.
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Returns the commission currency.
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Returns the commission rate.
     */
    public function getRate(): float
    {
        return $this->rate;
    }

    /**
     * Returns whether the transaction is from the EU.
     */
    public function isEu(): bool
    {
        return $this->isEu;
    }
}
