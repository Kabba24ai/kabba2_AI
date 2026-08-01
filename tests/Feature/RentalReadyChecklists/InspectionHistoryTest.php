<?php

namespace Tests\Feature\RentalReadyChecklists;

use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestionAnswer;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplateQuestion;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Services\ChecklistManagement\RentalReadyInspectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 2B — read-only equipment Rental Ready history + inspection detail.
 * Proves: every lifecycle state is listed; lifecycle and result render
 * separately; the detail reconstructs from the immutable snapshot and stays
 * correct after the master checklist is edited; drafts are clearly labelled.
 */
class InspectionHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $inspector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create(['first_name' => 'Hist', 'last_name' => 'Admin', 'email' => 'hist-admin@example.com', 'password' => bcrypt('x')]);
        $this->inspector = User::create(['first_name' => 'Yard', 'last_name' => 'Tech', 'email' => 'hist-tech@example.com', 'password' => bcrypt('x'), 'status' => 'Active']);
    }

    /** @return array{0: ChecklistMaster, 1: RentalReadyChecklistQuestion, 2: array<string,RentalReadyChecklistQuestionAnswer>} */
    private function makeTemplate(): array
    {
        $category = RentalReadyChecklistCategory::create(['category_name' => 'Engine Bay']);
        $required = RentalReadyChecklistQuestion::create(['question_name' => 'Coolant Level', 'category_id' => $category->id, 'required_question' => true]);
        $answers = [
            'Rental Ready' => RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'Full', 'question_id' => $required->id, 'type' => 'Rental Ready', 'index_number' => 1]),
            'Damaged' => RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'Empty', 'question_id' => $required->id, 'type' => 'Damaged', 'index_number' => 2]),
            'Maint. Hold' => RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'Low', 'question_id' => $required->id, 'type' => 'Maint. Hold', 'index_number' => 3]),
        ];
        $template = RentalReadyChecklistTemplate::create(['template_name' => 'Hist Template', 'active_template' => true]);
        RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $required->id, 'index_number' => 1]);
        $master = ChecklistMaster::create(['checklist_system_name' => 'Hist Master', 'rental_ready_template_id' => $template->id]);

        return [$master, $required, $answers];
    }

    private function makeEquipment(ChecklistMaster $master, string $status = 'available'): Equipment
    {
        return Equipment::create(['equipment_name' => 'Hist Loader', 'equipment_id' => 'EQP-H-' . uniqid(), 'brand' => 'T', 'current_status' => $status, 'checklist_master_id' => $master->id]);
    }

    private function record(Equipment $equipment, RentalReadyChecklistQuestion $q, RentalReadyChecklistQuestionAnswer $a): EquipmentRentalReadyTemplate
    {
        return app(RentalReadyInspectionService::class)->record(
            equipment: $equipment->fresh(),
            performedBy: $this->inspector,
            actor: $this->admin,
            source: 'web',
            answers: [['question_unique_id' => $q->unique_id, 'answer_unique_id' => $a->unique_id, 'note' => 'seen at inspection']],
            equipmentHours: 100,
            generalNotes: null,
        )->template;
    }

    /** A raw row in a terminal lifecycle state the write path doesn't produce directly. */
    private function rawRow(Equipment $equipment, string $lifecycle): EquipmentRentalReadyTemplate
    {
        return EquipmentRentalReadyTemplate::create([
            'equipment_id' => $equipment->id,
            'employee_name' => 'Legacy Inspector',
            'inspection_date' => now()->toDateString(),
            'inspection_time' => now()->format('H:i'),
            'equipment_hours' => 50,
            'lifecycle_status' => $lifecycle,
            'status' => 'Draft',
        ]);
    }

    private function historyUrl(Equipment $e): string
    {
        return route('admin.checklist-management.equipment-management.rental-ready-history', $e->unique_id);
    }

    public function test_history_lists_every_lifecycle_state(): void
    {
        [$m, $q, $a] = $this->makeTemplate();
        $equipment = $this->makeEquipment($m, 'maintenance');

        $completed = $this->record($equipment, $q, $a['Rental Ready']);   // completed / rental_ready
        $voided = $this->rawRow($equipment, 'voided');
        $superseded = $this->rawRow($equipment, 'superseded');
        $abandoned = $this->rawRow($equipment, 'abandoned');
        $draft = $this->rawRow($equipment, 'draft');

        $res = $this->actingAs($this->admin)->get($this->historyUrl($equipment))->assertOk();

        foreach (['Completed', 'Voided', 'Superseded', 'Abandoned', 'Draft'] as $label) {
            $res->assertSee($label);
        }
        // Every inspection is a distinct row of record (View Inspection link per row).
        foreach ([$completed, $voided, $superseded, $abandoned, $draft] as $row) {
            $res->assertSee(route('admin.checklist-management.equipment-management.rental-ready-history.show', [$equipment->unique_id, $row->unique_id]), false);
        }
    }

    public function test_lifecycle_and_result_are_shown_separately(): void
    {
        [$m, $q, $a] = $this->makeTemplate();
        $equipment = $this->makeEquipment($m, 'available');
        $this->record($equipment, $q, $a['Maint. Hold']); // completed / maintenance_hold

        $this->actingAs($this->admin)->get($this->historyUrl($equipment))->assertOk()
            ->assertSee('Completed')          // lifecycle
            ->assertSee('Maintenance Hold');  // result — distinct from lifecycle
    }

    public function test_detail_reconstructs_from_snapshot_and_survives_master_edits(): void
    {
        [$m, $q, $a] = $this->makeTemplate();
        $equipment = $this->makeEquipment($m, 'maintenance');
        $inspection = $this->record($equipment, $q, $a['Rental Ready']);

        // Now the checklist master is edited AFTER the inspection.
        $q->update(['question_name' => 'RENAMED QUESTION']);
        $a['Rental Ready']->update(['answer_name' => 'RENAMED ANSWER']);

        $res = $this->actingAs($this->admin)->get(route('admin.checklist-management.equipment-management.rental-ready-history.show', [$equipment->unique_id, $inspection->unique_id]))->assertOk();

        // The detail shows what the inspector saw, NOT the edited master.
        $res->assertSee('Coolant Level')->assertSee('Full')->assertSee('Engine Bay');
        $res->assertDontSee('RENAMED QUESTION')->assertDontSee('RENAMED ANSWER');
        // Attribution + identity present.
        $res->assertSee('Yard Tech')->assertSee($inspection->unique_id);
    }

    public function test_detail_labels_an_incomplete_draft_with_no_result(): void
    {
        [$m, $q, $a] = $this->makeTemplate();
        $equipment = $this->makeEquipment($m, 'available');

        // Required question omitted → an incomplete draft (result null).
        $draft = app(RentalReadyInspectionService::class)->record(
            equipment: $equipment, performedBy: $this->inspector, actor: $this->admin, source: 'web',
            answers: [], equipmentHours: 40, generalNotes: null,
        )->template;
        $this->assertSame('draft', $draft->lifecycle_status->value);

        $this->actingAs($this->admin)->get(route('admin.checklist-management.equipment-management.rental-ready-history.show', [$equipment->unique_id, $draft->unique_id]))
            ->assertOk()
            ->assertSee('Incomplete draft')
            ->assertSee('None (draft)');
    }

    public function test_history_list_avoids_n_plus_1_on_order_context(): void
    {
        [$m] = $this->makeTemplate();
        $equipment = $this->makeEquipment($m, 'maintenance');
        $order = \App\Models\Orders\Order::create(['order_number' => '9001', 'order_date' => now()->toDateString(), 'customer_name' => 'Acme']);

        // 15 order-scoped completed inspections (order_id set, no order product).
        for ($i = 0; $i < 15; $i++) {
            $this->rawRow($equipment, 'completed')->update(['result' => 'rental_ready', 'order_id' => $order->id]);
        }

        \Illuminate\Support\Facades\DB::enableQueryLog();
        $this->actingAs($this->admin)->get($this->historyUrl($equipment))->assertOk();
        $orderTableQueries = collect(\Illuminate\Support\Facades\DB::getQueryLog())
            ->filter(fn ($q) => str_contains($q['query'], '`orders`'))
            ->count();
        \Illuminate\Support\Facades\DB::disableQueryLog();

        // Order context must be eager-loaded (one whereIn), not fetched per row.
        $this->assertLessThanOrEqual(2, $orderTableQueries, 'order context must be eager-loaded, not N+1');
    }

    public function test_detail_order_number_is_a_single_hash_hotlink(): void
    {
        [$m, $q, $a] = $this->makeTemplate();
        $order = \App\Models\Orders\Order::create(['order_number' => '2775', 'order_date' => now()->toDateString(), 'customer_name' => 'Acme']);
        $equipment = $this->makeEquipment($m, 'available');
        $equipment->forceFill(['current_order_id' => $order->id])->save();

        $inspection = $this->record($equipment, $q, $a['Rental Ready']);

        $res = $this->actingAs($this->admin)
            ->get(route('admin.checklist-management.equipment-management.rental-ready-history.show', [$equipment->unique_id, $inspection->unique_id]))
            ->assertOk();

        // Exactly one '#', and a hotlink to the order it was created from.
        $res->assertSee('#2775')->assertDontSee('##2775');
        $res->assertSee(route('admin.order-management.orders.edit', ['unique_id' => $order->unique_id]), false);
    }

    public function test_detail_is_scoped_to_its_equipment(): void
    {
        [$m, $q, $a] = $this->makeTemplate();
        $equipmentA = $this->makeEquipment($m, 'maintenance');
        $equipmentB = $this->makeEquipment($m, 'maintenance');
        $inspection = $this->record($equipmentA, $q, $a['Rental Ready']);

        // The inspection belongs to A; requesting it under B is a 404.
        $this->actingAs($this->admin)->get(route('admin.checklist-management.equipment-management.rental-ready-history.show', [$equipmentB->unique_id, $inspection->unique_id]))
            ->assertNotFound();
    }
}
