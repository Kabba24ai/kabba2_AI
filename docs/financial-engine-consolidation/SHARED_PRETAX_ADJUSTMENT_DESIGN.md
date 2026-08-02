# Shared pre-tax adjustment engine — audit and revised design

**Status: DRAFT, awaiting approval. No application code written.**

Baseline: `d8591af5` (`production/raj_development`). Governing decisions A, B, C as issued 2026-08-02. Companion: `GOODWILL_PRODUCTION_REBASELINE_REPORT.md`.

Central principle being implemented:

> Gross merchandise values remain stable; discounts are explicit; tax and net revenue follow the allocated discounts.

---

## Part 1 — Mandatory shared-engine audit

Every result below was **executed** against `OrderDiscountTarget` on the production branch, not reasoned about. The probe was thrown away after use.

### 1.1 Findings summary

| Scenario | Verdict | Detail |
|---|---|---|
| Mixed taxable / tax-free lines | ✅ **Correct** | Blended rate is arithmetically exact under proportional reduction |
| Product-targeted discounts | ✅ **Not reachable today** | No product-level target exists |
| Discounts on selected lines only | ✅ **Not reachable today** | Targets are surface-level, not line-level |
| **Special-tax lines** | ❌ **LIVE DEFECT** | Special tax is preserved unchanged; over-collected |
| Added fees | ⚠️ **Correct but fragile** | Correctly preserved, but indistinguishable from special tax |
| Multiple simultaneous discounts | ✅ **Correct** | Recompute-from-snapshot makes stacking exact |
| Rounding | ⚠️ **Float** | `round(…, 2)` throughout; not integer cents |

### 1.2 The blended rate is NOT a defect — resolving the open question

`OrderDiscountTarget::taxRate()` derives `tax_amount_before_discount ÷ subtotal`. I flagged this as suspect. **It is correct**, and here is why:

```
newTax = (subtotal − D) × (baseTax ÷ subtotal) = baseTax × (1 − D/subtotal)
```

Under a proportional reduction, the taxable basis `Bt` also becomes `Bt × (1 − D/subtotal)`, so the true tax is `Bt × realRate × (1 − D/subtotal)` = `baseTax × (1 − D/subtotal)`. **Identical.**

Executed proof — $100 taxable @ 9.75% plus $100 tax-free, $50 discount:

```
blended rate  = 9.75 / 200 = 0.04875
engine result = 7.31
proportional truth: taxable base 100 × (1 − 50/200) = 75 → 75 × 0.0975 = 7.31   ✓
```

**Condition on which this depends:** the discount must reduce the *entire* merchandise population proportionally. `eligibleProductValue()` returns `subtotal − pretax_discount_total`, i.e. always the whole base, and `DiscountTargetType` offers only surface-level targets (`Order`, `FuelCharge`, `DamageCharge`, `Extension`) — never a product or line.

> **Guard required.** The moment any line-scoped or product-scoped target is introduced, this rate becomes wrong in **both** directions — under-taxing when the discount lands on exempt lines, over-taxing when it lands on taxable ones. Per Decision B we are introducing per-line allocation, which makes that scoping *possible*. The rate must therefore stop being blended and start being derived from the **allocated taxable basis** (Part 2.4).

### 1.3 Special tax is over-collected — live defect, independent of Goodwill

`recompute()` preserves everything that is not product base or product tax:

```php
$otherComponents = round($origGrand - $subtotal - $baseTax, 2);
$newGrand = round($remainingBase + $newTax + $otherComponents, 2);
```

Special tax is **basis-derived** (`$itemSubTotal × special_taxes/100`, per `CartHelper::buildCartItem()`). When the basis shrinks, special tax must shrink with it. It does not.

**Executed proof** — $200 merchandise, ordinary tax $19.50, special tax $4.00 (2%), grand total $223.50; $50 discount:

```
after:  subtotal=200.00  tax_amount=14.63  grand_total=168.63
preserved "other components" = 4.00
special tax SHOULD be 150.00 × 2% = 3.00
→ $1.00 of special tax OVER-COLLECTED
```

The customer is charged special tax on merchandise value they were not charged for. This affects **every Store Credit discount on an order carrying special tax, today**, and is not caused by Goodwill.

### 1.4 Why the defect cannot be fixed without the special-tax columns

`otherComponents` is a single lumped residual containing special tax **+** added fees **+** delivery **+** coupon. The engine cannot reduce special tax while preserving added fees because **it cannot tell them apart** — neither has a column, and both survive only inside `order_products.product_data` JSON.

This is the direct link between the two workstreams: **the special-tax/added-fee columns from the stale branch are a prerequisite for fixing a defect that already exists in production.** They are no longer merely a Goodwill enabler.

### 1.5 Rounding

Float arithmetic with `round(…, 2)` at each step. Observed: `66.67 × 0.0975 = 6.500325 → 6.50`. Correct here, but the engine offers no exact-reconciliation guarantee, and Goodwill's contract requires the identity to close in integer cents or write nothing.

### 1.6 Duplicate tax computation

`DiscountCalculator` computes `taxBefore`/`taxAfter`/`finalAmountDue` via `TaxCalculationService`. `OrderDiscountTarget::applyDiscount()` **discards all of it** and uses only `$result->discountAmount`, recomputing tax itself in `recompute()`. Two independent tax paths that must agree, with nothing asserting that they do.

---

## Part 2 — Revised design

### 2.1 Canonical columns

**`orders`** — gross values never move:

| Column | Meaning | Status |
|---|---|---|
| `subtotal` | **gross** merchandise, Σ line gross. Never reduced | existing |
| `tax_amount` | ordinary tax **after** allocated discounts | existing |
| `special_tax_amount` | special tax **after** allocated discounts | **new** |
| `added_fees_amount` | flat fees — **never** reduced | **new** |
| `pretax_discount_total` | Σ all pre-tax adjustments | existing |
| `discount_amount` | legacy coupon, post-tax. Untouched | existing |
| `tax_amount_before_discount` | ordinary tax at original basis | existing |
| `special_tax_before_discount` | special tax at original basis | **new** |
| `grand_total_before_discount` | grand total at original basis | existing |
| `grand_total` | current amount due | existing |

**`order_products`** — gross values never move:

| Column | Meaning | Status |
|---|---|---|
| `sub_total` | **gross** line merchandise. Never reduced | existing |
| `tax` | ordinary tax after allocation | existing |
| `special_tax` | special tax after allocation | **new** |
| `added_fees` | flat fees — never reduced | **new** |
| `pretax_discount_allocated` | this line's share of every pre-tax adjustment | **new** |

**Canonical identity** (integer cents):

```
grand_total = subtotal
            − pretax_discount_total
            + tax_amount
            + special_tax_amount
            + added_fees_amount
            − discount_amount
```

Per line:

```
line_net = sub_total − pretax_discount_allocated
line_total = line_net + tax + special_tax + added_fees
```

Σ line values must equal the order values, to the cent.

### 2.2 Line-level allocation model

A new table records **which adjustment** allocated **how much** to **which line** — `pretax_discount_allocated` alone cannot support exact reversal of one adjustment among several.

**`order_product_discount_allocations`**

| Column | Notes |
|---|---|
| `id` | |
| `product_discount_id` | FK → `product_discounts` (the adjustment) |
| `order_product_id` | FK → `order_products` |
| `allocated_cents` | integer cents |
| `tax_before_cents`, `tax_after_cents` | ordinary |
| `special_tax_before_cents`, `special_tax_after_cents` | |
| `reversed_at` | nullable |
| unique | (`product_discount_id`, `order_product_id`) |

**Allocation rule** — unchanged from FD-002 Amendment 3, now applied in the shared layer:

- proportional to each line's **remaining** gross (`sub_total − already allocated`);
- across **all reducible merchandise lines**, taxable and untaxed alike;
- **never** to protected components (added fees);
- largest-remainder for leftover cents, ties by ascending `order_product_id`, so the same input always produces the same allocation — the property that makes exact reversal possible.

### 2.3 Ordering, interaction and stacking

**Stacking is permitted.** `pretax_discount_total` already accrues and `recompute()` derives from the immutable `*_before_discount` snapshot, so N adjustments in any order produce the same result.

**Ordering rule:** adjustments apply in `id` order and are always recomputed from the original snapshot, never from the current state. Store Credit and Goodwill are peers; neither has precedence.

**Interaction constraints:**

1. Combined `pretax_discount_total` may never exceed `subtotal`.
2. Goodwill's own ceiling is `grand_total − payments accepted`; Store Credit's is the customer's balance. Each is enforced by its own domain.
3. **At most one active Goodwill adjustment per order** (FD-002) — enforced in the Goodwill domain under the order row lock, not in the shared layer.
4. Reversing one adjustment must not disturb the others: reversal removes that adjustment's allocations and recomputes from the snapshot.

### 2.4 Tax allocation and rounding

**Ordinary tax.** Stop using the blended rate once per-line allocation exists. Derive per line from the line's own frozen posture:

- a line that originally carried tax keeps its own effective rate `tax_before ÷ sub_total`;
- a line that carried none stays untaxed however much it is reduced;
- the residual cent falls to the **last taxed line** by ascending id.

This is equivalent to the blended rate for whole-population proportional adjustments and **remains correct** if a line-scoped target is ever added.

**Special tax.** Recomputed at its **own** rate on **its own** basis — never blended with ordinary tax. Only lines that carried special tax can carry it afterwards; residual cent to the last such line. **This is the fix for §1.3.**

**Added fees.** Never reduced. Recorded in the allocation row so a reversal can prove they were untouched.

**Arithmetic.** Integer cents at the boundary: convert on read, compute in integers, convert once on write. `TaxCalculationService` remains the only tax primitive; its float contract stays recorded as debt.

**Assertion.** The identity in §2.1 must close exactly, or the operation writes nothing.

### 2.5 Reversal

| Step | Behaviour |
|---|---|
| 1 | Lock the order row |
| 2 | Mark the adjustment's allocation rows `reversed_at` |
| 3 | Recompute `pretax_discount_total` as Σ non-reversed allocations |
| 4 | Recompute tax, special tax, grand total from the `*_before_discount` snapshot |
| 5 | Rewrite per-line `pretax_discount_allocated`, `tax`, `special_tax` |
| 6 | Never touch `sub_total`, `added_fees`, `price`, or `product_data` |

Gross values never moved, so reversal restores by **recomputation from an immutable snapshot**, not by replaying deltas. Goodwill additionally records reversal authority, reason, and timestamp in `order_goodwill_adjustments`.

### 2.6 Revised reporting formulas

| Report | Current | Revised |
|---|---|---|
| **Product Sales Performance** | `order_products.sub_total`, documented "discounts are priced-in" — **false today** | `sub_total − pretax_discount_allocated` = **net** revenue. Documentation corrected |
| **Pure Sales Summary** | gross | net of `pretax_discount_total` |
| **Sales Tax Report** | reads `tax_amount` (already post-discount) — **no change**, but re-prove under cash basis | plus `special_tax_amount` once it exists |
| **Payment Reconciliation Ledger** | actual money received | **unchanged** — a concession is not a receipt of money |

Gross sales remain reportable from `subtotal`, which is the point of Decision B: the original sale value is never erased.

### 2.7 Receipts — issued vs. draft

Per Decision C:

| State | Behaviour |
|---|---|
| **Draft** (not yet issued: never emailed, downloaded, or printed) | May refresh in place — production's current behaviour, correct for a document nobody has seen |
| **Issued** | **Immutable.** Any later adjustment or reversal creates a superseding receipt linked to the prior one |

Requires an issued marker. `receipts.is_email_status` and `mail_send_at` exist but only cover email; download and print are not currently recorded. **A dedicated `issued_at` is needed**, set on the first email/download/print, plus the supersession columns (`superseded_receipt_id`, `goodwill_adjustment_id`, `pretax_discount_total`).

Chain: **original → adjusted → restored**, append-only, current = highest `id`, never self-referential, never forked.

---

## Part 3 — Consequences for the port

1. **A shared-layer defect fix now precedes Goodwill.** §1.3 is live and must be corrected on its own merits.
2. **The special-tax/fee columns move earlier** — they are the prerequisite for that fix, not a Goodwill enabler.
3. **`HistoricalTaxBasisResolver` narrows.** With gross values stable and per-line allocations explicit, most of what it reconstructed is now stored. It may reduce to a reconciliation validator, or become unnecessary for the discount path while remaining needed for the refund denominator.
4. **The refund denominator fix stays.** `tax_amount / subtotal` is still wrong, and now more so: post-discount `tax_amount` over gross `subtotal`.
5. **Goodwill's calculation domain shrinks** to determining the *amount* to waive; allocation, tax and persistence move to the shared layer.
6. **`DiscountType::Goodwill` already exists**, marked deferred, and `isOperationalInPhase1()` gates it. Enabling it is a deliberate switch, not new plumbing.

## Part 4 — Revised commit sequence

| # | Commit | Notes |
|---|---|---|
| 1 | `financial: persist current special tax and added fees` | Columns + backfill. Prerequisite for #3 |
| 2 | `financial: add per-line pre-tax discount allocation` | New table + `pretax_discount_allocated` |
| 3 | `financial: correct special tax on pre-tax discounts` | **Fixes the live defect.** Store Credit benefits immediately |
| 4 | `financial: harden shared discount engine to integer cents` | Exact identity; per-line rate replaces blended |
| 5 | `financial: net revenue reporting on allocated discounts` | Product Sales Performance + docs |
| 6 | `financial: fix refund tax basis reconstruction` | Denominator fix |
| 7 | `financial: restore extension child refund basis` | |
| 8 | `financial: enable goodwill discount type` | Authorization, audit, idempotency, reversal, payment linkage |
| 9 | `financial: goodwill apply and reverse with AR/invoice guards` | |
| 10 | `financial: receipt issued-state and supersession` | Requires `issued_at` |
| 11 | `financial: goodwill pending-payment workflow` | |
| 12 | `financial: lifecycle suite` | Store Credit + Goodwill together |
| 13 | `docs: rebuild deployment runbook` | |

Commits 1–5 are shared-engine work that stands on its own merits and delivers a defect fix before any Goodwill code lands.

---

## Part 5 — Open question for the business

**§1.3 is a live defect affecting orders already in production.** Special tax has been over-collected on every Store Credit discount applied to an order carrying special tax.

Two decisions follow, and neither is mine to make:

1. Should the fix ship **ahead** of Goodwill as its own release?
2. Do **already-affected orders** need identifying and remediating? A read-only query can quantify the exposure — orders with `pretax_discount_total > 0` whose `product_data` shows a non-zero `special_tax` — without changing anything.
