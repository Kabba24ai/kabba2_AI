<?php

namespace Tests\Feature\ChecklistManagement\Templates;

use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplate;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplateQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplateQuestion;
use App\Models\Iam\Personnel\User;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PR-B4.3 — characterization baseline for the Rental Ready and Customer Admin
 * Template CRUD controllers, before any shared-service refactor. See
 * docs/checklist-system-audit/PR-B4_3_TEMPLATE_READINESS.md for the full
 * duplication/divergence analysis this suite locks in place.
 *
 * Unlike PR-B4.2's Question suite, Templates' update strategy is IDENTICAL in
 * both trees (wipe-and-recreate) — there is no diff-and-keep-IDs variant here.
 */
class TemplateCrudCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Template', 'last_name' => 'Admin',
            'email' => 'template-crud-admin@example.com', 'password' => bcrypt('password'),
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

    private function equipmentCategory(): ProductCategory
    {
        return ProductCategory::create(['title' => 'Excavators', 'slug' => 'excavators-' . uniqid()]);
    }

    private function rrQuestion(?RentalReadyChecklistCategory $category = null): RentalReadyChecklistQuestion
    {
        $category ??= RentalReadyChecklistCategory::create(['category_name' => 'RR Cat']);
        return RentalReadyChecklistQuestion::create(['question_name' => 'Q', 'category_id' => $category->id]);
    }

    private function caQuestion(?CustomerAdminCategory $category = null): CustomerAdminQuestion
    {
        $category ??= CustomerAdminCategory::create(['category_name' => 'CA Cat']);
        return CustomerAdminQuestion::create([
            'question_name' => 'Q', 'category_id' => $category->id,
            'question_delivery_text' => 'D', 'question_return_text' => 'R',
        ]);
    }

    // ── 1/2. Store creates template + template-questions in order ──────────────

    public function test_rental_ready_template_store_creates_template_with_ordered_questions(): void
    {
        $equipmentCategory = $this->equipmentCategory();
        $q1 = $this->rrQuestion();
        $q2 = $this->rrQuestion();

        $response = $this->postAs('admin.checklist-management.rental-ready.templates.store', [
            'template_name'      => 'RR Template',
            'equipment_category' => $equipmentCategory->id,
            'is_active'          => 1,
            'questions'          => json_encode([['id' => $q1->id], ['id' => $q2->id]]),
        ]);

        $response->assertRedirect(route('admin.checklist-management.rental-ready.index'));
        $response->assertSessionHas('active_tab', 'templates');
        $response->assertSessionMissing('success');

        $template = RentalReadyChecklistTemplate::where('template_name', 'RR Template')->firstOrFail();
        $this->assertSame((string) $equipmentCategory->id, (string) $template->equipment_category_id);
        $this->assertSame(2, $template->templateQuestions()->count());
        $ordered = $template->templateQuestions()->pluck('question_id')->all();
        $this->assertSame([$q1->id, $q2->id], $ordered);

        $this->assertSame(0, CustomerAdminTemplate::count());
    }

    public function test_customer_admin_template_store_creates_template_with_ordered_questions(): void
    {
        $equipmentCategory = $this->equipmentCategory();
        $q1 = $this->caQuestion();
        $q2 = $this->caQuestion();

        $response = $this->postAs('admin.checklist-management.customer-admin.templates.store', [
            'template_name'      => 'CA Template',
            'equipment_category' => $equipmentCategory->id,
            'is_active'          => 1,
            'questions'          => json_encode([['id' => $q1->id], ['id' => $q2->id]]),
        ]);

        $response->assertRedirect(route('admin.checklist-management.customer-admin.index'));
        $response->assertSessionMissing('success');

        $template = CustomerAdminTemplate::where('template_name', 'CA Template')->firstOrFail();
        $this->assertSame(2, $template->templateQuestions()->count());
        $ordered = $template->templateQuestions()->pluck('question_id')->all();
        $this->assertSame([$q1->id, $q2->id], $ordered);

        $this->assertSame(0, RentalReadyChecklistTemplate::count());
    }

    // ── 3/4/5/6. Update wipes and recreates all template-questions, both trees ─

    public function test_rental_ready_template_update_wipes_and_recreates_questions_preserving_order(): void
    {
        $equipmentCategory = $this->equipmentCategory();
        $q1 = $this->rrQuestion();
        $q2 = $this->rrQuestion();
        $q3 = $this->rrQuestion();

        $template = RentalReadyChecklistTemplate::create([
            'template_name' => 'Original', 'equipment_category_id' => $equipmentCategory->id,
        ]);
        $originalLink = RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $q1->id, 'index_number' => 1]);

        $this->putAs('admin.checklist-management.rental-ready.templates.update', [$template->unique_id], [
            'template_name'      => 'Updated',
            'equipment_category' => $equipmentCategory->id,
            'is_active'          => 1,
            'questions'          => json_encode([['id' => $q3->id], ['id' => $q2->id]]),
        ]);

        // The original link row is wiped (via a plain query-builder ->delete(), which
        // still soft-deletes since RentalReadyChecklistTemplateQuestion uses
        // SoftDeletes) — no ID preservation, unlike Question's Rental Ready path,
        // which explicitly diffs and keeps matched IDs. The row still physically
        // exists with deleted_at set, so this asserts soft-deleted, not missing.
        $this->assertSoftDeleted('rental_ready_checklist_template_questions', ['id' => $originalLink->id]);
        $this->assertSame(2, $template->templateQuestions()->count());
        // Ordering follows the NEW submitted order (q3 first, q2 second), not the old one.
        $this->assertSame([$q3->id, $q2->id], $template->templateQuestions()->pluck('question_id')->all());
    }

    public function test_customer_admin_template_update_wipes_and_recreates_questions_preserving_order(): void
    {
        $equipmentCategory = $this->equipmentCategory();
        $q1 = $this->caQuestion();
        $q2 = $this->caQuestion();
        $q3 = $this->caQuestion();

        $template = CustomerAdminTemplate::create([
            'template_name' => 'Original', 'equipment_category_id' => $equipmentCategory->id,
        ]);
        $originalLink = CustomerAdminTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $q1->id, 'index_number' => 1]);

        $this->putAs('admin.checklist-management.customer-admin.templates.update', [$template->unique_id], [
            'template_name'      => 'Updated',
            'equipment_category' => $equipmentCategory->id,
            'is_active'          => 1,
            'questions'          => json_encode([['id' => $q3->id], ['id' => $q2->id]]),
        ]);

        $this->assertDatabaseMissing('customer_admin_template_questions', ['id' => $originalLink->id]);
        $this->assertSame(2, $template->templateQuestions()->count());
        $this->assertSame([$q3->id, $q2->id], $template->templateQuestions()->pluck('question_id')->all());
    }

    // ── 7/8. Delete: soft vs hard ────────────────────────────────────────────

    public function test_rental_ready_template_delete_soft_deletes_template_and_questions(): void
    {
        $equipmentCategory = $this->equipmentCategory();
        $q1 = $this->rrQuestion();
        $template = RentalReadyChecklistTemplate::create(['template_name' => 'To delete', 'equipment_category_id' => $equipmentCategory->id]);
        $link = RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $q1->id, 'index_number' => 1]);

        $response = $this->deleteAs('admin.checklist-management.rental-ready.templates.delete', [$template->unique_id]);

        $response->assertRedirect(route('admin.checklist-management.rental-ready.index'));
        $this->assertSoftDeleted('rental_ready_checklist_templates', ['id' => $template->id]);
        $this->assertSoftDeleted('rental_ready_checklist_template_questions', ['id' => $link->id]);
        // The upstream question itself is a different model — must be unaffected.
        $this->assertDatabaseHas('rental_ready_checklist_questions', ['id' => $q1->id]);
    }

    public function test_customer_admin_template_delete_hard_deletes_template_and_cascades_questions(): void
    {
        $equipmentCategory = $this->equipmentCategory();
        $q1 = $this->caQuestion();
        $template = CustomerAdminTemplate::create(['template_name' => 'To delete', 'equipment_category_id' => $equipmentCategory->id]);
        $link = CustomerAdminTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $q1->id, 'index_number' => 1]);

        $response = $this->deleteAs('admin.checklist-management.customer-admin.templates.delete', [$template->unique_id]);

        $response->assertRedirect(route('admin.checklist-management.customer-admin.index'));
        $this->assertDatabaseMissing('customer_admin_templates', ['id' => $template->id]);
        $this->assertDatabaseMissing('customer_admin_template_questions', ['id' => $link->id]);
        $this->assertDatabaseHas('customer_admin_questions', ['id' => $q1->id]);
    }

    // ── 9/10. Copy duplicates template + ordered question links ────────────────

    public function test_rental_ready_template_copy_duplicates_template_and_ordered_questions(): void
    {
        $equipmentCategory = $this->equipmentCategory();
        $q1 = $this->rrQuestion();
        $q2 = $this->rrQuestion();
        $template = RentalReadyChecklistTemplate::create(['template_name' => 'Original', 'equipment_category_id' => $equipmentCategory->id]);
        RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $q1->id, 'index_number' => 1]);
        RentalReadyChecklistTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $q2->id, 'index_number' => 2]);

        $response = $this->postAs('admin.checklist-management.rental-ready.templates.copy', [], [$template->unique_id]);

        $response->assertRedirect(route('admin.checklist-management.rental-ready.index'));

        $copy = RentalReadyChecklistTemplate::where('template_name', 'Original (Copy)')->firstOrFail();
        $this->assertNotSame($template->unique_id, $copy->unique_id);
        $this->assertSame([$q1->id, $q2->id], $copy->templateQuestions()->pluck('question_id')->all());
        // Original untouched.
        $this->assertSame(2, $template->fresh()->templateQuestions()->count());
    }

    public function test_customer_admin_template_copy_duplicates_template_and_ordered_questions(): void
    {
        $equipmentCategory = $this->equipmentCategory();
        $q1 = $this->caQuestion();
        $q2 = $this->caQuestion();
        $template = CustomerAdminTemplate::create(['template_name' => 'Original', 'equipment_category_id' => $equipmentCategory->id]);
        CustomerAdminTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $q1->id, 'index_number' => 1]);
        CustomerAdminTemplateQuestion::create(['template_id' => $template->id, 'question_id' => $q2->id, 'index_number' => 2]);

        $response = $this->postAs('admin.checklist-management.customer-admin.templates.copy', [], [$template->unique_id]);

        $response->assertRedirect(route('admin.checklist-management.customer-admin.index'));

        $copy = CustomerAdminTemplate::where('template_name', 'Original (Copy)')->firstOrFail();
        $this->assertNotSame($template->unique_id, $copy->unique_id);
        $this->assertSame([$q1->id, $q2->id], $copy->templateQuestions()->pluck('question_id')->all());
        $this->assertSame(2, $template->fresh()->templateQuestions()->count());
    }

    // ── 11/12. Validation ─────────────────────────────────────────────────────

    public function test_rental_ready_template_store_requires_template_name_and_equipment_category(): void
    {
        $response = $this->postAs('admin.checklist-management.rental-ready.templates.store', [
            'questions' => json_encode([]),
        ]);

        $response->assertSessionHasErrors(['template_name', 'equipment_category']);
        $this->assertSame(0, RentalReadyChecklistTemplate::count());
    }

    public function test_customer_admin_template_store_requires_template_name_and_equipment_category(): void
    {
        $response = $this->postAs('admin.checklist-management.customer-admin.templates.store', [
            'questions' => json_encode([]),
        ]);

        $response->assertSessionHasErrors(['template_name', 'equipment_category']);
        $this->assertSame(0, CustomerAdminTemplate::count());
    }

    // ── 13. Equipment-category referential integrity DIVERGES between the trees ─

    public function test_rental_ready_template_store_rejects_a_nonexistent_equipment_category_id(): void
    {
        // Rental Ready's equipment_category_id is a REAL foreignId (added by a later
        // migration, 2025_08_21_162850_update_equipment_category_in_rental_ready_checklist_templates.php)
        // constrained to product_categories — a nonexistent id violates that FK,
        // throws a QueryException, and is caught by the controller's normal
        // catch(\Throwable) block like any other failure.
        $response = $this->postAs('admin.checklist-management.rental-ready.templates.store', [
            'template_name'      => 'No real category',
            'equipment_category' => 999999, // no ProductCategory with this id exists
            'questions'          => json_encode([]),
        ]);

        $response->assertSessionHasErrors('error');
        $this->assertSame(0, RentalReadyChecklistTemplate::count());
    }

    public function test_customer_admin_template_store_accepts_a_nonexistent_equipment_category_id(): void
    {
        // Customer Admin's equipment_category_id is still the original plain STRING
        // column — no later migration ever added a foreign key for this tree (unlike
        // Rental Ready). Nothing rejects a nonexistent value; it's stored as-is.
        $response = $this->postAs('admin.checklist-management.customer-admin.templates.store', [
            'template_name'      => 'No real category',
            'equipment_category' => 999999,
            'questions'          => json_encode([]),
        ]);

        $response->assertRedirect(route('admin.checklist-management.customer-admin.index'));
        $this->assertDatabaseHas('customer_admin_templates', [
            'template_name' => 'No real category', 'equipment_category_id' => 999999,
        ]);
    }

    // ── 14. Invalid JSON questions payload rolls back cleanly ───────────────────

    public function test_rental_ready_template_update_with_invalid_json_questions_rolls_back_and_flashes_error(): void
    {
        $equipmentCategory = $this->equipmentCategory();
        $template = RentalReadyChecklistTemplate::create(['template_name' => 'Original', 'equipment_category_id' => $equipmentCategory->id]);

        $response = $this->putAs('admin.checklist-management.rental-ready.templates.update', [$template->unique_id], [
            'template_name'      => 'Updated',
            'equipment_category' => $equipmentCategory->id,
            'questions'          => 'not valid json',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
        // Template fields were not persisted either — the whole operation rolled back.
        $this->assertSame('Original', $template->fresh()->template_name);
    }

    // ── 15/16. No mirror behavior ────────────────────────────────────────────

    public function test_rental_ready_template_store_creates_no_customer_admin_mirror(): void
    {
        $equipmentCategory = $this->equipmentCategory();

        $this->postAs('admin.checklist-management.rental-ready.templates.store', [
            'template_name'      => 'No mirror check',
            'equipment_category' => $equipmentCategory->id,
            'questions'          => json_encode([]),
        ]);

        $this->assertSame(0, CustomerAdminTemplate::count());
    }

    public function test_customer_admin_template_store_creates_no_rental_ready_mirror(): void
    {
        $equipmentCategory = $this->equipmentCategory();

        $this->postAs('admin.checklist-management.customer-admin.templates.store', [
            'template_name'      => 'No mirror check',
            'equipment_category' => $equipmentCategory->id,
            'questions'          => json_encode([]),
        ]);

        $this->assertSame(0, RentalReadyChecklistTemplate::count());
    }

    // ── 17/18. Store atomicity — template rolls back if a question link fails ──

    public function test_rental_ready_template_store_rolls_back_template_when_a_question_link_fails(): void
    {
        $equipmentCategory = $this->equipmentCategory();
        $q1 = $this->rrQuestion();
        $q2 = $this->rrQuestion();

        $failOnSecond = 0;
        RentalReadyChecklistTemplateQuestion::creating(function () use (&$failOnSecond) {
            $failOnSecond++;
            if ($failOnSecond === 2) {
                throw new \RuntimeException('Forced failure for PR-B4.3 atomicity test');
            }
        });

        $response = $this->postAs('admin.checklist-management.rental-ready.templates.store', [
            'template_name'      => 'Should Not Persist',
            'equipment_category' => $equipmentCategory->id,
            'questions'          => json_encode([['id' => $q1->id], ['id' => $q2->id]]),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
        $this->assertSame(0, RentalReadyChecklistTemplate::count());
        $this->assertSame(0, RentalReadyChecklistTemplateQuestion::count());
    }

    public function test_customer_admin_template_store_rolls_back_template_when_a_question_link_fails(): void
    {
        $equipmentCategory = $this->equipmentCategory();
        $q1 = $this->caQuestion();
        $q2 = $this->caQuestion();

        $failOnSecond = 0;
        CustomerAdminTemplateQuestion::creating(function () use (&$failOnSecond) {
            $failOnSecond++;
            if ($failOnSecond === 2) {
                throw new \RuntimeException('Forced failure for PR-B4.3 atomicity test');
            }
        });

        $response = $this->postAs('admin.checklist-management.customer-admin.templates.store', [
            'template_name'      => 'Should Not Persist',
            'equipment_category' => $equipmentCategory->id,
            'questions'          => json_encode([['id' => $q1->id], ['id' => $q2->id]]),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
        $this->assertSame(0, CustomerAdminTemplate::count());
        $this->assertSame(0, CustomerAdminTemplateQuestion::count());
    }
}
