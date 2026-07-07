# Platform Stabilization Sprint 1 — ModuleSeeder Reliability Repair

Date: 2026-07-04
Branch: `raj_development`
Status: **Complete.** `ModuleSeeder::run()` now completes successfully from a genuinely empty state, is idempotent on repeat runs, and correctly seeds both a pre-existing module (`personnel`) and the new Customer Credit module — verified via the real, unmodified seeder logic, not a workaround.

---

## Root Cause

Two independent `NOT NULL` columns with no default value anywhere in the stack (not in their migration, not in their Eloquent model, not supplied by `ModuleSeeder`'s own insert calls) caused any attempt to create a **brand-new** module or permission to fail — while never affecting already-existing rows, which is why this went undetected until Customer Credit Phase 3.1 tried to seed its first genuinely new module category.

| Column | Migration | Default anywhere? |
|---|---|---|
| `modules.need_set_permissions` | `2025_05_22_112417_create_modules_table.php` — `$table->enum('need_set_permissions', ['Yes','No']);` | No — not in the migration, not on `App\Models\Iam\AccessControl\Module`, not in `ModuleSeeder`'s `Module::firstOrCreate([...])` call |
| `permissions.permission_to_all` | `2025_05_22_195301_alter_column_to_permissions_table.php` — `$table->enum('permission_to_all', ['No','Yes'])->after('guard_name');` | No — not in the migration, not on any Permission model, not in `ModuleSeeder`'s `Permission::firstOrCreate([...])` call |

**A third, related fact discovered during root-cause analysis, not a defect itself**: `ModuleSeeder.php` creates modules via the customized `App\Models\Iam\AccessControl\Module` (imported explicitly), but creates permissions via **Spatie's own** `Spatie\Permission\Models\Permission` — not the customized `App\Models\Iam\AccessControl\Permission`. Both share the same underlying `permissions` table (confirmed: `config/permission.php` is left at its default, so Spatie's own class and the custom one are two different Eloquent views onto identical rows), but this means a model-level default added only to the custom `AccessControl\Permission` class would never actually run during seeding, since the seeder never instantiates that class. This directly shaped where the real fix had to go (see below).

**Reproduction, confirming this predates Customer Credit entirely**: calling `Module::firstOrCreate(['module_category_id' => ..., 'name' => 'personnel'])` — the original, completely untouched Personnel module, part of the very first module category ever defined — failed with the identical `NOT NULL constraint failed: modules.need_set_permissions` error, with zero Customer Credit code involved.

## Files Changed

| File | Change | Why here |
|---|---|---|
| `app/Models/Iam/AccessControl/Module.php` | Added `protected $attributes = ['need_set_permissions' => 'No'];` | This is the actual class `ModuleSeeder` uses to create modules — a model-level default is the correct, framework-idiomatic fix, and protects any *other* code path that creates a `Module` without explicitly setting this field, not just the seeder |
| `app/Models/Iam/AccessControl/Permission.php` | Added `protected $attributes = ['permission_to_all' => 'No'];` | Defense-in-depth for any code path that *does* use this customized class to create a permission — **note: this alone does not fix the seeder**, since the seeder uses Spatie's class instead (see below) |
| `database/seeders/Iam/ModuleSeeder.php` | `Permission::firstOrCreate([...])` now supplies `'permission_to_all' => 'No'` as the method's **second** argument (creation-only values, not search criteria) | The actual point where the real defect manifests for the seeder specifically, since it uses Spatie's vendor `Permission` model, which must never be modified directly. Passed as the second `firstOrCreate()` argument, not merged into the first, specifically so this does not change which existing row a lookup matches — an existing permission with a different `permission_to_all` value (if one ever exists) is still found correctly, not treated as missing |

**No other file was touched.** `ModuleSeeder`'s overall architecture, loop structure, and the meaning/values of `need_set_permissions`/`permission_to_all` are entirely unchanged — this is a defaults-only repair, not a redesign.

## Why 'No' Is the Correct Default (not merely convenient)

- `need_set_permissions`: `ModuleSeeder`'s own logic only ever sets this to `'Yes'` when a module's permission set actually *changed* (`if ($module_item->isDirty()) { $module_item->need_set_permissions = 'Yes'; ... }`). A brand-new module, at the exact moment of creation, has no such prior state to have changed *from* — `'No'` is the logically correct starting state, not an arbitrary placeholder. (In practice, a newly-created module's `title`/`model_name` assignment immediately after creation does mark it dirty, so it correctly flips to `'Yes'` on the very next line regardless — confirmed in this sprint's validation.)
- `permission_to_all`: confirmed via repository-wide search to be read by **zero** authorization checks anywhere in this codebase today — it is inert metadata. `'No'` (matching the enum's own listed order, `['No','Yes']`) is the least-privileged, safest possible default for a field that, if ever wired into a real check, would mean "this permission is automatically granted to everyone." Defaulting a not-yet-used grant-to-everyone flag to "off" is the only defensible choice.

## Validation

Using the same rolled-back-transaction methodology this initiative has used since Phase 2.4 — this time exercising the real, unmodified `ModuleSeeder::run()` end-to-end, not a workaround:

1. **Existing modules still seed correctly**: all of `personnel`, `roles`, `modules`, `product_categories`, `products` were created without error.
2. **Existing permissions remain unchanged in meaning**: `personnel.view`/`.add`/`.edit`/`.delete` were created exactly as the seeder's own data defines them — no change to names, titles, or the module they belong to.
3. **New modules can now be created**: `customer_credit` (the module added in Customer Credit Phase 3.1) was created successfully in the same run, with no manual workaround.
4. **Customer Credit permissions seed correctly**: all six (`customer_credit.view`/`.grant`/`.redeem`/`.reverse`/`.delete`/`.view_audit_history`) were created, correctly attached to the `customer_credit` module, and correctly granted to the Master Admin role (`$masterAdmin->hasPermissionTo('customer_credit.grant')` returned `true` — confirmed the *entire* chain works, not merely that rows exist).
5. **Idempotency**: running the seeder a second time in the same transaction produced identical module and permission counts (6 modules, 23 permissions, before and after) — no duplicates.
6. **Column values landed correctly**: the created module's `need_set_permissions` was `'Yes'` (correctly flipped dirty by the subsequent `title`/`model_name` assignment, per the seeder's own existing logic) and the created permission's `permission_to_all` was `'No'` (the intended default) — neither was `null`.

**Zero rows persisted after rollback**, confirmed via direct post-rollback count queries.

## Regression Checks

- Full `tests/Unit` suite: **50/50 passing**, unchanged from before this sprint.
- `git diff --stat` confirms **zero changes** to `LedgerBalanceService.php`, `CustomHelper.php`, `CustomerCreditService.php`, `CustomerCredit.php`, or any other Financial Engine / Customer Credit file — this sprint touched exactly three files, all platform infrastructure.
- `ModuleSeeder`'s loop structure, module-list data, and permission-naming convention are byte-for-byte unchanged except for the one added `firstOrCreate()` argument.

## Future Recommendations

- Consider auditing other `NOT NULL` enum columns across the `Iam\AccessControl` schema for the same missing-default pattern, proactively, rather than discovering each one only when a brand-new row of that type is first created — this sprint fixed the two *confirmed* instances, not a systematic sweep (out of this sprint's explicit scope).
- Consider whether `ModuleSeeder` should use the customized `App\Models\Iam\AccessControl\Permission` consistently, rather than Spatie's base class, for internal consistency with how `Module`/`Role` are already handled — **not decided or done here**, since this sprint's rule was "do not redesign ModuleSeeder," and changing which model class is used is a design decision, not a defaults repair.
