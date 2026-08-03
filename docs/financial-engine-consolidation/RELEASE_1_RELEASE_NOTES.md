# Release 1 — release notes

**Shared pre-tax adjustment engine and Store Credit special-tax correction**

Prepared for management. Non-technical summary first; detail follows.

---

## In one paragraph

We found and fixed a defect that was **over-charging customers**. When a customer used Store Credit on an order carrying a special tax, the special tax was not reduced along with the merchandise value — so the customer paid special tax on money they were never charged. This release corrects that going forward, and adds the underlying record-keeping the fix required. It also makes Store Credit concessions finally show up in product revenue reporting, which they never did before.

---

## The financial defect corrected

**What was happening.** A pre-tax discount (Store Credit) reduces the merchandise value of an order, and the taxes calculated from that value should reduce with it. Ordinary sales tax did. **Special tax did not.**

**Worked example** — a $200.00 order carrying $4.00 of special tax, with $50.00 of Store Credit applied:

| | Before this release | After |
|---|---|---|
| Merchandise value | $200.00 | $200.00 |
| Store Credit applied | $50.00 | $50.00 |
| Sales tax | $14.63 ✓ | $14.63 ✓ |
| **Special tax** | **$4.00 ✗** | **$3.00 ✓** |

The customer was over-charged **$1.00** on that order. The correct figure is 2% of the $150.00 they actually paid for, not of the original $200.00.

**Why it happened.** Special tax had **no column anywhere in the database**. It was calculated once at checkout, folded into the order total, and stored only inside a per-line data blob alongside several other charges. When the discount engine reduced an order, it could not reduce special tax while protecting flat fees — because it had no way to tell the two apart. The fix required giving both their own fields first.

**Scope.** Only orders where Store Credit (or any pre-tax discount) was applied **and** the order carried special tax. Orders without special tax, or without a discount, were never affected.

**Historical orders are not changed by this release.** A separate read-only audit measures how many past orders were affected and by how much; remediating them is a business decision, deliberately kept separate.

---

## What changed

**1. Special tax and added fees now have real fields.** Previously invisible to the system outside a data blob. Backfilled from historical checkout records under a strict rule: where the historical data could not be read, or did not add up, the record was **left alone and reported** rather than guessed at.

**2. The tax correction.** Special tax now reduces in proportion to the merchandise value. **Added fees do not** — a flat fee is charged for something other than merchandise value and must not shrink when merchandise is discounted.

**3. Concessions are now tracked line by line.** The system records which discount reduced which order line, by how much. This makes accurate product reporting possible and lets one discount be reversed without disturbing another.

**4. Product reporting now shows net revenue.** Store Credit concessions reduce product revenue. **They previously did not** — the report's own documentation claimed discounts were accounted for, and they were not. Gross figures remain available in reports that explicitly say "gross".

**5. One adjustment engine, hardened.** Preview and confirmation now share a single calculation, so what a user is shown is arithmetically identical to what gets saved. All money arithmetic uses whole cents.

---

## What intentionally did **not** change

| | Why |
|---|---|
| **Historical orders** | No past order is re-priced or rewritten. The fix applies going forward |
| **Gross sales figures** | The original sale value is never erased. Gross reporting is unchanged |
| **Added fees** | Correctly protected — they are not derived from merchandise value |
| **Legacy discounted orders in product reporting** | Report **gross**, with the unattributed concession disclosed separately. There is no record of which products bore a historical concession, and inventing a split would produce product figures that look authoritative and are not |
| **Checkout, payments, refunds, invoicing, Accounts Receivable** | No workflow changed. Only the arithmetic behind existing behaviour |
| **User permissions** | None added or changed |
| **Sales Trend report** | Flagged for a separate decision — see below |

---

## Why Goodwill is deferred

Goodwill Adjustment — a manager-authorised discretionary concession — was originally built first. Two things changed that.

**It was built against the wrong code.** The work targeted a repository that had not been updated since 22 July, while production had moved 169 commits ahead. Several assumptions it relied on were no longer true.

**Production had already built half of it.** Store Credit had been reframed as a pre-tax discount with a reusable engine underneath. Goodwill needs exactly that engine. Building a second one alongside would have meant **two independent systems changing order prices** — with the ordinary risk of two implementations disagreeing about the same money.

So the shared engine was finished, corrected and proven first. **Goodwill now becomes a policy layer** deciding *how much* concession to grant, while the engine handles pricing, tax, allocation, reporting and reversal identically for both.

The delay also had a direct payoff: **the special-tax defect was found during that work.** It is live today, unrelated to Goodwill, and would not have been discovered by building Goodwill as originally planned.

Goodwill follows in Release 2, on a foundation already proven in production.

---

## Risk and confidence

| | |
|---|---|
| New database structures | 3 migrations — all additive; nothing dropped or narrowed |
| Rollback | Code-only; the schema is retained deliberately |
| Test coverage | 78 discount tests, 132 report tests, plus a full lifecycle suite |
| Pre-existing test results | Unchanged across every suite — this release introduced no regressions |
| Deployment gate | The migration **will not proceed** unless a read-only audit confirms every historical order's figures reconcile exactly |

**One honest caveat.** The pre-migration audit has never run against production data — both development databases are empty. Its first production run is the first real test, and it may legitimately stop the deployment. That is the gate working as designed, not a failure.

---

## Follow-up work, recorded

Seven items are logged in `RELEASE_2_BACKLOG.md`, including Goodwill itself, a refund tax-calculation defect that is **still live**, the Sales Trend gross-vs-net decision, whether to reconstruct historical concession data, and a pre-existing security posture question. None blocks this release; none is forgotten.

---

*Prepared 2026-08-02 · Baseline `d8591af5` · Release head `0a7dcaad` · 6 commits*
