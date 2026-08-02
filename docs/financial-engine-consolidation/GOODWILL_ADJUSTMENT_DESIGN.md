# Goodwill Adjustment — Implementation Design

Document date: 2026-08-01
Revision: **3.** Revision 2 was post-audit; Revision 3 adopts FD-002 Amendment 3 (reducible merchandise vs protected components) and rewrites §4, §5 and §11.9 accordingly.
Branch: `feature/goodwill-adjustment` (cut from `raj_development`)
Status: **DESIGN COMPLETE — NO APPLICATION CODE WRITTEN.**
Governing policy: `FINANCIAL_DECISIONS.md` **FD-002 + Amendments 1-3**; `FINANCIAL_TRUTH_TABLE.md` **A-001 + Revisions 1-3**.

---

## 1. Scope

**In scope:** a manager-authorized pre-tax reduction of an order's adjustable merchandise basis (taxable **and** reducible untaxed merchandise), applied from Order Details → Pending Payment, so the revised grand total equals cumulative settled payments; its reversal, audit record, permission, receipt supersession, tests; **and** a narrowly-scoped correction of `PaymentAllocationService`'s tax-rate denominator via a shared resolver.

**Out of scope, by decision:** Store Credit behavior (unchanged; its post-tax treatment stays an open finding); `SalesTaxReportEngine` (frozen, and §9 proves no change needed); `TaxCalculationService` modernization (technical debt); any generic pre-tax-adjustment abstraction; any refund-allocation behavior beyond the denominator fix.

---

## 2. Monetary precision

Integer cents throughout the Goodwill domain. `TaxCalculationService` is the sole authorized calculation boundary; cents are converted to `float` dollars **only** for that call and normalized straight back via `(int) round($x * 100)`. Every reconciliation is asserted as **integer equality**, never a float epsilon. Failure aborts the transaction with nothing written. No parallel calculator.

Storage remains `decimal(10,2)`, matching every existing money column.

**MySQL rounding, validated (closes the Phase 2.7A open question).** MySQL 9.7.1, real `DECIMAL(10,2)` writes: `350.00+350.00*0.0887` → **381.05**; `12.34+12.34*0.0887` → **13.43**; `185.00/1.0975` → **168.56**; `185.00-185.00/1.0975` → **16.44**. **`DECIMAL` rounds half-up on write — it does not truncate**, the opposite of the SQLite behavior that left Phase 2.7A inconclusive. Caveat: this exercised MySQL's exact decimal arithmetic; a PHP `float` crossing a prepared statement can still carry binary error into a half-cent case, which is precisely why the integer-cent assertion in PHP is mandatory rather than belt-and-braces.

---

## 3. The historical taxable basis (the denominator)

**Audit finding — `orders.subtotal` is not the taxable basis.**

```php
// CartHelper::buildCartItem()
$itemTax = ($taxExempt || $product->is_tax_free_item) ? 0 : $itemSubTotal * $taxRate;
```

`orders.subtotal` sums **every** line including `is_tax_free_item` products, so `tax_amount / subtotal` understates the rate on any mixed order (a $200 order half tax-free at 9.75% derives 4.875%).

**Authoritative denominator:**

```
ordinaryTaxableBasis = Σ order_products.sub_total  WHERE the line carried ordinary sales tax
```

Self-consistent by construction: each taxable line's tax is `sub_total × rate`, so the taxable lines reproduce `orders.tax_amount` exactly. Taxability is identified by the line's own recorded tax, cross-checked against `product_data`.

**Reconciliation gate:** `Σ order_products.tax` must equal `orders.tax_amount`. Disagreement means the basis is unreconstructable → reject.

### 3.1 The order also carries two components — now with columns (FD-002 Am.4)

`CartHelper` builds the total as `sub_total + tax + special_tax + added_fees − discount`.

- **`special_tax`** — a second tax, `$itemSubTotal × special_taxes/100`, gated per-product by `apply_special_tax`, at a **different rate**. Basis-derived, so it **must** be recomputed when the basis shrinks (FD-002 Am.1 §2), at its own separately-derived rate, never blended with the ordinary rate.
- **`added_fees`** — flat per-unit charges, not basis-derived, **preserved unchanged**.

**Originally neither had a column anywhere**, surviving only inside `order_products.product_data` JSON. That was the defect: the resolver read basis and ordinary tax from mutable columns but these two from an immutable snapshot, so an adjusted order could never reconcile again and every later refund on it was refused.

FD-002 Amendment 4 promotes both to first-class **current** columns — `orders.special_tax_amount`/`added_fees_amount` and `order_products.special_tax`/`added_fees` — while `product_data` remains the **immutable original checkout snapshot**, never written. Columns are money; JSON is classification and audit. Still unreconstructable or inconsistent → reject.

### 3.2 Shared resolver

`App\Services\Orders\HistoricalTaxBasisResolver` — a focused financial reconstruction service, **not** a generic abstraction. It reconstructs, for one order: ordinary taxable basis, ordinary rate, special-tax basis and rate, added fees, non-taxable subtotal, and a reconciliation verdict.

**Consumed by both `GoodwillAdjustmentService` and `PaymentAllocationService`**, so the two cannot drift.

### 3.2.1 Source priority (FD-002 Amendment 2)

Every successful resolution records which of three named sources it used, tried in this order. The source is exposed on the result as `HistoricalTaxBasisSource` and logged.

| Priority | Source | Evidence | Applies to |
|---|---|---|---|
| 1 | `order_product_lines` | Per-line frozen basis and tax, reconciled exactly against stored order totals | Every order that has lines |
| 2 | `extension_billing_charge` | The extension child's linked `billing_charges` row (`amount`, `tax_amount`, `tax_type`), reconciled exactly against the child's stored totals | Extension children with a linked charge |
| 3 | `extension_order_level` | The extension child's own stored totals | Extension children with **no** linked charge, once every invariant is proven |

Source 3 is logged at **warning** level so the weakest evidence stays visible in operations rather than becoming an invisible default.

**Binding constraints:**

1. **`extension_order_level` is a named transaction-type exception, not a generic fallback.** It exists solely for extension child orders, which `Extension\StoreController` creates with totals and no `order_products` rows by design.
2. **Its validity rests on an invariant, not on arithmetic.** An extension is one base amount under one `add_tax` flag — one tax posture, no hidden fee, special-tax, discount, or tax-free component. The arithmetic is identical to the defective `tax_amount / subtotal` formula this initiative removed; what makes it sound here is the *proven absence* of a tax-free component, nothing else.
3. **Any future extension feature that changes those invariants must update or disable this mode.** Multiple lines, per-line tax posture, a discount, an added fee, or a special tax on an extension invalidates it. The guards (no discount; `subtotal + tax === grand_total` exactly; `is_tax_exempt` agreeing with stored tax) are the tripwires.
4. **A contradictory billing charge is a hard failure.** When a linked charge exists but disagrees with the child order, resolution rejects outright and **never** falls through to source 3. Contradictory authoritative data is a reason to stop, not to retry with weaker evidence.
5. **Anonymous line-less orders remain unsupported.** No lines and no provable extension relationship means rejection.

**Why extensions needed this at all.** Extension children are independently payable, appear in the orders index, render on the ordinary Order Details page, and have no refund-path guard — so they reach the Standard refund path. A purely line-based reconstruction rejected all of them, which *regressed working behavior* rather than exposing a defect: for this type specifically, `orders.subtotal` genuinely was the taxable basis, so the old formula had been returning the correct rate.

### 3.3 Mandatory rejection conditions

Reject — never approximate — on: zero taxable basis with nonzero stored tax; mixed/multiple historical rates across lines; manual tax override unexplainable from stored values; taxable charges outside the selected basis; existing adjustments making the denominator ambiguous; corrupt or unreconciled stored totals; `product_data` absent, malformed, or inconsistent.

Where `PaymentAllocationService` already has a safe fallback, that fallback is used rather than a new rejection path, so refund behavior is not broadened.

---

## 4. Calculation algorithm

All integer cents. `A` = cumulative settled payments after this payment; `Bo` = ordinary taxable basis; `Bs` = special-tax basis; `N` = non-taxable line subtotals; `F` = added fees; `D` = discount; `Xo` = `orders.tax_amount`; `Xs` = special tax total.

1. Resolve via `HistoricalTaxBasisResolver`; reject per §3.3.
2. `ro = Xo / Bo`, `rs = Xs / Bs` — derived independently, never summed.
3. `absorbable = A − N − F + D`. If `< 0` → reject: the payment cannot cover the non-reducible components.
4. The absorbable amount is inclusive of **both** taxes on the reduced basis. Solve for the reduced ordinary basis `Br` such that `Br + Br·ro + Br·rs = absorbable`, i.e. the combined inclusive rate `(1 + ro + rs)`. Call `TaxCalculationService::extractTaxFromInclusiveAmount(absorbable/100, ro + rs)` **once** to obtain `Br`; then compute each tax separately from `Br` at its own rate. The service is used for the inclusive→exclusive extraction only; the two taxes are never reported as one blended figure.
5. Normalize all outputs to cents. `revisedOrdinaryTax = round(Br·ro)`, `revisedSpecialTax = round(Br·rs)`, with any residual cent assigned to the ordinary tax so the identity closes.
6. **Assert exactly:** `Br + N + revisedOrdinaryTax + revisedSpecialTax + F − D === A`.
7. `goodwill = Bo − Br`. If `<= 0` → reject.
8. Revised stored values: `subtotal = Br + N`, `tax_amount = revisedOrdinaryTax`, `grand_total = A`.

**Worked example** (no special tax, no fees, no discount — verified by executing the real service):

| | Cents | Dollars |
|---|---|---|
| Original basis `Bo` | 20000 | $200.00 |
| Original tax `Xo` | 1950 | $19.50 |
| Original grand total | 21950 | $219.50 |
| Accepted `A` | 18500 | $185.00 |
| Derived `ro` | — | 0.0975 |
| **Revised basis** | **16856** | **$168.56** |
| **Revised tax** | **1644** | **$16.44** |
| **Revised grand total** | **18500** | **$185.00** |
| **Goodwill** | **3144** | **$31.44** |

`16856 + 0 + 1644 + 0 + 0 − 0 === 18500` ✓

---

## 5. Line-level allocation

**Corrected from Revision 1:** the column to reduce is `order_products.sub_total`, **not** `price`. `ProductSalesPerformanceEngine`'s own header states *"Revenue source: `order_products.sub_total`"*. `price` is the original unit price and is **left unchanged** — Goodwill is an order-level concession, not a repricing, and `sub_total` already diverges from `price × quantity` where discounts are priced in.

Rule — proportional by `sub_total` across eligible taxable lines, largest-remainder:

1. Eligible = **all reducible merchandise lines**, taxable or untaxed (FD-002 Amendment 3). Only proven fee-type lines are excluded; no share is ever allocated to a protected component. Tax is recomputed solely on lines that carried ordinary tax — an untaxed line stays untaxed however much it is reduced.
2. `rawShare = goodwill × lineSubTotal / Bo`, remainders retained.
3. Leftover cents distributed one each by largest fractional remainder, ties by ascending `id`.
4. **Assert:** shares sum exactly to `goodwill`.
5. Recompute each line's `tax` at `ro` and `total`; residual cent to the final eligible line, mirroring `SalesTaxReportEngine`'s remainder-to-last-row convention rather than inventing a second one.
6. **Assert:** `Σ line sub_total + N === revised subtotal`; `Σ line tax === revisedOrdinaryTax`.

Deterministic — required for exact reversal.

---

## 6. Domain model

`order_goodwill_adjustments`, all money `decimal(10,2)`: `id`, `order_id`, `goodwill_amount`, `reason_code`, `reason_note`, `approved_by`, `performed_by`, `original_subtotal`/`original_tax`/`original_special_tax`/`original_grand_total`, `revised_subtotal`/`revised_tax`/`revised_special_tax`/`revised_grand_total`, `line_allocations` (json, before/after per line), `basis_snapshot` (json — resolved basis, both rates, reconciliation verdict), `payments_accepted`, `payment_status_before`/`payment_status_after`, `order_payment_id`, `superseded_receipt_id`, `idempotency_token` (unique), `reversed_at`/`reversed_by`/`reversal_reason`, timestamps.

`basis_snapshot` exists so a future reader can see exactly which basis and rates were used, without re-deriving them from data that may have since changed.

There is deliberately **no "amount paid after" column**. Goodwill moves no money, so a before/after pair on the money would always hold the same number and would read as evidence of a payment that never happened. `payments_accepted` records the one relevant fact — the cumulative settled payments, re-read under the row lock, that management accepted as payment in full. What genuinely changes is the *derived* payment status, which `payment_status_before`/`payment_status_after` record. (The original schema shipped `total_paid_before`/`total_paid_after`; renamed forward in migration `2026_08_01_000002` rather than by editing committed history.)

**Constraints:** unique `idempotency_token`; index `order_id`; at most one active (non-reversed) adjustment per order — enforced in-transaction under the row lock and asserted by test, since MySQL has no partial index.

**Rollback:** `down()` drops the table, which **destroys the ability to reverse** any applied adjustment. Documented in the migration as a deliberate one-way door.

Service: `App\Services\Orders\GoodwillAdjustmentService` with `preview()` / `apply()` / `reverse()`. `preview()` and `apply()` share one private calculation path so previewed and committed figures cannot diverge.

---

## 7. Server-side transaction

Inside one `DB::transaction`: `lockForUpdate()` the order → re-read settled payments **inside the lock** → re-validate eligibility → compare caller's `state_fingerprint` (hash of `grand_total`, `total_paid`, payment-row count) → record the real payment through the existing path → resolve basis, compute, assert → write the adjustment with snapshots → update `orders` and `order_products` → supersede and reissue the receipt (§10) → recompute status via **canonical logic only** (`Order::is_paid` / `scopeSettled()`, never direct assignment) → audit event → commit. Any failure rolls back both payment and adjustment.

**Reason codes:** `customer_service_resolution`, `pricing_misunderstanding`, `equipment_issue`, `delivery_or_pickup_issue`, `manager_courtesy`, `other` (requires `reason_note`).

**Permissions:** `goodwill.apply`, `goodwill.reverse`, dot-notation per `customer_credit.grant`/`.redeem`. Enforced server-side in the FormRequest; `approved_by` is the authenticated permission-holder, never a browser-supplied name.

---

## 8. Guardrails

Reject on: missing permission (403); already fully paid; voided/ineligible order; `goodwill <= 0`; payment ≤ 0; any §3.3 reconstruction failure; `absorbable < 0`; payments would exceed the revised total (remedy is a refund, and the message says so); `state_fingerprint` mismatch (409); duplicate idempotency token (200, returns original, no second write); already reversed; any cent assertion failure (500, rolled back). No float tolerance anywhere.

---

## 9. Reporting — verified, no engine changes

| Engine | Reads | Reflects Goodwill? |
|---|---|---|
| `SalesTaxReportEngine` (frozen) | `o.tax_amount`, split across settled payments | **Yes**, automatically |
| `PureSalesSummaryReport` | `orders.subtotal` | Yes |
| `PaymentReconciliationLedger` | `o.subtotal`, `o.tax_amount` | Yes |
| `ProductSalesPerformanceEngine` | `order_products.sub_total` | Yes — via §5 |

**No engine caches or snapshots original totals**; all read stored columns live. Because Goodwill updates those columns canonically, every report follows with zero code change.

`SalesTaxReportEngine`'s allocation was executed against the Goodwill figures: single $185.00 payment → 168.56 / 16.44 / 185.00; split $100 + $85 → 91.11+77.45 / 8.89+7.55 / 185.00. Both reconcile exactly. FD-001's freeze is preserved.

Goodwill appears in **no** payment-method, tender, cash-basis, collections, or Store-Credit figure, because it creates no payment row.

---

## 10. Receipts — supersede and reissue

**Audit finding, correcting Revision 1:** `ReceiptService` persists a **snapshot** (`receipts.subtotal`/`sales_tax`/`total` + per-item rows) and sets `receipt_status = 'created'`. Receipts are historical records, not live views.

Therefore Goodwill **marks any existing receipt superseded and issues a new one** with revised totals. The original row is never edited or deleted; `superseded_receipt_id` on the adjustment links them. A receipt issued before the adjustment remains a truthful record of what was presented at that moment.

Presentation:

```
Rental Charges             $200.00
Goodwill Adjustment        -$31.44
Sales Tax                   $16.44
Total                      $185.00
Cash Received              $185.00
Balance                      $0.00
```

Order Details shows Goodwill applied, amount, reason, approving manager, timestamp, reversal status. Payment history shows **only** the real payment.

---

## 11. Scenario coverage

1. **Before any payment** — unsupported by decision; $0.00 collected is a write-off, undecided per Truth Table §5.9. Rejected by guardrail.
2. **After partial payment** — `A` is cumulative; $100 + $85 → $185. Proven in §9.
3. **Payments exceed proposed revised total** — rejected; remedy is a refund.
4. **Repeat / concurrent** — unique idempotency token makes a retry a no-op returning the first result; `lockForUpdate()` serializes managers; the loser fails the fingerprint check.
5. **Reversal after payment** — §12.
6. **Receipts before/after** — §10.
7. **Refund basis** — §13.
8. **Store Credit** — unchanged. A Store Credit payment counts toward `A` like any settled tender via `scopeSettled()`. No Store Credit code touched.
9. **Zero-tax** — `ro = 0`; the service's `$rate <= 0` guard returns base = accepted, tax = 0. Verified: $185.00 → $185.00 / $0.00, Goodwill $15.00.
10. **One-cent / half-cent** — $0.01 verified exactly; half-cent cases are caught by the §4.6 assertion, which aborts rather than writing a cent-off order.

---

## 12. Reversal

Restores `original_*` and `line_allocations` to `orders`/`order_products`; sets `reversed_at`/`reversed_by`/`reversal_reason`; **preserves the adjustment row and every real payment**; recomputes status canonically, reopening the balance; supersedes the post-Goodwill receipt in turn. Requires `goodwill.reverse`.

**Blocked** with a descriptive 422 — never a partial write — when refunds were issued after the adjustment, when already reversed, or when a dependent transaction makes restoration unsafe.

---

## 13. Refund basis — confirmed, plus a defect being fixed

**Confirmed correct with no change:**

- `Order::remaining_amount` = `total_paid − total_refunded` — actual money, never `grand_total`. After Goodwill the ceiling is the $185 collected.
- Tax refunds are capped at `orders.tax_amount` **and** `remaining_amount` (`PaymentAllocationService:490-491`). Once Goodwill rewrites `tax_amount` to 16.44, the cap follows.

**Defect being corrected (FD-002 Am.1 §3):** `PaymentAllocationService:520-521` computes `$originalTaxRate = $order->tax_amount / $order->subtotal` — the same defective denominator, live today, independent of Goodwill. It moves onto `HistoricalTaxBasisResolver`. Narrowly scoped: denominator only; existing safe fallbacks retained; no other refund-allocation behavior touched.

---

## 14. Open findings and technical debt

1. **Store Credit is post-tax tender** — pre-existing, unchanged by decision, still an open financial-engine finding.
2. **`TaxCalculationService` float contract** — modernization is technical debt; contained by integer-cent assertions at the boundary.
3. **Goodwill with $0.00 collected unsupported** — needs its own business decision (relates to Truth Table §5.9 write-off).
4. ~~**`special_tax` / `added_fees` have no columns**~~ — **RESOLVED** by FD-002 Amendment 4 (2026-08-02). Both are now first-class current columns on `orders` and `order_products`, backfilled from the frozen JSON under an exact-residual reconciliation that declines rather than guesses. `product_data` remains the immutable original. The writer's `SpecialTaxUnsupported` refusal is removed.
5. **`(float)` money in the surrounding payment path** (`Order::is_paid` uses `+ 0.005`) — untouched; Goodwill adds no new float logic.
6. **`rc_kabba_testing` does not exist on this machine** — only `kabba2_ai` is present. Must be created before the suite runs.

---

## 15. Tests

```bash
export PATH="/opt/homebrew/opt/php@8.4/bin:$PATH"

# One-time: the allowlisted test database does not exist yet
mysql -u root -e "CREATE DATABASE IF NOT EXISTS rc_kabba_testing;"

php artisan test                                    # full suite
php artisan test --filter=Goodwill
php artisan test --filter=HistoricalTaxBasis
php artisan test tests/Feature/Orders tests/Feature/OrderManagement
php artisan test tests/Feature/Billing tests/Feature/BillingEngine
php artisan test tests/Feature/Reports
php artisan test tests/Unit/Services
```

**Safety verified:** `.env.testing` targets a database distinct from `.env`, and `tests/TestCase.php` hard-allowlists `rentnking_kabba_testing` / `rc_kabba_testing` before any `migrate:fresh`. Development data cannot be touched.

**Coverage** — *calculation:* worked example, zero-tax, tax-exempt, mixed taxable/tax-free, multi-line cent allocation, one-cent, half-cent boundary, special tax present, added fees present, discount present. *Resolver regression (required):* fully taxable orders, mixed taxable/tax-free, zero-tax, partial refunds, tax-only refunds, refunds after Goodwill, line-tax totals that fail to reconcile with `orders.tax_amount`, legacy orders with insufficient line data. *Workflow:* partial-without-Goodwill unchanged, new payment + Goodwill closes, cumulative partials close, canonical Paid, zero balance, real tender preserved, **no `order_payments` row for Goodwill**, server-side permission, reason required, `other` requires note, duplicate idempotency → one payment + one adjustment, failure rolls back both, stale fingerprint rejected. *Reporting:* product revenue reduced, taxable sales reduced, tax reduced, collections = actual money, not counted as tender, Store Credit totals unaffected. *Refund:* cannot exceed adjusted paid total, allocation on adjusted basis, tax refund capped at adjusted tax. *Reversal:* restores subtotal/tax/special tax, payment survives, balance reopens, status reverts, receipt re-superseded, audit retained, double reversal rejected, blocked after dependent refund.

**Statically verified already:** the calculation figures (executed against the real `TaxCalculationService`), the report allocation (executed), and MySQL `DECIMAL` rounding (executed).

**Must run in your terminal:** everything database-backed.

**Untested risk:** PHP `float` → prepared statement → `DECIMAL` at half-cent boundaries, mitigated but not eliminated by the integer-cent assertion.
