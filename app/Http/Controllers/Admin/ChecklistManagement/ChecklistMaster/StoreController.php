<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

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


    // dd($request->all());

        $validated = $request->validated();


        // dd($validated['customer_admin_template_id']);


        DB::beginTransaction();

        try {
            // Save ChecklistMaster
            $checklistMaster = ChecklistMaster::create([
                'checklist_system_name'   => $validated['checklist_system_name'],
                'equipment_category_id'   => $validated['equipment_category_id'],
                'rental_ready_template_id' => $validated['rental_ready_template_id'] ?? null,
                'customer_admin_template_id' => $validated['customer_admin_template_id'],
            ]);

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
