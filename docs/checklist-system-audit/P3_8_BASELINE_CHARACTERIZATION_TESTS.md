# P3-8 — Baseline characterization tests for SaveDeliveryController / SaveReturnController / RemoveController

**Date:** 2026-07-17
**Scope:** PR order item 8 from `PHASE3_IMPLEMENTATION_PLAN.md` §4 — "Characterization tests for `SaveDeliveryController`/`SaveReturnController`/`RemoveController`," the explicit prerequisite for P3-9 through P3-12 (the delivery/return bug fixes) and later ARCH-2 (delivered/returned write-path consolidation). No production code was changed in this PR — tests only.

---

## Why this PR exists

Before this PR, these three controllers had exactly two dedicated test files:

- `tests/Feature/CustomerChecklists/ChecklistTransactionTest.php` — PR-A2's transaction-rollback guarantee (happy path + forced-listener-failure rollback), one test per controller plus a nested-transaction billing case.
- `tests/Feature/CustomerChecklists/CompletenessObservabilityLoggingTest.php` — PR-A4's observability-only logging behavior (missing signature / unanswered required question / omitted checklist).

Neither file exercises the controllers' other response branches (not-found, conflict, already-submitted, no-questions-found), their damage/fuel billing side effects, or the specific branches that BUG-2, BUG-3, BUG-4, and BUG-5 (all already documented in `PHASE3_IMPLEMENTATION_PLAN.md`) depend on. Writing P3-9 through P3-12's fixes without this baseline would mean the first regression-catcher for those fixes is the fix's own PR — no safety net proving the bug actually existed beforehand, and no way to confirm afterward that the fix changed the *intended* behavior without changing anything else.

## What was added

Three new test files, none of which change any production code:

### `tests/Feature/CustomerChecklists/SaveDeliveryControllerCharacterizationTest.php` (9 tests)

- Not-found (order product's parent order soft-deleted) and conflict (equipment already rented) response branches.
- Checklist-submitted-but-no-template / template-has-zero-questions branches.
- New-equipment-assignment happy path: confirms `markRented()` fires and an `equipment_status_logs` row is written.
- **BUG-2** (pinned, not fixed): re-delivering the *same* already-assigned equipment does not call `markRented()` again — proven by forcing the equipment into a stale `'damaged'` status between two delivery calls and confirming it survives the second call untouched, with no new status-log row.
- **BUG-4** (pinned, not fixed): a second delivery submission on the same order product (equipment cycled out of `'rented'` between calls, exactly as BUG-2's scenario requires to even reach this code path) destructively deletes and recreates the entire checklist-question/answer snapshot, with no guard blocking the resubmission.
- Signature-media upload and `EquipmentSoftAssign` row cleanup (existing, unremarkable behavior).

### `tests/Feature/CustomerChecklists/SaveReturnControllerCharacterizationTest.php` (9 tests)

- Not-found / equipment-missing / equipment-not-rented / already-submitted (409) response branches, and the no-prior-checklist-questions branch.
- Damaged-return-answer path: confirms a `BillingCharge` row is created, `damage_status` becomes `'pending'`, and the equipment transitions to `'damaged'`.
- Non-damaged-return path: confirms no charge is created and the equipment transitions to `'maintenance'`.
- Fuel-charge bridge: confirms the legacy `ChargeService` + `BillingEngine` bridge fires and produces a `BillingCharge` row.
- Signature-media upload (existing, unremarkable behavior).

### `tests/Feature/CustomerChecklists/RemoveControllerCharacterizationTest.php` (7 tests)

- Not-found (order unique_id invalid at the request layer, and order soft-deleted so the controller's own query excludes it) response branches.
- Products with zero `checklistQuestions` rows are skipped by the loop entirely — equipment/status untouched.
- Happy path: delivery-side fields reset to `'Pending'`/`null`, equipment reverted to `'available'` via `markAvailableOnChecklistRemove()`, with an `equipment_status_logs` row confirming it.
- **BUG-5** (pinned, not fixed — and its write-up in `PHASE3_IMPLEMENTATION_PLAN.md` corrected, see below): after a product that was already returned gets its checklist removed, `is_returned` is explicitly reset to `false`, but `pickup_status` (still `'Completed'`) and `pickup_by` are left completely untouched — a self-contradictory end state.
- **BUG-3** (pinned, not fixed): soft-deleting a product's `checklistQuestions` rows leaves their child `OrderProductChecklistQuestionAnswers` rows fully live (`deleted_at IS NULL`), still flagged `is_delivery_answer=true`, orphaned under a parent no longer in the active (non-trashed) checklist.

## A correction found while writing these tests

`PHASE3_IMPLEMENTATION_PLAN.md`'s original BUG-5 write-up stated `RemoveController` "resets only `delivery_*`/`is_delivered`-family fields, leaving `pickup_*`/`is_returned` untouched." Writing a real HTTP characterization test against the actual controller code (rather than re-reading the description) showed this is not quite accurate: the controller's `$orderProductData` array does explicitly include `'is_returned' => false`. The real, narrower gap is that `pickup_status`, `pickup_by`, and every other `pickup_*`/`damage_status` field are left untouched — producing an even more self-contradictory state (`is_returned=false` next to `pickup_status='Completed'`) than a simple "untouched" field would. `PHASE3_IMPLEMENTATION_PLAN.md`'s BUG-5 entry has been corrected accordingly (with the correction itself left visible in the entry, not silently overwritten), and this file's test is named to match the corrected understanding: `test_bug5_pickup_status_and_pickup_by_are_not_reverted_even_though_is_returned_is`.

## A fixture-writing gotcha worth recording

Several early drafts of the BUG-2/BUG-4/409-guard tests failed with unexpected status codes (409 instead of an expected 200, or 403 instead of an expected 409) because of a subtle Eloquent pitfall: reusing a PHP model instance created *before* an HTTP call, then setting an attribute back to its own original creation-time value and calling `saveQuietly()`, is a no-op — Eloquent's dirty-tracking compares against that instance's own `$original` snapshot, not the database's actual current row, so a value that matches what the object was *created* with is never marked dirty and the `UPDATE` is skipped entirely (even though the row had since changed via the controller's own write in an intervening HTTP call). The fix is to call `->refresh()` on the model immediately before reassigning an attribute that a prior request may have changed, whenever the test needs to force a specific pre-state between two calls in the same test method.

## Test results

```
php artisan test --filter="SaveDeliveryControllerCharacterizationTest|SaveReturnControllerCharacterizationTest|RemoveControllerCharacterizationTest|ChecklistTransactionTest|CompletenessObservabilityLoggingTest"
```

Result: **41 passed, 174 assertions, 0 failures** (9 + 9 + 7 new characterization tests, plus the pre-existing 8 `ChecklistTransactionTest` + 8 `CompletenessObservabilityLoggingTest` tests, confirmed still green and undisturbed).

Full application regression suite (`php artisan test`, no filter) also run to confirm no unrelated breakage — see the PR report for the final count.

## Files changed

- `tests/Feature/CustomerChecklists/SaveDeliveryControllerCharacterizationTest.php` (new, 9 tests)
- `tests/Feature/CustomerChecklists/SaveReturnControllerCharacterizationTest.php` (new, 9 tests)
- `tests/Feature/CustomerChecklists/RemoveControllerCharacterizationTest.php` (new, 7 tests)
- `docs/checklist-system-audit/PHASE3_IMPLEMENTATION_PLAN.md` (BUG-5 entry corrected; P3-8 revision note added)
- `docs/checklist-system-audit/P3_8_BASELINE_CHARACTERIZATION_TESTS.md` (new — this file)

No production code, migrations, or schema changes. BUG-2, BUG-3, BUG-4, and BUG-5 remain **not fixed** — this PR only makes their current behavior explicit and regression-testable, per its own stated scope (baseline tests, not fixes).

## Verdict

**PASS.** All three controllers now have baseline HTTP-level characterization coverage for their main response branches and known-but-unfixed defects. P3-9 (BUG-2), P3-10 (BUG-4), P3-11 (BUG-5, pending its own product decision), and P3-12 (BUG-3) each have an existing test that currently asserts the *buggy* behavior — flipping that assertion to the *fixed* behavior is the acceptance criterion for each of those future PRs. No behavior changed in this PR; no other Sprint 2/3 item started.
