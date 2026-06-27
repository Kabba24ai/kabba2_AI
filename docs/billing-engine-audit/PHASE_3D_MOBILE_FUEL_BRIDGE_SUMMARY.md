# Phase 3D — Mobile Return Checklist Fuel Bridge Integration

**Branch:** `feature/billing-engine-consolidation`
**Date:** 2026-06-27
**Status:** Complete

---

## Controller / Service Modified

**Controller:** `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveReturnController.php`
**Service (unchanged):** `app/Services/ChargeService.php`
**Route:** `POST http://api.kabba.local/api/admin/v1/orders/customer-checklists/save-return`

---

## Pre-Integration Audit

### Call site
`SaveReturnController` is the **only caller** of `ChargeService::createFromOrderProduct()`. No other controllers use this method.

**Line 134 (original):**
```php
ChargeService::createFromOrderProduct($orderProduct, 'fuel', $validated['user_id'] ?? null);
```

**After Phase 3D:**
```php
$legacyCa = ChargeService::createFromOrderProduct($orderProduct, 'fuel', $validated['user_id'] ?? null);
// bridge fires if $legacyCa !== null
```

### Request fields (SaveReturnRequest)
| Field | Type | Notes |
|-------|------|-------|
| `order_product_unique_id` | required string | Lookup key for OrderProduct |
| `store_id` | required string (→ int exists check) | `exists:stores,id` |
| `user_id` | required string (→ int exists check) | Mobile user/driver ID |
| `fuel_final_reading` | nullable string | `'1/4'`, `'3/4'`, etc. |
| `fuel_total_charge` | nullable string | Dollar amount as string |
| `fuel_total_charge` | nullable string | Dollar amount as string |
| `signature_media` | nullable image | If provided, sets pickup_signature_media_id |
| `note`, `end_hours`, `total_charge`, `checklist` | nullable | Not relevant to fuel bridge |

### Available OrderProduct fields for bridge
| Field | Value | Notes |
|-------|-------|-------|
| `$orderProduct->order_id` | direct field | FK to orders |
| `$orderProduct->id` | direct field | FK used for order_product_id |
| `$orderProduct->product_id` | direct field | Product reference |
| `$orderProduct->equipment_id` | direct field | Equipment reference |
| `$orderProduct->fuel_initial_reading` | set by delivery | Reading at pickup |
| `$orderProduct->fuel_final_reading` | set by this request | Reading at return |
| `$orderProduct->fuel_total_charge` | set by this request | Charge amount |
| `$legacyCa->customer_id` | from CA record | Customer ID via order |
| `$legacyCa->id` | from CA record | Legacy anchor for metadata |

### Existing duplicate prevention (unchanged)
1. **Layer 1 — Controller**: If `returnSignatureMedia` exists on the OP (i.e., a previous return was already submitted and a signature was uploaded), `SaveReturnController` returns HTTP 409. Bridge never reached.
2. **Layer 2 — ChargeService**: Before saving the CA, `ChargeService::createFromOrderProduct()` checks for an existing `CustomerAccount` where `order_product_id + reason='Fuel Charge' + type='charge' + fuel_alert_status IN (pending, completed)`. If found, returns `null`. Bridge guard `if ($legacyCa !== null)` then prevents the bridge from firing.
3. **Layer 3 — BillingEngine**: Idempotency key check in `BillingEngine::charge()` prevents duplicate `billing_charges` rows even if the bridge is called twice.

### Legacy tables written (ChargeService::createFromOrderProduct)
| Table | Written |
|-------|---------|
| `customer_accounts` | Yes — with order_id, order_product_id, customer_id |
| `order_extra_charges` | No |
| `order_product_fuel_charge_logs` | No |
| `order_products` | Only updated with fuel/return fields via `$orderProduct->update(...)` |

### ChargeService handles fuel AND damage
`ChargeService::createFromOrderProduct($orderProduct, $type)` accepts `'fuel'` or `'damage'`. Phase 3D bridges **only the `'fuel'` call**. The damage path (`$type === 'damage'`) is not wired in `SaveReturnController` at all — mobile damage charges are not yet implemented.

---

## What Was Changed

### `SaveReturnController` — bridge added after `ChargeService::createFromOrderProduct()`

**Before:**
```php
if (!empty($orderProductData['fuel_total_charge']) && $orderProductData['fuel_total_charge'] > 0) {
    $orderProduct->refresh();
    ChargeService::createFromOrderProduct($orderProduct, 'fuel', $validated['user_id'] ?? null);
}
```

**After:**
```php
if (!empty($orderProductData['fuel_total_charge']) && $orderProductData['fuel_total_charge'] > 0) {
    $orderProduct->refresh();
    $legacyCa = ChargeService::createFromOrderProduct($orderProduct, 'fuel', $validated['user_id'] ?? null);

    // Bridge fires only when legacy CA was actually created (non-null return)
    if ($legacyCa !== null) {
        try {
            BillingEngine::charge(new BillingChargeRequest(...));
        } catch (\Throwable $e) {
            Log::channel('billing_engine')->error(...);
        }
    }
}
```

### `ChargeService` — NOT modified
The service remains a pure legacy CA writer. The bridge lives in the controller, keeping ChargeService decoupled from BillingEngine.

---

## BillingCharge Mapping

| `billing_charges` column | Value | Source |
|--------------------------|-------|--------|
| `billing_charge_type` | `fuel` | `BillingChargeType::Fuel->value` |
| `status` | `pending` | BillingEngine default |
| `parent_order_id` | `$orderProduct->order_id` | **Populated** |
| `customer_id` | `$legacyCa->customer_id` | From the created CA record |
| `order_product_id` | `$orderProduct->id` | **Populated — first path with non-null value** |
| `amount` | `$orderProduct->fuel_total_charge` | Float cast |
| `tax_type` | `free` | Hardcoded (ChargeService always uses 'free') |
| `tax_amount` | `0` | BillingEngine default |
| `responsible_person_id` | `(int) $validated['user_id']` | Mobile user/driver |
| `source_module` | `mobile_checklist` | `BillingSourceModule::MobileChecklist` |
| `source_event` | `return_checklist_fuel_charge` | `BillingSourceEvent::ReturnChecklistFuelCharge` |
| `source_reference_type` | `OrderProduct` | |
| `source_reference_id` | `$orderProduct->id` | |
| `idempotency_key` | `mobile_return_fuel:{op_id}:{fuel_final_reading}` | See below |
| `metadata.legacy_controller` | `SaveReturnController` | |
| `metadata.legacy_service` | `ChargeService::createFromOrderProduct` | |
| `metadata.legacy_customer_account_id` | `$legacyCa->id` | |
| `metadata.order_id` | `$orderProduct->order_id` | |
| `metadata.order_product_id` | `$orderProduct->id` | |
| `metadata.customer_id` | `$legacyCa->customer_id` | |
| `metadata.fuel_initial_reading` | `$orderProduct->fuel_initial_reading` | |
| `metadata.fuel_final_reading` | `$orderProduct->fuel_final_reading` | |
| `metadata.fuel_total_charge` | `$orderProduct->fuel_total_charge` | |
| `metadata.product_id` | `$orderProduct->product_id` | |
| `metadata.equipment_id` | `$orderProduct->equipment_id` | |
| `metadata.submitted_by_user_id` | `$validated['user_id']` | |
| `metadata.mobile_source` | `true` | |

---

## Idempotency Key

Format: `mobile_return_fuel:{order_product_id}:{fuel_final_reading}`

Example: `mobile_return_fuel:42:1/4`

**Why not the CA ID:**
The CA ID is only stable after the CA row is created. On a mobile offline retry:
1. The app may re-submit the same return before receiving a response
2. `SaveReturnController` checks `returnSignatureMedia` first — if the signature was uploaded on the first attempt, the 409 fires on retry
3. If the signature was NOT uploaded (edge case), `ChargeService` may create a second CA row
4. In that scenario, the CA IDs would differ, making `ca_id`-based idempotency unreliable

Using `order_product_id + fuel_final_reading` is stable because:
- `order_product_id` never changes — it's the OP being returned
- `fuel_final_reading` is written by the mobile app and doesn't change between retries
- This key is deterministic from the mobile request data alone, without needing a DB read

---

## Failure Handling

```
┌────────────────────────────────────────────────────────────────────────────┐
│ $orderProduct->update($orderProductData)  ← sets fuel_total_charge on OP  │
│                                                                            │
│ if (fuel_total_charge > 0) {                                               │
│   $orderProduct->refresh()                                                 │
│   $legacyCa = ChargeService::createFromOrderProduct(...)                   │
│   // $legacyCa = null if duplicate guard fired; CA committed if non-null   │
│                                                                            │
│   if ($legacyCa !== null) {                                                │
│     try {                                                                  │
│       BillingEngine::charge(...)  ← bridge                                │
│     } catch (Throwable $e) {                                               │
│       Log::channel('billing_engine')->error(...)  ← logged only           │
│       // swallowed — mobile app sees success                               │
│     }                                                                      │
│   }                                                                        │
│ }                                                                          │
│                                                                            │
│ return response()->json(['success' => true])                               │
└────────────────────────────────────────────────────────────────────────────┘
```

If the bridge throws:
- `ChargeService` CA row is **already saved** — unaffected (no transaction wrapping in this controller)
- Error is logged to `billing_engine` channel with `order_product_id`, `customer_account_id`, and exception message
- Mobile app receives `success: true` — return flow is not interrupted
- No duplicate legacy records are created

---

## Testing Notes

**API domain constraint:** The `SaveReturnController` route is domain-restricted to `api.kabba.local`. Tests must use the full URL:
```php
->postJson('http://api.kabba.local/api/admin/v1/orders/customer-checklists/save-return', [...])
```

**`store_id` and `user_id` must be strings** in the request body — `SaveReturnRequest` validates them as `string`.

**Setup chain:** Customer → User → Store → Product → Equipment (current_status='rented') → Order (with morphs) → OrderProduct

---

## Tests

**File:** `tests/Feature/BillingEngine/MobileReturnFuelBridgeTest.php`
**21 tests / 44 assertions — all passing**

| Test | Covers |
|------|--------|
| `test_mobile_return_creates_customer_account_record` | Legacy CA row with order + OP context |
| `test_mobile_return_response_is_success_json` | API success response shape |
| `test_no_fuel_charge_skips_both_legacy_and_bridge` | null fuel_total_charge = no write |
| `test_charge_service_duplicate_guard_prevents_double_legacy_write` | ChargeService returns null on retry |
| `test_mobile_return_fuel_also_creates_billing_charge` | BillingCharge created |
| `test_billing_charge_has_correct_type_and_status` | type=fuel, status=pending |
| `test_billing_charge_has_correct_amount_and_customer` | amount + customer_id |
| `test_billing_charge_has_populated_parent_order_id` | parent_order_id non-null |
| `test_billing_charge_has_populated_order_product_id` | **order_product_id non-null** |
| `test_billing_charge_stores_mobile_source_module_and_event` | source tracking |
| `test_billing_charge_stores_order_product_as_source_reference` | source_reference = OrderProduct |
| `test_billing_charge_stores_mobile_metadata` | full metadata verified |
| `test_billing_charge_stores_legacy_customer_account_id` | CA id in metadata |
| `test_billing_charge_has_blc_prefixed_unique_id` | BLC- prefix |
| `test_billing_charge_idempotency_key_uses_order_product_id_and_reading` | key format |
| `test_idempotency_prevents_duplicate_billing_charge_on_retry` | no duplicate on retry |
| `test_bridge_does_not_fire_when_legacy_write_was_skipped` | null CA → no bridge |
| `test_legacy_ca_succeeds_even_when_billing_engine_bridge_fails` | failure isolation |
| `test_billing_engine_failure_is_logged_to_billing_engine_channel` | log channel |
| `test_billing_charge_does_not_interfere_with_legacy_fuel_alert_query` | no report interference |
| `test_mobile_damage_charges_are_not_added` | damage path untouched |

### Full suite result
```
104 tests / 223 assertions — all passing
  BillingEngineTest:              25 tests  (Phase 2)
  FuelChargeBridgeTest:           18 tests  (Phase 3A)
  FuelAlertChargeBridgeTest:      20 tests  (Phase 3B)
  CrmFuelChargeBridgeTest:        20 tests  (Phase 3C)
  MobileReturnFuelBridgeTest:     21 tests  (Phase 3D)
```

---

## Complete Fuel Charge Bridge Map (all admin + mobile paths)

| Phase | Controller | `parent_order_id` | `order_product_id` | Source |
|-------|-----------|-------------------|--------------------|--------|
| 3A | `FuelChargeStoreController` | null | null | Dashboard modal |
| 3B | `AlertChargeController` | populated | null | Order Edit alert |
| 3C | `ChargeStoreController` | null | null | CRM customer page |
| 3D | `SaveReturnController` | populated | **populated** | Mobile return checklist |

Phase 3D is the **first path with both `parent_order_id` and `order_product_id` populated**. This means BillingCharge rows from this path can be traced back to their exact originating order product.

---

## Guardrail Confirmations

| Area | Changed? |
|------|---------|
| Mobile damage charges (`SaveReturnController`) | No — damage not wired |
| `ChargeService::createFromOrderProduct()` internals | No — service unchanged |
| Admin damage charge paths | No |
| `FuelChargeStoreController` (Phase 3A) | No |
| `AlertChargeController` (Phase 3B) | No |
| `ChargeStoreController` (Phase 3C) | No |
| Reports (Calls Log / Fuel Charge Alerts / Damage Alerts) | No |
| Tax reports / QuickBooks | No |
| Rental extension logic | No |
| Service tickets | No |
| Order Edit UI | No |

---

## Files Created

- `tests/Feature/BillingEngine/MobileReturnFuelBridgeTest.php`
- `docs/billing-engine-audit/PHASE_3D_MOBILE_FUEL_BRIDGE_SUMMARY.md`

## Files Modified

- `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveReturnController.php` — bridge block added

---

## Next Phase

**Phase 4: Damage charges**

With all fuel charge entry points now bridged, Phase 4 converts the damage charge paths:
- `DamageChargeStoreController` (Dashboard modal, type=damage) — analogous to Phase 3A
- `AlertChargeController` (Order Edit, type=damage) — bridge guard already in place, just add the damage side
- `ChargeStoreController` (CRM, reason='Damages') — bridge guard already in place

Mobile damage charges are greenfield — not yet implemented in `SaveReturnController`.
