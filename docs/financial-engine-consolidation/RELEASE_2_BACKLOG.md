# Release 2 backlog — technical debt register

Everything deferred out of Release 1, recorded so none of it is mistaken for resolved.

Baseline for all estimates: `d8591af5` + Release 1 (`0a7dcaad`).

**Two of these are live defects, not enhancements.** They are marked ⚠.

---

## Summary

| # | Item | Kind | Priority |
|---|---|---|---|
| 1 | Goodwill Adjustment | Feature | High — the original objective |
| 2 | ⚠ Refund taxable-basis defect | **Live defect** | High |
| 3 | `HistoricalTaxBasisResolver` | Prerequisite for #2 | High |
| 4 | SalesTrendReport gross-vs-net | Decision | Medium |
| 5 | Legacy concession reconstruction | Policy + optional project | Medium |
| 6 | `product_data` snapshot integrity | Debt | Medium |
| 7 | ⚠ `Gate::before` authorization bypass | **Security posture** | Business decision |
| 8 | ⚠ `ReceiptService` swallows write failures | **Silent failure** | High |

---

## 1. Goodwill Adjustment

**What.** Manager-authorised discretionary pre-tax concession, applied so an order's revised total equals the payments accepted as payment in full.

**Now much smaller than originally designed.** Release 1's shared engine handles allocation, tax recomputation, persistence, reporting and reversal. Goodwill becomes a **policy layer** deciding *how much* to grant, plus its own authorization, audit, idempotency, reversal and payment-linkage domain.

**Already in place:** `DiscountType::Goodwill` exists, marked deferred and gated by `isOperationalInPhase1()`. Enabling it is a deliberate switch, not new plumbing.

**Constraints carried forward from Release 1 work:**

- A pre-tax concession applies **only while a balance remains**. `OrderDiscountTarget` refuses an order with no remaining balance — a concession after full payment is an overpayment, i.e. a refund workflow, not a discount. Goodwill's design already assumes a remaining balance, so it fits, but any "waive after payment" scenario is out of scope by construction.
- AR-posted and invoiced orders are already refused by the shared engine. Goodwill inherits that.
- Authority must be enforced through Spatie directly — see item 7.

**Prior work worth reusing:** the design in `GOODWILL_ADJUSTMENT_DESIGN.md` and decisions FD-002 Am.1–7, **with the caveat** that FD-002 itself does not exist in this repository and must be ported and re-validated rather than assumed. `GOODWILL_PRODUCTION_REBASELINE_REPORT.md` records exactly which assumptions survived and which did not.

---

## 2. ⚠ Refund taxable-basis defect — LIVE

**Status: OPEN in production. Not fixed by Release 1.**

`PaymentAllocationService::proportionalTaxRefund()` derives its rate as:

```php
$originalTaxRate = (float) $order->tax_amount / (float) $order->subtotal;
```

**`orders.subtotal` is not the taxable basis.** `CartHelper::buildCartItem()` zeroes a line's tax when the product is `is_tax_free_item`, but `subtotal` still accumulates that line. On a mixed order — $100 taxable at 9.75% plus $100 tax-free — this yields **4.875% instead of 9.75%**. A plausible-looking number that is simply wrong, which is why it has gone unnoticed.

**Release 1 made it worse, unavoidably.** After a pre-tax discount, `tax_amount` is reduced while `subtotal` remains gross, so the denominator is now wrong in a second, independent way.

**Effect.** The tax portion of a refund is mis-split on any mixed-tax or discounted order. The customer's total refund is correct; its tax/base split is not, which matters for tax remittance.

**Fix.** Depends on item 3.

---

## 3. `HistoricalTaxBasisResolver`

**Status: NOT PORTED.**

A read-only service that reconstructs an order's true taxable basis from its own lines, replacing the `tax_amount / subtotal` denominator in item 2. Written against the stale repository; **must be re-derived**, not copied, because the production reconciliation identity now includes `pretax_discount_total` and the Release 1 columns.

Its identity must become:

```
grand_total = subtotal − pretax_discount_total + tax_amount
            + special_tax_amount + added_fees_amount − discount_amount
```

**Note.** Release 1 already stores much of what the original resolver had to reconstruct. It may reduce to a reconciliation *validator* rather than a full reconstruction service — worth re-scoping before building.

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

## 5. Legacy concession reconstruction — policy

**Status: policy required; project optional.**

Orders discounted before Release 1 carry `legacy_unallocated_pretax_discount` with no per-line allocation. Consequently:

- product reporting shows **gross** revenue for them;
- the unattributed amount is **disclosed separately**, never absorbed;
- no line-level allocation is invented.

That is honest but leaves a permanent asterisk on historical product reporting, and Goodwill will add a second concession source to the same reports.

**Options:**

1. **Accept permanently.** Disclose the gap; historical product revenue stays gross. Zero risk, permanent caveat.
2. **Reconstruct proportionally.** Distribute each historical concession across that order's lines by gross value — the same rule new allocations use. Defensible, but it manufactures per-line figures no record supports.
3. **Reconstruct only where unambiguous.** Single-line orders can be attributed with certainty; leave the rest disclosed.

**Recommendation: option 3**, with option 1 as the fallback. It attributes exactly what is knowable and invents nothing.

**Related, and quantified separately:** the historical special-tax over-collection measured by `diagnostics:store-credit-special-tax-exposure`. Whether to remediate affected customers is its own business decision, distinct from reporting attribution.

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
| 1 | #3 resolver (re-scoped) | Prerequisite for #2 |
| 2 | #2 refund defect | Live defect; small once #3 exists |
| 3 | #1 Goodwill | The objective, on a proven foundation |
| 4 | #8 receipt error handling | Silent financial write failures; small and self-contained |
| 5 | #6 `product_data` | Low risk, reduces a real hazard |
| — | #4, #5, #7 | Business decisions — resolve in parallel, not blocking |
