<?php

namespace Tests\Feature\Service;

use App\Enums\Service\DiagnosticStatus;
use App\Enums\Service\FinancialResponsibility;
use App\Enums\Service\FinancialStatus;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ResponsibilityDecision;
use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceType;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Service\ServiceComplaintType;
use App\Models\Service\ServiceTicket;
use App\Models\Stores\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Rental-order intake flow: the create page is a fast intake form only
 * (Order → Equipment from that order → Store → Priority → Crew w/ Team
 * Leader → Complaint/Notes). Everything else — diagnosis, responsibility,
 * approval, repair — happens on the workbench after the ticket exists.
 */
class ServiceTicketIntakeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $techA;
    private User $techB;
    private Store $store;
    private Equipment $lift;
    private Equipment $excavator;
    private Equipment $unrelated;
    private int $customerId;
    private int $multiOrderId;
    private int $singleOrderId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'test-admin', 'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]);
        $this->techA = User::create([
            'unique_id' => 'test-tech-a', 'first_name' => 'Alice', 'last_name' => 'Wrench',
            'email' => 'alice@test.local', 'status' => 'Active',
        ]);
        $this->techB = User::create([
            'unique_id' => 'test-tech-b', 'first_name' => 'Bob', 'last_name' => 'Torque',
            'email' => 'bob@test.local', 'status' => 'Active',
        ]);

        $this->store = Store::create(['store_name' => 'Main Shop', 'status' => 'Active']);

        $this->lift      = Equipment::create(['unique_id' => 'test-lift', 'equipment_name' => 'Scissor Lift', 'equipment_id' => 'SL-1', 'brand' => 'Test']);
        $this->excavator = Equipment::create(['unique_id' => 'test-exc', 'equipment_name' => 'Mini Excavator', 'equipment_id' => 'EX-1', 'brand' => 'Test']);
        $this->unrelated = Equipment::create(['unique_id' => 'test-oth', 'equipment_name' => 'Trencher', 'equipment_id' => 'TR-1', 'brand' => 'Test']);

        $this->customerId = DB::table('customers')->insertGetId([
            'unique_id' => 'test-cust', 'first_name' => 'Rental', 'last_name' => 'Customer',
        ]);

        $this->multiOrderId  = $this->makeOrder('#9001', [$this->lift->id, $this->excavator->id]);
        $this->singleOrderId = $this->makeOrder('#9002', [$this->lift->id]);

        $this->actingAs($this->admin);
    }

    private function makeOrder(string $number, array $equipmentIds): int
    {
        $orderId = DB::table('orders')->insertGetId([
            'unique_id' => 'test-ord-' . trim($number, '#'), 'order_number' => $number,
            'order_date' => now()->toDateString(), 'customer_name' => 'Rental Customer',
            'customer_id' => $this->customerId,
        ]);

        foreach ($equipmentIds as $i => $equipmentId) {
            DB::table('order_products')->insert([
                'unique_id' => 'test-op-' . trim($number, '#') . "-$i", 'order_id' => $orderId,
                'product_name' => 'Rental Line ' . ($i + 1), 'price' => 100, 'quantity' => 1, 'total' => 100,
                'equipment_id' => $equipmentId, 'delivery_date' => '2026-07-01',
            ]);
        }

        return $orderId;
    }

    private function intakePayload(array $overrides = []): array
    {
        return array_merge([
            'intake'       => 1,
            'order_id'     => $this->multiOrderId,
            'equipment_id' => $this->lift->id,
            'priority'     => ServicePriority::Normal->value,
        ], $overrides);
    }

    private function store(array $overrides = [])
    {
        return $this->post(route('admin.service-management.tickets.store'), $this->intakePayload($overrides));
    }

    // 1 + 9. Create page is a rental-order intake form — workflow fields gone
    public function test_create_page_defaults_to_rental_order_intake(): void
    {
        $response = $this->get(route('admin.service-management.tickets.create'))
            ->assertOk()
            ->assertSee('New Service Ticket')
            ->assertSee('Rental Order')
            ->assertSee('Assign Now')
            ->assertSee('Team Leader')
            ->assertSee('Complaint')
            ->assertSee('Service Store')
            ->assertDontSee('Technician Diagnosis')
            ->assertDontSee('Root Cause')
            ->assertDontSee('Repair Summary')
            ->assertDontSee('Financial Responsibility')
            ->assertDontSee('Financial Status')
            // The card heading says "Equipment & Service Location" — what must
            // be gone is the old workflow form's service_location dropdown.
            ->assertDontSee('name="service_location"', false);

        $content = $response->getContent();

        // Intake marker defaults the form to the order-related path
        $this->assertStringContainsString('name="intake" value="1"', $content);

        // Notes label replaces Internal Notes on this page
        $this->assertStringContainsString('>Notes<', $content);
        $this->assertStringNotContainsString('>Internal Notes<', $content);
    }

    // Customer-Related intake uses ONE consolidated order selector (order ID
    // OR customer name), not the old two split-search inputs.
    public function test_create_page_uses_one_consolidated_order_selector(): void
    {
        $content = $this->get(route('admin.service-management.tickets.create'))->getContent();

        $this->assertStringContainsString('Customer Order ID / Name', $content);
        $this->assertStringContainsString('Search by order ID or customer name...', $content);
        $this->assertStringContainsString('id="st-order-search"', $content);

        // The old split-search inputs/labels are gone.
        $this->assertStringNotContainsString('Search by Order #', $content);
        $this->assertStringNotContainsString('Search by Customer', $content);
        $this->assertStringNotContainsString('id="st-search-order"', $content);
        $this->assertStringNotContainsString('id="st-search-customer"', $content);
    }

    // 2 + 6 + 10. Intake stores references + safe defaults, then redirects to workbench
    public function test_intake_stores_references_and_defaults_then_redirects_to_workbench(): void
    {
        $this->store([
            'service_store_id'   => $this->store->id,
            'customer_complaint' => 'Platform will not raise',
            'internal_notes'     => 'Reported at return check-in',
        ])->assertRedirect(route('admin.service-management.tickets.show', ServiceTicket::first()));

        $this->assertDatabaseHas('service_tickets', [
            // References only — the Order remains the source of truth
            'order_id'         => $this->multiOrderId,
            'customer_id'      => $this->customerId,
            'rental_date'      => '2026-07-01 00:00:00',
            'equipment_id'     => $this->lift->id,
            'service_store_id' => $this->store->id,
            // Safe intake defaults for the rental-order path
            'service_type'             => ServiceType::CustomerDamageRepair->value,
            'service_location'         => ServiceLocation::InShop->value,
            'repair_status'            => RepairStatus::Open->value,
            'diagnostic_status'        => DiagnosticStatus::NotStarted->value,
            'responsibility_decision'  => ResponsibilityDecision::Pending->value,
            'financial_responsibility' => FinancialResponsibility::Pending->value,
            'financial_status'         => FinancialStatus::NotBillable->value,
            'customer_complaint'       => 'Platform will not raise',
            'internal_notes'           => 'Reported at return check-in',
            'created_by'               => $this->admin->id,
        ]);
    }

    // 3. Equipment outside the selected order is rejected in this path
    public function test_intake_rejects_equipment_not_on_the_order(): void
    {
        $this->store(['equipment_id' => $this->unrelated->id])
            ->assertSessionHasErrors('equipment_id');

        $this->assertDatabaseCount('service_tickets', 0);
    }

    // 3b. Order itself is mandatory on the intake path
    public function test_intake_requires_an_order(): void
    {
        $this->store(['order_id' => null])->assertSessionHasErrors('order_id');
    }

    // 4. Create page ships the order → equipment map that drives the
    //    dependent dropdown (and single-equipment auto-select) client-side
    public function test_create_page_embeds_order_scoped_equipment_choices(): void
    {
        $content = $this->get(route('admin.service-management.tickets.create'))->getContent();

        // Single-equipment order lists exactly its one unit
        $this->assertStringContainsString('"label":"Scissor Lift (SL-1)"', $content);
        // Multi-equipment order carries both of its units
        $this->assertStringContainsString('"label":"Mini Excavator (EX-1)"', $content);
        // Equipment on no order is never offered in the order-scoped map —
        // it may appear only in the Equipment ID Override selector, whose
        // JSON entries carry a product_id key after the label
        $this->assertStringNotContainsString('"label":"Trencher (TR-1)"}', $content);
    }

    public function test_override_selector_ships_product_keys_for_filtering(): void
    {
        $excavators = ProductCategory::create(['unique_id' => 'test-cat-exc-ov', 'title' => 'Excavators', 'status' => 'Published']);
        $tb290      = Product::create(['unique_id' => 'test-prod-tb290-ov', 'product_name' => 'TB290', 'product_type' => 'Rental']);
        $tb290->categories()->attach($excavators->id);

        $this->unrelated->update(['assigned_product_id' => $tb290->id]);

        $content = $this->get(route('admin.service-management.tickets.create'))->getContent();

        // Units carry their product key so the Category/Product search aids
        // can narrow the override list client-side
        $this->assertStringContainsString('"label":"Trencher (TR-1)","product_id":' . $tb290->id, $content);
        // Units without an assigned product ship null — they match only when
        // no filter is active
        $this->assertStringContainsString('"label":"Scissor Lift (SL-1)","product_id":null', $content);
        // The product→category map that resolves category filtering is present
        $this->assertStringContainsString('"name":"TB290","category_ids":[' . $excavators->id . ']', $content);
    }

    // ── Structured complaint intake ──────────────────────────────────

    public function test_create_page_ships_the_grouped_complaint_library(): void
    {
        $content = $this->get(route('admin.service-management.tickets.create'))->getContent();

        // Library seeded by the migration, serialized for client-side filtering
        $this->assertStringContainsString('"name":"Will not start"', $content);
        $this->assertStringContainsString('"name":"Hydraulic leak"', $content);
        $this->assertStringContainsString('"group_label":"Engine \\u0026 Starting"', $content); // @json hex-encodes &
        $this->assertStringContainsString('Other \/ Not Listed', $content);
        // Capability requirements travel with each complaint
        $this->assertStringContainsString('"required_capabilities":["enclosed_cab"]', $content);
        // Order equipment entries carry the keys the filter needs
        $this->assertStringContainsString('"capabilities":null', $content);
        // Structured section scaffold + evidence controls + Notes intact
        $this->assertStringContainsString('id="st-complaint-list"', $content);
        $this->assertStringContainsString('Selected Complaints', $content);
        $this->assertStringContainsString('Complaint Details', $content);
        $this->assertStringContainsString('Complaint Evidence', $content);
        $this->assertStringContainsString('name="evidence[]"', $content);
        $this->assertStringContainsString('>Notes<', $content);
    }

    public function test_intake_creates_one_structured_record_per_selected_complaint(): void
    {
        $leak  = ServiceComplaintType::where('name', 'Hydraulic leak')->firstOrFail();
        $start = ServiceComplaintType::where('name', 'Will not start')->firstOrFail();
        $other = ServiceComplaintType::where('name', 'Other / Not Listed')->firstOrFail();

        $this->store([
            'complaints'         => [$leak->id, $start->id, $other->id],
            'customer_complaint' => 'Leak near the boom cylinder; dies after 20 minutes.',
        ])->assertSessionHasNoErrors();

        $ticket = ServiceTicket::firstOrFail();
        $this->assertSame(3, $ticket->complaints()->count());

        // Individually identifiable records with name + group snapshots
        $this->assertDatabaseHas('service_ticket_complaints', [
            'service_ticket_id'         => $ticket->id,
            'service_complaint_type_id' => $leak->id,
            'name'                      => 'Hydraulic leak',
            'system_group'              => 'hydraulic',
        ]);
        $this->assertDatabaseHas('service_ticket_complaints', [
            'name' => 'Will not start', 'system_group' => 'engine_starting',
        ]);

        // Freeform details still land on the shared column
        $this->assertSame('Leak near the boom cylinder; dies after 20 minutes.', $ticket->customer_complaint);
    }

    public function test_unknown_complaint_ids_are_rejected(): void
    {
        $this->store(['complaints' => [999999]])->assertSessionHasErrors('complaints.0');
        $this->assertDatabaseCount('service_tickets', 0);
    }

    public function test_complaint_evidence_attaches_photos_and_video_to_the_ticket(): void
    {
        Storage::fake('public_asset');

        $this->store([
            'evidence' => [
                UploadedFile::fake()->image('leak-photo.jpg', 800, 600),
                UploadedFile::fake()->create('walkaround.mp4', 2048, 'video/mp4'),
            ],
        ])->assertSessionHasNoErrors();

        $ticket = ServiceTicket::firstOrFail();
        $media  = $ticket->media()->get();

        $this->assertCount(2, $media);
        $this->assertTrue($media->every(fn ($m) => $m->category->value === 'complaint_evidence'));
        $this->assertTrue($media->contains(fn ($m) => $m->original_filename === 'leak-photo.jpg'));
        $this->assertTrue($media->contains(fn ($m) => $m->original_filename === 'walkaround.mp4'));
    }

    public function test_workbench_displays_structured_complaints_as_chips(): void
    {
        $leak = ServiceComplaintType::where('name', 'Hydraulic leak')->firstOrFail();

        $this->store(['complaints' => [$leak->id], 'customer_complaint' => null]);
        session()->forget('flash_notification');

        $this->get(route('admin.service-management.tickets.show', ServiceTicket::firstOrFail()))
            ->assertOk()
            ->assertSee('Hydraulic leak')
            ->assertSee('No additional details recorded.');
    }

    // Assigned-team avatar display: presentation scaffold ships with the
    // page; the chips themselves are built client-side from the checkboxes
    public function test_create_page_renders_crew_avatar_display_scaffold(): void
    {
        $content = $this->get(route('admin.service-management.tickets.create'))->getContent();

        $this->assertStringContainsString('id="st-crew-display"', $content);
        $this->assertStringContainsString('Lead Technician', $content);
        $this->assertStringContainsString('Assigned Team', $content);
        // Hidden until someone is assigned — no empty avatar placeholders
        $this->assertStringContainsString('<div id="st-crew-display" class="hidden', $content);
    }

    // ── Equipment ID Override ────────────────────────────────────────

    public function test_create_page_renders_override_controls(): void
    {
        $this->get(route('admin.service-management.tickets.create'))
            ->assertOk()
            ->assertSee('Equipment ID Override')
            ->assertSee('Override Reason')
            ->assertSee('No override')
            // The override selector offers the whole fleet, order or not
            ->assertSee('Trencher (TR-1)');
    }

    public function test_equipment_override_preserves_both_references_with_full_audit_trail(): void
    {
        $this->store([
            'equipment_override_id'     => $this->unrelated->id, // Trencher — not on the order
            'equipment_override_reason' => 'Yard loaded the wrong unit',
        ])->assertSessionHasNoErrors();

        $ticket = ServiceTicket::firstOrFail();

        // equipment_id = the machine actually being repaired (drives all
        // service operations); the order's original unit is preserved
        $this->assertSame($this->unrelated->id, $ticket->equipment_id);
        $this->assertSame($this->lift->id, $ticket->order_equipment_id);
        $this->assertTrue($ticket->equipment_override);
        $this->assertSame($this->admin->id, $ticket->equipment_override_by);
        $this->assertNotNull($ticket->equipment_override_at);
        $this->assertSame('Yard loaded the wrong unit', $ticket->equipment_override_reason);

        // Rental order untouched
        $this->assertDatabaseHas('order_products', [
            'order_id' => $this->multiOrderId, 'equipment_id' => $this->lift->id,
        ]);

        // Timeline event: original → override, with who and why
        $event = $ticket->events()->where('event_type', 'equipment_override')->firstOrFail();
        $this->assertStringContainsString('Scissor Lift', $event->old_value);
        $this->assertStringContainsString('Trencher', $event->new_value);
        $this->assertSame('Yard loaded the wrong unit', $event->notes);
        $this->assertSame($this->admin->id, $event->user_id);
    }

    public function test_no_override_leaves_override_fields_empty(): void
    {
        $this->store()->assertSessionHasNoErrors();

        $ticket = ServiceTicket::firstOrFail();
        $this->assertSame($this->lift->id, $ticket->equipment_id);
        $this->assertNull($ticket->order_equipment_id);
        $this->assertFalse($ticket->equipment_override);
        $this->assertNull($ticket->equipment_override_reason);
        $this->assertSame(0, $ticket->events()->where('event_type', 'equipment_override')->count());
    }

    public function test_selecting_the_orders_own_unit_as_override_is_a_noop(): void
    {
        $this->store([
            'equipment_override_id'     => $this->lift->id, // same unit as equipment_id
            'equipment_override_reason' => 'accidental self-override',
        ])->assertSessionHasNoErrors();

        $ticket = ServiceTicket::firstOrFail();
        $this->assertFalse($ticket->equipment_override);
        $this->assertNull($ticket->order_equipment_id);
        $this->assertNull($ticket->equipment_override_reason);
    }

    public function test_override_does_not_bypass_order_equipment_validation(): void
    {
        // equipment_id must still come from the order — the override is not
        // a back door around the order-scoped rule
        $this->store([
            'equipment_id'          => $this->unrelated->id,
            'equipment_override_id' => $this->lift->id,
        ])->assertSessionHasErrors('equipment_id');

        $this->assertDatabaseCount('service_tickets', 0);
    }

    public function test_workbench_shows_informational_override_banner(): void
    {
        $this->store([
            'equipment_override_id'     => $this->unrelated->id,
            'equipment_override_reason' => 'Customer exchanged machines',
        ]);
        session()->forget('flash_notification');
        $ticket = ServiceTicket::firstOrFail();

        $this->get(route('admin.service-management.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Equipment Override Applied')
            ->assertSee('Order Equipment:')
            ->assertSee('Scissor Lift (SL-1)')
            ->assertSee('Service Equipment:')
            ->assertSee('Trencher (TR-1)')
            ->assertSee('Customer exchanged machines')
            ->assertSee('tracked') // helper text present
            ->assertSee('Overridden by Admin User');
    }

    public function test_workbench_hides_banner_without_override(): void
    {
        $this->store();
        session()->forget('flash_notification');

        $this->get(route('admin.service-management.tickets.show', ServiceTicket::firstOrFail()))
            ->assertOk()
            ->assertDontSee('Equipment Override Applied');
    }

    // 4b. Category/Product search aids: filters narrow the order list client-
    //     side, are never submitted, and never become ticket data
    public function test_create_page_ships_category_and_product_search_aids(): void
    {
        $excavators = ProductCategory::create(['unique_id' => 'test-cat-exc', 'title' => 'Excavators', 'status' => 'Published']);
        $skidSteers = ProductCategory::create(['unique_id' => 'test-cat-skid', 'title' => 'Skid Steers', 'status' => 'Published']);

        $tb290  = Product::create(['unique_id' => 'test-prod-tb290', 'product_name' => 'TB290', 'product_type' => 'Rental']);
        $svl75  = Product::create(['unique_id' => 'test-prod-svl75', 'product_name' => 'SVL75', 'product_type' => 'Rental']);
        $retail = Product::create(['unique_id' => 'test-prod-teeth', 'product_name' => 'Bucket Teeth', 'product_type' => 'Retail']);

        $tb290->categories()->attach($excavators->id);
        $svl75->categories()->attach($skidSteers->id);

        // One line of the multi-equipment order rents the TB290 excavator
        DB::table('order_products')
            ->where('order_id', $this->multiOrderId)
            ->where('equipment_id', $this->lift->id)
            ->update(['product_id' => $tb290->id]);

        $response = $this->get(route('admin.service-management.tickets.create'))
            ->assertOk()
            ->assertSee('Filter Category')
            ->assertSee('Filter Product')
            ->assertSee('Excavators')
            ->assertSee('Skid Steers')
            ->assertSee('TB290')
            ->assertSee('SVL75')
            ->assertSee('Rental Orders (', false)
            ->assertSee('<span id="st-order-count">2</span>', false)
            // Retail items are not rentable products — never offered as a filter
            ->assertDontSee('Bucket Teeth');

        $content = $response->getContent();

        // Search aids only: no name attributes, so nothing ever posts or saves
        $this->assertStringContainsString('<select id="st-filter-category"', $content);
        $this->assertStringContainsString('<select id="st-filter-product"', $content);
        $this->assertStringNotContainsString('name="st-filter-category"', $content);
        $this->assertStringNotContainsString('name="st-filter-product"', $content);

        // Order options carry the filter keys that drive in-memory matching
        $this->assertStringContainsString('"product_ids":[' . $tb290->id . ']', $content);
        $this->assertStringContainsString('"category_ids":[' . $excavators->id . ']', $content);
        $this->assertStringContainsString('"product_ids":[],"category_ids":[]', $content); // order with no product refs

        // Product list maps each product to its categories so Filter Product
        // can reload when Filter Category changes
        $this->assertStringContainsString('"name":"TB290","category_ids":[' . $excavators->id . ']', $content);
        $this->assertStringContainsString('"name":"SVL75","category_ids":[' . $skidSteers->id . ']', $content);
    }

    // 5. Multi-equipment order requires picking one of its units
    public function test_multi_equipment_order_requires_selection_from_that_order(): void
    {
        $this->store(['equipment_id' => null])->assertSessionHasErrors('equipment_id');

        // Both of the order's own units are accepted
        $this->store(['equipment_id' => $this->excavator->id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('service_tickets', ['equipment_id' => $this->excavator->id]);
    }

    // 7 + 8. Crew from existing HRM users, with one marked Team Leader
    public function test_personnel_assignment_with_team_leader(): void
    {
        $this->store([
            'personnel'      => [$this->techA->id, $this->techB->id],
            'team_leader_id' => $this->techA->id,
        ])->assertSessionHasNoErrors();

        $ticket = ServiceTicket::firstOrFail();

        $this->assertDatabaseHas('service_ticket_personnel', [
            'service_ticket_id' => $ticket->id, 'employee_id' => $this->techA->id, 'is_team_leader' => 1,
        ]);
        $this->assertDatabaseHas('service_ticket_personnel', [
            'service_ticket_id' => $ticket->id, 'employee_id' => $this->techB->id, 'is_team_leader' => 0,
        ]);
        $this->assertSame($this->techA->id, $ticket->teamLeader()?->id);
    }

    public function test_team_leader_must_be_one_of_the_assigned_personnel(): void
    {
        $this->store([
            'personnel'      => [$this->techB->id],
            'team_leader_id' => $this->techA->id,
        ])->assertSessionHasErrors('team_leader_id');
    }

    // Editing personnel without a leader field keeps the existing leader
    public function test_edit_resync_preserves_existing_team_leader(): void
    {
        $this->store([
            'personnel'      => [$this->techA->id, $this->techB->id],
            'team_leader_id' => $this->techA->id,
        ]);

        $ticket = ServiceTicket::firstOrFail();
        $ticket->syncPersonnel([$this->techA->id, $this->techB->id]); // no leader passed

        $this->assertSame($this->techA->id, $ticket->fresh()->teamLeader()?->id);
    }
}
