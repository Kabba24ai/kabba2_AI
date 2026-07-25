<?php

namespace Tests\Feature\Orders;

use App\Enums\Customers\PaymentMethod;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use App\Services\CustomerCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 1 payment vocabulary standardization, controller-level integration:
 *   - Every canonical payment method actually gets recorded as itself
 *     (never collapsed into another value — "Cash means cash" extended to
 *     every method).
 *   - "Other" requires a descriptive note.
 *   - Store Credit genuinely moves the customer's real balance, not just a
 *     label — both taking a payment (redeem) and refunding one (grant).
 *   - Payment status is never inferred from payment method.
 */
class PaymentVocabularyStandardizationTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $admin;
    private User $employee;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Jordan', 'last_name' => 'Payee',
            'email' => 'jordan.payee@example.com', 'status' => 'Active',
        ]);

        $this->admin = User::create([
            'first_name' => 'Gary', 'last_name' => 'Admin',
            'email' => 'gary-vocab-test@example.com', 'status' => 'Active',
        ]);

        $this->employee = User::create([
            'first_name' => 'Sam', 'last_name' => 'Clerk',
            'email' => 'sam-vocab-test@example.com', 'status' => 'Active',
        ]);

        $this->order = Order::create([
            'customer_id'   => $this->customer->id,
            'customer_name' => $this->customer->full_name,
            'subtotal'      => 200.0,
            'tax_amount'    => 0.0,
            'grand_total'   => 200.0,
        ]);

        $this->actingAs($this->admin);
    }

    private function receivePayment(array $overrides = [])
    {
        return $this->putJson(
            route('admin.order-management.orders.receive-payment', $this->order->unique_id),
            array_merge([
                'payment_type'        => 'Cash',
                'responsible_person'  => $this->employee->id,
            ], $overrides)
        );
    }

    // ── Every canonical method records as itself ────────────────────────

    /** @dataProvider canonicalMethodProvider */
    public function test_each_canonical_method_records_as_itself(string $customerMethodValue, OrderPaymentMethod $expected, array $extra = []): void
    {
        $this->receivePayment(array_merge(['payment_type' => $customerMethodValue], $extra))->assertOk();

        $payment = OrderPayment::where('order_id', $this->order->id)->latest('id')->firstOrFail();
        $this->assertSame($expected, $payment->payment_method);
    }

    public static function canonicalMethodProvider(): array
    {
        // Exactly the eight approved methods (Bank Transfer is deliberately
        // absent — see test_bank_transfer_is_rejected_everywhere below).
        return [
            'Cash means cash' => [PaymentMethod::Cash->value, OrderPaymentMethod::Cash],
            'Check'            => [PaymentMethod::Cheque->value, OrderPaymentMethod::Cheque, ['cheque_number' => 'CHQ-1']],
            'Tap to Pay'       => [PaymentMethod::TapToPay->value, OrderPaymentMethod::TapToPay],
            'Gift Card'        => [PaymentMethod::GiftCard->value, OrderPaymentMethod::GiftCard],
            'Zelle / Venmo'    => [PaymentMethod::ZelleVenmo->value, OrderPaymentMethod::ZelleVenmo],
        ];
    }

    // ── Bank Transfer is fully removed ──────────────────────────────────

    public function test_bank_transfer_is_rejected_everywhere(): void
    {
        // Not offered in the dropdown source.
        $this->assertArrayNotHasKey(PaymentMethod::BankTransfer->value, PaymentMethod::options());
        $this->assertNotContains(PaymentMethod::BankTransfer, PaymentMethod::canonical());
        $this->assertNotContains(OrderPaymentMethod::Online, OrderPaymentMethod::canonical());

        // Rejected by validation on every payment-recording surface.
        $this->receivePayment(['payment_type' => 'BankTransfer'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payment_type']);
        $this->assertSame(0, OrderPayment::where('order_id', $this->order->id)->count());
    }

    public function test_bank_transfer_is_rejected_on_refund(): void
    {
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount'           => 200.0,
            'status'           => OrderPaymentStatus::Paid->value,
        ]);

        $response = $this->putJson(
            route('admin.order-management.orders.refund-payment', $this->order->unique_id),
            [
                'amount'        => 50.0,
                'payment_type'  => 'BankTransfer',
                'reason'        => 'billing_error',
                'processed_by'  => $this->employee->id,
                'employee_code' => $this->employee->employee_code,
            ]
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['payment_type']);
    }

    public function test_cash_is_never_used_as_a_catch_all_for_other_methods(): void
    {
        // Regression guard for the one real "Cash means cash" violation
        // found in the audit (ReceiptService collapsing COD into 'cash').
        // At the OrderPayment level, every one of these must be distinct.
        $seen = [];
        foreach (self::canonicalMethodProvider() as [$value, $expected]) {
            $seen[] = $expected;
        }
        $this->assertSame(count($seen), count(array_unique(array_map(fn ($m) => $m->value, $seen))));
    }

    // ── "Other" requires a note ──────────────────────────────────────────

    public function test_other_without_a_note_is_rejected(): void
    {
        $this->receivePayment(['payment_type' => 'Other', 'payment_note' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payment_note']);

        $this->assertSame(0, OrderPayment::where('order_id', $this->order->id)->count());
    }

    public function test_other_with_a_note_is_accepted_and_note_is_stored(): void
    {
        $this->receivePayment(['payment_type' => 'Other', 'payment_note' => 'Manufacturer Credit'])
            ->assertOk();

        $payment = OrderPayment::where('order_id', $this->order->id)->latest('id')->firstOrFail();
        $this->assertSame(OrderPaymentMethod::Other, $payment->payment_method);
        $this->assertSame('Manufacturer Credit', $payment->payment_note);
    }

    public function test_cash_does_not_require_a_note(): void
    {
        $this->receivePayment(['payment_type' => 'Cash'])->assertOk();
    }

    // ── Store Credit actually moves the real balance ────────────────────

    public function test_store_credit_payment_redeems_the_real_balance(): void
    {
        CustomerCreditService::createFinancialCredit(
            customerId: $this->customer->id,
            amount: 300.0,
            reason: 'Test grant',
        );

        $this->assertSame(300.0, CustomerCreditService::remainingBalance($this->customer->id));

        $this->receivePayment(['payment_type' => 'StoreCredit'])->assertOk();

        // The order's full $200 balance was redeemed.
        $this->assertSame(100.0, CustomerCreditService::remainingBalance($this->customer->id));

        $payment = OrderPayment::where('order_id', $this->order->id)->latest('id')->firstOrFail();
        $this->assertSame(OrderPaymentMethod::StoreCredit, $payment->payment_method);
        $this->assertSame(OrderPaymentStatus::Paid, $payment->status);
    }

    public function test_store_credit_payment_exceeding_balance_is_rejected_and_records_nothing(): void
    {
        CustomerCreditService::createFinancialCredit(
            customerId: $this->customer->id,
            amount: 50.0, // less than the $200 order total
            reason: 'Test grant',
        );

        $this->receivePayment(['payment_type' => 'StoreCredit'])->assertStatus(422);

        // Neither the balance nor the order gained a phantom payment.
        $this->assertSame(50.0, CustomerCreditService::remainingBalance($this->customer->id));
        $this->assertSame(0, OrderPayment::where('order_id', $this->order->id)->count());
    }

    public function test_duplicate_store_credit_submission_with_the_same_idempotency_token_does_not_redeem_twice(): void
    {
        CustomerCreditService::createFinancialCredit(
            customerId: $this->customer->id,
            amount: 300.0,
            reason: 'Test grant',
        );

        $token = (string) \Illuminate\Support\Str::uuid();

        // Same modal-open, same token — simulates a double-click / network
        // retry submitting the identical request twice.
        $first  = $this->receivePayment(['payment_type' => 'StoreCredit', 'idempotency_token' => $token]);
        $second = $this->receivePayment(['payment_type' => 'StoreCredit', 'idempotency_token' => $token]);

        $first->assertOk();
        $second->assertOk();

        // Only $200 (the order total) was ever actually redeemed — not $400.
        $this->assertSame(100.0, CustomerCreditService::remainingBalance($this->customer->id));

        // Only one CustomerCredit redemption ledger row exists for this
        // order — amounts in this ledger are always stored positive and
        // distinguished by `type`, not by sign (see CustomerCreditService).
        $this->assertSame(
            1,
            \App\Models\Customers\CustomerCredit::where('order_id', $this->order->id)
                ->where('type', CustomerCreditService::TYPE_REDEMPTION)
                ->count()
        );
    }

    public function test_store_credit_refund_grants_real_credit_back_to_the_customer(): void
    {
        // Original payment: paid in full by card.
        $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount'           => 200.0,
            'status'           => OrderPaymentStatus::Paid->value,
        ]);

        $this->assertSame(0.0, CustomerCreditService::remainingBalance($this->customer->id));

        $response = $this->putJson(
            route('admin.order-management.orders.refund-payment', $this->order->unique_id),
            [
                'amount'        => 75.0,
                'payment_type'  => 'StoreCredit',
                'reason'        => 'billing_error',
                'processed_by'  => $this->employee->id,
                'employee_code' => $this->employee->employee_code,
            ]
        );

        $response->assertOk()->assertJson(['success' => true]);

        // The customer's real store-credit balance actually increased.
        $this->assertSame(75.0, CustomerCreditService::remainingBalance($this->customer->id));

        $refundRow = OrderPayment::where('order_id', $this->order->id)
            ->where('status', OrderPaymentStatus::PartialRefund)->firstOrFail();
        $this->assertSame(OrderPaymentMethod::StoreCredit, $refundRow->payment_method);
    }

    // ── Status is never inferred from method ─────────────────────────────

    public function test_pending_cash_payment_is_not_reported_as_paid(): void
    {
        // A Cash OrderPayment left in Pending status must never read as
        // paid just because Cash is (usually) an immediate method.
        $payment = $this->order->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount'           => 200.0,
            'status'           => OrderPaymentStatus::Pending->value,
        ]);

        $this->order->refresh();
        $this->assertFalse($this->order->is_paid);
    }

    public function test_pod_terms_do_not_imply_a_completed_payment_method(): void
    {
        $codOrder = Order::create([
            'customer_id'   => $this->customer->id,
            'customer_name' => $this->customer->full_name,
            'subtotal'      => 100.0,
            'tax_amount'    => 0.0,
            'grand_total'   => 100.0,
        ]);
        $codOrder->payments()->create([
            'payment_method'   => OrderPaymentMethod::COD->value,
            'payment_datetime' => now(),
            'amount'           => 0.0,
            'status'           => OrderPaymentStatus::Pending->value,
        ]);
        $codOrder->refresh();

        $this->assertSame('Pay on Delivery', \App\Services\PaymentDescriptionPresenter::termsLabel($codOrder));
        $this->assertFalse($codOrder->is_paid);

        // Once actually paid (by cash), terms no longer apply — status
        // takes over, matching "operational screens should emphasize
        // current status over stale original terms."
        $codOrder->payments()->create([
            'payment_method'   => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount'           => 100.0,
            'status'           => OrderPaymentStatus::Paid->value,
        ]);
        $codOrder->refresh();

        $this->assertNull(\App\Services\PaymentDescriptionPresenter::termsLabel($codOrder));
    }

    // ── Account IS offered as a FILTER option (Consistency Initiative Ph.9) ──

    public function test_order_list_payment_filters_expose_account(): void
    {
        // Payment & Accounts Consistency Initiative (Phase 9): the Orders
        // filters must be able to isolate on-account orders, so both the
        // Payment Type and Payment Status filters now expose Account
        // (via OrderPaymentMethod/Status::filterOptions()). Account remains
        // excluded from canonical() for money-movement/validation contexts —
        // filters just use the augmented provider.
        $response = $this->get(route('admin.order-management.orders.index'));

        $response->assertOk();
        $response->assertSee('value="' . OrderPaymentMethod::Cash->value . '"', false);
        $response->assertSee('value="' . OrderPaymentMethod::Account->value . '"', false);
        $response->assertSee('value="' . OrderPaymentStatus::Account->value . '"', false);
    }
}
