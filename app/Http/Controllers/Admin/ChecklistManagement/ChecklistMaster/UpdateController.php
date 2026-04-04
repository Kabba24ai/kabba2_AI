<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;

// Request
use App\Http\Requests\Admin\ChecklistManagement\ChecklistMaster\UpdateRequest;

use App\Http\Requests\Admin\ChecklistManagement\ChecklistMaster\StoreRequest;
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\MaintenanceManagement\Equipment;


class UpdateController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateRequest $request, $id)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            $ChecklistMaster = ChecklistMaster::where('unique_id', $id)->first();

            $ChecklistMaster->update([
                'checklist_system_name'   => $validated['checklist_system_name'],
                'equipment_category_id'   => $validated['equipment_category_id'],
                'rental_ready_template_id' => $validated['rental_ready_template_id'] ?? null,
                'customer_admin_template_id' => $validated['customer_admin_template_id'] ?? null,

            ]);

            //  Assign new equipment (if selected)
        if (!empty($validated['assign_equipment']) ){

                //  Remove old assignments
                Equipment::where('checklist_master_id', $ChecklistMaster->id)
                ->update(['checklist_master_id' => null]);


                $equipmentIds = explode(',', $validated['equipment_ids']);

                Equipment::whereIn('id', $equipmentIds)
                    ->update([
                        'checklist_master_id' => $ChecklistMaster->id
                    ]);
            }

            DB::commit();

            flash('Checklist Master Update.')->success();

            session()->flash('active_tab', 'templates');

            return redirect()
                ->route('admin.checklist-management.checklist-master.index');
        } catch (\Throwable $e) {

            DB::rollBack();
            report($e);

            flash('Something went wrong while updating the category.')->error();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while updating the category.']);
        }
    }
}
