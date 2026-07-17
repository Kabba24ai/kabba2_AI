<?php

namespace Tests\Unit\Models;

use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplate;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P3-5 / TD-3: Equipment::customerAdminTemplates() was rewritten from a raw
 * hasOneThrough() (hard-wired to column-name strings, confirmed unused
 * anywhere in the app) to compose from the existing checklistMaster() and
 * ChecklistMaster::customerAdminTemplate() relations. This proves the new
 * composition resolves the same value the old query would have.
 * See docs/checklist-system-audit/P3_5_TECHNICAL_DEBT.md.
 */
class EquipmentCustomerAdminTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_customer_admin_template_through_checklist_master(): void
    {
        $category = ProductCategory::create(['title' => 'Test Category', 'slug' => 'test-category-' . uniqid()]);
        $customerAdminTemplate = CustomerAdminTemplate::create(['template_name' => 'Test CA Template', 'active_template' => true]);
        $checklistMaster = ChecklistMaster::create([
            'checklist_system_name'      => 'Test Master',
            'equipment_category_id'      => $category->id,
            'customer_admin_template_id' => $customerAdminTemplate->id,
        ]);

        $equipment = Equipment::create([
            'equipment_name'      => 'Test Excavator',
            'equipment_id'        => 'EQP-TD3-' . uniqid(),
            'brand'               => 'TestBrand',
            'current_status'      => 'available',
            'checklist_master_id' => $checklistMaster->id,
        ]);

        $resolved = $equipment->customerAdminTemplates();

        $this->assertInstanceOf(CustomerAdminTemplate::class, $resolved);
        $this->assertSame($customerAdminTemplate->id, $resolved->id);
    }

    public function test_returns_null_when_equipment_has_no_checklist_master(): void
    {
        $equipment = Equipment::create([
            'equipment_name' => 'Test Excavator',
            'equipment_id'   => 'EQP-TD3-' . uniqid(),
            'brand'          => 'TestBrand',
            'current_status' => 'available',
        ]);

        $this->assertNull($equipment->customerAdminTemplates());
    }

    public function test_returns_null_when_checklist_master_has_no_customer_admin_template(): void
    {
        $category = ProductCategory::create(['title' => 'Test Category', 'slug' => 'test-category-' . uniqid()]);
        $checklistMaster = ChecklistMaster::create([
            'checklist_system_name' => 'Test Master',
            'equipment_category_id' => $category->id,
        ]);

        $equipment = Equipment::create([
            'equipment_name'      => 'Test Excavator',
            'equipment_id'        => 'EQP-TD3-' . uniqid(),
            'brand'               => 'TestBrand',
            'current_status'      => 'available',
            'checklist_master_id' => $checklistMaster->id,
        ]);

        $this->assertNull($equipment->customerAdminTemplates());
    }
}
