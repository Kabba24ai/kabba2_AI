# Financial Truth Table

Document date: 2026-07-02
Branch: `raj_development`
Status: **Documentation only. No application code, database, migration, or business-rule change made.**
Financial Engine Version: **2.0.1** (Financial Truth Table Complete)
Purpose: **The permanent, authoritative accounting-policy reference for the Financial Engine.** `LedgerBalanceService` and every future Financial Engine service (`FinancialReportingAdapter`, any future Customer Balance Service, any future accounting integration) must *implement* this document. None of them may define new business policy themselves — if this document doesn't answer a question, the answer must come from the business, be recorded here as an amendment, and only then be implemented.

Everything in this document was re-verified directly against the current codebase during this phase — not assumed from prior Financial Engine documentation. Two specific places where a prior assumption would have been wrong are called out explicitly in §3 and §5, as evidence this re-verification was substantive, not a formality.

---

## 1. Executive Summary

Every phase of this initiative so far has answered a narrower question than the one `LedgerBalanceService` needs answered. Phase 2.1 asked "what is the tax formula for one transaction." Phase 2.2-2.3 asked "does a report's total match the transactions it summarizes." Phase 2.4 asked "does one invoice's total match its payments." Phase 2.5A asked "is `LedgerBalanceService` ready to build" and concluded, correctly, that it could not answer that question without first answering a bigger one: **what does this business consider the correct financial behavior for every kind of transaction it can create?**

This document exists to answer that question once, completely, in one place — not per-service, not per-controller, not re-derived by whoever happens to be building the next Financial Engine component. `LedgerBalanceService` depends on it because `LedgerBalanceService`'s core deliverable, `amountWithTax()`, is not a calculation problem (the arithmetic is simple and already proven in `TaxCalculationService`) — it is a **policy lookup problem**: given a transaction type and its attributes, what treatment does this business intend? Every confirmed bug and every discovered inconsistency in this initiative (the Billing Summary bug, the Dashboard bug, the `account_invoice` presentation inconsistency, the front-end/admin reverse-charge discrepancy) has the same root cause — someone answered this lookup independently, in a new location, without a single reference to check against.

**Business policy must exist before implementation** for a reason stronger than good practice: this codebase already has direct, verified proof of what happens when it doesn't. Four independent methods in `CustomHelper.php` each answer "how does this transaction type affect a customer's balance" differently, for the same transaction types, and have done so for long enough that a dedicated repair tool (`FixRunningBalancesController`) exists specifically to find and fix the drift this has caused. `LedgerBalanceService` is meant to be the fix for that pattern — writing it before the policy is settled would not fix the pattern, it would become the fifth independent answer.

This document is organized so that every future question of the form "what should happen when X" can be answered by looking it up here, not by reading four methods and guessing which one is right.

---

## 2. Financial Transaction Inventory

Every transaction type below was verified directly against the code during this phase — via direct file reads and two independent, parallel research passes — not carried over from prior documentation.

**Verified: the complete, current list of `customer_accounts.type` enum values (8 total)**, reconstructed from every migration that has ever touched this column, in order:

| Migration | Date | Change |
|---|---|---|
| `2025_07_17_111412_create_customer_accounts_table.php` | 2025-07-17 | Creates enum: `charge`, `payment`, `discount`, `credit`, `debit`, `refund` |
| `2025_07_23_055744_alter_customer_accounts_type_enum.php` | 2025-07-23 | Adds `order` |
| `2026_02_24_074506_modify_customer_accounts_type_enum.php` | 2026-02-24 | Adds `account_invoice` |

**Final list**: `charge`, `payment`, `discount`, `credit`, `debit`, `refund`, `order`, `account_invoice`.

**Transaction types documented in this Truth Table (§3), reconciling the enum above with sub-kinds distinguished only by `reason`/context, and with concepts that exist outside `customer_accounts` entirely:**

1. **Payment** — `customer_accounts.type = 'payment'`.
2. **Refund** — `customer_accounts.type = 'refund'`.
3. **Discount** — `customer_accounts.type = 'discount'`.
4. **Credit** — `customer_accounts.type = 'credit'`. **Verified dead**: zero creation sites anywhere in the codebase.
5. **Debit** — `customer_accounts.type = 'debit'`. **Verified dead**: zero creation sites anywhere in the codebase.
6. **Manual Charge** — `customer_accounts.type = 'charge'` with an arbitrary, user-entered `reason` (not `'Fuel Charge'` or `'Damages'`).
7. **Fuel Charge** — `customer_accounts.type = 'charge'`, `reason = 'Fuel Charge'`.
8. **Damage Charge** — `customer_accounts.type = 'charge'`, `reason = 'Damages'`.
9. **Extension Charge** — does **not** create a `customer_accounts` row at all; exists only as a `billing_charges` row (`billing_charge_type = 'extension'`) plus a child `Order`.
10. **Order** — `customer_accounts.type = 'order'` (checkout-to-account and COD-to-account conversions).
11. **Invoice** — the `invoices` table's own lifecycle (`subtotal`/`sales_tax`/`total`/`paid_amount`/`open_amount`/`invoice_status`), a distinct entity from any `customer_accounts` row.
12. **Account Invoice** — `customer_accounts.type = 'account_invoice'`, a summary ledger row created alongside an `invoice_type = 'account'` invoice.
13. **Adjustment** — not a transaction type; a delta applied to an existing Fuel/Damage `BillingCharge.amount`, logged via `OrderProductFuelChargeLog`/`OrderProductDamageChargeLog`.
14. **Void** — a real, implemented feature (`AuthorizeNetService::voidOrder()`, `VoidPaymentController`), but operates on **`order_payments`/`OrderPaymentStatus`**, not on `customer_accounts` at all.
15. **Write-Off** — does not exist under this name. The closest real analog is `ChargeService::markUncollectible()` / `BillingEngine::markUncollectible()`, which sets a status flag and does **not** create any offsetting ledger entry.
16. **Unimplemented `BillingChargeType` values** (`service_ticket`, `cleaning`, `delivery`, `misc`) — defined in the enum, zero creation code found for any of them.

No other financial transaction type was found. This list is believed complete as of this document's date; if a future code change introduces a new `customer_accounts.type` value or a new `BillingChargeType`, this document must be amended before any Financial Engine service is built or extended to handle it.

---

## 3. Financial Truth Table

Presented as two linked tables (identity/tax, then effects/status) for the same 16 transaction types, since an 18-column single table would not be legible. Both halves together constitute "the Financial Truth Table" required by this phase.

### 3a. Identity and Tax Treatment

| # | Transaction Type | Description | Example Scenario | Taxable | Tax Calculation Method | Transaction-Safe or Analytics-Only |
|---|---|---|---|---|---|---|
| 1 | **Payment** | A customer pays money toward their account or an invoice. `amount` is tax-inclusive. | Customer pays $150 toward Invoice #12345. | No — amount already includes tax | None on the balance-write side (amount used as-is); `sales_tax` stored informationally at the current rate. Display uses `TaxCalculationService::extractTaxFromInclusiveAmount()` (division). | Transaction-Safe |
| 2 | **Refund** | A customer receives money back, reducing what they owe or increasing available credit. | Customer returns equipment and receives a $75 refund. | **Depends — divergent across existing implementations** | `updateCreditBalance()` always taxes a refund if the customer is taxable (no `sales_tax_type` check); the other three methods disagree (see §5). No form ever collects `sales_tax_type` for a refund. | Transaction-Safe (once resolved) |
| 3 | **Discount** | A reduction applied to a customer's balance. | Manager applies a $50 goodwill discount to a customer's account. | **Depends on `sales_tax_type`, inconsistently applied** | `updateCreditBalance()`/`reverseTransactionEffect()` branch on `sales_tax_type` (`add`/`reverse`/`free`); `getAvailableCredit()` ignores `sales_tax_type` entirely and always taxes a discount if the customer is taxable (see §5). | Transaction-Safe (once resolved) |
| 4 | **Credit** (`type='credit'`) | Enum value exists; no code creates it. | *(No real-world scenario exists — never produced by any current feature.)* | **Undefined** — no implementation anywhere | **Undefined** — none of the four candidate balance methods has a branch for this type | Undefined |
| 5 | **Debit** (`type='debit'`) | Enum value exists; no code creates it. | *(No real-world scenario exists — never produced by any current feature.)* | **Undefined** | **Undefined** | Undefined |
| 6 | **Manual Charge** | An admin manually adds an arbitrary charge to a customer's account via the CRM Charge modal. | Admin adds a $40 "Late Return Fee" charge to a customer's account. | Depends on `sales_tax_type === 'add'` | Multiplication: `TaxCalculationService::addTaxToExclusiveAmount()`, already centralized (Phase 2.5) on the display side; not yet centralized on the balance-write side. | Transaction-Safe |
| 7 | **Fuel Charge** | A system- or admin-created charge for fuel not refilled at return. | Customer is billed $45 for returning equipment without refueling. | Same as Manual Charge — `sales_tax_type === 'add'` | Same formula as Manual Charge. Note: `billing_charges.tax_amount` is always stored as `0` for Fuel Charges — tax is a flag only, never materialized as a stored amount (per `docs/billing-engine-audit/CURRENT_BILLING_ARCHITECTURE.md`, re-confirmed this phase). | Transaction-Safe |
| 8 | **Damage Charge** | A charge for equipment damage found at return or inspection. | Customer is billed $350 for a damaged hydraulic hose. | Same as Manual Charge | Same as Fuel Charge (tax flag only, `tax_amount` always `0` today). | Transaction-Safe |
| 9 | **Extension Charge** | A charge for extending a rental period past its original return date. | Customer extends a 3-day rental by 2 more days for $120. | Depends on an `add_tax` flag passed to `Extension\StoreController` | Computed inline in the controller itself: `round($baseAmount * $salesTaxRate, 2)` if `add_tax`, else `0` — entirely separate from `CustomHelper`'s four methods and from `TaxCalculationService`. | Transaction-Safe (self-contained, not yet using the shared service) |
| 10 | **Order** (`type='order'`) | A ledger row created when a customer pays for an order via account credit (checkout or COD-to-account conversion), rather than a card/cash payment. | Customer selects "Pay via Account" at checkout for a $200 rental order. | Handled by the **caller**, not this method | `updateCreditBalance()`'s `case 'order':` takes an `$externalTaxAmount` parameter supplied by the caller (e.g. checkout) — tax is computed upstream, not by this method. | Transaction-Safe |
| 11 | **Invoice** (the `invoices` table lifecycle) | The invoice's own `subtotal`/`sales_tax`/`total`, summed from line items. | An invoice is generated for a customer's monthly account charges, totaling $544.35. | Yes — sum of line-item `invoice_items.tax`, a **dollar amount**, not a rate | `InvoiceCalculationService::recomputeSummary()` — already correct, moved verbatim from `CustomHelper::updateInvoiceSummary()` (Phase 2.4). Does not call `TaxCalculationService` at all (no rate-based math needed at this level). | Transaction-Safe (already implemented) |
| 12 | **Account Invoice** (`type='account_invoice'`) | A summary ledger row created alongside an `invoice_type='account'` invoice; `sales_tax` here is a **dollar amount** (the invoice's), not a rate. | An "account" invoice for $544.35 is created; a matching `account_invoice` ledger row is stored for reference. | N/A — informational only | **Confirmed this phase: this row does NOT call `updateCreditBalance()` at all** (verified directly in `Invoice\StoreController.php` — no such call appears near its creation). It does not affect any balance. Its `sales_tax` value is only ever displayed (with the direct-subtraction formula in the two `_tab_credit.blade.php` views; incorrectly as a rate-multiplication in the PDF — Phase 2.5 finding, still open). | Transaction-Safe (display only) |
| 13 | **Adjustment** | A correction to an existing Fuel/Damage `BillingCharge.amount`, not a new transaction. | A $50 damage charge is adjusted down to $30 after review. | **Not verified this phase** — the adjustment changes `amount` directly; whether/how it affects any already-taxed portion was not traced to a conclusion | **Low confidence — needs further verification**, not answered here | Presumed Transaction-Safe, unconfirmed |
| 14 | **Void** | Cancels an unsettled Authorize.Net card transaction before it settles. | A same-day card charge is voided before end-of-day settlement, so it never appears on the customer's statement. | N/A — the transaction is cancelled, not adjusted | N/A — no tax recalculation; `AuthorizeNetService::voidOrder()` calls the gateway's void API directly | Transaction-Safe (but out of `customer_accounts` scope entirely — see §5) |
| 15 | **Write-Off** (`markUncollectible()`) | Flags a Fuel/Damage charge as uncollectible; does not adjust any balance or create an offsetting entry. | A $75 fuel charge is marked uncollectible after repeated failed collection attempts. | N/A | N/A — status flag only | N/A (not a calculation) |
| 16 | **Unimplemented `BillingChargeType`s** (Service Ticket, Cleaning, Delivery, Misc) | Enum values exist; no creation code exists for any of them. | *(No real-world scenario exists yet.)* | Undefined | Undefined | Undefined |

### 3b. Effects, Ownership, and Decision Status

| # | Transaction Type | Balance Effect | Running Balance Effect | Available Credit Effect | Customer Balance Effect | Invoice Effect | Ledger Effect | Reporting Effect | Reversible | Related Transaction Types | Current Implementation | Future Financial Engine Owner | Business Decision Status |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| 1 | Payment | Decreases balance owed by the full `amount` | Recorded via `customer_accounts.balance` snapshot | Increases | Decreases amount owed | If `invoice_id` set, increments `invoices.paid_amount` via `InvoiceCalculationService` | Creates 1 `customer_accounts` row | Counted in `SalesReportEngineV2`/`SalesTaxReportEngine`/`PaymentReconciliationLedger` Stream C | Yes — `reverseTransactionEffect()` + delete path | Order, Account Invoice, Invoice | `updateCreditBalance()` (consistent across all 4 methods) | `LedgerBalanceService::amountWithTax()` | **Approved** |
| 2 | Refund | Increases balance owed (reverses a prior payment/charge) | Yes | Decreases | Increases amount owed | Not directly linked to invoice paid/open amounts today | Creates 1 `customer_accounts` row | Stream B in `SalesTaxReportEngine`/`PaymentReconciliationLedger` (order-level refunds only — CRM ledger refunds are not separately reported) | Yes, same mechanism as Payment | Payment, Discount | `RefundStoreController` | `LedgerBalanceService::amountWithTax()` | **Pending Business Decision** |
| 3 | Discount | Decreases balance owed | Yes | Increases | Decreases amount owed | Can appear as an `invoice_items` line (type=`discount`) counted in `InvoiceCalculationService` | Creates 1 `customer_accounts` row (and optionally 1 `invoice_items` row) | Not separately broken out in current reports | Yes | Refund, Manual Charge (as a reversal — see `markResolved()`) | `DiscountStoreController`, `ChargeService::markResolved()` (creates a discount as a charge-reversal) | `LedgerBalanceService::amountWithTax()` | **Pending Business Decision** |
| 4 | Credit | None — never created | None | None | None | None | None | None | N/A | None (dormant) | None (dead enum value) | Undecided — define or deprecate | **Undefined** |
| 5 | Debit | None — never created | None | None | None | None | None | None | N/A | None (dormant) | None (dead enum value) | Undecided — define or deprecate | **Undefined** |
| 6 | Manual Charge | Increases balance owed | Yes | Decreases | Increases amount owed | Can appear as an `invoice_items` line | Creates 1 `customer_accounts` row | Included in `SalesReportEngineV2`'s Billing Engine revenue stream **only if** it happens to match `reason='Fuel Charge'`/`'Damages'` and bridges to `BillingEngine`; **any other `reason` string never reaches `billing_charges` at all**, and is only captured via the Payment stream once eventually paid | Yes | Fuel Charge, Damage Charge (same mechanism, different `reason`) | `ChargeStoreController` | `LedgerBalanceService::amountWithTax()` | **Approved** for the tax formula (already proven, Phase 2.5); **the reporting asymmetry (non-Fuel/Damage charges never reach `billing_charges`) is a newly-flagged gap, Pending Business Decision** |
| 7 | Fuel Charge | Increases balance owed | Yes | Decreases | Increases amount owed | Can appear as an `invoice_items` line | Creates 1 `customer_accounts` row **and** 1 `billing_charges` row (bridge) | Counted in `SalesReportEngineV2`'s Billing Engine revenue and the Fuel Charge Alerts report | Yes | Manual Charge, Damage Charge, Adjustment | `FuelChargeStoreController`, `AlertChargeController`, `ChargeService`, mobile checklist | `LedgerBalanceService::amountWithTax()` | **Approved** |
| 8 | Damage Charge | Increases balance owed | Yes | Decreases | Increases amount owed | Can appear as an `invoice_items` line | Creates 1 `customer_accounts` row **and** 1 `billing_charges` row (bridge) | Counted in `SalesReportEngineV2`'s Billing Engine revenue and the New Damage Alerts report | Yes | Manual Charge, Fuel Charge, Adjustment | `DamageChargeStoreController`, `AlertChargeController`, `ChargeService` | `LedgerBalanceService::amountWithTax()` | **Approved** |
| 9 | Extension Charge | **No `customer_accounts` effect at all** — `customer_account_id` is explicitly `null` for this type | N/A (no ledger row) | N/A | Indirect only, via the child Order's own payment | The child Order carries its own totals, separate from any invoice | Creates 1 `billing_charges` row + 1 child `Order` | Counted in `SalesReportEngineV2`'s Billing Engine revenue (specifically because it has no `customer_account_id`, avoiding double-count) | Not clearly defined — no reversal mechanism identified | Order (the child order it creates) | `Extension\StoreController` → `BillingEngine::charge()` | Out of `LedgerBalanceService` scope entirely (never touches `customer_accounts`) | **Approved** (scope boundary), tax formula itself unreviewed by this Truth Table (self-contained, not using `TaxCalculationService`) |
| 10 | Order | Decreases available balance (uses account credit) | Yes | Decreases | Increases amount owed | Can be linked to an invoice via `Invoice\StoreController` | Creates 1 `customer_accounts` row per order product | Order-level revenue is already primarily tracked via `orders`/`order_payments`, not this row | Not clearly defined | Payment, Invoice | `Front\Checkout\PostController`, `AddToAccountPaymentController`, `Invoice\StoreController` | `LedgerBalanceService::amountWithTax()` | **Approved** (tax computed by caller, not this method — a clean, already-consistent boundary) |
| 11 | Invoice | N/A (this is the invoice's own totals, not a ledger balance effect) | N/A | N/A | Indirect, via linked payments | Owns `subtotal`/`sales_tax`/`total`/`paid_amount`/`open_amount`/`invoice_status` | N/A (not a ledger row itself) | Not directly reported; invoice payments flow into Payment's reporting effect | Yes — edit/delete paths call `reverseTransactionEffect()`+`fixTheRunningBalance()` for linked ledger rows | Payment, Account Invoice, Order | `InvoiceCalculationService::recomputeSummary()` | Already implemented — `InvoiceCalculationService` | **Approved** |
| 12 | Account Invoice | **None — confirmed this phase: does not call `updateCreditBalance()`** | None | None | None | Mirrors the invoice's own total (for display/reference) | Creates 1 `customer_accounts` row, `invoice_id`-linked | Not separately reported | Has a dedicated delete handler (`DeleteInvoiceController.php:67`) | Invoice | `Invoice\StoreController` | Not a `LedgerBalanceService` concern (no balance effect) — presentation-layer questions remain for `TaxCalculationService`/display | **Pending Business Decision** (the PDF-vs-tab-view presentation inconsistency, Phase 2.5) |
| 13 | Adjustment | Indirect — changes the underlying `BillingCharge.amount`, which affects that charge's eventual balance effect when paid | Not verified | Not verified | Not verified | Not directly | Does not create a new `customer_accounts` row; modifies an existing `BillingCharge` | Adjusted amount flows into whatever the charge already reports as | Implicitly, by adjusting again | Fuel Charge, Damage Charge | `AdjustController`, `OrderProduct{Fuel,Damage}ChargeLog` | Undecided — likely `LedgerBalanceService` if it ever needs to trigger a balance recompute, unconfirmed | **Needs Technical Decision** — verify whether/how this should trigger any ledger recompute |
| 14 | Void | **None on `customer_accounts`** — operates entirely on `order_payments`/`OrderPaymentStatus` | N/A | N/A | N/A | N/A unless the voided payment was linked to an invoice (not verified) | No `customer_accounts` effect | Presumably excluded from revenue reports once voided (not verified against `SalesReportEngineV2`'s filters) | It IS itself a reversal action | Payment (the thing it cancels) | `AuthorizeNetService::voidOrder()`, `VoidPaymentController` | Out of `LedgerBalanceService` scope (different table entirely) — but reporting-engine interaction unverified | **Needs Technical Decision** — confirm reporting engines correctly exclude voided payments |
| 15 | Write-Off | **None** — `markUncollectible()` only sets a status field, no ledger entry created or reversed | None | None | None (the balance remains "owed," just flagged as uncollectible) | N/A | No `customer_accounts` effect from `markUncollectible()` itself | Presumably still counted as outstanding in any AR-style report (not verified — no such report exists yet, see §4.7 in `PHASE_2_5A_LEDGER_READINESS_REVIEW.md`, "Payment Due") | No — it is a terminal status, not a reversal | Fuel Charge, Damage Charge | `ChargeService::markUncollectible()`, `BillingEngine::markUncollectible()` | Out of `LedgerBalanceService` scope as currently implemented (no balance effect exists to migrate) | **Needs Business Decision** — should a write-off ever actually reduce the customer's owed balance, or is "flagged but still technically owed" the intended permanent behavior? |
| 16 | Unimplemented `BillingChargeType`s | None — never created | None | None | None | None | None | None | N/A | None | None | Undecided | **Future Enhancement** |

---

## 4. Decision Confidence

| Transaction Type | Confidence | Why |
|---|---|---|
| Payment | **High** | All four `CustomHelper` methods agree; already re-verified directly this phase and in Phase 2.5A; the confirmed Billing Summary and Dashboard bugs were both display-layer, never ledger-layer, for this type |
| Refund | **Medium** | The *fact* of the divergence is High Confidence (directly re-read this phase); the *correct resolution* is Low Confidence, since no business rule has ever been documented |
| Discount | **Medium** | Same reasoning as Refund — divergence is certain, correct answer is not |
| Credit | **High** | High confidence that it is dead code (two independent broad searches this phase found zero creation sites); zero confidence on what it *should* do if ever revived |
| Debit | **High** | Same as Credit |
| Manual Charge | **High** for the tax formula (already proven, Phase 2.5); **Medium** for the reporting-asymmetry finding (newly discovered, not yet cross-checked against every report) |
| Fuel Charge | **High** — thoroughly documented in `docs/billing-engine-audit/`, re-confirmed this phase |
| Damage Charge | **High** — same as Fuel Charge |
| Extension Charge | **High** on the mechanism (verified this phase: no `CustomerAccount` row, child Order + `BillingEngine` bridge only); **Medium** on the tax formula's correctness, since it was not re-derived or compared against `TaxCalculationService` in this phase |
| Order | **High** — verified this phase across all 3 creation sites; consistent, simple design (tax computed by caller) |
| Invoice | **High** — already implemented and validated (Phase 2.4, including a synthetic execution proof) |
| Account Invoice | **High** on the mechanism (verified this phase: does not affect balance); **Medium** on the presentation question (already flagged Phase 2.5, still unresolved) |
| Adjustment | **Low** — this phase found the mechanism exists but did not trace its full interaction with tax/balance recomputation to a conclusion; flagged for follow-up, not resolved here |
| Void | **Medium** — the void mechanism itself is clearly and fully implemented (High Confidence on that), but its interaction with reporting engines and any invoice linkage was not traced (Low Confidence there), yielding a blended Medium |
| Write-Off | **Medium** — the mechanism (`markUncollectible()`) is clearly implemented and unambiguous in what it does *not* do (create a ledger entry); whether that's the *correct* long-term business behavior is unconfirmed |
| Unimplemented `BillingChargeType`s | **High** confidence that they are unimplemented (verified via `docs/billing-engine-audit/CURRENT_BILLING_ARCHITECTURE.md` and direct grep); no confidence possible on undefined future behavior |

---

## 5. Missing Business Rules

Per instruction, these are documented, not answered or assumed.

1. **Refund tax treatment** — no documented rule exists anywhere; four implementations disagree (§3b).
2. **Discount tax treatment**, specifically whether `sales_tax_type` should govern taxation consistently across all balance computations, or whether `getAvailableCredit()`'s current behavior (ignore `sales_tax_type`, always tax if customer taxable) reflects an intentional, undocumented rule.
3. **Whether `'credit'` and `'debit'` should ever be used**, and if so, with what balance and tax behavior. If not, whether they should be formally removed from the enum.
4. **The Manual Charge / `BillingEngine` reporting asymmetry** (newly discovered this phase): a manual charge with any `reason` other than exactly `'Fuel Charge'` or `'Damages'` creates a `customer_accounts` row but is **never** bridged into `billing_charges`, meaning `SalesReportEngineV2`'s Billing Engine revenue stream never sees it (it would only appear later, if and when paid, via the Payment stream). Is this intentional (only Fuel/Damage need `BillingEngine` visibility) or a gap?
5. **The `account_invoice` presentation inconsistency** (Phase 2.5, still open): the two `_tab_credit.blade.php` views treat its `sales_tax` as a dollar amount (correct, per this phase's confirmation of how it's stored); the PDF view has no such exception and would incorrectly treat it as a rate. Which display is correct, and should the other be corrected to match — the presentation layer's answer must trace back to the same fact this document now confirms about storage.
6. **Front-end/admin "reverse charge" type-list discrepancy** (Phase 2.5): admin includes `discount` as eligible for reverse-charge treatment; front-end does not.
7. **Adjustment's tax interaction** — not traced to a conclusion this phase; needs a technical (not necessarily business) investigation before a policy question can even be framed.
8. **Void's interaction with reporting and invoices** — not traced this phase; needs a technical investigation to determine whether a voided payment is correctly excluded from revenue reports and whether it can ever be linked to an invoice.
9. **Whether "Write-Off" should ever reduce a customer's recorded balance owed**, or whether "flagged as uncollectible, balance technically unchanged" is the permanent intended design. Directly relevant to any future "Payment Due"/aging/collections feature (already flagged as undecided scope in `PHASE_2_5A_LEDGER_READINESS_REVIEW.md`).
10. **Aging, Account Status, and "Payment Due" scope** — carried forward unresolved from Phase 2.5A; not re-litigated here, but still blocking.
11. **Available balance authority during transition** (stored vs. live) — carried forward unresolved from Phase 2.5A.
12. **Invoice paid/open-amount equivalence procedure** — carried forward unresolved from Phase 2.4/2.5A (what happens if the still-unrun staging drift snapshot finds disagreement).

No new assumption was made to resolve any of the above. Each is stated as a question, not answered.

---

## 6. LedgerBalanceService Dependency Map

| `LedgerBalanceService` Responsibility | Depends on Truth Table Rows |
|---|---|
| `applyTransaction()` (apply a new ledger entry's balance effect) | Payment (1), Refund (2), Discount (3), Manual/Fuel/Damage Charge (6-8), Order (10) — and, if ever revived, Credit/Debit (4-5) |
| `reverseTransaction()` (undo a ledger entry's balance effect) | Same rows as `applyTransaction()`, plus the confirmed sequencing dependency on Invoice (11) via `updateInvoiceSummary()`/`InvoiceCalculationService` when a linked invoice item is deleted |
| `rebuildForCustomer()` (full ledger replay) | All rows with a real "Balance Effect" — i.e., every row in §3b except Credit, Debit, Extension Charge, Account Invoice, Adjustment (indirect only), Void, Write-Off, and the unimplemented types |
| `currentAvailableCredit()` (live balance computation) | Same dependency set as `rebuildForCustomer()`; must match `applyTransaction()`'s output for the same input, which is precisely the "Available Balance Authority" open question (§5) |
| `amountWithTax()` (the unified tax-inclusion decision) | Payment (1, Approved), Refund (2, Pending), Discount (3, Pending), Credit/Debit (4-5, Undefined), Manual/Fuel/Damage Charge (6-8, Approved), Order (10, Approved — caller-supplied tax) |
| Anything touching Invoice totals | **Explicitly not `LedgerBalanceService`'s responsibility** — Invoice (11) is owned by `InvoiceCalculationService`; `LedgerBalanceService` must call into it or read its output, never reimplement it |
| Anything touching Extension Charge, Void, or Write-Off | **Explicitly out of `LedgerBalanceService`'s scope** — none of these three ever produce a `customer_accounts` row `LedgerBalanceService` would need to apply/reverse |

---

## 7. Implementation Readiness

| Transaction Type | Status |
|---|---|
| Payment | **Ready Now** |
| Order | **Ready Now** |
| Fuel Charge | **Ready Now** |
| Damage Charge | **Ready Now** |
| Manual Charge (tax formula) | **Ready Now** |
| Manual Charge (reporting asymmetry, item #4 in §5) | **Needs Business Decision** |
| Invoice | **Ready Now** (already implemented, Phase 2.4) |
| Refund | **Needs Business Decision** |
| Discount | **Needs Business Decision** |
| Credit | **Needs Business Decision** (define or deprecate) |
| Debit | **Needs Business Decision** (define or deprecate) |
| Account Invoice | **Needs Business Decision** (presentation inconsistency) |
| Extension Charge | **Blocked** — not on a business decision, but on the fact that its tax formula has never been compared against `TaxCalculationService`; treat as **Needs Technical Decision** before folding into any shared service |
| Adjustment | **Needs Technical Decision** (trace its tax/balance interaction before any policy question can even be framed) |
| Void | **Needs Technical Decision** (confirm reporting/invoice interaction) |
| Write-Off | **Needs Business Decision** (should it ever reduce recorded balance) |
| Unimplemented `BillingChargeType`s | **Blocked** (nothing to implement against — Future Enhancement, not a current gap) |

---

## 8. Version History

Recorded in `FINANCIAL_ENGINE_MASTER_ROADMAP.md` §0:

| Version | Milestone | Status |
|---|---|---|
| 2.0.1 | **Financial Truth Table Complete** | Complete — current version |

This is a Financial Engine **architecture milestone**, not an application release number, consistent with every other version entry in the table.

---

## 9. Approval Checklist

### Business Owner

- [ ] Confirm Refund tax treatment (§5 item 1).
- [ ] Confirm Discount tax treatment (§5 item 2).
- [ ] Decide whether `'credit'`/`'debit'` should ever be used, or should be deprecated (§5 item 3).
- [ ] Confirm whether the Manual Charge / `BillingEngine` reporting asymmetry is intentional (§5 item 4).
- [ ] Confirm which `account_invoice` presentation (tab views' dollar-amount treatment, or the PDF's rate-based treatment) is correct (§5 item 5).
- [ ] Confirm which front-end/admin reverse-charge type list is correct (§5 item 6).
- [ ] Decide whether "Write-Off" should ever reduce a customer's recorded balance (§5 item 9).
- [ ] Decide Aging/Account Status/"Payment Due" scope (§5 item 10, carried from Phase 2.5A).
- [ ] Confirm available-balance authority during transition (§5 item 11, carried from Phase 2.5A).

### Technical Lead

- [ ] Verify Adjustment's tax/balance interaction (§5 item 7).
- [ ] Verify Void's interaction with reporting engines and invoices (§5 item 8).
- [ ] Confirm Extension Charge's self-contained tax formula against `TaxCalculationService` before any future consolidation.
- [ ] Assign a named technical owner and reviewer for `LedgerBalanceService` (carried from Phase 2.5A — still unassigned as of this document).
- [ ] Confirm `FixRunningBalancesController` will be run against real or realistic production-scale data before Phase 2.7 (carried from Phase 2.5A).

### Financial Engine Owner

- [ ] Confirm this document supersedes the truth-table fragments in `CRM_BILLING_PAYMENT_AUDIT.md` §4.2 and `PHASE_2_5A_LEDGER_READINESS_REVIEW.md` §2-3 as the single reference going forward (those documents remain historically accurate for what was known when written; this document is now authoritative for current and future decisions).
- [ ] Confirm no future Financial Engine service will define new business policy — any gap found must be added here first, via an amendment, before being implemented.
- [ ] Confirm the Implementation Readiness table (§7) accurately reflects what Phase 2.6 (narrow scope, per Phase 2.5A §8) may proceed to build without further sign-off (Payment, Order, Fuel/Damage/Manual Charge tax formula, Invoice — all "Ready Now").

---

## 10. Final Recommendation

## **GO WITH CONDITIONS**

This does not change the recommendation already reached in `PHASE_2_5A_LEDGER_READINESS_REVIEW.md` — it sharpens it. The narrow Phase 2.6 scope identified there (build `LedgerBalanceService`'s skeleton plus the `payment`, `order`, and `charge` branches only) is now backed by an explicit "Ready Now" status for every one of those transaction types in §7, with `Invoice` already implemented. Nothing in this phase's re-verification found a reason to change that narrow scope.

**What is different after this document**: the list of remaining conditions is now precise and complete rather than a general "resolve the pending items" instruction. Specifically:

- Two items that a prior phase might have treated as blocking (the `account_invoice` "missing case" in `updateCreditBalance()`, and the commented-out `updateCreditBalance()` call in `Invoice\StoreController.php`) were **directly verified this phase to be correct-by-design, not bugs** — removed from any future risk register as a result. This is itself evidence the "re-verify, don't assume" instruction for this phase produced real value, not just restated prior findings.
- Two new items were found that were not visible before this phase: the Manual Charge / `BillingEngine` reporting asymmetry, and Adjustment/Void's unverified tax and reporting interactions. Neither blocks the narrow Phase 2.6 scope (none of the four affected transaction types — Adjustment, Void, the reporting asymmetry, Write-Off — are part of what Phase 2.6 builds), but all four must be resolved before Phase 2.7/2.8 can expand `LedgerBalanceService` to cover them.

**Evidence supporting GO WITH CONDITIONS over a flat GO**: five of sixteen transaction types are marked "Needs Business Decision" and three more "Needs Technical Decision" in §7 — exactly matching the count and nature of items in `PHASE_2_5A_LEDGER_READINESS_REVIEW.md`'s Pending Decisions, now cross-verified against a complete inventory rather than the four-type analysis that document worked from.

**Evidence supporting GO WITH CONDITIONS over NO-GO**: eleven of sixteen transaction types are "Ready Now," including every type the narrow Phase 2.6 scope touches. There is no reason to halt work on the part of the service that is fully specified while waiting on the part that isn't.

---

## Final Executive Summary

- **Financial Engine Version**: **2.0.1** (Financial Truth Table Complete).
- **Overall project completion**: ~43% of the full Implementation Plan (2.0-2.9) — unchanged in implementation terms; this phase added documentation and decision-readiness, not code.
- **Transaction types documented**: **16**.
- **Approved business rules** (Business Decision Status = Approved): **7** — Payment, Fuel Charge, Damage Charge, Order, Invoice, Extension Charge's scope boundary, and Manual Charge's tax formula specifically (its reporting asymmetry is separately Pending).
- **Pending business decisions**: **6** — Refund tax treatment, Discount tax treatment, the Manual Charge/`BillingEngine` reporting asymmetry, the `account_invoice` presentation inconsistency, the front-end/admin reverse-charge discrepancy, and Write-Off's balance-reduction question.
- **Undefined transaction types**: **2** — Credit and Debit (confirmed dead code; no defined behavior exists to build against).
- **Future enhancements identified**: **1 category** — the four unimplemented `BillingChargeType` values (Service Ticket, Cleaning, Delivery, Misc).
- **`LedgerBalanceService` readiness**: unchanged from Phase 2.5A's **GO WITH CONDITIONS**, now with a complete, code-verified truth table backing every "Ready Now" and "Blocked" classification instead of the four-transaction-type analysis available before this phase.
- **May Version 2.1 (`LedgerBalanceService`) begin?** **Yes, in the same narrow form already approved in Phase 2.5A** (service skeleton plus the `payment`, `order`, and `charge` branches, zero callers migrated) — every transaction type that scope touches is "Ready Now" in §7. **Expanding beyond that narrow scope (Phase 2.7-2.8, the 52 real call sites) still requires the conditions in `PHASE_2_5A_LEDGER_READINESS_REVIEW.md` §10, now with this document as the single reference for resolving them, rather than a fresh investigation per open item.**
