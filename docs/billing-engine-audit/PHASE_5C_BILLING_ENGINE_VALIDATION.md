# Phase 5C — Billing Engine Validation and Reporting Architecture Review

**Branch:** `feature/billing-engine-consolidation`
**Date:** 2026-06-27
**Status:** Complete (documentation and analysis only — no production code changes)

---

## Files Reviewed

| File | Purpose |
|------|---------|
| `app/Services/BillingEngine.php` | Core charge service |
| `app/Http/DataObjects/BillingChargeRequest.php` | Data object passed to BillingEngine |
| `app/Models/Orders/BillingCharge.php` | ORM model — fillable, casts, relationships |
| `database/migrations/orders/2026_06_27_000001_create_billing_charges_table.php` | Schema foundation |
| `database/migrations/orders/2026_06_27_000002_make_billing_charges_parent_order_nullable.php` | parent_order_id nullable fix |
| `app/Enums/Billing/BillingChargeType.php` | Charge type enum |
| `app/Enums/Billing/BillingChargeStatus.php` | Status enum |
| `app/Enums/Billing/BillingSourceModule.php` | Source module enum |
| `app/Enums/Billing/BillingSourceEvent.php` | Source event enum |
| `app/Services/ChargeService.php` | Legacy charge creation service |
| `app/Models/Customers/CustomerAccount.php` | Legacy ledger model |
| `app/Models/Orders/OrderExtraCharges.php` | Legacy order-level payment model |
| `app/Http/Controllers/Admin/Dashboard/FuelChargeStoreController.php` | Phase 3A |
| `app/Http/Controllers/Admin/OrderManagement/Orders/AlertChargeController.php` | Phase 3B / 4C |
| `app/Http/Controllers/Admin/Crm/Customers/CustomerAccount/ChargeStoreController.php` | Phase 3C / 4D |
| `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveReturnController.php` | Phase 3D |
| `app/Http/Controllers/Admin/Dashboard/DamageChargeStoreController.php` | Phase 4B |
| `app/Http/Controllers/Admin/OrderManagement/Orders/Extension/StoreController.php` | Phase 5B |
| `app/Http/Controllers/Admin/Dashboard/PaymentStoreController.php` | Payment collection (unrelated) |
| `app/Http/Controllers/Admin/Dashboard/MarkResolvedController.php` | Resolution (unrelated) |
| `app/Http/Controllers/Admin/Reports/FuelChargeAlerts/IndexController.php` | Reads customer_accounts |
| `app/Http/Controllers/Admin/Reports/NewDamageAlerts/IndexController.php` | Reads customer_accounts |
| `app/Http/Controllers/Admin/Reports/SalesTax/IndexController.php` | Reads orders + order_extra_charges |

---

## 1. Completeness Review

### Question 1: Does every existing billable fuel, damage, and rental extension workflow now create a `billing_charges` record?

**Yes — with one exception (mobile damage, which is greenfield and has never had a charge path).**

| Phase | Controller | Type | Bridge Status |
|-------|-----------|------|---------------|
| 3A | `FuelChargeStoreController` | fuel | ✅ Bridged |
| 3B | `AlertChargeController` (fuel arm) | fuel | ✅ Bridged |
| 3C | `ChargeStoreController` (fuel reason) | fuel | ✅ Bridged |
| 3D | `SaveReturnController` (mobile fuel) | fuel | ✅ Bridged |
| 4B | `DamageChargeStoreController` | damage | ✅ Bridged |
| 4C | `AlertChargeController` (damage arm) | damage | ✅ Bridged |
| 4D | `ChargeStoreController` (damage reason) | damage | ✅ Bridged |
| 5B | `Extension\StoreController` | extension | ✅ Bridged |

---

### Question 2: Are there any remaining current financial charge paths that bypass the Billing Engine?

**One greenfield path and several non-charge paths that are correctly untracked.**

#### Unbridged charge-creation paths (by design or greenfield)

| Path | Status | Notes |
|------|--------|-------|
| Mobile return checklist damage | **Greenfield** | `SaveReturnController` has no mobile damage charge code at all. No `ChargeService::createFromOrderProduct($op, 'damage', ...)` call exists. Nothing to bridge yet. |
| Service ticket charges | **Greenfield** | No `ServiceTicket` model, no controller, no DB table. Phase 6. |

#### Non-charge paths correctly NOT tracked by BillingEngine

These write to `customer_accounts` or `order_extra_charges` but are **payment collection or status mutation** operations, not charge creation. They should NOT be bridged:

| Controller | What it does | Why not bridged |
|-----------|-------------|----------------|
| `Dashboard/PaymentStoreController` | Collects payment against a fuel/damage charge; writes `OrderExtraCharges` (payment record) + `CustomerAccount` (type='payment') | Payment collection, not charge creation |
| `Dashboard/MarkResolvedController` | Marks charge resolved; writes `CustomerAccount` (type='discount', amount=0) reversal | Status mutation |
| `Dashboard/MarkUncollectibleController` | Marks charge uncollectible | Status mutation |
| `ChargeService::recordPayment()` | Records payment for mobile-originated charges | Payment recording |
| `ChargeService::markResolved()` | Resolution + reversal entry | Status mutation |
| `ChargeService::markUncollectible()` | Uncollectible status | Status mutation |

**BillingEngine does expose `markPaid()`, `markResolved()`, and `markUncollectible()` methods.** Wiring these status mutations to BillingEngine (so `billing_charges.status` stays synchronized) is a Phase 7/8 concern and is not in scope for bridge-mode phases.

#### `CRM/DiscountStoreController`

Writes `type='discount'` rows to `customer_accounts`. Discounts are balance credits, not charges. These are intentionally out of scope — discounts require separate analysis before a BillingEngine discount path is designed.

---

### Question 3: Can every bridged legacy charge be traced to exactly one `billing_charges` row?

**Yes — by idempotency key.** Every bridged path generates a deterministic idempotency key anchored to the legacy record's primary key and a unique prefix. There is one-to-one correspondence between each legacy CA record (or extension order) and exactly one `billing_charges` row.

The one exception is the mobile fuel bridge (Phase 3D), whose key is `mobile_return_fuel:{op_id}:{final_reading}`. Multiple checklist sessions on the same OrderProduct with the same final reading would share a key. In practice this cannot happen because the `ChargeService` duplicate guard prevents a second CA record from being created for the same OP + type.

---

## 2. Idempotency Review

### Question 4: Are idempotency keys consistent and collision-safe?

**Yes. All eight key formats are distinct and collision-safe.**

| Phase | Key format | Anchor | Prefix unique? |
|-------|-----------|--------|---------------|
| 3A | `admin_fuel_charge:{ca_id}` | CustomerAccount ID | ✅ |
| 3B | `admin_fuel_alert_charge:{ca_id}` | CustomerAccount ID | ✅ |
| 3C | `crm_fuel_charge:{ca_id}` | CustomerAccount ID | ✅ |
| 3D | `mobile_return_fuel:{op_id}:{final_reading}` | OrderProduct ID + reading value | ✅ |
| 4B | `admin_dashboard_damage_charge:{ca_id}` | CustomerAccount ID | ✅ |
| 4C | `admin_damage_alert_charge:{ca_id}` | CustomerAccount ID | ✅ |
| 4D | `crm_damage_charge:{ca_id}` | CustomerAccount ID | ✅ |
| 5B | `rental_extension:{extension_order_id}` | Extension Order ID | ✅ |

**No cross-phase key collision is possible.** Each prefix is unique to its source path.

**`idempotency_key` column** has a `UNIQUE` database constraint and a max length of 128 characters. All current keys are well within that limit.

**Future key naming guidance** for new phases:
- Service ticket lines: `service_ticket_labor:{st_line_id}`, `service_ticket_parts:{st_line_id}`
- Mobile damage (when built): `mobile_return_damage:{op_id}`

---

## 3. Relationship Review

### Question 5: Are parent/child relationships sufficient?

**Mostly yes. One important gap exists.**

#### `parent_order_id` — null by design for certain paths

| Bridge | `parent_order_id` | Rationale |
|--------|------------------|-----------|
| 3A — Dashboard fuel | **null** | Fuel modal has no order context |
| 3B — Order Edit fuel | populated | Charged from within an order's detail page |
| 3C — CRM fuel | **null** | CRM charge modal takes only customer_id |
| 3D — Mobile return fuel | populated | Return checklist is tied to a specific order |
| 4B — Dashboard damage | **null** | Damage modal has no order context |
| 4C — Order Edit damage | populated | |
| 4D — CRM damage | **null** | CRM charge modal takes only customer_id |
| 5B — Extension | populated | Extension always tied to parent order |

This is correct. Dashboard and CRM charges represent customer-level financial events with no direct order reference. `parent_order_id = null` is the right representation.

#### `child_order_id` — only populated for extensions (correct)

Phase 5B is the only bridge that populates `child_order_id`. All fuel and damage bridges correctly leave it null — they do not create child orders.

#### `order_product_id` — only populated for mobile checklist (correct)

Mobile return fuel (Phase 3D) is the only path with line-item context. Admin charge modals operate at the customer/order level, not the product level.

#### Missing relationships on related models

Neither the `Customer` model nor the `Order` model has a `billingCharges()` relationship defined. This means:

```php
$customer->billingCharges  // does not work — no hasMany defined
$order->billingCharges     // does not work — no hasMany defined
```

These are simple additions to the models (no migration, no schema change) and should be added before any reporting is built from `billing_charges`.

**Recommended additions (no schema change):**

In `Customer`:
```php
public function billingCharges(): HasMany
{
    return $this->hasMany(BillingCharge::class, 'customer_id');
}
```

In `Order`:
```php
public function billingCharges(): HasMany
{
    return $this->hasMany(BillingCharge::class, 'parent_order_id');
}

public function extensionBillingCharge(): HasOne
{
    return $this->hasOne(BillingCharge::class, 'child_order_id');
}
```

---

## 4. Metadata Review

### Question 6: Is metadata sufficient for future reporting?

**Mostly yes, with one structural gap.**

#### What metadata currently contains (by phase)

| Phase | Key metadata fields |
|-------|-------------------|
| 3A (dashboard fuel) | `legacy_controller`, `legacy_customer_account_id`, `customer_id`, `sales_tax_type`, `dashboard_context: true` |
| 3B (order edit fuel) | `legacy_controller`, `legacy_customer_account_id`, `order_id`, `order_unique_id`, `customer_id`, `sales_tax_type`, `alert_context: true` |
| 3C (CRM fuel) | `legacy_controller`, `legacy_customer_account_id`, `customer_id`, `sales_tax_type` |
| 3D (mobile fuel) | `fuel_initial_reading`, `fuel_final_reading`, `submitted_by_user_id`, `legacy_service`, `legacy_customer_account_id`, `order_id`, `customer_id` |
| 4B (dashboard damage) | `legacy_controller`, `legacy_customer_account_id`, `customer_id`, `sales_tax_type`, `dashboard_context: true` |
| 4C (order edit damage) | `legacy_controller`, `legacy_customer_account_id`, `order_id`, `order_unique_id`, `customer_id`, `sales_tax_type`, `alert_context: true` |
| 4D (CRM damage) | `legacy_controller`, `legacy_customer_account_id`, `customer_id`, `sales_tax_type`, `crm_context: true` |
| 5B (extension) | `legacy_controller`, `parent_order_id`, `parent_order_number`, `child_order_id`, `child_order_number`, `base_amount`, `tax_amount`, `add_tax`, `description`, `extension_context: true` |

#### Gap: `legacy_customer_account_id` in metadata only — not in `billing_charges.customer_account_id`

The `billing_charges` table has a `customer_account_id` column and `BillingCharge` has a `legacyCustomerAccount()` relationship. **But this column is never populated by any bridge.** The CA ID is stored only in `metadata['legacy_customer_account_id']`.

This means:
- `BillingCharge::first()->legacyCustomerAccount` always returns `null` for all currently bridged records
- There is no direct FK join from `billing_charges` to `customer_accounts` — only a metadata lookup
- The `customer_account_id` column exists purely as dead schema

**Recommendation: Add `customerAccountId` to `BillingChargeRequest` and populate it from all CA-creating bridges.** This is a low-risk, no-migration change (column already exists and is nullable). All 7 CA-creating bridges (3A, 3B, 3C, 3D, 4B, 4C, 4D) can pass the CA record's ID after the legacy write commits.

---

## 5. Missing Fields Review

### Question 7: Are there any missing fields that will make reporting difficult later?

#### Gap 1: `customer_account_id` never populated

As documented above. Impact: No direct FK join from `billing_charges` to `customer_accounts`. Workaround is `metadata['legacy_customer_account_id']` but this requires JSON parsing.

**Fix:** Add `?int $customerAccountId = null` to `BillingChargeRequest`. No migration required.

#### Gap 2: `tax_amount` is always 0

`BillingEngine::charge()` hardcodes `'tax_amount' => 0` regardless of `tax_type`. For extension charges, the actual tax IS calculated:
- `$taxAmount = $validated['add_tax'] ? round($baseAmount * $salesTaxRate, 2) : 0.00`
- This value IS stored in `metadata['tax_amount']` for extensions
- But `billing_charges.tax_amount` remains 0

For extension rows where `tax_type = 'add'`, the `billing_charges.amount` = `grand_total` (base + tax), so the tax is "in the amount" — but not separately extractable without reading metadata. For fuel/damage charges, tax is almost always `free`, so this is a non-issue today.

**Fix:** Add `?float $taxAmount = null` to `BillingChargeRequest`. Pass it through in `BillingEngine::charge()`. No migration required (column exists).

#### Gap 3: No `Customer::billingCharges()` or `Order::billingCharges()` relationship

As documented above. Impact: Cannot eager-load billing charges from customer or order records without writing raw queries.

**Fix:** Add `hasMany` and `hasOne` relationships to `Customer` and `Order` models. No migration required.

#### Gap 4: No `billing_charges.order_extra_charge_id`

The `order_extra_charges` table is written by `PaymentStoreController` when payment is collected against a fuel/damage charge. There is no link from a `BillingCharge` to its corresponding `OrderExtraCharges` payment record.

**Assessment:** This link is only needed if `billing_charges` is to track payment collection (Phase 7). It can be added when needed. No migration needed now.

---

## 6. Null-by-Design Summary

### Question 8: Charge paths where `parent_order_id` is null by design

| Path | Reason |
|------|--------|
| Dashboard fuel charge (3A) | No order context — charged to a customer, not an order |
| CRM fuel charge (3C) | No order context — CRM modal takes only `customer_id` |
| Dashboard damage charge (4B) | No order context |
| CRM damage charge (4D) | No order context |

**These are permanent design decisions, not gaps.** Customer-level charges that are not associated with a specific order are a valid billing concept (e.g., a general fuel fee for a regular customer without a current open order).

### Question 9: Charge paths where `order_product_id` is null by design

All except Phase 3D (mobile return checklist fuel). Admin-initiated charges operate at the customer or order level, not the order-product level. `order_product_id` is null for all admin charges and all extension charges — by design.

---

## 7. Billing Category Recommendation

### Question: Should `billing_category` be added now?

**Recommendation: No. Do not add it now. Defer to Phase 6.**

#### Analysis

**What `billing_category` would do:**  
A separate `billing_category` dimension (e.g., `rental`, `fuel`, `damage`, `service`, `labor`, `parts`) would let reports group charges by business function independently from charge type. For example, a service ticket might have `charge_type = service_ticket` and `billing_category = labor` or `billing_category = parts`.

**Arguments for adding now:**
1. Fewer rows to backfill (8 source paths vs. potentially dozens post-Phase 6)
2. Clean design moment with known type-to-category mappings

**Arguments against adding now:**
1. **No current consumer.** No report, controller, or API currently reads `billing_category`. Adding a nullable column that nothing reads is pure schema debt.
2. **`billing_charge_type` already covers current needs.** `fuel`, `damage`, `extension` are unambiguous — they don't need a separate category dimension to distinguish them.
3. **Service ticket design is unknown.** The mappings that matter (labor vs. parts vs. shop_supplies vs. environmental) don't exist yet. Adding `billing_category` now without knowing the Service Ticket shape risks designing the wrong enum.
4. **Can be derived.** `billing_category` can always be computed from `billing_charge_type` via a case statement, meaning it adds no information that isn't already available.
5. **Migration + deploy pipeline.** Every schema change runs through the deploy pipeline (`migrate --force`). Adding it now for zero benefit has non-zero risk.

**Future mapping (for when Service Ticket billing is designed):**

| `billing_charge_type` | `billing_category` |
|-----------------------|-------------------|
| `fuel` | `fuel` |
| `damage` | `damage` |
| `extension` | `rental` |
| `service_ticket` (labor) | `service_labor` |
| `service_ticket` (parts) | `service_parts` |
| `service_ticket` (supply) | `service_supply` |
| `cleaning` | `cleaning` |
| `delivery` | `delivery` |
| `misc` | `administrative` |

**Decision:** Skip `billing_category` until Phase 6. At that point, design it with full knowledge of Service Ticket line-item types and add the migration once with complete backfill.

---

## 8. Reporting Readiness Review

### Currently possible from `billing_charges`

All current bridged data supports these queries:

| Report | Query pattern | Available now? |
|--------|--------------|---------------|
| Fuel revenue by date range | `where('billing_charge_type', 'fuel')->whereBetween('created_at', [...])` | ✅ Yes |
| Damage revenue by date range | `where('billing_charge_type', 'damage')` | ✅ Yes |
| Extension revenue by date range | `where('billing_charge_type', 'extension')` | ✅ Yes |
| Revenue by customer | `where('customer_id', $id)` | ✅ Yes |
| Revenue by order | `where('parent_order_id', $id)` | ✅ Yes |
| Revenue by responsible person | `where('responsible_person_id', $id)` | ✅ Yes |
| Revenue by source module | `where('source_module', BillingSourceModule::X->value)` | ✅ Yes |
| Status breakdown (pending/paid/resolved) | `groupBy('status')` | ✅ Yes |
| Charge type breakdown | `groupBy('billing_charge_type')` | ✅ Yes |
| Mobile vs. admin charges | `where('source_module', 'mobile_checklist')` | ✅ Yes |

### Currently NOT possible from `billing_charges`

| Report | Why not available | Fix |
|--------|-----------------|-----|
| Tax revenue | `tax_amount` is always 0 — actual tax is in metadata for extensions only | Add `taxAmount` to `BillingChargeRequest` |
| Payment method breakdown | `billing_charges` has no payment method field | Out of scope until Phase 7 (payment tracking) |
| Service ticket labor/parts breakdown | Service Ticket billing not yet built | Phase 6 |

### Current reports and their data source

**None of the current reports read from `billing_charges`.** All existing reports read from legacy tables:

| Report | Data source |
|--------|------------|
| Fuel Charge Alerts | `customer_accounts` where `reason='Fuel Charge'` |
| Damage Alerts | `customer_accounts` where `reason='Damages'` |
| Calls Log | `customer_accounts` |
| Sales Tax Report | `orders` + `order_extra_charges` |
| Sales Trend | `orders` |
| Employee Performance | `orders` |
| Product Performance | `orders` + `order_products` |

**The switch from legacy sources to `billing_charges` is Phase 7 scope.**

---

## 9. Service Ticket Readiness Review

### Question 1: One BillingCharge or multiple per service ticket?

**Multiple BillingCharges — one per billable line item.**

A service ticket will have line items of different types (labor, parts, shop supplies, environmental fee). Aggregating them into one BillingCharge would:
- Destroy line-item traceability
- Make labor vs. parts revenue reporting impossible
- Prevent per-line-item status tracking (e.g., waiving one parts line without waiving all)

**Recommended pattern:**
```
ServiceTicket → creates → child Order (service order with multiple OrderProducts)
                       → creates → BillingCharge per line item:
                                     billing_charge_type = service_ticket
                                     source_event = service_ticket_labor_item (or parts_item, etc.)
                                     order_product_id = the specific OrderProduct line
                                     parent_order_id = original rental order (if any)
                                     child_order_id = service order
```

### Question 2: One child order with multiple line items?

**Yes.** The service order should be a full `Order` row with multiple `OrderProducts` — one per line item. This enables:
- Customer-visible invoice/receipt
- Payment collection against the service total
- Integration with the Schedules and Orders index
- Existing `order_extra_charges` payment collection flow

This mirrors the extension pattern but is more complex: extensions have one line item; service tickets have N.

### Question 3: Should labor and parts be separate charge types?

**No — use `source_event` to differentiate, not separate enum cases.**

Adding `BillingChargeType::ServiceTicketLabor`, `BillingChargeType::ServiceTicketParts`, etc. would bloat the enum. The enum is for high-level financial categories, not line-item types.

**Recommended approach:**
- `billing_charge_type = service_ticket` for all service ticket lines
- `source_event` differentiates:
  - `service_ticket_labor_item`
  - `service_ticket_parts_item`
  - `service_ticket_shop_supply_item`
  - `service_ticket_environmental_fee`
  - `service_ticket_consolidated_charge` (for an approval-gate aggregate, if needed)

This requires adding new `BillingSourceEvent` enum cases but does NOT require new `BillingChargeType` cases.

### Question 4: Should Service Ticket billing require approval before posting?

**Yes.** Recommended approval gates:
1. **Draft stage**: Technician records labor hours and parts used. BillingEngine is not called yet.
2. **Review stage**: Admin reviews line items and totals.
3. **Post stage**: Admin approves → `ServiceTicketBillingService` calls `BillingEngine::charge()` for each line item and creates the child order.

This prevents accidental charge posting and allows line-item adjustment before the customer ledger is affected.

### Question 5: `ServiceTicketBillingService` or direct `BillingEngine::charge()`?

**`ServiceTicketBillingService` that calls `BillingEngine::charge()`.**

Service ticket billing has enough complexity to warrant its own service:
- Multiple `BillingEngine::charge()` calls (one per line item)
- Child order creation with multiple `OrderProducts`
- Approval state machine
- Tax calculation across multiple line types
- Optional link to a parent rental order

`BillingEngine::charge()` should remain a single-charge, single-responsibility method. `ServiceTicketBillingService` orchestrates the multi-step process and calls `BillingEngine::charge()` for each line item.

### Question 6: What metadata should be stored?

For each `billing_charges` row created by a service ticket:

```php
metadata: [
    'legacy_controller'      => 'ServiceTicket\\PostController',
    'service_ticket_id'      => $serviceTicket->id,
    'service_ticket_number'  => $serviceTicket->ticket_number,
    'line_item_id'           => $lineItem->id,
    'line_item_type'         => 'labor' | 'parts' | 'supply' | 'environmental',
    'quantity'               => $lineItem->quantity,
    'unit_price'             => $lineItem->unit_price,
    'technician_id'          => $lineItem->technician_id ?? null,
    'technician_name'        => $lineItem->technician_name ?? null,
    'equipment_id'           => $serviceTicket->equipment_id ?? null,
    'equipment_serial'       => $serviceTicket->equipment_serial ?? null,
    'service_context'        => true,
]
```

---

## 10. Risks Before Phase 6

### Question 10: Are there any risks before building Service Ticket billing?

#### Risk 1: `customer_account_id` never populated — medium risk

The `billing_charges.customer_account_id` column exists and has a relationship defined, but no bridge ever populates it. If Phase 7 (reporting unification) attempts to join `billing_charges` to `customer_accounts` via this column, it will find null for all records.

**Mitigation:** Add `customerAccountId` to `BillingChargeRequest` and populate it in all CA-creating bridges before Phase 6. This is a small, safe change.

#### Risk 2: `tax_amount` always 0 — low risk now, higher in Phase 6

Service ticket charges may have taxable line items (shop supplies, environmental fees). If `tax_amount` remains locked at 0 by the BillingEngine, Phase 6 will either (a) store tax in metadata (inconsistent) or (b) need to patch `BillingChargeRequest` mid-phase.

**Mitigation:** Add `?float $taxAmount = null` to `BillingChargeRequest` before Phase 6. No migration required.

#### Risk 3: Service Ticket has no model or DB table yet

`ServiceTicket` does not exist. Phase 6 cannot begin without at least a minimal `service_tickets` table and model. This is the blocking prerequisite for Phase 6, not a BillingEngine risk.

#### Risk 4: No `BillingSourceEvent` cases for service ticket line items

`ServiceTicketChargeCreated` exists but is too coarse for line-item-level reporting. New cases should be added before Phase 6 begins.

---

## 11. Schema Changes Recommended Before Phase 6

**No new DB migrations required.** All recommended changes are additive and use existing nullable columns.

### Recommended code changes (before Phase 6 begins)

| Change | File | Risk | Value |
|--------|------|------|-------|
| Add `?int $customerAccountId = null` to `BillingChargeRequest` | `BillingChargeRequest.php` | Very low | Enables `billing_charges.customer_account_id` to actually be used |
| Pass `customer_account_id` in `BillingEngine::charge()` | `BillingEngine.php` | Very low | Same |
| Pass `customerAccountId: $record->id` in all 7 CA-creating bridges | 7 controllers | Very low | Same |
| Add `?float $taxAmount = null` to `BillingChargeRequest` | `BillingChargeRequest.php` | Very low | Enables `billing_charges.tax_amount` to be populated |
| Pass `tax_amount` in `BillingEngine::charge()` | `BillingEngine.php` | Very low | Same |
| Add `billingCharges()` to `Customer` model | `Customer.php` | Zero | Enables `$customer->billingCharges` |
| Add `billingCharges()` and `extensionBillingCharge()` to `Order` model | `Order.php` | Zero | Enables `$order->billingCharges` |
| Add `BillingSourceEvent` cases for service ticket line types | `BillingSourceEvent.php` | Zero | Required for Phase 6 |

### No schema changes needed

- `customer_account_id` column: already exists (nullable)
- `child_order_id` column: already exists (nullable)
- `tax_amount` column: already exists
- `billing_charge_type` enum already has `Extension`, `ServiceTicket`, `Fuel`, `Damage`
- `billing_charges` table has no hard FK constraints yet — these are deferred to a future Phase 8 "harden" migration

---

## 12. Recommended Next Steps

### Immediate (before Phase 6)

1. **Bridge gap: `customerAccountId`** — Add to `BillingChargeRequest` and all 7 CA-creating bridges. One PR, all changes together.
2. **Bridge gap: `taxAmount`** — Add to `BillingChargeRequest`. Pass from extension bridge for now (it's the only one with a calculated tax).
3. **Model relationships** — Add `billingCharges()` to `Customer` and `Order` models.
4. **`BillingSourceEvent` additions** — Add cases for service ticket line types (`ServiceTicketLaborItem`, `ServiceTicketPartsItem`, `ServiceTicketShopSupplyItem`, `ServiceTicketEnvironmentalFee`).

### Phase 5D (optional — not currently planned)

Bridge mobile return checklist damage. This is greenfield: wire `ChargeService::createFromOrderProduct($orderProduct, 'damage', ...)` in `SaveReturnController` (the CA write), then add the BillingEngine bridge immediately after. Required for completeness before Phase 7 reporting unification.

### Phase 6

Service Ticket billing. Prerequisite: `ServiceTicket` model and DB table must exist. Use `ServiceTicketBillingService` to orchestrate. Multiple `BillingEngine::charge()` calls per ticket. Approval gate before posting.

### Phase 7

Reporting unification. Switch Fuel Charge Alerts, Damage Alerts, and Calls Log to read from `billing_charges` instead of `customer_accounts`. Build revenue reports from `billing_charges`. Add payment tracking to BillingEngine.

### Phase 8

Remove legacy bridge mode. Add hard FK constraints to `billing_charges`. Harden schema. Deprecate unused `customer_accounts` charge types.

---

## Summary

| Question | Answer |
|---------|--------|
| Is Billing Engine coverage complete for existing fuel/damage/extension? | **Yes** — 8 entry points bridged |
| Any remaining bypass paths for existing charge types? | **No** — mobile damage is greenfield (never existed); all others bridged |
| Are idempotency keys collision-safe? | **Yes** — 8 unique prefixes, all anchored to stable IDs |
| Are parent/child relationships sufficient? | **Mostly yes** — `customer_account_id` and `tax_amount` never populated are the two gaps |
| Should `billing_category` be added now? | **No** — defer to Phase 6 when Service Ticket shapes are known |
| Is `billing_charges` reporting-ready? | **Yes for fuel/damage/extension revenue** — gaps exist for tax breakdown and payment method |
| What schema changes are needed before Phase 6? | **None (migrations)** — only `BillingChargeRequest` parameter additions and model relationship additions |
