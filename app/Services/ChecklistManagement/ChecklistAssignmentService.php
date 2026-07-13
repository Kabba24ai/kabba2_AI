<?php

namespace App\Services\ChecklistManagement;

use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ChecklistAssignmentService — single source of truth for writing
 * equipment.checklist_master_id (PR-B1, Phase 2 Track B).
 *
 * Before this service existed, three independent controllers wrote this column
 * directly via raw Eloquent calls with no shared guard logic and no audit trail:
 * AssignChecklistMasterController (single-equipment assign), ChecklistMaster\StoreController
 * (bulk-assign on create), and ChecklistMaster\UpdateController (bulk unassign-then-
 * reassign on edit — confirmed by PHASE3_RESULTS.md S5 to silently mass-unassign
 * equipment set via a different path with no warning). See
 * docs/checklist-system-audit/PHASE2_IMPLEMENTATION_PLAN.md (PR-B1) and
 * docs/checklist-system-audit/PR-B1_ASSIGNMENT_CONSOLIDATION.md for full detail.
 */
class ChecklistAssignmentService
{
    /**
     * Assign a single piece of equipment to a checklist master.
     *
     * Mirrors AssignChecklistMasterController's original guard: a no-op if the
     * equipment is already assigned to this exact master.
     *
     * @return bool true if the assignment was made, false if it was already assigned
     *              to this master (no-op — caller decides how to respond, e.g. 400).
     */
    public function assignSingle(Equipment $equipment, ChecklistMaster $checklistMaster): bool
    {
        if ($equipment->checklist_master_id === $checklistMaster->id) {
            return false;
        }

        $equipment->checklist_master_id = $checklistMaster->id;
        $equipment->save();

        return true;
    }

    /**
     * Bulk-assign a list of equipment IDs to a checklist master.
     *
     * @param  array<int>  $equipmentIds
     * @param  bool  $unassignExisting  When true (ChecklistMaster\UpdateController's
     *         edit-time behavior), any equipment currently assigned to this master but
     *         NOT present in $equipmentIds is unassigned first. When false
     *         (ChecklistMaster\StoreController's create-time behavior), nothing is
     *         unassigned — there is nothing to unassign yet for a brand-new master.
     * @return array<int>  The equipment IDs that were unassigned as a side effect of
     *         $unassignExisting (empty if $unassignExisting is false, or if every
     *         previously-assigned equipment ID is also present in the new list).
     *         Phase 2 decision D1 ("warn before bulk unassignment") is satisfied here
     *         by logging this list — see the class docblock for why a UI confirmation
     *         step was not added in this backend-focused PR.
     *
     * Wrapped in its own DB::transaction() so the unassign-then-reassign pair is
     * atomic even if a future caller invokes this outside an existing transaction.
     * Laravel's transaction nesting uses savepoints, so this is also safe when a
     * caller (e.g. StoreController, UpdateController) already has an outer
     * DB::beginTransaction() open — this just becomes a nested savepoint within it.
     *
     * PR-B1 review fix: the final unassign update also re-checks
     * checklist_master_id = $checklistMaster->id (not just whereIn('id', ...)), so a
     * row that a concurrent request has already reassigned to a different master in
     * the gap between the SELECT and this UPDATE is left alone instead of being
     * silently clobbered.
     */
    public function bulkAssign(ChecklistMaster $checklistMaster, array $equipmentIds, bool $unassignExisting = false): array
    {
        return DB::transaction(function () use ($checklistMaster, $equipmentIds, $unassignExisting) {
            $unassignedIds = [];

            if ($unassignExisting) {
                $unassignedIds = Equipment::where('checklist_master_id', $checklistMaster->id)
                    ->whereNotIn('id', $equipmentIds)
                    ->pluck('id')
                    ->toArray();

                if (!empty($unassignedIds)) {
                    Equipment::whereIn('id', $unassignedIds)
                        ->where('checklist_master_id', $checklistMaster->id)
                        ->update(['checklist_master_id' => null]);

                    Log::channel('api_errors')->warning(
                        'Checklist Master bulk update unassigned equipment that may have been assigned via a different path',
                        [
                            'checklist_master_id'        => $checklistMaster->id,
                            'checklist_master_unique_id' => $checklistMaster->unique_id,
                            'unassigned_equipment_ids'   => $unassignedIds,
                        ]
                    );
                }
            }

            if (!empty($equipmentIds)) {
                Equipment::whereIn('id', $equipmentIds)->update(['checklist_master_id' => $checklistMaster->id]);
            }

            return $unassignedIds;
        });
    }

    /**
     * Null out checklist_master_id for every piece of equipment currently pointing at
     * a checklist master. Intended to be called before/at delete time — closes the
     * dangling-reference gap where a soft delete leaves equipment pointing at a
     * trashed, invisible master (the column's ON DELETE SET NULL foreign key behavior
     * never fires for a soft delete, since the row is never actually removed).
     *
     * @return int the number of equipment rows unassigned.
     */
    public function unassignAllForMaster(ChecklistMaster $checklistMaster): int
    {
        return Equipment::where('checklist_master_id', $checklistMaster->id)
            ->update(['checklist_master_id' => null]);
    }
}
