<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use App\Services\AuthorizeNetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOrderFixtureLines;
use Tests\TestCase;

/**
 * "Processed By" operational audit layer on refund + void: multiple
 * employees share terminals, so alongside the logged-in user (unchanged)
 * the actual employee must select themselves and confirm their employee
 * code, plus pick a structured reason. Also proves the pre-existing
 * refund/void behavior still works with the new fields supplied.
 */
class RefundVoidProcessedByTest extends TestCase
{
    use RefreshDatabase;
    use CreatesOrderFixtureLines;

    private User $admin;
    private User $employee;
    private Order $order;
    private OrderPayment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Gary', 'last_name' => 'Terminal',
            'email' => 'terminal@test.local', 'status' => 'Active',
        ]);

        $this->employee = User::create([
            'first_name' => 'John', 'last_name' => 'Smith',
            'email' => 'john.smith@test.local', 'status' => 'Active',
        ]);

        $this->order = Order::create([
            'order_date'    => now()->format('Y-m-d'),
            'customer_name' => 'Walk-in Customer',
            'subtotal'      => 100,
            'tax_amount'    => 0,
            'grand_total'   => 100,
        ]);
        $this->addFixtureLine($this->order);
        $this->order = $this->order->fresh();

        // Cash-paid order: refunds skip the payment gateway entirely
        $this->payment = $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount'           => 100,
            'status'           => OrderPaymentStatus::Paid->value,
        ]);

        $this->actingAs($this->admin);
    }

    private function refund(array $overrides = [])
    {
        return $this->putJson(
            route('admin.order-management.orders.refund-payment', $this->order->unique_id),
            array_merge([
                'amount'        => 50,
                'payment_type'  => 'Cash',
                'reason'        => 'billing_error',
                'processed_by'  => $this->employee->id,
                'employee_code' => $this->employee->employee_code,
            ], $overrides)
        );
    }

    private function void(array $overrides = [])
    {
        return $this->putJson(
            route('admin.order-management.orders.void-payment', $this->order->unique_id),
            array_merge([
                'reason'        => 'billing_error',
                'processed_by'  => $this->employee->id,
                'employee_code' => $this->employee->employee_code,
                // Phase 3C/3D contract: void targets a SPECIFIC payment row
                // (the UI's per-row void button always sends this; see
                // MultiSourceRefundTest test_30). Required by
                // VoidPaymentController's validation.
                'order_payment_id' => $this->payment->id,
            ], $overrides)
        );
    }

    // ── Refund: existing behavior preserved + audit recorded ───────

    public function test_refund_still_works_and_records_both_users(): void
    {
        $this->refund()->assertOk()->assertJson(['success' => true]);

        $refundRow = OrderPayment::where('order_id', $this->order->id)
            ->where('status', OrderPaymentStatus::PartialRefund)->firstOrFail();

        // Existing behavior: amounts, note, logged-in user tracking unchanged
        $this->assertEquals(50, (float) $refundRow->refund_amount);
        $this->assertSame('Billing error', $refundRow->refund_note);
        $this->assertEquals($this->admin->id, $refundRow->created_by_id);

        // New audit layer: verified employee + structured reason
        $this->assertEquals($this->employee->id, $refundRow->processed_by_id);
        $this->assertSame('John Smith', $refundRow->processed_by_name);
        $this->assertSame('billing_error', $refundRow->processed_reason_code);
        $this->assertSame('Billing error', $refundRow->processed_reason_label);
        $this->assertNull($refundRow->processed_reason_other);
    }

    public function test_refund_requires_all_processed_by_fields(): void
    {
        $this->refund(['processed_by' => null, 'employee_code' => null, 'reason' => null])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['processed_by', 'employee_code', 'reason']);

        $this->assertSame(0, OrderPayment::where('order_id', $this->order->id)
            ->where('status', OrderPaymentStatus::PartialRefund)->count());
    }

    public function test_refund_blocked_when_employee_code_does_not_match(): void
    {
        $this->refund(['employee_code' => '000000' === $this->employee->employee_code ? '999999' : '000000'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['employee_code']);

        $this->assertSame(0, OrderPayment::where('order_id', $this->order->id)
            ->where('status', OrderPaymentStatus::PartialRefund)->count());
    }

    public function test_refund_blocked_for_inactive_employee(): void
    {
        $this->employee->update(['status' => 'Inactive']);

        $this->refund()->assertStatus(422)->assertJsonValidationErrors(['processed_by']);
    }

    public function test_other_reason_requires_and_stores_custom_text(): void
    {
        $this->refund(['reason' => 'other'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason_other']);

        $this->refund(['reason' => 'other', 'reason_other' => 'Customer changed rental dates'])
            ->assertOk()->assertJson(['success' => true]);

        $refundRow = OrderPayment::where('order_id', $this->order->id)
            ->where('status', OrderPaymentStatus::PartialRefund)->firstOrFail();

        $this->assertSame('other', $refundRow->processed_reason_code);
        $this->assertSame('Customer changed rental dates', $refundRow->processed_reason_other);
        $this->assertSame('Other — Customer changed rental dates', $refundRow->refund_note);
    }

    // ── Void: verification enforced before any gateway contact ─────

    public function test_void_requires_verification_and_blocks_code_mismatch(): void
    {
        // Missing everything — validation fails before the gateway is touched
        $this->putJson(route('admin.order-management.orders.void-payment', $this->order->unique_id), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['processed_by', 'employee_code', 'reason']);

        $this->void(['employee_code' => 'WRONG'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['employee_code']);

        // Payment untouched
        $this->assertEquals(OrderPaymentStatus::Paid, $this->payment->refresh()->status);
    }

    public function test_void_still_works_and_records_both_users(): void
    {
        // Void requires a card payment with a gateway transaction
        $this->payment->update([
            'payment_method' => OrderPaymentMethod::Card->value,
            'transaction_id' => 'TXN-TEST-123',
        ]);

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('getTransactionDetails')
                ->once()->with('TXN-TEST-123')
                ->andReturn((object) ['status' => 'capturedPendingSettlement']);
            $mock->shouldReceive('voidOrder')
                ->once()->andReturn(['status' => 'success']);
        });

        $this->void()->assertOk()->assertJson(['success' => true]);

        $this->payment->refresh();

        // Existing behavior: in-place void, no new row
        $this->assertEquals(OrderPaymentStatus::Voided, $this->payment->status);
        $this->assertNotNull($this->payment->voided_at);
        $this->assertSame(1, OrderPayment::where('order_id', $this->order->id)->count());

        // New audit layer on the voided payment
        $this->assertEquals($this->employee->id, $this->payment->processed_by_id);
        $this->assertSame('John Smith', $this->payment->processed_by_name);
        $this->assertSame('billing_error', $this->payment->processed_reason_code);

        // History keeps logged-in user AND names the verified employee
        $history = $this->order->history()->latest('id')->firstOrFail();
        $this->assertEquals($this->admin->id, $history->user_id);
        $this->assertStringContainsString('voided by Gary Terminal', $history->description);
        $this->assertStringContainsString('Processed by John Smith (Employee ID verified)', $history->description);
        $this->assertStringContainsString('Reason: Billing error', $history->description);
        $this->assertEquals($this->employee->id, json_decode($history->extras)->processed_by_id);
    }
}
