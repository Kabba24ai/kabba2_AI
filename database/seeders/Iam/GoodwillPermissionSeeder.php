<?php

namespace Database\Seeders\Iam;

use App\Models\Iam\AccessControl\Module;
use App\Models\Iam\AccessControl\ModuleCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

/**
 * Creates ONLY the Goodwill module and its three permissions.
 *
 * WHY THIS EXISTS RATHER THAN RUNNING ModuleSeeder.
 *
 * `ModuleSeeder` is a full RECONCILIATION seeder, not an additive one. Its
 * run() ends with:
 *
 *     Permission::where('module_id', $module->id)->whereNotIn('name', $keep)->delete();
 *     Module::whereNotIn('title', $modulesKept)->delete();
 *     ModuleCategory::whereNotIn('title', $categoriesKept)->delete();
 *     Permission::whereNull('module_id')->delete();
 *
 * Anything in the database that is not in its hardcoded `$moduleList` is
 * DELETED. This was verified empirically, not inferred: three planted rows —
 * an orphan permission, an unrelated module category, and an extra permission
 * attached to an existing module — were all removed by a single re-run.
 * Deleting a Spatie permission cascades through `role_has_permissions` and
 * `model_has_permissions`, so access is revoked silently.
 *
 * It also throws outright when no `Master Admin` role exists, and it is not
 * wrapped in a transaction, so a failure part-way leaves modules and
 * permissions half-created.
 *
 * Running it in production to obtain three new permissions would risk the
 * entire access-control table to add three rows. This seeder does only the
 * additive part.
 *
 * GUARANTEES:
 *  - Creates missing rows; never deletes or renames anything.
 *  - Never touches roles or role assignments. Granting `goodwill.apply` to a
 *    manager role is a deliberate IAM action, done through the admin screen —
 *    a seeder that hands out authority to reduce revenue would defeat the
 *    separation FD-002 §7.3 exists to enforce.
 *  - Idempotent: running it repeatedly produces no further changes.
 *  - Transactional: either the module and all three permissions exist, or
 *    nothing was written.
 */
class GoodwillPermissionSeeder extends Seeder
{
    private const CATEGORY = 'Goodwill Adjustment';
    private const MODULE   = 'goodwill';

    /** permission suffix => human title */
    private const PERMISSIONS = [
        'apply'   => 'Apply Goodwill Adjustment',
        'reverse' => 'Reverse Goodwill Adjustment',
        'view'    => 'View Goodwill Adjustments',
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $category = ModuleCategory::firstOrCreate(['title' => self::CATEGORY]);

            $module = Module::firstOrCreate([
                'module_category_id' => $category->id,
                'name'               => self::MODULE,
            ], [
                'title'      => 'Goodwill Adjustment',
                'model_name' => 'OrderGoodwillAdjustment',
            ]);

            $names = [];

            foreach (self::PERMISSIONS as $suffix => $title) {
                $name = self::MODULE.'.'.$suffix;
                $names[] = $name;

                // Matched on name + guard only. Title is a creation-only value
                // so re-running never rewrites a title an administrator may
                // have edited.
                $permission = Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    ['title' => $title, 'permission_to_all' => 'No'],
                );

                if ($permission->module_id !== $module->id) {
                    $permission->update(['module_id' => $module->id]);
                }
            }

            // Keep the module's own descriptor consistent with what was
            // created, so the IAM screen can render its checkboxes. Assigned
            // only when it would actually change, so a re-run is a no-op.
            $descriptor = [
                'permission_names'   => implode(',', array_keys(self::PERMISSIONS)),
                'permission_options' => json_encode($names),
            ];

            $module->fill($descriptor);

            if ($module->isDirty()) {
                $module->need_set_permissions = 'Yes';
                $module->save();
            }
        });

        $this->command?->info('Goodwill permissions ensured: '.implode(', ', array_map(
            fn ($s) => self::MODULE.'.'.$s,
            array_keys(self::PERMISSIONS),
        )));
        $this->command?->warn('Nothing was granted. Assign goodwill.apply / goodwill.reverse to a manager role through the IAM screen.');
    }
}
