# Phase 2.5A — LedgerBalanceService Readiness Review

Review date: 2026-07-01
Branch: `raj_development`
Status: **Architecture, planning, and business-rule review only. No application code, database, or business-rule change made.**
Purpose: the official approval gate before `LedgerBalanceService` implementation (Phase 2.6+) begins.
Governing documents reviewed: `CRM_BILLING_PAYMENT_AUDIT.md`, `PHASE_2_ARCHITECTURE_REPORT.md`, `PHASE_2_IMPLEMENTATION_PLAN.md`, `FINANCIAL_ENGINE_MASTER_ROADMAP.md`, `PHASE_2_0_SIGNOFF_READINESS.md`, `PHASE_2_1_COMPLETION_REPORT.md`, `PHASE_2_2_PRE_IMPLEMENTATION_CHECKLIST.md`, `PHASE_2_2_COMPLETION_REPORT.md`, `PHASE_2_3_PRE_IMPLEMENTATION_CHECKLIST.md`, `PHASE_2_3_COMPLETION_REPORT.md`, `PHASE_2_3A_CALCULATION_PATTERN_ARCHITECTURE.md`, `PHASE_2_3A_COMPLETION_REPORT.md`, `PHASE_2_4_PRE_IMPLEMENTATION_CHECKLIST.md`, `PHASE_2_4_COMPLETION_REPORT.md`, `PHASE_2_4_VALIDATION_ADDENDUM.md`, `PHASE_2_5_PRE_IMPLEMENTATION_CHECKLIST.md`, `PHASE_2_5_COMPLETION_REPORT.md`, `FINANCIAL_DECISIONS.md`, `FINANCIAL_ENGINE_TODO.md`. Additionally, this review directly re-inspected `app/Helpers/CustomHelper.php`'s four candidate methods against the live codebase rather than relying solely on prior summaries, and found one new, previously-undocumented gap (§2, §3, §7).

---

## 1. Executive Summary

The Financial Engine has, across six completed phases, established a working pattern: build a shared calculation primitive with zero callers, prove it equivalent to what it replaces, migrate callers in small validated batches, and formalize the categories of calculation (transaction-safe vs. analytics-only) that govern which primitive fits which context. That pattern has succeeded twice on read-only or narrow-write surfaces — `TaxCalculationService` (Phase 2.1-2.3, reporting and dashboard consumers) and `InvoiceCalculationService` (Phase 2.4-2.5, two payment controllers and four display views).

`LedgerBalanceService` is different in kind, not just degree. It is the only remaining Financial Engine service that:

- **Touches every customer's real, currently-correct monetary balance directly** — not a report, not a display value, not one invoice's total, but `customers.available_credit_balance` and `customer_accounts.balance` for the entire customer base.
- **Has 52 confirmed call sites** across five feature areas (CRM, Invoicing, Dashboard/Orders, Checkout, `ChargeService`) — roughly double `updateInvoiceSummary()`'s already-corrected caller count, and with a materially larger blast radius per call site (a wrong invoice total affects one invoice; a wrong balance calculation affects every future transaction for that customer until corrected).
- **Cannot be correctly built without answering business questions that have been open since Phase 2.0** and remain open today, five phases later — this review confirms they are still unresolved, not merely re-flags them.
- **This review itself surfaced a new, previously-undocumented gap** (§2, §7): none of the four candidate methods handle the `'credit'` or `'debit'` `customer_accounts.type` enum values at all — not divergently, but *consistently absent* across all four. This was not caught by any prior phase because no prior phase needed to build a truth table covering every enum value; `LedgerBalanceService`'s unified method does.

**This is why `LedgerBalanceService` is the highest-risk remaining component of this initiative**: it is the point where the Financial Engine stops describing money and starts *being* the mechanism that moves it. Every phase so far has been reversible by reverting a code change. A `LedgerBalanceService` built on an incomplete or guessed truth table would not be reversible in the same way — it would require reconciling real customer balances that had already drifted, which is a fundamentally different, harder problem than reverting a display formula.

---

## 2. Ledger Responsibility Matrix

| Responsibility | Current Owner | Future Owner | Migration Risk | Current State |
|---|---|---|---|---|
| **Customer Balance** (`customers.available_credit_balance`) | `CustomHelper::updateCreditBalance()` (write), `getAvailableCredit()` (live read) | `LedgerBalanceService` | **High** — 26 call sites for the write path alone | Working today; the write path (`updateCreditBalance`) and the live-read path (`getAvailableCredit`) are two independent implementations of the same tax-inclusion logic, not guaranteed to agree (Audit §4.4) |
| **Running Balance** (`customer_accounts.balance` per-row snapshot) | `updateCreditBalance()` (incremental), `fixTheRunningBalance()` (full replay) | `LedgerBalanceService` | **High** — coupled to the same 26+7 call sites | Working today; `FixRunningBalancesController`'s existence is direct evidence this has drifted from the ledger in production before |
| **Available Credit** (live, vs. `credit_limit`) | `CustomHelper::getAvailableCredit()` | `LedgerBalanceService::currentAvailableCredit()` (proposed, Architecture Report §2.2) | **Medium** — 12 call sites, all read-only display | Working today; independently re-implements the tax-inclusion branch a *fifth* time relative to the write path (Audit §4.2) |
| **Credit Limit** (`customers.credit_limit`) | Customer profile edit (outside Financial Engine) | **Not migrating** | **N/A** | A stored data field, not a calculation. `LedgerBalanceService` will *read* it but does not own writing it — confirm this boundary explicitly before Phase 2.6 (not currently stated anywhere) |
| **Ledger Entries** (`customer_accounts` row creation) | `CustomerAccount\{Payment,Refund,Discount,Charge}StoreController`, `Invoice\PaymentStoreController` (×2), `ChargeService` | **Not migrating** | **Low** (unchanged) | `LedgerBalanceService` reacts to entries already created by these controllers, the same pattern `InvoiceCalculationService` uses — it does not own record creation |
| **Payment Allocation** (the tax-inclusion decision itself) | Independently implemented 6 times across `CustomHelper`'s 4 methods (Audit §4.2) | `LedgerBalanceService::amountWithTax()` (proposed) | **Critical** — this *is* the service's core deliverable | **Not fully defined** — see §3. Cannot be built without a business-confirmed truth table, which does not yet exist |
| **Aging** (`Customer::getDaysSinceLastPaymentAttribute()`, days-since-payment badges) | `Customer` model accessors | **Undecided** | **Unknown** | Not named as in-scope for any of the four proposed services in the Architecture Report. **This review recommends an explicit scope decision** — currently neither confirmed in-scope nor confirmed out-of-scope |
| **Account Status** (`CustomHelper::getCustomerAccountStatus()`) | `CustomHelper` (a fifth method, distinct from the four tax-inclusion methods) | **Undecided** | **Unknown** | Same as Aging — not named in the Architecture Report's four-service design at all. Confirm scope before Phase 2.6, not during it |
| **Invoice Balance** (`invoices.paid_amount`/`open_amount`) | `InvoiceCalculationService::recomputeSummary()` | Already migrated | **Resolved** | **Done** (Phase 2.4) — explicitly excluded from `LedgerBalanceService`'s scope; invoices are financial transaction records per Phase 2.3A §8, and already have their own service |
| **Outstanding Balance** ("Payment Due" / overdue amount) | Hard-coded to `-` in Billing Summary | **Not implemented anywhere** | **N/A until implemented** | Confirmed as a known gap since the original audit (§4.7) — not a bug, a missing feature. Needs an explicit decision: implement, or permanently de-scope |
| **Customer Credit** (ambiguous term — see note) | Overlaps with Available Credit above if referring to credit balance; if referring to the `'credit'` `customer_accounts.type` enum value, see Credits below | — | — | This review treats "Customer Credit" as synonymous with Available Credit above unless the business means the `'credit'` transaction type, in which case see the next row — **this ambiguity should itself be resolved in the same conversation as the truth table sign-off** |
| **Refund Impact** | `getAvailableCredit`, `updateCreditBalance`, `reverseTransactionEffect`, `fixTheRunningBalance` — 4 independent, partially divergent implementations (Audit §4.2 table) | `LedgerBalanceService::amountWithTax()` | **Critical** | **Not fully defined** — Refund never collects `sales_tax_type` from the user (only modal of the four that doesn't); every helper method still branches as if it could vary. Business rule undocumented |
| **Discount Impact** | Same 4 methods, same table | `LedgerBalanceService::amountWithTax()` | **Critical** | **Not fully defined** — `getAvailableCredit()` always taxes a discount if the customer is taxable, ignoring `sales_tax_type`; the other three methods branch on it. A discount created with `sales_tax_type='free'` can already produce disagreeing numbers between the live-displayed and persisted balance |
| **Manual Charges** | Same 4 methods; also `_additional_charges.blade.php` (already migrated to `TaxCalculationService`, Phase 2.5) | `LedgerBalanceService::amountWithTax()` | **Medium** | **Partially defined** — all 4 methods agree charges are taxed only when `sales_tax_type==='add'`, and this exact rule is already proven correct and centralized on the display side (Phase 2.5). The *balance-affecting* write-path version of this same rule still needs to move onto the unified method |
| **Credits** (`customer_accounts.type = 'credit'`) | **None** — see finding below | `LedgerBalanceService::amountWithTax()` | **Unknown — newly discovered gap** | **Undefined.** Verified directly in this review: none of the four candidate methods (`getAvailableCredit`, `updateCreditBalance`, `reverseTransactionEffect`, `fixTheRunningBalance`) has a `case 'credit':` branch. Confirmed via `grep` that no application code currently creates a `CustomerAccount` row with `type='credit'` — the enum value appears dormant, not actively wrong today, but **completely unspecified** for the unified method. See §3 and §7 |
| **Voids** (delete/reversal of a ledger entry) | `CustomerAccount\DeleteController` → `reverseTransactionEffect()` then `fixTheRunningBalance()`, in that order, in one transaction | `LedgerBalanceService` (both methods, migrated together per Implementation Plan Phase 2.8) | **High** — sequencing-sensitive | **Confirmed correct** (Architecture Report §1.5) — this specific sequencing invariant is understood and must be preserved exactly when both methods migrate; not an open question, but a hard constraint on *how* Phase 2.8 is implemented |

**Row not requested but required for completeness: `'debit'` (`customer_accounts.type = 'debit'`).** Same finding as Credits — no method handles it, no code currently creates it. Grouped with Credits in §3/§7 rather than a separate matrix row, since the evidence and the required resolution are identical.

---

## 3. Remaining Business Decisions

### Confirmed Policy

- **FD-001** (`FINANCIAL_DECISIONS.md`): transaction/line-level tax is the Financial Engine's source of truth; reports must not independently recalculate tax from aggregated totals; analytics may defer rounding during aggregation but must reconcile to transaction-level values and must never write a financial transaction (amended in Phase 2.3A).
- **`SalesTaxReportEngine` is officially deferred** to a future "Sales Tax Report V2" project, as a direct consequence of FD-001. No phase of this initiative — including `LedgerBalanceService`'s phases — may modify it.
- **The Financial Transaction vs. Financial Analytics calculation-family distinction** (Phase 2.3A) governs how `LedgerBalanceService`'s methods must be categorized: `amountWithTax()` and any balance-write method are transaction-safe by definition (they produce or reflect one customer's one balance state); no method on this service should ever be analytics-only, since the service itself does not aggregate across customers.
- **Invoices are out of scope for `LedgerBalanceService`** (Phase 2.3A §8, confirmed again by Phase 2.4's completion) — `InvoiceCalculationService` already owns invoice totals; `LedgerBalanceService` owns the customer-level ledger balance, a distinct concern even where they interact (an invoice payment affects both).
- **The delete-path sequencing invariant** (`reverseTransactionEffect()` before delete, `fixTheRunningBalance()` after commit) is confirmed correct and must be preserved exactly (Architecture Report §1.5) — this is a technical constraint, not an open business question.

### Pending Decision

*(All of the following were already tracked as open in `FINANCIAL_ENGINE_TODO.md` before this review; this review confirms none have been resolved in the interim, and adds two newly-discovered items.)*

- **Refund tax treatment** — no documented business rule; four implementations disagree in ways not visible to an end user until they compare a live and a persisted number.
- **Discount tax treatment** — same class of problem; a discount with `sales_tax_type='free'` can already produce visibly different available-credit numbers depending on which of the four methods computed it.
- **Partial payment allocation rule** — whether a partial payment's tax allocation should reference the original charge's tax rate or be recomputed against the remaining balance; not documented either way.
- **Tax-included vs. tax-added mental model confirmation** — lower-stakes than the above; this is asking the business to confirm an already-observed fact (a payment's amount is tax-inclusive; a charge's amount is tax-exclusive) rather than resolve a genuine disagreement, but it has not been formally confirmed in writing.
- **Invoice paid/open-amount equivalence procedure** — what happens procedurally if the (still-unrun) staging drift-detection snapshot from Phase 2.4 finds a real invoice where old and new logic disagree.
- **Available balance authority during transition** — whether the stored (`available_credit_balance`) or live (`getAvailableCredit()`) computation is authoritative if they disagree mid-migration.
- **`account_invoice` presentation differences** (discovered Phase 2.5) — the `_tab_credit.blade.php` views treat `account_invoice`-type rows as having `sales_tax` stored as a dollar amount (direct subtraction); `transaction_accounts_pdf.blade.php` has no such exception and uses the generic rate-based multiplication formula instead. These can currently show *different* numbers for the same transaction depending on which screen is used.
- **PDF vs. Blade presentation differences** — the same finding as above, stated generally: the PDF and the two tab views are not proven to agree for every transaction type, only for the two formulas Phase 2.5 centralized (tax-included, tax-added).
- **Front-end/admin "reverse charge" type-list discrepancy** (discovered Phase 2.5) — admin treats `charge` and `discount` as eligible for reverse-charge treatment; front-end treats only `charge`. Not resolved, deliberately preserved as-is through Phase 2.5.
- **`'credit'`/`'debit'` transaction-type treatment** (newly discovered in this review, §2, §7) — no existing implementation defines behavior for these two enum values at all. Because they are (as far as this review's static analysis can confirm) currently unused in application code, this does not describe a live bug — but it is a genuine gap in the truth table `amountWithTax()` needs, and must be resolved (even if the resolution is "these types are deprecated and cannot occur, enforced by removing them from the enum in a later phase") before the unified method can claim to be complete.
- **Aging and Account Status scope** — neither is named in the Architecture Report's four-service design. Needs an explicit decision: in-scope for a future phase, or permanently out of scope for the Financial Engine (i.e., they remain display-layer concerns on the `Customer` model forever). Left undecided, a future contributor may guess wrong in either direction.
- **"Payment Due" / overdue-amount column** — still hard-coded to `-` (Audit §4.7). Needs an explicit decision to implement or permanently de-scope; currently neither.

### Future Enhancement

- Customer Statements (no design work started; would consume `LedgerBalanceService`/`InvoiceCalculationService` once built).
- QuickBooks integration (same).
- Sales Tax Report V2 (the eventual, separately-approved successor to the deferred `SalesTaxReportEngine`).
- Unifying the stored vs. live available-balance computations into one call-the-other relationship, if `LedgerBalanceService`'s unified tax method makes them structurally identical (Architecture Report §Phase 4 idea) — an optimization to consider *after* the unified method exists and is proven correct, not a precondition for building it.

---

## 4. Code Impact Analysis

**Files expected to migrate:**

| File | Role | Call sites affected |
|---|---|---|
| `app/Helpers/CustomHelper.php` | `updateCreditBalance()`, `getAvailableCredit()`, `reverseTransactionEffect()`, `fixTheRunningBalance()` become thin delegators to `LedgerBalanceService`, mirroring the `updateInvoiceSummary()` pattern from Phase 2.4 | N/A (the methods themselves) |
| `CustomerAccount\{Payment,Refund,Discount,Charge}StoreController`, `UpdateController` | `updateCreditBalance()` callers | 5 |
| `Invoice\PaymentStoreController` (×2, admin+front-end), `Invoice\UpdateController`, `StoreController` | `updateCreditBalance()` callers | 5 |
| `Dashboard\{Damage,Fuel}ChargeStoreController`, `Dashboard\PaymentStoreController`, `Orders\AlertChargeController`, `Orders\AddToAccountPaymentController` | `updateCreditBalance()` callers | 5 |
| `Front\Checkout\PostController` | `updateCreditBalance()` caller | 1 |
| `ChargeService` (3 call sites) | `updateCreditBalance()` callers — also the bridge point to `BillingEngine` | 3 |
| `CustomerAccount\UpdateController`, `DeleteController`; `Invoice\UpdateController`, `DeleteInvoiceController`; `BulkDeleteController`, `RepairDeletedOrdersController` | `reverseTransactionEffect()` / `fixTheRunningBalance()` callers | 7 + 7 |
| 12 Blade/JS display locations | `getAvailableCredit()` callers, all read-only | 12 |

Total: **52 call sites**, confirmed by direct caller inventory during Phase 2.2 and re-cited here, not re-derived — this review found no reason to doubt that count.

**Files expected to remain (out of scope for `LedgerBalanceService`):**

- `app/Services/Reports/SalesTaxReportEngine.php` — deferred per FD-001.
- `app/Services/InvoiceCalculationService.php` and its callers — already migrated, Phase 2.4/2.5.
- `app/Services/TaxCalculationService.php` — the shared primitive `LedgerBalanceService::amountWithTax()` is expected to *use* internally, per the same "one source of truth for the tax formula" principle already established; not itself modified by this migration.
- `app/Services/BillingEngine.php` — a distinct, already-consolidated service for post-order charges; `LedgerBalanceService`'s job is to absorb the `CustomerAccount`-writing side of `ChargeService`'s bridge calls, not to touch `BillingEngine` itself.
- `Customer` model aging/status accessors — pending the scope decision in §3.

**Highest-risk call sites:**

1. **`ChargeService`'s 3 call sites** — the bridge point to `BillingEngine`, meaning a regression here could simultaneously affect the already-shipped, 197-test-covered Billing Engine consolidation (`docs/billing-engine-audit/`). Any `LedgerBalanceService` migration touching `ChargeService` must re-run that existing test suite as part of its own validation, not just the Financial Engine's own tests.
2. **`Front\Checkout\PostController`'s 1 call site** — the only call site outside the CRM/Dashboard/Invoice cluster; a checkout-time balance error is customer-facing at the point of payment, the least forgiving moment for a mistake.
3. **The delete-path pairing** (`reverseTransactionEffect()` + `fixTheRunningBalance()`) — sequencing-sensitive; migrating them separately (rather than together, as the Implementation Plan already specifies) would be the single easiest way to introduce a regression here.

**Expected migration order** (per Implementation Plan Phase 2.7-2.8, re-confirmed by this review as still the correct order):

```
Phase 2.6 → build LedgerBalanceService::amountWithTax(), zero callers
   (gated on the Pending Decisions in §3 above)
Phase 2.7.a → CRM CustomerAccount controllers (5 sites)
Phase 2.7.b → Invoice controllers (5 sites)
Phase 2.7.c → Dashboard/Orders (5 sites)
Phase 2.7.d → Checkout (1 site)
Phase 2.7.e → ChargeService (3 sites) — validate against BillingEngine's existing 197 tests
Phase 2.8 → getAvailableCredit + reverseTransactionEffect + fixTheRunningBalance together (26 sites, delete-path pairing preserved)
Phase 2.9 → remove deprecated CustomHelper wrappers, after one full billing cycle with no drift
```

**Estimated PR sizes:** consistent with the Implementation Plan's original estimates — each Phase 2.7 sub-phase should be small (3-5 call sites each), reviewable independently. Phase 2.8 is necessarily larger (26 sites) because the delete-path pairing cannot be safely split further without breaking the sequencing invariant in §2/§4.

**Rollback complexity:**

- **Per sub-phase: low**, following the same thin-wrapper pattern proven in Phase 2.4 (`CustomHelper`'s methods stay callable under their existing names throughout, so no caller needs to change if a sub-phase is reverted).
- **In aggregate: this is the point where "rollback" and "undo the damage" stop being the same thing.** A code revert restores correct *behavior*, but if a `LedgerBalanceService` bug has already run against real customer balances for any period of time, the *data* — real customers' stored balances — may need separate reconciliation, which a code rollback does not automatically provide. This is the single most important way `LedgerBalanceService` differs from every prior phase's rollback story, and should be stated explicitly to whoever approves Phase 2.6, not left implicit.

---

## 5. Validation Strategy

Before any `LedgerBalanceService` PR merges, the following must be validated — each maps to a specific risk identified above, not a generic checklist:

- **Balance reconciliation**: for a sample (ideally all) of customers in a copy of production data, `customers.available_credit_balance` before a migrated call site's change must equal the value after, for every existing transaction (no new activity). This is `FixRunningBalancesController`'s existing before/after diff pattern, already built and already trusted — reuse it, do not rebuild it.
- **Customer balance validation**: beyond the numeric match above, confirm no customer's balance sign flips (positive becomes negative or vice versa) unexpectedly — a sign flip is the class of error most likely to trigger a customer-facing complaint immediately.
- **Credit validation**: confirm `getAvailableCredit()`'s live computation and the stored `available_credit_balance` agree after migration, for the same sample — directly testing whether Architecture Report §Phase 4's "unify the two computations" opportunity is actually realized, not just theoretically possible.
- **Invoice reconciliation**: confirm that invoices already migrated to `InvoiceCalculationService` (Phase 2.4) are unaffected by `LedgerBalanceService` changes — the two services interact (a payment affects both an invoice's `paid_amount` and the customer's overall balance) but must remain independently correct.
- **Historical transaction validation**: replay a sample of *closed* historical transactions (payments, refunds, discounts, charges already fully resolved) through the new logic in a read-only/dry-run mode and confirm the replayed result matches the historical, already-settled outcome — this is the concrete test for whether the resolved truth table (§3) actually matches what the business has been doing in practice, not just what it says it wants going forward.
- **Running balance validation**: for a sample of customers with many transactions, confirm the full `fixTheRunningBalance()`-style replay produces a `customer_accounts.balance` sequence identical to the pre-migration sequence — this is the test that would have caught a `'credit'`/`'debit'` regression immediately, had those types ever been used.

**What this review cannot itself validate**: none of the above can be executed in a documentation-only review. `PHASE_2_4_VALIDATION_ADDENDUM.md` already established that this development environment has no accessible staging/production database and no `Invoice`/`InvoiceItem`/`CustomerAccount` model factories — the same limitation applies here, at a larger scale (every customer's balance, not one invoice). This is a Production Readiness Checklist item (§6), not something this review can close.

---

## 6. Production Readiness Checklist

Must be complete before any `LedgerBalanceService` PR merges:

- [ ] Business sign-off obtained on the complete tax-inclusion truth table, covering **all six** `customer_accounts.type` values (`payment`, `charge`, `discount`, `refund`, `credit`, `debit`) — not just the four already analyzed.
- [ ] `'credit'`/`'debit'` types explicitly resolved: either given defined behavior, or formally confirmed as deprecated/unused with a plan to remove them from the enum (in a later, separate phase — not silently ignored here).
- [ ] `account_invoice` presentation inconsistency (§3) resolved with a business-confirmed answer, and that answer reflected consistently across the PDF and both tab views.
- [ ] Front-end/admin reverse-charge type-list discrepancy (§3) resolved.
- [ ] Aging and Account Status scope explicitly decided (in-scope for a future phase, or permanently out of scope) and recorded.
- [ ] "Payment Due" / overdue-amount column scope explicitly decided.
- [ ] Available-balance authority (stored vs. live) during transition explicitly decided.
- [ ] Invoice paid/open-amount equivalence procedure decided (what happens if the still-unrun Phase 2.4 staging snapshot finds drift).
- [ ] A named technical owner assigned for `LedgerBalanceService` and a named reviewer for validation evidence — not yet assigned as of this review (already flagged as open in `FINANCIAL_ENGINE_TODO.md` §Open Technical Decisions).
- [ ] `FixRunningBalancesController`'s before/after diff validated against a **real or realistic copy of production data**, not synthetic data alone — the Phase 2.4 precedent (synthetic-only validation) is not sufficient here given the larger blast radius; a real-data pass is the specific bar for this service.
- [ ] Historical transaction replay validation (§5) executed and reviewed, not merely planned.
- [ ] `ChargeService`'s existing 197-test `BillingEngine` suite re-run and passing after any change touching its 3 call sites.
- [ ] Rollback data-reconciliation plan documented (not just code rollback) — see §4's "rollback complexity" note — before the first Phase 2.7 sub-phase merges, since by definition it starts writing to a real code path.
- [ ] Test-database/model-factory infrastructure gap (tracked since Phase 2.2, still open) at minimum has a stated mitigation for this specific service, given its blast radius exceeds every prior phase's.

---

## 7. Risk Register

**Critical**

- **The truth table for `amountWithTax()` is incomplete, not merely unconfirmed.** This review found that two of six required transaction types (`credit`, `debit`) have no defined behavior anywhere in the current codebase, in any of the four methods being unified. This is qualitatively worse than "four implementations disagree" (the Refund/Discount situation) — for these two types, there is no existing implementation to even disagree with. Building `amountWithTax()` without resolving this would mean inventing behavior for two enum values with zero precedent, exactly the failure mode this whole initiative exists to prevent.
- **Real customer balance data is at stake, with no proven rollback-to-correct-data story.** Every prior phase's "rollback plan" has been a pure code revert. This phase's blast radius (52 call sites touching every customer's balance) means a code revert restores correct *logic* but does not automatically un-corrupt any *data* written while incorrect logic ran. No document in this initiative has yet specified what that reconciliation process would be.

**High**

- **`ChargeService`'s bridge to `BillingEngine`** — a regression here risks the already-shipped, separately-tested Billing Engine consolidation, a cross-initiative risk not fully within this review's own document set.
- **The delete-path sequencing invariant** — confirmed correct today, but must survive the migration of both `reverseTransactionEffect()` and `fixTheRunningBalance()` together; splitting this pairing across separate sub-phases (a plausible temptation, since Phase 2.7 otherwise splits by module) would be a self-inflicted regression risk.
- **No staging/production-scale validation is possible in this development environment**, and this service's correctness bar (per §6) explicitly requires real-or-realistic data, not synthetic data alone — a materially higher bar than Phase 2.4 met, in an environment that has not yet demonstrated it can meet it.

**Medium**

- **`account_invoice` and PDF/Blade presentation inconsistencies** (§3) — currently a display-only divergence (per Phase 2.5's finding, no write path is affected by these two specific issues), but `LedgerBalanceService`'s unified method will need to decide whether to standardize this behavior, which is a business decision layered onto a technical migration.
- **Aging/Account Status/Payment Due scope ambiguity** — not dangerous in itself, but capable of causing scope disagreement mid-implementation if not settled beforehand.
- **No named technical owner or reviewer** for this specific service — a process risk more than a technical one, but relevant given the stakes.

**Low**

- **The 12 `getAvailableCredit()` call sites** — all read-only display, lower risk than any write path, though still requiring the "does the live computation now match the stored value" validation in §5.
- **Test-database/factory infrastructure gap** — a known, long-standing limitation with an established mitigation pattern (algebraic proof + synthetic validation) from Phase 2.4, even though this service's scale argues for eventually exceeding that pattern (see High, above, for the scale-specific escalation of this same underlying gap).

---

## 8. Recommended Phase 2.6 Scope

**The smallest safe implementation of `LedgerBalanceService` is: build the class and its public method signatures, with `amountWithTax()`'s actual branch logic populated *only* to the extent the truth table is business-confirmed at the time of implementation — and if the truth table is not fully confirmed, do not build `amountWithTax()`'s real logic at all yet.**

This mirrors exactly the discipline already proven safe in Phase 2.1 (`TaxCalculationService` built with zero callers, fully unit-tested, before anything depended on it) and explicitly rejects the temptation to "make progress" on the unified method by guessing at the unresolved branches.

**Should be included in Phase 2.6:**

- The `LedgerBalanceService` class skeleton and confirmed method signatures (`applyTransaction()`, `reverseTransaction()`, `rebuildForCustomer()`, `currentAvailableCredit()` — per Architecture Report §2.2's proposal, `applyTransaction()`'s and `reverseTransaction()`'s bodies can be structured to delegate to `amountWithTax()`, even before that method's branches are all confirmed).
- `amountWithTax()`'s implementation for the branches that **are** already confirmed and non-controversial: `payment` (tax never added — already correct and consistent across all four existing methods) and `order` (existing behavior already consistent).
- `charge` type, since Phase 2.5 already established and centralized the correct `sales_tax_type==='add'` rule on the display side — the balance-affecting version of this exact rule is a low-risk, already-validated formula to bring over.
- Unit tests for every branch actually implemented, following the Phase 2.1-2.3A pattern of testing against concrete, previously-confirmed numeric examples.
- Zero callers wired in — this is a "build and prove," not "migrate," phase, exactly like Phase 2.1.

**Should NOT be included in Phase 2.6:**

- `amountWithTax()`'s `refund`, `discount`, `credit`, or `debit` branches — these are exactly the branches gated on unresolved business decisions (§3, §6). Implementing them now, even provisionally, risks becoming the encoded-without-sign-off behavior this whole initiative was created to eliminate.
- Any migration of the 52 existing call sites (that is Phase 2.7-2.8, unchanged from the Implementation Plan).
- Any change to `CustomHelper`'s existing methods — they remain the production-serving implementation, untouched, until their replacement is proven.
- Any change to `Customer` model aging/status accessors, pending the scope decision in §3/§6.
- Any attempt to "unify" the stored vs. live balance computations (Architecture Report's Phase 4 idea) — that is a follow-on optimization once the unified tax method exists and is proven, not part of building the method itself.

---

## 9. Success Criteria

Phase 2.6 (scoped per §8) will be considered successful when:

- `LedgerBalanceService` exists with the confirmed method signatures, and `amountWithTax()` correctly handles every branch that had a business-confirmed, unambiguous answer at the time of implementation.
- Every implemented branch is unit-tested against a concrete numeric example already established as correct by a prior phase's evidence (e.g., the `payment` branch tested against the same class of example used in Phase 2.1-2.2's equivalence proofs).
- Zero existing application behavior changes — confirmed the same way every prior phase confirmed it: `git status` shows no unrelated file touched, the existing test suite passes unchanged, and `grep` confirms zero new callers into the new class from production code.
- The unimplemented branches (`refund`, `discount`, `credit`, `debit`) are explicitly documented as "not yet implemented, pending business sign-off" in the class's own docblock — not silently absent, the same discipline `TaxCalculationService`'s docblock already models for its own scope boundaries.
- This review's Production Readiness Checklist (§6) shows visible progress (at minimum, the truth table sign-off item) — Phase 2.6 does not need to close every item, but should not be considered the trigger to *start* Phase 2.7 until more of §6 is closed than is closed today (currently, zero items in §6 are checked).

---

## 10. Go / No-Go Recommendation

## **GO WITH CONDITIONS**

**What this means concretely:** proceed with the narrowly-scoped Phase 2.6 described in §8 (build the service skeleton and only the already-confirmed branches, zero callers migrated). **Do not proceed to Phase 2.7 or 2.8** (migrating any of the 52 real call sites) until the conditions below are met.

**Conditions for proceeding beyond Phase 2.6's narrow scope:**

1. Business sign-off obtained on the complete truth table — all six transaction types, not the four already analyzed, explicitly including a decision on `'credit'`/`'debit'`.
2. The `account_invoice` and front-end/admin presentation inconsistencies (§3) resolved with a documented business answer.
3. Aging, Account Status, and "Payment Due" scope explicitly decided (in-scope later, or permanently out of scope).
4. A named technical owner and reviewer assigned.
5. A data-reconciliation rollback plan documented — not just a code-revert plan — given this is the first phase where a mistake could affect real, already-settled customer balances rather than a report or a display value.
6. `FixRunningBalancesController` validated against real or realistic production-scale data at least once before Phase 2.7 begins, closing the gap Phase 2.4's synthetic-only validation left open at a smaller scale.

**Evidence supporting this recommendation:**

- **In favor of proceeding at all (not NO-GO)**: the pattern this initiative has used for every prior service (build isolated, prove equivalence, migrate in small validated batches) has worked five times in a row, including catching real bugs before they shipped (the Phase 2.4 front-end ordering issue, the Phase 2.2 rounding-order bug, the Phase 2.3 Dashboard formula, the Phase 2.5 Blade-comment-in-`@php` mistake). There is no evidence this pattern would fail here — only evidence that this specific service has more unresolved inputs than any prior one.
- **In favor of conditions, not an unconditional GO**: this review confirmed, via direct code inspection rather than repeating prior summaries, that the truth table `amountWithTax()` needs is not just "unconfirmed" but genuinely incomplete (the `'credit'`/`'debit'` finding, §2/§7) — a fact no prior phase's documentation stated, because no prior phase needed to look at all six enum values at once. Proceeding to build the full unified method today would mean guessing at two branches with zero precedent, which is precisely the mistake this entire initiative exists to correct.
- **Against a flat NO-GO**: the narrow Phase 2.6 scope in §8 is genuinely safe to start now — it has zero callers, mirrors an already-proven pattern (Phase 2.1), and does not require any of the six pending business decisions to be resolved for its reduced scope. Blocking all forward motion would waste the safe part of this work while waiting on business input that is orthogonal to it.

---

## Final Executive Summary

- **Current Financial Engine version**: 2.0 (`InvoiceCalculationService` + Blade tax-breakdown consolidation, per `FINANCIAL_ENGINE_MASTER_ROADMAP.md` §0).
- **Overall completion**: ~40% of the full Implementation Plan (2.0-2.9), unchanged by this review since it added no implementation.
- **`LedgerBalanceService` readiness**: **Partially defined.** The service boundary and proposed method signatures are fully defined (Architecture Report §2.2). The core deliverable — `amountWithTax()`'s complete truth table — is **not fully defined**, and this review found it to be more incomplete than previously documented (the `'credit'`/`'debit'` gap).
- **Remaining business decisions**: six pending items in §3, unchanged in count from before this review except for two newly-discovered ones (`'credit'`/`'debit'` treatment, and the review's explicit call-out that "Customer Credit" as a requested matrix term is itself ambiguous).
- **Remaining technical risks**: two Critical, three High, three Medium, two Low (§7) — dominated by the incomplete truth table and the fact that this is the first phase where a mistake affects real, already-settled money rather than a report or single record.
- **Should Phase 2.6 begin?** **Yes, in the narrow form specified in §8** — build the service skeleton and only the branches already confirmed correct by prior phases' evidence, with zero callers migrated. **Phase 2.7/2.8 (migrating the 52 real call sites) should not begin** until the six conditions in §10 are met.
- **What must happen first (for anything beyond §8's narrow scope)**: business sign-off on the complete six-type truth table; resolution of the `account_invoice` and front-end/admin presentation discrepancies; an explicit Aging/Account Status/Payment Due scope decision; a named owner and reviewer; a data-reconciliation rollback plan; and at least one real-or-realistic-data validation pass with `FixRunningBalancesController`, closing the gap left open since Phase 2.4.
