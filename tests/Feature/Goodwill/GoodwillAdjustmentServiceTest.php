<?php

namespace Tests\Feature\Goodwill;

use App\Enums\Discounts\DiscountCalculationType;
use App\Enums\Discounts\DiscountTargetType;
use App\Enums\Discounts\DiscountType;
use App\Enums\Goodwill\GoodwillReason;
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
use App\Services\Discounts\DiscountException;
use App\Services\Discounts\PretaxDiscountAllocator;
use App\Services\Discounts\Targets\OrderDiscountTarget;
use App\Services\Goodwill\GoodwillAdjustmentService;
use App\Services\Goodwill\GoodwillApplyRequest;
use App\Services\Goodwill\GoodwillException;
use App\Services\Goodwill\GoodwillFailure;
use App\Services\Goodwill\GoodwillPermissions;
use App\Services\Goodwill\GoodwillSolution;
use App\Services\ReceiptService;
use Database\Seeders\Iam\GoodwillPermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Increment G2 — preview, apply and reverse.
 *
 * Everything runs through the real `GoodwillAdjustmentService` and the real
 * shared engine. Nothing here recomputes a total independently: where a figure
 * is asserted it is asserted against the canonical order columns, because the
 * point of the whole design is that Goodwill computes nothing itself.
 *
 * The invariant every scenario checks is the same one:
 *
 *     revised grand_total + rounding_residual == settled payments
 *
 * with gross merchandise untouched and no payment row created.
 */
class GoodwillAdjustmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private GoodwillAdjustmentService $service;

    private DiscountApplicationService $discounts;

    private Customer $customer;

    private User $manager;

    private int $productId;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(GoodwillAdjustmentService::class);
        $this->discounts = app(DiscountApplicationService::class);
        $this->customer = Customer::factory()->create();

        $this->productId = (int) DB::table('products')->insertGetId([
            'unique_id' => 'PRD-GW-1', 'product_name' => 'Goodwill Probe',
            'slug' => 'goodwill-probe', 'created_at' => now(), 'updated_at' => now(),
        ]);

        (new GoodwillPermissionSeeder())->run();

        $this->manager = $this->user('gw-manager@test.local');
        $this->manager->givePermissionTo(GoodwillPermissions::APPLY);
        $this->manager->givePermissionTo(GoodwillPermissions::REVERSE);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // ── 1–5. The merchandise shapes ────────────────────────────────────────

    public function test_ordinary_taxable_short_pay(): void
    {
        // $200 merchandise + 9.75% tax = $219.50. Customer pays $200.
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 200.00);

        $adjustment = $this->grant($order);
        $order->refresh();

        $this->assertSame('17.77', (string) $order->pretax_discount_total);
        $this->assertSame('200.00', (string) $order->grand_total);
        $this->assertSame('200.00', (string) $order->subtotal, 'Gross merchandise never moves.');
        $this->assertSame('17.77', (string) $adjustment->concessionAmount());
        $this->assertSame('0.00', (string) $adjustment->rounding_residual);
        $this->assertTrue($order->is_paid);
        $this->assertClosed($order);
    }

    public function test_zero_tax_order(): void
    {
        // No tax at all: the concession equals the shortfall exactly.
        $order = $this->order([['sub' => 200.00, 'tax' => 0.00]]);
        $this->pay($order, 150.00);

        $this->grant($order);
        $order->refresh();

        $this->assertSame('50.00', (string) $order->pretax_discount_total);
        $this->assertSame('0.00', (string) $order->tax_amount);
        $this->assertSame('150.00', (string) $order->grand_total);
        $this->assertClosed($order);
    }

    public function test_mixed_taxable_and_tax_free_merchandise(): void
    {
        $order = $this->order([
            ['sub' => 100.00, 'tax' => 9.75],
            ['sub' => 100.00, 'tax' => 0.00],
        ]);
        $this->pay($order, 200.00);

        $this->grant($order);
        $order->refresh();

        $this->assertSame('9.30', (string) $order->pretax_discount_total);
        $this->assertSame('200.00', (string) $order->grand_total);

        $lines = $order->products()->orderBy('id')->get();
        $this->assertSame('0.00', (string) $lines[1]->tax, 'A tax-free line stays tax-free…');
        $this->assertSame('4.65', (string) $lines[1]->pretax_discount_allocated, '…but bears its share.');
        $this->assertClosed($order);
    }

    public function test_special_tax_moves_with_the_basis(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00]]);
        $this->pay($order, 200.00);

        $this->grant($order);
        $order->refresh();

        // Special tax is basis-derived, so it falls with the merchandise.
        $this->assertLessThan(4.00, (float) $order->special_tax_amount);
        $this->assertSame('200.00', (string) $order->grand_total);
        $this->assertClosed($order);
    }

    public function test_added_fees_are_protected_and_never_scale(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00, 'fees' => 6.00]]);
        $this->pay($order, 200.00);

        $this->grant($order);
        $order->refresh();

        $this->assertSame('6.00', (string) $order->added_fees_amount, 'Flat fees never scale.');
        $this->assertSame('26.40', (string) $order->pretax_discount_total);
        $this->assertSame('200.00', (string) $order->grand_total);
        $this->assertClosed($order);
    }

    // ── 6–7. Stacking with Store Credit ────────────────────────────────────

    public function test_store_credit_and_goodwill_stack(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00, 'fees' => 6.00]]);
        $this->applyStoreCredit($order, 40.00);
        $order->refresh();

        $this->pay($order, (float) $order->grand_total - 20.00);

        $adjustment = $this->grant($order);
        $order->refresh();

        // Both concessions live in the same cumulative total.
        $this->assertSame(
            round(40.00 + (float) $adjustment->concessionAmount(), 2),
            round((float) $order->pretax_discount_total, 2)
        );
        $this->assertClosed($order);

        // Two separate discount rows, both active.
        $this->assertSame(2, ProductDiscount::where('target_id', $order->id)->where('status', 'applied')->count());
    }

    public function test_reversing_goodwill_leaves_store_credit_intact(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00, 'fees' => 6.00]]);
        $this->applyStoreCredit($order, 40.00);
        $order->refresh();

        $totalsAfterCredit = [
            'discount' => (string) $order->pretax_discount_total,
            'tax' => (string) $order->tax_amount,
            'special' => (string) $order->special_tax_amount,
            'grand' => (string) $order->grand_total,
        ];

        $this->pay($order, (float) $order->grand_total - 20.00);
        $adjustment = $this->grant($order);

        $this->service->reverse($adjustment->fresh(), $this->manager, 'Customer disputed the resolution.');
        $order->refresh();

        // Exactly back to the Store-Credit-only position, to the cent.
        $this->assertSame($totalsAfterCredit['discount'], (string) $order->pretax_discount_total);
        $this->assertSame($totalsAfterCredit['tax'], (string) $order->tax_amount);
        $this->assertSame($totalsAfterCredit['special'], (string) $order->special_tax_amount);
        $this->assertSame($totalsAfterCredit['grand'], (string) $order->grand_total);

        $this->assertSame(
            1,
            ProductDiscount::where('target_id', $order->id)
                ->where('discount_type', DiscountType::StoreCredit->value)
                ->where('status', ProductDiscount::STATUS_APPLIED)->count(),
            'The Store Credit adjustment must survive untouched.'
        );

        $this->assertNull(PretaxDiscountAllocator::reconcile($order->fresh()));
        $this->assertGreaterThan(0, (float) $order->balance_due, 'Reversal re-opens the balance.');
    }

    // ── 8–11. Residual behaviour ───────────────────────────────────────────

    public function test_an_exact_close_records_a_zero_residual(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 175.00);

        $adjustment = $this->grant($order);
        $order->refresh();

        $this->assertSame('0.00', (string) $adjustment->rounding_residual);
        $this->assertSame('175.00', (string) $order->grand_total);
        $this->assertSame('40.55', (string) $order->pretax_discount_total);
    }

    public function test_a_one_cent_residual_is_recorded_not_absorbed(): void
    {
        // $229.50 total, $229.44 collected. The closest reachable total is
        // $229.43 — the tax rounding boundary skips $229.44 entirely.
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00, 'fees' => 6.00]]);
        $this->pay($order, 229.44);

        $adjustment = $this->grant($order);
        $order->refresh();

        $this->assertSame('0.01', (string) $adjustment->rounding_residual);
        $this->assertSame('229.43', (string) $order->grand_total);
        $this->assertSame('0.06', (string) $adjustment->concessionAmount(), 'The residual is not folded in.');
        $this->assertTrue($order->is_paid);
    }

    public function test_a_two_cent_residual_is_recorded_not_absorbed(): void
    {
        // Ordinary tax and special tax round down on the same cent, so the
        // total steps by three and skips two reachable values.
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00, 'fees' => 6.00]]);
        $this->pay($order, 229.22);

        $adjustment = $this->grant($order);
        $order->refresh();

        $this->assertSame('0.02', (string) $adjustment->rounding_residual);
        $this->assertSame('229.20', (string) $order->grand_total);
        $this->assertSame('0.26', (string) $adjustment->concessionAmount());
        $this->assertTrue($order->is_paid);
    }

    public function test_a_residual_over_two_cents_refuses_before_any_write(): void
    {
        // Unreachable through real order data — the total never steps by more
        // than three cents — so the engine is stubbed to prove the guard fires
        // rather than trusting that it would.
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 200.00);

        $target = new class ((int) $order->id) extends OrderDiscountTarget
        {
            public function previewTotals(float $additionalDiscount): array
            {
                $a = (int) round($additionalDiscount * 100);

                // Steps of five cents from an odd starting point: 21951,
                // 21946, 21941 … so $200.00 is skipped by four cents.
                return [
                    'subtotal' => 20000, 'discount' => $a, 'remaining' => 20000 - $a,
                    'tax' => 0, 'special_tax' => 0, 'added_fees' => 0, 'other' => 0,
                    'grand_total' => 21951 - ($a * 5),
                ];
            }
        };

        try {
            \App\Services\Goodwill\GoodwillConcessionSolver::solve($target, 20000);
            $this->fail('A residual beyond two cents must refuse.');
        } catch (GoodwillException $e) {
            $this->assertSame(GoodwillFailure::ResidualOutOfBounds, $e->failure);
        }

        // Nothing written.
        $this->assertSame(0, ProductDiscount::where('target_id', $order->id)->count());
        $this->assertSame('0.00', (string) $order->fresh()->pretax_discount_total);
    }

    public function test_a_balance_of_only_fees_cannot_be_closed_by_goodwill(): void
    {
        // Zeroing every merchandise line still leaves the fee standing.
        $order = $this->order([['sub' => 200.00, 'tax' => 0.00, 'fees' => 60.00]]);
        $this->pay($order, 50.00);

        $this->assertRefusal(GoodwillFailure::Unreachable, fn () => $this->grant($order));
    }

    // ── 12–17. Refusals ────────────────────────────────────────────────────

    public function test_an_order_with_no_settled_payment_is_refused(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);

        $this->assertRefusal(GoodwillFailure::NoSettledPayment, fn () => $this->grant($order));
    }

    public function test_a_pending_payment_does_not_count_as_settled(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(), 'amount' => 100.00,
            'status' => OrderPaymentStatus::Pending->value,
        ]);

        $this->assertRefusal(GoodwillFailure::NoSettledPayment, fn () => $this->grant($order));
    }

    public function test_a_fully_paid_order_is_refused(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 219.50);

        $this->assertRefusal(GoodwillFailure::AlreadyPaidInFull, fn () => $this->grant($order));
    }

    public function test_an_order_posted_to_the_credit_account_is_refused(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 200.00);

        CustomerAccount::create([
            'customer_id' => $this->customer->id, 'order_id' => $order->id,
            'type' => 'order', 'amount' => 100.00, 'date' => now(), 'balance' => 0,
        ]);

        $this->assertRefusal(GoodwillFailure::PostedToAccount, fn () => $this->grant($order));
    }

    public function test_an_invoiced_order_is_refused(): void
    {
        // The guard the shared engine does NOT make — see Release 2 backlog #9.
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 200.00);
        $order->update(['invoice_id' => $this->invoice()]);

        $this->assertRefusal(GoodwillFailure::Invoiced, fn () => $this->grant($order->fresh()));
    }

    public function test_a_user_without_the_permission_is_refused(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 200.00);

        $nobody = $this->user('gw-nobody@test.local');

        // The Gate bypass would consent; the direct check does not.
        $this->assertTrue($nobody->can(GoodwillPermissions::APPLY));

        $this->assertRefusal(
            GoodwillFailure::Unauthorized,
            fn () => $this->service->preview($order, $nobody)
        );

        $this->assertSame(0, OrderGoodwillAdjustment::count());
    }

    public function test_an_unauthorized_approver_is_refused_even_when_the_operator_is_authorized(): void
    {
        // Naming a manager is not evidence that the manager has authority.
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 200.00);

        $notAManager = $this->user('gw-not-a-manager@test.local');

        $this->assertRefusal(
            GoodwillFailure::ApproverUnauthorized,
            fn () => $this->grant($order, approver: $notAManager)
        );
    }

    public function test_other_requires_a_note(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 200.00);

        $this->assertRefusal(
            GoodwillFailure::NoteRequired,
            fn () => $this->grant($order, reason: GoodwillReason::Other, note: '  ')
        );
    }

    public function test_a_second_active_adjustment_is_refused(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 200.00);
        $this->grant($order);

        $this->assertRefusal(
            GoodwillFailure::ActiveAdjustmentExists,
            fn () => $this->grant($order->fresh())
        );
    }

    // ── 14. Stale state ────────────────────────────────────────────────────

    public function test_a_payment_landing_after_the_preview_refuses_as_stale(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 190.00);

        $preview = $this->service->preview($order->fresh(), $this->manager);

        // The customer pays a little more between preview and submit.
        $this->pay($order, 5.00);

        $this->assertRefusal(GoodwillFailure::StaleState, fn () => $this->service->apply(
            new GoodwillApplyRequest(
                order: $order->fresh(),
                reason: GoodwillReason::ServiceFailure,
                note: null,
                idempotencyKey: 'gw-stale-1',
                operator: $this->manager,
                approver: $this->manager,
                expectedGoodwillAmount: $preview->concession(),
                expectedAcceptedPaymentTotal: $preview->acceptedPaymentTotal(),
            )
        ));

        $this->assertSame('0.00', (string) $order->fresh()->pretax_discount_total, 'Nothing applied.');
        $this->assertSame(0, OrderGoodwillAdjustment::count());
    }

    public function test_a_concession_that_no_longer_matches_the_preview_refuses_as_stale(): void
    {
        // The collected total is unchanged, but the ORDER moved — a Store
        // Credit discount landed in between — so the required concession is
        // different from the one approved.
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 190.00);

        $preview = $this->service->preview($order->fresh(), $this->manager);

        $this->applyStoreCredit($order, 5.00);

        $this->assertRefusal(GoodwillFailure::StaleState, fn () => $this->service->apply(
            new GoodwillApplyRequest(
                order: $order->fresh(),
                reason: GoodwillReason::ServiceFailure,
                note: null,
                idempotencyKey: 'gw-stale-2',
                operator: $this->manager,
                approver: $this->manager,
                expectedGoodwillAmount: $preview->concession(),
                expectedAcceptedPaymentTotal: $preview->acceptedPaymentTotal(),
            )
        ));

        $this->assertSame(0, OrderGoodwillAdjustment::count());
    }

    // ── 18–19. Idempotency and concurrency ─────────────────────────────────

    public function test_a_replayed_submission_returns_the_original_decision(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 200.00);

        // A replay is the SAME submission retried — a double-click, a lost
        // response, a retried request — not a fresh preview. Building the
        // request once and applying it twice is what actually happens.
        $preview = $this->service->preview($order->fresh(), $this->manager);

        $request = new GoodwillApplyRequest(
            order: $order->fresh(),
            reason: GoodwillReason::ServiceFailure,
            note: null,
            idempotencyKey: 'gw-replay',
            operator: $this->manager,
            approver: $this->manager,
            expectedGoodwillAmount: $preview->concession(),
            expectedAcceptedPaymentTotal: $preview->acceptedPaymentTotal(),
        );

        $first = $this->service->apply($request);
        $again = $this->service->apply($request);

        $this->assertSame($first->id, $again->id);
        $this->assertSame(1, OrderGoodwillAdjustment::count());
        $this->assertSame(1, ProductDiscount::where('target_id', $order->id)->count());

        $order->refresh();
        $this->assertSame('17.77', (string) $order->pretax_discount_total, 'Applied once, not twice.');
        $this->assertClosed($order);
    }

    public function test_a_concurrent_winner_is_translated_into_a_business_failure(): void
    {
        // Simulates losing the race: the application check passed, and another
        // request claimed the order's one active slot before this one wrote.
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 200.00);

        $this->injectDuringApply(fn () => $this->rawAdjustmentRow($order, [
            'active_order_id' => $order->id,
            'idempotency_key' => 'gw-race-winner',
        ]));

        try {
            $this->assertRefusal(GoodwillFailure::ActiveAdjustmentExists, fn () => $this->grant($order));
        } finally {
            ProductDiscount::flushEventListeners();
        }
    }

    public function test_an_unrelated_integrity_error_is_not_disguised_as_a_business_failure(): void
    {
        // Same interception point, but the collision is on the idempotency key
        // rather than the one-active index. That is a real defect and must
        // surface as one — not as "a Goodwill adjustment already exists".
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 200.00);

        $this->injectDuringApply(fn () => $this->rawAdjustmentRow($order, [
            'active_order_id' => null,
            'status' => 'reversed',
            'idempotency_key' => 'gw-unrelated',
        ]));

        try {
            $this->expectException(QueryException::class);
            $this->grant($order, key: 'gw-unrelated');
        } finally {
            ProductDiscount::flushEventListeners();
        }
    }

    // ── 20–21. Reversal safety and real money ──────────────────────────────

    public function test_reversal_is_blocked_once_the_order_has_been_refunded(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 200.00);
        $adjustment = $this->grant($order);

        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(), 'amount' => 50.00,
            'refund_amount' => 50.00, 'refunded_at' => now()->addSecond(),
            'status' => OrderPaymentStatus::PartialRefund->value,
        ]);

        $this->assertRefusal(
            GoodwillFailure::ReversalBlocked,
            fn () => $this->service->reverse($adjustment->fresh(), $this->manager, 'attempt')
        );

        $this->assertFalse($adjustment->fresh()->isReversed());
    }

    public function test_reversing_twice_is_refused(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 200.00);
        $adjustment = $this->grant($order);

        $this->service->reverse($adjustment->fresh(), $this->manager, 'first');

        $this->assertRefusal(
            GoodwillFailure::AlreadyReversed,
            fn () => $this->service->reverse($adjustment->fresh(), $this->manager, 'second')
        );
    }

    public function test_reversal_requires_its_own_permission(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 200.00);
        $adjustment = $this->grant($order);

        $applyOnly = $this->user('gw-apply-only@test.local');
        $applyOnly->givePermissionTo(GoodwillPermissions::APPLY);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertRefusal(
            GoodwillFailure::Unauthorized,
            fn () => $this->service->reverse($adjustment->fresh(), $applyOnly, 'nope')
        );
    }

    public function test_real_payments_are_untouched_through_apply_and_reverse(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 200.00);

        $before = DB::table('order_payments')->where('order_id', $order->id)->orderBy('id')->get();

        $adjustment = $this->grant($order);

        $this->assertEquals(
            $before,
            DB::table('order_payments')->where('order_id', $order->id)->orderBy('id')->get(),
            'Applying Goodwill must not create, alter or remove a payment row.'
        );

        $this->service->reverse($adjustment->fresh(), $this->manager, 'withdrawn');

        $this->assertEquals(
            $before,
            DB::table('order_payments')->where('order_id', $order->id)->orderBy('id')->get(),
            'Reversing must not touch the customer\'s money either.'
        );

        $this->assertSame(200.00, round($order->fresh()->total_paid, 2));
    }

    public function test_the_audit_row_links_the_discount_and_the_payment(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $payment = $this->pay($order, 200.00);

        $adjustment = $this->grant($order, reason: GoodwillReason::LargeOrder);

        $this->assertSame(DiscountType::Goodwill, $adjustment->productDiscount->discount_type);
        $this->assertSame($payment->id, $adjustment->payment_id);
        $this->assertSame('200.00', (string) $adjustment->accepted_payment_total);
        $this->assertSame($this->manager->id, $adjustment->approved_by);
        $this->assertSame('business_courtesy', $adjustment->reason_category->value);

        $snapshot = $adjustment->settled_payments_snapshot;
        $this->assertCount(1, $snapshot);
        $this->assertSame($payment->id, $snapshot[0]['id']);

        // The snapshots record what product_discounts cannot. assertEquals,
        // not assertSame: JSON has one number type, so a whole-dollar figure
        // comes back as an int whatever was written.
        $this->assertEquals(219.50, $adjustment->before_snapshot['grand_total']);
        $this->assertEquals(200.00, $adjustment->after_snapshot['grand_total']);
        $this->assertEquals(200.00, $adjustment->before_snapshot['total_paid']);
        $this->assertArrayHasKey('special_tax_amount', $adjustment->before_snapshot);
        $this->assertArrayHasKey('added_fees_amount', $adjustment->before_snapshot);
    }

    // ── 22. Receipts ───────────────────────────────────────────────────────

    public function test_the_receipt_is_refreshed_on_apply_and_on_reverse(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00]]);
        $this->pay($order, 200.00);

        $receipt = ReceiptService::getOrCreateReceipt($order->fresh());
        $this->assertSame('223.50', (string) $receipt->total);

        $adjustment = $this->grant($order);

        $refreshed = $receipt->fresh();
        $this->assertSame('200.00', (string) $refreshed->total, 'Refreshed without another read.');
        $this->assertSame((string) $order->fresh()->pretax_discount_total, (string) $refreshed->pretax_discount_total);
        $this->assertSame((string) $order->fresh()->special_tax_amount, (string) $refreshed->special_tax);

        $this->service->reverse($adjustment->fresh(), $this->manager, 'withdrawn');

        $this->assertSame('223.50', (string) $receipt->fresh()->total, 'And restored on reversal.');
        $this->assertSame('0.00', (string) $receipt->fresh()->pretax_discount_total);
    }

    // ── 23. The generic path stays shut ────────────────────────────────────

    public function test_the_generic_discount_entry_point_still_refuses_goodwill(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 200.00);

        $this->expectException(DiscountException::class);
        $this->expectExceptionMessage("Discount type 'goodwill' is not operational in Phase 1.");

        $this->discounts->apply(
            new OrderDiscountTarget((int) $order->id),
            DiscountType::Goodwill,
            DiscountCalculationType::FixedAmount,
            10.00, null, 'gw-generic-path', $this->manager->id,
        );
    }

    public function test_the_generic_path_leaves_the_order_untouched_when_it_refuses(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->pay($order, 200.00);

        try {
            $this->discounts->apply(
                new OrderDiscountTarget((int) $order->id),
                DiscountType::Goodwill,
                DiscountCalculationType::FixedAmount,
                10.00, null, 'gw-generic-path-2', $this->manager->id,
            );
        } catch (DiscountException) {
            // expected
        }

        $this->assertSame('0.00', (string) $order->fresh()->pretax_discount_total);
        $this->assertSame(0, ProductDiscount::where('target_id', $order->id)->count());
    }

    public function test_store_credit_still_applies_through_the_generic_path(): void
    {
        // The gate must refuse Goodwill without closing the door on the type
        // that legitimately uses it.
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50]]);
        $this->grantCredit(100.00);

        $discount = $this->discounts->applyStoreCredit(
            DiscountTargetType::Order, (int) $order->id, 25.00,
            'gw-sc-still-works', null, null, null, $this->customer->id,
        );

        $this->assertSame(DiscountType::StoreCredit, $discount->discount_type);
        $this->assertSame('25.00', (string) $order->fresh()->pretax_discount_total);
    }

    // ── Preview ────────────────────────────────────────────────────────────

    public function test_preview_writes_nothing_and_matches_what_apply_persists(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00, 'fees' => 6.00]]);
        $this->pay($order, 200.00);

        $before = DB::table('orders')->where('id', $order->id)->first();
        $preview = $this->service->preview($order->fresh(), $this->manager);

        $this->assertEquals($before, DB::table('orders')->where('id', $order->id)->first(), 'Preview must not write.');
        $this->assertSame(0, ProductDiscount::where('target_id', $order->id)->count());

        $this->grant($order);
        $order->refresh();

        $c = fn ($v) => (int) round(((float) $v) * 100);
        $this->assertSame($preview->totals['grand_total'], $c($order->grand_total));
        $this->assertSame($preview->totals['tax'], $c($order->tax_amount));
        $this->assertSame($preview->totals['special_tax'], $c($order->special_tax_amount));
        $this->assertSame($preview->totals['discount'], $c($order->pretax_discount_total));
    }

    public function test_the_preview_breakdown_carries_every_operator_facing_figure(): void
    {
        $order = $this->order([['sub' => 200.00, 'tax' => 19.50, 'special' => 4.00, 'fees' => 6.00]]);
        $this->pay($order, 200.00);

        $display = $this->service->preview($order->fresh(), $this->manager)->toDisplayArray();

        foreach ([
            'amount_collected', 'remaining_balance', 'goodwill_amount', 'gross_subtotal',
            'total_pretax_adjustments', 'discounted_product_value', 'sales_tax',
            'special_tax', 'added_fees', 'revised_grand_total', 'rounding_residual',
        ] as $key) {
            $this->assertArrayHasKey($key, $display);
        }

        $this->assertSame(200.00, $display['amount_collected']);
        $this->assertSame(29.50, $display['remaining_balance']);
        $this->assertSame(200.00, $display['gross_subtotal']);
        $this->assertSame(6.00, $display['added_fees']);
        $this->assertSame(200.00, $display['revised_grand_total']);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /** grand_total + residual == settled payments, and the ledger reconciles. */
    private function assertClosed(Order $order): void
    {
        $order = $order->fresh();
        $adjustment = OrderGoodwillAdjustment::activeFor((int) $order->id);

        $this->assertNotNull($adjustment);
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

        $this->assertNull(PretaxDiscountAllocator::reconcile($order));
        $this->assertSame(0, $order->payments()->where('amount', '<', 0)->count(), 'Goodwill creates no payment row.');
    }

    private function assertRefusal(GoodwillFailure $expected, callable $operation): void
    {
        try {
            $operation();
            $this->fail("Expected refusal {$expected->value}, but the operation succeeded.");
        } catch (GoodwillException $e) {
            $this->assertSame($expected, $e->failure, "Wrong failure: {$e->getMessage()}");
        }
    }

    /** Fire $callback the moment the shared engine writes its discount row. */
    private function injectDuringApply(callable $callback): void
    {
        ProductDiscount::created(function () use ($callback) {
            static $done = false;
            if (! $done) {
                $done = true;
                $callback();
            }
        });
    }

    private function rawAdjustmentRow(Order $order, array $overrides): void
    {
        DB::table('order_goodwill_adjustments')->insert(array_merge([
            'order_id' => $order->id,
            'product_discount_id' => ProductDiscount::where('target_id', $order->id)->value('id'),
            'status' => 'applied',
            'active_order_id' => $order->id,
            'reason_code' => 'SERVICE_FAILURE',
            'reason_category' => 'service_recovery',
            'approved_by' => $this->manager->id,
            'approved_at' => now(),
            'accepted_payment_total' => 200.00,
            'rounding_residual' => 0.00,
            'before_snapshot' => json_encode([]),
            'after_snapshot' => json_encode([]),
            'idempotency_key' => 'gw-injected',
            'applied_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function grant(
        Order $order,
        ?GoodwillReason $reason = null,
        ?string $note = null,
        ?string $key = null,
        $operator = null,
        $approver = null,
    ): OrderGoodwillAdjustment {
        $operator ??= $this->manager;
        $preview = $this->previewFor($order, $operator, $approver);

        return $this->service->apply(new GoodwillApplyRequest(
            order: $order->fresh(),
            reason: $reason ?? GoodwillReason::ServiceFailure,
            note: $note,
            idempotencyKey: $key ?? 'gw-'.$order->id.'-'.(++$this->sequence),
            operator: $operator,
            approver: $approver ?? $operator,
            expectedGoodwillAmount: $preview?->concession() ?? 0.0,
            expectedAcceptedPaymentTotal: $preview?->acceptedPaymentTotal() ?? 0.0,
            sourceInterface: 'test',
        ));
    }

    /**
     * Previews exactly as the UI will: a refusal here propagates rather than
     * being swallowed. Preview and apply share `assertEligible()`, so a
     * condition that stops one stops the other with the same typed failure —
     * which is the property worth asserting.
     */
    private function previewFor(Order $order, $operator, $approver): ?GoodwillSolution
    {
        return $this->service->preview($order->fresh(), $operator);
    }

    private function applyStoreCredit(Order $order, float $amount): ProductDiscount
    {
        $this->grantCredit($amount);

        return $this->discounts->applyStoreCredit(
            DiscountTargetType::Order, (int) $order->id, $amount,
            'gw-sc-'.$order->id.'-'.(++$this->sequence), null, null, null, $this->customer->id,
        );
    }

    private function grantCredit(float $amount): void
    {
        CustomerCredit::create([
            'customer_id' => $this->customer->id,
            'type' => 'grant', 'amount' => $amount, 'reason' => 'goodwill test seed',
        ]);
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

    private function invoice(): int
    {
        return (int) \App\Models\Customers\Invoice::create([
            'invoice_number' => 'INV-GW-'.(++$this->sequence),
            'invoice_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'invoice_created_by' => $this->manager->id,
            'subtotal' => 200.00, 'sales_tax' => 19.50, 'total' => 219.50,
        ])->id;
    }

    private function user(string $email): User
    {
        return User::create([
            'first_name' => 'GW', 'last_name' => 'User',
            'email' => $email, 'status' => 'Active',
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
                'unique_id' => 'ORD-GW-'.$order->id.'-'.$i,
                'product_id' => $this->productId,
                'product_name' => 'Goodwill Probe',
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
