<?php

namespace Tests\Feature\Orders;

use App\Enums\Billing\BillingChargeStatus;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use App\Services\AuthorizeNetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Integrated extension → payment flow: the Add Extension Charge modal chains
 * into the existing Make a Payment modal. The payment actually taken must
 * determine the child order's recorded method — never the COD placeholder
 * ("Paid in Full – Pay on Delivery" is a contradiction, bug #2996-A).
 */
class ExtensionPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $employee;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Chain', 'last_name' => 'Flow',
            'email' => 'chain-flow@example.com', 'status' => 'Active',
        ]);

        $this->employee = User::create([
            'first_name' => 'Pay', 'last_name' => 'Clerk',
            'email' => 'pay-clerk@example.com', 'password' => bcrypt('secret'),
        ]);

        $this->order = Order::create([
            'order_number'  => '2996',
            'order_date'    => now()->toDateString(),
            'customer_id'   => $this->customer->id,
            'customer_name' => 'Chain Flow',
        ]);

        $this->actingAs($this->employee);
    }

    private function createExtension(array $overrides = [])
    {
        return $this->withoutMiddleware()->postJson(
            route('admin.order-management.orders.extension.store', ['unique_id' => $this->order->unique_id]),
            array_merge([
                'description'        => 'One extra week',
                'base_amount'        => '150.00',
                'add_tax'            => false,
                'responsible_person' => $this->employee->id,
            ], $overrides)
        );
    }

    private function latestCharge(): BillingCharge
    {
        return BillingCharge::latest('id')->firstOrFail();
    }

    private function childOrder(): Order
    {
        return Order::where('order_number', 'like', '2996-%')->latest('id')->firstOrFail();
    }

    private function payCharge(BillingCharge $charge, array $overrides = [])
    {
        return $this->withoutMiddleware()->post(route('admin.dashboard.paymentstore'), array_merge([
            'source'                   => 'crm',
            'type'                     => 'extension',
            'billing_charge_unique_id' => $charge->unique_id,
            'customer_id'              => $this->customer->id,
            'amount'                   => '150.00',
            'payment_type'             => 'Cash',
            'responsible_person'       => $this->employee->id,
        ], $overrides));
    }

    private function mockGateway(): Mockery\MockInterface
    {
        $mock = Mockery::mock(AuthorizeNetService::class);
        $this->app->instance(AuthorizeNetService::class, $mock);

        return $mock;
    }

    private function attachSavedCard(): string
    {
        $this->customer->forceFill(['authorize_profile_id' => 'CP-777'])->saveQuietly();

        return $this->customer->cards()->create([
            'unique_id'          => 'card-uid-1',
            'payment_profile_id' => 'PP-888',
            'first_name'         => 'Chain',
            'last_name'          => 'Flow',
            'card_number'        => 'XXXX1111',
            'card_type'          => 'Visa',
        ])->unique_id;
    }

    // ── Step 1: creation returns the payment-chaining payload ─────────

    public function test_extension_creation_returns_billing_charge_payload_for_chaining(): void
    {
        $response = $this->createExtension()->assertOk()->assertJson(['success' => true]);

        $charge = $this->latestCharge();
        $response->assertJsonPath('billing_charge.unique_id', $charge->unique_id)
            ->assertJsonPath('billing_charge.total', 150)
            ->assertJsonPath('billing_charge.customer_id', $this->customer->id)
            ->assertJsonPath('billing_charge.order_number', '2996-A');

        // Deferred placeholder marks the arrangement, and it is NOT paid
        $placeholder = $this->childOrder()->payments()->firstOrFail();
        $this->assertSame(OrderPaymentMethod::COD, $placeholder->payment_method);
        $this->assertSame(OrderPaymentStatus::Pending, $placeholder->status);
        $this->assertFalse($this->childOrder()->is_paid);
    }

    public function test_duplicate_request_uuid_cannot_create_a_second_child_order(): void
    {
        $this->createExtension(['request_uuid' => 'same-key'])->assertOk();
        $this->createExtension(['request_uuid' => 'same-key'])
            ->assertStatus(409)
            ->assertJson(['success' => false, 'duplicate' => true]);

        $this->assertSame(1, Order::where('order_number', 'like', '2996-%')->count());
        $this->assertSame(1, BillingCharge::count());
    }

    public function test_fresh_request_keys_never_block_a_legitimate_second_extension(): void
    {
        // The modal mints a new key per open — two deliberate extensions
        // (different keys) must both succeed
        $this->createExtension(['request_uuid' => 'open-1'])->assertOk();
        $this->createExtension(['request_uuid' => 'open-2', 'base_amount' => '150.00'])->assertOk();

        $this->assertSame(2, Order::where('order_number', 'like', '2996-%')->count());
        $this->assertSame(2, BillingCharge::count());
        $this->assertNotNull(Order::where('order_number', '2996-B')->first());
    }

    // ── Pay Later path: charge stays outstanding, row action still works ──

    public function test_pay_later_leaves_charge_outstanding_and_payable_via_row_action(): void
    {
        $this->createExtension();
        $charge = $this->latestCharge();

        $this->assertTrue($charge->status->isOpen());
        $this->assertFalse($this->childOrder()->is_paid);

        // The existing two-step path still settles it later
        $this->payCharge($charge)->assertRedirect();
        $this->assertSame(BillingChargeStatus::Paid, $charge->fresh()->status);
        $this->assertTrue($this->childOrder()->is_paid);
    }

    // ── Non-card methods: recorded method must be the method actually used ──

    public function test_cash_payment_replaces_the_deferred_placeholder_method(): void
    {
        $this->createExtension();
        $this->payCharge($this->latestCharge(), ['payment_type' => 'Cash']);

        $child   = $this->childOrder();
        $payment = $child->payments()->firstOrFail();

        $this->assertSame(OrderPaymentMethod::Cash, $payment->payment_method);
        $this->assertSame(OrderPaymentStatus::Paid, $payment->status);
        $this->assertSame($this->employee->id, $payment->processed_by_id);
        $this->assertTrue($child->is_paid);
        $this->assertSame(OrderPaymentMethod::Cash, $child->last_payment_type);
        // The contradiction that produced bug #2996-A can no longer occur
        $this->assertNotSame(OrderPaymentMethod::COD, $child->last_payment_type);
        $this->assertSame(1, $child->payments()->count(), 'Placeholder updated in place — no stacked rows');
    }

    public function test_check_payment_records_method_and_check_number(): void
    {
        $this->createExtension();
        $this->payCharge($this->latestCharge(), ['payment_type' => 'Cheque', 'cheque_number' => 'CHK-4451']);

        $payment = $this->childOrder()->payments()->firstOrFail();
        $this->assertSame(OrderPaymentMethod::Cheque, $payment->payment_method);
        $this->assertSame('CHK-4451', $payment->cheque_number);
        $this->assertSame(OrderPaymentStatus::Paid, $payment->status);
    }

    public function test_bank_transfer_payment_records_online_method(): void
    {
        $this->createExtension();
        $this->payCharge($this->latestCharge(), ['payment_type' => 'BankTransfer']);

        $this->assertSame(OrderPaymentMethod::Online, $this->childOrder()->payments()->firstOrFail()->payment_method);
    }

    // ── Card on file: full gateway metadata lands on the child order ──

    public function test_card_on_file_payment_records_full_gateway_metadata(): void
    {
        $this->createExtension();
        $cardUid = $this->attachSavedCard();

        $this->mockGateway()->shouldReceive('chargeCustomerProfile')
            ->once()
            ->with('CP-777', 'PP-888', '150.00', Mockery::any())
            ->andReturn([
                'status'              => 'success',
                'transaction_id'      => 'TXN-123456',
                'auth_code'           => 'AUTH99',
                'customer_profile_id' => 'CP-777',
                'payment_profile_id'  => 'PP-888',
                'card_number'         => 'XXXX1111',
                'card_type'           => 'Visa',
            ]);

        $this->payCharge($this->latestCharge(), [
            'payment_type'     => 'CreditCard',
            'card_option'      => 'CardOnFile',
            'existing_card_id' => $cardUid,
        ])->assertRedirect();

        $child   = $this->childOrder();
        $payment = $child->payments()->firstOrFail();

        $this->assertSame(OrderPaymentMethod::Card, $payment->payment_method);
        $this->assertSame(OrderPaymentStatus::Paid, $payment->status);
        $this->assertSame('TXN-123456', $payment->transaction_id);
        $this->assertSame('AUTH99', $payment->auth_code);
        $this->assertSame('CP-777', $payment->customer_profile_id);
        $this->assertSame('PP-888', $payment->payment_profile_id);
        $this->assertSame('XXXX1111', $payment->card_number);
        $this->assertNotNull($payment->payment_datetime);
        $this->assertSame(BillingChargeStatus::Paid, $this->latestCharge()->status);

        // Refund compatibility: the refund modal offers Credit/Debit Card
        // only when last_payment_type is Card, and RefundPaymentController
        // locates the gateway transaction via lastPaidPayment->transaction_id
        $this->assertSame(OrderPaymentMethod::Card, $child->last_payment_type);
        $this->assertSame('TXN-123456', $child->lastPaidPayment->transaction_id);
        $this->assertSame(OrderPaymentMethod::Card, $child->lastPaidPayment->payment_method);

    }

    public function test_card_paid_extension_offers_card_refund_on_child_edit_page(): void
    {
        // The edit page reads the Authorize.net Accept.js keys — the keys
        // just need to exist (safe_decrypt degrades gracefully in tests)
        foreach (['payment_api_public_key', 'payment_api_key'] as $name) {
            Setting::create([
                'setting_type' => 'Payment Settings', 'value_type' => 'password',
                'setting_name' => $name, 'setting_title' => $name, 'setting_value' => 'test',
            ]);
        }

        // Full-middleware run (withoutMiddleware would strip the session the
        // edit page needs): create → pay by saved card → render child page
        $this->postJson(
            route('admin.order-management.orders.extension.store', ['unique_id' => $this->order->unique_id]),
            ['description' => 'Refundable week', 'base_amount' => '150.00', 'add_tax' => false,
             'responsible_person' => $this->employee->id]
        )->assertOk();

        $cardUid = $this->attachSavedCard();
        $this->mockGateway()->shouldReceive('chargeCustomerProfile')->once()->andReturn([
            'status' => 'success', 'transaction_id' => 'TXN-777', 'auth_code' => 'A1',
            'customer_profile_id' => 'CP-777', 'payment_profile_id' => 'PP-888',
            'card_number' => 'XXXX1111', 'card_type' => 'Visa',
        ]);

        $this->post(route('admin.dashboard.paymentstore'), [
            'source' => 'crm', 'type' => 'extension',
            'billing_charge_unique_id' => $this->latestCharge()->unique_id,
            'customer_id' => $this->customer->id, 'amount' => '150.00',
            'payment_type' => 'CreditCard', 'card_option' => 'CardOnFile',
            'existing_card_id' => $cardUid, 'responsible_person' => $this->employee->id,
        ])->assertRedirect();

        // The child order's edit page now renders the card refund option
        $this->get(route('admin.order-management.orders.edit', $this->childOrder()->unique_id))
            ->assertOk()
            ->assertSee('Credit / Debit Card');
    }

    // ── Decline handling ───────────────────────────────────────────────

    public function test_declined_card_keeps_extension_unpaid_and_flags_modal_reopen(): void
    {
        $this->createExtension();
        $cardUid = $this->attachSavedCard();

        $this->mockGateway()->shouldReceive('chargeCustomerProfile')
            ->once()
            ->andReturn(['status' => 'error', 'message' => 'This transaction has been declined.']);

        $response = $this->payCharge($this->latestCharge(), [
            'payment_type'     => 'CreditCard',
            'card_option'      => 'CardOnFile',
            'existing_card_id' => $cardUid,
        ]);

        $response->assertRedirect()
            ->assertSessionHas('error', 'This transaction has been declined.')
            ->assertSessionHas('be_reopen_charge', $this->latestCharge()->unique_id);

        // Nothing was marked paid, nothing silently became Pay on Delivery-paid
        $child = $this->childOrder();
        $this->assertFalse($child->is_paid);
        $this->assertTrue($this->latestCharge()->status->isOpen());
        $placeholder = $child->payments()->firstOrFail();
        $this->assertSame(OrderPaymentStatus::Pending, $placeholder->status);
        $this->assertNull($placeholder->transaction_id);

        // Exactly one of everything — a decline creates no duplicates
        $this->assertSame(1, Order::where('order_number', 'like', '2996-%')->count());
        $this->assertSame(1, BillingCharge::count());
        $this->assertSame(1, $child->payments()->count());
    }

    public function test_decline_then_cash_retry_succeeds_with_correct_method(): void
    {
        $this->createExtension();
        $cardUid = $this->attachSavedCard();

        $this->mockGateway()->shouldReceive('chargeCustomerProfile')
            ->once()->andReturn(['status' => 'error', 'message' => 'Declined.']);

        $this->payCharge($this->latestCharge(), [
            'payment_type' => 'CreditCard', 'card_option' => 'CardOnFile', 'existing_card_id' => $cardUid,
        ]);

        // Retry with cash — same modal, same endpoint
        $this->payCharge($this->latestCharge(), ['payment_type' => 'Cash'])->assertRedirect();

        $child = $this->childOrder();
        $this->assertTrue($child->is_paid);
        $this->assertSame(OrderPaymentMethod::Cash, $child->last_payment_type);
        // Retry updated the existing placeholder — no second payment row
        $this->assertSame(1, $child->payments()->count());
    }

    // ── Idempotency: a settled charge never reaches the gateway again ──

    public function test_settled_charge_short_circuits_before_the_gateway(): void
    {
        $this->createExtension();
        $charge  = $this->latestCharge();
        $cardUid = $this->attachSavedCard();

        $this->payCharge($charge, ['payment_type' => 'Cash']);
        $this->assertSame(BillingChargeStatus::Paid, $charge->fresh()->status);

        // Resubmit as a card payment — the gateway must never be called
        $this->mockGateway()->shouldNotReceive('chargeCustomerProfile');

        $this->payCharge($charge->fresh(), [
            'payment_type'     => 'CreditCard',
            'card_option'      => 'CardOnFile',
            'existing_card_id' => $cardUid,
        ])->assertRedirect();

        // Still exactly one payment row, method unchanged
        $child = $this->childOrder();
        $this->assertSame(1, $child->payments()->count());
        $this->assertSame(OrderPaymentMethod::Cash, $child->last_payment_type);
    }
}
