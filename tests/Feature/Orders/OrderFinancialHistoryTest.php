<?php

namespace Tests\Feature\Orders;

use App\Enums\Discounts\DiscountTargetType;
use App\Enums\Discounts\DiscountType;
use App\Enums\Goodwill\GoodwillReason;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerCredit;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\Discounts\DiscountApplicationService;
use App\Services\Goodwill\GoodwillAdjustmentService;
use App\Services\Goodwill\GoodwillApplyRequest;
use App\Services\Goodwill\GoodwillPermissions;
use App\Services\Orders\OrderFinancialHistory;
use Database\Seeders\Iam\GoodwillPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Financial activity clarity — the order must explain itself.
 *
 * The summary previously showed one cumulative "Pre-Tax Discounts" line and the
 * modal showed only payment rows, so a $150 payment settling a $223.50 order
 * looked inexplicable. Nothing about the arithmetic was wrong; the page simply
 * did not say which concessions had been applied or that they were concessions
 * at all rather than money.
 *
 * PRESENTATION ONLY. Every test that touches money also asserts the underlying
 * records are unchanged.
 */
class OrderFinancialHistoryTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private User $manager;

    private int $productId;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Activity', 'last_name' => 'Probe',
            'email' => 'activity-probe@test.local', 'status' => 'Active',
        ]);

        $this->productId = (int) DB::table('products')->insertGetId([
            'unique_id' => 'PRD-OFH-1', 'product_name' => 'Activity Probe',
            'slug' => 'activity-probe', 'created_at' => now(), 'updated_at' => now(),
        ]);

        (new GoodwillPermissionSeeder())->run();

        $this->manager = $this->user('ofh-manager@test.local', [
            GoodwillPermissions::APPLY, GoodwillPermissions::REVERSE,
        ]);
    }

    // ── 1–2, 8. The summary lines ──────────────────────────────────────────

    public function test_store_credit_and_goodwill_display_as_separate_lines(): void
    {
        $order = $this->orderWithBoth();

        $lines = OrderFinancialHistory::for($order)->activeAdjustmentLines();

        $this->assertCount(2, $lines, 'Two types must never collapse into one line.');

        $labels = array_column($lines, 'label');
        $this->assertContains('Store Credit - Pre-Tax', $labels);
        $this->assertContains('Goodwill - Pre-Tax', $labels);

        // Labels come from the enum, not from the view.
        $this->assertSame(DiscountType::StoreCredit->receiptLabel(), 'Store Credit - Pre-Tax');
        $this->assertSame(DiscountType::Goodwill->receiptLabel(), 'Goodwill - Pre-Tax');

        $storeCredit = collect($lines)->firstWhere('key', 'store_credit');
        $this->assertSame(50.00, $storeCredit['amount']);
    }

    public function test_same_type_adjustments_aggregate_into_one_line(): void
    {
        $order = $this->order(200.00, 19.50);
        $this->applyStoreCredit($order, 30.00);
        $this->applyStoreCredit($order, 20.00);

        $lines = OrderFinancialHistory::for($order->fresh())->activeAdjustmentLines();

        $this->assertCount(1, $lines, 'Same type sums into a single line.');
        $this->assertSame('Store Credit - Pre-Tax', $lines[0]['label']);
        $this->assertSame(50.00, $lines[0]['amount']);
    }

    public function test_the_displayed_lines_sum_to_the_orders_own_total(): void
    {
        // The property that stops the page contradicting itself.
        foreach ([$this->orderWithBoth(), $this->orderWithStoreCreditOnly()] as $order) {
            $order = $order->fresh();
            $lines = OrderFinancialHistory::for($order)->activeAdjustmentLines();

            $this->assertSame(
                (int) round((float) $order->pretax_discount_total * 100),
                array_sum(array_map(fn ($l) => (int) round($l['amount'] * 100), $lines)),
                'Displayed adjustment lines must sum to pretax_discount_total.'
            );
        }
    }

    // ── 3–4. Payments and adjustments stay distinct ────────────────────────

    public function test_store_credit_and_goodwill_remain_separate_in_the_activity_view(): void
    {
        $order = $this->orderWithBoth();

        $adjustments = OrderFinancialHistory::for($order)->pretaxAdjustments();

        $this->assertCount(2, $adjustments);
        $this->assertSame(
            ['Store Credit - Pre-Tax', 'Goodwill - Pre-Tax'],
            array_column($adjustments, 'label'),
            'Oldest first, each named by its own type.'
        );
        $this->assertSame(['Applied', 'Applied'], array_column($adjustments, 'status'));
    }

    public function test_neither_goodwill_nor_store_credit_is_ever_listed_as_a_payment(): void
    {
        $order = $this->orderWithBoth();

        $payments = OrderFinancialHistory::for($order)->paymentsReceived();

        $this->assertCount(1, $payments, 'Only the real cash payment.');
        $this->assertSame(OrderPaymentMethod::Cash, $payments->first()->payment_method);

        foreach ($payments as $payment) {
            $this->assertNotSame(OrderPaymentMethod::StoreCredit, $payment->payment_method);
        }

        // And the order carries no payment row for either concession.
        $this->assertSame(1, $order->fresh()->payments()->count());
    }

    // ── 5. Reversal ────────────────────────────────────────────────────────

    public function test_reversed_goodwill_stays_in_history_but_leaves_the_active_summary(): void
    {
        $order = $this->orderWithBoth();
        $goodwill = \App\Models\Goodwill\OrderGoodwillAdjustment::activeFor((int) $order->id);

        app(GoodwillAdjustmentService::class)->reverse($goodwill, $this->manager, 'Customer disputed it.');

        $history = OrderFinancialHistory::for($order->fresh());

        // Gone from the summary…
        $labels = array_column($history->activeAdjustmentLines(), 'label');
        $this->assertContains('Store Credit - Pre-Tax', $labels);
        $this->assertNotContains('Goodwill - Pre-Tax', $labels, 'A reversed concession is not active.');

        // …still in the history, marked, not deleted.
        $adjustments = $history->pretaxAdjustments();
        $reversed = collect($adjustments)->firstWhere('label', 'Goodwill - Pre-Tax');

        $this->assertNotNull($reversed, 'Removing it would hide that a concession was granted and withdrawn.');
        $this->assertSame('Reversed', $reversed['status']);
        $this->assertTrue($reversed['reversed']);
        $this->assertSame('Customer disputed it.', $reversed['reversal_reason']);

        // The compensating row the engine appends is not a second concession.
        $this->assertCount(2, $adjustments, 'Two events: the Store Credit and the reversed Goodwill.');
    }

    // ── 6. Nothing is written ──────────────────────────────────────────────

    public function test_reading_the_history_changes_no_record(): void
    {
        $order = $this->orderWithBoth();

        $payments = DB::table('order_payments')->where('order_id', $order->id)->orderBy('id')->get();
        $discounts = DB::table('product_discounts')->where('target_id', $order->id)->orderBy('id')->get();
        $orderRow = DB::table('orders')->where('id', $order->id)->first();

        $history = OrderFinancialHistory::for($order->fresh());
        $history->activeAdjustmentLines();
        $history->paymentsReceived();
        $history->pretaxAdjustments();

        $this->assertEquals($payments, DB::table('order_payments')->where('order_id', $order->id)->orderBy('id')->get());
        $this->assertEquals($discounts, DB::table('product_discounts')->where('target_id', $order->id)->orderBy('id')->get());
        $this->assertEquals($orderRow, DB::table('orders')->where('id', $order->id)->first());
    }

    // ── 7. Permission ──────────────────────────────────────────────────────

    public function test_internal_goodwill_detail_is_hidden_without_the_direct_spatie_permission(): void
    {
        $order = $this->orderWithBoth();
        $nobody = $this->user('ofh-nobody@test.local');

        // The Gate consents for everyone; the direct check must not.
        $this->assertTrue($nobody->can(GoodwillPermissions::APPLY), 'Baseline: the Gate consents.');
        $this->assertFalse(GoodwillPermissions::canApply($nobody));

        $withoutPermission = $this->renderModal($order, $nobody);

        // The basic financial fact stays — it is on the customer's receipt.
        $this->assertStringContainsString('Goodwill - Pre-Tax', $withoutPermission);
        $this->assertStringContainsString('Applied', $withoutPermission);

        // The internal authorisation detail does not.
        $this->assertStringNotContainsString('Service Failure', $withoutPermission);
        $this->assertStringNotContainsString('Service Recovery', $withoutPermission);
        $this->assertStringNotContainsString('Approved by', $withoutPermission);
        $this->assertStringNotContainsString('Accepted Payment Total', $withoutPermission);
        $this->assertStringNotContainsString('Rounding Residual', $withoutPermission);

        // …and does for a manager.
        $withPermission = $this->renderModal($order, $this->manager);

        $this->assertStringContainsString('Service Failure', $withPermission);
        $this->assertStringContainsString('Service Recovery', $withPermission);
        $this->assertStringContainsString('Approved by', $withPermission);
        $this->assertStringContainsString('Accepted Payment Total', $withPermission);
    }

    // ── 9. Ordinary orders ─────────────────────────────────────────────────

    public function test_an_order_with_no_adjustments_renders_cleanly(): void
    {
        $order = $this->order(200.00, 19.50);
        $this->pay($order, 219.50);

        $history = OrderFinancialHistory::for($order->fresh());

        $this->assertSame([], $history->activeAdjustmentLines());
        $this->assertSame([], $history->pretaxAdjustments());
        $this->assertCount(1, $history->paymentsReceived());

        $html = $this->renderModal($order->fresh(), $this->manager);

        $this->assertStringContainsString('Payments Received', $html);
        $this->assertStringNotContainsString('Pre-Tax Adjustments', $html, 'No empty section.');
        $this->assertStringNotContainsString('Pre-Tax Discounts', $html, 'The old generic label is gone.');
    }

    public function test_the_summary_renders_a_line_per_type_and_no_generic_label(): void
    {
        $order = $this->orderWithBoth()->fresh();

        $lines = OrderFinancialHistory::for($order)->activeAdjustmentLines();
        $rendered = collect($lines)->map(fn ($l) => $l['label'])->implode(' | ');

        $this->assertStringContainsString('Store Credit - Pre-Tax', $rendered);
        $this->assertStringContainsString('Goodwill - Pre-Tax', $rendered);
        $this->assertStringNotContainsString('Pre-Tax Discounts', $rendered);
    }

    // ── 10. The safety fallback ────────────────────────────────────────────

    public function test_an_unattributed_remainder_is_labelled_and_logged(): void
    {
        // NOT a supported workflow. The page must never show a set of lines
        // that disagrees with the order's own total, so an unexplained gap is
        // surfaced and logged rather than silently dropped or absorbed into a
        // named type.
        $order = $this->orderWithStoreCreditOnly();

        // Force a discrepancy the tracked rows cannot explain.
        DB::table('orders')->where('id', $order->id)->update(['pretax_discount_total' => 60.00]);

        Log::spy();

        $lines = OrderFinancialHistory::for($order->fresh())->activeAdjustmentLines();

        $labels = array_column($lines, 'label');
        $this->assertContains('Store Credit - Pre-Tax', $labels);
        $this->assertContains(OrderFinancialHistory::UNATTRIBUTED_LABEL, $labels);
        $this->assertSame('Other Pre-Tax Adjustment', OrderFinancialHistory::UNATTRIBUTED_LABEL);

        $remainder = collect($lines)->firstWhere('key', 'unattributed');
        $this->assertSame(10.00, $remainder['amount']);
        $this->assertFalse($remainder['attributed']);

        // It still reconciles — that is the point of the fallback.
        $this->assertSame(
            6000,
            array_sum(array_map(fn ($l) => (int) round($l['amount'] * 100), $lines))
        );

        Log::shouldHaveReceived('warning')->once();
    }

    public function test_tracked_rows_exceeding_the_total_collapse_to_one_honest_line(): void
    {
        // The record contradicts itself, so no split derived from it is
        // trustworthy. Show the canonical total, say nothing about attribution.
        $order = $this->orderWithStoreCreditOnly();
        DB::table('orders')->where('id', $order->id)->update(['pretax_discount_total' => 20.00]);

        Log::spy();

        $lines = OrderFinancialHistory::for($order->fresh())->activeAdjustmentLines();

        $this->assertCount(1, $lines);
        $this->assertSame(OrderFinancialHistory::UNATTRIBUTED_LABEL, $lines[0]['label']);
        $this->assertSame(20.00, $lines[0]['amount']);

        Log::shouldHaveReceived('warning')->once();
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function renderModal(Order $order, User $as): string
    {
        $this->actingAs($as);

        return view('admin.order_management.orders.partials._payment_and_adjustment_details', [
            'order' => $order->fresh(),
        ])->render();
    }

    /** $200 + $19.50 tax, $50 Store Credit, $150 paid, Goodwill closing the rest. */
    private function orderWithBoth(): Order
    {
        $order = $this->order(200.00, 19.50);
        $this->applyStoreCredit($order, 50.00);
        $order->refresh();

        $this->pay($order, 150.00);
        $this->applyGoodwill($order);

        return $order->fresh();
    }

    private function orderWithStoreCreditOnly(): Order
    {
        $order = $this->order(200.00, 19.50);
        $this->applyStoreCredit($order, 50.00);

        return $order->fresh();
    }

    private function applyStoreCredit(Order $order, float $amount): void
    {
        CustomerCredit::create([
            'customer_id' => $this->customer->id,
            'type' => 'grant', 'amount' => $amount, 'reason' => 'activity seed',
        ]);

        app(DiscountApplicationService::class)->applyStoreCredit(
            DiscountTargetType::Order, (int) $order->id, $amount,
            'ofh-sc-'.$order->id.'-'.(++$this->sequence), $this->manager->id,
            null, null, $this->customer->id,
        );
    }

    private function applyGoodwill(Order $order): void
    {
        $service = app(GoodwillAdjustmentService::class);
        $preview = $service->preview($order->fresh(), $this->manager);

        $service->apply(new GoodwillApplyRequest(
            order: $order->fresh(),
            reason: GoodwillReason::ServiceFailure,
            note: null,
            idempotencyKey: 'ofh-gw-'.$order->id.'-'.(++$this->sequence),
            operator: $this->manager,
            approver: $this->manager,
            expectedGoodwillAmount: $preview->concession(),
            expectedAcceptedPaymentTotal: $preview->acceptedPaymentTotal(),
        ));
    }

    private function pay(Order $order, float $amount): void
    {
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => $amount,
            'status' => OrderPaymentStatus::Paid->value,
            'payment_note' => 'Counter payment',
            'created_by_id' => $this->manager->id,
            'created_by_type' => User::class,
        ]);
    }

    private function user(string $email, array $permissions = []): User
    {
        $user = User::create([
            'first_name' => 'OFH', 'last_name' => 'User',
            'email' => $email, 'status' => 'Active',
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function order(float $sub, float $tax): Order
    {
        $order = Order::create([
            'order_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Activity Probe',
            'subtotal' => $sub, 'tax_amount' => $tax,
            'special_tax_amount' => 0, 'added_fees_amount' => 0,
            'discount_amount' => 0, 'pretax_discount_total' => 0,
            'grand_total' => $sub + $tax,
        ]);

        $order->products()->create([
            'unique_id' => 'ORD-OFH-'.$order->id,
            'product_id' => $this->productId,
            'product_name' => 'Activity Probe',
            'price' => $sub, 'quantity' => 1,
            'sub_total' => $sub, 'tax' => $tax,
            'special_tax' => 0, 'added_fees' => 0,
            'total' => $sub + $tax,
            'product_data' => json_encode(['sub_total' => $sub, 'tax' => $tax, 'special_tax' => 0, 'added_fees' => 0]),
        ]);

        return $order->fresh();
    }
}
