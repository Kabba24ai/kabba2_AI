# P3-12A — Complete BUG-3 soft-delete cascade coverage

**Date:** 2026-07-17
**Scope:** Phase 3, follow-up to P3-12. Completes BUG-3's cascade coverage across every remaining call site found during P3-12's audit, plus one additional exhaustive search of the whole application. No API work, no P3-6, no architecture consolidation, no unrelated refactoring.

---

## Every deletion call site found (complete application search)

A fresh, exhaustive search of `app/` (relationship deletes, query-builder deletes, model-instance deletes, `forceDelete()`, raw SQL, migrations, console commands) for anything touching `order_product_checklist_questions` or `order_product_checklist_question_answers` found **seven** call sites total — the five already known from P3-12, one already-correct model-hook pair, and one previously unexamined controller:

| # | Location | Classification |
|---|---|---|
| 1 | `RemoveController.php:109-111` | Already fixed (P3-12) — reference pattern |
| 2 | `SaveDeliveryController.php:121` | **Affected — fixed in this PR** |
| 3 | `AssignEquipmentController.php:71` | **Affected — fixed in this PR** |
| 4 | `RemoveEquipmentController.php:51` | **Affected — fixed in this PR** |
| 5 | `UpdateProductScheduleController.php:209` ('Reschedule' branch) | **Affected — fixed in this PR** |
| 6 | `UpdateProductScheduleController.php:250` ('Pending' branch) | **Affected — fixed in this PR** |
| 7 | `OrderProduct.php` `static::deleting`/`static::restoring` hooks (lines 244-248, 276-280) | Already correct — no fix needed (reference pattern; this is the precedent all fixes in this PR mirror) |
| 8 | `BulkDeleteController.php:330` (`$order->delete()`) | Already correct — cascades via `Order`→`OrderProduct` model-event chain (#7), no fix needed |

No `forceDelete()` calls, no raw SQL (`DB::statement`/`DB::delete`), and no migration data-deletion touch either table. No console command touches either table. The Rental-Ready/CustomerAdmin template-question tables (`RentalReadyChecklistQuestion`, `CustomerAdminTemplateQuestion`, etc.) are entirely separate models/tables and were confirmed out of scope.

## Classification per affected call site

### `SaveDeliveryController.php:121`
- **Why questions are deleted:** rebuilding the checklist snapshot for a new delivery submission (first-time or a legitimate new cycle, per P3-9/P3-10's guards).
- **Soft or hard:** soft (bulk relation `delete()` on a `SoftDeletes` model).
- **Can child answers exist:** yes — any prior delivery/return cycle on this order product leaves real answer rows.
- **Already deleted elsewhere:** no.
- **Model events/hooks fire:** no — bulk relation `delete()` fires no per-row events.
- **Inside a transaction:** yes — the entire method is wrapped in `DB::transaction()` (unchanged since PR-A2).
- **Cascade required:** yes.

### `AssignEquipmentController.php:71`
- **Why questions are deleted:** clearing a stale checklist snapshot from a previous equipment assignment before (optionally) rebuilding one from the newly-assigned equipment's template.
- **Soft or hard:** soft.
- **Can child answers exist:** yes — this runs on any order product being (re)assigned equipment, which may already carry a full checklist from an earlier assignment/delivery.
- **Already deleted elsewhere:** no.
- **Model events/hooks fire:** no.
- **Inside a transaction:** **no** — this controller has no `DB::transaction()`/manual transaction anywhere. Pre-existing condition, unrelated to BUG-3, not addressed by this PR (see "Additional issues found, not fixed" below).
- **Cascade required:** yes.

### `RemoveEquipmentController.php:51`
- **Why questions are deleted:** undoing an equipment assignment — a pure removal, no rebuild.
- **Soft or hard:** soft.
- **Can child answers exist:** yes — reachable on any equipment-assigned order product, including one with a fully completed delivery checklist.
- **Already deleted elsewhere:** no.
- **Model events/hooks fire:** no.
- **Inside a transaction:** **no** — same pre-existing condition as above.
- **Cascade required:** yes — this was the starkest case of the five: no rebuild follows, so the orphan was permanent, not just a brief window.

### `UpdateProductScheduleController.php:209` ('Reschedule' branch) and `:250` ('Pending' branch)
- **Why questions are deleted:** an admin manually changes a product's schedule status back to 'Reschedule' or 'Pending' from the dispatch/schedule board — both branches reset the order product to an un-delivered state, with no rebuild.
- **Soft or hard:** soft, both sites.
- **Can child answers exist:** yes, both — reachable from any prior `delivery_status` including `'Completed'`, i.e. after a real delivery checklist (and possibly a full return) was already recorded.
- **Already deleted elsewhere:** no.
- **Model events/hooks fire:** no.
- **Inside a transaction:** **no** — same pre-existing condition; the whole controller runs unwrapped.
- **Cascade required:** yes, both sites — same permanent-orphan class as `RemoveEquipmentController`.

### `OrderProduct.php` (`static::deleting`/`static::restoring`) — reference, not touched
Already correctly cascades: `$model->checklistQuestions()->get()->each(fn($q) => $q->answers()->delete())` before the bulk question delete, and the mirror `restore()` cascade. This is the precedent every fix in this PR (and P3-12) mirrors — not modified, cited only as the pattern being extended.

### `BulkDeleteController.php:330` — reference, not touched
Calls `$order->delete()` (a model-instance delete, not a query-builder mass delete), so `Order::deleting` → per-product `$product->delete()` → `OrderProduct::deleting`'s own correct cascade all fire in the normal Eloquent event chain. Already correct; no fix needed.

## Implementation per controller

All four newly-fixed sites (five call sites) use the same minimal, two-line pattern, placed immediately before the existing question soft-delete:

```php
$staleQuestionIds = $orderProduct->checklistQuestions()->pluck('id');
OrderProductChecklistQuestionAnswers::whereIn('order_product_checklist_question_id', $staleQuestionIds)->delete();

$orderProduct->checklistQuestions()->delete();
```

This differs slightly from P3-12's `RemoveController` fix (which read question IDs from an already eager-loaded `checklistQuestions.answers` collection) — here, `checklistQuestions()->pluck('id')` runs a small fresh query instead. This was a deliberate choice: `AssignEquipmentController` eager-loads `checklistQuestions.answers` at fetch time, but `RemoveEquipmentController` only eager-loads `checklistQuestions` (no `.answers`), and `UpdateProductScheduleController` doesn't eager-load either relation at all. Using a fresh `pluck('id')` query works correctly regardless of what each controller happens to eager-load, avoiding the need to audit and potentially change each controller's fetch query just to support the cascade — keeping every fix a pure two-line addition with no changes to existing eager-loading or query structure.

Each site needed one new import (`App\Models\Orders\OrderProductChecklistQuestionAnswers`) and the two-line addition; nothing else in any of the four files was changed.

## Transaction behavior

- **`SaveDeliveryController`** — the new statement runs inside the exact same `DB::transaction()` closure already covering the whole method; a failure anywhere in the request (proven by a new test, see below) rolls the new cascade back along with everything else, exactly as it already did for the question soft-delete, the `OrderProduct` update, and the equipment status transition.
- **`AssignEquipmentController`, `RemoveEquipmentController`, `UpdateProductScheduleController`** — none of these three controllers has a transaction wrapper today (confirmed during the audit — a pre-existing condition, not introduced or worsened by this PR). The two-line cascade addition runs as two consecutive, independent statements exactly like the surrounding code in each file already does; it introduces no new atomicity requirement beyond what already existed (the question soft-delete itself was already unwrapped in these three files before this PR). Adding a transaction wrapper to any of these three controllers would be a distinct, larger change — flagged below as a follow-up, not performed in this PR per its explicit "no unrelated refactoring" / "no architecture changes" scope.

## Why soft delete was chosen (same reasoning as P3-12, extended)

Every affected call site already soft-deletes the parent `OrderProductChecklistQuestion` (it uses `SoftDeletes`), and `OrderProductChecklistQuestionAnswers` already uses `SoftDeletes` too — no migration was needed anywhere. The fix is not introducing a new deletion strategy; it is extending the exact soft-delete semantics already in place for the parent down to the child, at every site that was missing it, matching the already-correct precedent in `OrderProduct`'s own cascade hooks.

## Tests added or changed

- **`tests/Feature/OrderManagement/ChecklistAnswerCascadeTest.php`** (new file, 6 tests) — covers `AssignEquipmentController`, `RemoveEquipmentController` (including a cross-order-product isolation test), and both `UpdateProductScheduleController` branches (including a second cross-order-product isolation test). Every test seeds a stale checklist question + answer directly on the order product, calls the real HTTP endpoint, and asserts the answer is excluded from a default query but recoverable via `withTrashed()` with a non-null `deleted_at` — never hard-deleted.
- **`tests/Feature/CustomerChecklists/SaveDeliveryControllerCharacterizationTest.php`** — new test `test_bug3_stale_answers_are_soft_deleted_before_checklist_rebuild_on_a_new_cycle`: builds a real first delivery cycle (with a real template/question/answer chain), closes it with a genuine return, redelivers (triggering the destructive rebuild), and confirms the *first* cycle's answer rows are now soft-deleted rather than left live.
- **`tests/Feature/CustomerChecklists/ChecklistTransactionTest.php`** — new test `test_forced_listener_failure_rolls_back_the_bug3_answer_cascade_too`: forces the same synchronous-listener failure the existing PR-A2 rollback tests use, but on a *second* delivery cycle (so the new cascade statement actually runs), and confirms the first cycle's answers are still live and unmodified after the 500 — the new cascade statement rolls back exactly like everything else in the same transaction.

No existing test in any of these files needed a behavior-assertion change beyond what P3-9/P3-10/P3-11/P3-12 already established — all of it continues to pass unmodified.

## Regression results

```
php artisan test --filter="SaveDeliveryControllerCharacterizationTest|SaveReturnControllerCharacterizationTest|RemoveControllerCharacterizationTest|ChecklistTransactionTest|CompletenessObservabilityLoggingTest|EquipmentStatus|MobileReturnCycleIdempotencyTest|EquipmentStatusLogAdditionalPathsTest|UpdateProductScheduleEquipmentStatusLogTest|ChecklistAnswerCascadeTest"
```
Result: **82 passed, 311 assertions, 0 failures.** Covers every CustomerChecklists characterization suite (P3-8 through P3-12A), the PR-A2 transaction suite (now including the new BUG-3-cascade rollback test), the PR-A4 observability suite, both `EquipmentStatusService`/`EquipmentStatusLog` call-site suites, the billing-idempotency suite, and the new `ChecklistAnswerCascadeTest` suite covering all four newly-fixed admin controllers/branches.

## Files changed

- `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveDeliveryController.php` (production fix)
- `app/Http/Controllers/Admin/OrderManagement/Orders/AssignEquipmentController.php` (production fix)
- `app/Http/Controllers/Admin/OrderManagement/Orders/RemoveEquipmentController.php` (production fix)
- `app/Http/Controllers/Admin/OrderManagement/Orders/UpdateProductScheduleController.php` (production fix, 2 call sites)
- `tests/Feature/OrderManagement/ChecklistAnswerCascadeTest.php` (new, 6 tests)
- `tests/Feature/CustomerChecklists/SaveDeliveryControllerCharacterizationTest.php` (1 new test)
- `tests/Feature/CustomerChecklists/ChecklistTransactionTest.php` (1 new test)
- `docs/checklist-system-audit/PHASE3_IMPLEMENTATION_PLAN.md` (BUG-3 marked fully resolved; P3-12A revision note)
- `docs/checklist-system-audit/P3_12A_BUG3_COMPLETE_SOFT_DELETE_CASCADE.md` (new — this file)

## Additional issues found, NOT fixed in this PR (follow-up items only)

Per this PR's explicit instructions, the following were observed during the audit but deliberately left unchanged:

- **No transaction wrapper** in `AssignEquipmentController`, `RemoveEquipmentController`, or `UpdateProductScheduleController` — a pre-existing condition affecting far more than just the checklist-answer cascade (equipment status writes, soft-assignment churn, and the order product's own field updates in these three files are all similarly unwrapped today). This is a distinct architectural gap from BUG-3 and was not introduced or worsened by this PR's two-line additions. Candidate for its own future item (e.g. extending PR-A2's transaction-wrapping discipline to these three admin controllers) — not attempted here.
- No other unrelated bugs were discovered during this audit beyond what P3-12 had already flagged.

## Is BUG-3 now fully resolved?

**Yes.** The complete application search in this PR (relationship deletes, query-builder deletes, model-instance deletes, `forceDelete()`, raw SQL, migrations, console commands) confirms all eight sites that touch `order_product_checklist_questions`/`order_product_checklist_question_answers` are now accounted for: two were already correct by inheriting Eloquent's model-event cascade (`OrderProduct`'s own hooks, and `BulkDeleteController` which relies on them), one was fixed in P3-12 (`RemoveController`), and the five remaining sites across four controllers are fixed in this PR. No affected deletion path remains.

## Verdict

**PASS.** BUG-3 is now fully resolved across the entire application, not just `RemoveController`. Every fix uses the same minimal, two-line, soft-delete-only pattern already established by P3-12 and by `OrderProduct`'s own pre-existing correct cascade — no new deletion strategy, no refactor, no consolidation into a shared helper (per this PR's explicit instruction to avoid a large refactor solely for deduplication). P3-9, P3-10, P3-11, and P3-12's behavior are all unaffected and verified via 82 passing tests, including a dedicated transaction-rollback test proving the new cascade is fully covered by `SaveDeliveryController`'s existing atomicity guarantee. One pre-existing, unrelated gap (no transaction wrapper in three admin controllers) was found and documented as a follow-up item only, not fixed. Stopping here — not starting P3-6 or any later Phase 3 work.
