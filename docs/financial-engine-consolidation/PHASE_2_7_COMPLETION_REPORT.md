# Phase 2.7 — Completion Report

Report date: 2026-07-02
Branch: `raj_development`
Status: **Complete, with one significant finding documented rather than fixed.** `LedgerBalanceService::applyTransaction()` built, zero production callers, deferred types blocked. Write-path validation found a genuine, previously-undocumented rounding discrepancy in production's own code for one sub-case (`charge` + `sales_tax_type='add'`) — not a defect in the new code, and not corrected here, per this phase's explicit instruction to document rather than fix.

---

## Executive Summary

This phase built `LedgerBalanceService::applyTransaction()`, the database-write structure around Phase 2.6's already-validated `amountWithTax()`. The write-path validation — extending Phase 2.6A's technique from comparing pure arithmetic to comparing the *full persisted effect* of the real, unmodified `CustomHelper::updateCreditBalance()` against the new method — ran 12 live, rolled-back comparisons. **9 of 12 matched bit-for-bit. 3 of 12 revealed a real, sub-cent floating-point discrepancy that exists in production today**, independent of anything built in this initiative: `updateCreditBalance()`'s `'charge'` + `sales_tax_type='add'` branch computes `$record->amount + $record->amount * $record->sales_tax` and stores the raw result with no `round()` call, while `LedgerBalanceService::amountWithTax()` (via `TaxCalculationService::addTaxToExclusiveAmount()`, unchanged since Phase 2.1) rounds to 2 decimals before returning. For most dollar amounts, the un-rounded raw float and the rounded total disagree in the third decimal place.

This is documented, not fixed, per this phase's explicit instruction. No line of `CustomHelper.php` was touched, and `LedgerBalanceService::applyTransaction()` was not weakened to reproduce the unrounded behavior — doing either would violate "do not change production behavior" and "do not silently improve or correct production behavior," respectively (weakening the new, correct code to hide a real defect would itself be a silent, undisclosed behavior choice).

**A secondary, retroactive finding**: Phase 2.6A's own validation script rounded the production method's observed balance effect to 2 decimals *before* comparing it against the candidate (`round($customerAfter - $customerBefore, 2)` in that phase's script). That rounding step silently absorbed exactly the class of discrepancy this phase's more precise, unrounded comparison caught. Phase 2.6A's underlying conclusion — that the same *formula* applies to the same *transaction type* in both implementations — remains correct and is not contradicted by this finding. But its "100% equivalence" claim was measured with less precision than it appeared to have. This is recorded here, not by editing `PHASE_2_6A_PARALLEL_VALIDATION_REPORT.md` (a historical record of what was tested and found at that time, left intact per this initiative's standing practice of correcting forward rather than rewriting past reports).

## Objective Completed

- [x] `LedgerBalanceService::applyTransaction()` built as the database-write wrapper around `amountWithTax()`.
- [x] Supports Payment, Order, and Charge (Manual/Fuel/Damage — one shared branch).
- [x] Extension Charge structurally excluded (no `customer_accounts` row exists for it) — documented, not silently omitted.
- [x] Every deferred type (Refund, Discount, Credit, Debit, Account Invoice, anything unrecognized) throws immediately, before any database work begins.
- [x] Retry/transaction-wrapping behavior preserved exactly (5 max retries, `QueryException` code `40001`, 100ms backoff).
- [x] Zero production callers, confirmed via repository-wide grep.
- [x] `CustomHelper.php` and `SalesTaxReportEngine.php` remain byte-for-byte unchanged.
- [x] Write-path behavioral equivalence tested via live, rolled-back comparison — **result documented honestly, including the one sub-case where exact equivalence could not be proven**, per this phase's explicit instruction not to force a fix.

## Files Created

| File | Purpose |
|---|---|
| `docs/financial-engine-consolidation/PHASE_2_7_PRE_IMPLEMENTATION_CHECKLIST.md` | Pre-implementation checklist |
| `docs/financial-engine-consolidation/PHASE_2_7_COMPLETION_REPORT.md` | This report |

## Files Modified

| File | Change |
|---|---|
| `app/Services/LedgerBalanceService.php` | Added `applyTransaction()` and its 5 required imports (`Customer`, `CustomerAccount`, `Setting`, `QueryException`, `DB`). Updated the class-level docblock to describe the new method's scope. **No changes to `amountWithTax()`'s logic, signature, or the four `TYPE_*` constants.** |
| `tests/Unit/Services/LedgerBalanceServiceTest.php` | Added 6 tests covering `applyTransaction()`'s fail-fast deferred-type guards (the only part of the method that doesn't require a database). |

## Files Intentionally Not Modified

- **`app/Helpers/CustomHelper.php`** — byte-for-byte unchanged, including the unrounded `'charge'`+`'add'` arithmetic this phase's validation found. Fixing that is an explicit non-goal of this phase.
- **Every one of the 52 confirmed `CustomHelper` call sites** — none reference `LedgerBalanceService`.
- **`app/Services/Reports/SalesTaxReportEngine.php`** — untouched, confirmed via `git diff --stat`.
- `PHASE_2_6A_PARALLEL_VALIDATION_REPORT.md` — left intact despite the retroactive finding above; corrected forward in this report instead, per standing practice.
- Any controller, route, migration, or database schema file.
- `reverseTransaction()`, `rebuildForCustomer()`, `currentAvailableCredit()` — not built in this phase.

## Supported Transaction Types

Payment, Order, Manual Charge, Fuel Charge, Damage Charge (one shared `'charge'` code path) — identical set to Phase 2.6's `amountWithTax()`, minus Extension Charge.

## Deferred Transaction Types

Refund, Discount, Credit, Debit, Account Invoice, Aging, Collections, Payment Plans, Bad Debt, Settlement Adjustments, Promotional Credit, Available Credit — identical list and identical reasons to Phase 2.6 (see `FINANCIAL_TRUTH_TABLE.md` §7). Each throws immediately via a fail-fast guard at the top of `applyTransaction()`, before any `DB::transaction()` is opened.

**Extension Charge is a structural exclusion, not a policy deferral** — it is fully approved (per Phase 2.6/2.6A), but has no `customer_accounts` row for `applyTransaction()` to operate on. Calling `applyTransaction()` with a record of type `'extension'` throws the same fail-fast guard as any other unsupported type, with a message explicitly naming this structural reason.

## applyTransaction() Design

```
applyTransaction(CustomerAccount $record, float $externalTaxAmount = 0.0): void
```

1. **Fail-fast guard** — if `$record->type` is not `payment`/`charge`/`order`, throw immediately.
2. **Retry loop** — identical to production: `$maxRetries = 5`, catch `QueryException` with code `40001`, `usleep(100000)`, rethrow otherwise.
3. Inside `DB::transaction()`:
   - `$customer = Customer::findOrFail($record->customer_id);` — identical to production.
   - `$currentBalance = $customer->available_credit_balance ?? 0;` — identical.
   - Resolve `$salesTaxRate` from the `sales_tax` setting — identical query.
   - Resolve `$customerTaxable` from `$customer->getTaxStatus()` — identical.
   - Call `self::amountWithTax($record->type, $record->amount, $salesTaxRate, $record->sales_tax_type, $customerTaxable, $externalTaxAmount)` — **this is the one structural change from production**: the tax-dollar computation is delegated to the already-validated Phase 2.6 method instead of being re-implemented inline.
   - Set `$record->sales_tax` to the **rate** (not the computed dollar tax) — reproducing production's exact per-type behavior: `payment` records the rate only if the customer is taxable; `charge` records the rate only for `'add'`/`'reverse'`; `order` is left alone entirely, matching production's own commented-out assignment for that case (a pre-existing no-op, preserved exactly).
   - Compute `$newBalance = $currentBalance + direction * $breakdown->totalAmount` (`payment` subtracts, `charge`/`order` add) — identical sign convention to production.
   - `$record->balance = $newBalance; $record->save();` — identical.
   - `$customer->available_credit_balance = $newBalance; $customer->save();` — identical.

The only formula-level difference from production, anywhere in this method, is that `amountWithTax()` rounds its dollar output and production's `'charge'`+`'add'` inline formula does not — see Behavioral Equivalence Validation below.

## Behavioral Equivalence Validation

**Methodology**: extending Phase 2.6A's proven technique, two synthetic customers with identical starting balances and two synthetic `customer_accounts` rows with identical inputs were created per scenario, inside one outer `DB::beginTransaction()`. The real, unmodified `CustomHelper::updateCreditBalance()` ran on customer A's record; `LedgerBalanceService::applyTransaction()` ran independently on customer B's record. Resulting `customer_accounts.balance`, `customer_accounts.sales_tax`, and `customers.available_credit_balance` were compared between A and B with **no rounding applied to the comparison itself** — a stricter check than Phase 2.6A used. `DB::rollBack()` ran unconditionally; confirmed via direct query afterward that zero synthetic rows of any kind persisted.

**12 comparisons executed. 9 matched bit-for-bit. 3 did not.**

| # | Scenario | Bit-for-bit match? |
|---|---|---|
| 1 | Payment — taxable, $150.00 | ✅ |
| 2 | Payment — non-taxable, $150.00 | ✅ |
| 3 | Payment — taxable, $0.01 | ✅ |
| 4 | Charge (Fuel Charge) — add, $100.00 | ✅ (coincidence — see below) |
| 5 | Charge (Damage Charge) — add, $350.00 | ❌ |
| 6 | Charge (Manual Charge) — add, $40.00 | ❌ |
| 7 | Charge — reverse, $150.00 | ✅ |
| 8 | Charge — free, $75.00 | ✅ |
| 9 | Charge — null sales_tax_type, $60.00 | ✅ |
| 10 | Charge — add, non-taxable customer, $90.00 | ❌ |
| 11 | Order — $200.00 + $17.74 external tax | ✅ |
| 12 | Order — $500.00 + $0.00 external tax | ✅ |

**The 3 mismatches, exactly as observed:**

| Scenario | Production (unrounded) | Candidate (rounded) | `sales_tax` field |
|---|---|---|---|
| Damage Charge, $350.00 @ 8.87% | `381.045` | `381.05` | Matched (`0.0887` both sides) |
| Manual Charge, $40.00 @ 8.87% | `43.548` | `43.55` | Matched |
| Charge, $90.00 @ 8.87%, non-taxable customer | `97.983` | `97.98` | Matched |

Note scenario #4 ($100.00 @ 8.87%) matched only because `100.00 × 0.0887 = 8.87` happens to already be exactly representable at 2 decimal places — not because the underlying formulas agree. **The un-rounded/rounded discrepancy is the common case for this branch, not a rare edge case**: of the 4 `'add'`-branch scenarios tested, 3 diverged.

## Root Cause Analysis

**Classification: existing production behavior, and an implementation defect in that existing behavior, and a documentation gap** (this specific defect was not surfaced by the original CRM Billing Audit, by Phase 2.5A's detailed re-inspection of these same four `CustomHelper` methods, or by Phase 2.6A's own validation — see the retroactive correction below). It is **not** a business-rule ambiguity: the correct behavior (round currency arithmetic once, immediately) is already established Financial Engine policy since Phase 2.3A; this is a case where the *old*, not-yet-migrated code simply never implemented that policy for this one branch.

**Root cause**: `CustomHelper::updateCreditBalance()`'s `'charge'` case, `sales_tax_type === 'add'` branch, computes `$amountWithTax = $record->amount + $record->amount * $record->sales_tax;` and passes the raw PHP float straight into `$record->balance = $newBalance;` with no `round()` call anywhere in between. `discount`'s `'add'` branch and `refund`'s unconditional branch use the identical unrounded pattern (confirmed by direct source inspection during this phase) — this is very likely the same defect, though Refund and Discount are out of scope for this phase's testing (deferred types) and were not separately validated here.

**What this means for the database columns involved**: `customer_accounts.balance` is `decimal('balance')` (Laravel's default precision, `DECIMAL(8,2)` in MySQL); `customers.available_credit_balance` was originally a `string` column and was later fixed to `DECIMAL(15,2)` by `2026_06_20_100001_fix_available_credit_balance_to_decimal.php`. **In real MySQL production, both columns almost certainly round the unrounded PHP float to 2 decimals on write** (standard MySQL `DECIMAL` insert behavior), and for all 3 example values found in this validation, that rounding lands on the *same* value `TaxCalculationService` already produces (`381.045→381.05`, `43.548→43.55`, `97.983→97.98`, using standard round-half-away-from-zero arithmetic). **This could not be independently confirmed against a real MySQL instance in this environment** (this validation ran against local SQLite, which does not enforce fixed decimal precision on `NUMERIC`/`DECIMAL` columns, which is precisely why this discrepancy was visible here and might not be in production). Floating-point-representation-dependent edge cases exactly on a rounding boundary cannot be ruled out with certainty from this evidence alone.

**Retroactive correction to Phase 2.6A**: that phase's validation script rounded the production method's observed effect to 2 decimals *before* comparing (`round($customerAfter - $customerBefore, 2)`), which would have silently absorbed this exact discrepancy had it appeared in that phase's scenarios (it did include a `$350.00` Damage Charge scenario that reported a bit-for-bit match under that rounded comparison). Phase 2.6A's core conclusion — that `amountWithTax()` selects the correct formula for the correct transaction type — remains correct. Its precision claim should be read as "equivalent to the nearest cent," not "bit-for-bit," for the `'charge'`+`'add'` case specifically.

## Test Results

| Suite | Result |
|---|---|
| `./vendor/bin/phpunit tests/Unit/Services/LedgerBalanceServiceTest.php` | **23/23 passed, 38 assertions** (17 from Phase 2.6 + 6 new `applyTransaction()` guard tests added this phase) |
| `./vendor/bin/phpunit tests/Unit` (full existing suite) | **44/44 passed** |
| `./vendor/bin/pint --test` on both modified files | Pass |
| `php -l` on both modified files | No syntax errors |
| Repository-wide grep for `LedgerBalanceService` outside its own file | Zero matches — confirmed zero production callers |
| `git diff --stat app/Services/Reports/SalesTaxReportEngine.php` | Empty — confirmed untouched |
| Write-path live validation (12 comparisons, rolled back) | **9/12 bit-for-bit match; 3/12 show the documented sub-cent discrepancy above** |

**Honest disclosure on Feature tests**: the known HTTPS/console-bootstrap issue and missing test-database/factory infrastructure (documented since Phase 2.1, most recently re-confirmed in Phase 2.4's Validation Addendum) still apply. `tests/Feature` was **not run** in this phase, for the same unchanged reasons. This report does not claim otherwise.

## Regression Checks

- Full `tests/Unit` suite: 44/44 passing (no regression from the 37 passing at the end of Phase 2.6A).
- `SalesTaxReportEngine.php`: zero diff.
- `CustomHelper.php`: zero diff — the unrounded formula this phase found was **observed**, not **introduced or altered**.
- Repository-wide `LedgerBalanceService` caller search: zero matches outside its own file, both before and after this phase's changes.

## Risks Encountered

The central risk of this phase turned out to be exactly the one it was designed to surface: **a real, sub-cent floating-point discrepancy between old and new code**, for one specific branch. This is treated as a successful outcome of the validation process, not a failure of the implementation — `applyTransaction()` did precisely what it should: compute the policy-correct, rounded answer, making the old code's imprecision visible by contrast rather than silently reproducing it.

A secondary risk, now resolved: early in this phase's validation design, running both `updateCreditBalance()` and `applyTransaction()` sequentially against the *same* customer record was considered, but rejected in favor of two separate, identically-seeded synthetic customers — this avoids any order-dependency or accumulated-balance ambiguity in the comparison, and is the reason this phase's comparison is more precise than Phase 2.6A's (which used delta-based comparison against a single customer, and additionally applied rounding to that delta).

## Rollback Plan

Trivial. Delete `applyTransaction()` from `LedgerBalanceService.php` and its 6 associated tests from `LedgerBalanceServiceTest.php`. `amountWithTax()` and its own tests (Phase 2.6) are entirely unaffected. Nothing else references `applyTransaction()`, so no other file requires changes.

## Outstanding Issues

- **The `'charge'`+`'add'` rounding discrepancy (this phase's central finding) is unresolved.** It requires a technical decision, not a business-policy decision: is MySQL's `DECIMAL` column rounding on write sufficient to make this a non-issue in real production (plausible, per the worked examples, but unverified against a live MySQL instance), or should this be treated as a confirmed data-quality defect warranting its own small, separately-approved fix to `CustomHelper.php` before or alongside any `'charge'`-type caller migration? **This report takes no position — it documents the finding and its evidence, per this phase's explicit instruction not to force a fix.**
- The identical unrounded-arithmetic pattern likely also exists in `updateCreditBalance()`'s `discount` (`'add'` branch) and `refund` (unconditional) cases — **observed by inspection, not independently validated in this phase**, since both are deferred types out of this phase's scope.
- `reverseTransaction()`, `rebuildForCustomer()`, `currentAvailableCredit()` remain unbuilt, as expected — Phase 2.8+ concerns.
- Every business/technical decision item in `FINANCIAL_TRUTH_TABLE.md` §7 remains open, unaffected by this phase.

## Recommendation for Phase 2.8

**Not uniformly ready.** The evidence supports a split recommendation, precise to the sub-case:

- **Payment and Order**: bit-for-bit equivalence confirmed, by construction (no multiplication occurs in either code path, so no rounding risk exists structurally, not just empirically). These remain ready for the same narrow next step recommended in Phase 2.6A.
- **Charge — `'reverse'`, `'free'`, and `null` sales_tax_type**: bit-for-bit equivalence confirmed, by the same structural reasoning (`amountWithTax` reduces to `record->amount` unchanged in all three sub-cases, no multiplication).
- **Charge — `'add'` sales_tax_type (the branch Fuel Charge, Damage Charge, and taxed Manual Charges actually use when tax applies)**: **not confirmed equivalent to the sub-cent level.** This is very likely the single most common real-world path through the `'charge'` type, since it's the one that actually adds tax. Before any `'charge'`-type caller migrates, the outstanding technical question above should be resolved — either by confirming MySQL's column-level rounding genuinely absorbs this in practice, or by deciding whether to fix the underlying imprecision in `CustomHelper.php` first, as its own small, explicitly-scoped, separately-approved change.

Building `reverseTransaction()` next (mirroring this phase's approach for `applyTransaction()`) remains reasonable regardless of the above, since it is independent of this specific rounding question. Caller migration itself should wait for the `'charge'`+`'add'` question to be resolved.
