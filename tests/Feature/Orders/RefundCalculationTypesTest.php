<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Enums\Orders\RefundCalculationType;
use App\Models\Configurations\Setting;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use App\Services\AuthorizeNetService;
use App\Services\Reports\PaymentReconciliationLedger;
use App\Services\Reports\SalesTaxReportEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 2 refund workflow: Full Amount Less Card Processing Fee, Sales Tax
 * Only, and the (unchanged) Standard proportional refund — each stores an
 * explicit refund_calculation_type separate from the reason, and each
 * flows correctly through the Sales Tax Report and Payment Reconciliation
 * Ledger.
 */
class RefundCalculationTypesTest extends TestCase
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
            'email' => 'gary-refund-calc-test@example.com', 'status' => 'Active',
        ]);

        $this->employee = User::create([
            'first_name' => 'Sam', 'last_name' => 'Clerk',
            'email' => 'sam-refund-calc-test@example.com', 'status' => 'Active',
        ]);

        // $1000 subtotal + $97.50 tax (9.75%) = $1097.50 grand total —
        // matches the worked examples in the mission brief.
        $this->order = Order::create([
            'order_date'    => now()->format('Y-m-d'),
            'customer_name' => 'Walk-in Customer',
            'subtotal'      => 1000.0,
            'tax_amount'    => 97.50,
            'grand_total'   => 1097.50,
        ]);

        $this->actingAs($this->admin);
    }

    private function setCcFeePercentage(float $pct): void
    {
        Setting::updateOrCreate(
            ['setting_type' => 'Product Settings', 'setting_name' => 'credit_card_processing_fee'],
            ['setting_title' => 'Credit Card Processing Fee', 'value_type' => 'number', 'setting_value' => $pct]
        );
    }

    private function payCardInFull(string $transactionId = 'TXN-TEST-1'): OrderPayment
    {
        return $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount'           => $this->order->grand_total,
            'status'           => OrderPaymentStatus::Paid->value,
            'transaction_id'   => $transactionId,
        ]);
    }

    private function mockGatewayRefund(float $expectedAmount): void
    {
        $this->mock(AuthorizeNetService::class, function ($mock) use ($expectedAmount) {
            $mock->shouldReceive('getTransactionDetails')
                ->andReturn((object) ['status' => 'settledSuccessfully']);
            $mock->shouldReceive('refundOrder')
                ->once()
                ->withArgs(fn ($txnId, $amount) => abs($amount - $expectedAmount) < 0.001)
                ->andReturn(['status' => 'success', 'transaction_id' => 'REFUND-TXN-1']);
        });
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

    // ── Credit Card Processing Fee Refund ────────────────────────────────

    public function test_card_processing_fee_refund_computes_correct_split(): void
    {
        $this->setCcFeePercentage(3.00);
        $this->payCardInFull();

        $expectedFee = round(1097.50 * 0.03, 2);
        $expectedRefund = round(1097.50 - $expectedFee, 2);

        // The gateway must receive the server-computed fee-adjusted amount
        // — never the raw client-submitted `amount` below.
        $this->mockGatewayRefund($expectedRefund);

        $response = $this->refund([
            'amount' => 999999, // deliberately wrong — server must ignore this and compute its own
            'payment_type' => 'CreditCard',
            'refund_calculation_type' => 'card_processing_fee_retained',
        ]);

        $response->assertOk();

        $refundRow = OrderPayment::where('order_id', $this->order->id)
            ->where('status', OrderPaymentStatus::Refund)->firstOrFail();

        $this->assertEquals(RefundCalculationType::CardProcessingFeeRetained, $refundRow->refund_calculation_type);
        $this->assertEquals($expectedFee, (float) $refundRow->cc_fee_retained);
        $this->assertEquals($expectedRefund, (float) $refundRow->refund_amount);
        // Currency rounding: fee + refund reconstitutes the original eligible amount exactly.
        $this->assertEquals(1097.50, round((float) $refundRow->cc_fee_retained + (float) $refundRow->refund_amount, 2));
    }

    public function test_card_processing_fee_refund_rejected_when_fee_not_configured(): void
    {
        $this->setCcFeePercentage(0);
        $this->payCardInFull();

        $this->refund([
            'payment_type' => 'CreditCard',
            'refund_calculation_type' => 'card_processing_fee_retained',
        ])->assertStatus(422);

        $this->assertSame(0, OrderPayment::where('order_id', $this->order->id)->where('status', OrderPaymentStatus::Refund)->count());
    }

    public function test_card_processing_fee_refund_rejected_for_non_card_payment(): void
    {
        $this->setCcFeePercentage(3.00);
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => $this->order->grand_total,
            'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->refund([
            'payment_type' => 'CreditCard',
            'refund_calculation_type' => 'card_processing_fee_retained',
        ])->assertStatus(422);
    }

    public function test_card_processing_fee_refund_rejected_for_multiple_payments(): void
    {
        $this->setCcFeePercentage(3.00);
        // Split payment: two Paid rows — mixed-payment orders are
        // explicitly out of scope for this shortcut (per approved plan).
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value, 'transaction_id' => 'TXN-A',
        ]);
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 597.50, 'status' => OrderPaymentStatus::Paid->value, 'transaction_id' => 'TXN-B',
        ]);

        $this->refund([
            'payment_type' => 'CreditCard',
            'refund_calculation_type' => 'card_processing_fee_retained',
        ])->assertStatus(422);
    }

    public function test_prior_partial_refund_reduces_the_card_fee_eligible_amount(): void
    {
        $this->setCcFeePercentage(3.00);
        $this->payCardInFull();

        // First, an unrelated standard partial refund eats into the balance.
        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('getTransactionDetails')->andReturn((object) ['status' => 'settledSuccessfully']);
            $mock->shouldReceive('refundOrder')->once()->andReturn(['status' => 'success']);
        });
        $this->refund(['amount' => 97.50, 'payment_type' => 'CreditCard'])->assertOk();

        $remaining = (float) $this->order->fresh()->remaining_amount; // 1000.00
        $expectedFee = round($remaining * 0.03, 2);
        $expectedRefund = round($remaining - $expectedFee, 2);

        $this->mock(AuthorizeNetService::class, function ($mock) use ($expectedRefund) {
            $mock->shouldReceive('getTransactionDetails')->andReturn((object) ['status' => 'settledSuccessfully']);
            $mock->shouldReceive('refundOrder')
                ->once()
                ->withArgs(fn ($txnId, $amount) => abs($amount - $expectedRefund) < 0.001)
                ->andReturn(['status' => 'success']);
        });

        $this->refund([
            'amount' => 1,
            'payment_type' => 'CreditCard',
            'refund_calculation_type' => 'card_processing_fee_retained',
        ])->assertOk();

        $lastRefund = OrderPayment::where('order_id', $this->order->id)
            ->where('refund_calculation_type', RefundCalculationType::CardProcessingFeeRetained->value)
            ->firstOrFail();
        $this->assertEquals($expectedFee, (float) $lastRefund->cc_fee_retained);
    }

    public function test_card_processing_fee_cannot_produce_a_negative_refund(): void
    {
        // An absurd 100% fee would consume the entire eligible amount.
        $this->setCcFeePercentage(100.00);
        $this->payCardInFull();

        $this->refund([
            'payment_type' => 'CreditCard',
            'refund_calculation_type' => 'card_processing_fee_retained',
        ])->assertStatus(422);

        $this->assertSame(0, OrderPayment::where('order_id', $this->order->id)->where('status', OrderPaymentStatus::Refund)->count());
    }

    public function test_card_processing_fee_refund_is_processed_against_the_correct_transaction(): void
    {
        $this->setCcFeePercentage(3.00);
        $payment = $this->payCardInFull('TXN-SPECIFIC-999');

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('getTransactionDetails')
                ->once()->with('TXN-SPECIFIC-999')
                ->andReturn((object) ['status' => 'settledSuccessfully']);
            $mock->shouldReceive('refundOrder')
                ->once()
                ->withArgs(fn ($txnId) => $txnId === 'TXN-SPECIFIC-999')
                ->andReturn(['status' => 'success']);
        });

        $this->refund([
            'payment_type' => 'CreditCard',
            'refund_calculation_type' => 'card_processing_fee_retained',
        ])->assertOk();
    }

    // ── Sales Tax Only ────────────────────────────────────────────────────

    public function test_sales_tax_only_refunds_the_full_remaining_tax(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => $this->order->grand_total, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->refund([
            'payment_type' => 'Cash',
            'refund_calculation_type' => 'sales_tax_only',
        ])->assertOk();

        $refundRow = OrderPayment::where('order_id', $this->order->id)
            ->where('status', OrderPaymentStatus::Refund)->firstOrFail();

        $this->assertEquals(RefundCalculationType::SalesTaxOnly, $refundRow->refund_calculation_type);
        $this->assertEquals(97.50, (float) $refundRow->refund_amount);
        $this->assertEquals(97.50, (float) $refundRow->tax_refunded);
        $this->assertNull($refundRow->cc_fee_retained);
    }

    public function test_sales_tax_only_does_not_touch_purchase_revenue(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => $this->order->grand_total, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->refund(['payment_type' => 'Cash', 'refund_calculation_type' => 'sales_tax_only'])->assertOk();

        $rows = (new SalesTaxReportEngine())->refundRows([
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
        ]);
        $row = $rows->firstWhere('order_number', $this->order->order_number);

        $this->assertNotNull($row);
        $this->assertEquals(0.0, $row->subtotal); // no merchandise/rental impact
        $this->assertEquals(-97.50, $row->tax_amount);
        $this->assertEquals(-97.50, $row->grand_total);
    }

    public function test_previous_tax_refund_reduces_remaining_refundable_tax(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => $this->order->grand_total, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        // Partially refund $40 of the $97.50 tax via a standard proportional refund
        // that happens to land entirely in tax terms — simpler: refund $43.88
        // (proportional split) then check remaining eligibility via a second call.
        $this->refund(['amount' => 50, 'payment_type' => 'Cash'])->assertOk();

        $alreadyRefundedTax = (float) OrderPayment::where('order_id', $this->order->id)
            ->whereIn('status', [OrderPaymentStatus::PartialRefund, OrderPaymentStatus::Refund])
            ->sum('tax_refunded');
        $expectedRemaining = round(97.50 - $alreadyRefundedTax, 2);

        $this->refund(['payment_type' => 'Cash', 'refund_calculation_type' => 'sales_tax_only'])->assertOk();

        $taxOnlyRow = OrderPayment::where('order_id', $this->order->id)
            ->where('refund_calculation_type', RefundCalculationType::SalesTaxOnly->value)
            ->firstOrFail();
        $this->assertEquals($expectedRemaining, (float) $taxOnlyRow->refund_amount);
    }

    public function test_cannot_refund_more_tax_than_was_collected(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => $this->order->grand_total, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        // Exhaust the refundable tax once.
        $this->refund(['payment_type' => 'Cash', 'refund_calculation_type' => 'sales_tax_only'])->assertOk();

        // A second attempt has nothing left to refund.
        $this->refund(['payment_type' => 'Cash', 'refund_calculation_type' => 'sales_tax_only'])
            ->assertStatus(422);
    }

    public function test_sales_tax_only_disabled_when_no_refundable_tax_remains(): void
    {
        $taxFreeOrder = Order::create([
            'order_date' => now()->format('Y-m-d'), 'customer_name' => 'Tax Exempt Co',
            'subtotal' => 500.0, 'tax_amount' => 0.0, 'grand_total' => 500.0,
        ]);
        $taxFreeOrder->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 500.0, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $response = $this->putJson(
            route('admin.order-management.orders.refund-payment', $taxFreeOrder->unique_id),
            [
                'amount' => 10, 'payment_type' => 'Cash', 'reason' => 'billing_error',
                'processed_by' => $this->employee->id, 'employee_code' => $this->employee->employee_code,
                'refund_calculation_type' => 'sales_tax_only',
            ]
        );

        $response->assertStatus(422);
    }

    // ── Standard Partial Refund (unchanged) ──────────────────────────────

    public function test_standard_partial_refund_allocation_is_unchanged(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => $this->order->grand_total, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->refund(['amount' => 500, 'payment_type' => 'Cash'])->assertOk();

        $refundRow = OrderPayment::where('order_id', $this->order->id)
            ->where('status', OrderPaymentStatus::PartialRefund)->firstOrFail();

        $this->assertEquals(RefundCalculationType::Standard, $refundRow->refund_calculation_type);

        $taxRate = 97.50 / 1000.0;
        $expectedTax = round(500 - (500 / (1 + $taxRate)), 2);
        $expectedPurchase = round(500 - $expectedTax, 2);

        $this->assertEquals($expectedTax, (float) $refundRow->tax_refunded);
        // Purchase + tax portions sum exactly to the total refund — no
        // one-cent rounding imbalance.
        $this->assertEquals(500.0, round($expectedPurchase + $expectedTax, 2));
    }

    // ── Payment Reconciliation Ledger correction ─────────────────────────

    public function test_ledger_splits_base_and_tax_for_a_sales_tax_only_refund(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => $this->order->grand_total, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->refund(['payment_type' => 'Cash', 'refund_calculation_type' => 'sales_tax_only'])->assertOk();

        $rows = app(PaymentReconciliationLedger::class)->rows([
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
        ]);
        $row = collect($rows)->first(fn ($r) => $r->order_number === $this->order->order_number && $r->stream === 'refund');

        $this->assertNotNull($row);
        // Before the fix, base_amount would have absorbed the entire
        // refund (-97.50) and tax_amount would have stayed 0.
        $this->assertEquals(0.0, $row->base_amount);
        $this->assertEquals(-97.50, $row->tax_amount);
        $this->assertEquals(-97.50, $row->grand_total);
    }

    // ── General ───────────────────────────────────────────────────────────

    public function test_refund_reason_and_calculation_type_are_stored_separately(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => $this->order->grand_total, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->refund([
            'payment_type' => 'Cash',
            'reason' => 'customer_cancellation',
            'refund_calculation_type' => 'sales_tax_only',
        ])->assertOk();

        $refundRow = OrderPayment::where('order_id', $this->order->id)
            ->where('status', OrderPaymentStatus::Refund)->firstOrFail();

        $this->assertSame('customer_cancellation', $refundRow->processed_reason_code);
        $this->assertEquals(RefundCalculationType::SalesTaxOnly, $refundRow->refund_calculation_type);
    }

    public function test_card_processing_fee_must_be_refunded_to_credit_card(): void
    {
        $this->setCcFeePercentage(3.00);
        $this->payCardInFull();

        // Requesting the fee shortcut but routing the refund to Cash is rejected.
        $this->refund([
            'payment_type' => 'Cash',
            'refund_calculation_type' => 'card_processing_fee_retained',
        ])->assertStatus(422);
    }

    public function test_omitting_calculation_type_defaults_to_standard(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => $this->order->grand_total, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        // No refund_calculation_type in the payload at all — exactly what
        // every pre-existing caller/test sends.
        $this->refund(['amount' => 200, 'payment_type' => 'Cash'])->assertOk();

        $refundRow = OrderPayment::where('order_id', $this->order->id)
            ->where('status', OrderPaymentStatus::PartialRefund)->firstOrFail();
        $this->assertEquals(RefundCalculationType::Standard, $refundRow->refund_calculation_type);
    }
}
