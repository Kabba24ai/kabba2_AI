# Current Billing Architecture

Audit date: 2026-06-27  
Branch: feature/billing-engine-consolidation  
Rollback point: commit `68c8c74b`

---

## Overview

Post-order billing in this system is fragmented across three independent charge types, each implemented differently. There is no shared service layer. No service ticket, cleaning fee, delivery fee, or miscellaneous charge modules exist yet.

---

## 1. Fuel Charges

### Entry points (three separate controllers, same end-state)

| Entry | Controller | Route |
|-------|-----------|-------|
| Order Edit page → "Fuel Charge Alert" button | `Admin\OrderManagement\Orders\AlertChargeController` | `POST /orders/{unique_id}/alert-charge` (type=fuel) |
| Dashboard modal | `Admin\Dashboard\FuelChargeStoreController` | `POST /fuel-charge/store` |
| CRM Customer Account tab | `Admin\Crm\Customers\CustomerAccount\ChargeStoreController` | (CRM customer route) |
| Driver checklist completion (API) | `ChargeService::createFromOrderProduct()` | Called from checklist save controllers |

### Write path

All paths write a `CustomerAccount` row with:
- `type = 'charge'`
- `reason = 'Fuel Charge'`
- `fuel_alert_status = 'pending'`
- `sales_tax_type` (add / free / reverse)
- `sales_tax = 0` (always zero; tax type is a flag for future calculation)

`CustomHelper::updateCreditBalance()` is called after every write to recompute the customer's running balance.

### Payment collection

Dashboard payment modal → `Admin\Dashboard\PaymentStoreController`

This controller writes **two records**:
1. An `OrderExtraCharges` row (`type = 'fuel'`) — stores the gateway/payment-method data
2. A `CustomerAccount` row (`type = 'payment'`, `reason = 'Payment — Fuel Charge'`) — affects the balance

`ChargeService::recordPayment()` is the shared backend for option 2 (also called from the CRM path).

### Resolution / write-off

Dashboard `MarkResolvedController` → `ChargeService::markResolved()`:
- Updates `OrderProduct.fuel_charge_status = Resolved`
- Creates a `CustomerAccount` reversal row (`type = 'discount'`) to zero out the balance
- Marks the original CA charge as `fuel_alert_status = 'resolved'`

`MarkUncollectibleController` → `ChargeService::markUncollectible()`:
- Updates `OrderProduct.fuel_charge_status = Uncollectible`
- Marks CA charge `fuel_alert_status = 'uncollectible'`

### Tables

| Table | Role |
|-------|------|
| `customer_accounts` | Primary ledger; charge, payment, reversal entries |
| `order_extra_charges` | Secondary payment record; gateway/payment-method data |
| `order_products` | Has `fuel_total_charge`, `fuel_charge_status` fields |
| `order_product_fuel_charge_logs` | Audit log: before/after amounts for each checklist-driven change |

### Models

- `App\Models\Customers\CustomerAccount`
- `App\Models\Orders\OrderExtraCharges`
- `App\Models\Orders\OrderProduct`
- `App\Models\Orders\OrderProductFuelChargeLog`
- `App\Models\Dashboard\FuelNotePreset`

### Tax logic

Tax type is a string flag (`add`, `free`, `reverse`) stored on the `CustomerAccount`. Actual tax calculation (`amount × sales_tax_rate`) is only computed at display time in blade views. No tax amount is ever written to the row for fuel or damage charges (`sales_tax = 0` always).

### Reporting

- Fuel Charge Alerts report: `Admin\Reports\FuelChargeAlerts\IndexController` — reads `customer_accounts` where `reason = 'Fuel Charge'`
- Sales Tax report: `Admin\Reports\SalesTax\IndexController` — reads `OrderExtraCharges` (not `customer_accounts`) for tax line items
- Pure Sales Summary / SalesReportEngineV2: includes `customer_accounts.type = 'payment'` as Stream C account payments

### UI sections

- `resources/views/admin/order_management/orders/edit.blade.php` — "Fuel Charge Alert" button (line ~309), fuel modal (line ~331), `_additional_charges.blade.php` partial (line 1845)
- `resources/views/admin/order_management/orders/partials/_additional_charges.blade.php` — renders all `CustomerAccount` charge rows for the order

---

## 2. Rental Extension Charges

### Entry point (single controller)

| Entry | Controller | Route |
|-------|-----------|-------|
| Order Edit page → "Extension Charge" button | `Admin\OrderManagement\Orders\Extension\StoreController` | `POST /orders/{unique_id}/extension/store` |

### Write path

Creates a **new child Order** record:
- `order_number = '{parent_number}-{suffix}'` (e.g., `#047-A`)
- `reference_order_number = parent.order_number`
- Full order record: subtotal, tax_amount, grand_total, customer info copied from parent
- Billing address copied from parent via `order_addresses`
- A COD Pending payment row added to `order_payments`
- A history entry written to the **original** order

### Order numbering

Suffix logic lives exclusively in `Extension/StoreController`:
```php
$existingCount = Order::where('reference_order_number', $order->order_number)->count();
$suffix = chr(65 + $existingCount); // A=0, B=1, …, Z=25
```

**Known issues with this logic:**
- Hard limit of 26 extensions per parent (returns 422 at 26+)
- `count()` includes soft-deleted child orders — a deleted `#047-A` still consumes the A slot; next extension becomes `#047-B`, creating a gap
- Reorders also write `reference_order_number`, which means a reorder on the same parent would inflate `existingCount` and skip a suffix letter

### Tax logic

Tax is calculated inline in the controller:
```php
$salesTaxRate = ConfigurationHelper::getSettings(null, 'sales_tax');
$taxAmount = $validated['add_tax'] ? round($baseAmount * $salesTaxRate, 2) : 0.00;
```

### Payment

Extension orders appear in the Orders list and can receive payments through the standard order payment flow (`ReceivePaymentController`). Extension orders have `is_tax_exempt = 'Yes'` if no tax was added.

### Tables

| Table | Role |
|-------|------|
| `orders` | Extension order row; `reference_order_number` links to parent |
| `order_payments` | COD pending placeholder; replaced when paid |
| `order_addresses` | Billing address copied from parent |
| `order_notes` | Extension description stored here |
| `order_history` | History entry on the parent order |

### Models

- `App\Models\Orders\Order` (`parentOrder()`, `childOrders()` relationships via `reference_order_number`)
- `App\Models\Orders\Order` has `boot()` that generates `order_number` automatically if empty; the extension controller pre-fills `order_number` to prevent auto-generation

### UI sections

- `edit.blade.php` line 1847 — "Extension Charges" section; `$relatedOrders` variable filtered by `order_number LIKE '{parent}-%'`
- Extension orders appear in the main Orders list as separate entries

---

## 3. Damage Charges

### Entry points (mirror of Fuel — three separate controllers)

| Entry | Controller | Route |
|-------|-----------|-------|
| Order Edit page → "Damage Alert" button | `Admin\OrderManagement\Orders\AlertChargeController` | `POST /orders/{unique_id}/alert-charge` (type=damage) |
| Dashboard modal | `Admin\Dashboard\DamageChargeStoreController` | `POST /damage-charge/store` |
| CRM Customer Account tab | `Admin\Crm\Customers\CustomerAccount\ChargeStoreController` | (CRM customer route) |
| Checklist / rental-ready completion | `ChargeService::createFromOrderProduct()` | Called from checklist controllers |

### Write path

Identical pattern to Fuel:
- `CustomerAccount` row: `reason = 'Damages'`, `damage_alert_status = 'pending'`
- `CustomHelper::updateCreditBalance()` called after save

### Payment, resolution, uncollectible

Same flow as Fuel — `ChargeService::recordPayment()`, `markResolved()`, `markUncollectible()` — with `'damage'` type passed instead of `'fuel'`.

### Tables

| Table | Role |
|-------|------|
| `customer_accounts` | Primary ledger |
| `order_extra_charges` | Secondary payment record |
| `order_products` | Has `damage_charge`, `damage_status` fields |
| `order_product_damage_charge_logs` | Audit log |

### Models

- `App\Models\Orders\OrderProductDamageChargeLog` (identical structure to fuel log)

### Reporting

- New Damage Alerts report: `Admin\Reports\NewDamageAlerts\IndexController`
- Damage charges appear in Pure Sales Summary via `customer_accounts` Stream C

---

## 4. Service Ticket Charges — NOT IMPLEMENTED

No files exist for service ticket charges. This charge type is planned but not yet built.

---

## 5. Cleaning Fees / Delivery Fees / Miscellaneous

Not implemented as separate post-order billing modules. Delivery/pickup fees may exist as product pricing fields but are not post-order charges in the current architecture.

---

## 6. Order Extra Payments (UI element)

The Order Edit page renders `$payments` via `<x-admin.order-management.orders.order-extra-payments-list>` (line 1731). This variable is populated as:
```php
$payments = $order->extraCharges->sortByDesc('type');
```
`extraCharges` is the `order_extra_charges` relationship. This section shows **payment receipts** against fuel/damage charges, not the charges themselves. The "Additional Charges" partial below it shows the charges (from `customer_accounts`).

**The Order Edit page therefore has three distinct billing UI areas:**
1. **Order Extra Payments** (line 1731) — payment records from `order_extra_charges`
2. **Additional Charges** (line 1845) — charge records from `customer_accounts`
3. **Extension Charges** (line 1847) — child `orders` with `reference_order_number`

These three areas represent three different data sources and three different billing concepts, presented as separate sections with no unifying structure.

---

## 7. Tax Handling Summary

| Charge type | Tax storage | Tax calculation |
|-------------|------------|-----------------|
| Fuel / Damage (CustomerAccount path) | `sales_tax_type` flag; `sales_tax = 0` always | Computed at display time only |
| Extension (child Order) | `tax_amount` column on `orders` | Computed in controller at creation |
| Order rental (original order) | `tax_amount` on `orders`; line item rates from `order_products` | Computed in CartHelper at checkout |

---

## 8. Parent/Child Order Relationships

```
orders.order_number = '#047'
    └── orders.reference_order_number = '#047', order_number = '#047-A'  ← extension
    └── orders.reference_order_number = '#047', order_number = '#047-B'  ← extension
    └── orders.reference_order_number = '#047', order_number = '#148'    ← reorder (new number)
```

`Order::parentOrder()` = `belongsTo(Order::class, 'reference_order_number', 'order_number')`  
`Order::childOrders()` = `hasMany(Order::class, 'reference_order_number', 'order_number')`

---

## 9. Services

| Service | Purpose |
|---------|---------|
| `App\Services\ChargeService` | Shared create/pay/resolve/uncollectible logic for fuel and damage (checklist-originated path) |
| `App\Services\AuthorizeNetService` | Card gateway for payment collection |
| `App\Services\ReceiptService` | Generates PDF receipts for orders |
| `App\Services\Reports\SalesReportEngineV2` | Revenue calculations including customer_account payments |
| `App\Services\Reports\SalesTaxReportEngine` | Tax report using OrderExtraCharges |
| `App\Helpers\CustomHelper::updateCreditBalance()` | Recomputes customer running balance after every CustomerAccount write |

---

## 10. Events

| Event | Fired by | Listeners |
|-------|---------|-----------|
| `OrderExtraChargeEvent` | `PaymentStoreController` (Dashboard) | Logging/notification listeners |
| `PaymentInitiateEvent` | Payment controllers on order payments | `StopCodFunnelsOnPaymentListener` |

---

## Key Duplication and Problems

1. **Dual write system for Fuel and Damage**: Every charge potentially creates rows in BOTH `customer_accounts` AND `order_extra_charges`. The two tables represent different things (ledger entry vs. payment receipt) but there is no formal relationship between them.

2. **Four independent controllers do the same thing**: `AlertChargeController`, `FuelChargeStoreController`, `DamageChargeStoreController`, and `ChargeStoreController` all create `CustomerAccount` charge rows with nearly identical code. Adding a new charge field requires changes in all four places.

3. **Order numbering is unsafe**: Soft-deleted extensions still consume suffix slots. Reorders inflate the count. There is no protection against suffix collisions.

4. **Tax never gets stored for fuel/damage**: `sales_tax = 0` always. The `sales_tax_type` flag is captured but never materialized. The Sales Tax report uses `OrderExtraCharges` (the payment side) rather than the charge side, which may miss charges that haven't been paid yet.

5. **Three confusingly named UI sections on the Order Edit page** with different data sources and different meanings, with no explanatory grouping.
