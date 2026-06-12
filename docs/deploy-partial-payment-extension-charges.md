# Deployment Notes — Partial Payment & Extension Charges
**Branch:** `gary_dev`  
**Date:** 2026-06-11  
**Features:** Partial Payment system + Extension Charges on Order Edit screen

---

## What Was Built

### 1. Partial Payment System
- Process Payment modal now has **Full Payment / Partial Payment** toggle
- Partial payments are tracked and recorded in Order History
- Balance Due banner shows remaining amount every time the modal opens
- Order stays in "Pending Payment" status until the full balance is collected
- An orange badge shows partial amount paid next to the Pending Payment pill

### 2. Extension Charges
- New **Extension Charges** section at the bottom of the Order Edit page
- Creates a real linked order with a suffixed order number (e.g. `#047-A`, `#047-B`)
- Sales tax is a configurable separate line item (Add Tax / No Tax toggle)
- Each extension starts as Pending — a **Process Payment Now** button opens the payment modal directly from the original order's page
- Extension orders appear in the main Orders index table with correct billing address and "Extension Charge" label

---

## Pre-Deployment Checklist

- [ ] Merge or deploy `gary_dev` branch
- [ ] SSH into the server
- [ ] Navigate to the project root

---

## Deployment Steps

### Step 1 — Pull the latest code

```bash
git pull origin gary_dev
```

---

### Step 2 — Install/update PHP dependencies

```bash
composer install --no-dev --optimize-autoloader
```

> Safe to run even if no new packages were added.

---

### Step 3 — Run the database migration

**This is the only required schema change.**

```bash
php artisan migrate
```

**What it does:**  
Adds `'Partial Payment'` to the `order_payments.status` MySQL ENUM column.

Migration file:
```
database/migrations/orders/2026_06_11_000001_add_partial_payment_status_to_order_payments_table.php
```

> ⚠️ **Do not skip this step.** Without it, any attempt to save a partial payment will throw a database error.

---

### Step 4 — Clear all caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan optimize:clear
```

Or as a single line:

```bash
php artisan config:clear && php artisan cache:clear && php artisan view:clear && php artisan route:clear && php artisan optimize:clear
```

---

### Step 5 — Verify (quick sanity check)

```bash
php artisan route:list --name=extension
```

Expected output:
```
POST  admin.kabba.local/order-management/orders/{unique_id}/extension/store
      admin.order-management.orders.extension.store
```

If that route appears, the feature is wired up correctly.

---

## No Build Step Required

No `npm run build` is needed. All new JavaScript is inline in the Blade template — no asset compilation required.

---

## Files Changed (for reference)

| File | Change |
|------|--------|
| `app/Enums/Orders/OrderPaymentStatus.php` | Added `PartialPayment = 'Partial Payment'` |
| `app/Enums/Orders/OrderHistoryAction.php` | Added `PartialPaymentReceived`, `ExtensionChargeCreated` |
| `app/Models/Orders/Order.php` | Added `getTotalPaidAttribute()`, `getBalanceDueAttribute()`, `relatedOrders()` relationship; boot() now guards pre-set `order_number` |
| `app/Http/Requests/Admin/OrderManagement/Orders/ReceivePaymentRequest.php` | Added `partial_payment` and `payment_amount` fields |
| `app/Http/Controllers/Admin/OrderManagement/Orders/ReceivePaymentController.php` | Partial/full payment logic; full payment charges remaining balance, not original total |
| `app/Http/Controllers/Admin/OrderManagement/Orders/EditController.php` | Passes `$relatedOrders` to view |
| `app/Http/Controllers/Admin/OrderManagement/Orders/Extension/StoreController.php` | **New** — creates extension order, copies billing address, creates Pending payment, writes history |
| `app/Http/Requests/Admin/OrderManagement/Orders/Extension/StoreRequest.php` | **New** — validates extension charge form |
| `app/Listeners/Activities/Admin/Orders/PaymentInitiateListener.php` | Writes formatted history entry for partial payments |
| `app/Http/Resources/Api/Admin/V1/Orders/ListResource.php` | Null-safe guard on `shippingAddress->isSameAs()` (was crashing for extension orders) |
| `routes/admin/order_management/orders/routes.php` | Added `POST /{unique_id}/extension/store` route |
| `resources/views/admin/order_management/orders/edit.blade.php` | Full/Partial modal UI + Balance Due banner + Extension Charges section + modal + JS |
| `resources/views/admin/order_management/orders/partials/_table.blade.php` | Null-safe billing address; "Extension Charge" label for extension orders |
| `database/migrations/orders/2026_06_11_000001_add_partial_payment_status_to_order_payments_table.php` | **New migration** — adds `Partial Payment` to `order_payments.status` ENUM |

---

## One-Time Backfill (if test extension orders exist without a billing address)

If any extension orders were created during testing **before** the billing address copy was added, they will show `—` for Billing Address and Phone in the Orders index. 

Run this in Tinker to backfill:

```bash
php artisan tinker
```

```php
$extensions = \App\Models\Orders\Order::whereNotNull('reference_order_number')
    ->doesntHave('addresses')
    ->with('referenceOrder.billingAddress')
    ->get();

foreach ($extensions as $ext) {
    $billing = $ext->referenceOrder?->billingAddress;
    if (!$billing) continue;

    $ext->addresses()->create([
        'type'       => 'Billing',
        'first_name' => $billing->first_name,
        'last_name'  => $billing->last_name,
        'email'      => $billing->email,
        'phone'      => $billing->phone,
        'address'    => $billing->address,
        'city'       => $billing->city,
        'state'      => $billing->state,
        'state_id'   => $billing->state_id,
        'zip_code'   => $billing->zip_code,
    ]);

    echo "Backfilled billing address for {$ext->order_number}\n";
}
```

This is safe to run — it only touches extension orders that have no addresses at all.

---

## Key Commits

| Commit | Description |
|--------|-------------|
| `7301d08a` | Partial payment complete (all 5 partial payment commits) |
| `4624110f` | Extension Charges feature |
| `b7b8ed75` | Fix isSameAs() null crash + process payment modal for extensions |
| `b43cd8c8` | Guard null billingAddress in orders table partial |
| `29809d0d` | Extension order: copy billing address + fix Product column |

---

*Generated 2026-06-11*
