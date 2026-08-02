<?php

namespace Tests\Feature\Orders;

use App\Models\Iam\AccessControl\Module;
use App\Models\Iam\AccessControl\ModuleCategory;
use Database\Seeders\Iam\GoodwillPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * The Goodwill permission seeder must be safe to run against production.
 *
 * It exists because `ModuleSeeder` is not: that seeder reconciles the whole
 * access-control table against a hardcoded list and DELETES everything absent
 * from it, cascading through role and user assignments. Running it to obtain
 * three new permissions would risk the entire table.
 *
 * `it_never_deletes_unrelated_access_control_rows` is the load-bearing test —
 * it plants exactly the three kinds of row ModuleSeeder was empirically shown
 * to destroy and proves this seeder leaves them alone.
 */
class GoodwillPermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_module_and_all_three_permissions(): void
    {
        $this->seed(GoodwillPermissionSeeder::class);

        $this->assertDatabaseHas('module_categories', ['title' => 'Goodwill Adjustment']);
        $this->assertDatabaseHas('modules', ['name' => 'goodwill', 'model_name' => 'OrderGoodwillAdjustment']);

        foreach (['goodwill.apply', 'goodwill.reverse', 'goodwill.view'] as $name) {
            $this->assertDatabaseHas('permissions', ['name' => $name, 'guard_name' => 'web']);
        }

        $module = Module::where('name', 'goodwill')->firstOrFail();
        $this->assertSame(3, Permission::where('module_id', $module->id)->count());
    }

    public function test_it_is_idempotent(): void
    {
        $this->seed(GoodwillPermissionSeeder::class);

        $permissions = Permission::orderBy('id')->get()->toArray();
        $modules     = Module::orderBy('id')->get()->toArray();
        $categories  = ModuleCategory::orderBy('id')->get()->toArray();

        $this->seed(GoodwillPermissionSeeder::class);
        $this->seed(GoodwillPermissionSeeder::class);

        $this->assertEquals($permissions, Permission::orderBy('id')->get()->toArray(), 'Re-running must change nothing.');
        $this->assertEquals($modules, Module::orderBy('id')->get()->toArray());
        $this->assertEquals($categories, ModuleCategory::orderBy('id')->get()->toArray());
    }

    public function test_it_never_deletes_unrelated_access_control_rows(): void
    {
        // Exactly the three shapes ModuleSeeder was proven to destroy.
        $orphan = Permission::create([
            'name' => 'legacy.custom_thing', 'title' => 'Legacy',
            'guard_name' => 'web', 'permission_to_all' => 'No',
        ]);
        $category = ModuleCategory::create(['title' => 'Unrelated Category']);
        $module = Module::create([
            'module_category_id' => $category->id,
            'name' => 'unrelated', 'title' => 'Unrelated', 'model_name' => 'Whatever',
        ]);
        $attached = Permission::create([
            'name' => 'unrelated.export', 'title' => 'Export',
            'guard_name' => 'web', 'permission_to_all' => 'No', 'module_id' => $module->id,
        ]);

        $this->seed(GoodwillPermissionSeeder::class);

        $this->assertDatabaseHas('permissions', ['id' => $orphan->id]);
        $this->assertDatabaseHas('permissions', ['id' => $attached->id]);
        $this->assertDatabaseHas('modules', ['id' => $module->id]);
        $this->assertDatabaseHas('module_categories', ['id' => $category->id]);
    }

    public function test_it_does_not_require_a_master_admin_role(): void
    {
        // ModuleSeeder throws outright when this role is missing, leaving a
        // half-created state behind. This one must not depend on it.
        $this->assertSame(0, \Spatie\Permission\Models\Role::count());

        $this->seed(GoodwillPermissionSeeder::class);

        $this->assertDatabaseHas('permissions', ['name' => 'goodwill.apply']);
    }

    public function test_it_grants_nothing_to_anyone(): void
    {
        // Authority to reduce revenue is assigned deliberately through IAM,
        // never handed out by a seeder (FD-002 §7.3).
        $this->seed(GoodwillPermissionSeeder::class);

        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('role_has_permissions')->count());
        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('model_has_permissions')->count());
    }

    public function test_an_administrator_edited_title_survives_a_rerun(): void
    {
        $this->seed(GoodwillPermissionSeeder::class);

        Permission::where('name', 'goodwill.apply')->update(['title' => 'Approve Courtesy Waiver']);

        $this->seed(GoodwillPermissionSeeder::class);

        $this->assertSame(
            'Approve Courtesy Waiver',
            Permission::where('name', 'goodwill.apply')->value('title'),
            'Title is a creation-only value; a re-run must not overwrite it.'
        );
    }
}
