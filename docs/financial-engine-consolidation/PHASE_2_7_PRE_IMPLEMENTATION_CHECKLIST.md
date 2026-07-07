# Phase 2.7 — Pre-Implementation Checklist

Date: 2026-07-02
Branch: `raj_development`
Governing documents: `FINANCIAL_TRUTH_TABLE.md` (authoritative policy), `PHASE_2_6_COMPLETION_REPORT.md` (arithmetic foundation), `PHASE_2_6A_PARALLEL_VALIDATION_REPORT.md` (100% equivalence proof for the arithmetic, and the explicit recommendation this phase implements).

---

## Scope

Build `LedgerBalanceService::applyTransaction()` — the database-write wrapper around `amountWithTax()` (already built in Phase 2.6, already independently validated in Phase 2.6A). This is the "structure" `updateCreditBalance()` provides beyond arithmetic: looking up the customer, resolving the tax-rate setting, computing the new balance, persisting both the ledger row and the customer row, and retrying on deadlock. Nothing about the arithmetic changes in this phase — it is reused verbatim via `self::amountWithTax()`.

This is **not** a caller migration phase. `applyTransaction()` will exist, be tested, and be validated — and have zero production callers when this phase ends, exactly as `amountWithTax()` did after Phase 2.6.

## Supported Transaction Types

Identical to Phase 2.6's `amountWithTax()` scope, minus Extension Charge (see below):

- Payment (`customer_accounts.type = 'payment'`)
- Order (`customer_accounts.type = 'order'`)
- Manual Charge, Fuel Charge, Damage Charge (`customer_accounts.type = 'charge'`, one shared code path — they differ only by `reason`, which has no bearing on the write path either)

**Extension Charge is structurally excluded from `applyTransaction()`, not merely deferred.** Confirmed repeatedly since Phase 2.5B: Extension Charge never creates a `customer_accounts` row (`customer_account_id` is explicitly `null`). `applyTransaction()`'s signature takes a `CustomerAccount $record` — there is no such record for Extension Charge to pass in. Extension Charge's tax treatment remains fully served by `amountWithTax(TYPE_EXTENSION, ...)` directly (already built and validated in Phases 2.6-2.6A); `applyTransaction()` has nothing to add for it. This is documented here so it is not mistaken for an oversight during review.

## Deferred Transaction Types

Refund, Discount, Credit, Debit, Account Invoice special handling, Aging, Collections, Payment Plans, Bad Debt, Settlement Adjustments, Promotional Credit, Available Credit — identical list to Phase 2.6, for identical reasons (see `FINANCIAL_TRUTH_TABLE.md` §7). Calling `applyTransaction()` with a `CustomerAccount` whose `type` is any of these will throw immediately, before any database work begins (fail-fast, no wasted transaction/lock), with a message citing the specific Truth Table row.

## Files Expected to Change

| File | Change |
|---|---|
| `app/Services/LedgerBalanceService.php` | Add `applyTransaction()`. No changes to the existing `amountWithTax()` method or its constants. |
| `tests/Unit/Services/LedgerBalanceServiceTest.php` | Add tests for `applyTransaction()`'s deferred-type guards (the parts of it that don't require a database — the write-path equivalence itself is validated separately, below, not via `tests/Unit`, since `tests/Unit` has no database). |
| `docs/financial-engine-consolidation/PHASE_2_7_PRE_IMPLEMENTATION_CHECKLIST.md` | This document. |
| `docs/financial-engine-consolidation/PHASE_2_7_COMPLETION_REPORT.md` | Created at completion. |
| `docs/financial-engine-consolidation/FINANCIAL_ENGINE_MASTER_ROADMAP.md`, `FINANCIAL_ENGINE_TODO.md` | Updated at completion. |

## Files Explicitly Out of Scope

- `app/Helpers/CustomHelper.php` — `updateCreditBalance()` and the other three legacy methods remain byte-for-byte unchanged. Nothing calls `applyTransaction()` from here, and nothing here is deleted or deprecated yet.
- **Every one of the 52 confirmed `CustomHelper` call sites** — none will be edited to call `applyTransaction()`. Zero production callers is a hard success criterion, not an aspiration.
- `app/Services/Reports/SalesTaxReportEngine.php` — untouched, per standing project rule.
- Any controller, route, migration, or database schema file.
- `LedgerBalanceService::reverseTransaction()`, `rebuildForCustomer()`, `currentAvailableCredit()` — none of these exist yet and none are built in this phase. Only `applyTransaction()`.

## Validation Strategy

Extend Phase 2.6A's proven technique (rolled-back database transaction, real production method executed live) one level up: instead of comparing pure tax arithmetic, compare the **full write effect** of `CustomHelper::updateCreditBalance()` against `LedgerBalanceService::applyTransaction()`.

For each supported transaction type, two synthetic customers with identical starting balances and two synthetic `customer_accounts` rows with identical inputs will be created inside one outer `DB::beginTransaction()`. `updateCreditBalance()` will be called on customer A's record (the real, unmodified production method); `applyTransaction()` will be called independently on customer B's record (the new method). The resulting `customer_accounts.balance`, `customer_accounts.sales_tax`, and `customers.available_credit_balance` will be compared between A and B — they must be identical. `DB::rollBack()` will run unconditionally in a `finally` block; a row-count check immediately after will confirm nothing persisted, exactly as done in Phase 2.6A.

Extension Charge is not part of this write-path validation, for the structural reason stated above — its arithmetic was already validated in Phase 2.6A and nothing about it changes here.

## Rollback Strategy

If code is committed: delete `applyTransaction()` from `LedgerBalanceService.php` and its associated tests. Nothing else references it (zero callers is a completion requirement), so no other file requires changes to fully revert. Identical rollback shape to Phase 2.6.

If a discrepancy is found during validation: per mission rule, stop, document it in the completion report's Behavioral Equivalence Validation section, and do not force a fix or proceed to recommend Phase 2.8 caller migration. The already-built, already-validated `amountWithTax()` from Phase 2.6 is unaffected either way.

## Risk Assessment

| Risk | Likelihood | Mitigation |
|---|---|---|
| `applyTransaction()`'s retry/transaction-wrapping logic diverges subtly from `updateCreditBalance()`'s (e.g., different deadlock handling) | Low | Retry loop and `DB::transaction()` wrapping will be copied structurally, not reinvented; re-verified against the live source immediately before writing, same discipline as Phase 2.6 |
| The `sales_tax` column's recorded value (a rate, not a dollar amount) is confused with `TaxBreakdown->taxAmount` (a dollar amount) | Medium — this is a real, non-obvious distinction in the production code | Addressed explicitly in the design: a separate, small "which rate gets recorded" step, kept apart from the tax-amount calculation, matching production's exact per-type behavior including its one asymmetry (`order` never sets `sales_tax` at all — a pre-existing no-op, not something this phase will "fix") |
| No live database in a truly clean environment for every reviewer | Low — already solved | Uses the exact same `VITE_ORIGIN_PROTOCOL=http` runtime override and rolled-back-transaction technique proven in Phases 2.4 and 2.6A |
| Scope creep toward building `reverseTransaction()` or migrating a caller "since it's right there" | Medium | This checklist and the mission both name zero production callers as a hard completion requirement; the completion report will independently re-confirm via repository-wide grep, exactly as Phases 2.6 and 2.6A did |

## Success Criteria

- `LedgerBalanceService::applyTransaction()` exists, implements Payment/Order/Charge (Manual+Fuel+Damage), and throws immediately for every deferred type.
- Write-path behavioral equivalence proven for all three supported types via live, rolled-back comparison against the real `updateCreditBalance()` — 100% match, or an honestly documented discrepancy with no forced fix.
- Zero production callers, confirmed via repository-wide grep.
- `CustomHelper.php` and `SalesTaxReportEngine.php` remain byte-for-byte unchanged.
- All existing unit tests continue to pass; new tests added for the parts of `applyTransaction()` that don't require a database.
- Any Feature-test or database limitation encountered is disclosed honestly, not glossed over.
