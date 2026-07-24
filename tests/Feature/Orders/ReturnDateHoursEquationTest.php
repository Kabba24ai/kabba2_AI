<?php

namespace Tests\Feature\Orders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Change Return Date modal — equipment-hours summary is now a live equation:
 *   Allocated Hours: {current} + Hours Added: {added} = Total Hours: {total}
 *
 * The calculation is client-side JS (recalcTotal in edit.blade.php) and there
 * is no JS test harness in this repo, so these tests guard the shipped markup
 * and JS logic at the source level (deterministic, no heavy edit-page fixture).
 * The live per-interaction values are documented for browser verification:
 *   current 8 + Daily 8×1     = 16
 *   current 8 + Weekend 5.6×1 = 13.6
 *   current 8 + Weekly 40×2   = 88
 *   current 8 + Custom 2      = 10   (→ change custom to 4 ⇒ 12)
 *   Change Return Date only   ⇒ 0 added (hours section + summary hidden)
 */
class ReturnDateHoursEquationTest extends TestCase
{
    use RefreshDatabase;

    private string $blade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->blade = file_get_contents(
            resource_path('views/admin/order_management/orders/edit.blade.php')
        );
    }

    public function test_summary_renders_the_three_part_equation(): void
    {
        // Equation labels, in order.
        $this->assertStringContainsString('Allocated Hours:', $this->blade);
        $this->assertStringContainsString('Hours Added:', $this->blade);
        $this->assertStringContainsString('Total Hours:', $this->blade);

        // The new finished-sum span exists and Total Hours is emphasized.
        $this->assertStringContainsString('id="returnGrandTotalHours"', $this->blade);
        $this->assertMatchesRegularExpression(
            '/id="returnGrandTotalHours"[^>]*class="[^"]*font-bold/',
            $this->blade,
        );

        // The old confusing single-line phrasing is gone.
        $this->assertStringNotContainsString('Current Allocated Hours:', $this->blade);
        $this->assertStringNotContainsString('Total Hours Added:', $this->blade);
    }

    public function test_recalc_drives_the_live_equation_and_keeps_the_submitted_value_as_added_hours(): void
    {
        // Grand total = allocated + added, updated live.
        $this->assertStringContainsString('allocated + added', $this->blade);
        $this->assertStringContainsString('grandTotalDisplay.textContent = formatHours(allocated + added)', $this->blade);

        // recalcTotal still returns ADDED hours (save path unchanged) — it must
        // not start returning the grand total.
        $this->assertMatchesRegularExpression('/function recalcTotal\(\)[\s\S]*return added;[\s\S]*?\}/', $this->blade);
        $this->assertStringNotContainsString('return allocated + added', $this->blade);
    }

    public function test_shared_formatter_strips_trailing_zeros_and_float_noise(): void
    {
        // One shared formatter for all three values.
        $this->assertStringContainsString('function formatHours(', $this->blade);
        $this->assertStringContainsString('Math.round(num * 1000) / 1000', $this->blade);

        // Mirror the JS formatting rules to lock the expected outputs.
        $fmt = fn ($n) => (string) (round(((float) $n) * 1000) / 1000);
        $this->assertSame('8', $fmt(8.0));       // not "8.00"
        $this->assertSame('5.6', $fmt(5.60));    // not "5.60"
        $this->assertSame('13.6', $fmt(8 + 5.6));// not "13.6000001"
        $this->assertSame('88', $fmt(8 + 40 * 2));
        $this->assertSame('10', $fmt(8 + 2));
        $this->assertSame('0', $fmt(0));
    }
}
