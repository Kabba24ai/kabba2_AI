<?php

namespace Tests\Feature\Service;

use App\Enums\Service\FinancialResponsibility;
use App\Enums\Service\FinancialStatus;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ServiceChargeType;
use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServiceMediaCategory;
use App\Enums\Service\ServiceMediaType;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceTicketEventType;
use App\Enums\Service\ServiceType;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketPart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceTicketRepairRecordTest extends TestCase
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
    }

    private function makeTicket(array $overrides = []): ServiceTicket
    {
        return ServiceTicket::create(array_merge([
            'service_type'             => ServiceType::CustomerDamageRepair->value,
            'service_location'         => ServiceLocation::InShop->value,
            'priority'                 => ServicePriority::Normal->value,
            'repair_status'            => RepairStatus::InProgress->value,
            'financial_responsibility' => FinancialResponsibility::CustomerPay->value,
            'financial_status'         => FinancialStatus::NotBillable->value,
            'equipment_id'             => $this->equipment->id,
            'opened_at'                => now(),
        ], $overrides));
    }

    private function assertHasEvent(ServiceTicket $ticket, ServiceTicketEventType $type, ?string $notesContains = null): void
    {
        $events = $ticket->events()->where('event_type', $type->value)->get();
        $this->assertTrue($events->isNotEmpty(), "Expected a {$type->value} event.");

        if ($notesContains !== null) {
            $this->assertTrue(
                $events->contains(fn ($e) => str_contains((string) $e->notes, $notesContains)),
                "Expected a {$type->value} event whose notes contain '{$notesContains}'."
            );
        }
    }

    // 1. Part belongs to ticket
    public function test_part_belongs_to_ticket(): void
    {
        $ticket = $this->makeTicket();

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.parts.store', $ticket), [
                'part_number' => 'HH-3848',
                'description' => 'Hydraulic Hose',
                'quantity'    => 1,
                'unit_cost'   => 42.10,
            ])
            ->assertRedirect();

        $part = ServiceTicketPart::first();
        $this->assertSame($ticket->id, $part->ticket->id);
        $this->assertTrue($ticket->partsUsed()->pluck('id')->contains($part->id));
    }

    // 2. Part totals calculate correctly
    public function test_part_totals_calculate_correctly(): void
    {
        $ticket = $this->makeTicket();

        $part = $ticket->partsUsed()->create([
            'description'    => 'Drive sprocket',
            'quantity'       => 2,
            'unit_cost'      => 150.25,
            'customer_price' => 219.99,
        ]);
        $this->assertEquals(300.50, $part->cost_total);
        $this->assertEquals(439.98, $part->customer_total);

        // Nullable prices → null line totals, excluded from ticket totals
        $ticket->partsUsed()->create(['description' => 'Shop-made bracket', 'quantity' => 1]);

        $fresh = $ticket->fresh();
        $this->assertEquals(300.50, $fresh->parts_cost_total);
        $this->assertEquals(439.98, $fresh->parts_customer_total);
    }

    // 3. Adding/removing a part creates timeline events
    public function test_part_add_and_remove_create_timeline_events(): void
    {
        $ticket = $this->makeTicket();

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.parts.store', $ticket), [
                'description' => 'Hydraulic Hose', 'quantity' => 1,
            ]);
        $this->assertHasEvent($ticket, ServiceTicketEventType::PartAdded, 'Hydraulic Hose');

        $part = $ticket->partsUsed()->first();
        $this->actingAs($this->admin)
            ->delete(route('admin.service-management.tickets.parts.destroy', [$ticket, $part]))
            ->assertRedirect();
        $this->assertHasEvent($ticket, ServiceTicketEventType::PartRemoved, 'Hydraulic Hose');

        $event = $ticket->events()->where('event_type', ServiceTicketEventType::PartAdded->value)->first();
        $this->assertSame($this->admin->id, $event->user_id);
    }

    // 4 + 5. Media upload: belongs to ticket, stored via Kabba pipeline, event logged
    public function test_media_upload_belongs_to_ticket_and_creates_event(): void
    {
        Storage::fake('public_asset');
        $ticket = $this->makeTicket();

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.media.store', $ticket), [
                'file'     => UploadedFile::fake()->image('broken-hose.jpg', 800, 600),
                'category' => ServiceMediaCategory::BeforeRepair->value,
                'notes'    => 'Damage on arrival',
            ])
            ->assertRedirect();

        $media = $ticket->media()->first();
        $this->assertNotNull($media);
        $this->assertSame($ticket->id, $media->ticket->id);
        $this->assertSame(ServiceMediaType::Image, $media->media_type);
        $this->assertSame(ServiceMediaCategory::BeforeRepair, $media->category);
        $this->assertSame('broken-hose.jpg', $media->original_filename);
        $this->assertSame($this->admin->id, $media->uploaded_by);

        // Physical file went through MediaHelper onto the public_asset disk
        Storage::disk('public_asset')->assertExists($media->file_path);
        $this->assertNotNull($media->media_id);

        $this->assertHasEvent($ticket, ServiceTicketEventType::MediaUploaded, 'broken-hose.jpg');
    }

    // 6. Removing media creates a timeline event and deletes the stored file
    public function test_media_removal_creates_event_and_deletes_file(): void
    {
        Storage::fake('public_asset');
        $ticket = $this->makeTicket();

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.media.store', $ticket), [
                'file'     => UploadedFile::fake()->create('warranty-bulletin.pdf', 100, 'application/pdf'),
                'category' => ServiceMediaCategory::WarrantyDocumentation->value,
            ]);

        $media = $ticket->media()->first();
        $path  = $media->file_path;
        $this->assertSame(ServiceMediaType::Pdf, $media->media_type);

        $this->actingAs($this->admin)
            ->delete(route('admin.service-management.tickets.media.destroy', [$ticket, $media]))
            ->assertRedirect();

        Storage::disk('public_asset')->assertMissing($path);
        $this->assertSoftDeleted('service_ticket_media', ['id' => $media->id]);
        $this->assertHasEvent($ticket, ServiceTicketEventType::MediaRemoved, 'warranty-bulletin.pdf');
    }

    // 7. Ticket creation + status changes create timeline events
    public function test_creation_and_status_changes_create_timeline_events(): void
    {
        $ticket = $this->actingAs($this->admin)->makeTicket(['repair_status' => RepairStatus::Diagnosing->value]);
        $this->assertHasEvent($ticket, ServiceTicketEventType::TicketCreated);

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.status', $ticket), [
                'repair_status'        => RepairStatus::WaitingOnParts->value,
                'blocked_reason'       => 'Backorder',
                'expected_action_date' => now()->addDays(3)->format('Y-m-d'),
            ]);

        $event = $ticket->events()->where('event_type', ServiceTicketEventType::StatusChanged->value)->first();
        $this->assertNotNull($event);
        $this->assertSame('Diagnosing', $event->old_value);
        $this->assertSame('Waiting on Parts', $event->new_value);

        // Completed and Closed get their own event types
        $ticket->fresh()->transitionTo(RepairStatus::Completed);
        $this->assertHasEvent($ticket, ServiceTicketEventType::Completed);

        $ticket->fresh()->transitionTo(RepairStatus::Closed);
        $this->assertHasEvent($ticket, ServiceTicketEventType::Closed);

        $ticket->fresh()->transitionTo(RepairStatus::Open);
        $this->assertHasEvent($ticket, ServiceTicketEventType::Reopened);
    }

    // 7b. Financial status change logs an event
    public function test_financial_status_change_creates_event(): void
    {
        $ticket = $this->makeTicket();
        $ticket->laborEntries()->create(['labor_date' => '2026-07-05', 'hours' => 1, 'labor_rate' => 100, 'billable' => true]);

        $ticket->fresh()->transitionTo(RepairStatus::Completed);

        $event = $ticket->events()->where('event_type', ServiceTicketEventType::FinancialStatusChanged->value)->first();
        $this->assertNotNull($event);
        $this->assertSame('Not Billable', $event->old_value);
        $this->assertSame('Ready to Bill', $event->new_value);
    }

    // 8. Personnel add/remove creates timeline events
    public function test_personnel_sync_creates_timeline_events(): void
    {
        $tech = User::create([
            'unique_id' => 'test-tech', 'employee_code' => '02',
            'first_name' => 'Tech', 'last_name' => 'One',
            'email' => 'tech@test.local', 'status' => 'Active',
        ]);

        $ticket = $this->makeTicket();

        $this->actingAs($this->admin);
        $ticket->syncPersonnel([$tech->id]);
        $this->assertHasEvent($ticket, ServiceTicketEventType::PersonnelAdded, 'Tech One');

        $ticket->syncPersonnel([]);
        $this->assertHasEvent($ticket, ServiceTicketEventType::PersonnelRemoved, 'Tech One');

        // No-change sync logs nothing new
        $count = $ticket->events()->count();
        $ticket->syncPersonnel([]);
        $this->assertSame($count, $ticket->events()->count());
    }

    // 9. Labor add/remove creates timeline events
    public function test_labor_add_and_remove_create_timeline_events(): void
    {
        $ticket = $this->makeTicket();

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.labor.store', $ticket), [
                'employee_id' => $this->admin->id, 'labor_date' => '2026-07-05', 'hours' => 2.5,
            ]);
        $this->assertHasEvent($ticket, ServiceTicketEventType::LaborAdded, '2.5h');

        $entry = $ticket->laborEntries()->first();
        $this->actingAs($this->admin)
            ->delete(route('admin.service-management.tickets.labor.destroy', [$ticket, $entry]));
        $this->assertHasEvent($ticket, ServiceTicketEventType::LaborRemoved, '2.5h');
    }

    // 10. Charge line add/remove creates timeline events
    public function test_charge_line_add_and_remove_create_timeline_events(): void
    {
        $ticket = $this->makeTicket();

        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.charges.store', $ticket), [
                'charge_type' => ServiceChargeType::Travel->value,
                'description' => 'Site trip', 'quantity' => 1, 'unit_amount' => 75,
            ]);
        $this->assertHasEvent($ticket, ServiceTicketEventType::ChargeLineAdded, 'Site trip');

        $line = $ticket->chargeLines()->first();
        $this->actingAs($this->admin)
            ->delete(route('admin.service-management.tickets.charges.destroy', [$ticket, $line]));
        $this->assertHasEvent($ticket, ServiceTicketEventType::ChargeLineRemoved, 'Site trip');
    }

    // 11. Dashboard still loads; detail page renders the new sections
    public function test_dashboard_and_detail_page_still_load(): void
    {
        $ticket = $this->makeTicket();
        $ticket->partsUsed()->create([
            'part_number' => 'HH-3848', 'description' => 'Hydraulic Hose',
            'quantity' => 1, 'unit_cost' => 42.10, 'customer_price' => 79.99,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.service-management.overview'))
            ->assertOk()
            ->assertSee('Service Operations');

        $this->actingAs($this->admin)
            ->get(route('admin.service-management.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Parts Used')
            ->assertSee('Hydraulic Hose')
            ->assertSee('Media')
            ->assertSee('Timeline')
            ->assertSee('Ticket Created');
    }

    // 13. No inventory deduction when adding parts
    public function test_no_inventory_deduction_when_adding_parts(): void
    {
        $partsTableBefore = DB::table('parts')->count();

        $ticket = $this->makeTicket();
        $this->actingAs($this->admin)
            ->post(route('admin.service-management.tickets.parts.store', $ticket), [
                'part_number' => 'ANY-123', 'description' => 'Not an inventory part', 'quantity' => 5,
            ])
            ->assertRedirect();

        // Service parts are free-form records — the parts module is untouched.
        $this->assertSame($partsTableBefore, DB::table('parts')->count());
        $this->assertDatabaseCount('service_ticket_parts', 1);
    }

    // 14. No Order Extra Payments rows in this phase
    public function test_no_order_extra_payment_rows_created(): void
    {
        $extraChargesBefore = DB::table('order_extra_charges')->count();
        $paymentsBefore     = DB::table('order_payments')->count();

        $ticket = $this->makeTicket();
        $ticket->partsUsed()->create(['description' => 'Part', 'quantity' => 1, 'customer_price' => 100]);
        $ticket->chargeLines()->create([
            'charge_type' => ServiceChargeType::Parts->value,
            'description' => 'Part charge', 'unit_amount' => 100,
        ]);
        $ticket->fresh()->transitionTo(RepairStatus::Completed);

        $this->assertSame($extraChargesBefore, DB::table('order_extra_charges')->count());
        $this->assertSame($paymentsBefore, DB::table('order_payments')->count());
    }
}
