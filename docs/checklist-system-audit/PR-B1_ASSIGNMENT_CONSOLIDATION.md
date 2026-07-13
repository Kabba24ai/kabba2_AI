# PR-B1 — Consolidate Equipment ↔ ChecklistMaster Assignment

**Date:** 2026-07-10
**Depends on:** `PHASE2_IMPLEMENTATION_PLAN.md` (PR-B1 definition), `PHASE2_DECISION_MATRIX.md` (D1), `PHASE2_DECISION_MATRIX_SUMMARY.md` (client approval), `PR-B3_VALIDATION_GUARDS.md` (the observability-logging pattern this reuses).
**Code modified:** Yes.
**Scope:** exactly the 3 equipment-assignment write paths + the delete-time dangling-reference gap. No PR-B2 or PR-B4 work included. No database migrations. No mobile impact — this is 100% an admin-web surface.

---

## 1. Objective

Route all writes to `equipment.checklist_master_id` through one shared service (`ChecklistAssignmentService`), so assignment/unassignment logic — including its one safety guard and its one logging side effect — is defined once, not independently in three different controllers.

---

## 2. Root cause (recap from Phase 2 planning)

Three independent, uncoordinated code paths wrote `equipment.checklist_master_id` directly via raw Eloquent calls:

1. `AssignChecklistMasterController.php` — single-equipment assign. Had a duplicate-assignment guard.
2. `ChecklistMaster\StoreController.php` — bulk-assigns equipment on creation of a new master. No guard.
3. `ChecklistMaster\UpdateController.php` — bulk *unassign-then-reassign* on every edit. No guard — confirmed by live testing in `PHASE3_RESULTS.md` (S5) to silently mass-unassign equipment that was assigned via a different path, with zero warning.

Separately: `ChecklistMaster\DeleteController.php` soft-deletes a master without nulling `equipment.checklist_master_id` first — equipment can end up pointing at a trashed, invisible master. This happens because `checklist_masters` uses `SoftDeletes`; the column's `ON DELETE SET NULL` foreign key behavior never fires for a soft delete, since the row is never actually removed from the table.

---

## 3. Files changed

| File | Change |
|---|---|
| `app/Services/ChecklistManagement/ChecklistAssignmentService.php` *(new)* | `assignSingle()`, `bulkAssign()`, `unassignAllForMaster()` — see §4. Post-review fix (§4a): `bulkAssign()`'s unassign update re-checks `checklist_master_id`, and its body is wrapped in `DB::transaction()`. |
| `app/Http/Controllers/Admin/MaintenanceManagement/Equipment/AssignChecklistMasterController.php` | Constructor-injects the service; the raw `$equipment->checklist_master_id = ...; $equipment->save();` write replaced with `$this->checklistAssignmentService->assignSingle(...)`. Response logic (404/400/200 JSON) unchanged. |
| `app/Http/Controllers/Admin/ChecklistManagement/ChecklistMaster/StoreController.php` | Constructor-injects the service; the raw `Equipment::whereIn(...)->update(...)` replaced with `bulkAssign($checklistMaster, $equipmentIds)` (`$unassignExisting` defaults to `false` — nothing to unassign yet for a brand-new master). Removed the now-unused `Equipment` import. |
| `app/Http/Controllers/Admin/ChecklistManagement/ChecklistMaster/UpdateController.php` | Constructor-injects the service; the two raw `Equipment::...->update(...)` calls (null-all, then reassign) replaced with one `bulkAssign($ChecklistMaster, $equipmentIds, unassignExisting: true)` call. Removed the now-unused `Equipment` import. |
| `app/Http/Controllers/Admin/ChecklistManagement/ChecklistMaster/DeleteController.php` | Constructor-injects the service; calls `unassignAllForMaster($ChecklistMaster)` immediately before `$ChecklistMaster->delete()`, closing the dangling-reference gap. |
| `tests/Feature/ChecklistManagement/ChecklistAssignmentServiceTest.php` *(new)* | 7 tests — see §6. |

No other files were touched. No unrelated refactoring was performed in any of the four controllers.

---

## 4. Target implementation — what the service does

```php
class ChecklistAssignmentService
{
    public function assignSingle(Equipment $equipment, ChecklistMaster $checklistMaster): bool;
    public function bulkAssign(ChecklistMaster $checklistMaster, array $equipmentIds, bool $unassignExisting = false): array;
    public function unassignAllForMaster(ChecklistMaster $checklistMaster): int;
}
```

- **`assignSingle()`** — exact behavior-preserving extraction of `AssignChecklistMasterController`'s original guard: returns `false` (no-op) if the equipment is already assigned to this exact master, `true` otherwise after making the assignment. The controller's existing 400 response is unchanged; it now just triggers off the boolean return value instead of an inline comparison.
- **`bulkAssign()`** — the one place this PR **intentionally improves** on the original behavior, per Phase 2 decision D1 (see §5). Computes the exact set of equipment IDs that will be unassigned (`whereNotIn($equipmentIds)`) rather than the original code's blanket "null everyone, then reassign" — same final state, but avoids an unnecessary double-write for equipment that stays assigned, and gives the caller (and the log) an exact list of what changed rather than nothing.
- **`unassignAllForMaster()`** — the delete-time fix. Called before the soft delete, so no equipment is left dangling.

---

## 4a. Review fix — TOCTOU race in `bulkAssign()` (post-review update, 2026-07-10)

An independent PR-B1 review flagged two related findings in `bulkAssign()`, both now fixed.

**Race condition found:** the unassign step computed `$unassignedIds` via a `SELECT ... WHERE checklist_master_id = ? AND id NOT IN (...)`, then unassigned those rows with `Equipment::whereIn('id', $unassignedIds)->update(...)` — keyed only on `id`, not re-checking `checklist_master_id`. The pre-service code's unassign step was a single atomic `WHERE checklist_master_id = ?` update, safe by construction against a concurrent reassignment (MySQL re-evaluates the WHERE clause at UPDATE time). The service's SELECT-then-UPDATE split reintroduced a TOCTOU (time-of-check-to-time-of-use) window: if a concurrent request reassigned one of those equipment rows to a *different* master in the gap between the SELECT and the UPDATE, the stale `$unassignedIds` list would still null it out, silently clobbering that concurrent reassignment.

**Fix applied:** the final unassign update now re-checks the master condition:
```php
Equipment::whereIn('id', $unassignedIds)
    ->where('checklist_master_id', $checklistMaster->id)
    ->update(['checklist_master_id' => null]);
```
This restores the original atomicity guarantee (a row is only nulled if it still points at this master at UPDATE time) while keeping the exact-list improvement for logging.

**Transaction guarantee added:** `bulkAssign()` now wraps its entire body in `DB::transaction()`, so the unassign step and the reassign step are atomic even if a future caller invokes the service outside of an existing transaction. Laravel's transaction nesting uses savepoints, so this remains safe when called from `StoreController`/`UpdateController`, which already open an outer `DB::beginTransaction()` — the service's `DB::transaction()` call simply becomes a nested savepoint within it; no double-commit or double-rollback risk.

**Files changed for this fix:**
- `app/Services/ChecklistManagement/ChecklistAssignmentService.php` — added the `where('checklist_master_id', ...)` guard to the unassign update; wrapped `bulkAssign()`'s body in `DB::transaction()`.
- `tests/Feature/ChecklistManagement/ChecklistAssignmentServiceTest.php` — added `test_bulk_assign_does_not_null_equipment_already_reassigned_to_a_different_master`, which reassigns `equipmentA` to a second master immediately before calling `bulkAssign()` directly (simulating the race), then asserts `equipmentA` still points at the second master (not nulled) and the kept equipment is still correctly assigned.

No other files were touched for this fix.

---

## 5. Phase 2 decision D1 — how it was implemented

D1 asked: should the bulk unassign-then-reassign step in `ChecklistMaster\UpdateController` warn the admin before it happens, or is logging the side effect sufficient? The recommendation (and the approved Phase 2 outcome) was "add the warning."

**This PR implements the logging half of D1, not a new UI confirmation dialog.** `bulkAssign()` logs a structured warning to the existing `api_errors` channel whenever `$unassignExisting` produces a non-empty unassigned-IDs list:

```php
Log::channel('api_errors')->warning(
    'Checklist Master bulk update unassigned equipment that may have been assigned via a different path',
    [
        'checklist_master_id'        => $checklistMaster->id,
        'checklist_master_unique_id' => $checklistMaster->unique_id,
        'unassigned_equipment_ids'   => $unassignedIds,
    ]
);
```

**Why no UI change in this PR:** the task scope for PR-B1 was backend consolidation (`ChecklistAssignmentService` + wiring it into the 4 controllers). A confirmation dialog is a frontend/Blade-view change with its own design and UX considerations, not mentioned in this PR's implementation list. Per `PHASE2_IMPLEMENTATION_PLAN.md`'s own PR-B1 section: *"If a full UI change can't be scheduled immediately, ship the logging-only version first... and follow with the UI confirmation as a fast-follow."* This PR is that logging-only version. **Recommend a small, separate fast-follow PR** to add the actual confirmation/diff dialog to the Checklist Master edit screen, using this same log data to show the admin what's about to be unassigned before they submit.

---

## 6. Tests added

`tests/Feature/ChecklistManagement/ChecklistAssignmentServiceTest.php` — 7 tests:

1. `test_single_assign_via_controller_assigns_equipment` — confirms the happy path through `AssignChecklistMasterController` still assigns correctly.
2. `test_single_assign_via_controller_rejects_duplicate_assignment` — confirms the original 400 "already assigned" behavior is unchanged.
3. `test_store_controller_bulk_assigns_equipment_on_create` — confirms `ChecklistMaster\StoreController` still bulk-assigns equipment IDs supplied at creation time.
4. `test_update_controller_reassigns_and_unassigns_excluded_equipment` — the key regression test: equipment assigned via a *different* path (`equipmentA`, simulating `AssignChecklistMasterController`'s own assignment) is correctly unassigned when excluded from an edit's new equipment list; equipment kept in the list stays assigned; new equipment gets assigned; **and** asserts the D1 warning is logged with the exact unassigned ID.
5. `test_update_controller_does_not_log_when_nothing_is_unassigned` — confirms no log noise when the new equipment list doesn't actually exclude anything previously assigned.
6. `test_delete_controller_nulls_out_checklist_master_id_on_assigned_equipment` — confirms the delete-time fix: after deleting a master, all equipment that pointed at it now has `checklist_master_id = null`.
7. `test_no_equipment_points_at_a_soft_deleted_checklist_master_after_delete` — a broader data-integrity assertion: after the delete, zero equipment rows reference any soft-deleted `ChecklistMaster`, including a control equipment row that was never assigned (confirming it's correctly unaffected).
8. `test_bulk_assign_does_not_null_equipment_already_reassigned_to_a_different_master` *(added in the post-review fix, §4a)* — reassigns an equipment row to a second master immediately before calling `bulkAssign()` on the first master with that row excluded from the new list, then asserts the row still points at the second master instead of being nulled by the stale unassign list.

---

## 7. Commands run

```bash
php -l app/Services/ChecklistManagement/ChecklistAssignmentService.php
php -l app/Http/Controllers/Admin/MaintenanceManagement/Equipment/AssignChecklistMasterController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/ChecklistMaster/StoreController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/ChecklistMaster/UpdateController.php
php -l app/Http/Controllers/Admin/ChecklistManagement/ChecklistMaster/DeleteController.php
php -l tests/Feature/ChecklistManagement/ChecklistAssignmentServiceTest.php

php artisan test --env=testing tests/Feature/ChecklistManagement/ChecklistAssignmentServiceTest.php

php artisan test --env=testing \
  tests/Feature/CustomerChecklists/CompletenessObservabilityLoggingTest.php \
  tests/Feature/RentalReadyChecklists/ValidationGuardObservabilityTest.php
```

---

## 8. Pass/fail counts

```
php artisan test --env=testing tests/Feature/ChecklistManagement/ChecklistAssignmentServiceTest.php
→ 8 passed (25 assertions)

php artisan test --env=testing \
  tests/Feature/CustomerChecklists/CompletenessObservabilityLoggingTest.php \
  tests/Feature/RentalReadyChecklists/ValidationGuardObservabilityTest.php
→ 14 passed (61 assertions)   — regression check on the closest-related domain
   (both suites touch Equipment.checklist_master_id in their fixtures); confirms
   this PR didn't disturb either.
```

**Total: 22/22 passing, 0 failures, 0 new regressions.** All 7 original tests plus the new race-condition regression test (§4a, §6 item 8) passed.

---

## 9. Rollback strategy

**Low risk.** The service is a thin, mechanical wrapper around the same Eloquent calls the 4 controllers already made — reverting means restoring the raw `Equipment::...->update(...)` calls inline in each controller. No schema change, no data migration, no change to any response shape or status code. The one behavior addition (the D1 log warning, and the delete-time null-out) can each be reverted independently of the service extraction itself if either is ever second-guessed — they're additive, not replacements of existing logic.

---

## 10. Deployment considerations

- **No database migrations.**
- **No mobile-facing behavior change of any kind** — all 4 controllers are exclusively admin-web session routes; the mobile app never calls any of them.
- **One new log message** lands in the existing `api_errors` channel (already used elsewhere in this codebase — no new channel, no new infrastructure).
- **One behavior change, not a UI change:** `ChecklistMaster\DeleteController` now also nulls `equipment.checklist_master_id` for previously-assigned equipment. This is a strict improvement (closes a confirmed data-integrity gap) with no plausible downside — equipment pointing at a soft-deleted, invisible master was never a state anyone could have been relying on as "working correctly."
- Safe to deploy directly; no staged rollout needed.

---

## 11. Explicitly not done in this PR

- **No UI confirmation/diff dialog** for the bulk-reassignment step — see §5 for why, and the recommended fast-follow.
- **No PR-B2 work** (Rental Ready completion calculator).
- **No PR-B4 work** (duplicated CRUD logic reduction).
- **No changes to `ChecklistMaster\AssignChecklistController`** (the view-loader for the assign-equipment picker screen) — confirmed during Phase 2 planning research that it performs no write of its own; it only renders a view.

No code has been committed as of this document.
