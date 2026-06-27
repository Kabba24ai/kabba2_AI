# Billing Engine Refactor Plan

Audit date: 2026-06-27  
Branch: feature/billing-engine-consolidation

---

## Goal

Replace fragmented, per-charge-type billing logic with a single reusable `BillingEngine` service that owns:
- Child order creation
- Charge type classification
- Parent order relationship
- Order number suffix assignment
- Taxable status
- Payment status
- Receipt behavior
- Reporting compatibility

Existing workflows remain operational throughout the transition. Each charge type is converted one at a time and verified before the next begins.

---

## Architecture Direction

```
Any billable event
→ BillingEngine::charge(ChargeRequest $request)
→ creates BillingCharge record
→ optional: creates child Order (for extension-style charges)
→ optional: creates CustomerAccount ledger entry
→ fires BillingChargeCreatedEvent
→ Receipt / reporting pick it up from one place
```

All modules (Fuel checklist, Damage checklist, Rental extension, Service ticket) call `BillingEngine::charge()`. No module owns financial transaction creation directly.

---

## Phase 1 — Build the Billing Engine (no existing code changes)

### 1.1 New table: `billing_charges`

```
id
unique_id              — unique identifier (prefix: BLC-)
billing_charge_type    — ENUM: fuel | damage | extension | service_ticket | cleaning | delivery | misc
status                 — ENUM: pending | paid | resolved | uncollectible | voided
parent_order_id        — FK → orders.id (the original rental order)
child_order_id         — FK → orders.id nullable (for extension-type charges that spawn an Order)
customer_id            — FK → customers.id
order_product_id       — FK → order_products.id nullable
amount                 — DECIMAL(12,2)
tax_amount             — DECIMAL(12,2) default 0
tax_type               — ENUM: add | free | reverse
responsible_person_id  — FK → users.id nullable
responsible_person_name — VARCHAR
notes                  — TEXT nullable
customer_account_id    — FK → customer_accounts.id nullable (bridge to legacy ledger while migrating)
created_by_id          — FK → users.id
created_at / updated_at / deleted_at
```

### 1.2 New service: `App\Services\BillingEngine`

Public API:
```php
BillingEngine::charge(BillingChargeRequest $request): BillingCharge
BillingEngine::recordPayment(BillingCharge $charge, PaymentRequest $request): void
BillingEngine::markResolved(BillingCharge $charge, string $note, int $userId): void
BillingEngine::markUncollectible(BillingCharge $charge, int $userId): void
```

`BillingChargeRequest` is a plain PHP object (not a Laravel FormRequest):
```php
class BillingChargeRequest {
    public string $type;        // fuel | damage | extension | etc.
    public int    $orderId;
    public int    $customerId;
    public float  $amount;
    public string $taxType;     // add | free | reverse
    public ?int   $orderProductId;
    public ?int   $responsiblePersonId;
    public ?string $notes;
    public bool   $createChildOrder = false;  // true for extension
    public ?array  $childOrderData;
}
```

### 1.3 New model: `App\Models\Orders\BillingCharge`

### 1.4 New event: `BillingChargeCreatedEvent`

### 1.5 Verify

- Write unit tests for `BillingEngine::charge()` in isolation
- Confirm migration runs clean
- Confirm no existing route or controller references it yet

---

## Phase 2 — Convert Fuel Charges

### 2.1 Route all fuel charge creation through BillingEngine

Update these four controllers to call `BillingEngine::charge()` instead of writing `CustomerAccount` directly:
- `AlertChargeController` (type=fuel)
- `FuelChargeStoreController`
- `ChargeStoreController` (reason=Fuel Charge)
- `ChargeService::createFromOrderProduct()` (type=fuel)

BillingEngine internally still writes the `CustomerAccount` row (bridge mode) so the existing `updateCreditBalance()` flow and all existing reports continue to work unchanged.

### 2.2 Route fuel payment through BillingEngine

Update `PaymentStoreController` (Dashboard) to call `BillingEngine::recordPayment()`. BillingEngine writes both the `OrderExtraCharges` row and the `CustomerAccount` payment row (same as today, but from one place).

### 2.3 Route resolve/uncollectible through BillingEngine

`MarkResolvedController` and `MarkUncollectibleController` → `BillingEngine::markResolved()` / `markUncollectible()`

### 2.4 Validate

- Fuel Charge Alerts report unchanged
- Sales Tax report unchanged
- CustomerAccount balance unchanged
- `order_extra_charges` populated same as before
- All four charge entry points produce identical `billing_charges` rows

---

## Phase 3 — Convert Damage Charges

Identical to Phase 2 with `type = 'damage'`. Damage and Fuel share the same `ChargeService` so this requires minimal new work. After Phase 2 is complete, Phase 3 is primarily a type-flag change.

### Validate

- New Damage Alerts report unchanged
- Checklist-originated damage charges appear in `billing_charges`
- `damage_alert_status` lifecycle (pending → paid / resolved / uncollectible) unchanged

---

## Phase 4 — Convert Rental Extension Charges

### 4.1 Move suffix generation into BillingEngine

Extract this logic from `Extension/StoreController` and fix the known bugs:
```php
// Current (buggy):
$existingCount = Order::where('reference_order_number', $order->order_number)->count();
$suffix = chr(65 + $existingCount);

// Fixed:
$existingCount = Order::withTrashed()
    ->where('reference_order_number', $order->order_number)
    ->where('order_number', 'LIKE', $order->order_number . '-%')
    ->count();
// Filter to extension-only children (exclude reorders)
```

Fix the >26 limit issue by implementing double-letter suffixes (AA, AB, ...) after Z.

### 4.2 Extension creates BillingCharge with child Order

`BillingEngine::charge()` with `$request->createChildOrder = true`:
- Creates `billing_charges` row
- Creates child `Order` row (same as today)
- Sets `billing_charges.child_order_id`

### 4.3 Validate

- Extension orders still appear in Orders list
- `$relatedOrders` on Order Edit page still populated
- History entry on parent order unchanged
- Suffix gaps are repaired for new extensions

---

## Phase 5 — Add Service Ticket Charges

### 5.1 Build Service Ticket module (separate project scope)

When service tickets are built, they fire `BillingEngine::charge(type: 'service_ticket')` when a ticket is approved/closed. No special controller or table needed — the Billing Engine handles it.

### 5.2 Validate

- Service ticket charges appear in reporting via `billing_charges`
- Same payment/resolve/uncollectible flow as all other types

---

## Phase 6 — Unified Additional Charges UI

Replace the three separate billing UI sections on the Order Edit page with one unified section:

**Before:**
1. "Order Extra Payments" (from `order_extra_charges`)
2. "Additional Charges" (from `customer_accounts`)
3. "Extension Charges" (from `orders` with suffix)

**After:**
1. One "Billing & Charges" section reading from `billing_charges` — grouped by type, showing status, amount, and payment actions inline

The underlying data (CustomerAccount, OrderExtraCharges, child Orders) continues to exist — only the UI layer changes to read from `billing_charges` as the single source.

### Sub-types to display in one unified section

| Type | Icon | Creates child Order? |
|------|------|----------------------|
| Fuel Charge | Fuel pump | No |
| Damage | Warning | No |
| Extension | Calendar+ | Yes (shows link to child order) |
| Service Ticket | Wrench | No |
| Cleaning | Broom | No |
| Delivery Fee | Truck | No |

---

## Phase 7 — Remove Obsolete Code

Only after all charge types are converted and reporting is validated:
- Remove `FuelChargeStoreController`, `DamageChargeStoreController` (replaced by BillingEngine entry points)
- Remove duplicate charge-creation code from `AlertChargeController` (keep the route, delegate to BillingEngine)
- Remove `ChargeService` (absorbed into BillingEngine)
- Remove the three separate Order Edit UI sections
- Remove bridge code that writes legacy `CustomerAccount` / `OrderExtraCharges` rows if reporting has been migrated to read from `billing_charges`

---

## Conversion Order Summary

| Phase | Scope | Risk |
|-------|-------|------|
| 1 | Build BillingEngine (no existing changes) | Low |
| 2 | Fuel Charges | Medium |
| 3 | Damage Charges | Medium |
| 4 | Rental Extension Charges | Medium (order numbering fix) |
| 5 | Service Ticket Charges | Low (new module) |
| 6 | Unified UI | Low (display only) |
| 7 | Remove obsolete code | Low (all tests passing) |

Between each phase: run the full test suite, verify all reports, and confirm customer account balances are consistent.
