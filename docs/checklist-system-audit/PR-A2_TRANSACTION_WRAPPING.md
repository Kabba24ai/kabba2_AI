# PR-A2 — DB Transaction Wrapping

**Date:** 2026-07-06
**Depends on:** `PR-A1_REVIEW.md` / `PR-A1_FOLLOWUP.md` (PR-A1, now closed) and `CORRECTION_PHASE1_PLAN.md` Issue #4 in this same folder.
**Code modified:** Yes.
**Scope:** exactly as instructed — wrap the 4 named controllers' write sequences in `DB::transaction()`. Billing idempotency (Issue #1) was explicitly not touched. PR-A3 was not started.

---

## 1. The problem this closes

Per `PHASE2_DEPENDENCY_RUNTIME_AUDIT.md` §5 and `CORRECTION_PHASE1_PLAN.md` Issue #4: none of the checklist controllers wrapped their write sequence in a transaction, and their event listeners run synchronously, inline, before the HTTP response returns. If a listener threw (e.g. a bad enum value, a DB blip), the exception propagated into a 500 **after** the `OrderProduct` update and `EquipmentStatusService` status change had already committed — the client saw total failure, but the data change and equipment status change had already happened, with only the audit-log (`OrderHistory`) entry silently missing.

## 2. The fix

Wrapped the entire write sequence of each controller — including the `event()` dispatch, since Laravel's default listeners are synchronous and execute inline — in a single `DB::transaction()` closure. If any statement inside throws (including inside a listener), the transaction rolls back everything: checklist rows, the `OrderProduct` update, and the equipment status change (and its `EquipmentStatusLog` row from PR-A1) together. The client still gets a 500 in that case, but it's now an *accurate* 500 — nothing partially happened.

**Approach used:** rather than surgically extracting only the "write" portion of each method, the entire method body (including the early guard-clause `return response()->json(...)` calls that run before any write) was wrapped in one `DB::transaction(function () use (...) { ... });`, and the outer method does `return DB::transaction(...)`. This was deliberate:
- It required zero restructuring of existing control flow — every original `return` statement stays exactly where it was, now just returning from the closure instead of the outer method.
- Guard-clause-only requests (404/409 responses, no writes) pay the cost of an open+immediate-commit transaction, which is negligible and standard practice — far lower risk than hand-splitting each method into "read-only guards" vs. "writes."
- This is the same pattern implicitly endorsed by `CORRECTION_PHASE1_PLAN.md`'s own phrasing: "wrap each controller's write sequence — including the `event()` dispatch."

## 3. Files changed

| File | Change |
|---|---|
| `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveDeliveryController.php` | Entire body wrapped in `DB::transaction()`. No logic changed. |
| `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/SaveReturnController.php` | Entire body wrapped in `DB::transaction()`. The existing `try/catch` blocks around `BillingEngine::charge()` calls were left exactly as-is (billing idempotency is explicitly out of scope for this PR) — those calls already swallow their own failures and log to the `billing_engine` channel, so they don't interact with the new transaction boundary. |
| `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/RemoveController.php` | Entire body wrapped in `DB::transaction()`. Note: `MediaHelper::removeFile()`/`forceDelete()` on delivery media still delete physical files as a side effect that a transaction rollback cannot undo — same accepted trade-off `CORRECTION_PHASE1_PLAN.md` already documented for signature uploads (an orphaned file with no DB reference is inert; not engineered around here). |
| `app/Http/Controllers/Api/Admin/V1/Orders/Schedules/DriverChecklistController.php` | The write sequence (previously a bare `try { ... }` with no transaction) now runs inside `DB::transaction()` nested within the existing `try/catch(\Throwable)`. **Also added exception logging** — the catch block previously swallowed every exception with no trace at all (a documented finding in `PHASE2_DEPENDENCY_RUNTIME_AUDIT.md` §5); it now logs `Log::channel('equipment_status')->error(...)` with the order product id, exception message, and exception class before returning the same `{status:false}` response as before (response shape unchanged, per instruction not to change behavior). |

No UI was touched. No files outside this list were modified.

## 4. New test file

`tests/Feature/CustomerChecklists/ChecklistTransactionTest.php` — 7 tests:

1. `test_happy_path_delivery_still_commits` — delivery still completes normally: `is_delivered=true`, equipment → rented, `EquipmentStatusLog` row exists, `order_histories` row exists.
2. `test_happy_path_return_still_commits` — return still completes normally: `is_returned=true`, equipment → maintenance, log rows exist.
3. `test_forced_listener_failure_rolls_back_delivery_checklist_order_product_and_equipment_status` — forces `OrderCustomerChecklistListener`'s event to throw; asserts `is_delivered` stays `false`, `delivery_status` isn't `Completed`, `equipment_id` stays `null`, equipment stays `available`, and **zero** `EquipmentStatusLog` rows exist — i.e. a full rollback, not a partial commit.
4. `test_forced_listener_failure_rolls_back_return_checklist_order_product_and_equipment_status` — same shape for the return flow; equipment correctly stays `rented` (its pre-return state) rather than advancing to `maintenance`.
5. `test_driver_checklist_controller_forced_failure_rolls_back_and_logs` — forces `OrderProductDriverChecklistUpdated`'s listener to throw; asserts the `delivery_equipment_fuel` field write rolled back and the response is the expected `{status:false}` 500.
6. `test_driver_checklist_controller_happy_path_still_commits` — confirms the happy path is unaffected by the new transaction/logging.
7. `test_remove_controller_happy_path_still_commits` — basic happy-path coverage for the 4th wrapped controller.

### A note on the "forced listener failure" technique

The first attempt used `Schema::drop('order_histories')` before the request — the same technique already used elsewhere in this codebase's test suite (`CrmDamageChargeBridgeTest`'s `Schema::drop('billing_charges')`). **This produced false negatives**: all 3 rollback tests failed, showing the writes committed anyway despite the forced failure.

**Root cause diagnosed during this session:** `DROP TABLE` is DDL, which causes MySQL to implicitly commit any active transaction at the moment it runs. Since `RefreshDatabase` wraps each test in its own outer transaction, dropping a table mid-test silently ends that outer transaction and desyncs Laravel's internal transaction-nesting counter from the database's real state. The `DB::transaction()` call made later, inside the HTTP request, believed it was creating a real transaction, but timing/state was already corrupted — the writes it made were not actually protected by a rollback-able transaction.

**Fix:** switched to registering an additional throwing listener via `Event::listen(EventClass::class, fn() => throw new \RuntimeException(...))`. This is a pure in-memory substitution — no DDL, no transaction-state corruption — and still exercises a completely real code path: an event with two listeners, one of which throws during the same synchronous dispatch the controller relies on. This is now the more reliable pattern; worth using this technique (not `Schema::drop()`) for any future test that needs to verify transaction rollback specifically, and reserving `Schema::drop()` for tests that only need to verify a failure is *isolated/swallowed* elsewhere (as the existing billing tests do).

## 5. Full test results

```
php -l  (all 4 touched controllers)                          → no syntax errors

php artisan test --env=testing tests/Feature/CustomerChecklists/ChecklistTransactionTest.php
→ 7 passed (36 assertions)

php artisan test --env=testing \
  tests/Unit/Equipment/EquipmentStatusServiceLogTest.php \
  tests/Feature/OrderManagement/UpdateProductScheduleEquipmentStatusLogTest.php \
  tests/Feature/OrderManagement/EquipmentStatusLogAdditionalPathsTest.php \
  tests/Feature/CustomerChecklists/ChecklistTransactionTest.php
→ 31 passed (76 assertions)          — PR-A1 suite unaffected by PR-A2's changes

php artisan test --env=testing tests/Feature/BillingEngine
→ 202 passed, 1 failed (408 assertions)
   The 1 failure (MobileReturnFuelBridgeTest > billing engine failure is logged to
   billing engine channel — a Mockery call-count assertion) is the SAME pre-existing,
   unrelated failure already confirmed in PR-A1_REVIEW.md by re-running it against
   pre-PR-A1 code. Not caused by this PR — SaveReturnController's billing logic was
   moved into the transaction closure verbatim, with no change to its try/catch
   or BillingEngine calls.
```

**Total: 31/31 new/PR-A1 tests passing, 202/203 BillingEngine regression tests passing (1 pre-existing unrelated failure, unchanged from before this PR).**

## 6. What was deliberately not done

- **Billing idempotency (Issue #1)** — untouched, as instructed. `SaveReturnController`'s damage/fuel charge logic is unchanged.
- **PR-A3** — not started.
- **UI** — not touched.
- **DriverChecklistController's response envelope** (`{status,message}` vs. the `{success,message}` used elsewhere) — left as-is; this is a separate, already-tracked Low-priority cleanup item in `IMPLEMENTATION_ROADMAP.md` (PR-C5), not part of this PR's scope.
- **`MediaHelper` file-upload/delete side effects inside the transaction** — left as an accepted, explicitly-documented trade-off (see §3), matching the precedent already set in `CORRECTION_PHASE1_PLAN.md`.

No code has been committed as of this document.
