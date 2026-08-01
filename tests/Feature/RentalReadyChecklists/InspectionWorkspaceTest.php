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
 * Phase 3A — Three-Column Rental Ready Workspace.
 *
 * The presentation layer (left navigator / center current inspection / right
 * latest-completed comparison) is driven by a read-only endpoint plus the
 * equipment reader relations. These tests pin the SERVER contract the client
 * comparison depends on: the right panel resolves the latest COMPLETED (not the
 * latest ROW), a draft and a completed inspection are surfaced simultaneously
 * without the draft shadowing the completed result, answers carry the stable
 * master question id used for alignment, historical evidence survives master
 * edits, and the endpoint is equipment-scoped. None of the Phase 2A/2B write,
 * lifecycle, result, or reader rules are touched here.
 */
class InspectionWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $inspector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create(['first_name' => 'Work', 'last_name' => 'Admin', 'email' => 'work-admin@example.com', 'password' => bcrypt('x')]);
        $this->inspector = User::create(['first_name' => 'Bay', 'last_name' => 'Tech', 'email' => 'work-tech@example.com', 'password' => bcrypt('x'), 'status' => 'Active']);
    }

    private function makeCategory(string $name): RentalReadyChecklistCategory
    {
        return RentalReadyChecklistCategory::create(['category_name' => $name]);
    }

    /** @return array{0: RentalReadyChecklistQuestion, 1: array<string,RentalReadyChecklistQuestionAnswer>} */
    private function makeQuestion(RentalReadyChecklistTemplate $template, RentalReadyChecklistCategory $category, string $name, int $order, bool $required = true): array
    {
        $q = RentalReadyChecklistQuestion::create(['question_name' => $name, 'category_id' => $category->id, 'required_question' => $required]);
        $answers = [
            'Rental Ready' => RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'Good', 'question_id' => $q->id, 'type' => 'Rental Ready', 'index_number' => 1]),
            'Damaged' => RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'Broken', 'question_id' => $q->id, 'type' => 'Damaged', 'index_number' => 2]),
            'Maint. Hold' => RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'Check', 'question_id' => $q->id, 'type' => 'Maint. Hold', 'index_number' => 3]),
        ];
        RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $q->id, 'index_number' => $order]);

        return [$q, $answers];
    }

    private function makeEquipment(ChecklistMaster $master, string $status = 'available'): Equipment
    {
        return Equipment::create(['equipment_name' => 'Work Loader', 'equipment_id' => 'EQP-W-' . uniqid(), 'brand' => 'T', 'current_status' => $status, 'checklist_master_id' => $master->id]);
    }

    /** @param array<int, array{0: RentalReadyChecklistQuestion, 1: RentalReadyChecklistQuestionAnswer}> $picks */
    private function record(Equipment $equipment, array $picks): EquipmentRentalReadyTemplate
    {
        $answers = array_map(fn ($p) => ['question_unique_id' => $p[0]->unique_id, 'answer_unique_id' => $p[1]->unique_id, 'note' => ''], $picks);

        return app(RentalReadyInspectionService::class)->record(
            equipment: $equipment->fresh(),
            performedBy: $this->inspector,
            actor: $this->admin,
            source: 'web',
            answers: $answers,
            equipmentHours: 120,
            generalNotes: null,
        )->template;
    }

    private function latestUrl(Equipment $e): string
    {
        return route('admin.checklist-management.equipment-management.rental-ready-latest-completed', $e->unique_id);
    }

    public function test_right_panel_resolves_latest_completed_not_latest_row(): void
    {
        $cat = $this->makeCategory('Engine');
        $template = RentalReadyChecklistTemplate::create(['template_name' => 'W', 'active_template' => true]);
        [$q, $a] = $this->makeQuestion($template, $cat, 'Coolant', 1);
        $master = ChecklistMaster::create(['checklist_system_name' => 'WM', 'rental_ready_template_id' => $template->id]);
        $equipment = $this->makeEquipment($master, 'available');

        // A completed inspection, then a LATER draft row (higher id, newer).
        $completed = $this->record($equipment, [[$q, $a['Rental Ready']]]);
        $draft = $this->record($equipment, []); // no answers → stays a draft

        $this->assertSame('completed', $completed->lifecycle_status->value);
        $this->assertSame('draft', $draft->fresh()->lifecycle_status->value);
        $this->assertGreaterThan($completed->id, $draft->id, 'the draft must be the newer row');

        // The endpoint returns the COMPLETED one, never the newer draft row.
        $res = $this->actingAs($this->admin)->getJson($this->latestUrl($equipment))->assertOk();
        $res->assertJsonPath('inspection.unique_id', $completed->unique_id);
        $res->assertJsonPath('inspection.result', 'rental_ready');
    }

    public function test_draft_and_latest_completed_surface_simultaneously(): void
    {
        $cat = $this->makeCategory('Engine');
        $template = RentalReadyChecklistTemplate::create(['template_name' => 'W', 'active_template' => true]);
        [$q, $a] = $this->makeQuestion($template, $cat, 'Coolant', 1);
        $master = ChecklistMaster::create(['checklist_system_name' => 'WM', 'rental_ready_template_id' => $template->id]);
        $equipment = $this->makeEquipment($master, 'available');

        $completed = $this->record($equipment, [[$q, $a['Rental Ready']]]);
        $draft = $this->record($equipment, []);

        // Both readers resolve independently: the completed reader is the right
        // panel's source; the draft reader is the left card's "active draft"
        // indicator. The draft NEVER shadows the completed result.
        $equipment->load(['latestRentalReadyTemplate', 'latestDraftRentalReadyTemplate']);
        $this->assertNotNull($equipment->latestRentalReadyTemplate, 'completed reader present');
        $this->assertSame($completed->id, $equipment->latestRentalReadyTemplate->id);
        $this->assertNotNull($equipment->latestDraftRentalReadyTemplate, 'draft indicator present');
        $this->assertSame($draft->id, $equipment->latestDraftRentalReadyTemplate->id);

        // The right-panel endpoint still returns the completed one alongside the draft.
        $this->actingAs($this->admin)->getJson($this->latestUrl($equipment))->assertOk()
            ->assertJsonPath('inspection.unique_id', $completed->unique_id);
    }

    public function test_answers_carry_stable_master_question_id_for_alignment(): void
    {
        $cat = $this->makeCategory('Engine');
        $template = RentalReadyChecklistTemplate::create(['template_name' => 'W', 'active_template' => true]);
        [$q, $a] = $this->makeQuestion($template, $cat, 'Coolant', 1);
        $master = ChecklistMaster::create(['checklist_system_name' => 'WM', 'rental_ready_template_id' => $template->id]);
        $equipment = $this->makeEquipment($master, 'available');

        $this->record($equipment, [[$q, $a['Rental Ready']]]);

        // The comparison aligns current `main_id` ↔ snapshot `question_id`; both
        // are the numeric master question id. The endpoint must expose it.
        $this->actingAs($this->admin)->getJson($this->latestUrl($equipment))->assertOk()
            ->assertJsonPath('inspection.questions.0.question_id', $q->id)
            ->assertJsonPath('inspection.questions.0.selected_answer_name', 'Good')
            ->assertJsonPath('inspection.questions.0.selected_answer_type', 'Rental Ready');
    }

    public function test_removed_historical_question_is_retained_from_snapshot(): void
    {
        $cat = $this->makeCategory('Engine');
        $template = RentalReadyChecklistTemplate::create(['template_name' => 'W', 'active_template' => true]);
        [$q1, $a1] = $this->makeQuestion($template, $cat, 'Coolant', 1);
        [$q2, $a2] = $this->makeQuestion($template, $cat, 'Belt Tension', 2);
        $master = ChecklistMaster::create(['checklist_system_name' => 'WM', 'rental_ready_template_id' => $template->id]);
        $equipment = $this->makeEquipment($master, 'available');

        $this->record($equipment, [[$q1, $a1['Rental Ready']], [$q2, $a2['Rental Ready']]]);

        // The second question is later removed from the current checklist master.
        RentalReadyChecklistTemplateQuestion::where('template_id', $template->id)
            ->where('question_id', $q2->id)->delete();
        $q2->delete();

        // The endpoint reconstructs from the immutable snapshot, so the removed
        // question is STILL returned (the client files it under "Questions no
        // longer in the current checklist" — evidence is never discarded).
        $res = $this->actingAs($this->admin)->getJson($this->latestUrl($equipment))->assertOk();
        $ids = collect($res->json('inspection.questions'))->pluck('question_id')->all();
        $this->assertContains($q1->id, $ids);
        $this->assertContains($q2->id, $ids, 'removed historical question must be retained from the snapshot');
    }

    public function test_new_current_question_has_no_match_in_previous_snapshot(): void
    {
        $cat = $this->makeCategory('Engine');
        $template = RentalReadyChecklistTemplate::create(['template_name' => 'W', 'active_template' => true]);
        [$q1, $a1] = $this->makeQuestion($template, $cat, 'Coolant', 1);
        $master = ChecklistMaster::create(['checklist_system_name' => 'WM', 'rental_ready_template_id' => $template->id]);
        $equipment = $this->makeEquipment($master, 'available');

        $this->record($equipment, [[$q1, $a1['Rental Ready']]]);

        // A brand-new question is added to the current checklist AFTER the
        // previous inspection.
        [$qNew] = $this->makeQuestion($template, $cat, 'Hydraulic Fluid', 2);

        // The previous inspection's snapshot cannot contain the new question, so
        // the client shows "No previous answer" for it.
        $res = $this->actingAs($this->admin)->getJson($this->latestUrl($equipment))->assertOk();
        $ids = collect($res->json('inspection.questions'))->pluck('question_id')->all();
        $this->assertContains($q1->id, $ids);
        $this->assertNotContains($qNew->id, $ids, 'a question added after the inspection must not appear in its snapshot');
    }

    public function test_completed_only_unit_is_never_a_blank_context(): void
    {
        $cat = $this->makeCategory('Engine');
        $template = RentalReadyChecklistTemplate::create(['template_name' => 'W', 'active_template' => true]);
        [$q, $a] = $this->makeQuestion($template, $cat, 'Coolant', 1);
        $master = ChecklistMaster::create(['checklist_system_name' => 'WM', 'rental_ready_template_id' => $template->id]);
        $equipment = $this->makeEquipment($master, 'maintenance');

        $completed = $this->record($equipment, [[$q, $a['Maint. Hold']]]);

        // Selecting a completed-only unit yields real context (result + inspector
        // + comparison data), never an unexplained blank checklist.
        $this->actingAs($this->admin)->getJson($this->latestUrl($equipment))->assertOk()
            ->assertJsonPath('inspection.unique_id', $completed->unique_id)
            ->assertJsonPath('inspection.result', 'maintenance_hold')
            ->assertJsonPath('inspection.inspector', 'Bay Tech');
    }

    public function test_never_inspected_unit_returns_a_null_inspection(): void
    {
        $cat = $this->makeCategory('Engine');
        $template = RentalReadyChecklistTemplate::create(['template_name' => 'W', 'active_template' => true]);
        $this->makeQuestion($template, $cat, 'Coolant', 1);
        $master = ChecklistMaster::create(['checklist_system_name' => 'WM', 'rental_ready_template_id' => $template->id]);
        $equipment = $this->makeEquipment($master, 'available');

        $this->actingAs($this->admin)->getJson($this->latestUrl($equipment))->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('inspection', null);
    }

    public function test_endpoint_is_scoped_to_its_equipment(): void
    {
        $cat = $this->makeCategory('Engine');
        $template = RentalReadyChecklistTemplate::create(['template_name' => 'W', 'active_template' => true]);
        [$q, $a] = $this->makeQuestion($template, $cat, 'Coolant', 1);
        $master = ChecklistMaster::create(['checklist_system_name' => 'WM', 'rental_ready_template_id' => $template->id]);
        $equipmentA = $this->makeEquipment($master, 'available');
        $equipmentB = $this->makeEquipment($master, 'available');

        $inspection = $this->record($equipmentA, [[$q, $a['Rental Ready']]]);

        // Equipment B has no inspection of its own and must never see A's.
        $res = $this->actingAs($this->admin)->getJson($this->latestUrl($equipmentB))->assertOk();
        $res->assertJsonPath('inspection', null);
        $this->assertNotEquals($inspection->unique_id, $res->json('inspection'));

        // The detail route remains equipment-scoped (Phase 2B guarantee upheld).
        $this->actingAs($this->admin)
            ->get(route('admin.checklist-management.equipment-management.rental-ready-history.show', [$equipmentB->unique_id, $inspection->unique_id]))
            ->assertNotFound();
    }

    public function test_unknown_equipment_returns_404(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('admin.checklist-management.equipment-management.rental-ready-latest-completed', 'EQP-DOES-NOT-EXIST'))
            ->assertNotFound();
    }

    public function test_endpoint_exposes_only_the_panel_fields(): void
    {
        $cat = $this->makeCategory('Engine');
        $template = RentalReadyChecklistTemplate::create(['template_name' => 'W', 'active_template' => true]);
        [$q, $a] = $this->makeQuestion($template, $cat, 'Coolant', 1);
        $master = ChecklistMaster::create(['checklist_system_name' => 'WM', 'rental_ready_template_id' => $template->id]);
        $equipment = $this->makeEquipment($master, 'available');
        $this->record($equipment, [[$q, $a['Rental Ready']]]);

        $ins = $this->actingAs($this->admin)->getJson($this->latestUrl($equipment))->assertOk()->json('inspection');

        // Exactly the comparison-panel fields — no audit columns, no raw model
        // attributes, no notes, no unnecessary snapshot internals.
        $this->assertEqualsCanonicalizing(
            ['unique_id', 'result', 'result_label', 'inspector', 'equipment_hours', 'completed_at', 'order_number', 'detail_url', 'history_url', 'questions'],
            array_keys($ins),
        );
        $this->assertArrayNotHasKey('counts', $ins);
        $this->assertArrayNotHasKey('inspection_date', $ins);

        $this->assertEqualsCanonicalizing(
            ['question_id', 'question_unique_id', 'question_name', 'selected_answer_name', 'selected_answer_type'],
            array_keys($ins['questions'][0]),
        );
        $this->assertArrayNotHasKey('note', $ins['questions'][0]);
    }
}
