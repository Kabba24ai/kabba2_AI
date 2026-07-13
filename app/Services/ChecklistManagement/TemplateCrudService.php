<?php

namespace App\Services\ChecklistManagement;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * TemplateCrudService — shared transaction-wrapped CRUD mechanics for the Rental
 * Ready and Customer Admin Template controllers (PR-B4.3, Phase 2 Track B).
 *
 * Extracted from 8 controllers whose transaction/lookup/persistence shape was
 * identical (see docs/checklist-system-audit/PR-B4_3_TEMPLATE_READINESS.md and
 * PR-B4_3_TEMPLATE_REFACTOR.md). Unlike QuestionCrudService (PR-B4.2), Templates'
 * update strategy is IDENTICAL in both trees — both wipe every existing
 * template-question row and recreate from the submitted list — so there is only
 * ONE update method here, not two.
 *
 * Every method takes the target model classes as parameters — it has no
 * knowledge of Rental Ready vs. Customer Admin. It has no opinion on soft vs.
 * hard delete (delete() simply calls ->delete() and lets each model's own
 * SoftDeletes trait, or absence of it, decide the real outcome) and no opinion on
 * equipment_category_id's foreign-key enforcement (that is a schema-level fact
 * enforced by MySQL itself, not application code — a nonexistent category simply
 * throws inside store()'s/updateWithReplacedQuestions()'s transaction for Rental
 * Ready, and does not for Customer Admin, exactly as before this refactor).
 */
class TemplateCrudService
{
    /**
     * Create a template and its template-question rows in one transaction.
     *
     * @param  class-string<Model>  $templateModelClass
     * @param  class-string<Model>  $templateQuestionModelClass
     * @param  array<int, array<string, mixed>>  $questionRows  each row's own attributes; 'template_id' is added automatically.
     */
    public function store(string $templateModelClass, array $templateAttributes, string $templateQuestionModelClass, array $questionRows): Model
    {
        return DB::transaction(function () use ($templateModelClass, $templateAttributes, $templateQuestionModelClass, $questionRows) {
            $template = $templateModelClass::create($templateAttributes);

            foreach ($questionRows as $row) {
                $templateQuestionModelClass::create($row + ['template_id' => $template->id]);
            }

            return $template;
        });
    }

    /**
     * Update a template by unique_id, unconditionally deleting all its existing
     * template-question rows and recreating every one from the submitted rows.
     * This is BOTH trees' exact update behavior — unlike Questions, there is no
     * diff-and-keep-IDs variant for Templates.
     *
     * @param  class-string<Model>  $templateModelClass
     * @param  class-string<Model>  $templateQuestionModelClass
     * @param  array<int, array<string, mixed>>  $questionRows
     */
    public function updateWithReplacedQuestions(string $templateModelClass, string $templateQuestionModelClass, string $uniqueId, array $templateAttributes, array $questionRows): Model
    {
        return DB::transaction(function () use ($templateModelClass, $templateQuestionModelClass, $uniqueId, $templateAttributes, $questionRows) {
            $template = $templateModelClass::where('unique_id', $uniqueId)->firstOrFail();
            $template->update($templateAttributes);

            $templateQuestionModelClass::where('template_id', $template->id)->delete();

            foreach ($questionRows as $row) {
                $templateQuestionModelClass::create($row + ['template_id' => $template->id]);
            }

            return $template;
        });
    }

    /**
     * Look up a template by unique_id (firstOrFail — unchanged from today's
     * DeleteControllers), explicitly delete its template-questions, then delete
     * the template itself. Whether either delete is soft or a real, cascading
     * hard delete is entirely determined by each model's own SoftDeletes trait
     * (or absence of it) — this method has no opinion on it.
     *
     * @param  class-string<Model>  $templateModelClass
     */
    public function delete(string $templateModelClass, string $uniqueId): void
    {
        DB::transaction(function () use ($templateModelClass, $uniqueId) {
            $template = $templateModelClass::where('unique_id', $uniqueId)->firstOrFail();
            $template->questions()->delete();
            $template->delete();
        });
    }

    /**
     * Duplicate a template (by unique_id — unchanged from today's
     * CopyControllers) and all of its template-question links, in their
     * original index-order. $uniqueIdPrefix is passed straight to
     * ModelHelper::generateUniqueID() exactly as each original CopyController
     * called it — including preserving today's Customer Admin quirk of using the
     * Rental Ready prefix ('TQS') rather than its own creation-time prefix
     * ('CATQS'); see PR-B4_3_TEMPLATE_REFACTOR.md for why this was found and
     * deliberately not "fixed" here. $mapQuestionForCopy is called once per
     * original template-question and must return that domain's own
     * create-attributes array (minus template_id, added automatically).
     *
     * @param  class-string<Model>  $templateModelClass
     * @param  class-string<Model>  $templateQuestionModelClass
     * @param  callable(Model $templateQuestion): array<string, mixed>  $mapQuestionForCopy
     */
    public function copy(string $templateModelClass, string $templateQuestionModelClass, string $uniqueId, string $uniqueIdPrefix, callable $mapQuestionForCopy): Model
    {
        return DB::transaction(function () use ($templateModelClass, $templateQuestionModelClass, $uniqueId, $uniqueIdPrefix, $mapQuestionForCopy) {
            $template = $templateModelClass::where('unique_id', $uniqueId)->firstOrFail();

            $new = $template->replicate();
            $new->template_name = $template->template_name . ' (Copy)';
            $new->unique_id = ModelHelper::generateUniqueID(new $templateModelClass, $uniqueIdPrefix);
            $new->save();

            foreach ($template->templateQuestions as $templateQuestion) {
                $templateQuestionModelClass::create($mapQuestionForCopy($templateQuestion) + ['template_id' => $new->id]);
            }

            return $new;
        });
    }
}
