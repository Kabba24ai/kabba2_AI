<?php

namespace Tests\Feature\Reports;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Services\Reports\CollectedRevenueQuery;
use App\Services\Reports\SalesReportEngineV2;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Canonical line-attribution decomposition — filtered views are exact
 * partitions of the same payment allocation, never a recalculation.
 *
 * Fixture: one order, two lines —
 *   line A: Rental, $600 sub_total, $60 tax, store 1, category A
 *   line B: Retail, $400 sub_total, $0 tax (NON-TAXABLE), store 2, category B
 *   order: subtotal $1,000, tax $60, grand_total $1,060
 */
class CashBasisLineAttributionTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;
    private Product $productA;
    private Product $productB;
    private ProductCategory $categoryA;
    private ProductCategory $categoryB;

    protected function setUp(): void
    {
        parent::setUp();

        $customer = Customer::create([
            'first_name' => 'Line', 'last_name' => 'Attribution',
            'email' => 'line-attribution@example.com', 'status' => 'Active',
        ]);

        $this->actingAs(User::create([
            'first_name' => 'Line', 'last_name' => 'Clerk',
            'email' => 'line-clerk@example.com', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]));

        $this->categoryA = ProductCategory::create(['title' => 'Line Category A']);
        $this->categoryB = ProductCategory::create(['title' => 'Line Category B']);

        $this->productA = Product::create([
            'product_name' => 'Taxable Rental Line',
            'slug'         => 'taxable-rental-line-' . uniqid(),
            'product_type' => 'Rental',
        ]);
        $this->productA->categories()->attach($this->categoryA->id);

        $this->productB = Product::create([
            'product_name' => 'Nontaxable Retail Line',
            'slug'         => 'nontaxable-retail-line-' . uniqid(),
            'product_type' => 'Retail',
        ]);
        $this->productB->categories()->attach($this->categoryB->id);

        $this->order = Order::create([
            'order_number'  => 'LINES-1',
            'order_date'    => '2026-06-24',
            'customer_id'   => $customer->id,
            'customer_name' => 'Line Attribution',
            'subtotal'      => 1000,
            'tax_amount'    => 60,
            'grand_total'   => 1060,
        ]);

        OrderProduct::create([
            'order_id' => $this->order->id, 'product_id' => $this->productA->id,
            'product_name' => 'Taxable Rental Line',
            'price' => 600, 'quantity' => 1, 'sub_total' => 600, 'tax' => 60, 'total' => 660,
            'delivery_store_id' => 1,
            'product_data' => ['product_type' => 'Rental'],
        ]);
        OrderProduct::create([
            'order_id' => $this->order->id, 'product_id' => $this->productB->id,
            'product_name' => 'Nontaxable Retail Line',
            'price' => 400, 'quantity' => 1, 'sub_total' => 400, 'tax' => 0, 'total' => 400,
            'delivery_store_id' => 2,
            'product_data' => ['product_type' => 'Retail'],
        ]);
    }

    private function juneRows(array $filters = []): \Illuminate\Support\Collection
    {
        return app(CollectedRevenueQuery::class)->rows($filters, '2026-06-01', '2026-06-30');
    }

    private function v2Filters(array $extra = []): array
    {
        return array_merge([
            'date_range' => 'custom', 'start_date' => '2026-06-01', 'end_date' => '2026-07-31',
            'payment_status' => 'paid',
        ], $extra);
    }

    private function payJune530(): void
    {
        // Exactly half the $1,060 order.
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => '2026-06-24 09:00:00',
            'amount'           => 530, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);
    }

    public function test_line_allocations_of_a_partial_payment_sum_exactly_to_the_canonical_payment_row(): void
    {
        $this->payJune530();

        $row = $this->juneRows()->firstWhere('order_id', $this->order->id);
        $this->assertNotNull($row);

        // Canonical payment figures: applied 530; tax = 60 × 530/1060 = 30; base = 500.
        $this->assertSame(530.0, $row->applied);
        $this->assertSame(30.0, $row->tax);
        $this->assertSame(500.0, $row->base);

        // Line partitions sum back to the canonical row EXACTLY — every column.
        $lines = collect($row->lines);
        $this->assertCount(2, $lines);
        foreach (['base', 'tax', 'discount', 'sc_discount'] as $field) {
            $this->assertSame(
                round($row->{$field}, 2),
                round($lines->sum($field), 2),
                "Σ line {$field} must equal the payment's canonical {$field}"
            );
        }

        // Base follows sub-total weights: 600/1000 and 400/1000 of $500.
        $this->assertSame(300.0, $lines[0]->base);
        $this->assertSame(200.0, $lines[1]->base);
    }

    public function test_order_tax_is_never_attributed_to_a_non_taxable_line(): void
    {
        $this->payJune530();

        $lines = collect($this->juneRows()->first()->lines);

        $this->assertSame(30.0, $lines[0]->tax, 'the taxable line carries the payment tax');
        $this->assertSame(0.0, $lines[1]->tax, 'the non-taxable line must receive NO tax');
    }

    public function test_a_later_payment_does_not_restate_earlier_line_allocations(): void
    {
        $this->payJune530();
        $before = collect($this->juneRows()->first()->lines)
            ->map(fn ($l) => [$l->line_id, $l->base, $l->tax, $l->discount])->all();

        // The completing $530 arrives in July.
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => '2026-07-29 10:00:00',
            'amount'           => 530, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $after = collect($this->juneRows()->first()->lines)
            ->map(fn ($l) => [$l->line_id, $l->base, $l->tax, $l->discount])->all();

        $this->assertSame($before, $after, 'June line allocations must be byte-for-byte identical after July is recorded');
    }

    public function test_filtered_totals_roll_up_exactly_to_the_unfiltered_sales_summary(): void
    {
        $this->payJune530();
        // Complete the order in July so the whole $1,060 is in the window.
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => '2026-07-29 10:00:00',
            'amount'           => 530, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $engine = app(SalesReportEngineV2::class);

        $all = $engine->kpis($this->v2Filters());
        $this->assertSame(1000.0, round($all['gross_sales'], 2));
        $this->assertSame(60.0, round($all['tax_collected'], 2));

        // Product rollup: A + B == unfiltered, for gross AND tax.
        $byProductA = $engine->kpis($this->v2Filters(['product' => $this->productA->id]));
        $byProductB = $engine->kpis($this->v2Filters(['product' => $this->productB->id]));
        $this->assertSame(round($all['gross_sales'], 2), round($byProductA['gross_sales'] + $byProductB['gross_sales'], 2));
        $this->assertSame(round($all['tax_collected'], 2), round($byProductA['tax_collected'] + $byProductB['tax_collected'], 2));
        $this->assertSame(0.0, round($byProductB['tax_collected'], 2), 'the non-taxable product reports no tax under its own filter');

        // Category rollup mirrors product rollup (each product has one category).
        $byCatA = $engine->kpis($this->v2Filters(['category' => $this->categoryA->id]));
        $byCatB = $engine->kpis($this->v2Filters(['category' => $this->categoryB->id]));
        $this->assertSame(round($byProductA['gross_sales'], 2), round($byCatA['gross_sales'], 2));
        $this->assertSame(round($all['gross_sales'], 2), round($byCatA['gross_sales'] + $byCatB['gross_sales'], 2));

        // Store rollup: line A is store 1, line B is store 2.
        $store1 = $engine->kpis($this->v2Filters(['store' => 1]));
        $store2 = $engine->kpis($this->v2Filters(['store' => 2]));
        $this->assertSame(round($all['gross_sales'], 2), round($store1['gross_sales'] + $store2['gross_sales'], 2));
        $this->assertSame(round($all['tax_collected'], 2), round($store1['tax_collected'] + $store2['tax_collected'], 2));
    }

    public function test_discounts_and_store_credit_follow_the_same_line_allocation_and_stay_additive(): void
    {
        // Separate order with a $50 order-level discount: $1,000 + $60 − $50 = $1,010.
        $order = Order::create([
            'order_number'  => 'LINES-2',
            'order_date'    => '2026-06-24',
            'customer_id'   => $this->order->customer_id,
            'customer_name' => 'Line Attribution',
            'subtotal'        => 1000,
            'tax_amount'      => 60,
            'discount_amount' => 50,
            'grand_total'     => 1010,
        ]);
        OrderProduct::create([
            'order_id' => $order->id, 'product_id' => $this->productA->id,
            'product_name' => 'Taxable Rental Line',
            'price' => 600, 'quantity' => 1, 'sub_total' => 600, 'tax' => 60, 'total' => 660,
            'product_data' => ['product_type' => 'Rental'],
        ]);
        OrderProduct::create([
            'order_id' => $order->id, 'product_id' => $this->productB->id,
            'product_name' => 'Nontaxable Retail Line',
            'price' => 400, 'quantity' => 1, 'sub_total' => 400, 'tax' => 0, 'total' => 400,
            'product_data' => ['product_type' => 'Retail'],
        ]);

        // Store-credit ledger entry: $20 applied to this order.
        DB::table('product_discounts')->insert([
            'discount_type'              => 'store_credit',
            'target_type'                => 'order',
            'target_id'                  => $order->id,
            'status'                     => 'applied',
            'calculated_discount_amount' => 20,
            'tax_before'                 => 60,
            'tax_after'                  => 59,
            'created_at'                 => now(),
            'updated_at'                 => now(),
        ]);

        // Half the order: $505.
        $order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => '2026-06-24 09:00:00',
            'amount'           => 505, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);

        $row   = $this->juneRows()->firstWhere('order_id', $order->id);
        $lines = collect($row->lines);

        // Canonical: discount = 50 × 505/1010 = 25; sc_discount = 20 × 505/1010 = 10.
        $this->assertSame(25.0, $row->discount);
        $this->assertSame(10.0, $row->sc_discount);

        // Line partitions by sub-total weight, additive to the payment figures.
        $this->assertSame([15.0, 10.0], [$lines[0]->discount, $lines[1]->discount]);
        $this->assertSame(round($row->discount, 2), round($lines->sum('discount'), 2));
        $this->assertSame([6.0, 4.0], [$lines[0]->sc_discount, $lines[1]->sc_discount]);
        $this->assertSame(round($row->sc_discount, 2), round($lines->sum('sc_discount'), 2));
    }
}
