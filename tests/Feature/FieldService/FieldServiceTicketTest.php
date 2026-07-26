<?php

namespace Tests\Feature\FieldService;

use App\Enums\FieldService\FieldMissionStatus;
use App\Enums\FieldService\FieldOperationalOutcome;
use App\Enums\FieldService\FieldTicketEventType;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ServiceType;
use App\Livewire\ServiceManagement\OperationsBoard;
use App\Models\Dispatch\DispatchAiTruck;
use App\Models\FieldService\FieldServiceTicket;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Field Service — mission-based dispatch workflow, separate from Shop
 * Service. Covers ticket creation (incident, media, dispatch assessment,
 * assignment, expectation), the Field Operations Workbench, and the
 * initial dispatch status flow through Assessment Complete.
 */
class FieldServiceTicketTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $tech;
    private DispatchAiTruck $truck;
    private Equipment $lift;
    private int $customerId;
    private int $orderId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'test-admin', 'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]);
        $this->tech = User::create([
            'unique_id' => 'test-tech', 'first_name' => 'Terry', 'last_name' => 'Fieldman',
            'email' => 'terry@test.local', 'status' => 'Active',
        ]);

        $this->truck = DispatchAiTruck::create(['truck_name' => 'Service 1', 'truck_number' => '7', 'is_active' => true]);

        $this->lift = Equipment::create([
            'unique_id' => 'test-lift', 'equipment_name' => 'Boom Lift', 'equipment_id' => 'BL-1',
            'brand' => 'Genie', 'serial_number' => 'SN-778899',
        ]);

        $this->customerId = DB::table('customers')->insertGetId([
            'unique_id' => 'test-cust', 'first_name' => 'Field', 'last_name' => 'Customer',
        ]);

        $this->orderId = DB::table('orders')->insertGetId([
            'unique_id' => 'test-ord', 'order_number' => '#7001', 'order_date' => now()->toDateString(),
            'customer_name' => 'Field Customer', 'customer_id' => $this->customerId,
        ]);
        DB::table('order_products')->insert([
            'unique_id' => 'test-op-1', 'order_id' => $this->orderId, 'product_name' => 'Boom Lift Rental',
            'price' => 500, 'quantity' => 1, 'total' => 500, 'equipment_id' => $this->lift->id,
        ]);
        // Delivery (Shipping) address on the order — the source for the
        // "Delivery Address" and "Order Contact" derivations.
        DB::table('order_addresses')->insert([
            'order_id' => $this->orderId, 'type' => 'Shipping',
            'first_name' => 'Dana', 'last_name' => 'Foreman', 'phone' => '615-555-0199',
            'address' => '12 Depot Rd', 'city' => 'Dickson', 'state' => 'TN', 'zip_code' => '37055',
        ]);

        $this->actingAs($this->admin);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'order_id'                => $this->orderId,
            'equipment_id'            => $this->lift->id,
            'serial_number'           => 'SN-778899',
            'contact_source'          => 'other',
            'contact_name'            => 'Site Foreman',
            'contact_phone'           => '615-555-0100',
            'location_source'         => 'other',
            'loc_street'              => '4400 Ridge Rd',
            'loc_city'                => 'Bon Aqua',
            'loc_state'               => 'TN',
            'loc_zip'                 => '37025',
            'additional_details'      => 'Boom lift will not start on jobsite.',
            'photos_received'         => 1,
            'media_reviewed'          => 1,
            'priority'                => 'high',
            'safety_concern'          => 'none',
            'machine_status'          => 'inoperable',
            'machine_stuck'           => 'no',
            'recovery_risk'           => 'unknown',
            'site_access'             => 'normal',
            'site_notes'              => 'Gate code 4411.',
            'operational_expectation' => 'unknown',
        ], $overrides);
    }

    private function makeTicket(array $overrides = []): FieldServiceTicket
    {
        $this->post(route('admin.field-service.tickets.store'), $this->payload($overrides));

        // The "ticket created" flash carries the ticket number — clear it so
        // page assertions only match real page content.
        session()->forget('flash_notification');

        return FieldServiceTicket::latest('id')->firstOrFail();
    }

    /** Walk a ticket forward through the mission flow up to $target. */
    private function advanceTo(FieldServiceTicket $ticket, FieldMissionStatus $target): FieldServiceTicket
    {
        $ticket->fill(['technician_id' => $this->tech->id])->save();

        foreach (FieldMissionStatus::missionFlow() as $status) {
            if ($status === FieldMissionStatus::Draft) {
                continue;
            }
            if ($status === FieldMissionStatus::AssessmentComplete) {
                $ticket->assessment_summary = 'Assessed on site.';
                $ticket->save();
            }
            $this->assertTrue($ticket->transitionTo($status), 'Could not advance to ' . $status->value);
            if ($status === $target) {
                break;
            }
        }

        return $ticket->fresh();
    }

    // ── Creation ─────────────────────────────────────────────────────

    public function test_create_page_renders_all_five_intake_sections(): void
    {
        $this->get(route('admin.field-service.tickets.create'))
            ->assertOk()
            ->assertSee('New Field Service Request')
            ->assertSee('Dispatch Information')
            ->assertSee('Customer Order')
            ->assertSee('Who should the technician ask for?')
            ->assertSee('Service Location')
            ->assertSee('Reported Problem(s)')
            ->assertSee('Media Review')
            ->assertSee('Dispatch Assessment')
            ->assertSee('Dispatch Assignment')
            ->assertSee('Machine Stuck')
            ->assertSee('Recovery Risk')
            // Removed from the streamlined intake.
            ->assertDontSee('Date / Time Reported')
            ->assertDontSee('Diagnostic Summary')
            ->assertDontSee('Financial Responsibility')
            ->assertDontSee('Settlement');
    }

    public function test_ticket_creation_captures_incident_media_assessment_and_expectation(): void
    {
        $response = $this->post(route('admin.field-service.tickets.store'), $this->payload());

        $ticket = FieldServiceTicket::latest('id')->firstOrFail();

        $response->assertRedirect(route('admin.field-service.tickets.show', $ticket));

        $this->assertSame('FLD-' . str_pad((string) $ticket->id, 5, '0', STR_PAD_LEFT), $ticket->ticket_number);
        $this->assertSame(FieldMissionStatus::Draft, $ticket->mission_status);
        $this->assertSame($this->orderId, $ticket->order_id);
        $this->assertSame($this->customerId, $ticket->customer_id, 'Customer must be derived from the order');
        $this->assertSame($this->lift->id, $ticket->equipment_id);
        // Address composed from the "Different Address" fields.
        $this->assertStringContainsString('4400 Ridge Rd', $ticket->job_site_address);
        // Server timestamp is the truth — reported_at is set, never from the form.
        $this->assertNotNull($ticket->reported_at);
        // Problem summary derived from the reported problems / details.
        $this->assertSame('Boom lift will not start on jobsite.', $ticket->problem_summary);
        $this->assertTrue($ticket->photos_received);
        $this->assertTrue($ticket->media_reviewed);
        $this->assertFalse($ticket->video_received);
        $this->assertFalse($ticket->media_bypassed);
        $this->assertSame('high', $ticket->priority->value);
        $this->assertSame('inoperable', $ticket->machine_status->value);
        $this->assertSame('unknown', $ticket->operational_expectation->value);
        $this->assertSame($this->admin->id, $ticket->created_by);

        // Creation is recorded on the timeline
        $this->assertTrue($ticket->events->contains(fn ($e) => $e->event_type === FieldTicketEventType::Created));
    }

    public function test_creation_validation_requires_site_problem_and_valid_enums(): void
    {
        // The order is canonical; equipment, contact/location choices, and a
        // stated problem are all required.
        $this->post(route('admin.field-service.tickets.store'), $this->payload([
            'order_id' => '', 'equipment_id' => '', 'contact_source' => '',
            'location_source' => '', 'additional_details' => '', 'complaints' => [],
        ]))->assertSessionHasErrors(['order_id', 'equipment_id', 'contact_source', 'location_source', 'complaints']);

        $this->post(route('admin.field-service.tickets.store'), $this->payload([
            'safety_concern' => 'extreme', 'machine_status' => 'melted', 'operational_expectation' => 'maybe',
        ]))->assertSessionHasErrors(['safety_concern', 'machine_status', 'operational_expectation']);

        $this->post(route('admin.field-service.tickets.store'), $this->payload([
            'estimated_departure_at' => '2026-07-10T10:00',
            'estimated_arrival_at'   => '2026-07-10T09:00',
        ]))->assertSessionHasErrors(['estimated_arrival_at']);

        $this->assertSame(0, FieldServiceTicket::count());
    }

    public function test_order_is_required(): void
    {
        // Every field service request originates from an order — it is the
        // canonical source for customer, equipment, address, and contact.
        $this->post(route('admin.field-service.tickets.store'), $this->payload(['order_id' => null]))
            ->assertSessionHasErrors('order_id');

        $this->assertSame(0, FieldServiceTicket::count());
    }

    public function test_contact_and_location_derive_from_the_order_when_chosen(): void
    {
        $ticket = $this->makeTicket([
            'contact_source' => 'order',   'contact_name' => null, 'contact_phone' => null,
            'location_source' => 'delivery', 'loc_street' => null, 'loc_city' => null, 'loc_state' => null, 'loc_zip' => null,
        ]);

        // Derived from the order's shipping address — the dispatcher entered nothing.
        $this->assertSame('Dana Foreman', $ticket->contact_name);
        $this->assertSame('615-555-0199', $ticket->contact_phone);
        $this->assertStringContainsString('12 Depot Rd', $ticket->job_site_address);
        $this->assertStringContainsString('Dickson', $ticket->job_site_address);
    }

    public function test_deriving_from_an_order_without_delivery_address_is_rejected(): void
    {
        // A second order with no shipping address on file.
        $bareOrderId = DB::table('orders')->insertGetId([
            'unique_id' => 'test-ord-2', 'order_number' => '#7002', 'order_date' => now()->toDateString(),
            'customer_name' => 'Field Customer', 'customer_id' => $this->customerId,
        ]);
        DB::table('order_products')->insert([
            'unique_id' => 'test-op-2', 'order_id' => $bareOrderId, 'product_name' => 'Loader',
            'price' => 1, 'quantity' => 1, 'total' => 1, 'equipment_id' => $this->lift->id,
        ]);

        $this->post(route('admin.field-service.tickets.store'), $this->payload([
            'order_id' => $bareOrderId, 'location_source' => 'delivery',
            'loc_street' => null, 'loc_city' => null, 'loc_state' => null, 'loc_zip' => null,
        ]))->assertSessionHasErrors('location_source');
    }

    public function test_reported_problems_become_companion_complaints(): void
    {
        $category = \App\Models\Service\ServiceSymptomCategory::create(['name' => 'Engine ' . \Illuminate\Support\Str::random(4), 'display_order' => 1, 'is_active' => true]);
        $symptom  = \App\Models\Service\ServiceSymptom::create(['service_symptom_category_id' => $category->id, 'name' => 'Will not start ' . \Illuminate\Support\Str::random(4), 'display_order' => 1, 'is_active' => true]);

        $ticket = $this->makeTicket([
            'complaints'         => [$symptom->id],
            'additional_details' => 'Cranks but no fire.',
        ]);

        // The shared problem vocabulary flows onto the canonical companion ticket.
        $companion = $ticket->serviceTicket;
        $this->assertNotNull($companion);
        $this->assertSame(1, $companion->complaints()->count());
        $this->assertDatabaseHas('service_ticket_complaints', [
            'service_ticket_id' => $companion->id,
            'name'              => $symptom->name,
        ]);
        // Free-text detail rides the companion's customer complaint.
        $this->assertSame('Cranks but no fire.', $companion->customer_complaint);
    }

    // ── Workbench ────────────────────────────────────────────────────

    public function test_workbench_shows_mission_context_ribbon_and_current_step(): void
    {
        $ticket = $this->makeTicket();

        $this->get(route('admin.field-service.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Field Operations Workbench')
            ->assertSee($ticket->ticket_number)
            ->assertSee('Current Mission Step')
            ->assertSee('Mark Ready for Dispatch')      // draft primary action
            ->assertSee('Ready for Dispatch')           // ribbon stage
            ->assertSee('Field Assessment')             // ribbon stage
            ->assertSee('Boom lift will not start on jobsite.')
            ->assertSee('4400 Ridge Rd')
            ->assertSee('Field Customer')
            ->assertSee('#7001')
            ->assertSee('Gate code 4411.')
            ->assertSee('Media Review')
            ->assertSee('Dispatch Details')
            ->assertSee('Timeline');
    }

    // ── Mission status flow ──────────────────────────────────────────

    public function test_full_dispatch_flow_from_draft_to_completed(): void
    {
        $ticket = $this->makeTicket();
        $route  = fn () => route('admin.field-service.tickets.status', $ticket);

        $this->post($route(), ['mission_status' => 'ready_for_dispatch']);
        $this->assertSame(FieldMissionStatus::ReadyForDispatch, $ticket->fresh()->mission_status);
        $this->assertNotNull($ticket->fresh()->ready_at);

        // Assign technician + truck via the dispatch panel
        $this->put(route('admin.field-service.tickets.dispatch.update', $ticket), [
            'technician_id' => $this->tech->id,
            'truck_id'      => $this->truck->id,
        ]);
        $this->assertSame($this->tech->id, $ticket->fresh()->technician_id);

        foreach ([
            ['assigned', 'assigned_at'],
            ['en_route', 'en_route_at'],
            ['on_site', 'on_site_at'],
            ['field_assessment', 'assessment_started_at'],
        ] as [$status, $column]) {
            $this->post($route(), ['mission_status' => $status]);
            $fresh = $ticket->fresh();
            $this->assertSame($status, $fresh->mission_status->value);
            $this->assertNotNull($fresh->{$column}, "$column should be stamped");
        }

        // Assessment completion records findings + corrected expectation, and
        // automatically opens the mandatory Operational Decision stage
        $this->post($route(), [
            'mission_status'          => 'assessment_complete',
            'assessment_summary'      => 'Dead battery bank; field repair possible with replacement batteries.',
            'operational_expectation' => 'field_repair_expected',
        ]);
        $fresh = $ticket->fresh();
        $this->assertSame(FieldMissionStatus::OperationalDecision, $fresh->mission_status);
        $this->assertNotNull($fresh->assessment_completed_at);
        $this->assertNotNull($fresh->operational_decision_at);
        $this->assertSame('field_repair_expected', $fresh->operational_expectation->value);
        $this->assertStringContainsString('Dead battery bank', $fresh->assessment_summary);

        // The mission cannot close until an outcome is recorded
        $this->post($route(), ['mission_status' => 'completed']);
        $this->assertSame(FieldMissionStatus::OperationalDecision, $ticket->fresh()->mission_status);

        $this->post(route('admin.field-service.tickets.decision.store', $ticket), [
            'operational_outcome' => 'repair_on_site',
        ]);
        $fresh = $ticket->fresh();
        $this->assertSame(FieldOperationalOutcome::RepairOnSite, $fresh->operational_outcome);

        $this->post($route(), ['mission_status' => 'completed']);
        $fresh = $ticket->fresh();
        $this->assertSame(FieldMissionStatus::Completed, $fresh->mission_status);
        $this->assertNotNull($fresh->completed_at);

        // Every step landed on the timeline, including the decision
        $statusEvents = $fresh->events->where('event_type', FieldTicketEventType::StatusChanged);
        $this->assertCount(8, $statusEvents);

        $decision = $fresh->events->firstWhere('event_type', FieldTicketEventType::OperationalDecision);
        $this->assertNotNull($decision);
        $this->assertSame('Repair On Site', $decision->new_value);
    }

    public function test_statuses_cannot_be_skipped(): void
    {
        $ticket = $this->makeTicket();

        $this->post(route('admin.field-service.tickets.status', $ticket), ['mission_status' => 'en_route']);
        $this->assertSame(FieldMissionStatus::Draft, $ticket->fresh()->mission_status);

        $this->post(route('admin.field-service.tickets.status', $ticket), ['mission_status' => 'completed']);
        $this->assertSame(FieldMissionStatus::Draft, $ticket->fresh()->mission_status);
    }

    public function test_assignment_confirmation_requires_a_technician(): void
    {
        $ticket = $this->makeTicket();
        $ticket->transitionTo(FieldMissionStatus::ReadyForDispatch);

        // No technician yet — transition refused
        $this->post(route('admin.field-service.tickets.status', $ticket), ['mission_status' => 'assigned']);
        $this->assertSame(FieldMissionStatus::ReadyForDispatch, $ticket->fresh()->mission_status);

        $ticket->fill(['technician_id' => $this->tech->id])->save();

        $this->post(route('admin.field-service.tickets.status', $ticket), ['mission_status' => 'assigned']);
        $this->assertSame(FieldMissionStatus::Assigned, $ticket->fresh()->mission_status);
    }

    public function test_assessment_complete_requires_a_summary(): void
    {
        $ticket = $this->advanceTo($this->makeTicket(), FieldMissionStatus::FieldAssessment);

        $this->post(route('admin.field-service.tickets.status', $ticket), [
            'mission_status' => 'assessment_complete',
        ])->assertSessionHasErrors(['assessment_summary']);

        $this->assertSame(FieldMissionStatus::FieldAssessment, $ticket->fresh()->mission_status);
    }

    public function test_cancellation_requires_a_note_and_closes_the_mission(): void
    {
        $ticket = $this->makeTicket();

        $this->post(route('admin.field-service.tickets.status', $ticket), [
            'mission_status' => 'cancelled',
        ])->assertSessionHasErrors(['note']);

        $this->post(route('admin.field-service.tickets.status', $ticket), [
            'mission_status' => 'cancelled', 'note' => 'Customer got it running.',
        ]);

        $fresh = $ticket->fresh();
        $this->assertSame(FieldMissionStatus::Cancelled, $fresh->mission_status);
        $this->assertNotNull($fresh->cancelled_at);

        // Terminal: no further transitions, no dispatch edits
        $this->post(route('admin.field-service.tickets.status', $fresh), ['mission_status' => 'ready_for_dispatch']);
        $this->assertSame(FieldMissionStatus::Cancelled, $fresh->fresh()->mission_status);

        $this->put(route('admin.field-service.tickets.dispatch.update', $fresh), ['technician_id' => $this->tech->id]);
        $this->assertNull($fresh->fresh()->technician_id);
    }

    // ── Operational Decision (mandatory branching point) ─────────────

    public function test_decision_stage_presents_all_five_outcome_cards(): void
    {
        $ticket = $this->advanceTo($this->makeTicket(), FieldMissionStatus::OperationalDecision);

        $this->get(route('admin.field-service.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Operational Decision Required')
            ->assertSee('Repair On Site')
            ->assertSee('Recover to Shop')
            ->assertSee('Dealer Service')
            ->assertSee('Complex Recovery')
            ->assertSee('No Repair Required')
            ->assertSee('Record Operational Decision')
            ->assertSee('This decision is permanent')
            ->assertDontSee('Complete Mission');
    }

    public function test_decision_is_stage_gated_and_permanent(): void
    {
        $ticket = $this->makeTicket();

        // Cannot decide before the assessment is complete
        $this->post(route('admin.field-service.tickets.decision.store', $ticket), [
            'operational_outcome' => 'repair_on_site',
        ]);
        $this->assertNull($ticket->fresh()->operational_outcome);

        $ticket = $this->advanceTo($ticket, FieldMissionStatus::OperationalDecision);

        $this->post(route('admin.field-service.tickets.decision.store', $ticket), [
            'operational_outcome' => 'invalid_choice',
        ])->assertSessionHasErrors(['operational_outcome']);

        $this->post(route('admin.field-service.tickets.decision.store', $ticket), [
            'operational_outcome' => 'recover_to_shop',
        ]);
        $this->assertSame(FieldOperationalOutcome::RecoverToShop, $ticket->fresh()->operational_outcome);

        // Permanent — a second decision is rejected
        $this->post(route('admin.field-service.tickets.decision.store', $ticket), [
            'operational_outcome' => 'dealer_service',
        ]);
        $this->assertSame(FieldOperationalOutcome::RecoverToShop, $ticket->fresh()->operational_outcome);
    }

    public function test_mission_cannot_complete_without_an_operational_decision(): void
    {
        $ticket = $this->advanceTo($this->makeTicket(), FieldMissionStatus::OperationalDecision);

        $this->assertFalse($ticket->fresh()->transitionTo(FieldMissionStatus::Completed));
        $this->post(route('admin.field-service.tickets.status', $ticket), ['mission_status' => 'completed']);
        $this->assertSame(FieldMissionStatus::OperationalDecision, $ticket->fresh()->mission_status);

        $ticket->fresh()->recordOperationalDecision(FieldOperationalOutcome::DealerService);

        $this->post(route('admin.field-service.tickets.status', $ticket), ['mission_status' => 'completed']);
        $this->assertSame(FieldMissionStatus::Completed, $ticket->fresh()->mission_status);
    }

    public function test_recorded_decision_shows_on_workbench_and_timeline(): void
    {
        $ticket = $this->advanceTo($this->makeTicket(), FieldMissionStatus::OperationalDecision);
        $ticket->fresh()->recordOperationalDecision(FieldOperationalOutcome::ComplexRecovery);

        $this->get(route('admin.field-service.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Complex Recovery')
            ->assertSee('Operational Decision: Complex Recovery')   // emphasized timeline entry
            ->assertSee('Complete Mission');
    }

    // ── Workbench panels ─────────────────────────────────────────────

    public function test_dispatch_update_saves_assignment_and_records_event(): void
    {
        $ticket = $this->makeTicket();

        $this->put(route('admin.field-service.tickets.dispatch.update', $ticket), [
            'technician_id'          => $this->tech->id,
            'truck_id'               => $this->truck->id,
            'estimated_departure_at' => '2026-07-10T08:00',
            'estimated_arrival_at'   => '2026-07-10T09:30',
            'suggested_tools'        => 'Multimeter, jump pack',
            'suggested_parts'        => 'Batteries x4',
            'special_instructions'   => 'Call foreman on arrival.',
        ]);

        $fresh = $ticket->fresh();
        $this->assertSame($this->tech->id, $fresh->technician_id);
        $this->assertSame($this->truck->id, $fresh->truck_id);
        $this->assertSame('Batteries x4', $fresh->suggested_parts);
        $this->assertTrue($fresh->events->contains(fn ($e) => $e->event_type === FieldTicketEventType::DispatchUpdated));
    }

    public function test_media_checklist_updates_and_records_event(): void
    {
        $ticket = $this->makeTicket();

        $this->put(route('admin.field-service.tickets.media.update', $ticket), [
            'photos_received' => 1, 'video_received' => 1, 'media_reviewed' => 1,
        ]);

        $fresh = $ticket->fresh();
        $this->assertTrue($fresh->video_received);
        $this->assertTrue($fresh->media_reviewed);
        $this->assertFalse($fresh->additional_media_required);
        $this->assertTrue($fresh->events->contains(fn ($e) => $e->event_type === FieldTicketEventType::MediaUpdated));
    }

    public function test_notes_can_be_added_and_appear_on_the_workbench(): void
    {
        $ticket = $this->makeTicket();

        $this->post(route('admin.field-service.tickets.notes.store', $ticket), [
            'note' => 'Customer called — machine is behind the north barn.',
        ]);

        $this->assertCount(1, $ticket->fresh()->notes);
        $this->assertTrue($ticket->fresh()->events->contains(fn ($e) => $e->event_type === FieldTicketEventType::NoteAdded));

        $this->get(route('admin.field-service.tickets.show', $ticket))
            ->assertSee('north barn');
    }

    // ── Index ────────────────────────────────────────────────────────

    // ── Consolidation: field missions live on the Operations Board ──────
    // The standalone field index is gone; a field mission now creates a
    // companion canonical service ticket that carries it onto the board.

    public function test_field_mission_creates_a_board_visible_companion_ticket(): void
    {
        $ticket = $this->makeTicket();
        $st = $ticket->serviceTicket;

        $this->assertNotNull($st, 'A field mission must create a companion service ticket.');
        $this->assertSame(ServiceType::FieldServiceCall, $st->service_type);
        $this->assertSame($this->lift->id, $st->equipment_id);

        // It appears on the board, and its card opens the FIELD workbench
        // (not the shop workbench).
        Livewire::test(OperationsBoard::class)
            ->assertSee($st->ticket_number)
            ->assertSeeHtml(route('admin.field-service.tickets.show', $ticket));
    }

    public function test_dispatching_a_technician_makes_them_the_board_lane_leader(): void
    {
        $ticket = $this->makeTicket();

        // Assigning the technician on the mission syncs the companion's team
        // leader → it lands in that technician's board lane.
        $ticket->update(['technician_id' => $this->tech->id, 'mission_status' => FieldMissionStatus::Assigned]);

        $this->assertSame($this->tech->id, $ticket->fresh()->serviceTicket->teamLeader()?->id);
    }

    public function test_completing_a_mission_drops_its_companion_off_the_board(): void
    {
        $ticket = $this->makeTicket();
        $st = $ticket->serviceTicket;

        $this->advanceTo($ticket, FieldMissionStatus::OperationalDecision);
        $this->assertTrue($ticket->fresh()->recordOperationalDecision(FieldOperationalOutcome::NoRepairRequired));
        $this->assertTrue($ticket->fresh()->transitionTo(FieldMissionStatus::Completed));

        // Companion is Completed → excluded from the board (notFinished()).
        $this->assertSame(RepairStatus::Completed, $st->fresh()->repair_status);
        Livewire::test(OperationsBoard::class)->assertDontSee($st->ticket_number);
    }
}
