<?php

namespace Tests\Feature\Service;

use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Service\ServiceSymptom;
use App\Models\Service\ServiceSymptomCategory;
use App\Models\Service\ServiceSymptomProfile;
use App\Models\Service\ServiceSymptomProfileSymptom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Equipment Reported-Problem templates — built by EXTENDING the symptom
 * library (no parallel problem_* tables). Pins: library CRUD + reorder +
 * deactivate, the template builder's explicit ordered item list, per-unit
 * attachment (single + bulk), and the schema gaps the migration added.
 */
class ProblemTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::create([
            'first_name' => 'Problem', 'last_name' => 'Admin',
            'email' => 'problem-admin@test.local', 'status' => 'Active',
        ]));
    }

    private function makeCategory(string $name, int $order = 10): ServiceSymptomCategory
    {
        return ServiceSymptomCategory::create(['name' => $name, 'display_order' => $order, 'is_active' => true]);
    }

    private function makeItem(ServiceSymptomCategory $cat, string $name, int $order = 10): ServiceSymptom
    {
        return ServiceSymptom::create([
            'service_symptom_category_id' => $cat->id, 'name' => $name,
            'display_order' => $order, 'is_active' => true,
        ]);
    }

    private function makeEquipment(string $name): Equipment
    {
        $category = ProductCategory::create(['title' => 'PT-' . Str::random(5), 'status' => 'Published', 'sort_order' => 1]);

        return Equipment::create([
            'unique_id' => 'pt-eq-' . Str::random(6), 'equipment_name' => $name,
            'equipment_id' => 'PT-' . Str::random(4), 'brand' => 'Test',
            'product_category_id' => $category->id,
            'current_status' => 'available', 'not_for_rent' => 0,
        ]);
    }

    // ── Schema foundation ───────────────────────────────────────────────

    public function test_migration_added_the_three_gap_columns(): void
    {
        $this->assertTrue(\Schema::hasColumn('service_symptom_profiles', 'description'));
        $this->assertTrue(\Schema::hasColumn('service_symptom_profile_symptoms', 'sort_order'));
        $this->assertTrue(\Schema::hasColumn('equipment', 'service_symptom_profile_id'));
    }

    // ── Library CRUD ────────────────────────────────────────────────────

    public function test_category_crud_and_reorder(): void
    {
        $this->postJson(route('admin.service-management.problem-templates.categories.store'), ['name' => 'Engine'])
            ->assertOk()->assertJsonPath('success', true);
        $a = ServiceSymptomCategory::firstOrFail();

        $b = $this->makeCategory('Hydraulics', 20);

        $this->postJson(route('admin.service-management.problem-templates.categories.reorder'), ['ids' => [$b->id, $a->id]])
            ->assertOk();
        $this->assertTrue($b->fresh()->display_order < $a->fresh()->display_order);

        // Deactivate (not delete) preserves history.
        $this->putJson(route('admin.service-management.problem-templates.categories.update', $a), ['is_active' => false])
            ->assertOk();
        $this->assertFalse($a->fresh()->is_active);
    }

    public function test_category_with_items_cannot_be_hard_deleted(): void
    {
        $cat = $this->makeCategory('Engine');
        $this->makeItem($cat, 'Will not start');

        $this->deleteJson(route('admin.service-management.problem-templates.categories.destroy', $cat))
            ->assertStatus(422);
        $this->assertDatabaseHas('service_symptom_categories', ['id' => $cat->id]);
    }

    public function test_item_crud_within_a_category(): void
    {
        $cat = $this->makeCategory('Engine');

        $this->postJson(route('admin.service-management.problem-templates.items.store'), [
            'service_symptom_category_id' => $cat->id, 'name' => 'Will not start',
        ])->assertOk();

        $item = ServiceSymptom::firstOrFail();
        $this->assertSame($cat->id, $item->service_symptom_category_id);

        $this->putJson(route('admin.service-management.problem-templates.items.update', $item), ['name' => 'Will not crank'])
            ->assertOk();
        $this->assertSame('Will not crank', $item->fresh()->name);
    }

    public function test_duplicate_item_name_is_rejected(): void
    {
        $cat = $this->makeCategory('Engine');
        $this->makeItem($cat, 'Will not start');

        $this->postJson(route('admin.service-management.problem-templates.items.store'), [
            'service_symptom_category_id' => $cat->id, 'name' => 'Will not start',
        ])->assertStatus(422);
    }

    // ── Template builder ────────────────────────────────────────────────

    public function test_template_builder_saves_an_explicit_ordered_item_list(): void
    {
        $cat = $this->makeCategory('Engine');
        $a = $this->makeItem($cat, 'Will not start', 10);
        $b = $this->makeItem($cat, 'Low power', 20);
        $c = $this->makeItem($cat, 'Overheating', 30);

        $create = $this->postJson(route('admin.service-management.problem-templates.store'), ['name' => 'Skid Steer'])
            ->assertOk();
        $template = ServiceSymptomProfile::firstOrFail();
        $this->assertStringContainsString('/builder', $create->json('builder_url'));

        // Save in a deliberate order (c, a, b) — not the library display order.
        $this->postJson(route('admin.service-management.problem-templates.save-items', $template), [
            'item_ids' => [$c->id, $a->id, $b->id],
        ])->assertOk()->assertJsonPath('count', 3);

        $ordered = ServiceSymptomProfileSymptom::where('service_symptom_profile_id', $template->id)
            ->orderBy('sort_order')->pluck('service_symptom_id')->all();
        $this->assertSame([$c->id, $a->id, $b->id], $ordered);

        // Re-saving replaces the set (idempotent, no duplicates).
        $this->postJson(route('admin.service-management.problem-templates.save-items', $template), [
            'item_ids' => [$a->id, $b->id],
        ])->assertOk();
        $this->assertSame(2, ServiceSymptomProfileSymptom::where('service_symptom_profile_id', $template->id)->count());
    }

    public function test_deleting_a_template_detaches_units_not_deletes_them(): void
    {
        $template = ServiceSymptomProfile::create(['name' => 'Boom Lift', 'display_order' => 10, 'is_active' => true]);
        $unit = $this->makeEquipment('Boom Lift 40');
        $unit->update(['service_symptom_profile_id' => $template->id]);

        $this->deleteJson(route('admin.service-management.problem-templates.destroy', $template))->assertOk();

        $this->assertDatabaseMissing('service_symptom_profiles', ['id' => $template->id]);
        $this->assertDatabaseHas('equipment', ['id' => $unit->id]); // unit survives
        $this->assertNull($unit->fresh()->service_symptom_profile_id); // reverts to No Template Listed
    }

    // ── Equipment attachment ────────────────────────────────────────────

    public function test_single_and_bulk_attachment(): void
    {
        $template = ServiceSymptomProfile::create(['name' => 'Skid Steer', 'display_order' => 10, 'is_active' => true]);
        $u1 = $this->makeEquipment('Skid 1');
        $u2 = $this->makeEquipment('Skid 2');

        // Single attach
        $this->postJson(route('admin.service-management.problem-templates.equipment.attach'), [
            'equipment_id' => $u1->id, 'template_id' => $template->id,
        ])->assertOk();
        $this->assertSame($template->id, $u1->fresh()->service_symptom_profile_id);

        // Bulk apply to both
        $this->postJson(route('admin.service-management.problem-templates.equipment.bulk-apply'), [
            'equipment_ids' => [$u1->id, $u2->id], 'template_id' => $template->id,
        ])->assertOk();
        $this->assertSame($template->id, $u2->fresh()->service_symptom_profile_id);

        // Clear via null
        $this->postJson(route('admin.service-management.problem-templates.equipment.attach'), [
            'equipment_id' => $u1->id, 'template_id' => null,
        ])->assertOk();
        $this->assertNull($u1->fresh()->service_symptom_profile_id);
    }

    // ── Pages load + auth ───────────────────────────────────────────────

    public function test_management_pages_render(): void
    {
        $this->get(route('admin.service-management.problem-templates.index'))->assertOk()->assertSee('Problem Templates');
        $this->get(route('admin.service-management.problem-templates.library'))->assertOk()->assertSee('Problem Library');
        $this->get(route('admin.service-management.problem-templates.equipment'))->assertOk()->assertSee('Equipment Templates');
    }

    public function test_guests_are_rejected(): void
    {
        auth()->logout();
        $this->flushSession();

        $r = $this->get(route('admin.service-management.problem-templates.index'));
        $this->assertContains($r->getStatusCode(), [302, 401, 403]);
    }
}
