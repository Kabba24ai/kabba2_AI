<?php

namespace Tests\Feature\BillingEngine;

use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestionAnswer;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Models\Stores\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PR-A3: proves the billing idempotency fix for CORRECTION_PHASE1_PLAN.md Issue #1 —
 * a second legitimate rental cycle's damage/fuel charge was previously silently
 * dropped because both idempotency mechanisms keyed only on order_product_id
 * (the OrderProduct row is reused across delivery/return cycles rather than
 * recreated per cycle). Confirmed reproduced in PHASE3_RESULTS.md.
 *
 * Fix: derive a cycleKey from the order product's currently-active (non-deleted)
 * order_product_checklist_questions rows — recreated fresh on every save-delivery
 * call — and fold it into the BillingCharge idempotency key (damage + fuel) and
 * the ChargeService legacy CustomerAccount duplicate guard (via a created_at cutoff,
 * since customer_accounts has no idempotency_key column).
 */
class MobileReturnCycleIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;
    private Order $order;
    private OrderProduct $orderProduct;
    private Equipment $equipment;
    private Store $store;
    private CustomerAdminQuestion $question;
    private CustomerAdminQuestionAnswer $damagedAnswer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::create([
            'first_name' => 'Cycle',
            'last_name'  => 'Actor',
            'email'      => 'cycle-actor@example.com',
            'password'   => bcrypt('password'),
        ]);

        $this->store = Store::create(['store_name' => 'Test Store']);

        $customer = Customer::create([
            'first_name' => 'Cycle',
            'last_name'  => 'Customer',
            'email'      => 'cycle-customer@example.com',
        ]);

        $this->order = Order::create([
            'order_date'    => now()->format('Y-m-d'),
            'customer_name' => 'Cycle Customer',
            'customer_id'   => $customer->id,
        ]);

        $product = Product::create([
            'product_name' => 'Test Rental Product',
            'slug'         => 'test-rental-product-' . uniqid(),
            'product_type' => 'Rental',
        ]);

        $this->equipment = Equipment::create([
            'equipment_name' => 'Test Excavator',
            'equipment_id'   => 'EQP-TEST-' . uniqid(),
            'brand'          => 'TestBrand',
            'current_status' => 'rented',
        ]);

        $this->orderProduct = OrderProduct::create([
            'order_id'     => $this->order->id,
            'product_id'   => $product->id,
            'product_name' => 'Test Rental Product',
            'price'        => 100,
            'quantity'     => 1,
            'total'        => 100,
            'equipment_id' => $this->equipment->id,
        ]);

        $this->equipment->current_order_id = $this->order->id;
        $this->equipment->current_order_product_id = $this->orderProduct->id;
        $this->equipment->saveQuietly();

        $category = CustomerAdminCategory::create(['category_name' => 'Test Category']);
        $this->question = CustomerAdminQuestion::create([
            'question_name'          => 'Was it damaged?',
            'category_id'            => $category->id,
            'question_delivery_text' => 'Any damage at delivery?',
            'question_return_text'   => 'Any damage at return?',
        ]);
        $this->damagedAnswer = CustomerAdminQuestionAnswer::create([
            'answer_delivery_text' => 'Yes',
            'answer_return_text'   => 'Yes',
            'question_id'          => $this->question->id,
            'is_damaged'           => true,
        ]);
    }

    private function apiUrl(string $path): string
    {
        return 'http://' . config('app.domains.api') . '/api/admin/v1/orders/' . ltrim($path, '/');
    }

    private function callAs(string $method, string $path, array $payload = []): \Illuminate\Testing\TestResponse
    {
        return $this->withoutMiddleware()
            ->actingAs($this->actor, 'api_user')
            ->json($method, $this->apiUrl($path), $payload);
    }

    /**
     * Simulates what SaveDeliveryController does on every delivery: delete any
     * existing checklist rows and create a fresh batch — this is what changes
     * the cycleKey (min id) between rental cycles.
     */
    private function startNewCycle(): array
    {
        $this->orderProduct->checklistQuestions()->delete();

        $checklistQuestion = $this->orderProduct->checklistQuestions()->create([
            'order_id'      => $this->order->id,
            'question_id'   => $this->question->id,
            'question_name' => $this->question->question_name,
            'index_number'  => 1,
        ]);
        $checklistAnswer = $checklistQuestion->answers()->create([
            'order_id'     => $this->order->id,
            'question_id'  => $this->question->id,
            'answer_id'    => $this->damagedAnswer->id,
            'index_number' => 1,
        ]);

        // Refresh first — a prior return in this test may have changed this equipment's
        // status via a separate model instance inside the controller; without refreshing,
        // Eloquent's dirty-tracking sees the reassignment below as a no-op (same in-memory
        // enum value) and saveQuietly() silently skips the UPDATE.
        $this->equipment->refresh();
        $this->equipment->current_status = 'rented';
        $this->equipment->current_order_id = $this->order->id;
        $this->equipment->current_order_product_id = $this->orderProduct->id;
        $this->equipment->saveQuietly();

        // A real delivery would set delivery_by/status too; not needed for these
        // assertions since SaveReturnController only gates on equipment status.
        $this->orderProduct->refresh();
        $this->orderProduct->update([
            'pickup_signature_media_id' => null,
            'is_returned'               => false,
        ]);

        return [$checklistQuestion, $checklistAnswer];
    }

    private function submitReturn(string $questionUniqueId, string $answerUniqueId, string $fuelFinalReading, string $damageAmount): \Illuminate\Testing\TestResponse
    {
        return $this->callAs('POST', 'customer-checklists/save-return', [
            'order_product_unique_id' => $this->orderProduct->unique_id,
            'store_id'                => (string) $this->store->id,
            'user_id'                 => (string) $this->actor->id,
            'fuel_final_reading'      => $fuelFinalReading,
            'fuel_total_charge'       => '25.00',
            'checklist' => [
                [
                    'question_unique_id' => $questionUniqueId,
                    'answer_unique_id'   => $answerUniqueId,
                    'amount'             => $damageAmount,
                ],
            ],
        ]);
    }

    // ── 1. Single cycle creates one damage and one fuel charge ─────────────

    public function test_single_cycle_creates_one_damage_and_one_fuel_charge(): void
    {
        [$checklistQuestion, $checklistAnswer] = $this->startNewCycle();

        $this->submitReturn($checklistQuestion->unique_id, $checklistAnswer->unique_id, '60', '150.00')
            ->assertOk()->assertJson(['success' => true]);

        $this->assertEquals(1, BillingCharge::where('billing_charge_type', 'damage')->count());
        $this->assertEquals(1, BillingCharge::where('billing_charge_type', 'fuel')->count());
        $this->assertEquals(1, CustomerAccount::where('reason', 'Fuel Charge')->count());
    }

    // ── 2. Second legitimate rental cycle creates a second charge of each type ──

    public function test_second_rental_cycle_creates_a_second_damage_and_fuel_charge(): void
    {
        // Cycle 1: deliver, return with damage + fuel.
        [$q1, $a1] = $this->startNewCycle();
        $this->submitReturn($q1->unique_id, $a1->unique_id, '60', '150.00')->assertOk();

        $this->assertEquals(1, BillingCharge::where('billing_charge_type', 'damage')->count());
        $this->assertEquals(1, BillingCharge::where('billing_charge_type', 'fuel')->count());
        $this->assertEquals(1, CustomerAccount::where('reason', 'Fuel Charge')->count());

        // Real rental cycles are hours/days apart, not microseconds — advance the clock
        // so the ChargeService created_at cutoff isn't comparing two events that
        // round to the same second (created_at columns are second-precision).
        $this->travel(1)->day();

        // Cycle 2: re-delivered (fresh checklist batch => new cycleKey), returned again
        // with a NEW damaged answer and a NEW fuel reading — a genuinely separate,
        // legitimate re-rental, not a retry of cycle 1's submission.
        [$q2, $a2] = $this->startNewCycle();
        $this->submitReturn($q2->unique_id, $a2->unique_id, '70', '200.00')->assertOk();

        // Before the fix, both of these stayed at 1 — the second cycle's charges were
        // silently dropped because the idempotency key collided with cycle 1's.
        $this->assertEquals(2, BillingCharge::where('billing_charge_type', 'damage')->count());
        $this->assertEquals(2, BillingCharge::where('billing_charge_type', 'fuel')->count());
        $this->assertEquals(2, CustomerAccount::where('reason', 'Fuel Charge')->count());

        // Confirm the second cycle's actual amounts were recorded, not just a count bump.
        $this->assertDatabaseHas('billing_charges', [
            'billing_charge_type' => 'damage',
            'amount'              => 200.00,
        ]);
        $this->assertDatabaseHas('billing_charges', [
            'billing_charge_type' => 'fuel',
            'amount'              => 25.00, // fuel_total_charge is fixed at 25.00 in both submissions
        ]);
    }

    // ── 3. Duplicate retry within the same cycle still does not double-charge ──

    /**
     * A real end-to-end HTTP retry of a completed return cannot be exercised here:
     * SaveReturnController's own (pre-existing, out-of-scope-for-PR-A3) equipment-status
     * guard already rejects any second call once the first one has transitioned the
     * equipment away from 'rented' — a genuine retry would hit that 403 before ever
     * reaching the billing logic, in both the old and new code. What PR-A3 actually
     * changed is the idempotency mechanism itself (the key construction in
     * BillingChargeRequest::mobileReturnDamage() and the created_at cutoff in
     * ChargeService::createFromOrderProduct()), so that mechanism is exercised
     * directly here — the correct altitude for this specific regression guard.
     */
    public function test_duplicate_retry_within_same_cycle_does_not_double_charge(): void
    {
        [$checklistQuestion, $checklistAnswer] = $this->startNewCycle();
        $cycleKey       = (string) $checklistQuestion->id;
        $cycleStartedAt = $checklistQuestion->created_at;

        // Same BillingChargeRequest (same cycleKey) submitted twice, as a mobile offline
        // sync replay would — BillingEngine::charge()'s own idempotency-key lookup must
        // return the existing charge rather than creating a second one.
        $first = \App\Services\BillingEngine::charge(\App\Http\DataObjects\BillingChargeRequest::mobileReturnDamage(
            orderId: $this->order->id,
            customerId: (int) $this->order->customer_id,
            orderProductId: $this->orderProduct->id,
            amount: 150.00,
            submittedByUserId: $this->actor->id,
            checklistQuestionIds: [$checklistQuestion->id],
            cycleKey: $cycleKey,
        ));
        $second = \App\Services\BillingEngine::charge(\App\Http\DataObjects\BillingChargeRequest::mobileReturnDamage(
            orderId: $this->order->id,
            customerId: (int) $this->order->customer_id,
            orderProductId: $this->orderProduct->id,
            amount: 150.00,
            submittedByUserId: $this->actor->id,
            checklistQuestionIds: [$checklistQuestion->id],
            cycleKey: $cycleKey,
        ));

        $this->assertEquals(1, BillingCharge::where('billing_charge_type', 'damage')->count());
        $this->assertEquals($first->id, $second->id);

        // Same cycleStartedAt passed twice — ChargeService's duplicate guard must find
        // the first call's CustomerAccount row and decline to create a second one.
        $this->orderProduct->update(['fuel_total_charge' => 25.00]);
        $firstCa  = \App\Services\ChargeService::createFromOrderProduct($this->orderProduct, 'fuel', $this->actor->id, $cycleStartedAt);
        $secondCa = \App\Services\ChargeService::createFromOrderProduct($this->orderProduct, 'fuel', $this->actor->id, $cycleStartedAt);

        $this->assertNotNull($firstCa);
        $this->assertNull($secondCa); // duplicate correctly declined, not a second row
        $this->assertEquals(1, CustomerAccount::where('reason', 'Fuel Charge')->count());
    }
}
