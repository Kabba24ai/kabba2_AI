<?php

namespace App\Services\ChecklistManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * QuestionCrudService — shared transaction-wrapped CRUD mechanics for the Rental
 * Ready and Customer Admin Question controllers (PR-B4.2, Phase 2 Track B).
 *
 * Extracted from 8 controllers whose transaction/lookup/persistence shape was
 * identical, while their answer schemas and update strategies are genuinely
 * different business behaviors (see docs/checklist-system-audit/PR-B4_2_QUESTION_READINESS.md
 * and PR-B4_2_QUESTION_REFACTOR.md). This service therefore exposes TWO distinct
 * update methods (updateWithDiffedAnswers / updateWithReplacedAnswers) rather than
 * one method with a behavior-switching flag — each is independently testable and
 * neither can be silently misused by a caller passing the wrong boolean.
 *
 * Every method takes the target model classes as parameters — it has no
 * knowledge of Rental Ready vs. Customer Admin. Controllers still build their own
 * per-domain $questionAttributes/$answerRows arrays from their own request/options
 * shape (JSON string vs. array, different field names) — this service only
 * persists already-domain-mapped arrays. It has no opinion on soft vs. hard
 * delete: delete() simply calls ->delete() and lets each model's own SoftDeletes
 * trait (or absence of it) decide the real outcome, exactly as before this
 * refactor.
 */
class QuestionCrudService
{
    /**
     * Create a question and its answer rows in one transaction.
     *
     * @param  class-string<Model>  $questionModelClass
     * @param  class-string<Model>  $answerModelClass
     * @param  array<int, array<string, mixed>>  $answerRows  each row's own attributes; 'question_id' is added automatically.
     */
    public function store(string $questionModelClass, array $questionAttributes, string $answerModelClass, array $answerRows): Model
    {
        return DB::transaction(function () use ($questionModelClass, $questionAttributes, $answerModelClass, $answerRows) {
            $question = $questionModelClass::create($questionAttributes);

            foreach ($answerRows as $row) {
                $answerModelClass::create($row + ['question_id' => $question->id]);
            }

            return $question;
        });
    }

    /**
     * Update a question, diffing its answers against the submitted rows: a row
     * carrying an 'id' key that matches an existing answer is updated in place
     * (keeping that answer's own ID); a row with no matching existing answer is
     * created fresh; any existing answer not represented in the new list is
     * deleted. This is Rental Ready's exact update behavior — answer identity is
     * preserved across an edit wherever possible.
     *
     * @param  class-string<Model>  $questionModelClass
     * @param  class-string<Model>  $answerModelClass
     * @param  array<int, array<string, mixed>>  $answerRows  each row may include an 'id' key (int|null); it is stripped before create()/update().
     */
    public function updateWithDiffedAnswers(string $questionModelClass, string $answerModelClass, int $id, array $questionAttributes, array $answerRows): Model
    {
        return DB::transaction(function () use ($questionModelClass, $answerModelClass, $id, $questionAttributes, $answerRows) {
            $question = $questionModelClass::findOrFail($id);
            $question->update($questionAttributes);

            $existingAnswers = $question->answers()->get()->keyBy('id');
            $keepIds = [];

            foreach ($answerRows as $row) {
                $rowId = $row['id'] ?? null;
                unset($row['id']);

                if (!empty($rowId) && $existingAnswers->has($rowId)) {
                    $answer = $existingAnswers[$rowId];
                    $answer->update($row);
                    $keepIds[] = $answer->id;
                } else {
                    $new = $answerModelClass::create($row + ['question_id' => $question->id]);
                    $keepIds[] = $new->id;
                }
            }

            $question->answers()->whereNotIn('id', $keepIds)->delete();

            return $question;
        });
    }

    /**
     * Update a question, unconditionally deleting all its existing answers and
     * recreating every one from the submitted rows. This is Customer Admin's
     * exact update behavior — answer identity is NOT preserved; every answer gets
     * a new ID on every edit, even one whose content didn't change.
     *
     * @param  class-string<Model>  $questionModelClass
     * @param  class-string<Model>  $answerModelClass
     * @param  array<int, array<string, mixed>>  $answerRows
     */
    public function updateWithReplacedAnswers(string $questionModelClass, string $answerModelClass, int $id, array $questionAttributes, array $answerRows): Model
    {
        return DB::transaction(function () use ($questionModelClass, $answerModelClass, $id, $questionAttributes, $answerRows) {
            $question = $questionModelClass::findOrFail($id);
            $question->update($questionAttributes);

            $question->answers()->delete();

            foreach ($answerRows as $row) {
                $answerModelClass::create($row + ['question_id' => $question->id]);
            }

            return $question;
        });
    }

    /**
     * Look up a question by unique_id (firstOrFail — unchanged from today's
     * DeleteControllers), explicitly delete its answers, then delete the question
     * itself. Whether either delete is soft or a real, cascading hard delete is
     * entirely determined by each model's own SoftDeletes trait (or absence of
     * it) — this method has no opinion on it. The explicit answers()->delete()
     * call remains necessary for Rental Ready's soft-delete chain (a soft delete
     * on the question never cascades to its answers at the DB level).
     *
     * @param  class-string<Model>  $questionModelClass
     */
    public function delete(string $questionModelClass, string $uniqueId): void
    {
        DB::transaction(function () use ($questionModelClass, $uniqueId) {
            $question = $questionModelClass::where('unique_id', $uniqueId)->firstOrFail();
            $question->answers()->delete();
            $question->delete();
        });
    }

    /**
     * Duplicate a question (by numeric primary key — unchanged from today's
     * CopyControllers) and all of its answers. $assignFreshUniqueId is called
     * with the replicated (unsaved) question and the original, and is
     * responsible for however that domain ensures a fresh unique_id (Rental
     * Ready explicitly generates one; Customer Admin resets it to null and
     * relies on the model's own boot() hook) — this method does not assume
     * either strategy. $mapAnswerForCopy is called once per original answer and
     * must return that domain's own create-attributes array (minus question_id,
     * added automatically).
     *
     * @param  class-string<Model>  $questionModelClass
     * @param  class-string<Model>  $answerModelClass
     * @param  callable(Model $newQuestion, Model $original): void  $assignFreshUniqueId
     * @param  callable(Model $answer): array<string, mixed>  $mapAnswerForCopy
     */
    public function copy(string $questionModelClass, string $answerModelClass, int $id, callable $assignFreshUniqueId, callable $mapAnswerForCopy): Model
    {
        return DB::transaction(function () use ($questionModelClass, $answerModelClass, $id, $assignFreshUniqueId, $mapAnswerForCopy) {
            $question = $questionModelClass::with('answers')->findOrFail($id);

            $newQuestion = $question->replicate();
            $newQuestion->question_name = $question->question_name . ' (Copy)';
            $assignFreshUniqueId($newQuestion, $question);
            $newQuestion->save();

            foreach ($question->answers as $answer) {
                $answerModelClass::create($mapAnswerForCopy($answer) + ['question_id' => $newQuestion->id]);
            }

            return $newQuestion;
        });
    }
}
