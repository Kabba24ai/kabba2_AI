<?php

namespace Tests\Feature\ChecklistManagement\Templates;

use App\Models\Iam\Personnel\User;
use App\Models\ProductManagement\ProductCategory;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P3-4 / CLEAN-10 (Phase 3 housekeeping): the Rental Ready/Customer Admin
 * template builder's hidden `questions` input had no "must not be empty"
 * client-side check. Verified directly (not just from the prior audit note)
 * that the server does NOT reject an empty-questions template submission
 * either — a template with 0 questions saves successfully today. Per
 * instructions, only the client-side inline warning was added here; the
 * server-side gap is a separate, out-of-scope finding documented in
 * docs/checklist-system-audit/P3_4_HOUSEKEEPING.md.
 *
 * These are render-level checks confirming the new warning element and guard
 * script are present on both pages — no browser/JS test runner is configured
 * in this project, so the actual submit-blocking behavior is not executed
 * here.
 */
class EmptyTemplateClientValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Templ', 'last_name' => 'Admin',
            'email' => 'templ-validation-admin@example.com', 'password' => bcrypt('password'),
        ]);
    }

    public function test_rental_ready_index_renders_empty_template_warning_and_guard_script(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.checklist-management.rental-ready.index'));

        $response->assertOk();
        $response->assertSee('id="questionsRequiredWarning"', false);
        $response->assertSee('Please add at least one question to this template.', false);
        $response->assertSee("if (!window.templateData || window.templateData.length === 0)", false);
    }

    public function test_customer_admin_index_renders_empty_template_warning_and_guard_script(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.checklist-management.customer-admin.index'));

        $response->assertOk();
        $response->assertSee('id="questionsRequiredWarning"', false);
        $response->assertSee('Please add at least one question to this template.', false);
        $response->assertSee("if (!window.templateData || window.templateData.length === 0)", false);
    }

    public function test_server_side_still_accepts_an_empty_questions_template_unchanged(): void
    {
        // Documents the actual current server behavior (not the prior audit
        // note's claim) — this fix is client-side only, per instructions not to
        // change server-side business behavior.
        $category = ProductCategory::create(['title' => 'Excavators', 'slug' => 'excavators-' . uniqid()]);

        $response = $this->actingAs($this->admin)->post(route('admin.checklist-management.rental-ready.templates.store'), [
            'template_name'      => 'Empty Template',
            'equipment_category' => $category->id,
            'questions'          => '[]',
        ]);

        $response->assertRedirect();
        $this->assertSame(1, RentalReadyChecklistTemplate::count());
    }
}
