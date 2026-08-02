# Deployment plan — current special-tax and added-fees columns

Covers migration `2026_08_02_000001_add_current_special_tax_and_added_fees_columns`, which adds four columns and backfills them from each line's frozen `product_data`.

Governing decision: **FD-002 Amendment 4**. Design context: `GOODWILL_ADJUSTMENT_DESIGN.md` §3.1.

---

## Why this needs a gate at all

The backfill reconstructs money that has never had a column. It reconciles each order against the residual it must explain —

```
residual = grand_total − (subtotal + tax_amount − discount_amount)
```

— which *is* special tax plus added fees, by construction of `CartHelper::buildCart()`. Where the reconstruction matches to the cent, the row is written. Where it does not, the row is **declined** and left at the column default.

A declined row is not silently broken: `HistoricalTaxBasisResolver` reconciles exactly, so those orders keep failing afterwards for the same reason they fail today. **That safety net is not the planned outcome.** A migration that knowingly leaves financially active orders unreadable to refund reconstruction requires explicit review before it runs, not a shrug afterwards.

---

## 1. Pre-migration audit — MANDATORY

Run against production, before the migration:

```bash
php artisan diagnostics:special-tax-backfill-audit --show-ids
```

The command is strictly read-only — every operation beneath it is a `SELECT`. It performs the identical reconstruction the migration performs, so the two cannot disagree. Running it twice produces identical output.

**The production migration must not be run until this result is reviewed.**

## 2. Automatic-proceed condition

Deployment may proceed automatically **only** when all three are zero:

```text
missing_json_unexplained         = 0
unreconciled                     = 0
lineless_with_nonzero_residual   = 0
```

The command prints `DEPLOYMENT GATE PASSED` when, and only when, this holds.

### The three declined categories

| Category | Meaning |
|---|---|
| `missing_json_unexplained` | A line's `product_data` is NULL or not an object, **and** the order owes a nonzero residual |
| `unreconciled` | Every snapshot parsed, but the reconstructed components disagree with the residual |
| `lineless_with_nonzero_residual` | The order has no lines at all, **and** owes a nonzero residual |

The third is tracked separately on purpose. **"No lines" does not independently prove "no special tax or fees"** — it only means there is no line to attribute a component to. Extension children happen to be constrained to a zero residual today, but that is a property of how `Extension\StoreController` builds them, not a law of the schema. A line-less order is written as zero **only** when its residual is also exactly zero; otherwise it is reported on its own, never grouped with the safely-zero ones.

## 3. If any declined rows exist

1. **Stop the deployment.**
2. **Preserve the audit output** verbatim.
3. **Inspect the listed order IDs.**
4. **Classify each transaction shape** — what kind of order is it, and where did the residual come from?
5. **Create an explicit reconstruction or exclusion rule** for that shape.
6. **Re-run the audit.**
7. **Do not rely on default-zero columns as acceptance of those records.**

## 4. Required deployment steps

| Step | Requirement |
|---|---|
| Before | **Database backup taken and verified restorable** |
| Before | Audit run, gate passed, **output saved with the release evidence** |
| Migrate | `php artisan migrate --force` |
| After | **Aggregate verification** — order-level values equal the sum of line-level values |
| After | **Checkout smoke test** — one ordinary order and one special-tax order |

### Post-migration aggregate verification

```sql
SELECT o.id, o.special_tax_amount, o.added_fees_amount,
       COALESCE(SUM(p.special_tax), 0) AS line_special_tax,
       COALESCE(SUM(p.added_fees), 0)  AS line_added_fees
FROM orders o
LEFT JOIN order_products p ON p.order_id = o.id
GROUP BY o.id, o.special_tax_amount, o.added_fees_amount
HAVING ABS(o.special_tax_amount - line_special_tax) > 0.001
    OR ABS(o.added_fees_amount  - line_added_fees)  > 0.001;
```

**Expected: zero rows.** Any row returned means an order-level aggregate disagrees with its own lines — investigate before allowing financial activity on it.

### Checkout smoke test

Place two real orders through checkout and confirm all four columns populate and reconcile:

1. **Ordinary order** — no special tax, no added fees. Both columns `0.00`; `grand_total = subtotal + tax_amount`.
2. **Special-tax order** — a product with `apply_special_tax = 1`. `orders.special_tax_amount` nonzero, equal to the sum of its lines' `special_tax`, and `grand_total = subtotal + tax_amount + special_tax_amount + added_fees_amount − discount_amount`.

Automated equivalents already exist in `tests/Feature/Cart/CheckoutSpecialTaxColumnsTest.php`; the manual pass confirms the deployed configuration, not the code.

---

## 5. Rollback

`down()` drops the four columns, returning special tax and added fees to JSON-only. **Any Goodwill Adjustment applied to a special-tax order while the columns existed becomes unreconcilable at that moment** — the order's grand total will have moved while the frozen JSON did not. Reverse only if no such adjustment was ever applied.

## 6. Invariants this migration establishes

1. Columns are **current and mutable**; `product_data` is the **immutable original checkout snapshot** and is never written.
2. The order-level aggregate is the **sum of its own line columns**, never a separately computed figure.
3. Checkout writes all three: line columns, order aggregate, frozen snapshot. At creation the column and the snapshot are equal — that equality is what makes the snapshot usable as the original-state record once an adjustment later moves the column away from it.
4. `HistoricalTaxBasisResolver` reads money from **columns**, and consults `product_data` only for what a line *is*, never for what it currently *costs*.
