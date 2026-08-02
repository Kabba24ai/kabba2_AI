# Release 1 — Shared pre-tax adjustment engine and Store Credit special-tax correction

**Status: DRAFT, awaiting approval. Goodwill is explicitly OUT of this release.**

Baseline: `d8591af5` (`production/raj_development`). Governing decisions: A / B / C plus the four approvals of 2026-08-02. Design: `SHARED_PRETAX_ADJUSTMENT_DESIGN.md`. Audit: `GOODWILL_PRODUCTION_REBASELINE_REPORT.md`.

> **Governing principle:** gross merchandise values remain stable; discounts are explicit; tax and net revenue follow the allocated discounts.

---

## 1. Why this release exists on its own

`OrderDiscountTarget::recompute()` preserves special tax unchanged when a pre-tax discount reduces the merchandise basis. Special tax is basis-derived, so it must shrink with the basis. **It does not, and customers are over-charged.**

This is live in production today on every Store Credit discount applied to an order carrying special tax. It is not caused by Goodwill and must not wait for it.

The fix is impossible without the special-tax and added-fee columns, because the engine currently lumps special tax, added fees, delivery and coupon into one undifferentiated residual and cannot reduce one while preserving another. That dependency is what sets the order of the increments below.

---

## 2. Scope

### In

| Item | Purpose |
|---|---|
| Current special-tax and added-fee storage | `orders.special_tax_amount` / `added_fees_amount`; `order_products.special_tax` / `added_fees`; backfilled from frozen `product_data` under an exact-residual gate |
| Line-level pre-tax discount allocations | `order_products.pretax_discount_allocated` + `order_product_discount_allocations` |
| Hardened shared discount calculation | Integer cents; per-line tax derivation replacing the blended rate; separately-derived ordinary and special tax; protected fees |
| Store Credit migration to that calculation | Store Credit becomes the first consumer of the hardened engine |
| **Special-tax defect correction** | The reason for the release |
| Reporting updates | Net revenue = gross − allocated discounts; `ProductSalesPerformanceEngine` and its documentation corrected |
| Issued-receipt lifecycle support | `receipts.issued_at`; draft may refresh, issued is immutable |
| Focused lifecycle and regression tests | Including a Store-Credit-with-special-tax lifecycle |

### Out — deferred to Release 2

Goodwill in every form: `order_goodwill_adjustments`, the calculation domain, apply/reverse, AR and invoice guards, receipt supersession, the pending-payment UI, `GoodwillPermissionSeeder`, and `DiscountType::Goodwill` remaining gated by `isOperationalInPhase1()`.

Also out: the refund taxable-basis fix (`tax_amount / subtotal`) and `HistoricalTaxBasisResolver`. Both are still needed, but they are refund-path work and do not gate the defect fix. Moving them out keeps this release to one coherent subject.

---

## 3. Increment sequence

Each stops before commit and reports files changed, semantic conflict resolutions, focused test results, broad-suite comparison against the §5 baseline, pre-existing failures separately, and confirmation that no production database or configuration was touched.

| # | Commit | Gate before proceeding |
|---|---|---|
| 1 | `financial: persist current special tax and added fees` | Backfill audit gate passes on production data; checkout writes all three (line columns, order aggregate, frozen JSON); frozen JSON never written |
| 2 | `financial: add per-line pre-tax discount allocation` | Σ line allocations = `pretax_discount_total`, to the cent, for every existing discounted order |
| 3 | `financial: harden shared discount engine to integer cents` | Identity closes exactly or nothing is written; per-line rate replaces blended; existing Store Credit results unchanged where they were already correct |
| 4 | `financial: correct special tax on pre-tax discounts` | **The defect fix.** Exposure audit re-run shows zero NEW exposure going forward |
| 5 | `financial: net revenue reporting on allocated discounts` | Product Sales Performance reflects allocated discounts; gross still reportable from `subtotal`; documentation no longer claims discounts are "priced-in" |
| 6 | `financial: receipt issued state and lifecycle` | `issued_at` set on first email/download/print; draft refreshes, issued does not |
| 7 | `financial: shared pre-tax lifecycle suite` | Store Credit with special tax, mixed taxable/exempt, stacking, reversal, tax-exempt |
| 8 | `docs: shared pre-tax deployment runbook` | Rebuilt against this chain and the §5 baselines |

Increment 1 is a strict prerequisite for 4. Increment 2 is a prerequisite for 3 and 5.

---

## 4. Exposure audit — built and verified

`php artisan diagnostics:store-credit-special-tax-exposure --show-ids [--csv=path]`

Strictly read-only: every operation is a `SELECT`. No writes, no migration, no queue dispatch, no HTTP call. Safe against production; identical output on repeat runs.

**Sources of truth, per instruction:** each line's frozen `order_products.product_data` for the special tax and added fees actually charged, and the stored adjustment values on the order (`subtotal`, `pretax_discount_total`, `*_before_discount`). It never reads the `products` table, `settings`, or any current tax configuration.

**Arithmetic.** A pre-tax discount reduces the whole merchandise population proportionally by `f = (subtotal − pretax_discount_total) ÷ subtotal`. Special tax is linear in the basis, so the correct figure is the frozen special tax scaled by `f`. Integer cents throughout.

**Reports per order:** order ID and number, date, Store Credit amount, stored special tax, corrected special tax, over-collection, added fees, payment status, total paid, refund count, AR row count, invoiced yes/no, receipt state, and a verdict of `SAFE` or `WORKFLOW` with the specific blockers. Plus aggregate over-collection and disposition counts.

**Correctability rule.** `WORKFLOW` when any of: AR posted · invoiced · refunded · a receipt exists · paid in full (correcting would create a credit owed to the customer). Otherwise `SAFE`.

**Verified** against seeded fixtures on `rc_kabba_testing`:

| Order | Basis | Credit | Stored special | Corrected | Over | Verdict |
|---|---|---|---|---|---|---|
| EXP-1 | 200.00 | 50.00 | 4.00 | 3.00 | **1.00** | SAFE |
| EXP-2 | 100.00 | 25.00 | 5.00 | 3.75 | **1.25** | WORKFLOW — receipt issued, paid in full |
| EXP-3 | 100.00 | 25.00 | 0.00 | — | — | correctly excluded |

A partly unreadable frozen snapshot is counted and flagged: the reported figure becomes a **floor**, not an exact total.

**Not yet run against production.** Both local databases are empty. The first production run is the first real measurement.

---

## 5. Test baseline on `d8591af5`

The stale-branch baselines are void. These are the reference for this release.

| Suite | Failing / Total |
|---|---|
| `tests/Feature/Reports` | 23 / 123 |
| `tests/Feature/BillingEngine` | 47 / 206 |
| `tests/Feature/OrderManagement` | 6 / 85 |
| `tests/Unit/Services` | 3 / 210 |
| `tests/Feature/Billing` | 1 / 63 |
| Refund + extension (11 files) | 2 / 131 |
| `tests/Feature/Cart` | 0 / 23 |

`tests/Feature/Orders` as a whole exhausts the 128M limit; run by file group.

---

## 6. Deployment shape

1. **Exposure audit first**, read-only, output preserved with the release evidence. It measures existing exposure; it is not a gate on deploying the fix, since the fix stops the bleeding regardless.
2. **Backfill audit gate** (`diagnostics:special-tax-backfill-audit`) — this *is* a hard gate, unchanged: `missing_json_unexplained`, `unreconciled`, and `lineless_with_nonzero_residual` must all be zero before migrating.
3. Backup, verified independently, outside the application tree.
4. Migrate, rebuild caches, restart workers.
5. Post-migration verification, including Σ line allocations = `pretax_discount_total`.
6. Smoke tests: ordinary checkout · special-tax checkout · added-fee checkout · Store Credit on a special-tax order (**the defect scenario**) · reversal · reporting.

`ModuleSeeder` is still destructive and must not be run. No new permissions are needed in this release.

---

## 7. Remediation of historical orders — NOT in this release

Per instruction, no historical order is rewritten automatically. The exposure audit measures; it does not remediate.

Once the production figures exist, the decisions are:

1. Are the `SAFE` rows corrected in place, and by what mechanism?
2. What workflow handles the `WORKFLOW` rows, where money has already been reported to a customer through a receipt, an invoice, or a settled payment?
3. Is there a materiality threshold below which no action is taken?

All three are business decisions. Remediation code will not be written without approval.

---

## 8. Release 2 — Goodwill (after this ships and is validated)

| # | Commit |
|---|---|
| 1 | `financial: add historical tax basis resolver` |
| 2 | `financial: fix refund tax basis reconstruction` |
| 3 | `financial: restore extension child refund basis` |
| 4 | `financial: enable goodwill discount type` |
| 5 | `financial: goodwill apply and reverse with AR/invoice guards` |
| 6 | `financial: goodwill receipt supersession` |
| 7 | `financial: goodwill pending-payment workflow` |
| 8 | `financial: goodwill lifecycle suite` |
| 9 | `docs: goodwill deployment runbook` |

Goodwill's calculation domain is materially smaller than in the stale design: it decides *how much* to waive and owns authorization, audit, idempotency, reversal and payment linkage. Allocation, tax and persistence belong to the shared engine this release delivers.

---

## 9. DEFERRED — open defects, explicitly NOT resolved by this release

Recorded so these are not mistaken for closed. Each is a **known, live problem** that Release 1 deliberately does not touch.

### 9.1 Refund taxable-basis defect — OPEN

`PaymentAllocationService::proportionalTaxRefund()` derives its rate from:

```php
$originalTaxRate = (float) $order->tax_amount / (float) $order->subtotal;
```

`orders.subtotal` is **not** the taxable basis. `CartHelper::buildCartItem()` zeroes a line's tax when the product is `is_tax_free_item`, but `subtotal` still accumulates that line. On a mixed order — $100 taxable at 9.75% plus $100 tax-free — this yields 4.875% instead of 9.75%: a plausible-looking number that is simply wrong, so the defect does not announce itself.

**Compounded by pre-tax discounts.** After a Store Credit discount, `tax_amount` has been reduced while `subtotal` has not, so the denominator is now wrong in a second, independent way.

**Effect:** the tax portion of a refund is mis-split on any mixed-tax or discounted order. **Status: OPEN, live in production, scheduled for Release 2 commit 2.**

### 9.2 `HistoricalTaxBasisResolver` — NOT PORTED

The read-only reconstruction service that replaces the above denominator does not exist on this baseline. It was written against the stale repository and must be re-derived against the production identity, which now includes `pretax_discount_total`.

**Status: NOT PORTED, scheduled for Release 2 commit 1.** Release 2 commit 2 depends on it.

### 9.3 Historical special-tax over-collection — MEASURED, NOT REMEDIATED

Release 1 stops the defect going forward. It does **not** correct orders already affected. The exposure audit measures them; remediation is a separate business decision (§7).

### 9.4 Summary

| Issue | Status after Release 1 |
|---|---|
| Special tax over-collected on new discounts | **FIXED** |
| Special tax over-collected on historical orders | **MEASURED, not remediated** |
| Refund taxable-basis denominator | **OPEN** — Release 2 |
| `HistoricalTaxBasisResolver` | **NOT PORTED** — Release 2 |
| Goodwill | **DEFERRED** — Release 2 |
| `Gate::before(fn () => true)` bypass | **UNCHANGED** — out of scope entirely |
| `ModuleSeeder` destructive reconciliation | **UNCHANGED** — must not be run |
