<?php

namespace Tests\Feature\ChecklistManagement;

use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplate;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplateQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplateQuestion;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P3-3 / DB-2: composite unique constraints on the template<->question join
 * tables, confirming duplicate pairs are rejected at the DB layer while valid
 * pairs and the existing soft-delete-then-recreate template-save flow both
 * still work. See docs/checklist-system-audit/P3_3_DB2_UNIQUE_CONSTRAINT.md.
 */
class TemplateQuestionUniqueConstraintTest extends TestCase
{
    use RefreshDatabase;

    private function makeRentalReadyTemplateAndQuestion(): array
    {
        $category = RentalReadyChecklistCategory::create(['category_name' => 'DB2 Test Category']);
        $question = RentalReadyChecklistQuestion::create([
            'question_name'      => 'DB2 Test Question',
            'category_id'        => $category->id,
            'required_question'  => true,
        ]);
        $template = RentalReadyChecklistTemplate::create([
            'template_name'   => 'DB2 Test Template',
            'active_template' => true,
        ]);

        return [$template, $question];
    }

    private function makeCustomerAdminTemplateAndQuestion(): array
    {
        $category = CustomerAdminCategory::create(['category_name' => 'DB2 CA Test Category']);
        $question = CustomerAdminQuestion::create([
            'question_name'          => 'DB2 CA Test Question',
            'category_id'            => $category->id,
            'question_delivery_text' => 'DB2 CA Test Question (delivery)',
            'question_return_text'   => 'DB2 CA Test Question (return)',
            'required_question'      => true,
        ]);
        $template = CustomerAdminTemplate::create([
            'template_name'   => 'DB2 CA Test Template',
            'active_template' => true,
        ]);

        return [$template, $question];
    }

    // ── Rental Ready: duplicate active pair rejected ────────────────────────

    public function test_rental_ready_duplicate_active_pair_cannot_be_inserted(): void
    {
        [$template, $question] = $this->makeRentalReadyTemplateAndQuestion();

        RentalReadyChecklistTemplateQuestion::create([
            'template_id'  => $template->id,
            'question_id'  => $question->id,
            'index_number' => 1,
        ]);

        $this->expectException(QueryException::class);

        RentalReadyChecklistTemplateQuestion::create([
            'template_id'  => $template->id,
            'question_id'  => $question->id,
            'index_number' => 2,
        ]);
    }

    // ── Rental Ready: valid distinct pairs still work ───────────────────────

    public function test_rental_ready_distinct_pairs_are_accepted(): void
    {
        [$template, $question1] = $this->makeRentalReadyTemplateAndQuestion();
        $category = RentalReadyChecklistCategory::first();
        $question2 = RentalReadyChecklistQuestion::create([
            'question_name'     => 'DB2 Test Question 2',
            'category_id'       => $category->id,
            'required_question' => false,
        ]);

        RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $question1->id, 'index_number' => 1]);
        RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $question2->id, 'index_number' => 2]);

        $this->assertSame(2, RentalReadyChecklistTemplateQuestion::where('template_id', $template->id)->count());
    }

    // ── Rental Ready: the existing soft-delete-then-recreate save flow (TemplateCrudService::updateWithReplacedQuestions) still works ──

    public function test_rental_ready_soft_deleting_then_recreating_the_same_pair_still_works(): void
    {
        [$template, $question] = $this->makeRentalReadyTemplateAndQuestion();

        $first = RentalReadyChecklistTemplateQuestion::create([
            'template_id'  => $template->id,
            'question_id'  => $question->id,
            'index_number' => 1,
        ]);

        // Mirrors TemplateCrudService::updateWithReplacedQuestions(): soft-delete the
        // existing row for this template, then recreate the same (template_id,
        // question_id) pair fresh — this happens on every single template save.
        $first->delete();
        $this->assertSoftDeleted($first);

        $second = RentalReadyChecklistTemplateQuestion::create([
            'template_id'  => $template->id,
            'question_id'  => $question->id,
            'index_number' => 1,
        ]);

        $this->assertNotNull($second->id);
        $this->assertNotEquals($first->id, $second->id);
        $this->assertSame(1, RentalReadyChecklistTemplateQuestion::where('template_id', $template->id)->whereNull('deleted_at')->count());
        $this->assertSame(1, RentalReadyChecklistTemplateQuestion::onlyTrashed()->where('template_id', $template->id)->count());
    }

    // ── Rental Ready: two soft-deleted rows for the same pair don't collide with each other ──

    public function test_rental_ready_multiple_soft_deleted_rows_for_the_same_pair_do_not_collide(): void
    {
        [$template, $question] = $this->makeRentalReadyTemplateAndQuestion();

        RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $question->id, 'index_number' => 1])->delete();
        RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $question->id, 'index_number' => 1])->delete();
        $active = RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $question->id, 'index_number' => 1]);

        $this->assertNotNull($active->id);
        $this->assertSame(2, RentalReadyChecklistTemplateQuestion::onlyTrashed()->where('template_id', $template->id)->count());
    }

    // ── Customer Admin: duplicate pair rejected ─────────────────────────────

    public function test_customer_admin_duplicate_pair_cannot_be_inserted(): void
    {
        [$template, $question] = $this->makeCustomerAdminTemplateAndQuestion();

        CustomerAdminTemplateQuestion::create([
            'template_id'  => $template->id,
            'question_id'  => $question->id,
            'index_number' => 1,
        ]);

        $this->expectException(QueryException::class);

        CustomerAdminTemplateQuestion::create([
            'template_id'  => $template->id,
            'question_id'  => $question->id,
            'index_number' => 2,
        ]);
    }

    // ── Customer Admin: valid distinct pairs still work ─────────────────────

    public function test_customer_admin_distinct_pairs_are_accepted(): void
    {
        [$template, $question1] = $this->makeCustomerAdminTemplateAndQuestion();
        $category = CustomerAdminCategory::first();
        $question2 = CustomerAdminQuestion::create([
            'question_name'          => 'DB2 CA Test Question 2',
            'category_id'            => $category->id,
            'question_delivery_text' => 'DB2 CA Test Question 2 (delivery)',
            'question_return_text'   => 'DB2 CA Test Question 2 (return)',
            'required_question'      => false,
        ]);

        CustomerAdminTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $question1->id, 'index_number' => 1]);
        CustomerAdminTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $question2->id, 'index_number' => 2]);

        $this->assertSame(2, CustomerAdminTemplateQuestion::where('template_id', $template->id)->count());
    }
}
