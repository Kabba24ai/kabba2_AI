<?php

namespace Tests\Unit\Services\ChecklistManagement;

use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestionAnswer;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestionAnswer;
use App\Services\ChecklistManagement\QuestionCrudService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PR-B4.2 — focused tests for QuestionCrudService in isolation from any
 * controller. Uses RefreshDatabase (like CategoryCrudServiceTest) because this
 * service's job is Eloquent persistence + transaction/diff behavior — there is
 * nothing meaningful to test without a real database.
 */
class QuestionCrudServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuestionCrudService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuestionCrudService();
    }

    private function rrCategory(): RentalReadyChecklistCategory
    {
        return RentalReadyChecklistCategory::create(['category_name' => 'RR Cat']);
    }

    private function caCategory(): CustomerAdminCategory
    {
        return CustomerAdminCategory::create(['category_name' => 'CA Cat']);
    }

    // ── store() ──────────────────────────────────────────────────────────────

    public function test_store_creates_question_and_its_answers(): void
    {
        $category = $this->rrCategory();

        $question = $this->service->store(
            RentalReadyChecklistQuestion::class,
            ['question_name' => 'Q1', 'category_id' => $category->id, 'required_question' => true],
            RentalReadyChecklistQuestionAnswer::class,
            [
                ['answer_name' => 'A', 'type' => 'Rental Ready', 'index_number' => 1],
                ['answer_name' => 'B', 'type' => 'Damaged', 'index_number' => 2],
            ]
        );

        $this->assertInstanceOf(RentalReadyChecklistQuestion::class, $question);
        $this->assertSame(2, $question->answers()->count());
    }

    public function test_store_rolls_back_the_question_when_an_answer_creation_fails(): void
    {
        $category = $this->rrCategory();

        RentalReadyChecklistQuestionAnswer::creating(function () {
            throw new \RuntimeException('forced failure');
        });

        try {
            $this->service->store(
                RentalReadyChecklistQuestion::class,
                ['question_name' => 'Should not persist', 'category_id' => $category->id],
                RentalReadyChecklistQuestionAnswer::class,
                [['answer_name' => 'A', 'type' => 'Rental Ready', 'index_number' => 1]]
            );
            $this->fail('Expected exception was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('forced failure', $e->getMessage());
        }

        $this->assertSame(0, RentalReadyChecklistQuestion::count());
    }

    // ── updateWithDiffedAnswers() — Rental Ready's behavior ────────────────────

    public function test_update_with_diffed_answers_keeps_matched_ids_creates_and_deletes(): void
    {
        $category = $this->rrCategory();
        $question = RentalReadyChecklistQuestion::create(['question_name' => 'Original', 'category_id' => $category->id]);
        $kept = RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'Keep', 'type' => 'Rental Ready', 'index_number' => 1, 'question_id' => $question->id]);
        $removed = RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'Remove', 'type' => 'Damaged', 'index_number' => 2, 'question_id' => $question->id]);

        $this->service->updateWithDiffedAnswers(
            RentalReadyChecklistQuestion::class,
            RentalReadyChecklistQuestionAnswer::class,
            $question->id,
            ['question_name' => 'Updated', 'category_id' => $category->id],
            [
                ['id' => $kept->id, 'answer_name' => 'Keep (edited)', 'type' => 'Rental Ready', 'index_number' => 1],
                ['answer_name' => 'New', 'type' => 'Maint. Hold', 'index_number' => 2],
            ]
        );

        $this->assertSame('Updated', $question->fresh()->question_name);
        $this->assertDatabaseHas('rental_ready_checklist_question_answers', ['id' => $kept->id, 'answer_name' => 'Keep (edited)']);
        $this->assertSoftDeleted('rental_ready_checklist_question_answers', ['id' => $removed->id]);
        $this->assertSame(2, $question->answers()->count());
    }

    public function test_update_with_diffed_answers_throws_when_id_does_not_match_any_row(): void
    {
        $category = $this->rrCategory();

        $this->expectException(ModelNotFoundException::class);

        $this->service->updateWithDiffedAnswers(
            RentalReadyChecklistQuestion::class,
            RentalReadyChecklistQuestionAnswer::class,
            999999,
            ['question_name' => 'X', 'category_id' => $category->id],
            []
        );
    }

    // ── updateWithReplacedAnswers() — Customer Admin's behavior ────────────────

    public function test_update_with_replaced_answers_deletes_all_and_recreates(): void
    {
        $category = $this->caCategory();
        $question = CustomerAdminQuestion::create([
            'question_name' => 'Original', 'category_id' => $category->id,
            'question_delivery_text' => 'D', 'question_return_text' => 'R',
        ]);
        $original = CustomerAdminQuestionAnswer::create([
            'question_id' => $question->id, 'index_number' => 1,
            'answer_delivery_text' => 'Old', 'answer_return_text' => 'Old',
        ]);

        $this->service->updateWithReplacedAnswers(
            CustomerAdminQuestion::class,
            CustomerAdminQuestionAnswer::class,
            $question->id,
            ['question_name' => 'Updated', 'category_id' => $category->id, 'question_delivery_text' => 'D2', 'question_return_text' => 'R2'],
            [['index_number' => 1, 'answer_delivery_text' => 'Old', 'answer_return_text' => 'Old', 'delivery_amt' => 0, 'return_amt' => 0]]
        );

        $this->assertDatabaseMissing('customer_admin_question_answers', ['id' => $original->id]);
        $this->assertSame(1, $question->answers()->count());
        $this->assertNotSame($original->id, $question->answers()->first()->id);
    }

    // ── delete() — soft vs hard, pass-through ──────────────────────────────────

    public function test_delete_soft_deletes_question_and_answers_when_model_uses_soft_deletes(): void
    {
        $category = $this->rrCategory();
        $question = RentalReadyChecklistQuestion::create(['question_name' => 'Q', 'category_id' => $category->id]);
        $answer = RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'A', 'type' => 'Rental Ready', 'index_number' => 1, 'question_id' => $question->id]);

        $this->service->delete(RentalReadyChecklistQuestion::class, $question->unique_id);

        $this->assertSoftDeleted('rental_ready_checklist_questions', ['id' => $question->id]);
        $this->assertSoftDeleted('rental_ready_checklist_question_answers', ['id' => $answer->id]);
    }

    public function test_delete_hard_deletes_and_cascades_when_model_does_not_use_soft_deletes(): void
    {
        $category = $this->caCategory();
        $question = CustomerAdminQuestion::create([
            'question_name' => 'Q', 'category_id' => $category->id,
            'question_delivery_text' => 'D', 'question_return_text' => 'R',
        ]);
        $answer = CustomerAdminQuestionAnswer::create(['question_id' => $question->id, 'index_number' => 1, 'answer_delivery_text' => 'A', 'answer_return_text' => 'A']);

        $this->service->delete(CustomerAdminQuestion::class, $question->unique_id);

        $this->assertDatabaseMissing('customer_admin_questions', ['id' => $question->id]);
        $this->assertDatabaseMissing('customer_admin_question_answers', ['id' => $answer->id]);
    }

    public function test_delete_throws_model_not_found_when_unique_id_does_not_match_any_row(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->delete(RentalReadyChecklistQuestion::class, 'NONEXISTENT-ID');
    }

    // ── copy() — domain-supplied unique_id strategy + answer mapping ──────────

    public function test_copy_duplicates_question_and_answers_via_supplied_callbacks(): void
    {
        $category = $this->rrCategory();
        $question = RentalReadyChecklistQuestion::create(['question_name' => 'Original', 'category_id' => $category->id]);
        RentalReadyChecklistQuestionAnswer::create(['answer_name' => 'A', 'type' => 'Rental Ready', 'index_number' => 1, 'question_id' => $question->id]);

        $copy = $this->service->copy(
            RentalReadyChecklistQuestion::class,
            RentalReadyChecklistQuestionAnswer::class,
            $question->id,
            function ($newQuestion, $original) {
                $newQuestion->unique_id = 'FORCED-UNIQUE-ID';
            },
            fn ($answer) => ['answer_name' => $answer->answer_name, 'type' => $answer->type, 'index_number' => $answer->index_number]
        );

        $this->assertSame('Original (Copy)', $copy->question_name);
        $this->assertSame('FORCED-UNIQUE-ID', $copy->unique_id);
        $this->assertSame(1, $copy->answers()->count());
        $this->assertSame(1, $question->fresh()->answers()->count());
    }

    public function test_copy_throws_when_id_does_not_match_any_row(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->copy(
            RentalReadyChecklistQuestion::class,
            RentalReadyChecklistQuestionAnswer::class,
            999999,
            fn ($newQuestion, $original) => null,
            fn ($answer) => []
        );
    }
}
