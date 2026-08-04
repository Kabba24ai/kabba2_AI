# Gift Cards — Reporting Treatment Analysis

**Date:** 2026-08-03
**Status:** Analysis for approval. **No code changed.**
**Prerequisite for:** the reporting fix, which must ship in the same release as issuance.

Written because the instruction was explicit: do not copy Store Credit's exclusions, and
document how each of the four sites handles **cash collection**, **revenue recognition**,
**liability movement**, and **granted promotional value** before implementing.

Doing that analysis found that **a filter-based exclusion cannot work at all.** That
finding is the substance of this document.

---

## 1. Why Store Credit's exclusion is the wrong template

Store Credit was retired as a tender because it is a **pre-tax discount**. By the time an
order reaches reporting, a Store Credit concession has *already* reduced `subtotal`,
`taxable basis`, `tax_amount` and `grand_total`. The order's canonical figures are lower.
A legacy `StoreCredit` payment row is therefore a **duplicate of a reduction already
recorded**, and deleting it from the payment universe loses nothing.

Gift Card is the opposite in every respect:

| | Store Credit | Gift Card |
|---|---|---|
| Reduces `subtotal` | Yes | **No** |
| Reduces taxable basis | Yes | **No** |
| Reduces `tax_amount` | Yes | **No** |
| Reduces `grand_total` | Yes | **No** |
| Order revenue when applied | Already reduced | **Full, real, recognisable** |
| Cash at time of application | None (never was) | **None — but $ arrived earlier, at issuance** |

A $548.75 order paid with a $500 gift card and $48.75 cash **earned $548.75 of revenue and
owes tax on all of it**. Only the *cash timing* differs: $500 arrived when the card was
bought, possibly months earlier and possibly from a different person.

**Excluding the redemption row the way Store Credit is excluded would delete $500 of real
revenue from the sales reports.** Proven in §3.

---

## 2. The engine fuses cash and revenue — this is the blocking constraint

`SalesReportEngineV2::kpis()` line 349:

```php
$grossCollections = $grossSales + $taxCollected - $discounts;
```

`grossSales` and `taxCollected` are **not independent of cash**. They are derived by
`CollectedRevenueQuery` from *allocated payment amounts* — `ProportionalPaymentSplit`
distributes the order's canonical figures across payment events, and revenue is recognised
in proportion to money applied. The engine's own comment records the identity:

> `gross_collections` … (positive cash in: Σ applied payment amounts + account payments +
> billing charges)

So in this architecture **cash collected and revenue recognised are the same number,
computed once.** That is correct and deliberate for a cash-basis system in which every
tender is real money.

Gift card redemption is the first tender for which it is false:

- Revenue recognised: **$500** (the rental happened)
- Cash collected: **$0** (the cash arrived at issuance)

**No filter can express this,** because a filter can only remove the payment event, and
removing it removes both figures at once. The fix must introduce a **new term into the
identity**, not a predicate into a query.

---

## 3. What a naive exclusion would actually do

Order: subtotal $500.00, tax $48.75, grand total $548.75.
Tender: $500.00 gift card + $48.75 cash.

**If `GiftCard` were added to the `!=` list at `CollectedRevenueQuery.php:283`:**

Only the $48.75 cash row qualifies. `ProportionalPaymentSplit::applied(548.75, [48.75])`
applies **$48.75** of a $548.75 order — 8.9%. Allocation yields roughly:

| Figure | Correct | After naive exclusion | Error |
|---|---|---|---|
| Revenue (`base`) | $500.00 | **$44.42** | **−$455.58** |
| Tax collected | $48.75 | **$4.33** | **−$44.42** |
| Cash collected | $48.75 | $48.75 | ✅ correct |

Cash would be right and **91% of the revenue and the sales tax would vanish** — including
tax the business genuinely owes and must remit. This is materially worse than the
double-count it was meant to fix: the double-count overstates cash, this understates a
tax liability.

**Conclusion: the four `!=` sites must not be touched. `GiftCard` stays in the qualifying
payment universe so revenue and tax allocate correctly.**

---

## 4. The correct model — two opposite, symmetric adjustments

A gift card's life has two money events that must be counted **exactly once each, in
different periods and different columns**.

### Event 1 — Issuance (purchased card)
Customer pays $500 by credit card.

- **Cash collection: YES** — $500 real money, real Authorize.Net transaction, must
  reconcile to settlement.
- **Revenue recognition: NO** — nothing has been sold. No product, no rental, no service.
- **Liability: +$500** purchased-card liability.
- **Sales tax: NONE.**

### Event 2 — Redemption
Card applied to a $548.75 order.

- **Cash collection: NO** — no money moves. Counting it double-counts Event 1.
- **Revenue recognition: YES** — $500 of rental revenue, taxed normally.
- **Liability: −$500** purchased-card liability drawn down.
- **Sales tax: YES**, on the full order, unchanged by the tender.

Over the card's life: **$500 cash, counted once, at issuance. $500 revenue, counted once,
at redemption. Liability +500 then −500 → zero.** Correct and complete.

### Granted cards (per the approved decision)
Same *tax and revenue* treatment as purchased — tender applied after tax, order fully taxed.
Different *funding* treatment:

- Issuance: **no cash, no revenue, no purchased-card liability.** Records
  `promotional_value_issued`.
- Redemption: **no cash. Revenue YES** (the rental happened). Draws down
  `promotional_value`, **never purchased-card liability**.

Granted redemption must therefore be distinguishable from purchased redemption at every
site — a single "gift card redeemed" total would silently merge merchant-funded promotion
with customer-prepaid liability.

---

## 5. Site-by-site treatment matrix

The required deliverable. `CollectedRevenueQuery` is upstream of the other three: it emits
the rows they all sum, so it is where the classification originates.

### 5.1 `CollectedRevenueQuery` — the upstream allocator

| Concern | Treatment |
|---|---|
| **Cash collection** | Row keeps `amount`/`applied` for allocation. **New derived field `is_cash_tender` (bool)** — false for `GiftCard`. Consumers sum cash only over cash tenders. |
| **Revenue recognition** | **Unchanged.** `GiftCard` stays in `qualifyingPayments()`. `base`, `tax` and the `lines` decomposition allocate exactly as today. |
| **Liability movement** | Not represented here. `gift_card_transactions` is the ledger; this engine reads orders, not cards. |
| **Granted promotional value** | **New derived field `non_cash_tender_class`**: `null` \| `gift_card_purchased` \| `gift_card_granted`, resolved by joining the redemption transaction to its card's `issuance_class`. |

Additive only: two new fields on the emitted row, no existing field's value changes.
Every current consumer that ignores them behaves exactly as it does today.

### 5.2 `SalesReportEngineV2` — KPIs

| Concern | Treatment |
|---|---|
| **Cash collection** | Identity gains a subtrahend: `gross_collections = grossSales + taxCollected − discounts − giftCardRedeemed`. This is the only way to keep revenue whole while removing non-cash tender. |
| **Revenue recognition** | **Unchanged.** `gross_sales`, `net_sales`, `tax_collected` continue to include gift-card-funded revenue — it is real. |
| **Liability movement** | New KPIs, sourced from `gift_card_transactions`, not from orders: `gift_card_liability_issued`, `gift_card_liability_redeemed`, `gift_card_liability_outstanding`. |
| **Granted promotional value** | Separate KPIs: `promotional_value_issued`, `promotional_value_redeemed`. Never added into `gift_card_liability_*`. |

`gift_card_redeemed` is surfaced as its own KPI, following the **exact precedent of
`overpayments`** — already "surfaced separately; deliberately NOT part of
gross/net/tax/total_collected" (line 373-375). The engine already has a slot for
"money on a row that is not collections". This is its mirror image.

### 5.3 `SalesTaxReportEngine`

| Concern | Treatment |
|---|---|
| **Cash collection** | Not this engine's subject. |
| **Revenue recognition** | **Unchanged — and this is the point.** Taxable sales must include gift-card-funded sales in full. A $500 gift-card rental owes exactly the tax a $500 cash rental owes. |
| **Liability movement** | **None. Deliberately.** Gift-card issuance is not a taxable sale; it must never appear as taxable receipts. Since issuance creates no order product lines, it is already excluded structurally (see §6). |
| **Granted promotional value** | Also fully taxable. Under the approved tender-with-tax model a granted card does not reduce taxable receipts. |

**No change required here** — which is the correct outcome and worth stating explicitly, so
a future reader does not "fix" it by adding an exclusion.

### 5.4 `PaymentReconciliationLedger`

| Concern | Treatment |
|---|---|
| **Cash collection** | Stream A currently emits every qualifying payment. A gift-card redemption row must **not** carry cash `grand_total`, or the ledger will expect an Authorize.Net settlement that does not exist. |
| **Revenue recognition** | Row is **retained**, with `grand_total = 0` and revenue/tax visible in their own columns — so the event is auditable and the redemption is not silently missing from the order's payment history. |
| **Liability movement** | **New Stream E — `gift_card`**, carrying the *funding* payment for a purchased card: real cash, real gateway transaction, reconciles to settlement, **zero revenue and zero tax**. |
| **Granted promotional value** | No stream row. No cash, no gateway transaction, nothing to reconcile. Reported only via the liability KPIs in §5.2. |

The ledger already has a `stream` discriminator (`order` / `refund` / `account` /
`billing`), so a fifth stream is the architecturally native extension, not a bolt-on.

---

## 6. The one identity that must be re-proved

`docs`-level invariant, asserted by
`tests/Feature/Reports/CashBasisCrossSurfaceReportingTest.php`:

> Σ ledger `grand_total` == `kpis()['net_collections']`

Both sides change, and they must change **consistently**:

- Ledger: redemption rows contribute `0`; new Stream E contributes the funding cash.
- KPIs: `gross_collections` loses `giftCardRedeemed`; funding cash enters through Stream E.

**This identity is the single best regression test for the whole change** and must be
extended to cover: purchased issuance, redemption, granted redemption, and a mixed
gift-card + cash order — before any gift-card write path exists.

---

## 7. Open question for Phase 1 — how a purchased card is *sold*

The treatment above assumes the funding payment is reachable. Two options, with real
reporting consequences:

**(a) Sell as an Order with a gift-card line.** Funding flows through the existing payment
path and Authorize.Net reconciliation for free. **But** `qualifyingPayments()` requires
`order_products` to exist, so the sale would enter `CollectedRevenueQuery` and be counted as
**taxable product revenue** — precisely the first error the brief lists. It would need
explicit exclusion from revenue while remaining in cash: the inverse of the redemption fix.

**(b) Sell as a standalone `gift_cards` record with its own payment.** Structurally outside
the order system, so it can never contaminate sales or tax reporting — the `order_products`
requirement excludes it automatically. Requires Stream E to make the cash reconcilable.

**Recommendation: (b).** It gets the default right (invisible to sales/tax reporting) and
makes the cash visible through one deliberate, purpose-built stream — rather than getting
the default wrong and needing a second exclusion to correct it. It also avoids inventing a
fake "gift card product" in the catalogue.

Confirm (b) and I will build Phase 1 against it.

---

## 8. Implementation order

1. Extend `CashBasisCrossSurfaceReportingTest` with the four gift-card scenarios — **failing
   first**, before any write path exists.
2. Add `is_cash_tender` + `non_cash_tender_class` to `CollectedRevenueQuery` rows (additive).
3. Add the `giftCardRedeemed` subtrahend and the liability/promotional KPIs to V2.
4. Zero the `grand_total` of redemption rows in Stream A; add Stream E.
5. Assert `SalesTaxReportEngine` is unchanged, with a test that would fail if someone
   later adds an exclusion there.
6. Re-run the full reporting suite against the recorded baseline.

**Nothing in steps 1–6 touches the four `!=` predicates.** They stay exactly as they are.
