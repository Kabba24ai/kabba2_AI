<?php

namespace Tests\Feature\ChecklistManagement;

use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplate;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Tests\TestCase;

/**
 * PR-B1 (Phase 2): proves the 3 previously-independent equipment <-> ChecklistMaster
 * write paths (AssignChecklistMasterController, ChecklistMaster\StoreController,
 * ChecklistMaster\UpdateController) now all go through ChecklistAssignmentService,
 * that the delete path no longer leaves a dangling checklist_master_id, and that the
 * bulk-reassignment side effect is logged per Phase 2 decision D1 — with no behavior
 * change to the happy path and no mobile impact (100% admin-web surface).
 */
class ChecklistAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ProductCategory $category;
    private RentalReadyChecklistTemplate $rentalReadyTemplate;
    private CustomerAdminTemplate $customerAdminTemplate;
    private TestHandler $apiErrorsHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Assignment',
            'last_name'  => 'Admin',
            'email'      => 'assignment-admin@example.com',
            'password'   => bcrypt('password'),
        ]);

        $this->category = ProductCategory::create([
            'title' => 'Test Category',
            'slug'  => 'test-category-' . uniqid(),
        ]);

        $this->rentalReadyTemplate = RentalReadyChecklistTemplate::create([
            'template_name'   => 'Test Rental Ready Template',
            'active_template' => true,
        ]);

        $this->customerAdminTemplate = CustomerAdminTemplate::create([
            'template_name'   => 'Test Customer Admin Template',
            'active_template' => true,
        ]);

        $this->apiErrorsHandler = new TestHandler();
        Log::channel('api_errors')->getLogger()->pushHandler($this->apiErrorsHandler);
    }

    private function makeEquipment(?int $checklistMasterId = null): Equipment
    {
        return Equipment::create([
            'equipment_name'      => 'Test Excavator',
            'equipment_id'        => 'EQP-TEST-' . uniqid(),
            'brand'               => 'TestBrand',
            'current_status'      => 'available',
            'checklist_master_id' => $checklistMasterId,
        ]);
    }

    private function makeChecklistMaster(): ChecklistMaster
    {
        return ChecklistMaster::create([
            'checklist_system_name'      => 'Test Checklist Master',
            'equipment_category_id'      => $this->category->id,
            'rental_ready_template_id'   => $this->rentalReadyTemplate->id,
            'customer_admin_template_id' => $this->customerAdminTemplate->id,
        ]);
    }

    private function callAs(string $method, string $url, array $payload = []): \Illuminate\Testing\TestResponse
    {
        return $this->withoutMiddleware()
            ->actingAs($this->admin)
            ->call($method, $url, $payload);
    }

    // ── 1. Single assign — AssignChecklistMasterController ──────────────────

    public function test_single_assign_via_controller_assigns_equipment(): void
    {
        $master = $this->makeChecklistMaster();
        $equipment = $this->makeEquipment();

        $response = $this->callAs('POST', route('admin.maintenance-management.equipment.checklist-master-assign'), [
            'checklist_master_unique_id' => $master->unique_id,
            'equipment_unique_id'        => $equipment->unique_id,
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertEquals($master->id, $equipment->fresh()->checklist_master_id);
    }

    public function test_single_assign_via_controller_rejects_duplicate_assignment(): void
    {
        $master = $this->makeChecklistMaster();
        $equipment = $this->makeEquipment($master->id);

        $response = $this->callAs('POST', route('admin.maintenance-management.equipment.checklist-master-assign'), [
            'checklist_master_unique_id' => $master->unique_id,
            'equipment_unique_id'        => $equipment->unique_id,
        ]);

        // Behavior unchanged — still the original 400 "already assigned" response.
        $response->assertStatus(400)->assertJson(['success' => false]);
        $this->assertEquals($master->id, $equipment->fresh()->checklist_master_id);
    }

    // ── 2. Bulk create assign — ChecklistMaster\StoreController ─────────────

    public function test_store_controller_bulk_assigns_equipment_on_create(): void
    {
        $equipmentA = $this->makeEquipment();
        $equipmentB = $this->makeEquipment();

        $response = $this->callAs('POST', route('admin.checklist-management.checklist-master.store'), [
            'checklist_system_name'      => 'New Master',
            'equipment_category_id'      => $this->category->id,
            'rental_ready_template_id'   => $this->rentalReadyTemplate->id,
            'customer_admin_template_id' => $this->customerAdminTemplate->id,
            'equipment_ids'              => "{$equipmentA->id},{$equipmentB->id}",
        ]);

        $response->assertRedirect();

        $master = ChecklistMaster::where('checklist_system_name', 'New Master')->firstOrFail();
        $this->assertEquals($master->id, $equipmentA->fresh()->checklist_master_id);
        $this->assertEquals($master->id, $equipmentB->fresh()->checklist_master_id);
    }

    // ── 3. Bulk update reassignment — ChecklistMaster\UpdateController ──────

    public function test_update_controller_reassigns_and_unassigns_excluded_equipment(): void
    {
        $master = $this->makeChecklistMaster();

        // equipmentA assigned via a DIFFERENT path (single-assign) before the bulk update.
        $equipmentA = $this->makeEquipment($master->id);
        // equipmentB will be the only equipment kept in the new list.
        $equipmentB = $this->makeEquipment($master->id);
        $equipmentC = $this->makeEquipment(); // not yet assigned, will be newly added

        $response = $this->callAs('PUT', route('admin.checklist-management.checklist-master.update', $master->unique_id), [
            'checklist_system_name'      => $master->checklist_system_name,
            'equipment_category_id'      => $this->category->id,
            'rental_ready_template_id'   => $this->rentalReadyTemplate->id,
            'customer_admin_template_id' => $this->customerAdminTemplate->id,
            'assign_equipment'           => true,
            'equipment_ids'              => "{$equipmentB->id},{$equipmentC->id}",
        ]);

        $response->assertRedirect();

        // equipmentA was excluded from the new list — unassigned as a side effect.
        $this->assertNull($equipmentA->fresh()->checklist_master_id);
        // equipmentB stays assigned (present in the new list).
        $this->assertEquals($master->id, $equipmentB->fresh()->checklist_master_id);
        // equipmentC newly assigned.
        $this->assertEquals($master->id, $equipmentC->fresh()->checklist_master_id);

        // Phase 2 decision D1: the unassignment side effect must be logged.
        $this->assertTrue($this->apiErrorsHandler->hasWarningThatContains(
            'Checklist Master bulk update unassigned equipment that may have been assigned via a different path'
        ));
        $record = $this->apiErrorsHandler->getRecords()[0];
        $this->assertEquals([$equipmentA->id], $record->context['unassigned_equipment_ids']);
    }

    public function test_bulk_assign_does_not_null_equipment_already_reassigned_to_a_different_master(): void
    {
        // PR-B1 review fix: simulates the TOCTOU race — equipmentA is excluded from
        // master A's new equipment list (so it would normally be unassigned), but by
        // the time the unassign step runs it has already been reassigned to masterB
        // via a different path. The unassign step must not clobber that reassignment.
        $masterA = $this->makeChecklistMaster();
        $masterB = $this->makeChecklistMaster();

        $equipmentA = $this->makeEquipment($masterA->id);
        $equipmentKept = $this->makeEquipment($masterA->id);

        // Simulate the concurrent reassignment that happens between the SELECT and
        // the UPDATE inside bulkAssign().
        $equipmentA->update(['checklist_master_id' => $masterB->id]);

        app(\App\Services\ChecklistManagement\ChecklistAssignmentService::class)
            ->bulkAssign($masterA, [$equipmentKept->id], unassignExisting: true);

        // equipmentA must still point at masterB — not nulled by the stale unassign list.
        $this->assertEquals($masterB->id, $equipmentA->fresh()->checklist_master_id);
        $this->assertEquals($masterA->id, $equipmentKept->fresh()->checklist_master_id);
    }

    public function test_update_controller_does_not_log_when_nothing_is_unassigned(): void
    {
        $master = $this->makeChecklistMaster();
        $equipment = $this->makeEquipment($master->id);

        $response = $this->callAs('PUT', route('admin.checklist-management.checklist-master.update', $master->unique_id), [
            'checklist_system_name'      => $master->checklist_system_name,
            'equipment_category_id'      => $this->category->id,
            'rental_ready_template_id'   => $this->rentalReadyTemplate->id,
            'customer_admin_template_id' => $this->customerAdminTemplate->id,
            'assign_equipment'           => true,
            // Same equipment kept in the new list — nothing to unassign.
            'equipment_ids'              => (string) $equipment->id,
        ]);

        $response->assertRedirect();
        $this->assertEquals($master->id, $equipment->fresh()->checklist_master_id);
        $this->assertFalse($this->apiErrorsHandler->hasWarningRecords());
    }

    // ── 4 & 5. Delete cleanup — no dangling checklist_master_id references ──

    public function test_delete_controller_nulls_out_checklist_master_id_on_assigned_equipment(): void
    {
        $master = $this->makeChecklistMaster();
        $equipmentA = $this->makeEquipment($master->id);
        $equipmentB = $this->makeEquipment($master->id);

        $response = $this->callAs('DELETE', route('admin.checklist-management.checklist-master.delete', $master->unique_id));

        $response->assertRedirect();

        // The master is soft-deleted...
        $this->assertSoftDeleted('checklist_masters', ['id' => $master->id]);

        // ...and no equipment is left dangling, pointing at the now-trashed master.
        $this->assertNull($equipmentA->fresh()->checklist_master_id);
        $this->assertNull($equipmentB->fresh()->checklist_master_id);
    }

    public function test_no_equipment_points_at_a_soft_deleted_checklist_master_after_delete(): void
    {
        $master = $this->makeChecklistMaster();
        $this->makeEquipment($master->id);
        $this->makeEquipment($master->id);
        $this->makeEquipment(); // unrelated, unassigned equipment — must be unaffected

        $this->callAs('DELETE', route('admin.checklist-management.checklist-master.delete', $master->unique_id));

        // Data-integrity assertion: zero equipment rows reference a trashed master.
        $danglingCount = Equipment::whereIn('checklist_master_id', ChecklistMaster::onlyTrashed()->pluck('id'))->count();
        $this->assertEquals(0, $danglingCount);
    }
}
