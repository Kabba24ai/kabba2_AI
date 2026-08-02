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

### Related document — Phase 2.5B (2026-07-02): `FINANCIAL_TRUTH_TABLE.md` (FD-001)

This is a pointer, not a new decision — no new business policy was approved in Phase 2.5B. `docs/financial-engine-consolidation/FINANCIAL_TRUTH_TABLE.md` is now the detailed, per-transaction-type reference implementing FD-001's principles (transaction/line-level tax as source of truth; the Financial Transaction vs. Financial Analytics distinction) across all 16 transaction types found in the codebase. It records, per type, which tax-treatment rules are already Approved (consistent with FD-001 and this decision's existing scope) and which remain Pending Business Decision, Undefined, or Future Enhancement. **When any of those pending items is actually resolved by the business, record the resolution as a new FD entry here (FD-002 or later) and then update the Truth Table's corresponding row and Business Decision Status column — the Truth Table must never be the origin of a new policy, only its recorded implementation.**

---

## FD-002: Goodwill Adjustment is a Pre-Tax Reduction of an Order's Taxable Basis

**Decision date:** *(pending)*
**Approval status:** **DRAFT — NOT YET APPROVED.** Awaiting business sign-off. No application code may be written against this entry until this line reads Approved and a decision date is recorded.
**Recorded during:** Goodwill Adjustment design (2026-08-01)

### Decision

A **Goodwill Adjustment** is a discretionary, manager-authorized, **pre-tax reduction of an order's taxable product basis**, applied so that the order's revised grand total equals the cumulative settled payments the business has agreed to accept as payment in full.

The following rules are approved as a unit:

1. **Goodwill reduces the taxable basis before tax is computed.** Sales tax is then recalculated on the reduced basis. Goodwill is never applied against a post-tax balance.
2. **The accepted inclusive amount is cumulative settled payments**, as defined by `OrderPayment::scopeSettled()` — not the newest payment alone. Failed, Voided, Superseded, and other non-settled rows are excluded by that scope, not by any new rule introduced here.
3. **Goodwill is not tender.** It never creates an `order_payments` row, never appears in `OrderPaymentMethod`, and never appears in any payment-method report, collection total, or cash-basis figure. The money actually received is recorded through the real tender the customer used.
4. **Goodwill is not a refund, not Store Credit, and not a `customer_accounts` discount.** It creates no `customer_accounts` row of any type.
5. **The order's canonical stored totals become the revised values.** `orders.subtotal`, `orders.tax_amount`, `orders.grand_total`, and the affected `order_products.price`/`tax`/`total` rows are updated in place, because those columns are what every downstream report and receipt reads. The pre-adjustment values are preserved in a dedicated side record, which is the audit history; the order columns are the current authoritative state.
6. **Tax is recalculated only by `TaxCalculationService`.** No new tax formula, and no parallel calculator, may be introduced for Goodwill. The authorized primitive is `extractTaxFromInclusiveAmount()`, which is already classified TRANSACTION-SAFE.
7. **Paid status is only ever reached through canonical status logic** (`Order::getIsPaidAttribute()` / `scopeSettled()`). No code path may assign a paid status directly as a consequence of applying Goodwill.
8. **Goodwill is reversible, never deletable.** A reversal restores the prior canonical totals and records its own actor, timestamp, and reason. Neither the original adjustment record nor the customer's real payments are removed or edited.

### Monetary Precision Rule

Goodwill introduces **no new floating-point calculation logic**. Monetary values entering and leaving the Goodwill domain are normalized to **integer cents**. `TaxCalculationService` is the authorized calculation boundary even though its current internal contract uses `float`: cents are converted to that representation only for the duration of the call, and the returned base and tax are immediately normalized back to integer cents, after which `base + tax == target inclusive amount` is asserted **exactly, in cents**. If that assertion fails, the operation aborts and nothing is written.

Creating a parallel tax calculator merely to avoid the shared service's float interface is explicitly prohibited — it would reproduce precisely the duplicate-formula failure mode FD-001 exists to prevent.

**Recorded as technical debt, not addressed here:** modernizing `TaxCalculationService` to a decimal or minor-unit contract. That is a separate, independently-scoped project. Until it happens, the float boundary is a known, contained, asserted-at-the-edge exception rather than an accepted precision risk.

### Relationship to Existing Pending Decisions

This decision **does not resolve, depend on, or prejudge** any open item in `FINANCIAL_TRUTH_TABLE.md` §5 — specifically Refund tax treatment (§5.1), Discount tax treatment (§5.2), or whether Write-Off should reduce a recorded balance (§5.9).

It is able to avoid them because a Goodwill Adjustment is **order-scoped**: it writes to `orders`, `order_products`, and its own side table, and never creates a `customer_accounts` row. The four divergent `CustomHelper` balance methods, and the `sales_tax_type` branching whose correct behavior is still undecided, are therefore never reached. This containment is a deliberate design constraint of FD-002, not an incidental property — if a future change gives Goodwill a `customer_accounts` effect, those pending decisions become blocking and this entry must be superseded.

### Business Rationale

The business already accepts less than the full amount due in practice; today that leaves the order permanently partially paid, with the shortfall invisible as anything other than an unexplained gap between `grand_total` and payments. That has two consequences worth correcting: revenue and taxable sales are overstated for orders that were never going to collect in full, and there is no record of who authorized the concession or why.

Treating the waived amount as tender would be the simpler implementation and is rejected deliberately. Fake tender would inflate collections, corrupt payment-method reporting, and — most seriously — leave sales tax calculated on revenue the business never received, overstating tax liability. A pre-tax basis reduction is the only treatment under which the tax figure remains correct.

### Architectural Impact

- **`TaxCalculationService`** is confirmed as the sole tax authority for this transaction type, consistent with FD-001.
- **`SalesTaxReportEngine` requires no modification and must remain frozen.** Its Stream A reads `orders.tax_amount` and allocates it across settled payments using integer-cent arithmetic with the remainder assigned to the final row. Because FD-002 updates `orders.tax_amount` as a canonical stored value, that engine reports Goodwill-adjusted figures with no code change. This was verified by executing the engine's own allocation arithmetic against both the single-payment and split-payment cases; both reconcile exactly. FD-001's deferral of that engine is therefore untouched by this decision.
- **Reporting engines generally** continue to consume stored transaction values. No report is permitted to re-derive a pre-Goodwill figure.
- **A new permission** (`goodwill.apply`, `goodwill.reverse`) is required, following the established dot-notation convention used by `customer_credit.grant` / `customer_credit.redeem`. Authority to receive a payment does not confer authority to reduce revenue.

### Future Implementation Guidance

- Any future pre-tax adjustment type (promotional credit, loyalty adjustment) should follow this entry's shape: order-scoped, canonical-totals-updated, side-record-audited, `TaxCalculationService`-calculated, never tender.
- Should Store Credit ever be migrated from its current post-tax tender treatment to a pre-tax basis reduction, that migration requires its own FD entry. FD-002 deliberately does not authorize it and does not change Store Credit's behavior in any way.

### Amendment 1 (2026-08-01) — Historical taxable basis, special tax, and the shared resolver

Approved together with FD-002. Added after the pre-implementation audit found that the original text left the tax-rate denominator undefined and was silent on the existence of a second tax bucket. Both gaps are closed here; nothing above is reversed.

**1. The historical effective tax rate may only be derived from the exact taxable basis that originally generated `orders.tax_amount`.**

Audit finding: **`orders.subtotal` is NOT that basis.** `CartHelper::buildCartItem()` computes a line's tax as `($taxExempt || $product->is_tax_free_item) ? 0 : $itemSubTotal * $taxRate`, while `orders.subtotal` accumulates **every** line's `sub_total` including `is_tax_free_item` products. Dividing `tax_amount` by `subtotal` on a mixed order therefore yields a plausible-looking but wrong rate.

**The authoritative denominator is the *ordinary taxable basis*:**

```
ordinaryTaxableBasis = Σ order_products.sub_total  WHERE that line carried ordinary sales tax
```

reconciled against `orders.tax_amount`. `orders.subtotal` may never be used as the denominator, by Goodwill or by anything else.

**2. Special tax is recomputed proportionally, and never blended into the ordinary rate.**

`special_tax` is a second, independent tax (`$itemSubTotal × special_taxes/100`, gated per-product by `apply_special_tax`) levied at a different rate from ordinary sales tax. Because it is derived from the same basis Goodwill reduces, leaving it unchanged would levy a tax on a basis that no longer exists.

Therefore: when Goodwill reduces the taxable basis, `special_tax` is **recomputed on the reduced basis at its own separately-derived rate**. The two rates are resolved and applied independently and must never be summed into a single blended rate.

**3. One shared historical-tax-basis resolver, used by every consumer.**

The basis reconstruction above is implemented **once**, in a focused financial reconstruction service, and consumed by both the Goodwill path and `PaymentAllocationService`. This is deliberately **not** a generic pre-tax-adjustment abstraction — it reconstructs historical tax basis and nothing else.

This is required because the audit found `PaymentAllocationService` already computes `$originalTaxRate = $order->tax_amount / $order->subtotal` — the identical defective denominator, live in production refund allocation today. Goodwill may not ship while the adjustment path and the refund path derive tax from different bases for the same order. Correcting that call site is in scope for FD-002; broadening the change into unrelated refund allocation behavior is not.

**4. Rejection is mandatory when the basis cannot be reconstructed reliably.**

The feature must never infer a plausible-looking rate from an incorrect denominator. The operation is rejected — never approximated — when any of the following holds:

- Zero taxable basis with a nonzero stored tax.
- Mixed or multiple historical tax rates across lines.
- A manual tax override that cannot be explained from stored values.
- Taxable charges outside the selected basis.
- Existing adjustments that make the denominator ambiguous.
- Corrupt or unreconciled stored totals (summed line tax disagrees with `orders.tax_amount`).
- Components recoverable only from `order_products.product_data` JSON where that blob is absent, malformed, or inconsistent with stored columns.

Where `PaymentAllocationService` has an existing safe fallback, that fallback is used rather than a new rejection path, so refund behavior is not broadened.

**5. Receipts are superseded, never rewritten.**


Audit finding: `ReceiptService` persists a **snapshot** (`receipts.subtotal`/`sales_tax`/`total` plus per-item rows), not a live view. A receipt issued before an adjustment is a historical record. Goodwill therefore marks any existing receipt **superseded** and issues a new one carrying the revised totals. The original receipt row is never edited or deleted, consistent with this initiative's standing rule against rewriting history.

### Amendment 2 (2026-08-01) — Historical tax basis source priority

Approved together with FD-002. Added after implementing Amendment 1 §3 revealed that one legitimate transaction type carries no product lines at all, and that rejecting it outright regressed working behavior.

**Resolver source priority.** `HistoricalTaxBasisResolver` reconstructs a basis from exactly one of three named sources, tried in this order, and records which one it used on every successful result:

| Priority | Source | Evidence |
|---|---|---|
| 1 | `order_product_lines` | The order's own `order_products` rows — per-line frozen basis and tax, reconciled exactly against stored order totals. The normal case and the strongest proof. |
| 2 | `extension_billing_charge` | An extension child's linked `billing_charges` row (`amount`, `tax_amount`, `tax_type`), reconciled exactly against the child order's stored totals. |
| 3 | `extension_order_level` | An extension child's own stored totals. Permitted **only** when no linked charge exists and every extension invariant is proven. |

The selected source is logged on every resolution, `extension_order_level` at warning level, so the weakest evidence stays visible in operations rather than becoming an invisible default.

**Constraints, all binding:**

1. **`extension_order_level` is a named transaction-type exception, not a generic fallback.** It exists solely for extension child orders, which `Extension\StoreController` creates with totals and no `order_products` rows by design.

2. **Its validity rests on an invariant, not on arithmetic.** An extension is one base amount under one `add_tax` flag, so it has exactly one tax posture and no hidden fee, special-tax, discount, or tax-free component. That is the only reason its `subtotal` may serve as the taxable basis. The arithmetic is identical to the defective `tax_amount / subtotal` formula this initiative removed — what makes it sound here is the proven absence of any tax-free component, nothing else.

3. **Any future extension feature that changes those invariants must update or disable this mode.** Permitting multiple lines, a per-line tax posture, a discount, an added fee, or a special tax on an extension invalidates it immediately. The mode's guards (no discount, `subtotal + tax === grand_total` exactly, `is_tax_exempt` agreeing with stored tax) are the tripwires; a change that routes around them must revisit this amendment first.

4. **A contradictory billing charge is a hard failure.** When a linked extension charge exists but disagrees with the child order, resolution rejects outright and **must never fall through to `extension_order_level`**. Contradictory authoritative data is a reason to stop, not to retry with weaker evidence.

5. **Anonymous line-less orders remain unsupported.** An order with no lines that cannot prove the extension relationship is rejected. There is no general line-less path, and none may be added without superseding this amendment.

**Why this is recorded as policy rather than left to implementation.** Reconstructing a historical rate from anything other than per-line data is a financial judgement, not a coding detail. Writing the permitted exceptions down — with the invariant each depends on — is what stops the next such exception being added silently because it looked reasonable at the time. That is the same failure mode FD-001 exists to prevent.

### Amendment 3 (2026-08-01) — Reducible merchandise vs protected components

Approved together with FD-002. Added after implementation revealed that treating "carries no sales tax" as "may not be reduced" made Goodwill impossible on a tax-exempt order — a case FD-002 always intended to support.

**The distinction is merchandise vs charge, not taxed vs untaxed.**

| Bucket | Reducible? | What it is |
|---|---|---|
| **Ordinary taxable merchandise** | Yes | Product lines that generated ordinary sales tax |
| **Reducible untaxed merchandise** | **Yes** | Product lines that are part of the sale but generated no ordinary sales tax because the order, customer, or product is tax-exempt |
| **Protected non-reducible components** | **No** | Flat added fees, charges unrelated to merchandise price, and anything FD-002 explicitly preserves |

Ordinary taxable merchandise and reducible untaxed merchandise together form the **adjustable merchandise basis** — the only thing Goodwill may reduce.

**Rules:**

1. **Tax-exempt merchandise is reducible.** The absence of tax says nothing about whether a line is part of the sale.
2. **A tax-free product is still reducible merchandise** unless its frozen data *proves* it represents a protected fee-type component. Merchandise is the default; only positive evidence moves a line into the protected bucket. (No such evidence can exist today — the frozen `product_type` is only `Rental` or `Retail`, both merchandise. The check exists so the rule is stated in advance rather than improvised later.)
3. **`added_fees` remain unchanged** by any adjustment.
4. **Ordinary tax remains zero when the original posture was exempt.** Reducing an exempt order never creates tax.
5. **Special tax is recalculated only for the basis that was subject to it**, at its own rate, never blended with the ordinary rate.
6. **Current product or store configuration must never be used to reinterpret a historical line.** Classification comes from the frozen line alone.

**Reconciliation identity, superseding the form in Amendment 1:**

```
revised taxable merchandise
  + revised untaxed merchandise
  + revised ordinary tax
  + revised special tax
  + protected non-reducible components
  − discount
  = accepted final total
```

Asserted in integer cents. Failure writes nothing.

**Worked example — fully tax-exempt order:**

| | Amount |
|---|---|
| Original merchandise basis | $200.00 |
| Accepted final total | $185.00 |
| Ordinary sales tax | $0.00 |
| Special tax | $0.00 |
| **Goodwill adjustment** | **$15.00** |
| Revised merchandise basis | $185.00 |
| Revised grand total | $185.00 |

**Allocation.** Goodwill is allocated proportionally across **all reducible merchandise lines**, taxable or untaxed, using the existing largest-remainder rule with ties broken by ascending line id. **No share is ever allocated to a protected component.** Tax is recomputed only on lines that actually carried ordinary tax; an untaxed line stays untaxed however much it is reduced.

### Amendment 4 (2026-08-02) — Special tax and added fees become first-class current columns

Approved after an audit of every consumer. Added because the writer was refusing special-tax orders outright, which contradicted Amendment 1 §5 and Amendment 3 §5 — both of which require special tax to be *recomputed*, not avoided.

**The defect.** Special tax and added fees existed nowhere in the schema. `CartHelper::buildCartItem()` computed them at checkout, folded them into `orders.grand_total`, and persisted them **only** inside the per-line `product_data` JSON. `orders.tax_amount` never contained them.

That left `HistoricalTaxBasisResolver` sourcing basis and ordinary tax from **mutable columns** while sourcing these two from an **immutable snapshot**. Any operation that legitimately moved the columns and the grand total — a Goodwill Adjustment — could not move the JSON, because the JSON is the frozen original by design. The reconciliation identity would then fail permanently, refusing **every subsequent refund** on that order. The refusal was correct; the storage was not.

**The decision:**

| Table | Columns | Role |
|---|---|---|
| `orders` | `special_tax_amount`, `added_fees_amount` | current, mutable, authoritative |
| `order_products` | `special_tax`, `added_fees` | current, mutable, authoritative |
| `order_products.product_data` | unchanged | **immutable original checkout snapshot** |

**Rules:**

1. **Columns are current; JSON is original.** The resolver reads money from columns and consults `product_data` only for what a line *is* (its classification), never for what it currently *costs*.
2. **`product_data` is never written** by the backfill, by an adjustment, or by a reversal. Its byte-identity through apply and reverse is asserted by test.
3. **The order-level aggregate is the sum of its own line columns**, never a separately computed figure that happens to agree.
4. **Checkout writes all three**: line columns, order aggregate, and the frozen snapshot. At creation the column and the snapshot are equal — that equality is what makes the snapshot usable as the original-state record once an adjustment later moves the column away from it.
5. **The backfill never records an unreadable snapshot as a trustworthy zero.** Every order is checked against the residual it must explain — `grand_total − (subtotal + tax_amount − discount_amount)`, which *is* special tax plus added fees by construction. A row is written only when the reconstruction equals that residual exactly, or when both are zero. Anything else keeps the column default and is reported as a named exception.
6. **Added fees remain protected** (Amendment 3 §3). Special tax is now genuinely recomputed at its own rate, per line, with the residual cent falling to the last special-taxed line — the same determinism rule ordinary tax uses, so an exact reversal remains possible.

7. **A line-less order is written as zero only when its residual is also exactly zero.** "No lines" does not independently prove "no special tax or fees" — it only proves there is no line to attribute one to. Extension children are constrained that way today by how `Extension\StoreController` builds them, not by the schema. A line-less order carrying a nonzero residual is reported as its own category, never grouped with the safely-zero ones.

Because reading money from a column is what the resolver already did for basis and tax, this makes it *internally consistent* rather than weaker. The `SpecialTaxUnsupported` refusal is removed.

**Production deployment is gated.** The migration must not run until `diagnostics:special-tax-backfill-audit` reports zero in all three declined categories. See `SPECIAL_TAX_COLUMNS_DEPLOYMENT_PLAN.md`.
