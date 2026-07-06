<?php

namespace Tests\Feature\Service;

use App\Enums\Service\ApprovalStatus;
use App\Enums\Service\FinancialStatus;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ResponsibilityDecision;
use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceTicketEventType;
use App\Enums\Service\ServiceType;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Service\ServiceTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTicketAuthorizationEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'test-admin', 'employee_code' => '01',
            'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]);

        $this->equipment = Equipment::create([
            'unique_id' => 'test-eq', 'equipment_name' => 'Test Lift',
            'equipment_id' => 'TL-1', 'brand' => 'Test',
        ]);

        $this->actingAs($this->admin);
    }

    /** An unauthorized ticket: diagnosed + decided customer-pay, approval still pending. */
    private function makeUnauthorizedTicket(): ServiceTicket
    {
        $ticket = ServiceTicket::create([
            'service_type'             => ServiceType::CustomerDamageRepair->value,
            'service_location'         => ServiceLocation::InShop->value,
            'priority'                 => ServicePriority::Normal->value,
            'repair_status'            => RepairStatus::Open->value,
            'financial_responsibility' => 'pending',
            'financial_status'         => FinancialStatus::NotBillable->value,
            'equipment_id'             => $this->equipment->id,
            'opened_at'                => now(),
        ]);

        $ticket->completeDiagnostic();
        $ticket->decideResponsibility(ResponsibilityDecision::CustomerPay);

        return $ticket->fresh();
    }

    private function attemptStatus(ServiceTicket $ticket, RepairStatus $status): void
    {
        $this->post(route('admin.service-management.tickets.status', $ticket), [
            'repair_status' => $status->value,
        ]);
    }

    // 1. Unauthorized ticket cannot transition to In Progress
    public function test_unauthorized_ticket_cannot_move_to_in_progress(): void
    {
        $ticket = $this->makeUnauthorizedTicket();
        $this->assertFalse($ticket->repairExecutionAllowed());

        $this->attemptStatus($ticket, RepairStatus::InProgress);

        $this->assertSame(RepairStatus::Open, $ticket->fresh()->repair_status);
    }

    // 2. Unauthorized ticket cannot transition to Completed
    public function test_unauthorized_ticket_cannot_move_to_completed(): void
    {
        $ticket = $this->makeUnauthorizedTicket();

        $this->attemptStatus($ticket, RepairStatus::Completed);

        $fresh = $ticket->fresh();
        $this->assertSame(RepairStatus::Open, $fresh->repair_status);
        $this->assertNull($fresh->completed_at);
    }

    // 3. Unauthorized ticket cannot transition to Ready for Pickup
    public function test_unauthorized_ticket_cannot_move_to_ready_for_pickup(): void
    {
        $ticket = $this->makeUnauthorizedTicket();

        $this->attemptStatus($ticket, RepairStatus::ReadyForPickup);

        $this->assertSame(RepairStatus::Open, $ticket->fresh()->repair_status);
    }

    // 3b. Non-execution statuses remain allowed while unauthorized
    public function test_waiting_statuses_remain_allowed_while_unauthorized(): void
    {
        $ticket = $this->makeUnauthorizedTicket();

        $this->post(route('admin.service-management.tickets.status', $ticket), [
            'repair_status'        => RepairStatus::WaitingOnCustomerApproval->value,
            'blocked_reason'       => 'Estimate sent, awaiting sign-off',
            'expected_action_date' => now()->addDays(3)->format('Y-m-d'),
        ]);
        $this->assertSame(RepairStatus::WaitingOnCustomerApproval, $ticket->fresh()->repair_status);

        $this->attemptStatus($ticket, RepairStatus::Diagnosing);
        $this->assertSame(RepairStatus::Diagnosing, $ticket->fresh()->repair_status);

        $this->attemptStatus($ticket, RepairStatus::Cancelled);
        $this->assertSame(RepairStatus::Cancelled, $ticket->fresh()->repair_status);
    }

    // 4. Authorized ticket transitions normally (and logs Repair Started)
    public function test_authorized_ticket_transitions_normally(): void
    {
        $ticket = $this->makeUnauthorizedTicket();
        $ticket->approveEstimate('Mike Harrison');
        $ticket->fresh()->authorizeRepair();

        $this->attemptStatus($ticket, RepairStatus::InProgress);
        $fresh = $ticket->fresh();
        $this->assertSame(RepairStatus::InProgress, $fresh->repair_status);
        $this->assertTrue(
            $ticket->events()->where('event_type', ServiceTicketEventType::RepairStarted->value)->exists()
        );

        $this->attemptStatus($ticket, RepairStatus::Completed);
        $this->assertSame(RepairStatus::Completed, $ticket->fresh()->repair_status);

        $this->attemptStatus($ticket, RepairStatus::Closed);
        $this->assertNotNull($ticket->fresh()->closed_at);
    }

    // 5 + 9. Manager override allows repair to proceed and is logged
    public function test_manager_override_allows_repair_and_logs_event(): void
    {
        $ticket = $this->makeUnauthorizedTicket();

        // Reason is mandatory
        $this->post(route('admin.service-management.tickets.authorization.override', $ticket), [])
            ->assertSessionHasErrors('authorization_override_reason');

        $this->post(route('admin.service-management.tickets.authorization.override', $ticket), [
            'authorization_override_reason' => 'Customer approved verbally on-site; paperwork to follow.',
        ]);

        $fresh = $ticket->fresh();
        $this->assertTrue($fresh->authorization_override);
        $this->assertTrue($fresh->repair_authorized);
        $this->assertSame($this->admin->id, $fresh->authorization_override_by);
        $this->assertNotNull($fresh->authorization_override_at);

        // Timeline records the override (with reason) but not a duplicate grant
        $override = $ticket->events()->where('event_type', ServiceTicketEventType::AuthorizationOverride->value)->first();
        $this->assertNotNull($override);
        $this->assertSame($this->admin->id, $override->user_id);
        $this->assertStringContainsString('verbally on-site', $override->notes);
        $this->assertFalse(
            $ticket->events()->where('event_type', ServiceTicketEventType::RepairAuthorized->value)->exists()
        );

        $this->attemptStatus($ticket, RepairStatus::InProgress);
        $this->assertSame(RepairStatus::InProgress, $ticket->fresh()->repair_status);
    }

    // 6 + 10. Revoking approval removes authorization (even overridden) and blocks execution
    public function test_revoking_approval_removes_authorization_and_blocks_execution(): void
    {
        $ticket = $this->makeUnauthorizedTicket();
        $ticket->overrideAuthorization('Rush job.');
        $this->assertTrue($ticket->fresh()->repair_authorized);

        $this->post(route('admin.service-management.tickets.approval.revoke', $ticket), [
            'approval_notes' => 'Customer disputed the estimate.',
        ]);

        $fresh = $ticket->fresh();
        $this->assertSame(ApprovalStatus::Revoked, $fresh->approval_status);
        $this->assertFalse($fresh->repair_authorized);
        $this->assertFalse($fresh->authorization_override);
        $this->assertFalse($fresh->repairExecutionAllowed());
        $this->assertTrue(
            $ticket->events()->where('event_type', ServiceTicketEventType::RepairAuthorizationRevoked->value)->exists()
        );

        // Repair history preserved, but further execution blocked
        $this->attemptStatus($ticket, RepairStatus::InProgress);
        $this->assertNotSame(RepairStatus::InProgress, $ticket->fresh()->repair_status);
    }

    // 7. Backend validation prevents bypass through the edit form too
    public function test_edit_form_cannot_bypass_authorization(): void
    {
        $ticket = $this->makeUnauthorizedTicket();

        $this->put(route('admin.service-management.tickets.update', $ticket), [
            'service_type'             => $ticket->service_type->value,
            'service_location'         => $ticket->service_location->value,
            'priority'                 => $ticket->priority->value,
            'repair_status'            => RepairStatus::Completed->value,
            'financial_responsibility' => $ticket->financial_responsibility->value,
            'financial_status'         => $ticket->financial_status->value,
            'equipment_id'             => $this->equipment->id,
            'opened_at'                => now()->format('Y-m-d'),
        ]);

        $fresh = $ticket->fresh();
        $this->assertSame(RepairStatus::Open, $fresh->repair_status);
        $this->assertNull($fresh->completed_at);
    }

    // 7b. Blocked attempts are recorded on the timeline with the blocker list
    public function test_blocked_attempt_logs_repair_start_blocked_event(): void
    {
        $ticket = $this->makeUnauthorizedTicket();

        $this->attemptStatus($ticket, RepairStatus::InProgress);

        $event = $ticket->events()->where('event_type', ServiceTicketEventType::RepairStartBlocked->value)->first();
        $this->assertNotNull($event);
        $this->assertSame($this->admin->id, $event->user_id);
        $this->assertSame('In Progress', $event->new_value);
        $this->assertStringContainsString('Waiting on Customer Approval', $event->notes);
    }

    // 8. Disabled UI actions render correctly
    public function test_disabled_ui_actions_render_correctly(): void
    {
        $ticket = $this->makeUnauthorizedTicket();

        $response = $this->get(route('admin.service-management.tickets.show', $ticket))->assertOk();

        // Execution buttons render disabled with the blocker tooltip
        $response->assertSee('Repair not authorized', false)
            ->assertSee('Waiting on Customer Approval')
            ->assertSee('Manager Override');

        // Non-execution work stays available
        $response->assertSee('Edit Ticket')
            ->assertSee('Add Labor Entry')
            ->assertSee('Add Part')
            ->assertSee('Upload File');

        // Once authorized, execution buttons unlock
        $ticket->approveEstimate();
        $ticket->fresh()->authorizeRepair();
        $this->get(route('admin.service-management.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Mark In Progress');
    }

    // Equipment protection hook: open repair holds equipment out of service
    public function test_equipment_hold_hook(): void
    {
        $ticket = $this->makeUnauthorizedTicket();

        $this->assertTrue($ticket->holdsEquipment());
        $this->assertTrue(ServiceTicket::hasActiveHoldForEquipment($this->equipment->id));

        // Ready for Pickup and finished states release the hold
        $ticket->update(['repair_authorized' => true]);
        $ticket->fresh()->transitionTo(RepairStatus::ReadyForPickup);
        $this->assertFalse($ticket->fresh()->holdsEquipment());
        $this->assertFalse(ServiceTicket::hasActiveHoldForEquipment($this->equipment->id));

        $ticket->fresh()->transitionTo(RepairStatus::Completed);
        $this->assertFalse(ServiceTicket::hasActiveHoldForEquipment($this->equipment->id));
    }
}
