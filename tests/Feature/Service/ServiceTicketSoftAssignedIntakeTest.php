<?php

namespace Tests\Feature\Service;

use App\Enums\Service\ServicePriority;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Service\ServiceTicket;
use App\Models\Stores\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Customer-Related intake for an order whose serviceable unit is only
 * SOFT-assigned through the Queue Line (no hard order_products.equipment_id).
 *
 * The regression this pins: the intake picker offers such a unit (resolved by
 * ServiceIntakeOrderPresenter from hard-FK OR soft assignment), the single-unit
 * order locks the select and posts equipment_id via its hidden mirror — but the
 * server-side membership check used to look ONLY at the hard FK, so it wrongly
 * rejected the submission with "Select equipment from the chosen rental order."
 * Validation must accept exactly the units the form offered.
 */
class ServiceTicketSoftAssignedIntakeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Store $store;
    private Equipment $softUnit;
    private Equipment $otherSoftUnit;
    private int $customerId;
    private int $softOrderId;
    private int $otherOrderId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'sai-admin', 'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'sai-admin@test.local', 'status' => 'Active',
        ]);

        $this->store = Store::create(['store_name' => 'Soft Shop', 'status' => 'Active']);

        $this->softUnit = Equipment::create([
            'unique_id' => 'sai-soft', 'equipment_name' => 'CAT Backhoe', 'equipment_id' => 'CAT-1',
            'brand' => 'CAT', 'current_status' => 'available',
        ]);
        $this->otherSoftUnit = Equipment::create([
            'unique_id' => 'sai-other', 'equipment_name' => 'Deere Backhoe', 'equipment_id' => 'JD-1',
            'brand' => 'Deere', 'current_status' => 'available',
        ]);

        $this->customerId = DB::table('customers')->insertGetId([
            'unique_id' => 'sai-cust', 'first_name' => 'Soft', 'last_name' => 'Customer',
        ]);

        // Order #3151-style: unit only soft-assigned (hard equipment_id null).
        $this->softOrderId  = $this->makeSoftOrder('#3151', $this->softUnit->id);
        $this->otherOrderId = $this->makeSoftOrder('#3152', $this->otherSoftUnit->id);

        $this->actingAs($this->admin);
    }

    /** An order whose only line has no hard equipment FK — the unit is soft-assigned. */
    private function makeSoftOrder(string $number, int $equipmentId): int
    {
        $tag = trim($number, '#');

        $orderId = DB::table('orders')->insertGetId([
            'unique_id' => 'sai-ord-' . $tag, 'order_number' => $number,
            'order_date' => now()->toDateString(), 'customer_name' => 'Soft Customer',
            'customer_id' => $this->customerId,
        ]);

        $opId = DB::table('order_products')->insertGetId([
            'unique_id' => 'sai-op-' . $tag, 'order_id' => $orderId,
            'product_name' => 'Rental Line', 'price' => 100, 'quantity' => 1, 'total' => 100,
            'equipment_id' => null, 'delivery_date' => '2026-07-01',
        ]);

        DB::table('equipment_soft_assigns')->insert([
            'equipment_id' => $equipmentId, 'order_id' => $orderId,
            'order_product_id' => $opId, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $orderId;
    }

    private function store(array $overrides = [])
    {
        return $this->post(route('admin.service-management.tickets.store'), array_merge([
            'intake'           => 1,
            'order_id'         => $this->softOrderId,
            'equipment_id'     => $this->softUnit->id,
            'priority'         => ServicePriority::Normal->value,
            'service_store_id' => $this->store->id,
        ], $overrides));
    }

    // The core regression: a single soft-assigned unit submits successfully.
    // (This also exercises requirement 8 — the value the locked/mirror control
    // carries is honored; there is no false "not on the order" error.)
    public function test_soft_assigned_order_equipment_submits_successfully(): void
    {
        $this->store()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('service_tickets', [
            'order_id'     => $this->softOrderId,
            'customer_id'  => $this->customerId,
            'equipment_id' => $this->softUnit->id,
        ]);
    }

    // Requirement 3: the selected unit must belong to the chosen order. A unit
    // that is soft-assigned to a DIFFERENT order is still rejected.
    public function test_soft_assigned_equipment_from_another_order_is_rejected(): void
    {
        $this->store(['equipment_id' => $this->otherSoftUnit->id])
            ->assertSessionHasErrors('equipment_id');

        $this->assertDatabaseCount('service_tickets', 0);
    }

    // Requirement 5: the general Standard-path fields are never required on the
    // Customer path — omitting equipment_category_id must not raise an error.
    public function test_customer_path_does_not_require_equipment_category(): void
    {
        $this->store()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('service_tickets', 1);
    }

    // Requirement 7: after an unrelated validation error the re-render stays on
    // the Customer layout — the Standard block (category + general equipment)
    // is rendered hidden, so there is never a second, conflicting equipment
    // field, and the chosen order is rehydrated (hard OR soft) for restoration.
    public function test_validation_failure_keeps_customer_layout_without_duplicate_fields(): void
    {
        // Missing priority → validation fails, input is flashed for the redirect.
        $this->from(route('admin.service-management.tickets.create'))
            ->store(['priority' => null])
            ->assertRedirect(route('admin.service-management.tickets.create'))
            ->assertSessionHasErrors('priority');

        $html = $this->get(route('admin.service-management.tickets.create'))
            ->assertOk()
            ->getContent();

        // The Standard equipment block carries the `hidden` class...
        $this->assertMatchesRegularExpression(
            '/<div class="[^"]*\bhidden\b[^"]*" data-src="standard">/',
            $html,
            'Standard equipment block must be hidden on the Customer path re-render.'
        );

        // ...while the Customer equipment block does not.
        preg_match('/<div class="([^"]*)" data-src="customer">/', $html, $m);
        $this->assertNotEmpty($m, 'Customer equipment block must be present.');
        $this->assertStringNotContainsString('hidden', $m[1], 'Customer block must stay visible.');

        // The soft-assigned order rehydrates so its equipment restores without a
        // preload (the picker JSON carries the unit resolved via soft assignment).
        $this->assertStringContainsString('"label":"CAT Backhoe (CAT-1)"', $html);
    }
}
