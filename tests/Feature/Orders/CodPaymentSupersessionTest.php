<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\AuthorizeNetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P0 fix — SMS_AUTOMATION_AUDIT.md F-1 / RC-1.
 *
 * A COD ("Pay on Delivery") order left its placeholder payment row at
 * status=Pending forever once the order was instead paid off through
 * another method, because nothing ever closed it out. SendPodPaymentReminderJob
 * (and its 3 sibling sub-sequences) select orders by exactly
 * `payments.payment_method = COD AND payments.status = Pending`, so it kept
 * sending payment-link/reminder SMS after the customer had already paid.
 *
 * Fix: whenever a payment fully settles the order (Order::is_paid), the
 * same inline check — `if ($order->is_paid) { ...->update(['status' =>
 * Superseded]) }` — runs right before DB::commit() in every controller that
 * can record such a payment: ReceivePaymentController,
 * Api/V1/Orders/PaymentController, ChargeCreditCardController, and
 * Front/Checkout/OrderPaymentController::store (the page the POD SMS
 * payment link points to). Target status is Superseded, not Paid — the
 * COD row's `amount` is a checkout-time placeholder equal to the full
 * grand_total, and OrderPayment::scopeSettled() sums by status, so marking
 * it Paid would double-count that amount against the order.
 */
class CodPaymentSupersessionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employee;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Gary', 'last_name' => 'Admin',
            'email' => 'gary-p0-test@example.com', 'status' => 'Active',
        ]);

        $this->employee = User::create([
            'first_name' => 'Sam', 'last_name' => 'Clerk',
            'email' => 'sam-p0-test@example.com', 'status' => 'Active',
        ]);

        $this->customer = Customer::factory()->create();

        $this->actingAs($this->admin);
    }

    private function makeCodOrder(float $grandTotal): Order
    {
        $order = Order::create([
            'order_date' => now()->format('Y-m-d'), 'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->full_name ?? 'Test Customer',
            'subtotal' => $grandTotal, 'tax_amount' => 0.0, 'grand_total' => $grandTotal,
        ]);

        // The checkout-time COD placeholder: amount = full grand_total, Pending.
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::COD->value,
            'amount' => $grandTotal,
            'status' => OrderPaymentStatus::Pending->value,
        ]);

        return $order;
    }

    /** Exactly what SendPodPaymentReminderJob's 4 sub-sequences select on. */
    private function matchesPodReminderQuery(Order $order): bool
    {
        return Order::whereKey($order->id)
            ->whereHas('payments', fn ($q) => $q->where('payment_method', 'COD')->where('status', 'Pending'))
            ->exists();
    }

    // ── Admin path (ReceivePaymentController) ───────────────────────────

    public function test_admin_full_payment_via_another_method_closes_out_the_stale_cod_row(): void
    {
        $order = $this->makeCodOrder(500.0);
        $this->assertTrue($this->matchesPodReminderQuery($order));

        $this->putJson(route('admin.order-management.orders.receive-payment', $order->unique_id), [
            'payment_type' => 'Cash', 'responsible_person' => $this->employee->id,
        ])->assertOk();

        $codPayment = $order->payments()->where('payment_method', OrderPaymentMethod::COD->value)->first();
        $this->assertSame(OrderPaymentStatus::Superseded->value, $codPayment->status->value);
        $this->assertFalse($this->matchesPodReminderQuery($order), 'POD reminder job must no longer select this order.');
    }

    public function test_admin_partial_payment_does_not_close_out_the_cod_row(): void
    {
        $order = $this->makeCodOrder(500.0);

        $this->putJson(route('admin.order-management.orders.receive-payment', $order->unique_id), [
            'payment_type' => 'Cash', 'responsible_person' => $this->employee->id,
            'partial_payment' => true, 'payment_amount' => 200.0,
        ])->assertOk();

        $codPayment = $order->payments()->where('payment_method', OrderPaymentMethod::COD->value)->first();
        $this->assertSame(OrderPaymentStatus::Pending->value, $codPayment->status->value);
        $this->assertTrue($this->matchesPodReminderQuery($order), 'Reminders must continue while a balance remains.');
    }

    // ── API path (Api/V1/Orders/PaymentController) ──────────────────────

    public function test_api_full_payment_via_another_method_closes_out_the_stale_cod_row(): void
    {
        $order = $this->makeCodOrder(500.0);

        $this->withoutMiddleware()
            ->actingAs($this->admin, 'api_user')
            ->postJson('http://' . config('app.domains.api') . '/api/admin/v1/orders/payment', [
                'order_unique_id' => $order->unique_id,
                'payment_type' => 'Cash',
                'responsible_person' => $this->employee->id,
            ])->assertOk();

        $codPayment = $order->payments()->where('payment_method', OrderPaymentMethod::COD->value)->first();
        $this->assertSame(OrderPaymentStatus::Superseded->value, $codPayment->status->value);
        $this->assertFalse($this->matchesPodReminderQuery($order), 'POD reminder job must no longer select this order.');
    }

    // ── Existing direct COD confirmation is unaffected ──────────────────

    public function test_direct_cod_confirmation_still_marks_the_cod_row_paid(): void
    {
        $order = $this->makeCodOrder(500.0);

        $this->putJson(route('admin.order-management.orders.confirm-payment', $order->unique_id))->assertOk();

        $codPayment = $order->payments()->where('payment_method', OrderPaymentMethod::COD->value)->first();
        $this->assertSame(OrderPaymentStatus::Paid->value, $codPayment->status->value);
        $this->assertFalse($this->matchesPodReminderQuery($order));
    }

    // ── Admin "charge full amount via card" path (ChargeCreditCardController) ──
    // No partial-payment option exists on this endpoint — it always charges
    // the order's full grand_total, so every successful call fully settles
    // the order. No separate partial-payment test applies here.

    public function test_charge_credit_card_full_payment_closes_out_the_stale_cod_row(): void
    {
        $order = $this->makeCodOrder(500.0);

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('validateOpaqueData')->andReturn(true);
            $mock->shouldReceive('createOpaqueDataTransaction')->once()->andReturn([
                'status' => 'success', 'transaction_id' => 'TXN-CCC-1', 'payment_status' => 'Paid',
            ]);
        });

        $this->putJson(route('admin.order-management.orders.charge-credit-card', $order->unique_id), [
            'firstName' => 'Jane', 'lastName' => 'Doe',
            'opaqueDataValue' => 'x', 'opaqueDataDescriptor' => 'y',
        ])->assertOk();

        $codPayment = $order->payments()->where('payment_method', OrderPaymentMethod::COD->value)->first();
        $this->assertSame(OrderPaymentStatus::Superseded->value, $codPayment->status->value);
        $this->assertFalse($this->matchesPodReminderQuery($order), 'POD reminder job must no longer select this order.');
    }

    // ── Customer-facing POD payment-link page (Front/Checkout/OrderPaymentController) ──
    // Always charges exactly the current balance_due, so a successful call
    // always fully settles the order — there is no partial-payment variant
    // to test here either.

    public function test_pod_payment_link_page_full_payment_closes_out_the_stale_cod_row(): void
    {
        $order = $this->makeCodOrder(500.0);

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('validateOpaqueData')->andReturn(true);
            $mock->shouldReceive('createOpaqueDataTransaction')->once()->andReturn([
                'status' => 'success', 'transaction_id' => 'TXN-PODPAY-1', 'payment_status' => 'Paid',
            ]);
        });

        $this->postJson(route('front.checkout.order-payment-form.store', encrypt($order->unique_id)), [
            'firstName' => 'Jane', 'lastName' => 'Doe',
            'opaqueDataValue' => 'x', 'opaqueDataDescriptor' => 'y',
        ])->assertOk()->assertJson(['success' => true]);

        $codPayment = $order->payments()->where('payment_method', OrderPaymentMethod::COD->value)->first();
        $this->assertSame(OrderPaymentStatus::Superseded->value, $codPayment->status->value);
        $this->assertFalse($this->matchesPodReminderQuery($order), 'POD reminder job must no longer select this order.');
    }
}
