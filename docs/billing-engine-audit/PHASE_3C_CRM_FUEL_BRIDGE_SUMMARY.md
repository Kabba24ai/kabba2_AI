# Phase 3C — CRM Fuel Charge Bridge Integration

**Branch:** `feature/billing-engine-consolidation`
**Date:** 2026-06-27
**Status:** Complete

---

## Controller Converted

**File:** `app/Http/Controllers/Admin/Crm/Customers/CustomerAccount/ChargeStoreController.php`
**Route:** `POST /crm/customers/customer-account/charge-store`
**Route name:** `admin.crm.customers.customer-account.chargestore`
**Form Request:** `App\Http\Requests\Admin\Crm\Customers\CustomerAccount\ChargeStoreRequest`

---

## Pre-Integration Audit

### Request fields
| Field | Constraint | Notes |
|-------|-----------|-------|
| `customer_id` | required | Direct customer ID — no order lookup |
| `amount` | required, numeric, min:0.01 | |
| `reason` | required | Free string; `'Fuel Charge'` and `'Damages'` have special meaning |
| `responsible_person` | required | User ID |
| `sales_tax` | nullable, in:add,free,reverse | **Note: field is `sales_tax`, not `sales_tax_type`** |
| `notes` | nullable, string | |

### Handles fuel, damage, and potentially other reasons (single controller)

`reason` drives all conditional logic:
- `reason === 'Fuel Charge'` → `fuel_alert_status = 'pending'`
- `reason === 'Damages'` → `damage_alert_status = 'pending'`
- Any other `reason` → both statuses `null`

Phase 3C bridges **only the `'Fuel Charge'` reason**. All other reasons are unchanged.

### Legacy tables written
| Table | Written? |
|-------|---------|
| `customer_accounts` | Yes — always |
| `customer_notes` | No — this controller does NOT write notes (unlike 3A/3B) |
| `order_extra_charges` | No |
| `order_product_fuel_charge_logs` | No |
| `order_products` | No |

### Order context
**None.** The CRM charge modal takes only `customer_id`. No `order_id` is in the request or resolved from any source. `parent_order_id = null`.

### Order product context
**None.** `order_product_id = null`.

### Tax field naming difference
The request field is `sales_tax` (from `ChargeStoreRequest`), stored on the CA model as `sales_tax_type`. The bridge reads `$record->sales_tax_type` (post-save model value) to get the correct stored value.

### Response type
`redirect()->back()` — **NOT JSON**. This is a traditional web form controller. The bridge's `try/catch` does not affect the redirect path.

---

## What Was Changed

### `ChargeStoreController` — bridge added after `DB::commit()`

The bridge fires only when `$validated['reason'] === 'Fuel Charge'`. All other reasons (Damages, any custom reason) have no bridge and are completely unmodified.

**Structure:**
1. Legacy `DB::transaction` runs exactly as before (CA row + `updateCreditBalance`)
2. `DB::commit()` completes
3. If `reason === 'Fuel Charge'`: `BillingEngine::charge()` runs in a separate `try/catch`
4. Bridge failure → logged to `billing_engine` channel, response unaffected (admin still redirected)
5. Bridge success → `BillingCharge` row created, event fired
6. `flash()` and `redirect()->back()` proceed regardless of bridge outcome

---

## BillingCharge Mapping

| `billing_charges` column | Value | Source |
|--------------------------|-------|--------|
| `billing_charge_type` | `fuel` | `BillingChargeType::Fuel->value` |
| `status` | `pending` | BillingEngine default |
| `parent_order_id` | `null` | No order context in CRM modal |
| `customer_id` | `$record->customer_id` | From CA record |
| `order_product_id` | `null` | Not available at this level |
| `amount` | `$record->amount` | CA record after save |
| `tax_type` | `$record->sales_tax_type ?? 'free'` | Null-coalesced — CRM modal may omit tax |
| `tax_amount` | `0` | BillingEngine default |
| `responsible_person_id` | `$user->id` | Resolved via `User::findOrFail` |
| `notes` | `$record->notes` | CA record |
| `source_module` | `admin_fuel_charge` | `BillingSourceModule::AdminFuelCharge` |
| `source_event` | `admin_fuel_charge_created` | `BillingSourceEvent::AdminFuelChargeCreated` |
| `source_reference_type` | `CustomerAccount` | |
| `source_reference_id` | `$record->id` | CA record ID |
| `idempotency_key` | `crm_fuel_charge:{ca_id}` | Distinct prefix from 3A/3B |
| `metadata.legacy_controller` | `ChargeStoreController` | |
| `metadata.legacy_customer_account_id` | `$record->id` | |
| `metadata.customer_id` | `$record->customer_id` | |
| `metadata.sales_tax_type` | `$record->sales_tax_type` | |

---

## Idempotency Key

Format: `crm_fuel_charge:{customer_account_id}`

**Rationale:** The `CustomerAccount` record is the only stable legacy anchor. The prefix `crm_fuel_charge:` is distinct from Phase 3A (`admin_fuel_charge:`) and Phase 3B (`admin_fuel_alert_charge:`) to allow easy source identification in logs and future reporting.

| Phase | Controller | Idempotency key format |
|-------|-----------|----------------------|
| 3A | `FuelChargeStoreController` | `admin_fuel_charge:{ca_id}` |
| 3B | `AlertChargeController` | `admin_fuel_alert_charge:{ca_id}` |
| 3C | `ChargeStoreController` | `crm_fuel_charge:{ca_id}` |

---

## Failure Handling

```
┌────────────────────────────────────────────────────────────┐
│ DB::transaction {                                           │
│   CustomerAccount::create(...)                              │
│   updateCreditBalance(...)                                  │
│   DB::commit() ◄── legacy complete                         │
│ }                                                           │
│                                                             │
│ if (reason === 'Fuel Charge') {                             │
│   try {                                                     │
│     BillingEngine::charge(...)  ◄── bridge                 │
│   } catch (Throwable $e) {                                  │
│     Log::channel('billing_engine')->error(...)              │
│     // swallowed — admin is still redirected               │
│   }                                                         │
│ }                                                           │
│                                                             │
│ flash('Charge successfully added')->success()               │
│ return redirect()->back()                                   │
└────────────────────────────────────────────────────────────┘
```

If the bridge throws:
- Legacy `CustomerAccount` row is **already committed** — unaffected
- Error is logged to `billing_engine` channel with `controller`, `customer_account_id`, `customer_id`, and exception message
- Admin is redirected back with the success flash — no indication of bridge failure
- No duplicate legacy records are created

---

## Architectural Notes

### This is the third admin fuel charge entry point

| Path | Controller | Order context | `parent_order_id` |
|------|-----------|--------------|-------------------|
| Dashboard modal | `FuelChargeStoreController` | None | null |
| Order Edit alert | `AlertChargeController` | Yes — `$order->id` | Populated |
| CRM customer page | `ChargeStoreController` | None | null |

Two of three admin fuel charge paths have no order context. Only the Order Edit path (Phase 3B) populates `parent_order_id`.

### `customer_notes` not written here

Unlike Phase 3A (`FuelChargeStoreController`) and Phase 3B (`AlertChargeController`), this controller does NOT create a `customer_notes` record. The flash message and session redirect serve as the UI confirmation; there is no audit note attached to the customer.

---

## Tests

**File:** `tests/Feature/BillingEngine/CrmFuelChargeBridgeTest.php`
**20 tests / 31 assertions — all passing**

| Test | Covers |
|------|--------|
| `test_crm_fuel_charge_creates_customer_account_record` | Legacy CA row created |
| `test_crm_fuel_charge_returns_redirect` | Response is 302 redirect (not JSON) |
| `test_crm_damage_charge_creates_customer_account_with_correct_fields` | Damage path unmodified |
| `test_crm_damage_charge_does_not_create_billing_charge` | Bridge fires only for 'Fuel Charge' |
| `test_crm_fuel_charge_also_creates_billing_charge_record` | BillingCharge created |
| `test_billing_charge_has_correct_type_and_status` | Type=fuel, status=pending |
| `test_billing_charge_has_correct_amount_and_customer` | Amount and customer_id |
| `test_billing_charge_has_null_parent_order_id_for_crm_path` | parent_order_id is null |
| `test_billing_charge_has_null_order_product_id` | order_product_id is null |
| `test_billing_charge_stores_source_module_and_event` | Source tracking |
| `test_billing_charge_stores_legacy_customer_account_id` | source_reference_id = CA id |
| `test_billing_charge_stores_crm_context_in_metadata` | controller + customer_id in metadata |
| `test_billing_charge_has_blc_prefixed_unique_id` | BLC- prefix |
| `test_billing_charge_idempotency_key_uses_crm_prefix_and_ca_id` | Key format |
| `test_duplicate_idempotency_key_does_not_create_second_billing_charge` | No duplicate on retry |
| `test_legacy_charge_succeeds_even_when_billing_engine_bridge_fails` | Failure isolation |
| `test_billing_engine_failure_is_logged_to_billing_engine_channel` | Log channel verified |
| `test_tax_type_is_stored_on_billing_charge` | tax_type=add |
| `test_tax_type_defaults_to_free_when_not_provided` | tax_type null → 'free' |
| `test_billing_charge_does_not_interfere_with_legacy_crm_query` | No report interference |

### Full suite result
```
83 tests / 179 assertions — all passing
  BillingEngineTest:          25 tests  (Phase 2)
  FuelChargeBridgeTest:       18 tests  (Phase 3A)
  FuelAlertChargeBridgeTest:  20 tests  (Phase 3B)
  CrmFuelChargeBridgeTest:    20 tests  (Phase 3C)
```

---

## Guardrail Confirmations

| Area | Changed? |
|------|---------|
| Mobile checklist fuel charges (`SaveReturnController`) | No |
| Mobile damage charges | No |
| Damage path in `ChargeStoreController` | No |
| `AlertChargeController` (Phase 3B) | No |
| `FuelChargeStoreController` (Phase 3A) | No |
| `order_extra_charges` writes | No |
| `order_product_fuel_charge_logs` | No |
| Reports (Calls Log / Fuel Charge Alerts / Damage Alerts) | No |
| Tax reports / QuickBooks | No |
| Order Edit UI | No |
| Rental extension logic | No |
| Service tickets | No |
| Existing database migrations | No |
| Legacy `CustomerAccount` write in `ChargeStoreController` | No (unchanged) |

---

## Files Created

- `tests/Feature/BillingEngine/CrmFuelChargeBridgeTest.php`
- `docs/billing-engine-audit/PHASE_3C_CRM_FUEL_BRIDGE_SUMMARY.md`

## Files Modified

- `app/Http/Controllers/Admin/Crm/Customers/CustomerAccount/ChargeStoreController.php` — bridge added

---

## Next Phase

**Phase 3D: `ChargeService::createFromOrderProduct()` (mobile return checklist path, type=fuel)**

This is the mobile path through `SaveReturnController`. Unlike all Phase 3 admin bridges, this path:
- Has full order AND order_product context
- Requires an idempotency key derived from the OrderProduct ID and fuel reading (not a CA ID)
- Must not create duplicate charges on mobile offline retries
- Is the first path where `order_product_id` will be non-null in `billing_charges`
