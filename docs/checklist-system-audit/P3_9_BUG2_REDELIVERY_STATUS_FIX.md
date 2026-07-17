# P3-9 — BUG-2: Reapply equipment rented status on re-delivery

**Date:** 2026-07-17
**Scope:** Phase 3, PR order item 9. BUG-2 only — no other bug (BUG-3, BUG-4, BUG-5), API work, or architecture work was touched. Depends on P3-8's baseline characterization tests, already complete and reviewed.

---

## Root cause

`SaveDeliveryController::__invoke()` only calls `EquipmentStatusService::markRented()` — the equipment status transition to `'rented'`, plus the fresh `equipment_status_logs` audit row it writes — inside this guard:

```php
if (empty($orderProduct->equipment_details) || $orderProduct->equipment_id !== $equipment->id) {
    $orderProductData['equipment_id'] = $equipment->id;
    $orderProductData['equipment_details'] = $equipment->toArray();
    $orderProductData['assigned_by'] = $validated['user_id'];
    $orderProductData['assigned_at'] = now();

    $equipment->equipment_hours = $validated['start_hours'] ?? null;
    EquipmentStatusService::markRented(...);
}
```

This condition is true only for a **new** equipment assignment: either the order product has never had equipment assigned (`equipment_details` empty), or the equipment being delivered now differs from whatever was assigned before. It is `false` whenever the **same** equipment is delivered again on the same order product — exactly the re-delivery scenario BUG-2 describes (e.g. the equipment was previously delivered, then returned and left `'damaged'`/`'maintenance'`, and is now being redelivered for a new cycle on the same order product). In that case the condition is `false`, so `markRented()` is skipped entirely: the equipment's `current_status` is left exactly as it was (stale `'damaged'`/`'maintenance'`), and no new `equipment_status_logs` row is written — even though the delivery itself proceeds and reports success (`delivery_status` unconditionally becomes `'Completed'`, `is_delivered` unconditionally becomes `true`, a few lines below, outside this `if`).

## Why the previous behavior occurred

The `if` condition conflates two genuinely separate concerns that happen to change together on a *first* delivery, but diverge on a re-delivery:

1. **Assignment-metadata bookkeeping** — whether `equipment_id`/`equipment_details`/`assigned_by`/`assigned_at` need to be (re)written on the order product, which is only meaningful when the assignment is actually changing.
2. **Equipment status transition** — whether the equipment itself needs to move to `'rented'`, which is meaningful on *every* successful delivery, regardless of whether the equipment assignment metadata is changing.

The original code treated (2) as a subset of (1) — reasonable for the "new assignment" case where both are true together, but incorrect for a same-equipment re-delivery where (1) is false (nothing about the assignment record changes) while (2) is still true (the equipment must still transition out of whatever status it cycled into).

## Implementation

The controller's own `isRented()` conflict guard, evaluated earlier in the same method —

```php
if($equipment && $equipment->current_status->isRented()){
    return response()->json([...], JsonResponse::HTTP_CONFLICT);
}
```

— already guarantees that execution can only reach the assignment block when the equipment is **not** currently rented. That guarantee makes it safe to reapply `markRented()` unconditionally on every successful delivery: the two possible cases are (a) a genuinely new assignment, where it already fired before and continues to; and (b) a re-delivery of the same equipment while it is in some non-rented status, where it previously did not fire and now correctly does.

The fix splits the two concerns apart. The assignment-metadata write (`equipment_id`/`equipment_details`/`assigned_by`/`assigned_at`) stays exactly as before, gated on the same condition, unchanged. The equipment-hours assignment and `markRented()` call are moved out of that `if` block so they run unconditionally on every successful delivery:

```php
if (empty($orderProduct->equipment_details) || $orderProduct->equipment_id !== $equipment->id) {
    $orderProductData['equipment_id'] = $equipment->id;
    $orderProductData['equipment_details'] = $equipment->toArray();
    $orderProductData['assigned_by'] = $validated['user_id'];
    $orderProductData['assigned_at'] = now();
}

// BUG-2 fix: reapply the rented transition on EVERY successful delivery...
$equipment->equipment_hours = $validated['start_hours'] ?? null;
EquipmentStatusService::markRented(
    $equipment,
    $orderProduct->order_id,
    $orderProduct->id,
    isset($validated['user_id']) ? (int) $validated['user_id'] : null
);
```

This is the smallest change that closes the gap: one `if` block split into an unconditional statement and a narrower conditional one, no new methods, no new parameters, no changes to `EquipmentStatusService` itself (`markRented()` was already idempotent-safe to call repeatedly — it simply writes the current values and a fresh log row each time).

### What was deliberately left untouched

- **Assignment-metadata fields** (`equipment_id`, `equipment_details`, `assigned_by`, `assigned_at`) — still only updated when the assignment is genuinely new, exactly as before. A same-equipment re-delivery does not refresh these, matching pre-fix behavior; changing that is out of BUG-2's scope.
- **Billing** — no billing code in this controller; nothing to change. Confirmed via `MobileReturnCycleIdempotencyTest` (see Regression results).
- **Checklist answers** — the checklist-question/answer rebuild logic above this block is entirely untouched.
- **Media uploads** — the signature-media upload block below this change is untouched.
- **Transactions** — the whole method remains wrapped in the same `DB::transaction()` call; `markRented()`'s own `saveQuietly()` + log write now simply runs on one more code path than before, still inside that same transaction.
- **Idempotency guarantees** — PR-A3's cycle-key idempotency (`SaveReturnController`'s billing-charge dedup) is untouched; this fix is entirely on the delivery side and doesn't touch billing.
- **BUG-3, BUG-4, BUG-5** — untouched. BUG-4's characterization test (destructive checklist-snapshot rebuild on a second delivery) still passes unchanged, since it never asserted anything about equipment status.

## Test coverage

**`tests/Feature/CustomerChecklists/SaveDeliveryControllerCharacterizationTest.php`** — the BUG-2 test was flipped from asserting the buggy behavior to asserting the fix:

- `test_bug2_redelivering_same_equipment_reapplies_rented_status` (renamed from `..._does_not_call_mark_rented_again`): re-delivers the same equipment after forcing it to `'damaged'` between calls, and now asserts the equipment ends up `'rented'` again, with a new `equipment_status_logs` row recording the `damaged → rented` transition (previously asserted the opposite — equipment stays `'damaged'`, no new log row).
- `test_bug2_redelivering_same_equipment_from_maintenance_also_reapplies_rented_status` (new): the more common real-world path — equipment cycled through a normal (non-damaged) return into `'maintenance'`, then redelivered on the same order product — also ends up `'rented'`.

No other test in the file needed a behavior-assertion change; `test_new_equipment_assignment_calls_mark_rented_and_creates_status_log` (the first-delivery happy path) and `test_bug4_...` (destructive-rebuild characterization, asserts nothing about equipment status) both continue to pass unmodified.

## Regression results

```
php artisan test --filter="SaveDeliveryControllerCharacterizationTest|SaveReturnControllerCharacterizationTest|RemoveControllerCharacterizationTest|ChecklistTransactionTest|CompletenessObservabilityLoggingTest|EquipmentStatus"
```
Result: **66 passed, 219 assertions, 0 failures** — includes both CustomerChecklists controller suites, the PR-A2 transaction and PR-A4 observability suites, and the broader `EquipmentStatusLogAdditionalPathsTest`/`UpdateProductScheduleEquipmentStatusLogTest` suites (other call sites of the shared `EquipmentStatusLog`/`EquipmentStatusService` machinery).

```
php artisan test --filter=MobileReturnCycleIdempotencyTest
```
Result: **3 passed, 20 assertions, 0 failures** — this suite specifically exercises delivering the *same* order product across two full rental cycles (delivery → return → re-delivery → re-return) and asserts exactly one damage + one fuel `BillingCharge` per cycle; it is the closest existing regression to BUG-2's exact re-delivery scenario from the billing side, and confirms the fix does not disturb billing idempotency.

## Production impact

- **Behavior change:** a re-delivery of previously-assigned equipment (same `equipment_id` as the order product's current assignment) now correctly transitions the equipment back to `'rented'` and writes an `equipment_status_logs` row, instead of silently leaving a stale status in place.
- **No change** to the HTTP response shape, status codes, billing, checklist-answer persistence, media uploads, or the surrounding transaction boundary.
- **No schema or migration changes.**
- **Risk:** low — the change only affects equipment already known (via the pre-existing `isRented()` guard) to be non-rented at the time of a successful delivery; it makes the equipment's `current_status` newly consistent with `is_delivered=true`/`delivery_status='Completed'`, closing exactly the data-integrity gap BUG-2 described, without altering any other guarded or gated behavior.

## Files changed

- `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveDeliveryController.php` (production fix — the `if` block split described above)
- `tests/Feature/CustomerChecklists/SaveDeliveryControllerCharacterizationTest.php` (BUG-2 test flipped to assert the fix; one new regression test added for the `maintenance`-status re-delivery path)
- `docs/checklist-system-audit/PHASE3_IMPLEMENTATION_PLAN.md` (P3-9 revision note; BUG-2 marked resolved)
- `docs/checklist-system-audit/P3_9_BUG2_REDELIVERY_STATUS_FIX.md` (new — this file)

## Verdict

**PASS.** BUG-2 is fixed with the smallest possible production-code change — one `if` block split into an unconditional statement and a narrower conditional — verified by flipping its own characterization test from asserting the bug to asserting the fix, adding one additional regression test for the more common `maintenance`-status re-delivery path, and re-running the full CustomerChecklists/equipment-status/billing-idempotency regression surface (69 tests total across the two filtered runs above, 0 failures). BUG-3, BUG-4, BUG-5, API work, and architecture work were not touched. Stopping here — not starting P3-10 or any other item.
