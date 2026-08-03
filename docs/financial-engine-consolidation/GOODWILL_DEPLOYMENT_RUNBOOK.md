# Goodwill Adjustment — production deployment runbook

**Target:** `RajChotaliya/Kaaba2`, branch `raj_development` — the true production
repository.

**Rebased onto production head `37df459c`** (`fix: unwrap double-nested checklist
arrays…`, 2026-08-03).

Re-verify that head before running anything. Production moved **twice** during
this work — `afa84552` → `a5b81f18` → `37df459c` — so treat a stale head as the
expected case, not the exception, and re-check rather than assume.

Nothing in this document has been executed. There is **no production server
access** from the development environment: every step is run by the operator.

---

## 0. Rebase — COMPLETE

The branch was rebased onto the current production head. Recorded here because
the commit hashes changed, and because production moved twice while this work
was in progress.

```
previous merge base   afa84552
production now        37df459c   (a5b81f18 + 3 further commits)
release branch        0457ad09   4 commits, replayed onto 37df459c
```

**The rebase produced no conflicts.** The three commits added after `a5b81f18`
touch `Api/Admin/V1/Customers/IndexController`, two CustomerChecklists request
classes, one migration and some audit documents — no file this release changes.

Verified after rebasing:

```bash
git merge-base --is-ancestor production/raj_development HEAD   # exit 0
git rev-list --left-right --count production/raj_development...HEAD   # 0  4
git status --short                                             # empty
```

Zero commits unique to production, so the push is a clean fast-forward.
**Do not force-push.**

### The migration filename collision, resolved

`a5b81f18` added `2026_08_03_000001_make_total_clean_charge_nullable_on_order_products_table.php`
to `database/migrations/orders/` — the same `2026_08_03_000001` prefix this work
originally used, in the same directory.

Laravel sorts by full filename, so both would have run and neither would have
errored, but two migrations sharing a timestamp is ambiguous and would confuse
any later `migrate:rollback --step` reasoning. The Goodwill migration was
renumbered to `2026_08_03_000002`. It has never run in production, so the rename
cost nothing.

Nothing about the Goodwill table depends on ordering — it references only
long-existing tables (`orders`, `product_discounts`, `users`, `order_payments`).

---

## 1. Commit chain

Four commits, in order:

| Hash | Subject |
|---|---|
| `9b08bb42` | `financial: add goodwill audit domain` |
| `62de7779` | `financial: add goodwill apply and reversal service` |
| `a54e5388` | `financial: add goodwill pending-payment workflow` |
| `0457ad09` | `financial: correct refund tax basis and verify goodwill lifecycle` |

Plus the documentation commit that records these hashes, which sits on top.

**These are post-rebase hashes, valid against production head `37df459c`.** If
production moves again the branch must be rebased again and they will change —
re-read them before deploying and record the values actually deployed.

---

## 2. Backup and rollback tag

Both steps happen **immediately before** the migration, not the day before. A
stale backup is not a rollback plan.

```bash
# 2a. Tag the exact production commit being replaced.
git fetch production
git tag pre-goodwill-$(date +%Y%m%d-%H%M) production/raj_development
git push production --tags

# 2b. Fresh database backup, taken now.
mysqldump --single-transaction --routines --triggers \
  -u <user> -p <production_db> > /backups/pre-goodwill-$(date +%Y%m%d-%H%M).sql
```

Confirm before proceeding:

- the dump file is non-zero and its last line reads `-- Dump completed`;
- **you personally can restore it** — knowing where a backup lives is not the
  same as being able to use it;
- the tag is visible on the remote (`git ls-remote production --tags | grep pre-goodwill`).

---

## 3. Migration

**One migration. It is purely additive: one new table, no existing column
altered, no data rewritten, no backfill.**

```bash
php artisan migrate:status | tail -20     # confirm the Release 1 chain is present
php artisan migrate --force
```

Expected order after the rebase:

| # | Migration | Origin |
|---|---|---|
| 1 | `2026_08_03_000001_make_total_clean_charge_nullable_on_order_products_table` | `a5b81f18`, production — already deployed |
| 2 | `2026_08_03_000002_create_order_goodwill_adjustments_table` | **this release** |

If step 1 shows as already run, that is expected — it belongs to production, not
to this release.

`order_goodwill_adjustments` carries a `CHECK` constraint bounding
`rounding_residual` to ±$0.02 and a `UNIQUE` index on `active_order_id`. Both
are created with the table. Expected runtime: **under one second** on an empty
new table.

### Verify

```sql
SHOW CREATE TABLE order_goodwill_adjustments\G
-- expect: UNIQUE KEY `oga_one_active_per_order` (`active_order_id`)
-- expect: CONSTRAINT `oga_rounding_residual_bounded` CHECK (...)
SELECT COUNT(*) FROM order_goodwill_adjustments;   -- expect 0
```

---

## 4. Permissions — additive seeder ONLY

```bash
php artisan db:seed --class="Database\Seeders\Iam\GoodwillPermissionSeeder" --force
```

Creates the `Goodwill` module category, the `goodwill` module, the permissions
`goodwill.apply` and `goodwill.reverse` (each with `module_id` set), and grants
both to **Master Admin**. It issues **no DELETE of any kind** and is safe to run
repeatedly — proven by running it twice against a populated database with an
unrelated planted permission left untouched.

### ⛔ NEVER RUN `ModuleSeeder` IN PRODUCTION

`Database\Seeders\Iam\ModuleSeeder` is a **destructive reconciliation seeder**.
It deletes:

- every permission attached to a module but absent from its hardcoded list;
- every module absent from that list;
- every module category absent from that list;
- **every permission with a null `module_id`**, whatever it is for.

Deleting a Spatie permission cascades through role and user assignments and
revokes access silently. Proven empirically: three planted rows were all removed
by a single re-run.

The Goodwill module *is* declared in `ModuleSeeder`'s list — but only so that if
it is ever run, it would not delete what the additive seeder created. That is a
safety net, **not** permission to run it.

### Verify

```sql
SELECT p.name, p.module_id, m.name AS module
FROM permissions p LEFT JOIN modules m ON m.id = p.module_id
WHERE p.name IN ('goodwill.apply','goodwill.reverse');
-- expect 2 rows, both with a non-null module_id

SELECT r.name FROM roles r
JOIN role_has_permissions rhp ON rhp.role_id = r.id
JOIN permissions p ON p.id = rhp.permission_id
WHERE p.name = 'goodwill.apply';
-- expect at least: Master Admin
```

**A null `module_id` is a defect, not a cosmetic detail** — `ModuleSeeder` would
delete such a permission outright.

Then grant the permissions to the real managers who should hold them. Until
that is done, **only Master Admin can apply or reverse Goodwill**, which is a
safe default, not a bug.

---

## 5. Cache rebuild

This release adds routes and a Blade partial and modifies `edit.blade.php`.
**Neither will appear without a cache rebuild.**

```bash
php artisan route:clear && php artisan route:cache
php artisan view:clear && php artisan view:cache
php artisan config:clear && php artisan config:cache
php artisan optimize:clear && php artisan optimize
```

Verify the routes exist:

```bash
php artisan route:list --name=goodwill
# expect exactly three:
#   GET|HEAD  .../{unique_id}/goodwill/preview   admin.order-management.orders.goodwill.preview
#   POST      .../{unique_id}/goodwill           admin.order-management.orders.goodwill.apply
#   DELETE    .../{unique_id}/goodwill/{id}      admin.order-management.orders.goodwill.reverse
```

---

## 6. Queue restart

Workers hold the **old code in memory** and will keep running it until
restarted.

```bash
php artisan queue:restart
# then, depending on the process manager:
sudo supervisorctl restart all      # or: systemctl restart <worker-service>
```

Goodwill dispatches no jobs of its own, so this is not strictly required for the
feature to work. It is required so that queued work touching orders, receipts or
permissions is not running against a stale permission cache.

---

## 7. Smoke tests

Run in order, on a **real but low-value order**. Stop at the first unexpected
result.

| # | Action | Expected |
|---|---|---|
| 1 | Open an order with **no payment** | No **Apply Goodwill** pill |
| 2 | Open a **fully paid** order | No pill |
| 3 | Take a **partial payment** on a taxable order, reload | Pill appears beside *Balance Due* |
| 4 | Open the modal | Breakdown loads; header reads *"Goodwill changes the order total. It does not record another payment."* |
| 5 | Check the breakdown lines | Amount Collected · Current Balance · Goodwill - Pre-Tax · Discounted Product Value · Sales Tax · Revised Order Total (Special Tax / Added Fees / Rounding Residual only when nonzero) |
| 6 | Confirm the arithmetic | Revised Order Total **equals** Amount Collected (or differs by the stated residual) |
| 7 | Select reason, manager, apply | Page reloads; order reads **Paid In Full**; balance $0.00 |
| 8 | Check payments | **No new payment row.** `total_paid` unchanged |
| 9 | Download the receipt | Line reads **`Goodwill - Pre-Tax`** with a plain `-` and the correct amount; the receipt's own figures sum to its total |
| 10 | Check the order summary | Subtotal unchanged (gross); Pre-Tax Discounts shows the concession |
| 11 | Reverse it, with a reason | Balance re-opens to the pre-Goodwill figure; receipt returns to the original total |
| 12 | Sign in as a user **without** `goodwill.apply` | **No pill at all** on the same order |
| 13 | Apply a Store Credit discount, then Goodwill, then reverse only the Goodwill | Store Credit survives; totals return exactly to the Store-Credit-only position |

Reconciliation check on any adjusted order:

```sql
SELECT id, subtotal, pretax_discount_total, tax_amount, special_tax_amount,
       added_fees_amount, discount_amount, grand_total
FROM orders WHERE id = <order_id>;
-- grand_total = subtotal - pretax_discount_total + tax_amount
--             + special_tax_amount + added_fees_amount - discount_amount
```

---

## 8. Kill switch

**Revoke the permission. No deploy, no migration, no restart.**

```sql
DELETE rhp FROM role_has_permissions rhp
JOIN permissions p ON p.id = rhp.permission_id
WHERE p.name = 'goodwill.apply';

DELETE mhp FROM model_has_permissions mhp
JOIN permissions p ON p.id = mhp.permission_id
WHERE p.name = 'goodwill.apply';
```

```bash
php artisan permission:cache-reset
```

Effect: the pill disappears for everyone and every endpoint returns 403. Authority
is checked through `hasPermissionTo()`, which reads these tables directly — it
does **not** go through the Gate, so the application-wide `Gate::before` bypass
cannot re-enable it.

**Adjustments already applied are untouched.** They remain valid, visible and
reconciled. Revoking `goodwill.apply` while leaving `goodwill.reverse` granted
lets managers still unwind an adjustment while no new ones can be created —
usually the right posture during an incident.

To restore, re-run the seeder (§4) and re-grant to the intended roles.

---

## 9. Rollback

### 9a. Preferred — kill switch

For anything short of data corruption, use §8. It is instant, reversible, and
destroys nothing.

### 9b. Code revert, **retaining schema and audit history**

```bash
git revert --no-commit 0457ad09 a54e5388 62de7779 9b08bb42
git commit -m "revert: goodwill adjustment release"
# deploy, then:
php artisan route:cache && php artisan view:cache && php artisan queue:restart
```

**Do NOT roll back the migration.**

```
DO NOT RUN: php artisan migrate:rollback
```

`order_goodwill_adjustments` is an **audit table**. Dropping it destroys the
record of every concession granted — who authorized it, why, and against which
payment — while the financial effects remain in `product_discounts`, `orders`
and the allocation ledger. That leaves reduced order totals with no explanation,
which is strictly worse than the situation the rollback was meant to fix.

The table is additive and referenced by nothing else. An empty, unused table
costs nothing to keep.

The same applies to `product_discounts` rows of type `goodwill`: reverting the
code does not reverse applied concessions, and it should not. Any adjustment
that must be undone should be **reversed through the UI before** the revert, so
the reversal is recorded rather than erased.

### 9c. Full database restore

Last resort only, and only for genuine corruption. Restoring the §2 dump
discards **every transaction taken since it**, not only Goodwill ones. Weigh
that against the alternative before choosing it.

---

## 10. Known baseline failures — present BEFORE this release

These fail on production's current head and are **not** caused by this work. Do
not treat them as a deployment signal.

| Suite | Failing / total |
|---|---|
| `Feature/Orders` | 12 / 389 |
| `Feature/Reports` | 23 / 132 |
| `Feature/BillingEngine` | 47 / 206 |
| `Feature/OrderManagement` | 6 / 85 |
| `Unit/Services` | 3 / 210 |
| `Feature/Billing` | 1 / 63 |
| `Feature/Discounts` · `Feature/Credit` · `Feature/Cart` | 0 |

Verified by stashing all Goodwill work and re-running: identical counts and
assertion totals before and after.

`Feature/Goodwill` is **129 / 129 passing, 636 assertions.**

Note also: every suite reports PHP 8.5 deprecation notices for
`PDO::MYSQL_ATTR_SSL_CA`. Environment noise, repository-wide, unrelated.

---

## 11. Included in this release — the refund tax-basis fix

**This release also corrects a live production defect in the refund tax split.**
It was escalated from follow-up to a release blocker: shipping Goodwill would
have made the defect reachable on more orders.

`PaymentAllocationService::proportionalTaxRefund()` derived its rate as
`tax_amount / subtotal`. That understated it in two independent ways — `subtotal`
includes tax-free lines that never generated tax, and it does not move when a
pre-tax adjustment reduces `tax_amount`.

The rate now comes from `App\Services\Orders\TaxableBasisResolver`:

```
taxable_basis = taxable_gross × (subtotal − pretax_discount_total) ÷ subtotal
rate          = tax_amount ÷ taxable_basis
```

### ⚠ Operator briefing — brief this BEFORE deployment

> **The customer's refund amount does not change. Nothing about this makes
> refunds bigger.**
>
> A refund total has always been made of two parts: the merchandise being
> returned, and the sales tax that was charged on it. That **total is unchanged**
> and always was correct.
>
> What changes is how the total is **divided between those two parts** in our
> records. We were recording too little of it as sales tax, so on a mixed-tax or
> discounted order the `tax_refunded` figure will now be **larger than it would
> have been yesterday for the same refund** — and the merchandise portion
> correspondingly smaller. The two still add up to exactly the same amount the
> customer receives.
>
> This matters for what we remit to the tax authority, not for what anyone is
> paid. If a report shows refunded tax rising after this deployment with no
> change in refund volume, **that is the fix working**, not an error to
> investigate.

Say this in those terms. "Refunds will show more tax" invites exactly the wrong
conclusion if the first sentence is left out.

**No schema change, no migration, no backfill.** Read-only resolution at refund
time.

### FORWARD-ONLY. Refunds already issued are never recalculated.

**The corrected calculation is effective for refunds processed after deployment
only.**

- **No historical financial data is modified by this release.**
- **No historical refunds are recalculated.**
- **No remediation script, backfill or exposure report is provided** for
  previously issued refunds, and none is to be built.

Refunds issued before this deployment keep the split they were processed with,
in `order_payments.tax_refunded` and everything derived from it. They are part
of the permanent audit trail: they match the receipts customers hold, the bank
deposits they settled against, and the tax filings they were reported on.
Restating them would make the system disagree with all of those at once, and
silently.

This is standing policy, not a decision specific to this release — see
`FINANCIAL_DECISIONS.md` **G-10**.

**Nothing in §3 changes this.** The single migration creates one new table and
touches no existing row.

### Extra smoke test for this fix

| # | Action | Expected |
|---|---|---|
| 14 | Refund an order with **mixed taxable and tax-free** merchandise | `tax_refunded` reflects the **taxable lines only** — roughly double what the old formula produced on a half-exempt order |
| 15 | Refund an order carrying a Store Credit or Goodwill concession | Base + tax still sum exactly to the refund total; tax portion larger than before |
| 16 | Refund an **extension child** order | Still works, split at its own rate — extension refunds must not regress |

---

## 12. Sign-off checklist

- [ ] Production head re-verified; branch rebased onto it (§0)
- [ ] Rollback tag pushed and visible on the remote (§2a)
- [ ] Fresh backup taken, verified complete, restore path confirmed (§2b)
- [ ] `migrate --force` run; both constraints verified in `SHOW CREATE TABLE` (§3)
- [ ] `GoodwillPermissionSeeder` run; both permissions exist with non-null `module_id` (§4)
- [ ] `ModuleSeeder` **not** run (§4)
- [ ] Route, view and config caches rebuilt; three routes listed (§5)
- [ ] Queue workers restarted (§6)
- [ ] All 13 smoke tests pass (§7)
- [ ] Kill switch understood and tested on staging if available (§8)
- [ ] Managers granted the permissions they should hold (§4)
