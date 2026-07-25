<?php

namespace Tests\Feature\Orders;

use App\Enums\Billing\BillingChargeStatus;
use App\Enums\Billing\BillingChargeType;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Http\DataObjects\BillingChargeRequest;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use App\Services\AuthorizeNetService;
use App\Services\BillingEngine;
use App\Services\ExtensionPaymentSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payment & Accounts Consistency Initiative — Stage 2 (defect D6).
 *
 * When an extension child order's card payment is VOIDED, the parent
 * Extension BillingCharge must stop reading "Paid". Previously the paid-sync
 * only ever moved the charge forward to Paid and a void left it stranded, so
 * the Billing Engine showed a Paid badge for cancelled money.
 *
 * Accounting is unchanged: every extension revenue/tax report already gates
 * on the child order holding a live 'Paid' payment (the settled-extension
 * guard), so a voided extension is excluded whether or not the charge status
 * is reverted. This fix only makes the charge's own status/badge/isPaid()
 * honest — a REFUND (which keeps the original payment Paid + adds a separate
 * Refund row) is correctly left untouched.
 */
class ExtensionVoidChargeSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Gary', 'last_name' => 'Terminal',
            'email' => 'ext-void-admin@test.local', 'status' => 'Active',
        ]);
        $this->employee = User::create([
            'first_name' => 'John', 'last_name' => 'Smith',
            'email' => 'ext-void-emp@test.local', 'status' => 'Active',
        ]);

        $this->actingAs($this->admin);
    }

    private function customer(): Customer
    {
        return Customer::create([
            'first_name' => 'Ext', 'last_name' => 'Void',
            'email' => 'ext-void-' . uniqid() . '@example.com', 'status' => 'Active',
        ]);
    }

    /**
     * A parent order + an extension child whose Extension charge is Paid.
     * $withCardPayment adds a settled Card payment on the child (needed to
     * exercise the void controller end-to-end).
     *
     * @return array{0: Order, 1: Order, 2: BillingCharge}
     */
    private function paidExtension(bool $withCardPayment = false): array
    {
        $customer = $this->customer();
        $parent   = Order::create([
            'order_number' => 'EV' . random_int(1000, 999999), 'order_date' => now()->toDateString(),
            'customer_id' => $customer->id, 'customer_name' => $customer->full_name, 'grand_total' => 44,
        ]);
        $child = Order::create([
            'order_number' => $parent->order_number . '-A', 'reference_order_number' => $parent->order_number,
            'order_date' => now()->toDateString(), 'customer_id' => $customer->id,
            'customer_name' => $customer->full_name, 'grand_total' => 44,
        ]);

        $charge = BillingEngine::charge(new BillingChargeRequest(
            type: BillingChargeType::Extension->value,
            orderId: $parent->id, customerId: $customer->id, amount: 40, taxType: 'add',
            childOrderId: $child->id, responsiblePersonId: $this->admin->id,
            idempotencyKey: 'ev_' . $child->id, taxAmount: 4.0,
        ));
        // Simulate the child being paid — the same forward transition the
        // payment listeners drive via ExtensionPaymentSyncService::syncParentCharge.
        BillingEngine::markPaid($charge);

        if ($withCardPayment) {
            $child->payments()->create([
                'payment_datetime' => now(),
                'payment_method'   => OrderPaymentMethod::Card->value,
                'amount'           => 44,
                'status'           => OrderPaymentStatus::Paid->value,
                'transaction_id'   => 'TXN-EV-' . $child->id,
            ]);
        }

        return [$parent, $child, $charge->fresh()];
    }

    // ── BillingEngine::markVoided ────────────────────────────────────────

    public function test_mark_voided_sets_status_and_is_idempotent(): void
    {
        [, , $charge] = $this->paidExtension();
        $this->assertTrue($charge->isPaid());

        BillingEngine::markVoided($charge, $this->admin->id);
        $this->assertSame(BillingChargeStatus::Voided, $charge->fresh()->status);

        // Idempotent — a second call is a no-op, no exception.
        BillingEngine::markVoided($charge->fresh(), $this->admin->id);
        $this->assertSame(BillingChargeStatus::Voided, $charge->fresh()->status);
    }

    // ── revertParentChargeOnVoid ─────────────────────────────────────────

    public function test_revert_reverts_a_paid_extension_charge_to_voided(): void
    {
        [, $child, $charge] = $this->paidExtension();

        ExtensionPaymentSyncService::revertParentChargeOnVoid($child, $this->admin->id);

        $charge->refresh();
        $this->assertSame(BillingChargeStatus::Voided, $charge->status);
        $this->assertFalse($charge->isPaid(), 'charge no longer reads Paid');
    }

    public function test_revert_is_noop_for_a_pending_extension_charge(): void
    {
        [$parent, $child] = $this->paidExtension();
        // A fresh, unpaid extension charge on a different child.
        $child2 = Order::create([
            'order_number' => $parent->order_number . '-B', 'reference_order_number' => $parent->order_number,
            'order_date' => now()->toDateString(), 'customer_id' => $parent->customer_id,
            'customer_name' => $parent->customer_name, 'grand_total' => 20,
        ]);
        $charge2 = BillingEngine::charge(new BillingChargeRequest(
            type: BillingChargeType::Extension->value, orderId: $parent->id, customerId: $parent->customer_id,
            amount: 20, taxType: 'free', childOrderId: $child2->id, idempotencyKey: 'ev2_' . $child2->id, taxAmount: 0.0,
        ));

        ExtensionPaymentSyncService::revertParentChargeOnVoid($child2, $this->admin->id);

        $this->assertSame(BillingChargeStatus::Pending, $charge2->fresh()->status, 'pending charge untouched');
    }

    public function test_revert_is_noop_for_a_non_extension_order(): void
    {
        $customer = $this->customer();
        $plainOrder = Order::create([
            'order_number' => 'PLAIN' . random_int(1000, 9999), 'order_date' => now()->toDateString(),
            'customer_id' => $customer->id, 'customer_name' => $customer->full_name, 'grand_total' => 100,
        ]);

        // No extension charge exists — must simply do nothing, never throw.
        ExtensionPaymentSyncService::revertParentChargeOnVoid($plainOrder, $this->admin->id);

        $this->assertSame(0, BillingCharge::where('child_order_id', $plainOrder->id)->count());
    }

    // ── End-to-end: void controller reverts the charge ───────────────────

    public function test_voiding_the_child_card_payment_reverts_the_parent_charge(): void
    {
        [, $child, $charge] = $this->paidExtension(withCardPayment: true);
        $payment = $child->payments()->latest('id')->firstOrFail();

        $this->mock(AuthorizeNetService::class, function ($mock) use ($payment) {
            $mock->shouldReceive('getTransactionDetails')
                ->once()->with($payment->transaction_id)
                ->andReturn((object) ['status' => 'capturedPendingSettlement']);
            $mock->shouldReceive('voidOrder')->once()->andReturn(['status' => 'success']);
        });

        $this->putJson(route('admin.order-management.orders.void-payment', $child->unique_id), [
            'reason'           => 'billing_error',
            'processed_by'     => $this->employee->id,
            'employee_code'    => $this->employee->employee_code,
            'order_payment_id' => $payment->id,
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame(OrderPaymentStatus::Voided, $payment->fresh()->status);
        $this->assertSame(BillingChargeStatus::Voided, $charge->fresh()->status, 'parent extension charge reverted');
    }
}
