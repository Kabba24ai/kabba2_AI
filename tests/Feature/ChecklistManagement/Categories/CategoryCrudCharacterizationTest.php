<?php

namespace Tests\Feature\ChecklistManagement\Categories;

use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;
use App\Models\Iam\Personnel\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PR-B4.1 — characterization baseline for the Rental Ready and Customer Admin
 * Category CRUD controllers, before any shared-service refactor. See
 * docs/checklist-system-audit/PR-B4_1_CATEGORY_READINESS.md for the full
 * duplication/divergence analysis this suite is locking in place.
 *
 * These tests assert on TODAY'S unmodified controllers/models. In particular,
 * tests 9/10 concretely prove the single most important finding of the
 * readiness review: RentalReadyChecklistCategory soft-deletes (its cascade FK
 * never fires), while CustomerAdminCategory hard-deletes (its cascade FK DOES
 * fire, permanently destroying child questions). Any future refactor must keep
 * these two tests passing unchanged, or it has silently unified a real
 * behavioral difference that requires its own separate decision.
 */
class CategoryCrudCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Category', 'last_name' => 'Admin',
            'email' => 'category-crud-admin@example.com', 'password' => bcrypt('password'),
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

    // ── 1/2. Rental Ready store ──────────────────────────────────────────────

    public function test_rental_ready_category_store_creates_category_without_mirror(): void
    {
        $response = $this->postAs('admin.checklist-management.rental-ready.categories.store', [
            'category_name' => 'RR Category A',
            'description'   => 'A description',
        ]);

        $response->assertRedirect(route('admin.checklist-management.rental-ready.index'));
        $response->assertSessionHas('active_tab', 'questions');
        $response->assertSessionHas('active_subtab', 'categories');
        // Rental Ready's redirect does NOT chain ->with('success', ...) — unlike
        // Customer Admin's (see test 3) — a confirmed behavioral difference.
        $response->assertSessionMissing('success');

        $this->assertDatabaseHas('rental_ready_checklist_categories', ['category_name' => 'RR Category A']);
        $this->assertSame(0, CustomerAdminCategory::count());
    }

    public function test_rental_ready_category_store_with_mirror_checkbox_creates_customer_admin_category_too(): void
    {
        $this->postAs('admin.checklist-management.rental-ready.categories.store', [
            'category_name'           => 'RR Mirrored Category',
            'description'             => 'Mirrored description',
            'create_customer_folder'  => 1,
        ]);

        $this->assertDatabaseHas('rental_ready_checklist_categories', ['category_name' => 'RR Mirrored Category']);
        $this->assertDatabaseHas('customer_admin_categories', [
            'category_name' => 'RR Mirrored Category',
            'description'   => 'Mirrored description',
        ]);
    }

    // ── 3/4. Customer Admin store ────────────────────────────────────────────

    public function test_customer_admin_category_store_creates_category_without_mirror(): void
    {
        $response = $this->postAs('admin.checklist-management.customer-admin.categories.store', [
            'category_name' => 'CA Category A',
            'description'   => 'A description',
        ]);

        $response->assertRedirect(route('admin.checklist-management.customer-admin.index'));
        $response->assertSessionHas('active_tab', 'questions');
        $response->assertSessionHas('active_subtab', 'categories');
        // Customer Admin's redirect DOES chain ->with('success', ...) — unlike
        // Rental Ready's (see test 1) — the other half of the confirmed difference.
        $response->assertSessionHas('success', 'Category created successfully.');

        $this->assertDatabaseHas('customer_admin_categories', ['category_name' => 'CA Category A']);
        $this->assertSame(0, RentalReadyChecklistCategory::count());
    }

    public function test_customer_admin_category_store_with_mirror_checkbox_creates_rental_ready_category_too(): void
    {
        $this->postAs('admin.checklist-management.customer-admin.categories.store', [
            'category_name'         => 'CA Mirrored Category',
            'description'           => 'Mirrored description',
            'create_rental_folder'  => 1,
        ]);

        $this->assertDatabaseHas('customer_admin_categories', ['category_name' => 'CA Mirrored Category']);
        $this->assertDatabaseHas('rental_ready_checklist_categories', [
            'category_name' => 'CA Mirrored Category',
            'description'   => 'Mirrored description',
        ]);
    }

    // ── 5/6. Update happy path ───────────────────────────────────────────────

    public function test_rental_ready_category_update_updates_fields(): void
    {
        $category = RentalReadyChecklistCategory::create(['category_name' => 'Old Name', 'description' => 'Old']);

        $response = $this->putAs('admin.checklist-management.rental-ready.categories.update', [$category->unique_id], [
            'category_name' => 'New Name',
            'description'   => 'New',
        ]);

        $response->assertRedirect(route('admin.checklist-management.rental-ready.index'));
        $this->assertSame('New Name', $category->fresh()->category_name);
        $this->assertSame('New', $category->fresh()->description);
    }

    public function test_customer_admin_category_update_updates_fields(): void
    {
        $category = CustomerAdminCategory::create(['category_name' => 'Old Name', 'description' => 'Old']);

        $response = $this->putAs('admin.checklist-management.customer-admin.categories.update', [$category->unique_id], [
            'category_name' => 'New Name',
            'description'   => 'New',
        ]);

        $response->assertRedirect(route('admin.checklist-management.customer-admin.index'));
        $response->assertSessionHas('success', 'Category updated successfully.');
        $this->assertSame('New Name', $category->fresh()->category_name);
    }

    // ── 7/8. Update with a nonexistent unique_id — today's incidental rollback ─

    public function test_rental_ready_category_update_with_nonexistent_id_rolls_back_and_flashes_error(): void
    {
        // No such category exists — UpdateController's ->first() (not firstOrFail())
        // returns null, then ->update() on null throws, caught by the broad
        // catch (\Throwable) block. Locks in today's behavior before any refactor
        // reconciles ->first() to ->firstOrFail().
        $response = $this->putAs('admin.checklist-management.rental-ready.categories.update', ['NONEXISTENT-ID'], [
            'category_name' => 'Whatever',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
        $this->assertSame(0, RentalReadyChecklistCategory::count());
    }

    public function test_customer_admin_category_update_with_nonexistent_id_rolls_back_and_flashes_error(): void
    {
        $response = $this->putAs('admin.checklist-management.customer-admin.categories.update', ['NONEXISTENT-ID'], [
            'category_name' => 'Whatever',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
        $this->assertSame(0, CustomerAdminCategory::count());
    }

    // ── 9/10. Delete: soft vs hard, and cascade behavior ────────────────────────

    public function test_rental_ready_category_delete_soft_deletes_and_does_not_cascade_delete_questions(): void
    {
        $category = RentalReadyChecklistCategory::create(['category_name' => 'To Soft Delete']);
        $question = RentalReadyChecklistQuestion::create([
            'question_name' => 'Child question', 'category_id' => $category->id, 'required_question' => true,
        ]);

        $response = $this->deleteAs('admin.checklist-management.rental-ready.categories.delete', [$category->unique_id]);

        $response->assertRedirect(route('admin.checklist-management.rental-ready.index'));

        // Soft delete: the row still exists with deleted_at set.
        $this->assertSoftDeleted('rental_ready_checklist_categories', ['id' => $category->id]);

        // The cascade FK never fires for a soft delete — the child question survives.
        $this->assertDatabaseHas('rental_ready_checklist_questions', ['id' => $question->id]);
    }

    public function test_customer_admin_category_delete_hard_deletes_and_cascades_child_questions(): void
    {
        $category = CustomerAdminCategory::create(['category_name' => 'To Hard Delete']);
        $question = CustomerAdminQuestion::create([
            'question_name' => 'Child question',
            'category_id' => $category->id,
            'question_delivery_text' => 'Delivery text',
            'question_return_text' => 'Return text',
            'required_question' => true,
        ]);

        $response = $this->deleteAs('admin.checklist-management.customer-admin.categories.delete', [$category->unique_id]);

        $response->assertRedirect(route('admin.checklist-management.customer-admin.index'));
        $response->assertSessionHas('success', 'Category deleted successfully.');

        // Hard delete: the category row is completely gone (no deleted_at column exists).
        $this->assertDatabaseMissing('customer_admin_categories', ['id' => $category->id]);

        // The onDelete('cascade') FK DOES fire for a real delete — the child question
        // is permanently destroyed along with it. This is the concrete proof of the
        // readiness review's highest-severity finding (§2.1) — not a hypothetical.
        $this->assertDatabaseMissing('customer_admin_questions', ['id' => $question->id]);
    }

    // ── 11. Validation ───────────────────────────────────────────────────────

    public function test_rental_ready_category_store_requires_category_name(): void
    {
        $response = $this->postAs('admin.checklist-management.rental-ready.categories.store', [
            'description' => 'No name given',
        ]);

        $response->assertSessionHasErrors('category_name');
        $this->assertSame(0, RentalReadyChecklistCategory::count());
    }

    public function test_customer_admin_category_store_requires_category_name(): void
    {
        $response = $this->postAs('admin.checklist-management.customer-admin.categories.store', [
            'description' => 'No name given',
        ]);

        $response->assertSessionHasErrors('category_name');
        $this->assertSame(0, CustomerAdminCategory::count());
    }

    // ── 12. Cross-tree transactional atomicity ──────────────────────────────

    public function test_mirror_creation_failure_rolls_back_the_primary_category_too(): void
    {
        // Force the mirror model's own Eloquent 'creating' event to throw — a plain
        // in-memory listener, not a DDL statement, so it doesn't desync
        // RefreshDatabase's transaction-nesting counter (the same technique used in
        // tests/Feature/CustomerChecklists/ChecklistTransactionTest.php for PR-A2).
        CustomerAdminCategory::creating(function () {
            throw new \RuntimeException('Forced failure for PR-B4.1 cross-tree rollback test');
        });

        $response = $this->postAs('admin.checklist-management.rental-ready.categories.store', [
            'category_name'          => 'Should Not Persist',
            'create_customer_folder' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');

        // The single DB::beginTransaction() in StoreController genuinely covers both
        // writes — the primary RentalReadyChecklistCategory row must NOT have been
        // left behind just because it was created before the mirror write failed.
        $this->assertSame(0, RentalReadyChecklistCategory::count());
        $this->assertSame(0, CustomerAdminCategory::count());
    }
}
