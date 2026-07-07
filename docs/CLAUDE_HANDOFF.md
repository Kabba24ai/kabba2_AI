# Claude Handoff — CRM Billing / Invoicing / Credit Account Audit

Last updated: 2026-07-01
Branch: `raj_development`
Author of this handoff: Claude (audit session, no code changes made)

---

## 1. Why this exists

A confirmed reporting bug was reported: a **$150.00** payment (principal $136.67 + tax $13.33, processed correctly through Authorize.Net and correctly recorded in the ledger) displayed as **~$164.63** on the CRM Billing Summary page's "Last Payment" column.

The task was: audit the CRM Billing, Customer Invoicing, and Credit Account system end-to-end **before making any code changes**, produce a written audit, and only then plan fixes.

This session did the audit. **No application code was modified.** The only artifact produced is the audit document itself.

---

## 2. Current project state

- **No code changes made this session.** `git status` shows the only new item is the audit doc below; all other locally-modified files (139, mostly `storage/app/public/**` images/videos and `.gitignore` files) are pre-existing, unrelated to this work, and were not touched or investigated further.
- **New file added:** [`docs/crm-billing-audit/CRM_BILLING_PAYMENT_AUDIT.md`](crm-billing-audit/CRM_BILLING_PAYMENT_AUDIT.md) — the full audit report. This file is currently **untracked** (not yet `git add`ed or committed). Nothing has been committed.
- There is a separate, pre-existing, unrelated effort already in flight: `docs/billing-engine-audit/*` — a phased plan to consolidate **post-order** charges (fuel/damage/rental-extension) into a new `BillingEngine`/`billing_charges` system. That work does **not** touch the CRM Billing Summary, Credit Account ledger, or Invoicing screens this audit covers, except that the CRM "New Charge" modal (`ChargeStoreController`) already bridges Fuel/Damage charges into `BillingEngine::charge()`. Don't confuse the two efforts — they're complementary, not overlapping, on the payment-tax issue specifically.

---

## 3. Bugs / issues found (see full detail in the audit doc)

### 3.1 Confirmed bug (root cause of the reported issue)

**File:** [`resources/views/admin/crm/billing_summary/partials/_table.blade.php:162-165`](../resources/views/admin/crm/billing_summary/partials/_table.blade.php#L162-L165)

```blade
{{ \App\Helpers\CustomHelper::formatCurrency(
    optional($customer->last_payment)->amount +
        optional($customer->last_payment)->amount * (optional($customer->last_payment)->sales_tax ?? 0),
) }}

<!-- {{ \App\Helpers\CustomHelper::formatCurrency(optional($customer->last_payment)->amount) }}  -->
```

- For a payment row, `customer_accounts.amount` is already the **full amount received** ($150). `sales_tax` on that row is a **rate** (e.g. 0.0887), stored for informational breakdown only — `CustomHelper::updateCreditBalance()` deliberately does *not* add it back to the payment amount when updating the running balance.
- This view does the opposite: `amount + amount*rate`, double-counting tax (~$163–165 depending on rate/rounding — matches the reported $164.63).
- **The correct one-line fix is already written, commented out, directly below the bug** (line 168). Someone found and fixed this before; the fix never shipped.
- Confirmed as **display-only** — the ledger, the running balance (`customers.available_credit_balance`), and the Authorize.Net transaction amount are all correct. Three independent research passes plus direct file reads all converged on this same root cause and same fix.

### 3.2 Deeper structural findings (why this bug happened, and why similar ones are likely)

1. **The "is tax already included in this amount" decision is reimplemented independently in ~6 places** with real disagreements between them: 4 methods in `app/Helpers/CustomHelper.php` (`getAvailableCredit`, `updateCreditBalance`, `reverseTransactionEffect`, `fixTheRunningBalance`) and 3 Blade views (`_tab_credit.blade.php` admin + front-end copy, `transaction_accounts_pdf.blade.php`). Discounts and refunds are taxed inconsistently between these implementations — see the truth table in §4.2 of the audit doc.
2. **Two independent "available balance" computations can drift**: Billing Summary uses the stored `customers.available_credit_balance` column; the Credit tab's "Available Credit" widget uses a live recompute via `CustomHelper::getAvailableCredit()`. Evidence this has already happened: an existing admin utility, `FixRunningBalancesController`, exists specifically to replay the ledger and log `CORRUPTED_AND_FIXED` vs `OK` per customer.
3. **Unit mismatch**: `customer_accounts.sales_tax` is a *rate* (0–1 decimal); `invoices.sales_tax` / `invoice_items.tax` are *dollar amounts*. Same column name, different units, across tables — a landmine for future bugs of this exact class.
4. **Invoice `paid_amount`/`open_amount` updated in two places**: `CustomHelper::updateInvoiceSummary()` recomputes it from the ledger; `Invoice\PaymentStoreController` also increments it inline. Whether both run for the same payment needs **runtime verification**, not just static reading — flagged as unconfirmed, do not assume.
5. **"Payment Due" column on Billing Summary is hard-coded to `-`** — overdue-amount is not implemented, not a regression.
6. **Delete-path balance integrity unconfirmed**: it wasn't verified whether deleting a ledger transaction (`DeleteController`) calls any balance-reversal logic. Flagged as a risk to check before touching that code path.

None of items 2-6 have confirmed customer-facing impact today — they are structural risks identified during the audit, not additional confirmed bugs. Treat them as "verify before you build on this," not "fix immediately."

---

## 4. Pending tasks

Nothing has been implemented yet. In order:

- [ ] **Get sign-off to proceed to Phase 1** (the one-line display fix) — currently blocked only on user/stakeholder approval, not on further investigation.
- [ ] Phase 1: fix the confirmed bug (§5 below).
- [ ] Phase 2: centralize the duplicated per-row tax-breakdown Blade formula (3 copies) into `CustomHelper`.
- [ ] Phase 2b: runtime-confirm the invoice paid/open-amount dual-update question (§3.2 item 4) before touching `Invoice\PaymentStoreController` or `updateInvoiceSummary()`.
- [ ] Phase 3: unify the 4 divergent tax-inclusion implementations in `CustomHelper.php` behind one method, with an explicit business-confirmed truth table for discount/refund tax treatment (the audit doc's §4.2 table is a *starting point*, not a decision — the business owner needs to confirm intended behavior for refunds and discounts before this is coded).
- [ ] Phase 4: reconcile the two balance computations (§3.2 item 2), verify the delete path (§3.2 item 6), decide whether to implement or explicitly de-scope "Payment Due" (§3.2 item 5).
- [ ] Phase 5: add automated feature tests for the 16 scenarios listed in the audit doc's §7.

Full phase detail, rationale, and risk ratings per phase are in the audit doc §5-6 — don't re-derive them, read that section first.

---

## 5. Exact next steps (if resuming this work)

1. **Read** [`docs/crm-billing-audit/CRM_BILLING_PAYMENT_AUDIT.md`](crm-billing-audit/CRM_BILLING_PAYMENT_AUDIT.md) in full before touching any billing code — it has the architecture map, source-of-truth matrix, calculation inventory, and full inconsistency list this summary compresses.
2. **Phase 1 fix** (safe, isolated, zero data risk — do this first):
   - File: `resources/views/admin/crm/billing_summary/partials/_table.blade.php`
   - Replace lines 162-168 (the buggy expression + the commented-out correct line + the blank lines between them) with just:
     ```blade
     {{ \App\Helpers\CustomHelper::formatCurrency(optional($customer->last_payment)->amount) }}
     ```
   - Manually verify against a staging customer with a known $150 ($136.67 + $13.33) payment — column should read `$150.00`.
   - No other file needs to change for Phase 1.
3. **Before Phase 2/3**, get the business owner (whoever owns billing rules — likely the same person who reported the original $164.63 bug) to confirm the intended tax treatment for refunds and discounts using the truth table in audit doc §4.2. Do not guess and encode a new "unified" formula without that confirmation — it's exactly how the current divergence happened.
4. **Before touching `Invoice\PaymentStoreController` or `CustomHelper::updateInvoiceSummary()`**, add temporary logging or a debugger breakpoint to confirm at runtime whether both fire for the same payment. This determines whether Phase 2b is a real bug or a false alarm from static reading.
5. **Use `FixRunningBalancesController`'s existing before/after diff pattern** as the validation harness for Phase 3 and Phase 4 — run it against a copy of production data before and after any `CustomHelper` change, diff the results, and treat any unexpected `CORRUPTED_AND_FIXED` entries as a regression, not a fix.
6. **Do not commit anything to git yet** — nothing in this session has been staged or committed. When ready, `docs/crm-billing-audit/` and this handoff file should be committed together as documentation, separately from any Phase 1+ code change commits.

---

## 6. Key files reference (for fast orientation)

| Purpose | Path |
|---|---|
| The bug | `resources/views/admin/crm/billing_summary/partials/_table.blade.php` |
| Correct reference implementation (per-row tax breakdown) | `resources/views/admin/crm/customers/partials/_tab_credit.blade.php` |
| All the duplicated balance/tax math | `app/Helpers/CustomHelper.php` |
| Ledger model | `app/Models/Customers/CustomerAccount.php` |
| Customer model / `last_payment` accessor | `app/Models/Customers/Customer.php` |
| Invoice model + totals | `app/Models/Customers/Invoice.php`, `app/Models/Customers/InvoiceItem.php` |
| CRM payment write path | `app/Http/Controllers/Admin/Crm/Customers/CustomerAccount/PaymentStoreController.php` |
| Invoice payment write path | `app/Http/Controllers/Admin/Crm/Customers/Invoice/PaymentStoreController.php` |
| Refund / Discount / Charge write paths | `app/Http/Controllers/Admin/Crm/Customers/CustomerAccount/{Refund,Discount,Charge}StoreController.php` |
| Billing Summary read path | `app/Http/Controllers/Admin/Crm/BillingSummary/IndexController.php` |
| Existing balance-drift repair utility | `app/Http/Controllers/Admin/Crm/Customers/CustomerAccount/FixRunningBalancesController.php` |
| Full audit report | `docs/crm-billing-audit/CRM_BILLING_PAYMENT_AUDIT.md` |
| Unrelated, pre-existing charge-consolidation effort (don't confuse with this one) | `docs/billing-engine-audit/*` |
