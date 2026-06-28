# Phase 5D — Final Data Integrity Patch

**Branch:** `feature/billing-engine-consolidation`
**Date:** 2026-06-27
**Status:** Complete

---

## Purpose

Phase 5C identified two columns in `billing_charges` that existed but were never populated:

- `customer_account_id` — the legacy CustomerAccount row created in parallel by the bridge controller
- `tax_amount` — hardcoded to 0 by `BillingEngine::charge()` regardless of tax type

Both columns already existed. No new migrations were required.

---

## Files Modified

| File | Change |
|------|--------|
| `app/Http/DataObjects/BillingChargeRequest.php` | Added `?int $customerAccountId = null` and `?float $taxAmount = null` |
| `app/Services/BillingEngine.php` | Writes `customer_account_id` and `tax_amount` from request (tax defaults to 0 when null) |
| `app/Http/Controllers/Admin/Dashboard/FuelChargeStoreController.php` | Passes `customerAccountId: $record->id` |
| `app/Http/Controllers/Admin/Dashboard/DamageChargeStoreController.php` | Passes `customerAccountId: $record->id` |
| `app/Http/Controllers/Admin/OrderManagement/Orders/AlertChargeController.php` | Passes `customerAccountId: $record->id` in both fuel and damage arms |
| `app/Http/Controllers/Admin/Crm/Customers/CustomerAccount/ChargeStoreController.php` | Passes `customerAccountId: $record->id` in both fuel and damage arms |
| `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveReturnController.php` | Passes `customerAccountId: $legacyCa->id` |
| `app/Http/Controllers/Admin/OrderManagement/Orders/Extension/StoreController.php` | Passes `customerAccountId: null`, `taxAmount: $taxAmount > 0 ? $taxAmount : null` |

---

## No Files Created

No new controllers, services, models, migrations, or routes were created. This phase was additive parameter passing only.

---

## Gap 1: `customer_account_id`

### Root Cause

`BillingChargeRequest` had no `customerAccountId` parameter. `BillingEngine::charge()` did not write `customer_account_id`. All bridge controllers created the CA row and had the ID available in `$record->id` or `$legacyCa->id`, but never passed it.

### Fix

Added `?int $customerAccountId = null` to `BillingChargeRequest`. Updated `BillingEngine::charge()` to include `'customer_account_id' => $request->customerAccountId` in the `BillingCharge::create()` call. Updated all 7 CA-creating bridge paths.

### Which paths now populate `customer_account_id`

| Phase | Controller | `customer_account_id` |
|-------|-----------|----------------------|
| 3A | `FuelChargeStoreController` | ✅ `$record->id` |
| 3B | `AlertChargeController` (fuel) | ✅ `$record->id` |
| 3C | `ChargeStoreController` (fuel) | ✅ `$record->id` |
| 3D | `SaveReturnController` | ✅ `$legacyCa->id` |
| 4B | `DamageChargeStoreController` | ✅ `$record->id` |
| 4C | `AlertChargeController` (damage) | ✅ `$record->id` |
| 4D | `ChargeStoreController` (damage) | ✅ `$record->id` |
| 5B | `Extension\StoreController` | **null** — extensions do not create a CustomerAccount row |

### Behavioral impact

- `BillingCharge::first()->legacyCustomerAccount` now returns the linked `CustomerAccount` model for all 7 CA-creating paths.
- For extensions, `customer_account_id` remains null (correct by design — no CA row is created).

---

## Gap 2: `tax_amount`

### Root Cause

`BillingEngine::charge()` hardcoded `'tax_amount' => 0` regardless of whether the caller knew the actual tax. `BillingChargeRequest` had no `taxAmount` parameter.

### Fix

Added `?float $taxAmount = null` to `BillingChargeRequest`. Updated `BillingEngine::charge()` to write `'tax_amount' => $request->taxAmount ?? 0`. This preserves the default-to-zero behavior for any caller that omits the field.

### Which paths populate `tax_amount`

| Phase | Controller | `tax_amount` | Reason |
|-------|-----------|-------------|--------|
| 3A | `FuelChargeStoreController` | **0** | Dashboard fuel modal has no tax calculation |
| 3B | `AlertChargeController` (fuel) | **0** | Order Edit fuel alert has no tax calculation |
| 3C | `ChargeStoreController` (fuel) | **0** | CRM fuel charge has no tax calculation |
| 3D | `SaveReturnController` | **0** | Mobile checklist fuel is always tax-free |
| 4B | `DamageChargeStoreController` | **0** | Dashboard damage modal has no tax calculation |
| 4C | `AlertChargeController` (damage) | **0** | Order Edit damage alert has no tax calculation |
| 4D | `ChargeStoreController` (damage) | **0** | CRM damage charge has no tax calculation |
| **5B** | **`Extension\StoreController`** | **✅ `$taxAmount`** | Extension calculates tax from `sales_tax` setting × `base_amount` |

### Tax amount logic for extensions

```php
taxAmount: $taxAmount > 0 ? $taxAmount : null
```

Where `$taxAmount` is the value calculated inside the extension transaction:

```php
$taxAmount = $validated['add_tax'] ? round($baseAmount * $salesTaxRate, 2) : 0.00;
```

- If `add_tax = true` and `sales_tax` rate > 0: `taxAmount` is the calculated dollar amount (e.g., `8.00`)
- If `add_tax = true` and `sales_tax` rate = 0: `taxAmount` is null → stored as 0
- If `add_tax = false`: `taxAmount` is null → stored as 0

This matches the extension order's `tax_amount` field and allows accurate tax reporting from `billing_charges` for extension charges.

### Why fuel/damage tax_amount stays at 0

The admin fuel and damage charge modals do not perform tax calculations at the time of charge creation. The `sales_tax_type` (add/free/reverse) is recorded in both the CA record and `billing_charges.tax_type`, but no dollar amount is computed. This is an existing behavior in the legacy system — it was not introduced by the BillingEngine bridge. Future improvement would require adding tax calculation to those modals before a meaningful `tax_amount` can be passed.

---

## Idempotency Behavior Confirmed Unchanged

Adding `customerAccountId` and `taxAmount` parameters with nullable defaults does not affect idempotency behavior. When an idempotency key match is found, the existing charge is returned before any `create()` call — the new parameters are never evaluated in the duplicate path.

---

## `BillingChargeRequest` after Phase 5D

The complete parameter signature now has `customerAccountId` and `taxAmount` after the existing `childOrderId`:

```php
public readonly ?int  $childOrderId = null,
public readonly ?int  $customerAccountId = null,
public readonly ?float $taxAmount = null,
```

All three new-in-5x parameters are nullable with defaults. All existing callers that don't pass these parameters continue to work identically.

---

## Tests

### New tests in `BillingEngineTest.php`

| Test | Covers |
|------|--------|
| `test_charge_stores_customer_account_id_when_provided` | BillingEngine writes `customer_account_id` to DB |
| `test_charge_stores_tax_amount_when_provided` | BillingEngine writes `tax_amount` to DB |
| `test_charge_defaults_tax_amount_to_zero_when_not_provided` | Null taxAmount → 0 in DB |

### New `customer_account_id` test added to each bridge test file

| File | New test |
|------|---------|
| `FuelChargeBridgeTest.php` | `test_billing_charge_stores_customer_account_id` |
| `FuelAlertChargeBridgeTest.php` | `test_billing_charge_stores_customer_account_id` |
| `CrmFuelChargeBridgeTest.php` | `test_billing_charge_stores_customer_account_id` |
| `MobileReturnFuelBridgeTest.php` | `test_billing_charge_stores_customer_account_id` |
| `DashboardDamageChargeBridgeTest.php` | `test_billing_charge_stores_customer_account_id` |
| `DamageAlertChargeBridgeTest.php` | `test_billing_charge_stores_customer_account_id` |
| `CrmDamageChargeBridgeTest.php` | `test_billing_charge_stores_customer_account_id` |

### New `customer_account_id` and `tax_amount` tests in `RentalExtensionBridgeTest.php`

| Test | Covers |
|------|--------|
| `test_billing_charge_customer_account_id_is_null_for_extensions` | Extensions produce null CA ID |
| `test_billing_charge_stores_tax_amount_when_add_tax_true` | Tax amount stored when sales tax rate > 0 |
| `test_billing_charge_tax_amount_is_zero_when_no_tax` | Zero tax when add_tax = false |

### Full suite result

```
197 tests / 394 assertions — all passing
  BillingEngineTest:                    28 tests  (+3 from Phase 5D)
  FuelChargeBridgeTest:                 19 tests  (+1 from Phase 5D)
  FuelAlertChargeBridgeTest:            21 tests  (+1 from Phase 5D)
  CrmFuelChargeBridgeTest:              21 tests  (+1 from Phase 5D)
  MobileReturnFuelBridgeTest:           22 tests  (+1 from Phase 5D)
  DashboardDamageChargeBridgeTest:      21 tests  (+1 from Phase 5D)
  DamageAlertChargeBridgeTest:          21 tests  (+1 from Phase 5D)
  CrmDamageChargeBridgeTest:            21 tests  (+1 from Phase 5D)
  RentalExtensionBridgeTest:            24 tests  (+3 from Phase 5D)
```

---

## Guardrail Confirmations

| Area | Changed? |
|------|---------|
| Legacy CustomerAccount writes | No — preserved exactly |
| Customer credit balance updates | No |
| Reports | No |
| Payments | No |
| UI | No |
| Mobile damage | No |
| Service Ticket billing | No |
| Tax calculations | No — only reading/passing the already-calculated value |
| Existing bridge logic | No — only new named parameters added at the call site |
| Idempotency behavior | No |
