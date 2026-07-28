<?php

namespace Tests\Feature\Discounts;

use App\Enums\Discounts\DiscountTargetType;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Customers\CustomerCredit;
use App\Models\Discounts\ProductDiscount;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Configurations\Setting;
use App\Services\ChargeService;
use App\Services\CustomerCreditService;
use App\Services\Discounts\DiscountException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Increment 2b — FUEL + DAMAGE surfaces (discount at charge-creation time).
 * The discount reduces the base BEFORE updateCreditBalance books A/R, inside
 * the creation transaction; a rejection rolls back BOTH the redemption and the
 * charge; Store Credit is never a payment.
 */
class DiscountChargeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const RATE = 0.10;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::updateOrCreate(['setting_name' => 'sales_tax'], ['setting_type' => 'Product Settings', 'setting_value' => self::RATE]);
        $this->admin = User::create(['first_name' => 'Chg', 'last_name' => 'Admin', 'email' => 'chg-admin@test.local', 'status' => 'Active']);
        $this->actingAs($this->admin);
    }

    private function customer(float $credit): Customer
    {
        $c = Customer::create(['first_name' => 'Chg', 'last_name' => 'Cust', 'email' => 'chg-' . uniqid() . '@test.local', 'status' => 'Active']);
        if ($credit > 0) {
            CustomerCredit::create(['customer_id' => $c->id, 'type' => 'grant', 'amount' => $credit, 'reason' => 'seed']);
        }
        return $c;
    }

    private function fuel(Customer $c, float $amount, ?string $taxType, ?float $discount, ?string $key = null): CustomerAccount
    {
        return ChargeService::createManualCharge(
            customerId: $c->id, type: 'fuel', amount: $amount, salesTaxType: $taxType,
            notes: null, responsibleUserId: $this->admin->id, orderId: null, sourceContext: 'dashboard',
            storeCreditDiscount: $discount, discountIdempotencyKey: $key,
        );
    }

    private function bal(Customer $c): float
    {
        return round((float) $c->fresh()->available_credit_balance, 2);
    }

    // ── FUEL via the canonical service ───────────────────────────────────

    public function test_no_discount_fuel_unchanged(): void
    {
        $c = $this->customer(0);
        $r = $this->fuel($c, 100, 'add', null);
        $this->assertEquals(100.00, (float) $r->amount);
        $this->assertEquals(110.00, $this->bal($c), 'A/R = 100 + 10 tax');
        $this->assertEquals(0, ProductDiscount::count());
    }

    public function test_partial_fuel_discount_reduces_base_and_ar(): void
    {
        $c = $this->customer(1000);
        $r = $this->fuel($c, 1000, 'add', 400);
        $this->assertEquals(600.00, (float) $r->amount, 'stored base is discounted');
        $this->assertEquals(660.00, $this->bal($c), 'A/R booked on discounted base (600 + 60 tax)');

        $d = ProductDiscount::where('target_type', DiscountTargetType::FuelCharge->value)->first();
        $this->assertNotNull($d);
        $this->assertEquals($r->id, $d->target_id, 'linked to the CustomerAccount row');
        $this->assertEquals(400.00, (float) $d->calculated_discount_amount);
        $this->assertNotNull($d->store_credit_redemption_id);
        $this->assertEquals(600.00, CustomerCreditService::remainingBalance($c->id));
        $this->assertEquals(0, DB::table('customer_accounts')->where('type', 'payment')->count(), 'no Store Credit payment');
    }

    public function test_full_fuel_discount_zero_charge(): void
    {
        $c = $this->customer(500);
        $r = $this->fuel($c, 500, 'add', 500);
        $this->assertEquals(0.00, (float) $r->amount);
        $this->assertEquals(0.00, $this->bal($c), 'no A/R booked');
        $this->assertEquals(0.00, CustomerCreditService::remainingBalance($c->id));
    }

    public function test_reverse_treatment_discounts_backed_out_base(): void
    {
        // amount 110 tax-inclusive @10% → base 100. Discount 40 → base 60, A/R 66.
        $c = $this->customer(1000);
        $r = $this->fuel($c, 110, 'reverse', 40);
        $this->assertEquals(60.00, (float) $r->amount, 'discounted backed-out base');
        $this->assertEquals(66.00, $this->bal($c));
        $this->assertEquals(960.00, CustomerCreditService::remainingBalance($c->id));
    }

    public function test_over_available_rejected_and_rolled_back(): void
    {
        $c = $this->customer(100);
        try {
            $this->fuel($c, 1000, 'add', 400);
            $this->fail('expected rejection');
        } catch (DiscountException $e) {
            // expected
        }
        $this->assertEquals(0, CustomerAccount::count(), 'no charge created (rollback)');
        $this->assertEquals(100.00, CustomerCreditService::remainingBalance($c->id));
    }

    public function test_over_eligible_rejected_and_rolled_back(): void
    {
        $c = $this->customer(1000);
        $this->expectException(DiscountException::class);
        try {
            $this->fuel($c, 300, 'add', 400);
        } finally {
            $this->assertEquals(0, CustomerAccount::count());
        }
    }

    public function test_idempotent_retry_no_second_charge_or_discount(): void
    {
        $c = $this->customer(1000);
        $r1 = $this->fuel($c, 1000, 'add', 400, 'fuel-key-1');
        $r2 = $this->fuel($c, 1000, 'add', 400, 'fuel-key-1');
        $this->assertEquals($r1->id, $r2->id, 'same charge returned');
        $this->assertEquals(1, ProductDiscount::count());
        $this->assertEquals(1, CustomerAccount::where('reason', 'Fuel Charge')->count());
        $this->assertEquals(600.00, CustomerCreditService::remainingBalance($c->id), 'reduced once');
    }

    public function test_damage_via_service_records_damage_target(): void
    {
        $c = $this->customer(1000);
        ChargeService::createManualCharge(
            customerId: $c->id, type: 'damage', amount: 500, salesTaxType: 'add',
            notes: null, responsibleUserId: $this->admin->id, storeCreditDiscount: 200,
            discountIdempotencyKey: 'dmg-key-1',
        );
        $d = ProductDiscount::where('target_type', DiscountTargetType::DamageCharge->value)->first();
        $this->assertNotNull($d);
        $this->assertEquals(200.00, (float) $d->calculated_discount_amount);
        $this->assertEquals(800.00, CustomerCreditService::remainingBalance($c->id));
    }

    // ── DAMAGE dashboard controller (inline path) via HTTP ───────────────

    public function test_damage_dashboard_controller_partial_discount(): void
    {
        $c = $this->customer(1000);
        $this->postJson(route('admin.dashboard.damage-charge.store'), [
            'customer_id' => $c->id, 'amount' => 1000, 'sales_tax_type' => 'add',
            'responsible_person' => $this->admin->id, 'store_credit_discount' => 400,
        ])->assertOk();

        $r = CustomerAccount::where('customer_id', $c->id)->where('reason', 'Damages')->first();
        $this->assertEquals(600.00, (float) $r->amount);
        $this->assertEquals(660.00, $this->bal($c));
        $this->assertEquals(600.00, CustomerCreditService::remainingBalance($c->id));
        $this->assertEquals(1, ProductDiscount::where('target_type', DiscountTargetType::DamageCharge->value)->count());
    }

    public function test_damage_dashboard_controller_over_available_rejected(): void
    {
        $c = $this->customer(100);
        $this->postJson(route('admin.dashboard.damage-charge.store'), [
            'customer_id' => $c->id, 'amount' => 1000, 'sales_tax_type' => 'add',
            'responsible_person' => $this->admin->id, 'store_credit_discount' => 400,
        ])->assertStatus(422);

        $this->assertEquals(0, CustomerAccount::count(), 'rolled back');
        $this->assertEquals(100.00, CustomerCreditService::remainingBalance($c->id));
    }

    public function test_alert_charge_controller_partial_discount(): void
    {
        $c = $this->customer(1000);
        $order = Order::create([
            'order_date' => now()->format('Y-m-d'), 'customer_id' => $c->id,
            'customer_name' => $c->full_name, 'subtotal' => 100, 'tax_amount' => 0, 'grand_total' => 100,
        ]);
        $this->postJson(route('admin.order-management.orders.alert-charge', ['unique_id' => $order->unique_id]), [
            'type' => 'damage', 'amount' => 1000, 'sales_tax_type' => 'add',
            'responsible_person' => $this->admin->id, 'store_credit_discount' => 400,
        ])->assertOk();

        $r = CustomerAccount::where('order_id', $order->id)->where('reason', 'Damages')->first();
        $this->assertEquals(600.00, (float) $r->amount);
        $this->assertEquals(600.00, CustomerCreditService::remainingBalance($c->id));
    }
}
