# Customer Credit Architecture

Document date: 2026-07-03
Status: **Architecture and planning only. No code implemented.**
Financial Engine Version: 2.3 (Customer Credit & Customer Resolution Framework — planning)
Governing documents: `FINANCIAL_TRUTH_TABLE.md` (this document proposes amendments to it, does not silently override it), `PHASE_2_5A_LEDGER_READINESS_REVIEW.md`, `PHASE_2_6_COMPLETION_REPORT.md` / `PHASE_2_7_COMPLETION_REPORT.md` (`LedgerBalanceService`'s current scope).

---

## 1. Why This Document Exists

Every Financial Engine phase so far has treated `customer_accounts.type = 'credit'` and `'debit'` as a known gap: confirmed dead code since Phase 2.5A, confirmed still undefined in `FINANCIAL_TRUTH_TABLE.md` §3b rows 4-5 ("Undefined... zero existing precedent to implement against... Define real behavior or formally deprecate the enum value"), and explicitly excluded from every `LedgerBalanceService` phase since (`amountWithTax()` and `applyTransaction()` both throw immediately for `'credit'`/`'debit'`). This document is the first attempt to actually define what those two dormant values should mean — not by guessing, but by designing the complete business capability they were presumably reserved for: **Customer Credit**, purchasing power a customer holds independent of any specific order or invoice.

This is a planning document. Nothing here authorizes writing to `'credit'`/`'debit'`, changing `LedgerBalanceService`, or touching `CustomHelper`. Per `FINANCIAL_DECISIONS.md`'s standing rule, any of this becoming real policy requires its own dated decision entry before implementation — this document is the proposal that entry would ratify, not a substitute for it.

## 2. Two Kinds of Credit, One Ledger Mechanism

The mission separates **Financial Store Credit** (customer-earned, dollar-for-dollar, e.g. an overpayment refunded as credit instead of cash) from **Promotional Store Credit** (business-granted, marketing-driven, e.g. a birthday reward). These have different *origins*, different *expiration rules*, different *reporting categories*, and different *liability treatment* — but they are the same *kind of thing* from the ledger's point of view: a positive balance the customer can apply against a future charge. Treating them as two separate ledger mechanisms would duplicate redemption logic and create exactly the kind of divergence this whole initiative exists to eliminate (see `FINANCIAL_TRUTH_TABLE.md`'s repeated finding that duplicated formulas drift).

**Design decision: one `customer_accounts.type` value per direction (`credit` for grants, `debit` for redemptions), one new column to distinguish origin.**

| Concern | Design |
|---|---|
| Ledger type on grant | `customer_accounts.type = 'credit'` (finally giving this dormant enum value real meaning) |
| Ledger type on redemption | `customer_accounts.type = 'debit'` (same) |
| Distinguishing Financial vs. Promotional | New column, e.g. `customer_accounts.credit_category` (`'financial'` \| `'promotional'`), or a new `credit_source` enum — **exact column name is an implementation detail for the eventual pre-implementation checklist, not decided here** |
| Distinguishing *why* (overpayment vs. goodwill vs. birthday reward, etc.) | The existing `reason` column already used by every other transaction type — no new column needed, this is exactly what `reason` is for (see `ChargeStoreController`'s arbitrary-reason pattern, already proven in production) |
| Expiration | New concept, not present anywhere in `customer_accounts` today — see §4 |

This reuses the exact mechanism `FINANCIAL_TRUTH_TABLE.md` already anticipated (`'credit'`/`'debit'` as real enum values) rather than inventing a parallel ledger. It also means `LedgerBalanceService::amountWithTax()`/`applyTransaction()` gain two new, real branches once this is approved — a natural, additive extension of work already built, not a redesign.

## 3. Financial Credit — Sources

Every listed example is a **conversion of an existing, already-real financial event into stored purchasing power**, not a new kind of money:

| Source | Where it originates today | Ledger relationship |
|---|---|---|
| Overpayment | A payment (`type='payment'`) exceeds the amount owed | The excess becomes a `credit` grant, `reason='Overpayment — Invoice #...'` |
| Refund converted to credit | `CustomerAccount/RefundStoreController.php` (ledger refund) or `OrderManagement/Orders/RefundPaymentController.php` (gateway refund) — **both already exist and are kept deliberately separate today** (confirmed in this phase's research) | A refund decision point gains a third option: cash-back (existing), gateway refund (existing), or credit (new) — see `CUSTOMER_RESOLUTION_ARCHITECTURE.md` §3 for the decision tree this feeds |
| Warranty adjustment | Not currently modeled anywhere in the codebase — greenfield | A `credit` grant, `reason='Warranty Adjustment'`, tied to an order/product reference |
| Settlement adjustment | Not currently modeled — greenfield | A `credit` grant, `reason='Settlement Adjustment'`; **this is the same phrase Phase 2.6's mission used for an explicitly out-of-scope hypothetical type ("Settlement Adjustments") — this document proposes it becomes real specifically as a Financial Credit source, closing that naming gap** |
| Rental adjustment | Not currently modeled — greenfield | A `credit` grant tied to an `order_id`, `reason='Rental Adjustment'` |
| Manager goodwill | Closest existing analog: `CustomerAccount/DiscountStoreController.php` (a discount reduces what's owed on the spot); goodwill credit differs by *not* being tied to a specific charge — it's forward-looking purchasing power | A `credit` grant, `reason='Manager Goodwill'`, gated by the same permission tier as `DiscountStoreController` today (see §7) |

**Financial Credit does not expire by default** (it represents money the business already owes the customer in substance) — see §4 for the one narrow exception (state escheatment/unclaimed-property law, out of scope for this document but flagged as a future legal/compliance dependency).

## 4. Promotional Credit — Sources and Expiration

Full campaign-side design lives in `PROMOTIONAL_CREDIT_ARCHITECTURE.md`. This section covers only the ledger-facing half: how a promotional grant becomes a `customer_accounts` row.

| Source (from the mission) | Ledger relationship |
|---|---|
| Marketing campaign, Grand opening, "We miss you" campaign | Bulk `credit` grants issued by `PromotionalCreditService` (see `PROMOTIONAL_CREDIT_ARCHITECTURE.md`), one ledger row per recipient customer |
| Birthday reward, Referral bonus, VIP promotion | Same mechanism, triggered individually rather than in bulk |

**Promotional Credit expires by default; Financial Credit does not.** This is the single most important business-rule distinction between the two categories, and it must be a written business decision (recorded in `FINANCIAL_DECISIONS.md` when this initiative is approved for implementation), not an assumption baked into this document. Proposed default, **pending that approval**:

- Every promotional grant carries an `expires_at` timestamp, set at grant time by the campaign definition (e.g., "expires 90 days after issuance").
- An expiration sweep (a scheduled job, not built here) creates an offsetting `debit` row with `reason='Expired — <original grant reason>'` when `expires_at` passes with balance remaining — **this is itself a ledger-affecting event and must go through the same `LedgerBalanceService` path as a redemption**, not a silent balance overwrite. This preserves the "every balance change is an auditable transaction" principle every phase of this initiative has enforced.
- Financial Credit sources never carry an `expires_at` value (or it is explicitly null/unlimited) — again, pending explicit business confirmation, since "overpayment credit that expires" would itself be a policy this document does not assume.

## 5. Redemption

Redemption is a `debit` transaction reducing the customer's credit balance, applied against a charge, invoice, or order total. Two redemption paths are anticipated:

1. **Manual redemption** — staff-initiated, via the Customer Resolution Wizard (`CUSTOMER_RESOLUTION_ARCHITECTURE.md` §3, priority 2 of 3) or the CRM customer account screen directly.
2. **Order Entry redemption** — customer- or staff-initiated at the point of a new charge, via the "Apply Credit?" control described in §6 and detailed in `CUSTOMER_RESOLUTION_ARCHITECTURE.md`'s Order Entry Integration section.

**Redemption order when a customer holds both Financial and Promotional credit**: proposed default is Promotional Credit redeems first (since it expires and Financial Credit does not — using the expiring balance first minimizes forfeited value), **but this is a business decision, not decided here**.

**Redemption never exceeds the available balance** — this is enforced the same way `getAvailableCredit()`'s `credit_limit` check already works today (`CustomHelper.php:101`: `if (!(($customer->credit_limit ?? 0) > 0) ...) return 0;`), i.e., a hard ceiling check before the ledger write, not a post-hoc reconciliation.

## 6. Balance Tracking

**Design decision: Customer Credit balance is a distinct, separate figure from `customers.available_credit_balance`.**

This is the single most important architectural boundary in this whole document, and it exists because of a fact this initiative already discovered and documented: `customers.available_credit_balance` (the field `updateCreditBalance()`/`applyTransaction()` both write) conflates "running ledger balance" and "available credit" into one field — a known simplification flagged in `PHASE_2_5A_LEDGER_READINESS_REVIEW.md`. Adding Customer Credit into that same field would deepen an already-acknowledged ambiguity rather than resolve it. Instead:

- **`customers.available_credit_balance`** continues to mean exactly what it means today: the customer's running charge-account balance (what they owe/have available on a Net-terms or house-account basis) — unaffected by this initiative.
- **A new, separate balance** — proposed as a computed sum over `customer_accounts` rows filtered to `type IN ('credit','debit')`, or a dedicated `customer_credit_balances` table if computing it live proves too expensive at scale — represents Customer Credit specifically. **Which of these two implementations is correct is a technical decision for the eventual pre-implementation checklist**, not resolved here; this document only commits to the *conceptual* separation.

## 7. Reporting

See `REPORTING` section of the master roadmap update and the shared reporting list in this initiative's scope — the Customer-Credit-specific reports are: **Outstanding Store Credit** (Financial + Promotional combined, and split), **Credit Usage** (redemption rate over time), **Credit Expiration** (upcoming/passed expirations, promotional only), **Customer Lifetime Savings** (cumulative redeemed value per customer). Each of these should be built the same way every other Financial Engine report in this codebase has been built successfully: as a read-only consumer of `customer_accounts` rows, never an independent recalculation — the exact discipline `SalesReportEngineV2`/`PaymentReconciliationLedger` were migrated onto in Phase 2.2, and the exact discipline FD-001 already codifies project-wide.

## 8. Audit History

Every grant, redemption, and expiration is itself a `customer_accounts` row — meaning audit history is not a new feature to build, it already exists as a consequence of §2's design decision: the ledger *is* the audit log, exactly as it already is for Payment/Charge/Order today. No new audit table is needed. `customer_action_log` (the JSON audit trail already used by `CustomerAccount/UpdateController.php`) is available if per-field edit history is later needed for credit grants specifically.

## 9. CRM Integration

The existing CRM customer view (`resources/views/admin/crm/customers/partials/_tab_credit.blade.php`, already touched in Phase 2.5) is the natural home for a new "Store Credit" sub-section, listing `credit`/`debit` rows the same way `payment`/`charge`/`refund` rows are already listed today — this is a Blade-view addition, not a new screen, when implementation begins. No changes to that file are made by this document.

## 10. Financial Engine Integration — Responsibility Boundaries

| Responsibility | Owner |
|---|---|
| Deciding a credit grant should happen, for what reason, how much, with what expiration | **`CustomerCreditService`** (new, not built) |
| Computing whether a redemption amount is valid against current balance | **`CustomerCreditService`** |
| Applying the resulting ledger effect (`credit`/`debit` row, running balance impact) | **`LedgerBalanceService`** — once `'credit'`/`'debit'` gain approved `amountWithTax()`/`applyTransaction()` branches, `CustomerCreditService` calls into it exactly the way a future `applyTransaction()` caller would, never duplicating balance arithmetic itself |
| Tax treatment of a credit grant or redemption | **`LedgerBalanceService`** via `TaxCalculationService` — **explicitly flagged as undecided**: is a credit redemption tax-inclusive (like Payment) or does it interact with `sales_tax_type` (like Charge)? This is a new Truth Table row this initiative would need to add, not something this document assumes |
| Campaign definition, targeting, scheduling | **`PromotionalCreditService`** (new, not built) — calls `CustomerCreditService` to actually issue grants, never writes `customer_accounts` directly |
| Reporting | Read-only consumers of `customer_accounts`, same pattern as every existing report |

**`LedgerBalanceService` gains exactly two new branches when this is approved (`credit`, `debit`) and nothing else** — it does not gain campaign logic, expiration scheduling, or business-rule knowledge about *why* a credit exists. That knowledge lives entirely in `CustomerCreditService`, matching the separation this entire initiative has maintained between "what the ledger does" (`LedgerBalanceService`) and "why" (every calling controller/service).

## 11. Open Business Decisions (do not assume any of these)

1. Does Financial Credit expire, ever (e.g., state-mandated escheatment after N years of dormancy)?
2. Redemption order when both credit categories are present on one customer.
3. Tax treatment of credit grants and redemptions (new Truth Table row needed).
4. Exact schema for distinguishing Financial vs. Promotional (`credit_category` column vs. a new table vs. reason-string convention).
5. Whether Customer Credit balance is computed live or maintained as a running total (performance/consistency tradeoff, technical not business, but affects the schema).
6. Whether a customer can have a *negative* credit balance under any circumstance (this document assumes no — redemption is capped at available balance, per §5 — but this should be explicitly confirmed, not inferred).
