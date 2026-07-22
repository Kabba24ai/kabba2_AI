<?php

namespace Tests\Feature\Warranty;

use App\Enums\Service\ServiceType;
use App\Enums\Warranty\WarrantyOemDecision;
use App\Enums\Warranty\WarrantyQueue;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Service\ServiceTicket;
use App\Models\Warranty\WarrantyCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ST-4 — Warranty Phase 2 mid-lifecycle workflow: submit → OEM decision →
 * (customer decision) → repair complete → reimbursement → closed, plus the
 * auto-advance from the linked Service Ticket's diagnosis completion.
 */
class WarrantyPhase2Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private int $customerId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create([
            'unique_id' => 'w2-admin', 'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'w2-admin@test.local', 'status' => 'Active',
        ]);
        $this->customerId = DB::table('customers')->insertGetId([
            'unique_id' => 'w2-cust', 'first_name' => 'Warren', 'last_name' => 'Tee',
        ]);
        $this->actingAs($this->admin);
    }

    /** A case parked in a given queue with a linked ticket. */
    private function caseInQueue(WarrantyQueue $queue, array $extra = []): WarrantyCase
    {
        $ticket = ServiceTicket::create([
            'service_type' => ServiceType::OemWarrantyRepair->value, 'service_location' => 'in_shop',
            'priority' => 'normal', 'repair_status' => 'open',
            'financial_responsibility' => 'pending', 'financial_status' => 'not_billable',
            'opened_at' => now(), 'customer_id' => $this->customerId,
        ]);

        return WarrantyCase::create(array_merge([
            'path' => 'external', 'queue' => $queue->value, 'customer_id' => $this->customerId,
            'manufacturer' => 'Kubota', 'model' => 'U35', 'serial_number' => 'SN-1',
            'complaint' => 'Hydraulic fault', 'service_ticket_id' => $ticket->id,
            'created_by' => $this->admin->id,
        ], $extra));
    }

    public function test_submit_records_reference_and_advances(): void
    {
        $case = $this->caseInQueue(WarrantyQueue::ReadyToSubmit);

        $this->post(route('admin.warranty.claims.submit', $case), [
            'oem_submission_reference' => 'CLAIM-9910',
        ])->assertRedirect();

        $case->refresh();
        $this->assertSame('CLAIM-9910', $case->oem_submission_reference);
        $this->assertSame(WarrantyQueue::WaitingOnManufacturer, $case->queue);
    }

    public function test_full_approval_goes_straight_to_repair_and_sets_expected_reimbursement(): void
    {
        $case = $this->caseInQueue(WarrantyQueue::WaitingOnManufacturer);

        $this->post(route('admin.warranty.claims.oem-decision', $case), [
            'oem_decision' => 'approved', 'oem_approved_amount' => 1200,
        ])->assertRedirect();

        $case->refresh();
        $this->assertSame(WarrantyOemDecision::Approved, $case->oem_decision);
        $this->assertEqualsWithDelta(1200.0, (float) $case->oem_approved_amount, 0.001);
        $this->assertEqualsWithDelta(1200.0, (float) $case->reimbursement_expected_amount, 0.001);
        $this->assertSame(WarrantyQueue::ApprovedForRepair, $case->queue, 'full approval skips customer decision');
    }

    public function test_partial_and_denied_route_through_customer_decision(): void
    {
        $partial = $this->caseInQueue(WarrantyQueue::WaitingOnManufacturer);
        $this->post(route('admin.warranty.claims.oem-decision', $partial), [
            'oem_decision' => 'partial', 'oem_approved_amount' => 400,
        ])->assertRedirect();
        $this->assertSame(WarrantyQueue::AwaitingCustomerDecision, $partial->fresh()->queue);

        $denied = $this->caseInQueue(WarrantyQueue::WaitingOnManufacturer);
        $this->post(route('admin.warranty.claims.oem-decision', $denied), [
            'oem_decision' => 'denied',
        ])->assertRedirect();
        $denied->refresh();
        $this->assertSame(WarrantyQueue::AwaitingCustomerDecision, $denied->queue);
        $this->assertNull($denied->reimbursement_expected_amount, 'denied covers nothing');
    }

    public function test_approved_requires_an_amount(): void
    {
        $case = $this->caseInQueue(WarrantyQueue::WaitingOnManufacturer);
        $this->post(route('admin.warranty.claims.oem-decision', $case), ['oem_decision' => 'approved'])
            ->assertSessionHasErrors('oem_approved_amount');
        $this->assertSame(WarrantyQueue::WaitingOnManufacturer, $case->fresh()->queue);
    }

    public function test_customer_proceed_goes_to_repair_decline_closes(): void
    {
        $proceed = $this->caseInQueue(WarrantyQueue::AwaitingCustomerDecision, ['oem_decision' => 'partial', 'oem_approved_amount' => 400, 'reimbursement_expected_amount' => 400]);
        $this->post(route('admin.warranty.claims.customer-decision', $proceed), ['customer_decision' => 'proceed'])->assertRedirect();
        $this->assertSame(WarrantyQueue::ApprovedForRepair, $proceed->fresh()->queue);

        $decline = $this->caseInQueue(WarrantyQueue::AwaitingCustomerDecision, ['oem_decision' => 'denied']);
        $this->post(route('admin.warranty.claims.customer-decision', $decline), ['customer_decision' => 'decline'])->assertRedirect();
        $this->assertSame(WarrantyQueue::Closed, $decline->fresh()->queue);
    }

    public function test_repair_complete_routes_by_expected_reimbursement(): void
    {
        $withReimb = $this->caseInQueue(WarrantyQueue::ApprovedForRepair, ['reimbursement_expected_amount' => 900]);
        $this->post(route('admin.warranty.claims.repair-complete', $withReimb))->assertRedirect();
        $this->assertSame(WarrantyQueue::AwaitingReimbursement, $withReimb->fresh()->queue);

        // No expected reimbursement (e.g. denied + customer paid) → closes.
        $noReimb = $this->caseInQueue(WarrantyQueue::ApprovedForRepair);
        $this->post(route('admin.warranty.claims.repair-complete', $noReimb))->assertRedirect();
        $this->assertSame(WarrantyQueue::Closed, $noReimb->fresh()->queue);
    }

    public function test_reimbursement_records_and_closes(): void
    {
        $case = $this->caseInQueue(WarrantyQueue::AwaitingReimbursement, ['reimbursement_expected_amount' => 900]);

        $this->post(route('admin.warranty.claims.reimbursement', $case), [
            'reimbursement_received_amount' => 875.50,
            'reimbursement_received_at'     => now()->toDateString(),
        ])->assertRedirect();

        $case->refresh();
        $this->assertEqualsWithDelta(875.50, (float) $case->reimbursement_received_amount, 0.001);
        $this->assertNotNull($case->reimbursement_received_at);
        $this->assertSame(WarrantyQueue::Closed, $case->queue);
    }

    public function test_out_of_queue_actions_are_rejected(): void
    {
        // A case still awaiting diagnosis cannot be submitted.
        $case = $this->caseInQueue(WarrantyQueue::AwaitingDiagnosis);
        $this->post(route('admin.warranty.claims.submit', $case), ['oem_submission_reference' => 'X'])->assertRedirect();
        $this->assertSame(WarrantyQueue::AwaitingDiagnosis, $case->fresh()->queue);
        $this->assertNull($case->fresh()->oem_submission_reference);
    }

    public function test_auto_advance_on_linked_ticket_diagnosis_complete(): void
    {
        $case = $this->caseInQueue(WarrantyQueue::AwaitingDiagnosis);
        $ticket = $case->serviceTicket;

        // Bring the ticket to a diagnosable state, then complete diagnosis
        // through the canonical Service endpoint.
        $ticket->startDiagnostic();
        $this->post(route('admin.service-management.tickets.diagnostic.complete', $ticket))->assertRedirect();

        $this->assertSame(WarrantyQueue::ReadyToSubmit, $case->fresh()->queue, 'diagnosis completion should auto-advance the linked case');
    }
}
