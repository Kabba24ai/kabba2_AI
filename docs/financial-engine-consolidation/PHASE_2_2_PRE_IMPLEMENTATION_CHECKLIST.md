# Phase 2.2 — Pre-Implementation Checklist

Checklist date: 2026-07-01
Branch: `raj_development`
Status: **Investigation complete. This checklist reflects the validated, safe scope — not the originally assumed scope.**

---

## Why this checklist looks different from a typical "before we start" document

Per the Implementation Plan, Phase 2.2 was scoped as "migrate the three already-correct reporting engines (`SalesReportEngineV2`, `SalesTaxReportEngine`, `PaymentReconciliationLedger`) onto `TaxCalculationService`." Before writing any migration code, this phase's mandatory equivalence check (mission: "Every migrated calculation must demonstrate behavioral equivalence... If any calculation differs: STOP. Document the difference. Do not silently change behavior.") was run **against all three targets first, using synthetic numeric proofs, not just code review**. That investigation found two real issues — one fixable with zero risk, one not fixable without changing scope — and the checklist below reflects the scope *after* that investigation, not before it. See "Pre-implementation findings" below for the evidence; the Completion Report has the full detail.

## Pre-implementation findings (must be read before the Scope section makes sense)

1. **`TaxCalculationService::extractTaxFromInclusiveAmount()` (built in Phase 2.1) had a latent rounding-order bug.** It rounded `baseAmount` first, then derived `taxAmount` from the already-rounded base. The production formula (used identically today by all three reporting engines) rounds both from full-precision intermediate values. These two approaches disagree by exactly one cent on roughly 1 in 150,000 realistic amount/rate combinations (proven via a 3,000,000-combination sweep). **Because this method has zero existing callers (confirmed in the Phase 2.1 Completion Report), fixing it changes no current application behavior** — it only affects what a future caller would get, and Phase 2.2 is that future caller. The fix (round once, from full precision) was applied and re-verified with zero divergence across 5,000,000 combinations. This is documented here as a pre-implementation finding, not hidden as if it never happened — see the Completion Report for full detail and the regression test added to lock it in.
2. **A second, larger equivalence risk was found and is NOT fixed in this phase**: `SalesTaxReportEngine::accountRows()` returns per-row `subtotal`/`tax_amount` as **unrounded** floats, which are then **summed across multiple merged streams** in `Admin\Reports\SalesTax\IndexController` (lines 79-80) before being rounded once for the page's KPI totals. `TaxCalculationService`'s contract always returns rounded-to-cents values. Substituting it here would mean summing *already-rounded* per-row values instead of summing raw values and rounding once — a materially different aggregation order. A 20,000-trial simulation of realistic multi-row report totals found this discrepancy occurs in **83% of trials**, not a rare edge case, with drift up to $0.16 in the tested trials. **This is not safe to migrate in this phase** and is explicitly out of scope below.
3. **`PaymentReconciliationLedger::streamC()` does not have this problem**, because it already rounds `base_amount`/`tax_amount` to cents per-row in its current production code (`round($base, 2)`, `round($taxAmt, 2)`) before its own downstream summation in `_ledger.blade.php`. Since production already rounds per-row before summing, substituting the (now-corrected) `TaxCalculationService` introduces no aggregation-order change — it is safe to migrate.

---

## Scope

**In scope for Phase 2.2 implementation:**
- `app/Services/TaxCalculationService.php` — the Phase 2.1 rounding-order correction (finding #1 above), already applied and covered by a new regression test, prior to any reporting-engine change.
- `app/Services/Reports/SalesReportEngineV2.php` — two call sites (`queryAccountPayments()`, `queryDailyAccountPayments()`) migrated to a new, shared SQL-expression builder on `TaxCalculationService`, replacing the duplicated inline `CASE WHEN...` SQL text with a single generated source. The SQL text produced is functionally identical to what exists today.
- `app/Services/Reports/PaymentReconciliationLedger.php` — one call site (`streamC()`) migrated to call `TaxCalculationService::extractTaxFromInclusiveAmount()` instead of inlining the formula.

**Explicitly deferred, not migrated in this phase:**
- `app/Services/Reports/SalesTaxReportEngine.php` — `accountRows()` (Stream C) is **not** migrated, per finding #2 above. The existing inline formula remains untouched and unchanged. This is documented as an Outstanding Issue in the Completion Report, not silently skipped.

## Objectives

- Replace duplicated tax-extraction logic in the two safe target files with calls into the shared `TaxCalculationService`, producing provably identical output to today's implementation.
- Do not change any report's visible output, rounding, or formatting.
- Do not touch any file outside the tax-extraction call sites named above.
- Leave `SalesTaxReportEngine` completely untouched, with the reason recorded, rather than force an unsafe migration to hit a scope target.

## Files expected to change

| File | Change |
|---|---|
| `app/Services/TaxCalculationService.php` | Rounding-order correction to `extractTaxFromInclusiveAmount()`; new SQL-expression builder method added |
| `app/Services/Reports/SalesReportEngineV2.php` | Two inline `CASE WHEN` SQL blocks replaced with calls to the new shared SQL-expression builder |
| `app/Services/Reports/PaymentReconciliationLedger.php` | `streamC()`'s inline tax-extraction formula replaced with a call to `TaxCalculationService::extractTaxFromInclusiveAmount()` |
| `tests/Unit/Services/TaxCalculationServiceTest.php` | New regression test for the rounding-order fix; new tests for the SQL-expression builder |

## Files explicitly out of scope

- `app/Services/Reports/SalesTaxReportEngine.php` — deferred, see findings above. Not touched.
- `app/Http/Controllers/Admin/Dashboard/IndexController.php` — the confirmed Dashboard tax-formula bug is not in the Phase 2.2 implementation plan scope and is not touched here.
- Any invoice, ledger, balance, or payment-allocation logic (`CustomHelper.php`, `Invoice\PaymentStoreController`, `CustomerAccount\*Controller`) — untouched.
- Any database schema, migration, route, Blade view, or job/command file.
- Report *filters*, *scope*, or *query structure* beyond the specific tax-extraction expression — e.g. the store filter, date-range resolution, and payment-method mapping logic in all three engines remain exactly as they are.

## Risk level

**Low**, for the in-scope changes. Both migrated call sites are proven equivalent to their current behavior — the SQL migration is a text-centralization with no change to when/how aggregation happens; the PHP migration in `PaymentReconciliationLedger` is backed by a 5,000,000-combination equivalence sweep against the exact production formula, run after the Phase 2.1 rounding fix. The `TaxCalculationService` rounding fix itself is **zero risk** to current production behavior, since it has no existing callers.

**Not applicable / avoided**: the higher-risk path (migrating `SalesTaxReportEngine::accountRows()`) is not being attempted in this phase specifically because it could not be proven safe — this checklist reflects a scope that stayed inside the "low risk" band by design, not a scope that got lucky.

## Rollback strategy

- Each of the three changed files can be reverted independently — they do not depend on each other (the `PaymentReconciliationLedger` and `SalesReportEngineV2` changes are independent consumers of the same `TaxCalculationService` method/builder).
- `TaxCalculationService`'s rounding fix has no callers as of the start of this phase, so reverting it in isolation (before the reporting-engine changes) is also risk-free.
- No database migration, no data write, no schema change — a `git revert` of the relevant commit(s) is sufficient with no follow-up data cleanup required.

## Validation strategy

- **Numeric equivalence proof (already performed, pre-implementation):** exhaustive synthetic sweeps (millions of amount/rate combinations) comparing the corrected `TaxCalculationService` output to the exact current production formula, for both the SQL and PHP call sites being migrated. Full methodology and results in the Completion Report.
- **SQL text equivalence:** the generated SQL-expression builder's output string will be compared directly against the existing inline SQL text for semantic equivalence (ignoring only insignificant whitespace).
- **Unit tests:** new tests added to `TaxCalculationServiceTest.php` covering the rounding-order regression case and the SQL-expression builder's exact output.
- **What cannot be validated in this environment:** actual query execution against a live database (no test database is configured in this sandbox — see Phase 2.1 Completion Report's identical limitation). This will be explicitly flagged, not glossed over, in the Completion Report's Test Results section.

## Required test suites

- `tests/Unit/Services/TaxCalculationServiceTest.php` (existing + new tests) — runnable in this environment, no DB dependency.
- `tests/Unit` (full suite) — runnable, to confirm no regression to other unit tests.
- `tests/Feature/*` (any tests touching `SalesReportEngineV2`, `SalesTaxReportEngine`, `PaymentReconciliationLedger`, or the Sales Tax / Pure Sales Summary report controllers) — **expected to require a live database**; will be attempted, and the outcome (ran successfully / could not run and why) will be documented factually in the Completion Report, per the mission's explicit instruction not to claim untested things were tested.

## Expected pull request size

Small. Three application files changed (one of them a small addition to an already-small service class), one test file extended. No new files except this checklist and the eventual completion report. Estimated diff: under 150 lines of application code changes, excluding documentation.

## Success criteria

- `SalesReportEngineV2` and `PaymentReconciliationLedger` no longer contain their own independent copies of the account-payment tax-extraction formula; both call into `TaxCalculationService`.
- The generated SQL and the migrated PHP calculation are proven, via the documented equivalence methodology, to produce identical results to the pre-migration code for every tested input.
- `SalesTaxReportEngine` remains completely unchanged, with the reason formally documented rather than silently deferred.
- All runnable test suites pass; any suite that could not run in this environment is named and the reason given, not silently skipped or falsely claimed as passing.
- No file outside the explicitly listed set is touched.
