# PR-B4.1 — Category CRUD Duplication Reduction (Implementation)

**Date:** 2026-07-11
**Depends on:** `PR-B4_1_CATEGORY_READINESS.md` (the investigation and 13-test characterization baseline this PR builds on).
**Product decision (this PR):** preserve the Customer Admin hard-delete-with-cascade behavior exactly as-is. No `SoftDeletes` added, no migrations, no delete-semantics change. The underlying data-loss risk (§2.1 of the readiness review) remains documented as a separate, future product decision — not addressed by this refactor.

---

## 1. Objective

Reduce the near-line-for-line duplicated transaction/lookup/write mechanics across the 6 Category CRUD controllers (Rental Ready + Customer Admin) into one shared service, without changing any observable behavior in either tree.

---

## 2. Shared design used

`app/Services/ChecklistManagement/CategoryCrudService.php` — three methods, each taking the target model class as a parameter rather than hard-coding either tree:

- `store(string $modelClass, array $attributes, ?string $mirrorModelClass, bool $shouldMirror): Model` — creates the primary row; if `$shouldMirror` is true, creates an identical row in `$mirrorModelClass` too. Both writes happen inside one `DB::transaction()` closure, so a failure in the mirror write rolls back the primary write as well.
- `update(string $modelClass, string $uniqueId, array $attributes): Model` — looks the row up by `unique_id` via a plain `->first()` (**not** `->firstOrFail()`) and calls `->update()` on it, wrapped in `DB::transaction()`.
- `delete(string $modelClass, string $uniqueId): void` — looks the row up via `->firstOrFail()` and calls `->delete()`, wrapped in `DB::transaction()`. Has no special-casing for soft vs. hard delete — it simply calls `Model::delete()` and lets that model's own `SoftDeletes` trait (or absence of it) decide what actually happens to the row.

**What deliberately stayed in each of the 6 controllers, not the service:**
- Which model class is primary and which is the mirror target (`RentalReadyChecklistCategory`/`CustomerAdminCategory`, in each direction).
- The mirror-checkbox field name and truthiness check (`create_customer_folder` vs. `create_rental_folder`) — the service takes a plain `bool $shouldMirror` computed by the controller, never touching the request directly.
- The Request class used (`StoreRequest`/`UpdateRequest` per domain — untouched).
- The redirect route name, and the Customer Admin-only `->with('success', ...)` chain.
- The `flash()->success()`/`flash()->error()` messages and the `session()->flash('active_tab'/'active_subtab', ...)` pair — identical text in both trees today, but left in each controller rather than parameterized into the service, since they're response-shaping decisions, not CRUD mechanics.
- The `catch (\Throwable $e) { report($e); flash()->error(); redirect()->back()->... }` block — kept in each controller (not moved into the service) so each controller still owns its own user-facing error response exactly as before.

### A deliberate non-change, called out explicitly

The readiness review's own §4 suggested resolving `UpdateController`'s `->first()` to `->firstOrFail()` as a small, explicit improvement. **This PR does not make that change.** It wasn't requested in this round's scope, and per this project's standing "no unrelated changes" discipline, the shared `update()` method preserves the exact current lookup (`->first()`, not `->firstOrFail()`) — a nonexistent `unique_id` still produces a raw `Error` from calling `->update()` on `null`, caught by the same broad `catch (\Throwable)` block, with an identical user-facing redirect+error-flash outcome. This is proven unchanged by `test_rental_ready_category_update_with_nonexistent_id_rolls_back_and_flashes_error` / the Customer Admin equivalent, both re-run against the refactored code with zero assertion changes.

### Delete semantics — explicitly unchanged, per this PR's product decision

`CategoryCrudService::delete()` has no knowledge of soft vs. hard delete. `RentalReadyChecklistCategory` still soft-deletes (its `SoftDeletes` trait, untouched); `CustomerAdminCategory` still hard-deletes with its `onDelete('cascade')` FK still firing against `customer_admin_questions` (its model, untouched — no `SoftDeletes` added, no migration written). This is proven unchanged by `test_rental_ready_category_delete_soft_deletes_and_does_not_cascade_delete_questions` and `test_customer_admin_category_delete_hard_deletes_and_cascades_child_questions`, both re-run against the refactored controllers with zero assertion changes. **The underlying data-loss risk this exposes remains exactly as documented in `PR-B4_1_CATEGORY_READINESS.md` §2.1/§9 — a separate, future product decision, not something this PR's scope authorized fixing.**

### Transaction behavior

Each controller's own `DB::beginTransaction()`/`DB::commit()`/`DB::rollBack()` calls were removed — the service's `DB::transaction()` closure now owns the single transaction per request. This is not a behavior change: `DB::transaction()` commits on success and automatically rolls back and re-throws on any exception, which the controller's unchanged `catch (\Throwable $e)` block still catches exactly as it did the manually-rolled-back exception before. The cross-tree atomicity test (forcing the mirror model's `creating` event to throw) proves this concretely — both the primary and mirror rows are absent after a forced mid-transaction failure, same as before this refactor.

---

## 3. Exact files changed

| File | Change |
|---|---|
| `app/Services/ChecklistManagement/CategoryCrudService.php` *(new)* | `store()`, `update()`, `delete()` — see §2. |
| `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Categories/StoreController.php` | Constructor-injects the service; `DB::beginTransaction()`/inline create logic replaced with `store(RentalReadyChecklistCategory::class, [...], CustomerAdminCategory::class, $shouldMirror)`. Flash/session/redirect/catch unchanged. |
| `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Categories/UpdateController.php` | Constructor-injects the service; replaced with `update(RentalReadyChecklistCategory::class, $id, [...])`. Flash/session/redirect/catch unchanged. |
| `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Categories/DeleteController.php` | Constructor-injects the service; replaced with `delete(RentalReadyChecklistCategory::class, $unique_id)`. Flash/session/redirect/catch unchanged. |
| `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Categories/StoreController.php` | Same pattern, mirrors into `RentalReadyChecklistCategory::class` on `create_rental_folder`. `->with('success', ...)` chain preserved. |
| `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Categories/UpdateController.php` | Same pattern. `->with('success', ...)` chain preserved. |
| `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Categories/DeleteController.php` | Same pattern. `->with('success', ...)` chain preserved. |
| `tests/Unit/Services/ChecklistManagement/CategoryCrudServiceTest.php` *(new)* | 8 focused tests on the service — see §5. |
| `docs/checklist-system-audit/PR-B4_1_CATEGORY_REFACTOR.md` *(new, this document)* | |

**Not touched:** both `Categories` Request classes, both category models (no `SoftDeletes` added/removed, no fillable changes), both `_sub_tab_categories.blade.php` views, both route files, `tests/Feature/ChecklistManagement/Categories/CategoryCrudCharacterizationTest.php` (re-run, zero edits), anything under Questions or Templates, any database migration.

---

## 4. Commands run

```bash
php -l app/Services/ChecklistManagement/CategoryCrudService.php
php -l app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Categories/StoreController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Categories/UpdateController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Categories/DeleteController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Categories/StoreController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Categories/UpdateController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Categories/DeleteController.php
php -l tests/Unit/Services/ChecklistManagement/CategoryCrudServiceTest.php

php artisan test --env=testing \
  tests/Unit/Services/ChecklistManagement/CategoryCrudServiceTest.php \
  tests/Feature/ChecklistManagement/Categories/CategoryCrudCharacterizationTest.php

php artisan test --env=testing \
  tests/Feature/ChecklistManagement/ChecklistAssignmentServiceTest.php \
  tests/Feature/RentalReadyChecklists/ValidationGuardObservabilityTest.php \
  tests/Feature/RentalReadyChecklists/SaveControllerCharacterizationTest.php \
  tests/Feature/ChecklistManagement/EquipmentManagement/StoreControllerObservabilityTest.php
```

## 5. Pass/fail counts

```
CategoryCrudServiceTest (new, unit)                          →  8 passed
CategoryCrudCharacterizationTest (readiness-stage, unchanged) → 13 passed (54 assertions)
   — the full 13-test behavior contract, re-run with ZERO assertion changes
     against the refactored controllers.

ChecklistAssignmentServiceTest (PR-B1)                        →  7 passed
ValidationGuardObservabilityTest (PR-B3)                      →  6 passed
SaveControllerCharacterizationTest (PR-B2 Stage 1)            →  4 passed (64 assertions)
StoreControllerObservabilityTest (PR-B2 Stage 3/4)            →  5 passed (40 assertions)
```

**Total: 43/43 passing, 0 failures**, spanning this PR plus PR-B1, PR-B2, and PR-B3 — no cross-domain regressions.

(One test run in this session hit a transient Windows paging-file/memory error unrelated to any code — `VirtualAlloc() failed`, an infrastructure hiccup — and was simply re-run to completion; not a code defect.)

---

## 6. Confirmation that delete behavior is unchanged

- **Rental Ready:** `RentalReadyChecklistCategory` still soft-deletes. `test_rental_ready_category_delete_soft_deletes_and_does_not_cascade_delete_questions` (unmodified from the readiness stage) still passes: the row remains in the table with `deleted_at` set, and a child `RentalReadyChecklistQuestion` row survives (the cascade FK never fires for a soft delete, exactly as before).
- **Customer Admin:** `CustomerAdminCategory` still hard-deletes. `test_customer_admin_category_delete_hard_deletes_and_cascades_child_questions` (unmodified) still passes: the category row is completely gone from the table, and a child `CustomerAdminQuestion` row is also gone — the `onDelete('cascade')` FK still fires exactly as before. **No `SoftDeletes` trait was added anywhere. No migration was written or modified.**
- Both facts are additionally covered by the new `CategoryCrudServiceTest`'s `test_delete_soft_deletes_when_the_model_uses_soft_deletes` / `test_delete_hard_deletes_when_the_model_does_not_use_soft_deletes`, proving the shared service itself has no soft/hard-delete opinion baked in — the outcome is 100% determined by which model class is passed in.

---

## 7. Final review readiness

## ✅ Ready for review / merge

- All 13 pre-existing characterization tests pass with **zero assertion changes**, against the fully refactored controllers.
- The 8 new `CategoryCrudService` unit tests pass, independently proving the service's `store`/`update`/`delete` behavior (including the mirror-rollback atomicity and the soft-vs-hard-delete pass-through) without relying on any controller.
- The broader PR-B1/PR-B2/PR-B3 regression suites pass unchanged (23/23), confirming this refactor didn't touch anything outside the Category CRUD surface.
- Questions and Templates controllers were not touched, per this PR's explicit scope — `PR-B4.2`/`PR-B4.3` (or equivalent) remain separate future work.
- No schema changes, no `SoftDeletes` additions, no delete-semantics change — the Customer Admin hard-delete/cascade risk remains exactly as documented in `PR-B4_1_CATEGORY_READINESS.md`, flagged there as its own separate, future product decision, and is **not** resolved or altered by this PR.

**Recommend merging PR-B4.1 as scoped**, with the Customer Admin cascade-delete risk (already flagged to product/ops per the readiness review) tracked as an independent follow-up item, not a blocker on this refactor.

---

## Confirmation

Files created: `app/Services/ChecklistManagement/CategoryCrudService.php`, `tests/Unit/Services/ChecklistManagement/CategoryCrudServiceTest.php`, this document.
Files modified: all 6 Category CRUD controllers (constructor injection + one call-site replacement each; flash/session/redirect/catch bodies otherwise untouched).
Files NOT touched: both Request classes, both models, both views, both route files, the existing characterization test file, anything under Questions/Templates, any migration.

**Stopping after PR-B4.1 as instructed.**
