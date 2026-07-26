<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use App\Services\ExtensionTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Extension deletion lifecycle: an extension child order ("2996-A") and its
 * Rental Extension BillingCharge are ONE transaction — deleting either side
 * must soft-delete both and never leave a phantom.
 *
 * Payment & Accounts Consistency Initiative — Phase 10: EVERY extension delete
 * now requires an administrative disposition (verified employee + Employee ID
 * PIN + reason), not only a paid-with-no-refund/void one. The payment state is
 * still recorded in the audit trail, it just no longer gates authorization.
 */
class ExtensionTransactionDeletionTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $employee;
    private Order $parent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Life', 'last_name' => 'Cycle',
            'email' => 'lifecycle@example.com', 'status' => 'Active',
        ]);

        $this->employee = User::create([
            'first_name' => 'Del', 'last_name' => 'Clerk',
            'email' => 'del-clerk@example.com', 'password' => bcrypt('secret'),
            'status' => 'Active',
        ]);

        $this->parent = Order::create([
            'order_number'  => '2996',
            'order_date'    => now()->toDateString(),
            'customer_id'   => $this->customer->id,
            'customer_name' => 'Life Cycle',
        ]);

        $this->actingAs($this->employee);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private function createExtension(): array
    {
        $this->postJson(
            route('admin.order-management.orders.extension.store', ['unique_id' => $this->parent->unique_id]),
            [
                'description'        => 'One extra week',
                'base_amount'        => '150.00',
                'add_tax'            => false,
                'responsible_person' => $this->employee->id,
            ]
        )->assertOk()->assertJson(['success' => true]);

        $child  = Order::where('order_number', 'like', '2996-%')->latest('id')->firstOrFail();
        $charge = BillingCharge::where('child_order_id', $child->id)->firstOrFail();

        return [$child, $charge];
    }

    private function payChargeCash(BillingCharge $charge): void
    {
        $this->post(route('admin.dashboard.paymentstore'), [
            'source'                   => 'crm',
            'type'                     => 'extension',
            'billing_charge_unique_id' => $charge->unique_id,
            'customer_id'              => $this->customer->id,
            'amount'                   => '150.00',
            'payment_type'             => 'Cash',
            'responsible_person'       => $this->employee->id,
        ])->assertRedirect();
    }

    private function deleteViaChildOrder(Order $child, array $extra = [])
    {
        return $this->postJson(
            route('admin.order-management.orders.bulk-delete'),
            array_merge(['unique_ids' => [$child->unique_id]], $extra)
        );
    }

    private function deleteViaBillingRow(BillingCharge $charge, array $extra = [])
    {
        return $this->postJson(
            route('admin.order-management.orders.billing-charges.delete', $charge->unique_id),
            $extra
        );
    }

    private function disposition(array $overrides = []): array
    {
        return array_merge([
            'processed_by'  => $this->employee->id,
            'employee_code' => $this->employee->employee_code,
            'reason'        => 'refunded_through_gateway',
            'notes'         => 'Refunded in the Authorize.net portal, ref 998877.',
        ], $overrides);
    }

    private function parentDeletionHistory()
    {
        return $this->parent->history()
            ->where('action', OrderHistoryAction::ExtensionChargeDeleted->value)
            ->get();
    }

    // ── Every extension delete REQUIRES a disposition (Phase 10) ─────────

    public function test_unpaid_extension_is_blocked_without_disposition_from_billing_row(): void
    {
        [$child, $charge] = $this->createExtension();

        $this->deleteViaBillingRow($charge)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['processed_by', 'employee_code', 'reason']);

        $this->assertFalse($child->fresh()->trashed());
        $this->assertNull($charge->fresh()->deleted_at);
        $this->assertCount(0, $this->parentDeletionHistory());
    }

    public function test_unpaid_extension_is_blocked_without_disposition_from_bulk(): void
    {
        [$child, $charge] = $this->createExtension();

        $this->deleteViaChildOrder($child)
            ->assertStatus(422)
            ->assertJson(['success' => false, 'requires_disposition' => true]);

        $this->assertFalse($child->fresh()->trashed());
        $this->assertNull($charge->fresh()->deleted_at);
        $this->assertCount(0, $this->parentDeletionHistory());
    }

    public function test_wrong_employee_code_rejects_deletion(): void
    {
        [$child, $charge] = $this->createExtension();

        $this->deleteViaBillingRow($charge, $this->disposition(['employee_code' => 'WRONG-CODE']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['employee_code']);

        $this->assertFalse($child->fresh()->trashed());
        $this->assertNull($charge->fresh()->deleted_at);
    }

    // ── Deletes WITH a valid disposition, in every payment state ─────────

    public function test_unpaid_extension_deletes_with_disposition_from_billing_row(): void
    {
        [$child, $charge] = $this->createExtension();

        $this->deleteViaBillingRow($charge, $this->disposition())
            ->assertOk()->assertJson(['success' => true]);

        $this->assertNotNull(BillingCharge::withTrashed()->find($charge->id)->deleted_at);
        $this->assertTrue($child->fresh()->trashed());
        $this->assertFalse($this->parent->fresh()->trashed());

        $extras = json_decode($this->parentDeletionHistory()->firstOrFail()->extras, true);
        $this->assertSame(ExtensionTransactionService::ENTRY_BILLING_ROW, $extras['entry_point']);
        $this->assertSame(ExtensionTransactionService::STATE_UNPAID, $extras['payment_state']);
        $this->assertSame($this->employee->id, $extras['processed_by_id']);
        $this->assertSame('refunded_through_gateway', $extras['processed_reason_code']);
        $this->assertSame($child->order_number, $extras['child_order_number']);
    }

    public function test_unpaid_extension_deletes_with_disposition_from_child_order(): void
    {
        [$child, $charge] = $this->createExtension();

        $this->deleteViaChildOrder($child, $this->disposition(['reason' => 'duplicate_extension']))
            ->assertOk()->assertJson(['success' => true]);

        $this->assertTrue($child->fresh()->trashed());
        $this->assertNotNull(BillingCharge::withTrashed()->find($charge->id)->deleted_at);

        $extras = json_decode($this->parentDeletionHistory()->firstOrFail()->extras, true);
        $this->assertSame(ExtensionTransactionService::ENTRY_CHILD_ORDER, $extras['entry_point']);
        $this->assertSame('duplicate_extension', $extras['processed_reason_code']);
    }

    public function test_paid_unresolved_extension_deletes_with_disposition(): void
    {
        [$child, $charge] = $this->createExtension();
        $this->payChargeCash($charge);

        $this->deleteViaBillingRow($charge, $this->disposition())
            ->assertOk()->assertJson(['success' => true]);

        $this->assertTrue($child->fresh()->trashed());
        $this->assertNotNull(BillingCharge::withTrashed()->find($charge->id)->deleted_at);

        $extras = json_decode($this->parentDeletionHistory()->firstOrFail()->extras, true);
        $this->assertSame(ExtensionTransactionService::STATE_PAID_UNRESOLVED, $extras['payment_state']);
        $this->assertSame($this->employee->id, $extras['processed_by_id']);
        $this->assertSame('Refunded in the Authorize.net portal, ref 998877.', $extras['notes']);
        $this->assertSame($this->parent->order_number, $extras['parent_order_number']);
    }

    public function test_paid_settled_extension_deletes_with_disposition(): void
    {
        [$child, $charge] = $this->createExtension();
        $this->payChargeCash($charge);

        // A Kabba-recorded refund settles the money trail (paid_settled).
        $child->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount'           => 150,
            'status'           => OrderPaymentStatus::Refund->value,
        ]);
        $this->assertSame(
            ExtensionTransactionService::STATE_PAID_SETTLED,
            ExtensionTransactionService::paymentState($charge->fresh(), $child->fresh())
        );

        // Still requires a disposition (Phase 10) — but now succeeds with one.
        $this->deleteViaBillingRow($charge, $this->disposition())
            ->assertOk()->assertJson(['success' => true]);
        $this->assertTrue($child->fresh()->trashed());

        $extras = json_decode($this->parentDeletionHistory()->firstOrFail()->extras, true);
        $this->assertSame(ExtensionTransactionService::STATE_PAID_SETTLED, $extras['payment_state']);
    }

    public function test_parent_order_and_unrelated_records_remain_intact(): void
    {
        [, $charge] = $this->createExtension();

        $fuelCharge = BillingCharge::create([
            'billing_charge_type' => 'fuel', 'status' => 'pending',
            'parent_order_id' => $this->parent->id, 'customer_id' => $this->customer->id,
            'amount' => 40, 'tax_amount' => 0,
        ]);
        $parentPayment = $this->parent->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->deleteViaBillingRow($charge, $this->disposition())->assertOk();

        $this->assertFalse($this->parent->fresh()->trashed());
        $this->assertNull($fuelCharge->fresh()->deleted_at);
        $this->assertNull($parentPayment->fresh()->deleted_at);
    }

    // ── Idempotency and one-sided repair (all with a disposition) ───────

    public function test_repeated_deletion_is_idempotent(): void
    {
        [$child, $charge] = $this->createExtension();

        $this->deleteViaBillingRow($charge, $this->disposition())->assertOk();
        $this->deleteViaBillingRow($charge, $this->disposition())->assertOk()->assertJson(['success' => true]);

        // Still exactly one audit entry — replays are no-ops.
        $this->assertCount(1, $this->parentDeletionHistory());
        $this->assertFalse($this->parent->fresh()->trashed());
    }

    public function test_deleting_child_whose_charge_is_already_gone_still_works(): void
    {
        [$child, $charge] = $this->createExtension();

        $charge->delete(); // counterpart removed out-of-band

        $this->deleteViaChildOrder($child, $this->disposition())->assertOk()->assertJson(['success' => true]);

        $this->assertTrue($child->fresh()->trashed());
        $this->assertCount(1, $this->parentDeletionHistory());
    }

    public function test_deleting_phantom_charge_whose_child_is_already_gone_still_works(): void
    {
        // The #2996 defect: child deleted, charge left behind as a phantom.
        [$child, $charge] = $this->createExtension();

        $child->delete();
        $this->assertNull($charge->fresh()->deleted_at, 'Precondition: phantom charge is still active');

        $this->deleteViaBillingRow($charge, $this->disposition())->assertOk()->assertJson(['success' => true]);

        $this->assertNotNull(BillingCharge::withTrashed()->find($charge->id)->deleted_at);
        $this->assertCount(1, $this->parentDeletionHistory());
    }

    // ── Non-extension deletion is unchanged (no disposition needed) ─────

    public function test_reorder_with_reference_uses_generic_deletion(): void
    {
        $reorder = Order::create([
            'order_number' => '9000', 'reference_order_number' => '2996',
            'order_date' => now()->toDateString(), 'customer_id' => $this->customer->id,
            'customer_name' => 'Life Cycle',
        ]);

        $this->deleteViaChildOrder($reorder)->assertOk()->assertJson(['success' => true]);

        $this->assertTrue($reorder->fresh()->trashed());
        $this->assertCount(0, $this->parentDeletionHistory());
    }

    public function test_plain_order_deletion_is_unchanged(): void
    {
        $plain = Order::create([
            'order_number' => '7777', 'order_date' => now()->toDateString(),
            'customer_id' => $this->customer->id, 'customer_name' => 'Life Cycle',
        ]);

        $this->deleteViaChildOrder($plain)->assertOk()->assertJson(['success' => true]);

        $this->assertTrue($plain->fresh()->trashed());
        $this->assertCount(0, $this->parentDeletionHistory());
    }
}
