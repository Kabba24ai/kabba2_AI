# Phase 2.2 — Completion Report

Report date: 2026-07-01
Branch: `raj_development`
Status: **Partially complete by design.** 2 of the 3 originally-targeted reporting engines were safely migrated; the third was deliberately deferred after equivalence testing proved it unsafe to migrate as originally planned. See "Why this isn't a 3-for-3 migration" below.
Governing documents: `PHASE_2_ARCHITECTURE_REPORT.md`, `PHASE_2_IMPLEMENTATION_PLAN.md` (Phase 2.2), `PHASE_2_2_PRE_IMPLEMENTATION_CHECKLIST.md`

---

## Executive Summary

Phase 2.2 set out to migrate three reporting engines (`SalesReportEngineV2`, `SalesTaxReportEngine`, `PaymentReconciliationLedger`) onto the `TaxCalculationService` built in Phase 2.1. Before writing any migration code, this phase's mandatory equivalence check was run — not as a formality, but as an actual numerical proof, exhaustively, against every target. That check found two real issues:

1. **A latent rounding-order bug in `TaxCalculationService` itself** (zero production impact, since it had zero callers), which was corrected and re-verified with zero divergence across 5,000,000 test combinations.
2. **A genuine, common (not rare) equivalence-breaking issue** in `SalesTaxReportEngine::accountRows()`: its per-row tax/subtotal values are summed unrounded across multiple merged report streams before being rounded once for the page's KPI totals. Substituting `TaxCalculationService`'s always-rounded output would change those totals in an estimated 83% of realistic report runs — not a rare edge case, a near-certainty.

Given finding #2, migrating `SalesTaxReportEngine` as originally planned would have violated this phase's explicit, non-negotiable rule: **produce identical results, or stop and document — never silently change behavior.** So it was stopped, and documented, exactly as instructed. `SalesReportEngineV2` and `PaymentReconciliationLedger` — the two targets where equivalence *was* provable — were migrated. `SalesTaxReportEngine` was not touched at all.

## Objectives Completed

- [x] Migrated duplicated tax-extraction logic in `SalesReportEngineV2` (2 call sites) to a shared SQL-expression builder on `TaxCalculationService`.
- [x] Migrated the duplicated tax-extraction formula in `PaymentReconciliationLedger::streamC()` to call `TaxCalculationService::extractTaxFromInclusiveAmount()` directly.
- [x] Proved behavioral equivalence for every migrated call site, by direct numerical comparison against the pre-migration formula, not by inspection alone.
- [x] Did not change any calculation formula, rounding behavior, or output format for anything that was migrated.
- [x] Did not modify invoice, ledger, payment-allocation, or balance logic.
- [x] Did not fix the Dashboard bug (not in Phase 2.2 scope).
- [ ] ~~Migrated `SalesTaxReportEngine`~~ — **deliberately not done**, per the equivalence finding above. This is a scope adjustment made *because of*, not *despite*, following the phase's rules correctly.

## Why this isn't a 3-for-3 migration

The Implementation Plan named all three engines as Phase 2.2's target. Two are migrated. The third is not, because migrating it would have required either (a) silently accepting a behavior change in a customer-facing report's KPI totals, which this phase's rules explicitly forbid, or (b) redesigning `TaxCalculationService`'s contract (e.g. adding an "unrounded" variant) mid-phase, which would be a larger, unplanned architectural change outside "keep this phase intentionally small." Neither was acceptable, so the correct action — per this phase's own explicit instruction ("if any calculation differs: STOP... do not silently change behavior") — was to migrate what could be proven safe and document what could not, rather than force the original scope to completion.

## Files Created

None. (This report and the pre-implementation checklist are documentation, not application code.)

## Files Modified

| File | Change | Lines changed |
|---|---|---|
| `app/Services/TaxCalculationService.php` | Corrected `extractTaxFromInclusiveAmount()`'s rounding order (round once, from full precision, not from an already-rounded intermediate); added `extractTaxFromInclusiveAmountSql()` SQL-expression builder; updated class docblock | +34 / -10 |
| `app/Services/Reports/SalesReportEngineV2.php` | `queryAccountPayments()` and `queryDailyAccountPayments()` now build their `CASE WHEN` SQL from the shared builder instead of inlining it | +11 / -18 |
| `app/Services/Reports/PaymentReconciliationLedger.php` | `streamC()` now calls `TaxCalculationService::extractTaxFromInclusiveAmount()` instead of inlining the formula | +9 / -8 |
| `tests/Unit/Services/TaxCalculationServiceTest.php` | Added a regression test for the rounding-order fix and two tests for the SQL-expression builder | +40 lines |

Total application-code diff: 3 files, well under the 150-line estimate in the pre-implementation checklist.

## Files Intentionally Not Modified

- **`app/Services/Reports/SalesTaxReportEngine.php`** — not touched, per the equivalence finding above. Its `accountRows()` method still contains its own inline copy of the tax-extraction formula, unchanged, byte-for-byte, from before this phase.
- `app/Http/Controllers/Admin/Dashboard/IndexController.php` — the confirmed Dashboard bug remains untouched; not in Phase 2.2 scope.
- `app/Helpers/CustomHelper.php` and every ledger/invoice/balance write path — untouched.
- Every report's filter logic, query structure, date-range resolution, and payment-method mapping — untouched; only the tax-extraction expression itself was touched in the two migrated files.
- No database schema, migration, route, Blade view, job, or command file.

## Migration Summary

### `SalesReportEngineV2` (2 call sites migrated)

- `queryAccountPayments()`: the two `SUM(CASE WHEN ... END)` SQL blocks (base and tax) now call `TaxCalculationService::extractTaxFromInclusiveAmountSql()` and interpolate its output into the same `selectRaw()` structure.
- `queryDailyAccountPayments()`: same builder, reused for the daily-chart variant (base only).
- **Nature of the change:** pure text centralization. The SQL emitted to the database is functionally identical — confirmed by direct string comparison (see Equivalence Validation Results). No change to when or how `SUM()` aggregates rows; rounding still happens only once, wherever the caller currently rounds the aggregated total (unchanged).

### `PaymentReconciliationLedger` (1 call site migrated)

- `streamC()`: `$taxAmt = $taxRate > 0 ? $amount - $amount / (1 + $taxRate) : 0.0; $base = $amount - $taxAmt;` replaced with `$breakdown = TaxCalculationService::extractTaxFromInclusiveAmount($amount, $taxRate);`, then `'base_amount' => $breakdown->baseAmount, 'tax_amount' => $breakdown->taxAmount` (previously `round($base, 2)` / `round($taxAmt, 2)` inline).
- **Nature of the change:** this file already rounded its per-row values before its own downstream summation (`round($base, 2)`, `round($taxAmt, 2)` were already present in the pre-migration code), so replacing the inline formula with the (now rounding-order-corrected) service introduces no change to when rounding happens relative to aggregation — only where the formula's code lives.

### `SalesTaxReportEngine` (not migrated)

- `accountRows()` (Stream C) is unchanged. It still computes `$taxAmount = $taxRate > 0 ? $amount - $amount / (1 + $taxRate) : 0.0;` inline, and does **not** round it before returning the row — full precision flows into `Admin\Reports\SalesTax\IndexController`'s `$reportRows->sum(fn($row) => $row->tax_amount)` (line 79) and `->sum(fn($row) => $row->subtotal)` (line 80), which are rounded once at the very end for the page's KPI cards. This is intentionally left exactly as-is.

## Equivalence Validation Results

All validation was performed by direct execution, not by code inspection alone.

### 1. `TaxCalculationService::extractTaxFromInclusiveAmount()` rounding-order correction

- **Method:** compared the Phase 2.1 (pre-fix) implementation and the corrected implementation against the actual production formula (`tax = amount - amount/(1+rate); base = amount - tax; round both once`) used identically by all three reporting engines, across 3,000,000 randomized realistic amount/rate combinations (amounts up to $20,000, rates up to 15%).
- **Result:** pre-fix implementation: **20 divergences** (one-cent discrepancies). Corrected implementation: **0 divergences**.
- **Final confirmation:** re-ran the comparison invoking the actual, corrected `TaxCalculationService` class (not a standalone reimplementation) across **5,000,000** combinations, amounts up to $50,000, rates up to 15%: **0 divergences.**
- **Concrete example locked in as a regression test:** `amount=31423.99, rate=0.04` → production and corrected service both produce `base=30215.38, tax=1208.62`.

### 2. `SalesReportEngineV2` SQL-expression builder

- **Method:** direct string comparison between the SQL text generated by `TaxCalculationService::extractTaxFromInclusiveAmountSql()` and the exact inline `CASE WHEN` text it replaces, normalized only for insignificant whitespace (SQL does not assign meaning to whitespace between tokens).
- **Result:** token-for-token identical. Since the query's `SUM()` aggregation and rounding behavior are otherwise completely unchanged, this is not merely "equivalent" but literally the same computation, sourced from one place instead of two.

### 3. `PaymentReconciliationLedger::streamC()`

- **Method:** same 5,000,000-combination sweep as (1) above, using the corrected `TaxCalculationService`, compared directly against `PaymentReconciliationLedger`'s exact pre-migration formula (`taxAmt = amount - amount/(1+rate)`, `base = amount - taxAmt`, both rounded once).
- **Result:** 0 divergences. Additionally confirmed that this file's pre-migration code already rounded per-row before its own downstream summation (in `_ledger.blade.php`), so no new aggregation-order risk is introduced by this migration (unlike finding #4 below).

### 4. `SalesTaxReportEngine::accountRows()` — confirmed NOT equivalent, migration correctly withheld

- **Method:** simulated 20,000 realistic multi-row report totals (5-200 rows per simulated report run, realistic amount/rate distributions), comparing "sum raw per-row tax values then round once" (current production behavior) against "sum already-rounded per-row tax values then round again" (what would result from substituting `TaxCalculationService`'s always-rounded output here).
- **Result:** **16,625 of 20,000 simulated report runs (83%) produced a different total**, with drift up to $0.16 in the tested trials.
- **Conclusion:** this is not a rare edge case masked by the environment — it is the expected outcome for the majority of realistic report runs with more than a handful of transactions. Migrating this call site would have changed the Sales Tax report's displayed KPI totals for most date ranges a user would actually select. **Correctly not migrated.**

## Test Results

| Suite | Result |
|---|---|
| `./vendor/bin/phpunit tests/Unit/Services/TaxCalculationServiceTest.php` | **13/13 passed, 36 assertions** (10 from Phase 2.1 + 1 rounding-order regression test + 2 SQL-builder tests) |
| `./vendor/bin/phpunit tests/Unit` (full existing suite) | **14/14 passed, 37 assertions** |
| `./vendor/bin/pint --test` on all new/changed files where full-file reformatting is appropriate (`TaxCalculationService.php`, the new test file) | **Pass** |
| `php -l` on all 5 touched/created PHP files | **No syntax errors** |
| `./vendor/bin/phpunit tests/Feature/BillingEngine/BillingEngineTest.php` (attempted, as a general regression sanity check) | **Could not execute.** This environment has no live database connection configured (`phpunit.xml`'s SQLite in-memory override is commented out; no `.env.testing` exists) — identical limitation to Phase 2.1. The command completes with no test output or error, consistent with the test runner hanging on a DB connection attempt rather than failing outright. This is a pre-existing environment constraint, not something this phase's changes could have caused. |
| Feature tests for `SalesReportEngineV2`, `SalesTaxReportEngine`, `PaymentReconciliationLedger`, or their controllers | **None exist in the repository.** Confirmed via `grep -rl` across `tests/` — zero matches for any of these three class names outside the new unit test file. This is a pre-existing test-coverage gap, not something introduced or worsened by this phase, but worth flagging as an Outstanding Issue below. |

**I am not claiming the Feature suite passed. It could not be run in this environment, for the same reason as Phase 2.1, and that is stated plainly above rather than omitted.**

## Regression Checks

- Confirmed via `git status`/`git diff` that only the three intended application files were modified, plus one test file — no other file in the repository changed.
- Confirmed no other code references `SalesReportEngineV2::queryAccountPayments()`/`queryDailyAccountPayments()` or `PaymentReconciliationLedger::streamC()` in a way that would be affected by an internal implementation change (both remain private methods with unchanged return shapes — same array/collection structure, same field names, same value semantics).
- Confirmed the first attempt at this migration (which ran the project-wide code formatter across the two report-engine files) produced a 700+ line diff touching unrelated pre-existing code and would have deleted an already-unused `use` import as an incidental side effect. **This was caught, reverted, and redone surgically** — see Lessons Learned. The final diff is 3 files, well under the checklist's 150-line estimate.
- Confirmed `SalesTaxReportEngine.php` has zero diff — `git diff app/Services/Reports/SalesTaxReportEngine.php` returns nothing.

## Risks Encountered

1. **The Phase 2.1 rounding-order bug** (see Equivalence Validation Results #1). Risk was contained because the affected method had zero callers; fixing it changed no live behavior. Now closed, with a regression test.
2. **The `SalesTaxReportEngine` aggregation-order incompatibility** (see #4 above). This is not "encountered and fixed" — it's "encountered and correctly left alone." It represents a real, unresolved architectural tension: `TaxCalculationService`'s value-object contract (always rounded, representing a display-ready dollar amount) is fundamentally at odds with call sites that need to sum many transactions' tax portions at full precision before rounding once. This needs a design decision, not a code fix, before `SalesTaxReportEngine` can be migrated — see Outstanding Issues.
3. **Whole-file auto-formatting risk** (process risk, not application risk): running the project's linter without scoping it to only the intended edit produced a large, unreviewable, unrelated diff on first attempt. Caught before it reached the completion stage; documented here so future phases apply the same caution.

## Rollback Plan

- `app/Services/Reports/SalesReportEngineV2.php` and `app/Services/Reports/PaymentReconciliationLedger.php`: each can be reverted independently via `git checkout -- <file>` (or reverting the specific commit) — they do not depend on each other, only on `TaxCalculationService`, which remains backward-compatible (its public method signatures did not change; only `extractTaxFromInclusiveAmount()`'s internal rounding behavior changed, and it had no prior callers to break).
- `app/Services/TaxCalculationService.php`: the rounding-order fix and the new SQL-builder method can be reverted independently of each other and of the two report-engine changes.
- No database write, schema change, or data migration occurred at any point — rollback is a pure code revert with no follow-up cleanup required.

## Outstanding Issues

1. **`SalesTaxReportEngine::accountRows()` remains unmigrated**, and cannot be safely migrated under `TaxCalculationService`'s current "always rounded" contract without changing that report's KPI totals. Recommend a future phase resolve this via one of: (a) redesigning the affected report's aggregation to round once at the very end regardless of per-row rounding (i.e. re-derive the sum from unrounded per-row raw values, which would require the service to expose an unrounded variant), or (b) accepting the one-time, disclosed, signed-off total shift as a "bug fix" (rounding-in-aggregate has its own correctness argument) — analogous to how the Dashboard bug fix is handled, requiring explicit business sign-off before changing a report's displayed totals. This is a decision for a future phase, not something to resolve unilaterally here.
2. **Zero existing automated test coverage for all three reporting engines**, discovered while attempting to run a regression check. This predates this phase and was not introduced by it, but is worth flagging: `SalesReportEngineV2`, `SalesTaxReportEngine`, and `PaymentReconciliationLedger` have no Feature tests exercising them against a real database. This means Phase 2.2's equivalence claims, while numerically rigorous, could not be cross-checked against an actual end-to-end report run. Recommend adding Feature test coverage for these three engines as a prerequisite (or parallel task) before any further migration phases touch them.
3. **No live test database in this development environment**, identical to the gap noted in the Phase 2.1 Completion Report. Recommend the reviewer or CI run the full Feature suite, and specifically any Sales Tax / Pure Sales Summary report page smoke test, before merging this phase's PR.

## Lessons Learned

- **"Prove equivalence" must mean actual numerical execution, not code-reading.** Reading the three formulas side-by-side, they look identical in intent. Only exhaustive numerical comparison surfaced that (a) a rounding-order choice in the new service diverged from production in rare cases, and (b) an aggregation-order difference would diverge in the *majority* of cases for one specific call site. Neither would have been caught by inspection alone.
- **A phase's real scope can be smaller than its assigned scope, and that's the correct outcome, not a failure to complete the phase**, when the difference between the two is exactly what a rigorous validation step is designed to catch. Documenting a deliberately-withheld migration in detail is more valuable than forcing it through.
- **Running a project-wide formatter on a legacy file is not a "safe cleanup" step** — it can produce a large, unreviewable diff and incidentally remove things (like an unused import) that, while individually harmless, have nothing to do with the task at hand. Scoping tool-assisted formatting to only genuinely new code (this phase's new test file, the already-Pint-conformant `TaxCalculationService.php`) rather than pre-existing files with an older style, avoided this.
- **A method's contract (e.g., "always returns rounded values") is itself a design decision with downstream consequences that aren't visible until a real caller with different needs shows up.** This is exactly why the Implementation Plan sequences report migration before ledger migration — a low-stakes reporting call site was the right place to discover this tension, not a customer balance write path.

## Recommendation for Phase 2.3

**Proceed to Phase 2.3** (the separately-approved Dashboard tax-formula bug fix) as planned — it is unaffected by anything discovered in this phase and remains a small, isolated, well-understood change.

**Before attempting any further reporting-engine migration** (i.e., before revisiting `SalesTaxReportEngine`), recommend a short design discussion — not a full phase — on whether `TaxCalculationService` should offer an unrounded/raw variant for aggregation-heavy callers, or whether the affected report's rounding strategy itself should change (with sign-off, as a disclosed behavior change). This does not block Phase 2.3, which is independent.

No additional review beyond normal PR review is advised for the two migrations that *were* completed in this phase — both are backed by exhaustive numerical proof and are, in effect, textually/behaviorally identical to what they replaced. The one thing worth a reviewer's explicit attention is confirming they agree with the decision to leave `SalesTaxReportEngine` unmigrated, since that's a judgment call about acceptable risk, not just a code-correctness question.
