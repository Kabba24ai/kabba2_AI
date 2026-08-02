<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\GoodwillReasonCode;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Http\DataObjects\GoodwillOperationFailure;
use App\Models\Customers\Customer;
use App\Models\Orders\Order;
use App\Models\Orders\OrderGoodwillAdjustment;
use App\Models\Iam\Personnel\User;
use App\Services\Orders\GoodwillAdjustmentService;
use App\Services\Orders\HistoricalTaxBasisResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Goodwill apply() and reverse() — the financial writer.
 *
 * Everything here runs inside one DB::transaction with the order row locked
 * FOR UPDATE, so the checks and the writes cannot be interleaved by a
 * concurrent payment or a second manager. Failures roll back completely:
 * `rollback_leaves_the_existing_payment_unchanged_and_persists_no_adjustment`
 * is the test that proves a refused apply leaves no partial state behind —
 * Goodwill creates no payment of its own, so what it asserts on the money
 * side is that the customer's real tender is untouched.
 */
class GoodwillApplyReverseTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $manager;
    private User $clerk;
    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();
        HistoricalTaxBasisResolver::flushSchemaMemo();

        Permission::findOrCreate('goodwill.apply', 'web');
        Permission::findOrCreate('goodwill.reverse', 'web');

        $this->customer = Customer::factory()->create();

        $this->manager = User::create([
            'first_name' => 'Mona', 'last_name' => 'Manager',
            'email' => 'mona-gw@example.com', 'status' => 'Active',
        ]);
        $this->manager->givePermissionTo('goodwill.apply', 'goodwill.reverse');

        $this->clerk = User::create([
            'first_name' => 'Cal', 'last_name' => 'Clerk',
            'email' => 'cal-gw@example.com', 'status' => 'Active',
        ]);

        $this->productId = (int) DB::table('products')->insertGetId([
            'unique_id' => 'PRD-GW-AR', 'product_name' => 'Writer Test',
            'slug' => 'writer-test', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** $200 + 9.75% = $219.50, one taxable line. */
    private function makeOrder(float $subtotal = 200.00, float $tax = 19.50, array $frozen = []): Order
    {
        $order = Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Writer Customer',
            'subtotal' => $subtotal, 'tax_amount' => $tax,
            'special_tax_amount' => $frozen['special_tax'] ?? 0,
            'added_fees_amount' => $frozen['added_fees'] ?? 0,
            'discount_amount' => 0, 'grand_total' => $subtotal + $tax
                + (float) ($frozen['special_tax'] ?? 0) + (float) ($frozen['added_fees'] ?? 0),
        ]);

        static $n = 0;
        $n++;

        $order->products()->create([
            'unique_id' => 'ORD-GW-AR-'.$n,
            'product_id' => $this->productId,
            'product_name' => 'Writer Test',
            'price' => $subtotal, 'quantity' => 1,
            'sub_total' => $subtotal, 'tax' => $tax, 'total' => $subtotal + $tax,
            'special_tax' => $frozen['special_tax'] ?? 0,
            'added_fees' => $frozen['added_fees'] ?? 0,
            'product_data' => json_encode($frozen + ['special_tax' => 0, 'added_fees' => 0]),
        ]);

        return $order->fresh();
    }

    private function pay(Order $order, float $amount): void
    {
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => $amount,
            'status' => OrderPaymentStatus::PartialPayment->value,
        ]);
    }

    // ── Apply ──────────────────────────────────────────────────────────────

    public function test_successful_apply_closes_the_order_at_the_accepted_amount(): void
    {
        $order = $this->makeOrder();
        $this->pay($order, 185.00);
        $order = $order->fresh();

        $result = GoodwillAdjustmentService::apply(
            $order, 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->clerk,
        );

        $this->assertTrue($result['ok'], 'Apply should succeed.');

        $order->refresh();
        $this->assertSame('168.56', (string) $order->subtotal);
        $this->assertSame('16.44', (string) $order->tax_amount);
        $this->assertSame('185.00', (string) $order->grand_total);

        // Canonical paid status, derived — never assigned.
        $this->assertTrue($order->is_paid);
        $this->assertEqualsWithDelta(0.0, $order->balance_due, 0.001);

        // Goodwill is NOT tender: exactly one payment row, the real one.
        $this->assertSame(1, $order->payments()->count());

        $adj = $result['adjustment'];
        $this->assertSame('31.44', (string) $adj->goodwill_amount);
        $this->assertSame('200.00', (string) $adj->original_subtotal);
        $this->assertSame('219.50', (string) $adj->original_grand_total);
        $this->assertSame($this->manager->id, $adj->approved_by);
        $this->assertSame($this->clerk->id, $adj->performed_by);
    }

    public function test_apply_reduces_line_sub_total_and_leaves_price_untouched(): void
    {
        $order = $this->makeOrder();
        $this->pay($order, 185.00);

        GoodwillAdjustmentService::apply(
            $order->fresh(), 18500, GoodwillReasonCode::EquipmentIssue, null, $this->manager, $this->clerk,
        );

        $line = DB::table('order_products')->where('order_id', $order->id)->first();
        $this->assertSame('168.56', (string) $line->sub_total);
        $this->assertSame('16.44', (string) $line->tax);
        $this->assertSame('200.00', (string) $line->price, 'price is the original unit price and must not be repriced.');
    }

    public function test_partial_payment_short_pay_closes_using_cumulative_payments(): void
    {
        $order = $this->makeOrder();
        $this->pay($order, 100.00);
        $this->pay($order, 85.00);
        $order = $order->fresh();

        $result = GoodwillAdjustmentService::apply(
            $order, 18500, GoodwillReasonCode::CustomerServiceResolution, null, $this->manager, $this->clerk,
        );

        $this->assertTrue($result['ok']);
        $order->refresh();
        $this->assertSame('185.00', (string) $order->grand_total);
        $this->assertTrue($order->is_paid);
        $this->assertSame(2, $order->payments()->count());
    }

    public function test_duplicate_submission_creates_exactly_one_adjustment(): void
    {
        $order = $this->makeOrder();
        $this->pay($order, 185.00);

        $args = [$order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->clerk, 'tok-abc'];

        $first  = GoodwillAdjustmentService::apply(...$args);
        $second = GoodwillAdjustmentService::apply(...[$order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->clerk, 'tok-abc']);

        $this->assertTrue($first['ok']);
        $this->assertTrue($second['ok']);
        $this->assertFalse($first['replayed']);
        $this->assertTrue($second['replayed'], 'The retry must replay, not re-apply.');
        $this->assertSame(1, OrderGoodwillAdjustment::where('order_id', $order->id)->count());
    }

    public function test_second_active_adjustment_is_rejected(): void
    {
        $order = $this->makeOrder();
        $this->pay($order, 185.00);

        GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->clerk);

        $second = GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->clerk);

        $this->assertFalse($second['ok']);
        $this->assertSame(GoodwillOperationFailure::ActiveAdjustmentExists, $second['failure']);
        $this->assertSame(1, OrderGoodwillAdjustment::where('order_id', $order->id)->count());
    }

    public function test_overpayment_is_rejected(): void
    {
        $order = $this->makeOrder();
        $this->pay($order, 219.50);

        $result = GoodwillAdjustmentService::apply($order->fresh(), 21950, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->clerk);

        $this->assertFalse($result['ok']);
        $this->assertSame(GoodwillOperationFailure::CalculationFailed, $result['failure']);
        $this->assertSame(0, OrderGoodwillAdjustment::count());
    }

    public function test_stale_totals_are_rejected(): void
    {
        $order = $this->makeOrder();
        $this->pay($order, 185.00);

        // Caller believes $150 was collected; the order says $185.
        $result = GoodwillAdjustmentService::apply($order->fresh(), 15000, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->clerk);

        $this->assertFalse($result['ok']);
        $this->assertSame(GoodwillOperationFailure::StaleOrderState, $result['failure']);
    }

    public function test_permission_is_enforced_server_side(): void
    {
        $order = $this->makeOrder();
        $this->pay($order, 185.00);

        $result = GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->clerk, $this->clerk);

        $this->assertFalse($result['ok']);
        $this->assertSame(GoodwillOperationFailure::PermissionDenied, $result['failure']);
        $this->assertSame(0, OrderGoodwillAdjustment::count());
    }

    public function test_other_reason_requires_a_note(): void
    {
        $order = $this->makeOrder();
        $this->pay($order, 185.00);

        $result = GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::Other, '  ', $this->manager, $this->clerk);

        $this->assertFalse($result['ok']);
        $this->assertSame(GoodwillOperationFailure::ReasonNoteRequired, $result['failure']);
    }

    // ── Special tax — the capability the new columns unlocked ──────────────

    public function test_special_tax_order_applies_and_updates_every_component_coherently(): void
    {
        // 200.00 basis @ 9.75% = 19.50 ordinary ; special 4.00 (2% of basis)
        // grand total 223.50, of which 200.00 is accepted as payment in full.
        $order = $this->makeOrder(200.00, 19.50, ['special_tax' => 4.00]);
        $this->pay($order, 200.00);

        $result = GoodwillAdjustmentService::apply($order->fresh(), 20000, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->clerk);

        $this->assertTrue($result['ok'], 'Special-tax orders are supported now that the components have columns.');

        $order->refresh();
        $line = $order->products()->firstOrFail();

        // The revised order reconciles from COLUMNS, exactly, and equals what
        // was collected — the whole point of the adjustment.
        $c = fn ($v) => (int) round(((float) $v) * 100);
        $this->assertSame(
            20000,
            $c($order->subtotal) + $c($order->tax_amount) + $c($order->special_tax_amount)
                + $c($order->added_fees_amount) - $c($order->discount_amount),
            'Revised components must sum to the amount accepted as payment in full.'
        );
        $this->assertSame(20000, $c($order->grand_total));
        $this->assertTrue($order->is_paid);

        // Ordinary and special tax both moved, and neither absorbed the other.
        $this->assertLessThan(1950, $c($order->tax_amount));
        $this->assertLessThan(400, $c($order->special_tax_amount));
        $this->assertGreaterThan(0, $c($order->special_tax_amount));

        // The line agrees with the order.
        $this->assertSame($c($order->special_tax_amount), $c($line->special_tax));
        $this->assertSame($c($order->subtotal), $c($line->sub_total));
    }

    public function test_added_fees_are_never_reduced_by_goodwill(): void
    {
        // Added fees are a protected component: a concession on merchandise
        // must not quietly waive a fee that was charged for something else.
        $order = $this->makeOrder(200.00, 19.50, ['added_fees' => 6.00]);
        $this->pay($order, 200.00);

        $before = (string) $order->products()->firstOrFail()->added_fees;

        $result = GoodwillAdjustmentService::apply($order->fresh(), 20000, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->clerk);

        $this->assertTrue($result['ok']);

        $order->refresh();
        $this->assertSame('6.00', (string) $order->added_fees_amount, 'Added fees are protected and never move.');
        $this->assertSame($before, (string) $order->products()->firstOrFail()->added_fees);
    }

    public function test_refund_basis_still_reconstructs_after_special_tax_goodwill(): void
    {
        // The failure this guards against is the reason special tax was
        // refused at all: an adjusted order whose components no longer
        // reconcile can never be refunded again.
        $order = $this->makeOrder(200.00, 19.50, ['special_tax' => 4.00]);
        $this->pay($order, 200.00);

        $this->assertTrue(
            GoodwillAdjustmentService::apply($order->fresh(), 20000, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->clerk)['ok']
        );

        // Reversed first, because an active adjustment deliberately blocks
        // reconstruction; what matters is that the RESTORED order resolves.
        $this->assertTrue(GoodwillAdjustmentService::reverse($order->fresh(), $this->manager, 'Testing')['ok']);

        $basis = HistoricalTaxBasisResolver::resolve($order->fresh());

        $this->assertTrue($basis->succeeded(), $basis->failure?->message() ?? '');
        $this->assertSame(400, $basis->specialTaxCents);
        $this->assertEqualsWithDelta(0.0975, $basis->ordinaryRate(), 0.0000001);
    }

    public function test_reversal_restores_special_tax_columns_from_the_snapshot(): void
    {
        $order = $this->makeOrder(200.00, 19.50, ['special_tax' => 4.00, 'added_fees' => 6.00]);
        $this->pay($order, 200.00);

        GoodwillAdjustmentService::apply($order->fresh(), 20000, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->clerk);

        $this->assertTrue(GoodwillAdjustmentService::reverse($order->fresh(), $this->manager, 'Applied in error')['ok']);

        $order->refresh();
        $line = $order->products()->firstOrFail();

        $this->assertSame('4.00', (string) $order->special_tax_amount);
        $this->assertSame('6.00', (string) $order->added_fees_amount);
        $this->assertSame('4.00', (string) $line->special_tax);
        $this->assertSame('6.00', (string) $line->added_fees);
        $this->assertSame('229.50', (string) $order->grand_total);
    }

    public function test_frozen_product_data_survives_apply_and_reverse_byte_identical(): void
    {
        $order = $this->makeOrder(200.00, 19.50, ['special_tax' => 4.00]);
        $this->pay($order, 200.00);

        $before = DB::table('order_products')->where('order_id', $order->id)->pluck('product_data')->all();

        GoodwillAdjustmentService::apply($order->fresh(), 20000, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->clerk);
        $afterApply = DB::table('order_products')->where('order_id', $order->id)->pluck('product_data')->all();

        GoodwillAdjustmentService::reverse($order->fresh(), $this->manager, 'Testing');
        $afterReverse = DB::table('order_products')->where('order_id', $order->id)->pluck('product_data')->all();

        $this->assertSame($before, $afterApply, 'product_data is the immutable original and must not move with the columns.');
        $this->assertSame($before, $afterReverse);
    }

    /**
     * A refused apply must leave NOTHING behind. Goodwill never creates a
     * payment, so the money side of this assertion is that the customer's
     * existing tender is still there, byte for byte — the failure path must
     * not touch, re-stamp, or reallocate real money on its way out.
     */
    public function test_rollback_leaves_the_existing_payment_unchanged_and_persists_no_adjustment(): void
    {
        $order = $this->makeOrder();
        $this->pay($order, 185.00);

        $before = DB::table('orders')->where('id', $order->id)->first();
        $beforeLine = DB::table('order_products')->where('order_id', $order->id)->first();
        $beforePayments = DB::table('order_payments')->where('order_id', $order->id)->orderBy('id')->get()->toArray();

        // Refused for a reason discovered AFTER the calculation succeeds.
        $result = GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::Other, null, $this->manager, $this->clerk);

        $this->assertFalse($result['ok']);

        // No adjustment persisted, no order mutation persisted.
        $this->assertSame(0, OrderGoodwillAdjustment::count());
        $this->assertEquals($before, DB::table('orders')->where('id', $order->id)->first());
        $this->assertEquals($beforeLine, DB::table('order_products')->where('order_id', $order->id)->first());

        // The pre-existing payment survives unchanged — not merely "still one row".
        $this->assertEquals(
            $beforePayments,
            DB::table('order_payments')->where('order_id', $order->id)->orderBy('id')->get()->toArray(),
            'A refused Goodwill apply must never alter real money.'
        );
    }

    // ── Reverse ────────────────────────────────────────────────────────────

    public function test_reversal_restores_totals_from_the_snapshot_and_reopens_the_balance(): void
    {
        $order = $this->makeOrder();
        $this->pay($order, 185.00);
        GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->clerk);

        $result = GoodwillAdjustmentService::reverse($order->fresh(), $this->manager, 'Applied in error');

        $this->assertTrue($result['ok']);

        $order->refresh();
        $this->assertSame('200.00', (string) $order->subtotal);
        $this->assertSame('19.50', (string) $order->tax_amount);
        $this->assertSame('219.50', (string) $order->grand_total);

        // Balance reopens; the real payment survives untouched.
        $this->assertFalse($order->is_paid);
        $this->assertEqualsWithDelta(34.50, $order->balance_due, 0.001);
        $this->assertSame(1, $order->payments()->count());
        $this->assertEqualsWithDelta(185.00, $order->total_paid, 0.001);

        $line = DB::table('order_products')->where('order_id', $order->id)->first();
        $this->assertSame('200.00', (string) $line->sub_total);
        $this->assertSame('19.50', (string) $line->tax);

        // History preserved, not deleted.
        $adj = OrderGoodwillAdjustment::where('order_id', $order->id)->first();
        $this->assertNotNull($adj->reversed_at);
        $this->assertSame($this->manager->id, $adj->reversed_by);
        $this->assertSame('Applied in error', $adj->reversal_reason);
        $this->assertSame('31.44', (string) $adj->goodwill_amount, 'The original figures must remain intact.');
    }

    public function test_duplicate_reversal_is_rejected(): void
    {
        $order = $this->makeOrder();
        $this->pay($order, 185.00);
        GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->clerk);
        GoodwillAdjustmentService::reverse($order->fresh(), $this->manager, 'First');

        $second = GoodwillAdjustmentService::reverse($order->fresh(), $this->manager, 'Again');

        $this->assertFalse($second['ok']);
        $this->assertSame(GoodwillOperationFailure::AlreadyReversed, $second['failure']);
    }

    public function test_reversal_is_blocked_after_a_later_refund(): void
    {
        $order = $this->makeOrder();
        $this->pay($order, 185.00);
        GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->clerk);

        // A refund sized against the ADJUSTED basis lands afterwards.
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => 50.00,
            'status' => OrderPaymentStatus::PartialRefund->value,
        ]);

        $result = GoodwillAdjustmentService::reverse($order->fresh(), $this->manager, 'Too late');

        $this->assertFalse($result['ok']);
        $this->assertSame(GoodwillOperationFailure::ReversalUnsafe, $result['failure']);

        // Nothing restored.
        $order->refresh();
        $this->assertSame('185.00', (string) $order->grand_total);
    }

    public function test_reversal_permission_is_enforced(): void
    {
        $order = $this->makeOrder();
        $this->pay($order, 185.00);
        GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->clerk);

        $result = GoodwillAdjustmentService::reverse($order->fresh(), $this->clerk, 'No rights');

        $this->assertFalse($result['ok']);
        $this->assertSame(GoodwillOperationFailure::PermissionDenied, $result['failure']);
    }

    public function test_reverse_then_apply_again_is_allowed(): void
    {
        $order = $this->makeOrder();
        $this->pay($order, 185.00);
        GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->clerk);
        GoodwillAdjustmentService::reverse($order->fresh(), $this->manager, 'Redo');

        $again = GoodwillAdjustmentService::apply($order->fresh(), 18500, GoodwillReasonCode::ManagerCourtesy, null, $this->manager, $this->clerk);

        $this->assertTrue($again['ok'], 'A reversed adjustment must not block a new one.');
        $this->assertSame(2, OrderGoodwillAdjustment::where('order_id', $order->id)->count());
    }

    public function test_every_operation_failure_has_a_message(): void
    {
        foreach (GoodwillOperationFailure::cases() as $case) {
            $this->assertNotSame('', trim($case->message()));
        }
    }
}
