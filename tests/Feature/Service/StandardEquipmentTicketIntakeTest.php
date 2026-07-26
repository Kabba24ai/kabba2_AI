<?php

namespace Tests\Feature\Service;

use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceType;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Service\ServiceComplaintType;
use App\Models\Service\ServiceTicket;
use App\Models\Stores\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Standard Equipment Ticket intake — a simplified second intake path for a
 * fleet unit on our lot with no customer/order. It reuses the SAME shared
 * intake (controller → SaveTicketRequest → ServiceTicketIntakeService →
 * workbench); only the source of the equipment differs (category → server-
 * searched unit instead of an order). Classified as Internal Repair (the
 * canonical existing type) and hard-isolated from any order/customer/override.
 */
class StandardEquipmentTicketIntakeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Store $store;
    private ProductCategory $skidSteers;
    private ProductCategory $excavators;
    private Equipment $skid;        // in $skidSteers
    private Equipment $skidTwo;     // in $skidSteers
    private Equipment $excavator;   // in $excavators
    private int $customerId;
    private int $orderId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'std-admin', 'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'std-admin@test.local', 'status' => 'Active',
        ]);

        $this->store = Store::create(['store_name' => 'Main Shop', 'status' => 'Active']);

        $this->skidSteers = ProductCategory::create(['unique_id' => 'std-cat-skid', 'title' => 'Skid Steers', 'status' => 'Published']);
        $this->excavators = ProductCategory::create(['unique_id' => 'std-cat-exc', 'title' => 'Mini Excavators', 'status' => 'Published']);

        $this->skid = Equipment::create([
            'unique_id' => 'std-skid-1', 'equipment_name' => 'S185 Skid Steer', 'equipment_id' => 'EQ-0871',
            'brand' => 'Bobcat', 'product_category_id' => $this->skidSteers->id, 'current_status' => 'available',
        ]);
        $this->skidTwo = Equipment::create([
            'unique_id' => 'std-skid-2', 'equipment_name' => 'SVL75 Track Loader', 'equipment_id' => 'EQ-1043',
            'brand' => 'Kubota', 'product_category_id' => $this->skidSteers->id, 'current_status' => 'available',
        ]);
        $this->excavator = Equipment::create([
            'unique_id' => 'std-exc-1', 'equipment_name' => 'SJIII 3219', 'equipment_id' => 'EQ-0562',
            'brand' => 'Skyjack', 'product_category_id' => $this->excavators->id, 'current_status' => 'available',
        ]);

        // A customer-path fixture so the regression test can prove the existing
        // intake is untouched.
        $this->customerId = DB::table('customers')->insertGetId([
            'unique_id' => 'std-cust', 'first_name' => 'Rental', 'last_name' => 'Customer',
        ]);
        $this->orderId = DB::table('orders')->insertGetId([
            'unique_id' => 'std-ord', 'order_number' => '#7001', 'order_date' => now()->toDateString(),
            'customer_name' => 'Rental Customer', 'customer_id' => $this->customerId,
        ]);
        DB::table('order_products')->insert([
            'unique_id' => 'std-op', 'order_id' => $this->orderId, 'product_name' => 'Rental Line',
            'price' => 100, 'quantity' => 1, 'total' => 100, 'equipment_id' => $this->skid->id, 'delivery_date' => '2026-07-01',
        ]);

        $this->actingAs($this->admin);
    }

    private function standardPayload(array $overrides = []): array
    {
        return array_merge([
            'ticket_source'         => 'standard',
            'equipment_category_id' => $this->skidSteers->id,
            'equipment_id'          => $this->skid->id,
            'service_store_id'      => $this->store->id,
            'priority'              => ServicePriority::Normal->value,
        ], $overrides);
    }

    private function storeStandard(array $overrides = [])
    {
        return $this->post(route('admin.service-management.tickets.store'), $this->standardPayload($overrides));
    }

    // ── Step 1: both source options are offered ──────────────────────────

    public function test_create_page_offers_both_ticket_source_options(): void
    {
        $content = $this->get(route('admin.service-management.tickets.create'))->assertOk()->getContent();

        $this->assertStringContainsString('Ticket Source', $content);
        $this->assertStringContainsString('Customer-Related Ticket', $content);
        $this->assertStringContainsString('Standard Equipment Ticket', $content);
        $this->assertStringContainsString('value="customer"', $content);
        $this->assertStringContainsString('value="standard"', $content);
    }

    // Standard path renders its own equipment scaffold, and the shared toggling
    // wrappers exist for both paths (visibility itself is JS-driven).
    public function test_create_page_renders_standard_equipment_scaffold(): void
    {
        $content = $this->get(route('admin.service-management.tickets.create'))->assertOk()->getContent();

        $this->assertStringContainsString('Equipment Category', $content);
        $this->assertStringContainsString('id="st-category"', $content);
        $this->assertStringContainsString('name="equipment_category_id"', $content);
        $this->assertStringContainsString('id="st-std-search"', $content);
        $this->assertStringContainsString('id="st-std-equipment"', $content);
        // Categories that own fleet units are offered
        $this->assertStringContainsString('Skid Steers', $content);
        $this->assertStringContainsString('Mini Excavators', $content);
        // Both path wrappers are present so the client can toggle between them
        $this->assertStringContainsString('data-src="customer"', $content);
        $this->assertStringContainsString('data-src="standard"', $content);
    }

    // ── Equipment search endpoint (canonical, category-scoped) ────────────

    public function test_equipment_search_is_scoped_to_the_category(): void
    {
        $data = $this->getJson(route('admin.service-management.tickets.equipment-search', [
            'category_id' => $this->skidSteers->id,
        ]))->assertOk()->json('data');

        $ids = collect($data)->pluck('id')->all();
        $this->assertContains($this->skid->id, $ids);
        $this->assertContains($this->skidTwo->id, $ids);
        // A unit in another category never appears
        $this->assertNotContains($this->excavator->id, $ids);
    }

    public function test_equipment_search_matches_name_and_equipment_id_only(): void
    {
        // Name match
        $byName = $this->getJson(route('admin.service-management.tickets.equipment-search', [
            'category_id' => $this->skidSteers->id, 'search' => 'SVL75',
        ]))->assertOk()->json('data');
        $this->assertSame([$this->skidTwo->id], collect($byName)->pluck('id')->all());

        // Equipment ID match
        $byId = $this->getJson(route('admin.service-management.tickets.equipment-search', [
            'category_id' => $this->skidSteers->id, 'search' => 'EQ-0871',
        ]))->assertOk()->json('data');
        $this->assertSame([$this->skid->id], collect($byId)->pluck('id')->all());

        // Brand is deliberately NOT searchable (would be noise)
        $byBrand = $this->getJson(route('admin.service-management.tickets.equipment-search', [
            'category_id' => $this->skidSteers->id, 'search' => 'Kubota',
        ]))->assertOk()->json('data');
        $this->assertSame([], collect($byBrand)->pluck('id')->all());
    }

    public function test_equipment_search_requires_a_category(): void
    {
        $this->getJson(route('admin.service-management.tickets.equipment-search', ['search' => 'EQ']))
            ->assertOk()
            ->assertJson(['data' => []]);
    }

    public function test_equipment_search_options_carry_name_and_display_id(): void
    {
        $unit = collect($this->getJson(route('admin.service-management.tickets.equipment-search', [
            'category_id' => $this->skidSteers->id, 'search' => 'S185',
        ]))->json('data'))->firstWhere('id', $this->skid->id);

        $this->assertSame('S185 Skid Steer', $unit['name']);
        $this->assertSame('EQ-0871', $unit['display_id']);
        $this->assertSame('S185 Skid Steer (EQ-0871)', $unit['label']);
    }

    // ── Storing a standard ticket ─────────────────────────────────────────

    public function test_standard_ticket_stores_internal_repair_with_no_customer_or_order(): void
    {
        $this->storeStandard([
            'customer_complaint' => 'Auxiliary hydraulics slow',
        ])->assertSessionHasNoErrors()
          ->assertRedirect(route('admin.service-management.tickets.show', ServiceTicket::firstOrFail()));

        $ticket = ServiceTicket::firstOrFail();
        $this->assertSame(ServiceType::InternalRepair, $ticket->service_type);
        $this->assertSame($this->skid->id, $ticket->equipment_id);
        $this->assertSame($this->store->id, $ticket->service_store_id);
        $this->assertNull($ticket->order_id);
        $this->assertNull($ticket->customer_id);
        $this->assertNull($ticket->order_equipment_id);
        $this->assertNull($ticket->rental_date);
        $this->assertFalse((bool) $ticket->equipment_override);
        $this->assertNull($ticket->equipment_override_reason);
        $this->assertSame('Auxiliary hydraulics slow', $ticket->customer_complaint);
    }

    public function test_standard_ticket_requires_a_category(): void
    {
        $this->storeStandard(['equipment_category_id' => null])->assertSessionHasErrors('equipment_category_id');
        $this->assertDatabaseCount('service_tickets', 0);
    }

    public function test_standard_ticket_requires_a_service_store(): void
    {
        $this->storeStandard(['service_store_id' => null])->assertSessionHasErrors('service_store_id');
        $this->assertDatabaseCount('service_tickets', 0);
    }

    public function test_standard_ticket_rejects_equipment_outside_the_category(): void
    {
        // Excavator unit submitted under the Skid Steers category
        $this->storeStandard(['equipment_id' => $this->excavator->id])
            ->assertSessionHasErrors('equipment_id');
        $this->assertDatabaseCount('service_tickets', 0);
    }

    // Manipulated payloads: a standard ticket can never smuggle order/override
    // data past the server.
    public function test_standard_ticket_rejects_a_smuggled_order(): void
    {
        $this->storeStandard(['order_id' => $this->orderId])->assertSessionHasErrors('order_id');
        $this->assertDatabaseCount('service_tickets', 0);
    }

    public function test_standard_ticket_rejects_a_smuggled_equipment_override(): void
    {
        $this->storeStandard(['equipment_override_id' => $this->excavator->id])
            ->assertSessionHasErrors('equipment_override_id');
        $this->assertDatabaseCount('service_tickets', 0);
    }

    public function test_standard_ticket_snapshots_selected_complaints(): void
    {
        $leak = ServiceComplaintType::where('name', 'Hydraulic leak')->firstOrFail();

        $this->storeStandard(['complaints' => [$leak->id]])->assertSessionHasNoErrors();

        $ticket = ServiceTicket::firstOrFail();
        $this->assertSame(1, $ticket->complaints()->count());
        $this->assertDatabaseHas('service_ticket_complaints', [
            'service_ticket_id' => $ticket->id, 'name' => 'Hydraulic leak',
        ]);
    }

    // ── Customer path is untouched ────────────────────────────────────────

    public function test_customer_related_intake_still_works(): void
    {
        $this->post(route('admin.service-management.tickets.store'), [
            'ticket_source'    => 'customer',
            'intake'           => 1,
            'order_id'         => $this->orderId,
            'equipment_id'     => $this->skid->id,
            'service_store_id' => $this->store->id,
            'priority'         => ServicePriority::Normal->value,
        ])->assertSessionHasNoErrors();

        $ticket = ServiceTicket::firstOrFail();
        // Existing default for the customer path is preserved (not InternalRepair)
        $this->assertSame(ServiceType::CustomerDamageRepair, $ticket->service_type);
        $this->assertSame($this->orderId, $ticket->order_id);
        $this->assertSame($this->customerId, $ticket->customer_id);
    }
}
