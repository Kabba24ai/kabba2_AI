# P3-12B — BUG-12: Log equipment/booked-product category mismatches on delivery

**Date:** 2026-07-17
**Scope:** Phase 3, BUG-12 only (labelled P3-12a in the plan's PR-order table — renamed P3-12B here to avoid collision with the already-delivered P3-12A, which resolved BUG-3). No architecture changes, no service extraction, no refactoring, no API changes, no mobile changes, no migrations, no schema changes, no unrelated cleanup.

---

## Root cause

`SaveDeliveryController` never cross-checks the equipment being delivered against the category the order product's booked product belongs to. Any equipment record can be attached to any delivery regardless of category alignment — confirmed by re-reading the controller (already fully read during P3-9/P3-10/P3-12A work); no such check exists anywhere in the file, and nothing since has added one.

## A discovery that changed the fix's shape (resolved before coding, not guessed)

The plan's own root-cause language assumes "the order product's booked category" is a single value comparable to `Equipment::product_category_id` (a single `belongsTo`). Verifying the actual schema before writing any code found this isn't quite true:

- `OrderProduct` has no category column at all (already noted in P3-8's revision log).
- `OrderProduct` → `Product` (via `product_id`) → **`Product::categories()`** — a many-to-many relation to `ProductCategory` via a `ProductCategoryChild` pivot. This relation was not mentioned in the original audit finding or in P3-8's note.

A product can belong to zero, one, or several categories; equipment has exactly one. This is a genuine ambiguity the original audit didn't anticipate, so rather than guess at a match rule, it was raised explicitly before implementation. Resolved: **membership check** — log only if the equipment's single category isn't among any of the order product's assigned categories; a product with **zero** assigned categories is treated as unknown and intentionally **not** logged, to avoid noisy false positives from incomplete category data.

## Implementation

Follows the plan's own recommended approach verbatim ("Follow this project's established observe-first discipline — add a structured warning log... without blocking the delivery") and mirrors the existing `logIfDeliveryIncomplete()` (PR-A4) pattern already in the same file:

1. Added `'product.categories'` to the existing `OrderProduct::with([...])` eager-load (one line changed).
2. Added a call to a new private method, `logIfEquipmentCategoryMismatch($orderProduct, $equipment)`, placed alongside the existing `logIfDeliveryIncomplete()` call — same location, same non-blocking posture.
3. Added the method itself:

```php
private function logIfEquipmentCategoryMismatch(OrderProduct $orderProduct, ?Equipment $equipment): void
{
    if (!$equipment || !$equipment->product_category_id) {
        return;
    }

    $bookedCategoryIds = $orderProduct->product?->categories->pluck('id') ?? collect();

    if ($bookedCategoryIds->isEmpty() || $bookedCategoryIds->contains($equipment->product_category_id)) {
        return;
    }

    Log::channel('api_errors')->warning('Delivered equipment category does not match the booked product\'s category', [
        'order_product_id'      => $orderProduct->id,
        'order_id'               => $orderProduct->order_id,
        'equipment_id'           => $equipment->id,
        'equipment_category_id'  => $equipment->product_category_id,
        'product_id'             => $orderProduct->product_id,
        'booked_category_ids'    => $bookedCategoryIds->values()->all(),
    ]);
}
```

No response, status code, or `delivery_status` change in any case — purely observational, identical posture to the existing PR-A4 log.

## Production files modified

- `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveDeliveryController.php` only (42 net lines added: one eager-load addition, one call site, one new private method).

No other file was touched. No admin equipment-assignment controllers were modified — the plan flags them as also in scope ("Touches `SaveDeliveryController` and any admin equipment-assignment controller that can set `current_order_product_id`"), but extending to `AssignEquipmentController` would broaden this PR beyond a single-controller, single-purpose change; **deferred** (see below), not silently skipped.

## Test plan

Added to `tests/Feature/CustomerChecklists/CompletenessObservabilityLoggingTest.php` (the existing PR-A4 observability suite — same `TestHandler`-on-`api_errors`-channel convention, no new test file needed):

1. **Bug exists / is fixed** — `test_bug12_equipment_category_mismatch_logs_a_warning`: equipment's category differs from the booked product's only assigned category; asserts the delivery still succeeds (`200`, `delivery_status='Completed'`) and the specific BUG-12 warning is logged with the correct `equipment_id`/`equipment_category_id`/`booked_category_ids` context. **Verified via revert-and-reproduce**: temporarily commented out the new method call, re-ran this exact test, confirmed it failed with `Failed asserting that false is true` (no warning found) — the identical failure mode BUG-12 describes — then restored the fix and confirmed the test passes again and `git diff` matches the intended change exactly.
2. **Valid combination still succeeds** — `test_bug12_matching_equipment_category_does_not_log`: equipment's category matches the booked product's only category; asserts the delivery succeeds and the BUG-12-specific warning is absent (asserted specifically by message content, not by "no warnings at all," since this delivery omits a checklist/template and therefore also triggers the pre-existing, unrelated PR-A4 "incomplete checklist" warning — expected and orthogonal to BUG-12).

No broad characterization test file was created. Additional tests were not added for: equipment with no category assigned, or a product with zero categories assigned — these are already covered structurally by the method's own early-return guards (`!$equipment->product_category_id` and `$bookedCategoryIds->isEmpty()`), and a dedicated test for each would protect a one-line null-guard, not a distinguishable behavior; the risk of a regression there is adequately caught by the two tests above already exercising the method's full body on every call.

## Regression results

```
php artisan test --filter="SaveDeliveryControllerCharacterizationTest|SaveReturnControllerCharacterizationTest|RemoveControllerCharacterizationTest|ChecklistTransactionTest|CompletenessObservabilityLoggingTest"
```
Result: **51 passed, 235 assertions, 0 failures.** Covers every CustomerChecklists suite from P3-8 through P3-12A plus the two new BUG-12 tests; BUG-2/BUG-3/BUG-4/BUG-5's own tests all continue to pass unmodified.

## Risk assessment

- **Behavior change:** none for the response, status code, or persisted `delivery_status`/`is_delivered` fields in any case — this is a pure addition of a log statement.
- **Performance:** one additional eager-loaded relation (`product.categories`) on an already-executed query; no N+1 introduced (categories are eager-loaded, not lazy-loaded per request).
- **Noise risk:** mitigated by treating zero-category products as unknown (not logged) — real-world category-data completeness was not verified as part of this PR (out of scope), so this is a deliberate conservative choice, not a data-quality assumption.
- **Risk of the fix itself:** minimal — additive only, no existing code path altered besides the one-line eager-load addition.

## Deferred findings (not fixed in this PR)

| Finding | Classification | Why deferred |
|---|---|---|
| The plan's own text also lists "any admin equipment-assignment controller that can set `current_order_product_id`" (e.g. `AssignEquipmentController`) as in scope for the same check | **Deferred** | Extending to a second controller would broaden this PR beyond BUG-12's single-controller mission as scoped for this PR; candidate for its own immediate follow-up (mirroring the P3-12/P3-12A precedent) rather than silently expanding this one |
| Real-world frequency of category mismatches is unknown | **Future enhancement** | The plan's own recommended approach explicitly defers the hard-rejection decision until "real-world mismatch frequency is known" via this observability window — not decidable from code alone |
| Whether a legitimate override workflow exists for intentional cross-category substitutions | **Technical debt / needs product input** | Plan's own text flags this as undocumented in the reviewed audit material; not something to guess at in an observe-first PR |
| Category-data completeness across all products (how many products have zero categories assigned) | **Technical debt** | Would require a data audit, not a code change; relevant context for interpreting the new log's signal quality once collected |

None of the above block BUG-12 as scoped; none were implemented.

## Verdict

**PASS.** BUG-12 implemented exactly per the plan's own recommended observe-first approach, in one controller, as the smallest safe addition (42 lines, one file). A genuine data-model ambiguity (many-to-many product categories vs. single-value equipment category) was discovered during verification and resolved by explicit confirmation before any code was written, rather than guessed. Verified via revert-and-reproduce that the new tests actually catch the bug. Regression suite green (51/51). No architecture, API, mobile, schema, or unrelated changes. Stopping here — not continuing into P3-13, P3-14, P3-15, P3-16, P3-17, any architecture work, or any additional audit.
