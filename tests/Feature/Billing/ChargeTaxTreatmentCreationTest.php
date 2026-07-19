<?php

namespace Tests\Feature\Billing;

use App\Models\Customers\Customer;
use App\Models\Configurations\Setting;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Services\ChargeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sales Tax Architecture Audit / Correction. The CRM "New Charge" modal
 * (ChargeStoreController) and the Dashboard Damage Charge modal
 * (DamageChargeStoreController) both correctly captured the employee's
 * Sales Tax Treatment selection into CustomerAccount.sales_tax_type, but
 * never resolved it into a base/tax split before calling
 * BillingEngine::charge() — BillingCharge.tax_amount silently persisted as
 * 0 regardless of the selection, invisible to every reporting engine that
 * reads billing_charges (not customer_accounts). AlertChargeController and
 * FuelChargeStoreController already did this correctly; this test suite
 * proves the two broken paths now match them, and that the two
 * already-correct paths (now refactored onto the shared
 * ChargeTaxCalculator) are unaffected.
 */
class ChargeTaxTreatmentCreationTest extends TestCase
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
            'first_name' => 'Charge', 'last_name' => 'Tax',
            'email' => 'charge-tax-test@example.com', 'status' => 'Active',
        ]);

        $this->user = User::create([
            'first_name' => 'Gary', 'last_name' => 'Admin',
            'email' => 'gary-charge-tax-test@example.com', 'status' => 'Active',
        ]);

        $this->actingAs($this->user);
    }

    private function latestBillingCharge(string $type): BillingCharge
    {
        return BillingCharge::where('billing_charge_type', $type)->latest('id')->firstOrFail();
    }

    // ── CRM ChargeStoreController — the exact screenshot bug ─────────────

    public function test_crm_new_charge_modal_add_sales_tax_persists_real_tax_on_the_billing_charge(): void
    {
        $this->post(route('admin.crm.customers.customer-account.chargestore'), [
            'customer_id' => $this->customer->id,
            'amount' => 1000,
            'reason' => 'Fuel Charge',
            'responsible_person' => $this->user->id,
            'sales_tax' => 'add',
        ])->assertRedirect();

        $bc = $this->latestBillingCharge('fuel');
        $this->assertSame(1000.0, (float) $bc->amount);
        $this->assertSame(97.50, (float) $bc->tax_amount, 'previously always 0 regardless of the selected treatment');
    }

    public function test_crm_new_charge_modal_damage_add_sales_tax_persists_real_tax(): void
    {
        $this->post(route('admin.crm.customers.customer-account.chargestore'), [
            'customer_id' => $this->customer->id,
            'amount' => 500,
            'reason' => 'Damages',
            'responsible_person' => $this->user->id,
            'sales_tax' => 'add',
        ])->assertRedirect();

        $bc = $this->latestBillingCharge('damage');
        $this->assertSame(500.0, (float) $bc->amount);
        $this->assertSame(48.75, (float) $bc->tax_amount);
    }

    public function test_crm_new_charge_modal_tax_free_stores_zero_tax(): void
    {
        $this->post(route('admin.crm.customers.customer-account.chargestore'), [
            'customer_id' => $this->customer->id,
            'amount' => 1000,
            'reason' => 'Fuel Charge',
            'responsible_person' => $this->user->id,
            'sales_tax' => 'free',
        ])->assertRedirect();

        $bc = $this->latestBillingCharge('fuel');
        $this->assertSame(0.0, (float) $bc->tax_amount);
        $this->assertSame(1000.0, (float) $bc->amount);
    }

    public function test_crm_new_charge_modal_reverse_sales_tax_splits_the_entered_total(): void
    {
        $this->post(route('admin.crm.customers.customer-account.chargestore'), [
            'customer_id' => $this->customer->id,
            'amount' => 109.75,
            'reason' => 'Fuel Charge',
            'responsible_person' => $this->user->id,
            'sales_tax' => 'reverse',
        ])->assertRedirect();

        $bc = $this->latestBillingCharge('fuel');
        $this->assertEqualsWithDelta(100.0, (float) $bc->amount, 0.01, 'base must not equal the tax-inclusive entered total');
        $this->assertEqualsWithDelta(9.75, (float) $bc->tax_amount, 0.01);
    }

    // ── Dashboard DamageChargeStoreController ────────────────────────────

    public function test_dashboard_damage_charge_add_sales_tax_persists_real_tax(): void
    {
        $this->postJson(route('admin.dashboard.damage-charge.store'), [
            'customer_id' => $this->customer->id,
            'amount' => 200,
            'responsible_person' => $this->user->id,
            'sales_tax_type' => 'add',
        ])->assertOk();

        $bc = $this->latestBillingCharge('damage');
        $this->assertSame(200.0, (float) $bc->amount);
        $this->assertSame(19.50, (float) $bc->tax_amount);
    }

    public function test_dashboard_damage_charge_tax_free_stores_zero_tax(): void
    {
        $this->postJson(route('admin.dashboard.damage-charge.store'), [
            'customer_id' => $this->customer->id,
            'amount' => 200,
            'responsible_person' => $this->user->id,
            'sales_tax_type' => 'free',
        ])->assertOk();

        $bc = $this->latestBillingCharge('damage');
        $this->assertSame(0.0, (float) $bc->tax_amount);
    }

    public function test_dashboard_damage_charge_reverse_sales_tax_splits_correctly(): void
    {
        $this->postJson(route('admin.dashboard.damage-charge.store'), [
            'customer_id' => $this->customer->id,
            'amount' => 219.50,
            'responsible_person' => $this->user->id,
            'sales_tax_type' => 'reverse',
        ])->assertOk();

        $bc = $this->latestBillingCharge('damage');
        $this->assertEqualsWithDelta(200.0, (float) $bc->amount, 0.01);
        $this->assertEqualsWithDelta(19.50, (float) $bc->tax_amount, 0.01);
    }

    // ── AlertChargeController — already correct, now refactored (regression) ──

    private function makeOrderWithProduct(): Order
    {
        $order = Order::create([
            'order_date' => now()->toDateString(), 'customer_id' => $this->customer->id,
            'customer_name' => 'Charge Tax', 'subtotal' => 1000, 'grand_total' => 1000,
        ]);
        $product = Product::create([
            'product_name' => 'Charge Tax Test Product', 'slug' => 'charge-tax-test-' . uniqid(), 'product_type' => 'Rental',
        ]);
        OrderProduct::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'Charge Tax Test Product',
            'price' => 1000, 'quantity' => 1, 'sub_total' => 1000, 'tax' => 0, 'total' => 1000,
        ]);

        return $order;
    }

    public function test_order_alert_fuel_charge_add_sales_tax_still_persists_real_tax_after_refactor(): void
    {
        $order = $this->makeOrderWithProduct();

        $this->postJson(route('admin.order-management.orders.alert-charge', $order->unique_id), [
            'type' => 'fuel',
            'amount' => 1000,
            'responsible_person' => $this->user->id,
            'sales_tax_type' => 'add',
        ])->assertOk();

        $bc = $this->latestBillingCharge('fuel');
        $this->assertSame(97.50, (float) $bc->tax_amount);
    }

    public function test_order_alert_damage_charge_reverse_sales_tax_still_works_after_refactor(): void
    {
        $order = $this->makeOrderWithProduct();

        $this->postJson(route('admin.order-management.orders.alert-charge', $order->unique_id), [
            'type' => 'damage',
            'amount' => 109.75,
            'responsible_person' => $this->user->id,
            'sales_tax_type' => 'reverse',
        ])->assertOk();

        $bc = $this->latestBillingCharge('damage');
        $this->assertEqualsWithDelta(100.0, (float) $bc->amount, 0.01);
        $this->assertEqualsWithDelta(9.75, (float) $bc->tax_amount, 0.01);
    }

    // ── Dashboard FuelChargeStoreController — already correct, now refactored (regression) ──

    public function test_dashboard_fuel_charge_add_sales_tax_still_persists_real_tax_after_refactor(): void
    {
        $this->postJson(route('admin.dashboard.fuel-charge.store'), [
            'customer_id' => $this->customer->id,
            'amount' => 300,
            'responsible_person' => $this->user->id,
            'sales_tax_type' => 'add',
        ])->assertOk();

        $bc = $this->latestBillingCharge('fuel');
        $this->assertSame(29.25, (float) $bc->tax_amount);
    }

    // ── Mobile checklist fuel charge ──────────────────────────────────────

    public function test_mobile_fuel_charge_defaults_to_tax_free_when_app_sends_no_choice(): void
    {
        // Preserves the exact current, already-in-production behavior — the
        // app doesn't send a choice today, and defaulting to 'add' would be
        // a silent, unilateral tax-policy reversal with no rollout
        // mechanism (no employee/driver ever expressed intent to tax these,
        // unlike the CRM/Dashboard fixes which restored a discarded
        // choice). See this correction's report for the reasoning.
        $order = $this->makeOrderWithProduct();
        $orderProduct = $order->products()->first();
        $orderProduct->update(['fuel_total_charge' => 50]);

        $ca = ChargeService::createFromOrderProduct($orderProduct, 'fuel', $this->user->id);

        $this->assertNotNull($ca);
        $this->assertSame('free', $ca->sales_tax_type);
    }

    public function test_mobile_fuel_charge_honors_an_explicit_add_sales_tax_choice(): void
    {
        $order = $this->makeOrderWithProduct();
        $orderProduct = $order->products()->first();
        $orderProduct->update(['fuel_total_charge' => 50]);

        $ca = ChargeService::createFromOrderProduct($orderProduct, 'fuel', $this->user->id, null, 'add');

        $this->assertSame('add', $ca->sales_tax_type);
    }

    public function test_mobile_fuel_charge_rejects_an_invalid_treatment(): void
    {
        $order = $this->makeOrderWithProduct();
        $orderProduct = $order->products()->first();
        $orderProduct->update(['fuel_total_charge' => 50]);

        $this->expectException(\InvalidArgumentException::class);
        ChargeService::createFromOrderProduct($orderProduct, 'fuel', $this->user->id, null, 'bogus');
    }
}
