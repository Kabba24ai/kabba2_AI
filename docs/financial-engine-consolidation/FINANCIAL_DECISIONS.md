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
