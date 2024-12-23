<?php

declare(strict_types=1);

namespace App\Interface;

use App\DTO\Transaction;
use App\DTO\Commission;
use App\Exception\CalculatorException;

/**
 * Interface for commission calculation services.
 */
interface CommissionCalculatorInterface
{
    /**
     * Calculate commission for a transaction
     *
     * @param Transaction $transaction
     * @return Commission
     * @throws CalculatorException
     */
    public function calculate(Transaction $transaction): Commission;
}
