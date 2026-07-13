# PR-B4.3 — Template CRUD Duplication Reduction (Implementation)

**Date:** 2026-07-11
**Depends on:** `PR-B4_3_TEMPLATE_READINESS.md` (investigation + 17-test baseline), `PR-B4_1_CATEGORY_REFACTOR.md`/`PR-B4_2_QUESTION_REFACTOR.md` (the two precedents this PR follows and completes — PR-B4.3 is the final of the three CRUD-duplication sub-PRs).
**Scope:** reduce the duplicated transaction/lookup/persistence mechanics across the 8 Template CRUD controllers into one shared service, without changing any observable behavior in either tree. No schema changes. No Phase 2 release notes. No Phase 3 work.

---

## 1. Architecture

`app/Services/ChecklistManagement/TemplateCrudService.php` — four methods, each taking model classes as parameters, following the exact same design convention as `CategoryCrudService` (PR-B4.1) and `QuestionCrudService` (PR-B4.2):

- **`store(string $templateModelClass, array $templateAttributes, string $templateQuestionModelClass, array $questionRows): Model`** — creates the template, then creates each `TemplateQuestion` row from an already-domain-mapped array, all inside one `DB::transaction()`.
- **`updateWithReplacedQuestions(...)`** — **one** method, not two. Unlike `QuestionCrudService`, which needed `updateWithDiffedAnswers()` and `updateWithReplacedAnswers()` because Rental Ready and Customer Admin Questions genuinely diverge, Templates' update strategy is **identical in both trees**: `firstOrFail()` by `unique_id`, update the template, unconditionally delete all existing `TemplateQuestion` rows, recreate every one from the submitted rows. This was confirmed in the readiness review (§2) and is the one respect in which Templates' shared service is simpler than Questions'.
- **`delete(string $templateModelClass, string $uniqueId): void`** — `where('unique_id', ...)->firstOrFail()`, explicit `->questions()->delete()`, then `->delete()` — same shape as every original `DeleteController`.
- **`copy(string $templateModelClass, string $templateQuestionModelClass, string $uniqueId, string $uniqueIdPrefix, callable $mapQuestionForCopy): Model`** — `firstOrFail()` by `unique_id`, `replicate()`, rename to `"{name} (Copy)"`, regenerate `unique_id` via `ModelHelper::generateUniqueID()` with a caller-supplied prefix, save, then loop the original's **ordered** `templateQuestions` (the `orderBy('index_number')` relation, not the unordered `questions()` one) through a caller-supplied mapping closure.

**Why `copy()`'s unique_id prefix is a parameter, not hard-coded:** both original `CopyController`s happen to call `ModelHelper::generateUniqueID(new {Model}, 'TQS')` — the **same** literal string `'TQS'` for both trees. This is itself a pre-existing quirk worth flagging (see §3) rather than something to silently "normalize" — each controller still passes its own literal prefix string to the service, exactly matching what it always passed directly to `ModelHelper::generateUniqueID()`.

---

## 2. Files changed

| File | Change |
|---|---|
| `app/Services/ChecklistManagement/TemplateCrudService.php` *(new)* | `store()`, `updateWithReplacedQuestions()`, `delete()`, `copy()` — see §1. |
| `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Templates/StoreController.php` | Constructor-injects the service; delegates to `store()`. |
| `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Templates/UpdateController.php` | Delegates to `updateWithReplacedQuestions()`. |
| `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Templates/DeleteController.php` | Delegates to `delete()`. |
| `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Templates/CopyController.php` | Delegates to `copy()`, passing prefix `'TQS'` and a Rental-Ready-shaped answer/question mapping closure. |
| `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Templates/StoreController.php` | Same pattern. |
| `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Templates/UpdateController.php` | Same pattern. |
| `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Templates/DeleteController.php` | Same pattern. |
| `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Templates/CopyController.php` | Delegates to `copy()`, passing prefix `'TQS'` — **preserving**, not fixing, the pre-existing quirk that this doesn't match `CustomerAdminTemplate`'s own creation-time prefix (`'CATQS'`); see §3. |
| `tests/Unit/Services/ChecklistManagement/TemplateCrudServiceTest.php` *(new)* | 11 focused tests on the service — see §5. |
| `docs/checklist-system-audit/PR-B4_3_TEMPLATE_REFACTOR.md` *(new, this document)* | |

**Not touched:** both `IndexController`s (no logic to extract — confirmed in the readiness review), all 4 Template `Request` classes, both `RentalReadyChecklistTemplate(Question)` and `CustomerAdminTemplate(Question)` models (no `SoftDeletes` added/removed, no schema/fillable changes), both trees' Templates Blade partials, both route files, `CategoryCrudService`/`QuestionCrudService` and their controllers (already shipped), `tests/Feature/ChecklistManagement/Templates/TemplateCrudCharacterizationTest.php` (re-run, zero edits), any database migration.

---

## 3. Intentionally preserved behavioral differences

Per this PR's explicit requirements, none of the following were touched, unified, or "fixed":

- **Rental Ready soft-delete vs. Customer Admin hard-delete-with-cascade** — `TemplateCrudService::delete()` has zero opinion on this; it calls `->delete()` and lets each model's own `SoftDeletes` trait (or absence of it) decide. Proven unchanged by both the unmodified characterization tests and new dedicated service tests.
- **`equipment_category_id`'s FK asymmetry** — Rental Ready has a real `foreignId(...)->nullOnDelete()` constraint (added by a dedicated later migration); Customer Admin has none (still the original plain `string` column). The service has no schema-level control over this either way — MySQL itself enforces or doesn't. `store()` and `updateWithReplacedQuestions()` simply attempt the insert/update; a nonexistent category throws for Rental Ready and doesn't for Customer Admin, exactly as before. Proven unchanged by both the characterization suite's split test pair and the new service-level `test_store_enforces_the_equipment_category_foreign_key_for_rental_ready_only` / `test_store_does_not_enforce_the_equipment_category_foreign_key_for_customer_admin`.
- **The dead `'required'` field** — `UpdateController`s still build a `'required' => $q['required'] ?? false` key into each question row and pass it to the service exactly as before; `store()`'s rows still never include it (the original asymmetry between Store and Update is preserved). Neither `TemplateQuestion` model has `'required'` in `$fillable`, so it's still silently dropped by Eloquent's mass-assignment protection — dead code, preserved as dead code, not wired up and not removed.
- **`unique_id` lookup for Update/Delete/Copy in both trees** — all three service methods that need to find an existing template do so via `where('unique_id', $uniqueId)->firstOrFail()`, identical to every original controller. No route or route-parameter name was touched.
- **Ordering behavior** — `store()` and `updateWithReplacedQuestions()` receive `$questionRows` already carrying `index_number` computed by each controller from the submitted list's order; `copy()` iterates the **ordered** `templateQuestions` relation (not the unordered `questions()` one), exactly as the original `CopyController`s did.
- **Redirects, flash text, and session-flash keys** — every controller still redirects to its own domain's index route, uses its own exact flash text, and sets the same session keys (including the Copy controllers' `open_edit_template` key and the Store/Update controllers' `active_tab` key — no `->with('success', ...)` chain in either tree, matching the Question pattern from PR-B4.2, not the Category pattern from PR-B4.1).
- **A newly-discovered, pre-existing quirk, explicitly preserved, not fixed:** both `CopyController`s call `ModelHelper::generateUniqueID(..., 'TQS')` — the **same** prefix, regardless of tree. This matches `RentalReadyChecklistTemplate`'s own creation-time prefix (`'TQS'`, set in its `boot()`), but **does not** match `CustomerAdminTemplate`'s own creation-time prefix (`'CATQS'`). This means a Customer Admin template created via Store gets a `unique_id` like `CATQS-XXXX`, while the same template copied via the Copy button gets a `unique_id` like `TQS-XXXX` — an inconsistency in the *original* code, now made explicit in both the service's docblock and the Customer Admin `CopyController`'s own comment, and passed through by each controller exactly as it always called it. Not part of this PR's authorized scope to fix (would be a `unique_id`-format behavior change), and this PR was explicitly told not to change `unique_id` behavior.

---

## 4. Transaction design

Each controller's own `DB::beginTransaction()`/`DB::commit()`/`DB::rollBack()` calls were removed — the service's internal `DB::transaction()` closure now owns the single transaction per request, identical in principle to `CategoryCrudService`/`QuestionCrudService`. This is not a behavior change: `DB::transaction()` commits on success and automatically rolls back and re-throws on any exception, which each controller's unchanged `catch (\Throwable $e)` block still catches exactly as it did the manually-rolled-back exception before. Store's template+question-rows creation, Update's template-update+wipe+recreate, and Copy's replicate+save+question-loop are each single atomic units inside their respective service methods — confirmed by the passing `test_store_rolls_back_the_template_when_a_question_row_creation_fails` (service-level, both trees) and its controller-level counterparts, both forcing a mid-loop failure and asserting zero rows persisted.

---

## 5. A regression found and fixed during this refactor

The first implementation pass built each controller's `$questionRows` array using `collect($questions)->values()->map(...)->all()`. This introduced a real, unintended behavior change: Laravel's `collect(null)` silently returns an **empty** collection, whereas the original code's raw `foreach ($questions as $index => $q)` over a `null` value (from `json_decode()` failing on invalid JSON — the `questions` request field is only validated as `'required'`, with no `'json'`/`'array'` type rule, in any of the 4 Request classes) triggers a PHP warning that this application's exception handler converts into a catchable error. The characterization suite's `test_rental_ready_template_update_with_invalid_json_questions_rolls_back_and_flashes_error` test — carried over unmodified from the readiness stage — caught this immediately: the refactored code redirected successfully instead of rolling back and flashing an error.

**Fix:** all 4 Store/Update controllers (both trees) now build `$questionRows` with a plain `foreach` loop instead of `collect()->map()`, preserving the exact original null-unsafe iteration mechanic. Re-run confirmed the test passes again, unmodified, against the corrected code. This is called out explicitly rather than silently fixed and forgotten, since it's a concrete example of why the characterization-test-first discipline (mandated across all of PR-B4.1/B4.2/B4.3) matters — a plausible-looking "equivalent" refactor introduced a real, if narrow, behavior change that only a live test run surfaced.

---

## 6. Commands run

```bash
php -l app/Services/ChecklistManagement/TemplateCrudService.php
php -l app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Templates/StoreController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Templates/UpdateController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Templates/DeleteController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Templates/CopyController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Templates/StoreController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Templates/UpdateController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Templates/DeleteController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Templates/CopyController.php
php -l tests/Unit/Services/ChecklistManagement/TemplateCrudServiceTest.php

php artisan test --env=testing \
  tests/Unit/Services/ChecklistManagement/TemplateCrudServiceTest.php \
  tests/Feature/ChecklistManagement/Templates/TemplateCrudCharacterizationTest.php

php artisan test --env=testing \
  tests/Feature/ChecklistManagement/Categories/CategoryCrudCharacterizationTest.php \
  tests/Feature/ChecklistManagement/Questions/QuestionCrudCharacterizationTest.php \
  tests/Feature/ChecklistManagement/ChecklistAssignmentServiceTest.php \
  tests/Feature/RentalReadyChecklists/ValidationGuardObservabilityTest.php
```

---

## 7. Testing summary / pass-fail counts

```
TemplateCrudServiceTest (new, unit)                            → 11 passed
TemplateCrudCharacterizationTest (17-test behavior contract,    → 17 passed
   unchanged)

CategoryCrudCharacterizationTest (PR-B4.1, unchanged)           → 13 passed
QuestionCrudCharacterizationTest (PR-B4.2, unchanged)           → 18 passed
ChecklistAssignmentServiceTest (PR-B1, unchanged)                →  8 passed
ValidationGuardObservabilityTest (PR-B3, unchanged)              →  6 passed
```

**Total: 73/73 passing, 0 failures** — spanning this PR plus PR-B4.1, PR-B4.2, PR-B1, and PR-B3. No cross-domain regressions anywhere in the checklist-system.

**The 17-test `TemplateCrudCharacterizationTest` behavior contract passed with ZERO assertion changes** against the fully refactored controllers, on the second run (the first run correctly caught the `collect(null)` regression described in §5, which was a defect in the refactor, not in the test).

---

## 8. Confirmations

- **Soft vs. hard delete remains unchanged:** `test_rental_ready_template_delete_soft_deletes_template_and_questions` and `test_customer_admin_template_delete_hard_deletes_template_and_cascades_questions` (both unmodified from the readiness stage) still pass, plus the new `TemplateCrudServiceTest::test_delete_soft_deletes_...`/`test_delete_hard_deletes_and_cascades_...` independently confirm the service itself has no soft/hard-delete bias.
- **`equipment_category_id` FK behavior remains unchanged:** `test_rental_ready_template_store_rejects_a_nonexistent_equipment_category_id` and `test_customer_admin_template_store_accepts_a_nonexistent_equipment_category_id` (both unmodified) still pass, plus the new service-level equivalents.
- **Template update semantics remain unchanged:** both trees' update tests (`..._wipes_and_recreates_questions_preserving_order`, unmodified) still pass — every existing `TemplateQuestion` row is wiped and recreated on every edit, in both trees, with ordering following the newly submitted list.
- **`unique_id` behavior remains unchanged:** Update/Delete/Copy still resolve templates via `where('unique_id', ...)->firstOrFail()` in the service, matching every original controller; no route or route-parameter name was touched; the pre-existing Copy-prefix quirk (§3) is preserved exactly, not fixed.

---

## 9. Readiness for review

## ✅ Ready for review / merge

- All 17 pre-existing characterization tests pass with **zero assertion changes**, against the fully refactored controllers (after the one regression found and fixed during this PR — see §5).
- The 11 new `TemplateCrudService` unit tests independently prove `store`/`updateWithReplacedQuestions`/`delete`/`copy` behavior — including the store-atomicity rollback, the equipment-category FK divergence, and the soft/hard-delete pass-through — without relying on any controller.
- The broader PR-B4.1/PR-B4.2/PR-B1/PR-B3 regression suites pass unchanged (45/45), confirming this refactor didn't touch anything outside the Template CRUD surface.
- No schema changes, no `SoftDeletes` additions/removals, no FK changes, no validation-rule changes, no route renames, no `unique_id`-behavior changes, no Blade/view edits, no Request-class edits, per this PR's explicit scope.
- This closes out PR-B4 (all three sub-PRs — Categories, Questions, Templates — now shipped). Phase 2 release notes and Phase 3 remain explicitly out of scope for this PR, per instruction.

**Recommend merging PR-B4.3 as scoped.**

---

## Confirmation

Files created: `app/Services/ChecklistManagement/TemplateCrudService.php`, `tests/Unit/Services/ChecklistManagement/TemplateCrudServiceTest.php`, this document.
Files modified: all 8 Template CRUD controllers (constructor injection + one call-site replacement each; flash/session/redirect/catch bodies otherwise untouched; the 4 Store/Update controllers additionally corrected from `collect()->map()` to a plain `foreach` per §5).
Files NOT touched: both IndexControllers, all 4 Request classes, both models per tree (no schema/trait changes), both Blade partials, both route files, any migration.

**Stopping after PR-B4.3 as instructed.** Not writing Phase 2 release notes, not beginning Phase 3.
