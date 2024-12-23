<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Transaction;
use App\DTO\Commission;
use App\Interface\BinLookupInterface;
use App\Interface\ExchangeRateInterface;
use App\Interface\CommissionCalculatorInterface;
use App\Exception\CalculatorException;
use App\Helper\CountryHelper;
use Psr\Log\LoggerInterface;

/**
 * CommissionCalculator class for calculating commission based on transaction details.
 */
final class CommissionCalculator implements CommissionCalculatorInterface
{
    /**
     * @param BinLookupInterface $binLookup
     * @param ExchangeRateInterface $exchangeRates
     * @param CountryHelper $countryHelper
     * @param float $euRate
     * @param float $nonEuRate
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        private readonly BinLookupInterface $binLookup,
        private readonly ExchangeRateInterface $exchangeRates,
        private readonly CountryHelper $countryHelper,
        private readonly float $euRate,
        private readonly float $nonEuRate,
        private readonly ?LoggerInterface $logger = null
    ) {}

    /**
     * Calculate commission for a transaction
     *
     * @param Transaction $transaction
     * @return Commission
     * @throws CalculatorException
     */
    public function calculate(Transaction $transaction): Commission
    {
        try {
            $country = $this->binLookup->lookup($transaction->getBin());
            $isEu = $this->countryHelper->isEu($country);

            $amountInEur = $this->exchangeRates->convertToEur(
                $transaction->getAmount(),
                $transaction->getCurrency()
            );

            $rate = $isEu ? $this->euRate : $this->nonEuRate;
            $commissionAmount = $this->ceiling($amountInEur * $rate);

            $commission = new Commission(
                $commissionAmount,
                'EUR',
                $rate,
                $isEu
            );

            $this->logger?->info('Commission calculated', [
                'bin' => $transaction->getBin(),
                'amount' => $transaction->getAmount(),
                'currency' => $transaction->getCurrency(),
                'isEu' => $isEu,
                'commission' => $commissionAmount
            ]);

            return $commission;
        } catch (\Exception $e) {
            $this->logger?->error('Commission calculation failed', [
                'error' => $e->getMessage(),
                'transaction' => [
                    'bin' => $transaction->getBin(),
                    'amount' => $transaction->getAmount(),
                    'currency' => $transaction->getCurrency(),
                ]
            ]);
            throw new CalculatorException('Failed to calculate commission: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Ceiling function for amount
     *
     * @param float $amount
     * @return float
     */
    private function ceiling(float $amount): float
    {
        return ceil($amount * 100) / 100;
    }
}
