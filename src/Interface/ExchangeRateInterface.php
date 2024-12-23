<?php

declare(strict_types=1);

namespace App\Interface;

use App\Exception\CalculatorException;

/**
 * Interface for currency exchange rate services.
 */
interface ExchangeRateInterface
{
    /**
     * Convert amount from given currency to EUR
     *
     * @param float $amount Amount to convert
     * @param string $currency Source currency code (ISO 4217)
     * @return float Amount in EUR
     * @throws CalculatorException If conversion fails
     */
    public function convertToEur(float $amount, string $currency): float;

    /**
     * Get exchange rate for a currency
     *
     * @param string $currency Currency code (ISO 4217)
     * @return float Exchange rate relative to EUR
     * @throws CalculatorException If rate cannot be retrieved
     */
    public function getRate(string $currency): float;
}
