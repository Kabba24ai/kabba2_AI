# Goodwill Adjustment — production deployment runbook

**Status: NOT DEPLOYED.** Nothing in this document has been executed against production.

Governing decisions: `FINANCIAL_DECISIONS.md` (FD-002 and Amendments 1–7). Design: `GOODWILL_ADJUSTMENT_DESIGN.md`. Backfill gate detail: `SPECIAL_TAX_COLUMNS_DEPLOYMENT_PLAN.md`.

Read §9 before starting. The most important fact in this runbook is that **the first production audit run is the first time this backfill has ever seen real historical data.**

---

## 1. Release identity

| | |
|---|---|
| Base commit | `1f1948d6` |
| Head / final lifecycle fix | `7d9bd5fc` |
| Commits in release | **9** |
| Branch | `feature/goodwill-adjustment` → `raj_development` |

### Ordered commit list

| # | Commit | Subject |
|---|---|---|
| 1 | `e3773365` | financial: add historical tax basis resolver |
| 2 | `ab482ad9` | financial: fix refund tax basis reconstruction |
| 3 | `a2faadea` | financial: restore extension child refund basis |
| 4 | `78020401` | financial: add goodwill calculation domain |
| 5 | `38a0f146` | financial: persist current special tax and added fees |
| 6 | `85187b24` | financial: guard goodwill apply and reversal |
| 7 | `cbd23c85` | financial: supersede goodwill receipts |
| 8 | `d61dc897` | financial: add goodwill pending-payment workflow |
| 9 | `7d9bd5fc` | financial: keep adjusted orders refundable |

### Fast-forward confirmation

`origin/raj_development` is still at the base commit — verified:

```bash
git rev-list --count origin/raj_development ^1f1948d6   # → 0
git merge-base --is-ancestor origin/raj_development HEAD # → exit 0
```

**No rebase or merge commit is required.** Re-verify both immediately before deploying; if `raj_development` has moved, STOP and re-plan — the migration ordering below assumes this exact ancestry.

### Migration order (exactly four, in this sequence)

| # | Migration | Effect |
|---|---|---|
| 1 | `2026_08_01_000001_create_order_goodwill_adjustments_table` | New table, 30 columns |
| 2 | `2026_08_01_000002_rename_total_paid_columns_on_order_goodwill_adjustments_table` | `total_paid_before` → `payments_accepted`; drops `total_paid_after` |
| 3 | `2026_08_02_000001_add_current_special_tax_and_added_fees_columns` | 4 columns + **data backfill** |
| 4 | `2026_08_02_000002_add_supersession_columns_to_receipts_table` | 3 columns on `receipts` |

**#4 depends on #1** (FK `receipts.goodwill_adjustment_id` → `order_goodwill_adjustments`). **#3 is the only one that writes data.** Order validated locally by `migrate:fresh`.

---

## 2. Pre-deployment checks

Run every command and record the output in the release-evidence file.

```bash
# Environment identity — confirm you are on the right host and app
hostname; whoami; pwd
php -v | head -1
php artisan --version
php artisan tinker --execute="echo config('app.env'), ' ', config('database.connections.mysql.database');"
mysql -h "$DB_HOST" -u "$DB_USER" -p -e "SELECT VERSION();"

# Disk — the backup and the ALTERs both need room
df -h .

# Git state
git rev-parse --abbrev-ref HEAD
git status --porcelain          # MUST be empty
git fetch origin
git log -1 --format="%H %s"     # ← RECORD THIS: current production commit
```

Local development ran PHP 8.5.9 and MySQL 9.7.1. **Confirm production is not older than the project's `composer.json` floor before proceeding**; do not assume parity with development.

### Queue state

Migration #3 rewrites `orders` and `order_products` rows. A worker mid-job against those tables during the ALTER is a needless risk.

```bash
php artisan queue:monitor default          # depth before starting
ps aux | grep -c "[q]ueue:work"            # running workers
```

Let the queue drain, or pause workers, before migrating. Record the depth either way.

### Rollback tag

Create this **before** anything changes. It is the primary recovery mechanism.

```bash
PROD_COMMIT=$(git rev-parse HEAD)
git tag -a "pre-goodwill-$(date +%Y%m%d-%H%M)" "$PROD_COMMIT" \
  -m "Production state immediately before the Goodwill release ($PROD_COMMIT)"
git push origin "pre-goodwill-$(date +%Y%m%d-%H%M)"
git tag -l "pre-goodwill-*"    # confirm it exists
```

Record the tag name. **Do not proceed without it.**

### Database backup

```bash
STAMP=$(date +%Y%m%d-%H%M)
BACKUP="/backups/kabba2-pre-goodwill-${STAMP}.sql.gz"

mysqldump -h "$DB_HOST" -u "$DB_USER" -p \
  --single-transaction --quick --routines --triggers --events \
  --databases "$DB_NAME" | gzip > "$BACKUP"

echo "mysqldump exit: ${PIPESTATUS[0]}"    # MUST be 0
```

`--single-transaction` gives a consistent snapshot without locking the site out.

**Verify it independently — a backup that has never been read is not a backup:**

```bash
ls -lh "$BACKUP"                                  # size — record it
gzip -t "$BACKUP" && echo "archive intact"        # MUST pass
sha256sum "$BACKUP"                               # record checksum
zcat "$BACKUP" | grep -c "^CREATE TABLE"          # record table count
zcat "$BACKUP" | tail -5 | grep -i "dump completed"  # MUST be present
```

The trailing `-- Dump completed` line is the only proof mysqldump reached the end rather than dying mid-stream.

**Restore command (record it alongside the backup — see §8 before using it):**

```bash
zcat /backups/kabba2-pre-goodwill-<STAMP>.sql.gz | mysql -h "$DB_HOST" -u "$DB_USER" -p
```

> **DO NOT BEGIN DEPLOYMENT** unless `mysqldump` exited `0`, `gzip -t` passed, the completion marker is present, and the table count is plausible.

Record: filename · size · SHA-256 · table count · restore command.

---

## 3. Mandatory production audit

Run **before** any migration. Strictly read-only — every operation beneath it is a `SELECT`.

```bash
STAMP=$(date +%Y%m%d-%H%M)
EVIDENCE="/releases/goodwill-${STAMP}"
mkdir -p "$EVIDENCE"

php artisan diagnostics:special-tax-backfill-audit --show-ids \
  2>&1 | tee "$EVIDENCE/backfill-audit.txt"
```

Keep `backfill-audit.txt` with the release evidence permanently. It is the record of what the backfill was allowed to touch.

### Gate — all three MUST be zero

```text
missing_json_unexplained         = 0
unreconciled                     = 0
lineless_with_nonzero_residual   = 0
```

The command prints `DEPLOYMENT GATE PASSED` when, and only when, all three are zero. Anything else prints `DEPLOYMENT GATE FAILED`.

| Category | Meaning |
|---|---|
| `missing_json_unexplained` | A line's `product_data` is unreadable **and** the order owes a nonzero residual |
| `unreconciled` | Every snapshot parsed, but reconstructed components disagree with the residual |
| `lineless_with_nonzero_residual` | No lines at all, **and** a nonzero residual. Tracked separately because "no lines" does not prove "no special tax or fees" |

The residual each order must explain is `grand_total − (subtotal + tax_amount − discount_amount)`, which *is* special tax plus added fees by construction of `CartHelper::buildCart()`.

### If the gate fails — triage

1. **STOP. Do not migrate.** The gate is the decision, not a warning.
2. **Preserve `backfill-audit.txt` unmodified.**
3. **Inspect the listed order IDs** (`--show-ids` lists each with its residual and what was reconstructed).
4. **Classify each transaction shape.** What kind of order is it, and where did the unexplained residual come from? Group by shape, not by ID.
5. **Do NOT edit production data ad hoc.** A hand-patched row is indistinguishable from a correct one afterwards, and destroys the audit trail this gate exists to produce.
6. **Amend the reconstruction logic in `SpecialTaxColumnBackfill`, or define an explicit named exclusion rule** for that shape, with a test.
7. **Re-run the audit from the beginning.** Not from the failing subset — the whole population, so the new rule is proven against everything.

Declined rows would keep the column default and still fail `HistoricalTaxBasisResolver` afterwards, exactly as they do today — a useful safety net, but **not an acceptable planned outcome**. A migration that knowingly leaves financially active orders unreadable to refund reconstruction requires explicit sign-off from the operator, recorded in §10.

---

## 4. Deployment sequence

```bash
# 4.1 — Re-verify the fast-forward assumption
git fetch origin
git rev-list --count origin/raj_development ^1f1948d6    # MUST be 0
git checkout raj_development
git merge --ff-only origin/feature/goodwill-adjustment
git log -1 --format="%H %s"                              # expect 7d9bd5fc

# 4.2 — Maintenance mode: ONLY if this deployment already uses it safely.
#       If it is not part of the existing process, SKIP IT. Introducing an
#       untested maintenance mechanism during a financial release adds a new
#       failure mode instead of removing one. The migrations below are
#       additive (see the caveat under 4.3), so brief concurrent traffic is
#       tolerable; an unreachable site because --secret was misconfigured is
#       not.
# php artisan down --render="errors::503" --retry=60

# 4.3 — Migrate
php artisan migrate --force 2>&1 | tee "$EVIDENCE/migrate.txt"
```

> **Caveat on "additive".** Migrations #1 and #4 are purely additive. #2 **drops** `order_goodwill_adjustments.total_paid_after` — harmless, since that table is new in this same release and holds no production rows. #3 adds columns and **writes data**. Nothing existing is dropped or narrowed.

```bash
# 4.4 — Caches
php artisan config:clear && php artisan config:cache
php artisan route:clear  && php artisan route:cache
php artisan view:clear   && php artisan view:cache
php artisan event:clear  && php artisan event:cache

# 4.5 — Permissions: new IAM module must be seeded, or the authorising-manager
#       list is empty and nobody can be selected. Idempotent.
php artisan db:seed --class="Database\\Seeders\\Iam\\ModuleSeeder" --force \
  2>&1 | tee "$EVIDENCE/seed.txt"

# Spatie caches its permission map; it must be told the map changed.
php artisan permission:cache-reset

# 4.6 — Workers pick up new code only on restart
php artisan queue:restart
ps aux | grep -c "[q]ueue:work"

# 4.7 — Back into service (only if 4.2 was used)
# php artisan up
```

> **Do not use `migrate:rollback` as the primary recovery mechanism.** Migration #3's `down()` drops the special-tax/fee columns, which discards reconstructed data and makes any order adjusted in the meantime permanently unreconcilable. Migration #1's `down()` destroys the ability to reverse any applied adjustment. Recovery is §8: revert code, leave schema.

After 4.5, grant `goodwill.apply` / `goodwill.reverse` to the intended manager role through the normal IAM screen. Until that is done the feature is inert — which is a safe default, not a bug.

---

## 5. Post-migration verification

All read-only. Record every result.

```sql
-- 5.1 Order-level special tax equals the sum of its lines. EXPECT 0 ROWS.
SELECT o.id, o.special_tax_amount, COALESCE(SUM(p.special_tax),0) AS line_special_tax
FROM orders o LEFT JOIN order_products p ON p.order_id = o.id
GROUP BY o.id, o.special_tax_amount
HAVING ABS(o.special_tax_amount - line_special_tax) > 0.001;

-- 5.2 Order-level added fees equal the sum of its lines. EXPECT 0 ROWS.
SELECT o.id, o.added_fees_amount, COALESCE(SUM(p.added_fees),0) AS line_added_fees
FROM orders o LEFT JOIN order_products p ON p.order_id = o.id
GROUP BY o.id, o.added_fees_amount
HAVING ABS(o.added_fees_amount - line_added_fees) > 0.001;

-- 5.4 All four migrations recorded. EXPECT 4 ROWS.
SELECT migration, batch FROM migrations
WHERE migration LIKE '2026_08_0%goodwill%'
   OR migration LIKE '2026_08_0%special_tax%'
   OR migration LIKE '2026_08_0%supersession%'
ORDER BY id;

-- 5.5 New permissions exist. EXPECT 3 ROWS.
SELECT name, guard_name FROM permissions
WHERE name IN ('goodwill.apply','goodwill.reverse','goodwill.view');

-- 5.6 No order has two active adjustments. EXPECT 0 ROWS.
SELECT order_id, COUNT(*) FROM order_goodwill_adjustments
WHERE reversed_at IS NULL GROUP BY order_id HAVING COUNT(*) > 1;

-- 5.7a No receipt supersedes itself. EXPECT 0 ROWS.
SELECT id FROM receipts WHERE superseded_receipt_id = id;

-- 5.7b Every supersession points at an EARLIER receipt for the SAME order.
--      EXPECT 0 ROWS. This is what makes the chain acyclic.
SELECT r.id, r.superseded_receipt_id
FROM receipts r JOIN receipts prior ON prior.id = r.superseded_receipt_id
WHERE r.superseded_receipt_id >= r.id OR prior.order_id <> r.order_id;

-- 5.7c No two receipts supersede the same predecessor (no forked chain).
--      EXPECT 0 ROWS.
SELECT superseded_receipt_id, COUNT(*) FROM receipts
WHERE superseded_receipt_id IS NOT NULL
GROUP BY superseded_receipt_id HAVING COUNT(*) > 1;
```

```bash
# 5.3 No declined category appeared. Re-running the audit AFTER the migration
#     must report the same all-zero gate: the migration performs the identical
#     reconstruction, so the two cannot disagree.
php artisan diagnostics:special-tax-backfill-audit --show-ids \
  2>&1 | tee "$EVIDENCE/backfill-audit-post.txt"
diff "$EVIDENCE/backfill-audit.txt" "$EVIDENCE/backfill-audit-post.txt"
```

Any nonzero result in 5.1, 5.2, 5.6, 5.7a–c, or any divergence in 5.3 → **§7 rollback criteria**.

---

## 6. Live smoke tests

Use controlled orders on real production. **Record every order ID, receipt ID, and adjustment ID in the evidence file.** Tests 1–3 are first deliberately: they prove the checkout writer before anything is adjusted.

Reference tax rate below is 9.75%; substitute the configured rate. "Fees" are per-unit and **never reduced by Goodwill**.

| # | Scenario | Expected |
|---|---|---|
| 1 | **Ordinary checkout**, $200.00, no special tax, no fees | subtotal 200.00 · tax 19.50 · special 0.00 · fees 0.00 · goodwill n/a · **total 219.50** · unpaid · no receipt yet · refund ceiling 0.00 |
| 2 | **Special-tax checkout**, $200.00 with `apply_special_tax` | subtotal 200.00 · tax 19.50 · **special > 0.00** · fees 0.00 · total = 219.50 + special · both order and line columns populated and equal |
| 3 | **Added-fee checkout**, qty 2 with `apply_added_fees` | subtotal per pricing · tax per rate · special 0.00 · **fees = unit fee × 2** · total includes fees · `orders.added_fees_amount` = Σ line `added_fees` |
| 4 | **Ordinary short pay + Goodwill** — order #1, collect $185.00, apply Goodwill | subtotal **168.56** · tax **16.44** · special 0.00 · fees 0.00 · **goodwill 31.44** · **total 185.00** · **Paid in full**, balance 0.00 · adjusted receipt current · refund ceiling **185.00**, tax portion **16.44** |
| 5 | **Zero-tax Goodwill** — tax-exempt order $200.00, collect $185.00 | subtotal **185.00** · **tax 0.00** (reducing an untaxed order must never create tax) · goodwill 15.00 · total 185.00 · Paid · refund ceiling 185.00, tax portion 0.00 |
| 6 | **Mixed taxable / tax-free Goodwill** — $100 taxable + $100 tax-free, total 209.75, collect $190.00 | total **190.00** · Paid · taxable line keeps tax · **tax-free line still reduced but tax stays 0.00** · rate derived from the taxable basis only, never from subtotal |
| 7 | **Receipt supersession** — generate a receipt on an order, then apply Goodwill | **3 rows only after a reversal**; after apply expect **2**: original untouched (`total` unchanged, items unchanged) · new receipt `superseded_receipt_id` = original · `goodwill_amount` = concession · printed receipt shows the Goodwill line · `subtotal` on the new receipt is the **original** merchandise figure · identity `subtotal − goodwill + sales_tax = total` |
| 8 | **Refund after Goodwill** — refund order #4 | resolves against the **revised** basis · rate unchanged at 9.75% · **tax refunded capped at 16.44** · refund cannot exceed 185.00 |
| 9 | **Goodwill reversal** — reverse on a fresh adjusted order | subtotal/tax/special/total all restored · **balance reopens** · **payment row count unchanged** · third receipt issued superseding the adjusted one · order refundable again |
| 10 | **Extension-child refund** — refund an extension child order | basis resolves from the linked `billing_charges` row · rate correct · **not** derived from `orders.subtotal` |
| 11 | **Sales Tax Report** over the window | taxable sales and tax **reduced** by the Goodwill · engine unchanged (reads live order totals) |
| 12 | **Payment Reconciliation Ledger** | collections = **actual money received** ($185.00), unchanged by the concession · Goodwill appears nowhere as tender |
| 13 | **Pure Sales Summary** | revenue reduced by the concession |
| 14 | **Product Sales Performance** | line revenue reduced (reads `order_products.sub_total`) · **unit `price` unchanged** — Goodwill is a concession, not a repricing |

**Non-negotiable across every test:** no `order_payments` row is ever created by Goodwill; `product_data` JSON is never modified; the customer's real tender is never altered.

---

## 7. Fix forward vs. roll back

### Fix forward ONLY when all four hold

- The defect is **isolated** — one code path, understood, not a class of failures.
- The **financial effect is fully understood** — you can name every affected order.
- **No incorrect external artifact was produced** — no wrong payment, refund, receipt, invoice, or AR entry has reached a customer or the ledger.
- A **tested** correction can be deployed **within a few hours**.

If any one of these is uncertain, it is not a fix-forward situation.

### Roll back or disable IMMEDIATELY for

- Incorrect **tax** or **grand totals** on any order.
- **Checkout failure** or checkout writing wrong column values.
- **Payment or refund corruption** — any discrepancy between recorded and actual money.
- **Backfill reconciliation failure** — §5.1/5.2 returning rows.
- **Stale or misleading receipts** — a superseded receipt still being served as current.
- **Inability to refund or reverse safely** (the exact class of defect commit `7d9bd5fc` fixed).
- **Unexplained production data mutation** — anything changed that this release does not account for.

The fastest mitigation is almost always **§8.1 alone**: revoke the permission. That disables the feature in seconds without touching code or schema.

---

## 8. Rollback plan

**Primary rollback is code-only. The schema stays.**

```bash
# 8.1 — FIRST AND FASTEST: disable the action.
#       Revoking goodwill.apply makes every apply refuse server-side
#       immediately, and the UI panel stops offering it. No deploy required.
php artisan tinker --execute="
  \App\Models\Iam\Personnel\User::permission('goodwill.apply')->get()
    ->each(fn(\$u) => \$u->revokePermissionTo('goodwill.apply'));"
php artisan permission:cache-reset

# 8.2 — Revert code to the rollback tag
git checkout raj_development
git reset --hard <pre-goodwill-TAG>

# 8.3 — Rebuild and restart
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart

# 8.4 — LEAVE THE SCHEMA IN PLACE. Run no rollback migration.
```

### Why the schema stays

The added columns are **additive and inert** to the reverted code — it never reads or writes them. Dropping them is the destructive option:

- Dropping `orders.special_tax_amount` / `added_fees_amount` and the `order_products` pair **discards the reconstructed data** and makes any order adjusted in the meantime permanently unreconcilable.
- Dropping `order_goodwill_adjustments` **destroys the ability to reverse any applied adjustment**, and with it the audit record of what management approved.
- Dropping the receipt supersession columns collapses the chain: every receipt becomes indistinguishable from an original and "which is current?" degrades to "the newest row", which is only accidentally correct.

**Never drop:** `order_goodwill_adjustments` · `receipts.superseded_receipt_id` / `goodwill_adjustment_id` / `goodwill_amount` · `orders.special_tax_amount` / `added_fees_amount` · `order_products.special_tax` / `added_fees`.

### Database restore — last resort only

Reserved for **actual data corruption that cannot be safely repaired in place**. A restore rewinds the database to the backup moment, **erasing every transaction recorded since** — including real customer payments taken during the window.

**Before restoring, identify what would be lost:**

```sql
-- Substitute the backup's wall-clock time.
SET @cutoff = '<BACKUP TIMESTAMP>';

-- Real money taken since the backup. THESE WOULD BE ERASED.
SELECT id, order_id, payment_method, amount, status, created_at
FROM order_payments WHERE created_at >= @cutoff ORDER BY id;

-- Orders placed since the backup.
SELECT id, order_number, grand_total, created_at
FROM orders WHERE created_at >= @cutoff ORDER BY id;

-- Refunds, AR postings, invoices, receipts, adjustments since the backup.
SELECT 'refund_alloc' src, id, created_at FROM order_payment_refund_allocations WHERE created_at >= @cutoff
UNION ALL SELECT 'customer_account', id, created_at FROM customer_accounts WHERE created_at >= @cutoff
UNION ALL SELECT 'invoice',   id, created_at FROM invoices  WHERE created_at >= @cutoff
UNION ALL SELECT 'receipt',   id, created_at FROM receipts  WHERE created_at >= @cutoff
UNION ALL SELECT 'goodwill',  id, created_at FROM order_goodwill_adjustments WHERE created_at >= @cutoff
ORDER BY created_at;
```

**Export those rows to the evidence directory before restoring.** They must be re-entered by hand afterwards, and a payment that cannot be reconstructed is money the business cannot account for. If any real payment appears in that list, restoring is almost certainly the wrong call — prefer targeted repair with §8.1 already applied.

---

## 9. Known baselines and accepted limitations

### Pre-existing test failures — must remain **exactly** these counts

Every one was verified pre-existing by stashing the release and re-running.

| Suite | Failing / Total | Note |
|---|---|---|
| `tests/Feature/Billing` | **1** / 40 | |
| `tests/Feature/BillingEngine` | **27** / 179 | |
| `tests/Feature/Reports` | **21** / 89 | |
| `tests/Feature/OrderManagement` | **4** / 84 | all in `RefundedOrderScheduleClosureTest` |
| `tests/Unit/Services` | **10** / 200 | |
| `tests/Feature/Customers` | **1** / 2 | |
| `tests/Feature/Dashboard` | **2** / 58 | |
| Refund gate (11 files) | **1** / 140 | `RefundIdempotencyTest` — 422-vs-500 |
| `tests/Feature/Cart` | **0** / 31 | clean |
| `tests/Feature/Crm` | **0** / 23 | clean |
| Goodwill (7 files) | **0** / 92 | clean, 371 assertions |

Any *additional* failure is a regression from this release, not a baseline.

> `php artisan test` across all directories at once exhausts the 128M memory limit, and `-d memory_limit` does not help because the subprocess does not inherit it. **Run by directory.**

### Accepted limitations

1. **The global `Gate::before(fn () => true)` in `AppServiceProvider::gatesRegistration()` is UNCHANGED and outside this release.** Every `can()` / ability check in the application returns true for any signed-in user. This is a documented small-business posture, not a bug introduced here — but it means route middleware and `can()` are decorative application-wide.
2. **Goodwill therefore enforces permissions directly through Spatie** (`hasPermissionTo()`), bypassing the Gate, so FD-002 §7.3 is honoured without altering the application-wide posture. Removing that global bypass is a far wider decision than this feature.
3. **The first production audit run is the first validation against real historical data.** Both local databases are empty (`rc_kabba_testing` is truncated per test; the dev database holds 1 order, 0 lines). The backfill's category logic is proven by 15 seeded tests covering every branch, but its behaviour on real historical shapes is genuinely unknown until §3 runs.
4. **Production is the validation environment.** No realistic sandbox exists. This is why §3 is a hard gate rather than advisory, why the backup must be independently verified, and why the smoke tests use controlled real orders whose IDs are recorded.
5. **Goodwill on an order with $0.00 collected is unsupported** — that is a write-off, undecided per Truth Table §5.9. Both the preview and apply paths refuse it.
6. **`TaxCalculationService` has a float contract**, recorded as technical debt. Goodwill normalizes to integer cents at its own boundaries and adds no new floating-point logic.
7. **AR-posted and invoiced orders cannot receive Goodwill at all** (FD-002 Am.5). The remedy is a credit-memo / account-adjustment workflow that does not exist yet.

---

## 10. Operator sign-off

Each line is signed only after the evidence is in `$EVIDENCE`.

```text
[ ]  Backup verified
       file ______________________  size ________  sha256 ________________
       gzip -t passed [ ]   completion marker present [ ]   table count ____

[ ]  Audit clean
       missing_json_unexplained ____   unreconciled ____
       lineless_with_nonzero_residual ____
       DEPLOYMENT GATE PASSED [ ]      evidence file ____________________

[ ]  Rollback tag confirmed
       tag ______________________  pushed to origin [ ]  commit ____________

[ ]  Migration approved
       approver ____________________  time __________
       all 4 migrations recorded [ ]   §5.1–5.7 all returned 0 rows [ ]

[ ]  Smoke tests passed
       order IDs ______________________________________________
       receipt IDs ____________________________________________
       adjustment IDs _________________________________________
       tests 1–14 pass [ ]    any deviation recorded below [ ]

[ ]  Reports verified
       Sales Tax [ ]   Reconciliation Ledger [ ]
       Pure Sales Summary [ ]   Product Sales Performance [ ]

[ ]  Baselines unchanged
       no new test failures beyond §9 [ ]

[ ]  RELEASE ACCEPTED          signed ____________  time __________
[ ]  RELEASE ROLLED BACK       signed ____________  time __________
       reason _________________________________________________
       §8.1 permission revoked [ ]   code reverted to tag [ ]
       schema left in place [ ]      database restored [ ] (last resort only)
```

**Deviations, surprises, and anything not anticipated by this runbook:**

```text
______________________________________________________________________
______________________________________________________________________
______________________________________________________________________
```
