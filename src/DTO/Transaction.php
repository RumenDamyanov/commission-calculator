<?php

declare(strict_types=1);

namespace App\DTO;

use App\Exception\CalculatorException;

/**
 * Represents a transaction with a BIN, amount, and currency.
 *
 * @throws CalculatorException If any parameter is invalid
 */
class Transaction
{
    /**
     * Supported currencies.
     */
    private const SUPPORTED_CURRENCIES = ['EUR', 'USD', 'GBP', 'JPY']; // Add more as needed

    /**
     * Constructs a new Transaction instance.
     *
     * @param string $bin The BIN number
     * @param float $amount The transaction amount
     * @param string $currency The transaction currency
     * @throws CalculatorException If any parameter is invalid
     */
    public function __construct(
        private string $bin,
        private float $amount,
        private string $currency
    ) {
        $this->validate();
    }

    /**
     * Validates the transaction data.
     *
     * @throws CalculatorException If any parameter is invalid
     */
    private function validate(): void
    {
        if (strlen($this->bin) < 6) {
            throw new CalculatorException('Invalid BIN number');
        }

        if ($this->amount <= 0) {
            throw new CalculatorException('Amount must be greater than zero');
        }

        if (!in_array(strtoupper($this->currency), self::SUPPORTED_CURRENCIES, true)) {
            throw new CalculatorException('Unsupported currency: ' . $this->currency);
        }
    }

    /**
     * Returns the BIN number.
     */
    public function getBin(): string
    {
        return $this->bin;
    }

    /**
     * Returns the transaction amount.
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Returns the transaction currency.
     */
    public function getCurrency(): string
    {
        return strtoupper($this->currency);
    }
}
