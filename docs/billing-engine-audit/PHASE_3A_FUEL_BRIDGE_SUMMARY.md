# Billing Engine — Phase 3A: Fuel Charge Bridge Summary

Completed: 2026-06-27  
Branch: `feature/billing-engine-consolidation`

---

## Objective

Wire the Admin Dashboard fuel charge modal (`FuelChargeStoreController`) into the Billing Engine in bridge mode. Legacy behavior is fully preserved. A `billing_charges` row is now also created in parallel for validation and future migration.

---

## Controller Integrated

**File:** [app/Http/Controllers/Admin/Dashboard/FuelChargeStoreController.php](../../app/Http/Controllers/Admin/Dashboard/FuelChargeStoreController.php)  
**Route:** `POST /fuel-charge/store` (name: `admin.dashboard.fuel-charge.store`)  
**Trigger:** Admin clicks "Fuel Charge" in the Dashboard alert modal

---

## Pre-Integration Audit

### Request fields
| Field | Type | Validation |
|-------|------|-----------|
| `customer_id` | int | required, exists:customers,id |
| `amount` | float | required, numeric, min:0.01 |
| `notes` | string | nullable, max:500 |
| `responsible_person` | int | required, exists:users,id |
| `sales_tax_type` | string | nullable, in:add,free,reverse |

### Legacy records created (unchanged)
| Table | What is written |
|-------|----------------|
| `customer_accounts` | type=charge, reason='Fuel Charge', fuel_alert_status=pending |

No `order_extra_charges` rows are written by this controller — that is only written during payment collection.

### Customer account impact (unchanged)
`CustomHelper::updateCreditBalance()` is called after the CA row is saved, recomputing the customer's running balance.

### Alert / report impact (unchanged)
The Fuel Charge Alerts report reads `customer_accounts` where `reason = 'Fuel Charge'`. This query is unchanged — `billing_charges` is not read by any report.

### Payment impact (unchanged)
Payment collection is handled by `PaymentStoreController`. This integration does not touch payment logic.

### Key discovery: no order context
This controller operates at the **customer level**, not the order level. No `order_id` or `order_product_id` is present in the request. As a result:
- `parent_order_id` in `billing_charges` is `null` for this charge path
- A migration was added to make `parent_order_id` nullable

---

## Changes Made

### New migration
`2026_06_27_000002_make_billing_charges_parent_order_nullable.php`  
Makes `billing_charges.parent_order_id` nullable. Required because Dashboard-modal charges have no order context.

### Updated data object
`app/Http/DataObjects/BillingChargeRequest.php`  
`orderId` changed from `int` to `?int`. All existing tests still pass.

### Modified controller
`FuelChargeStoreController` — bridge write added **after** the legacy `DB::commit()`.

Bridge logic summary:
1. Legacy `DB::transaction` runs unchanged (creates CA row, calls `updateCreditBalance`, creates customer note)
2. If legacy write fails → rollback and return 500 exactly as before
3. If legacy write succeeds → `DB::commit()` called
4. **After commit**, `BillingEngine::charge()` is called in a separate `try/catch`
5. If BillingEngine fails → error logged to `billing_engine` channel; response still returns `{'success': true}`
6. If BillingEngine succeeds → `billing_charges` row created; `BillingChargeCreatedEvent` fired

---

## BillingCharge Mapping

| BillingChargeRequest field | Value |
|---------------------------|-------|
| `type` | `BillingChargeType::Fuel->value` = `'fuel'` |
| `orderId` | `null` (no order context in Dashboard modal) |
| `customerId` | `$record->customer_id` |
| `amount` | `(float) $record->amount` |
| `taxType` | `$record->sales_tax_type` |
| `responsiblePersonId` | `$user->id` |
| `notes` | `$record->notes` |
| `sourceModule` | `BillingSourceModule::AdminFuelCharge->value` = `'admin_fuel_charge'` |
| `sourceEvent` | `BillingSourceEvent::AdminFuelChargeCreated->value` = `'admin_fuel_charge_created'` |
| `sourceReferenceType` | `'CustomerAccount'` |
| `sourceReferenceId` | `$record->id` (the CA row's PK) |
| `metadata.legacy_controller` | `'FuelChargeStoreController'` |
| `metadata.legacy_customer_account_id` | `$record->id` |
| `metadata.sales_tax_type` | `$record->sales_tax_type` |
| `idempotencyKey` | `"admin_fuel_charge:{$record->id}"` |

---

## Idempotency Strategy

Key format: `admin_fuel_charge:{customer_account_id}`

The `customer_accounts.id` is the most stable identifier — it is auto-incremented by the DB, assigned atomically at save time, and unique per charge. Using it as the key means:
- If the same CA record's ID is submitted twice (e.g., a webhook or queue retry), only one `billing_charges` row is created
- No collision is possible between different fuel charges

---

## Failure Handling

```
┌─────────────────────────────────────────────────┐
│ Legacy write (DB::transaction)                  │
│   → CustomerAccount row                         │
│   → updateCreditBalance()                       │
│   → customer note                               │
│   → DB::commit()                                │
└─────────────┬───────────────────────────────────┘
              │ commit succeeded
              ▼
┌─────────────────────────────────────────────────┐
│ BillingEngine::charge() [separate try/catch]    │
│   SUCCESS → billing_charges row created         │
│             BillingChargeCreatedEvent fired     │
│   FAILURE → Log::channel('billing_engine')      │
│             ->error("BillingEngine bridge failed│
│             | controller=FuelChargeStoreCtrl    │
│             | customer_account_id=X ...")       │
│             response still returns success      │
└─────────────────────────────────────────────────┘
```

The legacy charge is **already committed** before the bridge write attempts. A bridge failure never causes:
- Duplicate CA rows
- Rollback of the legacy charge
- Error returned to the admin user

---

## Tests Added

**File:** [tests/Feature/BillingEngine/FuelChargeBridgeTest.php](../../tests/Feature/BillingEngine/FuelChargeBridgeTest.php)  
**Tests:** 18 | **Assertions:** 31 | **Result:** All passing

| Test | Verifies |
|------|---------|
| `fuel_charge_creates_customer_account_record` | Legacy CA row is still created |
| `fuel_charge_response_is_success` | Response unchanged |
| `fuel_charge_also_creates_billing_charge_record` | Bridge creates BillingCharge |
| `billing_charge_has_correct_type_and_status` | type=fuel, status=pending |
| `billing_charge_has_correct_amount_and_customer` | Amount and customer_id correct |
| `billing_charge_has_null_parent_order_id_for_dashboard_path` | No order context → null |
| `billing_charge_stores_source_module_and_event` | admin_fuel_charge / admin_fuel_charge_created |
| `billing_charge_stores_legacy_customer_account_id` | source_reference_id = CA id |
| `billing_charge_stores_legacy_controller_in_metadata` | metadata.legacy_controller set |
| `billing_charge_has_blc_prefixed_unique_id` | BLC- prefix on unique_id |
| `billing_charge_idempotency_key_uses_customer_account_id` | key = admin_fuel_charge:{ca.id} |
| `duplicate_request_does_not_create_duplicate_billing_charge` | Idempotency prevents dup |
| `legacy_charge_succeeds_even_when_billing_engine_bridge_fails` | CA created even if table missing |
| `billing_engine_failure_is_logged_to_billing_engine_channel` | Error is logged |
| `tax_type_is_stored_on_billing_charge` | sales_tax_type=add stored correctly |
| `tax_type_defaults_to_free_when_not_provided` | Defaults to free |
| `reports_use_customer_accounts_not_billing_charges` | Alert query unaffected |
| `billing_charge_is_not_read_by_fuel_alert_query` | Legacy report query isolation |

---

## Test Totals (full suite)

```
php artisan test tests/Feature/BillingEngine/

Tests:  43 passed (112 assertions)
Duration: ~38s
```

---

## Confirmation — What Was NOT Changed

| Component | Status |
|-----------|--------|
| Reports (Fuel Charge Alerts, Sales Tax, SalesReportEngineV2) | Unchanged |
| Payment handling (`PaymentStoreController`) | Unchanged |
| Order Edit UI / additional charges blade | Unchanged |
| Mobile checklist fuel charges (`SaveReturnController`) | Unchanged |
| `ChargeService::createFromOrderProduct()` | Unchanged |
| `AlertChargeController` (Order Edit fuel charge) | Unchanged |
| CRM `ChargeStoreController` | Unchanged |
| `DamageChargeStoreController` | Unchanged |
| Rental extension logic | Unchanged |
| `order_extra_charges` table/logic | Unchanged |
| Customer balance calculation | Unchanged |
| Customer notes | Unchanged |
| Routes | Unchanged |

---

## Risks Discovered

1. **Dashboard charges have no order context** — `parent_order_id` is null for this path. This was not obvious from the refactor plan, which assumed all charges would link to an order. The migration making `parent_order_id` nullable resolves this, but the Billing Engine will need a way to query charges by customer (not order) for these types.

2. **Bridge failure is silent to the admin** — by design, if `billing_charges` fails, the admin sees success. This means a failed bridge creates a gap (CA row exists, no BillingCharge row). Monitoring the `billing-engine.log` is the only way to detect this. A future health check comparing CA charges vs BillingCharges is recommended before Phase 3 bulk conversion.

---

## Next Step

**Phase 3B — Second fuel charge entry point**  
Convert `AlertChargeController` (type=fuel, Order Edit page). This controller DOES have an order context (`order_id` is available), so `parent_order_id` will not be null. This will also be the first charge with a non-null `order_product_id`.

Alternatively, proceed to **Phase 3C** to convert all three remaining admin fuel charge entry points in a single commit and close the fuel charge bridge loop.
