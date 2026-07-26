<?php

namespace Tests\Feature\Service;

use App\Models\Iam\Personnel\User;
use App\Models\Service\ServiceSymptom;
use App\Models\Service\ServiceSymptomCategory;
use App\Services\ServiceManagement\ServiceProblemLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One canonical Service Problem Engine: a single symptom repository + profile
 * model shared by Standard Service and Field Service intake, with contextual
 * categories (Field Conditions & Recovery) added for the operating environment.
 */
class ServiceProblemEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create([
            'unique_id' => 'spe-admin', 'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'spe-admin@test.local', 'status' => 'Active',
        ]);
        $this->actingAs($this->admin);
    }

    // 1 + 3. Both intakes draw from the SAME canonical symptom records.
    public function test_both_intakes_render_the_shared_symptom_library(): void
    {
        // A shared library symptom seeded by the library migration.
        $shared = ServiceSymptom::where('name', 'Will not start')->firstOrFail();

        $shop = $this->get(route('admin.service-management.tickets.create'))->assertOk()->getContent();
        $field = $this->get(route('admin.field-service.tickets.create'))->assertOk()->getContent();

        $this->assertStringContainsString('Will not start', $shop);
        $this->assertStringContainsString('Will not start', $field);
        // Same record id embedded in both — one repository, not two.
        $this->assertStringContainsString('"id":' . $shared->id . ',"name":"Will not start"', $shop);
        $this->assertStringContainsString('"id":' . $shared->id . ',"name":"Will not start"', $field);
    }

    // The presenter is the single source; it exposes categories, symptoms, and
    // the equipment-profile payload (product/category/includes/excludes/items).
    public function test_problem_library_presenter_exposes_repository_and_profiles(): void
    {
        $this->assertTrue(ServiceProblemLibrary::symptoms()->contains(fn ($s) => $s['name'] === 'Will not start'));
        $this->assertTrue(ServiceProblemLibrary::categories()->contains(fn ($c) => $c['name'] === 'Engine & Starting'));

        // The seeded "Boom Lift" profile is available with its resolution keys.
        $profile = ServiceProblemLibrary::profiles()->firstWhere('name', 'Boom Lift');
        $this->assertNotNull($profile);
        $this->assertArrayHasKey('category_ids', $profile);
        $this->assertArrayHasKey('additions', $profile);
        $this->assertArrayHasKey('exclusions', $profile);
    }

    // 4 + 5. Field Conditions & Recovery is a normal category in the ONE
    // repository — no separate field problems table.
    public function test_field_conditions_category_is_seeded_into_the_shared_library(): void
    {
        $category = ServiceSymptomCategory::where('name', 'Field Conditions & Recovery')->first();
        $this->assertNotNull($category, 'Field Conditions & Recovery category must be seeded.');
        $this->assertTrue($category->is_active);

        // Its symptoms are plain service_symptoms rows.
        $stuck = ServiceSymptom::where('name', 'Machine stuck in mud')->first();
        $this->assertNotNull($stuck);
        $this->assertSame($category->id, $stuck->service_symptom_category_id);

        // And they surface in Field Service intake (additive to the library).
        $field = $this->get(route('admin.field-service.tickets.create'))->assertOk()->getContent();
        $this->assertStringContainsString('Machine stuck in mud', $field);
    }

    // Distinct from equipment symptoms — a recovery condition is not a "Track"
    // symptom; both live in the same engine under meaningful categories.
    public function test_field_conditions_are_distinct_from_equipment_symptoms(): void
    {
        $stuck = ServiceSymptom::where('name', 'Machine stuck in mud')->firstOrFail();
        $track = ServiceSymptom::where('name', 'Track came off')->firstOrFail();

        $this->assertNotSame($stuck->service_symptom_category_id, $track->service_symptom_category_id);
    }

    // Hardened migration: an EXISTING category with a missing symptom is topped
    // up — the migration must not skip merely because the category exists.
    public function test_migration_inserts_missing_symptoms_when_category_already_exists(): void
    {
        $category = ServiceSymptomCategory::where('name', 'Field Conditions & Recovery')->firstOrFail();

        // Simulate a missing symptom under the existing category.
        ServiceSymptom::where('name', 'Machine stuck in mud')->delete();
        $this->assertDatabaseMissing('service_symptoms', ['name' => 'Machine stuck in mud']);
        $categoriesBefore = ServiceSymptomCategory::where('name', 'Field Conditions & Recovery')->count();

        // Re-run the migration — it should find the category and re-insert the
        // missing symptom (not early-return).
        $migration = require database_path('migrations/2026_07_26_110000_seed_field_conditions_recovery_symptoms.php');
        $migration->up();

        $this->assertDatabaseHas('service_symptoms', [
            'name'                        => 'Machine stuck in mud',
            'service_symptom_category_id' => $category->id,
        ]);
        // Category was not duplicated.
        $this->assertSame($categoriesBefore, ServiceSymptomCategory::where('name', 'Field Conditions & Recovery')->count());
    }
}
