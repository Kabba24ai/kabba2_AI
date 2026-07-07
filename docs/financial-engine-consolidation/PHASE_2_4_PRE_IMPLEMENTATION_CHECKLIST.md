# Phase 2.4 — Pre-Implementation Checklist

Checklist date: 2026-07-01
Branch: `raj_development`
Status: **Investigation complete. Reflects a discovered ordering nuance in the front-end payment controller — read before implementation.**

---

## Pre-implementation finding (must be read before the Scope section makes sense)

Direct code reads of both migration targets, done before writing this checklist:

- `Admin\Crm\Customers\Invoice\PaymentStoreController` creates the `CustomerAccount` ledger row first (line 59), then updates `$invoice->paid_amount`/`open_amount`/`invoice_status` inline (lines 75-93) using the just-known payment amount. The ledger row already exists in the database by the time the invoice is updated.
- `Front\Customer\Dashboard\Invoice\PaymentStoreController` does the opposite: it updates `$invoice->paid_amount`/`open_amount`/`invoice_status` inline (lines 97-116, duplicated again at lines 156-171 in a second branch) **before** the `CustomerAccount` ledger row is created (which happens later, at lines 205-231, common to both branches).

`CustomHelper::updateInvoiceSummary()` (the method `InvoiceCalculationService` will wrap) computes `paid_amount` by querying the ledger directly: `CustomerAccount::where('invoice_id', $invoice->id)->where('type', 'payment')->sum('amount')`. If the front-end controller's inline block were replaced with a call to the new service **in its current position**, that call would run *before* the new payment's ledger row exists, and would compute `paid_amount` from only the prior payments — silently dropping the payment just made from the invoice's recorded totals until some later, unrelated recompute happened to run. This would be a real, introduced bug, not a preserved behavior.

**Consequence for scope:** the front-end controller's migration is not a like-for-like inline-swap the way the admin controller's is. It requires moving the recompute call to after the ledger row is saved (removing the now-redundant inline blocks from both branches) — a small, structural correction required to make the approved migration behave correctly, not a scope expansion. This is documented here, before writing code, per the mission's explicit instruction to stop and document a discovered issue rather than proceed on an unstated assumption.

## Objectives

- Create `InvoiceCalculationService` as the authoritative home for invoice summary/paid/open/status recomputation, per Architecture Report §2.3 and Implementation Plan Phase 2.4.
- Move `CustomHelper::updateInvoiceSummary()`'s existing logic into the new service **verbatim** — no formula, rounding, or business-rule changes.
- Turn `CustomHelper::updateInvoiceSummary()` into a one-line delegator to the new service, so its existing callers (`CustomerAccount\UpdateController`, `CustomerAccount\DeleteController`, `Invoice\UpdateController`, `Invoice\DeleteInvoiceController`, `BulkDeleteController`, `RepairDeletedOrdersController`) continue to work completely unchanged.
- Migrate both invoice payment controllers off their inline `paid_amount`/`open_amount`/`invoice_status` arithmetic onto the new service, correcting the front-end controller's ordering issue as part of that migration (see finding above).

## Scope

**In scope:**
- `app/Services/InvoiceCalculationService.php` — new file, `recomputeSummary(Invoice $invoice): void`, body moved verbatim from `CustomHelper::updateInvoiceSummary()`.
- `app/Helpers/CustomHelper.php` — `updateInvoiceSummary()` becomes a thin delegator; no other method in this file changes.
- `app/Http/Controllers/Admin/Crm/Customers/Invoice/PaymentStoreController.php` — inline block (lines 75-93) replaced with a call to the new service, in the same position (safe, since the ledger row already exists by that point).
- `app/Http/Controllers/Front/Customer/Dashboard/Invoice/PaymentStoreController.php` — inline blocks removed from both branches; a single call to the new service added after the ledger row is created and saved (structural correction required for correctness, per the finding above).

**Explicitly out of scope:**
- `app/Services/Reports/SalesTaxReportEngine.php` — not touched.
- `LedgerBalanceService` — not built in this phase; `CustomHelper::updateCreditBalance()` and related balance methods are untouched.
- Any refund, discount, or charge write path (`CustomerAccount\RefundStoreController`, `DiscountStoreController`, `ChargeStoreController`).
- The existing (already-correct) callers of `CustomHelper::updateInvoiceSummary()` — they are not touched; they keep calling the same method name, which now delegates.
- Invoice creation logic (`Invoice\StoreController`), invoice item logic (`InvoiceItem` model), and any Blade view.
- Any database schema, migration, route, job, or command file.

## Files expected to change

| File | Change |
|---|---|
| `app/Services/InvoiceCalculationService.php` | New file — `recomputeSummary()`, moved verbatim |
| `app/Helpers/CustomHelper.php` | `updateInvoiceSummary()` becomes a one-line delegator |
| `app/Http/Controllers/Admin/Crm/Customers/Invoice/PaymentStoreController.php` | Inline block replaced with a service call, same position |
| `app/Http/Controllers/Front/Customer/Dashboard/Invoice/PaymentStoreController.php` | Inline blocks removed from both branches; one service call added after the ledger row is saved |

## Files out of scope

- `app/Services/Reports/SalesTaxReportEngine.php`
- `app/Services/Reports/SalesReportEngineV2.php`, `app/Services/Reports/PaymentReconciliationLedger.php` (already migrated, Phase 2.2)
- `app/Http/Controllers/Admin/Dashboard/IndexController.php` (already migrated, Phase 2.3)
- `app/Http/Controllers/Admin/Crm/Customers/CustomerAccount/UpdateController.php`, `DeleteController.php` — still call `CustomHelper::updateInvoiceSummary()` directly, unchanged
- `app/Http/Controllers/Admin/Crm/Customers/Invoice/UpdateController.php`, `DeleteInvoiceController.php` — same, unchanged
- `app/Http/Controllers/Admin/OrderManagement/Orders/BulkDeleteController.php`, `RepairDeletedOrdersController.php` — same, unchanged
- Any ledger balance method (`updateCreditBalance`, `reverseTransactionEffect`, `fixTheRunningBalance`, `getAvailableCredit`)

## Risk assessment

**Medium**, matching the Implementation Plan's original risk rating for this phase — this is the first phase in the initiative to touch an active write path affecting customer-facing invoice state, not a read-only report or a foundation class with zero callers.

- The admin controller's migration is **low risk**: a like-for-like replacement, the ledger row already exists at the point of the call, and `recomputeSummary()`'s query-the-ledger approach should reproduce the same `paid_amount` the inline `+=` approach would, *provided the invoice was already internally consistent* (see Behavioral Equivalence Validation strategy below).
- The front-end controller's migration is **higher risk**, specifically because of the ordering correction described above — this is exactly the kind of change that "looks like a one-line swap" but isn't, and is where a careless migration could introduce a real regression. Extra care and explicit before/after logic tracing (not just formula comparison) is applied here.
- **A residual, disclosed risk that cannot be fully closed in this environment**: `recomputeSummary()` recomputes `subtotal`/`sales_tax`/`total` from invoice items *every time it runs*, whereas the inline code being replaced never touched those fields at all — it trusted the invoice's already-stored `total`. If any existing invoice in production has ever drifted (its stored `total` no longer matches the sum of its current items — from a data bug unrelated to this phase, or from items being edited without a subsequent recompute), migrating to `recomputeSummary()` would silently correct that invoice's total the next time a payment is made against it. This is very likely the *intended*, more-correct behavior in the long run (a "recompute from source" method self-healing drift is generally good), but it is a behavior change relative to today's inline code for any invoice that has already drifted — see Validation Strategy and Outstanding Issues in the Completion Report.

## Validation strategy

- **Algebraic/logical equivalence proof** (performable without a database): trace both the old inline logic and the new `recomputeSummary()` logic symbolically for a representative invoice, showing that under the assumption of a *consistent* invoice (stored `total` already matches the sum of its items; stored `paid_amount` already matches the ledger sum before the new payment), the two approaches produce identical `paid_amount`, `open_amount`, and `invoice_status` results after the same payment is applied. This proof is documented in full in the Completion Report.
- **Ordering trace for the front-end controller**: an explicit, line-by-line trace confirming the new service call executes after the ledger row exists, for both branches, and that all other invoice fields set earlier in each branch (`payment_method`, `auth_code`, `customer_profile_id`, `payment_profile_id`, transaction IDs) are still correctly persisted.
- **What cannot be validated in this environment**: there is no live test database available (consistent with every prior phase's documented limitation), and no existing model factories for `Invoice`, `InvoiceItem`, or `CustomerAccount` to build new Feature tests against. This means the specific question "does any real invoice in production currently have drifted totals" cannot be answered here. This is disclosed as an Outstanding Issue in the Completion Report, with a concrete recommended staging validation step (the same before/after snapshot-and-diff method used conceptually in the Implementation Plan), not silently assumed away.

## Rollback strategy

- Each of the four changed files can be reverted independently via `git checkout -- <file>` (or reverting the specific commit).
- `CustomHelper::updateInvoiceSummary()` remains present under its existing name throughout — no caller anywhere in the codebase needs to change if this phase is rolled back, since the method name and signature are unchanged, only its internal implementation delegates elsewhere.
- No database schema or migration is introduced — rollback is a pure code revert with no data cleanup required.

## Success criteria

- `InvoiceCalculationService::recomputeSummary()` exists and is byte-for-byte the same calculation logic as the current `CustomHelper::updateInvoiceSummary()` (moved, not rewritten).
- `CustomHelper::updateInvoiceSummary()`'s six existing callers require zero code changes and are confirmed (via `git diff`) untouched.
- Both invoice payment controllers call the new service instead of inlining `paid_amount`/`open_amount`/`invoice_status` arithmetic.
- The front-end controller's ledger-row-before-recompute ordering is corrected as part of the migration, not left as a latent bug.
- The algebraic equivalence proof is documented and holds for the consistent-invoice case; the drift-correction risk is explicitly disclosed, not hidden.
- All runnable tests pass; any suite that cannot run in this environment is named with a reason.
- `SalesTaxReportEngine.php` has zero diff.

## Expected pull request size

Small-to-medium. Four application files (one new, three modified), no new database migration, no new Blade views. Estimated diff: under 120 lines of application code, excluding documentation — larger than Phase 2.3's Dashboard fix because this phase touches two controllers with independent structural nuances, but still scoped to exactly the approved migration.
