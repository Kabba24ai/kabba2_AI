# Phase 4D — CRM Damage Charge Bridge Integration

**Branch:** `feature/billing-engine-consolidation`
**Date:** 2026-06-27
**Status:** Complete

---

## Controller Modified

**File:** `app/Http/Controllers/Admin/Crm/Customers/CustomerAccount/ChargeStoreController.php`
**Route:** `POST /crm/customers/customer-account/charge-store`
**Route name:** `admin.crm.customers.customer-account.chargestore`
**Response:** `redirect()->back()` (web form controller — NOT JSON)

---

## Pre-Integration Audit

### Existing fuel bridge (Phase 3C) — confirmed intact

The `if ($validated['reason'] === 'Fuel Charge') { ... }` block added in Phase 3C remains unchanged. Phase 4D added `elseif ($validated['reason'] === 'Damages') { ... }` immediately below it. The fuel arm's idempotency key (`crm_fuel_charge:{ca_id}`), source module/event, and metadata are untouched.

### Exact damage reason value

```php
$validated['reason'] === 'Damages'
```

This is the exact string written to `customer_accounts.reason` for damage charges (line 50 of the pre-bridge controller: `$record->damage_alert_status = ($validated['reason'] === 'Damages') ? 'pending' : null`).

### Does this controller accept arbitrary reason values?

**Yes.** The `ChargeStoreRequest` validates `reason` as a required string but does not restrict it to an enum. Any string value (e.g., `'Late Return Fee'`, `'Storage Fee'`) will create a `CustomerAccount` record. The bridge fires **only** for `'Fuel Charge'` (Phase 3C) and `'Damages'` (Phase 4D). All other reason values fall through without a bridge. This is by design and tested explicitly.

### Request fields
| Field | Constraint | Notes |
|-------|-----------|-------|
| `customer_id` | required | No order context |
| `amount` | required, numeric, min:0.01 | |
| `reason` | required, string | `'Damages'` triggers this bridge arm |
| `responsible_person` | required, exists:users | User ID |
| `sales_tax` | nullable, in:add,free,reverse | Request field is `sales_tax`; CA model stores as `sales_tax_type` |
| `notes` | nullable, string | |

### Tax field naming difference (unchanged from Phase 3C)
The request field is `sales_tax`; the CA model stores it as `sales_tax_type`. The bridge reads `$record->sales_tax_type ?? 'free'` (from the saved CA model) — the same pattern used by the fuel arm.

### Legacy tables written by the damage path
| Table | Written |
|-------|---------|
| `customer_accounts` | Yes — `reason='Damages'`, `damage_alert_status='pending'`, `fuel_alert_status=null`, `type='charge'` |
| `customer_notes` | No — this controller does not write notes (unlike `DamageChargeStoreController` and `AlertChargeController`) |
| `order_extra_charges` | No |
| `order_product_damage_charge_logs` | No |
| `order_products` | No |

### Order context
**None.** The CRM charge modal takes only `customer_id`. `parent_order_id = null`.

### Order product context
**None.** `order_product_id = null`.

### Customer identification
Direct: `$validated['customer_id']` passed to the CA record.

### Payment / credit balance
`CustomHelper::updateCreditBalance($record)` runs inside the transaction. Unchanged.

### Report / UI dependencies
- **Damage Alerts report** (`NewDamageAlerts/IndexController`): reads from `customer_accounts` where `reason='Damages'`
- **CRM customer page**: reads from `customer_accounts` for the credit tab
- Neither reads from `billing_charges`. Both are completely unaffected.

---

## What Was Changed

### `ChargeStoreController` — `elseif` damage arm added

The existing `if ($validated['reason'] === 'Fuel Charge') { ... }` block was preserved exactly. An `elseif ($validated['reason'] === 'Damages') { ... }` arm was added immediately below. The comment on the fuel block was updated from `Phase 3C — fuel only` to `Phase 3C`.

**Structure after Phase 4D:**
```
DB::commit()

if (reason === 'Fuel Charge') {    ← Phase 3C (unchanged)
    try { BillingEngine... }
    catch { Log::error... }
} elseif (reason === 'Damages') {  ← Phase 4D (new)
    try { BillingEngine... }
    catch { Log::error... }
}

flash(...)
return redirect()->back()
```

### `CrmFuelChargeBridgeTest.php` — one test updated

`test_crm_damage_charge_does_not_create_billing_charge` was renamed to `test_crm_damage_charge_also_creates_billing_charge` and its assertion was updated from `assertEquals(0, ...)` to `assertEquals(1, ...)` with an added type check. The old assertion was correct for Phase 3C (damage unbrided); it is now correct for Phase 4D (damage is bridged).

---

## BillingCharge Mapping

| `billing_charges` column | Value | Source |
|--------------------------|-------|--------|
| `billing_charge_type` | `damage` | `BillingChargeType::Damage->value` |
| `status` | `pending` | BillingEngine default |
| `parent_order_id` | **null** | No order context in CRM modal |
| `customer_id` | `$record->customer_id` | From the saved CA record |
| `order_product_id` | **null** | Not available at this level |
| `amount` | `$record->amount` | Float cast |
| `tax_type` | `$record->sales_tax_type ?? 'free'` | Null-coalesced from CA model (request field is `sales_tax`) |
| `tax_amount` | `0` | BillingEngine default |
| `responsible_person_id` | `$user->id` | Resolved via `User::findOrFail` |
| `notes` | `$record->notes` | CA record |
| `source_module` | `admin_damage_charge` | `BillingSourceModule::AdminDamageCharge` |
| `source_event` | `admin_damage_charge_created` | `BillingSourceEvent::AdminDamageChargeCreated` |
| `source_reference_type` | `CustomerAccount` | |
| `source_reference_id` | `$record->id` | CA record ID |
| `idempotency_key` | `crm_damage_charge:{ca_id}` | See below |
| `metadata.legacy_controller` | `ChargeStoreController` | |
| `metadata.legacy_customer_account_id` | `$record->id` | |
| `metadata.customer_id` | `$record->customer_id` | |
| `metadata.sales_tax_type` | `$record->sales_tax_type` | |
| `metadata.crm_context` | `true` | Marks this as a CRM-originated charge |

---

## Idempotency Key

Format: `crm_damage_charge:{customer_account_id}`

Example: `crm_damage_charge:63`

**Rationale:** The CA record is created inside the committed transaction before the bridge fires. The prefix `crm_damage_charge:` is distinct from all existing keys:

| Phase | Key format |
|-------|-----------|
| 3A | `admin_fuel_charge:{ca_id}` |
| 3B | `admin_fuel_alert_charge:{ca_id}` |
| 3C | `crm_fuel_charge:{ca_id}` |
| 3D | `mobile_return_fuel:{op_id}:{fuel_final_reading}` |
| 4B | `admin_dashboard_damage_charge:{ca_id}` |
| 4C | `admin_damage_alert_charge:{ca_id}` |
| **4D** | **`crm_damage_charge:{ca_id}`** |

---

## Failure Handling

```
┌────────────────────────────────────────────────────────────────────┐
│ $validated = $request->validated()                                  │
│                                                                     │
│ DB::beginTransaction()                                              │
│ try {                                                               │
│   CustomerAccount::save(...)   ← CA with damage_alert_status       │
│   CustomHelper::updateCreditBalance(...)                            │
│   DB::commit()       ◄── legacy complete                            │
│                                                                     │
│   if (reason === 'Fuel Charge') {   ← Phase 3C (unchanged)         │
│     try { BillingEngine::charge(...) }                              │
│     catch { Log::error(...) }                                       │
│   } elseif (reason === 'Damages') {   ← Phase 4D                   │
│     try { BillingEngine::charge(...) }                              │
│     catch { Log::error(...) }  ← logged, swallowed                 │
│   }                                                                 │
│                                                                     │
│   flash('Charge successfully added')                                │
│   return redirect()->back()   ◄── admin redirected regardless      │
│                                                                     │
│ } catch (Throwable $e) {                                            │
│   DB::rollBack()       ◄── legacy failure path (unchanged)         │
│   flash('Something went wrong...').error()                          │
│   return redirect()->back()->withInput()->withErrors(...)            │
│ }                                                                   │
└────────────────────────────────────────────────────────────────────┘
```

If the damage bridge throws:
- CA row is **already committed** — unaffected
- Credit balance has **already been updated** — unaffected
- Error is logged to `billing_engine` channel with `customer_account_id`, `customer_id`, and exception message
- Admin is redirected with the success flash — no indication of bridge failure
- No duplicate legacy records

---

## Complete Damage Charge Bridge Map

| Phase | Controller | `parent_order_id` | `order_product_id` | Context |
|-------|-----------|-------------------|--------------------|---------|
| 4B | `DamageChargeStoreController` | null | null | Dashboard modal |
| 4C | `AlertChargeController` | populated | null | Order Edit alert |
| **4D** | **`ChargeStoreController`** | **null** | **null** | **CRM customer page** |

All three existing admin damage charge entry points are now bridged. Phase 4 (all three sub-phases) is complete.

## Complete Fuel + Damage Bridge Map

| Phase | Controller | Type | `parent_order_id` | `order_product_id` |
|-------|-----------|------|-------------------|--------------------|
| 3A | `FuelChargeStoreController` | fuel | null | null |
| 3B | `AlertChargeController` (fuel) | fuel | populated | null |
| 3C | `ChargeStoreController` (fuel) | fuel | null | null |
| 3D | `SaveReturnController` | fuel | populated | **populated** |
| 4B | `DamageChargeStoreController` | damage | null | null |
| 4C | `AlertChargeController` (damage) | damage | populated | null |
| 4D | `ChargeStoreController` (damage) | damage | null | null |

Seven entry points bridged. Zero schema changes required.

---

## Tests

**New file:** `tests/Feature/BillingEngine/CrmDamageChargeBridgeTest.php`
**20 tests / 32 assertions — all passing**

| Test | Covers |
|------|--------|
| `test_crm_damage_charge_creates_customer_account_record` | Legacy CA row with correct fields |
| `test_crm_damage_charge_returns_redirect` | Response is 302 (not JSON) |
| `test_crm_damage_charge_also_creates_billing_charge_record` | BillingCharge created |
| `test_billing_charge_has_correct_type_and_status` | type=damage, status=pending |
| `test_billing_charge_has_correct_amount_and_customer` | amount + customer_id |
| `test_billing_charge_has_null_parent_order_id_for_crm_path` | parent_order_id is null |
| `test_billing_charge_has_null_order_product_id` | order_product_id is null |
| `test_billing_charge_stores_source_module_and_event` | source tracking |
| `test_billing_charge_stores_customer_account_as_source_reference` | source_reference = CA |
| `test_billing_charge_stores_crm_damage_context_in_metadata` | full metadata verified |
| `test_billing_charge_has_blc_prefixed_unique_id` | BLC- prefix |
| `test_billing_charge_idempotency_key_uses_crm_damage_prefix_and_ca_id` | key format |
| `test_duplicate_key_does_not_create_second_billing_charge` | idempotency guard |
| `test_legacy_charge_succeeds_even_when_billing_engine_bridge_fails` | failure isolation |
| `test_billing_engine_failure_is_logged_to_billing_engine_channel` | log channel |
| `test_tax_type_is_stored_on_billing_charge` | tax_type=add |
| `test_tax_type_defaults_to_free_when_not_provided` | null → free |
| `test_crm_fuel_bridge_still_creates_fuel_billing_charge` | Phase 3C fuel regression |
| `test_arbitrary_crm_reason_does_not_create_billing_charge` | only Fuel Charge + Damages are bridged |
| `test_billing_charge_does_not_interfere_with_damage_alert_query` | no report interference |

**Updated test in `CrmFuelChargeBridgeTest.php`:**
- `test_crm_damage_charge_does_not_create_billing_charge` → renamed to `test_crm_damage_charge_also_creates_billing_charge`
- Assertion updated from `assertEquals(0, ...)` to `assertEquals(1, ...)` with type check
- Reflects Phase 4D reality: damage is now bridged

### Full suite result
```
163 tests / 329 assertions — all passing
  BillingEngineTest:                    25 tests  (Phase 2)
  FuelChargeBridgeTest:                 18 tests  (Phase 3A)
  FuelAlertChargeBridgeTest:            20 tests  (Phase 3B — one test updated in Phase 4C)
  CrmFuelChargeBridgeTest:              20 tests  (Phase 3C — one test updated in Phase 4D)
  MobileReturnFuelBridgeTest:           21 tests  (Phase 3D)
  DashboardDamageChargeBridgeTest:      20 tests  (Phase 4B)
  DamageAlertChargeBridgeTest:          20 tests  (Phase 4C)
  CrmDamageChargeBridgeTest:            20 tests  (Phase 4D)
```

---

## Guardrail Confirmations

| Area | Changed? |
|------|---------|
| `DamageChargeStoreController` (Phase 4B) | No |
| `AlertChargeController` damage path (Phase 4C) | No |
| Mobile damage charges | No |
| `ChargeService` | No |
| `AmountUpdateController` | No |
| Damage Alerts report | No |
| CRM customer page | No |
| Order Edit UI | No |
| Phase 3C fuel arm of `ChargeStoreController` | No — intact, no regression |
| Phase 3A–3D controllers | No |
| Rental extension logic | No |
| Service tickets | No |
| Tax reports / QuickBooks | No |
| Existing database migrations | No |
| Arbitrary CRM charge reasons (e.g., 'Late Return Fee') | No bridge — only 'Fuel Charge' and 'Damages' |
| Legacy CA write in `ChargeStoreController` | No (preserved exactly) |
| Credit balance update | No (preserved exactly) |

---

## Files Created

- `tests/Feature/BillingEngine/CrmDamageChargeBridgeTest.php`
- `docs/billing-engine-audit/PHASE_4D_CRM_DAMAGE_BRIDGE_SUMMARY.md`

## Files Modified

- `app/Http/Controllers/Admin/Crm/Customers/CustomerAccount/ChargeStoreController.php` — `elseif` damage arm added; fuel bridge comment updated
- `tests/Feature/BillingEngine/CrmFuelChargeBridgeTest.php` — one test renamed and updated to reflect Phase 4D behavior

---

## Phase 4 Complete

All three existing admin damage charge paths are now bridged into BillingEngine in bridge mode:

| Phase | Controller | Status |
|-------|-----------|--------|
| 4A | Architecture review | Complete |
| 4B | `DamageChargeStoreController` | Complete |
| 4C | `AlertChargeController` damage arm | Complete |
| 4D | `ChargeStoreController` damage reason | Complete |

Seven total charge entry points (4 fuel + 3 damage) are now bridged. All 163 tests pass. No schema changes were made in Phase 4.

---

## What Remains Unbridged

| Path | Reason | Future phase |
|------|--------|-------------|
| Mobile return checklist damage | Greenfield — not yet wired in `SaveReturnController` | Phase 4E or later |
| Service ticket charges | Greenfield — no ServiceTicket model exists | Phase 6 |
| Rental extension charges | Requires child order creation logic | Phase 5 |
