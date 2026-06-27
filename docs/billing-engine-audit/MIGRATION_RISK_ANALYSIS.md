# Migration Risk Analysis

Audit date: 2026-06-27  
Branch: feature/billing-engine-consolidation

---

## Risk 1 — Dual Write System (Fuel/Damage)

**Current state:** Every fuel/damage charge potentially lives in two places — `customer_accounts` (the charge) and `order_extra_charges` (the payment). These are not formally linked by a foreign key. There is no column that says "this OrderExtraCharges row is the payment for this CustomerAccount row."

**Risk:** During migration, if BillingEngine creates a `billing_charges` row but also writes legacy rows for backwards compatibility, there is a window where a charge could appear as both a legacy row and a new row. If the bridge code is removed too early, reports that read from `customer_accounts` or `order_extra_charges` will lose data.

**Mitigation:**
- Keep bridge writes (CustomerAccount + OrderExtraCharges) active until all reports are confirmed to read from `billing_charges`
- Add `billing_charge_id` column to both `customer_accounts` and `order_extra_charges` as a nullable FK during Phase 2, so the connection is traceable
- Only remove bridge writes in Phase 7, after all reports are migrated

---

## Risk 2 — Sales Tax Reporting Gap

**Current state:** `sales_tax = 0` is always stored on `customer_accounts` for fuel/damage charges. The `sales_tax_type` flag (add/free/reverse) is captured but never materialized. The Sales Tax report reads `order_extra_charges` (the payment side), not the charge side.

**Risk:** A charge created with `sales_tax_type = 'add'` that has never been paid is invisible to the Sales Tax report. Tax is only counted when payment is collected, not when the charge is created. If tax reporting requirements change, this creates an accrual vs. cash-basis mismatch.

**Mitigation:**
- During Phase 2, BillingEngine should calculate and store `tax_amount` on `billing_charges` at creation time using the current `sales_tax` configuration setting
- After migration, the Sales Tax report should read from `billing_charges.tax_amount`
- Before Phase 7, run a reconciliation query to confirm the old `order_extra_charges` tax totals match the new `billing_charges` tax totals

---

## Risk 3 — Extension Order Numbering Conflicts

**Current state:**
```php
$existingCount = Order::where('reference_order_number', $order->order_number)->count();
$suffix = chr(65 + $existingCount);
```

**Known bugs:**
1. Soft-deleted extension orders still count. Delete `#047-A`, create a new extension → it becomes `#047-B` (A is skipped)
2. Reorders set `reference_order_number` to the parent number but get a new sequential number. A reorder of `#047` increments `existingCount` so the next extension becomes `#047-B` instead of `#047-A`
3. Hard limit of 26 extensions (returns 422 at Z+1)

**Risk:** Duplicate suffix assignment if any of these race conditions trigger. Two extensions both claiming `#047-A` would violate order number uniqueness.

**Mitigation:**
- Phase 4 must fix the count query before converting extension logic to BillingEngine:
  ```php
  // Only count rows that are actual extensions (suffixed order_number), including soft-deleted
  $existingCount = Order::withTrashed()
      ->where('reference_order_number', $order->order_number)
      ->where('order_number', 'LIKE', $order->order_number . '-%')
      ->count();
  ```
- Add a `UNIQUE` constraint on `orders.order_number` (check if one exists already before adding)
- Implement double-letter suffix (AA, AB...) after Z to remove the 26-extension limit

---

## Risk 4 — CustomerAccount Balance Integrity

**Current state:** `CustomHelper::updateCreditBalance()` is called after every write to `customer_accounts`. This function recomputes the entire running balance for the customer. If any write is missed (e.g., BillingEngine creates a `billing_charges` row but the bridge code that writes the `CustomerAccount` row fails), the customer's balance will be wrong.

**Risk:** Partial write — `billing_charges` row created, `CustomerAccount` row missing. Customer balance understates their debt. The issue will not surface until the customer's Account tab is reviewed.

**Mitigation:**
- Wrap all BillingEngine writes in a single database transaction
- Do not call `updateCreditBalance()` in multiple places — call it once, at the end of the transaction, inside BillingEngine
- Add a balance verification query to the test checklist: `SELECT SUM(amount) FROM customer_accounts WHERE customer_id = X` should match `customers.credit_balance`

---

## Risk 5 — Payment History Disruption

**Current state:** The CRM Customer Account tab displays the full history of charges and payments from `customer_accounts`. The Order Edit page shows payments from `order_extra_charges` and charges from `customer_accounts`.

**Risk:** If the bridge writes are removed before the UI is updated to read from `billing_charges`, existing payment history will appear to disappear from the CRM Account tab and the Order Edit page.

**Mitigation:**
- Do not remove bridge writes until the UI for both pages is updated
- Phase 6 (unified UI) must be complete before Phase 7 (remove legacy code)
- Test by loading a customer with 10+ historical charges before and after any bridge write removal

---

## Risk 6 — Receipt Generation

**Current state:** `ReceiptService` generates PDFs for orders. Extension orders get their own receipts because they are full `Order` records. Fuel/damage charges do not generate receipts — they appear on the customer's account statement.

**Risk:** If BillingEngine introduces a formal receipt flow for fuel/damage charges, existing customers' historical charges will not have receipts. This creates an inconsistent paper trail.

**Mitigation:**
- Do not add receipt generation to BillingEngine until explicitly requested
- Document that receipts for fuel/damage charges are a future enhancement, not part of the initial migration

---

## Risk 7 — Refund Logic

**Current state:** Refunds on Order payments go through `RefundPaymentController`, which marks an `order_payments` row as refunded and creates a reversal `CustomerAccount` row. Fuel/damage charge reversals go through `ChargeService::markResolved()` (discount entry). There is no unified refund path.

**Risk:** BillingEngine migration does not change the refund logic, but if the reporting layer is updated to read from `billing_charges` before refunds are mapped, refunds may not appear against the correct charge.

**Mitigation:**
- Map refund/reversal records to `billing_charges` during Phase 2/3 migration
- Include a `reversed_billing_charge_id` nullable FK on `billing_charges` so reversals point to their source

---

## Risk 8 — Existing Child Orders in Production

**Current state:** There are existing extension orders in the database with `reference_order_number` set. These do not have corresponding `billing_charges` rows.

**Risk:** If reporting is switched to read from `billing_charges` before historical data is backfilled, historical extensions will disappear from reports.

**Mitigation:**
- Write a one-time backfill migration at the start of Phase 4 that creates `billing_charges` rows for all existing extension orders:
  ```sql
  INSERT INTO billing_charges (billing_charge_type, parent_order_id, child_order_id, ...)
  SELECT 'extension', parent.id, child.id, ...
  FROM orders child
  JOIN orders parent ON parent.order_number = child.reference_order_number
  WHERE child.order_number LIKE '%-[A-Z]';
  ```
- Similarly, backfill `billing_charges` for historical `customer_accounts` charge rows in Phases 2/3

---

## Risk 9 — Duplicate Billing Prevention

**Current state:** `ChargeService::createFromOrderProduct()` has a duplicate guard:
```php
$exists = CustomerAccount::where('order_product_id', $orderProduct->id)
    ->where('reason', $reason)->where('type', 'charge')
    ->whereIn($alertField, ['pending', 'completed'])->exists();
```

Other entry points (AlertChargeController, Dashboard controllers) have NO duplicate guard — an admin can create multiple fuel charges for the same order with no system check.

**Risk:** BillingEngine migration must not remove the existing duplicate guard and must not add it to paths where admins intentionally create multiple charges (e.g., multiple fuel stop charges on a single rental).

**Mitigation:**
- BillingEngine should expose a `$options['dedup_by_order_product'] = true` flag that activates the guard only for checklist-originated charges
- Admin-originated charges (Alert modal, Dashboard modal, CRM) should remain multi-charge capable

---

## Risk 10 — QuickBooks / Accounting Exports

**Current state:** No QuickBooks integration exists. The `SalesTaxReportEngine` and `SalesReportEngineV2` are the primary accounting outputs. If a QuickBooks export is added in the future, it will need to read from whichever table structure is current at that time.

**Risk:** Low for current migration. Medium if QuickBooks is added mid-migration while both old and new tables are active.

**Mitigation:**
- Document that any future accounting integration should read from `billing_charges` (not `customer_accounts` or `order_extra_charges`)
- Do not build accounting exports against legacy tables after Phase 1 is shipped

---

## Summary Risk Matrix

| Risk | Likelihood | Impact | Phase to address |
|------|-----------|--------|-----------------|
| Dual write gap (CA + OEC desync) | Medium | High (balance wrong) | Phase 1 bridge design |
| Sales tax not stored at charge time | High | Medium (tax report gap) | Phase 2 |
| Extension suffix collision | Low | High (duplicate order number) | Phase 4 |
| Balance integrity on partial write | Low | High | Phase 1 (transaction design) |
| Payment history display gap | Medium | Medium | Phase 6 (UI before Phase 7) |
| Receipts for fuel/damage | Low | Low | Deferred |
| Refund mapping | Medium | Medium | Phase 2/3 |
| Historical data backfill | High | High (reports look empty) | Phase 2/3/4 before reporting switch |
| Admin duplicate charges | Low | Medium | Phase 1 (options flag) |
| QuickBooks mid-migration | Low | Low | Deferred |
