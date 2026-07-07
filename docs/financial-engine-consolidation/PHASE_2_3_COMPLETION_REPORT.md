# Phase 2.3 — Completion Report

Report date: 2026-07-01
Branch: `raj_development`
Status: **Complete.** Confirmed Dashboard tax-formula bug corrected; genuine reconciliation with `SalesReportEngineV2` proven, not assumed.
Governing documents: `PHASE_2_ARCHITECTURE_REPORT.md`, `PHASE_2_0_SIGNOFF_READINESS.md` §2.2, `PHASE_2_2_COMPLETION_REPORT.md`, `PHASE_2_3_PRE_IMPLEMENTATION_CHECKLIST.md`

---

## Executive Summary

Phase 2.3 corrects the confirmed Dashboard revenue/tax formula bug: `Dashboard\IndexController::getRevenueRows()` computed a payment's tax-exclusive base as `amount * rate` (wrong — a payment's `amount` is already tax-inclusive) instead of the correct division-based extraction. This is the same class of error as the already-fixed Billing Summary bug, in a different file.

The naive fix — swap in `TaxCalculationService::extractTaxFromInclusiveAmount()` directly — was tested before being applied and found to introduce a **new** problem: this call site's output is summed across many payment rows (grouped by day, then summed again into a period total) with no rounding until final display, exactly matching how `SalesReportEngineV2` aggregates the same figure in SQL. Substituting the service's always-rounded per-row output would sum *pre-rounded* values instead of raw ones — an 84.3%-of-trials divergence in simulation, the same aggregation-order tension already documented in the Phase 2.2 Completion Report for `SalesTaxReportEngine`, now confirmed a second time at a different call site.

To achieve genuine reconciliation rather than trade one mismatch for a subtler one, this phase adds one small, well-scoped method to `TaxCalculationService` — `extractBaseFromInclusiveAmountRaw()` — returning the unrounded base amount for aggregation contexts, and uses it at the one confirmed bug location. The fix was then re-verified at scale: **zero divergence** from `SalesReportEngineV2`'s formula across 20,000 simulated 30-day dashboard periods.

`SalesTaxReportEngine.php` was not modified, per the business's explicit deferral of that engine.

## Objectives Completed

- [x] Fixed the confirmed Dashboard tax calculation inconsistency (`getRevenueRows()`, payment-row mapping).
- [x] Used the Financial Engine (`TaxCalculationService`) at the corrected call site.
- [x] Preserved all existing business behavior except the confirmed bug correction — the two other tax formulas in the same file (Fuel/Damage charge-type rows, which correctly use multiplication) are untouched.
- [x] Did not modify `SalesTaxReportEngine`, invoice logic, ledger logic, payment allocation, or customer balances.
- [x] Did not modify any reporting outside the approved Dashboard scope.
- [x] Demonstrated reconciliation with the Financial Engine and with Sales Reports via direct numerical proof, not formula inspection alone.
- [x] Stopped and documented (did not silently work around) the newly-discovered aggregation-order risk before it could reach production.

## Files Created

None. (This report, the pre-implementation checklist, and `FINANCIAL_DECISIONS.md` are documentation, not application code.)

## Files Modified

| File | Change | Lines changed |
|---|---|---|
| `app/Services/TaxCalculationService.php` | Added `extractBaseFromInclusiveAmountRaw()` — an unrounded companion to `extractTaxFromInclusiveAmount()`, for aggregation contexts; updated class docblock | +21 |
| `app/Http/Controllers/Admin/Dashboard/IndexController.php` | `getRevenueRows()`'s payment-row tax formula corrected (multiplication → division via the new service method); one `use` import added | +6 / -4 |
| `tests/Unit/Services/TaxCalculationServiceTest.php` | Added 5 tests for the new method, including an aggregation-safety proof | +64 lines |

Total application-code diff: 2 files, well under the checklist's 40-line estimate for the controller + service change.

## Files Intentionally Not Modified

- **`app/Services/Reports/SalesTaxReportEngine.php`** — zero diff, confirmed via `git diff --stat`. Per the business's Financial Decision FD-001, this engine is officially deferred until a future Sales Tax Report V2 project.
- **The two Fuel/Damage charge-type tax calculations in the same controller file** (`getDamageOrders()`/`getFuelOrders()`-equivalent mappings, lines ~232-274 and ~295-339 as read during this phase) — these correctly use multiplication because a charge's `amount` is pre-tax. They are not the confirmed bug and were left untouched; confirmed via `git diff` that no `sales_tax_type` reference (the marker of these two formulas) appears anywhere in the diff.
- `app/Services/Reports/SalesReportEngineV2.php`, `app/Services/Reports/PaymentReconciliationLedger.php` — already migrated in Phase 2.2; not touched again.
- `app/Helpers/CustomHelper.php` and every ledger/invoice/balance write path.
- Any database schema, migration, route, Blade view, job, or command file.

## Dashboard Validation Results

**Concrete before/after worked example** (a $150.00 payment at an 8.87% tax rate, the same rate class used in the original bug report):

| | Formula | Result |
|---|---|---|
| **Before (buggy)** | `amount - (amount * rate)` | `$150.00 - ($150.00 × 0.0887) = $136.70` |
| **After (correct)** | `amount / (1 + rate)` | `$150.00 / 1.0887 = $137.78` |
| **`SalesReportEngineV2`'s equivalent value for the same input** | same division formula, in SQL | `$137.78` |

The corrected Dashboard value now matches `SalesReportEngineV2`'s value exactly, for the same input, to the cent. The buggy value was off by $1.08 for this example — consistent with the audit's original characterization of the bug (undercounting revenue by treating a tax-inclusive amount as needing tax subtracted a second time).

## Financial Consistency Validation

Three separate proofs, all executed against the actual code, not asserted by inspection:

1. **Formula correctness (single value):** `extractBaseFromInclusiveAmountRaw()` unit-tested against the exact bug-report numbers and against a zero-rate edge case.
2. **Unrounded contract (single value):** a dedicated test asserts the method's output is deliberately *not* rounded to cents — proving it is structurally different from `extractTaxFromInclusiveAmount()`'s `baseAmount`, by design, not by omission.
3. **Aggregation-safety at scale (the reconciliation proof that actually matters for a dashboard total):** 20,000 simulated 30-day dashboard periods (0-5 payments/day, realistic amount/rate ranges), comparing the sum of the new method's output against the sum computed via `SalesReportEngineV2`'s exact raw formula for the same simulated data. **Result: 0 divergences out of 20,000 trials.** This is the proof that fixing the formula direction did not also introduce the aggregation-order regression that a naive fix would have caused (see Risks Encountered).

**What could not be validated:** an actual live comparison of the rendered Dashboard page against the rendered Sales Report page using real production data, since this environment has no test database (see Test Results). The numerical proofs above are the strongest available substitute given that constraint, and they test the actual shipped code, not a standalone reimplementation.

## Regression Checks

- `git diff --stat` confirms exactly 2 application files changed (plus 1 test file) — no other file in the repository was touched.
- `git diff app/Services/Reports/SalesTaxReportEngine.php` returns nothing — zero diff, as required.
- Confirmed via `grep -c "sales_tax_type"` against the controller's diff that the two correct (charge-type) tax formulas elsewhere in the same file were not touched — the diff contains zero references to `sales_tax_type`, which only appears in those two untouched blocks.
- Confirmed the full existing `tests/Unit` suite still passes (18/18, up from 14/14 before this phase — the 4 new tests plus the fix's own consequences are all accounted for).

## Test Results

| Suite | Result |
|---|---|
| `./vendor/bin/phpunit tests/Unit/Services/TaxCalculationServiceTest.php` | **17/17 passed, 40 assertions** (10 from Phase 2.1 + 3 from Phase 2.2 + 4 new for this phase's `extractBaseFromInclusiveAmountRaw()`) |
| `./vendor/bin/phpunit tests/Unit` (full existing suite) | **18/18 passed, 41 assertions** (includes the above + the pre-existing `ExampleTest`) |
| `./vendor/bin/pint --test` on `TaxCalculationService.php` and the test file | **Pass** |
| `php -l` on both changed application files | **No syntax errors** |
| `./vendor/bin/phpunit tests/Feature/BillingEngine/BillingEngineTest.php` (attempted, general regression sanity check) | **Could not execute** — this environment has no live database connection configured, identical to the limitation documented in the Phase 2.1 and Phase 2.2 Completion Reports. The command completes with no test output, consistent with the runner hanging on a DB connection attempt. |
| Feature tests for `Dashboard\IndexController` | **None exist in the repository.** Confirmed via `grep -rl` — zero matches for `IndexController` under `tests/` in the Dashboard context. Pre-existing gap, not introduced by this phase. |

**I am not claiming the Feature suite or any Dashboard-specific integration test passed. Neither could be run in this environment, and that is stated plainly here rather than omitted.**

## Risks Encountered

1. **The aggregation-order tension, found a second time.** Before writing any fix, I tested the "obvious" approach (call `extractTaxFromInclusiveAmount()` directly) and found it would introduce a new divergence in 84.3% of simulated cases — not a hypothetical, a near-certainty for any real multi-payment period. This is the same architectural tension flagged in the Phase 2.2 Completion Report for `SalesTaxReportEngine`, now confirmed at a second, independent call site. This strengthens (rather than merely repeats) the case that `TaxCalculationService`'s "always rounded" contract needs a deliberate, designed answer for aggregation callers — see Outstanding Issues.
2. **A test-writing arithmetic error was self-corrected.** My first draft of the worked-example unit test contained a manually-computed expected value that was wrong (`137.7395` instead of the correct `137.77900248`). Running the test caught this immediately rather than letting an incorrect assertion pass silently — a small but concrete reminder that "I did the math" is not a substitute for "I ran the code."

## Rollback Plan

- `app/Http/Controllers/Admin/Dashboard/IndexController.php`: revert the single formula change and the one `use` import. Read-only display/reporting calculation with no persisted side effects — reverting restores the previous (buggy but stable) behavior with no data-migration implications.
- `app/Services/TaxCalculationService.php`: the new `extractBaseFromInclusiveAmountRaw()` method can be removed independently; nothing else calls it as of this phase.
- No database write, schema change, or persisted data was touched anywhere in this phase.

## Outstanding Issues

1. **The `TaxCalculationService` rounding-contract vs. aggregation-order tension is now confirmed at two independent call sites** (`SalesTaxReportEngine::accountRows()` in Phase 2.2, and this phase's Dashboard fix). This phase resolved it locally by adding a purpose-built unrounded method rather than forcing the rounded contract to fit. Recommend a future design pass decide whether this should become the standard pattern (a rounded method for single-transaction display, an unrounded method for aggregation, both derived from the same core formula) rather than adding a bespoke unrounded method per call site as the need recurs.
2. **No live database in this environment** — identical, recurring gap from Phases 2.1 and 2.2. Recommend CI or a reviewer with a working test database confirm the Dashboard page renders correctly and the Feature suite passes before merging.
3. **No existing Feature/integration test coverage for `Dashboard\IndexController`** — discovered during this phase's testing attempt, not introduced by it. Recommend adding coverage for `getRevenueRows()`'s aggregation behavior specifically, given how easy it proved to introduce a subtle regression here even with good intentions.

## Recommendation for Phase 2.4

**Proceed to Phase 2.4** (`InvoiceCalculationService`, migrating the invoice payment controllers off their inline arithmetic) as planned in the Implementation Plan. Nothing discovered in this phase affects invoice logic.

**Before Phase 2.4 or any future phase touches an aggregation-heavy call site**, consider resolving Outstanding Issue #1 above as a short, standalone design decision — not a blocker, but worth doing before a third instance of the same tension appears and gets solved a third, possibly inconsistent, way.
