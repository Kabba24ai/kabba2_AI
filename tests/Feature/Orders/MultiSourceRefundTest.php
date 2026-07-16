<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentRefundAllocationStatus;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderHistory;
use App\Models\Orders\OrderPayment;
use App\Models\Orders\OrderPaymentRefundAllocation;
use App\Services\AuthorizeNetService;
use App\Services\Orders\PaymentAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 3C — Employee-Selected Refund Sources and Multi-Source Refund
 * Processing.
 *
 * WRITTEN BUT NOT EXECUTED in this environment: this sandbox has no MySQL
 * server (phpunit.xml's default DB connection is MySQL; RefreshDatabase
 * fails with "Connection refused" here regardless of what a Feature test
 * touches — the same documented, pre-existing sandbox limitation as every
 * prior Feature test in this codebase, including Phase 3A's and Phase 3B's
 * suites). Requires a real MySQL test database to run. Do not treat this
 * file as passing until it has actually been executed against one.
 *
 * Covers all 30 scenarios listed in the Phase 3C mission's Testing
 * Requirements section — numbered in matching order below. #28 (Phase 3A
 * and Phase 3B tests remain green) has no dedicated test here — it is
 * satisfied by re-running PaymentCorrectnessFoundationTest.php and
 * PaymentAllocationFoundationTest.php unmodified alongside this file.
 */
class MultiSourceRefundTest extends TestCase
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
            'email' => 'gary-p3c-test@example.com', 'status' => 'Active',
        ]);

        $this->employee = User::create([
            'first_name' => 'Sam', 'last_name' => 'Clerk',
            'email' => 'sam-p3c-test@example.com', 'status' => 'Active',
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

    private function makeSettledPayment(Order $order, float $amount, array $overrides = []): OrderPayment
    {
        return $order->payments()->create(array_merge([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => $amount,
            'status' => OrderPaymentStatus::Paid->value,
        ], $overrides));
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

    // ── 1: single-payment refund still creates one allocation ──────────

    public function test_1_single_payment_refund_still_creates_one_allocation(): void
    {
        $order = $this->makeOrder(1000.0);
        $original = $this->makeSettledPayment($order, 1000.0);

        // No `allocations` submitted at all — auto-derived single-source
        // path, the backward-compatibility case (also covers #26).
        $this->refund($order, ['amount' => 100])->assertOk();

        $refundRow = $order->payments()->refund()->first();
        $this->assertCount(1, $refundRow->refundAllocations);
        $this->assertSame($original->id, $refundRow->refundAllocations->first()->original_order_payment_id);
    }

    // ── 2: multi-payment refund creates several allocations ────────────

    public function test_2_multi_payment_refund_creates_several_allocations(): void
    {
        $order = $this->makeOrder(1000.0);
        $a = $this->makeSettledPayment($order, 600.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-A']);
        $b = $this->makeSettledPayment($order, 400.0);

        $response = $this->refund($order, [
            'amount' => 500, 'payment_type' => 'Cash',
            'allocations' => [
                ['original_order_payment_id' => $a->id, 'amount' => 300],
                ['original_order_payment_id' => $b->id, 'amount' => 200],
            ],
        ]);

        $response->assertOk();
        $refundRow = $order->payments()->refund()->first();
        $this->assertCount(2, $refundRow->refundAllocations);
    }

    // ── 3: allocation sum must equal refund total ───────────────────────

    public function test_3_allocation_sum_must_equal_refund_total(): void
    {
        $order = $this->makeOrder(1000.0);
        $a = $this->makeSettledPayment($order, 600.0);
        $b = $this->makeSettledPayment($order, 400.0);

        $response = $this->refund($order, [
            'amount' => 500, 'payment_type' => 'Cash',
            'allocations' => [
                ['original_order_payment_id' => $a->id, 'amount' => 300],
                ['original_order_payment_id' => $b->id, 'amount' => 100], // sums to 400, not 500
            ],
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('must equal the requested refund amount', $response->json('message'));
    }

    // ── 4: duplicate payment IDs are rejected ───────────────────────────

    public function test_4_duplicate_payment_ids_are_rejected(): void
    {
        $order = $this->makeOrder(1000.0);
        $a = $this->makeSettledPayment($order, 1000.0);

        $response = $this->refund($order, [
            'amount' => 500, 'payment_type' => 'Cash',
            'allocations' => [
                ['original_order_payment_id' => $a->id, 'amount' => 300],
                ['original_order_payment_id' => $a->id, 'amount' => 200],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('more than once', $response->json('message'));
    }

    // ── 5: payment from another order is rejected ───────────────────────

    public function test_5_payment_from_another_order_is_rejected(): void
    {
        $order = $this->makeOrder(1000.0);
        $this->makeSettledPayment($order, 1000.0);

        $otherOrder = $this->makeOrder(500.0);
        $foreignPayment = $this->makeSettledPayment($otherOrder, 500.0);

        $response = $this->refund($order, [
            'amount' => 100, 'payment_type' => 'Cash',
            'allocations' => [
                ['original_order_payment_id' => $foreignPayment->id, 'amount' => 100],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('does not belong to this order', $response->json('message'));
    }

    // ── 6: refund row cannot be selected as a source ────────────────────

    public function test_6_a_refund_row_cannot_be_selected_as_a_source(): void
    {
        $order = $this->makeOrder(1000.0);
        $this->makeSettledPayment($order, 1000.0);
        $this->refund($order, ['amount' => 100])->assertOk();

        $refundRow = $order->payments()->refund()->first();

        $response = $this->refund($order, [
            'amount' => 50, 'payment_type' => 'Cash',
            'allocations' => [
                ['original_order_payment_id' => $refundRow->id, 'amount' => 50],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('cannot itself be selected', $response->json('message'));
    }

    // ── 7: failed/voided/pending source cannot be selected ──────────────

    public function test_7_failed_voided_and_pending_sources_cannot_be_selected(): void
    {
        $order = $this->makeOrder(1000.0);
        $this->makeSettledPayment($order, 500.0); // keeps the order non-ambiguous-eligible for the check itself
        $failed = $order->payments()->create(['payment_method' => OrderPaymentMethod::Card->value, 'amount' => 200, 'status' => OrderPaymentStatus::Failed->value]);
        $voided = $order->payments()->create(['payment_method' => OrderPaymentMethod::Card->value, 'amount' => 200, 'status' => OrderPaymentStatus::Voided->value, 'transaction_id' => 'TXN-V']);
        $pending = $order->payments()->create(['payment_method' => OrderPaymentMethod::COD->value, 'status' => OrderPaymentStatus::Pending->value]);

        foreach ([$failed, $voided, $pending] as $ineligible) {
            $response = $this->refund($order, [
                'amount' => 50, 'payment_type' => 'Cash',
                'allocations' => [['original_order_payment_id' => $ineligible->id, 'amount' => 50]],
            ]);
            $response->assertStatus(422);
        }
    }

    // ── 8: per-payment remaining-refundable cap enforced ────────────────

    public function test_8_per_payment_remaining_refundable_cap_is_enforced(): void
    {
        $order = $this->makeOrder(1000.0);
        $a = $this->makeSettledPayment($order, 300.0);
        $b = $this->makeSettledPayment($order, 700.0);

        $response = $this->refund($order, [
            'amount' => 500, 'payment_type' => 'Cash',
            'allocations' => [
                ['original_order_payment_id' => $a->id, 'amount' => 400], // exceeds A's own $300
                ['original_order_payment_id' => $b->id, 'amount' => 100],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('remaining refundable balance', $response->json('message'));
    }

    // ── 9: order-level refundable cap enforced ──────────────────────────

    public function test_9_order_level_refundable_cap_is_enforced(): void
    {
        // Two payments totaling $1200 collected against a $1000 order (a
        // partial-refund-then-more-payment history, or simply an
        // over-collection — the cap tracks actual money in, not
        // grand_total). After a first refund of $700 from A, the order's
        // remaining balance is $500 — even though B still individually has
        // $600 of its own capacity left, a request for $500.01 from B
        // alone must still be rejected by the ORDER-level cap.
        $order = $this->makeOrder(1000.0);
        $a = $this->makeSettledPayment($order, 600.0);
        $b = $this->makeSettledPayment($order, 600.0);

        $this->refund($order, [
            'amount' => 700, 'payment_type' => 'Cash',
            'allocations' => [['original_order_payment_id' => $a->id, 'amount' => 700]],
        ])->assertOk();

        $this->assertSame(500.0, (float) $order->fresh()->remaining_amount);

        // Exactly at the boundary — must succeed.
        $this->refund($order, [
            'amount' => 500, 'payment_type' => 'Cash',
            'allocations' => [['original_order_payment_id' => $b->id, 'amount' => 500]],
        ])->assertOk();

        // Now fully refunded — B still has $100 of its OWN capacity left
        // (600 - 500), but the order has none. Must be rejected by the
        // order-level cap even though the per-payment cap alone would allow it.
        $response = $this->refund($order, [
            'amount' => 50, 'payment_type' => 'Cash',
            'allocations' => [['original_order_payment_id' => $b->id, 'amount' => 50]],
        ]);
        $response->assertStatus(422);
        $this->assertStringContainsString('remaining refundable balance', $response->json('message'));
    }

    // ── 10-11: suggested allocation + employee override ─────────────────

    public function test_10_suggested_allocation_uses_oldest_payment_first(): void
    {
        $order = $this->makeOrder(1000.0);
        $older = $this->makeSettledPayment($order, 400.0);
        $newer = $this->makeSettledPayment($order, 600.0);

        $suggestion = PaymentAllocationService::suggestAllocation($order, 500.0);

        $this->assertSame($older->id, $suggestion[0]['original_order_payment_id']);
        $this->assertEqualsWithDelta(400.0, $suggestion[0]['amount'], 0.001);
        $this->assertSame($newer->id, $suggestion[1]['original_order_payment_id']);
        $this->assertEqualsWithDelta(100.0, $suggestion[1]['amount'], 0.001);
    }

    public function test_11_employee_may_override_the_suggestion(): void
    {
        $order = $this->makeOrder(1000.0);
        $older = $this->makeSettledPayment($order, 400.0);
        $newer = $this->makeSettledPayment($order, 600.0);

        // The oldest-first suggestion would draw 400/100 — the employee
        // instead draws entirely from the newer payment. Must be honored,
        // not silently overridden back to the suggestion.
        $this->refund($order, [
            'amount' => 500, 'payment_type' => 'Cash',
            'allocations' => [['original_order_payment_id' => $newer->id, 'amount' => 500]],
        ])->assertOk();

        $refundRow = $order->payments()->refund()->first();
        $this->assertSame($newer->id, $refundRow->refundAllocations->first()->original_order_payment_id);
        $this->assertSame(400.0, PaymentAllocationService::remainingRefundable($older->fresh()));
    }

    // ── 12-14: base/tax split + rounding ─────────────────────────────────

    public function test_12_standard_base_tax_split_across_two_allocations(): void
    {
        $order = $this->makeOrder(1097.50, subtotal: 1000.0, taxAmount: 97.50);
        $a = $this->makeSettledPayment($order, 600.0);
        $b = $this->makeSettledPayment($order, 497.50);

        $this->refund($order, [
            'amount' => 219.50, 'payment_type' => 'Cash',
            'allocations' => [
                ['original_order_payment_id' => $a->id, 'amount' => 150],
                ['original_order_payment_id' => $b->id, 'amount' => 69.50],
            ],
        ])->assertOk();

        $refundRow = $order->payments()->refund()->first();
        $this->assertEqualsWithDelta(219.50, (float) $refundRow->refund_amount, 0.01);
        $totalTax = $refundRow->refundAllocations->sum('allocated_tax_amount');
        $this->assertEqualsWithDelta((float) $refundRow->tax_refunded, (float) $totalTax, 0.01);
    }

    public function test_13_sales_tax_only_across_two_allocations(): void
    {
        $order = $this->makeOrder(1097.50, subtotal: 1000.0, taxAmount: 97.50);
        $a = $this->makeSettledPayment($order, 600.0);
        $b = $this->makeSettledPayment($order, 497.50);

        $this->refund($order, [
            'payment_type' => 'Cash', 'refund_calculation_type' => 'sales_tax_only',
            'allocations' => [
                ['original_order_payment_id' => $a->id, 'amount' => 50],
                ['original_order_payment_id' => $b->id, 'amount' => 47.50],
            ],
        ])->assertOk();

        $refundRow = $order->payments()->refund()->first();
        foreach ($refundRow->refundAllocations as $allocation) {
            $this->assertSame(0.0, (float) $allocation->allocated_base_amount);
        }
    }

    public function test_14_one_cent_rounding_remains_balanced(): void
    {
        $order = $this->makeOrder(300.03, subtotal: 273.0, taxAmount: 27.03);
        $a = $this->makeSettledPayment($order, 100.01);
        $b = $this->makeSettledPayment($order, 100.01);
        $c = $this->makeSettledPayment($order, 100.01);

        $this->refund($order, [
            'amount' => 300.03, 'payment_type' => 'Cash',
            'allocations' => [
                ['original_order_payment_id' => $a->id, 'amount' => 100.01],
                ['original_order_payment_id' => $b->id, 'amount' => 100.01],
                ['original_order_payment_id' => $c->id, 'amount' => 100.01],
            ],
        ])->assertOk();

        $refundRow = $order->payments()->refund()->first();
        $sumBase = (float) $refundRow->refundAllocations->sum('allocated_base_amount');
        $sumTax = (float) $refundRow->refundAllocations->sum('allocated_tax_amount');
        $this->assertEqualsWithDelta((float) $refundRow->refund_amount, round($sumBase + $sumTax, 2), 0.001);
    }

    // ── 15-16: card fee applies only to card allocations + lifetime cap ──

    public function test_15_card_fee_applies_only_to_card_allocations(): void
    {
        Setting::updateOrCreate(
            ['setting_type' => 'Product Settings', 'setting_name' => 'credit_card_processing_fee'],
            ['setting_title' => 'Credit Card Processing Fee', 'value_type' => 'number', 'setting_value' => 3.00]
        );

        $order = $this->makeOrder(1000.0);
        $card = $this->makeSettledPayment($order, 600.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-CARD-1']);
        $cash = $this->makeSettledPayment($order, 400.0);

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('getTransactionDetails')->andReturn((object) ['status' => 'settledSuccessfully']);
            $mock->shouldReceive('refundOrder')->once()->andReturn(['status' => 'success', 'gateway_refund_id' => 'GW-1']);
        });

        $this->refund($order, [
            'payment_type' => 'CreditCard', 'refund_calculation_type' => 'card_processing_fee_retained',
            'allocations' => [
                ['original_order_payment_id' => $card->id, 'amount' => 300],
                ['original_order_payment_id' => $cash->id, 'amount' => 100],
            ],
        ])->assertOk();

        $refundRow = $order->payments()->refund()->first();
        $cardAllocation = $refundRow->refundAllocations->firstWhere('original_order_payment_id', $card->id);
        $cashAllocation = $refundRow->refundAllocations->firstWhere('original_order_payment_id', $cash->id);

        $this->assertGreaterThan(0, (float) $cardAllocation->processing_fee_retained);
        $this->assertSame(0.0, (float) ($cashAllocation->processing_fee_retained ?? 0.0));
    }

    public function test_16_card_fee_lifetime_cap_is_enforced_per_original_payment(): void
    {
        Setting::updateOrCreate(
            ['setting_type' => 'Product Settings', 'setting_name' => 'credit_card_processing_fee'],
            ['setting_title' => 'Credit Card Processing Fee', 'value_type' => 'number', 'setting_value' => 3.00]
        );

        $order = $this->makeOrder(1000.0);
        $card = $this->makeSettledPayment($order, 1000.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-CARD-CAP']);

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('getTransactionDetails')->andReturn((object) ['status' => 'settledSuccessfully']);
            $mock->shouldReceive('refundOrder')->twice()->andReturn(['status' => 'success', 'gateway_refund_id' => 'GW-CAP']);
        });

        // First fee-retained refund draws the full lifetime cap (3% of $1000 = $30).
        $this->refund($order, [
            'payment_type' => 'CreditCard', 'refund_calculation_type' => 'card_processing_fee_retained',
            'allocations' => [['original_order_payment_id' => $card->id, 'amount' => 1000]],
        ])->assertOk();

        $firstFee = (float) $order->payments()->refund()->first()->refundAllocations->first()->processing_fee_retained;
        $this->assertEqualsWithDelta(30.0, $firstFee, 0.01);

        $cap = PaymentAllocationService::remainingCardFeeCapacity($card->fresh(), 3.0);
        $this->assertSame(0.0, $cap);
    }

    // ── 17-18: gateway routing ─────────────────────────────────────────

    public function test_17_multi_card_refund_calls_the_correct_gateway_transaction_for_each(): void
    {
        $order = $this->makeOrder(1000.0);
        $cardA = $this->makeSettledPayment($order, 600.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-ROUTE-A']);
        $cardB = $this->makeSettledPayment($order, 400.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-ROUTE-B']);

        $calledWith = [];
        $this->mock(AuthorizeNetService::class, function ($mock) use (&$calledWith) {
            $mock->shouldReceive('getTransactionDetails')->andReturn((object) ['status' => 'settledSuccessfully']);
            $mock->shouldReceive('refundOrder')->twice()->andReturnUsing(function ($paymentId, $amount, $options) use (&$calledWith) {
                $calledWith[] = $paymentId;
                return ['status' => 'success', 'gateway_refund_id' => 'GW-' . $paymentId];
            });
        });

        $this->refund($order, [
            'amount' => 500, 'payment_type' => 'CreditCard',
            'allocations' => [
                ['original_order_payment_id' => $cardA->id, 'amount' => 300],
                ['original_order_payment_id' => $cardB->id, 'amount' => 200],
            ],
        ])->assertOk();

        $this->assertContains('TXN-ROUTE-A', $calledWith);
        $this->assertContains('TXN-ROUTE-B', $calledWith);

        $refundRow = $order->payments()->refund()->first();
        $allocA = $refundRow->refundAllocations->firstWhere('original_order_payment_id', $cardA->id);
        $allocB = $refundRow->refundAllocations->firstWhere('original_order_payment_id', $cardB->id);
        $this->assertSame('GW-TXN-ROUTE-A', $allocA->gateway_transaction_id);
        $this->assertSame('GW-TXN-ROUTE-B', $allocB->gateway_transaction_id);
    }

    public function test_18_card_plus_cash_refund_calls_gateway_only_for_the_card_source(): void
    {
        $order = $this->makeOrder(1000.0);
        $card = $this->makeSettledPayment($order, 600.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-MIX']);
        $cash = $this->makeSettledPayment($order, 400.0);

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('getTransactionDetails')->andReturn((object) ['status' => 'settledSuccessfully']);
            $mock->shouldReceive('refundOrder')->once()->andReturn(['status' => 'success', 'gateway_refund_id' => 'GW-MIX']);
        });

        $this->refund($order, [
            'amount' => 500, 'payment_type' => 'CreditCard',
            'allocations' => [
                ['original_order_payment_id' => $card->id, 'amount' => 300],
                ['original_order_payment_id' => $cash->id, 'amount' => 200],
            ],
        ])->assertOk();
    }

    // ── 19-22: partial failure, retry, idempotency ───────────────────────

    public function test_19_20_first_gateway_call_succeeds_second_fails_partial_failure_is_accurate(): void
    {
        $order = $this->makeOrder(1000.0);
        $cardA = $this->makeSettledPayment($order, 500.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-OK']);
        $cardB = $this->makeSettledPayment($order, 300.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-DECLINE']);

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('getTransactionDetails')->andReturn((object) ['status' => 'settledSuccessfully']);
            $mock->shouldReceive('refundOrder')->with('TXN-OK', 500.0, \Mockery::any())
                ->once()->andReturn(['status' => 'success', 'gateway_refund_id' => 'GW-OK']);
            $mock->shouldReceive('refundOrder')->with('TXN-DECLINE', 300.0, \Mockery::any())
                ->once()->andReturn(['status' => 'failed', 'message' => 'Gateway declined the refund.']);
        });

        $response = $this->refund($order, [
            'amount' => 800, 'payment_type' => 'CreditCard',
            'allocations' => [
                ['original_order_payment_id' => $cardA->id, 'amount' => 500],
                ['original_order_payment_id' => $cardB->id, 'amount' => 300],
            ],
        ]);

        $response->assertOk();
        $this->assertSame('partially_completed', $response->json('refund_operation_status'));

        $refundRow = $order->payments()->refund()->first();
        $this->assertSame(\App\Enums\Orders\RefundOperationStatus::PartiallyCompleted, $refundRow->refund_operation_status);

        $successAlloc = $refundRow->refundAllocations->firstWhere('original_order_payment_id', $cardA->id);
        $failedAlloc = $refundRow->refundAllocations->firstWhere('original_order_payment_id', $cardB->id);
        $this->assertSame(OrderPaymentRefundAllocationStatus::Allocated, $successAlloc->status);
        $this->assertSame(OrderPaymentRefundAllocationStatus::Failed, $failedAlloc->status);

        // The system must NOT report the full $800 as completed anywhere.
        $this->assertSame(500.0, PaymentAllocationService::totalSuccessfulRefunded($order->fresh()));
    }

    public function test_21_22_retry_processes_only_failed_allocations_no_duplicate_gateway_calls(): void
    {
        $order = $this->makeOrder(1000.0);
        $cardA = $this->makeSettledPayment($order, 500.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-RETRY-OK']);
        $cardB = $this->makeSettledPayment($order, 300.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-RETRY-FAIL-THEN-OK']);
        $token = (string) Str::uuid();

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('getTransactionDetails')->andReturn((object) ['status' => 'settledSuccessfully']);
            // Card A: succeeds exactly ONCE across both the initial attempt
            // and the retry — a second call would mean the retry re-charged
            // an already-successful allocation.
            $mock->shouldReceive('refundOrder')->with('TXN-RETRY-OK', 500.0, \Mockery::any())
                ->once()->andReturn(['status' => 'success', 'gateway_refund_id' => 'GW-A']);
            // Card B: fails on the first attempt, succeeds on the retry.
            $mock->shouldReceive('refundOrder')->with('TXN-RETRY-FAIL-THEN-OK', 300.0, \Mockery::any())
                ->once()->andReturn(['status' => 'failed', 'message' => 'Declined']);
            $mock->shouldReceive('refundOrder')->with('TXN-RETRY-FAIL-THEN-OK', 300.0, \Mockery::any())
                ->once()->andReturn(['status' => 'success', 'gateway_refund_id' => 'GW-B']);
        });

        $payload = [
            'amount' => 800, 'payment_type' => 'CreditCard', 'idempotency_token' => $token,
            'allocations' => [
                ['original_order_payment_id' => $cardA->id, 'amount' => 500],
                ['original_order_payment_id' => $cardB->id, 'amount' => 300],
            ],
        ];

        $first = $this->refund($order, $payload);
        $first->assertOk();
        $this->assertSame('partially_completed', $first->json('refund_operation_status'));

        // Retry — SAME token, SAME full allocation set resubmitted. The
        // server must skip A (already Allocated) and only re-attempt B.
        $second = $this->refund($order, $payload);
        $second->assertOk();
        $this->assertSame('completed', $second->json('refund_operation_status'));

        $this->assertSame(2, OrderPaymentRefundAllocation::count()); // no duplicate rows — B's failed row was updated in place, not re-inserted
        $refundRow = $order->payments()->refund()->first();
        $this->assertCount(2, $refundRow->refundAllocations);
        $this->assertSame(800.0, PaymentAllocationService::totalSuccessfulRefunded($order->fresh()));
    }

    public function test_22b_duplicate_submission_of_a_fully_completed_refund_makes_no_second_gateway_call(): void
    {
        $order = $this->makeOrder(500.0);
        $card = $this->makeSettledPayment($order, 500.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-DUP']);
        $token = (string) Str::uuid();

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('getTransactionDetails')->andReturn((object) ['status' => 'settledSuccessfully']);
            $mock->shouldReceive('refundOrder')->once()->andReturn(['status' => 'success', 'gateway_refund_id' => 'GW-DUP']);
        });

        $payload = ['amount' => 500, 'payment_type' => 'CreditCard', 'idempotency_token' => $token];

        $this->refund($order, $payload)->assertOk();
        $this->refund($order, $payload)->assertOk(); // duplicate — must reuse the same completed outcome, no second gateway call
    }

    // ── 23-24: parent pointer ─────────────────────────────────────────

    public function test_23_parent_pointer_set_for_a_single_allocation(): void
    {
        $order = $this->makeOrder(1000.0);
        $original = $this->makeSettledPayment($order, 1000.0);

        $this->refund($order, ['amount' => 200])->assertOk();

        $refundRow = $order->payments()->refund()->first();
        $this->assertSame($original->id, $refundRow->parent_order_payment_id);
    }

    public function test_24_parent_pointer_null_for_multiple_allocations(): void
    {
        $order = $this->makeOrder(1000.0);
        $a = $this->makeSettledPayment($order, 600.0);
        $b = $this->makeSettledPayment($order, 400.0);

        $this->refund($order, [
            'amount' => 500, 'payment_type' => 'Cash',
            'allocations' => [
                ['original_order_payment_id' => $a->id, 'amount' => 300],
                ['original_order_payment_id' => $b->id, 'amount' => 200],
            ],
        ])->assertOk();

        $refundRow = $order->payments()->refund()->first();
        $this->assertNull($refundRow->parent_order_payment_id);
    }

    // ── 25: history describes every source accurately ───────────────────

    public function test_25_history_describes_every_source_accurately(): void
    {
        $order = $this->makeOrder(1000.0);
        $a = $this->makeSettledPayment($order, 600.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-HIST', 'card_number' => '4242']);
        $b = $this->makeSettledPayment($order, 400.0);

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('getTransactionDetails')->andReturn((object) ['status' => 'settledSuccessfully']);
            $mock->shouldReceive('refundOrder')->once()->andReturn(['status' => 'success', 'gateway_refund_id' => 'GW-HIST']);
        });

        $this->refund($order, [
            'amount' => 700, 'payment_type' => 'Cash',
            'allocations' => [
                ['original_order_payment_id' => $a->id, 'amount' => 600],
                ['original_order_payment_id' => $b->id, 'amount' => 100],
            ],
        ])->assertOk();

        $history = OrderHistory::where('order_id', $order->id)
            ->where('action', \App\Enums\Orders\OrderHistoryAction::OrderPartialRefund)
            ->orWhere(function ($q) use ($order) {
                $q->where('order_id', $order->id)->where('action', \App\Enums\Orders\OrderHistoryAction::OrderRefunded);
            })
            ->latest('id')->first();

        $this->assertNotNull($history);
        $this->assertStringContainsString('Sources:', $history->description);
        $this->assertStringContainsString('4242', $history->description);
        $this->assertStringContainsString('600.00', $history->description);
        $this->assertStringContainsString('100.00', $history->description);
    }

    // ── 26: single-payment "UI" (auto-derived allocation) stays streamlined ──

    public function test_26_single_payment_order_never_requires_an_allocations_array(): void
    {
        $order = $this->makeOrder(500.0);
        $this->makeSettledPayment($order, 500.0);

        // No `allocations` key at all in the payload — exactly what the
        // pre-Phase-3C single-payment caller always sent.
        $response = $this->putJson(
            route('admin.order-management.orders.refund-payment', $order->unique_id),
            [
                'amount' => 100, 'payment_type' => 'Cash', 'reason' => 'billing_error',
                'processed_by' => $this->employee->id, 'employee_code' => $this->employee->employee_code,
                'idempotency_token' => (string) Str::uuid(),
            ]
        );

        $response->assertOk();
    }

    // ── 27: ambiguous legacy refund remains blocked safely ──────────────

    public function test_27_ambiguous_legacy_refund_blocks_new_refunds_safely(): void
    {
        $order = $this->makeOrder(1000.0);
        $this->makeSettledPayment($order, 600.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-AMBIG']);
        $this->makeSettledPayment($order, 400.0);

        // Genuinely ambiguous legacy refund row — no parent, multi-payment order.
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'refunded_at' => now(), 'status' => OrderPaymentStatus::PartialRefund->value,
            'refund_amount' => 100.0, 'tax_refunded' => 0.0,
        ]);

        $response = $this->refund($order, ['amount' => 50, 'payment_type' => 'Cash']);

        $response->assertStatus(422);
        $this->assertStringContainsString('legacy refund', $response->json('message'));
    }

    // ── 29: existing Standard, Sales Tax Only, Card-Fee-Retained refunds still work ──

    public function test_29a_existing_standard_refund_still_works(): void
    {
        $order = $this->makeOrder(1097.50, subtotal: 1000.0, taxAmount: 97.50);
        $this->makeSettledPayment($order, 1097.50);

        $this->refund($order, ['amount' => 200])->assertOk()->assertJson(['success' => true]);
    }

    public function test_29b_existing_sales_tax_only_refund_still_works(): void
    {
        $order = $this->makeOrder(1097.50, subtotal: 1000.0, taxAmount: 97.50);
        $this->makeSettledPayment($order, 1097.50);

        $this->refund($order, [
            'payment_type' => 'Cash', 'refund_calculation_type' => 'sales_tax_only',
        ])->assertOk()->assertJson(['success' => true]);
    }

    public function test_29c_existing_card_processing_fee_retained_refund_still_works(): void
    {
        Setting::updateOrCreate(
            ['setting_type' => 'Product Settings', 'setting_name' => 'credit_card_processing_fee'],
            ['setting_title' => 'Credit Card Processing Fee', 'value_type' => 'number', 'setting_value' => 3.00]
        );

        $order = $this->makeOrder(1000.0);
        $this->makeSettledPayment($order, 1000.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-CC-FEE-29C']);

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('getTransactionDetails')->andReturn((object) ['status' => 'settledSuccessfully']);
            $mock->shouldReceive('refundOrder')->once()->andReturn(['status' => 'success', 'gateway_refund_id' => 'GW-29C']);
        });

        $this->refund($order, [
            'payment_type' => 'CreditCard', 'refund_calculation_type' => 'card_processing_fee_retained',
        ])->assertOk()->assertJson(['success' => true]);
    }

    // ── 30: Void targets a specific payment ──────────────────────────

    public function test_30_void_targets_a_specific_payment_and_rejects_a_mismatched_one(): void
    {
        $order = $this->makeOrder(500.0);
        $payment = $this->makeSettledPayment($order, 500.0, [
            'payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-VOID-30',
        ]);

        $otherOrder = $this->makeOrder(200.0);
        $otherPayment = $this->makeSettledPayment($otherOrder, 200.0, [
            'payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-VOID-OTHER',
        ]);

        // A payment id belonging to a DIFFERENT order must be rejected —
        // the controller re-validates ownership, never trusts the id blindly.
        $response = $this->putJson(
            route('admin.order-management.orders.void-payment', $order->unique_id),
            [
                'order_payment_id' => $otherPayment->id,
                'reason' => 'billing_error', 'processed_by' => $this->employee->id,
                'employee_code' => $this->employee->employee_code,
            ]
        );

        $response->assertStatus(422);
    }

    // ── Refinement: reallocate a failed source to a DIFFERENT payment ──

    public function test_31_failed_allocation_can_be_reallocated_to_a_different_source_on_retry(): void
    {
        $order = $this->makeOrder(1000.0);
        $cardB = $this->makeSettledPayment($order, 300.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-REALLOC-FAIL']);
        $cardA = $this->makeSettledPayment($order, 500.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-REALLOC-OK']);
        $cash = $this->makeSettledPayment($order, 200.0);
        $token = (string) Str::uuid();

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('getTransactionDetails')->andReturn((object) ['status' => 'settledSuccessfully']);
            $mock->shouldReceive('refundOrder')->with('TXN-REALLOC-OK', 500.0, \Mockery::any())
                ->once()->andReturn(['status' => 'success', 'gateway_refund_id' => 'GW-A']);
            $mock->shouldReceive('refundOrder')->with('TXN-REALLOC-FAIL', 300.0, \Mockery::any())
                ->once()->andReturn(['status' => 'failed', 'message' => 'Declined']);
        });

        $first = $this->refund($order, [
            'amount' => 800, 'payment_type' => 'CreditCard', 'idempotency_token' => $token,
            'allocations' => [
                ['original_order_payment_id' => $cardA->id, 'amount' => 500],
                ['original_order_payment_id' => $cardB->id, 'amount' => 300],
            ],
        ]);
        $first->assertOk();
        $this->assertSame('partially_completed', $first->json('refund_operation_status'));
        $this->assertEqualsWithDelta(300.0, $first->json('outstanding_amount'), 0.01);

        // Retry — SAME token, but the employee moves the failed $300 to
        // Cash instead of retrying Card B. Card B is dropped from the
        // resubmission entirely.
        $second = $this->refund($order, [
            'amount' => 800, 'payment_type' => 'CreditCard', 'idempotency_token' => $token,
            'allocations' => [
                ['original_order_payment_id' => $cardA->id, 'amount' => 500],
                ['original_order_payment_id' => $cash->id, 'amount' => 300],
            ],
        ]);

        $second->assertOk();
        $this->assertSame('completed', $second->json('refund_operation_status'));
        $this->assertEqualsWithDelta(0.0, $second->json('outstanding_amount'), 0.01);

        $refundRow = $order->payments()->refund()->first();
        $cardBAllocation = $refundRow->refundAllocations->firstWhere('original_order_payment_id', $cardB->id);
        $cashAllocation = $refundRow->refundAllocations->firstWhere('original_order_payment_id', $cash->id);

        $this->assertSame(OrderPaymentRefundAllocationStatus::Superseded, $cardBAllocation->status);
        $this->assertSame(OrderPaymentRefundAllocationStatus::Allocated, $cashAllocation->status);

        // Card B's abandoned failure must not permanently tie up its
        // refundable balance — it was never actually charged.
        $this->assertSame(300.0, PaymentAllocationService::remainingRefundable($cardB->fresh()));

        // Order-level total reflects the full $800 actually refunded,
        // never double-counting the superseded attempt.
        $this->assertSame(800.0, PaymentAllocationService::totalSuccessfulRefunded($order->fresh()));
    }

    // ── Refinement: outstandingRefundAmount() / incompleteRefunds() ────

    public function test_32_outstanding_refund_amount_and_incomplete_refunds_reflect_the_true_gap(): void
    {
        $order = $this->makeOrder(1000.0);
        $cardA = $this->makeSettledPayment($order, 500.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-OUT-A']);
        $cardB = $this->makeSettledPayment($order, 300.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-OUT-B']);

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('getTransactionDetails')->andReturn((object) ['status' => 'settledSuccessfully']);
            $mock->shouldReceive('refundOrder')->with('TXN-OUT-A', 500.0, \Mockery::any())
                ->once()->andReturn(['status' => 'success', 'gateway_refund_id' => 'GW-OUT-A']);
            $mock->shouldReceive('refundOrder')->with('TXN-OUT-B', 300.0, \Mockery::any())
                ->once()->andReturn(['status' => 'failed', 'message' => 'Declined']);
        });

        $this->refund($order, [
            'amount' => 800, 'payment_type' => 'CreditCard',
            'allocations' => [
                ['original_order_payment_id' => $cardA->id, 'amount' => 500],
                ['original_order_payment_id' => $cardB->id, 'amount' => 300],
            ],
        ])->assertOk();

        $refundRow = $order->payments()->refund()->first();
        $this->assertEqualsWithDelta(300.0, PaymentAllocationService::outstandingRefundAmount($refundRow), 0.01);

        $incomplete = PaymentAllocationService::incompleteRefunds($order->fresh());
        $this->assertCount(1, $incomplete);
        $this->assertSame($refundRow->id, $incomplete->first()->id);
    }

    // ── Refinement: history correctly describes a reallocated refund ───

    public function test_33_history_shows_reallocated_source_separately_from_completed_sources(): void
    {
        $order = $this->makeOrder(1000.0);
        $cardB = $this->makeSettledPayment($order, 300.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-HISTREALLOC-FAIL']);
        $cardA = $this->makeSettledPayment($order, 500.0, ['payment_method' => OrderPaymentMethod::Card->value, 'transaction_id' => 'TXN-HISTREALLOC-OK']);
        $cash = $this->makeSettledPayment($order, 200.0);
        $token = (string) Str::uuid();

        $this->mock(AuthorizeNetService::class, function ($mock) {
            $mock->shouldReceive('getTransactionDetails')->andReturn((object) ['status' => 'settledSuccessfully']);
            $mock->shouldReceive('refundOrder')->with('TXN-HISTREALLOC-OK', 500.0, \Mockery::any())
                ->once()->andReturn(['status' => 'success', 'gateway_refund_id' => 'GW-HR-A']);
            $mock->shouldReceive('refundOrder')->with('TXN-HISTREALLOC-FAIL', 300.0, \Mockery::any())
                ->once()->andReturn(['status' => 'failed', 'message' => 'Declined']);
        });

        $this->refund($order, [
            'amount' => 800, 'payment_type' => 'CreditCard', 'idempotency_token' => $token,
            'allocations' => [
                ['original_order_payment_id' => $cardA->id, 'amount' => 500],
                ['original_order_payment_id' => $cardB->id, 'amount' => 300],
            ],
        ])->assertOk();

        $this->refund($order, [
            'amount' => 800, 'payment_type' => 'CreditCard', 'idempotency_token' => $token,
            'allocations' => [
                ['original_order_payment_id' => $cardA->id, 'amount' => 500],
                ['original_order_payment_id' => $cash->id, 'amount' => 300],
            ],
        ])->assertOk();

        $completedHistory = OrderHistory::where('order_id', $order->id)
            ->where('action', \App\Enums\Orders\OrderHistoryAction::OrderRefunded)
            ->orWhere(function ($q) use ($order) {
                $q->where('order_id', $order->id)->where('action', \App\Enums\Orders\OrderHistoryAction::OrderPartialRefund);
            })
            ->latest('id')->first();

        $this->assertNotNull($completedHistory);
        $this->assertStringContainsString('Reallocated', $completedHistory->description);
        // The "Sources:" total must reflect only what actually funded the
        // refund — never double-counting the abandoned Card B attempt.
        $this->assertStringNotContainsString('$1,100.00', $completedHistory->description);
    }
}
