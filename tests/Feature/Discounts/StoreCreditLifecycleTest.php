<?php

namespace Tests\Feature\Discounts;

use App\Enums\Discounts\DiscountTargetType;
use App\Models\Customers\Customer;
use App\Models\Discounts\ProductDiscount;
use App\Models\Orders\Order;
use App\Services\Discounts\DiscountApplicationService;
use App\Services\Discounts\PretaxDiscountAllocator;
use App\Services\Discounts\Targets\OrderDiscountTarget;
use App\Services\Orders\PaymentAllocationService;
use App\Services\ReceiptService;
use App\Services\Reports\ProductSalesPerformanceEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Store Credit end-to-end through the shared pre-tax engine.
 *
 * The unit suites prove each mechanism alone. This proves they COMPOSE — that
 * an order can be discounted, stacked, partly reversed, receipted, reported
 * and refunded in sequence without any step leaving the next unable to
 * reconcile.
 *
 * Everything runs through the REAL DiscountApplicationService, not the target
 * directly, so the contract between DiscountCalculator and OrderDiscountTarget
 * is exercised on every apply.
 *
 * No Goodwill code exists yet and none is referenced here.
 */
class StoreCreditLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private DiscountApplicationService $service;
    private Customer $customer;
    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DiscountApplicationService::class);
        $this->customer = Customer::factory()->create();

        $this->productId = (int) DB::table('products')->insertGetId([
            'unique_id' => 'PRD-SCLIFE-1', 'product_name' => 'Lifecycle Probe',
            'slug' => 'sc-lifecycle-probe', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->grantCredit(1000.00);
    }

    // ── 1–4: the four component shapes ─────────────────────────────────────

    public function test_1_ordinary_taxable_order(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);

        $this->applyCredit($order, 50.00);

        $order->refresh();
        $this->assertSame('200.00', (string) $order->subtotal, 'Gross never moves.');
        $this->assertSame('50.00', (string) $order->pretax_discount_total);
        $this->assertSame('14.63', (string) $order->tax_amount);
        $this->assertReconciles($order);
    }

    public function test_2_mixed_taxable_and_tax_free_merchandise(): void
    {
        $order = $this->order([
            ['sub' => 100.00, 'tax' => 9.75],
            ['sub' => 100.00, 'tax' => 0.00],
        ]);

        $this->applyCredit($order, 50.00);

        $order->refresh();
        $this->assertSame('7.31', (string) $order->tax_amount);

        $lines = $order->products()->orderBy('id')->get();
        $this->assertSame('0.00', (string) $lines[1]->tax, 'A tax-free line stays tax-free.');
        $this->assertSame('25.00', (string) $lines[1]->pretax_discount_allocated, '…but still bears its share.');
        $this->assertReconciles($order);
    }

    public function test_3_special_tax_is_reduced_proportionally(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00]]);

        $this->applyCredit($order, 50.00);

        $order->refresh();
        $this->assertSame('3.00', (string) $order->special_tax_amount, '150.00 x 2% — the corrected defect.');
        $this->assertReconciles($order);
    }

    public function test_4_added_fees_are_preserved(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00, 'fees' => 6.00]]);

        $this->applyCredit($order, 50.00);

        $order->refresh();
        $this->assertSame('6.00', (string) $order->added_fees_amount, 'Flat fees never scale.');
        $this->assertSame('3.00', (string) $order->special_tax_amount);
        $this->assertReconciles($order);
    }

    // ── 5–9: stacking, reversal, allocations ───────────────────────────────

    public function test_5_two_stacked_adjustments(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00, 'fees' => 6.00]]);

        $this->applyCredit($order, 30.00);
        $this->applyCredit($order, 20.00);

        $order->refresh();
        $this->assertSame('50.00', (string) $order->pretax_discount_total);
        $this->assertSame('14.63', (string) $order->tax_amount);
        $this->assertSame('3.00', (string) $order->special_tax_amount);
        $this->assertReconciles($order);
        $this->assertSame(5000, PretaxDiscountAllocator::allocatedCents($order));
    }

    public function test_6_reversing_only_the_second_adjustment(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $first = $this->applyCredit($order, 30.00);
        $second = $this->applyCredit($order, 20.00);

        $this->service->reverseStoreCredit($second->fresh(), null, 'test');

        $order->refresh();
        $this->assertSame('30.00', (string) $order->pretax_discount_total, 'Only the first survives.');
        $this->assertSame(3000, PretaxDiscountAllocator::allocatedCents($order));
        $this->assertReconciles($order);

        // The first adjustment's rows are untouched and still active.
        $this->assertSame(1, DB::table('order_product_discount_allocations')
            ->where('product_discount_id', $first->id)->whereNull('reversed_at')->count());
    }

    public function test_7_reversing_the_first_while_the_second_remains(): void
    {
        // Order-independence is the point: reversal is recomputation from the
        // immutable snapshot, not a replay of deltas, so adjustments may be
        // withdrawn in any order.
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $first = $this->applyCredit($order, 30.00);
        $this->applyCredit($order, 20.00);

        $this->service->reverseStoreCredit($first->fresh(), null, 'test');

        $order->refresh();
        $this->assertSame('20.00', (string) $order->pretax_discount_total);
        $this->assertSame(2000, PretaxDiscountAllocator::allocatedCents($order));
        $this->assertReconciles($order);
    }

    public function test_8_full_reversal_restores_the_before_discount_values(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00, 'fees' => 6.00]]);
        $before = DB::table('orders')->where('id', $order->id)->first();

        $a = $this->applyCredit($order, 30.00);
        $b = $this->applyCredit($order, 20.00);

        $this->service->reverseStoreCredit($a->fresh(), null, 'test');
        $this->service->reverseStoreCredit($b->fresh(), null, 'test');

        $after = DB::table('orders')->where('id', $order->id)->first();

        $this->assertSame($before->tax_amount, $after->tax_amount);
        $this->assertSame($before->special_tax_amount, $after->special_tax_amount);
        $this->assertSame($before->added_fees_amount, $after->added_fees_amount);
        $this->assertSame($before->grand_total, $after->grand_total);
        $this->assertSame('0.00', (string) $after->pretax_discount_total);
        $this->assertSame(0, PretaxDiscountAllocator::allocatedCents($order->fresh()));
    }

    public function test_9_allocations_match_the_active_adjustment_set(): void
    {
        $order = $this->order([['sub' => 150.00, 'tax' => 14.63], ['sub' => 50.00, 'tax' => 4.87]]);
        $a = $this->applyCredit($order, 40.00);
        $this->applyCredit($order, 60.00);

        $this->service->reverseStoreCredit($a->fresh(), null, 'test');

        $this->assertNull(PretaxDiscountAllocator::reconcile($order->fresh()));

        $lines = $order->products()->orderBy('id')->get();
        $this->assertSame('45.00', (string) $lines[0]->pretax_discount_allocated);
        $this->assertSame('15.00', (string) $lines[1]->pretax_discount_allocated);
    }

    // ── 10–12: reporting, receipts, refunds ────────────────────────────────

    public function test_10_product_reports_reflect_tracked_net_revenue(): void
    {
        // Discount FIRST, then pay the reduced total. A fully-paid order is
        // not discountable (see test_a_fully_paid_order_cannot_be_discounted),
        // so this is the only realistic ordering.
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->applyCredit($order, 50.00);
        $this->pay($order->fresh(), (float) $order->fresh()->grand_total);

        $engine = app(ProductSalesPerformanceEngine::class);
        $kpis = $engine->buildKpis($this->reportFilters());

        $this->assertSame(15000, (int) round(((float) $kpis['total_revenue']) * 100), '200.00 gross − 50.00 allocated.');
        $this->assertSame(0.0, $engine->legacyUnallocatedDiscount($this->reportFilters()));
    }

    public function test_11_receipts_reflect_the_current_discounted_totals(): void
    {
        // Production's receipt model refreshes a receipt's frozen totals when
        // the order's canonical figures move. A Store Credit discount is
        // exactly such a move.
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 100.00);   // partial — the order stays discountable

        $receipt = ReceiptService::getOrCreateReceipt($order->fresh());
        $this->assertSame('219.50', (string) $receipt->total);

        $this->applyCredit($order->fresh(), 50.00);

        $refreshed = ReceiptService::getOrCreateReceipt($order->fresh());
        $this->assertSame($receipt->id, $refreshed->id, 'Same document, refreshed in place.');
        $this->assertSame('164.63', (string) $refreshed->total);
        $this->assertSame('14.63', (string) $refreshed->sales_tax);
        $this->assertSame('200.00', (string) $refreshed->subtotal, 'Receipt subtotal remains the gross merchandise figure.');
    }

    public function test_12_refund_ceiling_and_tax_after_store_credit(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->applyCredit($order, 50.00);

        $order->refresh();
        $this->assertSame('164.63', (string) $order->grand_total);

        // The customer pays the REDUCED total. That is the refund ceiling.
        $this->pay($order, 164.63);
        $order->refresh();

        $this->assertEqualsWithDelta(164.63, (float) $order->total_paid, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $order->balance_due, 0.001);

        // Refund tax derives from the order's CURRENT stored figures, so it
        // follows the reduced tax rather than the original.
        $tax = PaymentAllocationService::proportionalTaxRefund($order, 164.63);
        $this->assertGreaterThan(0, $tax);
        $this->assertLessThan(19.50, $tax, 'Refundable tax follows the reduced tax.');
    }

    public function test_a_fully_paid_order_cannot_be_discounted(): void
    {
        // A production rule worth stating explicitly, because it constrains
        // what any future concession feature can do: OrderDiscountTarget
        // refuses an order with no remaining balance. A concession applied
        // AFTER full payment would create an overpayment rather than reduce
        // an obligation, which is a refund, not a discount.
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 219.50);

        $this->expectException(\App\Services\Discounts\DiscountException::class);
        $this->expectExceptionMessage('no remaining balance');

        $this->applyCredit($order->fresh(), 50.00);
    }

    // ── 13–15: legacy orders ───────────────────────────────────────────────

    public function test_13_a_legacy_discounted_order_reconciles(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $order->update([
            'pretax_discount_total' => 50.00,
            'legacy_unallocated_pretax_discount' => 50.00,
        ]);

        $this->assertNull(PretaxDiscountAllocator::reconcile($order->fresh()));
        $this->assertSame(0, PretaxDiscountAllocator::allocatedCents($order->fresh()));
    }

    public function test_14_a_new_adjustment_on_a_legacy_order(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $order->update([
            'pretax_discount_total' => 50.00,
            'legacy_unallocated_pretax_discount' => 50.00,
            'tax_amount_before_discount' => 19.50,
            'special_tax_before_discount' => 0,
            'grand_total_before_discount' => 219.50,
            'tax_amount' => 14.63,
            'grand_total' => 164.63,
        ]);

        $this->applyCredit($order->fresh(), 30.00);

        $order->refresh();
        $this->assertSame('80.00', (string) $order->pretax_discount_total);
        $this->assertNull(PretaxDiscountAllocator::reconcile($order), 'legacy 5000c + tracked 3000c = 8000c.');
        $this->assertSame(3000, PretaxDiscountAllocator::allocatedCents($order));
        $this->assertSame('30.00', (string) $order->products()->firstOrFail()->pretax_discount_allocated,
            'The line carries only the tracked share.');
    }

    public function test_15_reversing_the_new_adjustment_leaves_the_legacy_amount(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $order->update([
            'pretax_discount_total' => 50.00,
            'legacy_unallocated_pretax_discount' => 50.00,
            'tax_amount_before_discount' => 19.50,
            'special_tax_before_discount' => 0,
            'grand_total_before_discount' => 219.50,
            'tax_amount' => 14.63,
            'grand_total' => 164.63,
        ]);

        $d = $this->applyCredit($order->fresh(), 30.00);
        $this->service->reverseStoreCredit($d->fresh(), null, 'test');

        $order->refresh();
        $this->assertSame('50.00', (string) $order->pretax_discount_total);
        $this->assertSame(5000, PretaxDiscountAllocator::legacyUnallocatedCents($order), 'Untouched.');
        $this->assertSame(0, PretaxDiscountAllocator::allocatedCents($order));
        $this->assertNull(PretaxDiscountAllocator::reconcile($order));
    }

    // ── 16: idempotency, and the calculator/writer contract ────────────────

    public function test_16_a_retried_apply_is_idempotent(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);

        $key = 'sc-lifecycle-idem-1';
        $first = $this->service->applyStoreCredit(DiscountTargetType::Order, $order->id, 25.00, $key, null, null, null, $this->customer->id);
        $again = $this->service->applyStoreCredit(DiscountTargetType::Order, $order->id, 25.00, $key, null, null, null, $this->customer->id);

        $this->assertSame($first->id, $again->id, 'Same operation, same row.');
        $this->assertSame(1, ProductDiscount::where('target_id', $order->id)->count());

        $order->refresh();
        $this->assertSame('25.00', (string) $order->pretax_discount_total, 'Applied once, not twice.');
        $this->assertSame(2500, PretaxDiscountAllocator::allocatedCents($order));
    }

    public function test_preview_and_apply_produce_identical_totals(): void
    {
        // A preview must never show a figure the writer then recomputes
        // differently — they share computeTotals().
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00, 'fees' => 6.00]]);

        $preview = (new OrderDiscountTarget($order->id))->previewTotals(50.00);

        $this->applyCredit($order, 50.00);
        $order->refresh();

        $c = fn ($v) => (int) round(((float) $v) * 100);
        $this->assertSame($preview['tax'], $c($order->tax_amount));
        $this->assertSame($preview['special_tax'], $c($order->special_tax_amount));
        $this->assertSame($preview['grand_total'], $c($order->grand_total));
        $this->assertSame($preview['discount'], $c($order->pretax_discount_total));
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

        $this->assertNull(PretaxDiscountAllocator::reconcile($order), 'Allocations must reconcile.');
    }

    private function applyCredit(Order $order, float $amount): ProductDiscount
    {
        static $n = 0;
        $n++;

        return $this->service->applyStoreCredit(
            DiscountTargetType::Order, $order->id, $amount,
            'sc-life-'.$order->id.'-'.$n, null, 'lifecycle', null, $this->customer->id,
        );
    }

    private function grantCredit(float $amount): void
    {
        \App\Models\Customers\CustomerCredit::create([
            'customer_id' => $this->customer->id,
            'type' => 'grant',
            'amount' => $amount,
            'reason' => 'lifecycle seed',
        ]);
    }

    private function pay(Order $order, float $amount): void
    {
        $order->payments()->create([
            'payment_method' => \App\Enums\Orders\OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => $amount,
            'status' => \App\Enums\Orders\OrderPaymentStatus::Paid->value,
        ]);
    }

    /** @return array<string,mixed> */
    private function reportFilters(): array
    {
        return ['start_date' => now()->subYear()->toDateString(), 'end_date' => now()->addYear()->toDateString()];
    }

    /** @param array<int,array<string,float>> $lines */
    private function order(array $lines): Order
    {
        $sub = array_sum(array_column($lines, 'sub'));
        $tax = array_sum(array_column($lines, 'tax'));
        $special = array_sum(array_map(fn ($l) => $l['special'] ?? 0, $lines));
        $fees = array_sum(array_map(fn ($l) => $l['fees'] ?? 0, $lines));

        $order = Order::create([
            'order_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->full_name,
            'subtotal' => $sub, 'tax_amount' => $tax,
            'special_tax_amount' => $special, 'added_fees_amount' => $fees,
            'discount_amount' => 0, 'pretax_discount_total' => 0,
            'grand_total' => $sub + $tax + $special + $fees,
        ]);

        foreach ($lines as $i => $l) {
            $order->products()->create([
                'unique_id' => 'ORD-SCLIFE-'.$order->id.'-'.$i,
                'product_id' => $this->productId,
                'product_name' => 'Lifecycle Probe',
                'price' => $l['sub'], 'quantity' => 1,
                'sub_total' => $l['sub'], 'tax' => $l['tax'],
                'special_tax' => $l['special'] ?? 0, 'added_fees' => $l['fees'] ?? 0,
                'total' => $l['sub'] + $l['tax'] + ($l['special'] ?? 0) + ($l['fees'] ?? 0),
                'product_data' => json_encode([
                    'sub_total' => $l['sub'], 'tax' => $l['tax'],
                    'special_tax' => $l['special'] ?? 0, 'added_fees' => $l['fees'] ?? 0,
                ]),
            ]);
        }

        return $order->fresh();
    }
}
