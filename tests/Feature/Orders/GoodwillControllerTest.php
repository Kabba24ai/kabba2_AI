<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderGoodwillAdjustment;
use App\Services\Orders\HistoricalTaxBasisResolver;
use App\Services\ReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * The Goodwill HTTP boundary.
 *
 * These prove the CONTROLLER contract, not the arithmetic — that is covered by
 * GoodwillCalculationTest and GoodwillApplyReverseTest. What matters here is
 * that the boundary cannot be talked into doing something the service would
 * refuse, and that a refusal reaches the operator as a usable message with an
 * honest status code rather than a 500.
 *
 * `permission_is_enforced_even_though_the_app_gate_allows_everything` is the
 * load-bearing one: AppServiceProvider registers Gate::before(fn () => true),
 * so route middleware and can() are decorative for every ability in this
 * application. If Goodwill's authority check ever routes back through the
 * Gate, that test is what catches it.
 */
class GoodwillControllerTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $manager;
    private User $clerk;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        HistoricalTaxBasisResolver::flushSchemaMemo();

        Permission::findOrCreate('goodwill.apply', 'web');
        Permission::findOrCreate('goodwill.reverse', 'web');

        $this->customer = Customer::factory()->create();

        $this->manager = User::create([
            'first_name' => 'Mia', 'last_name' => 'Manager',
            'email' => 'mia-ctl@example.com', 'status' => 'Active',
        ]);
        $this->manager->givePermissionTo('goodwill.apply', 'goodwill.reverse');

        $this->clerk = User::create([
            'first_name' => 'Cy', 'last_name' => 'Clerk',
            'email' => 'cy-ctl@example.com', 'status' => 'Active',
        ]);

        $this->order = $this->paidOrder();
    }

    // ── Preview ────────────────────────────────────────────────────────────

    public function test_preview_returns_every_figure_the_manager_must_approve(): void
    {
        $response = $this->actingAs($this->clerk)->postJson($this->url('preview'));

        $response->assertOk()->assertJson(['success' => true]);

        $preview = $response->json('preview');

        // Each item the brief requires be visible before confirmation.
        foreach ([
            'accepted', 'goodwill', 'revised_basis', 'revised_tax',
            'revised_special_tax', 'protected_fees', 'revised_total',
        ] as $key) {
            $this->assertArrayHasKey($key, $preview, "Preview must state {$key}.");
        }

        $this->assertEqualsWithDelta(185.00, $preview['accepted'], 0.001);
        $this->assertEqualsWithDelta(31.44, $preview['goodwill'], 0.001);
        $this->assertEqualsWithDelta(168.56, $preview['revised_basis'], 0.001);
        $this->assertEqualsWithDelta(16.44, $preview['revised_tax'], 0.001);
        $this->assertEqualsWithDelta(185.00, $preview['revised_total'], 0.001);

        // The confirmation echoes this back so the service can detect drift.
        $this->assertSame(18500, $response->json('accepted_cents'));
    }

    public function test_preview_writes_nothing(): void
    {
        $before = DB::table('orders')->where('id', $this->order->id)->first();

        $this->actingAs($this->clerk)->postJson($this->url('preview'))->assertOk();

        $this->assertEquals($before, DB::table('orders')->where('id', $this->order->id)->first());
        $this->assertSame(0, OrderGoodwillAdjustment::count());
    }

    public function test_preview_refuses_an_order_with_nothing_collected(): void
    {
        $order = $this->paidOrder(payment: null);

        $this->actingAs($this->clerk)
            ->postJson(route('admin.order-management.orders.goodwill.preview', $order->unique_id))
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    // ── Apply ──────────────────────────────────────────────────────────────

    public function test_apply_closes_the_order_and_returns_the_adjusted_receipt(): void
    {
        ReceiptService::getOrCreateReceipt($this->order);

        $response = $this->actingAs($this->clerk)->postJson($this->url('apply'), $this->payload());

        $response->assertOk()->assertJson([
            'success' => true,
            'order'   => ['is_paid' => true, 'grand_total' => 185.00, 'balance_due' => 0.0],
        ]);

        // The adjusted document is handed back, not left to be hunted for.
        $current = ReceiptService::currentReceipt($this->order->fresh());
        $this->assertSame($current->id, $response->json('receipt_id'));
        $this->assertNotNull($current->superseded_receipt_id);
    }

    public function test_apply_links_the_real_payment_to_the_adjustment(): void
    {
        $this->actingAs($this->clerk)->postJson($this->url('apply'), $this->payload())->assertOk();

        $adjustment = OrderGoodwillAdjustment::firstOrFail();
        $payment = $this->order->payments()->first();

        $this->assertSame($payment->id, $adjustment->order_payment_id);
        $this->assertSame(1, $this->order->payments()->count(), 'Goodwill is not tender and creates no payment row.');
    }

    public function test_permission_is_enforced_even_though_the_app_gate_allows_everything(): void
    {
        // The clerk submits, naming HIMSELF as the authorising manager.
        $payload = $this->payload();
        $payload['approved_by'] = $this->clerk->id;

        $this->actingAs($this->clerk)
            ->postJson($this->url('apply'), $payload)
            ->assertStatus(403)
            ->assertJson(['success' => false, 'reason' => 'permission_denied']);

        $this->assertSame(0, OrderGoodwillAdjustment::count());
    }

    public function test_stale_accepted_amount_is_rejected_as_a_conflict(): void
    {
        $payload = $this->payload();
        $payload['expected_accepted_cents'] = 19000; // the operator's page is out of date

        $this->actingAs($this->clerk)
            ->postJson($this->url('apply'), $payload)
            ->assertStatus(409)
            ->assertJson(['reason' => 'stale_order_state']);

        $this->assertSame(0, OrderGoodwillAdjustment::count());
        $this->assertSame('219.50', (string) $this->order->fresh()->grand_total);
    }

    public function test_other_requires_a_note(): void
    {
        $payload = $this->payload();
        $payload['reason_code'] = 'other';
        unset($payload['reason_note']);

        $this->actingAs($this->clerk)
            ->postJson($this->url('apply'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason_note');

        $this->assertSame(0, OrderGoodwillAdjustment::count());
    }

    public function test_an_idempotency_token_is_required(): void
    {
        $payload = $this->payload();
        unset($payload['idempotency_token']);

        $this->actingAs($this->clerk)
            ->postJson($this->url('apply'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('idempotency_token');
    }

    public function test_a_duplicate_submission_records_one_adjustment(): void
    {
        $payload = $this->payload();

        $this->actingAs($this->clerk)->postJson($this->url('apply'), $payload)->assertOk();
        $replay = $this->actingAs($this->clerk)->postJson($this->url('apply'), $payload);

        $replay->assertOk()->assertJson(['success' => true, 'replayed' => true]);
        $this->assertSame(1, OrderGoodwillAdjustment::count());
        $this->assertSame(1, $this->order->payments()->count());
    }

    public function test_an_accounts_receivable_order_is_refused_with_a_usable_message(): void
    {
        DB::table('customer_accounts')->insert([
            'unique_id' => 'CA-CTL-1', 'customer_id' => $this->customer->id,
            'order_id' => $this->order->id, 'amount' => 200.00, 'balance' => 200.00,
            'sales_tax' => '0', 'date' => now(), 'type' => 'order',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->clerk)->postJson($this->url('apply'), $this->payload());

        $response->assertStatus(422)->assertJson(['reason' => 'accounts_receivable_already_posted']);
        $this->assertStringContainsString('credit memo', strtolower($response->json('message')));
        $this->assertSame(0, OrderGoodwillAdjustment::count());
    }

    public function test_a_refusal_records_neither_payment_nor_adjustment(): void
    {
        $beforeOrder    = DB::table('orders')->where('id', $this->order->id)->first();
        $beforePayments = DB::table('order_payments')->where('order_id', $this->order->id)->get()->toArray();

        $payload = $this->payload();
        $payload['expected_accepted_cents'] = 1;

        $this->actingAs($this->clerk)->postJson($this->url('apply'), $payload)->assertStatus(409);

        $this->assertEquals($beforeOrder, DB::table('orders')->where('id', $this->order->id)->first());
        $this->assertEquals($beforePayments, DB::table('order_payments')->where('order_id', $this->order->id)->get()->toArray());
        $this->assertSame(0, OrderGoodwillAdjustment::count());
    }

    public function test_apply_is_refused_when_nothing_has_been_collected(): void
    {
        // A $0.00 Goodwill is a write-off, not a concession against tender —
        // Truth Table §5.9, undecided. Neither entry point may reach it.
        $order = $this->paidOrder(payment: null);

        $response = $this->actingAs($this->clerk)->postJson(
            route('admin.order-management.orders.goodwill.apply', $order->unique_id),
            [
                'reason_code'             => 'manager_courtesy',
                'expected_accepted_cents' => 1,
                'approved_by'             => $this->manager->id,
                'idempotency_token'       => 'zero-collected-1',
            ]
        );

        // The order has 0 settled, so the caller's claim of 1 is stale.
        $response->assertStatus(409)->assertJson(['reason' => 'stale_order_state']);
        $this->assertSame(0, OrderGoodwillAdjustment::count());
    }

    public function test_a_zero_expected_amount_is_rejected_by_validation(): void
    {
        $payload = $this->payload();
        $payload['expected_accepted_cents'] = 0;

        $this->actingAs($this->clerk)
            ->postJson($this->url('apply'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('expected_accepted_cents');
    }

    public function test_the_linked_payment_is_always_a_real_settled_one(): void
    {
        // A pending/unsettled row must never be recorded as the tender the
        // concession was granted alongside.
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount' => 40.00,
            'status' => OrderPaymentStatus::Pending->value,
        ]);

        $this->actingAs($this->clerk)->postJson($this->url('apply'), $this->payload())->assertOk();

        $adjustment = OrderGoodwillAdjustment::firstOrFail();
        $linked = $this->order->payments()->find($adjustment->order_payment_id);

        $this->assertNotNull($linked);
        $this->assertSame(OrderPaymentStatus::PartialPayment, $linked->status);
    }

    // ── Reverse ────────────────────────────────────────────────────────────

    public function test_reverse_restores_the_balance_and_returns_the_new_receipt(): void
    {
        ReceiptService::getOrCreateReceipt($this->order);
        $this->actingAs($this->clerk)->postJson($this->url('apply'), $this->payload())->assertOk();

        $response = $this->actingAs($this->manager)
            ->postJson($this->url('reverse'), ['reversal_reason' => 'Applied in error']);

        $response->assertOk()->assertJson([
            'success' => true,
            'order'   => ['grand_total' => 219.50, 'is_paid' => false],
        ]);

        $this->assertSame(1, $this->order->payments()->count(), 'Real payments are never touched.');
        $this->assertSame(ReceiptService::currentReceipt($this->order->fresh())->id, $response->json('receipt_id'));
    }

    public function test_reverse_requires_a_reason(): void
    {
        $this->actingAs($this->clerk)->postJson($this->url('apply'), $this->payload())->assertOk();

        $this->actingAs($this->manager)
            ->postJson($this->url('reverse'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reversal_reason');

        $this->assertFalse(OrderGoodwillAdjustment::firstOrFail()->isReversed());
    }

    public function test_reverse_permission_is_enforced(): void
    {
        $this->actingAs($this->clerk)->postJson($this->url('apply'), $this->payload())->assertOk();

        $this->actingAs($this->clerk)
            ->postJson($this->url('reverse'), ['reversal_reason' => 'Nope'])
            ->assertStatus(403);

        $this->assertFalse(OrderGoodwillAdjustment::firstOrFail()->isReversed());
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function url(string $action): string
    {
        return route("admin.order-management.orders.goodwill.{$action}", $this->order->unique_id);
    }

    /** @return array<string,mixed> */
    private function payload(): array
    {
        return [
            'reason_code'             => 'manager_courtesy',
            'expected_accepted_cents' => 18500,
            'approved_by'             => $this->manager->id,
            'idempotency_token'       => 'ctl-token-1',
        ];
    }

    /** 200.00 @ 9.75% = 19.50; total 219.50; 185.00 collected unless $payment is null. */
    private function paidOrder(?float $payment = 185.00): Order
    {
        $order = Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Controller Customer',
            'subtotal' => 200.00, 'tax_amount' => 19.50,
            'discount_amount' => 0, 'grand_total' => 219.50,
        ]);

        $order->products()->create([
            'unique_id' => 'ORD-CTL-'.$order->id,
            'product_id' => null,
            'product_name' => 'Controller Tester',
            'price' => 200.00, 'quantity' => 1,
            'sub_total' => 200.00, 'tax' => 19.50, 'total' => 219.50,
            'special_tax' => 0, 'added_fees' => 0,
            'product_data' => json_encode(['special_tax' => 0, 'added_fees' => 0]),
        ]);

        if ($payment !== null) {
            $order->payments()->create([
                'payment_method' => OrderPaymentMethod::Cash->value,
                'payment_datetime' => now(),
                'amount' => $payment,
                'status' => OrderPaymentStatus::PartialPayment->value,
            ]);
        }

        return $order->fresh();
    }
}
