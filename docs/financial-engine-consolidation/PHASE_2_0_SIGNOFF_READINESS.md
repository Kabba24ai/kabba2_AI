# Phase 2.0 — Business/Technical Sign-Off Readiness Package

Document date: 2026-07-01
Branch: `raj_development`
Status: **Readiness review only — no application code changed.**
Author: Claude (documentation session, read-only against application code)

Source documents reviewed (unchanged since last written, verified via `git status` before this review began):
- `docs/crm-billing-audit/CRM_BILLING_PAYMENT_AUDIT.md`
- `docs/financial-engine-consolidation/PHASE_2_ARCHITECTURE_REPORT.md`
- `docs/financial-engine-consolidation/PHASE_2_IMPLEMENTATION_PLAN.md`
- `docs/financial-engine-consolidation/FINANCIAL_ENGINE_MASTER_ROADMAP.md`

---

## 1. Executive Summary

Four documents have been produced so far: the original CRM Billing Payment Audit (root-caused a confirmed display bug and surfaced a structural pattern of duplicated financial logic), the Phase 2 Architecture Report (proposed four shared services and a migration order, and independently discovered a second tax-formula bug in Dashboard reporting), the Phase 2 Implementation Plan (broke the work into ten small, independently reviewable phases, 2.0 through 2.9), and the Financial Engine Master Roadmap (the long-term executive reference tying all of this together).

**Phase 1 — the CRM Billing Summary "Last Payment" display fix — is complete and deployed** to `raj_development` (merged via PR #546). This is the only application code change made anywhere in this initiative to date.

**Phase 2.0, per the Implementation Plan, is a business sign-off gate — not a coding phase.** Its stated deliverable is a confirmed truth table for Refund/Discount tax treatment, obtained before any unified tax-inclusion logic is built (Implementation Plan §Phase 2.0). This document exists to prepare that gate properly: to lay out, in one place, exactly what has been confirmed, what is still just a proposal, what needs business approval, what needs technical approval, and what the safest possible first coding step is once approvals are in — so that sign-off can happen deliberately, against a complete picture, rather than being inferred from four separate documents of varying scope and audience.

No application code is changed by this document or by producing it. This is a checkpoint, not an implementation step.

---

## 2. Confirmed Production Issues

These are the only two items in the entire initiative that are **confirmed bugs with a customer-visible or business-visible symptom**, as distinct from the architectural/structural concerns in §3-4 of the Architecture Report, which are *risks to consolidate carefully*, not confirmed defects in themselves. Keeping this distinction sharp matters for sign-off: the items below need a fix decision; the structural items need a design decision.

### 2.1 CRM Billing Summary "Last Payment" display bug — FIXED, DEPLOYED

- **Status: Resolved.** Merged to `raj_development` via PR #546 (2026-07-01).
- **Symptom:** A $150.00 payment ($136.67 principal + $13.33 tax) displayed as ~$164.63 on the Billing Summary "Last Payment" column.
- **Root cause:** `resources/views/admin/crm/billing_summary/partials/_table.blade.php` added tax on top of an amount that was already tax-inclusive.
- **Scope of impact:** Display-only. The ledger, the running balance, and the Authorize.Net transaction amount were confirmed correct throughout — this was never a data-integrity issue.
- **No further action required.** Referenced here only for completeness of the record; not a sign-off item.

### 2.2 Dashboard revenue/tax formula bug — IDENTIFIED, NOT FIXED

- **Status: Confirmed, unfixed, unscheduled.**
- **Location:** `app/Http/Controllers/Admin/Dashboard/IndexController.php`, method `getRevenueRows()`, lines 837-849.
- **Symptom:** Account-payment tax is computed as `amount * rate` (multiplication). Three other services that compute the same figure — `SalesReportEngineV2`, `SalesTaxReportEngine`, `PaymentReconciliationLedger` — all correctly compute it as `amount - amount/(1+rate)` (division), because a payment's stored `amount` is already tax-inclusive. This was verified directly against the source code during the Phase 2 Architecture Report session (Architecture Report §1.6), not merely reported by an automated search.
- **Effect:** Dashboard revenue/tax totals do not reconcile with Pure Sales Summary or Sales Tax report totals for the same date range today. This is a live discrepancy in production right now, independent of anything this initiative does next.
- **Why it is not fixed yet:** The Implementation Plan (Phase 2.3) deliberately isolates this as its own signed-off change, separate from consolidation work, because correcting it will visibly change previously-reported dashboard figures for past periods — the same treatment Phase 1 received. It has not been bundled into any other phase and no code has been touched.
- **This is a §4 Business Sign-Off item (see §4.7 below), not a §3 architecture item** — it requires a decision to fix and a communication plan, not a design decision.

**No other confirmed bugs exist in the current documentation set.** Everything else described in the audit and architecture report (divergent tax-inclusion branches, dual invoice-update paths, two independent balance computations, the `sales_tax` unit mismatch) is a structural/architectural risk — plausible sources of future bugs, and reason enough to consolidate — but none of them has been shown to be producing an incorrect number in production today. That distinction should not be lost during sign-off: approving the architecture is not the same as declaring four more bugs confirmed.

---

## 3. Architecture Decision Checklist

The Phase 2 Architecture Report proposed four services. Each is evaluated below for approval-readiness: is the boundary well-defined enough to build against, or does something need to be resolved first?

### 3.1 `TaxCalculationService`

| | |
|---|---|
| **Purpose** | The single authoritative place that computes tax breakdowns — the root of the entire consolidation effort. |
| **Responsibilities** | Extracting tax from a tax-inclusive amount (division formula); adding tax to a tax-exclusive amount (multiplication formula); eventually, the unified `amountWithTax()` transaction-type-aware method. |
| **Non-responsibilities** | Does not touch the database. Does not know about `CustomerAccount`, `Invoice`, or any Eloquent model — it operates on plain numbers and a `TaxBreakdown` value object. Does not decide *when* a calculation should run, only *what* the correct answer is. |
| **First safe implementation target** | Build the two already-proven formulas (`extractTaxFromInclusiveAmount`, `addTaxToExclusiveAmount`) with zero callers — pure addition to the codebase, unit-tested against the existing $150/$136.67/$13.33 example and edge cases. |
| **Highest-risk migration area** | The unified `amountWithTax()` method — it cannot be safely written until Phase 2.0's business sign-off on Refund/Discount tax treatment exists (§4.1-4.2 below). Building this piece early, without that sign-off, is the single most likely way this initiative could repeat the mistake that caused the original bug. |
| **Ready for approval?** | **Yes, for the two proven formulas.** The unified method is explicitly gated and should not be approved as "ready to build" until §4.1-4.2 are resolved. |

### 3.2 `LedgerBalanceService`

| | |
|---|---|
| **Purpose** | Owns the customer running balance — both the stored, incrementally-updated column (`available_credit_balance`) and the live-recomputed value — collapsing today's two independent computations into one. |
| **Responsibilities** | Applying a transaction's balance effect; reversing a transaction's balance effect; rebuilding a customer's full running balance; reporting current available credit. |
| **Non-responsibilities** | Does not compute tax itself — delegates to `TaxCalculationService`. Does not own invoice totals (`InvoiceCalculationService`'s job). Does not decide Refund/Discount tax rules on its own authority — those rules come from the Phase 2.0 sign-off, not from whoever implements this service. |
| **First safe implementation target** | Not this phase — per the Implementation Plan, this service is built and migrated last (Phases 2.6-2.8), after `TaxCalculationService` and `InvoiceCalculationService` have already been proven in production. |
| **Highest-risk migration area** | `updateCreditBalance()`, with 26 confirmed call sites spanning CRM, Invoicing, Dashboard, Orders, checkout, and `ChargeService` — the single highest-blast-radius method in the entire codebase. Any behavioral drift here affects real customer balances across every one of those modules simultaneously. |
| **Ready for approval?** | **Boundary is well-defined and approvable as a design.** Implementation should not start until Phases 2.1-2.5 have been completed and validated, per the Implementation Plan's stated order — approving the design now does not mean authorizing its build now. |

### 3.3 `InvoiceCalculationService`

| | |
|---|---|
| **Purpose** | Owns invoice totals and the paid/open-amount lifecycle, replacing two currently-divergent code paths with one. |
| **Responsibilities** | Recomputing `subtotal`/`sales_tax`/`total`/`paid_amount`/`open_amount`/`invoice_status` from an invoice's line items and applied payments. |
| **Non-responsibilities** | Does not compute the Credit Account running balance (`LedgerBalanceService`'s job). Does not decide when a payment is applied — only what the invoice's totals should be once one has been. |
| **First safe implementation target** | Move the existing `updateInvoiceSummary()` logic into the new service **verbatim** first (copy, don't rewrite), before touching either payment controller — this alone is a zero-behavior-change step, per Implementation Plan Phase 2.4 step 1. |
| **Highest-risk migration area** | Migrating the two payment controllers (`Admin\Crm\Customers\Invoice\PaymentStoreController` and its front-end counterpart) off their confirmed inline `paid_amount`/`open_amount`/`invoice_status` arithmetic and onto the aggregation method — this is the one place in the whole plan where the change is genuinely behavioral (changing *when* a calculation runs, not just *where* the code lives), confirmed by direct code read during the Architecture Report session, not assumed. |
| **Ready for approval?** | **Yes.** This is the most self-contained of the four services — its caller list is small (4 existing + 2 to migrate) and already fully enumerated. No open business questions block its design. |

### 3.4 `FinancialReportingAdapter`

| | |
|---|---|
| **Purpose** | A thin, read-only adapter so Sales Reports and Dashboard Reporting consume `TaxCalculationService`'s formula instead of each maintaining an independent copy. Not a new calculation engine. |
| **Responsibilities** | Exposing the tax-extraction formula in a form usable both from PHP and from SQL aggregation (reporting engines operate on large date-range aggregates for performance and currently use raw SQL `CASE` expressions). |
| **Non-responsibilities** | Does not replace `BillingEngine`, which already owns its own charge lifecycle and receives pre-computed tax amounts from its callers. Does not change report *scope* or *filters* — only the shared arithmetic underneath. |
| **First safe implementation target** | Point the three **already-correct** report engines (`SalesReportEngineV2`, `SalesTaxReportEngine`, `PaymentReconciliationLedger`) at the shared formula — read-only, no ledger mutation, output must be byte-identical before/after (Implementation Plan Phase 2.2). |
| **Highest-risk migration area** | Not the adapter itself — the risk is entirely in Phase 2.3, the separately-gated Dashboard bug fix, which is a genuine behavior change (see §2.2 and §4.7) and must not be conflated with this adapter's zero-behavior-change migration of the three already-correct engines. |
| **Ready for approval?** | **Yes, for the read-only adapter design.** The Dashboard fix that eventually plugs into it is a distinct approval item, covered in §4.7, not a reason to hold up the adapter's design approval. |

### 3.5 Overall architecture readiness

All four service boundaries are coherent, non-overlapping, and match the natural structure already present in the data (ledger rows vs. invoices vs. reporting vs. post-order charges). None require a database schema change. None require a UI change. **The architecture is ready for approval as a design.** What is not ready — and should not be treated as approved by approving the design — is authorization to build the two highest-risk pieces (`LedgerBalanceService`'s `amountWithTax()`, and any `LedgerBalanceService` migration at all) before the business items in §4 are resolved.

---

## 4. Business Sign-Off Items

These require business/accounting approval before any related implementation begins. Each is stated as a question needing an answer, not a recommendation needing a rubber stamp — the whole point of this gate is that these are currently unresolved, not that a default answer already exists.

### 4.1 Refund tax behavior

Four independent existing implementations disagree on how tax applies to a Refund transaction (Architecture Report §1.1, Audit §4.2). `RefundStoreController` never even collects a `sales_tax_type` from the user — every existing implementation branches as if it could vary, but in practice refunds are always taxed the same way today, undocumented as an intentional rule. **Needs:** an explicit, written confirmation of whether tax should be added/removed/ignored on a refund, and whether that should ever vary by circumstance (e.g. full vs. partial refund).

### 4.2 Discount tax behavior

Same class of problem as Refunds: `getAvailableCredit()` always taxes a discount if the customer is taxable, ignoring the `sales_tax_type` flag entirely, while `updateCreditBalance()` and `reverseTransactionEffect()` branch on it (Audit §4.2). If a discount is created with `sales_tax_type = 'free'` today, the displayed available credit and the persisted balance can already disagree. **Needs:** an explicit, written confirmation of intended discount tax treatment per `sales_tax_type` value.

**Items 4.1 and 4.2 together are the Phase 2.0 deliverable itself** (Implementation Plan §Phase 2.0) — the confirmed truth table this phase exists to produce. They block Phase 2.6 (`amountWithTax()`) and, transitively, all of `LedgerBalanceService`. They do not block Phases 2.1-2.5.

### 4.3 Partial payment allocation

The audit's test scenario #2 (Audit §7) calls for confirming that a partial payment against a taxable charge reduces the balance correctly and that a second payment brings it to zero, without re-deriving or re-adding tax on the remaining balance. This has not been separately confirmed as a business rule distinct from the general tax-inclusion question in 4.1/4.2 — **Needs:** confirmation that partial-payment allocation should follow the same rule as full payment (tax portion of the *original* charge, not recomputed against the remaining balance) with no special-casing.

### 4.4 Tax-included vs. tax-added amounts

This is the conceptual root of every bug found so far: a payment's stored `amount` is tax-inclusive, while a charge's stored `amount` is tax-exclusive (tax added separately). This is already established as fact from the code (both the Phase 1 bug and the Dashboard bug are instances of this distinction being applied backwards), not a business question in the same sense as 4.1/4.2 — but business/accounting sign-off should still explicitly confirm this understanding matches their mental model of "amount," since the `TaxCalculationService`'s two core methods are named and built directly around this distinction. **Needs:** confirmation, not a new decision — a chance for the business owner to say "yes, that's how we think about it" before it's encoded permanently as the foundation of the new service.

### 4.5 Invoice paid/open amount rules

The Architecture Report confirmed (not merely suspected) that invoice payments today run inline arithmetic, never the aggregation method (`updateInvoiceSummary()`), for computing `paid_amount`/`open_amount`/`invoice_status` (Architecture Report §1.4). Both paths are believed to currently produce the same numbers, but this has not been proven for every historical invoice, only argued to be likely. **Needs:** business/accounting awareness that Phase 2.4 will validate this equivalence for every existing invoice before switching, and agreement on what should happen procedurally if the validation step (Implementation Plan §Phase 2.4) finds even one invoice where the two methods disagree — is that treated as a bug to fix immediately, or a case to review before proceeding?

### 4.6 Account balance and available credit rules

Two independent computations of "available balance" exist today (stored column vs. live recompute), and `FixRunningBalancesController`'s existence is itself evidence they have already drifted apart in production before (Audit §4.4). **Needs:** business/accounting confirmation of which computation is authoritative when they disagree during the transition, and sign-off that a full ledger-replay validation pass (via `FixRunningBalancesController`) against production data will be run and reviewed before `LedgerBalanceService` governs any real customer's balance.

### 4.7 Dashboard revenue/tax reporting rules

The confirmed Dashboard bug (§2.2) requires its own explicit approval to fix, separate from the architecture: correcting the formula will change dashboard revenue and tax figures for **past, already-reported periods** if historical data is recalculated, not just going forward. **Needs:** (a) approval to fix the formula at all, (b) a decision on whether historical dashboard figures should be recalculated retroactively or only corrected prospectively, and (c) a communication plan for whoever currently relies on the (currently incorrect) dashboard numbers, so the change isn't a surprise.

---

## 5. Technical Sign-Off Items

These require developer/technical-lead approval before coding begins on any phase.

### 5.1 Migration order

The Implementation Plan's order (`TaxCalculationService` → `FinancialReportingAdapter` → `InvoiceCalculationService` → `LedgerBalanceService`, with the Dashboard fix isolated as its own step) is driven explicitly by blast radius: the service with zero existing callers goes first, the service with 26 call sites goes last. **Needs:** technical sign-off that this order will be followed as sequenced, not reordered for convenience (e.g. tackling `LedgerBalanceService` early because it "matters most") — the order is a risk-management decision, not an arbitrary one.

### 5.2 Testing requirements

Three tiers are specified in the Master Roadmap (§9): unit tests for each service in isolation, reconciliation tests (before/after snapshot diff against real data) for every write-path migration, and regression tests asserting that previously-divergent call sites continue to agree going forward. **Needs:** technical sign-off that no phase merges without all three tiers present where applicable — unit tests alone are explicitly called out across the Implementation Plan as insufficient for the write-path phases.

### 5.3 Rollback strategy

Every phase in the Implementation Plan keeps the legacy method callable as a thin wrapper until every caller is confirmed migrated, specifically so any individual phase can be reverted independently of phases that come after it (Implementation Plan, per-phase "Rollback" sections; Master Roadmap §8). **Needs:** technical sign-off that no phase deletes a legacy method until this condition is met — the temptation to "clean up" early should be explicitly named and rejected here, not left implicit.

### 5.4 Code ownership

Not yet assigned in any prior document. **Needs:** a named owner (or small group) responsible for the four new services long-term, and a named reviewer for each phase's validation evidence (the before/after diffs, not just the code diff) — since several phases' safety depends on someone actually re-running and inspecting a reconciliation snapshot, not just approving a PR that claims one was run.

### 5.5 Validation tools

`FixRunningBalancesController` already exists and already performs exactly the ledger-replay-and-diff validation the highest-risk phases (2.6-2.8) depend on (Architecture Report §1.3, Implementation Plan Phases 2.7-2.8). **Needs:** confirmation that this tool will be run against a **copy of production data**, not just staging/seeded test data, before each `LedgerBalanceService` sub-phase — the tool's value is specifically in catching drift against real historical data shapes, which synthetic test fixtures cannot fully represent.

### 5.6 Expected PR size and sequencing

The Implementation Plan explicitly rejects large PRs for the highest-risk service: Phase 2.7 alone is split into five module-scoped sub-phases (2.7.a-e) specifically so a regression is isolated to one module's PR rather than forcing a revert of all 26 migrated call sites at once. **Needs:** technical sign-off that this sub-phase granularity is followed as specified — a reviewer should reject a PR that attempts to combine two sub-phases (e.g. "CRM and Invoice controllers in one PR") purely for convenience, since it defeats the isolation the plan was designed to provide.

---

## 6. Phase 2.1 Recommendation

**Recommended first implementation step after sign-off: build `TaxCalculationService` with only its two already-proven formulas (`extractTaxFromInclusiveAmount`, `addTaxToExclusiveAmount`), with zero production callers, per Implementation Plan Phase 2.1.**

This is the safest possible starting point for four independent reasons:

1. **Zero existing behavior depends on it.** No controller, view, or report calls this class yet, so its creation cannot change any output anywhere in the system. It is purely additive.
2. **It requires no business sign-off to start.** Unlike the unified `amountWithTax()` method (which needs Phase 2.0's Refund/Discount truth table), the two formulas being implemented here are already proven correct and already used, verbatim, in multiple places in the codebase today (`SalesReportEngineV2`'s division formula, `_additional_charges.blade.php`'s multiplication formula) — this phase copies existing, working logic into one place rather than deciding anything new.
3. **It is fully and cheaply testable in isolation.** Unit tests against the original $150.00/$136.67/$13.33 example and a small edge-case table (zero rate, zero amount, small-amount rounding) are sufficient to validate this phase completely — no staging environment, no production data copy, and no controller changes are needed to prove it correct.
4. **It creates immediate value without touching financial behavior.** Once built, it unblocks Phase 2.2 (migrating the three already-correct reporting engines) and, later, the isolated Dashboard bug fix in Phase 2.3 — both of which depend on this service existing, but neither of which needs to happen in the same PR.

**What should not be built yet, even though it lives in the same service class conceptually:** the unified `amountWithTax()` method. Recommend explicitly scoping the first PR to exclude it, so the "safe to build now" and "blocked on business sign-off" pieces of `TaxCalculationService` are not accidentally merged into one commit that then has to wait on Phase 2.0 as a whole.

---

## 7. No-Code Confirmation

**This Phase 2.0 readiness review made no application code changes.**

Verified via `git status` immediately before this document was written: the four source documents reviewed (`CRM_BILLING_PAYMENT_AUDIT.md`, `PHASE_2_ARCHITECTURE_REPORT.md`, `PHASE_2_IMPLEMENTATION_PLAN.md`, `FINANCIAL_ENGINE_MASTER_ROADMAP.md`) are unchanged since they were last written. No controllers, models, helpers, Blade views, routes, migrations, jobs, commands, tests, or database schema were created, modified, or deleted as part of producing this document. The only artifact produced by this task is this file itself:

`docs/financial-engine-consolidation/PHASE_2_0_SIGNOFF_READINESS.md`

No service classes (`TaxCalculationService`, `LedgerBalanceService`, `InvoiceCalculationService`, `FinancialReportingAdapter`) were created. The confirmed Dashboard tax-formula bug (§2.2) was **not** fixed as part of this task, per instruction. This document is a readiness checkpoint only — implementation of Phase 2.1 should not begin until the business items in §4 and technical items in §5 have been explicitly signed off by their respective owners.
