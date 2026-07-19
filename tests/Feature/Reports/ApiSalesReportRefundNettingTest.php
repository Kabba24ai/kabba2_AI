<?php

namespace Tests\Feature\Reports;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentRefundAllocationStatus;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPaymentRefundAllocation;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payment Architecture Finalization (Phase 4B) — dedicated refund-netting
 * coverage for the three V1 Sales Report API endpoints that previously
 * contained an unreachable (dead) refund branch: getRevenueBreakdown(),
 * getTaxAndPayments(), and getProductSalesDetails(). All three now share
 * the same allocation-aware SQL fragment (HasAllocationAwareRefundSql) and
 * exclude-fully-refunded/ratio-net-partial-refunds shape already proven in
 * Tier 1 (NetsRefundedRevenue) — see SalesReportController's class docblock.
 */
class ApiSalesReportRefundNettingTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private Store $store;
    private Product $product;
    private string $apiBase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiBase = 'http://' . config('app.domains.api') . '/api/sales-reports/v1';

        $this->customer = Customer::create([
            'first_name' => 'Refund', 'last_name' => 'Netting',
            'email' => 'v1-refund-netting@example.com', 'status' => 'Active',
        ]);

        $employee = User::create([
            'first_name' => 'Refund', 'last_name' => 'Clerk',
            'email' => 'v1-refund-netting-clerk@example.com', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);

        $this->store = Store::create(['store_name' => 'V1 Refund Netting Store']);

        $category = ProductCategory::create(['title' => 'V1 Refund Netting Category']);
        $this->product = Product::create([
            'product_name' => 'V1 Refund Netting Product',
            'slug'         => 'v1-refund-netting-product-' . uniqid(),
            'product_type' => 'Retail',
        ]);
        $this->product->categories()->attach($category->id);

        $this->actingAs($employee);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    /** @return array{0: Order, 1: \App\Models\Orders\OrderPayment} */
    private function makePaidOrder(string $orderNumber, float $subtotal, float $tax): array
    {
        $order = Order::create([
            'order_number'  => $orderNumber,
            'order_date'    => now()->toDateString(),
            'customer_id'   => $this->customer->id,
            'customer_name' => 'Refund Netting',
            'subtotal'      => $subtotal,
            'tax_amount'    => $tax,
            'grand_total'   => $subtotal + $tax,
        ]);

        OrderProduct::create([
            'order_id'          => $order->id,
            'product_id'        => $this->product->id,
            'product_name'      => 'V1 Refund Netting Product',
            'price'             => $subtotal,
            'quantity'          => 1,
            'sub_total'         => $subtotal,
            'tax'               => $tax,
            'total'             => $subtotal + $tax,
            'delivery_store_id' => $this->store->id,
            'product_data'      => ['product_type' => 'Retail'],
        ]);

        $payment = $order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount'           => $subtotal + $tax,
            'status'           => OrderPaymentStatus::Paid->value,
        ]);

        return [$order, $payment];
    }

    private function window(): array
    {
        return [now()->subDay()->toDateString(), now()->addDay()->toDateString()];
    }

    private function apiGet(string $path)
    {
        [$start, $end] = $this->window();

        return $this->getJson($this->apiBase . $path . '?' . http_build_query([
            'dateRange' => 'custom', 'startDate' => $start, 'endDate' => $end,
        ]));
    }

    private function revenueTotal(array $breakdown): float
    {
        return array_sum($breakdown);
    }

    private function productRow(array $rows, string $productId): ?array
    {
        return collect($rows)->firstWhere('productId', (string) $productId);
    }

    // ── 1. No-refund baseline ────────────────────────────────────────────

    public function test_unrefunded_order_counts_full_revenue_and_tax(): void
    {
        $this->makePaidOrder('V1-NONE', 1000, 100);

        $revenue = $this->apiGet('/revenue-breakdown')->assertOk()->json();
        $this->assertSame(1000.0, $this->revenueTotal($revenue));

        $tax = $this->apiGet('/tax-and-payments')->assertOk()->json();
        $this->assertSame(100.0, (float) $tax['salesTaxCollected']);
        $this->assertSame(1000.0, (float) $tax['otherPayments'], 'Paid status falls into otherPayments — pre-existing bucketing, unaffected by this phase');

        $products = $this->apiGet('/product-sales-details')->assertOk()->json();
        $row = $this->productRow($products, (string) $this->product->id);
        $this->assertSame(1000.0, (float) $row['grossSales']);
        $this->assertSame(0.0, (float) $row['refundAmount']);
        $this->assertSame(1000.0, (float) $row['netSales']);
    }

    // ── 2. Fully refunded orders — excluded entirely ─────────────────────

    public function test_fully_refunded_order_is_excluded_from_all_three_endpoints(): void
    {
        [$order] = $this->makePaidOrder('V1-FULL', 1000, 100);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(), 'refunded_at' => now(),
            'amount' => 0, 'refund_amount' => 1100,
            'status' => OrderPaymentStatus::Refund->value,
        ]);

        $revenue = $this->apiGet('/revenue-breakdown')->assertOk()->json();
        $this->assertSame(0.0, $this->revenueTotal($revenue), 'a fully-refunded order must contribute zero revenue');

        $tax = $this->apiGet('/tax-and-payments')->assertOk()->json();
        $this->assertSame(0.0, (float) $tax['salesTaxCollected']);
        $this->assertSame(0.0, (float) $tax['otherPayments']);

        $products = $this->apiGet('/product-sales-details')->assertOk()->json();
        $this->assertNull($this->productRow($products, (string) $this->product->id), 'the product line disappears entirely, not merely zeroed');
    }

    // ── 3. Partially refunded orders (legacy, no allocation rows) ───────

    public function test_partial_refund_with_no_allocations_falls_back_to_legacy_refund_amount(): void
    {
        [$order] = $this->makePaidOrder('V1-PARTIAL-LEGACY', 1000, 100);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(), 'refunded_at' => now(),
            'amount' => 0, 'refund_amount' => 300,
            'status' => OrderPaymentStatus::PartialRefund->value,
        ]);

        $revenue = $this->apiGet('/revenue-breakdown')->assertOk()->json();
        $this->assertSame(700.0, round($this->revenueTotal($revenue), 2), '1000 * (1000-300)/1000 = 700');

        $products = $this->apiGet('/product-sales-details')->assertOk()->json();
        $row = $this->productRow($products, (string) $this->product->id);
        $this->assertSame(1000.0, (float) $row['grossSales'], 'gross is unchanged — refund nets out separately');
        $this->assertSame(300.0, round((float) $row['refundAmount'], 2));
        $this->assertSame(700.0, round((float) $row['netSales'], 2));
    }

    // ── 4. Multi-source refunds ──────────────────────────────────────────

    public function test_multi_source_refund_sums_across_both_allocations(): void
    {
        [$order, $payment] = $this->makePaidOrder('V1-MULTI', 1000, 100);
        // Status deliberately NOT 'Paid' — these endpoints' own base WHERE
        // clause (whereIn('order_payments.status', ['Paid', ...])) would
        // otherwise match a second 'Paid' row too and fan out the join,
        // doubling every order_product row. 'Partial Payment' is excluded
        // from that whereIn (same reason split-payment orders don't fan
        // out today), so this second original leg stays invisible to the
        // base query while still being a valid allocation target.
        $payment2 = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(), 'amount' => 0,
            'status' => OrderPaymentStatus::PartialPayment->value,
        ]);

        $refund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(), 'refunded_at' => now(),
            'amount' => 0, 'refund_amount' => 999, 'tax_refunded' => 999,
            'status' => OrderPaymentStatus::PartialRefund->value,
        ]);

        // Two sources, deliberately different from the stale legacy
        // refund_amount (999) above — proves the allocation-aware branch,
        // not the fallback, drives the number.
        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $payment->id,
            'allocated_amount' => 150, 'allocated_base_amount' => 150, 'allocated_tax_amount' => 0,
            'processing_fee_retained' => 0, 'status' => OrderPaymentRefundAllocationStatus::Allocated->value,
        ]);
        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $payment2->id,
            'allocated_amount' => 100, 'allocated_base_amount' => 100, 'allocated_tax_amount' => 0,
            'processing_fee_retained' => 0, 'status' => OrderPaymentRefundAllocationStatus::Allocated->value,
        ]);

        $revenue = $this->apiGet('/revenue-breakdown')->assertOk()->json();
        $this->assertSame(750.0, round($this->revenueTotal($revenue), 2), '1000 * (1000-250)/1000 = 750, not 1000-999');
    }

    // ── 5. Card-fee-retention refunds ────────────────────────────────────

    public function test_card_fee_retention_refund_nets_only_the_customer_facing_amount(): void
    {
        [$order, $payment] = $this->makePaidOrder('V1-FEE', 1000, 100);
        $refund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(), 'refunded_at' => now(),
            'amount' => 0, 'status' => OrderPaymentStatus::PartialRefund->value,
        ]);

        // $220 gross drawn, $6 processing fee retained — net customer-
        // facing refund is $214, and that is what must reduce revenue
        // (the fee itself is retained revenue, not returned to the customer).
        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $payment->id,
            'allocated_amount' => 220, 'allocated_base_amount' => 220, 'allocated_tax_amount' => 0,
            'processing_fee_retained' => 6, 'status' => OrderPaymentRefundAllocationStatus::Allocated->value,
        ]);

        $revenue = $this->apiGet('/revenue-breakdown')->assertOk()->json();
        $this->assertSame(786.0, round($this->revenueTotal($revenue), 2), '1000 * (1000-214)/1000 = 786, not 1000-220=780');
    }

    // ── 6. Sales-tax-only refunds ─────────────────────────────────────────

    public function test_sales_tax_only_refund_reduces_revenue_buckets_by_the_tax_equivalent_amount(): void
    {
        // Documents real, current behavior rather than an idealized one:
        // HasAllocationAwareRefundSql::allocationAwareRefundAmountSql()
        // sums allocated_amount (which for a SalesTaxOnly allocation equals
        // allocated_tax_amount — PaymentAllocationService::
        // calculateAllocationSplits() sets base=0/tax=$amount for this calc
        // type) and nets it against sub_total-basis revenue — exactly the
        // same formula Tier 1's NetsRefundedRevenue already applies
        // unchanged elsewhere. Reproducing rather than special-casing this
        // is deliberate: Phase 4B's mandate is "do not create another
        // refund-netting formula." A tax-basis netting formula (netting
        // sales-tax-only refunds against the `tax` column instead of
        // `sub_total`) would be a genuine improvement but is a new formula
        // and is out of this phase's scope — flagged in the Phase 4B report.
        [$order, $payment] = $this->makePaidOrder('V1-TAXONLY', 1000, 100);
        $refund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(), 'refunded_at' => now(),
            'amount' => 0, 'status' => OrderPaymentStatus::PartialRefund->value,
        ]);

        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $payment->id,
            'allocated_amount' => 50, 'allocated_base_amount' => 0, 'allocated_tax_amount' => 50,
            'processing_fee_retained' => 0, 'status' => OrderPaymentRefundAllocationStatus::Allocated->value,
        ]);

        $revenue = $this->apiGet('/revenue-breakdown')->assertOk()->json();
        $this->assertSame(950.0, round($this->revenueTotal($revenue), 2), '1000 * (1000-50)/1000 = 950 — inherited Tier 1 formula characteristic, not a new bug');
    }

    // ── 7. Pending/failed refund operations ──────────────────────────────

    public function test_pending_and_failed_allocations_never_reduce_completed_totals(): void
    {
        [$order, $payment] = $this->makePaidOrder('V1-PENDINGFAILED', 1000, 100);
        $refund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(), 'refunded_at' => now(),
            'amount' => 0, 'status' => OrderPaymentStatus::PartialRefund->value,
        ]);

        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $payment->id,
            'allocated_amount' => 400, 'allocated_base_amount' => 400, 'allocated_tax_amount' => 0,
            'processing_fee_retained' => 0, 'status' => OrderPaymentRefundAllocationStatus::Failed->value,
        ]);
        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $payment->id,
            'allocated_amount' => 200, 'allocated_base_amount' => 200, 'allocated_tax_amount' => 0,
            'processing_fee_retained' => 0, 'status' => OrderPaymentRefundAllocationStatus::Pending->value,
        ]);

        $revenue = $this->apiGet('/revenue-breakdown')->assertOk()->json();
        $this->assertSame(1000.0, round($this->revenueTotal($revenue), 2), 'no Allocated rows exist — nothing actually moved, full revenue stands');

        $products = $this->apiGet('/product-sales-details')->assertOk()->json();
        $row = $this->productRow($products, (string) $this->product->id);
        $this->assertSame(0.0, round((float) $row['refundAmount'], 2));
    }

    // ── 8. Historical allocation-backfilled orders ───────────────────────

    public function test_backfilled_allocation_rows_net_identically_to_a_live_refund(): void
    {
        // payments:backfill-allocations reconstructs allocation rows for
        // historical refunds exactly like this — a real Allocated row
        // attached to an old refund payment, no different in shape from
        // one created live by RefundPaymentController. Proves the report
        // has no special-cased "was this live or backfilled" logic.
        [$order, $payment] = $this->makePaidOrder('V1-BACKFILLED', 1000, 100);
        $refund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now()->subMonths(6), 'refunded_at' => now()->subMonths(6),
            'amount' => 0, 'refund_amount' => 250,
            'status' => OrderPaymentStatus::PartialRefund->value,
        ]);
        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $payment->id,
            'allocated_amount' => 250, 'allocated_base_amount' => 250, 'allocated_tax_amount' => 0,
            'processing_fee_retained' => 0, 'status' => OrderPaymentRefundAllocationStatus::Allocated->value,
        ]);

        $revenue = $this->apiGet('/revenue-breakdown')->assertOk()->json();
        $this->assertSame(750.0, round($this->revenueTotal($revenue), 2), '1000 * (1000-250)/1000 = 750');
    }

    // ── 9. Split-payment orders ───────────────────────────────────────────

    public function test_split_payment_order_is_not_fanned_out_by_the_refund_netting_join(): void
    {
        $order = Order::create([
            'order_number'  => 'V1-SPLIT',
            'order_date'    => now()->toDateString(),
            'customer_id'   => $this->customer->id,
            'customer_name' => 'Refund Netting',
            'subtotal'      => 1000, 'tax_amount' => 0, 'grand_total' => 1000,
        ]);
        OrderProduct::create([
            'order_id' => $order->id, 'product_id' => $this->product->id,
            'product_name' => 'V1 Refund Netting Product', 'price' => 1000, 'quantity' => 1,
            'sub_total' => 1000, 'tax' => 0, 'total' => 1000,
            'delivery_store_id' => $this->store->id, 'product_data' => ['product_type' => 'Retail'],
        ]);
        // Two settled rows: only the 'Paid' one matches these endpoints'
        // own whereIn (Partial Payment is excluded), so no fan-out — the
        // added refund-netting joins (order_totals/order_refunds, both
        // GROUP BY order_id) must not introduce any new fan-out either.
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now()->subMinute(),
            'amount' => 600, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 400, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $revenue = $this->apiGet('/revenue-breakdown')->assertOk()->json();
        $this->assertSame(1000.0, $this->revenueTotal($revenue), 'must be counted once, not doubled by the join');
    }

    // ── 10. Response-contract preservation ───────────────────────────────

    public function test_response_contracts_are_unchanged_by_the_refund_netting_addition(): void
    {
        [$order, $payment] = $this->makePaidOrder('V1-CONTRACT', 1000, 100);
        $refund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(), 'refunded_at' => now(),
            'amount' => 0, 'status' => OrderPaymentStatus::PartialRefund->value,
        ]);
        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $payment->id,
            'allocated_amount' => 100, 'allocated_base_amount' => 100, 'allocated_tax_amount' => 0,
            'processing_fee_retained' => 0, 'status' => OrderPaymentRefundAllocationStatus::Allocated->value,
        ]);

        $revenue = $this->apiGet('/revenue-breakdown')->assertOk()->json();
        $this->assertSame(
            ['retailSales', 'rentalRevenue', 'deliveryRevenue', 'damageWaiverRevenue',
             'trackInsuranceRevenue', 'prepaidFuelRevenue', 'prepaidCleaningRevenue', 'feesOtherRevenue'],
            array_keys($revenue)
        );

        $tax = $this->apiGet('/tax-and-payments')->assertOk()->json();
        $this->assertSame(
            ['salesTaxCollected', 'cashPayments', 'cardPayments', 'achPayments', 'checkPayments', 'accountPayments', 'otherPayments'],
            array_keys($tax)
        );

        $products = $this->apiGet('/product-sales-details')->assertOk()->json();
        $this->assertSame(
            ['productId', 'productName', 'sku', 'quantitySold', 'grossSales', 'discountAmount', 'netSales',
             'averageSellingPrice', 'refundQuantity', 'refundAmount', 'netQuantitySold', 'taxCollected',
             'itemType', 'rentalUsageQuantity', 'netRentalUsageQuantity'],
            array_keys($products[0]),
            'field set and order preserved — refundQuantity/refundAmount remain present, not renamed or removed'
        );
        $this->assertSame(0, (int) $products[0]['refundQuantity'], 'no per-unit refund-quantity data model exists — documented limitation, unchanged by this phase');
    }
}
