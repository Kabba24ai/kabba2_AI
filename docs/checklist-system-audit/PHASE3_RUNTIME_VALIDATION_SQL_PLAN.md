# Checklist System Audit — Phase 3: Runtime Validation & SQL Verification Plan

**Audit date:** 2026-07-03
**Type:** Planning document only. This is an **audit-only deliverable** — it specifies what to run in staging and what SQL to run against a staging/read-replica database. **No code was changed to produce this plan, no migrations were run, and nothing in this document should be executed against production without a DBA/lead sign-off.**
**Code modified:** **No.**
**Depends on:** `CHECKLIST_SYSTEM_AUDIT.md` (Phase 1) and `PHASE2_DEPENDENCY_RUNTIME_AUDIT.md` (Phase 2) in this same folder — every scenario below cites the specific static finding it is designed to confirm or disprove.

---

## 0. How to use this document

Phase 1 and Phase 2 found issues by reading code. Neither phase executed anything. This phase closes that gap by specifying exact, reproducible steps: which API endpoint to call, with what payload, and which SQL query to run afterward to check the database actually did what the code implied it would do.

Every check below is written against the **confirmed schema** from Phase 1 (`CHECKLIST_SYSTEM_AUDIT.md` §4) and the **confirmed table names** re-verified directly against migrations for this document: `order_products`, `order_product_checklist_questions`, `order_product_checklist_question_answers`, `checklist_masters`, `equipment`, `equipment_status_logs`, `rental_ready_checklist_templates`, `rental_ready_checklist_template_questions`, `customer_admin_templates`, `customer_admin_template_questions`, `customer_admin_questions`, `customer_admin_question_answers`, `billing_charges`, `customer_accounts`.

Two columns worth flagging before running anything: `billing_charges.billing_charge_type` (not `type`) and `billing_charges.source_module` (used to isolate checklist-originated charges) — both confirmed by reading `database/migrations/orders/2026_06_27_000001_create_billing_charges_table.php` directly for this document, since Phase 1/2 referred to the model API (`BillingChargeRequest`) rather than raw column names.

**Environment requirements before starting:**
- A staging environment with a database you are authorized to write to (this plan creates real orders/deliveries/returns/charges — do not run against production).
- A valid `api_user` bearer token (all 9 mobile endpoints require `auth:api_user` — confirmed in Phase 2 §2).
- Read access to run raw SQL against the staging database (or a read replica) — a DBA or someone with `php artisan tinker`/direct DB access.
- At least one seeded `ChecklistMaster` with both a `RentalReadyChecklistTemplate` and a `CustomerAdminTemplate` attached, and at least one `Equipment` row assigned to it. **Phase 2 §1 confirmed no seeder creates this data** — it must be created manually first, either via the admin UI (`checklist-management/checklist-master/create`) or directly in staging.

---

## 1. Staging Test Scenarios, API Calls, and Expected DB Changes

Each scenario lists: the goal, the exact endpoint(s), a sample payload, and what should change in the database if the code behaves as Phase 1/2 described.

### S1 — Baseline delivery (establish a known-good order product)

**Goal:** Create a reference order product with a delivered checklist, to use as the base state for later scenarios (S2, S4, S6, S9).

**Endpoint:** `POST /api/admin/v1/orders/customer-checklists/save-delivery`
**Headers:** `Authorization: Bearer {api_user_token}`, `Content-Type: multipart/form-data`

**Sample payload:**
```
order_product_unique_id: ORD-PROD-0001   (an existing, not-yet-delivered order product)
equipment_unique_id: EQP-0001            (equipment assigned to a ChecklistMaster with both templates)
store_id: 1
user_id: 42
start_hours: 100
fuel_initial_reading: 75
note: "Phase 3 baseline delivery test"
signature_media: <attach a small JPEG>
checklist[0][question_unique_id]: CAQST-0001
checklist[0][answer_unique_id]: CAANS-0001
checklist[0][amount]: 0
```

**Expected DB changes:**
- `order_products` row for `ORD-PROD-0001`: `delivery_status='Completed'`, `is_delivered=1`, `is_returned=0`, `delivery_signature_media_id` populated (not null), `equipment_id` set, `equipment_details` populated with a JSON snapshot.
- `order_product_checklist_questions`: one row per question in the equipment's `CustomerAdminTemplate`.
- `order_product_checklist_question_answers`: one row per master answer option per question (not just the selected one — confirmed in Phase 2 §3 Workflow 1 step 4d).
- `equipment.current_status = 'rented'`.
- **No row expected in `equipment_status_logs`** for this transition (this is the specific gap Phase 2 §5 flagged — see SQL check V3).
- One `order_history` row with `action` mapping to `ChecklistDelivered`.

---

### S2 — Baseline return (establish a delivered-then-returned order product)

**Goal:** Complete S1's order product with a return, including a damaged answer, to exercise billing.

**Endpoint:** `POST /api/admin/v1/orders/customer-checklists/save-return`

**Sample payload:**
```
order_product_unique_id: ORD-PROD-0001
store_id: 1
user_id: 42
end_hours: 110
fuel_final_reading: 60
fuel_total_charge: 25.00
total_charge: 25.00
note: "Phase 3 baseline return test"
signature_media: <attach a small JPEG>
checklist[0][question_unique_id]: <the order_product_checklist_questions unique_id from S1, NOT the master question_unique_id>
checklist[0][answer_unique_id]: <the order_product_checklist_question_answers unique_id whose master answer has is_damaged=1>
checklist[0][amount]: 150.00
```

**Expected DB changes:**
- `order_products` row: `pickup_status='Completed'`, `is_returned=1`, `pickup_signature_media_id` populated, `damage_status='Pending'`, `fuel_total_charge=25.00`, `total_charge=25.00`.
- `order_product_checklist_question_answers`: the selected row has `is_return_answer=1`, `user_return_amount=150.00`.
- `billing_charges`: one new row with `billing_charge_type` for damage, `source_module='mobile_checklist'`, `amount=150.00`, `order_product_id` = S1's order product id, `idempotency_key = 'mobile_checklist:{order_product_id}:damage'`.
- `billing_charges`: a second new row for the fuel charge, `source_event` indicating a return-checklist fuel charge, `idempotency_key = 'mobile_return_fuel:{order_product_id}:60'` (keyed on the submitted `fuel_final_reading`).
- `customer_accounts`: one row with `reason='Fuel Charge'` (legacy bridge, per Phase 2 §3 Workflow 2 step 11b).
- `equipment.current_status = 'damaged'` (because a damaged answer was submitted — `markReturnedDamaged` path).
- **No new row in `equipment_status_logs`** (same gap as S1).
- One `order_history` row mapping to `ChecklistReturned`.

---

### S3 — Re-delivery without an intervening return (Phase 2 §6, Rule 5)

**Goal:** Confirm or disprove the "no re-delivery guard" gap.

**Steps:**
1. Run S1 only (do not run S2).
2. Assign a **different** equipment (`EQP-0002`, currently available, not rented) to the same order product.
3. Call `save-delivery` again with `order_product_unique_id: ORD-PROD-0001`, `equipment_unique_id: EQP-0002`.

**Expected if the bug is real:** HTTP 200 success. `order_product_checklist_questions`/`_answers` for `ORD-PROD-0001` are deleted and fully rebuilt against `EQP-0002`'s template. `equipment_details` snapshot now reflects `EQP-0002`. `EQP-0001` is left with `current_status='rented'`, `current_order_product_id` still pointing at `ORD-PROD-0001` (now orphaned from that product's perspective).

**Expected if the code is actually safe (disproves the finding):** HTTP 4xx/409 rejecting the second delivery attempt.

---

### S4 — Listener failure / non-atomicity (Phase 2 §5)

**Goal:** Confirm the "partial success reported as total failure" finding.

**This scenario requires either a code instrumentation step in staging only (temporarily, reverted immediately after the test, and NOT part of this audit's scope to perform) or a natural failure trigger.** The safest natural trigger identified by static analysis: submit a `checklist_type` string that is valid per the request rule but causes `OrderCustomerChecklistType::from()` to fail is not reachable from outside (that type string is hardcoded server-side, not client-controlled) — so this specific listener cannot be triggered by an external payload alone.

**Recommended approach:** ask engineering to temporarily add a single throwing line inside `OrderCustomerChecklistListener::handle()` in a staging-only branch, run S1, observe the HTTP response and DB state, then revert. This step is **out of scope for this audit** (it requires a code change) — flagging it here only so the correction-phase team knows exactly how to reproduce it. If this instrumented test is run, expected result: HTTP 500 returned to the client, but `order_products.delivery_status='Completed'` and `equipment.current_status='rented'` are both already committed, and no `order_history` row exists for the delivery.

---

### S5 — `ChecklistMaster` bulk unassign (Phase 2 §6, Rule 7b)

**Goal:** Confirm the silent mass-unassign bug.

**Steps:**
1. Note a `ChecklistMaster` (`CLM-0001`) with no equipment currently assigned.
2. Via `AssignChecklistMasterController` (admin UI: Equipment index → "Assign" modal, or `POST admin/maintenance-management/equipment/checklist-master-assign` with `checklist_master_unique_id=CLM-0001`, `equipment_unique_id=EQP-0003`), assign `EQP-0003` to `CLM-0001`.
3. Separately, open the Checklist Master edit screen for `CLM-0001` (`checklist-management/checklist-master/edit/CLM-0001`) and submit an update selecting a **different** equipment list that does not include `EQP-0003`, with equipment assignment enabled.

**Expected if the bug is real:** After step 3, `equipment.checklist_master_id` for `EQP-0003` is `NULL` (silently unassigned, no warning shown in step 3's form).

**Expected if disproved:** `EQP-0003` retains `checklist_master_id = CLM-0001`'s id, or the UI blocks/warns before removing it.

---

### S6 — Required questions unanswered (Phase 1 §12, Phase 2 §6 Rule 4)

**Goal:** Confirm a delivery/return can be marked "Completed" while leaving `required_question=1` questions unanswered.

**Endpoint:** `POST /api/admin/v1/orders/customer-checklists/save-delivery`

**Sample payload:** identical to S1 but with an **empty** `checklist` array, or a `checklist` array that omits a question flagged `required_question=1` on the master `CustomerAdminQuestion`.

**Expected if the bug is real:** HTTP 200, `delivery_status='Completed'`, `is_delivered=1`, despite zero (or partial) checklist answers submitted.

---

### S7 — Signature-less completion (Phase 1 §8, §12)

**Goal:** Confirm completion without a signature.

**Endpoint:** `POST /api/admin/v1/orders/customer-checklists/save-delivery` (and separately `save-return`)

**Sample payload:** identical to S1/S2 but **omit** the `signature_media` file entirely.

**Expected if the bug is real:** HTTP 200, `delivery_status`/`pickup_status='Completed'`, `delivery_signature_media_id`/`pickup_signature_media_id` remain `NULL`.

---

### S8 — Client-writable, unvalidated status flags (Phase 1 §8, §12)

**Goal:** Confirm the granular status endpoint accepts unvalidated, arbitrary strings with no cross-check.

**Endpoint:** `POST /api/admin/v1/orders/schedules/update-delivery-pickup-inputs`

**Sample payload:**
```
order_product_unique_id: ORD-PROD-0002   (a product that has NOT had a license or video uploaded, and has NOT been delivered)
type: delivery
tnc_status: "yolo-accepted"
drivers_license_status: "verified"
video_status: "definitely-uploaded"
checklist_status: "completed"
```

**Expected if the bug is real:** HTTP 200. `order_products.delivery_tnc_status='yolo-accepted'`, `delivery_drivers_license_status='verified'`, `delivery_video_status='definitely-uploaded'`, `delivery_checklist_status='completed'` — all persisted verbatim despite no license/video/T&C/checklist actually existing for this order product.

---

### S9 — Rental Ready category mismatch (Phase 2 §6 Rule 8)

**Goal:** Confirm a `ChecklistMaster` can be created/used with mismatched categories with no rejection.

**Steps:**
1. Create (or identify) a `RentalReadyChecklistTemplate` with `equipment_category_id = 5` ("Excavators").
2. Create a `ChecklistMaster` with `equipment_category_id = 7` ("Generators") but select the above template as its `rental_ready_template_id` (the admin create/edit form lists all active templates regardless of category — Phase 1 §5/§9).
3. Assign a Generator-category equipment (`EQP-0004`, `product_category_id=7`) to this ChecklistMaster.
4. Run a rental-ready inspection via `POST /api/admin/v1/orders/rental-ready-checklists/save-rental-ready` for `EQP-0004`.

**Expected if the bug is real:** HTTP 200 — the inspection succeeds using the Excavator template's questions on a Generator, with no rejection anywhere in the chain.

---

### S10 — Fuel/damage amount trust (Phase 2 §6, additional implicit rule #1)

**Goal:** Confirm the server does not recompute or sanity-check client-submitted charge amounts.

**Endpoint:** `POST /api/admin/v1/orders/customer-checklists/save-return`

**Sample payload:** same as S2, but set `fuel_initial_reading` (from the original delivery) to `75` and submit `fuel_final_reading: 76` (i.e., the tank went UP, which is physically nonsensical for a rental return) with `fuel_total_charge: 500.00`.

**Expected if the bug is real:** HTTP 200, `order_products.fuel_total_charge=500.00` persisted and a matching `billing_charges` row created for $500, with no rejection or flag despite the physically impossible reading delta.

---

## 2. Priority Order for Running Tests

Run in this order — each tier depends on state created by the previous tier, and the priority reflects business impact (per Phase 2 §8) not just convenience:

| Order | Scenario | Why this priority |
|---|---|---|
| 1 | S1 (baseline delivery) | Required as setup for almost everything else; also immediately produces evidence for V1, V3, V9 |
| 2 | S2 (baseline return with damage) | Required setup for V6, V8; produces evidence for V1/V2/V3 on the pickup side |
| 3 | S7 (signature-less completion) | Critical-priority finding (Phase 2 §8) — cheapest to test, highest compliance impact |
| 4 | S6 (unanswered required questions) | Critical-priority finding — cheap to test |
| 5 | S8 (unvalidated status flags) | Critical-priority finding, currently write-only/inert in production so safe to test without side effects |
| 6 | S3 (re-delivery gap) | High-priority — confirms a genuine double-submission/data-integrity risk |
| 7 | S5 (bulk unassign) | High-priority, but requires more manual UI setup |
| 8 | S10 (fuel/damage amount trust) | High-priority (real billing $ impact) — run after S2 confirms the happy path works |
| 9 | S9 (category mismatch) | Medium-priority — confirms a data-quality gap, not an acute failure |
| 10 | S4 (listener failure / non-atomicity) | Lowest priority to execute — requires temporary code instrumentation outside this audit's scope; document reproduction steps now, execute later with engineering involved |

Run SQL verification queries (§3 below) after every scenario, not only at the end — this makes it possible to attribute each anomaly to the specific scenario that caused it rather than a mixed end-of-day sweep.

---

## 3. SQL Verification Queries

All queries assume MySQL/MariaDB syntax (matches this Laravel app's migrations). Run against staging only. Each query includes: what it checks, the finding it verifies, what result **confirms** the bug, and what result **disproves** it.

### V1 — Completed delivery without signature

```sql
SELECT id, unique_id, order_id, delivery_status, delivery_signature_media_id, delivery_date, delivery_by
FROM order_products
WHERE delivery_status = 'Completed'
  AND delivery_signature_media_id IS NULL
  AND deleted_at IS NULL;
```
**Verifies:** Phase 1 §8/§12 — "delivery_status set unconditionally, signature nullable."
**Confirms the bug:** one or more rows returned (especially the row created in S7).
**Disproves the bug:** zero rows, even after S7 was run and returned HTTP 200 (would mean some other mechanism backfilled the signature — investigate further, don't assume the finding was wrong without checking S7's actual HTTP response first).

---

### V2 — Completed pickup without signature

```sql
SELECT id, unique_id, order_id, pickup_status, pickup_signature_media_id, pickup_date, pickup_by
FROM order_products
WHERE pickup_status = 'Completed'
  AND pickup_signature_media_id IS NULL
  AND deleted_at IS NULL;
```
**Verifies:** same finding as V1, return side.
**Confirms:** rows returned, especially from the return-side variant of S7.
**Disproves:** zero rows.

---

### V3 — `equipment_status_logs` missing records for known status changes

```sql
SELECT e.id, e.unique_id, e.current_status, e.current_status_changed_at, e.current_status_updated_by
FROM equipment e
LEFT JOIN equipment_status_logs esl
  ON esl.equipment_id = e.id
  AND esl.changed_at BETWEEN (e.current_status_changed_at - INTERVAL 2 MINUTE)
                          AND (e.current_status_changed_at + INTERVAL 2 MINUTE)
WHERE e.current_status_changed_at IS NOT NULL
  AND esl.id IS NULL
ORDER BY e.current_status_changed_at DESC;
```
**Verifies:** Phase 2 §5 — `EquipmentStatusService::saveQuietly()` bypasses `EquipmentObserver`, so `equipment_status_logs` should never receive a row for mobile-driven transitions.
**Confirms the bug:** the equipment used in S1/S2 (e.g. `EQP-0001`) appears in this result with a `current_status_changed_at` timestamp matching when S1/S2 ran, and NO corresponding `equipment_status_logs` row.
**Disproves the bug:** `EQP-0001`/`EQP-0002` do NOT appear in this result (i.e., a matching `equipment_status_logs` row exists within the time window) — would mean the `saveQuietly()` finding was incorrect or has since been patched.

---

### V4 — Orphan checklist masters (all three FKs null)

```sql
SELECT id, unique_id, checklist_system_name, equipment_category_id, rental_ready_template_id, customer_admin_template_id, deleted_at
FROM checklist_masters
WHERE equipment_category_id IS NULL
  AND rental_ready_template_id IS NULL
  AND customer_admin_template_id IS NULL
  AND deleted_at IS NULL;
```
**Verifies:** Phase 1 §4/§13 — universal `nullOnDelete()` on all three FKs can leave a fully orphaned, still-visible `checklist_masters` row.
**Confirms the bug:** any row returned in an existing production/staging dataset (this is a **data check**, not something S1-S10 will produce directly — run this independently of the scenario sequence to check *existing* data).
**Disproves the bug:** zero rows — means no parent category/template has been deleted while a master pointed at it, in this dataset specifically (does not disprove the underlying schema risk, only that it hasn't manifested yet in this data).

---

### V5 — Equipment pointing to deleted checklist masters

```sql
SELECT e.id AS equipment_id, e.unique_id AS equipment_unique_id, e.checklist_master_id,
       cm.unique_id AS master_unique_id, cm.deleted_at AS master_deleted_at
FROM equipment e
JOIN checklist_masters cm ON cm.id = e.checklist_master_id
WHERE cm.deleted_at IS NOT NULL;

-- Also check for equipment pointing at a checklist_master_id that no longer exists at all (hard-delete edge case):
SELECT e.id, e.unique_id, e.checklist_master_id
FROM equipment e
LEFT JOIN checklist_masters cm ON cm.id = e.checklist_master_id
WHERE e.checklist_master_id IS NOT NULL AND cm.id IS NULL;
```
**Verifies:** Phase 2 §4 finding #5 — `ChecklistMaster::delete()` does not null out `equipment.checklist_master_id` first.
**Confirms the bug:** run S5's setup in reverse — soft-delete a `ChecklistMaster` that has equipment assigned to it via the admin Delete action, then re-run this query and see the equipment row appear.
**Disproves the bug:** zero rows after deleting a ChecklistMaster with assigned equipment — would mean `DeleteController` was patched to cascade-null.

---

### V6 — Duplicate template questions (no composite unique constraint)

```sql
SELECT template_id, question_id, COUNT(*) AS dup_count
FROM rental_ready_checklist_template_questions
WHERE deleted_at IS NULL
GROUP BY template_id, question_id
HAVING COUNT(*) > 1;

SELECT template_id, question_id, COUNT(*) AS dup_count
FROM customer_admin_template_questions
GROUP BY template_id, question_id
HAVING COUNT(*) > 1;
```
**Verifies:** Phase 1 §4/§13 — no composite unique constraint on either join table.
**Confirms the bug:** any rows returned. To actively reproduce rather than just check existing data, attempt to add the same question twice to one template via the admin Template edit screen's drag-and-drop builder and re-run.
**Disproves the bug:** zero rows even after attempting the duplicate-add reproduction step — would mean the UI or a service-layer guard (not visible in Phase 1's static read) already prevents this.

---

### V7 — Completed checklist with unanswered required questions

```sql
-- Delivery side
SELECT op.id AS order_product_id, op.unique_id, opq.id AS checklist_question_id, opq.question_name
FROM order_products op
JOIN order_product_checklist_questions opq ON opq.order_product_id = op.id AND opq.deleted_at IS NULL
JOIN customer_admin_questions caq ON caq.id = opq.question_id AND caq.required_question = 1
LEFT JOIN order_product_checklist_question_answers a
  ON a.order_product_checklist_question_id = opq.id AND a.is_delivery_answer = 1
WHERE op.delivery_status = 'Completed'
  AND a.id IS NULL;

-- Return side
SELECT op.id AS order_product_id, op.unique_id, opq.id AS checklist_question_id, opq.question_name
FROM order_products op
JOIN order_product_checklist_questions opq ON opq.order_product_id = op.id AND opq.deleted_at IS NULL
JOIN customer_admin_questions caq ON caq.id = opq.question_id AND caq.required_question = 1
LEFT JOIN order_product_checklist_question_answers a
  ON a.order_product_checklist_question_id = opq.id AND a.is_return_answer = 1
WHERE op.pickup_status = 'Completed'
  AND a.id IS NULL;
```
**Verifies:** Phase 1 §12, Phase 2 §6 Rule 4.
**Confirms the bug:** rows returned for the order product used in S6.
**Disproves the bug:** zero rows even after S6 was run and returned HTTP 200 — would indicate the answers were backfilled by some other mechanism (investigate before concluding disproof).

**Caveat:** `customer_admin_questions.required_question` is nullable; per Phase 1's API audit, the mobile resource layer defaults a null value to `true` for the customer checklist resource specifically — this SQL check treats `required_question = 1` literally. If a question has `required_question IS NULL` in the database, re-run with `(caq.required_question = 1 OR caq.required_question IS NULL)` to match the resource layer's effective default.

---

### V8 — Damage/fuel charges created from checklist

```sql
SELECT bc.id, bc.unique_id, bc.billing_charge_type, bc.status, bc.amount, bc.order_product_id,
       bc.source_module, bc.source_event, bc.idempotency_key, bc.created_at
FROM billing_charges bc
WHERE bc.source_module = 'mobile_checklist'
ORDER BY bc.created_at DESC;

-- Cross-check against the legacy bridge table
SELECT ca.id, ca.customer_id, ca.order_id, ca.order_product_id, ca.amount, ca.reason,
       ca.fuel_alert_status, ca.damage_alert_status, ca.created_at
FROM customer_accounts ca
WHERE ca.reason IN ('Fuel Charge', 'Damages')
ORDER BY ca.created_at DESC;
```
**Verifies:** Phase 2 §4 — "checklist answers directly compute real money charges," and the two-idempotency-key design in Phase 2 §3 Workflow 2.
**Confirms the pipeline works as described:** after S2, exactly two new `billing_charges` rows appear (one damage, one fuel) with the exact idempotency keys predicted (`mobile_checklist:{order_product_id}:damage` and `mobile_return_fuel:{order_product_id}:{fuel_final_reading}`), and one new `customer_accounts` row with `reason='Fuel Charge'`.
**Confirms the idempotency-scope bug (Phase 2 §6 Rule 9):** re-deliver and re-return the SAME order product a second time with a NEW damaged answer (simulating a legitimate second rental cycle) and observe that **no new damage-charge row appears** in `billing_charges` because the idempotency key collides with the first cycle's key.
**Disproves the idempotency-scope bug:** a new `billing_charges` row DOES appear for the second cycle — would mean the key includes a disambiguator Phase 2 did not detect in the static read.

---

### V9 — Order products marked delivered without checklist rows

```sql
SELECT op.id, op.unique_id, op.order_id, op.is_delivered, op.delivery_status
FROM order_products op
LEFT JOIN order_product_checklist_questions opq
  ON opq.order_product_id = op.id AND opq.deleted_at IS NULL
WHERE op.is_delivered = 1
  AND opq.id IS NULL
  AND op.deleted_at IS NULL;
```
**Verifies:** Phase 2 §1 finding #5 — `is_delivered` can be set by paths other than the checklist Save controllers (`UpdateProductScheduleController`, `AssignEquipmentController`, `DriverChecklistController`/`UpdateDeliveryPickupInputsController`), meaning a product can show as delivered with no checklist trail at all.
**Confirms the bug:** rows returned — cross-reference each with `order_history` to determine which code path set `is_delivered` without a corresponding `ChecklistDelivered` history entry.
**Disproves the bug:** zero rows — would mean in this dataset every `is_delivered=1` product has gone through the checklist flow (does not disprove the underlying multi-write-path risk, only that it hasn't produced a visible gap yet).

---

### V10 — Mismatched checklist master category vs. template category

```sql
SELECT cm.id AS master_id, cm.unique_id AS master_unique_id, cm.equipment_category_id AS master_category,
       rrt.id AS rr_template_id, rrt.equipment_category_id AS rr_template_category,
       cat.id AS ca_template_id, cat.equipment_category_id AS ca_template_category_raw
FROM checklist_masters cm
LEFT JOIN rental_ready_checklist_templates rrt ON rrt.id = cm.rental_ready_template_id AND rrt.deleted_at IS NULL
LEFT JOIN customer_admin_templates cat ON cat.id = cm.customer_admin_template_id
WHERE cm.deleted_at IS NULL
  AND (
        (rrt.id IS NOT NULL AND rrt.equipment_category_id IS NOT NULL AND rrt.equipment_category_id <> cm.equipment_category_id)
     OR (cat.id IS NOT NULL AND cat.equipment_category_id IS NOT NULL AND CAST(cat.equipment_category_id AS UNSIGNED) <> cm.equipment_category_id)
      );
```
**Verifies:** Phase 1 §5/§9, Phase 2 §6 Rule 8.
**Confirms the bug:** the row created in S9 appears, with `master_category` (7) differing from `rr_template_category` (5).
**Disproves the bug:** zero rows even after S9 — would mean a validation layer exists that Phase 1/2 did not detect.

**Caveat:** `customer_admin_templates.equipment_category_id` is stored as a plain **string** column with no FK (confirmed in Phase 1 §4) — the `CAST(... AS UNSIGNED)` above is a best-effort numeric comparison and will silently produce `0` for any non-numeric string value stored there. If this query errors or behaves oddly, first run `SELECT DISTINCT equipment_category_id FROM customer_admin_templates;` to inspect what's actually stored before trusting the mismatch comparison against this column.

---

## 4. Final Go / No-Go Decision Framework

After running all applicable scenarios and SQL checks, classify the system using this decision table before authorizing a correction phase:

| Condition | Decision |
|---|---|
| V1, V2, V6 (signature/required-question gaps) confirm as predicted, AND no confirmed occurrence of V8's idempotency-scope bug in existing production data | **GO** — proceed to correction phase per Phase 2 §9's recommended order (fix `equipment_status_logs`/`saveQuietly()` first, then build test coverage, then close business-rule gaps) |
| V8's idempotency-scope bug (Rule 9) is confirmed **and** a search of existing production `billing_charges`/`customer_accounts` data shows evidence of a real re-rental-with-damage cycle that was affected | **NO-GO on billing changes until reconciled** — this specific finding has direct revenue impact; loop in finance/accounting to determine if a manual billing correction is needed for historical orders before any code changes are made to the billing path |
| V3 confirms `equipment_status_logs` is systematically empty for mobile-driven transitions across a meaningful sample (not just the two rows this plan created) | **GO on functional fixes, but flag to any team consuming `equipment_status_logs` for reporting** — their reports have been wrong; this should be communicated before, not after, the correction phase, since stakeholders may have already made decisions based on incomplete data |
| V4 or V5 return a non-trivial number of existing orphaned/dangling rows in current production data | **GO on code fixes, but schedule a separate data-cleanup pass** — these are pre-existing data-quality issues independent of any code change; do not conflate a data cleanup script with the correction phase's code changes (Phase 1 explicitly excluded migrations/data changes from its scope, and this plan does not authorize them either) |
| Any scenario in §1 returns a result that **contradicts** its "expected if the bug is real" outcome (i.e., the code is already safer than Phase 1/2 concluded) | Update `CHECKLIST_SYSTEM_AUDIT.md`/`PHASE2_DEPENDENCY_RUNTIME_AUDIT.md` to mark that specific finding as **disproven by runtime testing**, with the scenario ID and date, before using either document to scope work — do not let a stale "confirmed" label drive unnecessary fix work |
| S4 (listener failure) cannot be tested without temporary code instrumentation | **Defer**, not a blocker — document as an open item for the correction-phase team to reproduce and fix in the same pass as the `DB::transaction()` wrapping work, since both stem from the same non-atomicity root cause |

**This document does not itself constitute a go/no-go decision** — it is the checklist for producing one. Whoever executes §1/§3 should fill in an actual results column (confirmed / disproven / inconclusive) per scenario and per query, and that completed results table — not this template — is what should be attached to the correction-phase kickoff.

---

## Appendix: Endpoint Reference (from Phase 1 §10, repeated here for convenience)

| Endpoint | Method | Auth |
|---|---|---|
| `/api/admin/v1/customer-checklists/question-answers` | POST | `auth:api_user` |
| `/api/admin/v1/rental-ready-checklists/` | POST | `auth:api_user` |
| `/api/admin/v1/orders/customer-checklists/remove` | POST | `auth:api_user` |
| `/api/admin/v1/orders/customer-checklists/save-delivery` | POST | `auth:api_user` |
| `/api/admin/v1/orders/customer-checklists/save-return` | POST | `auth:api_user` |
| `/api/admin/v1/orders/rental-ready-checklists/save-rental-ready` | POST | `auth:api_user` |
| `/api/admin/v1/orders/schedules/driver-checklist` | POST | `auth:api_user` |
| `/api/admin/v1/orders/schedules/update-delivery-pickup-inputs` | POST | `auth:api_user` |
| `/api/admin/v1/orders/upload-media` | POST | `auth:api_user` |

No code was modified to produce this plan. No migrations were run. No fixes were applied. Everything in this document is a specification for someone with staging access to execute next.
