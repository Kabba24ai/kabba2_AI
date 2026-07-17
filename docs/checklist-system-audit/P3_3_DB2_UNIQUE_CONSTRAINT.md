# P3-3 / DB-2 — Composite unique constraints on template↔question join tables

**Date:** 2026-07-15
**Scope:** Phase 3, Sprint 1, third item only. No other Sprint 1/Phase 3 items were started.

---

## Objective

Add a composite unique constraint on `(template_id, question_id)` to both `rental_ready_checklist_template_questions` and `customer_admin_template_questions`, per `PHASE3_IMPLEMENTATION_PLAN.md` DB-2, so a question cannot be linked to the same template more than once at the database layer.

## Runtime audit — confirmed no duplicates exist today

Re-ran the duplicate-detection query live against `rc_kabba_7_7_26` (read-only) before adding any constraint, re-confirming the prior `PHASE3_RESULTS.md` V6 finding:

```sql
SELECT template_id, question_id, COUNT(*) AS cnt
FROM rental_ready_checklist_template_questions
WHERE deleted_at IS NULL
GROUP BY template_id, question_id
HAVING COUNT(*) > 1;
-- 0 rows

SELECT template_id, question_id, COUNT(*) AS cnt
FROM customer_admin_template_questions
GROUP BY template_id, question_id
HAVING COUNT(*) > 1;
-- 0 rows
```

Both returned zero rows — safe to add the constraint. Also noted for context: `rental_ready_checklist_template_questions` currently has 1,306 total rows, of which **950 (73%) are already soft-deleted** — this figure directly informed the migration design below.

## A critical design finding: the two tables are NOT symmetric

`customer_admin_template_questions` has **no `deleted_at` column** (`CustomerAdminTemplateQuestion` does not use `SoftDeletes`) — a plain composite unique key is sufficient and correct there.

`rental_ready_checklist_template_questions` **does** have `deleted_at` (`RentalReadyChecklistTemplateQuestion` uses `SoftDeletes`). Critically, `TemplateCrudService::updateWithReplacedQuestions()` — the method behind **every single Rental Ready template save** — does this on every call:

```php
$templateQuestionModelClass::where('template_id', $template->id)->delete(); // soft-delete, for this model
foreach ($questionRows as $row) {
    $templateQuestionModelClass::create($row + ['template_id' => $template->id]);
}
```

This soft-deletes all existing rows for the template, then **recreates fresh rows with the same `(template_id, question_id)` pairs** — on every save, even if the question list didn't change. A plain `UNIQUE(template_id, question_id)` index would have made the *second* save of any previously-saved template fail immediately, since the soft-deleted row physically remains in the table and collides with the newly-created one. The 950 already-soft-deleted rows found in the audit above are direct evidence of exactly this churn pattern already happening constantly in production.

**Resolution:** used a MySQL 8 generated (virtual) column, `active_pair_key`, defined as `IF(deleted_at IS NULL, 1, NULL)`. MySQL's unique-index semantics treat `NULL` as "not equal to any value, including another NULL" — so:
- Two **active** rows (`deleted_at IS NULL` → `active_pair_key = 1`) for the same `(template_id, question_id)` **do** collide and are rejected (both have `active_pair_key = 1`).
- Any number of **soft-deleted** rows (`active_pair_key = NULL`) for the same pair **do not** collide with each other or with an active row — preserving the existing soft-delete-then-recreate save flow exactly as it works today.

## Migration

**File:** `database/migrations/2026_07_15_100000_add_unique_constraint_to_template_questions_tables.php`

- `rental_ready_checklist_template_questions`: adds virtual generated column `active_pair_key INT GENERATED ALWAYS AS (IF(deleted_at IS NULL, 1, NULL))`, then `UNIQUE (template_id, question_id, active_pair_key)` named `rr_template_questions_active_unique`.
- `customer_admin_template_questions`: `UNIQUE (template_id, question_id)` named `ca_template_questions_unique`.
- `down()`: re-adds a plain index on `template_id` before dropping each unique key. **This was not optional** — verified empirically that adding the composite unique key (which starts with `template_id`) caused MySQL/InnoDB to automatically drop the original single-column index that existed solely to support each table's `template_id` foreign key (no longer needed once a suitable leftmost-prefix index existed). Attempting to `dropUnique()` without first restoring an equivalent index fails with `SQLSTATE[HY000]: 1553 Cannot drop index ...: needed in a foreign key constraint` — reproduced this directly, then fixed and re-verified a full rollback → re-migrate cycle succeeds cleanly.

Verified against the testing database (`rc_kabba_testing`):
```
php artisan migrate            (up)      → DONE
php artisan migrate:rollback   (down)    → DONE (after the index fix above)
php artisan migrate            (re-up)   → DONE
```

## Is this defense-in-depth, or a real gap being closed?

**A real gap, not merely defense-in-depth.** Checked both `RentalReady\Templates\StoreRequest`/`UpdateRequest` and `TemplateCrudService` for any existing duplicate-prevention logic (e.g. a `distinct` validation rule on question IDs, or an `array_unique()` before insert) — **none exists**. The `questions` field is validated only as `required`; nothing stops a caller (a frontend bug, a replayed request, or a future API consumer) from submitting the same `question_id` twice in one template save, which would previously have silently created two duplicate active rows. The 0-duplicates-found result reflects that this has never *actually* happened in practice, not that anything prevented it. This migration closes a real, previously-open latent risk at the one layer (the database) that can guarantee it regardless of which code path writes to these tables.

## Tests added

`tests/Feature/ChecklistManagement/TemplateQuestionUniqueConstraintTest.php` (6 tests):

1. `test_rental_ready_duplicate_active_pair_cannot_be_inserted` — second active insert for the same pair throws `QueryException`.
2. `test_rental_ready_distinct_pairs_are_accepted` — two different questions on the same template both save fine.
3. `test_rental_ready_soft_deleting_then_recreating_the_same_pair_still_works` — directly exercises the exact pattern `TemplateCrudService::updateWithReplacedQuestions()` uses (soft-delete, then recreate the same pair) and confirms it still succeeds.
4. `test_rental_ready_multiple_soft_deleted_rows_for_the_same_pair_do_not_collide` — two soft-deleted rows for the same pair, then a third active one, all coexist.
5. `test_customer_admin_duplicate_pair_cannot_be_inserted` — second insert for the same pair throws `QueryException` (no soft-delete complication on this table).
6. `test_customer_admin_distinct_pairs_are_accepted` — two different questions on the same template both save fine.

Also re-ran the full existing Template CRUD suite to confirm no regression in real save/update/copy/delete flows:
- `tests/Unit/Services/ChecklistManagement/TemplateCrudServiceTest.php`
- `tests/Feature/ChecklistManagement/Templates/TemplateCrudCharacterizationTest.php` (includes "template update wipes and recreates questions preserving order" for both trees — the exact real-world flow this constraint had to not break)

## Exact commands run

```
php artisan migrate --env=testing
php artisan migrate:rollback --step=1 --env=testing
php artisan migrate --env=testing
php artisan test --filter=TemplateQuestionUniqueConstraintTest
php artisan test --filter="TemplateQuestionUniqueConstraintTest|TemplateCrudServiceTest|TemplateCrudCharacterizationTest|Bug11NullGuardCharacterizationTest|SaveControllerCharacterizationTest|ValidationGuardObservabilityTest|UpdateDeliveryPickupInputsStatusValidationTest"
```

## Pass/fail counts

- New test file alone: **6 passed**, 11 assertions.
- Combined with the full Template CRUD suite and every Sprint 1 test added so far (BUG-11, SEC-1): **58 passed**, 231 assertions, 0 failures.

## Files changed

- `database/migrations/2026_07_15_100000_add_unique_constraint_to_template_questions_tables.php` (new)
- `tests/Feature/ChecklistManagement/TemplateQuestionUniqueConstraintTest.php` (new, 6 tests)
- `docs/checklist-system-audit/P3_3_DB2_UNIQUE_CONSTRAINT.md` (new — this file)

**No application logic was modified.** `TemplateCrudService`, the Store/Update/Copy controllers, and both models are untouched — the migration and the generated column are the entire change.

## Verdict

**PASS.** Constraint added to both tables, confirmed safe against live data (0 duplicates), confirmed compatible with the existing soft-delete-then-recreate save flow via a generated-column technique (not a plain composite unique, which would have broken every Rental Ready template edit), migration verified reversible, and documented as closing a real (if never-yet-triggered) gap rather than pure defense-in-depth. Stopping here — no P3-4 or later item started.
