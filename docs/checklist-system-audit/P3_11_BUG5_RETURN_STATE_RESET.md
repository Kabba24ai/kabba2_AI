# P3-11 — BUG-5: Make RemoveController's return-state reset consistent

**Date:** 2026-07-17
**Scope:** Phase 3, PR order item 11. BUG-5 only — BUG-3, `SaveDeliveryController`, `SaveReturnController`, API response format, and architecture were not touched. Depends on P3-8 (baseline characterization), P3-9 (BUG-2), and P3-10 (BUG-4), all complete and reviewed. Neither P3-9's nor P3-10's production code was modified.

---

## Root cause

`RemoveController::__invoke()` reverts a completed checklist by writing a single `$orderProductData` array to every order product that has checklist rows. That array resets the **delivery**-side fields fully (`delivery_by`, `delivery_notes`, `delivery_signature_media_id`, `delivery_status`, `is_delivered`, `start_hours`, plus the shared `equipment_id`/`equipment_details`/`assigned_by`/`assigned_at`) and also resets `is_returned` to `false` — but it never touches any of the **pickup**-side fields that `SaveReturnController` writes when a return actually happens: `pickup_status`, `pickup_by`, `pickup_notes`, `pickup_signature_media_id`, `end_hours`, or `damage_status`.

## Why the state became contradictory

`is_returned` was already being reset to `false` by the pre-existing code, but nothing else on the pickup side was. That combination is what produces the contradiction: after a removal that runs on an order product which had already been through a full delivery→return cycle, the record ends up with `is_returned = false` — correctly saying "not returned" — sitting right alongside `pickup_status = 'Completed'`, a still-populated `pickup_by`, and (if the return was flagged damaged) a stale `damage_status`, all of which say the opposite. No valid delivery/return cycle in this system can otherwise produce `is_returned=false` together with `pickup_status='Completed'` — this was reachable only through `RemoveController`'s partial reset.

## Identifying which fields represent return/pickup completion

Reading `SaveReturnController`'s own `$orderProductData` write (the only place that ever sets these fields to a "completed" value) gives the authoritative list of what a return actually populates:

```php
'pickup_store_id', 'pickup_date', 'pickup_time', 'pickup_by', 'pickup_notes',
'pickup_signature_media_id', 'pickup_status', 'is_returned', 'end_hours',
'fuel_final_reading', 'fuel_total_charge', 'damage_status' (conditional on a damaged answer)
```

Not all of these represent "return completion state" in the same sense the delivery-side fields already reset represent "delivery completion state" — some are historical/billing data rather than completion-tracking metadata. To decide which must be cleared, this fix mirrors the **exact** curated pattern `RemoveController` already applies on the delivery side, field-for-field:

| Already reset (delivery side) | Pickup-side counterpart | Action |
|---|---|---|
| `delivery_by` | `pickup_by` | **Now reset** |
| `delivery_notes` | `pickup_notes` | **Now reset** |
| `delivery_signature_media_id` (+ file deleted) | `pickup_signature_media_id` (+ file deleted) | **Now reset** |
| `delivery_status` → `'Pending'` | `pickup_status` | **Now reset** → `'Pending'` |
| `start_hours` | `end_hours` | **Now reset** |
| `is_delivered` | `is_returned` | Already reset (unchanged) |
| — (no delivery equivalent) | `damage_status` | **Now reset** — return-only, meaningless without a completed return on record |

Every pickup-side field with a direct, already-reset delivery-side counterpart is now reset the same way. `damage_status` has no delivery-side counterpart at all, but is unambiguously return-completion metadata (it is only ever set inside `SaveReturnController`'s damaged-answer branch), so it is cleared alongside the others — leaving it behind would recreate the exact same class of contradiction (a "damage pending" flag with no completed return backing it).

## Fields intentionally preserved (and why)

The delivery-side reset was never exhaustive — `RemoveController` already leaves `delivery_date`, `delivery_time`, `delivery_store_id`, `delivery_transport_mode`, and `fuel_initial_reading` untouched today, and that was true before this PR and remains true after it (not in this PR's scope to change). For consistency with that existing, established pattern — not because these fields were individually judged unimportant — their pickup-side counterparts are likewise left untouched:

- `pickup_date`, `pickup_time`, `pickup_store_id`, `pickup_transport_mode`, `pickup_priority`, `pickup_driver_locked`, `pickup_priority_locked`
- `fuel_final_reading`, `fuel_total_charge`, `total_charge`, `fuel_charge_status` (historical/billing values — mirrors `fuel_initial_reading` being preserved on the delivery side)
- The driver-checklist subsystem's own pickup fields (`pickup_equipment_fuel`, `pickup_equipment_key_location`, `pickup_equipment_driver_status`, `pickup_ready_to_go_at`, `pickup_arrived_at`, `pickup_is_delivered`, `pickup_is_arrived`, `pickup_inputs_date`, `pickup_tnc_status`, `pickup_drivers_license_status`, `pickup_video_status`, `pickup_checklist_status`) — these belong to `DriverChecklistController`/the mobile driver-checklist flow, a separate subsystem from the customer checklist this controller manages, and are out of scope for BUG-5.

No ambiguity requiring a stop was found: the delivery-side pattern this fix mirrors is itself a precedent already established and accepted in this codebase, and every field newly reset here has a direct, literal counterpart in that existing pattern.

## Implementation

Two additions to `RemoveController::__invoke()`, both mirroring existing delivery-side code one-for-one:

**1. Eager-load the pickup-side relations** (alongside the existing delivery-side ones):
```php
$order = Order::with([
        'products.checklistQuestions.answers',
        'products.deliveryMedia.media',
        'products.deliverySignatureMedia',
        'products.pickupMedia.media',
        'products.returnSignatureMedia',
    ])
```

**2. Extend `$orderProductData`** with the pickup-side counterparts identified above:
```php
'pickup_by' => null,
'pickup_notes' => null,
'pickup_signature_media_id' => null,
'pickup_status' => 'Pending',
'end_hours' => null,
'damage_status' => null,
```

**3. Delete pickup media/signature files**, mirroring the existing delivery-side cleanup exactly:
```php
foreach ($orderProduct->pickupMedia as $orderMedia) {
    $orderMedia->forceDelete();
}

if ($orderProduct->pickup_signature_media_id && $orderProduct->returnSignatureMedia) {
    MediaHelper::removeFile($orderProduct->returnSignatureMedia);
}
```

No new methods, no new parameters, no refactoring of the surrounding loop or transaction structure.

## Preserved behavior

- **Delivery reset behavior** — every existing delivery-side field/value in `$orderProductData` is unchanged.
- **Equipment availability transition** — `EquipmentStatusService::markAvailableOnChecklistRemove()` call is untouched.
- **Equipment status log creation** — unaffected; still fires exactly as before.
- **Transaction guarantees** — the whole method remains wrapped in the same `DB::transaction()` closure; the new writes are plain additions inside the existing loop, not a new transaction boundary.
- **Checklist deletion behavior** — `$orderProduct->checklistQuestions()->delete()` is untouched (BUG-3, the orphaned-answer-row gap it has, is explicitly out of scope for this PR).
- **`SaveDeliveryController`, `SaveReturnController`** — neither file was opened for this PR beyond reading them for reference; zero lines changed in either.
- **API response format** — the response envelope (`{success, message}`) is unchanged.

## Test coverage

All in `tests/Feature/CustomerChecklists/RemoveControllerCharacterizationTest.php`:

- **Flipped:** `test_bug5_pickup_status_and_pickup_by_are_not_reverted_even_though_is_returned_is` → `test_bug5_pickup_status_and_return_metadata_are_reverted_alongside_is_returned`. Now seeds `pickup_notes`, `end_hours`, and `damage_status` in addition to `pickup_status`/`pickup_by`, and asserts all of them are cleared (`pickup_status` → `'Pending'`, the rest → `null`) after removal.
- **New:** `test_bug5_pickup_signature_media_is_reset_and_its_file_deleted` — seeds a real file on a faked `public_asset` disk plus a matching `Media` row referenced by `pickup_signature_media_id`, then confirms both the order product's field and the underlying `Media` row/file are gone after removal (mirroring the existing delivery-signature-media test pattern).
- **New:** `test_bug5_fuel_and_scheduling_fields_are_intentionally_preserved` — seeds `pickup_date`, `pickup_time`, `fuel_final_reading`, `fuel_total_charge`, and `total_charge`, then confirms none of them are touched by a removal, documenting the "intentionally preserved" list above as an executable regression, not just prose.

No other existing test in the file needed a behavior-assertion change: `test_delivery_fields_are_reverted_and_equipment_made_available` (happy path), `test_bug3_...`, `test_products_with_no_checklist_questions_are_left_untouched`, and `test_equipment_status_log_records_the_revert_to_available` all continue to pass unmodified.

## Regression results

```
php artisan test --filter="RemoveControllerCharacterizationTest|SaveDeliveryControllerCharacterizationTest|SaveReturnControllerCharacterizationTest|ChecklistTransactionTest|CompletenessObservabilityLoggingTest|EquipmentStatus"
```
Result: **69 passed, 245 assertions, 0 failures.** Covers all three CustomerChecklists characterization suites (P3-8/P3-9/P3-10/P3-11), the PR-A2 transaction suite, the PR-A4 observability suite, and both `EquipmentStatusService`/`EquipmentStatusLog` call-site suites.

A search of the entire test suite for any other reference to `RemoveController`/the `customer-checklists/remove` route confirmed no other test file touches this code path beyond `ChecklistTransactionTest` and `RemoveControllerCharacterizationTest`, both included above.

## Production impact

- **Behavior change:** removing a checklist for an order product that had already been returned now also clears `pickup_status` (→ `'Pending'`), `pickup_by`, `pickup_notes`, `end_hours`, `damage_status`, and `pickup_signature_media_id` (deleting the underlying file), in addition to the delivery-side fields it already cleared. No order product can be left in the `is_returned=false` + `pickup_status='Completed'` (or similar) contradictory state produced by this controller anymore.
- **No change** to delivery-side reset behavior, equipment status transitions, checklist-question deletion, the transaction boundary, or the response shape.
- **No schema or migration changes** — every field written already exists and is already written elsewhere (`SaveReturnController`) with the same names and types.
- **Risk:** low — purely additive to an existing reset array and an existing media-cleanup pattern, verified against 69 passing tests including 3 dedicated to this exact fix (reset, media cleanup, and preserved-fields boundary).

## Files changed

- `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/RemoveController.php` (production fix — pickup-side relations eager-loaded, pickup-side fields added to the reset array, pickup media/signature cleanup added)
- `tests/Feature/CustomerChecklists/RemoveControllerCharacterizationTest.php` (BUG-5 test flipped and expanded; 2 new tests added — media cleanup and preserved-fields)
- `docs/checklist-system-audit/PHASE3_IMPLEMENTATION_PLAN.md` (P3-11 revision note; BUG-5 marked resolved)
- `docs/checklist-system-audit/P3_11_BUG5_RETURN_STATE_RESET.md` (new — this file)

## Verdict

**PASS.** BUG-5 is fixed by extending `RemoveController`'s existing reset pattern to its pickup-side counterparts, field-for-field, with no new methods or structural changes. The set of fields to clear vs. preserve was determined by mirroring an already-established precedent in the same file (what the delivery side already does and doesn't reset) rather than by guessing — no uncertainty requiring a stop was found. BUG-3, `SaveDeliveryController`, `SaveReturnController`, API format, and architecture were not touched; P3-9 and P3-10 behavior is unaffected. Stopping here — not starting P3-12 or any later PR.
