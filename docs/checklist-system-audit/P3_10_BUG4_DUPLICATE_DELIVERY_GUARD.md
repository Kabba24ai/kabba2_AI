# P3-10 — BUG-4: Prevent destructive duplicate delivery submissions

**Date:** 2026-07-17
**Scope:** Phase 3, PR order item 10. BUG-4 only — BUG-3, BUG-5, API normalization (P3-6), and architecture work were not touched. Depends on P3-8 (baseline characterization) and P3-9 (BUG-2), both complete and reviewed. BUG-2's production code was not modified; two of its characterization tests' fixtures were updated (see "Interaction with P3-9" below), which is what the P3-10 brief explicitly anticipated ("do not modify or revisit BUG-2 unless required to preserve its tests").

---

## Root cause

`SaveDeliveryController::__invoke()` had no guard against a second delivery submission for an order product that already has an active, uncompleted-by-return delivery on record. Every submission — first or repeat — ran the full destructive sequence unconditionally whenever a `checklist` array was present: delete all existing `order_product_checklist_questions` rows for the order product, recreate them from the master template, and recreate every answer row. A second submission for the same delivery cycle silently wiped and rebuilt the prior submission's checklist snapshot, re-uploaded a new signature (overwriting `delivery_signature_media_id`), and re-ran the equipment assignment/status logic — with no 409 or any other rejection, unlike `SaveReturnController`'s existing `ChecklistAlreadySubmitted` guard for the return side.

Critically, the controller's pre-existing `isRented()` conflict guard **only** blocks a duplicate submission by accident, and only while the equipment happens to still be `'rented'`. If anything else moved the equipment out of `'rented'` between the two submissions (most plausibly an admin correction via `UpdateProductScheduleController`/`AssignEquipmentController`, but also reachable via test/manual DB manipulation) **without** the order product ever actually being returned, the `isRented()` guard no longer fires, and the full destructive sequence runs again. This is exactly the gap P3-8's `SaveDeliveryControllerCharacterizationTest` characterized by forcing `current_status` to `'maintenance'` between two calls.

## Definition of a duplicate submission

A delivery submission is a **duplicate of the current cycle** — and must be rejected — when, at the moment the request is received, the target order product already has:

```php
$orderProduct->is_delivered === true && $orderProduct->is_returned === false
```

This combination means a delivery has already been completed for this order product, and nothing has closed that cycle since: a genuine return (`SaveReturnController`) always sets `is_returned = true` as part of its own update, and a checklist removal (`RemoveController`) always sets `is_delivered = false`. If both of those remain in their "already delivered, not yet returned" state, any further delivery submission — regardless of what triggered it (network retry, accidental double-tap, or any other resubmission) and regardless of what the equipment's own `current_status` happens to read at that moment — is necessarily hitting the same, still-open cycle.

## How a legitimate later delivery cycle is distinguished

A **legitimate new delivery cycle** for the same order product/equipment pairing is only reachable once the current cycle has been closed, which in this codebase happens exactly one way: `SaveReturnController` runs and sets `is_returned = true`. At that point `is_delivered` is still `true` (return doesn't clear it) but `is_returned` is now `true`, so the guard's condition (`is_delivered && !is_returned`) is false and a new delivery is allowed to proceed — including a full rebuild of the checklist snapshot for the new cycle, and (per P3-9) a fresh `markRented()` transition.

A checklist removal (`RemoveController`) is the other legitimate reset path: it sets `is_delivered = false` directly, which also makes the guard's condition false (nothing to guard against — there's no completed delivery on record at all).

This is a reliable, already-existing signal — not a new field or migration — because both `is_delivered` and `is_returned` are written unconditionally by the two controllers that define a "cycle" in this domain (`SaveDeliveryController` and `SaveReturnController` respectively), and by `RemoveController` for the removal-reset path. No ambiguity was found that would require stopping short of implementation.

## Implementation

The guard is placed immediately after the existing `isRented()` 409 conflict check and before the checklist-submitted block (the first destructive action), inside `SaveDeliveryController::__invoke()`:

```php
if($equipment && $equipment->current_status->isRented()){
    return response()->json([...], JsonResponse::HTTP_CONFLICT);
}

// BUG-4 fix
if ($orderProduct->is_delivered && !$orderProduct->is_returned) {
    return response()->json(
        [
            'success' => false,
            'message' => trans('messages.api.admin.v1.orders.checklist_already_exists'),
        ],
        JsonResponse::HTTP_CONFLICT,
    );
}
```

This position guarantees the guard runs before every destructive step requirement 3 named: checklist-question deletion, answer deletion, replacement-row creation, media upload, equipment assignment/status change, and the `markRented()`/audit-log write — all of which happen later in the same method, in sequence, after this point.

## Response status and payload

**HTTP 409 Conflict**, reusing the *exact same* response envelope shape (`{success, message}`, no new keys) and the *exact same* translation key (`messages.api.admin.v1.orders.checklist_already_exists`, "Checklist already exists for this order product") that `SaveReturnController`'s own `ChecklistAlreadySubmitted` guard already uses via `ApiResponseHelper::error()`. `SaveDeliveryController` does not use `ApiResponseHelper` anywhere else in this file (all of its other error branches use a local `response()->json([...], $status)` call), so the new guard matches *this file's own* established convention rather than introducing a dependency on a different controller's helper — while still reusing the shared translation key so the wording is identical to the equivalent return-side guard.

## Preserved behavior

- **First-time delivery** — `is_delivered` starts `false`, so the guard's condition is never true; completely unaffected. Verified: `test_new_equipment_assignment_calls_mark_rented_and_creates_status_log` still passes unmodified.
- **P3-9 re-delivery status behavior** — a genuine new cycle (equipment left `'rented'` *and* `is_returned` set `true`, mirroring a real return) still succeeds, still rebuilds the checklist, and still reapplies `markRented()`. Verified by a new test, `test_bug4_legitimate_new_delivery_cycle_after_a_return_is_still_allowed`, and by updating P3-9's own two BUG-2 tests to set `is_returned = true` alongside the equipment-status change they were already making — see "Interaction with P3-9" below.
- **Transaction guarantees** — the guard is a plain early `return` from inside the same `DB::transaction()` closure; nothing about the transaction wrapper changed.
- **Completeness observability logging** — `logIfDeliveryIncomplete()` is called later in the method, after the guard; a rejected duplicate submission never reaches it, so no spurious log entries are produced for a request that didn't actually deliver anything. Unaffected for every other case.
- **Media behavior** — a rejected duplicate never reaches the `hasFile('signature_media')` block; `MediaHelper::uploadStorageFile()` is not called, so no new `Media`/`OrderMedia` rows are created for a rejected duplicate. Verified directly in the new duplicate-rejection test.
- **Billing behavior** — `SaveDeliveryController` has no billing code; nothing to preserve here beyond confirming the broader billing-idempotency suite still passes (see Regression results).
- **Equipment assignment behavior** — a rejected duplicate never reaches the assignment/`markRented()` block; the equipment's `current_status`, `current_order_id`/`current_order_product_id`, and `equipment_status_logs` are left exactly as they were before the rejected request. Verified directly in the new duplicate-rejection test.

## Interaction with P3-9 (test fixtures only, not production code)

P3-9's own two BUG-2 characterization tests simulate "equipment left `'damaged'`/`'maintenance'` by a prior return" by directly calling `$equipment->saveQuietly()` on the equipment row, **without** ever touching the order product's `is_returned` flag. Under this PR's new guard, that fixture shape now reads as a same-cycle duplicate (`is_delivered=true`, `is_returned=false`), not a legitimate new cycle, and the second call in each of those tests would be rejected 409 instead of reaching P3-9's own assertions.

Both fixtures were updated to also set `$orderProduct->update(['is_returned' => true])` alongside the equipment-status change, which is what a genuine return actually does (`SaveReturnController` always sets both together). This makes the test fixtures a more accurate model of "a real return happened" rather than "equipment status changed by some other means," and is the reason both tests still pass unmodified in their actual assertions — only the fixture setup gained one line each. `SaveDeliveryController`'s BUG-2 production fix itself (the `markRented()` call moved outside the assignment-metadata `if`) was not touched.

## Tests added or changed

All in `tests/Feature/CustomerChecklists/SaveDeliveryControllerCharacterizationTest.php`:

- **Changed (fixture only):** `test_bug2_redelivering_same_equipment_reapplies_rented_status` and `test_bug2_redelivering_same_equipment_from_maintenance_also_reapplies_rented_status` — each now also sets `is_returned = true` between the two delivery calls, per "Interaction with P3-9" above.
- **Renamed and flipped:** `test_bug4_second_delivery_submission_destructively_rebuilds_checklist_snapshot_with_no_guard` → `test_bug4_duplicate_delivery_submission_is_rejected_and_leaves_everything_unchanged`. Now asserts: the second (same-cycle) submission returns 409; the checklist-question ids are byte-for-byte identical before and after (nothing deleted/recreated); `delivery_signature_media_id` is unchanged (no replacement media uploaded); the equipment's `current_status` is unchanged from whatever it was forced to; and `equipment_status_logs`'s count for that equipment is unchanged (no new transition/audit row).
- **New:** `test_bug4_legitimate_new_delivery_cycle_after_a_return_is_still_allowed` — a genuine new cycle (equipment status changed **and** `is_returned` set `true`, mirroring a real return) still succeeds, rebuilds the checklist with entirely new question-row ids, and still ends with the equipment `'rented'` again (confirming P3-9's fix and P3-10's guard compose correctly).

## Regression results

```
php artisan test --filter="SaveDeliveryControllerCharacterizationTest|SaveReturnControllerCharacterizationTest|RemoveControllerCharacterizationTest|ChecklistTransactionTest|CompletenessObservabilityLoggingTest|EquipmentStatus|MobileReturnCycleIdempotencyTest"
```
Result: **70 passed, 250 assertions, 0 failures.** Covers: both new CustomerChecklists characterization suites (P3-8/P3-9/P3-10), `RemoveControllerCharacterizationTest`, the PR-A2 transaction suite, the PR-A4 observability suite, both `EquipmentStatusService`/`EquipmentStatusLog` call-site suites (`EquipmentStatusLogAdditionalPathsTest`, `UpdateProductScheduleEquipmentStatusLogTest`), and the closest existing billing-idempotency regression to this exact re-delivery scenario (`MobileReturnCycleIdempotencyTest`, which exercises delivery → return → re-delivery → re-return across two full rental cycles on the same order product).

A search of the entire test suite for any other reference to `SaveDeliveryController`/`SaveDeliveryRequest`/the `save-delivery` route confirmed no other test file touches this code path beyond the four suites already included above.

## Production impact

- **Behavior change:** a delivery submission for an order product that already has an active, unreturned delivery on record is now rejected with `409 Conflict` instead of silently deleting and rebuilding the checklist snapshot, re-uploading media, and re-running equipment assignment logic.
- **No change** to first-time delivery, legitimate new-cycle re-delivery (post-return), the response envelope shape, billing, the transaction boundary, or observability logging for any request that isn't a rejected duplicate.
- **No schema or migration changes** — the guard reads two columns (`is_delivered`, `is_returned`) that already exist and are already written by the surrounding controllers.
- **Risk:** low — the guard only rejects requests that would otherwise have destructively overwritten an already-completed delivery; every legitimate path (first delivery, post-return re-delivery, post-removal re-delivery) was verified to still succeed.

## Files changed

- `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveDeliveryController.php` (production fix — new guard added after the existing `isRented()` check)
- `tests/Feature/CustomerChecklists/SaveDeliveryControllerCharacterizationTest.php` (BUG-4 test renamed and flipped to assert the guard; 1 new legitimate-cycle regression test added; P3-9's two BUG-2 tests' fixtures updated to set `is_returned = true`)
- `docs/checklist-system-audit/PHASE3_IMPLEMENTATION_PLAN.md` (P3-10 revision note; BUG-4 marked resolved)
- `docs/checklist-system-audit/P3_10_BUG4_DUPLICATE_DELIVERY_GUARD.md` (new — this file)

## Verdict

**PASS.** BUG-4 is fixed with a single, minimal guard mirroring `SaveReturnController`'s existing already-submitted convention (409, same message), placed before every destructive action it needs to prevent. The duplicate-vs-legitimate-cycle distinction rests on two already-existing, already-authoritative fields (`is_delivered`/`is_returned`) — no ambiguity requiring a stop was found. P3-9's fix and tests remain correct (with two fixtures made more realistic, not weakened). BUG-3, BUG-5, API normalization, and architecture work were not touched. Stopping here — not starting P3-11, P3-12, or P3-6.
