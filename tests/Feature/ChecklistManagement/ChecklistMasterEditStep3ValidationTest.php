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
 * P3-4 / CLEAN-9 (Phase 3 housekeeping): the Checklist Master wizard's Step 3
 * "Continue" button had no `disabled` HTML attribute at all in Edit mode (only
 * CSS classes cosmetically suggested a disabled state) — its inline
 * onclick="goToStep(4)" always fired regardless. A prior JS block also
 * unconditionally re-enabled it. Since every existing record's
 * customer_admin_template_id is non-null in practice (required at creation),
 * this had no live impact, but the edit template now genuinely gates the
 * button on whether a template is actually assigned, matching Create mode.
 *
 * P3 / BUG-15: that CLEAN-9 fix left a contradictory second script block in
 * place — an earlier DOMContentLoaded handler still unconditionally set
 * continue3Btn.disabled = true whenever a template was already assigned (the
 * exact opposite of CLEAN-9's fix), and the whole thing only "worked" because
 * DOMContentLoaded listeners fire in registration order and CLEAN-9's block
 * happened to run second. This consolidated both into a single named function,
 * setStep3ContinueButtonState(), and removed the earlier contradictory
 * assignment entirely — see docs/checklist-system-audit/P3_BUG15_STEP3_GATING.md.
 *
 * These are render-level checks — no browser/JS test runner is configured in
 * this project, so the actual client-side disabled-state/click behavior is
 * not executed here; a manual browser smoke test was performed separately
 * (see P3_BUG15_STEP3_GATING.md).
 */
class ChecklistMasterEditStep3ValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ProductCategory $category;
    private RentalReadyChecklistTemplate $rentalReadyTemplate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Edit', 'last_name' => 'Admin',
            'email' => 'edit-step3-admin@example.com', 'password' => bcrypt('password'),
        ]);

        $this->category = ProductCategory::create(['title' => 'Test Category', 'slug' => 'test-category-' . uniqid()]);
        $this->rentalReadyTemplate = RentalReadyChecklistTemplate::create([
            'template_name'         => 'Test RR Template',
            'active_template'       => true,
            'equipment_category_id' => $this->category->id,
        ]);
    }

    public function test_edit_page_marks_continue_enabled_when_customer_template_already_assigned(): void
    {
        $customerAdminTemplate = CustomerAdminTemplate::create([
            'template_name'         => 'Test CA Template',
            'active_template'       => true,
            'equipment_category_id' => $this->category->id,
        ]);

        $master = ChecklistMaster::create([
            'checklist_system_name'      => 'Master With Template',
            'equipment_category_id'      => $this->category->id,
            'rental_ready_template_id'   => $this->rentalReadyTemplate->id,
            'customer_admin_template_id' => $customerAdminTemplate->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.checklist-management.checklist-master.edit', $master->unique_id));

        $response->assertOk();
        $response->assertSee('id="hiddenCustomerTemplateId" value="' . $customerAdminTemplate->id . '"', false);
        $response->assertSee('setStep3ContinueButtonState(Boolean(hiddenCustomerTemplateId.value));', false);
    }

    public function test_edit_page_leaves_continue_disabled_when_no_customer_template_assigned(): void
    {
        // Directly via Eloquent, bypassing UpdateRequest's `required` rule, to
        // exercise the data-edge-case this fix specifically guards against.
        $master = ChecklistMaster::create([
            'checklist_system_name'      => 'Master Without Template',
            'equipment_category_id'      => $this->category->id,
            'rental_ready_template_id'   => $this->rentalReadyTemplate->id,
            'customer_admin_template_id' => null,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.checklist-management.checklist-master.edit', $master->unique_id));

        $response->assertOk();
        $response->assertSee('id="hiddenCustomerTemplateId" value=""', false);
        // Same initial-state call site as the "already assigned" test — the
        // disabled outcome for this record comes from Boolean('') being falsy,
        // not from a separate disabling code path.
        $response->assertSee('setStep3ContinueButtonState(Boolean(hiddenCustomerTemplateId.value));', false);
    }

    // ── BUG-15: single source of truth, contradictory logic removed ────────

    public function test_only_one_step3_gating_implementation_remains(): void
    {
        $customerAdminTemplate = CustomerAdminTemplate::create([
            'template_name'         => 'Test CA Template',
            'active_template'       => true,
            'equipment_category_id' => $this->category->id,
        ]);

        $master = ChecklistMaster::create([
            'checklist_system_name'      => 'Master With Template',
            'equipment_category_id'      => $this->category->id,
            'rental_ready_template_id'   => $this->rentalReadyTemplate->id,
            'customer_admin_template_id' => $customerAdminTemplate->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.checklist-management.checklist-master.edit', $master->unique_id));
        $response->assertOk();
        $html = $response->getContent();

        // Exactly one function declaration, and it is the only place that ever
        // assigns continue3Btn.disabled.
        $this->assertSame(1, substr_count($html, 'function setStep3ContinueButtonState('));
        $this->assertSame(1, substr_count($html, 'continue3Btn.disabled ='));
    }

    public function test_contradictory_assigned_template_disables_button_logic_is_gone(): void
    {
        $customerAdminTemplate = CustomerAdminTemplate::create([
            'template_name'         => 'Test CA Template',
            'active_template'       => true,
            'equipment_category_id' => $this->category->id,
        ]);

        $master = ChecklistMaster::create([
            'checklist_system_name'      => 'Master With Template',
            'equipment_category_id'      => $this->category->id,
            'rental_ready_template_id'   => $this->rentalReadyTemplate->id,
            'customer_admin_template_id' => $customerAdminTemplate->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.checklist-management.checklist-master.edit', $master->unique_id));
        $response->assertOk();

        // The old contradictory statement that unconditionally disabled the
        // button when a template was already restored/selected must not exist
        // anywhere in the rendered page.
        $response->assertDontSee("document.getElementById('continueStep3Btn').disabled = true;", false);
    }

    public function test_template_selection_handler_enables_the_button(): void
    {
        $master = ChecklistMaster::create([
            'checklist_system_name'      => 'Master Without Template',
            'equipment_category_id'      => $this->category->id,
            'rental_ready_template_id'   => $this->rentalReadyTemplate->id,
            'customer_admin_template_id' => null,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.checklist-management.checklist-master.edit', $master->unique_id));
        $response->assertOk();

        // The radio change handler calls the same consolidated function with
        // true, rather than duplicating the enable logic inline.
        $response->assertSee('setStep3ContinueButtonState(true);', false);
    }
}
