<?php
declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use PHPUnit\Framework\TestCase;
use App\Helper\CountryHelper;

/**
 * CountryHelperTest class
 */
class CountryHelperTest extends TestCase
{
  private CountryHelper $helper;

  /**
   * Set up the test environment
   */
  protected function setUp(): void
  {
    $this->helper = new CountryHelper();
  }

  /**
   * Test isEu with EU country
   */
  public function testIsEuWithEuCountry(): void
  {
    $this->assertTrue($this->helper->isEu('DE'));
    $this->assertTrue($this->helper->isEu('FR'));
    $this->assertTrue($this->helper->isEu('IT'));
  }

  /**
   * Test isEu with non-EU country
   */
  public function testIsEuWithNonEuCountry(): void
  {
    $this->assertFalse($this->helper->isEu('US'));
    $this->assertFalse($this->helper->isEu('GB'));
    $this->assertFalse($this->helper->isEu('CH'));
  }

  /**
   * Test isEu with invalid country
   */
  public function testIsEuWithInvalidCountry(): void
  {
    $this->assertFalse($this->helper->isEu(''));
    $this->assertFalse($this->helper->isEu('XX'));
  }

  /**
   * Test getEuCountries
   */
  public function testGetEuCountries(): void
  {
    $countries = $this->helper->getEuCountries();

    $this->assertIsArray($countries);
    $this->assertNotEmpty($countries);
    $this->assertContains('DE', $countries);
    $this->assertContains('FR', $countries);
    $this->assertNotContains('US', $countries);
    $this->assertNotContains('GB', $countries);
  }

  /**
   * Test isValidCountryCode
   */
  public function testIsValidCountryCode(): void
  {
    // Valid country codes
    $this->assertTrue($this->helper->isValidCountryCode('US'));
    $this->assertTrue($this->helper->isValidCountryCode('GB'));
    $this->assertTrue($this->helper->isValidCountryCode('de')); // Should work with lowercase

    // Invalid country codes
    $this->assertFalse($this->helper->isValidCountryCode(''));
    $this->assertFalse($this->helper->isValidCountryCode('USA'));
    $this->assertFalse($this->helper->isValidCountryCode('1'));
    $this->assertFalse($this->helper->isValidCountryCode('A'));
    $this->assertFalse($this->helper->isValidCountryCode('ABC'));
  }
}
