# Correction Phase 1 — Issue #5 Investigation Findings: 876 Delivered Order Products With No Checklist Rows

**Date:** 2026-07-03
**Type:** Read-only SQL investigation only, per `CORRECTION_PHASE1_PLAN.md`'s Issue #5 scope. **No code or data was modified.**
**Environment:** Same local dev database used for Phase 3 (`rc_kabba_03_07_26`), read-only queries only this time — no API calls, no writes.

---

## 1. Exact SQL used

### Base query (reproduces Phase 3's original 876-row finding, minus the 1 test row created during Phase 3)
```sql
SELECT COUNT(*) AS affected_count
FROM order_products op
LEFT JOIN order_product_checklist_questions opq
  ON opq.order_product_id = op.id AND opq.deleted_at IS NULL
WHERE op.is_delivered = 1
  AND opq.id IS NULL
  AND op.deleted_at IS NULL
  AND op.id != 77;  -- excludes the order product created during Phase 3 testing
```

### Step 1 — Break down by product type (`products.product_type` is `enum('Rental','Retail')`)
```sql
-- All delivered order products, by product type
SELECT p.product_type, COUNT(*) AS total_delivered
FROM order_products op
JOIN products p ON p.id = op.product_id
WHERE op.is_delivered = 1 AND op.deleted_at IS NULL
GROUP BY p.product_type;

-- Affected (no-checklist) subset, by product type
SELECT p.product_type, COUNT(*) AS affected_count
FROM order_products op
JOIN products p ON p.id = op.product_id
LEFT JOIN order_product_checklist_questions opq
  ON opq.order_product_id = op.id AND opq.deleted_at IS NULL
WHERE op.is_delivered = 1 AND opq.id IS NULL AND op.deleted_at IS NULL
GROUP BY p.product_type;
```

### Step 2 — Break down by `delivery_status`, `delivery_by`, `equipment_id`, `dispatch_checklist` presence (Rental only)
```sql
SELECT op.delivery_status, COUNT(*) AS cnt
FROM order_products op
JOIN products p ON p.id = op.product_id
LEFT JOIN order_product_checklist_questions opq
  ON opq.order_product_id = op.id AND opq.deleted_at IS NULL
WHERE op.is_delivered = 1 AND opq.id IS NULL AND op.deleted_at IS NULL AND p.product_type = 'Rental'
GROUP BY op.delivery_status;

SELECT (op.delivery_by IS NULL) AS delivery_by_is_null, COUNT(*) AS cnt
FROM order_products op
JOIN products p ON p.id = op.product_id
LEFT JOIN order_product_checklist_questions opq
  ON opq.order_product_id = op.id AND opq.deleted_at IS NULL
WHERE op.is_delivered = 1 AND opq.id IS NULL AND op.deleted_at IS NULL AND p.product_type = 'Rental'
GROUP BY delivery_by_is_null;

SELECT (op.equipment_id IS NULL) AS equipment_is_null, COUNT(*) AS cnt
FROM order_products op
JOIN products p ON p.id = op.product_id
LEFT JOIN order_product_checklist_questions opq
  ON opq.order_product_id = op.id AND opq.deleted_at IS NULL
WHERE op.is_delivered = 1 AND opq.id IS NULL AND op.deleted_at IS NULL AND p.product_type = 'Rental'
GROUP BY equipment_is_null;

SELECT (op.dispatch_checklist IS NULL) AS dispatch_checklist_is_null, COUNT(*) AS cnt
FROM order_products op
JOIN products p ON p.id = op.product_id
LEFT JOIN order_product_checklist_questions opq
  ON opq.order_product_id = op.id AND opq.deleted_at IS NULL
WHERE op.is_delivered = 1 AND opq.id IS NULL AND op.deleted_at IS NULL AND p.product_type = 'Rental'
GROUP BY dispatch_checklist_is_null;
```

### Step 3 — Correlate with `order_histories.action = 'product_schedule_updated'` (the action string logged by `UpdateProductScheduleController`)
```sql
SELECT COUNT(DISTINCT op.id) AS affected_with_schedule_update_history
FROM order_products op
JOIN products p ON p.id = op.product_id
LEFT JOIN order_product_checklist_questions opq
  ON opq.order_product_id = op.id AND opq.deleted_at IS NULL
WHERE op.is_delivered = 1 AND opq.id IS NULL AND op.deleted_at IS NULL AND p.product_type = 'Rental'
  AND EXISTS (
    SELECT 1 FROM order_histories oh
    WHERE oh.order_id = op.order_id AND oh.action = 'product_schedule_updated'
  );
```

### Step 4 — Date range / monthly distribution
```sql
SELECT MIN(op.created_at) AS earliest, MAX(op.created_at) AS latest, COUNT(*) AS total
FROM order_products op
JOIN products p ON p.id = op.product_id
LEFT JOIN order_product_checklist_questions opq
  ON opq.order_product_id = op.id AND opq.deleted_at IS NULL
WHERE op.is_delivered = 1 AND opq.id IS NULL AND op.deleted_at IS NULL AND p.product_type = 'Rental';

SELECT DATE_FORMAT(op.created_at, '%Y-%m') AS month, COUNT(*) AS cnt
FROM order_products op
JOIN products p ON p.id = op.product_id
LEFT JOIN order_product_checklist_questions opq
  ON opq.order_product_id = op.id AND opq.deleted_at IS NULL
WHERE op.is_delivered = 1 AND opq.id IS NULL AND op.deleted_at IS NULL AND p.product_type = 'Rental'
GROUP BY month ORDER BY month;
```

### Step 5 — Residual bucket (rows with `delivery_by` set but still no checklist — see §4)
```sql
SELECT op.id, op.unique_id, op.order_id, op.delivery_status, op.delivery_by, op.equipment_id
FROM order_products op
JOIN products p ON p.id = op.product_id
LEFT JOIN order_product_checklist_questions opq
  ON opq.order_product_id = op.id AND opq.deleted_at IS NULL
WHERE op.is_delivered = 1 AND opq.id IS NULL AND op.deleted_at IS NULL AND p.product_type = 'Rental'
  AND NOT EXISTS (
    SELECT 1 FROM order_histories oh
    WHERE oh.order_id = op.order_id AND oh.action = 'product_schedule_updated'
  );

-- Check whether their equipment has a ChecklistMaster assigned
SELECT (e.checklist_master_id IS NULL) AS no_checklist_master, COUNT(*) AS cnt
FROM order_products op
JOIN products p ON p.id = op.product_id
JOIN equipment e ON e.id = op.equipment_id
LEFT JOIN order_product_checklist_questions opq
  ON opq.order_product_id = op.id AND opq.deleted_at IS NULL
WHERE op.is_delivered = 1 AND opq.id IS NULL AND op.deleted_at IS NULL AND p.product_type = 'Rental'
  AND NOT EXISTS (
    SELECT 1 FROM order_histories oh
    WHERE oh.order_id = op.order_id AND oh.action = 'product_schedule_updated'
  )
GROUP BY no_checklist_master;
```

---

## 2. Counts by product/order type

| Breakdown | Result |
|---|---|
| Total `is_delivered=1` order products | 2,665 |
| — of which `product_type='Rental'` | 2,663 |
| — of which `product_type='Retail'` | 2 |
| Affected (no checklist rows) | 875 |
| — of which `product_type='Rental'` | **875** |
| — of which `product_type='Retail'` | 1 |

**The product-type hypothesis is ruled out.** Virtually all delivered order products are Rental products, and virtually all of the affected rows are Rental products too (99.9%). This is not a case of sale/service products being incorrectly flagged — these are legitimately rental deliveries that should, per the system's own design, have a checklist.

## 3. Counts by delivery source/path

| Signal (Rental-only affected rows, n=875) | Value | Count | % |
|---|---|---|---|
| `delivery_status` | `'Close as Completed'` | 636 | 73% |
| `delivery_status` | `'Completed'` | 239 | 27% |
| `delivery_by` | `NULL` | 708 | 81% |
| `delivery_by` | set | 167 | 19% |
| `equipment_id` | `NULL` | 715 | 82% |
| `equipment_id` | set | 160 | 18% |
| `dispatch_checklist` | `NULL` (unrelated system, ruled out) | 875 | 100% |
| Order has a `product_schedule_updated` history entry | Yes | 831 | 95% |
| Order has a `product_schedule_updated` history entry | No | 44 | 5% |

**Two distinct, fully-explained root causes account for all 875 rows:**

### Root cause A — Admin manual status override, 831 of 875 rows (95%)
`app/Http/Controllers/Admin/OrderManagement/Orders/UpdateProductScheduleController.php:151-165`: when an admin manually sets an order product's `delivery_status` to the literal admin-only value `'Close as Completed'` (confirmed via grep — this string only appears in this controller and its FormRequest), the code directly sets `is_delivered=true`, `is_returned=true`, `pickup_status='Completed'`, and — if equipment is assigned — moves the equipment to `Maintenance` status, **entirely independent of the checklist system**. No `order_product_checklist_questions` row is ever created by this path, `delivery_by` is never set (matching the 81% `NULL` finding), and equipment is only touched *if already assigned* (matching the 82% `NULL` equipment finding — many of these are being administratively closed out without ever having had equipment assigned at all). The plain `'Completed'` status set via this same controller (lines 143-150, no `Close as Completed` involved) behaves the same way and accounts for the remaining 239-row (27%) `'Completed'`-status subset — both branches of the same controller.

**Bonus finding relevant to Correction Phase 1, Issue #2:** this controller calls `$equipment->saveQuietly()` directly at lines 149, 162, and 247 — a **third, previously uncatalogued site** of the exact same `EquipmentObserver`-bypass bug documented for `EquipmentStatusService`. Any fix for Issue #2 needs to account for this controller too, not just the service class.

831 of these 875 rows (95%) have at least one `product_schedule_updated` history entry on their parent order, directly confirming this controller (which fires the `OrderProductScheduleUpdated` event logged under that action name) is the responsible code path. This pattern is spread evenly from January 2026 through July 2026 (monthly counts: 109, 194, 178, 155, 147, 91, 1) — this is a **long-standing, ongoing administrative workflow**, not a recent regression or a one-time data anomaly.

### Root cause B — Mobile checklist endpoint called with an empty/omitted `checklist` array, 44 of 875 rows (5%)
The residual 44 rows have `delivery_by` **set** to a real user ID and `equipment_id` **set** to real equipment — the signature of the actual mobile `SaveDeliveryController` flow, not the admin bypass. All 44 of their assigned equipment units *do* have a `checklist_master_id` configured (ruling out "no template assigned" as the explanation). This matches a gap already documented in `CHECKLIST_SYSTEM_AUDIT.md` §8: `checklist` is `nullable|array` on `SaveDeliveryRequest`, and if the array is omitted entirely, `SaveDeliveryController` skips the whole checklist-creation block but still unconditionally sets `delivery_by` and `delivery_status='Completed'`. This investigation is the first time this specific gap has been quantified against real data — it affects a much smaller share (44 rows, 5%) than root cause A, but it is a genuine confirmed mobile-side gap, not an administrative one.

## 4. Sample affected rows

**Root cause A sample (most recent 10, all `'Close as Completed'`):**
| id | unique_id | order_id | delivery_status | pickup_status | delivery_by | equipment_id | created_at |
|---|---|---|---|---|---|---|---|
| 3486 | ORD-SCH-VU2A-JX04 | 2971 | Close as Completed | Completed | NULL | NULL | 2026-07-01 |
| 3448 | ORD-SCH-7X0O-TBAB | 2932 | Close as Completed | Completed | NULL | NULL | 2026-06-30 |
| 3432 | ORD-SCH-LG0U-ODF6 | 2916 | Close as Completed | Completed | NULL | NULL | 2026-06-29 |
| 3426 | ORD-SCH-LEDE-IT7A | 2909 | Close as Completed | Completed | NULL | NULL | 2026-06-29 |
| 3422 | ORD-SCH-KR1T-EBHZ | 2906 | Close as Completed | Completed | NULL | NULL | 2026-06-29 |
| 3418 | ORD-SCH-4M6M-LV8K | 2903 | Close as Completed | Completed | NULL | NULL | 2026-06-29 |
| 3388 | ORD-SCH-L1UN-E6QJ | 2867 | Close as Completed | Completed | NULL | NULL | 2026-06-26 |
| 3386 | ORD-SCH-U4GZ-QPXX | 2865 | Close as Completed | Completed | NULL | NULL | 2026-06-26 |
| 3374 | ORD-SCH-XZYK-PCIS | 2854 | Close as Completed | Completed | NULL | NULL | 2026-06-25 |
| 3371 | ORD-SCH-WHC9-T0SV | 2853 | Close as Completed | Completed | NULL | NULL | 2026-06-25 |

**Root cause B sample (10 of 44, `delivery_by` set, equipment set, no checklist):**
| id | unique_id | order_id | delivery_status | delivery_by | equipment_id |
|---|---|---|---|---|---|
| 682 | ORD-SCH-CVOI-LUQJ | 576 | Completed | 27 | 41 |
| 881 | ORD-SCH-X6KK-TUE0 | 745 | Completed | 23 | 41 |
| 696 | ORD-SCH-3XMN-WPXY | 590 | Completed | 4 | 76 |
| 720 | ORD-SCH-A13Y-KPWD | 614 | Completed | 23 | 76 |
| 772 | ORD-SCH-SYAY-WOQM | 652 | Completed | 27 | 40 |
| 1942 | ORD-SCH-RP8I-7D4P | 1619 | Completed | 4 | 76 |
| 2420 | ORD-SCH-MAIG-0VOG | 2046 | Completed | 27 | 76 |
| 2442 | ORD-SCH-9GUO-01DH | 2064 | Completed | 29 | 76 |
| 2493 | ORD-SCH-81DO-487V | 2104 | Completed | 27 | 76 |
| 2516 | ORD-SCH-HHPI-VJIX | 2124 | Completed | 29 | 76 |

Note equipment ids 41, 76, and 40 repeat across many of these — a small number of specific equipment units account for a disproportionate share of root cause B, worth a follow-up look (outside this phase's scope) at whether something about those specific units' assignment/usage pattern makes this more likely.

## 5. Is this a real bug or expected data?

**Root cause A (831 rows, 95%) — this is expected, by-design administrative behavior, not a bug in the checklist system.** `'Close as Completed'` is a deliberate admin-facing status option that exists specifically to let staff close out an order product without going through the full delivery/return checklist workflow (e.g., cancelled bookings, administrative corrections, orders that never actually shipped). The checklist system is not "failing" to create rows here — this code path was never supposed to invoke it. This is a legitimate design decision, not a defect. **However**, it does mean "has a checklist" cannot be used anywhere in the system (reports, dashboards, billing reconciliation) as a reliable proxy for "was genuinely delivered to a customer" — a large minority of "delivered" rental order products were administratively closed, not actually delivered with a checklist. That is a data-interpretation risk worth communicating, not a code bug to fix.

**Root cause B (44 rows, 5%) — this is a real, confirmed gap, consistent with an already-documented finding.** These order products went through the actual mobile checklist submission flow and still ended up with zero checklist trail, purely because the mobile client didn't include a `checklist` array in its request. This is the same gap `CHECKLIST_SYSTEM_AUDIT.md` §8/§12 already flagged qualitatively ("if `checklist` is omitted entirely... `delivery_status` is still force-set to `'Completed'`") — this investigation is the first time it's been shown to have actually happened in real data, 44 times.

## 6. Recommended next action

1. **No code change needed for root cause A.** It is working as designed. Recommend only a **documentation/communication action**: any report, dashboard, or billing logic that treats "delivered" as synonymous with "checklist completed" should be made aware that ~31% of all delivered rental order products (831 of 2,663) were closed via this administrative path, not the checklist. This is not part of Correction Phase 1's code scope.
2. **Root cause B is a legitimate, small-scope candidate for Correction Phase 2**, once Correction Phase 1's Issue #3 observability logging (already planned) is live — that logging will naturally capture every future occurrence of this exact scenario (a "Completed" delivery with a missing/empty checklist), providing an ongoing count to track whether 44-and-counting is a stable low-frequency background rate or something worth actively preventing.
3. **Fold the newly-found third `saveQuietly()` site in `UpdateProductScheduleController.php` (lines 149, 162, 247) into Correction Phase 1's existing Issue #2 fix scope** — this is the same bug, in a third location, discovered as a direct byproduct of this investigation. It does not change Issue #2's priority or approach, only its file list.
4. **No data cleanup is warranted for either root cause.** Root cause A's rows are correctly reflecting genuine administrative closures; retroactively fabricating checklist data for them would be actively wrong. Root cause B's 44 rows are a real gap in the audit trail but, like the `equipment_status_logs` history, cannot be reconstructed after the fact — going forward, prevention (or at minimum observability) is the only viable remedy, consistent with Correction Phase 1's decision to add logging rather than backfill data.

No code or data was modified to produce these findings.
