<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Configurations\Setting;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use App\Services\AuthorizeNetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Refund idempotency — one UUID per modal-open (see edit.blade.php's
 * openRefundModal()), reused across retries of that same submission.
 * Applies uniformly across every payment method and every calculation
 * type: a duplicate submit must never create a second gateway refund,
 * a second refund row, a second tax reduction, or a second retained-fee
 * entry — while two genuinely different refunds (different tokens) must
 * both still be allowed to proceed.
 */
class RefundIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Gary', 'last_name' => 'Admin',
            'email' => 'gary-refund-idem-test@example.com', 'status' => 'Active',
        ]);

        $this->employee = User::create([
            'first_name' => 'Sam', 'last_name' => 'Clerk',
            'email' => 'sam-refund-idem-test@example.com', 'status' => 'Active',
        ]);

        $this->order = Order::create([
            'order_date' => now()->format('Y-m-d'), 'customer_name' => 'Walk-in Customer',
            'subtotal' => 1000.0, 'tax_amount' => 97.50, 'grand_total' => 1097.50,
        ]);

        $this->actingAs($this->admin);
    }

    private function refund(array $overrides = [])
    {
        return $this->putJson(
            route('admin.order-management.orders.refund-payment', $this->order->unique_id),
            array_merge([
                'amount'        => 100,
                'payment_type'  => 'Cash',
                'reason'        => 'billing_error',
                'processed_by'  => $this->employee->id,
                'employee_code' => $this->employee->employee_code,
            ], $overrides)
        );
    }

    private function refundCount(): int
    {
        return OrderPayment::where('order_id', $this->order->id)
            ->whereIn('status', [OrderPaymentStatus::Refund, OrderPaymentStatus::PartialRefund])
            ->count();
    }

    // ── Manual (Cash) — Standard ─────────────────────────────────────────

    public function test_duplicate_cash_refund_with_same_token_is_not_processed_twice(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => $this->order->grand_total, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $token = (string) Str::uuid();

        $first = $this->refund(['amount' => 200, 'payment_type' => 'Cash', 'idempotency_token' => $token]);
        $second = $this->refund(['amount' => 200, 'payment_type' => 'Cash', 'idempotency_token' => $token]);

        $first->assertOk()->assertJson(['success' => true]);
        $second->assertOk()->assertJson(['success' => true]);

        $this->assertSame(1, $this->refundCount());
        $this->assertSame(1, OrderPayment::where('order_id', $this->order->id)->where('idempotency_token', $token)->count());
    }

    // ── Credit / Debit Card ───────────────────────────────────────────────

    public function test_duplicate_card_refund_with_same_token_does_not_call_the_gateway_twice(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => $this->order->grand_total, 'status' => OrderPaymentStatus::Paid->value,
            'transaction_id' => 'TXN-IDEM-1',
        ]);

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('getTransactionDetails')->andReturn((object) ['status' => 'settledSuccessfully']);
            // The critical assertion: the gateway must be called AT MOST ONCE
            // for two submissions carrying the same idempotency token.
            $mock->shouldReceive('refundOrder')->once()->andReturn(['status' => 'success']);
        });

        $token = (string) Str::uuid();

        $this->refund(['amount' => 200, 'payment_type' => 'CreditCard', 'idempotency_token' => $token])->assertOk();
        $this->refund(['amount' => 200, 'payment_type' => 'CreditCard', 'idempotency_token' => $token])->assertOk();

        $this->assertSame(1, $this->refundCount());
    }

    // ── Card Processing Fee Retained ─────────────────────────────────────

    public function test_duplicate_card_processing_fee_refund_does_not_create_a_second_retained_fee_entry(): void
    {
        Setting::updateOrCreate(
            ['setting_type' => 'Product Settings', 'setting_name' => 'credit_card_processing_fee'],
            ['setting_title' => 'Credit Card Processing Fee', 'value_type' => 'number', 'setting_value' => 3.00]
        );

        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => $this->order->grand_total, 'status' => OrderPaymentStatus::Paid->value,
            'transaction_id' => 'TXN-IDEM-FEE-1',
        ]);

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('getTransactionDetails')->andReturn((object) ['status' => 'settledSuccessfully']);
            $mock->shouldReceive('refundOrder')->once()->andReturn(['status' => 'success']);
        });

        $token = (string) Str::uuid();
        $payload = [
            'payment_type' => 'CreditCard',
            'refund_calculation_type' => 'card_processing_fee_retained',
            'idempotency_token' => $token,
        ];

        $this->refund($payload)->assertOk();
        $this->refund($payload)->assertOk();

        $this->assertSame(1, $this->refundCount());
        $this->assertSame(
            1,
            OrderPayment::where('order_id', $this->order->id)
                ->whereNotNull('cc_fee_retained')
                ->count()
        );
    }

    // ── Sales Tax Only ────────────────────────────────────────────────────

    public function test_duplicate_sales_tax_only_refund_does_not_reduce_tax_twice(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => $this->order->grand_total, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $token = (string) Str::uuid();
        $payload = ['payment_type' => 'Cash', 'refund_calculation_type' => 'sales_tax_only', 'idempotency_token' => $token];

        $this->refund($payload)->assertOk();
        $this->refund($payload)->assertOk();

        $this->assertSame(1, $this->refundCount());

        $totalTaxRefunded = (float) OrderPayment::where('order_id', $this->order->id)
            ->whereIn('status', [OrderPaymentStatus::Refund, OrderPaymentStatus::PartialRefund])
            ->sum('tax_refunded');
        // Exactly the original tax, once — not double.
        $this->assertEquals(97.50, $totalTaxRefunded);
    }

    // ── Different tokens are independent refunds ─────────────────────────

    public function test_two_different_tokens_both_process_as_separate_refunds(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => $this->order->grand_total, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->refund(['amount' => 100, 'payment_type' => 'Cash', 'idempotency_token' => (string) Str::uuid()])->assertOk();
        $this->refund(['amount' => 100, 'payment_type' => 'Cash', 'idempotency_token' => (string) Str::uuid()])->assertOk();

        $this->assertSame(2, $this->refundCount());
    }

    // ── A failed attempt can be safely retried with the same token ──────

    public function test_a_failed_gateway_attempt_can_be_retried_with_the_same_token(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => $this->order->grand_total, 'status' => OrderPaymentStatus::Paid->value,
            'transaction_id' => 'TXN-IDEM-RETRY-1',
        ]);

        $token = (string) Str::uuid();

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('getTransactionDetails')->andReturn((object) ['status' => 'settledSuccessfully']);
            $mock->shouldReceive('refundOrder')->once()->andReturn(['status' => 'failed', 'message' => 'Declined']);
        });

        $failed = $this->refund(['amount' => 200, 'payment_type' => 'CreditCard', 'idempotency_token' => $token]);
        $failed->assertStatus(500);
        $this->assertSame(0, $this->refundCount());

        // Retry with the SAME token — the failed attempt left no row behind,
        // so this must be allowed to actually process.
        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('getTransactionDetails')->andReturn((object) ['status' => 'settledSuccessfully']);
            $mock->shouldReceive('refundOrder')->once()->andReturn(['status' => 'success']);
        });

        $retry = $this->refund(['amount' => 200, 'payment_type' => 'CreditCard', 'idempotency_token' => $token]);
        $retry->assertOk();
        $this->assertSame(1, $this->refundCount());
    }

    // ── No idempotency token supplied — refund still works (opt-in protection) ──

    public function test_refund_without_an_idempotency_token_still_works(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => $this->order->grand_total, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->refund(['amount' => 100, 'payment_type' => 'Cash'])->assertOk();
        $this->assertSame(1, $this->refundCount());
    }
}
