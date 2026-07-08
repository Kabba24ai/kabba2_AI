# Phase 2.8A — Completion Report

Report date: 2026-07-03
Branch: `raj_development`
Status: **Complete.** Financial Engine Version 2.2 — the first controlled production caller migration. 7 of 19 `CustomHelper::updateCreditBalance()` call sites migrated to `LedgerBalanceService::applyTransaction()`; 12 remain on `CustomHelper`, all confirmed members of a deferred family or genuinely ambiguous.

---

## Executive Summary

This phase migrated production callers for the first time in this initiative. Every one of the 19 real call sites of `CustomHelper::updateCreditBalance()` was individually read in full context — not inferred from naming — before any decision was made. 7 were confirmed to create only Payment or Order transactions via a hardcoded type literal with no code path to any other type; these were migrated to `LedgerBalanceService::applyTransaction()`, a one-line swap per call site. 12 were excluded: 8 as members of a deferred family (Refund, Discount, Charge), and 4 as genuinely ambiguous, type-agnostic edit/creation paths that must not be touched regardless of family, per this phase's explicit "if there is any ambiguity, stop" instruction. `applyTransaction()` itself was not modified — its logic has been unchanged and independently validated since Phase 2.7.

## Files Reviewed

All 19 files/methods listed in `PHASE_2_8A_PRE_MIGRATION_AUDIT.md`'s classification table were read in full around their call site before any decision was made.

## Files Modified

| File | Change |
|---|---|
| `app/Http/Controllers/Admin/Crm/Customers/CustomerAccount/PaymentStoreController.php` | Call site swapped; `CustomHelper` import removed (no other use in file), `LedgerBalanceService` import added |
| `app/Http/Controllers/Admin/Crm/Customers/Invoice/PaymentStoreController.php` | Same pattern |
| `app/Http/Controllers/Admin/Dashboard/PaymentStoreController.php` | Same pattern (call site is inside `handleCrmPayment()`) |
| `app/Http/Controllers/Front/Customer/Dashboard/Invoice/PaymentStoreController.php` | Same pattern |
| `app/Http/Controllers/Admin/OrderManagement/Orders/AddToAccountPaymentController.php` | Same pattern |
| `app/Http/Controllers/Front/Checkout/PostController.php` | Call site swapped; `CustomHelper` import **kept** (file also calls `CustomHelper::getCustomerAccountStatus()` elsewhere); `LedgerBalanceService` import added |
| `app/Services/ChargeService.php` | Only the `recordPayment()` call site (line 128) swapped; the other two call sites in this file (`createFromOrderProduct()`, `markResolved()`) remain on `CustomHelper` untouched. Same namespace as `LedgerBalanceService` (`App\Services`), so no new `use` import was needed |

**Net diff: 7 files, one call-site line changed per file, plus 5 import swaps and 2 import additions. No other line touched in any of the 7 files.**

## Files Not Modified

- `app/Helpers/CustomHelper.php` — `updateCreditBalance()` itself is byte-for-byte unchanged; it still serves the 12 deferred/ambiguous call sites.
- `app/Services/LedgerBalanceService.php` — `applyTransaction()` and `amountWithTax()` are byte-for-byte unchanged from Phase 2.7.
- `app/Services/Reports/SalesTaxReportEngine.php` — untouched, confirmed via `git diff --stat`.
- The 12 excluded call sites' files (`RefundStoreController.php`, `DiscountStoreController.php`, `ChargeStoreController.php`, `CustomerAccount/UpdateController.php`, `Invoice/StoreController.php`, `Invoice/UpdateController.php`, `DamageChargeStoreController.php`, `FuelChargeStoreController.php`, `AlertChargeController.php`) — none edited.
- Any database schema, migration, route, or Financial Truth Table / Policy Manual document.

## Callers Migrated (7)

| # | File:Line | Transaction family |
|---|---|---|
| 1 | `CustomerAccount/PaymentStoreController.php:60` | Payment |
| 2 | `Invoice/PaymentStoreController.php:63` (admin) | Payment |
| 3 | `Dashboard/PaymentStoreController.php:345` | Payment |
| 4 | `Front/Customer/Dashboard/Invoice/PaymentStoreController.php:204` | Payment |
| 5 | `ChargeService::recordPayment()` (`ChargeService.php:128`) | Payment |
| 6 | `OrderManagement/Orders/AddToAccountPaymentController.php:76` | Order |
| 7 | `Front/Checkout/PostController.php:527` | Order |

## Callers Deferred (12)

See `PHASE_2_8A_PRE_MIGRATION_AUDIT.md`'s full classification table for the reasoning behind each. In summary: `RefundStoreController` (Refund), `DiscountStoreController` (Discount), `ChargeStoreController` (Charge), `DamageChargeStoreController` (Charge), `FuelChargeStoreController` (Charge), `AlertChargeController` (Charge), `ChargeService::createFromOrderProduct()` (Charge), `ChargeService::markResolved()` (Discount reversal) — all deferred-family. `CustomerAccount/UpdateController.php`, `Invoice/StoreController.php:207`, `Invoice/UpdateController.php:164`, `Invoice/UpdateController.php:254` — all excluded as type-ambiguous regardless of family (three of the four also depend on `reverseTransactionEffect()`, which has no `LedgerBalanceService` equivalent).

## Validation Results

- **Repository-wide post-migration search**: `CustomHelper::updateCreditBalance()` is now referenced only by the 12 deferred/ambiguous call sites (confirmed — none of the 7 migrated call sites appear in the results). `LedgerBalanceService::applyTransaction()` is referenced only by the 7 approved callers plus its own definition (confirmed — no deferred-family file appears). **No mixed or accidental migration.**
- **Smoke test**: `LedgerBalanceService::applyTransaction()` was executed live, inside a rolled-back transaction, against a synthetic `payment`-type record after all 7 edits were made — confirmed correct balance effect (`-150.00` for a `$150.00` payment), then confirmed zero rows persisted after rollback.
- **No new synthetic validation of `applyTransaction()`'s arithmetic or write behavior was needed or performed in this phase** — that method's logic is unchanged since Phase 2.7, where it was already proven bit-for-bit equivalent to `updateCreditBalance()` for both Payment and Order across 12 live, rolled-back comparisons. This phase's own validation burden was narrower and different in kind: proving each *caller* passes only a hardcoded Payment/Order type, which was established by direct source reading (documented in the Pre-Migration Audit) rather than by re-running arithmetic already proven correct.

## Test Results

| Suite | Result |
|---|---|
| `./vendor/bin/phpunit tests/Unit` | **44/44 passed, 86 assertions** — unchanged from Phase 2.7A, confirming no regression |
| `php -l` on all 7 modified files | No syntax errors |
| `tests/Feature` (incl. `BillingEngine`'s 197 tests) | **Not run in this environment** — the same HTTPS/test-bootstrap and missing-test-database limitations documented since Phase 2.1 still apply. Stated honestly, not glossed over. |

## Regression Review

- Full `tests/Unit` suite: 44/44, identical count and result to Phase 2.7A — no regression.
- `git diff --stat app/Services/Reports/SalesTaxReportEngine.php`: empty.
- `git diff --stat app/Helpers/CustomHelper.php` / `app/Services/LedgerBalanceService.php`: unchanged from their Phase 2.7 state (this phase added zero lines to either file).
- Repository-wide caller search (above): confirms exact 7/12 split, no drift.

## Rollback Plan

Revert each of the 7 files' single-line call-site swap back to `CustomHelper::updateCreditBalance(...)`, and restore the `use App\Helpers\CustomHelper;` import (removing `use App\Services\LedgerBalanceService;`) in the 5 files where it was swapped, or simply remove the added `LedgerBalanceService` import in the 2 files where `CustomHelper` was kept. No database, migration, or data change occurred, so there is no data-remediation step — this is a pure code revert, file by file, independently reversible per call site if only some need to roll back.

## Outstanding Risks

- **The `'charge'`+`sales_tax_type='add'` rounding question from Phase 2.7A remains open and unaffected by this phase** — it does not block Payment/Order migration, since neither family touches that code path, but it still blocks Phase 2.8B (Charge migration).
- **`tests/Feature` remains unexercised** in this environment — the migrated callers' full HTTP-level behavior (validation, redirects, card-processing branches surrounding the migrated call sites) was not re-tested end-to-end via Feature tests, only via direct source reading and one targeted smoke test of the underlying service call.
- **`CustomerAccount/UpdateController.php`, `Invoice/StoreController.php`, and `Invoice/UpdateController.php` remain fully on `CustomHelper`**, including for Order-type edits/creations that happen to flow through them — these are not "half-migrated," they are entirely unmigrated by design, since isolating just the Order case from their shared multi-type logic would itself be an architecture change.

## Recommendation for Phase 2.8B

**Not yet.** Phase 2.8B (Charge migration) remains gated on the unresolved Phase 2.7A rounding question — a MySQL-backed (or equivalent production-like) re-validation of the `'charge'`+`sales_tax_type='add'` sub-case is still required, or an explicit, documented decision to accept the residual risk. Nothing in this phase changes that gate. A reasonable next step independent of Phase 2.8B: consider whether `CustomerAccount/UpdateController.php`'s reliance on `reverseTransactionEffect()` should be addressed (build that method's `LedgerBalanceService` equivalent) before attempting any further caller migration in that area — noted for future scoping, not decided here.
