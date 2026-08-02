<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\RefundCalculationType;
use App\Models\Customers\Customer;
use App\Models\Orders\Order;
use App\Services\Orders\HistoricalTaxBasisResolver;
use App\Services\Orders\PaymentAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Refund tax allocation after the denominator correction.
 *
 * Before this correction PaymentAllocationService derived its rate as
 * `orders.tax_amount / orders.subtotal`. On an order containing an
 * `is_tax_free_item` product that denominator includes the tax-free amount,
 * so the rate came out too low and every refund on such an order allocated
 * too little to tax and too much to base.
 *
 * The mixed-order test below is the one that would have failed before the
 * fix; the fully-taxable tests exist to prove the fix moved nothing that was
 * already correct.
 */
class RefundTaxBasisRegressionTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();
        HistoricalTaxBasisResolver::flushSchemaMemo();

        $this->customer = Customer::factory()->create();

        $this->productId = (int) DB::table('products')->insertGetId([
            'unique_id'    => 'PRD-REFUND-BASIS',
            'product_name' => 'Basis Test Product',
            'slug'         => 'basis-test-product',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    private function makeOrder(float $subtotal, float $tax, float $grandTotal): Order
    {
        return Order::create([
            'order_date'      => now()->format('Y-m-d'),
            'customer_id'     => $this->customer->id,
            'customer_name'   => 'Refund Basis Customer',
            'subtotal'        => $subtotal,
            'tax_amount'      => $tax,
            'discount_amount' => 0,
            'grand_total'     => $grandTotal,
        ]);
    }

    private function addLine(Order $order, float $subTotal, float $tax): void
    {
        static $n = 0;
        $n++;

        $order->products()->create([
            'unique_id'    => 'ORD-RB-'.$n,
            'product_id'   => $this->productId,
            'product_name' => 'Basis Test Product',
            'price'        => $subTotal,
            'quantity'     => 1,
            'sub_total'    => $subTotal,
            'tax'          => $tax,
            'total'        => $subTotal + $tax,
            'product_data' => json_encode(['special_tax' => 0, 'added_fees' => 0]),
        ]);
    }

    // ── The defect this commit corrects ────────────────────────────────────

    public function test_mixed_taxable_and_tax_free_allocates_refund_tax_on_the_true_basis(): void
    {
        // $100 taxable @ 9.75% = $9.75; $100 tax-free. Subtotal $200, tax $9.75.
        $order = $this->makeOrder(200.00, 9.75, 209.75);
        $this->addLine($order, 100.00, 9.75);
        $this->addLine($order, 100.00, 0.00);

        // Refund the full taxable line, tax-inclusive: $109.75.
        $tax = PaymentAllocationService::proportionalTaxRefund($order->fresh(), 109.75);

        // True rate 9.75% -> 109.75 - (109.75 / 1.0975) = 9.75
        $this->assertEqualsWithDelta(9.75, $tax, 0.01);

        // The old denominator (9.75/200 = 4.875%) would have produced ~5.10.
        $defectiveRate = 9.75 / 200.00;
        $defectiveTax = round(109.75 - (109.75 / (1 + $defectiveRate)), 2);
        $this->assertEqualsWithDelta(5.10, $defectiveTax, 0.01);
        $this->assertNotEqualsWithDelta($defectiveTax, $tax, 0.05, 'Still using the subtotal denominator.');
    }

    // ── Unchanged behavior ─────────────────────────────────────────────────

    public function test_fully_taxable_order_retains_existing_results(): void
    {
        // 1000 @ 9.75% = 97.50 — basis == subtotal, so old and new agree.
        $order = $this->makeOrder(1000.00, 97.50, 1097.50);
        $this->addLine($order, 1000.00, 97.50);

        $tax = PaymentAllocationService::proportionalTaxRefund($order->fresh(), 219.50);

        $this->assertEqualsWithDelta(19.50, $tax, 0.01);
    }

    public function test_zero_tax_order_returns_zero_without_deriving_a_rate(): void
    {
        $order = $this->makeOrder(500.00, 0.00, 500.00);
        $this->addLine($order, 500.00, 0.00);

        $this->assertSame(0.0, PaymentAllocationService::proportionalTaxRefund($order->fresh(), 250.00));
    }

    public function test_partial_refunds_retain_deterministic_cent_allocation(): void
    {
        $order = $this->makeOrder(300.00, 33.33, 333.33);
        $this->addLine($order, 300.00, 33.33);
        $order = $order->fresh();

        $result = PaymentAllocationService::calculateAllocationSplits(
            $order,
            new Collection(),
            [
                ['original_order_payment_id' => 1, 'amount' => 100.01],
                ['original_order_payment_id' => 2, 'amount' => 100.01],
                ['original_order_payment_id' => 3, 'amount' => 133.31],
            ],
            RefundCalculationType::Standard,
        );

        // Remainder-to-last-row must still land the aggregate exactly on a
        // single-shot computation of the same total.
        $expected = PaymentAllocationService::proportionalTaxRefund($order, 333.33);
        $this->assertEqualsWithDelta($expected, $result['total_tax'], 0.001);

        foreach ($result['rows'] as $row) {
            $this->assertEqualsWithDelta($row['amount'], $row['base'] + $row['tax'], 0.001);
        }
    }

    public function test_sales_tax_only_allocation_is_untouched_by_the_correction(): void
    {
        // Sales Tax Only never calls proportionalTaxRefund(), so it must work
        // even on an order whose basis could not be reconstructed at all.
        $order = $this->makeOrder(200.00, 19.50, 219.50); // deliberately no lines
        $order = $order->fresh();

        $result = PaymentAllocationService::calculateAllocationSplits(
            $order,
            new Collection(),
            [['original_order_payment_id' => 1, 'amount' => 19.50]],
            RefundCalculationType::SalesTaxOnly,
        );

        $this->assertCount(1, $result['rows']);
        $this->assertEqualsWithDelta(19.50, $result['rows'][0]['tax'], 0.001);
        $this->assertEqualsWithDelta(0.0, $result['rows'][0]['base'], 0.001);
    }

    // ── Safe failure ───────────────────────────────────────────────────────

    public function test_unreconstructable_history_fails_loudly_without_guessed_arithmetic(): void
    {
        // No lines at all: the basis cannot be reconstructed.
        $order = $this->makeOrder(200.00, 19.50, 219.50)->fresh();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Refusing to derive a rate from orders.subtotal');

        PaymentAllocationService::proportionalTaxRefund($order, 100.00);
    }

    public function test_unreconciled_line_tax_fails_rather_than_allocating(): void
    {
        $order = $this->makeOrder(200.00, 19.50, 219.50);
        $this->addLine($order, 200.00, 18.00); // disagrees with stored tax

        $this->expectException(\InvalidArgumentException::class);

        PaymentAllocationService::proportionalTaxRefund($order->fresh(), 100.00);
    }
}
