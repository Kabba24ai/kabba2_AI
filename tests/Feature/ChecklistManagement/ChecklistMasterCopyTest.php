<?php

namespace Tests\Feature\ChecklistManagement;

use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplate;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;
use App\Models\Iam\Personnel\User;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P3-4 / CLEAN-4 (Phase 3 housekeeping): the Copy button/form in
 * checklist_master/partials/_table.blade.php was left HTML-commented-out even
 * though its route and CopyController both work fully server-side. Uncommented
 * the button; this is the smoke test confirming the route+controller pairing
 * actually works before re-exposing it in the UI.
 * See docs/checklist-system-audit/P3_4_HOUSEKEEPING.md.
 */
class ChecklistMasterCopyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Copy',
            'last_name'  => 'Admin',
            'email'      => 'copy-admin@example.com',
            'password'   => bcrypt('password'),
        ]);
    }

    private function callAs(string $method, string $url, array $payload = []): \Illuminate\Testing\TestResponse
    {
        return $this->withoutMiddleware()
            ->actingAs($this->admin)
            ->call($method, $url, $payload);
    }

    public function test_copy_duplicates_checklist_master_with_new_unique_id(): void
    {
        $category = ProductCategory::create(['title' => 'Test Category', 'slug' => 'test-category-' . uniqid()]);
        $rentalReadyTemplate = RentalReadyChecklistTemplate::create(['template_name' => 'Test RR Template', 'active_template' => true]);
        $customerAdminTemplate = CustomerAdminTemplate::create(['template_name' => 'Test CA Template', 'active_template' => true]);

        $master = ChecklistMaster::create([
            'checklist_system_name'      => 'Original Master',
            'equipment_category_id'      => $category->id,
            'rental_ready_template_id'   => $rentalReadyTemplate->id,
            'customer_admin_template_id' => $customerAdminTemplate->id,
        ]);

        $response = $this->callAs('POST', route('admin.checklist-management.checklist-master.copy', $master->unique_id));

        $response->assertRedirect(route('admin.checklist-management.checklist-master.index'));

        $this->assertSame(2, ChecklistMaster::count());

        $copy = ChecklistMaster::where('id', '!=', $master->id)->firstOrFail();
        $this->assertSame('Original Master (Copy)', $copy->checklist_system_name);
        $this->assertNotSame($master->unique_id, $copy->unique_id);
        $this->assertSame($category->id, $copy->equipment_category_id);
        $this->assertSame($rentalReadyTemplate->id, $copy->rental_ready_template_id);
        $this->assertSame($customerAdminTemplate->id, $copy->customer_admin_template_id);
    }

    public function test_copy_of_a_nonexistent_master_flashes_error_without_creating_anything(): void
    {
        $response = $this->callAs('POST', route('admin.checklist-management.checklist-master.copy', 'CLM-DOES-NOT-EXIST'));

        $response->assertRedirect(route('admin.checklist-management.checklist-master.index'));
        $this->assertSame(0, ChecklistMaster::count());
    }
}
