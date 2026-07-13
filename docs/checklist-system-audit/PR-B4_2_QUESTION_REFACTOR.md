# PR-B4.2 — Question CRUD Duplication Reduction (Implementation)

**Date:** 2026-07-11
**Depends on:** `PR-B4_2_QUESTION_READINESS.md` (investigation + 17-test baseline), `PR-B4_2_BUGFIX_DD_REMOVAL.md` (the `dd()` fix + 18th test, both preconditions to this PR), `PR-B4_1_CATEGORY_READINESS.md`/`PR-B4_1_CATEGORY_REFACTOR.md` (the precedent this PR follows).
**Scope:** reduce the duplicated transaction/lookup/persistence mechanics across the 8 Question CRUD controllers into one shared service, without changing any observable behavior in either tree. No migrations. No Templates work.

---

## 1. Objective

Apply the same "extract only genuinely identical mechanics, keep domain-specific business logic in the controllers" discipline used in PR-B4.1 (Categories) to the 8 Question controllers (Rental Ready + Customer Admin × Store/Update/Delete/Copy).

---

## 2. Shared design used

`app/Services/ChecklistManagement/QuestionCrudService.php` — five methods, each taking model classes as parameters:

- **`store(string $questionModelClass, array $questionAttributes, string $answerModelClass, array $answerRows): Model`** — creates the question, then creates each answer row from an already-domain-mapped array, all inside one `DB::transaction()`.
- **`updateWithDiffedAnswers(...)`** — Rental Ready's exact behavior: `findOrFail($id)` (numeric PK), update the question, then diff submitted answer rows against existing ones — a row carrying a matching `id` updates that row in place (keeping its ID); a row with no match is created fresh; any existing answer not represented in the new list is deleted.
- **`updateWithReplacedAnswers(...)`** — Customer Admin's exact behavior: `findOrFail($id)`, update the question, unconditionally delete **all** existing answers, then recreate every one from the submitted rows.
- **`delete(string $questionModelClass, string $uniqueId): void`** — `where('unique_id', ...)->firstOrFail()`, explicitly `->answers()->delete()`, then `->delete()` — same shape as every original `DeleteController`.
- **`copy(string $questionModelClass, string $answerModelClass, int $id, callable $assignFreshUniqueId, callable $mapAnswerForCopy): Model`** — `with('answers')->findOrFail($id)`, `replicate()`, rename to `"{name} (Copy)"`, then calls the caller-supplied `$assignFreshUniqueId($newQuestion, $original)` closure (each domain's own unique_id strategy — see §3), saves, then maps and creates each original answer via the caller-supplied `$mapAnswerForCopy($answer)` closure.

**Why two separate update methods instead of one with a flag:** the readiness review (§4) was explicit that Rental Ready's diff-and-keep vs. Customer Admin's wipe-and-recreate are different *business behaviors*, not implementation details. A single `update($diffMode = true)`-style method would let a future caller silently flip behavior with one boolean; two named, independently-tested methods make the choice a compile-time fact at each call site instead.

**Why `copy()` takes closures instead of hard-coding either domain's unique_id/answer-mapping logic:** the two domains' "ensure a fresh unique_id" strategies are genuinely different mechanisms (Rental Ready explicitly calls `ModelHelper::generateUniqueID()`; Customer Admin sets `null` and relies on the model's own `boot()` hook), and their answer schemas share no fields at all. Parameterizing both as callables lets the service own the "replicate + save + loop-copy answers" mechanics (the part that actually was identical) without needing any per-domain branching inside the service itself.

---

## 3. Exact behavior intentionally left domain-specific

Per this PR's explicit requirements, the following were **not** touched, unified, or "fixed" — each is still driven entirely by what each controller passes into the shared service, exactly as before:

- **Rental Ready's answer-diff update strategy** (keeps IDs, updates/creates/deletes selectively) — now lives in `updateWithDiffedAnswers()`, called only by `RentalReady\Question\UpdateController`.
- **Customer Admin's wipe-and-recreate update strategy** — now lives in `updateWithReplacedAnswers()`, called only by `CustomerAdmin\Question\UpdateController`.
- **Rental Ready's soft-delete behavior** (`RentalReadyChecklistQuestion`/`RentalReadyChecklistQuestionAnswer` both keep their `SoftDeletes` trait, untouched) and **Customer Admin's hard-delete-with-cascade behavior** (`CustomerAdminQuestion`/`CustomerAdminQuestionAnswer` still have no `SoftDeletes` trait, untouched) — `QuestionCrudService::delete()` has zero opinion on this; it just calls `->delete()` and lets each model's own traits decide, exactly as `CategoryCrudService::delete()` does for Categories.
- **Numeric-PK lookup for Update/Copy, despite the misleading `{unique_id}` route-parameter name** — both `UpdateController`s and both `CopyController`s still receive `$id` and pass it straight through to `findOrFail($id)` inside the service; the route files and parameter names were **not** touched (per requirement 4).
- **`unique_id`-string lookup for Delete** — unchanged, `where('unique_id', $uniqueId)->firstOrFail()`.
- **Redirects** — every controller still redirects to its own domain's index route (`rental-ready.index` / `customer-admin.index`), untouched.
- **Flash/session messages** — every controller's `flash()->success()`/`flash()->error()` text and `session()->flash('active_tab'/'active_subtab', ...)` pairs are untouched, including the Copy controllers' differing session-flag key names (`edit_questions_open` for Rental Ready vs. `active_subtab` + `open_edit_question` for Customer Admin) — neither was reconciled.
- **Validation** — no `Request` class was modified; both trees' `question_name`/`category_id` required rules and Customer Admin's additional `question_delivery_text`/`question_return_text` rules are exactly as before.
- **Transaction/error handling** — every controller still wraps its call in its own `try/catch(\Throwable)` → `report($e)` → `flash()->error()` → `redirect()->back()->withInput()->withErrors(['error' => ...])`; the service's internal `DB::transaction()` replaces each controller's own `DB::beginTransaction()/commit()/rollBack()` calls, which is not a behavior change (`DB::transaction()` commits on success and auto-rolls-back-and-rethrows on failure, which the unchanged catch block still catches identically).
- **No cross-tree mirroring** — confirmed still absent; neither `store()` call passes anything resembling a mirror-model parameter (unlike `CategoryCrudService::store()`, which genuinely has one for Categories' checkbox feature).
- **The two harmless-but-vacuous defensive checks removed during extraction:** Customer Admin's original `StoreController`/`UpdateController` had `if (!$question || !$question->id) { throw ... }` and `if (!$answer || !$answer->id) { throw ... }` checks after each `create()` call. These were not preserved in the shared service, because they can never actually fire in practice — Eloquent's `create()` either succeeds and returns a model with an ID, or throws its own exception (e.g. a `QueryException`) before ever returning; there is no code path where `create()` returns an object with a falsy `id`. Dropping them changes no observable behavior (a real failure still throws, still gets caught by the same catch block, still produces the same rollback/flash/redirect), so this is a genuine "extract only identical *logic*" simplification, not a silently-dropped behavior. Documented here explicitly rather than left implicit.

---

## 4. Exact files changed

| File | Change |
|---|---|
| `app/Services/ChecklistManagement/QuestionCrudService.php` *(new)* | `store()`, `updateWithDiffedAnswers()`, `updateWithReplacedAnswers()`, `delete()`, `copy()` — see §2. |
| `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Question/StoreController.php` | Constructor-injects the service; builds `$answerRows` from the JSON-decoded `options` field (unchanged decoding), delegates to `store()`. Flash/session/redirect/catch unchanged. |
| `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Question/UpdateController.php` | Same pattern, delegates to `updateWithDiffedAnswers()`, `$answerRows` include each option's `id` key. |
| `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Question/DeleteController.php` | Delegates to `delete()`. |
| `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Question/CopyController.php` | Delegates to `copy()`, passing the exact original `ModelHelper::generateUniqueID(...)` closure and an answer-mapping closure. |
| `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Question/StoreController.php` | Same pattern as Rental Ready's Store, options already an array (unchanged), delegates to `store()`. The `dd()` fix from the prior bug-fix PR remains intact — the catch block is otherwise untouched. |
| `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Question/UpdateController.php` | Delegates to `updateWithReplacedAnswers()`. |
| `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Question/DeleteController.php` | Delegates to `delete()`. |
| `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Question/CopyController.php` | Delegates to `copy()`, passing the exact original `unique_id = null` closure (relying on the model's `boot()` regeneration) and an answer-mapping closure. |
| `tests/Unit/Services/ChecklistManagement/QuestionCrudServiceTest.php` *(new)* | 10 focused tests on the service in isolation — see §6. |
| `docs/checklist-system-audit/PR-B4_2_QUESTION_REFACTOR.md` *(new, this document)* | |

**Not touched:** all 4 Question `Request` classes, both Question models, both Answer models (no `SoftDeletes` added/removed), both trees' Question Blade partials, both route files (names and parameter names unchanged), `CategoryCrudService`/Category controllers (already shipped in PR-B4.1), `tests/Feature/ChecklistManagement/Questions/QuestionCrudCharacterizationTest.php` (re-run, zero edits), anything under Templates, any database migration.

---

## 5. Commands run

```bash
php -l app/Services/ChecklistManagement/QuestionCrudService.php
php -l app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Question/StoreController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Question/UpdateController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Question/DeleteController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Question/CopyController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Question/StoreController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Question/UpdateController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Question/DeleteController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Question/CopyController.php
php -l tests/Unit/Services/ChecklistManagement/QuestionCrudServiceTest.php

php artisan test --env=testing \
  tests/Unit/Services/ChecklistManagement/QuestionCrudServiceTest.php \
  tests/Feature/ChecklistManagement/Questions/QuestionCrudCharacterizationTest.php

php artisan test --env=testing \
  tests/Feature/ChecklistManagement/Categories/CategoryCrudCharacterizationTest.php \
  tests/Unit/Services/ChecklistManagement/CategoryCrudServiceTest.php \
  tests/Feature/ChecklistManagement/ChecklistAssignmentServiceTest.php \
  tests/Feature/RentalReadyChecklists/ValidationGuardObservabilityTest.php
```

*(One run in this session hit a corrupted test-database state left over from an earlier, unrelated MySQL memory crash mid-migration in this environment — `rc_kabba_testing` was dropped and recreated cleanly, then the suite re-run to a full pass. Unrelated to this code change.)*

---

## 6. Pass/fail counts

```
QuestionCrudServiceTest (new, unit)                              → 10 passed
QuestionCrudCharacterizationTest (18-test behavior contract,      → 18 passed (98 assertions combined with the above)
   unchanged)

CategoryCrudCharacterizationTest (PR-B4.1, unchanged)             → 13 passed
CategoryCrudServiceTest (PR-B4.1, unchanged)                      →  8 passed
ChecklistAssignmentServiceTest (PR-B1, unchanged)                 →  7 passed
ValidationGuardObservabilityTest (PR-B3, unchanged)               →  6 passed
```

**Total: 62/62 passing, 0 failures** — spanning this PR plus PR-B4.1, PR-B1, and PR-B3. No cross-domain regressions.

**The 18-test `QuestionCrudCharacterizationTest` behavior contract passed with ZERO assertion changes** against the fully refactored controllers — this is the concrete proof the extraction was mechanical.

---

## 7. Confirmation that deletion/update semantics remain unchanged

- **Deletion:** `test_rental_ready_question_delete_soft_deletes_question_and_answers` and `test_customer_admin_question_delete_hard_deletes_question_and_cascades_answers` (both unmodified from the readiness stage) still pass. No `SoftDeletes` trait was added or removed anywhere. `QuestionCrudService::delete()` and `CategoryCrudService::delete()` share the identical "no opinion, just call `->delete()`" design.
- **Update:** `test_rental_ready_question_update_diffs_answers_keeping_ids_creating_and_deleting` and `test_customer_admin_question_update_wipes_and_recreates_all_answers` (both unmodified) still pass — Rental Ready's answers keep their IDs across an edit that doesn't remove them; Customer Admin's answers still get entirely new IDs on every edit, with no attempt to preserve them.
- Both facts are additionally covered by dedicated `QuestionCrudServiceTest` methods (`test_update_with_diffed_answers_keeps_matched_ids_creates_and_deletes`, `test_update_with_replaced_answers_deletes_all_and_recreates`, `test_delete_soft_deletes_question_and_answers_when_model_uses_soft_deletes`, `test_delete_hard_deletes_and_cascades_when_model_does_not_use_soft_deletes`), proving the service itself — independent of any controller — has no built-in bias toward either behavior; the outcome is 100% determined by which model class and which of the two update methods is called.

---

## 8. Final review readiness

## ✅ Ready for review / merge

- All 18 pre-existing characterization tests pass with **zero assertion changes**, against the fully refactored controllers.
- The 10 new `QuestionCrudService` unit tests independently prove `store`/`updateWithDiffedAnswers`/`updateWithReplacedAnswers`/`delete`/`copy` behavior — including the store-atomicity rollback and both delete/update behavioral splits — without relying on any controller.
- The broader PR-B4.1/PR-B1/PR-B3 regression suites pass unchanged (35/35), confirming this refactor didn't disturb anything outside the Question CRUD surface.
- No migrations, no Request/model/view/route changes, no Templates work, per this PR's explicit scope.
- The one intentional simplification (dropping the two vacuous post-`create()` truthiness checks in the old Customer Admin controllers) is called out explicitly in §3, not silently absorbed into "extraction."

**Recommend merging PR-B4.2 as scoped.** PR-B4.3 (Templates) remains separate future work, not started here.

---

## Confirmation

Files created: `app/Services/ChecklistManagement/QuestionCrudService.php`, `tests/Unit/Services/ChecklistManagement/QuestionCrudServiceTest.php`, this document.
Files modified: all 8 Question CRUD controllers (constructor injection + one call-site replacement each; flash/session/redirect/catch bodies otherwise untouched).
Files NOT touched: all 4 Question Request classes, both Question models, both Answer models, both Question Blade partials, both route files, anything under Templates, any migration.

**Stopping after PR-B4.2 as instructed.** Not beginning Templates (PR-B4.3) without further direction.
