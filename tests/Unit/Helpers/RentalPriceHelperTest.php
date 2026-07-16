<?php

namespace Tests\Unit\Helpers;

use App\Helpers\RentalPriceHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Smart Rental Price Rounding — canonical algorithm.
 *
 * Endings and threshold are configuration, never hard-coded: every case
 * here passes them in explicitly, and alternate configurations are covered
 * alongside the primary 4/7 + $10 examples from the specification.
 */
class RentalPriceHelperTest extends TestCase
{
    /** The specification's boundary table: endings {4,7}, threshold $10. */
    public static function boundaryTable(): array
    {
        return [
            ['221.99', 224],
            ['224.00', 224],
            ['224.01', 227],
            ['245.38', 247],
            ['397.00', 397],
            ['397.01', 397],
            ['399.70', 397],
            ['400.00', 397],
            ['401.25', 397],
            ['409.99', 397],
            ['410.00', 414],
            ['414.00', 414],
            ['414.01', 417],
            ['497.50', 497],
            ['509.99', 497],
            ['510.00', 514],
        ];
    }

    #[DataProvider('boundaryTable')]
    public function test_boundary_table_with_endings_4_7_and_threshold_10(string $raw, int $expected): void
    {
        $rawCents = (int) str_replace('.', '', $raw);

        $this->assertSame(
            $expected,
            RentalPriceHelper::smartRoundDollars($rawCents, [4, 7], 10),
            "raw \${$raw} should normalize to \${$expected}",
        );
    }

    public function test_below_first_hundred_uses_lowest_upward_ending(): void
    {
        // No previous hundred exists: never a negative/invalid price.
        $this->assertSame(4, RentalPriceHelper::smartRoundDollars(300, [4, 7], 10));   // $3.00
        $this->assertSame(7, RentalPriceHelper::smartRoundDollars(500, [4, 7], 10));   // $5.00
        $this->assertSame(4, RentalPriceHelper::smartRoundDollars(0, [4, 7], 10));     // $0.00
        // Crossing protection still applies within the first band.
        $this->assertSame(97, RentalPriceHelper::smartRoundDollars(9950, [4, 7], 10)); // $99.50
    }

    public function test_different_allowed_endings(): void
    {
        // Endings {9}: everything rounds up to a 9 ending.
        $this->assertSame(229, RentalPriceHelper::smartRoundDollars(22101, [9], 10));  // $221.01
        $this->assertSame(399, RentalPriceHelper::smartRoundDollars(39900, [9], 10));  // $399.00
        $this->assertSame(399, RentalPriceHelper::smartRoundDollars(40500, [9], 10));  // $405.00 (entry zone)
        $this->assertSame(419, RentalPriceHelper::smartRoundDollars(41000, [9], 10));  // $410.00

        // Endings {0,5}: classic retail endings.
        $this->assertSame(225, RentalPriceHelper::smartRoundDollars(22101, [0, 5], 10));
        $this->assertSame(395, RentalPriceHelper::smartRoundDollars(39700, [0, 5], 10)); // $397 → up would be $400 (crossing) → $395
        $this->assertSame(410, RentalPriceHelper::smartRoundDollars(41000, [0, 5], 10));
    }

    public function test_different_threshold(): void
    {
        // Threshold $25: the entry zone widens.
        $this->assertSame(397, RentalPriceHelper::smartRoundDollars(42499, [4, 7], 25)); // $424.99 stays back
        $this->assertSame(427, RentalPriceHelper::smartRoundDollars(42500, [4, 7], 25)); // $425.00 enters

        // Threshold $0: no entry protection at all.
        $this->assertSame(404, RentalPriceHelper::smartRoundDollars(40000, [4, 7], 0));  // $400.00 → $404
        $this->assertSame(404, RentalPriceHelper::smartRoundDollars(40001, [4, 7], 0));
    }

    public function test_derived_price_applies_multiplier_then_smart_rounding(): void
    {
        // 424.00 × 1.54 = 652.96 → up to 654
        $this->assertSame('654.00', RentalPriceHelper::derivedPrice('424.00', '1.54', [4, 7], 10));
        // 424.00 × 3.15 = 1335.60 → up to 1337
        $this->assertSame('1337.00', RentalPriceHelper::derivedPrice('424.00', '3.15', [4, 7], 10));
        // 424.00 × 9.45 = 4006.80 → entry zone of 4000 → back to 3997
        $this->assertSame('3997.00', RentalPriceHelper::derivedPrice('424.00', '9.45', [4, 7], 10));
    }

    public function test_derived_price_without_endings_keeps_raw_multiplier_price(): void
    {
        // Smart rounding inactive: prior behavior, raw price to the cent.
        $this->assertSame('652.96', RentalPriceHelper::derivedPrice('424.00', '1.54', [], 10));
    }

    public function test_derived_price_inapplicable_cases_return_null(): void
    {
        $this->assertNull(RentalPriceHelper::derivedPrice(null, '1.54', [4, 7], 10));
        $this->assertNull(RentalPriceHelper::derivedPrice('', '1.54', [4, 7], 10));
        $this->assertNull(RentalPriceHelper::derivedPrice('0', '1.54', [4, 7], 10));
        $this->assertNull(RentalPriceHelper::derivedPrice('424.00', null, [4, 7], 10));
        $this->assertNull(RentalPriceHelper::derivedPrice('424.00', '', [4, 7], 10));
        $this->assertNull(RentalPriceHelper::derivedPrice('424.00', '0', [4, 7], 10));
        $this->assertNull(RentalPriceHelper::derivedPrice('abc', '1.54', [4, 7], 10));
    }

    public function test_parse_endings(): void
    {
        $this->assertSame([4, 7], RentalPriceHelper::parseEndings('4,7'));
        $this->assertSame([4, 7], RentalPriceHelper::parseEndings('7, 4, 4'));
        $this->assertSame([0, 9], RentalPriceHelper::parseEndings('9,0'));
        $this->assertSame([4], RentalPriceHelper::parseEndings('4, x, 12'));
        $this->assertSame([], RentalPriceHelper::parseEndings(''));
        $this->assertSame([], RentalPriceHelper::parseEndings(null));
        $this->assertSame([], RentalPriceHelper::parseEndings(' , ,'));
    }

    public function test_parse_threshold(): void
    {
        $this->assertSame(10, RentalPriceHelper::parseThreshold('10'));
        $this->assertSame(0, RentalPriceHelper::parseThreshold(null));
        $this->assertSame(0, RentalPriceHelper::parseThreshold(''));
        $this->assertSame(0, RentalPriceHelper::parseThreshold('-5'));
        $this->assertSame(0, RentalPriceHelper::parseThreshold('abc'));
        $this->assertSame(25, RentalPriceHelper::parseThreshold(25));
    }
}
