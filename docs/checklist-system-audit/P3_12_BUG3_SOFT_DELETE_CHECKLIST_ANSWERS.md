# P3-12 — BUG-3: Soft-delete checklist answers together with checklist questions

**Date:** 2026-07-17
**Scope:** Phase 3, PR order item 12 — the last of the delivery/return bug-fix wave (P3-9–P3-12). BUG-3 only, and only within `RemoveController` — no API work, no P3-6, no architecture changes, no unrelated cleanup. Depends on P3-8 (baseline characterization), P3-9 (BUG-2), P3-10 (BUG-4), and P3-11 (BUG-5), all complete and reviewed. None of their production code was touched.

---

## Root cause

`RemoveController::__invoke()` soft-deletes a product's checklist questions with a single bulk query-builder call:

```php
$orderProduct->checklistQuestions()->delete();
```

`OrderProductChecklistQuestion` uses the `SoftDeletes` trait, so this correctly sets `deleted_at` on every matching question row. But a bulk `delete()` call through a relation query builder is a plain `UPDATE ... SET deleted_at = ?` — it does not instantiate each model or fire any per-row Eloquent event, and there was no separate statement anywhere in this method to also soft-delete the child `OrderProductChecklistQuestionAnswers` rows (`order_product_checklist_question_answers`, related via `order_product_checklist_question_id`). The result: the parent question is correctly excluded from any default (non-trashed) query, but its answer rows remain fully live — `deleted_at IS NULL` — still carrying whatever `is_delivery_answer`/`is_return_answer` flags they had, orphaned under a parent that no longer appears in the active checklist.

## Why the orphaned answers occurred

Soft-delete cascades in Eloquent are never automatic — a parent's `SoftDeletes` trait has no built-in mechanism to propagate to a `hasMany` child; every cascade in this codebase is hand-written. `RemoveController` simply never had that hand-written cascade for this specific relationship, even though the codebase already establishes the exact right pattern for it elsewhere (see "Why soft delete was chosen" below) — it just wasn't applied here.

## Implementation

One addition, placed immediately before the existing bulk question-delete, inside the same `foreach ($order->products as $orderProduct)` loop and the same outer `DB::transaction()`:

```php
// BUG-3 fix: soft-delete every child answer row alongside its parent question row...
$answerIds = $orderProduct->checklistQuestions->pluck('answers')->flatten()->pluck('id');
OrderProductChecklistQuestionAnswers::whereIn('id', $answerIds)->delete();

$orderProduct->checklistQuestions()->delete();
```

`$orderProduct->checklistQuestions` (the already eager-loaded Collection, loaded via `Order::with(['products.checklistQuestions.answers', ...])` at the very top of the request, before any mutation) is used rather than a fresh query — this captures exactly the pre-delete set of live question IDs and their already-loaded `answers` relations, with no extra query needed to look them up again. `pluck('answers')->flatten()` collects every answer model across every question into one flat collection; `pluck('id')` reduces that to the ID list a single bulk `delete()` needs. If a question happens to have zero answers, `flatten()`/`pluck('id')` simply contributes nothing to the list — `whereIn()` with an empty array is a no-op, not an error.

This is the smallest correct change: one new two-line statement, no new methods, no schema change, no change to the surrounding loop structure or any other line in the file.

## Why soft delete was chosen (not hard delete)

Soft delete was never actually a choice to make here — it was already the parent's own established behavior (`OrderProductChecklistQuestion` soft-deletes; `OrderProductChecklistQuestionAnswers` also already uses the `SoftDeletes` trait, so no migration was needed either). The fix simply extends the *same* soft-delete semantics one level down to match, rather than introducing a different (harder) deletion behavior for the child that its own parent doesn't use.

This is also independently validated by an **already-existing, already-correct precedent in the same codebase**: `OrderProduct`'s own model-level `deleting` boot hook (fired when an `OrderProduct` itself is soft-deleted — e.g. via `Order`'s own `deleting` hook cascading down) already does exactly this cascade correctly:

```php
// app/Models/Orders/OrderProduct.php, static::deleting()
$model->checklistQuestions()->get()->each(function ($question) {
    $question->answers()->delete();
});
$model->checklistQuestions()->delete();
```

and its `restoring` hook cascades the restore symmetrically. `RemoveController` is the one place in the checklist-removal path that never had this cascade — this fix brings it in line with a pattern the codebase had already chosen and applied everywhere else it deletes a question/answer pair.

## Transaction behavior

The new statement runs inside the exact same `DB::transaction()` closure that already wraps the whole method (unchanged from PR-A2) and the exact same per-product `foreach` iteration the existing question-delete already runs inside. No new transaction boundary, no nested transaction, no change to how or when the outer transaction commits or rolls back. A failure anywhere else in the same request (e.g. the forced-listener-failure scenario `ChecklistTransactionTest` exercises) rolls the new answer soft-deletes back exactly the same way it already rolls back the question soft-deletes, the `OrderProduct` field reset, and the equipment status transition.

## Fields/behavior preserved

- **Equipment status reset** (`EquipmentStatusService::markAvailableOnChecklistRemove()`) — untouched.
- **Delivery reset** (P3-9/pre-existing) and **pickup reset** (P3-11) — the `$orderProductData` array is untouched.
- **Media deletion** — the delivery/pickup media and signature-file cleanup blocks are untouched.
- **Status logging** — `equipment_status_logs` writes are untouched.
- **Billing data** — nothing in this controller touches billing; unaffected either way.
- **`SaveDeliveryController`, `SaveReturnController`** — neither file was modified.
- **BUG-2, BUG-4, BUG-5** — none of their production code or fixed behavior was touched; the full P3-9/P3-10/P3-11 regression surface (see below) still passes unmodified.

## Test coverage

All in `tests/Feature/CustomerChecklists/RemoveControllerCharacterizationTest.php`:

- **Flipped:** `test_bug3_soft_deleting_checklist_questions_leaves_answer_rows_live` → `test_bug3_soft_deleting_checklist_questions_also_soft_deletes_their_answers`. Now asserts the answer row is excluded from a default query, present via `withTrashed()`, and has a non-null `deleted_at` — the parent-question and child-answer soft-delete semantics now match exactly.
- **New:** `test_bug3_no_active_answers_remain_after_removal_with_multiple_questions` — two separate checklist questions, each with its own answer, on the same order product; confirms zero active (non-trashed) rows remain among both answers and both are found soft-deleted via `withTrashed()`.
- **New:** `test_bug3_removing_one_order_products_checklist_does_not_affect_another_order_products_answers` — two order products under two *different* orders, each with its own checklist question/answer; removing only the first order's checklist leaves the second order's answer completely untouched (still findable via a default, non-trashed query) — confirms the fix is scoped correctly and doesn't touch unrelated data.

No other existing test in the file needed a behavior-assertion change — `test_delivery_fields_are_reverted_and_equipment_made_available`, both BUG-5 tests, `test_equipment_status_log_records_the_revert_to_available`, and `test_products_with_no_checklist_questions_are_left_untouched` all continue to pass unmodified, confirming equipment/media/status behavior is unaffected.

## Regression results

```
php artisan test --filter="RemoveControllerCharacterizationTest|SaveDeliveryControllerCharacterizationTest|SaveReturnControllerCharacterizationTest|ChecklistTransactionTest|CompletenessObservabilityLoggingTest|EquipmentStatus"
```
Result: **71 passed, 254 assertions, 0 failures.** Covers all four CustomerChecklists characterization suites (P3-8/P3-9/P3-10/P3-11/P3-12), the PR-A2 transaction suite, the PR-A4 observability suite, and both `EquipmentStatusService`/`EquipmentStatusLog` call-site suites.

## Files changed

- `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/RemoveController.php` (production fix — one new import, one new two-line statement)
- `tests/Feature/CustomerChecklists/RemoveControllerCharacterizationTest.php` (BUG-3 test flipped; 2 new tests added; a small shared fixture helper, `makeChecklistQuestionWithAnswer()`, factored out of the now-three tests that build the same question/answer shape)
- `docs/checklist-system-audit/PHASE3_IMPLEMENTATION_PLAN.md` (P3-12 revision note; BUG-3 marked resolved)
- `docs/checklist-system-audit/P3_12_BUG3_SOFT_DELETE_CHECKLIST_ANSWERS.md` (new — this file)

## Additional orphan scenarios found, NOT fixed in this PR (out of scope, documented per instructions)

While auditing every call site of `checklistQuestions()->delete()` to confirm `RemoveController` was the only place needing this fix, four other controllers were found with the exact same gap — a bulk `checklistQuestions()->delete()` with no matching answer cascade:

- `app/Http/Controllers/Admin/OrderManagement/Orders/AssignEquipmentController.php:71`
- `app/Http/Controllers/Admin/OrderManagement/Orders/RemoveEquipmentController.php:51`
- `app/Http/Controllers/Admin/OrderManagement/Orders/UpdateProductScheduleController.php:209` and `:250`
- `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveDeliveryController.php:121` (its own destructive-rebuild-on-a-new-cycle statement, unrelated to and unaffected by P3-10's BUG-4 guard, which only blocks *same-cycle* duplicates — a genuine new cycle still runs this exact orphan-producing delete)

None of these were touched in this PR — BUG-3's own scope, and this PR's explicit instructions, are limited to `RemoveController`. These are noted here for visibility and future backlog triage only, exactly as instructed ("document them but do not fix them in this PR").

## Verdict

**PASS.** BUG-3 is fixed in `RemoveController` with the smallest possible change — one bulk soft-delete statement added ahead of the existing one, using data already in memory from the request's initial eager load. The choice of soft (not hard) delete matches the child model's own existing `SoftDeletes` trait and an already-correct precedent in `OrderProduct`'s own cascade hooks, not a new decision. Equipment status reset, delivery reset, pickup reset (P3-11), media deletion, status logging, billing, and the transaction boundary are all unchanged and verified via 71 passing tests. BUG-2, BUG-4, BUG-5, `SaveDeliveryController`, `SaveReturnController`, API work, and architecture were not touched. Four additional instances of the same underlying gap were found in other controllers and documented, not fixed, per this PR's explicit scope. Stopping here — this was the last item in the P3-9–P3-12 bug-fix wave; not starting any later Phase 3 PR.
