# Release 1 — rollback

**Rollback is CODE-ONLY. The schema stays.**

Companion to `RELEASE_1_DEPLOYMENT_RUNBOOK.md`.

---

## 1. Fix forward, or roll back?

### Fix forward ONLY when all four hold

- The defect is **isolated** — one code path, understood, not a class of failures.
- The **financial effect is fully understood** — you can name every affected order.
- **No incorrect external artifact reached anyone** — no wrong receipt, invoice, refund or ledger entry.
- A **tested** correction can be deployed **within a few hours**.

If any one is uncertain, it is not a fix-forward situation.

### Roll back immediately for

- **Smoke Test 3 failing** — special tax still not reduced means the release did not do the one thing it exists to do.
- Incorrect tax or grand totals on any order.
- Checkout failure, or checkout writing wrong column values.
- Verification queries 5.2–5.6 returning rows.
- Payment or refund discrepancies.
- Pre-existing orders whose totals changed (nothing in this release re-prices historical orders).
- Any unexplained production data mutation.

---

## 2. Code rollback

```bash
# 2.1 — Revert to the tag created before deployment
git fetch production --tags
git checkout raj_development
git reset --hard <pre-release1-TAG>

# 2.2 — Rebuild every cache. Stale compiled views or routes after a
#        reset produce failures that look like new defects.
php artisan config:clear && php artisan config:cache
php artisan route:clear  && php artisan route:cache
php artisan view:clear   && php artisan view:cache
php artisan event:clear  && php artisan event:cache

# 2.3 — Workers must be restarted or they keep executing the new code
php artisan queue:restart
sudo supervisorctl restart <ACTUAL_WORKER_GROUP>

# 2.4 — RUN NO ROLLBACK MIGRATION. See §3.
```

There is **no feature flag** to disable first. This release has no new user-facing action — it corrects the arithmetic behind an existing one. Reverting the code restores the previous (defective) special-tax behaviour, which is the pre-deployment state.

---

## 3. Migration policy — the schema is RETAINED

**Do not run `migrate:rollback`.** The added columns and table are **additive and inert** to the reverted code: it never reads or writes them, so leaving them costs nothing. Dropping them is the destructive option.

| Object | Why it must survive |
|---|---|
| `orders.special_tax_amount`, `added_fees_amount` | Backfilled by reconstruction from frozen `product_data` under an exact-residual gate. Dropping discards that work, and re-deriving it later requires the gate to pass a second time |
| `order_products.special_tax`, `added_fees` | Same reconstruction, per line |
| `orders.special_tax_before_discount` | The **only** record of the original special tax for orders discounted before this release. Once dropped it cannot be recovered from the order — only from each line's frozen JSON, and only while that JSON is intact |
| `order_product_discount_allocations` | The per-adjustment ledger. Dropping it destroys which lines bore which concession, which is exactly what cannot be reconstructed afterwards |
| `order_products.pretax_discount_allocated` | Derived from the ledger above; meaningless once it is gone |
| `orders.legacy_unallocated_pretax_discount` | The explicit record of untracked historical concessions |

**Never drop any of the above.**

> ### ⚠ The specific hazard
>
> If the schema is dropped and Release 1 is later re-deployed, `special_tax_before_discount` would be re-backfilled from `special_tax_amount` — but by then the corrected code may already have **reduced** that value on some orders. The migration would enshrine an already-reduced figure as the "original" and compound the error downward on every subsequent adjustment. Retaining the schema avoids this entirely.

Reverted code writing to an order does **not** corrupt the retained columns: the old `OrderDiscountTarget` simply does not touch `special_tax_amount` or the allocation ledger. Those values become **stale, not wrong** — they still describe the last state the new code produced. Re-deploying picks up from there.

---

## 4. Verification after rollback

```bash
git log -1 --format="%H %s"        # MUST equal the rollback tag's commit
php artisan --version
ps -eo pid,cmd | grep -c "[q]ueue:work"
```

```sql
-- 4.1 Schema is still present. EXPECT 6 ROWS.
SELECT table_name, column_name FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND column_name IN ('special_tax_amount','added_fees_amount',
                      'special_tax_before_discount','legacy_unallocated_pretax_discount',
                      'special_tax','added_fees','pretax_discount_allocated');

-- 4.2 The allocation ledger still exists. EXPECT 1 ROW.
SELECT table_name FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'order_product_discount_allocations';

-- 4.3 No order was left mid-write. EXPECT 0 ROWS.
SELECT id, subtotal, tax_amount, grand_total, pretax_discount_total
FROM orders
WHERE grand_total < 0 OR tax_amount < 0 OR pretax_discount_total > subtotal;
```

**Functional check:** place a test order, apply Store Credit, confirm it behaves as it did before deployment — the totals reduce, and special tax does **not**. That is the expected (defective) pre-release behaviour, and seeing it confirms the rollback took effect.

Record in the evidence directory: rollback time · tag reverted to · who authorised · why · verification results.

---

## 5. Database restore — last resort only

Reserved for **actual data corruption that cannot be repaired in place**. A restore rewinds to the backup moment, **erasing every transaction since** — including real customer payments taken during the window.

**Before restoring, enumerate exactly what would be lost:**

```sql
SET @cutoff = '<BACKUP TIMESTAMP>';

-- Real money taken since the backup. THESE WOULD BE ERASED.
SELECT id, order_id, payment_method, amount, status, created_at
FROM order_payments WHERE created_at >= @cutoff ORDER BY id;

-- Orders, refunds, ledger postings, invoices, receipts and discounts since.
SELECT 'order' src, id, created_at FROM orders WHERE created_at >= @cutoff
UNION ALL SELECT 'refund_alloc', id, created_at FROM order_payment_refund_allocations WHERE created_at >= @cutoff
UNION ALL SELECT 'customer_account', id, created_at FROM customer_accounts WHERE created_at >= @cutoff
UNION ALL SELECT 'invoice', id, created_at FROM invoices WHERE created_at >= @cutoff
UNION ALL SELECT 'receipt', id, created_at FROM receipts WHERE created_at >= @cutoff
UNION ALL SELECT 'discount', id, created_at FROM product_discounts WHERE created_at >= @cutoff
ORDER BY created_at;
```

**Export those rows to the evidence directory before restoring.** They must be re-entered by hand afterwards, and a payment that cannot be reconstructed is money the business cannot account for.

**If any real payment appears in that list, restoring is almost certainly the wrong call** — prefer targeted repair with the code already reverted.

```bash
zcat /backups/kabba2-pre-release1-<STAMP>.sql.gz | mysql -h "$DB_HOST" -u "$DB_USER" -p
```
