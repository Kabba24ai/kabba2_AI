# Phase 4B — Dashboard Damage Charge Bridge Integration

**Branch:** `feature/billing-engine-consolidation`
**Date:** 2026-06-27
**Status:** Complete

---

## Controller Modified

**File:** `app/Http/Controllers/Admin/Dashboard/DamageChargeStoreController.php`
**Route:** `POST /dashboard/damage-charge/store`
**Route name:** `admin.dashboard.damage-charge.store`
**Response:** JSON `{'success': true, 'message': 'Damage Alert created successfully.'}`

---

## Pre-Integration Audit

### Request fields
| Field | Constraint | Notes |
|-------|-----------|-------|
| `customer_id` | required, exists:customers | No order context — customer only |
| `amount` | required, numeric, min:0.01 | |
| `notes` | nullable, string, max:500 | |
| `responsible_person` | required, exists:users | User ID |
| `sales_tax_type` | nullable, in:add,free,reverse | Field name is `sales_tax_type` (matches CA model directly — no alias needed) |

### Legacy tables written
| Table | Written |
|-------|---------|
| `customer_accounts` | Yes — `reason='Damages'`, `damage_alert_status='pending'`, `type='charge'`, `sales_tax=0` |
| `customer_notes` | Yes — description includes amount, responsible person name, and notes |
| `order_extra_charges` | No |
| `order_product_damage_charge_logs` | No |
| `order_products` | No |

### Order context
**None.** The Dashboard damage modal takes only `customer_id`. No `order_id` is in the request or resolved from any source.

### Order product context
**None.** No `order_product_id` is available at this level.

### Customer identification
Direct: `$request->customer_id` passed inline on the `customer_accounts` record.

### Payment / credit balance
`CustomHelper::updateCreditBalance($record)` is called inside the transaction, adjusting the customer's credit balance. This remains unchanged.

### Tax handling
`sales_tax_type = $request->sales_tax_type ?? 'free'`, `sales_tax = 0`. No calculated tax — the type flag is stored for downstream use only.

### Reports / UI dependencies
The Damage Alerts report (`NewDamageAlerts/IndexController`) reads from `customer_accounts` where `reason='Damages'`. The Dashboard reads from `EquipmentSoftAssign` and merges CRM CA records. Neither reads from `billing_charges`. Both are completely unaffected by this bridge.

---

## What Was Changed

### `DamageChargeStoreController` — bridge added after `DB::commit()`

**Structure:**
1. Inline `$request->validate()` runs first (unchanged)
2. `DB::beginTransaction()` / `DB::commit()` — CA + customer note created (unchanged)
3. `BillingEngine::charge()` fires in a separate `try/catch` after commit
4. Bridge failure → logged to `billing_engine` channel, outer success response unaffected
5. Bridge success → `BillingCharge` row created with all damage context
6. JSON `{'success': true}` returned regardless of bridge outcome

**Use statements added (6):**
- `BillingChargeType`
- `BillingSourceEvent`
- `BillingSourceModule`
- `BillingChargeRequest`
- `BillingEngine`
- `Log`

---

## BillingCharge Mapping

| `billing_charges` column | Value | Source |
|--------------------------|-------|--------|
| `billing_charge_type` | `damage` | `BillingChargeType::Damage->value` |
| `status` | `pending` | BillingEngine default |
| `parent_order_id` | **null** | No order context in Dashboard modal |
| `customer_id` | `$record->customer_id` | From the created CA record |
| `order_product_id` | **null** | Not available at this level |
| `amount` | `$record->amount` | Float cast |
| `tax_type` | `$record->sales_tax_type` | CA model value (defaults to `'free'`) |
| `tax_amount` | `0` | BillingEngine default |
| `responsible_person_id` | `$user->id` | Resolved via `User::findOrFail` |
| `notes` | `$record->notes` | CA record |
| `source_module` | `admin_damage_charge` | `BillingSourceModule::AdminDamageCharge` |
| `source_event` | `admin_damage_charge_created` | `BillingSourceEvent::AdminDamageChargeCreated` |
| `source_reference_type` | `CustomerAccount` | |
| `source_reference_id` | `$record->id` | CA record ID |
| `idempotency_key` | `admin_dashboard_damage_charge:{ca_id}` | See below |
| `metadata.legacy_controller` | `DamageChargeStoreController` | |
| `metadata.legacy_customer_account_id` | `$record->id` | |
| `metadata.customer_id` | `$record->customer_id` | |
| `metadata.sales_tax_type` | `$record->sales_tax_type` | |
| `metadata.dashboard_context` | `true` | Marks this as a dashboard-originated charge |

---

## Idempotency Key

Format: `admin_dashboard_damage_charge:{customer_account_id}`

Example: `admin_dashboard_damage_charge:47`

**Rationale:** The CA record is the only stable anchor — it is created inside the committed transaction before the bridge fires. The prefix `admin_dashboard_damage_charge:` is distinct from all Phase 3 and Phase 4 keys:

| Phase | Key format |
|-------|-----------|
| 3A | `admin_fuel_charge:{ca_id}` |
| 3B | `admin_fuel_alert_charge:{ca_id}` |
| 3C | `crm_fuel_charge:{ca_id}` |
| 3D | `mobile_return_fuel:{op_id}:{fuel_final_reading}` |
| **4B** | **`admin_dashboard_damage_charge:{ca_id}`** |
| 4C (next) | `admin_order_damage_charge:{ca_id}` |
| 4D (next) | `crm_damage_charge:{ca_id}` |

---

## Failure Handling

```
┌────────────────────────────────────────────────────────────────┐
│ $request->validate(...)                                         │
│                                                                 │
│ DB::beginTransaction()                                          │
│ try {                                                           │
│   CustomerAccount::save(...)       ← damage CA row             │
│   CustomHelper::updateCreditBalance(...)                        │
│   customer->notes()->create(...)   ← customer note             │
│   DB::commit()          ◄── legacy complete                     │
│                                                                 │
│   try {                                                         │
│     BillingEngine::charge(...)    ◄── bridge                   │
│   } catch (Throwable $e) {                                      │
│     Log::channel('billing_engine')->error(...)                  │
│     // swallowed — admin sees success response                  │
│   }                                                             │
│                                                                 │
│   return response()->json(['success' => true])                  │
│                                                                 │
│ } catch (Throwable $e) {                                        │
│   DB::rollBack()        ◄── legacy failure path (unchanged)    │
│   return response()->json(['success' => false], 500)            │
│ }                                                               │
└────────────────────────────────────────────────────────────────┘
```

If the bridge throws:
- CA row is **already committed** — unaffected
- Customer note is **already committed** — unaffected
- Credit balance has **already been updated** — unaffected
- Error is logged to `billing_engine` channel with `customer_account_id`, `customer_id`, and exception message
- Admin sees `{'success': true}` — dashboard workflow is not interrupted
- No duplicate legacy records

---

## Damage Charge Bridge Map — All Admin Paths

| Phase | Controller | `parent_order_id` | `order_product_id` | Context |
|-------|-----------|-------------------|--------------------|---------|
| 4B | `DamageChargeStoreController` | **null** | **null** | Dashboard modal |
| 4C (next) | `AlertChargeController` | populated | null | Order Edit alert |
| 4D (next) | `ChargeStoreController` | null | null | CRM customer page |

Phase 4B is the simplest path — no order, no OP, customer-only context. Mirrors Phase 3A exactly for damage.

---

## Tests

**File:** `tests/Feature/BillingEngine/DashboardDamageChargeBridgeTest.php`
**20 tests / 33 assertions — all passing**

| Test | Covers |
|------|--------|
| `test_damage_charge_creates_customer_account_record` | Legacy CA row with correct fields |
| `test_damage_charge_response_is_success` | JSON success shape |
| `test_damage_charge_creates_customer_note` | Customer note created |
| `test_damage_charge_also_creates_billing_charge_record` | BillingCharge created |
| `test_billing_charge_has_correct_type_and_status` | type=damage, status=pending |
| `test_billing_charge_has_correct_amount_and_customer` | amount + customer_id |
| `test_billing_charge_has_null_parent_order_id_for_dashboard_path` | parent_order_id is null |
| `test_billing_charge_has_null_order_product_id` | order_product_id is null |
| `test_billing_charge_stores_source_module_and_event` | source tracking |
| `test_billing_charge_stores_customer_account_as_source_reference` | source_reference = CA |
| `test_billing_charge_stores_metadata_with_legacy_context` | full metadata verified |
| `test_billing_charge_has_blc_prefixed_unique_id` | BLC- prefix |
| `test_billing_charge_idempotency_key_uses_dashboard_damage_prefix_and_ca_id` | key format |
| `test_duplicate_key_does_not_create_second_billing_charge` | idempotency guard |
| `test_legacy_charge_succeeds_even_when_billing_engine_bridge_fails` | failure isolation |
| `test_billing_engine_failure_is_logged_to_billing_engine_channel` | log channel |
| `test_tax_type_is_stored_on_billing_charge` | tax_type=add stored |
| `test_tax_type_defaults_to_free_when_not_provided` | null tax → free |
| `test_billing_charge_does_not_interfere_with_damage_alert_query` | no report interference |

### Full suite result
```
124 tests / 256 assertions — all passing
  BillingEngineTest:                    25 tests  (Phase 2)
  FuelChargeBridgeTest:                 18 tests  (Phase 3A)
  FuelAlertChargeBridgeTest:            20 tests  (Phase 3B)
  CrmFuelChargeBridgeTest:              20 tests  (Phase 3C)
  MobileReturnFuelBridgeTest:           21 tests  (Phase 3D)
  DashboardDamageChargeBridgeTest:      20 tests  (Phase 4B)
```

---

## Guardrail Confirmations

| Area | Changed? |
|------|---------|
| `AlertChargeController` damage path | No |
| `ChargeStoreController` damage path | No |
| Mobile damage charges | No |
| `ChargeService` | No |
| `AmountUpdateController` | No |
| Damage Alerts report | No |
| Dashboard fuel paths | No |
| Phase 3A–3D controllers | No |
| Order Edit UI | No |
| Rental extension logic | No |
| Service tickets | No |
| Tax reports / QuickBooks | No |
| Existing database migrations | No |
| Legacy CA write in `DamageChargeStoreController` | No (preserved exactly) |
| Customer note creation | No (preserved exactly) |
| Credit balance update (`CustomHelper::updateCreditBalance`) | No (preserved exactly) |

---

## Files Created

- `tests/Feature/BillingEngine/DashboardDamageChargeBridgeTest.php`
- `docs/billing-engine-audit/PHASE_4B_DASHBOARD_DAMAGE_BRIDGE_SUMMARY.md`

## Files Modified

- `app/Http/Controllers/Admin/Dashboard/DamageChargeStoreController.php` — 6 use statements added, bridge block added after `DB::commit()`

---

## Next Phase

**Phase 4C: `AlertChargeController` — damage arm**

The bridge guard from Phase 3B (`if ($request->type === 'fuel') { ... }`) is already in place. Phase 4C adds the `elseif ($request->type === 'damage') { ... }` arm immediately below. This is the only admin damage path with order context — `parent_order_id = $order->id` will be populated.
