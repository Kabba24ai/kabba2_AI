# PR-B4.1 — Category CRUD Duplication: Readiness Review

**Date:** 2026-07-11
**Type:** Investigation and baseline-testing only. **No production code was modified.**
**Depends on:** `PHASE2_IMPLEMENTATION_PLAN.md` (PR-B4, split into Categories/Questions/Templates sub-PRs), `PHASE2_DECISION_MATRIX.md` (D5 — "no full model/schema unification, logic-sharing only").

---

## 1. Exact duplicated logic

`RentalReady\Categories\{Store,Update,Delete}Controller` and `CustomerAdmin\Categories\{Store,Update,Delete}Controller` are near-line-for-line identical:

- **StoreController** (both): validate → `DB::beginTransaction()` → create the primary category from `category_name`/`description` → if a mirror checkbox is truthy, create an identical category in the *other* tree → `DB::commit()` → `flash()->success()` + two `session()->flash()` calls (`active_tab`, `active_subtab`) → redirect to the domain's index → on `\Throwable`, `DB::rollBack()` + `report($e)` + `flash()->error()` + `redirect()->back()->withInput()->withErrors(['error' => ...])`.
- **UpdateController** (both): validate → `DB::beginTransaction()` → `Model::where('unique_id', $id)->first()` (not `firstOrFail()`) → `->update([...])` → commit → identical flash/session/redirect pattern → identical catch block.
- **DeleteController** (both): `DB::beginTransaction()` → `Model::where('unique_id', $unique_id)->firstOrFail()` → `->delete()` → commit → identical flash/session/redirect pattern → identical catch block.
- **Requests** (both Store): `category_name` required, `description` nullable, plus one mirror-checkbox field that's `nullable` with no other rule. **No uniqueness rule on `category_name` in either domain.**
- **Requests** (both Update): identical — `category_name` required, `description` nullable.
- **Routes** (both): identical shape — `POST /categories/store`, `PUT /categories/{unique_id}/update`, `DELETE /categories/{unique_id}/delete`, all under a `categories.` name prefix.

This is close to the most duplicated logic found in Phase 2 so far — genuinely copy-pasted, not just structurally similar.

---

## 2. Exact behavioral differences

These are not code-style differences — they are real, currently-shipping behavioral divergences a shared abstraction must either preserve per-domain or deliberately reconcile as its own decision:

1. **Soft-delete vs. hard-delete — the most significant finding.** `RentalReadyChecklistCategory` uses `SoftDeletes`. `CustomerAdminCategory` has `SoftDeletes` **explicitly commented out** (`//use Illuminate\Database\Eloquent\SoftDeletes;` / `//use SoftDeletes;`). Both `rental_ready_checklist_questions.category_id` and `customer_admin_questions.category_id` have `onDelete('cascade')` foreign keys (confirmed in both tables' migrations). Because `RentalReadyChecklistCategory::delete()` is a soft delete, that FK cascade **never fires** (the row is never actually removed — the same class of gap PR-B1 found for `ChecklistMaster`). Because `CustomerAdminCategory::delete()` is a real hard delete, deleting a Customer Admin category **permanently cascade-deletes every `CustomerAdminQuestion` row under it at the database level**, with zero application-level warning, confirmation, or logging. This is a serious, silent data-loss behavior that has nothing to do with code duplication — it is a genuine, undocumented domain divergence.
2. **Mirror-checkbox field name and direction are domain-specific, not shared:** `create_customer_folder` (Rental Ready → Customer Admin) vs. `create_rental_folder` (Customer Admin → Rental Ready). Both are `nullable` request fields checked with `!empty(...) && ... == 1`.
3. **Mirroring only happens at create time, never on update or delete.** Editing a Rental Ready category's name does not rename the Customer Admin category that was mirror-created from it (if any) — they are two independent rows sharing only their initial values. There is no stored link between a category and "the one it was mirrored from"; matching is purely coincidental (same `category_name`/`description` at creation time only). This is a business-rule fact, not a bug, but it means any shared "mirror" logic must not assume the two rows stay in sync after creation.
4. **Redirect/flash response shape differs between the two trees.** All 3 Customer Admin controllers chain `->with('success', '<message>')` onto the redirect **in addition to** the `flash()->success()` call. All 3 Rental Ready controllers use **only** `flash()->success()` — no `->with('success', ...)`. This means the Customer Admin index view can read a `success` session key the Rental Ready index view never receives, for the equivalent action.
5. **`UpdateController` uses `->first()`, not `->firstOrFail()`, in both domains** (unlike `DeleteController`, which uses `firstOrFail()` in both). An update to a nonexistent `unique_id` results in `$category` being `null`, then a fatal `Error: Call to a member function update() on null` — caught by the surrounding `catch (\Throwable $e)`, which rolls back, reports it, and redirects back with a generic error flash. This "works" today only because the catch block is broad enough to catch a `TypeError`/`Error`, not because it was designed as an explicit not-found guard. A shared abstraction must decide whether to preserve this incidental behavior or make the 404 case explicit — see §4.
6. **No uniqueness validation on `category_name` in either domain** — duplicate category names are allowed within the same tree today. Confirmed by reading both `StoreRequest`/`UpdateRequest` pairs; not something to silently add during a refactor without a separate decision, since it would be a new behavior, not a preserved one.

---

## 3. Which logic is safe to share

- The transaction wrapper shape (`DB::beginTransaction()` / `try` / `DB::commit()` / `catch (\Throwable) { DB::rollBack(); report($e); flash()->error(); redirect()->back()->withInput()->withErrors(...) }`) is byte-for-byte identical across all 6 controllers and has no domain-specific behavior in it at all — this is the single best extraction candidate.
- The `session()->flash('active_tab', 'questions')` + `session()->flash('active_subtab', 'categories')` pair is identical across all 6 controllers.
- The "resolve category by `unique_id`, then act" lookup pattern is identical in shape (differs only in which Eloquent model class and whether `first()` vs `firstOrFail()` is used — see §4 on whether to reconcile that inconsistency).
- The `category_name`/`description` validation rule shape (`required`/`nullable`) is identical between the two Store requests and between the two Update requests.

## 4. Which logic must remain domain-specific

- **The soft-delete vs. hard-delete distinction must not be silently unified by a shared trait/service** — that would itself be a real behavior change (either newly soft-deleting Customer Admin categories, which changes what "deleted" means for that tree and stops the cascade from ever firing, or newly hard-deleting Rental Ready categories, which would newly cascade-destroy its questions). This is exactly the kind of "deliberate business-behavior change" the Phase 2 plan requires a stakeholder decision for before touching, not something to fold into a maintainability-only refactor. **Recommend flagging the Customer Admin hard-delete/cascade behavior to product/ops as its own decision, independent of and before PR-B4.1's refactor**, given its real data-loss potential — this is arguably more urgent than the CRUD duplication itself.
- **The mirror field name and direction** (`create_customer_folder` vs. `create_rental_folder`, and which model gets created first vs. mirrored) stay domain-specific — a shared method can accept "which model to mirror into" as a parameter, but the two request field names themselves are presentation-layer facts already baked into two different, unmodified Blade views (`_sub_tab_categories.blade.php` in each tree) and are out of scope to rename.
- **The redirect target route name and the extra `->with('success', ...)` on the Customer Admin side** are index-page-specific and must either be preserved per-domain (a parameter to a shared service) or reconciled as its own small, explicitly-called-out behavior change — not silently dropped or silently added to the other tree.
- **The `->first()` vs. `->firstOrFail()` inconsistency in `UpdateController`** should be resolved explicitly (both to `firstOrFail()`, matching `DeleteController`'s existing pattern) rather than preserved as "whatever accidentally works today via the broad catch block" — this is a small, low-risk, explicit improvement worth calling out as an intentional decision in the eventual PR-B4.1 implementation, not something to leave ambiguous.

## 5. Proposed shared trait/service design (for the next stage — not built yet)

Not implemented in this stage; sketched here only to frame what the characterization tests below need to survive:

- A `CategoryCrudService` (or similarly named class in `App\Services\ChecklistManagement`, matching PR-B1's/PR-B2's naming convention) with methods shaped roughly like:
  - `store(string $modelClass, array $attributes, ?string $mirrorModelClass, bool $shouldMirror): Model` — wraps the transaction, creates the primary row, conditionally creates the mirror row, returns the created model. Domain-specific route/flash/redirect logic stays in each controller, which calls this service and then handles its own response.
  - `update(string $modelClass, string $uniqueId, array $attributes): Model` — wraps the transaction and the (recommended, explicit) `firstOrFail()` lookup + update.
  - `delete(string $modelClass, string $uniqueId): void` — wraps the transaction and the `firstOrFail()` + delete. Deliberately does **not** attempt to unify soft vs. hard delete — it simply calls `$model->delete()` and lets each model's own `SoftDeletes` trait (or lack thereof) determine the actual behavior, exactly as today.
- Each of the 6 controllers keeps its own `__invoke()`, its own Request class, and its own flash/redirect/session-key choices — only the transaction-wrapped CRUD mechanics move into the shared service. This matches the "logic-sharing only, not full model unification" instruction from D5.

## 6. Risks

- **Highest risk, independent of this refactor:** the Customer Admin hard-delete cascade (§2.1) is a live, shipping data-loss risk today. Any characterization test that exercises delete on a category with real question children will need to prove this concretely — see §7 below — since it's the single most important thing to lock down before anyone touches these controllers.
- **Medium risk:** if a shared service's `store()` silently assumes both trees mirror identically, a future change to one tree's mirror direction/field could break the other tree without anyone noticing — mitigated by keeping the mirror-checkbox interpretation in each controller, not the shared service.
- **Medium risk:** reconciling `->first()` to `->firstOrFail()` in `UpdateController` changes the *shape* of the failure (a `ModelNotFoundException` instead of a raw `Error`) but, since both are `\Throwable` and both are already caught by the same broad catch block, should be response-shape-preserving — this must be proven by a characterization test before the refactor, not assumed.
- **Low risk:** the transaction-wrapper extraction itself is the safest part of this whole PR — it's identical, side-effect-free boilerplate in all 6 places today.

## 7. Required characterization tests (delivered in this stage)

1. Rental Ready category store — basic create, no mirror.
2. Rental Ready category store — mirror checkbox checked, creates a matching Customer Admin category.
3. Customer Admin category store — basic create, no mirror.
4. Customer Admin category store — mirror checkbox checked, creates a matching Rental Ready category.
5. Rental Ready category update — happy path.
6. Customer Admin category update — happy path.
7. Rental Ready category update — nonexistent `unique_id` triggers the catch block (rollback + error flash + redirect back), proving today's incidental `->first()`-returns-null behavior.
8. Customer Admin category update — same, for symmetry.
9. Rental Ready category delete — **soft** delete: row still exists in the table with `deleted_at` set; a child question row is **not** cascade-deleted (soft delete never fires the FK).
10. Customer Admin category delete — **hard** delete: row is completely gone from the table; a child question row **is** cascade-deleted at the DB level. This is the test that concretely proves the §2.1 finding rather than just describing it.
11. Validation — missing `category_name` rejected on both Store requests.
12. Cross-tree transactional atomicity — forcing the mirror-model's `creating` event to throw proves the **primary** category creation is rolled back too (both rows absent), confirming the single `DB::beginTransaction()` genuinely covers both writes, not just the first one.

All are implemented (as 13 test methods — the update-happy-path and store-mirror scenarios split into 2 tests per domain) in `tests/Feature/ChecklistManagement/Categories/CategoryCrudCharacterizationTest.php` — see §"Commands run"/"Pass/fail counts" below for the actual run against the current, unmodified controllers.

---

## 8. Exact files expected to change (in the eventual refactor PR — none of these are touched in this stage)

- `app/Services/ChecklistManagement/CategoryCrudService.php` *(new)*
- `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Categories/StoreController.php`
- `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Categories/UpdateController.php`
- `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Categories/DeleteController.php`
- `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Categories/StoreController.php`
- `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Categories/UpdateController.php`
- `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Categories/DeleteController.php`

**Not expected to change:** both `Categories` Request classes (validation rules stay per-domain, no new uniqueness rule without a separate decision), both category models (soft-delete behavior stays exactly as-is per §4), both `_sub_tab_categories.blade.php` views, both route files.

---

## 9. Readiness verdict

## ⚠ Ready with prerequisites

**Why not ✅ Ready to implement:** the Customer Admin hard-delete cascade (§2.1) is a real, live risk that this investigation surfaced but that is **out of scope for a "reduce duplication, no behavior change" refactor** to silently fix or paper over. Before PR-B4.1's actual refactor lands, product/ops should be made aware that deleting a Customer Admin category today permanently destroys its questions with cascade, no confirmation — independent of whether the CRUD code gets consolidated. Refactoring the controllers without flagging this first risks the team assuming a shared service "fixed" it when it deliberately didn't (and shouldn't, without a separate decision).

**Why not ❌ Not ready:** no blocking technical unknowns were found. The duplication is real and extensive, the safe-to-share/must-stay-domain-specific boundary is clear (§3/§4), and a baseline characterization suite now exists and passes against the current, unmodified controllers (§"Pass/fail counts"), including the one test that would have caught the soft/hard-delete asymmetry immediately if it were ever accidentally unified.

**Prerequisite before the refactor PR:** flag the Customer Admin category hard-delete/cascade behavior to product/ops as its own, separate item — it is not blocking the *refactor* (the refactor must preserve it exactly, per §4), but it is worth surfacing given its severity, independent of PR-B4's timeline.

---

## Exact files inspected

- `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Categories/{Store,Update,Delete}Controller.php`
- `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Categories/{Store,Update,Delete}Controller.php`
- `app/Http/Requests/Admin/ChecklistManagement/RentalReady/Categories/{Store,Update}Request.php`
- `app/Http/Requests/Admin/ChecklistManagement/CustomerAdmin/Categories/{Store,Update}Request.php`
- `routes/admin/checklist_management/rental_ready/categories/routes.php`
- `routes/admin/checklist_management/customer_admin/categories/routes.php`
- `routes/admin/checklist_management/rental_ready/routes.php`, `routes/admin/checklist_management/customer_admin/routes.php` (name-prefix confirmation)
- `app/Models/ChecklistManagement/RentalReady/RentalReadyChecklistCategory.php`
- `app/Models/ChecklistManagement/CustomerAdmin/CustomerAdminCategory.php`
- `app/Models/ChecklistManagement/CustomerAdmin/CustomerAdminQuestion.php` (fillable fields, for test fixtures)
- `database/migrations/checklist_mangement/rental_ready/2025_08_19_122148_create_rental_ready_checklist_questions_table.php`
- `database/migrations/checklist_mangement/customer_admin/2025_08_27_001953_customer_admin_questions.php`
- `resources/views/admin/checklist_management/rental_ready/partials/_sub_tab_categories.blade.php`
- `resources/views/admin/checklist_management/customer_admin/partials/_sub_tab_categories.blade.php`

## Exact commands run

```
Glob: **/RentalReady/Categories/*.php, **/CustomerAdmin/Categories/*.php
Grep: create_customer_folder|create_rental_folder — resources/views, then per-file content
Grep: category_id — database/migrations (to locate both questions-table migrations)
Grep: RentalReadyChecklistCategory|CustomerAdminCategory — tests/ (confirm no existing dedicated CRUD tests)

php -l tests/Feature/ChecklistManagement/Categories/CategoryCrudCharacterizationTest.php

php artisan test --env=testing tests/Feature/ChecklistManagement/Categories/CategoryCrudCharacterizationTest.php
```

## Pass/fail counts

```
php artisan test --env=testing tests/Feature/ChecklistManagement/Categories/CategoryCrudCharacterizationTest.php
→ 13 passed (54 assertions)
```

Run against the current, completely unmodified controllers/models/routes.

## Confirmation

**NO PRODUCTION CODE WAS MODIFIED.** Only this document and the new characterization test file were added. All findings were obtained by reading the current state of the listed files directly, and all test assertions run against the unmodified controllers.
