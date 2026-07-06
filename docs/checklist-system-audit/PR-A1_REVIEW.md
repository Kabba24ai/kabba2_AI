# PR-A1 Review — equipment_status_logs / saveQuietly() Fix

**Date:** 2026-07-06
**Reviewed by:** Claude (code-review skill, 5 independent finder agents + direct empirical verification)
**Code modified during this review:** No. This is a review-only document — no fixes were applied.
**Verdict:** **CHANGES REQUIRED — do not merge as-is**

---

## 1. Background

`PR-A1` was the first implementation task out of `IMPLEMENTATION_ROADMAP.md`, itself synthesized from a multi-phase audit of the checklist system (`CHECKLIST_SYSTEM_AUDIT.md`, `PHASE2_DEPENDENCY_RUNTIME_AUDIT.md`, `PHASE3_RESULTS.md`, `CORRECTION_PHASE1_PLAN.md`, `ISSUE5_INVESTIGATION_FINDINGS.md`).

**The bug PR-A1 was meant to fix:** `EquipmentStatusService` and (per a later investigation) `UpdateProductScheduleController` change `Equipment.current_status` via `Equipment::saveQuietly()`. `saveQuietly()` suppresses all Eloquent model events — including `EquipmentObserver`, which is the *only* code that writes to the dedicated `equipment_status_logs` audit table. Result: that table has been silently, 100%-reliably empty for every mobile-driven and admin-driven status transition that goes through these two files, confirmed by runtime testing in `PHASE3_RESULTS.md`.

**The fix implemented:** add an explicit `EquipmentStatusLog::create()` call immediately after every `saveQuietly()` call in those two files, guarded so a row is only written when the status actually changed (mirroring `EquipmentObserver`'s own `isDirty()` check). Deliberately did **not** change `saveQuietly()` to `save()` (that would re-enable all Eloquent events, an unbounded blast radius).

**Files changed:**
- `app/Services/Equipment/EquipmentStatusService.php` — added `persistStatusLog()` helper, called from all 7 transition methods.
- `app/Http/Controllers/Admin/OrderManagement/Orders/UpdateProductScheduleController.php` — added `persistEquipmentStatusLog()` helper, called from 5 of its 6 `saveQuietly()` call sites (the 6th only changes `store_id`, not status — correctly excluded).
- Two new test files (19 tests, `EquipmentStatusServiceLogTest.php` + `UpdateProductScheduleEquipmentStatusLogTest.php`), all passing at the time of implementation.

Committed by the user as `01c755b3`, then merged with `origin/raj_development` as `f7ed151d`.

---

## 2. Critical finding — a merge introduced a crash-level regression

### What happened

Between when PR-A1 was implemented/tested and when it was committed, a **separate, concurrent branch** (`788ea510` — "Add equipment wait list module: return-triggered demand alerts", by a different engineer) was merged into `raj_development`. That branch added a new private method, `evaluateWaitLists()`, to the *same file* (`EquipmentStatusService.php`), and called it from `markReturnedToMaintenance()` and `markReturnedDamaged()` — the same two methods PR-A1 was also editing.

During that merge, the `persistStatusLog(...)` line intended for `markReturnedDamaged()` ended up **misplaced inside `evaluateWaitLists()`'s body** instead of at the end of `markReturnedDamaged()` itself:

```php
// app/Services/Equipment/EquipmentStatusService.php — CURRENT (broken) state

public static function markReturnedToMaintenance(...): void {
    ...
    $equipment->saveQuietly();
    self::log(...);
    self::evaluateWaitLists($equipment);
    self::persistStatusLog($equipment->id, $oldRaw, EquipmentCurrentStatus::Maintenance->value, $actorId); // correct, added by PR-A1
}

public static function markReturnedDamaged(...): void {
    ...
    $equipment->saveQuietly();
    self::log(...);
    self::evaluateWaitLists($equipment);
    // <-- PR-A1's persistStatusLog(...) call for THIS method is missing here
}

private static function evaluateWaitLists(Equipment $equipment): void
{
    try {
        WaitListMatcher::evaluateReturn($equipment);
    } catch (\Throwable $e) {
        Log::channel(self::CHANNEL)->error('Wait list evaluation failed', [...]);
    }
    // BUG: this line belongs in markReturnedDamaged(), not here.
    // evaluateWaitLists(Equipment $equipment) only has $equipment in scope —
    // $oldRaw and $actorId are undefined variables in this method.
    self::persistStatusLog($equipment->id, $oldRaw, EquipmentCurrentStatus::Damaged->value, $actorId);
}
```

**This was not introduced by the PR-A1 implementation itself.** The file was read and verified correct multiple times during implementation and testing, before the wait-list branch existed in this working tree. It was introduced during the merge/conflict-resolution step that happened afterward, outside the implementation session.

### Empirical proof (not just static reading)

Re-running the existing PR-A1 test suite against the current merged code:

```
php artisan test --env=testing tests/Unit/Equipment/EquipmentStatusServiceLogTest.php

FAILED: mark returned damaged writes equipment status log       — ErrorException: Undefined variable $oldRaw
FAILED: mark returned to maintenance writes equipment status log — ErrorException: Undefined variable $oldRaw
FAILED: calling a transition method twice ...                    — ErrorException: Undefined variable $oldRaw

Tests: 3 failed, 8 passed
```

### Why this is critical, not just a data-quality bug

Because `APP_DEBUG=true` in this environment, PHP's undefined-variable warning is escalated to a **thrown `ErrorException`** — a hard crash, not a silent null. Concretely, as merged right now:

- Every mobile return that transitions equipment to **Maintenance** (`markReturnedToMaintenance`, called from `SaveReturnController` on a normal undamaged return) **throws a 500**.
- Every mobile return that transitions equipment to **Damaged** (`markReturnedDamaged`, called on a damaged return) **throws a 500**.
- These are two of the three real outcomes of the return-checklist mobile flow (`SaveReturnController`) — i.e. **the core return workflow is currently broken** on `raj_development`.
- Even where PHP's error level doesn't escalate to a crash (e.g. if `APP_DEBUG=false`), the undefined variables resolve to `null`, so `markReturnedToMaintenance` would write a **second, bogus** `EquipmentStatusLog` row (`from_status=null, to_status='Damaged', changed_by=null`) alongside its correct one, and `markReturnedDamaged` would never write a *correct* row at all — corrupting the exact audit trail this PR exists to fix.

### The fix (not yet applied)

Move `self::persistStatusLog($equipment->id, $oldRaw, EquipmentCurrentStatus::Damaged->value, $actorId);` out of `evaluateWaitLists()` and into `markReturnedDamaged()`, immediately after its `self::log(...)` call — matching the pattern every other method already follows. `evaluateWaitLists()` should go back to containing only the try/catch around `WaitListMatcher::evaluateReturn()`.

### Process takeaway

**The tests did their job.** The 19 tests written for PR-A1 passed cleanly when last verified, before the wait-list branch was merged in. This isn't a test-quality failure — it's a "tests must be re-run after every merge, not just once after the PR that wrote them" process gap. Worth adopting as a standing rule for the rest of this project.

---

## 3. High — PR-A1's stated scope was not fully covered

The original instruction was: *"Fix all checklist/order equipment status paths using saveQuietly()."* Implementation was scoped to the two files `CORRECTION_PHASE1_PLAN.md`'s Issue #2 explicitly named. Three more genuine, **pre-existing** `saveQuietly()` sites that mutate `equipment.current_status` were missed — confirmed by direct grep, not just agent claims:

| File | Line | Transition | First introduced |
|---|---|---|---|
| `app/Http/Controllers/Admin/OrderManagement/Orders/AssignEquipmentController.php` | 147 | → Rented (equipment assignment to an order) | predates this session |
| `app/Http/Controllers/Admin/OrderManagement/Orders/RemoveEquipmentController.php` | 45 | → Available (equipment removed from an order) | predates this session |
| `app/Models/Orders/Order.php` (a `deleting` model hook) | 243 | → Maintenance (cascades to every product's equipment when an order is deleted) | predates this session |

All three predate this session and the wait-list merge (oldest relevant commit: `ee4b5d4c`) — they are pre-existing gaps that neither the original Phase 1/2/Issue-5 audit **nor** this PR's implementation caught. They are squarely "order equipment status paths" per the original scope language.

The updated class docblock in `EquipmentStatusService.php` also now slightly overstates coverage — it names `UpdateProductScheduleController` as handled and implies other admin controllers are just "unchanged for now," without disclosing that `RemoveEquipmentController` and `Order.php` specifically were never even identified as gaps.

---

## 4. Medium-severity findings

**M1 — Duplicated logging helper.** `EquipmentStatusService::persistStatusLog()` and `UpdateProductScheduleController::persistEquipmentStatusLog()` are near-identical (same guard `if ($from === $to) return;`, same `EquipmentStatusLog::create()` field mapping) implemented independently in two classes. The critical bug in §2 landed in one of these two independent copies — direct evidence the duplication is a real hazard, not a style nit. A single shared helper (e.g. a static `EquipmentStatusLog::recordTransition(...)`) would make it structurally harder to have exactly this kind of copy-paste/merge-placement error.

**M2 — No transaction/error isolation around the new DB write.** `EquipmentStatusLog::create()` has no try/catch and isn't wrapped in a transaction alongside the `saveQuietly()` call it follows. If it throws (e.g. a `changed_by` foreign-key violation from a deleted user), the request now 500s even though the equipment status change itself already succeeded — a new failure mode that didn't exist before this write was added. This is explicitly the subject of the separately-planned PR-A2 ("DB transaction wrapping"), so it's an accepted, known gap for *this* PR rather than a surprise — flagging so it isn't lost track of.

---

## 5. Low-severity findings

**L1 — Minor efficiency.** `app/Http/Controllers/Api/Admin/V1/Orders/CustomerChecklists/RemoveController.php:63-74` calls `markAvailableOnChecklistRemove()` inside a `foreach` over every order product on the order, so PR-A1 adds one more synchronous DB insert per iteration to an already-existing per-iteration loop. Not a new N+1 *pattern* — just additional linear cost on an existing loop. No action needed now.

**L2 — Minor simplification.** All 7 `EquipmentStatusService` methods compute both `$old` (with `'unknown'` string fallback, used for the text log channel) and `$oldRaw` (nullable, used for the DB row) from the identical `$equipment->current_status?->value` expression. Could derive `$old = $oldRaw ?? 'unknown';` from a single capture instead of maintaining two near-identical variables per method.

---

## 6. Explicit checklist requested for this review

| # | Item | Result |
|---|---|---|
| 1 | Every `saveQuietly()` path that changes equipment status is covered | **No** — 3 gaps found (§3) |
| 2 | No duplicate `EquipmentStatusLog` rows can be created | **No** — the critical bug (§2) causes a duplicate row on every `markReturnedToMaintenance` call |
| 3 | Existing `EquipmentObserver` behavior is preserved | **Yes** — untouched; still bypassed by `saveQuietly()` exactly as before this PR |
| 4 | No new event recursion introduced | **Yes** — `EquipmentStatusLog::create()` is a plain Eloquent insert; no observers are registered on that model |
| 5 | No transaction inconsistencies introduced | **Partial** — new unguarded write (M2), by design deferred to PR-A2 |
| 6 | No N+1 queries or unnecessary DB writes added | **Minor exception only** (L1), not a new pattern |
| 7 | Implementation matches the approved roadmap | **Partial** — matches `CORRECTION_PHASE1_PLAN.md`'s literal file list; does not fully match the broader "fix all paths" instruction |
| 8 | Tests actually prove the bug is fixed | **Yes, and decisively** — re-running them against the current merged code is exactly what caught the critical bug in §2 |
| 9 | Edge cases missed | The 3 High-severity call sites in §3 |
| 10 | Approve or request changes | **CHANGES REQUIRED** |

---

## 7. Suggested improvements (not blocking, worth a backlog note)

- Add a shared `Equipment::transitionStatusQuietly($to, $actorId)` model method (or trait) that bundles `saveQuietly()` + the audit-log write atomically, so a future 9th call site can't be added without also logging it — a deeper, more durable fix than per-call-site logging.
- Fold the 3 newly-found call sites (`AssignEquipmentController`, `RemoveEquipmentController`, `Order.php`) into this same "Issue #2" fix scope before considering it closed, rather than opening a separate ticket later.

---

## 8. Final recommendation

**Do not merge as-is.**

The critical fix (§2) is a one-line relocation: move the misplaced `persistStatusLog(...)` call out of `evaluateWaitLists()` into `markReturnedDamaged()`. Re-running `EquipmentStatusServiceLogTest` after that fix should return 11/11 green again (it was passing before the merge introduced the corruption).

The 3-file scope gap (§3) is the same mechanical pattern already established in this PR (capture "before" status, mutate, `saveQuietly()`, then a guarded `EquipmentStatusLog::create()`) — recommend folding it into this same PR as a fast-follow rather than opening a new one, since "Issue #2" was scoped by the user as "fix all checklist/order equipment status paths using saveQuietly()," and these three sites fall within that language.

**Process note for the rest of this project:** re-run the relevant test suite after every merge that touches a file under active development, not just once when the PR implementing it is first verified. This single habit would have caught §2 before it reached `raj_development`.

---

*No code was modified to produce this review. All findings were independently verified — either by direct file reads, git history tracing, or by actually re-running the test suite against the current code (not just static analysis).*
