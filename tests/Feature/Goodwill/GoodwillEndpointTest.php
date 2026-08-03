<?php

namespace Tests\Feature\Goodwill;

use App\Enums\Goodwill\GoodwillReason;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Goodwill\OrderGoodwillAdjustment;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\Goodwill\GoodwillFailure;
use App\Services\Goodwill\GoodwillPermissions;
use Database\Seeders\Iam\GoodwillPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Increment G3 — the admin Goodwill endpoints and the Pending Payment UI.
 *
 * The controllers are deliberately thin: they validate the SHAPE of a request,
 * resolve two models, and hand the decision to `GoodwillAdjustmentService`.
 * So these tests are not re-proving the financial behaviour — G2 does that —
 * they prove the BOUNDARY: that authority still refuses through the Gate
 * bypass, that typed failures reach the client with the status the domain
 * assigned them, that both expected values are carried and re-checked, and
 * that the screen never offers an action the server would refuse.
 */
class GoodwillEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Customer $customer;

    private int $productId;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'End', 'last_name' => 'Point',
            'email' => 'gw-endpoint@test.local', 'status' => 'Active',
        ]);

        $this->productId = (int) DB::table('products')->insertGetId([
            'unique_id' => 'PRD-GWE-1', 'product_name' => 'Endpoint Probe',
            'slug' => 'gw-endpoint-probe', 'created_at' => now(), 'updated_at' => now(),
        ]);

        (new GoodwillPermissionSeeder())->run();

        $this->manager = $this->user('gw-endpoint-manager@test.local', [
            GoodwillPermissions::APPLY, GoodwillPermissions::REVERSE,
        ]);
    }

    // ── Preview ────────────────────────────────────────────────────────────

    public function test_preview_returns_the_full_breakdown_and_writes_nothing(): void
    {
        $order = $this->paidOrder(200.00, 19.50, 200.00, special: 4.00, fees: 6.00);
        $before = DB::table('orders')->where('id', $order->id)->first();

        $response = $this->actingAs($this->manager)->getJson($this->previewUrl($order));

        $response->assertOk()->assertJson(['success' => true]);

        foreach ([
            'amount_collected', 'remaining_balance', 'goodwill_amount', 'rounding_residual',
            'discounted_product_value', 'sales_tax', 'special_tax', 'added_fees',
            'revised_grand_total',
        ] as $key) {
            $response->assertJsonPath("breakdown.{$key}", fn ($v) => $v !== null, "breakdown.{$key}");
        }

        // Compared as numbers, not identically: JSON has one number type, so a
        // whole-dollar figure arrives as an int however it was encoded.
        $this->assertEquals(200.00, $response->json('breakdown.amount_collected'));
        $this->assertEquals(200.00, $response->json('breakdown.revised_grand_total'));
        $this->assertEquals(200.00, $response->json('expected_accepted_payment_total'));
        // 200.00 + 19.50 tax + 4.00 special + 6.00 fees = 229.50, less 200.00 collected.
        $this->assertEquals(29.50, $response->json('breakdown.remaining_balance'));
        $response->assertJsonPath('is_exact_close', true);

        $this->assertEquals($before, DB::table('orders')->where('id', $order->id)->first(), 'Preview must not write.');
        $this->assertSame(0, OrderGoodwillAdjustment::count());
    }

    public function test_preview_refuses_a_user_without_the_permission(): void
    {
        // The route middleware cannot refuse — Gate::before consents for every
        // signed-in user — so the 403 must come from the service.
        $order = $this->paidOrder(200.00, 19.50, 200.00);
        $nobody = $this->user('gw-endpoint-nobody@test.local');

        $this->assertTrue($nobody->can(GoodwillPermissions::APPLY), 'Baseline: the Gate consents.');

        $this->actingAs($nobody)->getJson($this->previewUrl($order))
            ->assertStatus(403)
            ->assertJson(['success' => false, 'code' => GoodwillFailure::Unauthorized->value]);
    }

    public function test_preview_reports_the_typed_reason_an_order_is_ineligible(): void
    {
        $order = $this->order(200.00, 19.50); // no payment at all

        $this->actingAs($this->manager)->getJson($this->previewUrl($order))
            ->assertStatus(422)
            ->assertJson(['code' => GoodwillFailure::NoSettledPayment->value]);
    }

    // ── Apply ──────────────────────────────────────────────────────────────

    public function test_apply_settles_the_order_and_links_the_refreshed_receipt(): void
    {
        $order = $this->paidOrder(200.00, 19.50, 200.00);

        $response = $this->actingAs($this->manager)->postJson($this->applyUrl($order), $this->payload($order));

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertEquals(17.77, $response->json('goodwill_amount'));
        $this->assertEquals(0.00, $response->json('rounding_residual'));
        $this->assertEquals(200.00, $response->json('revised_grand_total'));
        $this->assertEquals(0.00, $response->json('balance_due'));

        $this->assertStringContainsString('receipt-download', $response->json('receipt_url'));

        $order->refresh();
        $this->assertTrue($order->is_paid);
        $this->assertSame(1, $order->payments()->count(), 'Goodwill creates no payment row.');
    }

    public function test_apply_carries_both_expected_values_and_refuses_a_stale_payment_total(): void
    {
        $order = $this->paidOrder(200.00, 19.50, 190.00);
        $payload = $this->payload($order);

        // The customer pays a little more between preview and submit.
        $this->pay($order, 4.00);

        $this->actingAs($this->manager)->postJson($this->applyUrl($order), $payload)
            ->assertStatus(409)
            ->assertJson(['code' => GoodwillFailure::StaleState->value]);

        $this->assertSame('0.00', (string) $order->fresh()->pretax_discount_total);
        $this->assertSame(0, OrderGoodwillAdjustment::count());
    }

    public function test_apply_refuses_a_concession_that_no_longer_matches_the_preview(): void
    {
        // The collected total is unchanged; the ORDER moved, so the required
        // concession is no longer the one that was approved.
        $order = $this->paidOrder(200.00, 19.50, 190.00);
        $payload = $this->payload($order);
        $payload['expected_goodwill_amount'] = 5.00;

        $this->actingAs($this->manager)->postJson($this->applyUrl($order), $payload)
            ->assertStatus(409)
            ->assertJson(['code' => GoodwillFailure::StaleState->value]);
    }

    public function test_apply_refuses_other_without_a_note_using_the_typed_failure(): void
    {
        // Deliberately NOT a validation rule: the service owns it, so the
        // condition has one error shape rather than two.
        $order = $this->paidOrder(200.00, 19.50, 200.00);

        $this->actingAs($this->manager)->postJson(
            $this->applyUrl($order),
            $this->payload($order, ['reason_code' => GoodwillReason::Other->value, 'note' => '   '])
        )
            ->assertStatus(422)
            ->assertJson(['code' => GoodwillFailure::NoteRequired->value]);

        $this->assertSame(0, OrderGoodwillAdjustment::count());
    }

    public function test_apply_accepts_other_with_a_note(): void
    {
        $order = $this->paidOrder(200.00, 19.50, 200.00);

        $this->actingAs($this->manager)->postJson(
            $this->applyUrl($order),
            $this->payload($order, [
                'reason_code' => GoodwillReason::Other->value,
                'note' => 'Regional manager approved verbally after a site visit.',
            ])
        )->assertOk();

        $this->assertSame('other', OrderGoodwillAdjustment::first()->reason_category->value);
    }

    public function test_apply_refuses_a_user_the_gate_accepts_but_spatie_does_not(): void
    {
        // The middleware on this route calls canAny(), which the Gate bypass
        // answers true for. The 403 must therefore come from the service.
        $order = $this->paidOrder(200.00, 19.50, 200.00);
        $payload = $this->payload($order);
        $nobody = $this->user('gw-endpoint-apply-nobody@test.local');

        $this->assertTrue($nobody->can(GoodwillPermissions::APPLY), 'Baseline: the Gate consents.');

        $this->actingAs($nobody)->postJson($this->applyUrl($order), $payload)
            ->assertStatus(403)
            ->assertJson(['code' => GoodwillFailure::Unauthorized->value]);

        $this->assertSame(0, OrderGoodwillAdjustment::count());
        $this->assertSame('0.00', (string) $order->fresh()->pretax_discount_total);
    }

    public function test_the_returned_receipt_url_serves_the_refreshed_receipt(): void
    {
        $order = $this->paidOrder(200.00, 19.50, 200.00, special: 4.00);

        // A receipt that already exists at the pre-Goodwill total — the case
        // where a stale snapshot would actually be visible to a customer.
        $receipt = \App\Services\ReceiptService::getOrCreateReceipt($order->fresh());
        $this->assertSame('223.50', (string) $receipt->total);

        $this->actingAs($this->manager)->postJson($this->applyUrl($order), $this->payload($order))->assertOk();

        $order->refresh();
        $refreshed = $receipt->fresh();

        $this->assertSame((string) $order->grand_total, (string) $refreshed->total, 'Refreshed by apply, not by a later read.');
        $this->assertSame((string) $order->pretax_discount_total, (string) $refreshed->pretax_discount_total);
        $this->assertSame((string) $order->special_tax_amount, (string) $refreshed->special_tax);
    }

    public function test_apply_refuses_an_unauthorised_approver_even_from_an_authorised_operator(): void
    {
        $order = $this->paidOrder(200.00, 19.50, 200.00);
        $notAManager = $this->user('gw-endpoint-not-manager@test.local');

        $this->actingAs($this->manager)->postJson(
            $this->applyUrl($order),
            $this->payload($order, ['approved_by' => $notAManager->id])
        )
            ->assertStatus(403)
            ->assertJson(['code' => GoodwillFailure::ApproverUnauthorized->value]);
    }

    public function test_apply_refuses_an_invoiced_order(): void
    {
        $order = $this->paidOrder(200.00, 19.50, 200.00);
        $payload = $this->payload($order);
        $order->update(['invoice_id' => $this->invoice()]);

        $this->actingAs($this->manager)->postJson($this->applyUrl($order), $payload)
            ->assertStatus(422)
            ->assertJson(['code' => GoodwillFailure::Invoiced->value]);
    }

    public function test_apply_refuses_a_fully_paid_order(): void
    {
        $order = $this->order(200.00, 19.50);
        $this->pay($order, 219.50);

        $this->actingAs($this->manager)->postJson($this->applyUrl($order), [
            'reason_code' => GoodwillReason::ServiceFailure->value,
            'approved_by' => $this->manager->id,
            'expected_goodwill_amount' => 5.00,
            'expected_accepted_payment_total' => 219.50,
            'idempotency_token' => 'gw-full-paid',
        ])
            ->assertStatus(422)
            ->assertJson(['code' => GoodwillFailure::AlreadyPaidInFull->value]);
    }

    public function test_apply_validates_the_shape_of_the_request(): void
    {
        $order = $this->paidOrder(200.00, 19.50, 200.00);

        $this->actingAs($this->manager)->postJson($this->applyUrl($order), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'reason_code', 'approved_by', 'expected_goodwill_amount',
                'expected_accepted_payment_total', 'idempotency_token',
            ]);

        $this->actingAs($this->manager)->postJson(
            $this->applyUrl($order),
            $this->payload($order, ['reason_code' => 'NOT_A_REASON'])
        )->assertJsonValidationErrors(['reason_code']);
    }

    public function test_a_replayed_submission_returns_the_original_decision(): void
    {
        $order = $this->paidOrder(200.00, 19.50, 200.00);
        $payload = $this->payload($order);

        $first = $this->actingAs($this->manager)->postJson($this->applyUrl($order), $payload)->assertOk();
        $again = $this->actingAs($this->manager)->postJson($this->applyUrl($order), $payload)->assertOk();

        $this->assertSame($first->json('adjustment_id'), $again->json('adjustment_id'));
        $this->assertSame(1, OrderGoodwillAdjustment::count());
        $this->assertSame('17.77', (string) $order->fresh()->pretax_discount_total, 'Applied once, not twice.');
    }

    // ── Reverse ────────────────────────────────────────────────────────────

    public function test_reverse_reopens_the_balance_and_requires_a_reason(): void
    {
        $order = $this->paidOrder(200.00, 19.50, 200.00);
        $adjustmentId = $this->actingAs($this->manager)
            ->postJson($this->applyUrl($order), $this->payload($order))->json('adjustment_id');

        $this->actingAs($this->manager)->deleteJson($this->reverseUrl($order, $adjustmentId), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);

        $this->assertFalse(OrderGoodwillAdjustment::find($adjustmentId)->isReversed(), 'Nothing reversed yet.');

        $this->actingAs($this->manager)->deleteJson($this->reverseUrl($order, $adjustmentId), [
            'reason' => 'Customer disputed the resolution.',
        ])->assertOk()->assertJsonPath('revised_grand_total', 219.5);

        $order->refresh();
        $this->assertTrue(OrderGoodwillAdjustment::find($adjustmentId)->isReversed());
        $this->assertSame(19.50, round((float) $order->balance_due, 2));
        $this->assertSame(1, $order->payments()->count(), 'The real payment is untouched.');
    }

    public function test_reverse_requires_its_own_permission(): void
    {
        $order = $this->paidOrder(200.00, 19.50, 200.00);
        $adjustmentId = $this->actingAs($this->manager)
            ->postJson($this->applyUrl($order), $this->payload($order))->json('adjustment_id');

        $applyOnly = $this->user('gw-endpoint-apply-only@test.local', [GoodwillPermissions::APPLY]);

        $this->actingAs($applyOnly)->deleteJson($this->reverseUrl($order, $adjustmentId), ['reason' => 'nope'])
            ->assertStatus(403)
            ->assertJson(['code' => GoodwillFailure::Unauthorized->value]);

        $this->assertFalse(OrderGoodwillAdjustment::find($adjustmentId)->isReversed());
    }

    public function test_reverse_refuses_an_already_reversed_adjustment(): void
    {
        $order = $this->paidOrder(200.00, 19.50, 200.00);
        $adjustmentId = $this->actingAs($this->manager)
            ->postJson($this->applyUrl($order), $this->payload($order))->json('adjustment_id');

        $this->actingAs($this->manager)->deleteJson($this->reverseUrl($order, $adjustmentId), ['reason' => 'first'])->assertOk();

        $this->actingAs($this->manager)->deleteJson($this->reverseUrl($order, $adjustmentId), ['reason' => 'second'])
            ->assertStatus(422)
            ->assertJson(['code' => GoodwillFailure::AlreadyReversed->value]);
    }

    public function test_an_adjustment_cannot_be_reversed_through_another_orders_url(): void
    {
        $order = $this->paidOrder(200.00, 19.50, 200.00);
        $adjustmentId = $this->actingAs($this->manager)
            ->postJson($this->applyUrl($order), $this->payload($order))->json('adjustment_id');

        $other = $this->paidOrder(100.00, 0.00, 90.00);

        $this->actingAs($this->manager)->deleteJson($this->reverseUrl($other, $adjustmentId), ['reason' => 'wrong order'])
            ->assertNotFound();

        $this->assertFalse(OrderGoodwillAdjustment::find($adjustmentId)->isReversed());
    }

    // ── The screen ─────────────────────────────────────────────────────────

    public function test_the_trigger_appears_only_on_a_part_paid_order_for_an_authorised_user(): void
    {
        $eligible = $this->paidOrder(200.00, 19.50, 200.00);

        $this->assertStringContainsString('Apply Goodwill', $this->renderPanel($eligible, $this->manager));
    }

    public function test_the_trigger_is_absent_without_the_permission(): void
    {
        $order = $this->paidOrder(200.00, 19.50, 200.00);
        $nobody = $this->user('gw-endpoint-ui-nobody@test.local');

        $html = $this->renderPanel($order, $nobody);

        $this->assertStringNotContainsString('Apply Goodwill', $html);
        $this->assertStringNotContainsString('goodwillModal', $html);
    }

    public function test_the_trigger_is_absent_until_a_settled_payment_exists(): void
    {
        $unpaid = $this->order(200.00, 19.50);

        $this->assertStringNotContainsString('Apply Goodwill', $this->renderPanel($unpaid, $this->manager));
    }

    public function test_the_trigger_is_absent_on_a_fully_paid_order(): void
    {
        $order = $this->order(200.00, 19.50);
        $this->pay($order, 219.50);

        $this->assertStringNotContainsString('Apply Goodwill', $this->renderPanel($order, $this->manager));
    }

    public function test_the_trigger_is_absent_on_an_invoiced_order(): void
    {
        $order = $this->paidOrder(200.00, 19.50, 200.00);
        $order->update(['invoice_id' => $this->invoice()]);

        $this->assertStringNotContainsString('Apply Goodwill', $this->renderPanel($order->fresh(), $this->manager));
    }

    public function test_the_panel_renders_without_error_when_the_permission_is_unseeded(): void
    {
        // A deployment that has not run the seeder must lose the ACTION, not
        // the page. GoodwillPermissions fails closed and logs; nothing here
        // may surface a PermissionDoesNotExist.
        Permission::whereIn('name', array_keys(GoodwillPermissions::all()))->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $order = $this->paidOrder(200.00, 19.50, 200.00);

        $html = $this->renderPanel($order, $this->manager);

        $this->assertStringNotContainsString('Apply Goodwill', $html);
        $this->assertStringNotContainsString('goodwillModal', $html);
    }

    public function test_the_reversal_control_appears_only_for_an_active_adjustment(): void
    {
        $order = $this->paidOrder(200.00, 19.50, 200.00);

        $this->assertStringNotContainsString('Reverse Goodwill', $this->renderPanel($order, $this->manager));

        $adjustmentId = $this->actingAs($this->manager)
            ->postJson($this->applyUrl($order), $this->payload($order))->json('adjustment_id');

        $applied = $this->renderPanel($order->fresh(), $this->manager);
        $this->assertStringContainsString('Reverse Goodwill', $applied);
        $this->assertStringContainsString('Goodwill - Pre-Tax', $applied);
        $this->assertStringNotContainsString('Apply Goodwill', $applied, 'One active adjustment at a time.');

        // …and not for a user who may apply but not reverse.
        $applyOnly = $this->user('gw-endpoint-ui-apply-only@test.local', [GoodwillPermissions::APPLY]);
        $this->assertStringNotContainsString('Reverse Goodwill', $this->renderPanel($order->fresh(), $applyOnly));

        $this->actingAs($this->manager)->deleteJson($this->reverseUrl($order, $adjustmentId), ['reason' => 'withdrawn']);

        $this->assertStringNotContainsString('Reverse Goodwill', $this->renderPanel($order->fresh(), $this->manager));
    }

    public function test_the_modal_states_that_goodwill_is_not_a_payment(): void
    {
        $order = $this->paidOrder(200.00, 19.50, 200.00);

        $html = $this->renderPanel($order, $this->manager);

        $this->assertStringContainsString(
            'Goodwill changes the order total. It does not record another payment.',
            $html
        );
    }

    public function test_the_modal_lists_every_line_of_the_approved_sequence(): void
    {
        $order = $this->paidOrder(200.00, 19.50, 200.00, special: 4.00, fees: 6.00);

        $html = $this->renderPanel($order, $this->manager);

        foreach ([
            'Amount Collected', 'Current Balance', 'Goodwill - Pre-Tax', 'Rounding Residual',
            'Discounted Product Value', 'Sales Tax', 'Special Tax', 'Added Fees', 'Revised Order Total',
        ] as $label) {
            $this->assertStringContainsString($label, $html, "Missing breakdown line: {$label}");
        }

        // Reason options are grouped by the enum's own categories.
        $this->assertStringContainsString('Service Recovery', $html);
        $this->assertStringContainsString('Business Courtesy', $html);
        $this->assertStringContainsString('SERVICE_FAILURE', $html);
    }

    public function test_the_modal_offers_only_users_holding_goodwill_approval(): void
    {
        $this->user('gw-endpoint-outsider@test.local'); // no permission
        $order = $this->paidOrder(200.00, 19.50, 200.00);

        $html = $this->renderPanel($order, $this->manager);

        $this->assertStringContainsString('value="'.$this->manager->id.'"', $html);
        $this->assertStringNotContainsString('gw-endpoint-outsider', $html);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function renderPanel(Order $order, User $as): string
    {
        $this->actingAs($as);

        return view('admin.order_management.orders.partials._goodwill_adjustment_panel', [
            'order' => $order,
        ])->render();
    }

    private function payload(Order $order, array $overrides = []): array
    {
        $preview = $this->actingAs($this->manager)->getJson($this->previewUrl($order))->json();

        return array_merge([
            'reason_code' => GoodwillReason::ServiceFailure->value,
            'note' => null,
            'approved_by' => $this->manager->id,
            'expected_goodwill_amount' => $preview['expected_goodwill_amount'] ?? 0,
            'expected_accepted_payment_total' => $preview['expected_accepted_payment_total'] ?? 0,
            'idempotency_token' => 'gw-endpoint-'.$order->id.'-'.(++$this->sequence),
        ], $overrides);
    }

    private function previewUrl(Order $order): string
    {
        return route('admin.order-management.orders.goodwill.preview', $order->unique_id);
    }

    private function applyUrl(Order $order): string
    {
        return route('admin.order-management.orders.goodwill.apply', $order->unique_id);
    }

    private function reverseUrl(Order $order, int $adjustmentId): string
    {
        return route('admin.order-management.orders.goodwill.reverse', [$order->unique_id, $adjustmentId]);
    }

    private function user(string $email, array $permissions = []): User
    {
        $user = User::create([
            'first_name' => 'GW', 'last_name' => 'User',
            'email' => $email, 'status' => 'Active',
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function invoice(): int
    {
        return (int) \App\Models\Customers\Invoice::create([
            'invoice_number' => 'INV-GWE-'.(++$this->sequence),
            'invoice_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'invoice_created_by' => $this->manager->id,
            'subtotal' => 200.00, 'sales_tax' => 19.50, 'total' => 219.50,
        ])->id;
    }

    private function paidOrder(float $sub, float $tax, float $paid, float $special = 0.0, float $fees = 0.0): Order
    {
        $order = $this->order($sub, $tax, $special, $fees);
        $this->pay($order, $paid);

        return $order->fresh();
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

    private function order(float $sub, float $tax, float $special = 0.0, float $fees = 0.0): Order
    {
        $order = Order::create([
            'order_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'customer_name' => 'End Point',
            'subtotal' => $sub, 'tax_amount' => $tax,
            'special_tax_amount' => $special, 'added_fees_amount' => $fees,
            'discount_amount' => 0, 'pretax_discount_total' => 0,
            'grand_total' => $sub + $tax + $special + $fees,
        ]);

        $order->products()->create([
            'unique_id' => 'ORD-GWE-'.$order->id,
            'product_id' => $this->productId,
            'product_name' => 'Endpoint Probe',
            'price' => $sub, 'quantity' => 1,
            'sub_total' => $sub, 'tax' => $tax,
            'special_tax' => $special, 'added_fees' => $fees,
            'total' => $sub + $tax + $special + $fees,
            'product_data' => json_encode([
                'sub_total' => $sub, 'tax' => $tax, 'special_tax' => $special, 'added_fees' => $fees,
            ]),
        ]);

        return $order->fresh();
    }
}
