# PR-B4.2 — Question CRUD Duplication: Readiness Review

**Date:** 2026-07-11
**Type:** Investigation and baseline-testing only. **No production code was modified.**
**Depends on:** `PR-B4_1_CATEGORY_READINESS.md`/`PR-B4_1_CATEGORY_REFACTOR.md` (the Category precedent this PR mirrors and extends), `PHASE2_IMPLEMENTATION_PLAN.md` (PR-B4, split into Categories/Questions/Templates).

---

## 0. Headline finding — read this first

**`CustomerAdmin\Question\StoreController`'s catch block contains a live `dd($e->getMessage(), $e->getTraceAsString());` call (line 76).** `dd()` dumps and then calls `exit()` — it does not return a normal HTTP response. Any exception during Customer Admin question creation today does not flash an error or redirect the user; it **kills the PHP request entirely**, dumping a raw debug page (message + full stack trace) to whoever is looking at the screen. This is a severe, pre-existing production defect, unrelated to code duplication. **This investigation deliberately avoided writing any test that exercises this catch block**, because doing so would call `exit()` inside the test process itself — not fail one assertion, but terminate the entire test run. See §6 and §9 for how this constrains both this review and the eventual refactor.

---

## 1. Exact duplicated logic

- **StoreController** (both): validate → `DB::beginTransaction()` → create the question → loop over `options` creating one answer row per option → commit → `flash()->success()` + two `session()->flash()` calls (`active_tab`, `active_subtab`) → redirect to the domain index → `catch (\Throwable)` → rollback + report + error flash + redirect back with input/errors.
- **UpdateController** (both): validate → transaction → `findOrFail($id)` (by **numeric primary key**, not `unique_id` — see §2) → update the question's own fields → process `options` → commit → same flash/redirect/catch shape.
- **DeleteController** (both): transaction → `where('unique_id', $unique_id)->firstOrFail()` (by **unique_id string** — different lookup key than Update, see §2) → explicitly `$question->answers()->delete()` → `$question->delete()` → commit → same flash/redirect/catch shape.
- **CopyController** (both): transaction → `with('answers')->findOrFail($id)` (numeric PK again) → `$question->replicate()`, rename to `"{name} (Copy)"`, reset `unique_id` so a new one is generated → save → loop over the original's answers, creating a fresh copy of each → commit → flash + session flags to reopen the edit UI on the copy → redirect.
- **Requests**: `question_name` and `category_id` required in all 4 request classes; `required_question` nullable boolean in all 4.
- **Routes**: identical shape in both trees — `POST /questions/store`, `PUT /questions/{unique_id}/update`, `POST /questions/{unique_id}/copy`, `DELETE /questions/{unique_id}/delete`, all under a `questions.` name prefix.

## 2. Exact behavioral differences

1. **The `dd()` bug (§0)** — Customer Admin Store's error path is completely broken; Rental Ready's is not. This alone means the two `StoreController`s cannot honestly be called "identical" today, even though their happy paths are.
2. **Answer schema is completely different between the two trees**, not just field names:
   - Rental Ready answer: `{answer_name, type ('Rental Ready'|'Maint. Hold'|'Damaged'), index_number}` — a simple typed choice.
   - Customer Admin answer: `{answer_delivery_text, answer_return_text, delivery_amt, return_amt, required, sync_texts, is_damaged, index_number}` — a much richer structure (delivery/return charge amounts, per-answer required flag, a text-sync flag, a damage flag). There is no plausible "shared answer-processing" abstraction here — these are genuinely different domain concepts that happen to share a `question_id` FK shape.
3. **Update's answer-replacement strategy differs fundamentally:**
   - **Rental Ready** *diffs* the submitted options against existing answers: options with a matching existing `id` are updated in place (keeping that row's ID and its `created_at`/history); options without a match are created fresh; any existing answer **not** present in the new list is deleted. Answer IDs are stable across an edit that doesn't remove them.
   - **Customer Admin** unconditionally deletes **all** existing answers (`$question->answers()->delete()`) and recreates every one from scratch on every update — even an answer whose content didn't change gets a brand-new ID, new `created_at`, and (if hard-deleted, see §2.4) its old row is gone forever. This is a real, silent behavior difference a shared abstraction must not casually unify.
4. **Soft-delete vs. hard-delete, confirmed at both the Question and Answer level — the same systemic pattern PR-B4.1 found at the Category level:**
   - `RentalReadyChecklistQuestion` and `RentalReadyChecklistQuestionAnswer` both `use SoftDeletes`.
   - `CustomerAdminQuestion` and `CustomerAdminQuestionAnswer` have **no** `SoftDeletes` trait at all (not even a commented-out one this time — it was never present).
   - Both trees' `question_id` FK on the answers table, and `category_id` FK on the questions table, use `onDelete('cascade')`. Confirmed the cascade chain is DB-consistent for Customer Admin all the way down: `customer_admin_categories` → `customer_admin_questions` → `customer_admin_question_answers`, and separately `customer_admin_questions`/`customer_admin_templates` → `customer_admin_template_questions` — every one of these FKs cascades, so a Customer Admin category or question hard-delete leaves **no orphaned rows anywhere** in this chain; it just **permanently and silently destroys** everything under it, exactly as PR-B4.1 found for categories, now confirmed to extend through questions and answers too. Rental Ready's equivalent chain never cascades at the DB level (soft delete never triggers `ON DELETE`), which is why `RentalReadyChecklistQuestion`'s model boot explicitly hooks `deleting()` to clean up `templateQuestions()`, and why `DeleteController` explicitly calls `$question->answers()->delete()` before deleting the question itself — without that, Rental Ready's answers would never get cleaned up (soft or otherwise) on a question delete.
5. **Route parameter naming is misleading in both trees, identically.** The route segment is literally named `{unique_id}` for `update` and `copy` in both route files, but both `UpdateController`s and both `CopyController`s actually call `findOrFail($id)` — a **numeric primary-key lookup**, not a `unique_id` string lookup. Confirmed by reading both Blade views: the Edit/Copy buttons pass `$question->id` (numeric) into these routes, while the Delete button passes `$question->unique_id` (string) into the delete route, which *does* look up by `unique_id`. This is consistent and functioning (not a live bug), but the route parameter name is actively misleading — worth a documentation note or a rename in a future pass, not something to silently "fix" as part of a behavior-preserving refactor.
6. **No cross-tree mirroring exists for Questions**, unlike Categories' `create_customer_folder`/`create_rental_folder` checkboxes. Confirmed by reading all 8 controllers and both Store `Request` classes — there is no field, no checkbox, no code path that creates a matching question in the other tree. This is a real, confirmed absence, not an oversight in this investigation.
7. **Customer Admin's `options` request field auto-decodes a JSON string via `prepareForValidation()`** (accepts either an array or a JSON string, normalizing to an array before the `'array'` validation rule runs); **Rental Ready's stays a JSON string all the way into the controller**, which does its own `json_decode($validated['options'], true)` after validation. Two different payload-shape conventions for what is conceptually the same "list of answer options" field.
8. **Session-flag payloads on Copy differ slightly:** Rental Ready flashes `active_tab` + `edit_questions_open` (no `active_subtab`); Customer Admin flashes `active_tab` + `active_subtab` + `open_edit_question` (a differently-named key: `open_edit_question` vs. `edit_questions_open`). Both are used by their respective (separate, unmodified) Blade/JS to reopen the edit modal on the newly-copied question — a real key-name and key-count divergence a shared abstraction must not silently unify without checking both views' JS.
9. **Unlike Category's Customer Admin controllers (which all chain an extra `->with('success', ...)`), none of the 4 Question controllers in either tree chain `->with('success', ...)`.** The Category-specific quirk PR-B4.1 found does not reappear here — worth noting so nobody assumes the Category pattern generalizes.

## 3. Which logic is safe to share

- The transaction-wrapper shape (`DB::beginTransaction()`/commit/`catch(\Throwable){rollback; report; flash error; redirect back with input+errors}`) is identical in shape across all 8 controllers (modulo the Customer Admin Store `dd()` defect, which must be treated as a bug to flag, not a pattern to preserve/extract).
- The `session()->flash('active_tab', 'questions')` (+ `active_subtab` where present) pattern.
- The "resolve by numeric PK for update/copy, resolve by `unique_id` for delete" lookup shape is identical in structure (though the two models differ).
- The Copy operation's overall shape — `replicate()`, rename with `" (Copy)"`, ensure a fresh `unique_id`, save, then loop-copy the child answers — is structurally identical, even though the answer-copy payload differs completely per domain.

## 4. Which logic must remain domain-specific

- **The entire answer schema and its create/update payload mapping** (§2.2) — there is no shared "answer" abstraction worth building; a generic `array $optionAttributes` passed through to `$answerModelClass::create($optionAttributes)` is as far as sharing can honestly go.
- **The update-time answer-replacement strategy** (§2.3) — Rental Ready's diff-and-keep vs. Customer Admin's wipe-and-recreate are different business behaviors, not implementation details, and must not be unified without a separate decision (mirroring how PR-B4.1 refused to unify soft/hard delete).
- **Soft-delete vs. hard-delete-with-cascade** (§2.4) — same reasoning as PR-B4.1: a shared service must have zero opinion on this and simply call `->delete()`, letting each model's own traits decide.
- **The Customer Admin Store `dd()` defect** must not be silently "fixed" as an incidental side effect of extraction — if it's fixed at all in this initiative, it should be a small, explicitly-called-out, separately-reviewed one-line change (removing the `dd()` call), exactly like the `insepectorSlect` typo fix was called out on its own in PR-B2. Not attempted in this stage.
- **The differing Copy session-flag key names** (§2.8) stay per-domain; each set of keys is consumed by that domain's own unmodified view/JS.

## 5. Proposed service/trait design (for the next stage — not built yet)

Sketched to frame what the characterization tests below must survive, mirroring `CategoryCrudService`'s shape from PR-B4.1:

- A `QuestionCrudService` with methods roughly:
  - `store(string $questionModelClass, array $questionAttributes, string $answerModelClass, array $answerRowsAttributes): Model` — wraps the transaction, creates the question, then creates each answer row from an already-domain-mapped array (the controller still builds each `$answerRowsAttributes[$i]` from its own `options` payload shape — the service just persists them).
  - `updateWithDiffedAnswers(...)` and `updateWithReplacedAnswers(...)` as **two distinct methods**, not one parameterized method with a boolean flag — because §2.3's difference is a real business-behavior difference, not a style choice, it deserves two named, independently-testable code paths rather than a single method whose behavior silently depends on a flag someone could pass incorrectly.
  - `delete(string $questionModelClass, string $uniqueId): void` — `firstOrFail()` + explicit `->answers()->delete()` + `->delete()`, same shape as today's `DeleteController`s (the explicit answers-delete stays, since it's still necessary for Rental Ready's soft-delete chain — see §2.4).
  - `copy(string $questionModelClass, string $answerModelClass, int $id, callable $answerMapper): Model` — replicate + rename + fresh `unique_id` + save, then loop the original's answers through `$answerMapper` (a per-domain closure building that domain's answer-create array) and persist each. This is the one place genuinely identical structure exists despite the answer-shape difference, if the per-domain mapping is passed in rather than hard-coded.
- Whether `QuestionCrudService`'s `store`/`copy` construction should be one class or a trait mixed into each controller is a decision for the implementation stage, not this one — either can satisfy the "share only genuinely identical logic" constraint established in PR-B4.1.

## 6. Data integrity risks

- **Highest: the Customer Admin `dd()` defect (§0).** Independent of any refactor, this should be flagged to the team as its own near-zero-risk one-line fix (delete the `dd()` call) — it currently means Customer Admin question creation has **no working error path at all** in production. Recommend treating this with the same urgency as the Category hard-delete-cascade finding from PR-B4.1 — both are pre-existing production risks this audit surfaced, neither is something PR-B4.2's own scope (duplication reduction, no behavior change) is authorized to fix inline.
- **High, systemic: Customer Admin's hard-delete-with-cascade now confirmed at 3 levels** (category, question, answer) plus the template-question pivot — deleting anything in that tree is unrecoverable and, for a category or question, silently destroys everything beneath it. This compounds the risk already flagged in PR-B4.1; the Question-level confirmation here shows it's not an isolated Category quirk but a tree-wide design choice.
- **Medium: Customer Admin's wipe-and-recreate answer update strategy (§2.3) loses answer identity on every edit.** Any external system, log, or feature that ever referenced a Customer Admin answer by its row ID across an edit (audit trail, a report keyed on answer ID, a not-yet-built analytics feature) would silently break the moment that answer is edited, because the ID changes even when the content doesn't. Not in scope to fix here, but worth flagging since it's a different class of risk than Rental Ready's ID-stable diff approach.
- **Low: the misleading `{unique_id}` route-parameter name for Update/Copy (§2.5)** is a readability/footgun risk for future developers (someone could reasonably "fix" the controller to look up by the actual `unique_id` string, breaking the app, precisely because the parameter is named that) but is not a live defect today.

## 7. Required characterization tests (delivered in this stage)

1/2. Rental Ready / Customer Admin question store — happy path, question + all answers created, correct redirect/flash/session, no cross-tree row created.
3. Rental Ready update — diff behavior: a kept answer retains its ID; an omitted answer is deleted; a new answer is created.
4. Customer Admin update — wipe-and-recreate: the original answer's ID is completely gone after update; a new row exists with the new content.
5/6. Delete — Rental Ready soft-deletes question and answers (both survive as trashed rows); Customer Admin hard-deletes and cascades both away completely.
7/8. Copy — both trees duplicate the question (new `unique_id`, "(Copy)" suffix) and duplicate every answer, leaving the original question and its answers untouched.
9/10. Validation — Rental Ready rejects a missing `category_id`/`question_name`; Customer Admin additionally rejects a missing `question_delivery_text`/`question_return_text`.
11/12. Update with a nonexistent numeric id — both trees roll back and flash an error via their normal (non-`dd()`) catch path (confirmed safe to exercise — see §0).
13/14. Category relationship integrity — after store, the created question's `category()` relation resolves to the exact category that was passed.
15/16. Confirmed absence of mirror behavior — creating a question in either tree creates zero rows in the other tree's question table.
17. Rental Ready store atomicity — forcing the second answer's `creating` event to throw proves the question row itself is also rolled back, not left behind as an orphan with a partial answer set.

**Note on what is NOT tested and why:** the Customer Admin analogue of test 17 (forcing an answer-creation failure during `StoreController`) was deliberately **not** written, because doing so would execute the live `dd()` call in that controller's catch block (§0), which calls `exit()` and would kill the entire PHPUnit process, not just fail one test. This gap is itself evidence for §0/§6's severity, not an oversight.

All of the above are implemented in `tests/Feature/ChecklistManagement/Questions/QuestionCrudCharacterizationTest.php` (17 test methods) — see "Commands run"/"Pass/fail counts" below.

---

## 8. Exact files expected to change (in the eventual refactor PR — none of these are touched in this stage)

- `app/Services/ChecklistManagement/QuestionCrudService.php` *(new)*
- `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Question/{Store,Update,Delete,Copy}Controller.php`
- `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Question/{Store,Update,Delete,Copy}Controller.php`

**Not expected to change:** all 4 Question `Request` classes, both `RentalReadyChecklistQuestion(Answer)` and `CustomerAdminQuestion(Answer)` models (no `SoftDeletes` added/removed), both trees' Question Blade partials, both route files, Category controllers/service (already shipped in PR-B4.1), anything under Templates.

**Separately recommended, not part of this refactor's scope:** removing the `dd()` call in `CustomerAdmin\Question\StoreController`'s catch block (§0/§6) as its own tiny, explicitly-reviewed fix.

---

## 9. Readiness verdict

## ⚠ Ready with prerequisites

**Why not ✅ Ready to implement:** the live `dd()` defect (§0) is a genuine production hazard independent of this refactor, and it constrains what can even be safely characterized today (§7's note). It should be flagged to product/ops/eng leadership before — or at minimum, alongside — the PR-B4.2 refactor, the same way PR-B4.1 flagged the Category hard-delete/cascade risk. It is not this refactor's job to fix it silently, but proceeding without surfacing it risks the team assuming "duplication reduction" implicitly made question creation safer, when it didn't.

**Why not ❌ Not ready:** no blocking technical unknowns were found for the refactor itself. The duplication is real (transaction/lookup/flash shape), the non-shareable domain logic is clearly bounded (§4), and a 16-test characterization baseline now exists and passes against the current, unmodified controllers.

**Prerequisites before the refactor PR:**
1. Flag the `CustomerAdmin\Question\StoreController` `dd()` defect to product/ops/eng leadership as its own, urgent, separately-reviewed fix — independent of this refactor's timeline.
2. Re-flag (for completeness) that the Customer Admin hard-delete-cascade risk from PR-B4.1 now provably extends through Questions and Answers, not just Categories.

---

## Exact files inspected

- `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Question/{Store,Update,Delete,Copy}Controller.php`
- `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Question/{Store,Update,Delete,Copy}Controller.php`
- `app/Http/Requests/Admin/ChecklistManagement/RentalReady/Question/{Store,Update}Request.php`
- `app/Http/Requests/Admin/ChecklistManagement/CustomerAdmin/Question/{Store,Update}Request.php`
- `app/Models/ChecklistManagement/RentalReady/RentalReadyChecklistQuestion.php`, `RentalReadyChecklistQuestionAnswer.php`
- `app/Models/ChecklistManagement/CustomerAdmin/CustomerAdminQuestion.php`, `CustomerAdminQuestionAnswer.php`
- `database/migrations/checklist_mangement/rental_ready/2025_08_19_122153_create_rental_ready_checklist_question_answers_table.php`
- `database/migrations/checklist_mangement/customer_admin/2025_08_27_105917_customer_admin_question_answers.php`
- `database/migrations/checklist_mangement/customer_admin/2025_08_27_112245_customer_admin_template_questions.php`
- `database/migrations/checklist_mangement/rental_ready/2025_08_20_163320_create_rental_ready_checklist_template_questions_table.php`
- `routes/admin/checklist_management/rental_ready/question/routes.php`
- `routes/admin/checklist_management/customer_admin/question/routes.php`
- `resources/views/admin/checklist_management/rental_ready/partials/_sub_tab_questions.blade.php`
- `resources/views/admin/checklist_management/customer_admin/partials/_sub_tab_questions.blade.php`

## Exact commands run

```
Glob: **/RentalReady/Question/*.php, **/CustomerAdmin/**/Question*/*.php
Grep: questions.update|questions.copy|questions.delete — both trees' Blade views (route-parameter value confirmation)
Grep: RentalReadyChecklistQuestion::|CustomerAdminQuestion:: — tests/ (confirm no existing dedicated CRUD tests)

php -l tests/Feature/ChecklistManagement/Questions/QuestionCrudCharacterizationTest.php

php artisan test --env=testing tests/Feature/ChecklistManagement/Questions/QuestionCrudCharacterizationTest.php
```

## Pass/fail counts

```
php artisan test --env=testing tests/Feature/ChecklistManagement/Questions/QuestionCrudCharacterizationTest.php
→ 17 passed (71 assertions)
```

Run against the current, completely unmodified controllers/models/routes.

## Confirmation

**NO PRODUCTION CODE WAS MODIFIED.** Only this document and the new characterization test file were added. All findings were obtained by reading the current state of the listed files directly, and all test assertions run against the unmodified controllers.
