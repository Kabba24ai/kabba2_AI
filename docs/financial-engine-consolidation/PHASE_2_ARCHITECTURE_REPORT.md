# Financial Engine Consolidation — Phase 2 Architecture Report

Report date: 2026-07-01
Branch: `raj_development`
Status: **Architecture analysis only — no code changes made.**
Author: Claude (architecture session, read-only)

Inputs to this report:
- `docs/crm-billing-audit/CRM_BILLING_PAYMENT_AUDIT.md` (CRM Billing/Invoicing/Credit Account audit, completed 2026-07-01)
- `docs/billing-engine-audit/*` (post-order charge consolidation — `BillingEngine`/`billing_charges`, already merged to `raj_development`)
- New research this session: complete caller inventory for every `CustomHelper` financial method, and a sweep of Sales Reports / Dashboard financial calculations (not covered by the prior audit, but named explicitly in this mission's scope)

---

## 0. Mission recap

Goal: **one source of truth for every financial calculation** in the system — not new features, not changed accounting behavior. This report proposes the target architecture and a migration order. **No code is changed as part of this report.**

Future consumers named in the mission (CRM, Customer Credit Accounts, Invoicing, Billing Engine, Sales Reports, Dashboard Reporting, future QuickBooks integration, future Customer Statements) are all accounted for below. QuickBooks integration and Customer Statements do not exist yet in the codebase (confirmed by direct search — no controllers, services, or views reference either); they are **future consumers of the proposed layer**, not migration targets today.

---

## 1. Current state — every duplicated financial calculation found

This consolidates the prior audit's findings (§3-4 of `CRM_BILLING_PAYMENT_AUDIT.md`) with this session's new research into reporting/dashboard code, which the prior audit explicitly did not cover.

### 1.1 Ledger / balance / invoice math — `app/Helpers/CustomHelper.php`

| # | Method | Lines | Purpose | Independently reimplements |
|---|---|---|---|---|
| 1 | `getAvailableCredit(Customer $customer)` | 98-164 | Live balance recompute, iterates all ledger rows | Tax-inclusion decision (own branch logic) |
| 2 | `updateCreditBalance(CustomerAccount $record, float $externalTaxAmount = 0.0)` | 332-468 | Incremental balance update, called after every ledger write | Tax-inclusion decision (own branch logic, disagrees with #1) |
| 3 | `reverseTransactionEffect(CustomerAccount $record)` | 470-543 | Reverses a transaction's balance effect (edit/delete paths) | Tax-inclusion decision (own branch logic, disagrees with #1 and #2) |
| 4 | `fixTheRunningBalance(int $customerId)` | 545-679 | Full ledger replay/repair; **two separate branches** (credit-account vs. non-credit-account customers) | Tax-inclusion decision, twice more (5th and 6th independent copies) |
| 5 | `updateInvoiceSummary(Invoice $invoice)` | 763-874 | Invoice subtotal/tax/total/paid/open/status | Invoice paid/open-amount math (see §1.4) |
| 6 | `calculateSalesTaxRate()`, `calculateRefundSalesTax()` | 877-905 | Tax-rate back-calculation | — (thin, low-duplication-risk helpers) |

**Confirmed:** the "is tax already included, or should it be added" decision is implemented independently **6 times** across methods 1-4 (method 4 has two branches), each with different rules for how Payment/Refund/Discount/Charge are treated. Full divergence truth table: `CRM_BILLING_PAYMENT_AUDIT.md` §4.2.

### 1.2 Duplicated Blade-level tax breakdown (per-row ledger display)

Three near-identical copies of the same per-row tax-breakdown formula (`amount / (1 + sales_tax)` for principal, subtraction for tax portion):

| File | Lines |
|---|---|
| `resources/views/admin/crm/customers/partials/_tab_credit.blade.php` | 424-484 |
| `resources/views/front/customer/dashboard/partials/_tab_credit.blade.php` | ~same formula, front-end mirror |
| `resources/views/admin/crm/customers/transaction_accounts_pdf.blade.php` | 104-127 |

**New this session** — a fourth, previously undocumented site doing similar per-row tax math for **charge**-type rows specifically (correct for its context: charge amount is pre-tax, so multiplication is right here, unlike for payments):

| File | Lines |
|---|---|
| `resources/views/admin/order_management/orders/partials/_additional_charges.blade.php` | 32-35 |

Client-side JS mirrors of the same formula also exist (`_tab_credit.blade.php:1411-1418` admin, `_tab_credit.blade.php:512-519` front-end) for live preview — these will need to be re-derived from whatever the new service's contract is, since JS can't call a PHP service directly (see Migration Order, Phase 5).

### 1.3 Two independent "available balance" computations

- Billing Summary "Balance" column + sort/filter → stored `customers.available_credit_balance`, maintained incrementally by `updateCreditBalance()`.
- Credit tab "Available Credit" widget → live recompute via `getAvailableCredit()`, a **separate implementation** of the same tax rules.
- `FixRunningBalancesController` exists specifically because these two have drifted in production before (logs `CORRUPTED_AND_FIXED` vs `OK` per customer).

### 1.4 Invoice paid/open-amount — confirmed dual update (previously unconfirmed, now resolved)

The prior audit flagged this as needing runtime confirmation. **This session confirmed it via direct code read, not assumption:**

`Admin\Crm\Customers\Invoice\PaymentStoreController` and its front-end counterpart `Front\Customer\Dashboard\Invoice\PaymentStoreController` **both**:
1. Call `CustomHelper::updateCreditBalance($record)` (updates customer balance), **and**
2. Independently increment `invoice->paid_amount`, recompute `open_amount`, and set `invoice_status` **inline**, in the same request — **without calling `updateInvoiceSummary()`**.

`updateInvoiceSummary()` is only invoked from edit/delete paths (`CustomerAccount\UpdateController`, `CustomerAccount\DeleteController`, `Invoice\UpdateController`, `Invoice\DeleteInvoiceController`, bulk-delete/repair controllers) — **never from either payment controller**.

**Implication:** invoice payment is currently governed by inline arithmetic, not the aggregation method. These are two genuinely different code paths for the same two columns (`paid_amount`, `open_amount`), and they must be reconciled into one, not silently merged — see Risk Assessment §4.

### 1.5 Delete-path balance integrity — confirmed correct (previously unconfirmed, now resolved)

`CustomerAccount\DeleteController` correctly sequences, inside one DB transaction:
1. `CustomHelper::updateInvoiceSummary($invoice)` (if invoice-linked)
2. `CustomHelper::reverseTransactionEffect($transaction)`
3. `$transaction->delete()`
4. `CustomHelper::fixTheRunningBalance($customer_id)` (after commit)

No gap found here. This ordering is a real invariant the new service must preserve exactly (see Migration Order §5, Phase 4).

### 1.6 Sales Reports & Dashboard — new findings this session (not covered by the CRM audit)

The mission explicitly names Sales Reports and Dashboard Reporting as future consumers of the unified layer. Research this session found:

| Service | File | Reuses shared logic? | Tax formula for account payments |
|---|---|---|---|
| `SalesReportEngineV2` | `app/Services/Reports/SalesReportEngineV2.php:411-460` | No — own SQL | `amount / (1 + rate)` (division — correct for tax-inclusive payment amount) |
| `SalesTaxReportEngine` | `app/Services/Reports/SalesTaxReportEngine.php:170-230` | Partial — calls `CustomHelper::calculateRefundSalesTax()` for refunds only | `amount - amount/(1+rate)` (division — correct) |
| `PaymentReconciliationLedger` | `app/Services/Reports/PaymentReconciliationLedger.php:197-256` | No — own SQL | `amount - amount/(1+rate)` (division — correct) |
| `Dashboard\IndexController::getRevenueRows()` | `app/Http/Controllers/Admin/Dashboard/IndexController.php:837-849` | No — own PHP | **`amount * rate`** (multiplication — **wrong** for a tax-inclusive payment amount) |

**New confirmed finding, verified independently by direct code read (not just agent report):**

```php
// app/Http/Controllers/Admin/Dashboard/IndexController.php:837-849
$amount = (float) $p->amount;
$salesTaxRate = (float) $p->sales_tax;
$taxAmount = $salesTaxRate > 0 ? $amount * $salesTaxRate : 0;   // multiplication
return (object) ['date' => $p->date, 'grand_total' => $amount - $taxAmount];
```

versus `SalesReportEngineV2.php:434-449`:

```sql
-- pre_tax = amount / (1 + rate); tax = amount - amount / (1 + rate)
```

For the same `amount`/`rate`, these produce **different numbers** (e.g. $150 @ 8.87%: division gives base $137.85/tax $12.15; multiplication gives tax $13.31/base $136.69). **This means the Dashboard's revenue totals do not reconcile with Pure Sales Summary / Sales Tax report totals for the same date range today.** This is the same *class* of bug as the already-fixed Billing Summary issue (treating a tax-inclusive amount as if tax needed to be added), but a distinct, previously-undiscovered instance, in a different file, affecting a different screen.

This is flagged here as a **confirmed finding requiring its own sign-off and fix** — analogous to Phase 1 of the CRM audit, but out of scope for this architecture-only mission. **Recommend the business/reporting owner be notified separately**, since Dashboard revenue figures are currently silently wrong relative to the reports it's supposed to summarize. Do not fix as a side effect of this consolidation work — track and fix independently first, the same way Phase 1 was handled, before folding this call site into the new service.

Three independent, correct implementations of the account-payment tax-extraction formula also exist (`SalesReportEngineV2`, `SalesTaxReportEngine`, `PaymentReconciliationLedger`) — even the *correct* formula is triplicated, which is exactly the consolidation problem this mission targets.

Other report services (`PureSalesSummaryReport`, `SalesTrendAnalysisEngine`, `EmployeePerformanceEngine`) already delegate to `SalesReportEngineV2` and perform no independent financial math — good precedent for the target architecture. `SalesByStoresReport` and `ProductSalesRankingReport` query `order_products` directly for non-tax figures (avg ticket, growth %) — lower priority, no tax-inclusion risk.

### 1.7 Unit mismatch (structural landmine, unchanged from prior audit)

`customer_accounts.sales_tax` = a **rate** (0-1 decimal). `invoices.sales_tax` / `invoice_items.tax` = a **dollar amount**. `billing_charges.tax_amount` = a **dollar amount** (already computed by the caller before `BillingEngine::charge()` is invoked — confirmed by reading `app/Services/BillingEngine.php`, which stores whatever `tax_amount` it's given and performs no tax math itself). Any unified service's method signatures must make the unit explicit (e.g. `taxRate` vs `taxAmount` as distinct parameter names/types), not rely on column-name conventions that have already caused confusion once.

### 1.8 Complete caller inventory (for migration-risk sizing)

| Method | Confirmed call sites |
|---|---|
| `getAvailableCredit()` | 12 (all Blade/JS display, read-only) |
| `updateCreditBalance()` | 26 (across CRM, Invoice, Dashboard, Orders, checkout, `ChargeService`) |
| `reverseTransactionEffect()` | 7 (edit/delete/bulk-delete/repair paths) |
| `fixTheRunningBalance()` | 7 (edit/delete/bulk-delete/repair paths, admin repair utility) |
| `updateInvoiceSummary()` | 4 PHP call sites (edit/delete paths only — **not** payment paths, see §1.4); 9 same-named but unrelated JS functions in invoice-creation Blade views (false positives, not PHP calls) |
| `calculateSalesTaxRate()` / `calculateRefundSalesTax()` | 3 total (mostly `SalesTaxReportEngine`) |

**`updateCreditBalance()` at 26 call sites is the highest-blast-radius method in the system.** Any change to its signature or behavior touches CRM, Invoice, Dashboard, Orders, checkout, and `ChargeService` simultaneously. This drives the migration order in §5 — it must be the *last* method touched, once the new layer has been validated against lower-traffic call sites first.

---

## 2. Proposed Financial Calculation Services

Four narrow services, not one monolith — mirroring the natural boundaries already visible in the data (ledger rows vs. invoices vs. reporting vs. post-order charges), so each can be migrated and tested independently per the mission's "small, reviewable changes" principle.

### 2.1 `TaxCalculationService` (foundation — build first)

**Responsibility:** the single place that answers "given this amount, this rate/tax-amount, and this transaction type, what is the tax-inclusive/exclusive breakdown?" Pure functions, no I/O, no side effects.

Proposed API (illustrative — exact signatures need business sign-off per §3.4 below, not invented here):
```php
TaxCalculationService::extractTaxFromInclusiveAmount(float $amount, float $rate): TaxBreakdown  // division formula: base = amount/(1+rate), tax = amount - base
TaxCalculationService::addTaxToExclusiveAmount(float $amount, float $rate): TaxBreakdown         // multiplication formula: tax = amount*rate, total = amount+tax
TaxCalculationService::amountWithTax(string $transactionType, string $salesTaxType, float $amount, float $rate, bool $customerTaxable): TaxBreakdown
    // the unified replacement for the 6 divergent branch implementations in CustomHelper — truth table TBD, see §3.4
```

`TaxBreakdown` is a plain value object: `{ baseAmount, taxAmount, totalAmount }` — never a bare float, so callers can't accidentally treat one field as another (this is exactly how the current unit-mismatch bug class happens).

**Replaces:** the tax-inclusion branch logic inside `getAvailableCredit()`, `updateCreditBalance()`, `reverseTransactionEffect()`, `fixTheRunningBalance()` (×2 branches), all 4 duplicated Blade formulas, and the account-payment tax formula currently triplicated (correctly) across the 3 report engines and (incorrectly) in Dashboard.

**Does not replace:** `invoices.sales_tax` / `invoice_items.tax` handling, which is a distinct unit (dollar amount, not rate) and a distinct calculation (line-item sum, not per-transaction inclusion) — see `InvoiceCalculationService` below. Conflating these two was never correct and the new service must keep them separate, just make each internally consistent.

### 2.2 `LedgerBalanceService`

**Responsibility:** owns `customers.available_credit_balance` and `customer_accounts.balance` — the running-balance side of the ledger. Wraps `TaxCalculationService` for the tax portion of each calculation.

Proposed API:
```php
LedgerBalanceService::applyTransaction(CustomerAccount $record): void       // replaces updateCreditBalance()
LedgerBalanceService::reverseTransaction(CustomerAccount $record): void    // replaces reverseTransactionEffect()
LedgerBalanceService::rebuildForCustomer(int $customerId): RebuildResult  // replaces fixTheRunningBalance(), returns a diff for validation
LedgerBalanceService::currentAvailableCredit(Customer $customer): float  // replaces getAvailableCredit() — becomes a thin wrapper that either reads the stored column or recomputes, not two divergent implementations
```

**Replaces:** methods 1-4 in §1.1. This is the highest-risk service (26+7+7+12 = 52 call sites across #1.1's four methods) and must be migrated last, behind the validation harness in §5.

### 2.3 `InvoiceCalculationService`

**Responsibility:** owns `invoices.subtotal/sales_tax/total/paid_amount/open_amount/invoice_status`. Single method for recomputing all of these from invoice line items and applied payments — used by **both** the edit/delete paths (which already call `updateInvoiceSummary()`) **and** the two payment controllers (which currently don't, per §1.4).

Proposed API:
```php
InvoiceCalculationService::recomputeSummary(Invoice $invoice): void   // replaces updateInvoiceSummary()
```

**Replaces:** `updateInvoiceSummary()` **and** the inline `paid_amount`/`open_amount`/`invoice_status` arithmetic duplicated in both `Invoice\PaymentStoreController` (admin) and `Front\Customer\Dashboard\Invoice\PaymentStoreController`. This is the one place in this report where "consolidate" means "make the payment controllers call the existing method they currently bypass," not "write new logic" — lowest-risk-to-design, but requires care because it's changing *when* a calculation runs, not just *where* it lives (see Risk §4).

### 2.4 `FinancialReportingAdapter` (thin, read-only)

**Responsibility:** NOT a new calculation engine. A thin adapter so `SalesReportEngineV2`, `SalesTaxReportEngine`, and `PaymentReconciliationLedger` call `TaxCalculationService` for the account-payment tax split instead of each maintaining (correctly, but redundantly) their own SQL `CASE` expression, and so `Dashboard\IndexController` is fixed to use the same formula instead of its own (currently incorrect) inline PHP.

Since these are reporting engines operating on large date-range aggregates via raw SQL for performance, `TaxCalculationService`'s formula must be expressible as a portable SQL expression (already true today — the division formula is plain arithmetic), not just a PHP method — the service should expose both a PHP method and a documented SQL-expression constant/builder so report engines and PHP call sites stay in sync by construction, not by copy-paste discipline.

**Does not replace:** `BillingEngine` — `BillingEngine::charge()` already receives a pre-computed `tax_amount` from its callers and performs no tax math itself (confirmed by reading `app/Services/BillingEngine.php` this session). If those callers eventually compute that `tax_amount` via `TaxCalculationService` too, that's a caller-side change, not a `BillingEngine` change — `BillingEngine` stays a pure ledger/state-machine service.

---

## 3. Migration order and dependencies

```
TaxCalculationService (foundation, no dependents yet, zero behavior change if built additively)
    │
    ├──▶ FinancialReportingAdapter (read-only reports; safest first real consumer — no write-path risk)
    │       ├── SalesTaxReportEngine, SalesReportEngineV2, PaymentReconciliationLedger (swap SQL for shared expression)
    │       └── Dashboard\IndexController (swap wrong multiplication for correct division — separately sign-off first, §1.6)
    │
    ├──▶ InvoiceCalculationService (write-path, but isolated to Invoice columns; forces the payment-controller dual-update question to be resolved)
    │       └── Invoice\PaymentStoreController ×2 (admin + front-end) — start calling recomputeSummary()
    │
    └──▶ LedgerBalanceService (write-path, highest blast radius — 52 call sites; migrate LAST, one call site at a time)
            └── CustomHelper::getAvailableCredit/updateCreditBalance/reverseTransactionEffect/fixTheRunningBalance become thin deprecated wrappers during transition, then removed
```

**Why this order:**
1. `TaxCalculationService` has zero existing callers, so building it has zero behavior-change risk — it can be built, unit-tested against every scenario in the prior audit's §4.2 truth table and §7 test cases, and reviewed on its own before anything calls it.
2. Reports are read-only and don't mutate the ledger — the cheapest possible place to prove the shared tax logic produces correct output against real data, and it also finally fixes the confirmed Dashboard divergence (§1.6) as a side benefit once it's separately signed off.
3. `InvoiceCalculationService` touches write paths, but only two columns on one table (`invoices`), with a small, already-enumerated caller list (4 existing + 2 to be added).
4. `LedgerBalanceService` is last because it's the highest-risk surface: 52 call sites, the exact method (`updateCreditBalance`) already responsible for the confirmed Phase 1 bug's root data, and the one place `FixRunningBalancesController`'s existing before/after diff tooling is essential for validation.

### 3.1 Dependencies on already-in-flight work

`BillingEngine` (post-order Fuel/Damage/Extension charges, `docs/billing-engine-audit/*`) is **already merged** into `raj_development` and in active bridge mode (writes both `billing_charges` and legacy `CustomerAccount` rows). This consolidation must not regress that work:
- `BillingEngine::charge()` callers that also write a `CustomerAccount` row still go through `updateCreditBalance()` today (via `ChargeService` and the CRM/Dashboard charge controllers) — when `LedgerBalanceService` replaces `updateCreditBalance()`, these callers move with it, not before.
- The Billing Engine's own refactor plan (Phase 7, `docs/billing-engine-audit/BILLING_ENGINE_REFACTOR_PLAN.md`) already anticipates eventually removing the legacy `CustomerAccount`/`OrderExtraCharges` bridge writes once its own reporting migration is done — that is a separate, already-planned effort and should stay on its own track; this report's `LedgerBalanceService` migration should be sequenced to not block or be blocked by it, just to not contradict it (both converge on "one source of truth," from different starting points).

### 3.2 Risk Assessment

| Item | Risk | Reasoning |
|---|---|---|
| Build `TaxCalculationService` (new code, no callers yet) | **Very low** | Additive only. Can be fully unit-tested against the audit's existing truth table before any caller is touched. |
| Migrate reports to `FinancialReportingAdapter` | **Low** | Read-only, no ledger mutation. Validate by diffing report output before/after for a fixed date range — must be byte-identical for the 3 already-correct engines. |
| Fix Dashboard's wrong tax formula | **Low risk to fix, but is a visible number change** | Not a refactor — a bug fix. Must be scoped and signed off separately from this consolidation (own "Phase 1"-style approval), since dashboard totals will visibly change once corrected. |
| Migrate `InvoiceCalculationService` (incl. payment controllers) | **Medium** | Changes *when* `paid_amount`/`open_amount`/`invoice_status` are computed for a payment, not just where the code lives. Must prove old inline result == new `recomputeSummary()` result for every existing invoice before switching, not just for new payments going forward. |
| Migrate `LedgerBalanceService` (`updateCreditBalance` and siblings) | **High** | 52 call sites across CRM, Invoice, Dashboard, Orders, checkout, `ChargeService`. Any behavior drift affects real customer balances. Must use `FixRunningBalancesController`'s before/after diff pattern against a production data copy, migrate one call site category at a time (not all 52 at once), and keep the old methods callable (thin wrappers) until every caller is confirmed migrated. |
| Refund/Discount tax-treatment unification | **Medium-high, blocked on business input** | The 6 divergent implementations don't just differ in code structure — they encode genuinely different, undocumented business rules for refunds and discounts (audit §4.2). This cannot be resolved by picking one implementation arbitrarily; needs explicit business-owner sign-off on intended behavior, same conclusion as the original audit. |
| `sales_tax` unit mismatch (rate vs. dollar amount) | **Low to mitigate, high to fully resolve** | Mitigate via the `TaxBreakdown` value object (§2.1) and typed method parameters — don't rename columns or touch schema (mission explicitly says no schema changes unless absolutely necessary). |

---

## 4. Recommended implementation sequence (summary)

See the companion document, `PHASE_2_IMPLEMENTATION_PLAN.md`, for the full phase-by-phase breakdown with validation/testing/rollback detail per phase. Sequence at a glance:

1. Build `TaxCalculationService` (additive, zero callers, fully unit-tested against existing truth tables).
2. Get business sign-off on the refund/discount tax-treatment truth table (blocks nothing else — can run in parallel with step 1).
3. Point the 3 already-correct report engines at `TaxCalculationService` (read-only, output must be byte-identical).
4. Separately: get sign-off and fix the confirmed Dashboard tax-formula bug (§1.6) — its own small, isolated change, not bundled with consolidation.
5. Build `InvoiceCalculationService`; migrate the two payment controllers to call it instead of their inline arithmetic; validate against every existing invoice.
6. Consolidate the 3 duplicated Blade tax-breakdown views onto `TaxCalculationService` output.
7. Build `LedgerBalanceService`; migrate `updateCreditBalance()` callers in small batches (by module — CRM, then Invoice, then Dashboard/Orders/checkout, then `ChargeService`), each batch validated via `FixRunningBalancesController`'s diff tooling before the next.
8. Migrate `getAvailableCredit()`, `reverseTransactionEffect()`, `fixTheRunningBalance()` onto `LedgerBalanceService`.
9. Remove the deprecated `CustomHelper` wrapper methods once every caller is confirmed migrated and one full billing cycle has passed with no drift detected.

No phase in this sequence changes accounting behavior except step 4 (the Dashboard bug fix), which is explicitly called out as needing separate sign-off, matching how Phase 1 of the CRM audit was handled.
