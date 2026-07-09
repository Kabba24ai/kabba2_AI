<?php

namespace Tests\Feature\Warranty;

use App\Enums\Service\ServiceType;
use App\Enums\Warranty\WarrantyCaseEventType;
use App\Enums\Warranty\WarrantyPath;
use App\Enums\Warranty\WarrantyQueue;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Service\ServiceTicket;
use App\Models\Warranty\WarrantyCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Warranty Module Phase 1 — case foundation + intake. Warranty authorizes
 * work; the linked Service Ticket performs it. Fully additive: no existing
 * tables, controllers, billing logic, or service workflows are touched.
 */
class WarrantyCaseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Equipment $lift;
    private int $customerId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'test-admin', 'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]);

        $this->lift = Equipment::create([
            'unique_id' => 'test-lift', 'equipment_name' => 'Boom Lift', 'equipment_id' => 'BL-1',
            'brand' => 'Genie', 'serial_number' => 'GS70-11111',
        ]);

        $this->customerId = DB::table('customers')->insertGetId([
            'unique_id' => 'test-cust', 'first_name' => 'Warren', 'last_name' => 'Tee',
        ]);

        $this->actingAs($this->admin);
    }

    private function externalPayload(array $overrides = []): array
    {
        return array_merge([
            'path'                     => 'external',
            'customer_id'              => $this->customerId,
            'manufacturer'             => 'Kubota',
            'model'                    => 'U35-4',
            'serial_number'            => 'KBCZ061-72841',
            'engine_serial_number'     => 'D1803-77821',
            'has_hour_meter'           => 1,
            'hours'                    => 612,
            'complaint'                => 'Shuts down under load after twenty minutes.',
            'diagnostic_fee_amount'    => '95.00',
            'diagnostic_fee_taxable'   => 1,
        ], $overrides);
    }

    private function makeCase(array $overrides = []): WarrantyCase
    {
        $this->post(route('admin.warranty.claims.store'), $this->externalPayload($overrides));
        session()->forget('flash_notification');

        return WarrantyCase::latest('id')->firstOrFail();
    }

    // ── Creation ─────────────────────────────────────────────────────

    public function test_intake_page_renders_all_five_sections(): void
    {
        $this->get(route('admin.warranty.claims.create'))
            ->assertOk()
            ->assertSee('New Warranty Case')
            ->assertSee('Warranty Path')
            ->assertSee('Internal Equipment Warranty')
            ->assertSee('External Customer Warranty')
            ->assertSee('Equipment Identity')
            ->assertSee('Ownership')
            ->assertSee('Complaint')
            ->assertSee('Diagnostic Fee')
            ->assertSee('What happens next')
            ->assertDontSee('Settlement')
            ->assertDontSee('Billing Engine');
    }

    public function test_external_case_creates_case_and_linked_service_ticket(): void
    {
        $response = $this->post(route('admin.warranty.claims.store'), $this->externalPayload());

        $case = WarrantyCase::latest('id')->firstOrFail();
        $response->assertRedirect(route('admin.warranty.claims.show', $case));

        $this->assertSame('WC-' . str_pad((string) $case->id, 5, '0', STR_PAD_LEFT), $case->case_number);
        $this->assertSame(WarrantyQueue::NewIntake, $case->queue);
        $this->assertSame(WarrantyPath::External, $case->path);
        $this->assertSame($this->customerId, $case->customer_id);
        $this->assertNull($case->equipment_id);
        $this->assertSame('KBCZ061-72841', $case->serial_number);
        $this->assertSame(612, $case->hours);
        $this->assertSame('95.00', (string) $case->diagnostic_fee_amount);
        $this->assertNull($case->diagnostic_fee_collected_at);

        // The hinge: a linked Service Ticket, opened through the normal lifecycle
        $ticket = $case->serviceTicket;
        $this->assertInstanceOf(ServiceTicket::class, $ticket);
        $this->assertSame(ServiceType::OemWarrantyRepair, $ticket->service_type);
        $this->assertSame($this->customerId, $ticket->customer_id);
        $this->assertSame($case->complaint, $ticket->customer_complaint);

        $this->assertTrue($case->events->contains(fn ($e) => $e->event_type === WarrantyCaseEventType::Created));
    }

    public function test_internal_case_links_fleet_equipment_and_skips_fee(): void
    {
        $case = $this->makeCase([
            'path'         => 'internal',
            'customer_id'  => null,
            'equipment_id' => $this->lift->id,
            'manufacturer' => 'Genie',
            'model'        => 'S-70',
            'serial_number' => 'GS70-11111',
            'diagnostic_fee_amount' => null,
        ]);

        $this->assertSame(WarrantyPath::Internal, $case->path);
        $this->assertSame($this->lift->id, $case->equipment_id);
        $this->assertNull($case->customer_id);
        $this->assertFalse($case->feeApplies());
        $this->assertTrue($case->feeSettled());
        $this->assertSame('N/A — internal', $case->feeStatusLabel());
        $this->assertSame($this->lift->id, $case->serviceTicket->equipment_id);
    }

    public function test_validation_enforces_path_specific_requirements(): void
    {
        // External requires customer + fee amount
        $this->post(route('admin.warranty.claims.store'), $this->externalPayload([
            'customer_id' => null, 'diagnostic_fee_amount' => null,
        ]))->assertSessionHasErrors(['customer_id', 'diagnostic_fee_amount']);

        // Internal requires equipment
        $this->post(route('admin.warranty.claims.store'), $this->externalPayload([
            'path' => 'internal', 'customer_id' => null, 'equipment_id' => null,
        ]))->assertSessionHasErrors(['equipment_id']);

        // Identity + complaint always required; hours required with meter
        $this->post(route('admin.warranty.claims.store'), $this->externalPayload([
            'manufacturer' => '', 'model' => '', 'serial_number' => '', 'complaint' => '', 'hours' => null,
        ]))->assertSessionHasErrors(['manufacturer', 'model', 'serial_number', 'complaint', 'hours']);

        $this->assertSame(0, WarrantyCase::count());
        $this->assertSame(0, ServiceTicket::count(), 'No orphan tickets from failed validation');
    }

    // ── Diagnostic fee gate ──────────────────────────────────────────

    public function test_intake_cannot_complete_until_external_fee_is_settled(): void
    {
        $case = $this->makeCase();

        $this->post(route('admin.warranty.claims.complete-intake', $case));
        $this->assertSame(WarrantyQueue::NewIntake, $case->fresh()->queue);

        $this->put(route('admin.warranty.claims.fee.update', $case), ['action' => 'collected']);
        $fresh = $case->fresh();
        $this->assertNotNull($fresh->diagnostic_fee_collected_at);
        $this->assertTrue($fresh->events->contains(fn ($e) => $e->event_type === WarrantyCaseEventType::FeeUpdated));

        $this->post(route('admin.warranty.claims.complete-intake', $case));
        $fresh = $case->fresh();
        $this->assertSame(WarrantyQueue::AwaitingDiagnosis, $fresh->queue);
        $this->assertNotNull($fresh->awaiting_diagnosis_at);
        $this->assertTrue($fresh->events->contains(fn ($e) => $e->event_type === WarrantyCaseEventType::QueueChanged));
    }

    public function test_manager_waiver_also_unlocks_intake_completion(): void
    {
        $case = $this->makeCase();

        $this->put(route('admin.warranty.claims.fee.update', $case), ['action' => 'waived']);
        $this->assertSame($this->admin->id, $case->fresh()->diagnostic_fee_waived_by);
        $this->assertSame('Waived', $case->fresh()->feeStatusLabel());

        $this->post(route('admin.warranty.claims.complete-intake', $case));
        $this->assertSame(WarrantyQueue::AwaitingDiagnosis, $case->fresh()->queue);
    }

    public function test_internal_case_completes_intake_without_fee(): void
    {
        $case = $this->makeCase([
            'path' => 'internal', 'customer_id' => null, 'equipment_id' => $this->lift->id,
            'diagnostic_fee_amount' => null,
        ]);

        $this->post(route('admin.warranty.claims.complete-intake', $case));
        $this->assertSame(WarrantyQueue::AwaitingDiagnosis, $case->fresh()->queue);

        // Fee endpoint refuses internal cases
        $this->put(route('admin.warranty.claims.fee.update', $case), ['action' => 'collected']);
        $this->assertNull($case->fresh()->diagnostic_fee_collected_at);
    }

    // ── Queue engine ─────────────────────────────────────────────────

    public function test_queues_cannot_be_skipped(): void
    {
        $case = $this->makeCase();

        $this->assertFalse($case->transitionTo(WarrantyQueue::ReadyToSubmit));
        $this->assertFalse($case->transitionTo(WarrantyQueue::ApprovedForRepair));
        $this->assertSame(WarrantyQueue::NewIntake, $case->fresh()->queue);
    }

    // ── Pages ────────────────────────────────────────────────────────

    public function test_case_page_shows_ribbon_context_linkage_and_timeline(): void
    {
        $case = $this->makeCase();

        $this->get(route('admin.warranty.claims.show', $case))
            ->assertOk()
            ->assertSee($case->case_number)
            ->assertSee('Ready to Submit')            // ribbon stage
            ->assertSee('Waiting on Manufacturer')    // ribbon stage
            ->assertSee('Equipment Identity')
            ->assertSee('KBCZ061-72841')
            ->assertSee('Current Task')
            ->assertSee('Collect Diagnostic Fee')     // next required action
            ->assertSee('Linked Service Ticket')
            ->assertSee($case->serviceTicket->ticket_number)
            ->assertSee('Financial Snapshot')
            ->assertSee('Activity Timeline');
    }

    public function test_dashboard_lists_cases_with_kpis_and_filters(): void
    {
        $external = $this->makeCase();
        $internal = $this->makeCase([
            'path' => 'internal', 'customer_id' => null, 'equipment_id' => $this->lift->id,
            'manufacturer' => 'Genie', 'serial_number' => 'GS70-11111', 'diagnostic_fee_amount' => null,
        ]);

        $this->get(route('admin.warranty.claims.index'))
            ->assertOk()
            ->assertSee('Warranty Claims')
            ->assertSee('New Warranty Case')
            ->assertSee($external->case_number)
            ->assertSee($internal->case_number)
            ->assertSee('Next: Collect Diagnostic Fee')
            ->assertSee('Next: Complete Intake');

        // Path filter
        $this->get(route('admin.warranty.claims.index', ['path' => 'internal']))
            ->assertSee($internal->case_number)
            ->assertDontSee($external->case_number);

        // Queue filter
        $internal->fresh()->transitionTo(WarrantyQueue::AwaitingDiagnosis);
        $this->get(route('admin.warranty.claims.index', ['queue' => 'awaiting_diagnosis']))
            ->assertSee($internal->case_number)
            ->assertDontSee($external->case_number);

        // Search by serial
        $this->get(route('admin.warranty.claims.index', ['search' => 'KBCZ061']))
            ->assertSee($external->case_number)
            ->assertDontSee($internal->case_number);
    }
}
