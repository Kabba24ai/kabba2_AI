# PR-B4.3 — Template CRUD Duplication: Readiness Review

**Date:** 2026-07-11
**Type:** Investigation and baseline-testing only. **No production code was modified.**
**Depends on:** `PR-B4_1_CATEGORY_READINESS.md`/`PR-B4_1_CATEGORY_REFACTOR.md`, `PR-B4_2_QUESTION_READINESS.md`/`PR-B4_2_QUESTION_REFACTOR.md` (the two precedents this PR follows), `PHASE2_IMPLEMENTATION_PLAN.md` (PR-B4, final of its 3 sub-PRs).

---

## 1. Exact duplicated logic

Templates turn out to be **the most duplicated of all three Track B CRUD stacks** — more so than Questions, because both trees share the exact same field schema end to end:

- **IndexController** (both): a one-line view-loader (`return view('...templates.index')`), zero logic.
- **StoreController** (both): validate → `DB::beginTransaction()` → create the template (`template_name`, `description`, `equipment_category_id` from `equipment_category`, `active_template` from `is_active`) → `json_decode($validated['questions'], true)` → loop, creating one `TemplateQuestion` row per entry (`template_id`, `question_id`, `index_number = $index + 1`) → commit → flash/session/redirect → `catch(\Throwable)` → rollback + report + error flash + redirect back.
- **UpdateController** (both): transaction → `where('unique_id', $unique_id)->firstOrFail()` → update the template's own fields → **unconditionally delete all existing `TemplateQuestion` rows for this template** (`TemplateQuestion::where('template_id', $template->id)->delete()`) → re-decode `questions` → loop-recreate every row from scratch, including a `'required' => $q['required'] ?? false` key → commit → same flash/redirect/catch shape.
- **DeleteController** (both): transaction → `where('unique_id', ...)->firstOrFail()` → explicit `$template->questions()->delete()` → `$template->delete()` → commit → same flash/redirect/catch shape.
- **CopyController** (both): transaction → `where('unique_id', ...)->firstOrFail()` → `replicate()`, rename to `"{name} (Copy)"`, explicitly regenerate `unique_id` via `ModelHelper::generateUniqueID(new {Model}, 'TQS')`, save → loop over `$template->templateQuestions` (the **ordered** relation — `orderBy('index_number')`) copying each into a fresh `TemplateQuestion` row on the new template, preserving `question_id` and `index_number` — commit → flash/session (including a `open_edit_template` key) → redirect.
- **Requests** (all 4): identical rules — `template_name`/`equipment_category`/`questions` all `'required'`, `description`/`is_active` `'nullable'`. **`questions` has no `'json'` or `'array'` type rule** — any non-empty value passes validation; the controller's own `json_decode()` is the only place invalid JSON would surface.
- **Routes** (both): identical shape — `GET /templates`, `POST /templates/store`, `PUT /templates/{unique_id}/update`, `DELETE /templates/{unique_id}/delete`, `POST /templates/{unique_id}/copy`, all under a `templates.` name prefix. **Unlike Questions, there is no numeric-PK-vs-`unique_id` route-parameter mismatch here** — Update/Delete/Copy all consistently look up by the actual `unique_id` string in both trees.

---

## 2. Exact behavioral differences

1. **Soft-delete vs. hard-delete — the third confirmation of the same systemic pattern found in PR-B4.1 (Categories) and PR-B4.2 (Questions).** `RentalReadyChecklistTemplate` and `RentalReadyChecklistTemplateQuestion` both `use SoftDeletes` (added by a dedicated later migration, `2025_08_28_151041_add_soft_deletes_to_rental_ready_tables.php`, which added `deleted_at` to **five** Rental Ready tables — categories, questions, question_answers, templates, and template_questions — all at once). `CustomerAdminTemplate` and `CustomerAdminTemplateQuestion` have no `SoftDeletes` trait and no `deleted_at` column anywhere — confirmed no equivalent migration exists in the Customer Admin migration set (5 files total, none titled anything like "add soft deletes"). Both trees' `template_id`/`question_id` FKs on the template-questions table use `onDelete('cascade')`, so a Customer Admin template hard-delete permanently and silently destroys its template-question links; a Rental Ready template soft-delete never triggers that cascade at all (which is exactly why both `DeleteController`s explicitly call `->questions()->delete()`/`->answers()->delete()`-style cleanup before deleting the parent — necessary for Rental Ready's soft-delete chain, redundant-but-harmless for Customer Admin's cascade).
2. **`equipment_category_id` referential integrity DIVERGES between the two trees — a real behavioral difference, not the shared gap it first appeared to be.** Both `create_..._templates` migrations originally created this column as a plain `string` with no FK. But a **later, Rental-Ready-only migration** (`2025_08_21_162850_update_equipment_category_in_rental_ready_checklist_templates.php`) dropped that column and replaced it with a real `foreignId('equipment_category_id')->nullable()->constrained('product_categories')->nullOnDelete()`. **No equivalent migration exists anywhere in the Customer Admin migration set** (confirmed — only 3 files reference `customer_admin_templates` at all, and none alters this column). The practical consequence, concretely proven by testing: submitting a nonexistent `equipment_category_id` to the **Rental Ready** Store endpoint throws a `QueryException` (FK violation), caught by the controller's normal `catch(\Throwable)` block, rolling back and flashing an error — while the identical submission to the **Customer Admin** Store endpoint succeeds silently, storing the garbage value as-is. This was only discovered by writing and running the characterization test for this scenario against both trees; a read-only code review would very plausibly have missed it, since both controllers' code and both Request classes' validation rules are byte-for-byte identical — the divergence is purely in the schema. Also note: Rental Ready's FK uses `nullOnDelete()`, so if a `ProductCategory` referenced by a Rental Ready template is later deleted, that template's `equipment_category_id` is automatically set to `NULL` — Customer Admin has no such protection or cleanup at all.
3. **The `'required'` field submitted on Update is silently a no-op, in both trees identically.** `UpdateController::__invoke()` passes `'required' => $q['required'] ?? false` into `TemplateQuestion::create([...])`, but **neither** `RentalReadyChecklistTemplateQuestion` nor `CustomerAdminTemplateQuestion` has `'required'` in its `$fillable` array, and neither table has a `required` column at all (confirmed by re-reading both `create_..._template_questions` migrations: `template_id`, `question_id`, `index_number`, `unique_id`, timestamps — nothing else). Eloquent's mass-assignment protection silently drops attributes not in `$fillable` — no error, no effect. `StoreController` doesn't even attempt to pass this key at all. This is dead, misleading code in both trees identically — not a bug in the sense of causing incorrect behavior (it does nothing either way), but worth flagging so a future developer doesn't assume it works.
4. **Invalid-JSON `questions` payload** is not validated (`'required'` only, no `'json'`/`'array'` rule) in either tree. A non-JSON string would `json_decode()` to `null`; `foreach (null as ...)` throws a `TypeError`, caught by the generic `catch (\Throwable)` block, producing the same rollback/error-flash/redirect as any other failure — identical (non-)handling in both trees.
5. **No cross-tree mirroring exists for Templates**, confirmed by reading all 10 controllers and all 4 Request classes — no checkbox, no field, no code path creates a matching template in the other tree. Same confirmed absence as Questions (Templates never had Categories' mirror-checkbox feature either).
6. **Neither tree's Template controllers chain `->with('success', ...)`** onto their redirects — this matches the Question CRUD pattern from PR-B4.2, not the Category CRUD pattern from PR-B4.1 (where Customer Admin's controllers uniquely did chain it). Worth reconfirming per-PR rather than assuming one prior pattern generalizes.

**One genuinely new fact, different from both prior sub-PRs:** unlike Categories (mirror checkbox differs by field name/direction) and Questions (Rental Ready diffs answers, Customer Admin wipes-and-recreates), **Templates' update strategy is identical in both trees** — both unconditionally wipe all template-question rows and recreate them from scratch on every edit. There is no Rental-Ready-preserves-IDs vs. Customer-Admin-doesn't split here; both behave like Question's Customer Admin path. This means Templates' shared service can use **one** update method, not two, unlike Questions.

---

## 3. Which logic is safe to share

- The transaction-wrapper shape (`DB::beginTransaction()`/commit/`catch(\Throwable){rollback; report; flash error; redirect back with input+errors}`) — identical across all 8 non-Index controllers.
- The `unique_id`-string lookup via `firstOrFail()` for Update/Delete/Copy — identical in both trees, and (unlike Questions) with no numeric-PK-vs-`unique_id` inconsistency to preserve separately.
- The Store/Update "decode `questions` JSON, loop-create one `TemplateQuestion` row per entry with `index_number = $index + 1`" mechanics — identical in both trees, including the (currently dead) `'required'` key on Update.
- The **single** wipe-and-recreate update strategy — since it's identical in both trees, this can be one shared method (unlike Question's necessarily-two-methods split).
- The Copy operation's shape — `replicate()`, rename with `" (Copy)"`, regenerate `unique_id` via the same `ModelHelper::generateUniqueID()` call (both trees use the **same** prefix, `'TQS'`, unlike Questions where the two trees used different copy-time unique_id strategies) — and the ordered loop-copy of `templateQuestions`.

## 4. Which logic must remain domain-specific

- **Soft-delete vs. hard-delete-with-cascade** — same reasoning as PR-B4.1/PR-B4.2: a shared service must have zero opinion on this; `delete()` just calls `->delete()` and lets each model's own traits decide.
- **Each domain's own model classes** (`RentalReadyChecklistTemplate`/`RentalReadyChecklistTemplateQuestion` vs. `CustomerAdminTemplate`/`CustomerAdminTemplateQuestion`) and their own `Request` classes.
- **Redirect target route names** (`rental-ready.index` vs. `customer-admin.index`) and each domain's own flash text.
- **The dead `'required'` field** should be preserved exactly as currently dead code during extraction (passed through, still silently dropped by both models) rather than either wired up to work (a real behavior change, out of scope) or quietly deleted (an undocumented behavior change) — see §6 for the recommendation to flag this, not fix it, in this PR.
- **`equipment_category_id`'s FK enforcement** — Rental Ready's real, schema-level FK constraint (with `nullOnDelete()`) vs. Customer Admin's complete absence of one — must not be silently unified either direction by a shared service. A shared service has no schema-level control over this anyway (it's enforced by MySQL itself, not application code), but the eventual refactor's characterization tests must keep proving both outcomes so nobody "fixes" Customer Admin's gap as an unplanned side effect of consolidating the Store/Update controllers.

## 5. Proposed shared service design (for the next stage — not built yet)

Sketched to frame what the characterization tests below must survive, following the `CategoryCrudService`/`QuestionCrudService` naming and design convention:

- A `TemplateCrudService` with methods:
  - `store(string $templateModelClass, array $templateAttributes, string $templateQuestionModelClass, array $questionRows): Model` — creates the template, then creates each `TemplateQuestion` row from an already-domain-mapped array, in one `DB::transaction()`.
  - `updateWithReplacedQuestions(string $templateModelClass, string $templateQuestionModelClass, string $uniqueId, array $templateAttributes, array $questionRows): Model` — **one** method (not two, per §2's "genuinely new fact") — `firstOrFail()` by `unique_id`, update the template, wipe all existing `TemplateQuestion` rows, recreate from `$questionRows`.
  - `delete(string $templateModelClass, string $uniqueId): void` — `firstOrFail()` + explicit `->questions()->delete()` + `->delete()`, mirroring today's `DeleteController`s exactly.
  - `copy(string $templateModelClass, string $templateQuestionModelClass, string $uniqueId, string $uniqueIdPrefix, callable $mapQuestionForCopy): Model` — `firstOrFail()`, `replicate()`, rename, regenerate `unique_id` via `ModelHelper::generateUniqueID()` with a caller-supplied prefix (both trees currently use `'TQS'`, but the prefix should still be a parameter, not hard-coded, so each domain stays in control of its own ID-prefix convention going forward), save, then loop the original's **ordered** `templateQuestions` through a caller-supplied mapping closure.
- Whether the `'required'` no-op key is passed through by the controller into `$questionRows` (and silently dropped by the model, as today) or dropped at the controller level before calling the service is an implementation-stage decision; either preserves today's observable behavior identically, so it doesn't block this readiness review.

## 6. Data integrity risks

- **Systemic (3rd confirmation): Customer Admin's hard-delete-with-cascade**, now confirmed at Category, Question/Answer, **and** Template/TemplateQuestion level. Continues to be flagged as its own separate, urgent product/ops decision — not something any of the three CRUD refactors were authorized to fix.
- **New: `equipment_category_id` referential integrity diverges between the trees, confirmed by testing, not just reading.** Rental Ready has a real FK (`nullOnDelete()` — a deleted `ProductCategory` cleanly nulls out the reference). Customer Admin has no FK at all — a deleted/renamed/nonexistent category silently leaves (or is silently accepted into) a dangling reference with zero protection. This is a genuine, lower-severity asymmetry worth its own flag to product/ops, alongside (but distinct from) the hard-delete/cascade finding — it's a schema gap on Customer Admin's side that Rental Ready simply doesn't have, one more instance of Customer Admin's tree generally having weaker data-integrity guarantees than Rental Ready's.
- **Low: the dead `'required'` field** — no data-integrity risk today (it does nothing), but a maintainability trap: a future developer could reasonably assume flipping it in the request payload does something, and lose time debugging why it doesn't, or "fix" it by adding a migration without realizing it changes what students today've never actually used it for. Worth a one-line note in the eventual refactor's documentation, not a fix in this PR.

## 7. Required characterization tests (delivered in this stage)

1/2. Store — both trees create the template and its `TemplateQuestion` rows in submitted order.
3/4. Update — both trees wipe all existing `TemplateQuestion` rows and recreate from the new list (same strategy, both trees — a materially different assertion from Questions' PR-B4.2 suite, which had to prove two *different* strategies).
5/6. Update — index_number ordering from the submitted list is preserved exactly, both trees.
7. Delete — Rental Ready soft-deletes the template and its template-questions; the underlying `RentalReadyChecklistQuestion` (a different, upstream model) is unaffected.
8. Delete — Customer Admin hard-deletes the template and cascades its template-questions away.
9/10. Copy — both trees duplicate the template (new `unique_id`, `" (Copy)"` suffix) and duplicate every template-question link in the original order, leaving the original untouched.
11/12. Validation — both trees reject a missing `template_name`/`equipment_category`.
13. Equipment-category referential integrity divergence — submitting an `equipment_category_id` that doesn't correspond to any real `ProductCategory`: **Rental Ready rejects it** (a real FK constraint throws, caught by the normal error path, nothing persisted); **Customer Admin accepts it silently** (no FK, stored as-is) — both outcomes tested and confirmed, concretely proving §2 finding #2.
14. Invalid-JSON `questions` payload rolls back and flashes an error rather than leaking a raw `TypeError` — proves §2 finding #4.
15/16. No cross-tree mirroring — creating a template in either tree creates zero rows in the other tree's template table.
17/18. Store atomicity — forcing the second `TemplateQuestion`'s `creating` event to throw proves the template row itself is also rolled back, for both trees (no `dd()`-style landmine was found in either Store controller, confirmed safe to test both, unlike Question's Customer Admin Store before its bug-fix).

All of the above are implemented in `tests/Feature/ChecklistManagement/Templates/TemplateCrudCharacterizationTest.php` — see "Commands run"/"Pass/fail counts" below for the actual run against the current, unmodified controllers.

---

## 8. Exact files expected to change (in the eventual refactor PR — none of these are touched in this stage)

- `app/Services/ChecklistManagement/TemplateCrudService.php` *(new)*
- `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Templates/{Store,Update,Delete,Copy}Controller.php`
- `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Templates/{Store,Update,Delete,Copy}Controller.php`

**Not expected to change:** both `IndexController`s (no logic to extract), all 4 Template `Request` classes, both `RentalReadyChecklistTemplate(Question)` and `CustomerAdminTemplate(Question)` models (no `SoftDeletes` added/removed, no new `'required'` column/fillable entry), both trees' Templates Blade partials, both route files, `CategoryCrudService`/`QuestionCrudService` and their controllers (already shipped), any database migration.

---

## 9. Readiness verdict

## ⚠ Ready with prerequisites

**Why not ✅ Ready to implement:** two data-integrity findings (§6) are independent of this refactor and should be surfaced to product/ops before or alongside it, consistent with how PR-B4.1 and PR-B4.2 each flagged their own equivalent findings: (1) the now-3x-confirmed Customer Admin hard-delete-with-cascade pattern, now known to span the entire Category→Question→Answer→Template→TemplateQuestion surface, and (2) `equipment_category_id`'s complete lack of referential integrity in both trees, which is a new finding not previously surfaced in PR-B4.1/PR-B4.2 (Categories and Questions never referenced `ProductCategory` this way).

**Why not ❌ Not ready:** no blocking technical unknowns were found. The duplication is the most extensive of the three sub-PRs, the safe-to-share/must-stay-domain-specific boundary is clear (§3/§4) — and simpler than Questions' in one respect (Templates need only one update method, not two) — and a baseline characterization suite now exists and passes against the current, unmodified controllers.

**Prerequisites before the refactor PR:**
1. Re-flag the Customer Admin hard-delete/cascade pattern to product/ops, noting it now provably spans the entire tree (Categories, Questions, Answers, Templates, TemplateQuestions) — not a new decision, but worth restating its full scope now that all three sub-PRs have confirmed it.
2. Flag `equipment_category_id`'s divergent referential integrity (Rental Ready has a real FK with `nullOnDelete()`; Customer Admin has none) as its own, separate, lower-urgency item — no immediate data-loss risk like the hard-delete finding, but worth product/ops awareness, and worth confirming whether Customer Admin should eventually get the same FK Rental Ready already has (a schema change, out of scope for the CRUD-duplication refactor itself).

---

## Exact files inspected

**Controllers:**
- `app/Http/Controllers/Admin/ChecklistManagement/RentalReady/Templates/{Index,Store,Update,Delete,Copy}Controller.php`
- `app/Http/Controllers/Admin/ChecklistManagement/CustomerAdmin/Templates/{Index,Store,Update,Delete,Copy}Controller.php`

**Requests:**
- `app/Http/Requests/Admin/ChecklistManagement/RentalReady/Templates/{Store,Update}Request.php`
- `app/Http/Requests/Admin/ChecklistManagement/CustomerAdmin/Templates/{Store,Update}Request.php`

**Models:**
- `app/Models/ChecklistManagement/RentalReady/RentalReadyChecklistTemplate.php`, `RentalReadyChecklistTemplateQuestion.php`
- `app/Models/ChecklistManagement/CustomerAdmin/CustomerAdminTemplate.php`, `CustomerAdminTemplateQuestion.php`

**Migrations:**
- `database/migrations/checklist_mangement/rental_ready/2025_08_20_163308_create_rental_ready_checklist_templates_table.php`
- `database/migrations/checklist_mangement/rental_ready/2025_08_20_163320_create_rental_ready_checklist_template_questions_table.php`
- `database/migrations/checklist_mangement/rental_ready/2025_08_28_151041_add_soft_deletes_to_rental_ready_tables.php`
- `database/migrations/checklist_mangement/rental_ready/2025_08_21_162850_update_equipment_category_in_rental_ready_checklist_templates.php` *(the Rental-Ready-only FK migration — its absence on the Customer Admin side is §2 finding #2)*
- `database/migrations/checklist_mangement/customer_admin/2025_08_27_111450_customer_admin_templates.php`
- `database/migrations/checklist_mangement/customer_admin/2025_08_27_112245_customer_admin_template_questions.php`

**Routes:**
- `routes/admin/checklist_management/rental_ready/templates/routes.php`
- `routes/admin/checklist_management/customer_admin/templates/routes.php`

## Exact commands run

```
Glob: **/RentalReady/Templates/*.php, **/CustomerAdmin/**/Templates*/*.php
Grep: rental_ready_checklist_templates — database/migrations (locate ALL migrations touching this table, including the later FK-adding one)
Grep: rental_ready_checklist_template_questions|customer_admin_template_questions — database/migrations (locate the soft-deletes-adding migration)
Grep: customer_admin_templates — database/migrations (confirm no equivalent FK or soft-deletes migration exists for Customer Admin)
Grep: RentalReadyChecklistTemplate::|CustomerAdminTemplate:: — tests/ (confirm no existing dedicated CRUD tests)
Grep: protected $fillable — app/Models/ProductManagement/ProductCategory.php (test-fixture field confirmation)

php -l tests/Feature/ChecklistManagement/Templates/TemplateCrudCharacterizationTest.php

php artisan test --env=testing tests/Feature/ChecklistManagement/Templates/TemplateCrudCharacterizationTest.php
```

**Note on process:** the first test run surfaced two incorrect assumptions in the initial test draft, both corrected before finalizing this document — (1) an update-time test assumed a hard "missing" row where the row was actually soft-deleted (matching the already-established Rental Ready soft-delete pattern — a test-authoring fix, not a finding), and (2) the equipment-category "no referential integrity" test failed with a real MySQL foreign-key violation, which led directly to discovering the Rental-Ready-only FK migration in §2 finding #2 — a genuine correction to this document's initial (wrong) claim that the gap was identical in both trees. Both are called out here rather than silently smoothed over.

## Pass/fail counts

```
php artisan test --env=testing tests/Feature/ChecklistManagement/Templates/TemplateCrudCharacterizationTest.php
→ 17 passed (70 assertions)
```

Run against the current, completely unmodified controllers/models/routes.

## Confirmation

**NO PRODUCTION CODE WAS MODIFIED.** Only this document and the new characterization test file were added. All findings were obtained by reading the current state of the listed files directly, and all test assertions run against the unmodified controllers.
