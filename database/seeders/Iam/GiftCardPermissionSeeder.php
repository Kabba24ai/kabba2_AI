<?php

namespace Database\Seeders\Iam;

use App\Models\Iam\AccessControl\Module;
use App\Models\Iam\AccessControl\ModuleCategory;
use App\Services\GiftCards\GiftCardPermissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Registers the Gift Cards module and its permissions. **Additive only.**
 *
 * ── WHY THIS EXISTS SEPARATELY FROM ModuleSeeder ──────────────────────────
 *
 * `ModuleSeeder` is a destructive reconciliation seeder: it deletes
 * permissions attached to a module but absent from its hardcoded list,
 * modules and categories absent from that list, and **every permission with a
 * null `module_id`**. Deleting a Spatie permission cascades through role and
 * user assignments and revokes access silently. It must never run against
 * production.
 *
 * This seeder performs no reconciliation and issues no DELETE: it creates what
 * is missing, updates what it owns, and leaves everything else untouched. Safe
 * to run repeatedly. Same shape as `GoodwillPermissionSeeder`.
 *
 * ── UNTIL THIS RUNS, EVERY GIFT CARD OPERATION IS REFUSED ─────────────────
 *
 * {@see GiftCardPermissions} treats an unregistered permission as a denial and
 * logs it. That is deliberate: the alternative — an unseeded database where
 * `grant()` answers "unknown" and the caller reads it as "yes" — is an
 * unlimited cash-issuing endpoint.
 */
class GiftCardPermissionSeeder extends Seeder
{
    private const CATEGORY_TITLE = 'Gift Cards';

    private const MODULE_NAME = 'gift-card';

    private const MODULE_TITLE = 'Gift Cards';

    private const MODEL_NAME = 'GiftCard';

    public function run(): void
    {
        $category = ModuleCategory::firstOrCreate(['title' => self::CATEGORY_TITLE]);

        $module = Module::firstOrCreate([
            'module_category_id' => $category->id,
            'name' => self::MODULE_NAME,
        ]);

        // Suffixes only ('view,sell,grant,…'), matching ModuleSeeder's own
        // convention for this column rather than the qualified names.
        $suffixes = array_map(
            static fn (string $name): string => substr($name, strlen(self::MODULE_NAME) + 1),
            array_keys(GiftCardPermissions::all()),
        );

        $module->update([
            'title' => self::MODULE_TITLE,
            'model_name' => self::MODEL_NAME,
            'permission_names' => implode(',', $suffixes),
            'permission_options' => json_encode(array_keys(GiftCardPermissions::all())),
        ]);

        // Absent on a database that has never been seeded — the grant is then
        // skipped rather than failing.
        //
        // That skip is REPORTED rather than silent. A seeder that creates nine
        // permissions, attaches them to nobody, and prints nothing looks
        // exactly like success — and the first symptom would be an operator
        // being refused by a workspace that appears to be installed correctly.
        $masterAdmin = Role::where('name', 'Master Admin')->first();

        foreach (GiftCardPermissions::all() as $name => $title) {
            // Searched on (name, guard_name) only — the pair Spatie's unique
            // index covers. ModuleSeeder also searches on `title`, which
            // silently creates a duplicate the day a title is reworded.
            $permission = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['title' => $title, 'permission_to_all' => 'No'],
            );

            $permission->update([
                'module_id' => $module->id,
                'title' => $title,
            ]);

            // Master Admin gets everything. NO OTHER ROLE IS GRANTED ANYTHING
            // HERE — particularly not `grant` or `adjust`, which create money.
            // Who else may issue value is a business decision, made in the
            // roles screen, never a default a seeder quietly applies.
            $masterAdmin?->givePermissionTo($permission);
        }

        // Spatie caches the permission table; a check made in this same process
        // would otherwise read a cache that predates these rows.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->report($masterAdmin, count(GiftCardPermissions::all()));
    }

    /**
     * Say what actually happened, so a no-op cannot pass for success.
     *
     * Written for whoever is reading a deployment log at the time, which is
     * why it names the next action rather than only the outcome.
     */
    private function report(?Role $masterAdmin, int $count): void
    {
        if (! isset($this->command)) {
            return;
        }

        $this->command->info("Gift card permissions: {$count} registered.");

        if ($masterAdmin) {
            $held = $masterAdmin->permissions()->where('name', 'like', 'gift-card.%')->count();
            $this->command->info("Granted to role [Master Admin] — now holds {$held} of {$count}.");

            return;
        }

        $this->command->warn('Role [Master Admin] was not found.');
        $this->command->warn(
            "The {$count} gift card permissions exist, but NO ROLE HOLDS THEM, so every gift card "
            .'operation will be refused. Assign them in the roles screen, or grant them to a user directly.'
        );
    }
}
