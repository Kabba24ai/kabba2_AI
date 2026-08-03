<?php

namespace Database\Seeders\Iam;

use App\Models\Iam\AccessControl\Module;
use App\Models\Iam\AccessControl\ModuleCategory;
use App\Services\Goodwill\GoodwillPermissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Registers the Goodwill module and its permissions. **Additive only.**
 *
 * ── WHY THIS EXISTS SEPARATELY FROM ModuleSeeder ──────────────────────────
 *
 * `ModuleSeeder` is a destructive reconciliation seeder. It deletes:
 *
 *   - permissions attached to a module but absent from its hardcoded list;
 *   - modules absent from that list;
 *   - module categories absent from that list;
 *   - **every permission with a null `module_id`**, whatever it is for.
 *
 * Deleting a Spatie permission cascades through role and user assignments and
 * revokes access silently. It must never run against production. This seeder
 * therefore performs no reconciliation and issues no DELETE of any kind: it
 * creates what is missing, updates what it owns, and leaves everything else
 * exactly as it found it. It is safe to run repeatedly.
 *
 * The same module is ALSO declared in `ModuleSeeder`'s list. Not because that
 * seeder will be run, but because if it ever is, an undeclared module and its
 * permissions would be among the rows it deletes. Declaring it there is a
 * safety net, not a dependency.
 *
 * ── module_id IS NOT OPTIONAL ─────────────────────────────────────────────
 *
 * A permission created without `module_id` is deleted outright by
 * `ModuleSeeder`'s final statement. The module is therefore created first and
 * every permission is attached to it.
 */
class GoodwillPermissionSeeder extends Seeder
{
    private const CATEGORY_TITLE = 'Goodwill';

    private const MODULE_NAME = 'goodwill';

    private const MODULE_TITLE = 'Goodwill';

    private const MODEL_NAME = 'OrderGoodwillAdjustment';

    public function run(): void
    {
        $category = ModuleCategory::firstOrCreate(['title' => self::CATEGORY_TITLE]);

        $module = Module::firstOrCreate([
            'module_category_id' => $category->id,
            'name' => self::MODULE_NAME,
        ]);

        // The suffix list, matching ModuleSeeder's own convention for this
        // column ('apply,reverse', not the fully-qualified names).
        $suffixes = array_map(
            static fn (string $name): string => substr($name, strlen(self::MODULE_NAME) + 1),
            array_keys(GoodwillPermissions::all()),
        );

        $module->update([
            'title' => self::MODULE_TITLE,
            'model_name' => self::MODEL_NAME,
            'permission_names' => implode(',', $suffixes),
            'permission_options' => json_encode(array_keys(GoodwillPermissions::all())),
        ]);

        // Absent on a database that has never been seeded — the grant is then
        // skipped rather than failing. Nothing else depends on the role.
        $masterAdmin = Role::where('name', 'Master Admin')->first();

        foreach (GoodwillPermissions::all() as $name => $title) {
            // Searched on (name, guard_name) only — the pair Spatie's own
            // unique index covers. ModuleSeeder additionally searches on
            // `title`, which silently creates a duplicate row the day a title
            // is reworded; not repeated here.
            $permission = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['title' => $title, 'permission_to_all' => 'No'],
            );

            $permission->update([
                'module_id' => $module->id,
                'title' => $title,
            ]);

            $masterAdmin?->givePermissionTo($permission);
        }

        // Spatie caches the permission table. Without this, a check made in the
        // same process as the seed reads a cache that predates the rows.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
