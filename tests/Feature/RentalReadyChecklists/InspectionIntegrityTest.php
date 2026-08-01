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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 2A — Inspection Integrity Foundation. Proves the guarantees:
 *  - Rental Ready, Maintenance Hold, and Damaged inspections all SURVIVE a
 *    later reinspection (each finalized inspection is a distinct, frozen row).
 *  - A draft never shadows the latest COMPLETED inspection.
 *  - A draft is reused until it is finalized; a finalized inspection is never
 *    reused.
 *  - Web input cannot directly set equipment.current_status (server computes it).
 *  - A retry carrying the same completion key returns the existing inspection.
 */
class InspectionIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->employee = User::create([
            'first_name' => 'Integrity', 'last_name' => 'Inspector',
            'email' => 'integrity-inspector@example.com', 'password' => bcrypt('password'),
        ]);
    }

    // ── Fixtures ─────────────────────────────────────────────────────────

    /** @return array{0: ChecklistMaster, 1: RentalReadyChecklistQuestion, 2: array<string,RentalReadyChecklistQuestionAnswer>, 3: RentalReadyChecklistQuestion, 4: array<string,RentalReadyChecklistQuestionAnswer>} */
    private function makeTemplate(): array
    {
        $category = RentalReadyChecklistCategory::create(['category_name' => 'Hydraulics']);

        $required = RentalReadyChecklistQuestion::create(['question_name' => 'Fuel Level', 'category_id' => $category->id, 'required_question' => true]);
        $requiredAnswers = [
            'Rental Ready' => RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'Full', 'question_id' => $required->id, 'type' => 'Rental Ready', 'index_number' => 1]),
            'Damaged' => RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'Leaking', 'question_id' => $required->id, 'type' => 'Damaged', 'index_number' => 2]),
            'Maint. Hold' => RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'Low', 'question_id' => $required->id, 'type' => 'Maint. Hold', 'index_number' => 3]),
        ];

        $optional = RentalReadyChecklistQuestion::create(['question_name' => 'Cab Condition', 'category_id' => $category->id, 'required_question' => false]);
        $optionalAnswers = [
            'Rental Ready' => RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'Clean', 'question_id' => $optional->id, 'type' => 'Rental Ready', 'index_number' => 1]),
            'Damaged' => RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'Cracked', 'question_id' => $optional->id, 'type' => 'Damaged', 'index_number' => 2]),
            'Maint. Hold' => RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'Dirty', 'question_id' => $optional->id, 'type' => 'Maint. Hold', 'index_number' => 3]),
        ];

        $template = RentalReadyChecklistTemplate::create(['template_name' => 'Integrity Template', 'active_template' => true]);
        RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $required->id, 'index_number' => 1]);
        RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $optional->id, 'index_number' => 2]);

        $master = ChecklistMaster::create(['checklist_system_name' => 'Integrity Master', 'rental_ready_template_id' => $template->id]);

        return [$master, $required, $requiredAnswers, $optional, $optionalAnswers];
    }

    private function makeEquipment(ChecklistMaster $master, string $status = 'available'): Equipment
    {
        return Equipment::create([
            'equipment_name' => 'Integrity Skid Steer', 'equipment_id' => 'EQP-INT-' . uniqid(),
            'brand' => 'TestBrand', 'current_status' => $status, 'checklist_master_id' => $master->id,
        ]);
    }

    private function saveMobile(Equipment $equipment, array $checklist, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->withoutMiddleware()
            ->actingAs($this->employee, 'api_user')
            ->json('POST', 'http://' . config('app.domains.api') . '/api/admin/v1/orders/rental-ready-checklists/save-rental-ready', array_merge([
                'equipment_unique_id' => $equipment->unique_id,
                'user_id' => (string) $this->employee->id,
                'equipment_hours' => '120',
                'checklist' => $checklist,
            ], $extra));
    }

    private function answer(RentalReadyChecklistQuestion $q, RentalReadyChecklistQuestionAnswer $a): array
    {
        return ['question_unique_id' => $q->unique_id, 'answer_unique_id' => $a->unique_id];
    }

    // ── Reinspection survival (RR / Maintenance Hold / Damaged) ──────────

    public function test_rental_ready_inspection_survives_a_later_reinspection(): void
    {
        [$m, $req, $reqA, $opt, $optA] = $this->makeTemplate();
        $equipment = $this->makeEquipment($m, 'maintenance');

        $this->saveMobile($equipment, [$this->answer($req, $reqA['Rental Ready']), $this->answer($opt, $optA['Rental Ready'])])->assertOk();
        $first = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->latest('id')->firstOrFail();
        $this->assertSame('completed', $first->lifecycle_status->value);
        $this->assertSame('rental_ready', $first->result->value);

        // Reinspect: now Damaged.
        $this->saveMobile($equipment, [$this->answer($req, $reqA['Damaged'])])->assertOk();

        $rows = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->orderBy('id')->get();
        $this->assertCount(2, $rows, 'the earlier Rental Ready inspection is preserved as its own row');
        $this->assertSame('rental_ready', $rows[0]->fresh()->result->value, 'the first inspection is untouched');
        $this->assertSame('damaged', $rows[1]->result->value);
    }

    public function test_maintenance_hold_inspection_survives_a_later_reinspection(): void
    {
        [$m, $req, $reqA, $opt, $optA] = $this->makeTemplate();
        $equipment = $this->makeEquipment($m, 'available');

        // Required = Maint. Hold, optional answered → a COMPLETED maintenance hold.
        $this->saveMobile($equipment, [$this->answer($req, $reqA['Maint. Hold']), $this->answer($opt, $optA['Rental Ready'])])->assertOk();
        $first = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->latest('id')->firstOrFail();
        $this->assertSame('completed', $first->lifecycle_status->value);
        $this->assertSame('maintenance_hold', $first->result->value);
        $this->assertSame('maintenance', $equipment->fresh()->current_status->value);

        // Reinspect → Rental Ready.
        $this->saveMobile($equipment, [$this->answer($req, $reqA['Rental Ready']), $this->answer($opt, $optA['Rental Ready'])])->assertOk();

        $rows = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->orderBy('id')->get();
        $this->assertCount(2, $rows);
        $this->assertSame('maintenance_hold', $rows[0]->fresh()->result->value, 'the maintenance hold inspection survives');
        $this->assertSame('rental_ready', $rows[1]->result->value);
        $this->assertSame('available', $equipment->fresh()->current_status->value);
    }

    public function test_damaged_inspection_survives_a_later_reinspection(): void
    {
        [$m, $req, $reqA, $opt, $optA] = $this->makeTemplate();
        $equipment = $this->makeEquipment($m, 'available');

        $this->saveMobile($equipment, [$this->answer($req, $reqA['Damaged'])])->assertOk();
        $first = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->latest('id')->firstOrFail();
        $this->assertSame('damaged', $first->result->value);

        $this->saveMobile($equipment, [$this->answer($req, $reqA['Rental Ready']), $this->answer($opt, $optA['Rental Ready'])])->assertOk();

        $rows = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->orderBy('id')->get();
        $this->assertCount(2, $rows);
        $this->assertSame('damaged', $rows[0]->fresh()->result->value, 'the damaged inspection survives');
        $this->assertSame('rental_ready', $rows[1]->result->value);
    }

    // ── Drafts never shadow completed ────────────────────────────────────

    public function test_draft_does_not_shadow_the_latest_completed_inspection(): void
    {
        [$m, $req, $reqA, $opt, $optA] = $this->makeTemplate();
        $equipment = $this->makeEquipment($m, 'maintenance');

        // Complete a Rental Ready inspection → equipment available.
        $this->saveMobile($equipment, [$this->answer($req, $reqA['Rental Ready']), $this->answer($opt, $optA['Rental Ready'])])->assertOk();
        $completed = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->latest('id')->firstOrFail();
        $this->assertSame('available', $equipment->fresh()->current_status->value);

        // Start a NEW inspection but leave the required question unanswered → a draft.
        $this->saveMobile($equipment, [$this->answer($opt, $optA['Rental Ready'])])->assertOk();
        $draft = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->where('lifecycle_status', 'draft')->latest('id')->firstOrFail();
        $this->assertNotSame($completed->id, $draft->id);

        $equipment->refresh();
        // The authoritative reader still resolves the COMPLETED inspection…
        $this->assertSame($completed->id, $equipment->activeEquipmentRentalReadyTemplate->id);
        // …the draft is surfaced only through its own relation…
        $this->assertSame($draft->id, $equipment->latestDraftRentalReadyTemplate->id);
        // …and the draft did NOT change equipment status.
        $this->assertSame('available', $equipment->current_status->value);
    }

    public function test_a_draft_is_reused_until_finalized_then_frozen(): void
    {
        [$m, $req, $reqA, $opt, $optA] = $this->makeTemplate();
        $equipment = $this->makeEquipment($m, 'maintenance');

        // Two partial saves reuse the SAME draft row.
        $this->saveMobile($equipment, [$this->answer($opt, $optA['Rental Ready'])])->assertOk();
        $this->saveMobile($equipment, [$this->answer($opt, $optA['Rental Ready'])])->assertOk();
        $this->assertSame(1, EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->count(), 'a draft is reused, not duplicated');

        // Completing it finalizes the SAME row.
        $this->saveMobile($equipment, [$this->answer($req, $reqA['Rental Ready']), $this->answer($opt, $optA['Rental Ready'])])->assertOk();
        $this->assertSame(1, EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->count());
        $row = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->firstOrFail();
        $this->assertSame('completed', $row->lifecycle_status->value);

        // A brand-new inspection now forks a NEW row (the completed one is frozen).
        $this->saveMobile($equipment, [$this->answer($req, $reqA['Maint. Hold']), $this->answer($opt, $optA['Rental Ready'])])->assertOk();
        $this->assertSame(2, EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->count());
    }

    // ── Web cannot directly set equipment status ─────────────────────────

    public function test_web_input_cannot_directly_set_equipment_status(): void
    {
        [$m, $req, $reqA] = $this->makeTemplate();
        $equipment = $this->makeEquipment($m, 'maintenance');
        $admin = User::create(['first_name' => 'Admin', 'last_name' => 'Web', 'email' => 'web-admin@example.com', 'password' => bcrypt('password')]);

        // The browser CLAIMS 'available', but the selected answer is Damaged.
        $qaJson = ['questions' => [
            ['id' => $req->unique_id, 'main_id' => $req->id, 'selected_answer' => ['id' => $reqA['Damaged']->id]],
        ]];

        $this->actingAs($admin)->post(route('admin.checklist-management.equipment-management.store'), [
            'equipment_id' => $equipment->id,
            'equipment_status' => 'available',   // must be IGNORED
            'inspectorSelect' => $this->employee->id,
            'equipmentHours' => '55',
            'rental_ready_all_qa_json' => json_encode($qaJson),
        ])->assertRedirect(route('admin.checklist-management.equipment-management.show', $equipment->unique_id));

        // Server computed Damaged from the answer — the browser's 'available' is ignored.
        $this->assertSame('damaged', $equipment->fresh()->current_status->value);
        $template = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->latest('id')->firstOrFail();
        $this->assertSame('damaged', $template->result->value);
        // Inspector persisted from the form.
        $this->assertSame($this->employee->id, $template->employee_id);
        // Snapshot carries the section name + question order (server-built).
        $snapshot = json_decode($template->checklistQuestions()->first()->rental_ready_qa_json, true);
        $this->assertSame('Hydraulics', $snapshot['section_name']);
        $this->assertArrayHasKey('question_order', $snapshot);
    }

    // ── Deterministic result precedence ─────────────────────────────────

    public function test_result_precedence_is_deterministic_damaged_first(): void
    {
        [$m, $req, $reqA, $opt, $optA] = $this->makeTemplate();
        $equipment = $this->makeEquipment($m, 'available');

        // Required = Maintenance Hold, optional = Damaged. Damaged must win.
        $this->saveMobile($equipment, [$this->answer($req, $reqA['Maint. Hold']), $this->answer($opt, $optA['Damaged'])])->assertOk();
        $t = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->latest('id')->firstOrFail();
        $this->assertSame('damaged', $t->result->value, 'Damaged takes precedence over Maintenance Hold');
        $this->assertSame('damaged', $equipment->fresh()->current_status->value);
    }

    public function test_damage_finalizes_even_when_a_required_question_is_unanswered(): void
    {
        [$m, $req, $reqA, $opt, $optA] = $this->makeTemplate();
        $equipment = $this->makeEquipment($m, 'available');

        // Required question omitted (incomplete) but a Damaged answer exists →
        // damage is a hard signal: finalize as Damaged, NOT a draft.
        $this->saveMobile($equipment, [$this->answer($opt, $optA['Damaged'])])->assertOk();
        $t = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->latest('id')->firstOrFail();
        $this->assertSame('completed', $t->lifecycle_status->value);
        $this->assertSame('damaged', $t->result->value);
    }

    public function test_a_draft_result_is_null(): void
    {
        [$m, , , $opt, $optA] = $this->makeTemplate();
        $equipment = $this->makeEquipment($m, 'maintenance');

        // Required omitted, no damage → an incomplete DRAFT with a null result.
        $this->saveMobile($equipment, [$this->answer($opt, $optA['Rental Ready'])])->assertOk();
        $t = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->latest('id')->firstOrFail();
        $this->assertSame('draft', $t->lifecycle_status->value);
        $this->assertNull($t->result);
    }

    // ── Inspection UUID vs completion key; stale/offline protection ───────

    public function test_inspection_uuid_continues_a_draft_then_a_finalized_one_is_rejected(): void
    {
        [$m, $req, $reqA, $opt, $optA] = $this->makeTemplate();
        $equipment = $this->makeEquipment($m, 'maintenance');

        // Start a draft; capture its UUID.
        $draftUuid = $this->saveMobile($equipment, [$this->answer($opt, $optA['Rental Ready'])])
            ->assertOk()->json('inspection.uuid');
        $this->assertNotNull($draftUuid);

        // The inspection UUID CONTINUES that same draft (no new row).
        $this->saveMobile($equipment, [$this->answer($opt, $optA['Rental Ready'])], ['inspection_uuid' => $draftUuid])->assertOk();
        $this->assertSame(1, EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->count());

        // Finalize it.
        $this->saveMobile($equipment, [$this->answer($req, $reqA['Rental Ready']), $this->answer($opt, $optA['Rental Ready'])], ['inspection_uuid' => $draftUuid])->assertOk();
        $finalized = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->firstOrFail();
        $this->assertSame('completed', $finalized->lifecycle_status->value);

        // Stale/offline protection: continuing a FINALIZED inspection by its UUID
        // is rejected (409) — the UUID is not a retry mechanism.
        $this->saveMobile($equipment, [$this->answer($req, $reqA['Damaged'])], ['inspection_uuid' => $draftUuid])
            ->assertStatus(409)
            ->assertJson(['success' => false]);

        // No extra row was created by the rejected stale continuation.
        $this->assertSame(1, EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->count());
    }

    // ── Idempotent completion ────────────────────────────────────────────

    public function test_completion_idempotency_key_returns_existing_inspection_on_retry(): void
    {
        [$m, $req, $reqA, $opt, $optA] = $this->makeTemplate();
        $equipment = $this->makeEquipment($m, 'maintenance');

        $first = $this->saveMobile($equipment, [$this->answer($req, $reqA['Rental Ready']), $this->answer($opt, $optA['Rental Ready'])], [
            'completion_idempotency_key' => 'RETRY-KEY-1',
        ])->assertOk();
        $first->assertJsonPath('inspection.was_replay', false);
        $this->assertSame('available', $equipment->fresh()->current_status->value);

        // Retry with the SAME key but DIFFERENT answers (a Damaged double-submit).
        $retry = $this->saveMobile($equipment, [$this->answer($req, $reqA['Damaged'])], [
            'completion_idempotency_key' => 'RETRY-KEY-1',
        ])->assertOk();

        // The retry returns the ORIGINAL inspection — no duplicate row, no status flip.
        $retry->assertJsonPath('inspection.was_replay', true);
        $this->assertSame(1, EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->count());
        $this->assertSame('available', $equipment->fresh()->current_status->value);
        $this->assertSame('rental_ready', EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->firstOrFail()->result->value);
    }
}
