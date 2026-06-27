# Phase 3B — Fuel Alert Charge Bridge Integration

**Branch:** `feature/billing-engine-consolidation`
**Date:** 2026-06-27
**Status:** Complete

---

## Controller Converted

**File:** `app/Http/Controllers/Admin/OrderManagement/Orders/AlertChargeController.php`
**Route:** `POST /order-management/orders/{unique_id}/alert-charge`
**Route name:** `admin.order-management.orders.alert-charge`

---

## Pre-Integration Audit

### Request fields
| Field | Constraint |
|-------|-----------|
| `type` | `in:fuel,damage` — controller handles BOTH types |
| `amount` | `numeric`, `min:0.01` |
| `notes` | `nullable`, `string` |
| `responsible_person` | `exists:users,id` |
| `sales_tax_type` | `nullable`, `in:add,free,reverse` |
| Route: `{unique_id}` | Order `unique_id` — order is loaded from this |

### Handles fuel AND damage (single controller)

This is a single `__invoke` controller that accepts `type=fuel` or `type=damage` and diverges on that value. Phase 3B **bridges only the fuel path**. The damage path is completely unchanged.

### Legacy tables written (both charge types)
| Table | Purpose |
|-------|---------|
| `customer_accounts` | Primary charge record — always written for both types |
| `customer_notes` | Audit description note attached to the customer |

**NOT written:** `order_extra_charges`, `order_product_fuel_charge_logs`, `order_products`

### Order context (key difference from Phase 3A)
- `$order->id` **is available** — loaded from route param `{unique_id}`
- `order_product_id` is NOT available — this is an order-level alert, not per-product
- `customer_id` comes from `$order->customer_id`

### Tax handling
- `sales_tax_type` comes from request (default `'free'`)
- `sales_tax` is hardcoded to `0`

### Fuel alert status
- `fuel_alert_status = 'pending'` on the `CustomerAccount` row for fuel
- `damage_alert_status = 'pending'` for damage (unchanged)

---

## What Was Changed

### `AlertChargeController` — bridge added after `DB::commit()`

The bridge fires **only when `$request->type === 'fuel'`**. The damage path has no bridge and is unmodified.

**Structure:**
1. Legacy `DB::transaction` runs exactly as before (CA row + `updateCreditBalance` + customer note)
2. `DB::commit()` completes
3. If `type === 'fuel'`: `BillingEngine::charge()` runs in a separate `try/catch`
4. Bridge failure → logged to `billing_engine` channel, response unaffected
5. Bridge success → `BillingCharge` row created, event fired

### `BillingChargeRequest` — no changes needed
`orderId` was already made nullable in Phase 3A. No further changes required.

---

## BillingCharge Mapping

| `billing_charges` column | Value | Source |
|--------------------------|-------|--------|
| `billing_charge_type` | `fuel` | `BillingChargeType::Fuel->value` |
| `status` | `pending` | BillingEngine default |
| `parent_order_id` | `$order->id` | **Populated (unlike Phase 3A)** |
| `customer_id` | `$order->customer_id` | Order relationship |
| `order_product_id` | `null` | Not available at this level |
| `amount` | `$record->amount` | CA record after save |
| `tax_type` | `$record->sales_tax_type` | CA record |
| `tax_amount` | `0` | BillingEngine default |
| `responsible_person_id` | `$user->id` | Resolved from `responsible_person` field |
| `notes` | `$record->notes` | CA record |
| `source_module` | `admin_fuel_charge` | `BillingSourceModule::AdminFuelCharge` |
| `source_event` | `admin_fuel_charge_created` | `BillingSourceEvent::AdminFuelChargeCreated` |
| `source_reference_type` | `CustomerAccount` | |
| `source_reference_id` | `$record->id` | CA record ID |
| `idempotency_key` | `admin_fuel_alert_charge:{ca_id}` | CA ID is the stable legacy anchor |
| `metadata.legacy_controller` | `AlertChargeController` | |
| `metadata.legacy_customer_account_id` | `$record->id` | |
| `metadata.order_id` | `$order->id` | |
| `metadata.order_unique_id` | `$uniqueId` | Route param |
| `metadata.sales_tax_type` | `$record->sales_tax_type` | |

---

## Idempotency Key

Format: `admin_fuel_alert_charge:{customer_account_id}`

**Rationale:** The `CustomerAccount` record is the only stable legacy anchor for this controller. There is no `order_extra_charges` row and no fuel charge log row. The CA ID is created inside the transaction and is available immediately after `DB::commit()`.

---

## Failure Handling

```
┌────────────────────────────────────────────────────┐
│ DB::transaction {                                   │
│   CustomerAccount::create(...)                      │
│   updateCreditBalance(...)                          │
│   customer->notes()->create(...)                    │
│   DB::commit() ◄── legacy complete                 │
│ }                                                   │
│                                                     │
│ if (type === 'fuel') {                              │
│   try {                                             │
│     BillingEngine::charge(...)  ◄── bridge         │
│   } catch (Throwable $e) {                          │
│     Log::channel('billing_engine')->error(...)      │
│     // swallowed — admin sees success               │
│   }                                                 │
│ }                                                   │
│                                                     │
│ return response()->json(['success' => true])        │
└────────────────────────────────────────────────────┘
```

If the bridge throws (e.g., `billing_charges` table missing, constraint violation):
- Legacy `CustomerAccount` row is **already committed** — unaffected
- Error is logged to `billing_engine` channel with `controller`, `customer_account_id`, `order_id`, and the exception message
- Admin user receives the same success JSON response
- No duplicate legacy records are created

---

## Architectural Discovery

**Phase 3B is the first fuel charge path with a non-null `parent_order_id`.**

Phase 3A (`FuelChargeStoreController`) is customer-level — no order context, so `parent_order_id = null`.
Phase 3B (`AlertChargeController`) is order-level — `$order->id` is available, `parent_order_id` is populated.

This distinction will be important when the Billing Engine UI is built: alert charges can be linked back to their originating order, dashboard modal charges cannot.

---

## Tests

**File:** `tests/Feature/BillingEngine/FuelAlertChargeBridgeTest.php`
**20 tests / 36 assertions — all passing**

| Test | Covers |
|------|--------|
| `test_fuel_alert_charge_creates_customer_account_record` | Legacy CA row created |
| `test_fuel_alert_charge_response_is_success` | Response shape unchanged |
| `test_damage_alert_charge_creates_customer_account_with_correct_fields` | Damage path unmodified |
| `test_damage_alert_charge_does_not_create_billing_charge` | Bridge fires only for fuel |
| `test_fuel_alert_charge_also_creates_billing_charge_record` | BillingCharge created |
| `test_billing_charge_has_correct_type_and_status` | Type=fuel, status=pending |
| `test_billing_charge_has_correct_amount_and_customer` | Amount and customer_id |
| `test_billing_charge_has_populated_parent_order_id` | **parent_order_id non-null** |
| `test_billing_charge_has_null_order_product_id` | order_product_id is null |
| `test_billing_charge_stores_source_module_and_event` | Source tracking |
| `test_billing_charge_stores_legacy_customer_account_id` | source_reference_id = CA id |
| `test_billing_charge_stores_order_context_in_metadata` | order_id + unique_id in metadata |
| `test_billing_charge_has_blc_prefixed_unique_id` | BLC- prefix |
| `test_billing_charge_idempotency_key_uses_customer_account_id` | Key format |
| `test_duplicate_idempotency_key_does_not_create_second_billing_charge` | No duplicate on retry |
| `test_legacy_charge_succeeds_even_when_billing_engine_bridge_fails` | Failure isolation |
| `test_billing_engine_failure_is_logged_to_billing_engine_channel` | Log channel verified |
| `test_tax_type_is_stored_on_billing_charge` | tax_type=add |
| `test_tax_type_defaults_to_free_when_not_provided` | tax_type default |
| `test_billing_charge_does_not_interfere_with_legacy_alert_query` | No report interference |

### Full suite result
```
63 tests / 148 assertions — all passing
  BillingEngineTest:          25 tests
  FuelChargeBridgeTest:       18 tests (Phase 3A)
  FuelAlertChargeBridgeTest:  20 tests (Phase 3B)
```

---

## Guardrail Confirmations

| Area | Changed? |
|------|---------|
| Mobile checklist fuel charges (`SaveReturnController`) | No |
| Mobile damage charges | No |
| Admin damage charge path in `AlertChargeController` | No |
| `FuelChargeStoreController` (Phase 3A) | No |
| `order_extra_charges` writes | No |
| `order_product_fuel_charge_logs` | No |
| Reports (Calls Log / Fuel Charge Alerts / Damage Alerts) | No |
| Tax reports / QuickBooks | No |
| Order Edit UI | No |
| Rental extension logic | No |
| Service tickets | No |
| Existing database migrations | No |
| Legacy `CustomerAccount` write in `AlertChargeController` | No (unchanged) |

---

## Files Created

- `tests/Feature/BillingEngine/FuelAlertChargeBridgeTest.php`
- `docs/billing-engine-audit/PHASE_3B_FUEL_ALERT_BRIDGE_SUMMARY.md`

## Files Modified

- `app/Http/Controllers/Admin/OrderManagement/Orders/AlertChargeController.php` — bridge added

---

## Next Phase

**Phase 3C: `ChargeStoreController` (CRM path, type=fuel)**

This is the CRM-sourced fuel charge controller. Like Phase 3A it may or may not have order context — audit required before implementation.
