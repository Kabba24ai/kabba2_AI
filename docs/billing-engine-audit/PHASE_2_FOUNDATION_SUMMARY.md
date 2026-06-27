# Billing Engine — Phase 2 Foundation Summary

Completed: 2026-06-27  
Branch: `feature/billing-engine-consolidation`  
Commit: TBD (see git log)

---

## Objective

Build the Billing Engine foundation without touching any existing production charge workflows. All work in this phase is additive. No existing controllers, services, models, routes, or UI were modified.

---

## Files Created

### Enums

| File | Purpose |
|------|---------|
| [app/Enums/Billing/BillingChargeType.php](../../app/Enums/Billing/BillingChargeType.php) | Charge type values: fuel, damage, extension, service_ticket, cleaning, delivery, misc |
| [app/Enums/Billing/BillingChargeStatus.php](../../app/Enums/Billing/BillingChargeStatus.php) | Status lifecycle: pending, paid, resolved, uncollectible, voided |
| [app/Enums/Billing/BillingSourceModule.php](../../app/Enums/Billing/BillingSourceModule.php) | Which module created the charge — includes mobile paths |
| [app/Enums/Billing/BillingSourceEvent.php](../../app/Enums/Billing/BillingSourceEvent.php) | Specific event that triggered creation |

### Data Object

| File | Purpose |
|------|---------|
| [app/Http/DataObjects/BillingChargeRequest.php](../../app/Http/DataObjects/BillingChargeRequest.php) | Immutable value object passed to `BillingEngine::charge()`. Includes all source tracking and idempotency fields. Provides static convenience constructors for mobile paths. |

### Model

| File | Purpose |
|------|---------|
| [app/Models/Orders/BillingCharge.php](../../app/Models/Orders/BillingCharge.php) | Eloquent model for `billing_charges`. Auto-generates `unique_id` with BLC prefix. Has `isMobileOriginated()` and `isPending()` helpers. Casts `billing_charge_type` and `status` to enums. |

### Service

| File | Purpose |
|------|---------|
| [app/Services/BillingEngine.php](../../app/Services/BillingEngine.php) | Core service. Provides `charge()`, `markPaid()`, `markResolved()`, `markUncollectible()`, `findByIdempotencyKey()`. Idempotency is enforced before creation — duplicate key returns existing charge without a DB write or event. |

### Event

| File | Purpose |
|------|---------|
| [app/Events/Admin/Billing/BillingChargeCreatedEvent.php](../../app/Events/Admin/Billing/BillingChargeCreatedEvent.php) | Fired after a new charge is created. NOT fired on idempotency hits. Contains the `BillingCharge` instance. |

### Migration

| File | Table |
|------|-------|
| [database/migrations/orders/2026_06_27_000001_create_billing_charges_table.php](../../database/migrations/orders/2026_06_27_000001_create_billing_charges_table.php) | `billing_charges` |

### Tests

| File | Coverage |
|------|---------|
| [tests/Feature/BillingEngine/BillingEngineTest.php](../../tests/Feature/BillingEngine/BillingEngineTest.php) | 25 tests, 81 assertions — all passing |

---

## Files Modified

| File | Change |
|------|--------|
| [config/logging.php](../../config/logging.php) | Added `billing_engine` daily log channel → `storage/logs/billing-engine.log` |

---

## `billing_charges` Table Schema

```
id                      — auto-increment PK
unique_id               — VARCHAR UNIQUE (prefix: BLC-)
billing_charge_type     — VARCHAR (BillingChargeType enum value)
status                  — VARCHAR DEFAULT 'pending' (BillingChargeStatus enum value)
parent_order_id         — BIGINT UNSIGNED INDEX (FK to orders — constraint added in integration phase)
child_order_id          — BIGINT UNSIGNED NULLABLE INDEX
customer_id             — BIGINT UNSIGNED INDEX (FK to customers — constraint added in integration phase)
order_product_id        — BIGINT UNSIGNED NULLABLE INDEX
amount                  — DECIMAL(12,2)
tax_amount              — DECIMAL(12,2) DEFAULT 0
tax_type                — VARCHAR DEFAULT 'free' (add | free | reverse)
responsible_person_id   — BIGINT UNSIGNED NULLABLE INDEX
responsible_person_name — VARCHAR NULLABLE
notes                   — TEXT NULLABLE
customer_account_id     — BIGINT UNSIGNED NULLABLE INDEX (bridge: set when legacy CA row created)
created_by_id           — BIGINT UNSIGNED NULLABLE INDEX
source_module           — VARCHAR NULLABLE INDEX (BillingSourceModule enum value)
source_event            — VARCHAR NULLABLE INDEX (BillingSourceEvent enum value)
source_reference_type   — VARCHAR NULLABLE (polymorphic: 'OrderProduct', 'ServiceTicket', etc.)
source_reference_id     — BIGINT UNSIGNED NULLABLE (ID of the source record)
metadata                — JSON NULLABLE (arbitrary context — readings, user IDs, checklist IDs)
idempotency_key         — VARCHAR(128) NULLABLE UNIQUE
created_at / updated_at / deleted_at
```

**Note on FK constraints:** `parent_order_id` and `customer_id` are stored as plain unsigned integers without hard `FOREIGN KEY` constraints in this migration. Constraints are added in the Phase 2 integration migration (after production wiring is in place). Tests pass integer IDs without needing real Order or Customer rows, making the foundation tests fast and self-contained.

---

## Source Module Values

| Value | When used |
|-------|----------|
| `admin_fuel_charge` | Admin created fuel charge from Order Edit, Dashboard, or CRM |
| `admin_damage_charge` | Admin created damage charge from Order Edit, Dashboard, or CRM |
| `mobile_checklist` | iOS driver app submitted return checklist with a charge |
| `mobile_offline_sync` | Same as mobile_checklist but submission was queued offline |
| `rental_extension` | Extension charge created from Order Edit extension modal |
| `service_module` | Future — service ticket module not yet built |
| `manual_entry` | Admin manually added a miscellaneous charge |
| `maintenance_module` | Future — maintenance-originated charges |
| `delivery_module` | Future — delivery fee charges |
| `return_checklist` | Return checklist completed (general, non-mobile context) |

---

## Source Event Values

| Value | Description |
|-------|-------------|
| `return_checklist_fuel_charge` | Mobile return checklist recorded a fuel difference |
| `return_checklist_damage_charge` | Mobile return checklist recorded damage (greenfield — not yet wired) |
| `admin_fuel_charge_created` | Admin created a fuel charge through admin UI |
| `admin_damage_charge_created` | Admin created a damage charge through admin UI |
| `rental_extension_created` | A rental extension was created |
| `service_ticket_charge_created` | A service ticket charge was approved/closed |
| `manual_additional_charge_created` | An additional charge was added manually |

---

## Idempotency Behaviour

`BillingEngine::charge()` checks `billing_charges.idempotency_key` before creating:

1. If `idempotencyKey` is `null` → always creates a new charge (no deduplication)
2. If `idempotencyKey` matches an existing row → returns existing charge; no DB write, no event fired
3. If `idempotencyKey` is new → creates charge, logs, fires event

**Recommended key format for mobile:**
```
mobile_checklist:{order_product_id}:{type}:{reading_or_hash}
```
Examples:
- `mobile_checklist:42:fuel:1/4` — fuel charge for OP 42 with final reading "1/4"
- `mobile_checklist:42:damage` — damage charge for OP 42

The `BillingChargeRequest::mobileReturnFuel()` convenience constructor builds this key automatically.

---

## Mobile Charge Paths (Not Yet Wired — Documented for Phase 3)

### Mobile Fuel Charge — Current Path (unchanged)

```
SaveReturnController (POST /api/.../customer-checklists/save-return)
→ ChargeService::createFromOrderProduct($orderProduct, 'fuel', user_id)
→ CustomerAccount row (type=charge, reason='Fuel Charge', fuel_alert_status=pending)
→ CustomHelper::updateCreditBalance()
```

Current duplicate prevention: signature media 409 check + ChargeService `whereIn(['pending','completed'])` guard.

### Mobile Fuel Charge — Future Path (Phase 3 integration)

```
SaveReturnController
→ BillingEngine::charge(BillingChargeRequest::mobileReturnFuel(...))
→ billing_charges row with idempotency_key and metadata
→ [bridge mode] CustomerAccount row (legacy, same as today)
→ BillingChargeCreatedEvent
```

The idempotency key replaces the two-layer guard with a single atomic DB check.

### Mobile Damage Charge — Future Path (Greenfield)

No current mobile damage charge workflow exists. When built:

```
SaveReturnController or future mobile damage endpoint
→ BillingEngine::charge(BillingChargeRequest::mobileReturnDamage(...))
→ billing_charges row
→ [bridge mode] CustomerAccount row
```

The `BillingChargeRequest::mobileReturnDamage()` convenience constructor is already implemented in this phase.

---

## Tests Run

```
php artisan test tests/Feature/BillingEngine/BillingEngineTest.php

Tests:  25 passed (81 assertions)
Duration: ~14s
```

Test coverage includes:
- Charge row creation and unique_id generation
- Event firing on creation
- Source tracking field storage
- Mobile metadata (fuel readings, checklist IDs) stored and retrieved
- `isMobileOriginated()` detection
- Convenience constructor field mapping
- Idempotency: second call returns existing charge
- Idempotency: event NOT fired on second call
- No-key charges always create new rows
- `findByIdempotencyKey()` lookup
- Status transitions: markPaid, markResolved, markUncollectible
- All enum labels non-empty
- `isMobileEligible()` on BillingChargeType
- `createsChildOrder()` — only Extension returns true
- `isMobileOriginated()` on BillingSourceModule
- `expectedModule()` mapping on BillingSourceEvent

---

## Confirmation — No Existing Production Code Changed

The following were NOT modified in this phase:

- `SaveReturnController` — unchanged
- `ChargeService::createFromOrderProduct()` — unchanged
- `FuelChargeStoreController` — unchanged
- `DamageChargeStoreController` — unchanged
- `AlertChargeController` — unchanged
- `ChargeStoreController` (CRM) — unchanged
- `PaymentStoreController` — unchanged
- `Extension/StoreController` — unchanged
- All reports — unchanged
- All blade views — unchanged
- All existing routes — unchanged
- All existing migrations — unchanged

---

## Next Phase

**Phase 3 — Fuel Charge Integration**  
Wire admin and mobile fuel charge creation through `BillingEngine::charge()` in bridge mode (BillingEngine writes `billing_charges`, then also writes the legacy `CustomerAccount` row so existing reports are unaffected). Begin with `FuelChargeStoreController` (Dashboard modal) as the lowest-risk entry point.

See [BILLING_ENGINE_REFACTOR_PLAN.md](BILLING_ENGINE_REFACTOR_PLAN.md) Phase 2 section for the full integration plan.
