<?php

namespace Tests\Feature\Service;

use App\Enums\Service\DiagnosticStatus;
use App\Enums\Service\FinancialResponsibility;
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

class ServiceTicketDiagnosticTest extends TestCase
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
    }

    /** Intake payload without any status/responsibility fields. */
    private function intakePayload(array $overrides = []): array
    {
        return array_merge([
            'service_type'     => ServiceType::InspectionDiagnosis->value,
            'service_location' => ServiceLocation::InShop->value,
            'priority'         => ServicePriority::Normal->value,
            'equipment_id'     => $this->equipment->id,
            'opened_at'        => now()->format('Y-m-d'),
        ], $overrides);
    }

    private function makeDiagnosticTicket(): ServiceTicket
    {
        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.store'), $this->intakePayload());

        return ServiceTicket::latest('id')->first();
    }

    // 1. New ticket defaults to the diagnostic-first state
    public function test_new_ticket_defaults_to_diagnostic_first_state(): void
    {
        $ticket = $this->makeDiagnosticTicket();

        $this->assertSame(RepairStatus::Open, $ticket->repair_status);
        $this->assertSame(DiagnosticStatus::NotStarted, $ticket->diagnostic_status);
        $this->assertSame(ResponsibilityDecision::Pending, $ticket->responsibility_decision);
        $this->assertSame(FinancialResponsibility::Pending, $ticket->financial_responsibility);
        $this->assertSame(FinancialStatus::NotBillable, $ticket->financial_status);
        $this->assertNull($ticket->diagnostic_started_at);
        $this->assertNull($ticket->responsibility_decided_at);
    }

    // 1b. Explicit statuses still respected (existing flows unchanged)
    public function test_explicit_statuses_still_respected_at_creation(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.store'), $this->intakePayload([
                'repair_status'            => RepairStatus::InProgress->value,
                'financial_responsibility' => FinancialResponsibility::CustomerPay->value,
                'financial_status'         => FinancialStatus::ReadyToBill->value,
            ]));

        $ticket = ServiceTicket::latest('id')->first();
        $this->assertSame(RepairStatus::InProgress, $ticket->repair_status);
        $this->assertSame(FinancialResponsibility::CustomerPay, $ticket->financial_responsibility);
    }

    // 2 + 9. Diagnostic can be started; timeline event logged; repair status follows
    public function test_diagnostic_can_be_started(): void
    {
        $ticket = $this->makeDiagnosticTicket();

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.diagnostic.start', $ticket))
            ->assertRedirect();

        $fresh = $ticket->fresh();
        $this->assertSame(DiagnosticStatus::InProgress, $fresh->diagnostic_status);
        $this->assertNotNull($fresh->diagnostic_started_at);
        $this->assertSame(RepairStatus::Diagnosing, $fresh->repair_status);

        $this->assertTrue(
            $ticket->events()->where('event_type', ServiceTicketEventType::DiagnosticStarted->value)->exists()
        );
    }

    // 3 + 9. Diagnostic can be completed; timeline event logged
    public function test_diagnostic_can_be_completed(): void
    {
        $ticket = $this->makeDiagnosticTicket();
        $ticket->startDiagnostic();

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.diagnostic.complete', $ticket))
            ->assertRedirect();

        $fresh = $ticket->fresh();
        $this->assertSame(DiagnosticStatus::Completed, $fresh->diagnostic_status);
        $this->assertNotNull($fresh->diagnostic_completed_at);

        $this->assertTrue(
            $ticket->events()->where('event_type', ServiceTicketEventType::DiagnosticCompleted->value)->exists()
        );
    }

    // 4. Responsibility decision blocked until diagnostic completed
    public function test_responsibility_decision_requires_completed_diagnostic(): void
    {
        $ticket = $this->makeDiagnosticTicket();

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.responsibility.decide', $ticket), [
                'responsibility_decision' => $this->responsibilityDecisionId(ResponsibilityDecision::CustomerPay->value),
            ])
            ->assertRedirect();

        // Not completed → decision unchanged
        $this->assertFalse($ticket->fresh()->isResponsibilityDecided());

        $ticket->fresh()->completeDiagnostic();

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.responsibility.decide', $ticket), [
                'responsibility_decision' => $this->responsibilityDecisionId(ResponsibilityDecision::CustomerPay->value),
            ]);

        $fresh = $ticket->fresh();
        $this->assertTrue($fresh->isResponsibilityDecided());
        $this->assertSame('customer_pay', $fresh->responsibilityDecision->key);
        $this->assertNotNull($fresh->responsibility_decided_at);
        $this->assertSame($this->admin->id, $fresh->responsibility_decided_by);

        // A non-existent / inactive decision can never be chosen
        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.responsibility.decide', $ticket), [
                'responsibility_decision' => 999999,
            ])
            ->assertSessionHasErrors('responsibility_decision');
    }

    // 5. Customer Pay decision updates financial responsibility (and later billing works)
    public function test_customer_pay_decision_updates_financial_responsibility(): void
    {
        $ticket = $this->makeDiagnosticTicket();
        $ticket->completeDiagnostic();

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.responsibility.decide', $ticket), [
                'responsibility_decision' => $this->responsibilityDecisionId(ResponsibilityDecision::CustomerPay->value),
            ]);

        $fresh = $ticket->fresh();
        $this->assertSame(FinancialResponsibility::CustomerPay, $fresh->financial_responsibility);

        // Downstream billing-prep automation now applies to this ticket
        $fresh->laborEntries()->create(['labor_date' => now()->toDateString(), 'hours' => 2, 'labor_rate' => 95, 'billable' => true]);
        $fresh->fresh()->transitionTo(RepairStatus::Completed);
        $this->assertSame(FinancialStatus::ReadyToBill, $ticket->fresh()->financial_status);
    }

    // 6. OEM Warranty decision — responsibility set, no customer charge rows
    public function test_oem_warranty_decision_sets_responsibility_without_customer_charge(): void
    {
        $extraChargesBefore = DB::table('order_extra_charges')->count();

        $ticket = $this->makeDiagnosticTicket();
        $ticket->completeDiagnostic();

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.responsibility.decide', $ticket), [
                'responsibility_decision' => $this->responsibilityDecisionId(ResponsibilityDecision::OemWarranty->value),
            ]);

        $fresh = $ticket->fresh();
        $this->assertSame(FinancialResponsibility::OemWarranty, $fresh->financial_responsibility);

        // Billable work feeds the warranty claim total, never customer billing
        $fresh->laborEntries()->create(['labor_date' => now()->toDateString(), 'hours' => 3, 'labor_rate' => 100, 'billable' => true]);
        $fresh->fresh()->transitionTo(RepairStatus::Completed);

        $completed = $ticket->fresh();
        $this->assertSame(FinancialStatus::NotBillable, $completed->financial_status);
        $this->assertEquals(300.00, $completed->warranty_claim_total);
        $this->assertSame($extraChargesBefore, DB::table('order_extra_charges')->count());
    }

    // 7. Internal decision does not become ready to bill
    public function test_internal_decision_does_not_become_ready_to_bill(): void
    {
        $ticket = $this->makeDiagnosticTicket();
        $ticket->completeDiagnostic();

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.responsibility.decide', $ticket), [
                'responsibility_decision' => $this->responsibilityDecisionId(ResponsibilityDecision::InternalExpense->value),
            ]);

        $fresh = $ticket->fresh();
        $fresh->laborEntries()->create(['labor_date' => now()->toDateString(), 'hours' => 4, 'labor_rate' => 90, 'billable' => true]);
        $fresh->fresh()->transitionTo(RepairStatus::Completed);

        $this->assertSame(FinancialStatus::NotBillable, $ticket->fresh()->financial_status);
    }

    // 7b. No Problem Found / Not Repairable leave financial responsibility untouched
    public function test_no_payer_decisions_leave_financial_responsibility_untouched(): void
    {
        $ticket = $this->makeDiagnosticTicket();
        $ticket->completeDiagnostic();

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.responsibility.decide', $ticket), [
                'responsibility_decision' => $this->responsibilityDecisionId(ResponsibilityDecision::NoProblemFound->value),
            ]);

        $fresh = $ticket->fresh();
        $this->assertSame('no_problem_found', $fresh->responsibilityDecision->key);
        $this->assertSame(FinancialResponsibility::Pending, $fresh->financial_responsibility);

        // Ticket can still be closed
        $fresh->transitionTo(RepairStatus::Completed);
        $fresh->fresh()->transitionTo(RepairStatus::Closed);
        $this->assertNotNull($ticket->fresh()->closed_at);
    }

    // 8. Diagnostic fee state stored, event logged, no payment records
    public function test_diagnostic_fee_fields_stored_without_payment_records(): void
    {
        $paymentsBefore = DB::table('order_payments')->count();

        $ticket = $this->makeDiagnosticTicket();

        $this->actingAs($this->admin)
            ->put(route('admin.service-management.tickets.diagnostic.update', $ticket), [
                'diagnostic_fee_required'   => 1,
                'diagnostic_fee_amount'     => 125.00,
                'diagnostic_fee_paid'       => 1,
                'diagnostic_fee_creditable' => 1,
                'diagnostic_fee_credited'   => 0,
                'warranty_possible'         => 1,
                'recommended_repair'        => 'Replace final drive under warranty if approved.',
                'estimated_labor_hours'     => 6,
                'estimated_parts_total'     => 1800,
                'estimated_repair_total'    => 2500,
            ])
            ->assertRedirect();

        $fresh = $ticket->fresh();
        $this->assertTrue($fresh->diagnostic_fee_required);
        $this->assertEquals(125.00, (float) $fresh->diagnostic_fee_amount);
        $this->assertTrue($fresh->diagnostic_fee_paid);
        $this->assertTrue($fresh->diagnostic_fee_creditable);
        $this->assertFalse($fresh->diagnostic_fee_credited);
        $this->assertTrue($fresh->warranty_possible);
        $this->assertEquals(2500.00, (float) $fresh->estimated_repair_total);

        $feeEvent = $ticket->events()->where('event_type', ServiceTicketEventType::DiagnosticFeeUpdated->value)->first();
        $this->assertNotNull($feeEvent);
        $this->assertStringContainsString('125.00', $feeEvent->notes);

        $this->assertSame($paymentsBefore, DB::table('order_payments')->count());
    }

    // 9b. Responsibility decision logs a timeline event with old/new values
    public function test_responsibility_decision_creates_timeline_event(): void
    {
        $ticket = $this->makeDiagnosticTicket();
        $ticket->completeDiagnostic();

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.responsibility.decide', $ticket), [
                'responsibility_decision' => $this->responsibilityDecisionId(ResponsibilityDecision::Goodwill->value),
            ]);

        $event = $ticket->events()
            ->where('event_type', ServiceTicketEventType::ResponsibilityDecisionChanged->value)
            ->first();
        $this->assertNotNull($event);
        $this->assertSame('Pending', $event->old_value);
        $this->assertSame('Goodwill', $event->new_value);
    }

    // Detail page renders the diagnostic card in each state
    public function test_detail_page_renders_diagnostic_card(): void
    {
        $ticket = $this->makeDiagnosticTicket();

        $this->actingAs($this->admin)
            ->get(route('admin.service-management.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Diagnostic')
            ->assertSee('Start Diagnostic')
            ->assertSee('Complete the diagnostic to set the responsibility decision.');

        $ticket->startDiagnostic();
        $this->actingAs($this->admin)
            ->get(route('admin.service-management.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Complete Diagnostic');

        $ticket->fresh()->completeDiagnostic();
        $this->actingAs($this->admin)
            ->get(route('admin.service-management.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Set Responsibility');
    }
}
