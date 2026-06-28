# Rollback Plan

Audit date: 2026-06-27  
Branch: feature/billing-engine-consolidation  
Rollback commit: `68c8c74b` ("Checkpoint: before Billing Engine consolidation")

---

## Rollback Point

The checkpoint commit on `feature/billing-engine-consolidation` contains no code changes — it is a safe, empty marker commit. The base code state is identical to `gary_dev` at the time of branch creation.

---

## Git Rollback

### Full rollback (abandon all Billing Engine work)

```bash
git checkout gary_dev
git branch -D feature/billing-engine-consolidation
```

This leaves `gary_dev` exactly where it was before Phase 0. No production impact.

### Partial rollback (revert to checkpoint within the feature branch)

```bash
git checkout feature/billing-engine-consolidation
git reset --hard 68c8c74b
```

This discards all commits made after the checkpoint on the feature branch.

### Rollback a specific phase

Each phase should be committed as one or more discrete commits with clear commit messages (`Phase 2: route fuel charges through BillingEngine`). To roll back only Phase 2:

```bash
git revert <phase-2-commit-hash>..HEAD
```

This creates a new revert commit rather than rewriting history, which is safer if the branch has been pushed to remote.

---

## Database / Migration Rollback

### Phase 1 migration rollback (billing_charges table)

```bash
php artisan migrate:rollback --step=1
```

This drops the `billing_charges` table if the migration was the last one run. Verify with:

```bash
php artisan migrate:status
```

Before rolling back, confirm the table is empty (no production charges have been written to it yet).

### Phase 2/3 rollback (bridge columns on customer_accounts / order_extra_charges)

If columns `billing_charge_id` were added as nullable bridge FKs:

```bash
php artisan migrate:rollback --step=1
```

These columns are nullable with no NOT NULL constraint, so rolling them back has zero impact on existing data.

### Phase 4 rollback (extension order numbering fix)

The suffix logic change is code-only (no schema change). Revert the commit. Any extensions created with the new logic will have correct suffix assignments — rolling back the code does not change already-created order numbers.

### Emergency: table is live and has data

If `billing_charges` has been populated in production and a rollback is needed:
1. Do NOT drop the table — preserve the data for audit
2. Rename the table: `ALTER TABLE billing_charges RENAME TO billing_charges_rollback_YYYYMMDD`
3. Deploy the reverted code
4. Restore bridge writes if they were removed

---

## Data Preservation Strategy

### Before any migration runs in production

1. Take a full database backup:
   ```bash
   mysqldump -u root -p kabba2_db > backup_billing_engine_pre_YYYYMMDD.sql
   ```

2. Record current balance totals for spot-check after rollback:
   ```sql
   SELECT customer_id, SUM(amount) AS total_charges
   FROM customer_accounts
   WHERE type = 'charge'
   GROUP BY customer_id
   ORDER BY customer_id;
   ```

3. Record current `order_extra_charges` count by type:
   ```sql
   SELECT type, COUNT(*) FROM order_extra_charges GROUP BY type;
   ```

4. Record current extension order count:
   ```sql
   SELECT COUNT(*) FROM orders WHERE order_number LIKE '%-[A-Z]';
   ```

---

## Testing Checkpoints Before Each Phase

### Before Phase 2 (Fuel Charges)

- [ ] Create a fuel charge via Order Edit page → verify it appears in `customer_accounts` AND `billing_charges`
- [ ] Create a fuel charge via Dashboard modal → same
- [ ] Create a fuel charge via CRM Customer page → same
- [ ] Collect payment → verify `order_extra_charges` row AND `billing_charges` updated
- [ ] Mark charge as resolved → verify reversal entry in `customer_accounts`, balance correct
- [ ] Open Fuel Charge Alerts report → charges visible, counts match
- [ ] Open Sales Tax report → totals unchanged

### Before Phase 3 (Damage Charges)

- [ ] Repeat all Phase 2 checks with `type = 'damage'`
- [ ] Open New Damage Alerts report → charges visible

### Before Phase 4 (Extension Charges)

- [ ] Create extension charge on an order with no existing extensions → suffix = A
- [ ] Create second extension → suffix = B
- [ ] Soft-delete the A extension → create new extension → new suffix is C (not A — A slot preserved)
- [ ] Create a reorder → create an extension → reorder does not consume a suffix letter
- [ ] Extension order appears on Order Edit page under "Extension Charges"
- [ ] Extension order appears in Orders list with suffixed order number
- [ ] History entry written to parent order

### Before Phase 6 (Unified UI)

- [ ] Order Edit page displays all charge types in unified section
- [ ] Fuel/damage charges show status badges and amounts
- [ ] Extension charges show link to child order
- [ ] Payment action works from unified section

### Before Phase 7 (Remove Legacy Code)

- [ ] Run all Phase 2–6 checks again
- [ ] Confirm all reports read from `billing_charges` exclusively
- [ ] Confirm `customer_accounts` and `order_extra_charges` bridge writes are no longer active
- [ ] Confirm balance totals match: `SUM(customer_accounts.amount)` vs `billing_charges` derived totals
- [ ] Run `php artisan route:list` — no 404 routes

---

## Confirming System Returned to Prior State (after rollback)

Run these queries after any rollback and compare against the pre-migration baselines recorded above:

```sql
-- Customer balance check
SELECT customer_id, SUM(amount) AS total_charges
FROM customer_accounts
WHERE type = 'charge'
GROUP BY customer_id
ORDER BY customer_id;

-- Extra charges count
SELECT type, COUNT(*) FROM order_extra_charges GROUP BY type;

-- Extension order count
SELECT COUNT(*) FROM orders WHERE order_number LIKE '%-_';
```

If any number differs from the pre-migration baseline, investigate before declaring the rollback complete.

Run the Fuel Charge Alerts report and New Damage Alerts report visually — if charges and payment statuses are correct, the system is in its prior state.

---

## Communication

If a rollback is performed in production:
1. Note the exact rollback commit hash in the incident log
2. Note which phases had been applied (to determine which migration steps to roll back)
3. Confirm with the team before running `migrate:rollback` in production — rollbacks in production should be a conscious joint decision, not a solo action
