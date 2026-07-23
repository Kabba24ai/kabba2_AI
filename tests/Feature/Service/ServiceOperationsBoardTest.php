<?php

namespace Tests\Feature\Service;

use App\Enums\Service\RepairStatus;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceType;
use App\Livewire\ServiceManagement\OperationsBoard;
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
