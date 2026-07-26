<?php

namespace Tests\Feature\Service;

use App\Enums\Service\RepairStatus;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceTicketEventType;
use App\Enums\Service\ServiceType;
use App\Livewire\ServiceManagement\OperationsBoard;
use App\Models\FieldService\FieldServiceTicket;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Service\ServiceTicket;
use App\Services\ServiceManagement\ServiceTicketIntakeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Service Operations Board (Phase 1) — the consolidated technician-swimlane
 * dashboard. Pins that the page mounts the Livewire component, tickets group
 * into Team-Leader lanes (Unassigned when no leader), and the inline status
 * control routes through the canonical transition.
 */
class ServiceOperationsBoardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $tech;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create(['first_name' => 'Ada', 'last_name' => 'Admin', 'email' => 'board-admin@test.local', 'status' => 'Active']);
        $this->tech  = User::create(['first_name' => 'Jake', 'last_name' => 'Sherman', 'email' => 'jake@test.local', 'status' => 'Active']);
        $this->actingAs($this->admin);
    }

    private function makeEquipment(): Equipment
    {
        $cat = ProductCategory::create(['title' => 'Skid Steers ' . Str::random(4), 'status' => 'Published', 'sort_order' => 1]);

        return Equipment::create([
            'unique_id' => 'bd-eq-' . Str::random(6), 'equipment_name' => 'SVL75 Track Loader',
            'equipment_id' => 'EQ-' . Str::random(4), 'brand' => 'Kubota',
            'product_category_id' => $cat->id, 'current_status' => 'available',
        ]);
    }

    private function makeTicket(array $attrs = [], array $personnel = [], ?int $leader = null): ServiceTicket
    {
        return ServiceTicketIntakeService::create(
            array_merge(['equipment_id' => $this->makeEquipment()->id], $attrs),
            [],
            $personnel,
            $leader,
        );
    }

    public function test_board_page_mounts_the_livewire_component(): void
    {
        $this->get(route('admin.service-management.board'))
            ->assertOk()
            ->assertSee('Service Operations')
            ->assertSeeLivewire(OperationsBoard::class);
    }

    public function test_ticket_with_team_leader_lands_in_that_lane(): void
    {
        $this->makeTicket([], [$this->tech->id], $this->tech->id);

        Livewire::test(OperationsBoard::class)
            ->assertSee('Jake Sherman')       // lane header
            ->assertSee('SVL75 Track Loader') // card
            ->assertDontSee('Awaiting dispatch');
    }

    public function test_ticket_without_team_leader_lands_in_unassigned_lane(): void
    {
        $this->makeTicket();

        Livewire::test(OperationsBoard::class)
            ->assertSee('Unassigned · Intake')
            ->assertSee('Awaiting dispatch');
    }

    public function test_inline_status_change_transitions_the_ticket(): void
    {
        $ticket = $this->makeTicket([], [$this->tech->id], $this->tech->id);
        $this->assertSame(RepairStatus::Open, $ticket->repair_status);

        Livewire::test(OperationsBoard::class)
            ->call('setStatus', $ticket->id, RepairStatus::InProgress->value);

        $this->assertSame(RepairStatus::InProgress, $ticket->fresh()->repair_status);
    }

    public function test_invalid_status_is_ignored(): void
    {
        $ticket = $this->makeTicket([], [$this->tech->id], $this->tech->id);

        Livewire::test(OperationsBoard::class)
            ->call('setStatus', $ticket->id, 'not_a_status');

        $this->assertSame(RepairStatus::Open, $ticket->fresh()->repair_status);
    }

    public function test_issue_type_tab_filters_the_board(): void
    {
        // A warranty ticket + a customer-damage ticket, both led by the tech.
        $this->makeTicket(['service_type' => ServiceType::OemWarrantyRepair->value], [$this->tech->id], $this->tech->id);
        $this->makeTicket(['service_type' => ServiceType::CustomerDamageRepair->value], [$this->tech->id], $this->tech->id);

        Livewire::test(OperationsBoard::class)
            ->call('setType', 'Warranty')
            ->assertSet('typeFilter', 'Warranty')
            ->assertSee('OEM Warranty');
    }

    // ── Consolidation: board is the single hub ──────────────────────────

    public function test_search_filters_by_ticket_number(): void
    {
        $a = $this->makeTicket([], [$this->tech->id], $this->tech->id);
        $b = $this->makeTicket([], [$this->tech->id], $this->tech->id);

        Livewire::test(OperationsBoard::class)
            ->set('search', $a->ticket_number)
            ->assertSee($a->ticket_number)
            ->assertDontSee($b->ticket_number);
    }

    public function test_technician_filter_shows_only_that_technicians_lane(): void
    {
        $other = User::create(['first_name' => 'Mona', 'last_name' => 'Ortiz', 'email' => 'mona@test.local', 'status' => 'Active']);
        $mine   = $this->makeTicket([], [$this->tech->id], $this->tech->id);
        $theirs = $this->makeTicket([], [$other->id], $other->id);

        Livewire::test(OperationsBoard::class)
            ->set('technicianFilter', (string) $this->tech->id)
            ->assertSee($mine->ticket_number)
            ->assertDontSee($theirs->ticket_number);
    }

    public function test_board_page_offers_the_new_service_work_launcher(): void
    {
        $this->get(route('admin.service-management.board'))
            ->assertOk()
            ->assertSee('New Service Work')
            ->assertSee('Shop Repair')
            ->assertSee('Field Service');
    }

    public function test_legacy_overview_and_index_routes_are_removed(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.service-management.overview'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.service-management.tickets.index'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.field-service.tickets.index'));
    }

    // ── Drag reassignment (lane = canonical team leader) ────────────────

    // 10. Same-lane drag is a pure priority reorder — never an assignment change.
    public function test_same_lane_move_only_reorders_positions(): void
    {
        $a = $this->makeTicket([], [$this->tech->id], $this->tech->id);
        $b = $this->makeTicket([], [$this->tech->id], $this->tech->id);

        Livewire::test(OperationsBoard::class)
            ->call('moveCard', $b->id, (string) $this->tech->id, [$b->id, $a->id]);

        $this->assertSame(1, $b->fresh()->board_position);
        $this->assertSame(2, $a->fresh()->board_position);
        // Ownership untouched.
        $this->assertSame($this->tech->id, $a->fresh()->teamLeader()?->id);
        $this->assertSame($this->tech->id, $b->fresh()->teamLeader()?->id);
    }

    // 2 + 3 + 4 + 5. Cross-lane reassign changes the canonical leader, keeps
    // the rest of the crew, and records the position.
    public function test_reassign_changes_canonical_leader_and_preserves_crew(): void
    {
        $helper = User::create(['first_name' => 'Hank', 'last_name' => 'Helper', 'email' => 'hank@test.local', 'status' => 'Active']);
        $other  = User::create(['first_name' => 'Mike', 'last_name' => 'Ortiz', 'email' => 'mike@test.local', 'status' => 'Active']);
        $ticket = $this->makeTicket([], [$this->tech->id, $helper->id], $this->tech->id);

        Livewire::test(OperationsBoard::class)
            ->call('reassignCard', $ticket->id, (string) $other->id, [$ticket->id]);

        $fresh = $ticket->fresh();
        $this->assertSame($other->id, $fresh->teamLeader()?->id);          // new leader
        $this->assertFalse($fresh->personnel->firstWhere('id', $this->tech->id)->pivot->is_team_leader); // A no longer leader
        $this->assertTrue($fresh->personnel->contains('id', $helper->id)); // crew preserved
        $this->assertSame(1, $fresh->board_position);
    }

    // 9. Dragging to Unassigned clears the team leader.
    public function test_reassign_to_unassigned_clears_the_leader(): void
    {
        $ticket = $this->makeTicket([], [$this->tech->id], $this->tech->id);

        Livewire::test(OperationsBoard::class)
            ->call('reassignCard', $ticket->id, 'unassigned', [$ticket->id]);

        $this->assertNull($ticket->fresh()->teamLeader());
    }

    // 11. Reassignment writes an audit event (who / from / to / source).
    public function test_reassign_records_an_audit_event(): void
    {
        $other  = User::create(['first_name' => 'Mike', 'last_name' => 'Ortiz', 'email' => 'mike2@test.local', 'status' => 'Active']);
        $ticket = $this->makeTicket([], [$this->tech->id], $this->tech->id);

        Livewire::test(OperationsBoard::class)
            ->call('reassignCard', $ticket->id, (string) $other->id, [$ticket->id]);

        $event = $ticket->events()->where('event_type', ServiceTicketEventType::TechnicianReassigned->value)->first();
        $this->assertNotNull($event);
        $this->assertSame($this->admin->id, $event->user_id);              // actor
        $this->assertSame('Jake Sherman', $event->old_value);
        $this->assertSame('Mike Ortiz', $event->new_value);
        $this->assertSame('operations_board_dnd', $event->metadata['source']);
    }

    // Source + destination lanes are both renumbered after a cross-lane move.
    public function test_reassign_renormalizes_the_source_lane(): void
    {
        $other = User::create(['first_name' => 'Mike', 'last_name' => 'Ortiz', 'email' => 'mike5@test.local', 'status' => 'Active']);
        $a1  = $this->makeTicket([], [$this->tech->id], $this->tech->id);
        $mid = $this->makeTicket([], [$this->tech->id], $this->tech->id);
        $a3  = $this->makeTicket([], [$this->tech->id], $this->tech->id);

        // Establish 1,2,3 in technician A's lane.
        Livewire::test(OperationsBoard::class)
            ->call('moveCard', $a1->id, (string) $this->tech->id, [$a1->id, $mid->id, $a3->id]);

        // Reassign the MIDDLE card (position 2) to technician B.
        Livewire::test(OperationsBoard::class)
            ->call('reassignCard', $mid->id, (string) $other->id, [$mid->id]);

        // Source lane closes the gap → remaining two are 1,2 (not 1,3).
        $this->assertSame(1, $a1->fresh()->board_position);
        $this->assertSame(2, $a3->fresh()->board_position);
        // Destination lane normalized too.
        $this->assertSame($other->id, $mid->fresh()->teamLeader()?->id);
        $this->assertSame(1, $mid->fresh()->board_position);
    }

    // 6. Field Service companion technician stays in sync with the canonical leader.
    public function test_reassign_syncs_field_service_companion_technician(): void
    {
        $other = User::create(['first_name' => 'Mike', 'last_name' => 'Ortiz', 'email' => 'mike3@test.local', 'status' => 'Active']);
        $st = $this->makeTicket(['service_type' => ServiceType::FieldServiceCall->value], [$this->tech->id], $this->tech->id);
        $field = FieldServiceTicket::create([
            'service_ticket_id' => $st->id,
            'equipment_id'      => $this->makeEquipment()->id,
            'problem_summary'   => 'Field mission',
            'technician_id'     => $this->tech->id,
        ]);

        Livewire::test(OperationsBoard::class)
            ->call('reassignCard', $st->id, (string) $other->id, [$st->id]);

        $this->assertSame($other->id, $field->fresh()->technician_id);
    }

    // 12. An unauthenticated request cannot reassign (server-authoritative guard).
    public function test_unauthenticated_request_cannot_reassign(): void
    {
        $other  = User::create(['first_name' => 'Mike', 'last_name' => 'Ortiz', 'email' => 'mike4@test.local', 'status' => 'Active']);
        $ticket = $this->makeTicket([], [$this->tech->id], $this->tech->id);

        auth()->logout();

        try {
            Livewire::test(OperationsBoard::class)
                ->call('reassignCard', $ticket->id, (string) $other->id, [$ticket->id]);
            $this->fail('Expected a 403 for an unauthenticated reassignment.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // No ownership change occurred.
        $this->assertSame($this->tech->id, $ticket->fresh()->teamLeader()?->id);
    }

    public function test_completed_and_closed_tickets_are_excluded(): void
    {
        $this->makeTicket(['repair_status' => RepairStatus::Closed->value], [$this->tech->id], $this->tech->id);

        Livewire::test(OperationsBoard::class)
            ->assertDontSee('SVL75 Track Loader');
    }

    public function test_kpi_open_count_reflects_open_tickets(): void
    {
        $this->makeTicket([], [$this->tech->id], $this->tech->id);
        $this->makeTicket();

        Livewire::test(OperationsBoard::class)
            ->assertSee('Open Tickets')
            ->assertSee('Emergency');
    }

    // ── Phase 2: drag persistence (reorder + reassign) ───────────────────

    public function test_move_card_reorders_within_a_lane(): void
    {
        $a = $this->makeTicket([], [$this->tech->id], $this->tech->id);
        $b = $this->makeTicket([], [$this->tech->id], $this->tech->id);

        // Drop b before a in the tech's lane.
        Livewire::test(OperationsBoard::class)
            ->call('moveCard', $b->id, (string) $this->tech->id, [$b->id, $a->id]);

        $this->assertSame(1, $b->fresh()->board_position);
        $this->assertSame(2, $a->fresh()->board_position);
    }

    public function test_move_card_reassigns_the_team_leader(): void
    {
        $tech2 = User::create(['first_name' => 'Brady', 'last_name' => 'Stultz', 'email' => 'brady@test.local', 'status' => 'Active']);
        $ticket = $this->makeTicket([], [$this->tech->id], $this->tech->id);
        $this->assertSame($this->tech->id, $ticket->teamLeader()?->id);

        Livewire::test(OperationsBoard::class)
            ->call('moveCard', $ticket->id, (string) $tech2->id, [$ticket->id]);

        $ticket->refresh();
        $this->assertSame($tech2->id, $ticket->teamLeader()?->id);
        // Reassigning to a tech not on the crew adds them.
        $this->assertTrue($ticket->personnel()->where('users.id', $tech2->id)->exists());
    }

    public function test_move_card_to_unassigned_clears_the_team_leader(): void
    {
        $ticket = $this->makeTicket([], [$this->tech->id], $this->tech->id);
        $this->assertNotNull($ticket->teamLeader());

        Livewire::test(OperationsBoard::class)
            ->call('moveCard', $ticket->id, 'unassigned', [$ticket->id]);

        $this->assertNull($ticket->refresh()->teamLeader());
    }
}
