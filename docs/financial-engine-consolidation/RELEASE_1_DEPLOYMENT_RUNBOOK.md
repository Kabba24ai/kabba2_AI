# Release 1 — deployment runbook

**Shared pre-tax adjustment engine and Store Credit special-tax correction.**

**Status: NOT DEPLOYED.** Nothing here has been executed against production.

> This runbook is for **Release 1 only**. Goodwill is **not** in this release and no Goodwill instruction appears below. `GOODWILL_DEPLOYMENT_RUNBOOK.md` is a separate document for a separate, later release; it references a stale repository and must not be used for this deployment.

Companions: `RELEASE_1_SMOKE_TESTS.md` · `RELEASE_1_ROLLBACK.md` · `RELEASE_1_RELEASE_NOTES.md` · `RELEASE_2_BACKLOG.md`

---

## 1. Release identity

| | |
|---|---|
| Production repository | `RajChotaliya/Kaaba2` (remote `production`) |
| Production baseline | **`d8591af5`** |
| Release head | **`0a7dcaad`** |
| Branch | `feature/goodwill-production-rebaseline` |
| Commits | **6** (1 documentation, 5 code) |
| New permissions | **none** — no permission refresh step |

### Commit chain

| # | Commit | Subject |
|---|---|---|
| 1 | `75d6ed7c` | docs: define shared pretax adjustment release |
| 2 | `0a9d9991` | financial: persist current special tax and added fees |
| 3 | `deef7e2d` | financial: correct special tax under pretax discounts |
| 4 | `798b1704` | financial: persist line-level pretax discount allocations |
| 5 | `61a5b71f` | financial: report net product revenue after pretax discounts |
| 6 | `0a7dcaad` | financial: harden shared pretax adjustment lifecycle |

Re-verify the fast-forward immediately before deploying:

```bash
git fetch production
git merge-base --is-ancestor production/raj_development HEAD && echo "CLEAN FAST-FORWARD"
```

If `raj_development` has moved since `d8591af5`, **STOP** — the migration ordering below assumes this ancestry.

### Migration order — exactly three

| # | Migration | Effect |
|---|---|---|
| 1 | `2026_08_02_000001_add_current_special_tax_and_added_fees_columns` | 4 columns + **data backfill** from frozen `product_data` |
| 2 | `2026_08_02_000002_add_special_tax_before_discount_to_orders` | 1 column + **data backfill** from `special_tax_amount` |
| 3 | `2026_08_02_000003_create_order_product_discount_allocations_table` | new table + 2 columns |

Order validated by `migrate:fresh` and by a three-step rollback and re-apply.

---

> ## ⚠ ORDERING DEPENDENCY — READ BEFORE MIGRATING
>
> **Migration 2 must run before the corrected pricing code re-prices any pre-existing discounted order.**
>
> It backfills `orders.special_tax_before_discount` by copying `special_tax_amount`. That copy is **exact only while `special_tax_amount` still holds the ORIGINAL figure** — which is true today precisely *because* the defect being fixed never reduced it.
>
> - **Deploying migrations and code together is SAFE.** Migrations run first.
> - **Running migration 2 LATER is UNSAFE.** On a database where the corrected `OrderDiscountTarget` has already re-priced a pre-existing discounted order, it would copy an **already-reduced** figure in as the "original", and every subsequent adjustment would scale that reduced value again — compounding the error downward.
>
> The hazard applies only to orders discounted **before** this release. `captureOriginalSnapshotOnce()` fires only when `grand_total_before_discount` is null, which is already non-null for those orders, so this backfill is their only source of a special-tax original.
>
> The `whereNull` guard makes a re-run idempotent but **cannot detect an already-reduced value**. If migration 2 is ever found to have run out of order, recovery is to restore each order's original from its lines' frozen `product_data` — **not** to re-run it.
>
> **Never deploy the code without the migrations, and never migrate a database the code has already been running against.**

---

## 2. Pre-deployment

### 2.0 Evidence directory — create first, outside the app tree

```bash
STAMP=$(date +%Y%m%d-%H%M)
EVIDENCE="/releases/release1-${STAMP}"     # substitute a real path OUTSIDE the release directory
mkdir -p "$EVIDENCE"
```

Required contents by the end:

```text
01-pre-deploy-commit.txt      06-migrate.txt
02-rollback-tag.txt           07-verification-queries.txt
03-backup-verification.txt    08-smoke-test-results.txt
04-backup-checksum.txt        09-informational-audits.txt
05-backfill-audit.txt         10-final-commit.txt · 11-signoff.txt
```

### 2.1 Environment capture

```bash
hostname; whoami; pwd
php -v | head -1
php artisan --version
php artisan tinker --execute="echo config('app.env'),' ',config('database.connections.mysql.database');"
mysql -h "$DB_HOST" -u "$DB_USER" -p -e "SELECT VERSION();"
df -h .; df -h /backups; df -h /var/lib/mysql
git rev-parse --abbrev-ref HEAD
git status --porcelain            # MUST be empty
git log -1 --format="%H %s" | tee "$EVIDENCE/01-pre-deploy-commit.txt"
```

### 2.2 Size and duration

Migrations 1 and 2 read and write every order; migration 3 only adds structure.

```bash
mysql -h "$DB_HOST" -u "$DB_USER" -p -e "
  SELECT (SELECT COUNT(*) FROM orders) AS orders,
         (SELECT COUNT(*) FROM order_products) AS order_products,
         (SELECT COUNT(*) FROM orders WHERE pretax_discount_total > 0) AS discounted_orders;"
```

The backfill chunks orders in 500s and writes in transactions of 200, so memory is bounded regardless of row count. Expect **well under a minute** for a database of this size. Treat anything past five minutes as a signal to investigate — **do not interrupt a running migration.**

### 2.3 Queue handling

Migrations 1 and 2 rewrite `orders` and `order_products`. Discover the real service names — **do not guess them during deployment.**

```bash
ps -eo pid,etime,cmd | grep "[q]ueue:work"
sudo supervisorctl status 2>/dev/null || true
systemctl list-units --type=service | grep -i queue 2>/dev/null || true
php artisan queue:monitor default        # record the depth
```

```bash
# 1. Stop new work (use the ACTUAL group name discovered above)
sudo supervisorctl stop <ACTUAL_WORKER_GROUP>
#    or, if unsupervised, let workers exit at the next job boundary:
php artisan queue:restart

# 2. Wait for current jobs to FINISH — never kill mid-write
watch -n2 'ps -eo pid,etime,cmd | grep "[q]ueue:work" | wc -l'     # → 0

# 3. Confirm nothing is still writing orders
mysql -h "$DB_HOST" -u "$DB_USER" -p -e "
  SELECT id,user,db,command,time,state,LEFT(info,120) AS query
  FROM information_schema.processlist
  WHERE db='$DB_NAME' AND command <> 'Sleep';"
```

### 2.4 Maintenance mode — NOT used

`php artisan down` is not part of the proven deployment process; introducing it during a financial release adds an untested failure mode. Controls instead: drained queue, no active admin use during the window, direct deploy.

### 2.5 Rollback tag — create before anything changes

```bash
PROD_COMMIT=$(git rev-parse HEAD)
TAG="pre-release1-$(date +%Y%m%d-%H%M)"
git tag -a "$TAG" "$PROD_COMMIT" -m "Production state immediately before Release 1 ($PROD_COMMIT)"
git push production "$TAG"
git tag -l "pre-release1-*" | tee "$EVIDENCE/02-rollback-tag.txt"
```

**Do not proceed without it.**

### 2.6 Database backup

The backup must live **outside** the application release directory — deployment cleanup must not be able to remove it.

```bash
BACKUP_DIR=/backups                        # substitute the real path
case "$(readlink -f "$BACKUP_DIR")" in
  "$(readlink -f "$(pwd)")"*) echo "REFUSE: backup dir is inside the app tree";;
  *) echo "OK: outside the app tree";;
esac
ls -ld "$BACKUP_DIR"                       # record owner / group / mode

BACKUP="${BACKUP_DIR}/kabba2-pre-release1-${STAMP}.sql.gz"
mysqldump -h "$DB_HOST" -u "$DB_USER" -p \
  --single-transaction --quick --routines --triggers --events \
  --databases "$DB_NAME" | gzip > "$BACKUP"
echo "mysqldump exit: ${PIPESTATUS[0]}"    # MUST be 0
```

Verify it — a backup nobody has read is not a backup:

```bash
{
  ls -lh "$BACKUP"
  gzip -t "$BACKUP" && echo "archive intact"
  zcat "$BACKUP" | grep -c "^CREATE TABLE"
  zcat "$BACKUP" | tail -5 | grep -i "dump completed"     # MUST be present
} | tee "$EVIDENCE/03-backup-verification.txt"

sha256sum "$BACKUP" | tee "$EVIDENCE/04-backup-checksum.txt"
```

Record: absolute path · database name · MySQL host · directory owner/group/mode · who can read it · confirmation it is outside the app tree.

**DO NOT PROCEED** unless `mysqldump` exited `0`, `gzip -t` passed, and the completion marker is present.

---

## 3. HARD GATE — pre-migration backfill audit

The command ships in this release, so the code must be fetched before it can run — but it must run **before** migrating. That intermediate state is deliberate and is proven safe: the command reads only `orders` (`id, subtotal, tax_amount, discount_amount, grand_total, pretax_discount_total`) and `order_products` (`id, order_id, product_data`), every one of which exists on the current production schema.

```bash
git fetch production
git checkout raj_development
git merge --ff-only <release-head>
composer dump-autoload --no-scripts

php artisan list | grep special-tax-backfill-audit       # confirm present

php artisan diagnostics:special-tax-backfill-audit --show-ids \
  2>&1 | tee "$EVIDENCE/05-backfill-audit.txt"
```

> If the command errors mentioning an **unknown column**, STOP immediately — that falsifies the premise above and the audit cannot be trusted pre-migration.

### The gate — all three must be zero

```text
missing_json_unexplained         = 0
unreconciled                     = 0
lineless_with_nonzero_residual   = 0
```

The command prints `DEPLOYMENT GATE PASSED` only when all three are zero.

### If the gate fails

1. **STOP. Do not migrate.** The gate is the decision, not a warning.
2. Preserve `05-backfill-audit.txt` unmodified.
3. Inspect the listed order IDs (`--show-ids` gives each residual and what was reconstructed).
4. Classify each transaction **shape**, not each ID.
5. **Do not edit production data ad hoc** — a hand-patched row is indistinguishable from a correct one afterwards and destroys the audit trail this gate exists to produce.
6. Amend `SpecialTaxColumnBackfill`, or define an explicit named exclusion rule, with a test.
7. Re-run the audit against the **whole** population, not the failing subset.

---

## 4. Deployment sequence

```bash
# 4.1 — Code is already fetched (section 3). Confirm the head.
git log -1 --format="%H %s"

# 4.2 — Migrate
php artisan migrate --force 2>&1 | tee "$EVIDENCE/06-migrate.txt"

# 4.3 — Caches
php artisan config:clear && php artisan config:cache
php artisan route:clear  && php artisan route:cache
php artisan view:clear   && php artisan view:cache
php artisan event:clear  && php artisan event:cache

# 4.4 — Workers pick up new code only on restart
sudo supervisorctl start <ACTUAL_WORKER_GROUP>
php artisan queue:restart
ps -eo pid,cmd | grep -c "[q]ueue:work"

# 4.5 — Record the deployed commit
git log -1 --format="%H %s" | tee "$EVIDENCE/10-final-commit.txt"
```

**No permission seeding step.** This release adds no permissions and touches no seeder. `ModuleSeeder` must not be run — it reconciles the whole access-control table and deletes anything absent from its hardcoded list.

> **Do not use `migrate:rollback` as the recovery mechanism.** Migration 1's `down()` drops the special-tax columns, discarding reconstructed data; migration 3's drops the allocation ledger. Recovery is code-only — see `RELEASE_1_ROLLBACK.md`.

---

## 5. HARD GATE — post-migration verification

All read-only. Record everything in `$EVIDENCE/07-verification-queries.txt`.

```sql
-- 5.1 All three migrations recorded. EXPECT 3 ROWS.
SELECT migration, batch FROM migrations
WHERE migration LIKE '2026_08_02_00000%' ORDER BY id;

-- 5.2 Order-level special tax equals the sum of its lines. EXPECT 0 ROWS.
SELECT o.id, o.special_tax_amount, COALESCE(SUM(p.special_tax),0) AS line_special_tax
FROM orders o LEFT JOIN order_products p ON p.order_id = o.id
GROUP BY o.id, o.special_tax_amount
HAVING ABS(o.special_tax_amount - line_special_tax) > 0.001;

-- 5.3 Order-level added fees equal the sum of its lines. EXPECT 0 ROWS.
SELECT o.id, o.added_fees_amount, COALESCE(SUM(p.added_fees),0) AS line_added_fees
FROM orders o LEFT JOIN order_products p ON p.order_id = o.id
GROUP BY o.id, o.added_fees_amount
HAVING ABS(o.added_fees_amount - line_added_fees) > 0.001;

-- 5.4 Every discounted order has a special-tax snapshot. EXPECT 0 ROWS.
SELECT id FROM orders
WHERE grand_total_before_discount IS NOT NULL
  AND special_tax_before_discount IS NULL;

-- 5.5 Allocation identity holds for every order.
--     legacy_unallocated + active allocations = pretax_discount_total
--     EXPECT 0 ROWS.
SELECT o.id, o.pretax_discount_total, o.legacy_unallocated_pretax_discount,
       COALESCE(a.tracked, 0) AS tracked
FROM orders o
LEFT JOIN (
  SELECT order_id, SUM(allocated_amount) AS tracked
  FROM order_product_discount_allocations
  WHERE reversed_at IS NULL GROUP BY order_id
) a ON a.order_id = o.id
WHERE ABS(o.pretax_discount_total
          - o.legacy_unallocated_pretax_discount
          - COALESCE(a.tracked, 0)) > 0.001;

-- 5.6 Every pre-existing discount was classified as legacy. EXPECT 0 ROWS.
SELECT id FROM orders
WHERE pretax_discount_total > 0 AND legacy_unallocated_pretax_discount = 0
  AND id NOT IN (SELECT DISTINCT order_id FROM order_product_discount_allocations);
```

```bash
# 5.7 Re-run the backfill audit. The migration performs the identical
#     reconstruction, so the two cannot disagree.
php artisan diagnostics:special-tax-backfill-audit --show-ids \
  2>&1 | tee "$EVIDENCE/05b-backfill-audit-post.txt"
diff "$EVIDENCE/05-backfill-audit.txt" "$EVIDENCE/05b-backfill-audit-post.txt"
```

**Any nonzero result in 5.2–5.6, or any divergence in 5.7 → `RELEASE_1_ROLLBACK.md`.**

---

## 6. HARD GATE — smoke tests

See `RELEASE_1_SMOKE_TESTS.md`. Record every order ID in `$EVIDENCE/08-smoke-test-results.txt`.

The **decisive** one is Test 3 (order with special tax): it proves the over-collection defect is fixed.

---

## 7. INFORMATIONAL AUDITS — never block deployment

Run **after** the hard gates pass. These measure; they do not gate. The forward defect fix is already deployed and correct regardless of what they report.

```bash
{
  php artisan diagnostics:store-credit-special-tax-exposure --show-ids
} 2>&1 | tee "$EVIDENCE/09-informational-audits.txt"
```

**Historical exposure audit** — how much special tax was over-collected on orders discounted before the fix, per order and in aggregate, with a `SAFE` / `WORKFLOW` verdict each. This is the **first measurement against real data**. Remediation is a separate business decision; nothing here rewrites historical orders.

**Legacy revenue disclosure** — orders discounted before allocation tracking report **gross** product revenue, with the unattributed amount disclosed separately by `ProductSalesPerformanceEngine::legacyUnallocatedDiscount()`. Expect a nonzero figure if any order was discounted before this release. That is correct and expected, not a defect.

```sql
-- Scale of the legacy population, for the record.
SELECT COUNT(*) AS legacy_orders,
       SUM(legacy_unallocated_pretax_discount) AS unattributed_total
FROM orders WHERE legacy_unallocated_pretax_discount > 0;
```

---

## 8. Sign-off

```text
[ ] Backup verified      file ____________ sha256 ____________ outside app tree [ ]
[ ] Rollback tag pushed  tag ____________ commit ____________
[ ] BACKFILL AUDIT GATE PASSED (all three categories zero) [ ]
[ ] Migrations recorded (3) [ ]   Verification 5.2–5.6 all zero rows [ ]
[ ] Caches rebuilt [ ]            Workers restarted [ ]
[ ] Smoke tests 1–10 passed [ ]   order IDs recorded [ ]
[ ] Informational audits captured (non-blocking) [ ]
      exposure total $__________   legacy unattributed $__________

[ ] RELEASE ACCEPTED    signed __________ time __________
[ ] ROLLED BACK         signed __________ time __________  reason ____________
```

**Deviations and anything this runbook did not anticipate:**

```text
_______________________________________________________________
_______________________________________________________________
```
