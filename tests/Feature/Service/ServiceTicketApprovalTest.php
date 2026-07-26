<?php

namespace Tests\Feature\Service;

use App\Enums\Service\ApprovalStatus;
use App\Enums\Service\ApprovalType;
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
use Illuminate\Support\Facades\DB;
use Tests\Concerns\ResolvesResponsibilityDecisions;
use Tests\TestCase;

class ServiceTicketApprovalTest extends TestCase
{
    use RefreshDatabase;
    use ResolvesResponsibilityDecisions;

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

    /** A ticket that has completed diagnosis with the given responsibility decision. */
    private function makeDecidedTicket(ResponsibilityDecision $decision): ServiceTicket
    {
        $ticket = ServiceTicket::create([
            'service_type'             => ServiceType::InspectionDiagnosis->value,
            'service_location'         => ServiceLocation::InShop->value,
            'priority'                 => ServicePriority::Normal->value,
            'repair_status'            => RepairStatus::Open->value,
            'financial_responsibility' => 'pending',
            'financial_status'         => FinancialStatus::NotBillable->value,
            'equipment_id'             => $this->equipment->id,
            'opened_at'                => now(),
        ]);

        $ticket->startDiagnostic();
        $ticket->completeDiagnostic();
        $ticket->decideResponsibility($this->responsibilityDecision($decision->value));

        return $ticket->fresh();
    }

    // 1. Customer-pay ticket requires approval before repair authorization
    public function test_customer_pay_requires_approval_before_authorization(): void
    {
        $ticket = $this->makeDecidedTicket(ResponsibilityDecision::CustomerPay);

        $this->assertSame(ApprovalType::CustomerApproval, $ticket->approval_type);
        $this->assertSame(ApprovalStatus::Pending, $ticket->approval_status);
        $this->assertFalse($ticket->canAuthorizeRepair());

        // Authorization endpoint refuses while approval is pending
        $this->post(route('admin.service-management.tickets.authorization.store', $ticket))
            ->assertRedirect();
        $this->assertFalse($ticket->fresh()->repair_authorized);

        // Approve → authorization now possible
        $this->post(route('admin.service-management.tickets.approval.approve', $ticket), [
            'approved_by_customer_name' => 'Mike Harrison',
        ]);
        $fresh = $ticket->fresh();
        $this->assertSame(ApprovalStatus::Approved, $fresh->approval_status);
        $this->assertSame('Mike Harrison', $fresh->approved_by_customer_name);
        $this->assertTrue($fresh->canAuthorizeRepair());

        $this->post(route('admin.service-management.tickets.authorization.store', $ticket), [
            'repair_authorization_notes' => 'Go ahead per customer approval.',
        ]);
        $authorized = $ticket->fresh();
        $this->assertTrue($authorized->repair_authorized);
        $this->assertNotNull($authorized->repair_authorized_at);
        $this->assertSame($this->admin->id, $authorized->repair_authorized_by);
    }

    // 2. OEM warranty ticket requires OEM approval
    public function test_oem_warranty_requires_oem_approval(): void
    {
        $ticket = $this->makeDecidedTicket(ResponsibilityDecision::OemWarranty);

        $this->assertSame(ApprovalType::OemWarrantyApproval, $ticket->approval_type);
        $this->assertFalse($ticket->canAuthorizeRepair());
        $this->assertContains('Waiting on OEM Approval', $ticket->authorizationBlockers()->all());

        $ticket->approveEstimate();
        $this->assertTrue($ticket->fresh()->canAuthorizeRepair());
    }

    // 3. Internal ticket can be manager-approved
    public function test_internal_ticket_can_be_manager_approved(): void
    {
        $ticket = $this->makeDecidedTicket(ResponsibilityDecision::InternalExpense);

        $this->assertSame(ApprovalType::InternalManagementApproval, $ticket->approval_type);
        $this->assertFalse($ticket->canAuthorizeRepair());

        $this->post(route('admin.service-management.tickets.approval.approve', $ticket), [
            'approval_notes' => 'Approved by shop manager.',
        ]);

        $fresh = $ticket->fresh();
        $this->assertSame(ApprovalStatus::Approved, $fresh->approval_status);
        $this->assertSame($this->admin->id, $fresh->approved_by_user_id);
        $this->assertTrue($fresh->canAuthorizeRepair());
    }

    // 3b. No-payer decisions need no approval at all
    public function test_no_problem_found_requires_no_approval(): void
    {
        $ticket = $this->makeDecidedTicket(ResponsibilityDecision::NoProblemFound);

        $this->assertSame(ApprovalStatus::NotRequired, $ticket->approval_status);
        $this->assertNull($ticket->approval_type);
        $this->assertTrue($ticket->canAuthorizeRepair());
    }

    // 4. Deposit required blocks authorization until paid or overridden
    public function test_deposit_required_blocks_authorization(): void
    {
        $ticket = $this->makeDecidedTicket(ResponsibilityDecision::CustomerPay);
        $ticket->approveEstimate();

        $this->put(route('admin.service-management.tickets.deposit.update', $ticket), [
            'parts_deposit_required' => 1,
            'parts_deposit_amount'   => 850.00,
        ]);

        $fresh = $ticket->fresh();
        $this->assertTrue($fresh->parts_deposit_required);
        $this->assertFalse($fresh->depositSatisfied());
        $this->assertFalse($fresh->canAuthorizeRepair());
        $this->assertContains('Waiting on Parts Deposit', $fresh->authorizationBlockers()->all());

        $this->post(route('admin.service-management.tickets.authorization.store', $ticket));
        $this->assertFalse($ticket->fresh()->repair_authorized);
    }

    // 5. Deposit paid allows authorization
    public function test_deposit_paid_allows_authorization(): void
    {
        $ticket = $this->makeDecidedTicket(ResponsibilityDecision::CustomerPay);
        $ticket->approveEstimate();
        $ticket->fresh()->update(['parts_deposit_required' => true, 'parts_deposit_amount' => 850]);

        $this->put(route('admin.service-management.tickets.deposit.update', $ticket), [
            'parts_deposit_required'          => 1,
            'parts_deposit_amount'            => 850.00,
            'parts_deposit_paid'              => 1,
            'parts_deposit_payment_reference' => 'CHK-1042',
            'parts_deposit_creditable'        => 1,
        ]);

        $fresh = $ticket->fresh();
        $this->assertTrue($fresh->parts_deposit_paid);
        $this->assertNotNull($fresh->parts_deposit_paid_at);
        $this->assertTrue($fresh->depositSatisfied());
        $this->assertTrue($fresh->canAuthorizeRepair());

        $this->post(route('admin.service-management.tickets.authorization.store', $ticket));
        $this->assertTrue($ticket->fresh()->repair_authorized);
    }

    // 6. Manager override allows authorization without deposit
    public function test_manager_override_allows_authorization_without_deposit(): void
    {
        $ticket = $this->makeDecidedTicket(ResponsibilityDecision::CustomerPay);
        $ticket->approveEstimate();
        $ticket->fresh()->update(['parts_deposit_required' => true, 'parts_deposit_amount' => 850]);

        // Reason is mandatory
        $this->post(route('admin.service-management.tickets.deposit.override', $ticket), [])
            ->assertSessionHasErrors('deposit_override_reason');

        $this->post(route('admin.service-management.tickets.deposit.override', $ticket), [
            'deposit_override_reason' => 'Long-time commercial account, invoice on completion.',
        ]);

        $fresh = $ticket->fresh();
        $this->assertTrue($fresh->deposit_override);
        $this->assertSame($this->admin->id, $fresh->deposit_override_by);
        $this->assertNotNull($fresh->deposit_override_at);
        $this->assertFalse($fresh->parts_deposit_paid);
        $this->assertTrue($fresh->canAuthorizeRepair());

        $this->post(route('admin.service-management.tickets.authorization.store', $ticket));
        $this->assertTrue($ticket->fresh()->repair_authorized);
    }

    // 7. Approval events are written to the timeline
    public function test_approval_events_written_to_timeline(): void
    {
        $ticket = $this->makeDecidedTicket(ResponsibilityDecision::CustomerPay);

        $this->post(route('admin.service-management.tickets.approval.estimate-sent', $ticket));
        $this->post(route('admin.service-management.tickets.approval.approve', $ticket));

        $types = $ticket->events()->pluck('event_type')->map(fn ($t) => $t->value);
        $this->assertContains(ServiceTicketEventType::EstimateSent->value, $types);
        $this->assertContains(ServiceTicketEventType::EstimateApproved->value, $types);

        // Decline path on a second ticket
        $declined = $this->makeDecidedTicket(ResponsibilityDecision::CustomerPay);
        $this->post(route('admin.service-management.tickets.approval.decline', $declined), [
            'approval_notes' => 'Too expensive.',
        ]);
        $this->assertTrue(
            $declined->events()->where('event_type', ServiceTicketEventType::EstimateDeclined->value)->exists()
        );
        $this->assertSame(ApprovalStatus::Declined, $declined->fresh()->approval_status);
    }

    // 7b. Authorization + revocation events; approval revoke pulls authorization
    public function test_authorization_events_and_revoke_cascade(): void
    {
        $ticket = $this->makeDecidedTicket(ResponsibilityDecision::CustomerPay);
        $ticket->approveEstimate();
        $ticket->fresh()->authorizeRepair('Approved verbally.');

        $this->assertTrue(
            $ticket->events()->where('event_type', ServiceTicketEventType::RepairAuthorized->value)->exists()
        );

        // Revoking the approval also revokes authorization — both events logged
        $this->post(route('admin.service-management.tickets.approval.revoke', $ticket));
        $fresh = $ticket->fresh();
        $this->assertSame(ApprovalStatus::Revoked, $fresh->approval_status);
        $this->assertFalse($fresh->repair_authorized);
        $this->assertTrue(
            $ticket->events()->where('event_type', ServiceTicketEventType::RepairAuthorizationRevoked->value)->exists()
        );
    }

    // 8. Deposit events are written to the timeline
    public function test_deposit_events_written_to_timeline(): void
    {
        $ticket = $this->makeDecidedTicket(ResponsibilityDecision::CustomerPay);

        $this->put(route('admin.service-management.tickets.deposit.update', $ticket), [
            'parts_deposit_required' => 1, 'parts_deposit_amount' => 500,
        ]);
        $this->put(route('admin.service-management.tickets.deposit.update', $ticket), [
            'parts_deposit_required' => 1, 'parts_deposit_amount' => 500,
            'parts_deposit_paid' => 1, 'parts_deposit_payment_reference' => 'CC-AUTH-771',
        ]);

        $types = $ticket->events()->pluck('event_type')->map(fn ($t) => $t->value);
        $this->assertContains(ServiceTicketEventType::PartsDepositRequired->value, $types);
        $this->assertContains(ServiceTicketEventType::PartsDepositPaid->value, $types);

        $paidEvent = $ticket->events()->where('event_type', ServiceTicketEventType::PartsDepositPaid->value)->first();
        $this->assertStringContainsString('CC-AUTH-771', $paidEvent->notes);

        // Override event on a second ticket
        $other = $this->makeDecidedTicket(ResponsibilityDecision::CustomerPay);
        $other->update(['parts_deposit_required' => true]);
        $this->post(route('admin.service-management.tickets.deposit.override', $other), [
            'deposit_override_reason' => 'House account.',
        ]);
        $overrideEvent = $other->events()->where('event_type', ServiceTicketEventType::PartsDepositOverridden->value)->first();
        $this->assertNotNull($overrideEvent);
        $this->assertSame('House account.', $overrideEvent->notes);
    }

    // 9 + 10. No payment or Order Extra Payments rows are ever created
    public function test_no_payment_or_extra_payment_rows_created(): void
    {
        $paymentsBefore     = DB::table('order_payments')->count();
        $extraChargesBefore = DB::table('order_extra_charges')->count();

        $ticket = $this->makeDecidedTicket(ResponsibilityDecision::CustomerPay);
        $ticket->approveEstimate();
        $this->put(route('admin.service-management.tickets.deposit.update', $ticket), [
            'parts_deposit_required' => 1, 'parts_deposit_amount' => 850, 'parts_deposit_paid' => 1,
        ]);
        $this->post(route('admin.service-management.tickets.authorization.store', $ticket));

        $this->assertTrue($ticket->fresh()->repair_authorized);
        $this->assertSame($paymentsBefore, DB::table('order_payments')->count());
        $this->assertSame($extraChargesBefore, DB::table('order_extra_charges')->count());
    }

    // Workbench renders the gate states
    public function test_workbench_renders_authorization_states(): void
    {
        $ticket = $this->makeDecidedTicket(ResponsibilityDecision::CustomerPay);

        $this->get(route('admin.service-management.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Repair Authorization')
            ->assertSee('Waiting on Customer Approval')
            ->assertSee('Parts Deposit');

        $ticket->approveEstimate();
        $this->get(route('admin.service-management.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Authorize Repair');

        $ticket->fresh()->authorizeRepair();
        $this->get(route('admin.service-management.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Repair Authorized')
            ->assertSee('Revoke Authorization');
    }
}
