# PR-A1 Follow-up — Fixes Applied After Review

**Date:** 2026-07-06
**Depends on:** `PR-A1_REVIEW.md` (the review that found these issues) in this same folder.
**Code modified:** Yes — this document records the fixes actually applied. No code was written blind; every fix maps to a specific finding in `PR-A1_REVIEW.md`.

---

## 1. What this document is

`PR-A1_REVIEW.md` reviewed the original PR-A1 implementation and returned **CHANGES REQUIRED**, with one Critical finding, one High finding, and two Medium findings. This document records the fixes applied for the Critical, High, and one Medium finding, in two rounds, with the exact files changed and every test run.

---

## 2. Round 1 — Critical fix + the 3 missed scope sites

### Fix 2.1 — Critical: misplaced `persistStatusLog()` call

**Finding:** a concurrent merge (the "equipment wait list module" branch) had, during conflict resolution, placed the `persistStatusLog(...)` line meant for `markReturnedDamaged()` inside a different method, `evaluateWaitLists()`, where `$oldRaw`/`$actorId` were undefined variables. This crashed every mobile return that transitioned equipment to Maintenance or Damaged (`ErrorException: Undefined variable $oldRaw`, since `APP_DEBUG=true`).

**Fix:** moved the line back into `markReturnedDamaged()`, right after its `self::log(...)` call. Restored `evaluateWaitLists()` to contain only the wait-list try/catch it had before the merge.

**File:** `app/Services/Equipment/EquipmentStatusService.php`

**Proof:**
```
php artisan test --env=testing tests/Unit/Equipment/EquipmentStatusServiceLogTest.php
Before fix: 3 failed, 8 passed  (ErrorException: Undefined variable $oldRaw)
After fix:  11 passed
```

### Fix 2.2 — High: 3 missed `saveQuietly()` equipment-status paths

**Finding:** PR-A1's instructed scope was "fix all checklist/order equipment status paths using saveQuietly()," but implementation only covered `EquipmentStatusService` and `UpdateProductScheduleController`. Three more pre-existing sites were found via review agents and confirmed by direct grep:

| File | Line | Transition |
|---|---|---|
| `AssignEquipmentController.php` | 147 | → Rented (equipment assigned to an order) |
| `RemoveEquipmentController.php` | 45 | → Available (equipment removed from an order) |
| `Order.php` (`deleting` model hook) | 243 | → Maintenance (cascades to every product's equipment when an order is deleted) |

**Fix:** added the same pattern already established in PR-A1 to all three sites — capture `$beforeStatus` before mutation, `saveQuietly()`, then a guarded log write (`if ($from === $to) return;` before creating the row).

**Files:**
- `app/Http/Controllers/Admin/OrderManagement/Orders/AssignEquipmentController.php`
- `app/Http/Controllers/Admin/OrderManagement/Orders/RemoveEquipmentController.php`
- `app/Models/Orders/Order.php`

**New tests:** `tests/Feature/OrderManagement/EquipmentStatusLogAdditionalPathsTest.php` — 5 tests (one happy-path + one dedup-guard test for `RemoveEquipmentController`, one happy-path + one dedup-guard test for the `Order` deletion cascade, one happy-path test for `AssignEquipmentController`).

**Proof:**
```
php artisan test --env=testing tests/Feature/OrderManagement/EquipmentStatusLogAdditionalPathsTest.php
→ 5 passed (10 assertions)
```

### Round 1 combined result

```
php artisan test --env=testing \
  tests/Unit/Equipment/EquipmentStatusServiceLogTest.php \
  tests/Feature/OrderManagement/UpdateProductScheduleEquipmentStatusLogTest.php \
  tests/Feature/OrderManagement/EquipmentStatusLogAdditionalPathsTest.php
→ 24 passed (40 assertions)

php artisan test --env=testing tests/Feature/WaitList
→ 15 passed (81 assertions)   — regression check: confirms restoring evaluateWaitLists()
                                 didn't break the concurrent wait-list feature whose merge
                                 caused the original bug.
```

**Total after Round 1: 39/39 passing.**

**Not done in Round 1 (by instruction):** PR-A2 was not started; no unrelated refactoring was performed. The Medium findings (duplicated helper method, no transaction around the new write) were left as-is for a later pass.

---

## 3. Round 2 — Medium fix: consolidate the duplicated logging helper

**Finding (M1 in `PR-A1_REVIEW.md`):** after Round 1, the same `persistStatusLog()`/`persistEquipmentStatusLog()` logic (identical guard, identical `EquipmentStatusLog::create()` field mapping) existed as **5 separate copy-pasted private methods** across 5 classes. The review called this out explicitly as a real hazard, not a style nit — the Round-1 critical bug had landed in exactly one of these independent copies during a merge, which is direct evidence that duplicated logic like this is where this kind of mistake happens.

**Fix:** added one shared public static method, `EquipmentStatusLog::recordTransition(int $equipmentId, ?string $from, string $to, ?int $actorId)`, on the `EquipmentStatusLog` model itself (the review's own suggested location). Replaced all 14 call sites across 5 classes to call it directly, and deleted all 5 private duplicate methods along with their now-unused `Equipment` model imports.

**Files:**
- `app/Models/ChecklistManagement/EquipmentChecklist/EquipmentStatusLog.php` — added `recordTransition()`.
- `app/Services/Equipment/EquipmentStatusService.php` — 7 call sites repointed; `persistStatusLog()` deleted; stale docblock corrected.
- `app/Http/Controllers/Admin/OrderManagement/Orders/UpdateProductScheduleController.php` — 5 call sites repointed; `persistEquipmentStatusLog()` deleted; unused `Equipment` import removed.
- `app/Http/Controllers/Admin/OrderManagement/Orders/AssignEquipmentController.php` — 1 call site repointed; `persistEquipmentStatusLog()` deleted.
- `app/Http/Controllers/Admin/OrderManagement/Orders/RemoveEquipmentController.php` — 1 call site repointed; `persistEquipmentStatusLog()` deleted; unused `Equipment` import removed.
- `app/Models/Orders/Order.php` — 1 call site repointed; `persistEquipmentStatusLog()` deleted; unused `Equipment` import removed.

No test files were changed — the existing tests assert on database state (`equipment_status_logs` rows), not on which method wrote them, so they exercise the new shared path without modification.

**Verification that no dead references remain:**
```
grep -rn "persistStatusLog|persistEquipmentStatusLog" app/
→ only 1 hit: a docblock comment in EquipmentStatusLog.php referencing the old
  method name for historical context (explaining why recordTransition exists)
```

**Proof:**
```
php -l  (all 6 touched files)                        → no syntax errors

php artisan test --env=testing \
  tests/Unit/Equipment/EquipmentStatusServiceLogTest.php \
  tests/Feature/OrderManagement/UpdateProductScheduleEquipmentStatusLogTest.php \
  tests/Feature/OrderManagement/EquipmentStatusLogAdditionalPathsTest.php
→ 24 passed (40 assertions)   — unchanged from Round 1

php artisan test --env=testing tests/Feature/WaitList
→ 15 passed (81 assertions)   — unchanged from Round 1
```

**Total after Round 2: 39/39 passing, same as Round 1 — the refactor is behavior-preserving.**

---

## 4. Current state of PR-A1 vs. the original review

| # | Item from `PR-A1_REVIEW.md` §6 checklist | Status now |
|---|---|---|
| 1 | Every `saveQuietly()` path covered | **Fixed** — 3 gaps closed in Round 1 |
| 2 | No duplicate log rows possible | **Fixed** — Critical bug fixed in Round 1 |
| 3 | `EquipmentObserver` behavior preserved | Unchanged — still fine |
| 4 | No new event recursion | Unchanged — still fine |
| 5 | No transaction inconsistencies | **Still open** — explicitly deferred to PR-A2 per the roadmap, not addressed here |
| 6 | No N+1/unnecessary DB writes | Unchanged — the one minor instance noted (`RemoveController` foreach) was Low severity, no action needed |
| 7 | Matches approved roadmap | **Fixed** — the 3-file scope gap is closed |
| 8 | Tests prove the fix | **Yes** — 39/39 across both rounds |
| 9 | Duplicated helper (M1) | **Fixed** — consolidated into `EquipmentStatusLog::recordTransition()` in Round 2 |
| 10 | Approve or request changes | Ready for a fresh review pass |

**Deliberately not addressed (out of scope for this pass, by instruction):** M2 — no transaction/try-catch around the `EquipmentStatusLog::create()` write. This remains correctly scheduled for PR-A2 ("DB transaction wrapping") per `IMPLEMENTATION_ROADMAP.md`.

---

## 5. Full file change list (both rounds combined)

```
app/Models/ChecklistManagement/EquipmentChecklist/EquipmentStatusLog.php   (new recordTransition() method)
app/Services/Equipment/EquipmentStatusService.php                          (bug fix + consolidation)
app/Http/Controllers/Admin/OrderManagement/Orders/UpdateProductScheduleController.php  (consolidation)
app/Http/Controllers/Admin/OrderManagement/Orders/AssignEquipmentController.php        (new coverage + consolidation)
app/Http/Controllers/Admin/OrderManagement/Orders/RemoveEquipmentController.php        (new coverage + consolidation)
app/Models/Orders/Order.php                                                (new coverage + consolidation)
tests/Feature/OrderManagement/EquipmentStatusLogAdditionalPathsTest.php    (new, 5 tests)
```

## 6. Full test log (final state)

```
php artisan test --env=testing \
  tests/Unit/Equipment/EquipmentStatusServiceLogTest.php \
  tests/Feature/OrderManagement/UpdateProductScheduleEquipmentStatusLogTest.php \
  tests/Feature/OrderManagement/EquipmentStatusLogAdditionalPathsTest.php \
  tests/Feature/WaitList

Tests: 39 passed (121 assertions)
```

No code has been committed as of this document. Nothing beyond the scope described above was touched (no PR-A2 work, no unrelated refactoring).
