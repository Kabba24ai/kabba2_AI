# Goodwill — production re-baseline report

**Status: audit only. No application code written. Nothing pushed, merged, migrated, or deployed.**

---

## 1. Identity

| | |
|---|---|
| True production repository | `RajChotaliya/Kaaba2` (remote `production`) |
| Production head | **`d8591af5`** — *fix(rental-ready): single-# order hotlink…*, 2026-08-01 |
| Stale repository | `Kabba24ai/kabba2_AI` (remote `origin`) |
| Stale feature head | **`700b40e0`** — 11 commits |
| Stale feature base | `1f1948d6` — **does not exist on production** |
| Common ancestor | `1e8a21ac` — 2026-07-22 |
| Production ahead of ancestor | **169 commits** |
| Stale branch ahead of ancestor | 12 commits |
| Re-baseline branch | `feature/goodwill-production-rebaseline` from `d8591af5` |

The Goodwill work was developed against a repository whose `raj_development` had not moved since 2026-07-22. Production moved 169 commits in that window, including directly overlapping financial work.

### Overlapping files (12 of 60 ours / 555 theirs)

```
app/Http/Controllers/Admin/OrderManagement/ScheduleAssignment/ToggleAutoAssignController.php
app/Http/Controllers/Front/Checkout/PostController.php
app/Models/Orders/Order.php
app/Models/Orders/OrderProduct.php
app/Services/ReceiptService.php
database/seeders/Iam/ModuleSeeder.php
resources/views/admin/order_management/orders/edit.blade.php
resources/views/admin/order_management/orders/print_receipt.blade.php
resources/views/admin/order_management/schedule_assignment/index.blade.php
routes/admin/order_management/orders/routes.php
tests/Feature/OrderManagement/AutoAssignToggleProtectionTest.php
tests/Feature/Orders/PaymentAllocationFoundationTest.php
```

File overlap is the smaller problem. The semantic conflicts below matter more.

---

## 2. Assumptions that REMAIN VALID

| Assumption | Evidence on `d8591af5` |
|---|---|
| **The refund taxable-basis defect still exists** | `PaymentAllocationService::proportionalTaxRefund()` line 520 still computes `tax_amount / subtotal`. Unchanged, and now **worse** — see §3.4 |
| **Special tax and added fees still have no columns** | No migration defines `special_tax_amount`; both live only in `order_products.product_data` JSON |
| **`Gate::before(fn () => true)` still exists** | `AppServiceProvider` line 75, with a comment describing how to remove it. Direct Spatie enforcement remains necessary |
| **`ModuleSeeder` is still destructive** | Unchanged reconciliation deletes. `GoodwillPermissionSeeder` is still the correct approach |
| **`HistoricalTaxBasisResolver` does not exist on production** | Confirmed absent. The reconstruction problem is unsolved there |
| **`order_goodwill_adjustments` does not exist** | No Goodwill schema of any kind |
| **Goodwill is not a payment tender** | No production change contradicts FD-002's core premise |
| **Extension children own no order lines** | `Order::isExtensionChild()` / `scopeExtensionChildren()` unchanged; `extensionCharge()` relation still present |

---

## 3. Assumptions INVALIDATED by production

### 3.1 Store Credit is no longer post-tax tender — **FD-002 must be superseded**

FD-002 records *"Store Credit remains post-tax tender, out of scope by decision."* That is now false.

Commit `dd369166` — *"Store Credit: reframe as a pre-tax product discount, not a payment tender"* — plus a full discount abstraction:

```
app/Services/Discounts/Contracts/DiscountTarget.php
app/Services/Discounts/DiscountApplicationService.php
app/Services/Discounts/DiscountCalculator.php
app/Services/Discounts/DiscountResult.php
app/Services/Discounts/DiscountTargetResolver.php
app/Services/Discounts/Targets/OrderDiscountTarget.php
```

**A reusable pre-tax adjustment abstraction already exists.** This is what the original plan called for and did not have.

New order columns (`2026_07_27_090000_add_pretax_discount_snapshot_to_orders`): `pretax_discount_total`, `tax_amount_before_discount`, `grand_total_before_discount`.

### 3.2 The persistence model is structurally incompatible with mine

| | Production `OrderDiscountTarget` | Stale Goodwill implementation |
|---|---|---|
| `orders.subtotal` | **unchanged** — "so gross sales reporting is unaffected" | **reduced** |
| `order_products.sub_total` | **untouched** | **reduced per line** |
| Reduction recorded in | `pretax_discount_total` (accrues) | the reduced columns themselves |
| Original state | `*_before_discount` snapshot columns | `order_goodwill_adjustments` snapshot |
| Reversal | recompute from snapshot | restore per-line allocations |

These cannot both be right for the same order. **This is the central decision of the re-baseline.**

Consequence: `ProductSalesPerformanceEngine` documents *"Revenue source: `order_products.sub_total` (discounts are priced-in)"* — but the discount layer never touches `order_products.sub_total`, so **Store Credit discounts do not currently reduce product revenue.** My model would; production's does not. This inconsistency is pre-existing and should be resolved deliberately, not inherited by accident.

### 3.3 The reconciliation identity has changed

`HistoricalTaxBasisResolver` asserts:

```
grand_total = subtotal + tax + special_tax + added_fees − discount_amount
```

Production now maintains `pretax_discount_total` **outside** `discount_amount`, and `OrderDiscountTarget::recompute()` writes a reduced `tax_amount` and `grand_total` while leaving `subtotal` intact. **The resolver's identity fails on any order carrying a Store Credit discount** — it would return `UnreconciledGrandTotal` and refuse. Ported unchanged, the resolver would break refunds on exactly the orders Store Credit touches.

### 3.4 The refund denominator defect is now compounded

`tax_amount / subtotal` was wrong on mixed taxable/tax-free orders. On a discounted order it is now wrong twice over: reduced `tax_amount` divided by the **undiscounted** `subtotal`. The fix is more necessary than before, and the resolver must account for `pretax_discount_total`.

### 3.5 Receipts are now MUTATED in place — supersession conflicts

My audit finding *"`ReceiptService` freezes a snapshot and never refreshes it"* is obsolete. Production now rewrites the receipt when the order's totals drift:

```php
if (round($receipt->subtotal,2) !== round($order->subtotal,2) || …) {
    $receipt->subtotal = $order->subtotal;
    $receipt->sales_tax = $order->tax_amount;
    $receipt->total = $order->grand_total;
    $receipt->saveQuietly();
}
```

Both approaches fix staleness. They disagree on whether a receipt is a **document that was handed to a customer** (append-only, supersede) or a **view of current state** (mutate). Production chose mutate. The receipt schema itself is unchanged — no supersession columns exist.

### 3.6 Reporting is now cash-basis

`SalesTaxReportEngine` anchors on `COALESCE(op.payment_datetime, op.created_at)` — payment date, not order date (`7fb51595`, `67407a45`, `ea3977ef`). My proof that the engine needs zero changes was performed against the accrual-basis July 22 engine and **must be re-run**.

The engine does **not** read `pretax_discount_total` (grep count: 0). Whether a pre-tax concession is correctly reflected in taxable sales under cash basis is now an open question, not a settled one.

### 3.7 Order and line schema moved

`order_products` gained cleaning fields (`is_product_clean`, `rental_prepaid_cleaning`, clean-charge fields). `Order` gained the pre-tax discount trio. Any migration adding columns must be re-checked for collisions — none found, but the `after()` clauses in the stale migrations reference column positions that have changed.

---

## 4. Design amendments required

| # | Decision needed | Options |
|---|---|---|
| **A** | **Does Goodwill reuse `Discounts` or stay separate?** | (i) Implement Goodwill as a `DiscountTarget`/discount type, inheriting `pretax_discount_total`; (ii) keep a separate domain but adopt the same persistence model; (iii) keep both models — **not viable**, they contradict |
| **B** | **Does a pre-tax concession reduce `order_products.sub_total`?** | Production says no (preserves gross sales); FD-002 said yes (reduces product revenue). Determines Product Sales Performance behaviour for **both** features |
| **C** | **Receipt model: mutate or supersede?** | Production mutates. Supersession preserves what the customer was handed. Cannot do both |
| **D** | **Reconciliation identity** | Must incorporate `pretax_discount_total`, or the resolver refuses discounted orders |
| **E** | **Reporting basis** | Re-prove Goodwill under cash-basis reporting |
| **F** | **Blended-rate equivalence** | `OrderDiscountTarget` uses `tax_amount / subtotal` as a blended rate. This is arithmetically **correct** only when the reduction is proportional across taxable and exempt lines. Verify whether product-targeted discounts violate that — if so, production has a live tax defect independent of Goodwill |

---

## 5. Commit-by-commit port assessment

| # | Stale commit | Verdict | Why |
|---|---|---|---|
| 1 | `e3773365` historical tax basis resolver | **Port, modified** | Still needed; identity must handle `pretax_discount_total` (§3.3) |
| 2 | `ab482ad9` refund tax basis fix | **Port, modified** | Defect confirmed present; denominator must account for discounts |
| 3 | `a2faadea` extension child refund basis | **Port, likely unchanged** | Extension model unchanged; re-verify against `extensionCharge()` |
| 4 | `78020401` goodwill calculation domain | **Port, pending Decision A** | Arithmetic is sound; persistence target may change |
| 5 | `38a0f146` special tax + added fees columns | **Port, mostly unchanged** | Columns still absent. Rework migration `after()` positions; re-verify backfill residual formula against `pretax_discount_total` |
| 6 | `85187b24` AR/invoice guards | **Port, re-verify** | AR/invoice structures appear unchanged; `OrderDiscountTarget` independently blocks AR-posted orders — **precedent worth adopting** |
| 7 | `cbd23c85` receipt supersession | **HOLD — pending Decision C** | Directly conflicts with production's mutate-in-place |
| 8 | `d61dc897` pending-payment UI | **Port, modified** | `edit.blade.php` and routes both moved; UI must account for Store Credit already present on the page |
| 9 | `7d9bd5fc` keep adjusted orders refundable | **Port, unchanged** | Correction to our own resolver; carries with commit 1 |
| 10 | `ac7d3a1a` deployment runbook | **Rebuild** | Every commit hash and the fast-forward claim are wrong for production |
| 11 | `700b40e0` seeder + audit proof | **Port, unchanged** | `ModuleSeeder` still destructive; `GoodwillPermissionSeeder` still correct |

**Discard outright:** none. **Blocked pending decision:** 7 (and 4's persistence target).

---

## 6. Test baseline on `d8591af5` (clean, before any port)

Captured on `rc_kabba_testing` after `migrate:fresh` on the production branch.

| Suite | Failing / Total |
|---|---|
| `tests/Feature/Reports` | **23** / 123 |
| `tests/Feature/BillingEngine` | **47** / 206 |
| `tests/Feature/OrderManagement` | **6** / 85 |
| `tests/Unit/Services` | **3** / 210 |
| `tests/Feature/Billing` | **1** / 63 |
| Refund + extension (11 files) | **2** / 131 |
| `tests/Feature/Cart` | **0** / 23 |

**These differ substantially from the stale-branch baselines** (Reports 21→23, BillingEngine 27→47, Unit/Services 10→3, OrderManagement 4→6). The stale baselines in `GOODWILL_DEPLOYMENT_RUNBOOK.md` §9 are void and must not be used as the regression reference.

`tests/Feature/Orders` as a whole still exhausts the 128M limit; run by file group.

---

## 7. Migration compatibility

| Stale migration | Compatibility |
|---|---|
| `create_order_goodwill_adjustments_table` | **Clean** — no such table on production |
| `rename_total_paid_columns…` | **Clean** — should be folded into the create migration on the re-baseline, since neither has shipped |
| `add_current_special_tax_and_added_fees_columns` | **Needs rework** — `after('tax_amount')` on `orders` now lands beside the pre-tax discount trio; `after('tax')` on `order_products` now sits near cleaning fields. Backfill residual formula must subtract `pretax_discount_total` |
| `add_supersession_columns_to_receipts_table` | **Blocked** by Decision C |

No production migration collides by name or by column. Timestamps `2026_08_0*` sort after production's latest (`2026_07_31_100000`), so ordering is safe.

---

## 8. Proposed new commit sequence

Each stops before commit for review, per Phase 4.

| # | Commit | Gate |
|---|---|---|
| 0 | *(governance amendments — draft, approved first)* | Decisions A–F resolved |
| 1 | `financial: add historical tax basis resolver` | Identity handles `pretax_discount_total` |
| 2 | `financial: fix refund tax basis reconstruction` | Defect re-proved on production data shapes |
| 3 | `financial: restore extension child refund basis` | Re-verified against `extensionCharge()` |
| 4 | `financial: persist current special tax and added fees` | Migration positions reworked; backfill residual updated |
| 5 | `financial: add goodwill calculation domain` | Persistence model per Decision A/B |
| 6 | `financial: guard goodwill apply and reversal` | AR/invoice re-verified; align with `OrderDiscountTarget::ineligibleReason()` |
| 7 | *(receipts — only if Decision C selects supersession)* | Otherwise integrate with mutate-in-place |
| 8 | `financial: add goodwill pending-payment workflow` | UI coexists with Store Credit panel |
| 9 | `financial: lifecycle suite` | Adds a Store-Credit-plus-Goodwill lifecycle |
| 10 | `docs: rebuild deployment runbook` | New chain, new baselines, `d8591af5` ancestry |

---

## 9. Recommendation

**Do not port commit-by-commit until Decisions A, B, and C are made.** Two of them (persistence model, receipt model) determine whether roughly half the existing implementation is reusable or must be rewritten against `Discounts`.

The strongest option to evaluate first is **Decision A(i)** — implementing Goodwill as a discount type on the existing `Discounts` abstraction. It would give one pre-tax adjustment engine instead of two, which is what the original mission asked for and what production has since built. The cost is that FD-002's per-line allocation, its exact integer-cent reconciliation, and its typed-refusal model would need to be brought *into* that abstraction, which today uses float arithmetic and a blended rate.

That is a design decision, not an implementation detail, and it belongs to you.
