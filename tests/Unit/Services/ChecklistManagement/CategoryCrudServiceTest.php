<?php

namespace Tests\Unit\Services\ChecklistManagement;

use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Services\ChecklistManagement\CategoryCrudService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PR-B4.1 — focused tests for CategoryCrudService in isolation from any
 * controller. Uses RefreshDatabase (not a pure PHPUnit\Framework\TestCase, unlike
 * RentalReadyCompletionCalculatorTest) because this service's whole job is
 * Eloquent persistence + transaction behavior — there is nothing meaningful to
 * test about it without a real database.
 */
class CategoryCrudServiceTest extends TestCase
{
    use RefreshDatabase;

    private CategoryCrudService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CategoryCrudService();
    }

    public function test_store_creates_only_the_primary_model_when_shouldMirror_is_false(): void
    {
        $category = $this->service->store(
            RentalReadyChecklistCategory::class,
            ['category_name' => 'Solo Category', 'description' => 'desc'],
            CustomerAdminCategory::class,
            false
        );

        $this->assertInstanceOf(RentalReadyChecklistCategory::class, $category);
        $this->assertDatabaseHas('rental_ready_checklist_categories', ['category_name' => 'Solo Category']);
        $this->assertSame(0, CustomerAdminCategory::count());
    }

    public function test_store_creates_both_models_when_shouldMirror_is_true(): void
    {
        $this->service->store(
            CustomerAdminCategory::class,
            ['category_name' => 'Mirrored Category', 'description' => 'desc'],
            RentalReadyChecklistCategory::class,
            true
        );

        $this->assertDatabaseHas('customer_admin_categories', ['category_name' => 'Mirrored Category']);
        $this->assertDatabaseHas('rental_ready_checklist_categories', ['category_name' => 'Mirrored Category']);
    }

    public function test_store_rolls_back_the_primary_model_when_the_mirror_write_throws(): void
    {
        CustomerAdminCategory::creating(function () {
            throw new \RuntimeException('forced failure');
        });

        try {
            $this->service->store(
                RentalReadyChecklistCategory::class,
                ['category_name' => 'Should Not Persist'],
                CustomerAdminCategory::class,
                true
            );
            $this->fail('Expected exception was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('forced failure', $e->getMessage());
        }

        $this->assertSame(0, RentalReadyChecklistCategory::count());
        $this->assertSame(0, CustomerAdminCategory::count());
    }

    public function test_update_updates_the_matched_row(): void
    {
        $category = RentalReadyChecklistCategory::create(['category_name' => 'Old']);

        $updated = $this->service->update(RentalReadyChecklistCategory::class, $category->unique_id, [
            'category_name' => 'New',
        ]);

        $this->assertSame($category->id, $updated->id);
        $this->assertSame('New', $category->fresh()->category_name);
    }

    public function test_update_throws_when_unique_id_does_not_match_any_row(): void
    {
        // Mirrors the controllers' existing ->first() (not ->firstOrFail()) lookup —
        // no row found means $category is null, and ->update() on null throws.
        // Deliberately not "fixed" to firstOrFail() in this PR — see the class
        // docblock and PR-B4_1_CATEGORY_REFACTOR.md.
        $this->expectException(\Throwable::class);

        $this->service->update(RentalReadyChecklistCategory::class, 'NONEXISTENT-ID', [
            'category_name' => 'New',
        ]);
    }

    public function test_delete_soft_deletes_when_the_model_uses_soft_deletes(): void
    {
        $category = RentalReadyChecklistCategory::create(['category_name' => 'Soft']);

        $this->service->delete(RentalReadyChecklistCategory::class, $category->unique_id);

        $this->assertSoftDeleted('rental_ready_checklist_categories', ['id' => $category->id]);
    }

    public function test_delete_hard_deletes_when_the_model_does_not_use_soft_deletes(): void
    {
        $category = CustomerAdminCategory::create(['category_name' => 'Hard']);

        $this->service->delete(CustomerAdminCategory::class, $category->unique_id);

        $this->assertDatabaseMissing('customer_admin_categories', ['id' => $category->id]);
    }

    public function test_delete_throws_model_not_found_when_unique_id_does_not_match_any_row(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->delete(RentalReadyChecklistCategory::class, 'NONEXISTENT-ID');
    }
}
