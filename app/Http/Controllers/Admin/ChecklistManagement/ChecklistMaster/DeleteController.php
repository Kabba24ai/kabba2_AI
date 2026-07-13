<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Services\ChecklistManagement\ChecklistAssignmentService;

class DeleteController extends Controller
{
    public function __construct(private ChecklistAssignmentService $checklistAssignmentService)
    {
    }

    public function __invoke($unique_id)
    {
        DB::beginTransaction();

        try {
            $ChecklistMaster = ChecklistMaster::where('unique_id', $unique_id)->firstOrFail();

            // Null out checklist_master_id on any equipment still pointing at this master
            // BEFORE the soft delete — closes the dangling-reference gap where a soft
            // delete never triggers the column's ON DELETE SET NULL foreign key behavior
            // (the row is never actually removed, so the FK constraint never fires).
            $this->checklistAssignmentService->unassignAllForMaster($ChecklistMaster);

            $ChecklistMaster->delete();

            DB::commit();

            flash('Checklist Master deleted successfully.')->success();

            session()->flash('active_tab', 'templates');


            return redirect()
                ->route('admin.checklist-management.checklist-master.index');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            flash('Something went wrong while deleting the category.')->error();

            return redirect()
                ->back()
                ->withErrors(['error' => 'An error occurred while deleting the category.']);
        }
    }
}
