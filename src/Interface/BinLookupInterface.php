<?php
declare(strict_types=1);

namespace App\Interface;

/**
 * Interface for BIN (Bank Identification Number) lookup services.
 */
interface BinLookupInterface
{
    /**
     * Lookup country code by BIN
     *
     * @param string $bin Bank Identification Number
     * @return string ISO 3166-1 alpha-2 country code
     * @throws \App\Exception\CalculatorException
     */
    public function lookup(string $bin): string;

    /**
     * Check if BIN belongs to EU country
     *
     * @param string $bin Bank Identification Number
     * @return bool
     * @throws \App\Exception\CalculatorException
     */
    public function isEuCountry(string $bin): bool;
}
