<?php

namespace Tests\Feature\Billing;

use App\Enums\Billing\BillingChargeRefundStatus;
use App\Enums\Billing\BillingChargeType;
use App\Enums\Billing\BillingSourceEvent;
use App\Enums\Billing\BillingSourceModule;
use App\Http\DataObjects\BillingChargeRequest;
use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\BillingChargeRefund;
use App\Services\BillingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Billing Charge Refund Allocation — Safe Linked Refunds (Option B).
 *
 * Free-form refunds (no billing_charge_unique_id) take a completely
 * separate, byte-for-byte unchanged code path — see
 * test_free_form_refund_with_no_linked_charge_is_unaffected(). Every other
 * test here exercises the charge-linked path: ownership/eligibility
 * validation, cumulative-refund protection via billing_charge_refunds, and
 * idempotency.
 */
class ChargeLinkedRefundTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Middleware is deliberately left fully enabled. The web group's
        // CSRF middleware (ValidateCsrfToken, an alias of VerifyCsrfToken)
        // short-circuits whenever the app is running unit tests in the
        // 'testing' environment (VerifyCsrfToken::handle() checks
        // $app->runningUnitTests(), i.e. $app['env'] === 'testing'), so a
        // tokenless $this->post() reaches the controller in any correctly
        // bootstrapped test process — the same convention the passing
        // report-suite feature tests (e.g. SalesTaxExtensionReportingTest)
        // rely on when POSTing through this same stack. A
        // TokenMismatchException from these tests therefore does not mean
        // the test needs a token or withoutMiddleware(): it means the
        // process is NOT in the 'testing' environment (production .env
        // loaded, production database targeted) and the run must be stopped
        // and the environment fixed, not the test loosened. Auth is
        // satisfied via actingAs() below.

        // updateOrCreate, not create: SettingSeeder already seeds a
        // 'sales_tax' row against a properly-migrated database (unique on
        // setting_name) — force it to this test's exact rate rather than
        // colliding with or silently trusting whatever the seeder shipped.
        Setting::updateOrCreate(
            ['setting_name' => 'sales_tax'],
            ['setting_type' => 'Product Settings', 'value_type' => 'text', 'setting_title' => 'sales_tax', 'setting_value' => '0.0975']
        );

        $this->customer = Customer::create([
            'first_name' => 'Refund', 'last_name' => 'Linked',
            'email' => 'refund-linked-test@example.com', 'status' => 'Active',
            'tax_status' => 'Exempt', // deliberately opposite of the charge's own 'add' treatment
        ]);

        $this->user = User::create([
            'first_name' => 'Gary', 'last_name' => 'Admin',
            'email' => 'gary-refund-linked-test@example.com', 'status' => 'Active',
        ]);

        $this->actingAs($this->user);
    }

    /** @param 'add'|'free'|'reverse' $treatment */
    private function makeCharge(string $type, float $enteredAmount, string $treatment, ?int $customerId = null, string $status = 'paid'): BillingCharge
    {
        $customerId ??= $this->customer->id;
        $rate = 0.0975;
        $resolved = \App\Services\ChargeTaxCalculator::calculate($enteredAmount, $treatment, $treatment === 'free' ? 0.0 : $rate);

        $ca = new CustomerAccount();
        $ca->customer_id = $customerId;
        $ca->amount = $enteredAmount;
        $ca->reason = $type === BillingChargeType::Fuel->value ? 'Fuel Charge' : 'Damages';
        $ca->responsible_person_id = $this->user->id;
        $ca->date = now();
        $ca->sales_tax_type = $treatment;
        $ca->sales_tax = 0;
        $ca->type = 'charge';
        $ca->save();

        $bc = BillingEngine::charge(new BillingChargeRequest(
            type: $type,
            orderId: null,
            customerId: $customerId,
            amount: $resolved['base_amount'],
            taxType: $treatment,
            responsiblePersonId: $this->user->id,
            sourceModule: BillingSourceModule::AdminFuelCharge->value,
            sourceEvent: BillingSourceEvent::AdminFuelChargeCreated->value,
            sourceReferenceType: 'CustomerAccount',
            sourceReferenceId: $ca->id,
            idempotencyKey: 'test_charge_' . uniqid(),
            customerAccountId: $ca->id,
            taxAmount: $resolved['tax_amount'],
        ));

        if ($status === 'paid') {
            BillingEngine::markPaid($bc);
        } elseif ($status === 'uncollectible') {
            BillingEngine::markUncollectible($bc, $this->user->id);
        } elseif ($status === 'voided') {
            $bc->status = 'voided';
            $bc->save();
        }
        // else: leave at the default 'pending' status BillingEngine::charge() creates it with.

        return $bc->fresh();
    }

    private function makePaidFuelCharge(float $base, float $tax, string $treatment = 'add'): BillingCharge
    {
        // base/tax are pre-resolved values here (matching the old helper's
        // contract used by several tests below) — construct via 'add' with
        // the base as the entered amount so amount/tax_amount land exactly.
        $ca = new CustomerAccount();
        $ca->customer_id = $this->customer->id;
        $ca->amount = $base;
        $ca->reason = 'Fuel Charge';
        $ca->responsible_person_id = $this->user->id;
        $ca->date = now();
        $ca->sales_tax_type = $tax > 0 ? 'add' : 'free';
        $ca->sales_tax = 0;
        $ca->type = 'charge';
        $ca->save();

        $bc = BillingEngine::charge(new BillingChargeRequest(
            type: BillingChargeType::Fuel->value,
            orderId: null,
            customerId: $this->customer->id,
            amount: $base,
            taxType: $tax > 0 ? 'add' : 'free',
            responsiblePersonId: $this->user->id,
            sourceModule: BillingSourceModule::AdminFuelCharge->value,
            sourceEvent: BillingSourceEvent::AdminFuelChargeCreated->value,
            sourceReferenceType: 'CustomerAccount',
            sourceReferenceId: $ca->id,
            idempotencyKey: 'test_refund_linked_' . $ca->id,
            customerAccountId: $ca->id,
            taxAmount: $tax,
        ));
        BillingEngine::markPaid($bc);

        return $bc;
    }

    private function postRefund(array $overrides = [])
    {
        return $this->post(route('admin.crm.customers.customer-account.refundstore'), array_merge([
            'customer_id' => $this->customer->id,
            'amount' => 100,
            'reason' => 'Fuel charge refund',
            'responsible_person' => $this->user->id,
        ], $overrides));
    }

    // ── Free-form (unchanged) ─────────────────────────────────────────────

    public function test_free_form_refund_with_no_linked_charge_is_unaffected(): void
    {
        $this->assertSame(0, CustomerAccount::where('type', 'refund')->count(), 'no refund row may exist before the POST — a nonzero count means the test is reading foreign database state');

        $this->postRefund(['reason' => 'Goodwill credit'])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(1, CustomerAccount::where('type', 'refund')->count(), 'the POST must create exactly one refund row — zero means the request was rejected before reaching the controller');

        $refund = CustomerAccount::where('type', 'refund')
            ->where('customer_id', $this->customer->id)
            ->where('reason', 'Goodwill credit')
            ->firstOrFail();
        $this->assertSame(0.0, (float) $refund->sales_tax, 'tax-exempt customer, unlinked refund — unchanged formula');
        $this->assertSame(0, BillingChargeRefund::count(), 'no allocation row for a free-form refund');
    }

    // ── Ownership & Eligibility ─────────────────────────────────────────

    public function test_unknown_billing_charge_is_rejected_by_form_validation(): void
    {
        $this->postRefund(['billing_charge_unique_id' => 'BLC-DOES-NOT-EXIST'])
            ->assertSessionHasErrors('billing_charge_unique_id');
        $this->assertSame(0, CustomerAccount::where('type', 'refund')->count());
    }

    public function test_charge_belonging_to_another_customer_is_rejected(): void
    {
        $otherCustomer = Customer::create([
            'first_name' => 'Other', 'last_name' => 'Customer',
            'email' => 'other-customer-refund-test@example.com', 'status' => 'Active',
        ]);
        $bc = $this->makeCharge('fuel', 200.0, 'add', $otherCustomer->id);

        $this->postRefund(['amount' => 100, 'billing_charge_unique_id' => $bc->unique_id])
            ->assertSessionHasErrors('error');
        $this->assertSame(0, CustomerAccount::where('type', 'refund')->count());
        $this->assertSame(0, BillingChargeRefund::count());
    }

    public function test_extension_charge_is_rejected(): void
    {
        $order = \App\Models\Orders\Order::create([
            'order_number' => 'REFTEST-EXT', 'order_date' => now()->toDateString(),
            'customer_id' => $this->customer->id, 'customer_name' => 'Refund Linked',
            'subtotal' => 500, 'tax_amount' => 48.75, 'grand_total' => 548.75,
        ]);
        $bc = BillingEngine::charge(new BillingChargeRequest(
            type: BillingChargeType::Extension->value,
            orderId: $order->id,
            customerId: $this->customer->id,
            amount: 500,
            taxType: 'add',
            responsiblePersonId: $this->user->id,
            sourceModule: BillingSourceModule::RentalExtension->value,
            sourceEvent: BillingSourceEvent::RentalExtensionCreated->value,
            sourceReferenceType: 'Order',
            sourceReferenceId: $order->id,
            idempotencyKey: 'test_extension_reject_' . $order->id,
            childOrderId: $order->id,
            customerAccountId: null,
            taxAmount: 48.75,
        ));
        BillingEngine::markPaid($bc);

        $this->postRefund(['amount' => 500, 'billing_charge_unique_id' => $bc->unique_id])
            ->assertSessionHasErrors('error');
        $this->assertSame(0, BillingChargeRefund::count());
    }

    public function test_unpaid_pending_charge_is_rejected(): void
    {
        $bc = $this->makeCharge('fuel', 200.0, 'add', status: 'pending');

        $this->postRefund(['amount' => 100, 'billing_charge_unique_id' => $bc->unique_id])
            ->assertSessionHasErrors('error');
        $this->assertSame(0, BillingChargeRefund::count());
    }

    public function test_voided_charge_is_rejected(): void
    {
        $bc = $this->makeCharge('fuel', 200.0, 'add', status: 'voided');

        $this->postRefund(['amount' => 100, 'billing_charge_unique_id' => $bc->unique_id])
            ->assertSessionHasErrors('error');
        $this->assertSame(0, BillingChargeRefund::count());
    }

    public function test_uncollectible_charge_is_rejected(): void
    {
        $bc = $this->makeCharge('damage', 200.0, 'add', status: 'uncollectible');

        $this->postRefund(['amount' => 100, 'billing_charge_unique_id' => $bc->unique_id])
            ->assertSessionHasErrors('error');
        $this->assertSame(0, BillingChargeRefund::count());
    }

    public function test_paid_fuel_charge_is_accepted(): void
    {
        $bc = $this->makeCharge('fuel', 200.0, 'add');

        $this->postRefund(['amount' => 100, 'billing_charge_unique_id' => $bc->unique_id])
            ->assertRedirect()->assertSessionDoesntHaveErrors();
        $this->assertSame(1, BillingChargeRefund::count());
    }

    public function test_paid_damage_charge_is_accepted(): void
    {
        $bc = $this->makeCharge('damage', 200.0, 'add');

        $this->postRefund(['amount' => 100, 'reason' => 'Damage charge refund', 'billing_charge_unique_id' => $bc->unique_id])
            ->assertRedirect()->assertSessionDoesntHaveErrors();
        $this->assertSame(1, BillingChargeRefund::count());
    }

    // ── Refund Amounts ─────────────────────────────────────────────────

    public function test_charge_linked_full_refund_splits_at_the_charges_own_rate(): void
    {
        $bc = $this->makePaidFuelCharge(200.0, 19.50);

        $this->postRefund(['amount' => 219.50, 'billing_charge_unique_id' => $bc->unique_id])->assertRedirect();

        $refund = CustomerAccount::where('type', 'refund')->where('customer_id', $this->customer->id)->latest('id')->firstOrFail();
        $this->assertSame('reverse', $refund->sales_tax_type);
        $this->assertEqualsWithDelta(0.0975, (float) $refund->sales_tax, 0.0001, 'must use the charge\'s own effective rate, not the customer exemption status (Tax Exempt here) or a hardcoded global default');

        $allocation = BillingChargeRefund::firstOrFail();
        $this->assertSame($bc->id, $allocation->billing_charge_id);
        $this->assertSame($refund->id, $allocation->customer_account_id);
        $this->assertEqualsWithDelta(200.0, (float) $allocation->base_amount, 0.01);
        $this->assertEqualsWithDelta(19.50, (float) $allocation->tax_amount, 0.01);
        $this->assertSame(BillingChargeRefundStatus::Allocated, $allocation->status);
    }

    public function test_charge_linked_partial_refund_splits_proportionally(): void
    {
        $bc = $this->makePaidFuelCharge(200.0, 19.50);

        $this->postRefund(['amount' => 109.75, 'billing_charge_unique_id' => $bc->unique_id])->assertRedirect();

        $refund = CustomerAccount::where('type', 'refund')->where('customer_id', $this->customer->id)->latest('id')->firstOrFail();
        $this->assertEqualsWithDelta(0.0975, (float) $refund->sales_tax, 0.0001);
    }

    public function test_two_partial_refunds_within_the_original_total_both_succeed(): void
    {
        $bc = $this->makePaidFuelCharge(200.0, 19.50); // total 219.50

        $this->postRefund(['amount' => 100, 'billing_charge_unique_id' => $bc->unique_id])
            ->assertRedirect()->assertSessionDoesntHaveErrors();
        $this->postRefund(['amount' => 119.50, 'billing_charge_unique_id' => $bc->unique_id])
            ->assertRedirect()->assertSessionDoesntHaveErrors();

        $this->assertSame(2, BillingChargeRefund::count());
        $remaining = \App\Services\Orders\BillingChargeRefundService::remainingRefundable($bc->fresh());
        $this->assertSame(0.0, $remaining['total']);
    }

    public function test_second_refund_exceeding_the_remaining_total_is_rejected(): void
    {
        // THE core cumulative-protection scenario the prior implementation
        // could not guard against: 150 + 150 = 300 > 219.50.
        $bc = $this->makePaidFuelCharge(200.0, 19.50);

        $this->postRefund(['amount' => 150, 'billing_charge_unique_id' => $bc->unique_id])
            ->assertRedirect()->assertSessionDoesntHaveErrors();

        $response = $this->postRefund(['amount' => 150, 'billing_charge_unique_id' => $bc->unique_id]);
        $response->assertSessionHasErrors('error');

        $this->assertSame(1, BillingChargeRefund::count(), 'the second, over-limit refund must not create an allocation row');
        $this->assertSame(1, CustomerAccount::where('type', 'refund')->count());
    }

    public function test_cumulative_base_and_tax_cannot_exceed_the_original_recorded_values(): void
    {
        $bc = $this->makePaidFuelCharge(200.0, 19.50);

        $this->postRefund(['amount' => 219.50, 'billing_charge_unique_id' => $bc->unique_id])->assertSessionDoesntHaveErrors();

        $totalBase = BillingChargeRefund::where('billing_charge_id', $bc->id)->sum('base_amount');
        $totalTax = BillingChargeRefund::where('billing_charge_id', $bc->id)->sum('tax_amount');
        $this->assertLessThanOrEqual(200.0, (float) $totalBase);
        $this->assertLessThanOrEqual(19.50, (float) $totalTax);
    }

    public function test_refund_linked_to_a_tax_free_charge_carries_zero_rate(): void
    {
        $bc = $this->makePaidFuelCharge(150.0, 0.0);

        $this->postRefund(['amount' => 150, 'billing_charge_unique_id' => $bc->unique_id])->assertRedirect();

        $refund = CustomerAccount::where('type', 'refund')->where('customer_id', $this->customer->id)->latest('id')->firstOrFail();
        $this->assertSame(0.0, (float) $refund->sales_tax);

        $allocation = BillingChargeRefund::firstOrFail();
        $this->assertSame(0.0, (float) $allocation->tax_amount);
    }

    public function test_add_sales_tax_charge_preserves_correct_split(): void
    {
        $bc = $this->makeCharge('fuel', 200.0, 'add'); // amount=200, tax=19.50

        $this->postRefund(['amount' => 219.50, 'billing_charge_unique_id' => $bc->unique_id])->assertSessionDoesntHaveErrors();

        $allocation = BillingChargeRefund::firstOrFail();
        $this->assertEqualsWithDelta(200.0, (float) $allocation->base_amount, 0.01);
        $this->assertEqualsWithDelta(19.50, (float) $allocation->tax_amount, 0.01);
    }

    public function test_reverse_sales_tax_charge_preserves_correct_split(): void
    {
        // Entered $219.50 as a tax-inclusive total at creation — resolves to
        // base ≈200 / tax ≈19.50, same as the 'add' case, proving the refund
        // split works off the CHARGE's stored amount/tax_amount regardless
        // of which original treatment produced them.
        $bc = $this->makeCharge('fuel', 219.50, 'reverse');

        $this->postRefund(['amount' => (float) $bc->amount + (float) $bc->tax_amount, 'billing_charge_unique_id' => $bc->unique_id])
            ->assertSessionDoesntHaveErrors();

        $allocation = BillingChargeRefund::firstOrFail();
        $this->assertEqualsWithDelta((float) $bc->amount, (float) $allocation->base_amount, 0.01);
        $this->assertEqualsWithDelta((float) $bc->tax_amount, (float) $allocation->tax_amount, 0.01);
    }

    public function test_final_refund_consumes_the_exact_remaining_amount_avoiding_rounding_residue(): void
    {
        // $100.03 base at 9.75% doesn't divide evenly — after a $50 partial
        // refund, the final refund must consume the EXACT remaining figures
        // rather than re-deriving via division and leaving a stray cent.
        $bc = $this->makeCharge('fuel', 100.03, 'add');
        $originalTotal = round((float) $bc->amount + (float) $bc->tax_amount, 2);

        $this->postRefund(['amount' => 50.00, 'billing_charge_unique_id' => $bc->unique_id])->assertSessionDoesntHaveErrors();
        $afterFirst = \App\Services\Orders\BillingChargeRefundService::remainingRefundable($bc->fresh());

        $this->postRefund(['amount' => $afterFirst['total'], 'billing_charge_unique_id' => $bc->unique_id])->assertSessionDoesntHaveErrors();

        $finalRemaining = \App\Services\Orders\BillingChargeRefundService::remainingRefundable($bc->fresh());
        $this->assertSame(0.0, $finalRemaining['base']);
        $this->assertSame(0.0, $finalRemaining['tax']);
        $this->assertSame(0.0, $finalRemaining['total']);

        $totalRefunded = round(BillingChargeRefund::where('billing_charge_id', $bc->id)->sum('total_amount'), 2);
        $this->assertSame($originalTotal, $totalRefunded, 'two partial refunds must sum to exactly the original total, no rounding drift');
    }

    public function test_remaining_values_never_go_negative(): void
    {
        $bc = $this->makePaidFuelCharge(200.0, 19.50);
        // Manually force an over-allocation scenario (bypassing the
        // controller) to prove the SERVICE's own floor, independent of the
        // controller-level guard.
        BillingChargeRefund::create([
            'billing_charge_id' => $bc->id, 'base_amount' => 500, 'tax_amount' => 50, 'total_amount' => 550,
            'status' => BillingChargeRefundStatus::Allocated->value,
        ]);

        $remaining = \App\Services\Orders\BillingChargeRefundService::remainingRefundable($bc->fresh());
        $this->assertSame(0.0, $remaining['base']);
        $this->assertSame(0.0, $remaining['tax']);
        $this->assertSame(0.0, $remaining['total']);
    }

    // ── Status & Idempotency ──────────────────────────────────────────────

    public function test_pending_allocation_does_not_reduce_refundable_balance(): void
    {
        $bc = $this->makePaidFuelCharge(200.0, 19.50);
        BillingChargeRefund::create([
            'billing_charge_id' => $bc->id, 'base_amount' => 100, 'tax_amount' => 9.75, 'total_amount' => 109.75,
            'status' => BillingChargeRefundStatus::Pending->value,
        ]);

        $remaining = \App\Services\Orders\BillingChargeRefundService::remainingRefundable($bc->fresh());
        $this->assertSame(219.50, $remaining['total'], 'a Pending allocation must not consume balance');
    }

    public function test_failed_allocation_does_not_reduce_refundable_balance(): void
    {
        $bc = $this->makePaidFuelCharge(200.0, 19.50);
        BillingChargeRefund::create([
            'billing_charge_id' => $bc->id, 'base_amount' => 100, 'tax_amount' => 9.75, 'total_amount' => 109.75,
            'status' => BillingChargeRefundStatus::Failed->value, 'failure_reason' => 'test',
        ]);

        $remaining = \App\Services\Orders\BillingChargeRefundService::remainingRefundable($bc->fresh());
        $this->assertSame(219.50, $remaining['total']);
    }

    public function test_duplicate_idempotency_key_does_not_duplicate_the_refund(): void
    {
        $bc = $this->makePaidFuelCharge(200.0, 19.50);
        $token = 'idem-test-token-' . uniqid();

        $this->postRefund(['amount' => 100, 'billing_charge_unique_id' => $bc->unique_id, 'idempotency_token' => $token])
            ->assertRedirect()->assertSessionDoesntHaveErrors();

        // Simulate the cache lock having already expired/released (real
        // resubmit scenario) by calling again with the SAME token — the
        // database's unique index on idempotency_key is the final backstop.
        $this->postRefund(['amount' => 100, 'billing_charge_unique_id' => $bc->unique_id, 'idempotency_token' => $token])
            ->assertRedirect();

        $this->assertSame(1, BillingChargeRefund::count(), 'a repeated idempotency key must never create a second allocation');
        $this->assertSame(1, CustomerAccount::where('type', 'refund')->count());
    }

    public function test_different_idempotency_keys_allow_two_legitimate_partial_refunds(): void
    {
        $bc = $this->makePaidFuelCharge(200.0, 19.50);

        $this->postRefund(['amount' => 100, 'billing_charge_unique_id' => $bc->unique_id, 'idempotency_token' => 'token-a-' . uniqid()])
            ->assertSessionDoesntHaveErrors();
        $this->postRefund(['amount' => 119.50, 'billing_charge_unique_id' => $bc->unique_id, 'idempotency_token' => 'token-b-' . uniqid()])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(2, BillingChargeRefund::count());
    }
}
