# Phase 2.8A — Pre-Migration Audit

Date: 2026-07-03
Branch: `raj_development`
Scope: Migrate only unambiguous Payment- and Order-only callers of `CustomHelper::updateCreditBalance()` onto `LedgerBalanceService::applyTransaction()`.

---

## Every Candidate Caller

19 active call sites of `CustomHelper::updateCreditBalance()` exist (2 additional occurrences are commented-out, inactive code, excluded from this count). Each was read in full context — not inferred from naming — to determine exactly what `customer_accounts.type` value(s) it can produce.

| # | File:Line | Type(s) producible | Classification |
|---|---|---|---|
| 1 | `CustomerAccount/PaymentStoreController.php:60` | `payment` only (hardcoded) | **Safe — migrate** |
| 2 | `CustomerAccount/RefundStoreController.php:41` | `refund` only | Deferred family |
| 3 | `CustomerAccount/DiscountStoreController.php:48` | `discount` only | Deferred family |
| 4 | `CustomerAccount/UpdateController.php:182` | **Any type** (edits an existing record of whatever type it already has) | **Ambiguous — exclude** |
| 5 | `CustomerAccount/ChargeStoreController.php:55` | `charge` only | Deferred family |
| 6 | `Invoice/PaymentStoreController.php:63` (admin) | `payment` only (hardcoded) | **Safe — migrate** |
| 7 | `Invoice/StoreController.php:207` | `charge`, `discount`, `refund`, or `order` — determined at runtime by `$invoiceItem->type` | **Ambiguous — exclude** |
| 8 | `Invoice/UpdateController.php:164` | Whatever type the existing invoice item already has (`$existingItem->type`) | **Ambiguous — exclude** |
| 9 | `Invoice/UpdateController.php:254` | Whatever type the new invoice item is (`$newItem->type`) | **Ambiguous — exclude** |
| 10 | `Dashboard/DamageChargeStoreController.php:49` | `charge` only | Deferred family |
| 11 | `Dashboard/FuelChargeStoreController.php:50` | `charge` only | Deferred family |
| 12 | `Dashboard/PaymentStoreController.php:345` (inside `handleCrmPayment()`) | `payment` only (hardcoded) | **Safe — migrate** |
| 13 | `OrderManagement/Orders/AlertChargeController.php:53` | `charge` only | Deferred family |
| 14 | `OrderManagement/Orders/AddToAccountPaymentController.php:76` | `order` only (hardcoded) | **Safe — migrate** |
| 15 | `Front/Checkout/PostController.php:526` | `order` only (hardcoded, gated behind `payment === 'Account'`) | **Safe — migrate** |
| 16 | `Front/Customer/Dashboard/Invoice/PaymentStoreController.php:204` | `payment` only (hardcoded) | **Safe — migrate** |
| 17 | `ChargeService.php:78` (`createFromOrderProduct()`) | `charge` only | Deferred family |
| 18 | `ChargeService.php:128` (`recordPayment()`) | `payment` only (hardcoded — the `$type` parameter selects `'fuel'`/`'damage'` for the *reason*/*status field*, never for `->type`, which is unconditionally `'payment'`) | **Safe — migrate** |
| 19 | `ChargeService.php:197` (`markResolved()`) | `discount` only (a reversal entry) | Deferred family |

**Result: 7 of 19 call sites are unambiguously Payment- or Order-only. 12 are excluded** — 8 as members of a deferred transaction family (Refund, Discount, Credit/Debit N/A since dormant, Charge), and 4 as genuinely ambiguous, type-agnostic edit/creation paths that must not be touched regardless of family.

## Why Each Migrated Caller Is Safe

For every one of the 7 migrated call sites, safety rests on the same two facts, individually verified by reading the full method (not just the call site) in each case:

1. **The `customer_accounts.type` value is a hardcoded string literal**, never a variable derived from user input, a loop variable, or an existing record's own type. There is no code path within the method that could assign any other type before reaching this call.
2. **The method containing the call site has exactly one call to `updateCreditBalance()`** (confirmed via `grep` on each file), so there is no other branch in the same method that could reach a different type through the same call.

Additionally, for **#18** (`ChargeService::recordPayment()`), the `$type` parameter (`'fuel'`/`'damage'`) was traced through the entire method body and confirmed to affect only `$reason`, `$alertField`, and `$statusField` — never `$payment->type`, which is assigned the literal `'payment'` unconditionally.

## Why Excluded Callers Remain Excluded

- **Deferred-family callers (#2, #3, #5, #10, #11, #13, #17, #19)**: out of scope by the mission's explicit list (Refund, Discount, Manual/Fuel/Damage Charge). Not touched.
- **#4 (`CustomerAccount/UpdateController.php`)**: this is a generic "edit an existing ledger transaction" endpoint. It calls `CustomerAccount::findOrFail($id)` with no type filter — the transaction being edited could be a payment, a charge, a refund, or anything else already in the ledger. It also calls `CustomHelper::reverseTransactionEffect()` immediately before `updateCreditBalance()`, and `reverseTransactionEffect()` has no `LedgerBalanceService` equivalent at all yet. Migrating only half of a reverse-then-reapply pair, for an endpoint that isn't even type-restricted, would be unsafe on two independent grounds.
- **#7, #8, #9 (`Invoice/StoreController.php` and `Invoice/UpdateController.php`)**: all three share one call site that dynamically creates ledger rows across four different transaction types (`charge`, `discount`, `refund`, `order`) depending on the invoice line item being processed. There is no way to migrate "the Order case" out of this call site in isolation without either duplicating and forking this already-intricate logic (an architecture change, out of scope) or leaving the other three types on `CustomHelper` while this one type silently uses `LedgerBalanceService` from the same `if`/`elseif` chain (a correctness risk this mission explicitly warns against — "no mixed or accidental migrations"). #8 additionally depends on `reverseTransactionEffect()`, same as #4.

## Risk Assessment

| Risk | Assessment |
|---|---|
| A migrated call site secretly creates a non-Payment/Order type under some untested input | **Low.** All 7 migrated types are hardcoded string literals, not computed — there is no input that changes them. |
| `applyTransaction()` behaves differently from `updateCreditBalance()` for Payment/Order | **Very low.** Phase 2.7's live, rolled-back validation proved bit-for-bit equivalence for both types (Payment: 3/3 exact match; Order: 3/3 exact match across both Phase 2.7 and 2.7A testing) — these are the only two families where equivalence has been proven to that level of rigor. |
| Removing the `CustomHelper` import breaks other functionality in a migrated file | **Mitigated.** Checked every file for other `CustomHelper::` usage before deciding whether to remove the import. `Front/Checkout/PostController.php` (uses `getCustomerAccountStatus()` elsewhere) and `ChargeService.php` (two other `updateCreditBalance()` call sites remain) keep their import. The other 5 files have the import removed since it becomes genuinely unused. |
| Retry/transaction/exception behavior differs between the two methods | **None.** `applyTransaction()`'s retry loop, `DB::transaction()` wrapping, and `QueryException` handling were built in Phase 2.7 as a structural, line-for-line mirror of `updateCreditBalance()`'s — re-verified against the live source in that phase, not assumed here. |
| Scope creep toward migrating the `charge` family "since it's right there" | **Mitigated by design.** This audit's classification table is the single source of truth for what gets touched; nothing outside the 7 rows marked "Safe — migrate" will be edited. |

## Rollback Plan

Each migrated call site is a single-line change (`CustomHelper::updateCreditBalance(...)` → `LedgerBalanceService::applyTransaction(...)`), plus an import swap in 5 of the 7 files. Reverting is symmetric and trivial: restore the `CustomHelper::updateCreditBalance()` call and the `use App\Helpers\CustomHelper;` import in each of the 7 files. No database schema, migration, or data change is involved anywhere in this phase, so rollback is a pure code revert with no data-remediation step.

## Validation Plan

1. Run the full existing `tests/Unit` suite to confirm no regression from any prior phase's work.
2. Extend the Phase 2.7/2.7A synthetic, rolled-back validation methodology to the exact 7 migrated call sites' effective code paths (`applyTransaction()` for `payment` and `order`, which is unchanged since Phase 2.7 — the migration itself introduces no new arithmetic or write logic, only new callers of already-validated code).
3. Repository-wide `grep` after migration to confirm: `CustomHelper::updateCreditBalance()` is referenced only by the 12 excluded call sites; `LedgerBalanceService::applyTransaction()` is referenced only by the 7 migrated call sites.
4. Honest disclosure of the standing `tests/Feature` limitation (HTTPS/test-bootstrap issue, no test database) — unchanged since Phase 2.1, not re-solved here.
