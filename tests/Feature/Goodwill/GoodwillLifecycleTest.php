<?php

namespace Tests\Feature\Goodwill;

use App\Enums\Discounts\DiscountTargetType;
use App\Enums\Discounts\DiscountType;
use App\Enums\Goodwill\GoodwillReason;
use App\Enums\Goodwill\GoodwillReasonCategory;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Customers\CustomerCredit;
use App\Models\Discounts\ProductDiscount;
use App\Models\Goodwill\OrderGoodwillAdjustment;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\Discounts\DiscountApplicationService;
use App\Services\Discounts\PretaxAdjustmentPresenter;
use App\Services\Discounts\PretaxDiscountAllocator;
use App\Services\Goodwill\GoodwillAdjustmentService;
use App\Services\Goodwill\GoodwillApplyRequest;
use App\Services\Goodwill\GoodwillException;
use App\Services\Goodwill\GoodwillFailure;
use App\Services\Goodwill\GoodwillPermissions;
use App\Services\Orders\PaymentAllocationService;
use App\Services\ReceiptService;
use Database\Seeders\Iam\GoodwillPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Increment G4 — the whole feature, end to end, against the DEPLOYED shared
 * pre-tax engine.
 *
 * The unit and service suites prove each mechanism alone. This proves they
 * COMPOSE: that an order can be part-paid, conceded, receipted, reported,
 * refunded and reversed in sequence without any step leaving the next unable
 * to reconcile — and that every refusal still refuses when reached through the
 * real workflow rather than in isolation.
 *
 * THE INVARIANT every settling scenario checks:
 *
 *     revised grand_total + rounding_residual == settled payments
 *
 * with gross merchandise untouched, the allocation ledger reconciling, and no
 * payment row created.
 *
 * No new behaviour is introduced here. Where current behaviour is known to be
 * wrong — the refund tax basis — the test PINS it and says so, rather than
 * asserting a correctness this codebase does not yet have.
 */
class GoodwillLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private GoodwillAdjustmentService $goodwill;

    private DiscountApplicationService $discounts;

    private Customer $customer;

    private User $manager;

    private int $productId;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->goodwill = app(GoodwillAdjustmentService::class);
        $this->discounts = app(DiscountApplicationService::class);
        $this->customer = Customer::factory()->create();

        $this->productId = (int) DB::table('products')->insertGetId([
            'unique_id' => 'PRD-GWLIFE-1', 'product_name' => 'Goodwill Lifecycle Probe',
            'slug' => 'gw-lifecycle-probe', 'created_at' => now(), 'updated_at' => now(),
        ]);

        (new GoodwillPermissionSeeder())->run();

        $this->manager = $this->user('gw-life-manager@test.local', [
            GoodwillPermissions::APPLY, GoodwillPermissions::REVERSE,
        ]);
    }

    // ── 1–5. The five merchandise shapes ───────────────────────────────────

    public function test_1_ordinary_taxable_partial_payment(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 175.00);

        $adjustment = $this->grant($order);
        $order->refresh();

        $this->assertSame('40.55', (string) $adjustment->concessionAmount());
        $this->assertSame('175.00', (string) $order->grand_total);
        $this->assertSame('200.00', (string) $order->subtotal, 'Gross merchandise never moves.');
        $this->assertSettled($order);
    }

    public function test_2_zero_tax_order(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 0.00]]);
        $this->pay($order, 150.00);

        $this->grant($order);
        $order->refresh();

        // With no tax, the concession is exactly the shortfall.
        $this->assertSame('50.00', (string) $order->pretax_discount_total);
        $this->assertSame('0.00', (string) $order->tax_amount);
        $this->assertSettled($order);
    }

    public function test_3_mixed_taxable_and_tax_free_merchandise(): void
    {
        $order = $this->order([
            ['sub' => 100.00, 'tax' => 9.75],
            ['sub' => 100.00, 'tax' => 0.00],
        ]);
        $this->pay($order, 200.00);

        $this->grant($order);
        $order->refresh();

        $lines = $order->products()->orderBy('id')->get();
        $this->assertSame('0.00', (string) $lines[1]->tax, 'A tax-free line stays tax-free…');
        $this->assertGreaterThan(0, (float) $lines[1]->pretax_discount_allocated, '…but still bears its share.');
        $this->assertSettled($order);
    }

    public function test_4_special_tax_moves_with_the_basis(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00]]);
        $this->pay($order, 200.00);

        $before = (float) $order->special_tax_amount;
        $this->grant($order);
        $order->refresh();

        $this->assertLessThan($before, (float) $order->special_tax_amount, 'Special tax is basis-derived.');
        $this->assertSettled($order);
    }

    public function test_5_added_fees_are_protected(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00, 'fees' => 6.00]]);
        $this->pay($order, 200.00);

        $this->grant($order);
        $order->refresh();

        $this->assertSame('6.00', (string) $order->added_fees_amount, 'Flat fees never scale.');
        $this->assertSettled($order);
    }

    // ── 6–7. Stacking with Store Credit ────────────────────────────────────

    public function test_6_store_credit_then_goodwill(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00, 'fees' => 6.00]]);
        $this->applyStoreCredit($order, 40.00);
        $order->refresh();

        $this->pay($order, (float) $order->grand_total - 25.00);
        $adjustment = $this->grant($order);
        $order->refresh();

        $this->assertSame(
            round(40.00 + (float) $adjustment->concessionAmount(), 2),
            round((float) $order->pretax_discount_total, 2),
            'Both concessions accumulate in one pre-tax total.'
        );
        $this->assertSame(2, $this->activeDiscounts($order)->count());
        $this->assertSettled($order);
    }

    public function test_7_reversing_goodwill_leaves_store_credit_intact(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00, 'fees' => 6.00]]);
        $this->applyStoreCredit($order, 40.00);
        $order->refresh();

        $afterCredit = $this->snapshot($order);

        $this->pay($order, (float) $order->grand_total - 25.00);
        $adjustment = $this->grant($order);

        $this->goodwill->reverse($adjustment->fresh(), $this->manager, 'Customer disputed the resolution.');
        $order->refresh();

        $this->assertSame($afterCredit, $this->snapshot($order), 'Exactly back to the Store-Credit-only position.');

        $survivors = $this->activeDiscounts($order);
        $this->assertCount(1, $survivors);
        $this->assertSame(DiscountType::StoreCredit, $survivors->first()->discount_type);

        $this->assertNull(PretaxDiscountAllocator::reconcile($order));
        $this->assertGreaterThan(0, (float) $order->balance_due, 'Reversal re-opens the balance.');
    }

    // ── 8. The receipt label ───────────────────────────────────────────────

    public function test_8_the_receipt_names_goodwill_from_the_central_mapping(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 175.00);
        $this->grant($order);

        $html = $this->renderReceipt($order->fresh());

        // The label as rendered…
        $this->assertStringContainsString('Goodwill - Pre-Tax', $html);
        $this->assertStringNotContainsString('Pre-Tax Discounts', $html, 'Not the mixed-type generic.');
        $this->assertStringNotContainsString('Store Credit', $html);

        // …and the label the enum defines. If these ever diverge, one of them
        // is a hard-code.
        $this->assertStringContainsString(DiscountType::Goodwill->receiptLabel(), $html);
        $this->assertSame(DiscountType::Goodwill->receiptLabel(), PretaxAdjustmentPresenter::label($order->fresh()));
    }

    public function test_8b_the_receipt_template_hard_codes_no_adjustment_label(): void
    {
        // THE RELEASE GATE. The two assertions above would both pass against a
        // Blade template that happened to hard-code the same words. This is the
        // structural proof: the template contains no adjustment label at all,
        // only the presenter call that resolves one.
        $template = file_get_contents(
            resource_path('views/admin/order_management/orders/print_receipt.blade.php')
        );

        $this->assertStringContainsString('PretaxAdjustmentPresenter::label($order)', $template);

        foreach ([
            'Goodwill - Pre-Tax', 'Store Credit - Pre-Tax', 'General Discount - Pre-Tax',
        ] as $label) {
            $this->assertStringNotContainsString(
                $label,
                $template,
                "print_receipt.blade.php hard-codes '{$label}'. Labels belong to DiscountType::receiptLabel()."
            );
        }

        // Every case is reachable through the presenter, so adding a type
        // cannot silently leave the receipt unable to name it.
        foreach (DiscountType::cases() as $case) {
            $this->assertNotSame('', trim($case->receiptLabel()));
        }
    }

    // ── 9. Refund after Goodwill ───────────────────────────────────────────

    public function test_9_a_refund_after_goodwill_splits_at_the_true_taxable_basis(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 175.00);
        $this->grant($order);
        $order->refresh();

        // The order is settled at 175.00 against 175.00 collected.
        $this->assertSame('175.00', (string) $order->grand_total);
        $this->assertSame(175.00, round((float) $order->remaining_amount, 2), 'Fully refundable.');

        // ── THE CORRECTED SPLIT ────────────────────────────────────────────
        //
        // The rate comes from the SURVIVING taxable merchandise, not from gross
        // `subtotal`. Both were wrong before: the denominator was gross while
        // the numerator had already been reduced by the concession.
        $refunded = 100.00;
        $tax = PaymentAllocationService::proportionalTaxRefund($order, $refunded);

        $trueBasis = (float) $order->subtotal - (float) $order->pretax_discount_total;
        $trueRate = (float) $order->tax_amount / $trueBasis;
        $oldRate = (float) $order->tax_amount / (float) $order->subtotal;

        $this->assertEqualsWithDelta(round($refunded - ($refunded / (1 + $trueRate)), 2), $tax, 0.01);

        // The original 9.75% is recovered exactly — a proportional concession
        // preserves the effective rate, which is why this is a reconstruction
        // rather than an estimate.
        $this->assertEqualsWithDelta(0.0975, $trueRate, 0.0002);

        // And the correction is real, not cosmetic.
        $this->assertGreaterThan(
            round($refunded - ($refunded / (1 + $oldRate)), 2),
            $tax,
            'The corrected split recovers more tax than the gross-subtotal formula did.'
        );

        // Unchanged by the fix, and it must stay that way: the customer's TOTAL
        // refund was never wrong, only its base/tax split.
        $this->assertSame(175.00, round((float) $order->total_paid, 2), 'The payment itself is untouched.');
    }

    // ── 10–12. Residuals ───────────────────────────────────────────────────

    public function test_10_exact_close_records_a_zero_residual(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 175.00);

        $this->assertSame('0.00', (string) $this->grant($order)->rounding_residual);
        $this->assertSettled($order->fresh());
    }

    public function test_11_a_one_cent_residual_is_recorded_not_absorbed(): void
    {
        // $229.50 due, $229.44 collected. The tax rounding boundary skips
        // $229.44 entirely; the closest reachable total is $229.43.
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00, 'fees' => 6.00]]);
        $this->pay($order, 229.44);

        $adjustment = $this->grant($order);
        $order->refresh();

        $this->assertSame('0.01', (string) $adjustment->rounding_residual);
        $this->assertSame('229.43', (string) $order->grand_total);
        $this->assertSame('0.06', (string) $adjustment->concessionAmount(), 'Not folded into the concession.');
        $this->assertSettled($order);
    }

    public function test_12_a_two_cent_residual_is_recorded_not_absorbed(): void
    {
        // Both basis-derived taxes round down on the same cent, so the total
        // steps by three and skips two reachable values.
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00, 'fees' => 6.00]]);
        $this->pay($order, 229.22);

        $adjustment = $this->grant($order);
        $order->refresh();

        $this->assertSame('0.02', (string) $adjustment->rounding_residual);
        $this->assertSame('229.20', (string) $order->grand_total);
        $this->assertSettled($order);
    }

    // ── 13–18. Every refusal, reached through the real workflow ────────────

    public function test_13_no_payment_is_refused(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);

        $this->assertRefusal(GoodwillFailure::NoSettledPayment, $order);
    }

    public function test_14_a_fully_paid_order_is_refused(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 219.50);

        $this->assertRefusal(GoodwillFailure::AlreadyPaidInFull, $order);
    }

    public function test_15_an_order_posted_to_the_credit_account_is_refused(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 175.00);

        CustomerAccount::create([
            'customer_id' => $this->customer->id, 'order_id' => $order->id,
            'type' => 'order', 'amount' => 44.50, 'date' => now(), 'balance' => 0,
        ]);

        $this->assertRefusal(GoodwillFailure::PostedToAccount, $order);
    }

    public function test_16_an_invoiced_order_is_refused(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 175.00);
        $order->update(['invoice_id' => $this->invoice()]);

        $this->assertRefusal(GoodwillFailure::Invoiced, $order->fresh());
    }

    public function test_17_a_stale_accepted_payment_total_is_refused(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 175.00);

        $preview = $this->goodwill->preview($order->fresh(), $this->manager);
        $this->pay($order, 5.00); // lands between preview and submit

        $this->assertRefusalOf(
            GoodwillFailure::StaleState,
            fn () => $this->goodwill->apply($this->request($order, $preview->concession(), $preview->acceptedPaymentTotal()))
        );

        $this->assertUnchanged($order);
    }

    public function test_18_a_stale_concession_is_refused(): void
    {
        // The collected total is unchanged; the ORDER moved.
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 175.00);

        $preview = $this->goodwill->preview($order->fresh(), $this->manager);
        $this->applyStoreCredit($order, 5.00);

        $this->assertRefusalOf(
            GoodwillFailure::StaleState,
            fn () => $this->goodwill->apply($this->request($order, $preview->concession(), $preview->acceptedPaymentTotal()))
        );

        $this->assertSame(0, OrderGoodwillAdjustment::count());
    }

    // ── 19–20. Replay and concurrency ──────────────────────────────────────

    public function test_19_a_replayed_submission_returns_the_original_decision(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 175.00);

        $preview = $this->goodwill->preview($order->fresh(), $this->manager);
        $request = $this->request($order, $preview->concession(), $preview->acceptedPaymentTotal(), 'gw-life-replay');

        $first = $this->goodwill->apply($request);
        $again = $this->goodwill->apply($request);

        $this->assertSame($first->id, $again->id);
        $this->assertSame(1, OrderGoodwillAdjustment::count());
        $this->assertSame(1, ProductDiscount::where('target_id', $order->id)->count());
        $this->assertSettled($order->fresh());
    }

    public function test_20_a_concurrent_payment_cannot_change_the_total_mid_decision(): void
    {
        // The blocking proof lives in GoodwillPaymentLockTest, which needs a
        // second connection and committed fixtures. What is asserted HERE is
        // the consequence at lifecycle level: the concession is sized against
        // the payments read under the lock, and the audit names exactly those.
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $first = $this->pay($order, 175.00);

        $adjustment = $this->grant($order);

        $this->assertSame('175.00', (string) $adjustment->accepted_payment_total);
        $this->assertCount(1, $adjustment->settled_payments_snapshot);
        $this->assertSame($first->id, $adjustment->settled_payments_snapshot[0]['id']);
        $this->assertSame($first->id, $adjustment->payment_id);
    }

    // ── 21–22. Authority ───────────────────────────────────────────────────

    public function test_21_permission_is_denied_despite_the_gate_bypass(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 175.00);

        $nobody = $this->user('gw-life-nobody@test.local');
        $this->assertTrue($nobody->can(GoodwillPermissions::APPLY), 'Baseline: the Gate consents.');

        $this->assertRefusalOf(
            GoodwillFailure::Unauthorized,
            fn () => $this->goodwill->preview($order->fresh(), $nobody)
        );

        $preview = $this->goodwill->preview($order->fresh(), $this->manager);

        $this->assertRefusalOf(
            GoodwillFailure::Unauthorized,
            fn () => $this->goodwill->apply(
                $this->request($order, $preview->concession(), $preview->acceptedPaymentTotal(), null, $nobody)
            )
        );

        $this->assertUnchanged($order);
    }

    public function test_22_an_unseeded_permission_denies_rather_than_throwing(): void
    {
        Permission::whereIn('name', array_keys(GoodwillPermissions::all()))->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 175.00);

        $this->assertFalse(GoodwillPermissions::canApply($this->manager));
        $this->assertFalse(GoodwillPermissions::canReverse($this->manager));
        $this->assertTrue(GoodwillPermissions::approvers()->isEmpty(), 'No approvers, no exception.');

        // isAvailableFor() answers ELIGIBILITY, not authority — the order is
        // still a perfectly good candidate. The screen hides the action because
        // it requires BOTH, which is the combination the panel evaluates and
        // GoodwillEndpointTest asserts on the rendered output. Keeping the two
        // questions separate is deliberate: conflating them would make an
        // authority failure look like an ineligible order.
        $this->assertTrue($this->goodwill->isAvailableFor($order), 'The ORDER is still eligible.');
        $this->assertFalse(
            GoodwillPermissions::canApply($this->manager) && $this->goodwill->isAvailableFor($order),
            '…but the action is not offered, because authority fails closed.'
        );

        $this->assertRefusalOf(
            GoodwillFailure::Unauthorized,
            fn () => $this->goodwill->preview($order->fresh(), $this->manager)
        );

        $this->assertUnchanged($order);
    }

    // ── 23. Audit history and reporting ────────────────────────────────────

    public function test_23_the_audit_record_answers_who_why_and_against_what(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00]]);
        $payment = $this->pay($order, 180.00);

        $adjustment = $this->grant($order, GoodwillReason::DamagedProduct);

        $this->assertSame($this->manager->id, $adjustment->approved_by);
        $this->assertSame(GoodwillReason::DamagedProduct, $adjustment->reason_code);
        $this->assertSame(GoodwillReasonCategory::ServiceRecovery, $adjustment->reason_category);
        $this->assertSame('180.00', (string) $adjustment->accepted_payment_total);
        $this->assertSame($payment->id, $adjustment->payment_id);
        $this->assertNotNull($adjustment->applied_at);

        // The order-level position on either side — figures no other table
        // holds. This order cannot be closed exactly (the tax boundary skips
        // 180.00), so the snapshot and the residual together account for the
        // collected total, which is the property that actually matters.
        $this->assertEquals(223.50, $adjustment->before_snapshot['grand_total']);
        $this->assertEquals(
            180.00,
            round($adjustment->after_snapshot['grand_total'] + (float) $adjustment->rounding_residual, 2),
            'The after-snapshot plus the residual must account for what was collected.'
        );
        $this->assertEquals(4.00, $adjustment->before_snapshot['special_tax_amount']);
        $this->assertArrayHasKey('added_fees_amount', $adjustment->before_snapshot);

        // Money is read through the engine's record, never duplicated here.
        $this->assertSame(
            (string) $adjustment->productDiscount->calculated_discount_amount,
            (string) $adjustment->concessionAmount()
        );

        // Reversal appends to the story; it never rewrites it.
        $this->goodwill->reverse($adjustment->fresh(), $this->manager, 'Withdrawn after review.');
        $reversed = $adjustment->fresh();

        $this->assertTrue($reversed->isReversed());
        $this->assertNotNull($reversed->reversed_at);
        $this->assertSame('Withdrawn after review.', $reversed->reversal_reason);
        $this->assertNotNull($reversed->reversalProductDiscount);
        $this->assertSame(GoodwillReason::DamagedProduct, $reversed->reason_code, 'The original decision survives.');
        $this->assertSame('180.00', (string) $reversed->accepted_payment_total);
    }

    public function test_24_goodwill_is_reportable_by_category_without_parsing_labels(): void
    {
        $recovery = $this->settledOrder(GoodwillReason::ServiceFailure);
        $courtesy = $this->settledOrder(GoodwillReason::LargeOrder);
        $this->settledOrder(GoodwillReason::EquipmentIssue);

        $byCategory = OrderGoodwillAdjustment::query()
            ->selectRaw('reason_category, COUNT(*) AS adjustments')
            ->groupBy('reason_category')
            ->pluck('adjustments', 'reason_category');

        $this->assertSame(2, (int) $byCategory[GoodwillReasonCategory::ServiceRecovery->value]);
        $this->assertSame(1, (int) $byCategory[GoodwillReasonCategory::BusinessCourtesy->value]);

        // The value a report would actually publish: what each category cost,
        // read from the engine's records through the linkage.
        $recoveryTotal = OrderGoodwillAdjustment::query()
            ->inCategory(GoodwillReasonCategory::ServiceRecovery)
            ->with('productDiscount')
            ->get()
            ->sum(fn ($a) => (float) $a->productDiscount->calculated_discount_amount);

        $this->assertGreaterThan(0, $recoveryTotal);
        $this->assertSame(
            round($recovery + $this->concessionOn(GoodwillReason::EquipmentIssue), 2),
            round($recoveryTotal, 2)
        );
        $this->assertGreaterThan(0, $courtesy);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /** revised total + residual == collected, ledger reconciles, no payment created. */
    private function assertSettled(Order $order): void
    {
        $order = $order->fresh();
        $adjustment = OrderGoodwillAdjustment::activeFor((int) $order->id);

        $this->assertNotNull($adjustment, 'An active adjustment must exist.');

        $this->assertSame(
            round((float) $order->grand_total + (float) $adjustment->rounding_residual, 2),
            round((float) $order->total_paid, 2),
            'Revised total plus residual must equal what was collected.'
        );

        $c = fn ($v) => (int) round(((float) $v) * 100);
        $this->assertSame(
            $c($order->grand_total),
            $c($order->subtotal) - $c($order->pretax_discount_total)
                + $c($order->tax_amount) + $c($order->special_tax_amount)
                + $c($order->added_fees_amount) - $c($order->discount_amount),
            'The order must still reconcile.'
        );

        $this->assertNull(PretaxDiscountAllocator::reconcile($order), 'Allocations must reconcile.');
        $this->assertTrue($order->is_paid, 'The order must read as paid in full.');
        $this->assertSame(1, $order->payments()->count(), 'Goodwill creates no payment row.');
    }

    private function assertUnchanged(Order $order): void
    {
        $order = $order->fresh();

        $this->assertSame('0.00', (string) $order->pretax_discount_total, 'Nothing was applied.');
        $this->assertSame(0, OrderGoodwillAdjustment::where('order_id', $order->id)->count());
    }

    private function assertRefusal(GoodwillFailure $expected, Order $order): void
    {
        $this->assertRefusalOf($expected, fn () => $this->grant($order));
        $this->assertUnchanged($order);
    }

    private function assertRefusalOf(GoodwillFailure $expected, callable $operation): void
    {
        try {
            $operation();
            $this->fail("Expected refusal {$expected->value}, but the operation succeeded.");
        } catch (GoodwillException $e) {
            $this->assertSame($expected, $e->failure, "Wrong failure: {$e->getMessage()}");
        }
    }

    /** @return array<string,string> the order's money, for exact comparison */
    private function snapshot(Order $order): array
    {
        $order = $order->fresh();

        return [
            'discount' => (string) $order->pretax_discount_total,
            'tax' => (string) $order->tax_amount,
            'special' => (string) $order->special_tax_amount,
            'fees' => (string) $order->added_fees_amount,
            'grand' => (string) $order->grand_total,
        ];
    }

    private function activeDiscounts(Order $order)
    {
        return ProductDiscount::where('target_type', 'order')
            ->where('target_id', $order->id)
            ->where('status', ProductDiscount::STATUS_APPLIED)
            ->get();
    }

    private function renderReceipt(Order $order): string
    {
        $receipt = ReceiptService::getOrCreateReceipt($order);

        return view('admin.order_management.orders.print_receipt', [
            'receipt' => $receipt->fresh(),
            'order' => $order->fresh(),
            'customer' => $this->customer->fresh(),
            'users' => collect(),
            'sales_tax' => 0.0975,
        ])->render();
    }

    /** A settled order carrying one Goodwill adjustment; returns the concession. */
    private function settledOrder(GoodwillReason $reason): float
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 175.00);

        return (float) $this->grant($order, $reason)->concessionAmount();
    }

    private function concessionOn(GoodwillReason $reason): float
    {
        return (float) OrderGoodwillAdjustment::where('reason_code', $reason->value)
            ->first()->concessionAmount();
    }

    private function grant(Order $order, ?GoodwillReason $reason = null): OrderGoodwillAdjustment
    {
        $preview = $this->goodwill->preview($order->fresh(), $this->manager);

        return $this->goodwill->apply(
            $this->request($order, $preview->concession(), $preview->acceptedPaymentTotal(), null, null, $reason)
        );
    }

    private function request(
        Order $order,
        float $amount,
        float $accepted,
        ?string $key = null,
        $operator = null,
        ?GoodwillReason $reason = null,
    ): GoodwillApplyRequest {
        return new GoodwillApplyRequest(
            order: $order->fresh(),
            reason: $reason ?? GoodwillReason::ServiceFailure,
            note: null,
            idempotencyKey: $key ?? 'gw-life-'.$order->id.'-'.(++$this->sequence),
            operator: $operator ?? $this->manager,
            approver: $operator ?? $this->manager,
            expectedGoodwillAmount: $amount,
            expectedAcceptedPaymentTotal: $accepted,
            sourceInterface: 'lifecycle',
        );
    }

    private function applyStoreCredit(Order $order, float $amount): ProductDiscount
    {
        CustomerCredit::create([
            'customer_id' => $this->customer->id,
            'type' => 'grant', 'amount' => $amount, 'reason' => 'lifecycle seed',
        ]);

        return $this->discounts->applyStoreCredit(
            DiscountTargetType::Order, (int) $order->id, $amount,
            'gw-life-sc-'.$order->id.'-'.(++$this->sequence), null, null, null, $this->customer->id,
        );
    }

    private function invoice(): int
    {
        return (int) \App\Models\Customers\Invoice::create([
            'invoice_number' => 'INV-GWL-'.(++$this->sequence),
            'invoice_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'invoice_created_by' => $this->manager->id,
            'subtotal' => 200.00, 'sales_tax' => 19.50, 'total' => 219.50,
        ])->id;
    }

    private function user(string $email, array $permissions = []): User
    {
        $user = User::create([
            'first_name' => 'GW', 'last_name' => 'Life',
            'email' => $email, 'status' => 'Active',
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function pay(Order $order, float $amount)
    {
        return $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => $amount,
            'status' => OrderPaymentStatus::Paid->value,
        ]);
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
                'unique_id' => 'ORD-GWLIFE-'.$order->id.'-'.$i,
                'product_id' => $this->productId,
                'product_name' => 'Goodwill Lifecycle Probe',
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
