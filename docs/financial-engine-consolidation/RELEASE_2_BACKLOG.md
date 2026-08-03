# Release 2 backlog — technical debt register

Everything deferred out of Release 1, recorded so none of it is mistaken for resolved.

> ## ⚠ Standing policy: forward-only corrections
>
> **No item in this register may be addressed by rewriting historical financial
> records.** No migration, backfill, repair job, reconciliation process or
> automatic adjustment that changes past refunds, payments, receipts or
> allocations. A corrected calculation applies to transactions created after it
> ships; records already written stay exactly as they were processed.
>
> Issuing a **new** transaction today to correct a customer's position remains
> available — that is a forward correction with its own date and its own record.
>
> Full statement: `FINANCIAL_DECISIONS.md` **G-10**. This closed item 5 and
> bounds items 2, 4 and 6.

Baseline for all estimates: `d8591af5` + Release 1 (`0a7dcaad`).

**Four of these are live defects or gaps, not enhancements** — items 7, 8, 9 and 11. They are marked ⚠.

Items 1, 2, 3 and 5 were closed by the Goodwill release.

---

## Summary

| # | Item | Kind | Priority |
|---|---|---|---|
| 1 | ~~Goodwill Adjustment~~ | ✅ **DELIVERED** — G1–G4 | Closed |
| 2 | ~~Refund taxable-basis defect~~ | ✅ **FIXED** — Goodwill release | Closed |
| 3 | ~~`HistoricalTaxBasisResolver`~~ | ✅ **DELIVERED** as `TaxableBasisResolver` | Closed |
| 4 | SalesTrendReport gross-vs-net | Decision | Medium |
| 5 | ~~Legacy concession reconstruction~~ | ✅ **CLOSED** by G-10 — disclose, never backfill | Closed |
| 6 | `product_data` snapshot integrity | Debt | Medium |
| 7 | ⚠ `Gate::before` authorization bypass | **Security posture** | Business decision |
| 8 | ⚠ `ReceiptService` swallows write failures | **Silent failure** | High |
| 9 | ⚠ Invoiced orders are not refused by the discount engine | **Live gap** | Medium |
| 10 | Receipt supersession (`issued_at`) — draft decision C | Deferred decision | Medium |
| 11 | ⚠ Gateway call inside an open database transaction | **Live risk** | High |

---

## 1. Goodwill Adjustment

**What.** Manager-authorised discretionary pre-tax concession, applied so an order's revised total equals the payments accepted as payment in full.

**Now much smaller than originally designed.** Release 1's shared engine handles allocation, tax recomputation, persistence, reporting and reversal. Goodwill becomes a **policy layer** deciding *how much* to grant, plus its own authorization, audit, idempotency, reversal and payment-linkage domain.

**Already in place:** `DiscountType::Goodwill` exists, marked deferred and gated by `isOperationalInPhase1()`. Enabling it is a deliberate switch, not new plumbing.

**Constraints carried forward from Release 1 work:**

- A pre-tax concession applies **only while a balance remains**. `OrderDiscountTarget` refuses an order with no remaining balance — a concession after full payment is an overpayment, i.e. a refund workflow, not a discount. Goodwill's design already assumes a remaining balance, so it fits, but any "waive after payment" scenario is out of scope by construction.
- AR-posted orders are already refused by the shared engine. **Invoiced orders are not** — that claim was wrong and is corrected as item 9. Goodwill enforces the invoice guard itself.
- Authority must be enforced through Spatie directly — see item 7.

**Status: DELIVERED.** Built as increments G1–G4 against the deployed shared engine. Design in `GOODWILL_ADJUSTMENT_DESIGN.md`, decisions G-1…G-10 in `FINANCIAL_DECISIONS.md`, deployment in `GOODWILL_DEPLOYMENT_RUNBOOK.md`, summary in `GOODWILL_RELEASE_NOTES.md`. 129 tests, 636 assertions.

**Prior work NOT reused:** the stale standalone calculation engine is discarded, not ported. `GOODWILL_PRODUCTION_REBASELINE_REPORT.md` records which assumptions survived.

---

## 2. ✅ Refund taxable-basis defect — FIXED (Goodwill release)

**Status: CLOSED.** Corrected by `TaxableBasisResolver` (item 3), shipped with
Goodwill after being escalated from follow-up to a release blocker.

**Forward-only. Nothing further is outstanding on this item.** The description
below is kept as the historical record of what the defect was, not as work
remaining.

`PaymentAllocationService::proportionalTaxRefund()` derived its rate as:

```php
$originalTaxRate = (float) $order->tax_amount / (float) $order->subtotal;
```

**`orders.subtotal` is not the taxable basis.** `CartHelper::buildCartItem()` zeroes a line's tax when the product is `is_tax_free_item`, but `subtotal` still accumulates that line. On a mixed order — $100 taxable at 9.75% plus $100 tax-free — this yields **4.875% instead of 9.75%**. A plausible-looking number that is simply wrong, which is why it has gone unnoticed.

**Release 1 made it worse, unavoidably.** After a pre-tax discount, `tax_amount` is reduced while `subtotal` remains gross, so the denominator is now wrong in a second, independent way.

**Effect.** The tax portion of a refund is mis-split on any mixed-tax or discounted order. The customer's total refund is correct; its tax/base split is not, which matters for tax remittance.

**The fix.** `PaymentAllocationService::proportionalTaxRefund()` now derives its
rate from `TaxableBasisResolver::effectiveTaxRate()`:

```
taxable_basis = taxable_gross x (subtotal - pretax_discount_total) / subtotal
rate          = tax_amount / taxable_basis
```

where `taxable_gross` sums only the lines whose FROZEN `product_data` snapshot
shows they were taxable at checkout. Both error sources are removed at once: the
tax-free lines leave the denominator, and the denominator now moves with the
concession exactly as the numerator does.

**Effective for refunds processed after deployment only.** Refunds already
issued keep the split they were processed with, permanently. They match the
receipts customers hold, the deposits they settled against and the filings they
were reported on, and they are never recalculated.

**No remediation script, backfill or exposure report is to be built for them.**
Standing policy — see `FINANCIAL_DECISIONS.md` **G-10**.

---

## 3. ✅ `HistoricalTaxBasisResolver` — DELIVERED as `TaxableBasisResolver`

**Status: CLOSED.** Re-derived rather than ported, and deliberately narrower
than the original design.

A read-only service that reconstructs an order's true taxable basis from its own lines, replacing the `tax_amount / subtotal` denominator in item 2. Written against the stale repository; **must be re-derived**, not copied, because the production reconciliation identity now includes `pretax_discount_total` and the Release 1 columns.

Its identity must become:

```
grand_total = subtotal − pretax_discount_total + tax_amount
            + special_tax_amount + added_fees_amount − discount_amount
```

**It did reduce in scope, as anticipated.** Release 1 already stores most of what
the original design had to reconstruct, so `app/Services/Orders/TaxableBasisResolver.php`
answers exactly one question — what merchandise value generated `tax_amount` —
and nothing else. It validates no identity and refuses no operation.

**Line-less orders.** An extension child's `subtotal` IS its discounted taxable
base by construction, so that shape resolves explicitly. Any other line-less
order returns `null`, and the caller reports zero tax and logs. There is
deliberately **no** generic `tax_amount / subtotal` fallback: reinstating the
known-wrong denominator for the least trustworthy records is what the fix
exists to prevent.

**Carried-forward lesson.** An earlier version refused any order carrying an active adjustment. Because that resolver also serves refunds, it made every adjusted order **permanently un-refundable**. Preventing a second adjustment belongs in the writer, under the row lock — not in a read-only reconstruction. Recorded as FD-002 Amendment 7.

---

## 4. SalesTrendReport — gross or net?

**Status: flagged, deliberately unchanged.**

`SalesTrendReport` sums raw `order_products.sub_total`, labels it `revenue`, and has **no refund awareness at all** (predating this work). After Release 1 it disagrees with the five reports that share `NetsRefundedRevenue`.

**The intent cannot be established from the code.** Its docblock says only *"How is revenue / transaction volume / avg ticket trending over time?"*. Neighbouring reports are explicit either way: `SalesReportEngineV2` names its figures `gross_sales`/`daily_gross`; the five netted reports subtract allocated concessions.

**The decision, for the business:**

- **If trend means net** — adopt `NetsRefundedRevenue`, which also fixes its missing refund netting. Historical trend lines will shift downward where discounts or refunds occurred.
- **If trend means gross** — rename the metric to `gross_revenue` so it stops silently disagreeing with product reporting.

Either way the missing refund awareness should be addressed. **Do not hold Release 1 for this.**

---

## 5. Legacy concession reconstruction — RESOLVED BY POLICY

**Status: CLOSED by `FINANCIAL_DECISIONS.md` G-10 (forward-only corrections).**

Options 2 and 3 below are **backfills of historical financial records** and are
therefore no longer available. **Option 1 — accept permanently and disclose — is
the outcome.** The options are kept for the record so it is clear the choice was
made rather than overlooked.

Orders discounted before Release 1 carry `legacy_unallocated_pretax_discount` with no per-line allocation. Consequently:

- product reporting shows **gross** revenue for them;
- the unattributed amount is **disclosed separately**, never absorbed;
- no line-level allocation is invented.

That is honest but leaves a permanent asterisk on historical product reporting, and Goodwill will add a second concession source to the same reports.

**Options:**

1. **Accept permanently.** Disclose the gap; historical product revenue stays gross. Zero risk, permanent caveat.
2. **Reconstruct proportionally.** Distribute each historical concession across that order's lines by gross value — the same rule new allocations use. Defensible, but it manufactures per-line figures no record supports.
3. **Reconstruct only where unambiguous.** Single-line orders can be attributed with certainty; leave the rest disclosed.

**Outcome: option 1.** Options 2 and 3 would write per-line values onto orders
that already closed, which G-10 prohibits. The gap stays disclosed rather than
reconstructed: historical product revenue reads gross, the unattributed
concession is reported separately, and no per-line figure is invented for an
order that never recorded one.

**Related, and unaffected:** the historical special-tax over-collection measured
by `diagnostics:store-credit-special-tax-exposure`. That diagnostic is read-only
and stays. Deciding to make an affected customer whole is a **new transaction
today** — a credit or refund with its own record and date — not a rewrite of the
original order, so it remains available under G-10 and is a business decision.

---

## 6. `product_data` snapshot integrity

**Status: debt, no current harm.**

`product_data` is described throughout as an immutable checkout snapshot. It is not. `Admin\Tests\IndexController` (~line 1009) runs a maintenance backfill that **rewrites the JSON**, injecting `rental_prepaid_cleaning` and `is_product_clean`.

**No present harm** — it touches neither `special_tax` nor `added_fees`, so Release 1's backfill inputs were intact (verified, not assumed). The risk is **precedent**: a document treated as writable by one route will eventually be written by another, and the financial fields have no protection beyond convention.

**Work:**

1. Narrow the route to write its two dedicated columns and stop rewriting the JSON — both columns already exist, so the JSON write appears redundant.
2. Correct every docblock claiming `product_data` is never modified.
3. Consider a model guard rejecting writes that would alter `special_tax` or `added_fees`.
4. One read-only check that the route preserved unrelated keys rather than replacing the document. It reads `?? []` and adds keys, so it should have — but "should" is not "did".

---

## 7. ⚠ `Gate::before` authorization bypass — business decision

**Status: pre-existing, unchanged, outside every release so far.**

`AppServiceProvider::gatesRegistration()` registers:

```php
Gate::before(fn ($user, string $ability) => true);
```

**Every `can()` check and every ability-based route middleware returns true for any signed-in user, application-wide.** It is documented as a deliberate small-business posture, with a comment describing how to undo it.

**Consequences:**

- Ability-based authorization is decorative everywhere.
- Any feature needing genuine authority separation must consult Spatie directly (`hasPermissionTo()`), bypassing the Gate. Goodwill will need exactly this — authority to receive a payment must not imply authority to reduce revenue.
- `ModuleSeeder` — which would be run to restore granular roles — is a **destructive reconciliation seeder** that deletes any module, category or permission absent from its hardcoded list. Proven empirically: three planted rows were all removed by a single re-run. Deleting a Spatie permission cascades through role and user assignments, revoking access silently.

**This is a business decision, not a technical one.** Restoring granular permissions means deciding who may do what, then repairing `ModuleSeeder` before it can safely run. Recorded so it is a known posture rather than an assumed protection.

---

## 8. ⚠ `ReceiptService::getOrCreateReceipt()` swallows exceptions

**Status: pre-existing, confirmed live, deliberately not fixed in the receipt patch.**

The whole method body is wrapped in `try/catch (Throwable)` that logs and returns `null`. A caller receives no receipt and no indication anything failed.

**This is not theoretical.** While adding the charge-component columns, an in-memory `Order` model reported `null` for the new fields and the insert violated a `NOT NULL` constraint. Receipt creation failed **completely silently** — the only evidence was one line in `laravel.log`. It surfaced through an unrelated pre-existing test, not through anything in the calling path.

In production the same shape means: a customer's receipt is never created, nothing surfaces to the operator, and it is discovered when someone asks for a document that does not exist.

**Requirements for the fix:**

1. **Distinguish expected from unexpected.** A genuinely absent order is not the same as a failed write. Returning `null` for the first is reasonable; for the second it hides a defect.
2. **Alert on write failure**, not merely log. A `Log::error` in a file nobody watches is not a signal.
3. **Surface it to the caller.** Every call site assumes success — `$receipt->subtotal` on a null return is fatal one line later, which is exactly how the test failed.
4. **Consider letting write failures propagate** so an enclosing transaction rolls back rather than continuing with half-built state.
5. **Audit other swallow-and-return-null handlers** in financial services for the same pattern.

Out of scope for the receipt patch, which was narrow by instruction. Logged so this is a known risk rather than an assumed safety net.

---

## Suggested sequencing for Release 2

| Order | Item | Rationale |
|---|---|---|
| ✅ | #3 resolver (re-scoped) | Delivered as `TaxableBasisResolver` |
| ✅ | #2 refund defect | Fixed; shipped with Goodwill as a release blocker |
| ✅ | #1 Goodwill | Delivered |
| 4 | #8 receipt error handling | Silent financial write failures; small and self-contained |
| 5 | #6 `product_data` | Low risk, reduces a real hazard |
| 6 | #9 invoice guard | Small, but changes an existing feature's eligibility rules |
| — | #4, #5, #7, #10 | Business decisions — resolve in parallel, not blocking |

---

## 9. ⚠ Invoiced orders are not refused by the discount engine

**Status: pre-existing, confirmed live, deliberately not fixed.**

`OrderDiscountTarget::ineligibleReason()` checks two things: whether the order
has posted to the Credit Account, and whether a balance remains. It does **not**
check `orders.invoice_id`.

An order that has been placed on an invoice (`Crm/Customers/Invoice/StoreController`
sets `orders.invoice_id`) can therefore still receive a Store Credit pre-tax
discount. The order's `grand_total` moves; the invoice's `total`, `open_amount`
and its `invoice_items` do not. The two silently disagree from that point on.

**Not fixed here** because the Goodwill work was scoped not to broaden into
Store Credit's eligibility rules. Goodwill enforces the guard in its own policy
layer, so the new feature is safe; the existing exposure remains.

**The fix is small** — add the check to `ineligibleReason()` — but it changes
behaviour for an existing operational feature and needs its own regression pass
and a decision about already-affected orders.

---

## 10. Receipt supersession (`receipts.issued_at`) — draft decision C, deferred

**Status: decided in draft, never implemented.**

Draft decision C states that a receipt which has been **issued** is immutable,
and that a later adjustment produces a **superseding** receipt linked to the
prior one. `receipts.issued_at` was never added, and Release 1 shipped
`ReceiptService`'s in-place refresh instead.

Consequences accepted for now:

- A receipt already printed or emailed restates itself on the next read. The
  evidence of what the customer was originally shown is not retained.
- `receipts.pretax_discount_total` carries no attribution, so an order with two
  *different* adjustment types cannot name them on the document — it falls back
  to the truthful generic `Pre-Tax Discounts`.

**Work:** add `issued_at` (set on print/download/email, not just email — the
existing `is_email_status` / `mail_send_at` cover email only), branch the
refresh on it, add the supersession link, and add per-type snapshot columns or a
receipt-adjustment child table to close the attribution gap.

Recorded so decision C is not mistaken for delivered.

---

## 11. ⚠ Authorize.Net is called inside an open database transaction — LIVE

**Status: pre-existing, confirmed live, deliberately NOT changed in G2.**

`ReceivePaymentController` opens a transaction (`DB::beginTransaction()`, ~L56),
calls `AuthorizeNetService` (~L117 for a saved profile, ~L157 for a new card),
creates the payment row, and commits at ~L270. The gateway call therefore sits
inside the transaction, and the transaction stays open for the entire round trip
to an external network service.

**Why it has not bitten yet.** Every statement before the gateway call is a
plain `SELECT`, and plain selects take no row locks. The controller holds an
open read view but no locks during the call, so nothing else blocks on it today.

**Why it matters more now.** Goodwill's apply is the first path that takes locks
on `orders` and `order_payments` for a specific order. The two are compatible as
they stand — verified by the G2 concurrency tests — but the pattern is one
refactor away from being dangerous: the moment anything in that controller
acquires a row lock *before* the gateway call, that lock is held for the full
network round trip, and every other writer for that order queues behind an
external service's latency. A gateway timeout would then hold it for the timeout
duration.

**Secondary effects that exist today:**

- an open transaction spanning a network call holds its read view, so InnoDB
  cannot purge undo records behind it for the duration;
- a gateway call that hangs consumes a connection *and* a transaction slot;
- a rollback after a **successful** gateway authorization leaves money captured
  with no payment row — the failure mode this ordering makes possible.

**Requirements for the fix:**

1. Move the gateway call **outside** the transaction. Authorize, then open a
   short transaction to persist the result.
2. Reconcile the window that creates: an authorization succeeding while the
   subsequent write fails must be detectable and recoverable, not silent. The
   existing `idempotency_token` column and cache lock are the foundation.
3. Keep the transaction to local statements only, so its duration is bounded by
   the database rather than by a third party.
4. Audit the other payment surfaces — `Front\Checkout\OrderPaymentController`,
   `PaymentShortLinkController`, `RefundPaymentController` — for the same shape.

**Out of scope for G2 by instruction.** Goodwill does not modify the payment
flow, and its own transaction contains no gateway call, HTTP request or queue
dispatch — asserted by a test using a strict mock that throws on any call.
