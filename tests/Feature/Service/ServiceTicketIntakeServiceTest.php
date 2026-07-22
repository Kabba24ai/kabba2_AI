<?php

namespace Tests\Feature\Service;

use App\Enums\Service\FinancialResponsibility;
use App\Enums\Service\FinancialStatus;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceType;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Service\ServiceSymptom;
use App\Models\Service\ServiceSymptomCategory;
use App\Models\Service\ServiceTicket;
use App\Services\ServiceManagement\ServiceTicketIntakeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ST-1 — the canonical, idempotent Service Ticket intake service. Pins the
 * shared defaults, idempotency dedupe, complaint snapshotting, personnel
 * sync, and that both existing callers (web intake + Warranty) route
 * through it.
 */
class ServiceTicketIntakeServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actor = User::create([
            'first_name' => 'Intake', 'last_name' => 'Actor',
            'email' => 'intake-actor@test.local', 'status' => 'Active',
        ]);
        $this->actingAs($this->actor);
    }

    private function makeEquipment(): Equipment
    {
        $cat = ProductCategory::create(['title' => 'ST1-' . Str::random(5), 'status' => 'Published', 'sort_order' => 1]);

        return Equipment::create([
            'unique_id' => 'st1-eq-' . Str::random(6), 'equipment_name' => 'Test Unit',
            'equipment_id' => 'ST1-' . Str::random(4), 'brand' => 'Test',
            'product_category_id' => $cat->id, 'current_status' => 'available', 'not_for_rent' => 0,
        ]);
    }

    // ── Defaults ────────────────────────────────────────────────────────

    public function test_applies_canonical_defaults_when_attributes_are_minimal(): void
    {
        $ticket = ServiceTicketIntakeService::create(['equipment_id' => $this->makeEquipment()->id]);

        $this->assertSame(ServiceType::CustomerDamageRepair, $ticket->service_type);
        $this->assertSame(ServiceLocation::InShop, $ticket->service_location);
        $this->assertSame(ServicePriority::Normal, $ticket->priority);
        $this->assertSame(RepairStatus::Open, $ticket->repair_status);
        $this->assertSame(FinancialResponsibility::Pending, $ticket->financial_responsibility);
        $this->assertSame(FinancialStatus::NotBillable, $ticket->financial_status);
        $this->assertSame($this->actor->id, $ticket->created_by);
        $this->assertSame($this->actor->id, $ticket->updated_by);
        $this->assertStringStartsWith('SVC-', $ticket->fresh()->ticket_number);
        $this->assertTrue($ticket->wasRecentlyCreated);
    }

    public function test_explicit_attributes_override_defaults(): void
    {
        $ticket = ServiceTicketIntakeService::create([
            'equipment_id'   => $this->makeEquipment()->id,
            'service_type'   => ServiceType::OemWarrantyRepair->value,
            'priority'       => ServicePriority::Emergency->value,
        ]);

        $this->assertSame(ServiceType::OemWarrantyRepair, $ticket->service_type);
        $this->assertSame(ServicePriority::Emergency, $ticket->priority);
    }

    // ── Idempotency ─────────────────────────────────────────────────────

    public function test_same_idempotency_key_returns_the_first_ticket(): void
    {
        $eq = $this->makeEquipment();
        $key = (string) Str::uuid();

        $first  = ServiceTicketIntakeService::create(['equipment_id' => $eq->id], idempotencyKey: $key);
        $second = ServiceTicketIntakeService::create(['equipment_id' => $eq->id], idempotencyKey: $key);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, ServiceTicket::count());
        $this->assertTrue($first->wasRecentlyCreated);
        $this->assertFalse($second->wasRecentlyCreated, 'the deduped call must not have created a row');
    }

    public function test_null_key_never_dedupes(): void
    {
        $eq = $this->makeEquipment();
        ServiceTicketIntakeService::create(['equipment_id' => $eq->id]);
        ServiceTicketIntakeService::create(['equipment_id' => $eq->id]);

        $this->assertSame(2, ServiceTicket::count());
    }

    // ── Extras: complaints + personnel ──────────────────────────────────

    public function test_snapshots_complaints_and_syncs_personnel(): void
    {
        $eq = $this->makeEquipment();
        $cat = ServiceSymptomCategory::create(['name' => 'Engine', 'display_order' => 10, 'is_active' => true]);
        $s1 = ServiceSymptom::create(['service_symptom_category_id' => $cat->id, 'name' => 'Will not start', 'display_order' => 10, 'is_active' => true]);
        $s2 = ServiceSymptom::create(['service_symptom_category_id' => $cat->id, 'name' => 'Low power', 'display_order' => 20, 'is_active' => true]);

        $tech = User::create(['first_name' => 'Tia', 'last_name' => 'Tech', 'email' => 'tia@test.local', 'status' => 'Active']);
        $lead = User::create(['first_name' => 'Lee', 'last_name' => 'Lead', 'email' => 'lee@test.local', 'status' => 'Active']);

        $ticket = ServiceTicketIntakeService::create(
            attributes: ['equipment_id' => $eq->id],
            complaintSymptomIds: [$s1->id, $s2->id],
            personnelIds: [$tech->id, $lead->id],
            teamLeaderId: $lead->id,
        );

        // Complaints snapshot name + category label at creation time.
        $this->assertSame(2, $ticket->complaints()->count());
        $this->assertEqualsCanonicalizing(
            ['Will not start', 'Low power'],
            $ticket->complaints()->pluck('name')->all(),
        );
        $this->assertSame('Engine', $ticket->complaints()->first()->system_group);

        $this->assertSame(2, $ticket->personnel()->count());
        $this->assertSame($lead->id, $ticket->teamLeader?->id);
    }

    public function test_creation_is_atomic_a_bad_complaint_id_rolls_back_nothing_partial(): void
    {
        // A non-existent complaint id simply snapshots nothing (whereIn finds
        // none) — the ticket still creates cleanly. Guards against the loop
        // throwing and leaving a half-written ticket.
        $ticket = ServiceTicketIntakeService::create(
            attributes: ['equipment_id' => $this->makeEquipment()->id],
            complaintSymptomIds: [999999],
        );
        $this->assertSame(0, $ticket->complaints()->count());
        $this->assertSame(1, ServiceTicket::count());
    }

    // ── Callers route through the service ───────────────────────────────

    public function test_web_intake_double_submit_creates_one_ticket(): void
    {
        $eq = $this->makeEquipment();
        $token = (string) Str::uuid();
        // Non-intake path (no order): keeps the request valid with just a
        // fleet equipment unit while still exercising controller → service
        // idempotency.
        $payload = [
            'idempotency_token' => $token,
            'equipment_id' => $eq->id, 'priority' => ServicePriority::Normal->value,
            'customer_complaint' => 'Grinding noise on lift.',
        ];

        $this->post(route('admin.service-management.tickets.store'), $payload)->assertRedirect();
        $this->post(route('admin.service-management.tickets.store'), $payload)->assertRedirect();

        $this->assertSame(1, ServiceTicket::count(), 'same idempotency token must not mint a second ticket');
    }

    public function test_warranty_intake_uses_the_service_and_links_the_ticket(): void
    {
        $token = (string) Str::uuid();
        $customer = \App\Models\Customers\Customer::create([
            'first_name' => 'War', 'last_name' => 'Ranty', 'email' => 'warranty-cust@test.local', 'status' => 'Active',
        ]);
        $payload = [
            'idempotency_token' => $token,
            'path' => 'external', 'customer_id' => $customer->id,
            'manufacturer' => 'Acme', 'model' => 'X100', 'serial_number' => 'SN-1',
            'complaint' => 'Transmission fault under warranty.',
        ];

        $this->post(route('admin.warranty.claims.store'), $payload)->assertRedirect();
        $this->post(route('admin.warranty.claims.store'), $payload)->assertRedirect();

        // One case, one linked ticket — the shared idempotency token dedupes
        // the ticket even across the two case submissions.
        $this->assertSame(1, ServiceTicket::whereNotNull('idempotency_key')->count());
        $ticket = ServiceTicket::firstOrFail();
        $this->assertSame(ServiceType::OemWarrantyRepair, $ticket->service_type);
        $this->assertSame($customer->id, $ticket->customer_id);
    }
}
