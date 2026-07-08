# Financial Engine Consolidation — Phase 2 Implementation Plan

Plan date: 2026-07-01
Branch: `raj_development`
Status: **Plan only — no code changes made. Do not begin coding until this plan is reviewed and approved.**
Companion document: `PHASE_2_ARCHITECTURE_REPORT.md` (read that first — this plan assumes its service boundaries and migration order)

---

## Ground rules for every phase below

Carried forward from the mission brief — repeated here because every phase must satisfy all of them, not just the ones that seem relevant:

- Preserve existing financial behavior unless a confirmed bug is identified and separately signed off.
- No broad refactors — each phase touches the smallest possible surface.
- No database schema changes.
- No UI redesigns.
- Every phase is independently reviewable and mergeable — a phase that can't be merged on its own without a later phase is scoped wrong.
- Every phase states its own validation method and rollback method before it starts, not after.

---

## Phase 2.0 — Business sign-off on tax-treatment truth table (no code)

**What:** Before any consolidation code is written, get the business owner (whoever owns billing rules) to confirm intended tax treatment for Refund and Discount transactions, using the divergence table in `CRM_BILLING_PAYMENT_AUDIT.md` §4.2 as the discussion starting point, not a decision already made.

**Why first:** `LedgerBalanceService::amountWithTax()` (architecture report §2.1) cannot be written correctly without this — the 4 existing implementations don't just differ in code, they encode 4 different undocumented business assumptions. Writing the unified method without sign-off means guessing which of 4 wrong-in-different-ways behaviors to keep, and is exactly how the original bug happened.

**Deliverable:** a one-page confirmed truth table (Payment / Refund / Discount / Charge × taxable/exempt × `sales_tax_type`), signed off by the business owner, checked into `docs/financial-engine-consolidation/`.

**Validation:** N/A (no code).

**Rollback:** N/A (no code).

**Blocks:** Phase 2.6 and Phase 2.7 (anything that unifies the tax-inclusion branch logic). Does **not** block Phases 2.1-2.5, which don't touch Refund/Discount tax rules.

---

## Phase 2.1 — Build `TaxCalculationService` (additive, no callers)

**What:** Create `app/Services/TaxCalculationService.php` and a `TaxBreakdown` value object, implementing exactly the two formulas already proven correct elsewhere in the codebase:
- `extractTaxFromInclusiveAmount(amount, rate)` — the division formula (`base = amount/(1+rate)`, `tax = amount - base`), copied verbatim from `SalesReportEngineV2`/`_tab_credit.blade.php`, not re-derived.
- `addTaxToExclusiveAmount(amount, rate)` — the multiplication formula (`tax = amount*rate`, `total = amount+tax`), copied verbatim from `_additional_charges.blade.php`.

Do **not** implement `amountWithTax()` (the unified transaction-type-aware method) in this phase — that requires the Phase 2.0 sign-off and is scoped to Phase 2.6.

**Why safe:** Zero existing code calls this class yet. It cannot change any behavior because nothing depends on it.

**Validation:**
- Unit tests asserting both formulas against the exact numeric example from the original bug report ($150.00 = $136.67 + $13.33, rate ≈ 0.0887) and against a table of edge cases: `rate = 0`, `amount = 0`, very small amounts (rounding), typical rates (5-10%).
- No manual/staging validation needed — no integration point exists yet.

**Rollback:** Delete the new file. Nothing else references it, so this is a zero-risk revert.

**Review scope:** One new file + one test file. Reviewable in isolation.

---

## Phase 2.2 — Migrate `SalesTaxReportEngine`, `SalesReportEngineV2`, `PaymentReconciliationLedger` to `TaxCalculationService`

**What:** Replace the three independent (but already mutually-consistent, already-correct) SQL/PHP implementations of the account-payment tax-extraction formula with calls to `TaxCalculationService`'s SQL-expression form (or a shared query builder helper it exposes — see architecture report §2.4 on exposing both a PHP method and a portable SQL expression).

**Why safe:** All three currently produce the same (correct) numbers. This phase changes *where* the formula lives, not what it computes. Read-only reporting — no ledger mutation, no risk to customer balances.

**Validation:**
- Run each report for a fixed date range (e.g. a full prior month with a representative mix of payment types and tax rates) **before** the change, save the output.
- Run the same reports **after** the change for the same date range.
- Diff must be byte-identical (down to rounding) for every KPI these three services expose.
- Add a regression test that runs all three reports against a fixed seeded dataset and asserts exact output, so future changes can't silently drift.

**Rollback:** Revert the commit. No data was written; reports are pure reads.

**Review scope:** Three files changed (report engines), no behavior change expected — reviewer's job is to confirm the diff step in Validation was actually run and is attached to the PR description.

---

## Phase 2.3 — Fix the confirmed Dashboard tax-formula bug (separate sign-off, own phase)

**What:** `Dashboard\IndexController::getRevenueRows()` (lines 837-849) currently computes account-payment tax via `amount * rate` instead of the correct `amount - amount/(1+rate)`. Fix this call site to use `TaxCalculationService::extractTaxFromInclusiveAmount()`.

**This is a bug fix, not a refactor** — flag and treat it exactly like Phase 1 of the original CRM audit: get explicit sign-off before merging, because the Dashboard's displayed revenue totals will visibly change once corrected (they are currently wrong relative to Pure Sales Summary / Sales Tax report for the same period).

**Why this phase is separate from 2.2:** Bundling a genuine bug fix into a "no behavior change" consolidation phase would violate the "preserve existing behavior unless a confirmed bug is identified" ground rule, and would make it impossible to review the refactor and the bug fix independently.

**Validation:**
- Before merging: run Dashboard revenue totals and Pure Sales Summary totals for the same date range on a staging copy of production data; document the discrepancy (expected, since this is the bug).
- After merging: re-run both; totals for account-payment tax must now match (allowing for the two reports' different scopes — this is about the *formula*, not claiming the two reports become identical in every other respect).
- Get sign-off from whoever owns Dashboard reporting that the new (correct) numbers are expected and communicate the change, since historical dashboard figures for past periods will shift if recalculated.

**Rollback:** Revert the single-line formula change. Read-only display code, same safety profile as the original Billing Summary fix.

**Review scope:** One file, one formula. Same shape as the already-shipped Phase 1 fix.

---

## Phase 2.4 — Build `InvoiceCalculationService`, migrate payment controllers

**What:**
1. Create `app/Services/InvoiceCalculationService.php` wrapping the existing `CustomHelper::updateInvoiceSummary()` logic (move, don't rewrite — copy the method body verbatim into the new service first).
2. Update `Admin\Crm\Customers\Invoice\PaymentStoreController` and `Front\Customer\Dashboard\Invoice\PaymentStoreController` to call `InvoiceCalculationService::recomputeSummary($invoice)` **instead of** their current inline `paid_amount`/`open_amount`/`invoice_status` arithmetic.
3. Leave `CustomHelper::updateInvoiceSummary()` in place as a thin wrapper delegating to the new service (do not delete yet — other callers, e.g. `UpdateController`/`DeleteController`, still call it directly and should be left alone in this phase).

**Why this needs its own phase:** This is the one place in the whole plan where the change is genuinely behavioral — replacing inline arithmetic with an aggregation method changes *when* the calculation runs relative to other invoice-item edits, even if the two happen to produce the same number today. This must be validated per-invoice, not assumed.

**Validation:**
- Before the change: for every open/partially-paid invoice in a staging copy of production data, record `paid_amount`/`open_amount`/`invoice_status`.
- Make the change.
- Trigger a no-op recompute (call `recomputeSummary()` for every invoice without any new payment) and diff against the "before" snapshot — must match exactly. This proves the aggregation method reproduces the inline arithmetic's historical results before it starts being used for new payments.
- Add feature tests: single payment, partial payment, multiple partial payments, overpayment (credit), zero-amount edge case — covering test scenarios #2, #6, #10, #11, #13, #14 from `CRM_BILLING_PAYMENT_AUDIT.md` §7.
- Manual staging test: make a real payment through both the admin and front-end payment forms, confirm invoice status/amounts update identically to current production behavior.

**Rollback:** Revert the payment-controller changes to restore inline arithmetic; `InvoiceCalculationService` can stay (unused) or be removed — no data migration involved since no schema changed and the columns being written are the same ones written before.

**Review scope:** One new service file + two controller files + tests. The PR description must include the before/after snapshot diff from Validation, not just "tests pass."

---

## Phase 2.5 — Consolidate the 3 duplicated Blade tax-breakdown views

**What:** Extract the per-row tax-breakdown formula duplicated in `_tab_credit.blade.php` (admin), `_tab_credit.blade.php` (front-end), and `transaction_accounts_pdf.blade.php` into calls to `TaxCalculationService`. Also point the newly-discovered fourth site, `_additional_charges.blade.php:32-35`, at the service (it already uses the correct multiplication formula for charges — no behavior change, just de-duplication).

**Why safe:** Extract, don't rewrite — the exact existing formula moves into the shared service (already built and unit-tested in Phase 2.1); the Blade views call it instead of inlining it.

**Validation:**
- Snapshot rendered HTML output for a representative sample of ledger rows (every `type` × both `sales_tax_type` values, at least one taxable and one tax-exempt customer) before the change.
- Re-render after the change; diff must be byte-identical.
- Client-side JS mirrors (`_tab_credit.blade.php:1411-1418`, `:512-519`) are out of scope for this phase — they duplicate the formula for live client-side preview before a page reload and cannot call a PHP service directly. Leave them as-is; note in the PR description that this is a known, accepted remaining duplication (JS preview code, not authoritative — the server-rendered value after save is always the source of truth).

**Rollback:** Revert the Blade template changes. No data or backend logic touched.

**Review scope:** Four Blade files, using a service already reviewed and merged in Phase 2.1.

---

## Phase 2.6 — Build `amountWithTax()` on `LedgerBalanceService` (requires Phase 2.0 sign-off)

**What:** Using the business-confirmed truth table from Phase 2.0, implement the single unified `amountWithTax(transactionType, salesTaxType, amount, rate, customerTaxable)` method that will replace the 6 divergent branch implementations (`getAvailableCredit`, `updateCreditBalance`, `reverseTransactionEffect`, `fixTheRunningBalance` ×2 branches).

**Do not wire it into any caller yet** — this phase is "build and unit-test the replacement," not "cut over." Cutover is Phases 2.7-2.9.

**Why separated from building:** the unified method is the single highest-consequence piece of new logic in this entire plan — it decides how every future Refund and Discount transaction affects a customer's real financial balance. It deserves a review pass entirely on its own, against the signed-off truth table, before any production code path depends on it.

**Validation:**
- Unit tests: every cell of the Phase 2.0 truth table becomes one test case (transaction type × `sales_tax_type` × taxable/exempt).
- Regression tests: for every one of the 6 existing (divergent) implementations, run the same inputs through both old and new and document every input where they disagree — this list becomes the actual "behavior change" being introduced (since by definition, at least one of the 4-6 existing behaviors must change to become consistent). This diff list is the artifact the business owner reviews to confirm Phase 2.0's sign-off actually covers every case that changes.

**Rollback:** Delete the new method; nothing calls it yet.

**Review scope:** One method + its test suite + the disagreement diff list. No production caller changed.

---

## Phase 2.7 — Migrate `updateCreditBalance()` callers, one module at a time

**What:** `updateCreditBalance()` has 26 call sites (architecture report §1.8) across CRM, Invoice, Dashboard, Orders, checkout, and `ChargeService`. Migrate them to `LedgerBalanceService::applyTransaction()` in **separate sub-phases by module**, not all at once:

| Sub-phase | Module | Approx. call sites |
|---|---|---|
| 2.7.a | CRM CustomerAccount controllers (Payment/Refund/Discount/Charge/Update) | 5 |
| 2.7.b | Invoice controllers (admin + front-end Payment, Update, Store) | 5 |
| 2.7.c | Dashboard/Orders (Damage/Fuel charge, alert charge, add-to-account) | 5 |
| 2.7.d | Checkout (`Front\Checkout\PostController`) | 1 |
| 2.7.e | `ChargeService` (shared fuel/damage backend, also used by `BillingEngine` bridge callers) | 3 |

Each sub-phase is its own PR, merged and observed before the next starts.

**Why one module at a time:** 26 call sites migrated in one PR is not independently reviewable or safely rollback-able — a regression in one module would force reverting all 26. Per-module batching means a regression is isolated to that module's PR.

**Validation per sub-phase:**
- Use `FixRunningBalancesController`'s existing before/after diff pattern (it already replays the full ledger and reports `CORRUPTED_AND_FIXED` vs `OK` per customer) against a copy of production data, run once before and once after each sub-phase's change. Any new `CORRUPTED_AND_FIXED` entry that wasn't there before is a regression, not a fix — halt and investigate before proceeding to the next sub-phase.
- Feature tests covering that module's write paths from `CRM_BILLING_PAYMENT_AUDIT.md` §7 test list.
- 2.7.e specifically must also confirm no regression in `BillingEngine`'s bridge-mode tests (`docs/billing-engine-audit/PHASE_5D_FINAL_DATA_INTEGRITY_PATCH.md` documents 197 existing passing tests for this area) — re-run that suite as part of this sub-phase's validation.

**Rollback:** Each sub-phase is an independent commit/PR; revert just that sub-phase's commit. Since `CustomHelper::updateCreditBalance()` remains available as a wrapper throughout (see below), reverting a sub-phase is a pure code revert with no data implications.

**Important:** `CustomHelper::updateCreditBalance()` itself is **not removed** during Phase 2.7 — it becomes a thin wrapper calling `LedgerBalanceService::applyTransaction()` only after **all** sub-phases (2.7.a-e) are complete, so unmigrated call sites keep working unchanged throughout.

---

## Phase 2.8 — Migrate `getAvailableCredit()`, `reverseTransactionEffect()`, `fixTheRunningBalance()`

**What:** Same pattern as Phase 2.7, applied to the remaining three methods (12 + 7 + 7 = 26 more call sites). `reverseTransactionEffect()` and `fixTheRunningBalance()` are tightly coupled in the delete path (§1.5 of the architecture report) — migrate them together, not separately, to preserve the exact sequencing `DeleteController` depends on.

**Validation:** Same `FixRunningBalancesController` diff pattern, plus an explicit test that reproduces the delete-path sequence (create transaction → delete → assert balance reverts, matching test scenario #8 in the audit's §7) to confirm the sequencing invariant survived the migration.

**Rollback:** Same per-sub-phase revert strategy as Phase 2.7.

---

## Phase 2.9 — Remove deprecated `CustomHelper` wrapper methods

**What:** Once every caller from Phases 2.4, 2.7, and 2.8 is confirmed migrated, and at least one full billing cycle has passed in production with no drift detected (monitored via `FixRunningBalancesController` run on a schedule, or equivalent), delete the now-unused wrapper methods from `CustomHelper.php`.

**Validation:** Grep confirms zero remaining references before deletion. Full test suite passes.

**Rollback:** Trivial — these are dead wrappers by this point; restoring them from git history is risk-free if something surfaces later.

**Note:** This phase has no urgency and should not be scheduled until the team is confident in the prior phases. Leaving unused wrapper methods in place slightly longer costs nothing; removing them before confidence is earned costs a lot if something was missed.

---

## Phase-to-mission-consumer mapping

For traceability against the mission's list of future consumers:

| Consumer named in mission | Addressed by |
|---|---|
| CRM | Phases 2.6-2.8 (`LedgerBalanceService`) |
| Customer Credit Accounts | Phases 2.6-2.8 |
| Invoicing | Phase 2.4 (`InvoiceCalculationService`) |
| Billing Engine | Not migrated in this plan — already has its own bridge-mode architecture (`docs/billing-engine-audit/`); Phase 2.7.e ensures its `CustomerAccount`-writing callers move onto the new ledger service without regressing its own tests |
| Sales Reports | Phases 2.2-2.3 |
| Dashboard Reporting | Phase 2.3 |
| Future QuickBooks integration | Not built now — but `TaxCalculationService`/`InvoiceCalculationService` (Phases 2.1, 2.4) become the natural integration point once that work starts, since it would need exactly this kind of single authoritative tax/total calculation to export |
| Future Customer Statements | Same as QuickBooks — no statement feature exists today; the ledger/invoice services built here are what a future statement generator would read from, rather than re-deriving totals itself |

---

## What this plan deliberately does not do

- Does not touch database schema.
- Does not redesign any UI.
- Does not fix the Refund/Discount tax-inconsistency by picking a winner without business sign-off (Phase 2.0 is a hard gate, not a formality).
- Does not bundle the confirmed Dashboard bug fix (Phase 2.3) into the "no behavior change" consolidation work — it is its own signed-off change.
- Does not attempt to migrate `BillingEngine` itself or its already-planned Phase 6-7 work (`docs/billing-engine-audit/BILLING_ENGINE_REFACTOR_PLAN.md`) — that is a separate, already-scoped effort this plan is designed to not collide with.
- Does not set a deadline or urgency ranking beyond the dependency order above — sequencing here is about safety, not speed.
