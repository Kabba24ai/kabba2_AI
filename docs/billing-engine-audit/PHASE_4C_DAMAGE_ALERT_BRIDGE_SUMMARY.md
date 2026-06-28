# Phase 4C — Order Edit Damage Alert Bridge Integration

**Branch:** `feature/billing-engine-consolidation`
**Date:** 2026-06-27
**Status:** Complete

---

## Controller Modified

**File:** `app/Http/Controllers/Admin/OrderManagement/Orders/AlertChargeController.php`
**Route:** `POST /order-management/orders/{unique_id}/alert-charge`
**Route name:** `admin.order-management.orders.alert-charge`
**Response:** JSON `{'success': true, 'message': 'Damage Alert created successfully.'}`

---

## Pre-Integration Audit

### Existing fuel bridge (Phase 3B) — confirmed intact

The `if ($request->type === 'fuel') { ... }` block added in Phase 3B remains unchanged. Phase 4C added `elseif ($request->type === 'damage') { ... }` immediately below it. The fuel arm's idempotency key (`admin_fuel_alert_charge:{ca_id}`), source module/event, and metadata are untouched.

### Damage branch condition

```php
$request->type === 'damage'
```

The `type` field is validated as `required|in:fuel,damage` — only these two values are accepted. There is no third case.

### Request fields
| Field | Constraint | Notes |
|-------|-----------|-------|
| `type` | required, in:fuel,damage | Drives both the CA field set and the bridge arm |
| `amount` | required, numeric, min:0.01 | |
| `notes` | nullable, string, max:500 | |
| `responsible_person` | required, exists:users | |
| `sales_tax_type` | nullable, in:add,free,reverse | Stored directly on CA as `sales_tax_type` |

### Legacy tables written by the damage path
| Table | Written |
|-------|---------|
| `customer_accounts` | Yes — `reason='Damages'`, `damage_alert_status='pending'`, `fuel_alert_status=null`, `order_id=$order->id` |
| `customer_notes` | Yes — description includes "Damage charge added.", amount, responsible person, and notes |
| `order_extra_charges` | No |
| `order_product_damage_charge_logs` | No |
| `order_products` | No |

### Order context
**Yes.** `$order` is resolved from the `{unique_id}` route parameter via `Order::where('unique_id', $uniqueId)->with('customer')->firstOrFail()`. This is the only damage path where `parent_order_id` is populated.

### Order product context
**None.** The alert modal operates at the order level — no OP is targeted.

### Customer identification
`$order->customer_id` — resolved via the order, not from the request directly.

### Payment / credit balance
`CustomHelper::updateCreditBalance($record)` runs inside the transaction. Unchanged.

### Tax handling
`$request->sales_tax_type ?? 'free'` stored on CA as `sales_tax_type`; `sales_tax = 0`. No change.

### Report / UI dependencies
The Damage Alerts report reads from `customer_accounts` where `reason='Damages'`. The Order Edit UI reads from the same table. Neither reads from `billing_charges`. Both are completely unaffected.

---

## What Was Changed

### `AlertChargeController` — `elseif` damage arm added

The existing `if ($request->type === 'fuel') { ... }` block was preserved exactly. An `elseif ($request->type === 'damage') { ... }` arm was added immediately below. The comment on the fuel block was updated from `Phase 3B — fuel only` to `Phase 3B` since it now has a sibling.

**Structure after Phase 4C:**
```
DB::commit()

if (type === 'fuel') {          ← Phase 3B (unchanged)
    try { BillingEngine... }
    catch { Log::error... }
} elseif (type === 'damage') {  ← Phase 4C (new)
    try { BillingEngine... }
    catch { Log::error... }
}

return response()->json(...)
```

### `FuelAlertChargeBridgeTest.php` — one test updated

`test_damage_alert_charge_does_not_create_billing_charge` was renamed to `test_damage_alert_charge_also_creates_billing_charge` and its assertion was updated from `assertEquals(0, ...)` to `assertEquals(1, ...)` with an added type check. The old assertion was correct for Phase 3B (damage was unbrided); it is now correct for Phase 4C (damage is bridged).

---

## BillingCharge Mapping

| `billing_charges` column | Value | Source |
|--------------------------|-------|--------|
| `billing_charge_type` | `damage` | `BillingChargeType::Damage->value` |
| `status` | `pending` | BillingEngine default |
| `parent_order_id` | **`$order->id`** | **Populated — first damage path with order context** |
| `customer_id` | `$order->customer_id` | From the resolved order |
| `order_product_id` | **null** | Alert modal has no OP context |
| `amount` | `$record->amount` | Float cast |
| `tax_type` | `$record->sales_tax_type` | CA model value (defaults to `'free'`) |
| `tax_amount` | `0` | BillingEngine default |
| `responsible_person_id` | `$user->id` | Resolved via `User::findOrFail` |
| `notes` | `$record->notes` | CA record |
| `source_module` | `admin_damage_charge` | `BillingSourceModule::AdminDamageCharge` |
| `source_event` | `admin_damage_charge_created` | `BillingSourceEvent::AdminDamageChargeCreated` |
| `source_reference_type` | `CustomerAccount` | |
| `source_reference_id` | `$record->id` | CA record ID |
| `idempotency_key` | `admin_damage_alert_charge:{ca_id}` | See below |
| `metadata.legacy_controller` | `AlertChargeController` | |
| `metadata.legacy_customer_account_id` | `$record->id` | |
| `metadata.order_id` | `$order->id` | |
| `metadata.order_unique_id` | `$uniqueId` | Route param — matches the order's unique_id |
| `metadata.customer_id` | `$order->customer_id` | |
| `metadata.sales_tax_type` | `$record->sales_tax_type` | |
| `metadata.alert_context` | `true` | Marks this as an order-edit alert charge |

---

## Idempotency Key

Format: `admin_damage_alert_charge:{customer_account_id}`

Example: `admin_damage_alert_charge:52`

**Rationale:** The CA record is created inside the committed transaction before the bridge fires — it is the stable anchor. The prefix `admin_damage_alert_charge:` is distinct from all existing keys:

| Phase | Key format |
|-------|-----------|
| 3A | `admin_fuel_charge:{ca_id}` |
| 3B | `admin_fuel_alert_charge:{ca_id}` |
| 3C | `crm_fuel_charge:{ca_id}` |
| 3D | `mobile_return_fuel:{op_id}:{fuel_final_reading}` |
| 4B | `admin_dashboard_damage_charge:{ca_id}` |
| **4C** | **`admin_damage_alert_charge:{ca_id}`** |
| 4D (next) | `crm_damage_charge:{ca_id}` |

---

## Failure Handling

```
┌────────────────────────────────────────────────────────────────────┐
│ $request->validate(...)                                             │
│ $order = Order::where('unique_id', ..)->firstOrFail()              │
│ $user  = User::findOrFail(...)                                      │
│                                                                     │
│ DB::beginTransaction()                                              │
│ try {                                                               │
│   CustomerAccount::save(...)   ← CA with damage_alert_status       │
│   CustomHelper::updateCreditBalance(...)                            │
│   customer->notes()->create(...)                                    │
│   DB::commit()       ◄── legacy complete                            │
│                                                                     │
│   if (type === 'fuel') {   ← Phase 3B (unchanged)                  │
│     try { BillingEngine::charge(...) }                              │
│     catch { Log::error(...) }                                       │
│   } elseif (type === 'damage') {   ← Phase 4C                      │
│     try { BillingEngine::charge(...) }                              │
│     catch { Log::error(...) }  ← logged, swallowed                 │
│   }                                                                 │
│                                                                     │
│   return response()->json(['success' => true])                      │
│                                                                     │
│ } catch (Throwable $e) {                                            │
│   DB::rollBack()       ◄── legacy failure path (unchanged)         │
│   return response()->json(['success' => false], 500)                │
│ }                                                                   │
└────────────────────────────────────────────────────────────────────┘
```

If the damage bridge throws:
- CA row is **already committed** — unaffected
- Customer note is **already committed** — unaffected
- Credit balance has **already been updated** — unaffected
- Error is logged to `billing_engine` channel with `customer_account_id`, `order_id`, and exception message
- Admin sees `{'success': true}` — order edit workflow is not interrupted

---

## Damage Charge Bridge Map — Complete Admin Picture

| Phase | Controller | `parent_order_id` | `order_product_id` | Context |
|-------|-----------|-------------------|--------------------|---------|
| 4B | `DamageChargeStoreController` | null | null | Dashboard modal |
| **4C** | **`AlertChargeController`** | **populated** | **null** | **Order Edit alert** |
| 4D (next) | `ChargeStoreController` | null | null | CRM customer page |

Phase 4C is the **first damage path with `parent_order_id` populated.** This means `billing_charges` rows from this path can be traced back to the specific order that generated the damage alert.

---

## Tests

**New file:** `tests/Feature/BillingEngine/DamageAlertChargeBridgeTest.php`
**20 tests / 34 assertions — all passing**

| Test | Covers |
|------|--------|
| `test_damage_alert_charge_creates_customer_account_record` | Legacy CA row with correct fields |
| `test_damage_alert_charge_response_is_success` | JSON success shape |
| `test_damage_alert_charge_also_creates_billing_charge` | BillingCharge created |
| `test_billing_charge_has_correct_type_and_status` | type=damage, status=pending |
| `test_billing_charge_has_correct_amount_and_customer` | amount + customer_id |
| `test_billing_charge_has_populated_parent_order_id` | parent_order_id = order.id |
| `test_billing_charge_has_null_order_product_id` | order_product_id null |
| `test_billing_charge_stores_source_module_and_event` | source tracking |
| `test_billing_charge_stores_customer_account_as_source_reference` | source_reference = CA |
| `test_billing_charge_stores_order_context_in_metadata` | full metadata verified |
| `test_billing_charge_has_blc_prefixed_unique_id` | BLC- prefix |
| `test_billing_charge_idempotency_key_uses_damage_alert_prefix` | key format |
| `test_duplicate_key_does_not_create_second_billing_charge` | idempotency guard |
| `test_legacy_charge_succeeds_even_when_billing_engine_bridge_fails` | failure isolation |
| `test_billing_engine_failure_is_logged_to_billing_engine_channel` | log channel |
| `test_tax_type_is_stored_on_billing_charge` | tax_type=add |
| `test_tax_type_defaults_to_free_when_not_provided` | null → free |
| `test_fuel_arm_still_creates_fuel_billing_charge` | Phase 3B fuel regression |
| `test_fuel_bridge_uses_fuel_source_module_and_event` | fuel source tracking unchanged |
| `test_billing_charge_does_not_interfere_with_damage_alert_query` | no report interference |

**Updated test in `FuelAlertChargeBridgeTest.php`:**
- `test_damage_alert_charge_does_not_create_billing_charge` → renamed to `test_damage_alert_charge_also_creates_billing_charge`
- Assertion updated from `assertEquals(0, ...)` to `assertEquals(1, ...)` with type check
- This test was Phase 3B-era behavior (damage unbrided); now reflects Phase 4C reality

### Full suite result
```
143 tests / 290 assertions — all passing
  BillingEngineTest:                    25 tests  (Phase 2)
  FuelChargeBridgeTest:                 18 tests  (Phase 3A)
  FuelAlertChargeBridgeTest:            20 tests  (Phase 3B — one test updated)
  CrmFuelChargeBridgeTest:              20 tests  (Phase 3C)
  MobileReturnFuelBridgeTest:           21 tests  (Phase 3D)
  DashboardDamageChargeBridgeTest:      20 tests  (Phase 4B)
  DamageAlertChargeBridgeTest:          20 tests  (Phase 4C)
```

---

## Guardrail Confirmations

| Area | Changed? |
|------|---------|
| `DashboardDamageChargeController` (Phase 4B) | No |
| `ChargeStoreController` damage path | No |
| Mobile damage charges | No |
| `ChargeService` | No |
| `AmountUpdateController` | No |
| Damage Alerts report | No |
| Order Edit UI | No |
| Phase 3B fuel arm of `AlertChargeController` | No — intact, no regression |
| Phase 3A–3D controllers | No |
| Rental extension logic | No |
| Service tickets | No |
| Tax reports / QuickBooks | No |
| Existing database migrations | No |
| Legacy CA write in `AlertChargeController` | No (preserved exactly) |
| Customer note creation | No (preserved exactly) |
| Credit balance update | No (preserved exactly) |

---

## Files Created

- `tests/Feature/BillingEngine/DamageAlertChargeBridgeTest.php`
- `docs/billing-engine-audit/PHASE_4C_DAMAGE_ALERT_BRIDGE_SUMMARY.md`

## Files Modified

- `app/Http/Controllers/Admin/OrderManagement/Orders/AlertChargeController.php` — `elseif` damage arm added; fuel bridge comment updated
- `tests/Feature/BillingEngine/FuelAlertChargeBridgeTest.php` — one test renamed and updated to reflect Phase 4C behavior

---

## Next Phase

**Phase 4D: `ChargeStoreController` — damage reason (`reason === 'Damages'`)**

The fuel bridge guard from Phase 3C (`if ($validated['reason'] === 'Fuel Charge') { ... }`) is already in place. Phase 4D adds the `elseif ($validated['reason'] === 'Damages') { ... }` arm. This is a redirect-response controller (not JSON). `parent_order_id = null` — no order context in the CRM modal.
