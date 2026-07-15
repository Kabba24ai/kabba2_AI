<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerCredit;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderHistory;
use App\Models\Orders\OrderPayment;
use App\Services\AuthorizeNetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 3A — Payment Correctness Foundation.
 *
 * WRITTEN BUT NOT EXECUTED in this environment: this sandbox has no MySQL
 * server (phpunit.xml's default DB connection is MySQL; RefreshDatabase
 * fails with "Connection refused" here regardless of what a Feature test
 * touches — the same documented, pre-existing sandbox limitation as every
 * prior Feature test in this codebase). Requires a real MySQL test
 * database to run. Do not treat this file as passing until it has
 * actually been executed against one.
 */
class PaymentCorrectnessFoundationTest extends TestCase
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
            'email' => 'gary-p3a-test@example.com', 'status' => 'Active',
        ]);

        $this->employee = User::create([
            'first_name' => 'Sam', 'last_name' => 'Clerk',
            'email' => 'sam-p3a-test@example.com', 'status' => 'Active',
        ]);

        $this->customer = Customer::factory()->create();

        $this->actingAs($this->admin);
    }

    private function makeOrder(float $grandTotal, ?float $subtotal = null, float $taxAmount = 0.0): Order
    {
        return Order::create([
            'order_date' => now()->format('Y-m-d'), 'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->full_name ?? 'Test Customer',
            'subtotal' => $subtotal ?? $grandTotal, 'tax_amount' => $taxAmount, 'grand_total' => $grandTotal,
        ]);
    }

    private function refund(Order $order, array $overrides = [])
    {
        return $this->putJson(
            route('admin.order-management.orders.refund-payment', $order->unique_id),
            array_merge([
                'amount' => 100, 'payment_type' => 'Cash', 'reason' => 'billing_error',
                'processed_by' => $this->employee->id, 'employee_code' => $this->employee->employee_code,
                'idempotency_token' => (string) Str::uuid(),
            ], $overrides)
        );
    }

    // ── 1-3: parent_order_payment_id correctness (the confirmed bug) ───

    public function test_first_refund_links_to_the_original_payment(): void
    {
        $order = $this->makeOrder(1000.0);
        $original = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 1000.0, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->refund($order, ['amount' => 100])->assertOk();

        $refundRow = $order->payments()->refund()->first();
        $this->assertSame($original->id, $refundRow->parent_order_payment_id);
    }

    public function test_second_refund_also_links_to_the_original_payment_not_the_first_refund(): void
    {
        // Direct regression test for the confirmed bug: previously
        // parent_order_payment_id was set from Order::lastPayment (highest
        // id, ANY status) — on the second refund, lastPayment resolves to
        // the FIRST refund row instead of the original payment.
        $order = $this->makeOrder(1000.0);
        $original = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 1000.0, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->refund($order, ['amount' => 100])->assertOk();
        $this->refund($order, ['amount' => 100])->assertOk();

        $refunds = $order->payments()->refund()->orderBy('id')->get();
        $this->assertCount(2, $refunds);
        foreach ($refunds as $refundRow) {
            $this->assertSame($original->id, $refundRow->parent_order_payment_id);
        }
    }

    public function test_a_refund_row_never_becomes_the_parent_of_another_refund(): void
    {
        $order = $this->makeOrder(1000.0);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 1000.0, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->refund($order, ['amount' => 100])->assertOk();
        $this->refund($order, ['amount' => 100])->assertOk();

        $refundIds = $order->payments()->refund()->pluck('id');
        $parentIds = $order->payments()->refund()->pluck('parent_order_payment_id');

        $this->assertEmpty($parentIds->intersect($refundIds), 'No refund row should reference another refund row as its parent.');
    }

    // ── 4-5: refund caps ─────────────────────────────────────────────

    public function test_partially_paid_order_cannot_refund_more_than_collected(): void
    {
        $order = $this->makeOrder(1000.0);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 400.0, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);

        // The confirmed bug: grand_total - total_refunded (= 1000) would
        // have permitted this despite only $400 ever collected.
        $response = $this->refund($order, ['amount' => 500]);

        $response->assertStatus(400);
        $response->assertJsonPath('remaining', 400.0);
    }

    public function test_per_payment_refund_cap_is_enforced(): void
    {
        $order = $this->makeOrder(500.0);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 500.0, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->refund($order, ['amount' => 500])->assertOk();

        // Fully refunded — a second refund attempt must be rejected by the
        // per-payment cap even though nothing else has changed.
        $this->refund($order, ['amount' => 1])->assertStatus(400);
    }

    public function test_multi_payment_order_blocks_refund_with_a_clear_message_not_a_silent_guess(): void
    {
        // Phase 3A intentionally does not guess which payment a refund on a
        // multi-payment order belongs to — that is Phase 3B/3C scope.
        $order = $this->makeOrder(1000.0);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 600.0, 'status' => OrderPaymentStatus::Paid->value, 'transaction_id' => 'TXN-1',
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 400.0, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $response = $this->refund($order, ['amount' => 100]);

        $response->assertStatus(422);
        $this->assertStringContainsString('more than one payment', $response->json('message'));
    }

    // ── 6: failed refund does not consume refundable balance ───────────

    public function test_failed_gateway_refund_does_not_consume_refundable_balance(): void
    {
        $order = $this->makeOrder(500.0);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 500.0, 'status' => OrderPaymentStatus::Paid->value, 'transaction_id' => 'TXN-FAIL-1',
        ]);

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('getTransactionDetails')->andReturn((object) ['status' => 'settledSuccessfully']);
            $mock->shouldReceive('refundOrder')->once()->andReturn(['status' => 'failed', 'message' => 'Declined']);
        });

        $this->refund($order, ['amount' => 500, 'payment_type' => 'CreditCard'])->assertStatus(500);

        $order->refresh();
        $this->assertSame(500.0, $order->remaining_amount);
    }

    // ── 7-9: settled-payments totals ────────────────────────────────

    public function test_two_partial_payments_together_produce_paid_in_full(): void
    {
        $order = $this->makeOrder(1000.0);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 500.0, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 500.0, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);

        $this->assertTrue($order->fresh()->is_paid);
    }

    public function test_legacy_invoice_settled_status_counts_as_settled(): void
    {
        $order = $this->makeOrder(500.0);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 500.0, 'status' => OrderPaymentStatus::InvoiceCash->value,
        ]);

        $order = $order->fresh();
        $this->assertTrue($order->is_paid);
        $this->assertSame(500.0, $order->total_paid);
    }

    public function test_pending_failed_refunded_and_voided_rows_do_not_inflate_settled_payments(): void
    {
        $order = $this->makeOrder(1000.0);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 500.0, 'status' => OrderPaymentStatus::Paid->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 300.0, 'status' => OrderPaymentStatus::Pending->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 200.0, 'status' => OrderPaymentStatus::Failed->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 150.0, 'status' => OrderPaymentStatus::Voided->value,
        ]);

        $this->assertSame(500.0, $order->fresh()->total_paid);
    }

    // ── 10: Net Paid ─────────────────────────────────────────────────

    public function test_net_paid_correct_after_partial_and_full_refunds(): void
    {
        $order = $this->makeOrder(1000.0);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 1000.0, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->assertSame(1000.0, $order->fresh()->net_paid);

        $this->refund($order, ['amount' => 200])->assertOk();
        $this->assertSame(800.0, $order->fresh()->net_paid);

        $this->refund($order, ['amount' => 800])->assertOk();
        $this->assertSame(0.0, $order->fresh()->net_paid);
    }

    // ── 11-12: payment-entry idempotency ────────────────────────────

    public function test_duplicate_payment_submission_creates_one_row(): void
    {
        $order = $this->makeOrder(500.0);
        $token = (string) Str::uuid();
        $payload = [
            'payment_type' => 'Cash', 'responsible_person' => $this->employee->id,
            'idempotency_token' => $token,
        ];

        $this->putJson(route('admin.order-management.orders.receive-payment', $order->unique_id), $payload)->assertOk();
        $this->putJson(route('admin.order-management.orders.receive-payment', $order->unique_id), $payload)->assertOk();

        $this->assertSame(1, $order->payments()->where('idempotency_token', $token)->count());
    }

    public function test_duplicate_card_payment_submission_calls_the_gateway_once(): void
    {
        $order = $this->makeOrder(500.0);
        $token = (string) Str::uuid();

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('validateOpaqueData')->andReturn(true);
            $mock->shouldReceive('createOpaqueDataTransaction')->once()->andReturn([
                'status' => 'success', 'transaction_id' => 'TXN-DUP-1', 'payment_status' => 'Paid',
            ]);
        });

        $payload = [
            'payment_type' => 'CreditCard', 'card_option' => 'NewCard',
            'firstName' => 'Jane', 'lastName' => 'Doe',
            'opaqueDataValue' => 'x', 'opaqueDataDescriptor' => 'y',
            'responsible_person' => $this->employee->id, 'idempotency_token' => $token,
        ];

        $this->putJson(route('admin.order-management.orders.receive-payment', $order->unique_id), $payload)->assertOk();
        $this->putJson(route('admin.order-management.orders.receive-payment', $order->unique_id), $payload)->assertOk();

        $this->assertSame(1, $order->payments()->where('idempotency_token', $token)->count());
    }

    // ── 13: gateway decline records no payment ──────────────────────

    public function test_gateway_decline_records_no_successful_payment(): void
    {
        $order = $this->makeOrder(500.0);

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('validateOpaqueData')->andReturn(true);
            $mock->shouldReceive('createOpaqueDataTransaction')->once()->andReturn([
                'status' => 'error', 'message' => 'Card declined',
            ]);
        });

        $payload = [
            'payment_type' => 'CreditCard', 'card_option' => 'NewCard',
            'firstName' => 'Jane', 'lastName' => 'Doe',
            'opaqueDataValue' => 'x', 'opaqueDataDescriptor' => 'y',
            'responsible_person' => $this->employee->id,
        ];

        $this->putJson(route('admin.order-management.orders.receive-payment', $order->unique_id), $payload);

        $this->assertSame(0, $order->payments()->count());
    }

    // ── 14: Dashboard Store Credit gap ───────────────────────────────

    public function test_dashboard_crm_store_credit_payment_deducts_actual_customer_credit_once(): void
    {
        CustomerCredit::create([
            'customer_id' => $this->customer->id, 'type' => 'grant', 'amount' => 200.0,
            'reason' => 'Test grant', 'unique_id' => (string) Str::uuid(),
        ]);

        $customerAccount = \App\Models\Customers\CustomerAccount::create([
            'unique_id' => (string) Str::uuid(), 'customer_id' => $this->customer->id,
            'amount' => 150.0, 'balance' => 0, 'type' => 'charge', 'date' => now(),
            'fuel_alert_status' => 'pending',
        ]);

        $this->post(route('admin.dashboard.paymentstore'), [
            'source' => 'crm', 'customer_id' => $this->customer->id,
            'customer_account_id' => $customerAccount->unique_id,
            'amount' => 150.0, 'payment_type' => 'StoreCredit',
            'responsible_person' => $this->employee->id,
        ]);

        $this->assertSame(50.0, \App\Services\CustomerCreditService::remainingBalance($this->customer->id));
    }

    // ── 15: Account conversion nets against remaining balance ────────

    public function test_account_conversion_transfers_only_the_remaining_unpaid_balance(): void
    {
        $order = $this->makeOrder(1000.0);
        $product = $order->products()->create([
            'product_name' => 'Test Equipment', 'sub_total' => 1000.0, 'quantity' => 1,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::COD->value, 'status' => OrderPaymentStatus::Pending->value,
        ]);
        // A partial payment already collected via another channel before conversion.
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 400.0, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);

        $this->putJson(route('admin.order-management.orders.add-to-account', $order->unique_id))->assertOk();

        $totalConverted = \App\Models\Customers\CustomerAccount::where('order_id', $order->id)->sum('amount');
        // Previously would have been the full $1,000 product subtotal,
        // double-counting the $400 already collected.
        $this->assertSame(600.0, (float) $totalConverted);
    }

    // ── 16: history linkage ──────────────────────────────────────────

    public function test_payment_confirmed_history_row_has_order_payment_id(): void
    {
        $order = $this->makeOrder(500.0);
        $payment = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::COD->value, 'status' => OrderPaymentStatus::Pending->value,
        ]);

        $this->putJson(route('admin.order-management.orders.confirm-payment', $order->unique_id))->assertOk();

        $history = OrderHistory::where('order_id', $order->id)
            ->where('action', \App\Enums\Orders\OrderHistoryAction::OrderPaid)
            ->first();

        $this->assertNotNull($history);
        $this->assertSame($payment->id, $history->order_payment_id);
    }

    public function test_payment_added_to_account_history_row_has_order_payment_id(): void
    {
        $order = $this->makeOrder(500.0);
        $product = $order->products()->create([
            'product_name' => 'Test Equipment', 'sub_total' => 500.0, 'quantity' => 1,
        ]);
        $payment = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::COD->value, 'status' => OrderPaymentStatus::Pending->value,
        ]);

        $this->putJson(route('admin.order-management.orders.add-to-account', $order->unique_id))->assertOk();

        $history = OrderHistory::where('order_id', $order->id)
            ->where('action', \App\Enums\Orders\OrderHistoryAction::AddedToAccount)
            ->first();

        $this->assertNotNull($history);
        $this->assertSame($payment->id, $history->order_payment_id);
    }

    // ── 17-18: existing single-payment workflows unaffected ──────────

    public function test_existing_single_payment_standard_refund_still_works(): void
    {
        $order = $this->makeOrder(1097.50, subtotal: 1000.0, taxAmount: 97.50);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 1097.50, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->refund($order, ['amount' => 200])->assertOk()->assertJson(['success' => true]);
    }

    public function test_existing_card_processing_fee_retained_refund_still_works(): void
    {
        Setting::updateOrCreate(
            ['setting_type' => 'Product Settings', 'setting_name' => 'credit_card_processing_fee'],
            ['setting_title' => 'Credit Card Processing Fee', 'value_type' => 'number', 'setting_value' => 3.00]
        );

        $order = $this->makeOrder(1000.0);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 1000.0, 'status' => OrderPaymentStatus::Paid->value, 'transaction_id' => 'TXN-CC-FEE-1',
        ]);

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('getTransactionDetails')->andReturn((object) ['status' => 'settledSuccessfully']);
            $mock->shouldReceive('refundOrder')->once()->andReturn(['status' => 'success']);
        });

        $this->refund($order, [
            'payment_type' => 'CreditCard', 'refund_calculation_type' => 'card_processing_fee_retained',
        ])->assertOk()->assertJson(['success' => true]);
    }

    public function test_existing_sales_tax_only_refund_still_works(): void
    {
        $order = $this->makeOrder(1097.50, subtotal: 1000.0, taxAmount: 97.50);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 1097.50, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->refund($order, [
            'payment_type' => 'Cash', 'refund_calculation_type' => 'sales_tax_only',
        ])->assertOk()->assertJson(['success' => true]);
    }
}
