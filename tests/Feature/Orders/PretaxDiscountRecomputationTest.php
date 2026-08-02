<?php

namespace Tests\Feature\Orders;

use App\Models\Customers\Customer;
use App\Models\Orders\Order;
use App\Services\Discounts\Targets\OrderDiscountTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Shared pre-tax discount recomputation — the special-tax correction.
 *
 * The defect this fixes: `recompute()` reduced the merchandise basis and
 * recomputed ordinary tax, then preserved everything else as one lumped
 * residual. Special tax is basis-derived, so it must shrink with the basis.
 * It did not, and the customer was charged special tax on merchandise value
 * they were never billed for.
 *
 * `special_tax_shrinks_with_the_basis` is the load-bearing test. Everything
 * else guards the properties that make the correction safe to apply
 * repeatedly: gross values never move, added fees never move, and removing
 * every discount restores the original to the cent.
 */
class PretaxDiscountRecomputationTest extends TestCase
{
    use RefreshDatabase;

    private const RATE = 0.0975;

    // ── The correction ─────────────────────────────────────────────────────

    public function test_special_tax_shrinks_with_the_basis(): void
    {
        // $200 basis · 9.75% = $19.50 ordinary · 2% = $4.00 special · $223.50
        $order = $this->order(200.00, 19.50, 4.00, 0.00);

        $this->discount($order, 50.00);

        $order->refresh();
        $this->assertSame('14.63', (string) $order->tax_amount, 'Ordinary tax scales.');
        $this->assertSame('3.00', (string) $order->special_tax_amount, '150.00 x 2% = 3.00 — this is the fix.');
        $this->assertSame('167.63', (string) $order->grand_total);
    }

    public function test_ordinary_taxable_store_credit(): void
    {
        $order = $this->order(200.00, 19.50, 0.00, 0.00);

        $this->discount($order, 50.00);

        $order->refresh();
        $this->assertSame('14.63', (string) $order->tax_amount);
        $this->assertSame('164.63', (string) $order->grand_total);
        $this->assertReconciles($order);
    }

    public function test_mixed_taxable_and_tax_free_merchandise(): void
    {
        // $100 taxable @9.75% + $100 tax-free. Only the taxable line bears tax.
        $order = $this->order(200.00, 9.75, 0.00, 0.00, mixed: true);

        $this->discount($order, 50.00);

        $order->refresh();
        // The surviving taxable basis is 75.00 → 7.31.
        $this->assertSame('7.31', (string) $order->tax_amount);

        $lines = $order->products()->orderBy('id')->get();
        $this->assertSame('7.31', (string) $lines[0]->tax, 'The taxable line carries all of it.');
        $this->assertSame('0.00', (string) $lines[1]->tax, 'A tax-free line stays tax-free however much is discounted.');
        $this->assertReconciles($order);
    }

    public function test_special_tax_only(): void
    {
        // Tax-exempt order that still owes special tax.
        $order = $this->order(200.00, 0.00, 4.00, 0.00);

        $this->discount($order, 50.00);

        $order->refresh();
        $this->assertSame('0.00', (string) $order->tax_amount, 'Reducing an untaxed order must never create tax.');
        $this->assertSame('3.00', (string) $order->special_tax_amount);
        $this->assertReconciles($order);
    }

    public function test_added_fees_only_are_never_reduced(): void
    {
        $order = $this->order(200.00, 19.50, 0.00, 6.00);

        $this->discount($order, 50.00);

        $order->refresh();
        $this->assertSame('6.00', (string) $order->added_fees_amount, 'Flat fees are protected.');
        $this->assertSame('6.00', (string) $order->products()->firstOrFail()->added_fees);
        $this->assertReconciles($order);
    }

    public function test_special_tax_and_added_fees_together(): void
    {
        $order = $this->order(200.00, 19.50, 4.00, 6.00);

        $this->discount($order, 50.00);

        $order->refresh();
        $this->assertSame('3.00', (string) $order->special_tax_amount, 'Basis-derived: scales.');
        $this->assertSame('6.00', (string) $order->added_fees_amount, 'Flat: protected.');
        $this->assertReconciles($order);
    }

    public function test_zero_tax_order(): void
    {
        $order = $this->order(200.00, 0.00, 0.00, 0.00);

        $this->discount($order, 50.00);

        $order->refresh();
        $this->assertSame('0.00', (string) $order->tax_amount);
        $this->assertSame('150.00', (string) $order->grand_total);
        $this->assertReconciles($order);
    }

    // ── Stacking and reversal ──────────────────────────────────────────────

    public function test_multiple_stacked_adjustments_are_exact(): void
    {
        $order = $this->order(200.00, 19.50, 4.00, 6.00);

        $this->discount($order, 30.00);
        $this->discount($order->fresh(), 50.00);   // cumulative

        $stacked = $order->fresh();

        // Identical to applying 50.00 in one step, because every recompute
        // derives from the immutable snapshot rather than current state.
        $single = $this->order(200.00, 19.50, 4.00, 6.00);
        $this->discount($single, 50.00);
        $single->refresh();

        $this->assertSame((string) $single->tax_amount, (string) $stacked->tax_amount);
        $this->assertSame((string) $single->special_tax_amount, (string) $stacked->special_tax_amount);
        $this->assertSame((string) $single->grand_total, (string) $stacked->grand_total);
    }

    public function test_removing_every_discount_restores_the_original_exactly(): void
    {
        $order = $this->order(200.00, 19.50, 4.00, 6.00);
        $before = DB::table('orders')->where('id', $order->id)->first();
        $beforeLines = DB::table('order_products')->where('order_id', $order->id)->orderBy('id')->get()->toArray();

        $this->discount($order, 50.00);
        $this->discount($order->fresh(), 0.00);   // reversed to nothing

        $after = DB::table('orders')->where('id', $order->id)->first();

        $this->assertSame($before->tax_amount, $after->tax_amount);
        $this->assertSame($before->special_tax_amount, $after->special_tax_amount);
        $this->assertSame($before->added_fees_amount, $after->added_fees_amount);
        $this->assertSame($before->grand_total, $after->grand_total);
        $this->assertEquals(
            $beforeLines,
            DB::table('order_products')->where('order_id', $order->id)->orderBy('id')->get()->toArray(),
            'Lines must round-trip too.'
        );
    }

    // ── Invariants ─────────────────────────────────────────────────────────

    public function test_gross_values_never_change(): void
    {
        $order = $this->order(200.00, 19.50, 4.00, 6.00);
        $grossBefore = (string) $order->subtotal;
        $lineGrossBefore = $order->products()->pluck('sub_total')->map(fn ($v) => (string) $v)->all();

        $this->discount($order, 50.00);

        $order->refresh();
        $this->assertSame($grossBefore, (string) $order->subtotal, 'orders.subtotal is gross and never moves.');
        $this->assertSame(
            $lineGrossBefore,
            $order->products()->pluck('sub_total')->map(fn ($v) => (string) $v)->all(),
            'order_products.sub_total is gross and never moves.'
        );
        $this->assertSame('50.00', (string) $order->pretax_discount_total, 'The concession is explicit.');
    }

    public function test_order_totals_equal_the_sum_of_their_lines(): void
    {
        $order = $this->order(200.00, 19.50, 4.00, 6.00, mixed: true);

        $this->discount($order, 50.00);

        $order->refresh();
        $lines = $order->products()->get();
        $c = fn ($v) => (int) round(((float) $v) * 100);

        foreach ([['tax', 'tax_amount'], ['special_tax', 'special_tax_amount'], ['added_fees', 'added_fees_amount']] as [$lineCol, $orderCol]) {
            $this->assertSame(
                $c($order->{$orderCol}),
                (int) round($lines->sum(fn ($l) => (float) $l->{$lineCol}) * 100),
                "orders.{$orderCol} must equal the sum of order_products.{$lineCol}."
            );
        }
    }

    public function test_frozen_product_data_is_never_written(): void
    {
        $order = $this->order(200.00, 19.50, 4.00, 6.00);
        $before = DB::table('order_products')->where('order_id', $order->id)->pluck('product_data')->all();

        $this->discount($order, 50.00);
        $this->discount($order->fresh(), 80.00);

        $this->assertSame($before, DB::table('order_products')->where('order_id', $order->id)->pluck('product_data')->all());
    }

    public function test_a_discount_exceeding_the_basis_is_refused(): void
    {
        $order = $this->order(200.00, 19.50, 4.00, 0.00);

        $this->expectException(\App\Services\Discounts\DiscountException::class);
        $this->discount($order, 250.00);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function assertReconciles(Order $order): void
    {
        $c = fn ($v) => (int) round(((float) $v) * 100);

        $this->assertSame(
            $c($order->grand_total),
            $c($order->subtotal) - $c($order->pretax_discount_total)
                + $c($order->tax_amount) + $c($order->special_tax_amount)
                + $c($order->added_fees_amount) - $c($order->discount_amount),
            'grand_total = subtotal − pretax_discount + tax + special_tax + added_fees − discount'
        );
    }

    private function discount(Order $order, float $cumulative): void
    {
        $target = new OrderDiscountTarget($order->id);
        $ref = new \ReflectionClass($target);

        $capture = $ref->getMethod('captureOriginalSnapshotOnce');
        $capture->setAccessible(true);
        $capture->invoke($target);

        $recompute = $ref->getMethod('recompute');
        $recompute->setAccessible(true);
        $recompute->invoke($target, $cumulative);
    }

    /**
     * $mixed splits the basis into one taxable and one tax-free line; otherwise
     * a single line carries everything.
     */
    private function order(float $subtotal, float $tax, float $special, float $fees, bool $mixed = false): Order
    {
        $customer = Customer::factory()->create();

        $order = Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $customer->id,
            'customer_name' => 'Discount Probe',
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'special_tax_amount' => $special,
            'added_fees_amount' => $fees,
            'discount_amount' => 0,
            'pretax_discount_total' => 0,
            'grand_total' => $subtotal + $tax + $special + $fees,
        ]);

        if ($mixed) {
            $this->line($order, $subtotal / 2, $tax, $special, $fees);
            $this->line($order, $subtotal / 2, 0.00, 0.00, 0.00);
        } else {
            $this->line($order, $subtotal, $tax, $special, $fees);
        }

        return $order->fresh();
    }

    private function line(Order $order, float $sub, float $tax, float $special, float $fees): void
    {
        static $n = 0;
        $n++;

        $order->products()->create([
            'unique_id' => 'ORD-DISC-'.$n.'-'.$order->id,
            'product_name' => 'Probe',
            'price' => $sub, 'quantity' => 1,
            'sub_total' => $sub, 'tax' => $tax,
            'special_tax' => $special, 'added_fees' => $fees,
            'total' => $sub + $tax + $special + $fees,
            'product_data' => json_encode([
                'sub_total' => $sub, 'tax' => $tax,
                'special_tax' => $special, 'added_fees' => $fees,
            ]),
        ]);
    }
}
