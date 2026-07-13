<?php

namespace Tests\Unit\Services\ChecklistManagement;

use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplate;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplateQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplateQuestion;
use App\Models\ProductManagement\ProductCategory;
use App\Services\ChecklistManagement\TemplateCrudService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PR-B4.3 — focused tests for TemplateCrudService in isolation from any
 * controller. Uses RefreshDatabase (like CategoryCrudServiceTest and
 * QuestionCrudServiceTest) because this service's job is Eloquent persistence +
 * transaction behavior — there is nothing meaningful to test without a real
 * database, and the equipment_category_id FK divergence (§below) is itself a
 * real MySQL-enforced fact this suite must exercise against a real connection.
 */
class TemplateCrudServiceTest extends TestCase
{
    use RefreshDatabase;

    private TemplateCrudService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TemplateCrudService();
    }

    private function equipmentCategory(): ProductCategory
    {
        return ProductCategory::create(['title' => 'Excavators', 'slug' => 'excavators-' . uniqid()]);
    }

    private function rrQuestion(): RentalReadyChecklistQuestion
    {
        $category = RentalReadyChecklistCategory::create(['category_name' => 'RR Cat']);
        return RentalReadyChecklistQuestion::create(['question_name' => 'Q', 'category_id' => $category->id]);
    }

    private function caQuestion(): CustomerAdminQuestion
    {
        $category = CustomerAdminCategory::create(['category_name' => 'CA Cat']);
        return CustomerAdminQuestion::create([
            'question_name' => 'Q', 'category_id' => $category->id,
            'question_delivery_text' => 'D', 'question_return_text' => 'R',
        ]);
    }

    // ── store() ──────────────────────────────────────────────────────────────

    public function test_store_creates_template_and_its_question_rows(): void
    {
        $equipmentCategory = $this->equipmentCategory();
        $q1 = $this->rrQuestion();
        $q2 = $this->rrQuestion();

        $template = $this->service->store(
            RentalReadyChecklistTemplate::class,
            ['template_name' => 'T1', 'equipment_category_id' => $equipmentCategory->id],
            RentalReadyChecklistTemplateQuestion::class,
            [
                ['question_id' => $q1->id, 'index_number' => 1],
                ['question_id' => $q2->id, 'index_number' => 2],
            ]
        );

        $this->assertInstanceOf(RentalReadyChecklistTemplate::class, $template);
        $this->assertSame(2, $template->templateQuestions()->count());
    }

    public function test_store_rolls_back_the_template_when_a_question_row_creation_fails(): void
    {
        $equipmentCategory = $this->equipmentCategory();
        $q1 = $this->rrQuestion();

        RentalReadyChecklistTemplateQuestion::creating(function () {
            throw new \RuntimeException('forced failure');
        });

        try {
            $this->service->store(
                RentalReadyChecklistTemplate::class,
                ['template_name' => 'Should not persist', 'equipment_category_id' => $equipmentCategory->id],
                RentalReadyChecklistTemplateQuestion::class,
                [['question_id' => $q1->id, 'index_number' => 1]]
            );
            $this->fail('Expected exception was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('forced failure', $e->getMessage());
        }

        $this->assertSame(0, RentalReadyChecklistTemplate::count());
    }

    public function test_store_enforces_the_equipment_category_foreign_key_for_rental_ready_only(): void
    {
        // Rental Ready's equipment_category_id is a real FK — a nonexistent id throws.
        $this->expectException(QueryException::class);

        $this->service->store(
            RentalReadyChecklistTemplate::class,
            ['template_name' => 'No real category', 'equipment_category_id' => 999999],
            RentalReadyChecklistTemplateQuestion::class,
            []
        );
    }

    public function test_store_does_not_enforce_the_equipment_category_foreign_key_for_customer_admin(): void
    {
        // Customer Admin's equipment_category_id is still a plain string column — no FK.
        $template = $this->service->store(
            CustomerAdminTemplate::class,
            ['template_name' => 'No real category', 'equipment_category_id' => 999999],
            CustomerAdminTemplateQuestion::class,
            []
        );

        $this->assertSame(999999, (int) $template->equipment_category_id);
        $this->assertDatabaseHas('customer_admin_templates', ['equipment_category_id' => 999999]);
    }

    // ── updateWithReplacedQuestions() — identical strategy, both trees ─────────

    public function test_update_with_replaced_questions_wipes_and_recreates_preserving_order(): void
    {
        $equipmentCategory = $this->equipmentCategory();
        $q1 = $this->rrQuestion();
        $q2 = $this->rrQuestion();
        $q3 = $this->rrQuestion();

        $template = RentalReadyChecklistTemplate::create(['template_name' => 'Original', 'equipment_category_id' => $equipmentCategory->id]);
        $originalLink = RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $q1->id, 'index_number' => 1]);

        $this->service->updateWithReplacedQuestions(
            RentalReadyChecklistTemplate::class,
            RentalReadyChecklistTemplateQuestion::class,
            $template->unique_id,
            ['template_name' => 'Updated', 'equipment_category_id' => $equipmentCategory->id],
            [
                ['question_id' => $q3->id, 'index_number' => 1],
                ['question_id' => $q2->id, 'index_number' => 2],
            ]
        );

        $this->assertSame('Updated', $template->fresh()->template_name);
        $this->assertSoftDeleted('rental_ready_checklist_template_questions', ['id' => $originalLink->id]);
        $this->assertSame([$q3->id, $q2->id], $template->templateQuestions()->pluck('question_id')->all());
    }

    public function test_update_with_replaced_questions_throws_when_unique_id_does_not_match_any_row(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->updateWithReplacedQuestions(
            RentalReadyChecklistTemplate::class,
            RentalReadyChecklistTemplateQuestion::class,
            'NONEXISTENT-ID',
            ['template_name' => 'X', 'equipment_category_id' => $this->equipmentCategory()->id],
            []
        );
    }

    // ── delete() — soft vs hard, pass-through ──────────────────────────────────

    public function test_delete_soft_deletes_template_and_questions_when_model_uses_soft_deletes(): void
    {
        $equipmentCategory = $this->equipmentCategory();
        $q1 = $this->rrQuestion();
        $template = RentalReadyChecklistTemplate::create(['template_name' => 'T', 'equipment_category_id' => $equipmentCategory->id]);
        $link = RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $q1->id, 'index_number' => 1]);

        $this->service->delete(RentalReadyChecklistTemplate::class, $template->unique_id);

        $this->assertSoftDeleted('rental_ready_checklist_templates', ['id' => $template->id]);
        $this->assertSoftDeleted('rental_ready_checklist_template_questions', ['id' => $link->id]);
    }

    public function test_delete_hard_deletes_and_cascades_when_model_does_not_use_soft_deletes(): void
    {
        $equipmentCategory = $this->equipmentCategory();
        $q1 = $this->caQuestion();
        $template = CustomerAdminTemplate::create(['template_name' => 'T', 'equipment_category_id' => $equipmentCategory->id]);
        $link = CustomerAdminTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $q1->id, 'index_number' => 1]);

        $this->service->delete(CustomerAdminTemplate::class, $template->unique_id);

        $this->assertDatabaseMissing('customer_admin_templates', ['id' => $template->id]);
        $this->assertDatabaseMissing('customer_admin_template_questions', ['id' => $link->id]);
    }

    public function test_delete_throws_model_not_found_when_unique_id_does_not_match_any_row(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->delete(RentalReadyChecklistTemplate::class, 'NONEXISTENT-ID');
    }

    // ── copy() — ordered duplication via supplied mapping closure ──────────────

    public function test_copy_duplicates_template_and_ordered_questions(): void
    {
        $equipmentCategory = $this->equipmentCategory();
        $q1 = $this->rrQuestion();
        $q2 = $this->rrQuestion();
        $template = RentalReadyChecklistTemplate::create(['template_name' => 'Original', 'equipment_category_id' => $equipmentCategory->id]);
        RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $q1->id, 'index_number' => 1]);
        RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $q2->id, 'index_number' => 2]);

        $copy = $this->service->copy(
            RentalReadyChecklistTemplate::class,
            RentalReadyChecklistTemplateQuestion::class,
            $template->unique_id,
            'TQS',
            fn ($tq) => ['question_id' => $tq->question_id, 'index_number' => $tq->index_number]
        );

        $this->assertSame('Original (Copy)', $copy->template_name);
        $this->assertNotSame($template->unique_id, $copy->unique_id);
        $this->assertStringStartsWith('TQS', $copy->unique_id);
        $this->assertSame([$q1->id, $q2->id], $copy->templateQuestions()->pluck('question_id')->all());
        $this->assertSame(2, $template->fresh()->templateQuestions()->count());
    }

    public function test_copy_throws_when_unique_id_does_not_match_any_row(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->copy(
            RentalReadyChecklistTemplate::class,
            RentalReadyChecklistTemplateQuestion::class,
            'NONEXISTENT-ID',
            'TQS',
            fn ($tq) => []
        );
    }
}
