# P3-1 / BUG-11 — Rental Ready IndexController null-guard fix

**Date:** 2026-07-15
**Scope:** Phase 3, Sprint 1, first item only. No other Sprint 1 items (SEC-1, DB-2, cleanup) were started.

---

## Root cause

`app/Http/Controllers/Api/Admin/V1/RentalReadyChecklists/IndexController.php`, in the branch handling equipment that has an order product:

```php
$questions = optional($equipment->orderProduct->equipmentRentalReadyTemplate?->checklistQuestions)
            ->pluck('rental_ready_qa_json')
            ->filter()
            ->values() ?? collect();
```

`optional()` only proxies the **first** method call in a chain. When `equipmentRentalReadyTemplate` is `null` (no prior order-scoped Rental Ready inspection has ever been recorded for this order product), `optional(null)->pluck(...)` correctly returns `null` — but the chain doesn't stop there. `->filter()` is then called directly on that plain `null` return value, which is not wrapped by `optional()`, producing:

```
Call to a member function filter() on null
```

The trailing `?? collect()` never runs, because the exception is thrown before the expression finishes evaluating — a null-coalesce on the right of a chain doesn't catch an error thrown mid-chain.

This is a real, reachable production state: any equipment order-product row with no `EquipmentRentalReadyTemplate` yet created against it (e.g. a rental just started, no inspection performed) hits this exact path.

## Fix

Replaced the `optional(...)->method()` pattern with `collect(...)` directly on the relation value:

```php
$questions = collect($equipment->orderProduct->equipmentRentalReadyTemplate?->checklistQuestions)
            ->pluck('rental_ready_qa_json')
            ->filter()
            ->values();
```

`collect(null)` returns an empty `Collection`, so every subsequent call (`pluck`, `filter`, `values`) is safe regardless of whether the relation resolved. When the relation *is* present, `collect()` on an existing `Collection` behaves identically to before (no behavior change for valid records).

This is the smallest possible change — one line, no restructuring of the surrounding branch, loop, or response logic.

## Response for the missing-template case

No new response envelope was invented. With the null guard in place, a missing `equipmentRentalReadyTemplate` now simply yields `$questions` as an empty collection, which flows straight into the **existing** empty-questions check a few lines below (already present in the code prior to this fix, and already used by the sibling checklistMaster-only branch):

```php
if ($questions->isEmpty()) {
    return response()->json([
        'success' => false,
        'message' => trans('messages.api.admin.v1.rental_ready_checklists.no_questions_found'),
    ], JsonResponse::HTTP_NOT_FOUND);
}
```

Result: `404` with the existing `no_questions_found` message (`"No questions found for the rental ready checklist."`) — the same convention already used elsewhere in this exact controller for "nothing to return," not a new pattern.

## Files changed

- `app/Http/Controllers/Api/Admin/V1/RentalReadyChecklists/IndexController.php` — one-line null-guard fix (`optional(...)` → `collect(...)`), plus an explanatory comment referencing this document. No other lines in the controller were touched.
- `tests/Feature/RentalReadyChecklists/Bug11NullGuardCharacterizationTest.php` — new characterization/regression test file (4 tests).

No migrations, no schema changes, no other controllers/services touched.

## Before / after behavior

| Scenario | Before | After |
|---|---|---|
| Order product **with** a valid `EquipmentRentalReadyTemplate` | 200, questions returned | 200, questions returned (unchanged) |
| Order product **with no** `EquipmentRentalReadyTemplate` | **500 — uncaught `Error`: "Call to a member function filter() on null"** | 200-path avoided the crash; falls through to the existing empty-questions check → **404** `no_questions_found` |
| Available equipment, no previous Rental Ready result (checklistMaster path, no order product) | 200, `equipment_rental_ready: null`-equivalent (zeroed resource), questions from checklistMaster's template | Unchanged |
| Currently-rented equipment with order product + template (PR-B3 observability) | 200, `api_errors` warning logged, questions returned | Unchanged |

No behavior changed for any previously-working (non-crashing) request.

## Tests added

`tests/Feature/RentalReadyChecklists/Bug11NullGuardCharacterizationTest.php`:

1. `test_order_product_with_valid_rental_ready_template_returns_questions` — happy path, order product + real `EquipmentRentalReadyTemplate`.
2. `test_order_product_with_no_rental_ready_template_does_not_throw` — the BUG-11 case; confirmed to reproduce the original 500 against the pre-fix code (verified manually via `git stash` of the controller change), and to return `404`/`no_questions_found` against the fixed code.
3. `test_available_equipment_with_no_previous_result_returns_null_rental_ready_data` — checklistMaster-only path, no order product, no prior inspection.
4. `test_rented_equipment_with_order_product_and_template_still_logs_and_succeeds` — regression for the existing PR-B3 observability-only rented-equipment logging behavior.

## Exact commands run

```
php artisan test --filter=Bug11NullGuardCharacterizationTest
php artisan test --filter="Bug11NullGuardCharacterizationTest|ValidationGuardObservabilityTest|SaveControllerCharacterizationTest"
```

Plus a manual regression-proof step: `git stash push -- app/Http/Controllers/Api/Admin/V1/RentalReadyChecklists/IndexController.php`, re-ran test #2 alone to confirm it reproduces `Call to a member function filter() on null` against the original code, then `git stash pop` to restore the fix.

## Pass/fail counts

- New test file alone: **4 passed**, 12 assertions.
- Combined with the existing PR-B3 Rental Ready regression suites (`ValidationGuardObservabilityTest`, `SaveControllerCharacterizationTest`): **14 passed**, 95 assertions, 0 failures.
- Pre-fix reproduction run (controller temporarily reverted): confirmed 1 failure with the exact original error message, proving the test is a true characterization of the bug.

## Ready for review

**Yes.** Root cause identified and fixed with a single-line null-guard change, no unrelated refactoring, existing API conventions preserved for both the success and missing-template cases, and full regression coverage passing. Stopping here per instructions — no other Sprint 1 items (SEC-1, DB-2, cleanup) started.
