# Financial Engine — Decision Log

This is a permanent record of business-approved financial policy decisions governing the Financial Engine initiative. Unlike the phase completion reports (which document what was *built*), this document records what was *decided* — the durable rules that future implementation work must conform to. Add a new entry (FD-002, FD-003, ...) whenever a new financial policy decision is approved; never edit a past entry's Decision or Rationale after approval — if a decision is later reversed or superseded, add a new entry that references and supersedes it, so the history stays intact.

---

## FD-001: Transaction/Line-Level Sales Tax is the Financial Engine Source of Truth

**Decision date:** 2026-07-01
**Approval status:** Approved (business decision, communicated at the start of Phase 2.3)
**Recorded during:** Phase 2.3 (Dashboard Financial Consistency)

### Decision

Transaction/line-level sales tax is the single source of truth for sales tax across Kabba. For every taxable transaction, the system shall:

1. Determine taxability (is this transaction/line taxable, and at what rate).
2. Apply the correct tax rate.
3. Calculate the tax amount at the transaction/line level, at the point the transaction is created.
4. Round according to Financial Engine policy.
5. Store the resulting tax amount.

Reports must consume these already-calculated Financial Engine transaction values. **Reports must not independently recalculate tax from aggregated totals.** Aggregation (summing, grouping, reporting) is a downstream concern; tax calculation is not something a report is permitted to re-derive on its own.

As a direct consequence of this decision: `SalesTaxReportEngine` is **officially deferred**. It performs its own independent tax-recalculation logic (as documented in `PHASE_2_2_COMPLETION_REPORT.md`) rather than consuming transaction-level values, which is exactly the pattern this decision prohibits going forward. It will remain unchanged, as-is, until a future "Sales Tax Report V2" project is scoped and approved to bring it into compliance with this decision. No phase of the current Financial Engine Consolidation initiative may modify `SalesTaxReportEngine` unless that future project explicitly supersedes this deferral.

### Business Rationale

The confirmed bugs found during this initiative — the Billing Summary display bug (Phase 1) and the Dashboard revenue/tax formula bug (Phase 2.3) — share a common root cause: a downstream consumer (a display column, a dashboard aggregation) re-derived a tax calculation independently instead of reading an authoritative, already-correct value. Every time tax logic is re-implemented at a new consumption point, it is an opportunity for that implementation to disagree with every other implementation — which is exactly what happened, twice, in two unrelated parts of the codebase, using two different wrong formulas. Anchoring tax calculation to the transaction/line level, once, at creation time, removes the opportunity for this class of bug to recur anywhere downstream, because there is nothing left downstream to get wrong — only a value to read.

### Architectural Impact

- **`TaxCalculationService`** (Phase 2.1) is the mechanism by which transaction/line-level tax calculation is performed, and is confirmed by this decision as the intended long-term home for that logic — not merely one option among several.
- **Reporting engines** (`SalesReportEngineV2`, `PaymentReconciliationLedger`, and eventually a future Sales Tax Report V2) are expected to read already-calculated values or use the Financial Engine's shared, centralized formula (as `SalesReportEngineV2` now does via `TaxCalculationService::extractTaxFromInclusiveAmountSql()`, per Phase 2.2) rather than deriving tax figures from raw aggregated totals using their own logic.
- **`SalesTaxReportEngine`** is explicitly carved out of this requirement *for now*, by business decision, not by architectural necessity — it is a known, documented exception with an explicit future remediation path (Sales Tax Report V2), not a silently-accepted gap.
- This decision retroactively confirms that the Phase 2.2 choice to defer `SalesTaxReportEngine`'s migration (rather than force an unsafe fix) was the correct call — the engine's fundamental approach (recalculating tax from aggregated, summed values) is now understood to be a policy violation to be addressed by a future dedicated project, not a bug to patch incrementally within the current initiative.

### Future Implementation Guidance

- Any new financial reporting feature must consume transaction/line-level tax values already calculated and stored by the Financial Engine at transaction creation time. It must not compute its own tax figure from a subtotal, a rate, and an aggregated amount.
- Any new transaction type (a new charge type, a new payment method, a future integration) must determine taxability, rate, and tax amount at the point of transaction creation, via the Financial Engine, and store the result — not defer tax calculation to a later reporting or display step.
- When Sales Tax Report V2 is eventually scoped, its design should start from this decision: read transaction/line-level tax values already calculated by the Financial Engine, rather than replicating `SalesTaxReportEngine`'s current independent-recalculation approach in a new form.
- Aggregation-context needs (e.g., summing many transactions' base amounts before rounding once for a report or dashboard total, as encountered in Phase 2.3) do not conflict with this decision — the *tax amount itself* must still originate from the transaction/line level; only the *arithmetic of combining already-calculated values* (sum, then round) is a downstream, reporting-layer concern. See the `extractBaseFromInclusiveAmountRaw()` vs. `extractTaxFromInclusiveAmount()` distinction in `TaxCalculationService` (`PHASE_2_3_COMPLETION_REPORT.md`) for the precedent this creates: one shared source formula, exposed in both a rounded (single-transaction) and unrounded (aggregation) form, rather than two independently-maintained formulas.

### Update — Phase 2.3A (2026-07-01): Calculation Pattern clarification

This update does not reverse or alter the Decision, Business Rationale, or Architectural Impact recorded above — it clarifies how the decision applies now that a second, independent instance of the aggregation-order question (beyond `SalesTaxReportEngine`, this time in `Dashboard\IndexController`) has been found and resolved. Full detail: `PHASE_2_3A_CALCULATION_PATTERN_ARCHITECTURE.md`.

The original decision text above is amended to explicitly include the following four points, which were implicit in the original wording but are now stated directly to prevent future ambiguity:

1. **Transaction-level tax remains the source of truth.** Unchanged from the original decision — restated here for completeness, not as a new rule.
2. **Analytics/reporting may use high-precision intermediate calculations when aggregating many records.** "Reports must not independently recalculate tax" (original wording, above) means reports must not invent a *different tax rule or formula* — it does not forbid deferring rounding during aggregation of the *same* underlying formula. Using the same formula unrounded, purely so that summing many rows and rounding once matches the database's own `SUM()` behavior, is compliant. Using a materially different formula (as the Dashboard bug did, and as `SalesTaxReportEngine` is deferred for doing in its own way) is not.
3. **Analytics calculations must reconcile back to transaction-level source data.** Any dashboard, KPI, or report figure must be traceable to, and consistent with, the sum of the underlying transactions' recorded values. This is the concrete, testable form of "reports must consume Financial Engine transaction values" — Phase 2.2 and Phase 2.3 both demonstrated this via exhaustive numerical equivalence proofs, not assertion.
4. **Analytics calculations must never write financial transactions.** An analytics/reporting code path is read-only with respect to the ledger and invoices. If a future feature needs to create a transaction as a side effect of a reporting action, that creation must go through a transaction-level calculation and write path, not be folded into the analytics calculation itself.

This amendment formally names two calculation families — **Financial Transaction Calculations** and **Financial Analytics Calculations** — governed by the rules in `PHASE_2_3A_CALCULATION_PATTERN_ARCHITECTURE.md`. `TaxCalculationService`'s methods are categorized against this framework in that document's §6 and in the method-level PHPDoc in `app/Services/TaxCalculationService.php`. Future services (starting with `InvoiceCalculationService`, Phase 2.4) are expected to be built with this categorization in mind from the start, rather than discovering it after the fact as Phases 2.2 and 2.3 did.

### Related document — Phase 2.5B (2026-07-02): `FINANCIAL_TRUTH_TABLE.md`

This is a pointer, not a new decision — no new business policy was approved in Phase 2.5B. `docs/financial-engine-consolidation/FINANCIAL_TRUTH_TABLE.md` is now the detailed, per-transaction-type reference implementing FD-001's principles (transaction/line-level tax as source of truth; the Financial Transaction vs. Financial Analytics distinction) across all 16 transaction types found in the codebase. It records, per type, which tax-treatment rules are already Approved (consistent with FD-001 and this decision's existing scope) and which remain Pending Business Decision, Undefined, or Future Enhancement. **When any of those pending items is actually resolved by the business, record the resolution as a new FD entry here (FD-002 or later) and then update the Truth Table's corresponding row and Business Decision Status column — the Truth Table must never be the origin of a new policy, only its recorded implementation.**

---

# ⚠ DRAFT — NOT APPROVED — Goodwill production re-baseline (2026-08-02)

Everything below this line is **draft**. None of it is approved policy. It exists so the decisions the re-baseline requires are written down before any code is ported.

Supporting audit: `GOODWILL_PRODUCTION_REBASELINE_REPORT.md`.

## Context: FD-002 does not exist in this repository

FD-002 (*Goodwill Adjustment is a Pre-Tax Reduction of an Order's Taxable Basis*) and its Amendments 1–7 were authored against `Kabba24ai/kabba2_AI`, whose `raj_development` had not advanced since 2026-07-22. This repository — `RajChotaliya/Kaaba2`, the true production source — is 169 commits ahead of that point and its `FINANCIAL_DECISIONS.md` ends at FD-001.

**FD-002 must be ported here as part of the re-baseline, not assumed to apply.** Several of its recorded statements are already false against this codebase; those are enumerated below and must be superseded *at the time FD-002 is ported*, not silently carried over.

## D-A (draft) — Store Credit is a pre-tax product discount, superseding FD-002's scope note

FD-002 records *"Store Credit remains post-tax tender, out of scope by decision."* **That is no longer true.** Commit `dd369166` reframed Store Credit as a pre-tax product discount, and commit `2026_07_27_090000` added `orders.pretax_discount_total`, `tax_amount_before_discount`, `grand_total_before_discount`.

A reusable pre-tax adjustment abstraction now exists at `app/Services/Discounts/` (`DiscountTarget` contract, `DiscountApplicationService`, `DiscountCalculator`, `OrderDiscountTarget`).

**Open decision:** does Goodwill become a target/type on that abstraction, or remain a separate domain adopting the same persistence model? Two independent pre-tax engines is not an acceptable outcome.

## D-B (draft) — Whether a pre-tax concession reduces line revenue

The two implementations disagree:

- `OrderDiscountTarget` leaves `orders.subtotal` and `order_products.sub_total` **untouched**, accruing the reduction in `pretax_discount_total`, explicitly "so gross sales reporting is unaffected".
- FD-002's Goodwill **reduces** both, so `ProductSalesPerformanceEngine` reports reduced product revenue.

`ProductSalesPerformanceEngine` documents *"Revenue source: `order_products.sub_total` (discounts are priced-in)"* — which is **not currently true** for Store Credit, since the discount layer never writes that column. This inconsistency is pre-existing. Whatever is decided must apply to **both** features, not one.

## D-C (draft) — Receipt model: mutate or supersede

`ReceiptService::getOrCreateReceipt()` now **rewrites** an existing receipt's `subtotal`/`sales_tax`/`total` whenever they drift from the order. FD-002 Amendment 6 instead specifies **append-only supersession**, on the grounds that a receipt is a document handed to a customer and rewriting it destroys the evidence of what they were originally told.

Both fix staleness. They are mutually exclusive. The receipt table currently has no supersession columns.

## D-D (draft) — Reconciliation identity must include `pretax_discount_total`

FD-002 Amendment 3's identity —

```
subtotal + ordinary tax + special tax + added fees − discount = grand_total
```

— **fails on any order carrying a Store Credit discount**, because `pretax_discount_total` sits outside `discount_amount` while `tax_amount` and `grand_total` have already been reduced. `HistoricalTaxBasisResolver` would return `UnreconciledGrandTotal` and refuse, breaking refunds on exactly the orders Store Credit touches. The identity must be restated before the resolver is ported.

## D-E (draft) — Reporting is cash-basis; the frozen-engine verdict must be re-proved

FD-002 records that `SalesTaxReportEngine` requires no changes. That was proved against the accrual-basis engine of 2026-07-22. The engine now anchors on `COALESCE(op.payment_datetime, op.created_at)` (`7fb51595`). It also does not read `pretax_discount_total`. **The verdict is void until re-proved.**

## D-F (draft) — Blended-rate correctness in `OrderDiscountTarget`

`OrderDiscountTarget::taxRate()` uses `tax_amount ÷ subtotal` as a blended effective rate. This is arithmetically **correct** when the reduction is proportional across taxable and exempt lines in their original ratio, and **incorrect** otherwise.

Whether product-targeted discounts can violate that proportionality is **unverified**. If they can, production carries a live sales-tax defect independent of Goodwill, and it should be raised on its own merits rather than bundled into this release.

## Unchanged and still applicable

These FD-002 findings were re-verified against `d8591af5` and remain true:

1. `PaymentAllocationService::proportionalTaxRefund()` still derives its rate from `tax_amount / subtotal` — the defect FD-002 Amendment 1 exists to correct. It is now compounded, since a discounted order has a reduced `tax_amount` over an undiscounted `subtotal`.
2. ~~Special tax and added fees still have **no columns** and survive only in `order_products.product_data`.~~ **Superseded by Release 1 (2026-08-02).** `orders.special_tax_amount`, `orders.added_fees_amount`, `order_products.special_tax` and `order_products.added_fees` now exist and are maintained by the engine; `product_data` is the backfill source, no longer the only record.
3. `Gate::before(fn ($user, string $ability) => true)` is still registered in `AppServiceProvider`. Direct Spatie enforcement remains necessary for any authority separation.
4. `ModuleSeeder` is still a destructive reconciliation seeder and must not be run in production.
5. Extension children still own no order lines and still carry an `extensionCharge()` relation.

---

## Re-baseline decisions A / B / C (2026-08-02) — DRAFT, approved in principle

Issued after the production re-baseline audit. These resolve the open questions D-A, D-B and D-C above. Full design: `SHARED_PRETAX_ADJUSTMENT_DESIGN.md`.

**Governing principle:** *Gross merchandise values remain stable; discounts are explicit; tax and net revenue follow the allocated discounts.*

### A — One shared pre-tax engine

Goodwill uses `app/Services/Discounts/` and does **not** maintain a parallel pricing engine. `order_goodwill_adjustments` is retained as the Goodwill-specific authorization, audit, idempotency, reversal and payment-linkage domain.

| Layer | Responsibility |
|---|---|
| Discounts | calculate and persist the financial adjustment |
| Goodwill | authorize, invoke, snapshot, reverse, audit |

Goodwill is **not** integrated into the current float/blended-rate implementation unchanged. The shared engine is first hardened for integer-cent reconciliation, mixed taxable/exempt merchandise, separately-derived ordinary and special tax, and protected fees.

`DiscountType::Goodwill` already exists in production, marked deferred and gated by `isOperationalInPhase1()`.

### B — Gross line values are preserved

`orders.subtotal` and `order_products.sub_total` are **gross merchandise values and are never reduced.** This supersedes FD-002's line-reducing model.

The concession is stored explicitly in `orders.pretax_discount_total` and in a per-line allocation (`order_products.pretax_discount_allocated` plus `order_product_discount_allocations` for per-adjustment reversal).

**Product net revenue = gross line subtotal − allocated pre-tax discounts.** `ProductSalesPerformanceEngine` and its documentation are corrected accordingly; its current claim that discounts are "priced-in" to `sub_total` is **false** and must not be repeated. The same reporting rule governs Store Credit and Goodwill alike.

### C — Receipt lifecycle

A receipt that has **not been issued** may refresh in place. Once **issued**, it is immutable: any later Goodwill adjustment or reversal creates a **superseding** receipt linked to the prior one. Previously issued receipt and receipt-item rows are never overwritten.

Goodwill retains the append-only **original → adjusted → restored** chain, adapted to the production receipt model. This requires an explicit `issued_at` marker — the existing `is_email_status` / `mail_send_at` cover email only, not download or print.

### Audit outcome — one live defect found

Executed against `OrderDiscountTarget` on `d8591af5`:

| Scenario | Verdict |
|---|---|
| Mixed taxable / tax-free | **Correct.** The blended rate is exact under whole-population proportional reduction — verified numerically. It is *not* the defect it was suspected of being |
| Product- or line-targeted discounts | Not reachable today; no such target exists |
| **Special-tax lines** | ❌ **LIVE DEFECT** |
| Added fees | Correctly preserved, but indistinguishable from special tax in the lumped residual |
| Stacking | Correct — recompute-from-snapshot is exact |
| Rounding | Float with `round(…, 2)`; no exact-reconciliation guarantee |

**The defect:** `OrderDiscountTarget::recompute()` preserves `otherComponents = grand_total_before − subtotal − tax_before` intact. Special tax is basis-derived, so when the basis shrinks it must shrink too. It does not.

Proven: $200 merchandise, $19.50 ordinary tax, $4.00 special tax, $50 discount → special tax stays $4.00 where it should be $3.00. **$1.00 over-collected.** This affects every Store Credit discount on an order carrying special tax, **today**, and is independent of Goodwill.

It cannot be fixed without the special-tax/added-fee columns, because the lumped residual cannot distinguish special tax from added fees. Those columns therefore become a **prerequisite for a production defect fix**, not merely a Goodwill enabler.

**Guard:** the blended rate stays correct only while every adjustment reduces the whole merchandise population proportionally. Introducing per-line allocation makes line-scoped adjustment possible, so the rate must be replaced by per-line derivation from each line's own frozen tax posture at the same time.

---

# Goodwill decisions on the deployed engine (2026-08-02, approved 2026-08-03)

Issued after the post-deployment re-audit of `afa84552` and approved with
Increment G1. Full design: `GOODWILL_ADJUSTMENT_DESIGN.md`.

**The governing statement: Goodwill is a workflow layered on `ProductDiscount`,
not a financial engine.** It records who authorized a concession, why, and
against which collected payment. Every amount — concession, ordinary tax,
special tax, added fees, line allocations, net product revenue, receipt
components — is owned by `app/Services/Discounts/` and reached through the
linked `ProductDiscount`. The earlier standalone Goodwill calculation engine is
discarded, not ported.

## G-1 (approved) — Goodwill is a policy layer, never a calculation engine

Goodwill owns authorization, reason, payment linkage, idempotency, guards,
snapshots and audit history. It owns **no** financial arithmetic. Ordinary tax,
special tax, added fees, line allocations, net product revenue and the receipt
snapshot are computed exclusively by `app/Services/Discounts/`.

`order_goodwill_adjustments` duplicates no figure held by `product_discounts`
or `order_product_discount_allocations`. It records the **order-level**
before/after position — special tax, fees, grand total, payment position — which
no existing table holds.

## G-2 (approved) — The concession is sized by asking the engine, not by formula

The required concession is not `balance_due`: reducing the pre-tax basis also
reduces both basis-derived taxes. Goodwill seeds an estimate, then evaluates
candidates through `OrderDiscountTarget::previewTotals()` — the same function
the writer uses — and selects the smallest concession whose revised grand total
does not exceed cumulative settled payments.

**Residual (approved).** Because the grand total steps by more than one cent at
a tax-rounding boundary, an exact close is unreachable in roughly one case in
eight. The remainder is computed by the shared engine, stored explicitly in
`rounding_residual`, immutable once written, and visible in the audit history.
It is **never** absorbed into the concession amount.

`abs(rounding_residual) ≤ $0.02`. Anything larger **refuses the operation**: the
grand total falls by at most three cents per cent of concession, so a larger gap
means the concession was sized against different figures than the ones it is
being written with. Enforced by the model with a readable message and again by a
`CHECK` constraint a raw insert cannot bypass.

## G-3 (approved) — Authority is enforced through Spatie directly

`Gate::before(fn () => true)` makes `can()`, `@can` **and Spatie's own
`permission:` route middleware** non-refusing — the vendor middleware calls
`canAny()`, which routes through the Gate. Goodwill therefore calls
`hasPermissionTo()`, which does not.

The authorizing manager is recorded separately from the acting operator, and is
validated to hold the permission. Authority to receive a payment does not imply
authority to reduce revenue.

Permissions are created by a dedicated **additive** seeder that deletes nothing
and grants to Master Admin. `ModuleSeeder` is never run in production; its
declarative list is nonetheless updated in the same commit, because it deletes
permissions absent from that list **and** every permission with a null
`module_id`.

## G-4 (approved) — Decision C (receipt supersession) is deferred, not satisfied

`receipts.issued_at` was never added, and Release 1 ships in-place refresh.
Goodwill uses the deployed receipt model. Decision C is recorded as deferred so
it is not mistaken for delivered: an already-printed receipt will restate
itself, and a stacked order's receipt cannot attribute the concession per type.

## G-5 (approved) — Invoiced orders are guarded by Goodwill only

`OrderDiscountTarget::ineligibleReason()` checks A/R posting and remaining
balance, **not `orders.invoice_id`**. A Store Credit discount can therefore be
applied to an invoiced order today, desyncing the invoice. Pre-existing;
Goodwill enforces the guard in its own policy layer and the Store Credit
exposure is logged separately rather than fixed by widening this work.

## G-6 (approved) — Orders with no lines are refused

The allocator has no population to distribute across, `reconcile()` deliberately
skips such orders, and line propagation is a no-op. Rather than write an
unattributable concession, Goodwill refuses. Extension children are the known
shape this excludes.

## G-7 (approved) — Goodwill reasons carry a first-class reporting category

A concession granted because Kabba got something wrong is a **cost of failure**.
One granted to win or keep business is a **cost of sale**. They are identical in
the accounts — the same dollars leave through the same mechanism — and they mean
opposite things. Aggregated together they answer neither question.

Every reason therefore belongs to exactly one category:

| Category | Codes |
|---|---|
| **Service Recovery** | `SERVICE_FAILURE`, `EQUIPMENT_ISSUE`, `DAMAGED_PRODUCT`, `BILLING_ERROR`, `DELIVERY_PICKUP_ISSUE` |
| **Business Courtesy** | `REPEAT_CUSTOMER`, `CUSTOMER_LOYALTY`, `CUSTOMER_RETENTION`, `MULTIPLE_ITEMS`, `LARGE_ORDER`, `PRICE_MATCH`, `PROMOTIONAL_COURTESY`, `MANAGER_COURTESY` |
| **Other** | `OTHER` — mandatory written note |

**Codes are stable; labels are not.** The persisted value is an uppercase code.
`label()` is presentation and may be reworded freely. A report grouped on a
display string silently re-groups the day the copy deck changes, splitting a
historical series in two with nothing to indicate it happened.

`GoodwillReason::category()` is the single authoritative mapping.
`order_goodwill_adjustments.reason_category` is denormalised from it at write
time — derived by the model, never supplied by a caller — so the dimension is a
plain indexed `GROUP BY` and no consumer has to re-implement the mapping or
parse a label. Future reports answer "Service Recovery Goodwill" and "Business
Courtesy Goodwill" without hard-coded grouping.

## G-8 (approved) — The Goodwill record is an audit record, not an editable row

Once written, the decision is immutable: reason, note, approver, approval time,
accepted payment total, rounding residual, both snapshots, the idempotency key,
and the order and discount links can never be modified. Only the reversal fields
remain writable, because they are the one part of the story still unwritten when
the row is first persisted.

An audit record that can be edited afterwards documents the last edit, not the
decision. Enforced on the model, so no service or future caller can route around
it. A concession that turns out to be wrong is **reversed and re-applied**,
leaving both events in the history — never overwritten.
