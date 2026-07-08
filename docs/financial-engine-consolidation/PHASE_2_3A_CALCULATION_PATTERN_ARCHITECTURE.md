# Phase 2.3A — Calculation Pattern Architecture

Document date: 2026-07-01
Branch: `raj_development`
Status: **Architecture/documentation phase. A small, additive, comment-and-test-only code change accompanies this document — see `PHASE_2_3A_COMPLETION_REPORT.md` for full detail.**
Governing documents: `PHASE_2_ARCHITECTURE_REPORT.md`, `PHASE_2_2_COMPLETION_REPORT.md`, `PHASE_2_3_COMPLETION_REPORT.md`, `FINANCIAL_DECISIONS.md`

---

## 1. Executive Summary

This document exists because the same mistake was nearly made twice, for the same underlying reason, in two different files.

Phase 2.2 discovered that `SalesTaxReportEngine::accountRows()` sums many rows' tax values, unrounded, before rounding once for a report total — and that substituting `TaxCalculationService`'s always-rounded output there would have changed that report's KPI totals in an estimated 83% of realistic runs. Phase 2.3 discovered the identical pattern a second time, independently, in `Dashboard\IndexController::getRevenueRows()` — proven this time at 84.3% divergence across 20,000 simulated dashboard periods. Both times, the fix was the same shape: add a small, purpose-built method that preserves full precision until the caller is ready to round once, rather than forcing every caller through a single rounded contract.

Two occurrences of the identical tension, discovered independently, in unrelated files, is not a coincidence — it is evidence of a real, general distinction that the Financial Engine's API had not yet made explicit: **some calculations produce a single, finished, storable financial value; others produce an intermediate value meant to be combined with many others before anything is finished.** Conflating these two purposes is exactly how rounding-and-aggregation bugs like the original Billing Summary bug and the Dashboard bug happen — a downstream consumer treats a value as more "finished" than it actually is, or rounds at the wrong step.

This document formalizes that distinction into two named calculation families — **Financial Transaction Calculations** and **Financial Analytics Calculations** — with explicit rules for each, a decision tree for choosing between them, and an API categorization of every method on `TaxCalculationService` as it exists today. Phase 2.4 (`InvoiceCalculationService`) is the first new service to be built after this document exists, and is intended to be the first proof that having this distinction in writing, in advance, prevents the "discover it the hard way" pattern of the last two phases.

**No business behavior changes as a result of this document.** The accompanying code change is limited to PHPDoc clarification and new tests on existing, already-shipped methods — see §9 and the completion report for the precise, verified scope.

---

## 2. Two Calculation Families

### Financial Transaction Calculations

A calculation that produces, or contributes directly to, the recorded value of **one specific financial transaction** — an invoice, a payment, a ledger entry, a customer statement line, a QuickBooks export row. The output is a finished, storable, source-of-truth number. Once computed and stored (or displayed as the record of what was stored), it does not get recomputed differently by a downstream consumer.

### Financial Analytics Calculations

A calculation that **aggregates many already-recorded transactions** to produce a summary figure — a dashboard KPI, a trend chart point, an executive report total. The output describes a collection of transactions, not any single one of them, and is never itself stored back as a transaction. Analytics calculations may carry full floating-point precision through an aggregation step and round only once, at the very end, specifically so that summing many rows produces the same answer as if the underlying transaction-level values had been summed at full precision and rounded last — matching how the database's own `SUM()` behaves.

The distinction is not "rounded vs. unrounded" as an arbitrary style choice — it is "does this number have to individually match a stored record" (Transaction) vs. "does this number only have to match the sum of many stored records" (Analytics). Both are legitimate, correct, necessary patterns. The bug pattern this document exists to prevent is using the wrong one for a given context — specifically, rounding too early inside an aggregation (Phase 2.2/2.3's discovery), or, in the other direction, leaving a value unrounded when it is about to be stored or shown as a single transaction's figure.

---

## 3. Rules for Financial Transaction Calculations

**When to use them:**
- Creating a new invoice, payment, charge, refund, discount, or any other ledger-affecting record.
- Displaying a single, already-recorded transaction's amount, tax, or total back to a user.
- Producing any customer-facing or accounting-facing document that represents one transaction or one invoice (statements, receipts, QuickBooks export rows).
- Any time the output will be written to a database column, or shown as "this is what this specific transaction is."

**What they are allowed to do:**
- Round to the financial policy's precision (currently cents, i.e. 2 decimal places) as the final step of the calculation.
- Be called once per transaction, at creation time or at display time for that one transaction.
- Become the persisted source of truth for that transaction's tax/base/total values.

**What they are not allowed to do:**
- Aggregate or sum across multiple transactions before rounding — that is an Analytics concern, not a Transaction one. If a Transaction-family method's output needs to be summed with other transactions' outputs for a report, that summation happens *after* each individual value is already finished and rounded (this is safe — see §2's note on `PaymentReconciliationLedger`), not as a substitute for an Analytics calculation designed for that purpose.
- Silently change the recorded value of a transaction that has already been stored. A Transaction calculation computes what a value *should be* at creation time; it does not retroactively re-derive historical values as a side effect of being called again later (that is what `updateInvoiceSummary()`-style recompute methods do deliberately and explicitly, which is a different, already-understood concern from the tax-formula question this document addresses).

**Rounding rules:**
- Round once, from full-precision intermediate values, to the financial policy's precision (cents). Never round an intermediate value and then perform further arithmetic on the rounded result before producing the final answer — this was the exact rounding-order bug found and fixed in `TaxCalculationService::extractTaxFromInclusiveAmount()` during Phase 2.2 (rounding the base first, then deriving tax from the already-rounded base, disagreed with the correct single-rounding-pass answer on rare inputs).

**Storage/source-of-truth expectations:**
- The output of a Transaction calculation, once written to a `customer_accounts`, `invoices`, `invoice_items`, or equivalent row, **is** the source of truth for that transaction going forward. Any report or analytics process that later reads this transaction reads this stored value (or recomputes an equivalent, correctly-rounded value from it) — it does not independently re-derive a different tax amount for the same transaction.

**Examples in this codebase:**
- `TaxCalculationService::extractTaxFromInclusiveAmount()` — used by `PaymentReconciliationLedger::streamC()` to compute one payment row's base/tax split.
- `TaxCalculationService::addTaxToExclusiveAmount()` — the formula already used by `_additional_charges.blade.php` for a single manual charge.
- The forthcoming `InvoiceCalculationService` (Phase 2.4) — every method on it, by definition, since invoices are transaction records (see §8).

---

## 4. Rules for Financial Analytics Calculations

**When to use them:**
- Computing a dashboard KPI, a trend-chart data point, or an executive summary figure that describes many transactions at once.
- Any report or reporting engine (`SalesReportEngineV2`, `PaymentReconciliationLedger`'s own aggregate totals, the Dashboard's period-over-period sales figures) whose output is a sum, average, or other aggregate over multiple rows.
- Building a shared SQL expression that a reporting engine's own `SUM()`/`GROUP BY` query will aggregate at the database level.

**What they are allowed to do:**
- Carry full floating-point precision through every intermediate step of an aggregation, and round only once, at the very end, after all rows have been combined.
- Be expressed as SQL fragments meant to be summed by the database engine itself, not necessarily as a PHP value at all until the aggregate query returns.
- Produce a number that, for a single input row in isolation, may differ by a fraction of a cent from what the Transaction-family calculation would produce for that same row — this is expected and correct, not a bug, as long as the *aggregate* reconciles (see FD-001's update, §7).

**What they are not allowed to do:**
- **Create or modify a financial transaction, ever.** An analytics calculation is read-only with respect to the ledger, invoices, and any other transactional table. It answers "what do these already-recorded transactions add up to," never "what should this transaction's value be."
- Be used as the source of a value written back to a transactional record. If a report's per-row analytics value is ever displayed as if it were "the transaction's tax amount," that is a policy violation — the display should instead read the transaction's own stored (Transaction-family) value.
- Silently round per-row before an aggregation step that expects full precision. This is precisely the bug pattern Phases 2.2 and 2.3 both found: rounding too early inside what should be a full-precision aggregation shifts the final total away from what the underlying transactions actually sum to.

**Rounding rules:**
- Do not round until the aggregation is complete. Round the final, summed/averaged figure once, for display, and nowhere earlier in the calculation.

**Aggregation expectations:**
- An Analytics calculation's correctness is judged by whether its aggregate output reconciles with what you would get by summing the Transaction-family values for the same underlying rows, at full precision, and rounding once at the end — not by whether any single row's intermediate value looks "correct" on its own.
- Where an existing analytics consumer already rounds per-row before its own summation (e.g. `PaymentReconciliationLedger`, which rounds per row today and is unaffected by this document — see §2's note), that is an accepted, pre-existing pattern for that specific consumer, not a template for new analytics code going forward. New analytics code should default to "sum first, round once," matching `SalesReportEngineV2`'s SQL pattern, unless there is a specific, documented reason to do otherwise.

**Examples in this codebase:**
- `TaxCalculationService::extractTaxFromInclusiveAmountSql()` — used by `SalesReportEngineV2` to build a `SUM(CASE WHEN...)` SQL expression, aggregated at full precision by the database.
- `TaxCalculationService::extractBaseFromInclusiveAmountRaw()` — used by `Dashboard\IndexController::getRevenueRows()` to sum many payment rows' base amounts across a 30-day or monthly period before rounding once for the displayed total.
- Any future `SalesTaxReportEngine` replacement ("Sales Tax Report V2") should be designed against these rules from the start, per FD-001.

---

## 5. Decision Tree

Ask these questions, in order, about the code you are about to write:

```
1. Am I creating or modifying a financial transaction
   (an invoice, payment, charge, refund, discount, or
   equivalent ledger-affecting record)?
       │
       YES ──────────────────────────► Financial Transaction Calculation.
       │                                Use a transaction-safe method.
       │                                Round once, store the result.
       NO
       │
       ▼
2. Am I displaying an already-recorded transaction's
   value back to a user (one specific invoice, one
   specific payment row, one receipt)?
       │
       YES ──────────────────────────► Financial Transaction Calculation.
       │                                Read/recompute the single stored
       │                                value using a transaction-safe
       │                                method. Do not aggregate.
       NO
       │
       ▼
3. Am I aggregating many rows for a dashboard, KPI,
   trend graph, or executive analytics summary?
       │
       YES ──────────────────────────► Financial Analytics Calculation.
       │                                Use an analytics-only method.
       │                                Preserve precision through the
       │                                aggregation; round once at the end.
       │                                Never write this value back as a
       │                                transaction.
       NO
       │
       ▼
4. Am I producing a customer-facing or accounting-facing
   document (a statement, a QuickBooks export row, a
   printed receipt) that represents one transaction or
   invoice?
       │
       YES ──────────────────────────► Financial Transaction Calculation.
       │                                This is still a single-transaction
       │                                representation, even though it's
       │                                an "export" — use a transaction-safe
       │                                method, not an analytics one.
       NO
       │
       ▼
   If none of the above clearly apply, stop and ask before
   writing new tax/financial math — this is exactly the
   kind of ambiguity that produced the bugs this document
   exists to prevent.
```

---

## 6. API Guidance

Categorization of every method currently on `App\Services\TaxCalculationService`, as also now reflected directly in that file's PHPDoc (see `PHASE_2_3A_COMPLETION_REPORT.md`):

| Method | Category | Notes |
|---|---|---|
| `extractTaxFromInclusiveAmount(float $amount, float $rate): TaxBreakdown` | **Transaction-safe** | Rounds once, from full precision. Safe to store or display as a single transaction's base/tax split. |
| `addTaxToExclusiveAmount(float $amount, float $rate): TaxBreakdown` | **Transaction-safe** | Rounds once. Safe to store or display as a single transaction's tax-added total. |
| `extractTaxFromInclusiveAmountSql(string $amountColumn, string $rateColumn): array` | **Analytics-only** | Returns raw SQL fragments; rounding deliberately deferred to the aggregate query's final result. **Must not** be used to compute a value destined for a transactional column. |
| `extractBaseFromInclusiveAmountRaw(float $amount, float $rate): float` | **Analytics-only** | Returns an unrounded float. **Must not** be used as a transaction's stored base/tax amount — round only after summing across the rows being aggregated. |

**Methods that should not be used for persisted financial records:** both analytics-only methods above (`extractTaxFromInclusiveAmountSql()`, `extractBaseFromInclusiveAmountRaw()`). Neither produces a value that is "finished" in the sense a stored transaction field requires; both exist specifically to be combined with other rows before anything is finalized.

**A note on `PaymentReconciliationLedger`:** it is an analytics/reporting consumer that correctly calls the *transaction-safe* `extractTaxFromInclusiveAmount()`, not an analytics-only method — because its own per-row output is already rounded before its downstream summation (a pre-existing pattern, confirmed safe in Phase 2.2). This is not a contradiction of the categorization above; it demonstrates that the *category of the method* and the *category of the consumer* are related but not identical questions — see the decision tree in §5, which is about the calculation being performed, not merely which class happens to be calling it. When in doubt for a new analytics consumer, default to an analytics-only method and the "sum first, round once" pattern unless there's a specific, already-documented reason (like `PaymentReconciliationLedger`'s existing behavior) to do otherwise.

---

## 7. FD-001 Update Guidance

Financial Decision FD-001 ("Transaction/Line-Level Sales Tax is the Financial Engine Source of Truth") is amended, not replaced, to add the following four points — recorded as a dated update in `FINANCIAL_DECISIONS.md` rather than a rewrite of the original decision text:

1. **Transaction-level tax remains the source of truth.** Nothing in this document changes that. Every taxable transaction still determines taxability, rate, and tax amount, and stores the result, exactly as FD-001 originally specified.
2. **Analytics/reporting may use high-precision intermediate calculations when aggregating many records.** This is not an exception to FD-001 — it is a clarification that "reports must not independently recalculate tax" (FD-001's original wording) means reports must not *invent a different tax rule or formula*, not that reports are forbidden from deferring rounding during aggregation. Using the same underlying formula, unrounded, purely for summation order, is compliant; using a *different* formula (as the Dashboard bug did) is not.
3. **Analytics calculations must reconcile back to transaction-level source data.** Any dashboard, KPI, or report figure must be traceable to, and consistent with, the sum of the underlying transactions' recorded values — this is the concrete, testable form of "reports must consume Financial Engine transaction values."
4. **Analytics calculations must never write financial transactions.** This makes explicit what was implicit in the original decision: an analytics/reporting code path is read-only with respect to the ledger and invoices. If a future feature needs to create a transaction as a side effect of a reporting action, that creation must go through a Transaction-family calculation and write path, not be folded into the analytics calculation itself.

See `FINANCIAL_DECISIONS.md` for the actual dated amendment text.

---

## 8. Phase 2.4 Guidance

**Invoices are financial transaction records, not analytics.** An invoice's subtotal, tax, total, paid amount, and open amount are all values that get stored, displayed to the customer, and used as the basis for payment collection — they are never merely a summary of other data. Every one of these values must individually match what was actually invoiced; there is no notion of "close enough in aggregate" for a single invoice the way there is for a dashboard total.

**Therefore, `InvoiceCalculationService` (Phase 2.4) must use transaction-safe calculations only.** Concretely:

- Any tax calculation `InvoiceCalculationService` performs must round once, from full precision, exactly as `extractTaxFromInclusiveAmount()`/`addTaxToExclusiveAmount()` already do — it should call into these transaction-safe methods (or methods built the same way) rather than reimplementing tax math independently, continuing the "one source of truth" principle this whole initiative exists to establish.
- `InvoiceCalculationService` must never call `extractTaxFromInclusiveAmountSql()` or `extractBaseFromInclusiveAmountRaw()` — both are analytics-only, and using either to compute a value that gets written to `invoices.total`, `invoices.paid_amount`, or any other persisted column would silently reintroduce the exact class of bug (an unfinished, deferred-rounding value being treated as a finished transaction figure) this document exists to prevent.
- The known dual-update issue already documented (Phase 2 Architecture Report §1.4 — `Invoice\PaymentStoreController` currently updates `paid_amount`/`open_amount` inline instead of via `updateInvoiceSummary()`) is a *Transaction-calculation-ordering* question, not a Transaction-vs-Analytics question — both existing code paths are already (correctly) in the Transaction family. Phase 2.4's job is to unify which transaction-safe code path runs, not to introduce any analytics-style deferred rounding into invoice totals.

---

## 9. Risks Prevented

This architecture, once followed, prevents:

- **Rounding drift** — a value rounded at the wrong step (too early in an aggregation, or not at all before being stored) silently producing a slightly different number than the same underlying data would produce through the correct calculation path. This is the literal mechanism behind both the Dashboard bug and the discovered-and-fixed `TaxCalculationService` rounding-order bug.
- **Report/invoice mismatch** — a dashboard or report total disagreeing with the sum of the actual invoices/payments it's supposed to summarize, because the report used a different formula or rounding order than the transactions themselves used. This is the specific failure mode FD-001 exists to close, and this document gives it concrete engineering rules rather than leaving it as a general aspiration.
- **Accidental use of analytics formulas in transaction records** — a future developer, seeing `extractBaseFromInclusiveAmountRaw()` available and convenient, using it to compute an invoice line's stored tax amount, producing an unrounded or otherwise not-policy-compliant value in a persisted financial record. The explicit "ANALYTICS-ONLY — do not use for persisted financial records" PHPDoc markers (Phase 2.3A code change) and this document's §6 table exist specifically to make this mistake visible and easy to avoid at the point of writing new code, not just at review time.
- **Accidental use of transaction-rounded formulas in large aggregations** — the inverse mistake: calling a transaction-safe (always-rounded) method inside a loop that sums many rows, silently shifting an aggregate total away from what the database's own `SUM()` would produce, exactly as discovered (and avoided) in both Phase 2.2 and Phase 2.3.

---

## 10. Completion Criteria

Before Phase 2.4 begins, the following must be true:

- [x] This document exists and defines both calculation families, the decision tree, and the API categorization.
- [x] `TaxCalculationService`'s existing methods are labeled in their own PHPDoc as transaction-safe or analytics-only, so the categorization is visible at the point of use, not only in this document.
- [x] Tests exist that make the transaction-safe vs. analytics-only distinction executable (i.e., a future change that collapses the distinction should fail a test, not just violate a written rule).
- [x] `FINANCIAL_DECISIONS.md` is updated with the FD-001 amendment described in §7.
- [x] `FINANCIAL_ENGINE_MASTER_ROADMAP.md` and `FINANCIAL_ENGINE_TODO.md` reflect Phase 2.3A's completion.
- [x] `SalesTaxReportEngine.php` remains untouched (confirmed via `git diff` — zero diff).
- [x] No invoice, ledger, payment-processing, or balance logic was modified.
- [x] No existing test or application output changed — only new tests and PHPDoc were added.
- [x] All runnable tests pass; any suite that could not run in this environment is named with a reason (see `PHASE_2_3A_COMPLETION_REPORT.md`).

With these satisfied, **Phase 2.4 may begin**, with the explicit mandate (§8) to build `InvoiceCalculationService` using transaction-safe calculations exclusively.
