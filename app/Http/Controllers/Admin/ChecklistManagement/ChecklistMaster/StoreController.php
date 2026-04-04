<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\MaintenanceManagement\Equipment;
// Request
use App\Http\Requests\Admin\ChecklistManagement\ChecklistMaster\StoreRequest;
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;

class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreRequest $request)
    {

        $validated = $request->validated();


        DB::beginTransaction();

        try {
            // Save ChecklistMaster
            $checklistMaster = ChecklistMaster::create([
                'checklist_system_name'   => $validated['checklist_system_name'],
                'equipment_category_id'   => $validated['equipment_category_id'],
                'rental_ready_template_id' => $validated['rental_ready_template_id'] ?? null,
                'customer_admin_template_id' => $validated['customer_admin_template_id'],
            ]);



            if (!empty($validated['equipment_ids'])) {

                // Convert CSV to array
                $equipmentIds = explode(',', $validated['equipment_ids']);

                // Assign checklist_master_id to equipment
                Equipment::whereIn('id', $equipmentIds)
                    ->update([
                        'checklist_master_id' => $checklistMaster->id
                    ]);
            }

            DB::commit();

            flash('Checklist Master created successfully.')->success();

            session()->flash('active_tab', 'templates');

                return redirect()
                    ->route('admin.checklist-management.checklist-master.index');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            session()->flash('active_tab', 'templates');

            flash('Something went wrong while creating the checklist master.')->error();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while creating the checklist master.']);
        }
    }
}
