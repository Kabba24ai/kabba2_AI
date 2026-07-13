<?php

namespace Tests\Feature\ChecklistManagement\EquipmentManagement;

use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestionAnswer;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Monolog\Handler\TestHandler;
use Tests\TestCase;

/**
 * PR-B2 Stage 3 (Phase 2, D2: observability-first). Proves the admin-web
 * EquipmentManagement\StoreController now computes a server-trusted completion
 * result via RentalReadyCompletionCalculator and logs a warning when it disagrees
 * with the client-submitted equipment_status/counts — WITHOUT changing what gets
 * persisted or routing through EquipmentStatusService yet. Enforcement is a later,
 * separate stage. See docs/checklist-system-audit/PR-B2_STAGE3_ADMIN_OBSERVABILITY.md.
 */
class StoreControllerObservabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $inspector;
    private TestHandler $apiErrorsHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Store', 'last_name' => 'Admin',
            'email' => 'store-observability-admin@example.com', 'password' => bcrypt('password'),
        ]);

        $this->inspector = User::create([
            'first_name' => 'Inspector', 'last_name' => 'Person',
            'email' => 'store-observability-inspector@example.com', 'password' => bcrypt('password'),
        ]);

        $this->apiErrorsHandler = new TestHandler();
        \Illuminate\Support\Facades\Log::channel('api_errors')->getLogger()->pushHandler($this->apiErrorsHandler);
    }

    /**
     * @return array{0: RentalReadyChecklistQuestion, 1: array<string, RentalReadyChecklistQuestionAnswer>, 2: RentalReadyChecklistQuestion, 3: array<string, RentalReadyChecklistQuestionAnswer>}
     */
    private function makeQuestions(): array
    {
        $category = RentalReadyChecklistCategory::create(['category_name' => 'Observability Category']);

        $requiredQuestion = RentalReadyChecklistQuestion::create([
            'question_name' => 'Required question', 'category_id' => $category->id, 'required_question' => true,
        ]);
        $requiredAnswers = [
            'Rental Ready' => RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'Operable', 'question_id' => $requiredQuestion->id, 'type' => 'Rental Ready', 'index_number' => 1]),
            'Damaged'      => RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'Damaged', 'question_id' => $requiredQuestion->id, 'type' => 'Damaged', 'index_number' => 2]),
        ];

        $optionalQuestion = RentalReadyChecklistQuestion::create([
            'question_name' => 'Optional question', 'category_id' => $category->id, 'required_question' => false,
        ]);
        $optionalAnswers = [
            'Rental Ready' => RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'Operable', 'question_id' => $optionalQuestion->id, 'type' => 'Rental Ready', 'index_number' => 1]),
        ];

        return [$requiredQuestion, $requiredAnswers, $optionalQuestion, $optionalAnswers];
    }

    private function makeEquipment(): Equipment
    {
        return Equipment::create([
            'equipment_name' => 'Observability Excavator',
            'equipment_id'   => 'EQP-OBS-' . uniqid(),
            'brand'          => 'TestBrand',
            'current_status' => 'maintenance',
        ]);
    }

    private function submit(Equipment $equipment, array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)->post(
            route('admin.checklist-management.equipment-management.store'),
            $payload
        );
    }

    // ── PR-B2 Stage 4: insepectorSlect -> inspectorSelect typo fix ──────────────

    public function test_first_time_inspection_correctly_saves_employee_id(): void
    {
        [$requiredQuestion, $requiredAnswers, $optionalQuestion, $optionalAnswers] = $this->makeQuestions();
        $equipment = $this->makeEquipment();

        // No EquipmentRentalReadyTemplate exists yet for this equipment/order_product_id
        // combination, so StoreController takes its "create new template" branch —
        // the exact branch that read the misspelled 'insepectorSlect' request key
        // before this fix, silently nulling employee_id on every first-time inspection.
        $qaJson = [
            'counts' => [
                'total_questions' => 2, 'required_questions' => 1, 'optional_questions' => 1,
                'required_items_completed' => 1, 'items_requiring_maintenance' => 0, 'damaged_items' => 0,
            ],
            'questions' => [
                ['id' => $requiredQuestion->unique_id, 'main_id' => $requiredQuestion->id, 'selected_answer' => ['id' => $requiredAnswers['Rental Ready']->id]],
                ['id' => $optionalQuestion->unique_id, 'main_id' => $optionalQuestion->id, 'selected_answer' => ['id' => $optionalAnswers['Rental Ready']->id]],
            ],
        ];

        $response = $this->submit($equipment, [
            'equipment_id'     => $equipment->id,
            'equipment_status' => 'available',
            'inspectorSelect'  => $this->inspector->id,
            'equipmentHours'   => '50',
            'rental_ready_all_qa_json' => json_encode($qaJson),
        ]);

        $response->assertRedirect(route('admin.checklist-management.equipment-management.show', $equipment->unique_id));

        $template = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->latest('id')->firstOrFail();
        $this->assertSame($this->inspector->id, $template->employee_id);
        $this->assertSame($this->inspector->full_name, $template->employee_name);
    }

    // ── Status mismatch ─────────────────────────────────────────────────────────

    public function test_logs_mismatch_when_submitted_status_disagrees_with_computed_status(): void
    {
        [$requiredQuestion, $requiredAnswers] = $this->makeQuestions();
        $equipment = $this->makeEquipment();

        // Real answer is Damaged, but the client claims "available" (Rental Ready).
        $qaJson = [
            'counts' => [
                'total_questions' => 1, 'required_questions' => 1, 'optional_questions' => 0,
                'required_items_completed' => 1, 'items_requiring_maintenance' => 0, 'damaged_items' => 0,
            ],
            'questions' => [
                ['id' => $requiredQuestion->unique_id, 'main_id' => $requiredQuestion->id, 'selected_answer' => ['id' => $requiredAnswers['Damaged']->id]],
            ],
        ];

        $response = $this->submit($equipment, [
            'equipment_id'     => $equipment->id,
            'equipment_status' => 'available',
            'inspectorSelect'  => $this->inspector->id,
            'equipmentHours'   => '50',
            'rental_ready_all_qa_json' => json_encode($qaJson),
        ]);

        $response->assertRedirect(route('admin.checklist-management.equipment-management.show', $equipment->unique_id));

        $this->assertTrue($this->apiErrorsHandler->hasWarningThatContains(
            'Admin Rental Ready submission disagrees with server-computed completion result'
        ));
        $record = $this->apiErrorsHandler->getRecords()[0];
        $this->assertEquals($equipment->id, $record->context['equipment_id']);
        $this->assertEquals($equipment->unique_id, $record->context['equipment_unique_id']);
        $this->assertSame('available', $record->context['submitted_equipment_status']);
        $this->assertSame('Rental Ready', $record->context['submitted_template_status']);
        $this->assertSame('Damaged', $record->context['computed_status']);
        $this->assertSame($this->admin->id, $record->context['actor_id']);

        // Persisted values remain the client-submitted ones — observability only.
        $template = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->latest('id')->firstOrFail();
        $this->assertSame('Rental Ready', $template->status);
        $this->assertSame(1, $template->required_items_completed);
        $this->assertSame(0, $template->damaged_items);
        $this->assertSame('available', $equipment->fresh()->current_status->value);
    }

    // ── Counts mismatch ─────────────────────────────────────────────────────────

    public function test_logs_mismatch_when_submitted_counts_disagree_with_computed_counts(): void
    {
        [$requiredQuestion, $requiredAnswers] = $this->makeQuestions();
        $equipment = $this->makeEquipment();

        // Status label matches (both "Rental Ready"), but the submitted counts blob
        // is simply wrong (claims 2 required questions when there's only 1).
        $qaJson = [
            'counts' => [
                'total_questions' => 2, 'required_questions' => 2, 'optional_questions' => 0,
                'required_items_completed' => 2, 'items_requiring_maintenance' => 0, 'damaged_items' => 0,
            ],
            'questions' => [
                ['id' => $requiredQuestion->unique_id, 'main_id' => $requiredQuestion->id, 'selected_answer' => ['id' => $requiredAnswers['Rental Ready']->id]],
            ],
        ];

        $response = $this->submit($equipment, [
            'equipment_id'     => $equipment->id,
            'equipment_status' => 'available',
            'inspectorSelect'  => $this->inspector->id,
            'equipmentHours'   => '50',
            'rental_ready_all_qa_json' => json_encode($qaJson),
        ]);

        $response->assertRedirect(route('admin.checklist-management.equipment-management.show', $equipment->unique_id));

        $this->assertTrue($this->apiErrorsHandler->hasWarningThatContains(
            'Admin Rental Ready submission disagrees with server-computed completion result'
        ));
        $record = $this->apiErrorsHandler->getRecords()[0];
        // Status agrees (both "Rental Ready") — only counts disagree.
        $this->assertSame('Rental Ready', $record->context['submitted_template_status']);
        $this->assertSame('Rental Ready', $record->context['computed_status']);
        $this->assertContains('total_questions', $record->context['mismatched_count_fields']);
        $this->assertContains('required_questions', $record->context['mismatched_count_fields']);
        $this->assertContains('required_items_completed', $record->context['mismatched_count_fields']);
        $this->assertSame([
            'total_questions' => 2, 'required_questions' => 2, 'optional_questions' => 0,
            'required_items_completed' => 2, 'items_requiring_maintenance' => 0, 'damaged_items' => 0,
        ], $record->context['submitted_counts']);
        $this->assertSame([
            'total_questions' => 1, 'required_questions' => 1, 'optional_questions' => 0,
            'required_items_completed' => 1, 'items_requiring_maintenance' => 0, 'damaged_items' => 0,
        ], $record->context['computed_counts']);

        // Persisted counts remain the client-submitted (wrong) ones.
        $template = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->latest('id')->firstOrFail();
        $this->assertSame(2, $template->total_questions);
        $this->assertSame(2, $template->required_questions);
    }

    // ── No mismatch ──────────────────────────────────────────────────────────────

    public function test_does_not_log_when_submitted_status_and_counts_match_computed_values(): void
    {
        [$requiredQuestion, $requiredAnswers, $optionalQuestion, $optionalAnswers] = $this->makeQuestions();
        $equipment = $this->makeEquipment();

        $qaJson = [
            'counts' => [
                'total_questions' => 2, 'required_questions' => 1, 'optional_questions' => 1,
                'required_items_completed' => 1, 'items_requiring_maintenance' => 0, 'damaged_items' => 0,
            ],
            'questions' => [
                ['id' => $requiredQuestion->unique_id, 'main_id' => $requiredQuestion->id, 'selected_answer' => ['id' => $requiredAnswers['Rental Ready']->id]],
                ['id' => $optionalQuestion->unique_id, 'main_id' => $optionalQuestion->id, 'selected_answer' => ['id' => $optionalAnswers['Rental Ready']->id]],
            ],
        ];

        $response = $this->submit($equipment, [
            'equipment_id'     => $equipment->id,
            'equipment_status' => 'available',
            'inspectorSelect'  => $this->inspector->id,
            'equipmentHours'   => '50',
            'rental_ready_all_qa_json' => json_encode($qaJson),
        ]);

        $response->assertRedirect(route('admin.checklist-management.equipment-management.show', $equipment->unique_id));
        $this->assertFalse($this->apiErrorsHandler->hasWarningRecords());

        $template = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->latest('id')->firstOrFail();
        $this->assertSame('Rental Ready', $template->status);
        $this->assertSame('available', $equipment->fresh()->current_status->value);
    }

    // ── Calculator never enforces / overwrites current behavior ─────────────────

    public function test_calculator_result_does_not_enforce_or_overwrite_persisted_values(): void
    {
        [$requiredQuestion, $requiredAnswers] = $this->makeQuestions();
        $equipment = $this->makeEquipment();

        // Real answer computes Damaged; client submits "damaged" as well (agreeing on
        // label) but with an intentionally wrong counts blob — persisted values must
        // still be exactly what was submitted, not the calculator's output, in both
        // the agreeing and disagreeing fields.
        $qaJson = [
            'counts' => [
                'total_questions' => 1, 'required_questions' => 1, 'optional_questions' => 0,
                'required_items_completed' => 1, 'items_requiring_maintenance' => 0, 'damaged_items' => 0,
            ],
            'questions' => [
                ['id' => $requiredQuestion->unique_id, 'main_id' => $requiredQuestion->id, 'selected_answer' => ['id' => $requiredAnswers['Damaged']->id]],
            ],
        ];

        $this->submit($equipment, [
            'equipment_id'     => $equipment->id,
            'equipment_status' => 'damaged',
            'inspectorSelect'  => $this->inspector->id,
            'equipmentHours'   => '50',
            'rental_ready_all_qa_json' => json_encode($qaJson),
        ]);

        // Computed counts would say required_items_completed=0, damaged_items=1 — but
        // the persisted row must still reflect exactly what the client submitted.
        $template = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->latest('id')->firstOrFail();
        $this->assertSame('Damaged', $template->status);
        $this->assertSame(1, $template->required_items_completed);
        $this->assertSame(0, $template->damaged_items);
        $this->assertSame('damaged', $equipment->fresh()->current_status->value);

        // StoreController's plain (non-quiet) $equipment->update() already triggers
        // EquipmentObserver pre-existing this PR (it logs any current_status change
        // regardless of caller) — that single row is expected and unchanged. What
        // Stage 3 must NOT do is additionally route through EquipmentStatusService,
        // which would double-log or change this behavior; confirm exactly one row,
        // matching the observer's own from/to, not two.
        $this->assertDatabaseCount('equipment_status_logs', 1);
        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $equipment->id,
            'from_status'  => 'maintenance',
            'to_status'    => 'damaged',
        ]);
    }
}
