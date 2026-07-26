<?php

namespace Tests\Feature\Service;

use App\Enums\Service\FinancialResponsibility;
use App\Enums\Service\FinancialStatus;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ResponsibilityDecision;
use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceType;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Service\ServiceTicket;
use App\Models\Stores\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\ResolvesResponsibilityDecisions;
use Tests\TestCase;

/**
 * Phase 4 workbench: workflow ribbon + stage engine, permanent context
 * header, persistent complaint/notes rail, structured diagnostic steps,
 * and the right-rail status/activity panels. Business rules are the
 * pre-existing ones — these tests cover how the workbench presents them.
 */
class ServiceTicketWorkbenchTest extends TestCase
{
    use RefreshDatabase;
    use ResolvesResponsibilityDecisions;

    private User $admin;
    private User $lead;
    private User $member;
    private Equipment $equipment;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'test-admin', 'first_name' => 'Gary', 'last_name' => 'Jezorski',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]);
        $this->lead = User::create([
            'unique_id' => 'test-lead', 'first_name' => 'Lena', 'last_name' => 'Leader',
            'email' => 'lead@test.local', 'status' => 'Active',
        ]);
        $this->member = User::create([
            'unique_id' => 'test-member', 'first_name' => 'Marco', 'last_name' => 'Member',
            'email' => 'member@test.local', 'status' => 'Active',
        ]);

        $this->equipment = Equipment::create([
            'unique_id' => 'test-eq', 'equipment_name' => "70' Boom Lift",
            'equipment_id' => '204', 'brand' => 'Genie',
        ]);
        $this->store = Store::create(['store_name' => 'Bon Aqua', 'status' => 'Active']);

        $this->actingAs($this->admin);
    }

    private function makeTicket(array $overrides = []): ServiceTicket
    {
        return ServiceTicket::create(array_merge([
            'service_type'             => ServiceType::CustomerDamageRepair->value,
            'service_location'         => ServiceLocation::InShop->value,
            'priority'                 => ServicePriority::High->value,
            'repair_status'            => RepairStatus::Open->value,
            'financial_responsibility' => FinancialResponsibility::Pending->value,
            'financial_status'         => FinancialStatus::NotBillable->value,
            'equipment_id'             => $this->equipment->id,
            'opened_at'                => now()->format('Y-m-d'),
            'customer_complaint'       => 'Machine is dead. No lights, no functions, no motion.',
            'created_by'               => $this->admin->id,
        ], $overrides));
    }

    // 1 + 3 + 4. Context header, ribbon, team, and both rails render
    public function test_workbench_shows_context_ribbon_team_and_rails(): void
    {
        $customerId = DB::table('customers')->insertGetId([
            'unique_id' => 'test-cust', 'first_name' => 'Half Flake', 'last_name' => 'Contractors',
        ]);
        $orderId = DB::table('orders')->insertGetId([
            'unique_id' => 'test-ord', 'order_number' => '#2648',
            'order_date' => now()->toDateString(), 'customer_name' => 'Half Flake Contractors',
            'customer_id' => $customerId,
        ]);

        $ticket = $this->makeTicket([
            'order_id' => $orderId, 'customer_id' => $customerId,
            'rental_date' => '2026-07-01', 'service_store_id' => $this->store->id,
        ]);
        $ticket->syncPersonnel([$this->lead->id, $this->member->id], $this->lead->id);

        $this->get(route('admin.service-management.tickets.show', $ticket))
            ->assertOk()
            // Header + context row
            ->assertSee('Service Ticket ' . $ticket->fresh()->ticket_number)
            ->assertSee('More Actions')
            ->assertSee('Edit Ticket')
            ->assertSee('Half Flake Contractors')
            ->assertSee("70&#039; Boom Lift", false)
            ->assertSee('Unit # 204')
            ->assertSee('Bon Aqua')
            // Team block: leader named, member listed, no duplicated personnel card
            ->assertSee('Team Leader')
            ->assertSee('Lena Leader')
            ->assertSee('Team Members')
            ->assertSee('Marco Member')
            // Ribbon + work panel + rails
            ->assertSee('Service Workflow Progress')
            ->assertSee('Current Phase')
            ->assertSee('Goal')
            ->assertSee('Diagnostic Steps Taken')
            ->assertSee('Current Status')
            ->assertSee('Recent Activity')
            ->assertSee('View Full Timeline')
            // Left rail persistent context
            ->assertSee('Complaint')
            ->assertSee('Machine is dead. No lights, no functions, no motion.')
            ->assertSee('Add Note');
    }

    // 2. The stage engine follows the existing lifecycle automatically
    public function test_stage_engine_advances_with_the_lifecycle(): void
    {
        $ticket = $this->makeTicket();
        $this->assertSame('diagnostic', $ticket->currentStageKey());

        $ticket->startDiagnostic();
        $this->assertSame('diagnostic', $ticket->fresh()->currentStageKey());

        $ticket->fresh()->completeDiagnostic();
        $this->assertSame('responsibility', $ticket->fresh()->currentStageKey());

        $ticket->fresh()->decideResponsibility($this->responsibilityDecision(ResponsibilityDecision::CustomerPay->value));
        $this->assertSame('approval', $ticket->fresh()->currentStageKey());

        $ticket->fresh()->approveEstimate('Customer Smith');
        // No deposit required → deposit satisfied → next gate is authorization
        $this->assertSame('authorized', $ticket->fresh()->currentStageKey());

        $ticket->fresh()->authorizeRepair();
        $this->assertSame('repair', $ticket->fresh()->currentStageKey());

        $ticket->fresh()->transitionTo(RepairStatus::InProgress);
        $this->assertSame('repair', $ticket->fresh()->currentStageKey());

        $ticket->fresh()->transitionTo(RepairStatus::Completed);
        // Customer-pay with no settlement yet → settlement is the work at hand
        $this->assertSame('settlement', $ticket->fresh()->currentStageKey());

        // Ribbon state list mirrors the same engine
        $states = collect($ticket->fresh()->workflowStages())->pluck('state', 'key');
        $this->assertSame('complete', $states['diagnostic']);
        $this->assertSame('complete', $states['authorized']);
        $this->assertSame('complete', $states['repair']);
        $this->assertSame('current', $states['settlement']);
        $this->assertSame('pending', $states['close']);
    }

    public function test_non_customer_pay_skips_settlement_stage(): void
    {
        $ticket = $this->makeTicket();
        $ticket->startDiagnostic();
        $ticket->fresh()->completeDiagnostic();
        $ticket->fresh()->decideResponsibility($this->responsibilityDecision(ResponsibilityDecision::InternalExpense->value));
        $ticket->fresh()->approveEstimate(notes: 'Internal authorization'); // internal path still needs sign-off
        $fresh = $ticket->fresh();
        $fresh->authorizeRepair();
        $fresh->fresh()->transitionTo(RepairStatus::Completed);

        $fresh = $ticket->fresh();
        $this->assertSame('close', $fresh->currentStageKey());
        $this->assertSame(
            ['Not Applicable'],
            collect($fresh->workflowStages())->firstWhere('key', 'settlement')['meta']
        );
    }

    // 7. Current Status explains why work has stopped
    public function test_current_status_shows_operational_blockers(): void
    {
        // Approval pending → waiting on customer approval
        $ticket = $this->makeTicket();
        $ticket->startDiagnostic();
        $ticket->fresh()->completeDiagnostic();
        $ticket->fresh()->decideResponsibility($this->responsibilityDecision(ResponsibilityDecision::CustomerPay->value));

        $this->get(route('admin.service-management.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Waiting on Customer Approval');

        // Blocked repair status → waiting on parts, with the blocked banner
        $blocked = $this->makeTicket();
        $blocked->transitionTo(RepairStatus::WaitingOnParts, 'Final drive on backorder', now()->addDays(5)->toDateString());

        $this->get(route('admin.service-management.tickets.show', $blocked))
            ->assertOk()
            ->assertSee('Waiting on Parts')
            ->assertSee('Final drive on backorder');
    }

    // 8. Diagnostic steps: structured add/remove with timeline events
    public function test_diagnostic_steps_record_and_remove_with_events(): void
    {
        $ticket = $this->makeTicket();

        $this->post(route('admin.service-management.tickets.diagnostic-steps.store', $ticket), [
            'step_type'   => 'battery_test',
            'description' => 'Battery voltage was 9.8V',
            'outcome'     => 'failed',
        ])->assertRedirect(route('admin.service-management.tickets.show', $ticket));

        $this->assertDatabaseHas('service_ticket_diagnostic_steps', [
            'service_ticket_id' => $ticket->id,
            'step_type'         => 'battery_test',
            'outcome'           => 'failed',
            'created_by'        => $this->admin->id,
        ]);
        $this->assertDatabaseHas('service_ticket_events', [
            'service_ticket_id' => $ticket->id,
            'event_type'        => 'diagnostic_step_added',
        ]);

        $this->get(route('admin.service-management.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Battery Test')
            ->assertSee('Battery voltage was 9.8V')
            ->assertSee('Failed');

        // Unknown step types are rejected
        $this->post(route('admin.service-management.tickets.diagnostic-steps.store', $ticket), [
            'step_type' => 'guesswork', 'outcome' => 'failed',
        ])->assertSessionHasErrors('step_type');

        // Steps can be removed — but only through their own ticket
        $step  = $ticket->diagnosticSteps()->first();
        $other = $this->makeTicket();
        $this->delete(route('admin.service-management.tickets.diagnostic-steps.destroy', [$other, $step]))
            ->assertNotFound();

        $this->delete(route('admin.service-management.tickets.diagnostic-steps.destroy', [$ticket, $step]))
            ->assertRedirect();
        $this->assertSoftDeleted('service_ticket_diagnostic_steps', ['id' => $step->id]);
    }

    // 5. Notes: add + edit stay on the ticket with timeline events
    public function test_notes_can_be_added_and_edited(): void
    {
        $ticket = $this->makeTicket();

        $this->post(route('admin.service-management.tickets.notes.store', $ticket), [
            'note' => 'Battery tested low. Charging now.',
        ])->assertRedirect(route('admin.service-management.tickets.show', $ticket));

        $note = $ticket->notes()->firstOrFail();
        $this->assertSame($this->admin->id, $note->created_by);
        $this->assertDatabaseHas('service_ticket_events', [
            'service_ticket_id' => $ticket->id, 'event_type' => 'note_added',
        ]);

        $this->put(route('admin.service-management.tickets.notes.update', [$ticket, $note]), [
            'note' => 'Battery tested low. Replaced under shop stock.',
        ])->assertRedirect();

        $this->assertDatabaseHas('service_ticket_notes', ['id' => $note->id, 'note' => 'Battery tested low. Replaced under shop stock.']);
        $this->assertDatabaseHas('service_ticket_events', [
            'service_ticket_id' => $ticket->id, 'event_type' => 'note_updated',
        ]);

        // Both the intake note and the added note render in the Notes rail
        $this->get(route('admin.service-management.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Reported at return check-in.')
            ->assertSee('Battery tested low. Replaced under shop stock.')
            ->assertSee('Edit Note');

        // Notes are only reachable through their own ticket
        $other = $this->makeTicket();
        $this->put(route('admin.service-management.tickets.notes.update', [$other, $note]), ['note' => 'x'])
            ->assertNotFound();
    }

    // 6. Recent Activity reflects the latest timeline entries
    public function test_recent_activity_shows_latest_events(): void
    {
        $ticket = $this->makeTicket();
        $ticket->startDiagnostic();

        $this->get(route('admin.service-management.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Recent Activity')
            ->assertSee('Diagnostic Started')
            ->assertSee('Ticket Created')
            ->assertSee('View Full Timeline');
    }
}
