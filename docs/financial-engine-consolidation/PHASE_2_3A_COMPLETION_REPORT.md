# Phase 2.3A — Completion Report

Report date: 2026-07-01
Branch: `raj_development`
Status: **Complete.** Architecture/documentation phase with a small, additive, comment-and-test-only code change.
Governing documents: `PHASE_2_3A_CALCULATION_PATTERN_ARCHITECTURE.md`, `FINANCIAL_DECISIONS.md` (FD-001 amendment)

---

## Executive Summary

Phases 2.2 and 2.3 each independently discovered the same architectural tension: substituting a rounded, single-transaction tax calculation into a context that sums many rows before rounding once shifts the aggregate total away from what the underlying data actually sums to. Both times, the fix was the same shape — a small, purpose-built unrounded method — built after the mistake was nearly made, not before. Phase 2.3A exists to make that pattern explicit *before* the next service (Phase 2.4's `InvoiceCalculationService`) is built, rather than risk discovering it a third time.

This phase formalized two named calculation families — **Financial Transaction Calculations** and **Financial Analytics Calculations** — with explicit rules, a developer decision tree, and an API categorization of every method currently on `TaxCalculationService`. Financial Decision FD-001 was amended (appended to, not rewritten) with four clarifying rules distinguishing compliant aggregation-order deferral from a policy violation. `TaxCalculationService`'s docblocks now carry explicit "TRANSACTION-SAFE" / "ANALYTICS-ONLY" labels, and three new tests make the distinction executable.

**No business behavior changed.** The only code touched is comments (PHPDoc) and new tests on already-shipped, already-tested methods.

## What Was Documented

- `docs/financial-engine-consolidation/PHASE_2_3A_CALCULATION_PATTERN_ARCHITECTURE.md` — all 10 required sections: executive summary, the two calculation families, rules for each (when to use, allowed/not allowed, rounding rules, storage expectations, examples), a decision tree, API guidance categorizing every `TaxCalculationService` method, FD-001 update guidance, Phase 2.4 guidance (invoices must use transaction-safe calculations exclusively), risks prevented, and completion criteria.
- `docs/financial-engine-consolidation/FINANCIAL_DECISIONS.md` — FD-001 amended with a dated "Update — Phase 2.3A" section appended after the original Decision/Rationale/Architectural Impact/Future Implementation Guidance text, per this log's own stated convention (never edit a past entry's approved Decision or Rationale — append a dated update instead). The amendment adds four clarifying rules: transaction-level tax remains the source of truth; analytics may use high-precision intermediates when aggregating; analytics must reconcile back to transaction-level source data; analytics must never write financial transactions.
- `docs/financial-engine-consolidation/FINANCIAL_ENGINE_MASTER_ROADMAP.md` — status header, progress percentage (25% → 27%), and the Completed/In Progress/Planned roadmap section updated to record Phase 2.3A and point Phase 2.4 at the new architecture document's §8 guidance.
- `docs/financial-engine-consolidation/FINANCIAL_ENGINE_TODO.md` — Phase 2.3A added to Completed Phases; the previously-open "resolve the rounding-contract vs. aggregation-order tension as a standing design pattern" architectural task is now marked resolved (with a note that it resolves the *policy* question, not a mechanical 1:1-variant rule); Documentation Status and Testing Status tables updated with exact current counts.

## Whether Any Code Changed

**Yes, in a narrowly scoped, additive way:**

| File | Change | Nature |
|---|---|---|
| `app/Services/TaxCalculationService.php` | Class-level docblock expanded to explain the two calculation families and cross-reference the new architecture document; each of the 4 existing methods got a "TRANSACTION-SAFE" or "ANALYTICS-ONLY" label prepended to its existing docblock. | **Comments only.** Verified via `git diff` inspection: every added line is a docblock/comment line; zero executable code lines were added, removed, or modified. |
| `tests/Unit/Services/TaxCalculationServiceTest.php` | 3 new tests added: `test_transaction_safe_methods_always_return_rounded_cent_values`, `test_analytics_only_methods_deliberately_do_not_round`, `test_transaction_safe_and_analytics_only_results_can_differ_before_final_rounding`. | **New tests only.** No existing test was modified or removed. |

No method signature changed. No method's return value, rounding behavior, or formula changed. No renames or aliases were introduced — the mission explicitly allowed non-breaking aliases "if doing so improves clarity," but the existing method names (`extractTaxFromInclusiveAmount`, `extractBaseFromInclusiveAmountRaw`, etc.) were judged already self-descriptive enough that a rename would add churn without adding clarity beyond what the new docblock labels already provide.

## Why Phase 2.4 Is Now Safer

`InvoiceCalculationService` (Phase 2.4) will be the first new Financial Engine service built *after* this distinction exists in writing, with an explicit rule (`PHASE_2_3A_CALCULATION_PATTERN_ARCHITECTURE.md` §8): invoices are financial transaction records, not analytics, so `InvoiceCalculationService` must use transaction-safe calculations exclusively, and must never call either analytics-only method (`extractTaxFromInclusiveAmountSql()`, `extractBaseFromInclusiveAmountRaw()`) for a value destined to be stored in an `invoices` column. Without this phase, that rule existed only as an inference a careful developer might draw from reading two prior completion reports; now it is a direct, one-hop reference from the service being built to a document that states the rule plainly, with a decision tree to resolve any ambiguity, and API labels visible at the point of writing the code (in the IDE, via the docblock itself), not just in separate documentation.

## Validation Results

- **`SalesTaxReportEngine.php` remains untouched:** `git diff --stat app/Services/Reports/SalesTaxReportEngine.php` returns nothing — zero diff, confirmed.
- **No invoice, ledger, payment, or balance logic changed:** confirmed via `git status` — the only files touched this phase are `TaxCalculationService.php` (comments only) and its test file (new tests only). No file under `Invoice/`, `CustomerAccount/`, or any balance/ledger path was touched. (The pre-existing modifications to `Dashboard\IndexController.php`, `SalesReportEngineV2.php`, and `PaymentReconciliationLedger.php` visible in `git status` are carried over, unchanged, from Phases 2.2 and 2.3 — nothing in those files was edited during this phase.)
- **No existing outputs changed:** the docblock changes are comments; the new tests are additive. Every test that existed before this phase still passes with an identical assertion count contribution.
- **No business behavior changed:** no formula, rounding rule, or method signature was altered anywhere in the codebase during this phase.

## Test Results

| Suite | Result |
|---|---|
| `./vendor/bin/phpunit tests/Unit/Services/TaxCalculationServiceTest.php` | **20/20 passed, 47 assertions** (17 from Phases 2.1-2.3 + 3 new Phase 2.3A tests) |
| `./vendor/bin/phpunit tests/Unit` (full existing suite) | **21/21 passed, 48 assertions** |
| `./vendor/bin/pint --test` on `TaxCalculationService.php` and the test file | **Pass** |
| `php -l` on both changed files | **No syntax errors** |
| `./vendor/bin/phpunit tests/Feature/BillingEngine/BillingEngineTest.php` (attempted, general regression sanity check) | **Could not execute** — this environment has no live database connection configured, the same limitation documented in every prior phase's completion report (Phases 2.1, 2.2, 2.3). The command completes with no test output, consistent with the runner hanging on a DB connection attempt rather than failing outright. This phase's changes have no database dependency at all (comments and pure-function tests only), so this is stated for completeness and consistency with prior reports, not because this phase's changes could plausibly be at risk from it. |

**I am not claiming the Feature suite passed. It could not be run in this environment, for the same reason as every prior phase, and that is stated plainly rather than omitted.**

## Recommendation for Phase 2.4

**Phase 2.4 may begin.** All completion criteria from `PHASE_2_3A_CALCULATION_PATTERN_ARCHITECTURE.md` §10 are satisfied: the architecture document exists, `TaxCalculationService`'s methods are labeled in their own docblocks, the distinction is backed by executable tests, FD-001 is amended, the Master Roadmap and TODO tracker reflect this phase's completion, `SalesTaxReportEngine` remains untouched, and no invoice/ledger/payment/balance logic was modified.

Phase 2.4 should build `InvoiceCalculationService` using only transaction-safe calculations, per the explicit guidance in the architecture document's §8 — this is now a documented mandate to design against, not a lesson to be learned again the hard way.
