# Phase 4 — Damage Charge Architecture Review

**Branch:** `feature/billing-engine-consolidation`
**Date:** 2026-06-27
**Status:** Architecture review only — no code changes in this phase

---

## Purpose

Before bridging any damage charge controllers into BillingEngine, this document audits all existing damage charge entry points, evaluates future damage sources, and establishes the architectural decisions that govern Phases 4B, 4C, and 4D.

---

## 1. Existing Damage Charge Entry Points

### 1A. Dashboard Modal — `DamageChargeStoreController`

**File:** `app/Http/Controllers/Admin/Dashboard/DamageChargeStoreController.php`
**Route:** `POST /admin/dashboard/damage-charge/store`
**Route name:** `admin.dashboard.damage-charge.store`
**Response:** JSON `{'success': true}`

**Request fields:**
| Field | Constraint | Notes |
|-------|-----------|-------|
| `customer_id` | required, exists:customers | No order context |
| `amount` | required, numeric, min:0.01 | |
| `notes` | nullable, string, max:500 | |
| `responsible_person` | required, exists:users | User ID |
| `sales_tax_type` | nullable, in:add,free,reverse | Field name is `sales_tax_type` (matches CA model directly — no rename needed) |

**Legacy tables written:**
| Table | Written |
|-------|---------|
| `customer_accounts` | Yes — `damage_alert_status = 'pending'`, `reason = 'Damages'`, `type = 'charge'` |
| `customer_notes` | Yes — description built from amount + responsible_person + notes |

**BillingEngine mapping:**
| `billing_charges` column | Value |
|--------------------------|-------|
| `billing_charge_type` | `damage` |
| `parent_order_id` | **null** (no order in Dashboard modal) |
| `order_product_id` | **null** |
| `customer_id` | `$request->customer_id` |
| `amount` | `$record->amount` |
| `tax_type` | `$record->sales_tax_type` (field name is already `sales_tax_type` here — no alias) |
| `responsible_person_id` | `$user->id` |
| `source_module` | `admin_damage_charge` |
| `source_event` | `admin_damage_charge_created` |
| `source_reference_type` | `CustomerAccount` |
| `source_reference_id` | `$record->id` |

**Planned idempotency key:** `admin_dashboard_damage_charge:{ca_id}`

**Fuel analogue:** Phase 3A (`FuelChargeStoreController`) — direct parallel; standalone controller, JSON response, no order context.

---

### 1B. Order Edit Alert — `AlertChargeController` (damage arm)

**File:** `app/Http/Controllers/Admin/OrderManagement/Orders/AlertChargeController.php`
**Route:** `POST /orders/{uniqueId}/alert-charge` (same controller bridged for fuel in Phase 3B)
**Response:** JSON `{'success': true}`

**Guard:** `$request->type === 'damage'` (currently falls through — no bridge)

**Key differences from the fuel arm:**
- Has full order context: `$order->id` resolved from `$uniqueId`
- `parent_order_id` → **populated** (`$order->id`)
- `order_product_id` → **null** (alert modal does not target a specific OP)

**Bridge guard already in place:** Phase 3B added `if ($request->type === 'fuel') { ... }`. The damage path currently exits the if-block without triggering any bridge. Phase 4C will add the `elseif` arm immediately below.

**BillingEngine mapping:**
| `billing_charges` column | Value |
|--------------------------|-------|
| `billing_charge_type` | `damage` |
| `parent_order_id` | `$order->id` (**populated** — only damage path with order context) |
| `order_product_id` | **null** |
| `customer_id` | `$order->customer_id` |
| `amount` | `$record->amount` |
| `tax_type` | `$record->sales_tax_type` |
| `responsible_person_id` | `$user->id` |
| `source_module` | `admin_damage_charge` |
| `source_event` | `admin_damage_charge_created` |
| `source_reference_type` | `CustomerAccount` |
| `source_reference_id` | `$record->id` |
| `metadata.order_unique_id` | `$uniqueId` (route param — include for traceability) |

**Planned idempotency key:** `admin_order_damage_charge:{ca_id}`

**Fuel analogue:** Phase 3B (`AlertChargeController` fuel arm) — same controller, same transaction structure, same `$order` context.

---

### 1C. CRM Customer Page — `ChargeStoreController` (damage reason)

**File:** `app/Http/Controllers/Admin/Crm/Customers/CustomerAccount/ChargeStoreController.php`
**Route:** `POST /crm/customers/customer-account/charge-store`
**Route name:** `admin.crm.customers.customer-account.chargestore`
**Response:** `redirect()->back()` (web form controller, NOT JSON)

**Guard:** `$validated['reason'] === 'Damages'`

**Key differences from the fuel arm:**
- No order context → `parent_order_id = null`
- Tax field naming: request field is `sales_tax`, stored on CA model as `sales_tax_type`; read from model post-save as `$record->sales_tax_type ?? 'free'`
- Response is a redirect — test assertions must use `assertRedirect()`, not `assertOk()`

**Bridge guard already in place:** Phase 3C added `if ($validated['reason'] === 'Fuel Charge') { ... }`. The damage path falls through. Phase 4D will add the `elseif` arm immediately below.

**BillingEngine mapping:**
| `billing_charges` column | Value |
|--------------------------|-------|
| `billing_charge_type` | `damage` |
| `parent_order_id` | **null** (no order in CRM modal) |
| `order_product_id` | **null** |
| `customer_id` | `$record->customer_id` |
| `amount` | `$record->amount` |
| `tax_type` | `$record->sales_tax_type ?? 'free'` |
| `responsible_person_id` | `$user->id` |
| `source_module` | `admin_damage_charge` |
| `source_event` | `admin_damage_charge_created` |
| `source_reference_type` | `CustomerAccount` |
| `source_reference_id` | `$record->id` |

**Planned idempotency key:** `crm_damage_charge:{ca_id}`

**Fuel analogue:** Phase 3C (`ChargeStoreController` fuel arm) — same controller, same redirect response, no order context.

---

## 2. What `AmountUpdateController` Is NOT

**File:** `app/Http/Controllers/Admin/Dashboard/AmountUpdateController.php`

This controller handles admin-side amount adjustments but writes **only** to `order_product_damage_charge_logs` and `order_product_fuel_charge_logs`. It does NOT create `CustomerAccount` records. It is an audit trail controller, not a charge creation controller.

**Decision: Do NOT bridge `AmountUpdateController`.** There is no charge event to bridge — it creates no CA, issues no financial event, and carries no customer-facing billing implication. It is a pure ledger adjustment log and should remain so.

---

## 3. Damage Report Data Sources

The Damage Alerts report (`app/Http/Controllers/Admin/Reports/NewDamageAlerts/IndexController.php`) currently merges two sources:

1. **OP-based:** `order_products` where `damage_charge > 0` AND no matching CA record exists for that OP
2. **CRM-based:** `customer_accounts` where `reason = 'Damages'`

BillingEngine `billing_charges` rows created in Phase 4 are a **shadow record** in bridge mode — they do not alter either existing query. The report continues reading from legacy sources exactly as before. Report unification into `billing_charges` as the single source of truth is Phase 7 scope.

---

## 4. Future Damage Sources

### 4A. Mobile Return Checklist — Greenfield

`SaveReturnController` does not currently call `ChargeService::createFromOrderProduct($op, 'damage')`. The `'damage'` path in `ChargeService` exists but is not invoked from mobile.

When this is implemented, it will mirror Phase 3D exactly for the damage type:
- Both `parent_order_id` AND `order_product_id` will be populated
- `BillingSourceEvent::ReturnChecklistDamageCharge` already exists in the enum
- `BillingSourceModule::MobileChecklist` already exists in the enum
- Bridge guard: `if ($legacyCa !== null)` — same pattern as Phase 3D
- Idempotency key format: `mobile_return_damage:{order_product_id}:{damage_charge}`
  - `damage_charge` is the dollar amount set on the OP — the stable, deterministic value from the mobile request
  - Does NOT use CA ID (same rationale as Phase 3D: CA ID is not available until after the CA row is created)

**Scope:** Mobile damage is out of scope for Phase 4. It requires its own specification and feature work in `SaveReturnController`. It will be handled in a dedicated phase.

### 4B. Service Tickets — Greenfield

**Current state:**
- `service_tasks` table exists (fields: `name`, `description`, `category_id`, `auto_apply`, `instructions`, timestamps, softDeletes)
- `equipment_service_tasks` pivot table exists (links equipment to service templates and tasks)
- No `ServiceTicket` model exists in the codebase
- No billing integration exists

**`BillingChargeType::ServiceTicket`** is already defined in the enum, confirming this path is planned.

**Billing strategy: one `billing_charges` row per service ticket, NOT per task**

A service ticket is the financial event. Individual tasks within a ticket (oil change, filter replacement, inspection, etc.) are labor/material line items — not independent billing events. The correct mapping is:

| Level | BillingEngine role |
|-------|-------------------|
| Service ticket (approved/closed) | One `BillingCharge` row |
| Individual tasks within the ticket | Stored in `metadata` JSON on the charge row |

This approach:
- Keeps `billing_charges` at the granularity of financial events, not labor line items
- Avoids schema complexity (no `billing_charge_line_items` table needed in Phase 6)
- Preserves itemized detail in the `metadata` column for invoice generation
- If multi-line invoicing is required later, `metadata` already holds the data without a migration

**Idempotency key format (proposed):** `service_ticket:{service_ticket_id}` — one stable key per ticket approval event.

**Scope:** Service ticket billing is Phase 6. No action in Phase 4.

### 4C. Technician Inspections — Greenfield

No system exists yet. When implemented, damage charges resulting from a technician inspection should use:
- `BillingSourceModule::MaintenanceModule` (already defined in the enum)
- A new `BillingSourceEvent` case (`TechnicianInspectionDamageCharge` or similar)
- Idempotency key format TBD when the inspection system is designed

### 4D. Equipment Total Loss — Potential

Not currently in scope. If an equipment item is marked a total loss via the dashboard or CRM, the charge flows through the existing CRM path (`ChargeStoreController`, `reason = 'Damages'`). No new entry point is needed — the CRM bridge (Phase 4D) handles this automatically.

---

## 5. Parent / Child Order Relationship

### Rental Extensions

When an order is extended, a child order is created with a suffix (e.g., `ORD-001-1`, `ORD-001-2`). `billing_charges.parent_order_id` accepts any order ID — parent or child. The column name reflects its role as the "order context for this charge," not necessarily the root order.

**Damage charges by path:**

| Path | `parent_order_id` | Rationale |
|------|------------------|-----------|
| Dashboard modal | null | No order selected in the modal |
| Order Edit alert | `$order->id` (parent or child) | Whatever order is open in the edit view |
| CRM modal | null | No order selected in the modal |
| Mobile return (future) | `$orderProduct->order_id` | Direct FK from the OP being returned |

**No special child-order handling is required in Phase 4.** If a damage charge is added while editing a child (extension) order, `parent_order_id` will contain the child order's ID — which is correct, as the damage is associated with that rental period.

---

## 6. DB Column Audit — `billing_charges`

No new migrations are required for Phase 4.

| Column | Nullable | Status |
|--------|---------|--------|
| `parent_order_id` | Yes | Made nullable in Phase 3A migration `2026_06_27_000002_make_billing_charges_parent_order_nullable.php` |
| `order_product_id` | Yes | Nullable — confirmed by Phases 3A/3B/3C (all pass `null` without error) |
| `billing_charge_type` | No | `Damage` case exists in `BillingChargeType` enum |
| `source_module` | No | `AdminDamageCharge`, `MobileChecklist` already defined in `BillingSourceModule` |
| `source_event` | No | `AdminDamageChargeCreated`, `ReturnChecklistDamageCharge` already defined in `BillingSourceEvent` |
| `metadata` | Yes (JSON) | No changes needed |
| `tax_type` | No | `free`, `add`, `reverse` values already in use |

**All columns, enums, and nullability rules are already correct for Phase 4.**

---

## 7. Idempotency Key Map — Complete Damage Picture

| Phase | Controller | Key format | `parent_order_id` | `order_product_id` |
|-------|-----------|-----------|-------------------|--------------------|
| 4B | `DamageChargeStoreController` | `admin_dashboard_damage_charge:{ca_id}` | null | null |
| 4C | `AlertChargeController` | `admin_order_damage_charge:{ca_id}` | `$order->id` | null |
| 4D | `ChargeStoreController` | `crm_damage_charge:{ca_id}` | null | null |
| Future | `SaveReturnController` | `mobile_return_damage:{op_id}:{damage_charge}` | `$op->order_id` | `$op->id` |

**Prefix convention:** All damage keys are distinct from all fuel keys. No key from Phase 3 can collide with any Phase 4 key.

---

## 8. Implementation Sequence

### Phase 4B — `DamageChargeStoreController`

Simplest damage bridge. Standalone controller, single purpose (damage only), JSON response, no existing bridge code to work around.

Implementation pattern: identical to Phase 3A (`FuelChargeStoreController`). Add bridge block after `DB::commit()`, before `return response()->json(...)`. Wrap in `try/catch`. Log failure to `billing_engine` channel.

**Recommended first.**

### Phase 4C — `AlertChargeController` (damage arm)

Bridge guard already in place from Phase 3B (`if ($request->type === 'fuel') { ... }`). Phase 4C adds `elseif ($request->type === 'damage') { ... }` immediately below the closing brace of the fuel block.

Both the fuel and damage arms sit inside the outer `try` block, after `DB::commit()`. Each arm has its own inner `try/catch`. The outer catch handles `DB::rollBack()` — irrelevant once commit has run, but left intact for the pre-commit failure path.

**Recommended second.**

### Phase 4D — `ChargeStoreController` (damage reason)

Bridge guard already in place from Phase 3C (`if ($validated['reason'] === 'Fuel Charge') { ... }`). Phase 4D adds `elseif ($validated['reason'] === 'Damages') { ... }` immediately below.

Tax field: read `$record->sales_tax_type ?? 'free'` from the saved CA model (same as fuel arm — request field is `sales_tax`, CA column is `sales_tax_type`).

Response is `redirect()->back()` — tests use `assertRedirect()`.

**Recommended third.**

---

## 9. Test Targets Per Phase

Each phase follows the established 20-test pattern from Phase 3:

**Phase 4B — `DamageChargeStoreController` (20 tests)**
- Legacy CA creation (damage_alert_status=pending, reason=Damages)
- BillingCharge created with type=damage
- `parent_order_id = null`, `order_product_id = null`
- correct customer_id, amount, tax_type
- source_module = `admin_damage_charge`, source_event = `admin_damage_charge_created`
- source_reference_type = CustomerAccount, source_reference_id = ca_id
- BLC- prefixed unique_id
- idempotency key format `admin_dashboard_damage_charge:{ca_id}`
- duplicate key prevention
- failure isolation (drop billing_charges → legacy CA still saves → JSON success)
- error logged to `billing_engine` channel
- no fuel BillingCharge created from a damage request
- no interference with existing damage report queries

**Phase 4C — `AlertChargeController` damage arm (20 tests)**
Same checklist as 4B, plus:
- `parent_order_id = $order->id` (non-null — only admin damage path with order context)
- fuel arm still works (fuel request → BillingChargeType::Fuel, idempotency key unchanged)
- damage arm does not interfere with fuel arm

**Phase 4D — `ChargeStoreController` damage reason (20 tests)**
Same checklist as 4B, plus:
- `parent_order_id = null` (no order context in CRM modal)
- Response is `assertRedirect()` (not `assertOk()`)
- fuel arm (`reason = 'Fuel Charge'`) still works after damage arm added
- tax_type defaults to `'free'` when `sales_tax` is null

---

## 10. Architectural Findings

### Finding 1: No new migrations required

All three damage controller bridges can be implemented with zero schema changes. `billing_charges` already has nullable `parent_order_id`, nullable `order_product_id`, a `Damage` charge type, and damage-specific source events and modules in the enums.

### Finding 2: `AmountUpdateController` is permanently out of scope

Writes only to `order_product_damage_charge_logs`. No CA, no billing event. Do not bridge.

### Finding 3: Existing damage report is unaffected by bridge writes

The Damage Alerts report reads from `order_products` and `customer_accounts`. BillingEngine `billing_charges` rows are shadow records in bridge mode. The report is not modified in Phase 4.

### Finding 4: Mobile damage is greenfield — keep out of Phase 4

`SaveReturnController` has no `'damage'` call to `ChargeService::createFromOrderProduct`. Implementing mobile damage requires a separate spec and feature. Do not include in Phase 4.

### Finding 5: Service ticket billing strategy is confirmed

One `BillingCharge` per service ticket approval/close event. Individual tasks are `metadata`, not separate rows. This is the correct granularity and requires no new schema.

### Finding 6: All three admin damage paths are symmetric with their fuel counterparts

| Fuel phase | Damage phase | Controller | Context |
|-----------|-------------|-----------|---------|
| 3A | 4B | `DamageChargeStoreController` / `FuelChargeStoreController` | Dashboard, no order |
| 3B | 4C | `AlertChargeController` (fuel vs damage arm) | Order Edit, has order |
| 3C | 4D | `ChargeStoreController` (fuel vs damage reason) | CRM, no order |
| 3D | Future | `SaveReturnController` | Mobile, has order + OP |

This symmetry means the Phase 4 implementation is mechanical: replace `Fuel` with `Damage` in type/event/module/key, preserve the transaction structure, verify the guard condition.

---

## 11. Summary

Phase 4 bridges three existing admin damage charge controllers. No production schema changes are required. Implementation is symmetric with the fuel phases.

| Phase | Controller | Analogue | Recommended order |
|-------|-----------|---------|------------------|
| 4B | `DamageChargeStoreController` | 3A | First |
| 4C | `AlertChargeController` (damage arm) | 3B | Second |
| 4D | `ChargeStoreController` (damage reason) | 3C | Third |

Mobile damage (future Phase 3D analogue) and service ticket billing (Phase 6) are explicitly out of scope for Phase 4.

---

## Guardrail Confirmations

| Area | Changed in Phase 4A? |
|------|---------------------|
| Production controllers | No |
| Database migrations | No |
| BillingEngine core (`BillingEngine.php`) | No |
| Reports (Damage Alerts / Fuel Charge Alerts / Calls Log) | No |
| Mobile controllers (`SaveReturnController`) | No |
| `ChargeService` | No |
| Existing BillingEngine tests (Phases 2–3D) | No |
| Fuel charge paths | No |
| `AmountUpdateController` | No |

---

## Files Created in Phase 4A

- `docs/billing-engine-audit/PHASE_4_DAMAGE_ARCHITECTURE_REVIEW.md` (this file)
