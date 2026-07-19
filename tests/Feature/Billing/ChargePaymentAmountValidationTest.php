<?php

namespace Tests\Feature\Billing;

use App\Enums\Billing\BillingChargeType;
use App\Enums\Billing\BillingSourceEvent;
use App\Enums\Billing\BillingSourceModule;
use App\Http\DataObjects\BillingChargeRequest;
use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\BillingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sales Tax Architecture Audit / Correction — server-side gateway amount
 * guard. Fuel/damage/extension BillingCharges are paid in full, in one
 * shot; the "Make a Payment" modal's amount field is a plain editable
 * <input> pre-filled correctly by JS, but nothing previously verified the
 * submitted amount server-side before charging the gateway and marking the
 * charge paid. This suite proves the new
 * PaymentStoreController::validateAmountAgainstBillingCharge() guard.
 */
class ChargePaymentAmountValidationTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // updateOrCreate, not create: SettingSeeder already seeds a
        // 'sales_tax' row against a properly-migrated database (unique on
        // setting_name) — force it to this test's exact rate rather than
        // colliding with or silently trusting whatever the seeder shipped.
        Setting::updateOrCreate(
            ['setting_name' => 'sales_tax'],
            ['setting_type' => 'Product Settings', 'value_type' => 'text', 'setting_title' => 'sales_tax', 'setting_value' => '0.0975']
        );

        $this->customer = Customer::create([
            'first_name' => 'Payment', 'last_name' => 'Validation',
            'email' => 'payment-validation-test@example.com', 'status' => 'Active',
        ]);

        $this->user = User::create([
            'first_name' => 'Gary', 'last_name' => 'Admin',
            'email' => 'gary-payment-validation-test@example.com', 'status' => 'Active',
        ]);

        $this->actingAs($this->user);
    }

    /** Fuel charge: $200 base + $19.50 tax (9.75%), CustomerAccount-linked. */
    private function makeFuelChargeWithTax(): array
    {
        $ca = new CustomerAccount();
        $ca->customer_id = $this->customer->id;
        $ca->amount = 200;
        $ca->reason = 'Fuel Charge';
        $ca->responsible_person_id = $this->user->id;
        $ca->responsible_person_name = $this->user->full_name;
        $ca->date = now();
        $ca->sales_tax_type = 'add';
        $ca->sales_tax = 0.0975;
        $ca->type = 'charge';
        $ca->fuel_alert_status = 'pending';
        $ca->save();

        $bc = BillingEngine::charge(new BillingChargeRequest(
            type: BillingChargeType::Fuel->value,
            orderId: null,
            customerId: $this->customer->id,
            amount: 200,
            taxType: 'add',
            responsiblePersonId: $this->user->id,
            sourceModule: BillingSourceModule::AdminFuelCharge->value,
            sourceEvent: BillingSourceEvent::AdminFuelChargeCreated->value,
            sourceReferenceType: 'CustomerAccount',
            sourceReferenceId: $ca->id,
            idempotencyKey: "test_fuel_payment_validation:{$ca->id}",
            customerAccountId: $ca->id,
            taxAmount: 19.50,
        ));

        return [$ca, $bc];
    }

    public function test_fuel_payment_rejects_an_amount_that_omits_tax(): void
    {
        [$ca, $bc] = $this->makeFuelChargeWithTax();

        $response = $this->post(route('admin.dashboard.paymentstore'), [
            'source' => 'crm',
            'customer_id' => $this->customer->id,
            'customer_account_id' => $ca->unique_id,
            'billing_charge_unique_id' => $bc->unique_id,
            'amount' => 200, // base only — omits the $19.50 tax
            'payment_type' => 'Cash',
            'responsible_person' => $this->user->id,
        ]);

        $response->assertRedirect();
        $this->assertTrue(session()->has('error'), 'must reject an amount that silently drops the tax component');

        $bc->refresh();
        $this->assertNotSame('paid', $bc->status?->value, 'the charge must not be marked paid off a rejected amount');
    }

    public function test_fuel_payment_accepts_the_exact_tax_inclusive_total(): void
    {
        [$ca, $bc] = $this->makeFuelChargeWithTax();

        $response = $this->post(route('admin.dashboard.paymentstore'), [
            'source' => 'crm',
            'customer_id' => $this->customer->id,
            'customer_account_id' => $ca->unique_id,
            'billing_charge_unique_id' => $bc->unique_id,
            'amount' => 219.50, // base + tax
            'payment_type' => 'Cash',
            'responsible_person' => $this->user->id,
        ]);

        $response->assertRedirect();
        $this->assertFalse(session()->has('error'));

        $bc->refresh();
        $this->assertSame('paid', $bc->status->value);
    }

    public function test_fuel_payment_with_no_linked_billing_charge_is_unaffected(): void
    {
        // A manual/legacy CustomerAccount payment not tied to any specific
        // BillingCharge — preserves existing behavior exactly (no canonical
        // total to validate against).
        $ca = new CustomerAccount();
        $ca->customer_id = $this->customer->id;
        $ca->amount = 50;
        $ca->reason = 'Miscellaneous';
        $ca->responsible_person_id = $this->user->id;
        $ca->date = now();
        $ca->type = 'charge';
        $ca->sales_tax_type = 'free';
        $ca->sales_tax = 0;
        $ca->save();

        $response = $this->post(route('admin.dashboard.paymentstore'), [
            'source' => 'crm',
            'customer_id' => $this->customer->id,
            'customer_account_id' => $ca->unique_id,
            'amount' => 999, // arbitrary — no billing charge to check against
            'payment_type' => 'Cash',
            'responsible_person' => $this->user->id,
        ]);

        $response->assertRedirect();
        $this->assertFalse(session()->has('error'));
    }

    // ── Extensions ──────────────────────────────────────────────────────

    private function createPaidExtension(string $base = '500.00'): array
    {
        $parentOrderNumber = 'PAYVAL-' . uniqid();
        $parent = Order::create([
            'order_number' => $parentOrderNumber,
            'order_date' => now()->toDateString(), 'customer_id' => $this->customer->id,
            'customer_name' => 'Payment Validation', 'subtotal' => 1000, 'grand_total' => 1000,
        ]);

        $this->postJson(
            route('admin.order-management.orders.extension.store', ['unique_id' => $parent->unique_id]),
            [
                'description' => 'Extension', 'base_amount' => $base, 'add_tax' => true,
                'responsible_person' => $this->user->id,
            ]
        )->assertOk();

        $extension = Order::where('order_number', $parentOrderNumber . '-A')->firstOrFail();
        $bc = \App\Models\Orders\BillingCharge::where('child_order_id', $extension->id)->firstOrFail();

        return [$extension, $bc];
    }

    public function test_extension_payment_rejects_an_amount_that_omits_tax(): void
    {
        [$extension, $bc] = $this->createPaidExtension('500.00');

        $response = $this->post(route('admin.dashboard.paymentstore'), [
            'source' => 'crm',
            'customer_id' => $this->customer->id,
            'billing_charge_unique_id' => $bc->unique_id,
            'amount' => 500, // base only — omits ~$48.75 tax at 9.75%
            'payment_type' => 'Cash',
            'responsible_person' => $this->user->id,
        ]);

        $response->assertRedirect();
        $bc->refresh();
        $this->assertNotSame('paid', $bc->status->value, 'must not accept an amount that silently drops the tax component');
    }

    public function test_extension_payment_accepts_the_exact_tax_inclusive_total(): void
    {
        [$extension, $bc] = $this->createPaidExtension('500.00');
        $expectedTotal = round((float) $bc->amount + (float) $bc->tax_amount, 2);

        $response = $this->post(route('admin.dashboard.paymentstore'), [
            'source' => 'crm',
            'customer_id' => $this->customer->id,
            'billing_charge_unique_id' => $bc->unique_id,
            'amount' => $expectedTotal,
            'payment_type' => 'Cash',
            'responsible_person' => $this->user->id,
        ]);

        $response->assertRedirect();
        $bc->refresh();
        $this->assertSame('paid', $bc->status->value);
    }
}
