<?php

namespace Tests\Feature\ChecklistManagement\Questions;

use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestionAnswer;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestionAnswer;
use App\Models\Iam\Personnel\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PR-B4.2 — characterization baseline for the Rental Ready and Customer Admin
 * Question CRUD controllers, before any shared-service refactor. See
 * docs/checklist-system-audit/PR-B4_2_QUESTION_READINESS.md for the full
 * duplication/divergence analysis this suite locks in place.
 *
 * IMPORTANT: CustomerAdmin\Question\StoreController's catch block contains a live
 * dd() call (readiness doc §0). No test in this file forces that controller into
 * its catch block — doing so would call exit() inside the test process itself.
 * This is a deliberate, documented gap, not an oversight.
 */
class QuestionCrudCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Question', 'last_name' => 'Admin',
            'email' => 'question-crud-admin@example.com', 'password' => bcrypt('password'),
        ]);
    }

    private function postAs(string $routeName, array $payload = [], array $routeParams = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)->post(route($routeName, $routeParams), $payload);
    }

    private function putAs(string $routeName, array $routeParams, array $payload = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)->put(route($routeName, $routeParams), $payload);
    }

    private function deleteAs(string $routeName, array $routeParams): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)->delete(route($routeName, $routeParams));
    }

    private function rrOptions(array $options): string
    {
        return json_encode($options);
    }

    // ── 1/2. Store happy path ────────────────────────────────────────────────

    public function test_rental_ready_question_store_creates_question_with_answers(): void
    {
        $category = RentalReadyChecklistCategory::create(['category_name' => 'RR Cat']);

        $response = $this->postAs('admin.checklist-management.rental-ready.questions.store', [
            'question_name'     => 'Is the bucket intact?',
            'category_id'       => $category->id,
            'required_question' => 1,
            'options'           => $this->rrOptions([
                ['text' => 'Yes', 'status' => 'Rental Ready'],
                ['text' => 'No',  'status' => 'Damaged'],
            ]),
        ]);

        $response->assertRedirect(route('admin.checklist-management.rental-ready.index'));
        $response->assertSessionHas('active_tab', 'questions');
        $response->assertSessionHas('active_subtab', 'questions');
        $response->assertSessionMissing('success');

        $question = RentalReadyChecklistQuestion::where('question_name', 'Is the bucket intact?')->firstOrFail();
        $this->assertSame($category->id, $question->category_id);
        $this->assertSame(2, $question->answers()->count());
        $this->assertDatabaseHas('rental_ready_checklist_question_answers', ['answer_name' => 'Yes', 'type' => 'Rental Ready']);
        $this->assertDatabaseHas('rental_ready_checklist_question_answers', ['answer_name' => 'No', 'type' => 'Damaged']);

        // No cross-tree mirror row.
        $this->assertSame(0, CustomerAdminQuestion::count());
    }

    public function test_customer_admin_question_store_creates_question_with_answers(): void
    {
        $category = CustomerAdminCategory::create(['category_name' => 'CA Cat']);

        $response = $this->postAs('admin.checklist-management.customer-admin.questions.store', [
            'question_name'          => 'Any visible damage?',
            'category_id'            => $category->id,
            'question_delivery_text' => 'Check before delivery',
            'question_return_text'   => 'Check on return',
            'required_question'      => 1,
            'options' => [
                ['answer_delivery_text' => 'No damage', 'answer_return_text' => 'No damage', 'delivery_amt' => 0, 'return_amt' => 0, 'is_damaged' => 0],
                ['answer_delivery_text' => 'Damaged',   'answer_return_text' => 'Damaged',   'delivery_amt' => 0, 'return_amt' => 50, 'is_damaged' => 1],
            ],
        ]);

        $response->assertRedirect(route('admin.checklist-management.customer-admin.index'));
        $response->assertSessionHas('active_tab', 'questions');
        $response->assertSessionHas('active_subtab', 'questions');
        // Unlike Category's Customer Admin controllers, Question's Store does NOT
        // chain ->with('success', ...) — confirmed absent in either tree here.
        $response->assertSessionMissing('success');

        $question = CustomerAdminQuestion::where('question_name', 'Any visible damage?')->firstOrFail();
        $this->assertSame($category->id, $question->category_id);
        $this->assertSame(2, $question->answers()->count());
        $this->assertDatabaseHas('customer_admin_question_answers', ['answer_delivery_text' => 'No damage', 'is_damaged' => 0]);
        $this->assertDatabaseHas('customer_admin_question_answers', ['answer_delivery_text' => 'Damaged', 'is_damaged' => 1]);

        $this->assertSame(0, RentalReadyChecklistQuestion::count());
    }

    // ── 3. Rental Ready update — diff behavior (keep/create/delete) ─────────────

    public function test_rental_ready_question_update_diffs_answers_keeping_ids_creating_and_deleting(): void
    {
        $category = RentalReadyChecklistCategory::create(['category_name' => 'RR Cat']);
        $question = RentalReadyChecklistQuestion::create([
            'question_name' => 'Original', 'category_id' => $category->id, 'required_question' => true,
        ]);
        $kept = RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'Keep me', 'type' => 'Rental Ready', 'index_number' => 1, 'question_id' => $question->id]);
        $removed = RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'Remove me', 'type' => 'Damaged', 'index_number' => 2, 'question_id' => $question->id]);

        $this->putAs('admin.checklist-management.rental-ready.questions.update', [$question->id], [
            'question_name'     => 'Updated',
            'category_id'       => $category->id,
            'required_question' => 1,
            'options'           => $this->rrOptions([
                ['id' => $kept->id, 'text' => 'Keep me (edited)', 'status' => 'Rental Ready'],
                ['text' => 'New answer', 'status' => 'Maint. Hold'],
            ]),
        ]);

        // Kept answer retains its ID, content updated.
        $this->assertDatabaseHas('rental_ready_checklist_question_answers', [
            'id' => $kept->id, 'answer_name' => 'Keep me (edited)',
        ]);
        // Omitted answer is deleted (soft, per RentalReadyChecklistQuestionAnswer's SoftDeletes).
        $this->assertSoftDeleted('rental_ready_checklist_question_answers', ['id' => $removed->id]);
        // A brand-new answer was created for the option with no id.
        $this->assertDatabaseHas('rental_ready_checklist_question_answers', ['answer_name' => 'New answer', 'type' => 'Maint. Hold']);
        $this->assertSame(2, $question->answers()->count());
    }

    // ── 4. Customer Admin update — wipe and recreate ────────────────────────────

    public function test_customer_admin_question_update_wipes_and_recreates_all_answers(): void
    {
        $category = CustomerAdminCategory::create(['category_name' => 'CA Cat']);
        $question = CustomerAdminQuestion::create([
            'question_name' => 'Original', 'category_id' => $category->id,
            'question_delivery_text' => 'D', 'question_return_text' => 'R', 'required_question' => true,
        ]);
        $original = CustomerAdminQuestionAnswer::create([
            'question_id' => $question->id, 'index_number' => 1,
            'answer_delivery_text' => 'Old', 'answer_return_text' => 'Old',
        ]);

        $this->putAs('admin.checklist-management.customer-admin.questions.update', [$question->id], [
            'question_name'          => 'Updated',
            'category_id'            => $category->id,
            'question_delivery_text' => 'D2',
            'question_return_text'   => 'R2',
            'required_question'      => 1,
            'options' => [
                ['answer_delivery_text' => 'Old', 'answer_return_text' => 'Old', 'delivery_amt' => 0, 'return_amt' => 0], // same content, new row expected
            ],
        ]);

        // The original row is completely gone — no ID preservation, unlike Rental Ready.
        $this->assertDatabaseMissing('customer_admin_question_answers', ['id' => $original->id]);
        // A new row with the same content now exists under a new ID.
        $this->assertSame(1, $question->answers()->count());
        $this->assertNotSame($original->id, $question->answers()->first()->id);
    }

    // ── 5/6. Delete: soft vs hard ────────────────────────────────────────────

    public function test_rental_ready_question_delete_soft_deletes_question_and_answers(): void
    {
        $category = RentalReadyChecklistCategory::create(['category_name' => 'RR Cat']);
        $question = RentalReadyChecklistQuestion::create(['question_name' => 'To delete', 'category_id' => $category->id]);
        $answer = RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'A', 'type' => 'Rental Ready', 'index_number' => 1, 'question_id' => $question->id]);

        $response = $this->deleteAs('admin.checklist-management.rental-ready.questions.delete', [$question->unique_id]);

        $response->assertRedirect(route('admin.checklist-management.rental-ready.index'));
        $this->assertSoftDeleted('rental_ready_checklist_questions', ['id' => $question->id]);
        $this->assertSoftDeleted('rental_ready_checklist_question_answers', ['id' => $answer->id]);
    }

    public function test_customer_admin_question_delete_hard_deletes_question_and_cascades_answers(): void
    {
        $category = CustomerAdminCategory::create(['category_name' => 'CA Cat']);
        $question = CustomerAdminQuestion::create([
            'question_name' => 'To delete', 'category_id' => $category->id,
            'question_delivery_text' => 'D', 'question_return_text' => 'R',
        ]);
        $answer = CustomerAdminQuestionAnswer::create(['question_id' => $question->id, 'index_number' => 1, 'answer_delivery_text' => 'A', 'answer_return_text' => 'A']);

        $response = $this->deleteAs('admin.checklist-management.customer-admin.questions.delete', [$question->unique_id]);

        $response->assertRedirect(route('admin.checklist-management.customer-admin.index'));
        $this->assertDatabaseMissing('customer_admin_questions', ['id' => $question->id]);
        $this->assertDatabaseMissing('customer_admin_question_answers', ['id' => $answer->id]);
    }

    // ── 7/8. Copy ────────────────────────────────────────────────────────────

    public function test_rental_ready_question_copy_duplicates_question_and_answers(): void
    {
        $category = RentalReadyChecklistCategory::create(['category_name' => 'RR Cat']);
        $question = RentalReadyChecklistQuestion::create(['question_name' => 'Original Q', 'category_id' => $category->id]);
        RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'A1', 'type' => 'Rental Ready', 'index_number' => 1, 'question_id' => $question->id]);
        RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'A2', 'type' => 'Damaged', 'index_number' => 2, 'question_id' => $question->id]);

        $response = $this->postAs('admin.checklist-management.rental-ready.questions.copy', [], [$question->id]);

        $response->assertRedirect(route('admin.checklist-management.rental-ready.index'));

        $copy = RentalReadyChecklistQuestion::where('question_name', 'Original Q (Copy)')->firstOrFail();
        $this->assertNotSame($question->unique_id, $copy->unique_id);
        $this->assertSame(2, $copy->answers()->count());
        $this->assertDatabaseHas('rental_ready_checklist_question_answers', ['question_id' => $copy->id, 'answer_name' => 'A1']);
        $this->assertDatabaseHas('rental_ready_checklist_question_answers', ['question_id' => $copy->id, 'answer_name' => 'A2']);

        // Original untouched.
        $this->assertSame(2, $question->fresh()->answers()->count());
    }

    public function test_customer_admin_question_copy_duplicates_question_and_answers(): void
    {
        $category = CustomerAdminCategory::create(['category_name' => 'CA Cat']);
        $question = CustomerAdminQuestion::create([
            'question_name' => 'Original Q', 'category_id' => $category->id,
            'question_delivery_text' => 'D', 'question_return_text' => 'R',
        ]);
        CustomerAdminQuestionAnswer::create(['question_id' => $question->id, 'index_number' => 1, 'answer_delivery_text' => 'A1', 'answer_return_text' => 'A1']);

        $response = $this->postAs('admin.checklist-management.customer-admin.questions.copy', [], [$question->id]);

        $response->assertRedirect(route('admin.checklist-management.customer-admin.index'));

        $copy = CustomerAdminQuestion::where('question_name', 'Original Q (Copy)')->firstOrFail();
        $this->assertNotSame($question->unique_id, $copy->unique_id);
        $this->assertSame(1, $copy->answers()->count());
        $this->assertDatabaseHas('customer_admin_question_answers', ['question_id' => $copy->id, 'answer_delivery_text' => 'A1']);

        $this->assertSame(1, $question->fresh()->answers()->count());
    }

    // ── 9/10. Validation ─────────────────────────────────────────────────────

    public function test_rental_ready_question_store_requires_category_id_and_question_name(): void
    {
        $response = $this->postAs('admin.checklist-management.rental-ready.questions.store', [
            'options' => $this->rrOptions([['text' => 'Yes', 'status' => 'Rental Ready']]),
        ]);

        $response->assertSessionHasErrors(['category_id', 'question_name']);
        $this->assertSame(0, RentalReadyChecklistQuestion::count());
    }

    public function test_customer_admin_question_store_requires_delivery_and_return_text(): void
    {
        $category = CustomerAdminCategory::create(['category_name' => 'CA Cat']);

        $response = $this->postAs('admin.checklist-management.customer-admin.questions.store', [
            'question_name' => 'Missing delivery/return text',
            'category_id'   => $category->id,
            'options'       => [['answer_delivery_text' => 'x', 'answer_return_text' => 'x', 'delivery_amt' => 0, 'return_amt' => 0]],
        ]);

        $response->assertSessionHasErrors(['question_delivery_text', 'question_return_text']);
        $this->assertSame(0, CustomerAdminQuestion::count());
    }

    // ── 11/12. Update with nonexistent numeric id — safe rollback path ──────────

    public function test_rental_ready_question_update_with_nonexistent_id_rolls_back_and_flashes_error(): void
    {
        $category = RentalReadyChecklistCategory::create(['category_name' => 'RR Cat']);

        $response = $this->putAs('admin.checklist-management.rental-ready.questions.update', [999999], [
            'question_name' => 'Whatever',
            'category_id'   => $category->id,
            'options'       => $this->rrOptions([]),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
    }

    public function test_customer_admin_question_update_with_nonexistent_id_rolls_back_and_flashes_error(): void
    {
        $category = CustomerAdminCategory::create(['category_name' => 'CA Cat']);

        $response = $this->putAs('admin.checklist-management.customer-admin.questions.update', [999999], [
            'question_name'          => 'Whatever',
            'category_id'            => $category->id,
            'question_delivery_text' => 'D',
            'question_return_text'   => 'R',
            'options'                => [['answer_delivery_text' => 'x', 'answer_return_text' => 'x', 'delivery_amt' => 0, 'return_amt' => 0]],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
    }

    // ── 13/14. Category relationship integrity ──────────────────────────────

    public function test_rental_ready_question_category_relationship_resolves_correctly(): void
    {
        $category = RentalReadyChecklistCategory::create(['category_name' => 'RR Cat']);

        $this->postAs('admin.checklist-management.rental-ready.questions.store', [
            'question_name' => 'Category link test',
            'category_id'   => $category->id,
            'options'       => $this->rrOptions([['text' => 'Yes', 'status' => 'Rental Ready']]),
        ]);

        $question = RentalReadyChecklistQuestion::where('question_name', 'Category link test')->firstOrFail();
        $this->assertSame($category->id, $question->category->id);
    }

    public function test_customer_admin_question_category_relationship_resolves_correctly(): void
    {
        $category = CustomerAdminCategory::create(['category_name' => 'CA Cat']);

        $this->postAs('admin.checklist-management.customer-admin.questions.store', [
            'question_name'          => 'Category link test',
            'category_id'            => $category->id,
            'question_delivery_text' => 'D',
            'question_return_text'   => 'R',
            'options'                => [['answer_delivery_text' => 'x', 'answer_return_text' => 'x', 'delivery_amt' => 0, 'return_amt' => 0]],
        ]);

        $question = CustomerAdminQuestion::where('question_name', 'Category link test')->firstOrFail();
        $this->assertSame($category->id, $question->category->id);
    }

    // ── 15/16. No mirror behavior in either direction ───────────────────────

    public function test_rental_ready_question_store_creates_no_customer_admin_mirror(): void
    {
        $category = RentalReadyChecklistCategory::create(['category_name' => 'RR Cat']);

        $this->postAs('admin.checklist-management.rental-ready.questions.store', [
            'question_name' => 'No mirror check',
            'category_id'   => $category->id,
            'options'       => $this->rrOptions([['text' => 'Yes', 'status' => 'Rental Ready']]),
        ]);

        $this->assertSame(0, CustomerAdminQuestion::count());
    }

    public function test_customer_admin_question_store_creates_no_rental_ready_mirror(): void
    {
        $category = CustomerAdminCategory::create(['category_name' => 'CA Cat']);

        $this->postAs('admin.checklist-management.customer-admin.questions.store', [
            'question_name'          => 'No mirror check',
            'category_id'            => $category->id,
            'question_delivery_text' => 'D',
            'question_return_text'   => 'R',
            'options'                => [['answer_delivery_text' => 'x', 'answer_return_text' => 'x', 'delivery_amt' => 0, 'return_amt' => 0]],
        ]);

        $this->assertSame(0, RentalReadyChecklistQuestion::count());
    }

    // ── 17. Rental Ready store atomicity (question + answers) ──────────────

    public function test_rental_ready_question_store_rolls_back_question_when_an_answer_creation_fails(): void
    {
        $category = RentalReadyChecklistCategory::create(['category_name' => 'RR Cat']);

        $failOnSecond = 0;
        RentalReadyChecklistQuestionAnswer::creating(function () use (&$failOnSecond) {
            $failOnSecond++;
            if ($failOnSecond === 2) {
                throw new \RuntimeException('Forced failure for PR-B4.2 atomicity test');
            }
        });

        $response = $this->postAs('admin.checklist-management.rental-ready.questions.store', [
            'question_name' => 'Should Not Persist',
            'category_id'   => $category->id,
            'options'       => $this->rrOptions([
                ['text' => 'Yes', 'status' => 'Rental Ready'],
                ['text' => 'No',  'status' => 'Damaged'],
            ]),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');

        // The question itself must not have been left behind despite being created
        // before the second answer's failure — the single transaction covers both.
        $this->assertSame(0, RentalReadyChecklistQuestion::count());
        $this->assertSame(0, RentalReadyChecklistQuestionAnswer::count());
    }

    // ── Bug-fix regression: CustomerAdmin\Question\StoreController's dd() removal ──

    /**
     * Regression test for the dd() bug-fix documented in
     * docs/checklist-system-audit/PR-B4_2_BUGFIX_DD_REMOVAL.md. Before the fix, this
     * exact scenario (an exception thrown during answer creation) would call dd(),
     * which invokes exit() and would have killed this test process outright instead
     * of returning an HTTP response — so the mere fact that this test can run to
     * completion and make normal TestResponse assertions is itself proof the defect
     * is fixed, not just a check of the surrounding flash/rollback behavior.
     */
    public function test_customer_admin_question_store_rolls_back_and_flashes_error_when_an_answer_creation_fails(): void
    {
        $category = CustomerAdminCategory::create(['category_name' => 'CA Cat']);

        $failOnSecond = 0;
        CustomerAdminQuestionAnswer::creating(function () use (&$failOnSecond) {
            $failOnSecond++;
            if ($failOnSecond === 2) {
                throw new \RuntimeException('Forced failure for PR-B4.2 dd() bug-fix regression test');
            }
        });

        $response = $this->postAs('admin.checklist-management.customer-admin.questions.store', [
            'question_name'          => 'Should Not Persist',
            'category_id'            => $category->id,
            'question_delivery_text' => 'D',
            'question_return_text'   => 'R',
            'options' => [
                ['answer_delivery_text' => 'First', 'answer_return_text' => 'First', 'delivery_amt' => 0, 'return_amt' => 0],
                ['answer_delivery_text' => 'Second', 'answer_return_text' => 'Second', 'delivery_amt' => 0, 'return_amt' => 0],
            ],
        ]);

        // Normal HTTP response, not a terminated dd() dump — the request completed
        // through the controller's catch block and returned a real redirect.
        $response->assertRedirect();
        $response->assertSessionHasErrors('error');

        // Transaction rolled back: neither the question nor the first (successfully
        // created before the forced failure) answer was left behind.
        $this->assertSame(0, CustomerAdminQuestion::count());
        $this->assertSame(0, CustomerAdminQuestionAnswer::count());
    }
}
