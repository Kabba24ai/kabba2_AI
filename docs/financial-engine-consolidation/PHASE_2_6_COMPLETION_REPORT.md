# Phase 2.6 — Completion Report

Report date: 2026-07-02
Branch: `raj_development`
Status: **Complete.** `LedgerBalanceService` foundation built, mirroring Phase 2.1's approach exactly — six approved transaction types implemented (four real code branches, since Manual/Fuel/Damage Charge share identical treatment), every deferred type throws explicitly, zero production callers.
Governing documents implemented: `docs/financial-engine-consolidation/FINANCIAL_TRUTH_TABLE.md` (the authoritative policy source), `PHASE_2_5A_LEDGER_READINESS_REVIEW.md` §8-9 (the approved narrow scope), `PHASE_2_3A_CALCULATION_PATTERN_ARCHITECTURE.md` (transaction-safe categorization).

---

## Executive Summary

This phase builds `LedgerBalanceService::amountWithTax()`, the unified tax-inclusion decision the Financial Engine has been building toward since Phase 2.1. No business policy was interpreted or invented here — every implemented branch is a direct, re-verified port of the exact formula already in `CustomHelper::updateCreditBalance()` (re-read from the live file during this phase, not assumed from prior documentation), and every deferred branch throws a `\LogicException` naming the specific Truth Table row that blocks it, rather than silently guessing.

Of the six transaction types named as approved (Payment, Order, Manual Charge, Fuel Charge, Damage Charge, Extension Charge), only **four distinct code paths** were needed: Manual Charge, Fuel Charge, and Damage Charge all share `customer_accounts.type = 'charge'` with byte-identical tax treatment, differing only by the `reason` column, which has no bearing on tax calculation. This is not a scope reduction — all six named types are fully covered — it is the same "no duplicated financial formulas" discipline this whole initiative has enforced from Phase 2.1 onward, applied to the mission's own list.

## Objectives Completed

- [x] Built `LedgerBalanceService` with `amountWithTax()` as its only implemented method — mirroring Phase 2.1's scope exactly (a pure, deterministic, zero-side-effect calculation service).
- [x] Implemented Payment, Order, Manual/Fuel/Damage Charge (one shared branch), and Extension Charge — all six approved types.
- [x] Every deferred type (Refund, Discount, Credit, Debit, Account Invoice, and any unrecognized future type) throws a `\LogicException` with a message citing the specific `FINANCIAL_TRUTH_TABLE.md` row that blocks it.
- [x] Zero duplicated financial formulas — every branch delegates to `TaxCalculationService`; the only non-delegated arithmetic is `TYPE_ORDER`'s direct addition of two already-known values (not a tax calculation to centralize).
- [x] Comprehensive PHPDoc — class-level docblock states exactly what is implemented, what is deferred, and why, with citations to the Truth Table row for every claim.
- [x] Zero production callers — confirmed via repository-wide grep.
- [x] `SalesTaxReportEngine.php` untouched — confirmed via `git diff --stat`.
- [x] No business behavior changed anywhere — this is a new, uncalled class; nothing in the existing application can be affected by its existence.

## Files Created

| File | Purpose |
|---|---|
| `app/Services/LedgerBalanceService.php` | The new service — `amountWithTax()` only, plus four `TYPE_*` constants |
| `tests/Unit/Services/LedgerBalanceServiceTest.php` | 16 tests: one equivalence proof per implemented branch (reproducing the production formula inline from source, not merely re-asserting what the new class does), plus one exception test per deferred type |
| `docs/financial-engine-consolidation/PHASE_2_6_COMPLETION_REPORT.md` | This report |

## Files Modified

**None.** This phase is purely additive — consistent with "no scope expansion," "small PR," and the explicit instruction not to migrate any caller.

## Transaction Types Implemented

| Transaction Type | `customer_accounts.type` / mechanism | Production formula ported from | Notes |
|---|---|---|---|
| Payment | `'payment'` | `updateCreditBalance()` `case 'payment'` | `amountWithTax` is always the input amount, regardless of taxable status — modeled by passing an effective rate of `0` when `customerTaxable` is false, which `TaxCalculationService::extractTaxFromInclusiveAmount()` already collapses correctly |
| Order | `'order'` | `updateCreditBalance()` `case 'order'` | Tax is supplied entirely by the caller (`$externalTaxAmount`); no rate-based formula exists to centralize for this type |
| Manual Charge | `'charge'`, arbitrary `reason` | `updateCreditBalance()` `case 'charge'` | Same code path as Fuel/Damage Charge below |
| Fuel Charge | `'charge'`, `reason='Fuel Charge'` | `updateCreditBalance()` `case 'charge'` | Byte-identical to Manual Charge's tax treatment |
| Damage Charge | `'charge'`, `reason='Damages'` | `updateCreditBalance()` `case 'charge'` | Byte-identical to Manual Charge's tax treatment |
| Extension Charge | No `customer_accounts` row; `BillingChargeType::Extension` | `Extension\StoreController`'s inline formula | Included even though it never touches a ledger row — see "Files Intentionally Not Modified" below for why this doesn't contradict the "zero callers" rule |

## Transaction Types Intentionally Deferred

| Transaction Type | Reason (per `FINANCIAL_TRUTH_TABLE.md`) |
|---|---|
| Refund | Pending Business Decision — §3b row 2, the four legacy methods disagree |
| Discount | Pending Business Decision — §3b row 3, `getAvailableCredit()` ignores `sales_tax_type` while the others branch on it |
| Credit | Undefined — §3b row 4, confirmed dead code, zero precedent |
| Debit | Undefined — §3b row 5, confirmed dead code, zero precedent |
| Promotional Credit, Bad Debt, Settlement Adjustments, Payment Plans | No corresponding transaction type exists anywhere in this codebase — named in the Phase 2.6 mission as explicitly out of scope, not skipped from something that exists |
| Account Invoice special handling | Out of scope — this type never affects a balance at all (verified Phase 2.5B) |
| Aging, Collections | Not calculation concerns `amountWithTax()` could answer regardless — a scope question, not a tax-formula question |

Every named deferred type that corresponds to a real enum value (`refund`, `discount`, `credit`, `debit`, `account_invoice`) has its own explicit `match` arm throwing a tailored exception message. Everything else (including the four hypothetical types with no real string identifier) falls through to a generic default arm, which also throws.

## Files Intentionally Not Modified

- **`app/Helpers/CustomHelper.php`** — `updateCreditBalance()` and the other three legacy methods remain the production-serving implementation, completely untouched. `LedgerBalanceService` does not call them, and they do not call it.
- **`app/Http/Controllers/Admin/OrderManagement/Orders/Extension/StoreController.php`** — still computes its own tax inline. `LedgerBalanceService::amountWithTax(TYPE_EXTENSION, ...)` exists as a correct future migration target for this controller, but **migrating it is caller migration, explicitly out of scope for this phase.**
- **Every one of the 52 confirmed `CustomHelper` call sites** (`CustomerAccount`/`Invoice`/`Dashboard`/`Orders`/`Checkout`/`ChargeService` controllers) — none reference `LedgerBalanceService`.
- **`app/Services/Reports/SalesTaxReportEngine.php`** — zero diff, confirmed.
- Any database schema, migration, route, job, or command file.

## Test Results

| Suite | Result |
|---|---|
| `./vendor/bin/phpunit tests/Unit/Services/LedgerBalanceServiceTest.php` | **16/16 passed, 29 assertions** |
| `./vendor/bin/phpunit tests/Unit` (full existing suite) | **37/37 passed, 77 assertions** (21 pre-existing + 16 new) |
| `./vendor/bin/pint --test` on both new files | **Pass** |
| `php -l` on both new files | **No syntax errors** |
| Repository-wide grep for `LedgerBalanceService` outside its own file | **Zero matches** — confirmed zero production callers |
| `git diff --stat app/Services/Reports/SalesTaxReportEngine.php` | **Empty** — confirmed untouched |

No live database is required for any of this phase's tests — `amountWithTax()` is a pure function with no Eloquent model or database dependency, so unlike Phases 2.4-2.5, there is no environment-specific testing gap to disclose here.

## Behavioral Equivalence Proof (summary)

Each implemented branch's test reproduces the exact production arithmetic inline, from the source lines re-read during this phase, and asserts `LedgerBalanceService`'s output matches:

- Payment ($150.00, taxable and non-taxable): `totalAmount === 150.00` in both cases — matches `$amountWithTax = $record->amount` unconditionally.
- Charge, `add`: `$100.00 → $108.87` — matches `$record->amount + $record->amount * $record->sales_tax`.
- Charge, `reverse`: `$150.00 → $150.00` — matches `$amountWithTax = $record->amount` unchanged.
- Charge, `free`/null: `$100.00 → $100.00`, tax `$0` — matches the `else` branch.
- Order: `$200.00 + $17.74 external tax → $217.74` — matches `$record->amount + $externalTaxAmount`.
- Extension, with/without tax: `$120.00 @ 5% → $6.00 tax, $126.00 total` / `$120.00 → $0 tax, $120.00 total` — matches `round($baseAmount * $salesTaxRate, 2)` / `0.00`.

All six worked examples were independently verified via direct `php -r` arithmetic before being encoded as test assertions, following the same discipline established in Phase 2.3 (where an unverified mental-math example was caught and corrected by actually running the test).

## Risks Encountered

None requiring escalation. The one thing worth recording: while drafting the class docblock, care was taken not to imply that `applyTransaction()`, `reverseTransaction()`, `rebuildForCustomer()`, or `currentAvailableCredit()` (the other methods named in the Architecture Report's original `LedgerBalanceService` proposal) exist yet — they do not. Building them would require real database writes and design decisions (locking, retry behavior, matching `updateCreditBalance()`'s existing deadlock-retry loop) that are Phase 2.7/2.8 concerns, not foundation concerns. This phase deliberately built only the pure calculation piece, exactly as Phase 2.1 did for `TaxCalculationService`.

## Rollback Plan

Trivial. Delete `app/Services/LedgerBalanceService.php` and `tests/Unit/Services/LedgerBalanceServiceTest.php`. Nothing else references either file, so no other change is required.

## Outstanding Issues

- **`applyTransaction()`, `reverseTransaction()`, `rebuildForCustomer()`, `currentAvailableCredit()` remain unbuilt** — expected; these belong to Phase 2.7/2.8, not this foundation phase.
- **Extension\StoreController's inline formula remains unmigrated** — `LedgerBalanceService::amountWithTax(TYPE_EXTENSION, ...)` is ready to receive this migration, but doing so is caller migration, out of scope here.
- **The six pending business/technical decisions from `FINANCIAL_TRUTH_TABLE.md` §7 remain open** — unaffected by this phase, still blocking Phase 2.7/2.8's expansion beyond the narrow scope.

## Recommendation for Phase 2.7

**Not yet ready to begin in full.** Phase 2.7 (migrating `updateCreditBalance()`'s 26 call sites) remains gated on the conditions named in `PHASE_2_5A_LEDGER_READINESS_REVIEW.md` §10 and `FINANCIAL_TRUTH_TABLE.md` §7 — none of which this phase was scoped to resolve, and none of which changed as a result of this phase's work.

What Phase 2.6 does enable, safely, ahead of full Phase 2.7: a future small phase could migrate **only** the call sites that exclusively create `payment`, `order`, or `charge`-type records — if such a clean subset can be identified without touching any call site that might also encounter a `refund`/`discount` record — onto `LedgerBalanceService::amountWithTax()`, while the rest of `CustomHelper::updateCreditBalance()` remains as-is. This is not being recommended as the next action, only noted as a possibility the readiness review and Truth Table should evaluate before deciding Phase 2.7's exact shape.
