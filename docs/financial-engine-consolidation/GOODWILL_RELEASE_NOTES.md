# Goodwill Adjustment — release summary

**Scope of this release: the Goodwill domain and workflow only.** The shared
pre-tax adjustment engine it runs on was deployed separately and is already in
production.

Deployment procedure: `GOODWILL_DEPLOYMENT_RUNBOOK.md`.
Design and decisions: `GOODWILL_ADJUSTMENT_DESIGN.md`, `FINANCIAL_DECISIONS.md` G-1…G-8.

---

## What a Goodwill adjustment is

A manager-authorised, discretionary **pre-tax reduction of an order's product
cost**, applied so the revised order total equals the payments already accepted
as payment in full.

A customer owes $219.50, pays $200.00, and a manager agrees to treat that as
settled. Goodwill reduces the merchandise basis by $17.77; ordinary and special
tax fall with it; the revised total is $200.00; the balance closes.

**It is not a payment.** No payment row is created, ever. The customer's money is
recorded exactly as collected, and the order moves down to meet it.

---

## 1. Already deployed — the shared pre-tax engine

`app/Services/Discounts/` shipped in Release 1 and is live. It owns:

- pre-tax discount calculation and persistence;
- ordinary **and** special tax recomputation from the surviving basis;
- protected added fees, which never scale;
- gross merchandise values, which are never reduced;
- line-level allocation and its reconciliation identity;
- stacked-adjustment behaviour and lossless reversal;
- net product revenue across five reports;
- receipt component snapshots and labelling.

**Nothing in that list changed in this release.** Goodwill is a new *caller*.

## 2. Added now — the Goodwill domain and workflow

| Layer | What it owns |
|---|---|
| Domain | `order_goodwill_adjustments`, reason taxonomy, immutable audit snapshots |
| Service | authorization, sizing, guards, idempotency, apply and reverse |
| Workflow | preview / apply / reverse endpoints, Pending Payment modal |

Goodwill **computes no financial value**. The concession is sized by asking the
engine what a candidate would produce, and applied through it. Every amount is
reached via the linked `ProductDiscount`; none is copied.

**Operationally:**

- authority is enforced through Spatie directly, never the Gate;
- the authorising manager is recorded separately from the operator, and is
  verified to hold the permission in their own right;
- one active adjustment per order, guaranteed by a database unique index;
- reason codes carry a **Service Recovery / Business Courtesy** category, so a
  cost of failure is never aggregated with a cost of sale;
- the audit record is immutable once written, and a reversed adjustment can
  never be reinstated — re-granting is a new decision with its own record;
- A/R-posted and invoiced orders are refused; the invoice guard is new here,
  because the shared engine does not make it (backlog #9).

## 3. Out of scope — Gift Card remains tender

A Gift Card is **purchased value**. It does not reduce the taxable merchandise
basis and must never be grouped with pre-tax adjustments. It is deliberately
absent from `DiscountType` entirely, and `reducesTaxableBasis()` exists so it
cannot be added without the question being asked explicitly.

Unchanged by this release, and not planned.

## 4. Also fixed here — the refund tax basis

**A live production defect, corrected in this release.** Release 2 backlog items
2 and 3, now closed.

`proportionalTaxRefund()` derived its rate as `tax_amount ÷ subtotal`, which
understated it two ways: `subtotal` includes tax-free lines that never generated
tax, and it does not move when a pre-tax adjustment reduces `tax_amount`. On
$100 taxable plus $100 tax-free it produced **4.875% instead of 9.75%**.

It was escalated from follow-up to a **release blocker**: shipping Goodwill
without it would have made the defect reachable on more orders.

The rate now comes from `TaxableBasisResolver`, which recovers the merchandise
value that actually generated the tax.

> **This does not increase anyone's refund.** A refund total is made of
> merchandise plus the tax charged on it. The **total is unchanged** and always
> was correct — what changes is how it is divided between those two parts in our
> records. We were recording too little of it as sales tax. The customer
> receives exactly what they would have received before.

The consequence is for tax remittance, not for what anyone is paid. Expect
`tax_refunded` on mixed-tax and discounted orders to read higher after
deployment, with the merchandise portion correspondingly lower.

**Forward-only.** The corrected calculation applies to refunds processed **after
deployment**. Refunds already issued keep the split they were processed with —
permanently, as part of the audit trail. No historical financial data is
modified, no historical refund is recalculated, and no remediation script or
backfill is provided. Standing policy: `FINANCIAL_DECISIONS.md` **G-10**.

## 5. Deferred — issued-receipt immutability

Draft decision **C** — an issued receipt is immutable, and a later adjustment
produces a *superseding* receipt — is **decided but not implemented**.
`receipts.issued_at` was never added, and Release 1 shipped in-place refresh.

Two consequences accepted knowingly:

- a receipt already printed or emailed restates itself on the next read, so the
  evidence of what the customer was originally shown is not retained;
- `receipts.pretax_discount_total` carries no attribution, so an order with two
  *different* adjustment types falls back to the truthful generic
  `Pre-Tax Discounts` rather than naming either.

The second becomes visible the moment Goodwill and Store Credit are stacked on
one order. It is correct — it does not misattribute — but it is less
informative than a per-type breakdown would be. Backlog item 10.

---

## Verification

**129 Goodwill tests, 636 assertions, all passing.**

| Suite | Tests | Proves |
|---|---|---|
| `GoodwillDomainTest` | 30 | enum, category, immutability, one-active, residual bound, seeder |
| `GoodwillAdjustmentServiceTest` | 37 | solver, guards, typed failures, stacking, reversal |
| `GoodwillPaymentLockTest` | 8 | concurrency, against a live second database connection |
| `GoodwillEndpointTest` | 29 | routes, authority through the Gate bypass, UI visibility |
| `GoodwillLifecycleTest` | 25 | the whole feature composed end to end |

Every other suite is **identical to baseline**, verified by stashing all Goodwill
work and re-running rather than by inspection.

## Operator summary

> Take the payment first. If the customer has paid and a balance remains, an
> **Apply Goodwill** button appears next to the balance. Choose why, choose the
> manager approving it, and apply. The order closes at the amount collected, and
> no second payment is recorded — the order total changed, not the money.
>
> If it was wrong, reverse it. The balance re-opens and both the original
> decision and the reversal stay in the history.
