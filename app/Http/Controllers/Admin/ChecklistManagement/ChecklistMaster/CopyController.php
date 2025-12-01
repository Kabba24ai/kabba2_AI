<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;

// Request
use App\Http\Requests\Admin\ChecklistManagement\ChecklistMaster\UpdateRequest;

use App\Http\Requests\Admin\ChecklistManagement\ChecklistMaster\StoreRequest;
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;

class CopyController extends Controller
{
     public function __invoke($id)
    {
        try {
            $master = ChecklistMaster::where('unique_id', $id)->firstOrFail();

            DB::beginTransaction();

            // Duplicate Entry
            $newMaster = ChecklistMaster::create([
                'checklist_system_name'     => $master->checklist_system_name . ' (Copy)',
                'equipment_category_id'     => $master->equipment_category_id,
                'rental_ready_template_id'  => $master->rental_ready_template_id,
                'customer_admin_template_id'=> $master->customer_admin_template_id,
            ]);

            DB::commit();

            flash('Checklist Master duplicated successfully.')->success();

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            flash('Failed to duplicate Checklist Master.')->error();
        }

        return redirect()->route('admin.checklist-management.checklist-master.index');
    }
}
