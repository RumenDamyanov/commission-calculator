<?php

declare(strict_types=1);

namespace App\Helper;

/**
 * Helper class for country-related operations.
 */
class CountryHelper
{
    /**
     * List of EU country codes (ISO 3166-1 alpha-2)
     * @var array<string>
     */
    private const EU_COUNTRIES = [
        'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI',
        'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT',
        'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'
    ];

    /**
     * Checks if a country is in the European Union
     *
     * @param string $countryCode ISO 3166-1 alpha-2 country code
     * @return bool
     */
    public function isEu(string $countryCode): bool
    {
        return in_array(
            strtoupper(trim($countryCode)),
            self::EU_COUNTRIES,
            true
        );
    }

    /**
     * Get all EU country codes
     *
     * @return array<string>
     */
    public function getEuCountries(): array
    {
        return self::EU_COUNTRIES;
    }

    /**
     * Validates if the provided country code is a valid ISO 3166-1 alpha-2 code
     *
     * @param string $countryCode
     * @return bool
     */
    public function isValidCountryCode(string $countryCode): bool
    {
        return (bool) preg_match('/^[A-Z]{2}$/i', trim($countryCode));
    }
}
