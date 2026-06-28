# Phase 5B — Rental Extension Bridge Integration

**Branch:** `feature/billing-engine-consolidation`
**Date:** 2026-06-27
**Status:** Complete

---

## Controller Modified

**File:** `app/Http/Controllers/Admin/OrderManagement/Orders/Extension/StoreController.php`
**Route:** `POST /order-management/orders/{unique_id}/extension/store`
**Route name:** `admin.order-management.orders.extension.store`
**Response:** `response()->json(['success' => true, ...])` (JSON API — not a redirect)

---

## No Service Extracted

The suffix fix and bridge were applied directly to `StoreController`. No `OrderExtensionService` was created because:
- The controller is simple (single `__invoke`) and already well-contained.
- The suffix fix is three lines; extracting it to a service would add indirection with no benefit.
- The bridge follows the same after-`DB::commit()` pattern as all prior phases.

If the extension workflow grows significantly (EditController, CancelController, etc.), an `OrderExtensionService` becomes worth introducing at that point.

---

## Suffix Bug Fix

### Two bugs fixed simultaneously

**Bug 1 — Reorder inflation (non-fatal)**
The original query counted reorders that share `reference_order_number` with the parent order. Reorders are created by `Front/Checkout/PostController` and set `reference_order_number = $parent->order_number` but receive a NEW sequential `order_number` (not a suffixed one). Counting them inflated the suffix letter (first extension received `B` instead of `A`).

**Bug 2 — Soft-delete collision (fatal — 500 error)**
When an extension order is soft-deleted, it is excluded from a plain `Order::where(...)→count()`. That exclusion caused the suffix count to be `0`, which assigns suffix `A`. But the soft-deleted row still holds the `UNIQUE` constraint slot on `orders.order_number`. Attempting to insert `{order_number}-A` again caused a SQL duplicate-key error → HTTP 500.

### Before (buggy)
```php
$existingCount = Order::where('reference_order_number', $order->order_number)->count();
```

### After (fixed)
```php
$existingCount = Order::withTrashed()
    ->where('reference_order_number', $order->order_number)
    ->where('order_number', 'like', $order->order_number . '-%')
    ->count();
```

**Why this is correct:**
- `withTrashed()` includes soft-deleted extensions, so their UNIQUE slots are counted and avoided.
- `where('order_number', 'like', $order->order_number . '-%')` limits the count to true extension children (e.g., `#1668-A`, `#1668-B`). Reorders have plain sequential numbers (e.g., `#1670`) and are excluded.

---

## BillingChargeRequest Enhancement

Added `?int $childOrderId = null` to `BillingChargeRequest` (after the existing `$createChildOrder` parameter):

```php
public readonly ?int $childOrderId = null,
```

Updated `BillingEngine::charge()` to pass `child_order_id` to `BillingCharge::create()`:

```php
'child_order_id' => $request->childOrderId,
```

The `child_order_id` column already existed in the `billing_charges` table (Phase 2 migration, line 26) and in `BillingCharge::$fillable`. No new migrations were required.

---

## What Was Changed

### `Extension/StoreController.php` — suffix fix + bridge added

**Added use statements (6):**
```php
use App\Enums\Billing\BillingChargeType;
use App\Enums\Billing\BillingSourceEvent;
use App\Enums\Billing\BillingSourceModule;
use App\Http\DataObjects\BillingChargeRequest;
use App\Services\BillingEngine;
use Illuminate\Support\Facades\Log;
```

**Suffix query patched** (line 40–43):
```php
$existingCount = Order::withTrashed()
    ->where('reference_order_number', $order->order_number)
    ->where('order_number', 'like', $order->order_number . '-%')
    ->count();
```

**Bridge block added** (after `DB::commit()`, before `return response()->json()`):
```php
// ── Billing Engine bridge (Phase 5B) ───────────────────────────
try {
    BillingEngine::charge(new BillingChargeRequest(
        type:                BillingChargeType::Extension->value,
        orderId:             $order->id,
        customerId:          (int) $order->customer_id,
        amount:              $grandTotal,
        taxType:             $validated['add_tax'] ? 'add' : 'free',
        responsiblePersonId: $user->id,
        notes:               $validated['notes'] ?? null,
        sourceModule:        BillingSourceModule::RentalExtension->value,
        sourceEvent:         BillingSourceEvent::RentalExtensionCreated->value,
        sourceReferenceType: 'Order',
        sourceReferenceId:   $extension->id,
        metadata: [
            'legacy_controller'   => 'Extension\\StoreController',
            'parent_order_id'     => $order->id,
            'parent_order_number' => $order->order_number,
            'child_order_id'      => $extension->id,
            'child_order_number'  => $extension->order_number,
            'base_amount'         => $baseAmount,
            'tax_amount'          => $taxAmount,
            'add_tax'             => $validated['add_tax'],
            'description'         => $validated['description'],
            'extension_context'   => true,
        ],
        idempotencyKey: "rental_extension:{$extension->id}",
        childOrderId:   $extension->id,
    ));
} catch (\Throwable $e) {
    Log::channel('billing_engine')->error(
        "BillingEngine bridge failed | controller=Extension\\StoreController " .
        "| extension_order_id={$extension->id} | parent_order_id={$order->id} " .
        "| error=" . $e->getMessage()
    );
}
```

---

## Child Order Behavior Confirmed

Extensions are fundamentally different from fuel/damage charges: they already create a child `Order` row with full accounting, payment records, billing address, and order history. This workflow is **unchanged**:

| Table written (legacy) | Changed? |
|------------------------|---------|
| `orders` (child extension row) | No — created exactly as before |
| `order_payments` (pending COD placeholder) | No |
| `order_notes` (optional, if notes provided) | No |
| `order_addresses` (billing address copy from parent) | No |
| `order_histories` (entry on PARENT order) | No |

The `billing_charges` row is **additive**. The existing child order is not replaced or modified by BillingEngine.

**Why the child order cannot be removed:** It is required for customer-visible receipts, schedule display, Orders index, Dispatch, payment collection, and accounting. BillingEngine records the financial event; the child order is the operational entity.

---

## BillingCharge Mapping

| `billing_charges` column | Value | Source |
|--------------------------|-------|--------|
| `billing_charge_type` | `extension` | `BillingChargeType::Extension->value` |
| `status` | `pending` | BillingEngine default |
| `parent_order_id` | `$order->id` | Original parent rental order |
| `child_order_id` | `$extension->id` | Newly created extension child order |
| `customer_id` | `$order->customer_id` | Inherited from parent order |
| `order_product_id` | **null** | No line-item context in extension flow |
| `amount` | `$grandTotal` | base_amount + tax_amount (full billable total) |
| `tax_type` | `'add'` or `'free'` | Based on `add_tax` request field |
| `tax_amount` | `0` | BillingEngine default (base + tax already baked into `amount`) |
| `responsible_person_id` | `$user->id` | From `User::findOrFail($validated['responsible_person'])` |
| `notes` | `$validated['notes']` | Nullable |
| `source_module` | `rental_extension` | `BillingSourceModule::RentalExtension->value` |
| `source_event` | `rental_extension_created` | `BillingSourceEvent::RentalExtensionCreated->value` |
| `source_reference_type` | `Order` | The extension child order |
| `source_reference_id` | `$extension->id` | Child order ID |
| `idempotency_key` | `rental_extension:{extension_id}` | See below |
| `metadata.legacy_controller` | `Extension\\StoreController` | |
| `metadata.parent_order_id` | `$order->id` | |
| `metadata.parent_order_number` | `$order->order_number` | |
| `metadata.child_order_id` | `$extension->id` | |
| `metadata.child_order_number` | `$extension->order_number` | |
| `metadata.base_amount` | `$baseAmount` | Pre-tax amount |
| `metadata.tax_amount` | `$taxAmount` | Calculated tax (may be 0.00) |
| `metadata.add_tax` | `true`/`false` | Raw boolean from request |
| `metadata.description` | `$validated['description']` | Extension description |
| `metadata.extension_context` | `true` | Marks as extension-originated |

### Why `amount = $grandTotal`

The child order's `grand_total` = `base_amount + tax_amount`. This is what the customer actually owes. Storing the grand total in `billing_charges.amount` makes the billing record match the receivable on the child order. The tax breakdown (base/tax) is preserved in metadata.

---

## Idempotency Key

Format: `rental_extension:{child_order_id}`

Example: `rental_extension:214`

**Rationale:** The child order is created inside the committed transaction before the bridge fires. Its `id` is stable, unique, and already subject to DB constraints. The `rental_extension:` prefix is distinct from all existing keys:

| Phase | Key format |
|-------|-----------|
| 3A | `admin_fuel_charge:{ca_id}` |
| 3B | `admin_fuel_alert_charge:{ca_id}` |
| 3C | `crm_fuel_charge:{ca_id}` |
| 3D | `mobile_checklist:{op_id}:fuel:{reading}` |
| 4B | `admin_dashboard_damage_charge:{ca_id}` |
| 4C | `admin_damage_alert_charge:{ca_id}` |
| 4D | `crm_damage_charge:{ca_id}` |
| **5B** | **`rental_extension:{extension_order_id}`** |

---

## Failure Handling

```
┌────────────────────────────────────────────────────────────────────┐
│ $validated = $request->validated()                                  │
│ $order = Order::where('unique_id', $uniqueId)->firstOrFail()        │
│                                                                     │
│ DB::beginTransaction()                                              │
│ try {                                                               │
│   $existingCount = Order::withTrashed()...->count()  ← fixed       │
│   $extension = Order::create(...)   ← child order                  │
│   $extension->notes()->create(...)  ← optional                     │
│   $extension->addresses()->create(...)                              │
│   $extension->payments()->create(...)                               │
│   $order->history()->create(...)                                    │
│   DB::commit()       ◄── legacy complete                            │
│                                                                     │
│   try { BillingEngine::charge(...) }   ← Phase 5B (new)            │
│   catch { Log::error(...) }  ← logged, swallowed                   │
│                                                                     │
│   return response()->json(['success' => true, ...])                 │
│                                                                     │
│ } catch (Throwable $e) {                                            │
│   DB::rollBack()       ◄── legacy failure path (unchanged)         │
│   return response()->json(['success' => false, ...], 500)           │
│ }                                                                   │
└────────────────────────────────────────────────────────────────────┘
```

If BillingEngine bridge throws after `DB::commit()`:
- Child order is **already committed** — unaffected
- Payment, address, notes, history are **already committed** — unaffected
- Error is logged to `billing_engine` channel with `extension_order_id` and `parent_order_id`
- Admin receives the normal success JSON response — no indication of bridge failure
- No duplicate child orders

---

## Complete Bridge Map — Phases 3–5

| Phase | Controller | Type | `parent_order_id` | `child_order_id` | `order_product_id` |
|-------|-----------|------|-------------------|-----------------|--------------------|
| 3A | `FuelChargeStoreController` | fuel | null | null | null |
| 3B | `AlertChargeController` (fuel) | fuel | populated | null | null |
| 3C | `ChargeStoreController` (fuel) | fuel | null | null | null |
| 3D | `SaveReturnController` | fuel | populated | null | **populated** |
| 4B | `DamageChargeStoreController` | damage | null | null | null |
| 4C | `AlertChargeController` (damage) | damage | populated | null | null |
| 4D | `ChargeStoreController` (damage) | damage | null | null | null |
| **5B** | **`Extension\StoreController`** | **extension** | **populated** | **populated** | **null** |

Eight entry points bridged. **Phase 5B is the first and only bridge with `child_order_id` populated.**

---

## Tests

**New file:** `tests/Feature/BillingEngine/RentalExtensionBridgeTest.php`
**21 tests / 45 assertions — all passing**

| Test | Covers |
|------|--------|
| `test_suffix_uses_withTrashed_to_avoid_collision_with_soft_deleted_extension` | Fatal collision bug fix |
| `test_suffix_excludes_reorders_from_count` | Reorder inflation bug fix |
| `test_extension_creates_child_order` | Legacy child order still created |
| `test_extension_child_order_has_correct_suffix` | Suffix A for first extension |
| `test_extension_response_is_json_with_success_true` | Controller response format |
| `test_extension_creates_billing_charge` | Bridge creates BillingCharge |
| `test_billing_charge_has_correct_type_and_status` | type=extension, status=pending |
| `test_billing_charge_parent_order_id_is_original_order` | parent_order_id populated |
| `test_billing_charge_child_order_id_is_extension_order` | child_order_id populated |
| `test_billing_charge_has_correct_customer_and_amount` | customer_id + amount |
| `test_billing_charge_amount_includes_tax_when_add_tax_true` | amount = grand_total |
| `test_billing_charge_tax_type_is_add_when_add_tax_true` | tax_type=add |
| `test_billing_charge_tax_type_is_free_when_add_tax_false` | tax_type=free |
| `test_billing_charge_stores_source_module_and_event` | source tracking |
| `test_billing_charge_source_reference_is_extension_order` | source_reference = extension Order |
| `test_billing_charge_metadata_stores_parent_and_child_order_numbers` | full metadata verified |
| `test_billing_charge_has_blc_prefixed_unique_id` | BLC- prefix |
| `test_idempotency_key_format_is_rental_extension_child_id` | key format |
| `test_duplicate_key_does_not_create_second_billing_charge` | idempotency guard |
| `test_billing_engine_failure_does_not_break_extension_creation` | failure isolation |
| `test_billing_engine_failure_is_logged_to_billing_engine_channel` | log channel |

### Full suite result
```
184 tests / 378 assertions — all passing
  BillingEngineTest:                    25 tests  (Phase 2)
  FuelChargeBridgeTest:                 18 tests  (Phase 3A)
  FuelAlertChargeBridgeTest:            20 tests  (Phase 3B)
  CrmFuelChargeBridgeTest:              20 tests  (Phase 3C)
  MobileReturnFuelBridgeTest:           21 tests  (Phase 3D)
  DashboardDamageChargeBridgeTest:      20 tests  (Phase 4B)
  DamageAlertChargeBridgeTest:          20 tests  (Phase 4C)
  CrmDamageChargeBridgeTest:            20 tests  (Phase 4D)
  RentalExtensionBridgeTest:            21 tests  (Phase 5B)
```

---

## Guardrail Confirmations

| Area | Changed? |
|------|---------|
| `customer_accounts` table | No — extensions never wrote to it; unchanged |
| `order_extra_charges` table | No — extensions never wrote to it; unchanged |
| `order_product_damage_charge_logs` | No |
| Fuel paths (Phases 3A–3D) | No — all passing, no regression |
| Damage paths (Phases 4B–4D) | No — all passing, no regression |
| Mobile checklist damage | No |
| Service tickets | No |
| Reports (Calls Log, Fuel Charge Alerts, Damage Alerts) | No |
| Sales tax reports | No |
| QuickBooks exports | No |
| Order Edit UI | No |
| Dispatch | No |
| Schedules | No |
| Extension child order creation logic | No change to tables written or payment behavior |
| Extension billing address copy | No |
| Extension order history | No |
| Extension max-26 guard | No |
| Existing database migrations | No — `child_order_id` already existed |

---

## Files Created

- `tests/Feature/BillingEngine/RentalExtensionBridgeTest.php`
- `docs/billing-engine-audit/PHASE_5B_RENTAL_EXTENSION_BRIDGE_SUMMARY.md`

## Files Modified

- `app/Http/DataObjects/BillingChargeRequest.php` — added `?int $childOrderId = null`
- `app/Services/BillingEngine.php` — added `'child_order_id' => $request->childOrderId` to `BillingCharge::create()`
- `app/Http/Controllers/Admin/OrderManagement/Orders/Extension/StoreController.php` — suffix fix + bridge added

---

## What Remains Unbridged

| Path | Reason | Future phase |
|------|--------|-------------|
| Mobile return checklist damage | Greenfield — not yet wired in `SaveReturnController` | Phase 4E or later |
| Service ticket charges | Greenfield — no `ServiceTicket` model exists | Phase 6 |

Phase 5 (rental extensions) is fully complete with Phase 5B.
