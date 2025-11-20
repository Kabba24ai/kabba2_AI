<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;

class DeleteController extends Controller
{
    public function __invoke($unique_id)
    {
        DB::beginTransaction();

        try {
            $ChecklistMaster = ChecklistMaster::where('unique_id', $unique_id)->firstOrFail();
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
