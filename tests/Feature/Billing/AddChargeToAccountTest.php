<?php

namespace Tests\Feature\Billing;

use App\Enums\Billing\BillingChargeStatus;
use App\Enums\Billing\BillingChargeType;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Helpers\CustomHelper;
use App\Http\DataObjects\BillingChargeRequest;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Services\Billing\AddChargeToAccountService;
use App\Services\BillingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Add Charge to Account — transfer an unpaid supplemental charge (fuel /
 * damage / extension) to the customer's credit account (Accounts Receivable).
 *
 * The load-bearing correctness rule is IDEMPOTENT A-R BOOKING keyed on
 * billing_charges.customer_account_id:
 *   - already-booked (customer_account_id set: manual + checklist-fuel) →
 *     the transfer must NOT re-book (no double receivable);
 *   - not-booked (customer_account_id null: mobile-checklist-damage,
 *     extension) → the transfer MUST book the receivable exactly once.
 *
 * Mission test #5 ("partially paid charge transfers only the remainder") is
 * DELIBERATELY OMITTED: a BillingCharge has no partial-collection model — it
 * is fully outstanding (amount + tax) while pending and 0 once closed
 * (asserted by test_outstanding_is_binary_full_or_zero). There is no partial
 * state to construct, so that scenario is not applicable to this system.
 *
 * Reversal (mission #20) is intentionally withheld from v1 (approved: defer +
 * document) — test_no_reversal_endpoint_exists_in_v1 pins that boundary.
 */
class AddChargeToAccountTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'first_name' => 'Gary', 'last_name' => 'Admin',
            'email' => 'a2a-admin@example.com', 'status' => 'Active',
        ]);

        $this->actingAs($this->user);
    }

    // ── Fixtures ──────────────────────────────────────────────────────────

    private function customer(bool $eligible = true, float $limit = 5000): Customer
    {
        return Customer::create([
            'first_name' => 'Acme', 'last_name' => 'Construction',
            'email' => 'a2a-' . uniqid() . '@example.com', 'status' => 'Active',
            'is_credit_account' => $eligible ? 1 : 0,
            'credit_limit'      => $eligible ? $limit : null,
        ]);
    }

    private function order(Customer $customer, ?string $number = null): Order
    {
        return Order::create([
            'order_number'  => $number ?? ('A2A' . random_int(1000, 999999)),
            'order_date'    => now()->toDateString(),
            'customer_id'   => $customer->id,
            'customer_name' => $customer->full_name,
        ]);
    }

    /**
     * Manual / checklist-fuel style: the A-R debt is booked at creation
     * (customer_accounts 'charge' row + updateCreditBalance) and the charge
     * back-references it via customer_account_id.
     */
    private function alreadyBookedFuelCharge(Customer $customer, Order $order, float $amount): BillingCharge
    {
        $ca = new CustomerAccount();
        $ca->customer_id             = $customer->id;
        $ca->order_id                = $order->id;
        $ca->amount                  = $amount;
        $ca->reason                  = 'Fuel Charge';
        $ca->responsible_person_id   = $this->user->id;
        $ca->responsible_person_name = $this->user->full_name;
        $ca->date                    = now();
        $ca->sales_tax_type          = 'free';
        $ca->sales_tax               = 0;
        $ca->type                    = 'charge';
        $ca->fuel_alert_status       = 'pending';
        $ca->save();

        CustomHelper::updateCreditBalance($ca); // books available_credit_balance += amount

        return BillingEngine::charge(new BillingChargeRequest(
            type: BillingChargeType::Fuel->value,
            orderId: $order->id,
            customerId: $customer->id,
            amount: $amount,
            taxType: 'free',
            responsiblePersonId: $this->user->id,
            idempotencyKey: 'a2a_fuel_' . $ca->id,
            customerAccountId: $ca->id,
            taxAmount: 0.0,
        ))->fresh();
    }

    /**
     * Mobile-checklist-damage style: billing_charges row only, no CA, A-R NOT
     * booked at creation (customer_account_id null, order_product_id set).
     */
    private function unbookedDamageCharge(Customer $customer, Order $order, float $amount): BillingCharge
    {
        $product = Product::create([
            'product_name' => 'A2A Rig', 'slug' => 'a2a-rig-' . uniqid(), 'product_type' => 'Rental',
        ]);
        $op = OrderProduct::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'A2A Rig',
            'price' => $amount, 'quantity' => 1, 'sub_total' => $amount, 'tax' => 0, 'total' => $amount,
            'damage_charge' => $amount, 'damage_status' => 'pending',
        ]);

        return BillingEngine::charge(new BillingChargeRequest(
            type: BillingChargeType::Damage->value,
            orderId: $order->id,
            customerId: $customer->id,
            amount: $amount,
            taxType: 'free',
            orderProductId: $op->id,
            responsiblePersonId: $this->user->id,
            idempotencyKey: 'a2a_damage_' . $op->id,
            customerAccountId: null,
            taxAmount: 0.0,
        ))->fresh();
    }

    /** Extension style: parent + child order (child holds a pending COD payment), no CA. */
    private function extensionCharge(Customer $customer, float $amount): array
    {
        $parent = $this->order($customer, 'EXT' . random_int(1000, 999999));
        $child  = $this->order($customer, $parent->order_number . '-A');
        $child->payments()->create([
            'payment_datetime' => now(),
            'payment_method'   => OrderPaymentMethod::COD->value,
            'amount'           => $amount,
            'status'           => OrderPaymentStatus::Pending->value,
        ]);

        $charge = BillingEngine::charge(new BillingChargeRequest(
            type: BillingChargeType::Extension->value,
            orderId: $parent->id,
            customerId: $customer->id,
            amount: $amount,
            taxType: 'free',
            childOrderId: $child->id,
            responsiblePersonId: $this->user->id,
            idempotencyKey: 'a2a_ext_' . $child->id,
            customerAccountId: null,
            taxAmount: 0.0,
        ))->fresh();

        return [$charge, $parent, $child];
    }

    private function endpoint(BillingCharge $charge): string
    {
        return route('admin.order-management.orders.billing-charges.add-to-account', $charge->unique_id);
    }

    // ── Core transfers (mission #1–#3) ────────────────────────────────────

    public function test_eligible_fuel_charge_already_in_ar_transfers_without_re_booking(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer);
        $charge   = $this->alreadyBookedFuelCharge($customer, $order, 87.50);

        $this->assertEquals(87.50, (float) $customer->fresh()->available_credit_balance, 'debt booked at creation');

        AddChargeToAccountService::transfer($charge->unique_id, $this->user->id);

        // Balance UNCHANGED — the receivable was already on the account.
        $this->assertEquals(87.50, (float) $customer->fresh()->available_credit_balance, 'no double-booking');
        $this->assertSame(BillingChargeStatus::Account, $charge->fresh()->status);
    }

    public function test_eligible_damage_charge_not_in_ar_books_the_receivable_once(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer);
        $charge   = $this->unbookedDamageCharge($customer, $order, 120.00);

        $this->assertEquals(0.0, (float) $customer->fresh()->available_credit_balance, 'not booked at creation');

        AddChargeToAccountService::transfer($charge->unique_id, $this->user->id);

        $this->assertEquals(120.00, (float) $customer->fresh()->available_credit_balance, 'booked exactly once');
        $charge->refresh();
        $this->assertSame(BillingChargeStatus::Account, $charge->status);
        $this->assertNotNull($charge->customer_account_id, 'charge now linked to the A-R row');
    }

    public function test_eligible_extension_charge_books_ar_and_marks_child_payment_on_account(): void
    {
        $customer = $this->customer();
        [$charge, , $child] = $this->extensionCharge($customer, 150.00);

        AddChargeToAccountService::transfer($charge->unique_id, $this->user->id);

        $this->assertEquals(150.00, (float) $customer->fresh()->available_credit_balance);
        $this->assertSame(BillingChargeStatus::Account, $charge->fresh()->status);

        $payment = $child->payments()->latest('id')->first();
        $this->assertSame(OrderPaymentStatus::Account, $payment->status, 'extension leaves POD → A-R');
        $this->assertSame(OrderPaymentMethod::Account, $payment->payment_method);
    }

    // ── Accounting invariants (mission #4, #6, #7, #8, #9) ────────────────

    public function test_account_debit_equals_the_exact_outstanding_balance(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer);
        $charge   = $this->unbookedDamageCharge($customer, $order, 63.25);

        $result = AddChargeToAccountService::transfer($charge->unique_id, $this->user->id);

        $this->assertEquals(63.25, (float) $result['ledger_row']->amount);
        $this->assertEquals(63.25, (float) $customer->fresh()->available_credit_balance);
    }

    public function test_source_charge_is_settled_as_account_not_paid(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer);
        $charge   = $this->alreadyBookedFuelCharge($customer, $order, 40);

        AddChargeToAccountService::transfer($charge->unique_id, $this->user->id);

        $charge->refresh();
        $this->assertSame(BillingChargeStatus::Account, $charge->status);
        $this->assertFalse($charge->isPaid(), 'must never be marked paid (would over-state collected tax)');
        $this->assertNull($charge->paid_at);
    }

    public function test_no_cash_or_card_receipt_is_created(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer);
        $charge   = $this->unbookedDamageCharge($customer, $order, 99);

        AddChargeToAccountService::transfer($charge->unique_id, $this->user->id);

        // No credit/cash receipt on the A-R ledger, and no settled order payment.
        $this->assertSame(0, CustomerAccount::where('customer_id', $customer->id)->where('type', 'payment')->count());
        $this->assertFalse(
            $order->fresh()->payments()->whereIn('payment_method', ['Cash', 'Card'])->exists()
        );
    }

    public function test_crm_account_balance_increases_exactly_once_on_repeat_submit(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer);
        $charge   = $this->unbookedDamageCharge($customer, $order, 200);

        AddChargeToAccountService::transfer($charge->unique_id, $this->user->id);
        AddChargeToAccountService::transfer($charge->unique_id, $this->user->id); // retry
        AddChargeToAccountService::transfer($charge->unique_id, $this->user->id); // retry

        $this->assertEquals(200.00, (float) $customer->fresh()->available_credit_balance);
        $this->assertSame(1, CustomerAccount::where('customer_id', $customer->id)->where('type', 'charge')->count());
    }

    public function test_source_charge_remains_linked_and_identifiable(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer, 'A2A-3151');
        $charge   = $this->unbookedDamageCharge($customer, $order, 50);

        $result = AddChargeToAccountService::transfer($charge->unique_id, $this->user->id);

        $this->assertSame($result['ledger_row']->id, $charge->fresh()->customer_account_id);
        $this->assertStringContainsString('Order #A2A-3151', $result['ledger_row']->reason);
    }

    // ── Idempotency / concurrency (mission #10, #17) ─────────────────────

    public function test_duplicate_submission_returns_already_and_creates_no_second_debit(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer);
        $charge   = $this->unbookedDamageCharge($customer, $order, 75);

        $first  = AddChargeToAccountService::transfer($charge->unique_id, $this->user->id);
        $second = AddChargeToAccountService::transfer($charge->unique_id, $this->user->id);

        $this->assertSame('transferred', $first['status']);
        $this->assertSame('already', $second['status']);
        $this->assertSame(1, CustomerAccount::where('customer_id', $customer->id)->where('type', 'charge')->count());
    }

    // ── Eligibility & guards (mission #11, #12, #14, #15) ─────────────────

    public function test_ineligible_customer_without_credit_account_is_blocked(): void
    {
        $customer = $this->customer(eligible: false);
        $order    = $this->order($customer);
        $charge   = $this->unbookedDamageCharge($customer, $order, 30);

        $this->expectException(\DomainException::class);
        AddChargeToAccountService::transfer($charge->unique_id, $this->user->id);
    }

    public function test_customer_with_no_credit_limit_is_blocked(): void
    {
        $customer = Customer::create([
            'first_name' => 'No', 'last_name' => 'Limit',
            'email' => 'a2a-nolimit@example.com', 'status' => 'Active',
            'is_credit_account' => 1, 'credit_limit' => 0,
        ]);
        $order  = $this->order($customer);
        $charge = $this->unbookedDamageCharge($customer, $order, 30);

        $this->assertNotNull(AddChargeToAccountService::eligibilityError($charge->fresh()));
        $this->expectException(\DomainException::class);
        AddChargeToAccountService::transfer($charge->unique_id, $this->user->id);
    }

    public function test_paid_charge_cannot_be_added_to_account(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer);
        $charge   = $this->unbookedDamageCharge($customer, $order, 30);
        BillingEngine::markPaid($charge);

        $this->expectException(\DomainException::class);
        AddChargeToAccountService::transfer($charge->fresh()->unique_id, $this->user->id);
    }

    public function test_resolved_charge_cannot_be_added_to_account(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer);
        $charge   = $this->unbookedDamageCharge($customer, $order, 30);
        BillingEngine::markResolved($charge, 'waived', $this->user->id);

        $this->expectException(\DomainException::class);
        AddChargeToAccountService::transfer($charge->fresh()->unique_id, $this->user->id);
    }

    public function test_unsupported_service_ticket_charge_is_blocked(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer);
        $charge   = BillingEngine::charge(new BillingChargeRequest(
            type: BillingChargeType::ServiceTicket->value,
            orderId: $order->id, customerId: $customer->id, amount: 40, taxType: 'free',
            idempotencyKey: 'a2a_svc_' . uniqid(), taxAmount: 0.0,
        ))->fresh();

        $this->assertNotNull(AddChargeToAccountService::eligibilityError($charge));
        $this->expectException(\DomainException::class);
        AddChargeToAccountService::transfer($charge->unique_id, $this->user->id);
    }

    // ── No credit-limit enforcement (mission #13 — documented behavior) ──

    public function test_transfer_is_allowed_over_credit_limit_matching_account_orders(): void
    {
        // The app enforces NO limit ceiling on account orders; this feature
        // matches that — a transfer above the limit is NOT blocked.
        $customer = $this->customer(eligible: true, limit: 10);
        $order    = $this->order($customer);
        $charge   = $this->unbookedDamageCharge($customer, $order, 500);

        $result = AddChargeToAccountService::transfer($charge->unique_id, $this->user->id);

        $this->assertSame('transferred', $result['status']);
        $this->assertEquals(500.0, (float) $customer->fresh()->available_credit_balance);
    }

    // ── Workspace removal & reporting safety (mission #6, #19) ───────────

    public function test_charge_leaves_the_fuel_workspace_after_transfer(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer);

        // Checklist-fuel: OrderProduct-keyed + linked CustomerAccount alert,
        // both 'pending' (the exact states ChargeAlertQueue keys on).
        $product = Product::create(['product_name' => 'A2A Pump', 'slug' => 'a2a-pump-' . uniqid(), 'product_type' => 'Rental']);
        $op = OrderProduct::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'A2A Pump',
            'price' => 60, 'quantity' => 1, 'sub_total' => 60, 'tax' => 0, 'total' => 60,
            'fuel_total_charge' => 60, 'fuel_charge_status' => 'pending',
        ]);
        $ca = new CustomerAccount();
        $ca->customer_id = $customer->id; $ca->order_id = $order->id; $ca->order_product_id = $op->id;
        $ca->amount = 60; $ca->reason = 'Fuel Charge'; $ca->responsible_person_id = $this->user->id;
        $ca->date = now(); $ca->sales_tax_type = 'free'; $ca->sales_tax = 0; $ca->type = 'charge';
        $ca->fuel_alert_status = 'pending'; $ca->save();
        CustomHelper::updateCreditBalance($ca);
        $charge = BillingEngine::charge(new BillingChargeRequest(
            type: BillingChargeType::Fuel->value, orderId: $order->id, customerId: $customer->id, amount: 60,
            taxType: 'free', orderProductId: $op->id, idempotencyKey: 'a2a_wsfuel_' . $op->id,
            customerAccountId: $ca->id, taxAmount: 0.0,
        ))->fresh();

        // Pre-state: both alert surfaces are outstanding (in the workspace).
        $this->assertSame('pending', $op->fresh()->fuel_charge_status->value);
        $this->assertSame('pending', $ca->fresh()->fuel_alert_status);

        AddChargeToAccountService::transfer($charge->unique_id, $this->user->id);

        // Both surfaces move to 'account' — the value ChargeAlertQueue excludes
        // from the fuel queue (whereNotIn) and the CRM branch (requires
        // 'pending') — so the charge is gone from the collection workspace.
        $this->assertSame('account', $op->fresh()->fuel_charge_status->value);
        $this->assertSame('account', $ca->fresh()->fuel_alert_status);
    }

    // ── Structural invariants (#5 N/A, #20 reversal withheld) ────────────

    public function test_outstanding_is_binary_full_or_zero(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer);
        $charge   = $this->unbookedDamageCharge($customer, $order, 45);

        $this->assertEquals(45.0, AddChargeToAccountService::outstanding($charge), 'full while pending');

        BillingEngine::markPaid($charge);
        $this->assertEquals(0.0, AddChargeToAccountService::outstanding($charge->fresh()), 'zero once closed — no partial state');
    }

    public function test_no_reversal_endpoint_exists_in_v1(): void
    {
        // Reversal is deferred by design; assert the route family does not
        // expose a reverse/undo action for an on-account transfer.
        $names = collect(app('router')->getRoutes())->map->getName()->filter()->values();
        $this->assertTrue($names->contains('admin.order-management.orders.billing-charges.add-to-account'));
        $this->assertFalse($names->contains('admin.order-management.orders.billing-charges.reverse-account'));
    }

    // ── Later account payment reduces the balance (mission #18) ──────────

    public function test_later_account_payment_reduces_the_balance_normally(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer);
        $charge   = $this->unbookedDamageCharge($customer, $order, 100);

        AddChargeToAccountService::transfer($charge->unique_id, $this->user->id);
        $this->assertEquals(100.0, (float) $customer->fresh()->available_credit_balance);

        // Simulate a CRM account pay-down (type='payment' → balance decreases).
        $payment = new CustomerAccount();
        $payment->customer_id = $customer->id; $payment->amount = 100; $payment->reason = 'Payment — Damage';
        $payment->responsible_person_id = $this->user->id; $payment->date = now();
        $payment->sales_tax = 0; $payment->type = 'payment'; $payment->payment_type = 'Cash';
        $payment->save();
        CustomHelper::updateCreditBalance($payment);

        $this->assertEquals(0.0, (float) $customer->fresh()->available_credit_balance);
    }

    // ── HTTP endpoint (mission #16 — server-side, permissions bypassed) ──

    public function test_endpoint_transfers_and_returns_success(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer);
        $charge   = $this->unbookedDamageCharge($customer, $order, 80);

        $this->postJson($this->endpoint($charge), ['performed_by' => $this->user->id])
            ->assertOk()->assertJson(['success' => true]);

        $this->assertSame(BillingChargeStatus::Account, $charge->fresh()->status);
    }

    public function test_endpoint_rejects_a_closed_charge_with_422(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer);
        $charge   = $this->unbookedDamageCharge($customer, $order, 80);
        BillingEngine::markPaid($charge);

        $this->postJson($this->endpoint($charge->fresh()), ['performed_by' => $this->user->id])
            ->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_endpoint_requires_performed_by(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer);
        $charge   = $this->unbookedDamageCharge($customer, $order, 80);

        $this->postJson($this->endpoint($charge), [])->assertStatus(422);
    }
}
