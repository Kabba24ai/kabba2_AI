<?php

namespace Tests\Feature\RentalReadyChecklists;

use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyChecklistQuestionLog;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentStatusLog;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestionAnswer;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplateQuestion;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Monolog\Handler\TestHandler;
use Tests\TestCase;

/**
 * PR-B2 Stage 1 — characterization suite.
 *
 * Locks the mobile Rental Ready path's CURRENT behavior
 * (app/Http/Controllers/Api/Admin/V1/Orders/RentalReadyChecklists/SaveController.php)
 * before RentalReadyCompletionCalculator is extracted (PR-B2 Stage 2+). These tests
 * assert on today's production code, unmodified — they exist to prove the eventual
 * extraction is behavior-preserving: if any of these assertions ever needs to change
 * as a *result* of the calculator extraction, the extraction was not mechanical.
 *
 * See docs/checklist-system-audit/PR-B2_READINESS_REVIEW.md and
 * PR-B2_CALCULATOR_DESIGN.md §8 for the source of each scenario below.
 */
class SaveControllerCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;
    private TestHandler $apiErrorsHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::create([
            'first_name' => 'Inspector',
            'last_name'  => 'User',
            'email'      => 'characterization-inspector@example.com',
            'password'   => bcrypt('password'),
        ]);

        $this->apiErrorsHandler = new TestHandler();
        \Illuminate\Support\Facades\Log::channel('api_errors')->getLogger()->pushHandler($this->apiErrorsHandler);
    }

    private function apiUrl(string $path): string
    {
        return 'http://' . config('app.domains.api') . '/api/admin/v1/' . ltrim($path, '/');
    }

    private function callAs(string $method, string $path, array $payload = []): \Illuminate\Testing\TestResponse
    {
        return $this->withoutMiddleware()
            ->actingAs($this->employee, 'api_user')
            ->json($method, $this->apiUrl($path), $payload);
    }

    /**
     * Builds one required question (with Rental Ready / Damaged / Maint. Hold answer
     * options) and one optional question (with Rental Ready / Maint. Hold answer
     * options), mapped onto one RentalReadyChecklistTemplate, and a ChecklistMaster
     * pointing at it — the exact relation graph SaveController reads via
     * $equipment->checklistMaster->rentalReadyTemplate->templateQuestions.
     *
     * @return array{0: ChecklistMaster, 1: RentalReadyChecklistQuestion, 2: array<string, RentalReadyChecklistQuestionAnswer>, 3: RentalReadyChecklistQuestion, 4: array<string, RentalReadyChecklistQuestionAnswer>}
     */
    private function makeTemplate(): array
    {
        $category = RentalReadyChecklistCategory::create(['category_name' => 'Characterization Category']);

        $requiredQuestion = RentalReadyChecklistQuestion::create([
            'question_name'     => 'Required question',
            'category_id'       => $category->id,
            'required_question' => true,
        ]);
        $requiredAnswers = [
            'Rental Ready' => RentalReadyChecklistQuestionAnswer::create([
                'answer_name' => 'Operable', 'question_id' => $requiredQuestion->id, 'type' => 'Rental Ready', 'index_number' => 1,
            ]),
            'Damaged' => RentalReadyChecklistQuestionAnswer::create([
                'answer_name' => 'Damaged', 'question_id' => $requiredQuestion->id, 'type' => 'Damaged', 'index_number' => 2,
            ]),
            'Maint. Hold' => RentalReadyChecklistQuestionAnswer::create([
                'answer_name' => 'Needs maintenance', 'question_id' => $requiredQuestion->id, 'type' => 'Maint. Hold', 'index_number' => 3,
            ]),
        ];

        $optionalQuestion = RentalReadyChecklistQuestion::create([
            'question_name'     => 'Optional question',
            'category_id'       => $category->id,
            'required_question' => false,
        ]);
        $optionalAnswers = [
            'Rental Ready' => RentalReadyChecklistQuestionAnswer::create([
                'answer_name' => 'Operable', 'question_id' => $optionalQuestion->id, 'type' => 'Rental Ready', 'index_number' => 1,
            ]),
            'Maint. Hold' => RentalReadyChecklistQuestionAnswer::create([
                'answer_name' => 'Needs maintenance', 'question_id' => $optionalQuestion->id, 'type' => 'Maint. Hold', 'index_number' => 2,
            ]),
        ];

        $template = RentalReadyChecklistTemplate::create([
            'template_name'   => 'Characterization Template',
            'active_template' => true,
        ]);
        RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $requiredQuestion->id, 'index_number' => 1]);
        RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $optionalQuestion->id, 'index_number' => 2]);

        $checklistMaster = ChecklistMaster::create([
            'checklist_system_name'    => 'Characterization Checklist Master',
            'rental_ready_template_id' => $template->id,
        ]);

        return [$checklistMaster, $requiredQuestion, $requiredAnswers, $optionalQuestion, $optionalAnswers];
    }

    private function makeEquipment(ChecklistMaster $checklistMaster, string $initialStatus): Equipment
    {
        return Equipment::create([
            'equipment_name'      => 'Characterization Excavator',
            'equipment_id'        => 'EQP-CHAR-' . uniqid(),
            'brand'               => 'TestBrand',
            'current_status'      => $initialStatus,
            'checklist_master_id' => $checklistMaster->id,
        ]);
    }

    // ── Scenario 1: all required Rental Ready, optional also Rental Ready ──────

    public function test_scenario_1_all_rental_ready_marks_available_and_complete(): void
    {
        [$checklistMaster, $requiredQuestion, $requiredAnswers, $optionalQuestion, $optionalAnswers] = $this->makeTemplate();
        // Starting status differs from the expected final status ('available') so the
        // EquipmentStatusLog assertion below actually exercises recordTransition()
        // (it no-ops when $from === $to).
        $equipment = $this->makeEquipment($checklistMaster, 'maintenance');

        $response = $this->callAs('POST', 'orders/rental-ready-checklists/save-rental-ready', [
            'equipment_unique_id' => $equipment->unique_id,
            'user_id'             => (string) $this->employee->id,
            'equipment_hours'     => '100',
            'checklist' => [
                ['question_unique_id' => $requiredQuestion->unique_id, 'answer_unique_id' => $requiredAnswers['Rental Ready']->unique_id],
                ['question_unique_id' => $optionalQuestion->unique_id, 'answer_unique_id' => $optionalAnswers['Rental Ready']->unique_id],
            ],
        ]);

        // API response shape unchanged: exactly {success, message}, success=true.
        $response->assertOk()
            ->assertJsonStructure(['success', 'message'])
            ->assertJson(['success' => true]);

        $template = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->latest('id')->firstOrFail();
        $this->assertSame('Rental Ready', $template->status);
        $this->assertTrue((bool) $template->is_complete);

        // All 6 count fields.
        $this->assertSame(2, $template->total_questions);
        $this->assertSame(1, $template->required_questions);
        $this->assertSame(1, $template->optional_questions);
        $this->assertSame(1, $template->required_items_completed);
        $this->assertSame(0, $template->items_requiring_maintenance);
        $this->assertSame(0, $template->damaged_items);

        $this->assertSame('available', $equipment->fresh()->current_status->value);

        // EquipmentStatusLog audit row.
        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $equipment->id,
            'from_status'  => 'maintenance',
            'to_status'    => 'available',
        ]);

        // Consolidated EquipmentRentalReadyChecklistQuestionLog row.
        $this->assertSame(1, EquipmentRentalReadyChecklistQuestionLog::where('equipment_rental_ready_template_id', $template->id)->count());

        $this->assertFalse($this->apiErrorsHandler->hasWarningRecords());
    }

    // ── Scenario 2: required question Damaged — precedence over ready ──────────

    public function test_scenario_2_required_damaged_takes_precedence(): void
    {
        [$checklistMaster, $requiredQuestion, $requiredAnswers, $optionalQuestion, $optionalAnswers] = $this->makeTemplate();
        $equipment = $this->makeEquipment($checklistMaster, 'maintenance');

        $response = $this->callAs('POST', 'orders/rental-ready-checklists/save-rental-ready', [
            'equipment_unique_id' => $equipment->unique_id,
            'user_id'             => (string) $this->employee->id,
            'equipment_hours'     => '100',
            'checklist' => [
                ['question_unique_id' => $requiredQuestion->unique_id, 'answer_unique_id' => $requiredAnswers['Damaged']->unique_id],
                ['question_unique_id' => $optionalQuestion->unique_id, 'answer_unique_id' => $optionalAnswers['Rental Ready']->unique_id],
            ],
        ]);

        $response->assertOk()
            ->assertJsonStructure(['success', 'message'])
            ->assertJson(['success' => true]);

        $template = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->latest('id')->firstOrFail();
        $this->assertSame('Damaged', $template->status);
        // Damaged precedence: is_complete tracks $allRentalReady, which is false here
        // (the required question's answer type is Damaged, not Rental Ready).
        $this->assertFalse((bool) $template->is_complete);

        $this->assertSame(2, $template->total_questions);
        $this->assertSame(1, $template->required_questions);
        $this->assertSame(1, $template->optional_questions);
        $this->assertSame(0, $template->required_items_completed);
        $this->assertSame(0, $template->items_requiring_maintenance);
        $this->assertSame(1, $template->damaged_items);

        $this->assertSame('damaged', $equipment->fresh()->current_status->value);

        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $equipment->id,
            'from_status'  => 'maintenance',
            'to_status'    => 'damaged',
        ]);

        $this->assertSame(1, EquipmentRentalReadyChecklistQuestionLog::where('equipment_rental_ready_template_id', $template->id)->count());
    }

    // ── Scenario 3: all required Rental Ready, one OPTIONAL question unanswered ─

    public function test_scenario_3_unanswered_optional_question_does_not_block_completion(): void
    {
        [$checklistMaster, $requiredQuestion, $requiredAnswers, $optionalQuestion] = $this->makeTemplate();
        $equipment = $this->makeEquipment($checklistMaster, 'maintenance');

        $response = $this->callAs('POST', 'orders/rental-ready-checklists/save-rental-ready', [
            'equipment_unique_id' => $equipment->unique_id,
            'user_id'             => (string) $this->employee->id,
            'equipment_hours'     => '100',
            // Optional question is intentionally omitted from the payload entirely.
            'checklist' => [
                ['question_unique_id' => $requiredQuestion->unique_id, 'answer_unique_id' => $requiredAnswers['Rental Ready']->unique_id],
            ],
        ]);

        $response->assertOk()
            ->assertJsonStructure(['success', 'message'])
            ->assertJson(['success' => true]);

        $template = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->latest('id')->firstOrFail();
        // Still Rental Ready — allRentalReady is scoped to required questions only.
        $this->assertSame('Rental Ready', $template->status);
        $this->assertTrue((bool) $template->is_complete);

        $this->assertSame(2, $template->total_questions);
        $this->assertSame(1, $template->required_questions);
        $this->assertSame(1, $template->optional_questions);
        $this->assertSame(1, $template->required_items_completed);
        $this->assertSame(0, $template->items_requiring_maintenance);
        $this->assertSame(0, $template->damaged_items);

        $this->assertSame('available', $equipment->fresh()->current_status->value);

        $this->assertDatabaseHas('equipment_status_logs', [
            'equipment_id' => $equipment->id,
            'from_status'  => 'maintenance',
            'to_status'    => 'available',
        ]);

        // Phase 2A: the PR-B3/D3 observability-only warning is retired — the
        // canonical server-authoritative writer resolves completion from the
        // master template directly, so an unanswered OPTIONAL question no longer
        // needs a "saved despite unanswered questions" warning; all required
        // answered → a clean completed Rental Ready inspection.
        $this->assertSame('completed', $template->lifecycle_status->value);
        $this->assertSame('rental_ready', $template->result->value);
    }

    // ── Scenario 4: required question unanswered ────────────────────────────────

    public function test_scenario_4_unanswered_required_question_falls_back_to_draft(): void
    {
        [$checklistMaster, $requiredQuestion, , $optionalQuestion, $optionalAnswers] = $this->makeTemplate();
        $equipment = $this->makeEquipment($checklistMaster, 'available');

        $response = $this->callAs('POST', 'orders/rental-ready-checklists/save-rental-ready', [
            'equipment_unique_id' => $equipment->unique_id,
            'user_id'             => (string) $this->employee->id,
            'equipment_hours'     => '100',
            // Required question intentionally omitted; only the optional one is answered.
            'checklist' => [
                ['question_unique_id' => $optionalQuestion->unique_id, 'answer_unique_id' => $optionalAnswers['Rental Ready']->unique_id],
            ],
        ]);

        // Request still succeeds (PR-B3/D3: observability only, not enforced).
        $response->assertOk()
            ->assertJsonStructure(['success', 'message'])
            ->assertJson(['success' => true]);

        $template = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)->latest('id')->firstOrFail();
        $this->assertSame('Draft', $template->status);
        $this->assertFalse((bool) $template->is_complete);

        $this->assertSame(2, $template->total_questions);
        $this->assertSame(1, $template->required_questions);
        $this->assertSame(1, $template->optional_questions);
        $this->assertSame(0, $template->required_items_completed);
        $this->assertSame(0, $template->items_requiring_maintenance);
        $this->assertSame(0, $template->damaged_items);

        // Phase 2A change: an incomplete submission (a REQUIRED question
        // unanswered) is a DRAFT, and a draft must NOT change equipment status —
        // an in-progress inspection never erases the prior state. Previously
        // this incorrectly forced the unit to 'maintenance'.
        $this->assertSame('draft', $template->lifecycle_status->value);
        $this->assertNull($template->result);
        $this->assertSame('available', $equipment->fresh()->current_status->value);

        $this->assertDatabaseMissing('equipment_status_logs', [
            'equipment_id' => $equipment->id,
            'from_status'  => 'available',
            'to_status'    => 'maintenance',
        ]);

        // The submission log is still written (append-only audit).
        $this->assertSame(1, EquipmentRentalReadyChecklistQuestionLog::where('equipment_rental_ready_template_id', $template->id)->count());
    }
}
