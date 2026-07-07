# Phase 2.3 — Pre-Implementation Checklist

Checklist date: 2026-07-01
Branch: `raj_development`
Status: **Investigation complete. Reflects the validated fix approach, not just the naive one.**

---

## Pre-implementation finding (must be read before the Scope section makes sense)

The confirmed bug is in `Dashboard\IndexController::getRevenueRows()` (lines 837-849): for payment-type `CustomerAccount` rows, tax is computed as `amount * rate` (multiplication) instead of the correct `amount / (1 + rate)`-based extraction (division), because a payment's `amount` is already tax-inclusive. This is unchanged since the CRM Billing Audit and Phase 2.2 — confirmed by direct re-read before writing this checklist.

**The naive fix** — replacing the inline formula with a direct call to `TaxCalculationService::extractTaxFromInclusiveAmount()` and using its rounded `baseAmount` per row — was tested and found to introduce a **new** problem: `getRevenueRows()`'s output rows are grouped by date and summed (`$items->sum('grand_total')`) in `getRolling30DaysData()`, `getCurrentMonthData()`, and `getLastMonthData()`, and those daily sums are summed again into a period total (`array_sum($currentData)`). None of this currently rounds per-row or per-day — it sums at full precision, matching exactly how `SalesReportEngineV2` computes the same figure in SQL (`SUM()` over raw, unrounded per-row values). Substituting `TaxCalculationService`'s always-rounded per-row output would make Dashboard sum *pre-rounded* values instead — a 20,000-trial simulation of realistic 30-day payment volumes found this changes the final displayed period total in **84.3% of trials**. This is the identical architectural tension discovered and documented in the Phase 2.2 Completion Report for `SalesTaxReportEngine::accountRows()`, now confirmed a second time at a different call site.

**Consequence for scope:** fixing the formula direction while *also* introducing this new aggregation-order mismatch would fail the mission's own validation goal ("Dashboard totals reconcile with Sales Reports where expected") — it would trade one mismatch for a different, subtler one. To achieve genuine reconciliation, this phase adds one small, well-scoped method to `TaxCalculationService` — `extractBaseFromInclusiveAmountRaw()` — that returns the **unrounded** base amount, explicitly for aggregation contexts that must sum before rounding once at the end, matching SQL `SUM()` semantics. This mirrors exactly how Phase 2.2 added `extractTaxFromInclusiveAmountSql()` when the existing rounded method didn't fit a real, discovered need. It is not a scope expansion into redesigning the service; it is the minimum necessary correction to complete the approved fix without introducing a new bug.

---

## Scope

**In scope:**
- `app/Services/TaxCalculationService.php` — add `extractBaseFromInclusiveAmountRaw(float $amount, float $rate): float`, a small, pure, unrounded companion to the existing (rounded) `extractTaxFromInclusiveAmount()`.
- `app/Http/Controllers/Admin/Dashboard/IndexController.php` — `getRevenueRows()`'s payment-row mapping (lines ~837-849 as of this writing) changed to call the new method instead of `amount * rate`.

**Explicitly out of scope:**
- `SalesTaxReportEngine.php` — not touched, per explicit business decision (officially deferred until a future Sales Tax Report V2 project).
- The two other tax formulas in this same controller file (lines ~235-237, ~298-300, for Fuel/Damage **charge**-type rows) — these correctly use multiplication because a charge's amount is pre-tax; they are not the confirmed bug and must not be changed.
- Any invoice, ledger, payment-allocation, or customer-balance logic.
- Any other Dashboard figure not part of `getRevenueRows()`'s payment-tax calculation (delivery counts, maintenance charts, fuel/damage alert lists, team workload, etc.).

## Objectives

- Correct the confirmed formula-direction bug (multiplication → division-based extraction) for payment-type rows.
- Achieve actual reconciliation with `SalesReportEngineV2` for the same underlying data — not just "fixed direction, still off by a different amount."
- Change nothing else on the Dashboard.

## Files expected to change

| File | Change |
|---|---|
| `app/Services/TaxCalculationService.php` | Add one small new method (unrounded base-amount extraction for aggregation contexts) |
| `app/Http/Controllers/Admin/Dashboard/IndexController.php` | One call site's formula corrected |
| `tests/Unit/Services/TaxCalculationServiceTest.php` | Tests for the new method |

## Files out of scope

- `app/Services/Reports/SalesTaxReportEngine.php` — untouched, per explicit instruction.
- `app/Services/Reports/SalesReportEngineV2.php`, `app/Services/Reports/PaymentReconciliationLedger.php` — already migrated in Phase 2.2; not touched again here.
- `app/Helpers/CustomHelper.php` and all ledger/invoice/balance write paths.
- Any Blade view, route, migration, job, or command file.
- The two charge-type (Fuel/Damage) tax calculations in the same controller file, as noted above.

## Risk assessment

**Low.** The change is a single formula correction at one call site, using a new pure function with no side effects and no database access. The one thing that keeps this "low" rather than "very low" is that it's the first place this phase touches a value that feeds a customer-facing (admin-facing) revenue figure people may already be visually accustomed to — meaning the *correct* new numbers will visibly differ from the *incorrect* numbers users have been seeing, which is expected and desired, but should be validated with concrete before/after figures, not just declared safe by formula inspection.

## Validation strategy

- **Numeric equivalence proof for the new method:** unit tests confirming `extractBaseFromInclusiveAmountRaw()` matches the exact raw-division formula already used identically by `SalesReportEngineV2`, `SalesTaxReportEngine`, and `PaymentReconciliationLedger`.
- **Aggregation-safety proof:** the same 20,000-trial-style simulation used to discover the problem will be re-run against the *fixed* code path (sum of raw/unrounded per-row values) to confirm **zero** divergence from the "sum raw, round once" pattern that `SalesReportEngineV2` uses — i.e., prove the fix doesn't reintroduce the aggregation-order problem it was specifically designed to avoid.
- **Before/after worked example:** a concrete numeric example (same shape as the original $150/$136.67/$13.33 case) showing the old (wrong) Dashboard output vs. the new (correct) output for a representative payment.
- **What cannot be validated in this environment:** an actual side-by-side run of the live Dashboard page against the live Sales Report page against real production data, since no test database is available in this sandbox (same limitation noted in Phases 2.1 and 2.2). This will be stated plainly in the Completion Report.

## Rollback strategy

- `app/Http/Controllers/Admin/Dashboard/IndexController.php`: revert the single formula change; the file returns to its previous (buggy but stable) state with zero data-migration implications, since this is a read-only display/reporting calculation with no persisted side effects.
- `app/Services/TaxCalculationService.php`: the new method can be removed independently; it has no other callers introduced in this phase.
- No database write, schema change, or persisted data is touched anywhere in this phase — rollback is a pure code revert.

## Expected PR size

Very small. Two application files touched (one a small, additive method; one a single-formula correction), one test file extended. Estimated diff: under 40 lines of application code, excluding documentation.

## Success criteria

- The confirmed formula-direction bug is corrected.
- The corrected Dashboard payment-tax figure is proven, via the documented aggregation-safety simulation, to sum identically (raw-value-sum, round-once) to how `SalesReportEngineV2` computes the same figure — genuine reconciliation, not just a formula-direction fix that leaves a residual, subtler mismatch.
- `SalesTaxReportEngine.php` has zero diff.
- The two correct (charge-type) tax formulas in the same file are unchanged.
- No other Dashboard value changes.
- All runnable tests pass; any suite that cannot run in this environment is named with a reason, not silently skipped or falsely claimed as passing.
