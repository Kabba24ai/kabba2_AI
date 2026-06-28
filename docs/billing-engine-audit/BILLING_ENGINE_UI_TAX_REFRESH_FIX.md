# Billing Engine UI — Tax Amount & Page Refresh Fix

**Commit:** `44e0df52`
**Date:** 2026-06-28
**Branch:** `raj_development`

---

## Summary

Two bugs were found and fixed after the initial Billing Engine UI (`_billing_engine.blade.php`) was shipped:

1. `billing_charges.tax_amount` was always stored as `0.00` regardless of the selected sales tax treatment.
2. The Order Details page did not update the Billing Engine block after a charge was successfully added — a hard browser refresh was required.

---

## Issue 1 — Tax Amount Always Zero

### Root Cause

`AlertChargeController` (`admin.order-management.orders.alert-charge`) and `FuelChargeStoreController` (`admin.dashboard.fuel-charge.store`) both call `BillingEngine::charge()` via `BillingChargeRequest`. The `BillingChargeRequest::$taxAmount` parameter defaults to `null`, which `BillingEngine::charge()` stores as `0`.

The actual tax calculation lives inside `CustomHelper::updateCreditBalance()`. That method reads the `sales_tax` setting, computes the correct rate, and sets `$record->sales_tax` on the `CustomerAccount` model before saving. However, the tax rate calculated there was **never read back** and forwarded to the `BillingEngine::charge()` bridge call. The bridge always received `taxAmount: null`.

The `BillingChargeRequest` data object comment even acknowledged this explicitly:

```php
/**
 * Actual tax dollar amount.
 * Defaults to null (stored as 0) for all charge paths that do not calculate tax.
 * Currently only populated by the rental extension bridge (Phase 5B / 5D).
 */
public readonly ?float $taxAmount = null,
```

### Root Cause — Reverse Sales Tax Specifically

For Reverse Sales Tax, the entered amount is the **final total** (base + tax). The legacy path stores the entered amount in `customer_accounts.amount` (unchanged), and `updateCreditBalance()` applies the full entered amount to the customer balance. But the BillingCharge should store the **split** (base in `amount`, tax in `tax_amount`) so that `amount + tax_amount = entered total`. Because `taxAmount` was always `null`, the BillingCharge stored the full entered total in `amount` and `0` in `tax_amount`, making the total appear correct but the breakdown wrong.

---

## Fix — Tax Calculation

After `CustomHelper::updateCreditBalance($record)` returns, `$record->sales_tax` contains the actual rate used (e.g. `0.0975` for 9.75%). The fix reads this value and computes the billing amounts before calling `BillingEngine::charge()`.

### Add Sales Tax

```
billingBaseAmount = entered_amount
billingTaxAmount  = round(entered_amount × rate, 2)
stored:  amount = base,  tax_amount = tax
display: Total = base + tax
```

**Example:** $10.00 entered, 9.75% rate
```
base = $10.00
tax  = $0.98  (10.00 × 0.0975, rounded)
total = $10.98
```

### Tax Free

```
billingBaseAmount = entered_amount
billingTaxAmount  = 0.00
stored:  amount = entered,  tax_amount = 0.00
display: Total = entered
```

**Example:** $10.00 entered
```
base  = $10.00
tax   = $0.00
total = $10.00
```

### Reverse Sales Tax

```
entered_amount = final total (base + tax)
billingBaseAmount = round(entered_amount / (1 + rate), 2)
billingTaxAmount  = round(entered_amount − base, 2)
stored:  amount = base,  tax_amount = tax
display: Total = base + tax = entered_amount
```

**Example:** $10.00 entered, 9.75% rate
```
base  = round(10.00 / 1.0975, 2) = $9.11
tax   = round(10.00 − 9.11, 2)  = $0.89
total = $9.11 + $0.89            = $10.00  ✓
```

### Code Location

```php
// After CustomHelper::updateCreditBalance($record)
$enteredAmount = (float) $record->amount;
$taxRate       = (float) $record->sales_tax;  // set by updateCreditBalance()

if ($record->sales_tax_type === 'add') {
    $billingBaseAmount = $enteredAmount;
    $billingTaxAmount  = round($enteredAmount * $taxRate, 2);
} elseif ($record->sales_tax_type === 'reverse') {
    $divisor           = $taxRate > 0 ? (1 + $taxRate) : 1;
    $billingBaseAmount = round($enteredAmount / $divisor, 2);
    $billingTaxAmount  = round($enteredAmount - $billingBaseAmount, 2);
} else {
    $billingBaseAmount = $enteredAmount;
    $billingTaxAmount  = 0.0;
}
```

---

## Issue 2 — Page Does Not Refresh After Adding Charge

### Root Cause

The `submitAlertCharge()` JavaScript function in `edit.blade.php` handled the success response by showing a toast, closing the modal, and resetting the form. It did not trigger any refresh of the page or the Billing Engine block. Because the Billing Engine block is server-rendered by Blade (not a reactive component), it cannot update without a full page reload.

### Fix

Added a 1.5-second delayed `window.location.reload()` after the success toast. The delay allows the Notyf toast to remain visible before the page navigates away.

```js
if (data.success) {
    notyf.success(data.message);
    closeModal(type === 'fuel' ? 'orderFuelChargeModal' : 'orderDamageAlertModal');
    // Reload so the Billing Engine block reflects the new charge immediately.
    setTimeout(() => window.location.reload(), 1500);
}
```

**Behavior after fix:**
1. User submits charge in modal.
2. Success toast appears.
3. Modal closes.
4. After 1.5 seconds, page reloads.
5. Billing Engine block shows the new charge with correct amounts.

---

## Files Modified

| File | Change |
|------|--------|
| `app/Http/Controllers/Admin/OrderManagement/Orders/AlertChargeController.php` | Compute `billingBaseAmount` / `billingTaxAmount` from settled CustomerAccount; pass to both fuel and damage bridge calls |
| `app/Http/Controllers/Admin/Dashboard/FuelChargeStoreController.php` | Same tax calculation; pass to fuel bridge call |
| `resources/views/admin/order_management/orders/edit.blade.php` | Add `setTimeout(() => window.location.reload(), 1500)` after success in `submitAlertCharge()` |
| `tests/Feature/BillingEngine/FuelAlertChargeBridgeTest.php` | Add 3 tax amount tests (add / free / reverse) |
| `tests/Feature/BillingEngine/FuelChargeBridgeTest.php` | Add 3 tax amount tests (add / free / reverse) |

---

## Tests Added

### FuelAlertChargeBridgeTest (Order Details path — has order context)

| Test | Asserts |
|------|---------|
| `test_billing_charge_stores_correct_tax_amount_for_add_type` | `amount=100.00`, `tax_amount=9.75` with 9.75% rate |
| `test_billing_charge_stores_zero_tax_for_free_type` | `amount=100.00`, `tax_amount=0.00` |
| `test_billing_charge_splits_base_and_tax_for_reverse_type` | `amount=91.12`, `tax_amount=8.88`, `amount+tax_amount=100.00` |

### FuelChargeBridgeTest (Dashboard path — no order context)

| Test | Asserts |
|------|---------|
| `test_billing_charge_stores_correct_tax_amount_for_add_type` | Same as above |
| `test_billing_charge_stores_zero_tax_for_free_type` | Same as above |
| `test_billing_charge_splits_base_and_tax_for_reverse_type` | Same as above |

---

## Test Results

```
Tests\Feature\BillingEngine\FuelAlertChargeBridgeTest   24 tests  — PASS
Tests\Feature\BillingEngine\FuelChargeBridgeTest        23 tests  — PASS
Tests\Feature\BillingEngine\BillingEngineTest           27 tests  — PASS
Tests\Feature\BillingEngine\CrmDamageChargeBridgeTest   21 tests  — PASS
Tests\Feature\BillingEngine\CrmFuelChargeBridgeTest     21 tests  — PASS
Tests\Feature\BillingEngine\DamageAlertChargeBridgeTest 21 tests  — PASS
Tests\Feature\BillingEngine\DashboardDamageChargeBridgeTest 20 tests — PASS
Tests\Feature\BillingEngine\MobileReturnFuelBridgeTest  22 tests  — PASS
Tests\Feature\BillingEngine\RentalExtensionBridgeTest   22/23 tests — 1 pre-existing flaky failure (see KNOWN_FLAKY_TESTS.md)

Tests\Feature\FunnelLifecycle\*  31 tests — PASS

Total: 232 passed, 1 pre-existing flaky failure
```

---

## Guardrails — Confirmed Unchanged

| Area | Status |
|------|--------|
| Payment processing (`order_payments`, `OrderPayment` model) | Not touched |
| Customer balances (`CustomerAccount`, `updateCreditBalance()`) | Not touched — reads result only |
| Reports (Fuel Charge Alerts, Damage Alerts, Sales Trend) | Not touched |
| QuickBooks exports | Not touched |
| Mobile fuel logic (`SaveReturnController`, `ChargeService`) | Not touched |
| Rental extension logic (`Extension\StoreController`) | Not touched |
| Legacy tables (`customer_accounts`, `order_extra_charges`) | Not touched |
| Legacy writes (all CustomerAccount creates/updates) | Not touched — bridge is additive only |
| Tax calculation business rules | Not changed — reads the same `$record->sales_tax` that `updateCreditBalance()` already computed |
