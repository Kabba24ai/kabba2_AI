# P3-5 — Technical debt cleanup batch (TD-3, TD-4, TD-6, TD-7, TD-9, TD-13, TD-14, TD-15)

**Date:** 2026-07-15
**Scope:** Phase 3, Sprint 1, fifth item only. No other Sprint 1/Phase 3 item started.
**Split:** not needed — the implemented changes (4 items) stayed small and independent enough for one batch; no P3-5A/B split required.

---

## Disposition summary

| Item | Disposition | Reason |
|---|---|---|
| TD-3 | **Implemented** | Confirmed unused everywhere; safe mechanical refactor |
| TD-4 | **Implemented** | Documentation-only, zero behavior risk |
| TD-6 | **Implemented** | Documentation-only, zero behavior risk |
| TD-7 | **Deferred** | Requires a product decision (remove vs. build a real consumer) |
| TD-9 | **Deferred** | Plan's own recommendation says not to add until a real consumer need emerges — architectural, not cleanup |
| TD-13 | **Deferred** | Investigation found a deeper root cause than the plan described; proper fix requires redesigning the test's failure-simulation technique |
| TD-14 | **Deferred** | Requires migrating multiple existing call sites to a new shared pattern — a real refactor, not small-effort cleanup |
| TD-15 | **Implemented** | Confirmed purely cosmetic; safe mechanical simplification |

---

## Implemented

### TD-3 — Fragile `hasOneThrough` relation hard-wired to raw column names

**Investigation:** `Equipment::customerAdminTemplates()` used a raw `hasOneThrough(CustomerAdminTemplate::class, ChecklistMaster::class, 'id', 'id', 'checklist_master_id', 'customer_admin_template_id')` — every key duplicated as a string literal instead of composing from the already-existing `checklistMaster()` relation. Grepped the entire codebase for any caller — **confirmed unused everywhere** except its own definition, so this was a zero-risk refactor.

**Fix:** replaced with `return $this->checklistMaster?->customerAdminTemplate;`, composing from the existing `checklistMaster()` relation (on `Equipment`) and `customerAdminTemplate()` relation (on `ChecklistMaster`, itself cleaned up in P3-4/CLEAN-6). A future rename of either FK column now breaks at the relation-method level (visible in code review / IDE navigation) instead of silently returning wrong data at query time. Return type changed from an `Eloquent\Relations\HasOneThrough` builder to a plain `?CustomerAdminTemplate` — a real signature change, but safe given zero existing callers.

**Files edited:** `app/Models/MaintenanceManagement/Equipment.php`
**Tests added:** `tests/Unit/Models/EquipmentCustomerAdminTemplateTest.php` (3 tests: resolves through the full chain; returns null with no checklist master; returns null with a checklist master that has no customer admin template)

### TD-4 — `dispatch_checklist` naming collision

**Investigation:** confirmed `order_products.dispatch_checklist` (driver pre-delivery SOP: customer contact, keys, fuel) is read/written only by `Dispatch\ShowController` (5 usage sites total) and has zero relation to `ChecklistMaster`/templates. A real column rename would need a migration touching live production data — out of scope for this batch per "do not change business behavior." Took the plan's explicitly offered lower-risk alternative: **document, don't rename.**

**Fix:** strengthened the existing one-line `$fillable` comment into a full explanatory block, and added a matching docblock note on `ShowController::saveChecklist()`, both explicitly stating this has no relation to the checklist-template system.

**Files edited:** `app/Models/Orders/OrderProduct.php`, `app/Http/Controllers/Admin/OrderManagement/Dispatch/ShowController.php`

### TD-6 — `OrderProductObserver` auto-assign coupling

**Investigation:** confirmed the auto-assign gate (`delivery_status`/`pickup_status !== 'Reschedule'`) is implicitly coupled to fields the checklist Save controllers also write. The plan's own recommended solution is "Document; revisit if TD-1/ARCH-2 changes these fields' write path" — explicitly documentation-only, no logic change intended.

**Fix:** added a comment at the exact gate explaining the coupling and pointing to the future TD-1/ARCH-2 dependency.

**Files edited:** `app/Observers/OrderProductObserver.php`

### TD-15 — Duplicated `$old`/`$oldRaw` derivation across all 7 `EquipmentStatusService` methods

**Investigation:** confirmed all 7 methods (`markRented`, `markReturnedToMaintenance`, `markReturnedDamaged`, `markAvailableOnChecklistRemove`, `markAvailableFromRentalReady`, `markMaintenanceFromRentalReady`, `markDamagedFromRentalReady`) independently computed:
```php
$old    = $equipment->current_status?->value ?? 'unknown';
$oldRaw = $equipment->current_status?->value;
```
— the same underlying value read twice and only conditionally defaulted the second time. Purely cosmetic duplication with no behavior difference between the two orderings.

**Fix:** reordered each pair to derive `$old` from `$oldRaw` instead of re-reading the property: `$oldRaw = ...; $old = $oldRaw ?? 'unknown';` — identical resulting values, one less duplicated expression per method.

**Files edited:** `app/Services/Equipment/EquipmentStatusService.php`

---

## Deferred (with reasons)

### TD-7 — `BillingChargeCreatedEvent` has zero registered listeners

**Investigation:** confirmed `BillingEngine::charge()` fires `event(new BillingChargeCreatedEvent($charge))` after every successful charge, and confirmed via grep across `app/Providers/` and `app/Listeners/` that **no listener is registered anywhere** — this event is dispatched into the void today.

**Why deferred:** the event is explicitly documented in `BillingEngine::charge()`'s own docblock as fired "after the transaction commits" — a deliberate, already-built extension point, not an accident. Whether to (a) remove it as dead weight, or (b) build the finance-facing audit-trail consumer the plan speculates about, is a product/business decision about what BillingEngine's event surface should look like — not a pure code-cleanup call. Removing a documented extension point without confirming nothing is being planned against it risks silently breaking a future integration.

### TD-9 — No domain event for equipment status changes

**Investigation:** confirmed `EquipmentStatusService` mutates status via direct method calls (see TD-15 above) with no `Event` class dispatched anywhere in the chain.

**Why deferred:** the plan's own recommended solution states this explicitly: "Consider a lightweight `EquipmentStatusChanged` event for extensibility, **only if a real consumer need emerges; not urgent on its own**." Building unused extensibility now would be speculative architecture, not debt cleanup, and directly contradicts the plan's own guidance.

### TD-13 — Pre-existing Mockery test flake

**Investigation — this went deeper than the plan's description and found a different, more precise root cause:**

Reproduced `MobileReturnFuelBridgeTest::test_billing_engine_failure_is_logged_to_billing_engine_channel` in isolation 3 times — it failed **deterministically all 3 times**, not intermittently, with the exact "should be called exactly 1 times but called 2 times" error the plan describes. (The plan's "intermittent" framing was likely based on runs that also hit unrelated MySQL connection-exhaustion errors when many test classes ran back-to-back — confirmed separately that running the same filter across 8 sibling test classes at once produces `SQLSTATE[HY000] [2002] No connection could be made` failures unrelated to this specific issue.)

Traced the actual call chain with real (non-mocked) log handlers and Mockery argument-capture probes (not guessing from the assertion message alone):
- `SaveReturnController::__invoke()` wraps its entire body in `DB::transaction()`.
- `BillingEngine::charge()` wraps its own logic in a **nested** `DB::transaction()` (uses a MySQL SAVEPOINT).
- The test calls `Schema::drop('billing_charges')` — a DDL statement — **while already inside a transaction** (both PHPUnit's `RefreshDatabase` outer transaction and the two application-level nested transactions above it).
- **MySQL DDL statements always issue an implicit `COMMIT`.** Dropping the table mid-transaction silently commits everything Laravel's PHP-side transaction-nesting counter still believes is open, desynchronizing that counter from the database's actual state.
- Once desynchronized, the next `DB::transaction()` call (inside `BillingEngine::charge()`, triggered by the fuel-charge bridge in `SaveReturnController`) tries to create/rollback a savepoint that MySQL no longer recognizes — reproduced this exact failure directly: `SQLSTATE[42000]: ... 1305 SAVEPOINT trans2 does not exist`, and separately reproduced the "logged 2 times" outcome, in different runs with slightly different mock setups. The *specific* symptom (a savepoint error crashing the test outright, vs. the log-call-count mismatch) depends on exactly which nested-transaction operation the corrupted counter affects first — which is sensitive to subtle state, consistent with why this was originally perceived as "intermittent."

**Conclusion:** this is not a simple "add `atLeast()` to the mock" fix, as the plan speculated. The actual defect is in the **test's method of simulating a database failure** — using `Schema::drop()` (DDL, implicit commit) inside a nested-transaction code path is fundamentally incompatible with Laravel's transaction-nesting bookkeeping. **Why deferred:** the correct fix is to redesign how this test (and any sibling tests using the same `Schema::drop()`-before-request pattern — there appear to be several, based on the sibling classes seen during investigation) simulates a billing failure — e.g., partial-mocking the `BillingCharge` model or query builder to throw, instead of dropping a live table mid-request. That is a test-architecture change touching a shared pattern across multiple test classes, not a small isolated fix, and risks introducing new flakiness in the sibling tests if done hastily. Recommend a dedicated, carefully-scoped follow-up armed with this root-cause writeup rather than a quick patch here.

### TD-14 — No atomic `saveQuietly()` + audit-log helper

**Investigation:** confirmed the pattern (`$equipment->saveQuietly()` followed by a separate `EquipmentStatusLog::recordTransition()` call) repeats across all 7 `EquipmentStatusService` methods (see TD-15) **and**, per the service class's own docblock, across `UpdateProductScheduleController`, `AssignEquipmentController`, `RemoveEquipmentController`, and `Order`'s deleting hook — at least 4 additional call sites outside this service.

**Why deferred:** the plan's own recommended solution ("Add a shared `Equipment::transitionStatusQuietly($to, $actorId)` model method/trait") requires **migrating every one of those existing call sites** to the new pattern to actually close the gap — doing it in only `EquipmentStatusService` and leaving the other 4+ call sites on the old two-step pattern would not fix the underlying risk (a 9th call site could still be added without logging) and would leave the codebase in a worse, inconsistent state (two competing patterns). A real, multi-file migration is exactly the kind of broader change this batch's rules ask to defer rather than rush.

---

## Exact tests run

```
php artisan test --filter=EquipmentCustomerAdminTemplateTest
php artisan test --filter="EquipmentCustomerAdminTemplateTest|EquipmentStatusServiceLogTest|EquipmentStatusLogAdditionalPathsTest|UpdateProductScheduleEquipmentStatusLogTest|EquipmentWaitListTest|ChecklistManagement|SaveControllerCharacterizationTest|StoreControllerObservabilityTest"
```

(TD-4 and TD-6 are comment-only changes to files with no existing test coverage of the affected lines' behavior — verified via `git diff` that both changes touch only comments, no logic, so no test run was needed to prove no regression for those two specifically.)

## Pass/fail counts

- TD-3 new test file alone: **3 passed**, 4 assertions.
- Combined final regression (TD-3 + TD-15 + all touched/adjacent suites): **166 passed, 671 assertions, 0 failures.**

## Files changed

**Edited:**
- `app/Models/MaintenanceManagement/Equipment.php` (TD-3)
- `app/Models/Orders/OrderProduct.php` (TD-4, comment only)
- `app/Http/Controllers/Admin/OrderManagement/Dispatch/ShowController.php` (TD-4, comment only)
- `app/Observers/OrderProductObserver.php` (TD-6, comment only)
- `app/Services/Equipment/EquipmentStatusService.php` (TD-15)
- `docs/checklist-system-audit/PHASE3_IMPLEMENTATION_PLAN.md` (BUG-15 added per prior instruction, before P3-5 began)

**Added:**
- `tests/Unit/Models/EquipmentCustomerAdminTemplateTest.php`
- `docs/checklist-system-audit/P3_5_TECHNICAL_DEBT.md` (this file)

No migrations. No production data changed. No public API behavior changed — TD-3's return-type change is on an internal model method confirmed to have zero external callers (not part of any API response, controller, or resource).

## Deferred items and reasons (recap)

- **TD-7** — needs a product decision (remove the dead event vs. build a real consumer).
- **TD-9** — the plan's own recommendation says not to build this until a real need emerges.
- **TD-13** — deeper root cause found (nested-transaction/DDL-implicit-commit corruption); proper fix is a test-architecture redesign, not a quick patch.
- **TD-14** — requires migrating 4+ existing call sites outside the file touched here; a real refactor, not small-effort cleanup.

## Ready for review

**Yes.** All four implemented items are small, independently verified safe (three by direct investigation showing zero/comment-only impact, one by a new passing test), and the full regression suite is green. All four deferred items have a documented, specific reason tied to this batch's own rules (no product/architectural decisions made unilaterally). TD-13's investigation materially corrects the plan's prior diagnosis and should inform whoever picks up that follow-up. Stopping here — no P3-6 started.
