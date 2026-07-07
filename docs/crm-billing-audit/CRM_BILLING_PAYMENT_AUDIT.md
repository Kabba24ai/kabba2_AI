# CRM Billing, Invoicing & Credit Account — Payment/Tax Audit

Audit date: 2026-07-01
Scope: CRM Billing Summary, Customer Credit Account ledger, Invoicing, payment/refund/discount/charge allocation, sales tax handling.
Trigger: confirmed bug — a $150.00 payment (principal $136.67 + tax $13.33) displays as ~$164.63 on the CRM Billing Summary "Last Payment" column.
Status: **audit only — no code changes made.** This is a read-only investigation per instructions.

Related prior work: `docs/billing-engine-audit/` covers a separate, already-in-flight consolidation of **post-order** charges (fuel/damage/extension) into a new `BillingEngine`/`billing_charges` system. That effort does not touch the CRM Billing Summary, Customer Credit Account ledger, or Invoicing screens audited here — the two efforts are complementary, not overlapping, except that `ChargeStoreController` (CRM "New Charge") already bridges into `BillingEngine::charge()` for Fuel/Damage reasons (see §1.6).

---

## 1. Architecture Map

### 1.1 Tables

| Table | Purpose | Key financial columns |
|---|---|---|
| `customers` | Customer master record | `credit_limit`, `is_credit_account`, `available_credit_balance` (stored/cached running balance), `tax_status` (Taxable/Exempt) |
| `customer_accounts` | **The ledger.** Every payment, charge, refund, discount, credit, debit, order, account_invoice line for a customer | `amount`, `sales_tax` (a **rate**, e.g. 0.0887, not a dollar amount — see §3), `sales_tax_type` (`add`/`free`/`reverse`), `type`, `balance` (running balance snapshot at that row), `invoice_id` |
| `invoices` | Customer invoices | `subtotal`, `sales_tax` (here a **dollar amount**, not a rate — see §3.1 inconsistency), `total`, `paid_amount`, `open_amount`, `invoice_status` |
| `invoice_items` | Invoice line items | `unit`, `tax` (dollar amount), `sales_tax_type`, `total`, `type` (charge/order/discount/refund) |
| `order_payments` | Order-level payments (separate from CustomerAccount ledger) | `amount`, `refund_amount`, `tax_refunded`, `status` |
| `billing_charges` (new) | New consolidated charge ledger, currently only bridged from Fuel/Damage CRM charges | `amount`, `tax_amount`, `tax_type`, `customer_account_id` (bridge FK) |
| `receipts` | PDF receipt records for paid invoices | `subtotal`, `sales_tax`, `total` |

### 1.2 Models

- `App\Models\Customers\Customer` — `accounts()`, `paymentAccounts()`, `invoices()`, computed accessors: `getLastPaymentAttribute()`, `getDaysSinceLastPaymentAttribute()`, `getPaymentStatusBadgeAttribute()`, `getTotalPaidInvoicesAttribute()`, etc.
- `App\Models\Customers\CustomerAccount` — the ledger row model. Plain data model, **no computed financial accessors** (good — math lives elsewhere, see §3 for where).
- `App\Models\Customers\Invoice` — plain data model, no computed accessors. All invoice math lives in `CustomHelper::updateInvoiceSummary()`.
- `App\Models\Customers\InvoiceItem` — plain data model.
- `App\Models\Orders\BillingCharge` — new bridge-mode model (Fuel/Damage only today).

### 1.3 Controllers (write paths into the ledger)

| Controller | Route | Writes |
|---|---|---|
| `Admin\Crm\Customers\CustomerAccount\PaymentStoreController` | `POST customer-account/payment-store` | `CustomerAccount` (`type=payment`), optional Authorize.Net charge |
| `Admin\Crm\Customers\Invoice\PaymentStoreController` | invoice payment route | `CustomerAccount` (`type=payment`, `invoice_id` set) + updates `Invoice.paid_amount`/`open_amount`/`invoice_status` directly |
| `Admin\Crm\Customers\CustomerAccount\RefundStoreController` | `POST .../refund-store` | `CustomerAccount` (`type=refund`) — **no `sales_tax_type` set** |
| `Admin\Crm\Customers\CustomerAccount\DiscountStoreController` | `POST .../discount-store` | `CustomerAccount` (`type=discount`, `sales_tax_type` from form, `sales_tax=0.00` initially) |
| `Admin\Crm\Customers\CustomerAccount\ChargeStoreController` | `POST .../charge-store` | `CustomerAccount` (`type=charge`, `sales_tax_type` from form) **and**, for Fuel/Damage reasons, bridges into `BillingEngine::charge()` |
| `Admin\Crm\Customers\CustomerAccount\UpdateController` / `DeleteController` | edit/delete a ledger row | Mutates/soft-deletes `CustomerAccount`, no rebalance call visible in delete path (needs confirmation before any fix) |
| `Admin\Crm\Customers\CustomerAccount\FixRunningBalancesController` | admin-only utility endpoint | Runs `CustomHelper::fixTheRunningBalance()` across **all customers**, logs `CORRUPTED_AND_FIXED` vs `OK` — **this endpoint's existence indicates the team already knows the running balance has drifted from the ledger before.** |
| `Admin\Crm\BillingSummary\IndexController` | `GET admin/billing-summary` | Read-only; builds the customer list + filters for the Billing Summary page |
| `Admin\Crm\Customers\CustomerAccount\DownloadPdfController` | transaction PDF | Read-only |

### 1.4 Views

| View | Role | Tax/payment math present? |
|---|---|---|
| `resources/views/admin/crm/billing_summary/partials/_table.blade.php` | Billing Summary list ("Last Payment" column) | **YES — the confirmed bug**, lines 162-165 |
| `resources/views/admin/crm/customers/partials/_tab_credit.blade.php` | Customer Detail → Credit Account tab, full ledger table | YES — correct logic, lines 424-484 (this is the reference implementation) |
| `resources/views/front/customer/dashboard/partials/_tab_credit.blade.php` | Front-end customer-facing credit tab | Same correct pattern as admin tab |
| `resources/views/admin/crm/customers/transaction_accounts_pdf.blade.php` | Single-transaction PDF | Correct pattern (mirrors `_tab_credit.blade.php`) |
| `resources/views/admin/order_management/orders/partials/_additional_charges.blade.php` | Order Edit "Additional Charges" (Fuel/Damage) | Different but **correct** for its context — charge amount is pre-tax, only adds tax when `sales_tax_type === 'add'` |

### 1.5 Helper / Service layer (where the math actually lives)

All ledger math is centralized in **`App\Helpers\CustomHelper`** — but as four separate, independently-written implementations (see §4):
- `getAvailableCredit(Customer $customer)` — live recomputation of available credit by iterating all ledger rows
- `updateCreditBalance(CustomerAccount $record)` — incremental balance update, called after every write
- `reverseTransactionEffect(CustomerAccount $record)` — used when reversing/deleting a transaction
- `fixTheRunningBalance(int $customerId)` — full ledger replay/repair, used by the "Fix Running Balances" admin utility
- `updateInvoiceSummary(Invoice $invoice)` — invoice subtotal/tax/total/paid/open/status computation
- `calculateSalesTaxRate()`, `calculateRefundSalesTax()` — tax-rate helpers used by invoice/refund flows

`App\Services\BillingEngine` — new, narrow-scope service, currently only for Fuel/Damage charges bridged from the CRM Charge modal. Not involved in payments, refunds, discounts, or invoicing.

### 1.6 Data flow (payment)

```
PaymentStoreController (CRM or Invoice)
  → creates CustomerAccount row (type=payment, amount=<actual amount received>, sales_tax=<tax rate, informational>)
  → [optional] AuthorizeNetService charges the card for the same amount
  → CustomHelper::updateCreditBalance($record)
       → payment case: amountWithTax = record->amount  (tax NOT re-added — correct)
       → customer.available_credit_balance -= amount
  → [[if invoice_id set]] Invoice.paid_amount += amount; open_amount recalculated; status updated

Display layer:
  - Customer::getLastPaymentAttribute() → returns the raw CustomerAccount row (correct, no math)
  - _tab_credit.blade.php → correctly backs the tax portion OUT of amount via division (correct)
  - billing_summary/_table.blade.php → incorrectly ADDS amount * sales_tax back on top (BUG)
```

---

## 2. Source of Truth Matrix

| Financial value | Stored where | Computed where | Displayed where | Authoritative? |
|---|---|---|---|---|
| Payment amount (actual $ received) | `customer_accounts.amount` (type=payment) | Not computed — set directly from validated request / gateway amount | `_tab_credit.blade.php` (correct), `billing_summary/_table.blade.php` (**incorrect**), PDF | `customer_accounts.amount` is authoritative |
| Payment allocation (principal vs tax split) | Not stored separately — derived from `amount` and `sales_tax` (rate) at display time | `_tab_credit.blade.php`: `amount / (1 + sales_tax)` for principal, `amount - principal` for tax | Credit tab ledger table only | Derived, not stored — every consumer must replicate the same division formula (currently only one view does it correctly) |
| Customer running balance | `customers.available_credit_balance` (cached) + `customer_accounts.balance` (per-row snapshot) | `CustomHelper::updateCreditBalance()` (incremental) **and independently** `CustomHelper::fixTheRunningBalance()` (full replay) **and independently** `CustomHelper::getAvailableCredit()` (live, not persisted) | Billing Summary "Balance" column uses the **stored** `available_credit_balance`; Credit tab "Available Credit" widget uses the **live-computed** `getAvailableCredit()` | **Two different code paths compute what is presented as the same number** — see §4.4 |
| Invoice total | `invoices.total` | `CustomHelper::updateInvoiceSummary()` | Invoice view, PDF, credit tab (via `account_invoice` rows) | Authoritative once `updateInvoiceSummary()` has run |
| Invoice paid/open amount | `invoices.paid_amount`, `invoices.open_amount` | Recomputed in two places: `updateInvoiceSummary()` (sums `customer_accounts` payments by `invoice_id`) **and** `Invoice\PaymentStoreController` (increments `paid_amount` directly, inline) | Invoice view, Billing Summary (indirectly) | Two independent update paths for the same columns — see §4.5 |
| Sales tax on a charge/invoice item | `customer_accounts.sales_tax` (**rate**) vs `invoice_items.tax` (**dollar amount**) | Charge modal (rate from settings) / invoice line entry (dollar amount) | Ledger, invoice | Inconsistent units between the two tables — see §3.1 |
| Refund amount | `customer_accounts.amount` (type=refund) | Not computed — user-entered | Credit tab, billing summary (not currently surfaced separately) | Authoritative |
| Discount amount | `customer_accounts.amount` (type=discount) | Not computed — user-entered | Credit tab | Authoritative |
| Manual charge amount | `customer_accounts.amount` (type=charge) | Not computed — user-entered (pre-tax) | Credit tab, order Additional Charges | Authoritative |
| Last Payment | `customers.getLastPaymentAttribute()` → most recent `customer_accounts` row where `type=payment` | No computation in the model | Billing Summary (**bug**), Credit tab widget (date only) | The accessor itself is correct; the bug is purely in the consuming Blade template |
| Last Payment Date | Same accessor, `->date` | None | Both correct | Authoritative |
| Overdue amount / Aging days | `Customer::getDaysSinceLastPaymentAttribute()`; "Payment Due" column in Billing Summary is hard-coded to `-` (not implemented) | `diffInDays()` from last payment date | Billing Summary "Days Aging" column | **"Payment Due" / overdue-amount is not implemented at all** — column always renders `-` |
| Credit limit / available credit | `customers.credit_limit` (stored) vs `getAvailableCredit()` (live) | See running balance row above | Credit tab | Two parallel implementations, see §4.4 |

---

## 3. Calculation Inventory

Every place in the codebase where CRM/invoice/credit-account financial math occurs:

1. **`app/Helpers/CustomHelper.php:98-164` `getAvailableCredit()`** — live balance recompute, iterates all ledger rows, applies tax per type.
2. **`app/Helpers/CustomHelper.php:332-468` `updateCreditBalance()`** — incremental balance update on every write. Contains commented-out prior (buggy) formula for `payment` case at line 360 — evidence this exact bug (adding tax to payment amount) was already found and fixed **here**, but the fix was never propagated to the Billing Summary view.
3. **`app/Helpers/CustomHelper.php:470-543` `reverseTransactionEffect()`** — used when a ledger row is reversed/deleted.
4. **`app/Helpers/CustomHelper.php:545-679` `fixTheRunningBalance()`** — full ledger replay, has **two separate code branches** (credit-account customers vs. non-credit-account customers) each re-implementing the same tax-inclusion check a third and fourth time.
5. **`app/Helpers/CustomHelper.php:763-874` `updateInvoiceSummary()`** — invoice subtotal/tax/total/paid/open/status.
6. **`app/Helpers/CustomHelper.php:877-905` `calculateSalesTaxRate()`, `calculateRefundSalesTax()`** — tax-rate back-calculation helpers.
7. **`resources/views/admin/crm/billing_summary/partials/_table.blade.php:162-165`** — **the confirmed bug.**
8. **`resources/views/admin/crm/customers/partials/_tab_credit.blade.php:424-484`** — per-row Amount / Sales Tax / Balance Change columns, computed inline in Blade (four `@if/@elseif` branches, correct but fragile — this exact formula is not shared with any other view).
9. **`resources/views/front/customer/dashboard/partials/_tab_credit.blade.php`** — duplicate of #8 for the customer-facing portal.
10. **`resources/views/admin/crm/customers/transaction_accounts_pdf.blade.php:104-127`** — duplicate of #8 again, for the PDF.
11. **`resources/views/admin/order_management/orders/partials/_additional_charges.blade.php:32-35`** — separate, correct-for-context tax math for Fuel/Damage charges shown on the Order Edit page.
12. **`Admin\Crm\Customers\Invoice\PaymentStoreController.php:75-92`** — inline invoice `paid_amount`/`open_amount`/`status` recompute, duplicating what `updateInvoiceSummary()` also does.
13. **`app/Http/Controllers/Admin/Crm/BillingSummary/IndexController.php:104-114, 130-167`** — SQL-level balance/date filtering and sorting (not tax math, but a parallel definition of "aging"/"bad debt" alongside `CustomHelper::getCustomerAccountStatus()`).

**Observation:** the same "is tax already included in this amount, or does it need to be added" decision is re-implemented independently in at least **6 places** (items 1, 2, 4×2 branches, 8, 9, 10) with slightly different conditions each time. This is the root structural problem, not just the one Blade bug.

---

## 4. Inconsistency Report

### 4.1 CONFIRMED — the reported bug

**File:** `resources/views/admin/crm/billing_summary/partials/_table.blade.php:162-165`

```blade
{{ \App\Helpers\CustomHelper::formatCurrency(
    optional($customer->last_payment)->amount +
        optional($customer->last_payment)->amount * (optional($customer->last_payment)->sales_tax ?? 0),
) }}

<!-- {{ \App\Helpers\CustomHelper::formatCurrency(optional($customer->last_payment)->amount) }}  -->
```

- `customer_accounts.amount` for a payment row **already is the full amount received** ($150.00). `sales_tax` on that same row is a **rate** (e.g. 0.0887), stored for informational breakdown only (see `updateCreditBalance()` line ~356, which explicitly sets `sales_tax = rate` but computes `amountWithTax = record->amount` — i.e., it deliberately does *not* add tax to the payment amount).
- The view instead computes `amount + amount*rate`, double-counting tax: `150 + 150*0.0887 ≈ 163.30`, in the neighborhood of the reported $164.63 (exact rate/rounding not confirmed, but the formula match is exact).
- **The correct line is sitting right below it, commented out** (line 168) — someone already diagnosed this and wrote the fix, but never uncommented it / it was reverted.
- This is a **display-only** bug. The ledger (`customer_accounts`), the running balance (`available_credit_balance`), and `getAvailableCredit()` all treat the payment correctly. Confirmed by independent review of `updateCreditBalance()`, `getAvailableCredit()`, and `reverseTransactionEffect()` — none of them add tax on top of a payment's `amount`.

### 4.2 Duplicated, drifting tax-inclusion logic (structural risk)

The "is tax already included, or should it be added" branch is implemented **four times** in `CustomHelper.php` alone, with different conditions:

| Method | Payment | Refund | Discount | Charge |
|---|---|---|---|---|
| `getAvailableCredit()` (98-164) | tax never added (hardcoded `$tax=0`) | tax **always** added if customer taxable (no `sales_tax_type` check) | tax **always** added if customer taxable (no `sales_tax_type` check) | tax added only if `sales_tax_type==='add'` |
| `updateCreditBalance()` (332-468) | tax never added (comment shows old buggy line removed) | tax **always** added if customer taxable | tax added conditionally on `sales_tax_type` (`add`/`reverse`/`free`) | tax added only if `sales_tax_type==='add'` |
| `reverseTransactionEffect()` (470-543) | tax never added | tax **always** added (no `sales_tax_type` check) | tax added conditionally on `sales_tax_type` | tax added only if `sales_tax_type!=='reverse'` |
| `fixTheRunningBalance()` (545-679, ×2 branches) | tax never added | tax added unless type is payment (i.e., always for refund) | tax added unless `sales_tax_type==='reverse'` | tax added unless `sales_tax_type==='reverse'` |

**Discount handling is inconsistent between methods**: `getAvailableCredit()` always taxes a discount if the customer is taxable, ignoring `sales_tax_type` entirely, while `updateCreditBalance()` and `reverseTransactionEffect()` branch on `sales_tax_type`. If a discount is created with `sales_tax_type = 'free'`, the *displayed available credit* and the *persisted balance* can diverge.

**Refund never has a `sales_tax_type` set** by `RefundStoreController` (it's the only one of the four modals that doesn't collect it), yet every helper method still branches conditionally as if refunds *could* have `free`/`reverse` treatment. In practice refunds always get taxed — this may be intentional, but it isn't documented as a rule, and the UI doesn't expose the option like it does for Discount/Charge.

### 4.3 Duplicated Blade-level tax math (3 copies)

`_tab_credit.blade.php` (admin), the front-end customer-portal equivalent, and `transaction_accounts_pdf.blade.php` all reimplement the same per-row tax-breakdown formula independently in Blade. None of them call a shared helper method for this — if the formula needs a fix in the future (as it apparently already did once, per the commented-out line in the Billing Summary view), it has to be fixed in 3+ places by hand, and evidently at least one place was missed.

### 4.4 Two independent "available balance" computations

- Billing Summary's "Balance" column and sort/filter logic use the **stored** `customers.available_credit_balance` column, updated incrementally by `updateCreditBalance()`.
- The Credit tab's "Available Credit" widget calls `CustomHelper::getAvailableCredit($customer)` **live**, re-summing the entire ledger on every page load, using a *different* implementation of the same tax rules (see §4.2 table).
- These two numbers are only guaranteed to agree if both implementations are kept in sync by hand. The existence of `FixRunningBalancesController` (an admin utility that replays the whole ledger and logs `CORRUPTED_AND_FIXED` when it finds drift) is direct evidence this has already happened in production.

### 4.5 Duplicated invoice paid/open-amount computation

`CustomHelper::updateInvoiceSummary()` recomputes `paid_amount`/`open_amount`/`invoice_status` by summing `customer_accounts` rows for the invoice. `Invoice\PaymentStoreController` **also** increments `paid_amount` and recomputes `open_amount`/`status` inline, independently, immediately after creating the payment row. If both run for the same payment (unclear from static review whether `updateInvoiceSummary()` is also invoked in this controller's path), the increment could double-count; if only the controller's inline version runs, it will drift from `updateInvoiceSummary()`'s output the next time that function runs for any other reason (e.g. an edit to invoice items). This needs runtime confirmation, not just static reading, before altering.

### 4.6 Unit mismatch: `sales_tax` means different things in different tables

- `customer_accounts.sales_tax` = a **rate** (0–1 decimal).
- `invoices.sales_tax` and `invoice_items.tax` = a **dollar amount**.

Any code or future report that treats these two `sales_tax` columns as the same kind of value will silently produce nonsense (e.g. treating a $13.33 tax amount as an 1333% rate, or vice versa). This is exactly the class of bug behind the confirmed issue, just waiting to recur elsewhere. Column naming does not disambiguate this at all.

### 4.7 "Payment Due" / overdue amount not implemented

`billing_summary/_table.blade.php:180` hard-codes the "Payment Due" column to `-` for every row. The audit goal of verifying overdue-amount calculations cannot be completed because the calculation does not exist yet — flag this as a gap, not a bug.

### 4.8 Delete path balance integrity (needs runtime verification)

`DeleteController` for a ledger transaction was not confirmed to call `reverseTransactionEffect()` or `fixTheRunningBalance()` in this pass — if a payment/charge/refund row can be deleted without reversing its balance effect, the running balance will silently drift. **Recommend explicit verification before Phase 2** (see §6).

---

## 5. Risk Assessment

| Item | Risk to fix | Reasoning |
|---|---|---|
| §4.1 Billing Summary "Last Payment" display bug | **Very low** | Pure display-layer, single Blade file, one line, no persisted data touched, correct formula already written (commented out) and independently validated three ways (all three research passes + direct read). Safe to fix immediately. |
| §4.7 "Payment Due" column showing `-` | **Low** | Currently a known no-op, not a regression risk to leave as-is short-term; implementing it is new functionality, not a bug fix, so should be scoped/estimated separately. |
| §4.3 Duplicated Blade tax math (3 copies) | **Low-medium** | Consolidating into one helper method is safe if done carefully (extract, don't rewrite the formula) and tested against the existing correct output row-by-row. |
| §4.2 Duplicated/drifting tax-inclusion logic across 4 helper methods | **Medium-high** | These four methods are called from many places (every store/update/delete controller, the admin repair utility, and the live display widget). Consolidating them touches every write path to the ledger — must be done with a full transaction/regression test matrix (§7) before merging, and ideally behind a feature flag or dry-run mode first, mirroring the `FixRunningBalancesController`'s existing before/after diff pattern. |
| §4.4 Two independent balance computations | **Medium-high** | Same reasoning — touches both the write path and a read path used for real-time display. Recommend: pick one as canonical, make the other call it, verify against `FixRunningBalancesController`'s diff logging in a staging pass before rollout. |
| §4.5 Invoice paid/open-amount dual computation | **Medium** | Needs runtime confirmation of actual call order before any change (§4.8-style — don't assume from static read). |
| §4.6 `sales_tax` unit mismatch between tables | **High to fully resolve, low to mitigate** | Renaming columns or changing semantics is a migration-level change touching every consumer. Low-risk mitigation: add a code comment / doc note on both models clarifying the unit, and add a lint/test that asserts `customer_accounts.sales_tax` stays within [0,1] and `invoices.sales_tax` stays within a sane dollar range relative to `subtotal`, to catch future confusion early. |
| §4.8 Delete-path balance integrity | **Unknown until verified** | Do not change until confirmed via a runtime test (create a transaction, delete it, assert balance reverts). |

---

## 6. Proposed Fix Plan

### Phase 1 — Fix the confirmed Last Payment bug (this week)

1. In `resources/views/admin/crm/billing_summary/partials/_table.blade.php:162-168`, replace the buggy double-tax expression with the already-written, commented-out correct line:
   ```blade
   {{ \App\Helpers\CustomHelper::formatCurrency(optional($customer->last_payment)->amount) }}
   ```
2. Remove the dead buggy code and the stale comment once confirmed.
3. Manual regression: reproduce the exact $150/$136.67/$13.33 scenario from a staging/test customer and confirm the column now shows $150.00.
4. No other file needs to change for this phase — it is isolated to this one Blade partial.

### Phase 2 — Centralize payment amount & allocation logic

1. Extract the correct per-row breakdown logic currently duplicated in `_tab_credit.blade.php`, the front-end equivalent, and `transaction_accounts_pdf.blade.php` (§4.3) into single `CustomHelper` methods, e.g. `CustomHelper::transactionPrincipal($account)` and `CustomHelper::transactionTaxAmount($account)`, reusing the exact existing formulas (division/subtraction) — do not re-derive them.
2. Point all three Blade views at the new shared methods. Snapshot current rendered output for a representative sample of ledger rows (all types × both `sales_tax_type` values) before and after, and diff — must be byte-identical.
3. Confirm (runtime, not static read) whether `Invoice\PaymentStoreController`'s inline paid/open-amount update and `CustomHelper::updateInvoiceSummary()` are both invoked for the same payment (§4.5); if so, remove the inline duplicate and call `updateInvoiceSummary()` exclusively.

### Phase 3 — Centralize sales tax logic

1. Unify the four divergent tax-inclusion implementations in `CustomHelper` (§4.2) into one method, e.g. `CustomHelper::amountWithTax(CustomerAccount $record, float $taxRate): float`, with an explicit, documented truth table per `type` × `sales_tax_type` (the table in §4.2 of this document is the starting spec — reconcile the discrepancies with the business owner before coding, don't silently pick one).
2. Replace call sites in `getAvailableCredit()`, `updateCreditBalance()`, `reverseTransactionEffect()`, and both branches of `fixTheRunningBalance()` with calls to the unified method.
3. Run `FixRunningBalancesController` against a copy of production data before and after the change and diff the results — this endpoint already produces the exact before/after balance comparison needed to validate this phase safely.
4. Document the unit mismatch from §4.6 (`customer_accounts.sales_tax` = rate vs `invoices.sales_tax` = dollar amount) directly in both models' docblocks so future contributors don't conflate them.

### Phase 4 — Reconcile invoices, credit accounts, and reports

1. Confirm `available_credit_balance` (stored) and `getAvailableCredit()` (live) agree for a sample of accounts after Phase 3; if the unified tax method makes them structurally identical, consider having one call the other rather than maintaining two computations (§4.4).
2. Verify the delete path (§4.8) reverses balance correctly; add the missing reversal call if it's absent.
3. Cross-check `SalesReportEngineV2`/`SalesTaxReportEngine`/Pure Sales Summary (flagged by research but not read in full during this pass) against the unified tax method's output for a sample period.
4. Implement or explicitly de-scope the "Payment Due"/overdue-amount column (§4.7) — currently silent no-op, should not stay silently wrong forever.

### Phase 5 — Automated tests

Add feature tests (Laravel) covering the scenarios in §7 below, asserting against `CustomerAccount.amount`, `customers.available_credit_balance`, `Invoice.paid_amount/open_amount/invoice_status`, and the rendered Blade output of the Last Payment column and the credit-tab ledger table.

---

## 7. Test Cases

| # | Scenario | Assert |
|---|---|---|
| 1 | Taxable charge, paid in full | Charge balance increases by `amount + amount*rate`; payment balance decreases by the full payment amount only (no extra tax); Last Payment display == amount paid |
| 2 | Taxable charge, partial payment | Balance reduced by payment amount only; open balance = charge total − payment; second payment brings to zero |
| 3 | Non-taxable charge (`sales_tax_type = free`) | No tax added anywhere; payment amount == charge amount |
| 4 | Mixed taxable + non-taxable charges on one account | Each charge's tax computed independently per its own `sales_tax_type`; running balance = sum of correctly-taxed charges |
| 5 | Discount applied before payment | Discount reduces balance per its `sales_tax_type` (add/free/reverse) consistently across `getAvailableCredit()`, `updateCreditBalance()`, and display — currently these three disagree (§4.2), test should fail today and pass after Phase 3 |
| 6 | Discount applied after payment | Same as #5, ordering shouldn't matter to final balance |
| 7 | Refund | Refund amount restores balance; confirm whether tax should apply (business rule currently undocumented — write the test to match whatever the business owner confirms, not what the code currently assumes) |
| 8 | Voided payment | Reversal fully undoes the payment's balance effect (via `reverseTransactionEffect` or delete path) |
| 9 | Manual charge | Behaves identically to a system-generated charge of the same `sales_tax_type` |
| 10 | Zero balance | All displays show $0.00, no divide-by-zero in tax back-calculation (`amount / (1 + sales_tax)` when `sales_tax` could be 0) |
| 11 | Overpayment / account credit | Payment exceeding open balance results in a negative balance (credit) — confirm this is the intended UX and that it displays correctly, not as an error |
| 12 | Tax-exempt customer (`tax_status = Exempt`) | No tax computed anywhere regardless of `sales_tax_type` flags on individual rows |
| 13 | Multiple invoices for one customer | `paid_amount`/`open_amount` tracked independently per invoice; customer-level balance is the sum |
| 14 | Multiple partial payments across time | Running balance decreases monotonically with each payment; Last Payment always reflects the most recent one by `date`, not by insertion order |
| 15 | Aging / overdue account | `days_since_last_payment` and `payment_status_badge` correctly reflect 30/45/90-day boundaries; confirm "Payment Due" once implemented (§6 Phase 4.4) |
| 16 | Authorize.Net reconciliation | The amount charged via `AuthorizeNetService` and the `amount` stored on the `CustomerAccount` payment row are identical for the same transaction — add an assertion comparing the gateway response amount to the persisted ledger amount at write time |

---

## 8. Summary for immediate action

- **Root cause confirmed**: `resources/views/admin/crm/billing_summary/partials/_table.blade.php:162-165` double-counts tax on the payment amount. The correct one-line fix is already written and commented out at line 168.
- **The ledger, running balance, and Authorize.Net payment amount are correct.** This is a display-only bug in one file.
- **The deeper finding**: the same tax-inclusion decision is independently re-implemented in at least 6 places across `CustomHelper.php` and 3 Blade views, with documented disagreements on how discounts and refunds are taxed (§4.2). This is why the bug happened, and why similar bugs are likely to recur if the logic isn't consolidated (Phases 2-3 above).
- No code has been changed as part of this audit. Recommend approving Phase 1 (single-line, isolated, zero-risk) immediately, and scheduling Phases 2-5 as a tracked follow-up.
