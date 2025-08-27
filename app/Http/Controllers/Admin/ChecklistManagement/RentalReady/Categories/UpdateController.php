<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Categories;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;

// Request
use App\Http\Requests\Admin\ChecklistManagement\RentalReady\Categories\UpdateRequest;

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
            $category = RentalReadyChecklistCategory::where('unique_id',$id)->first();

            $category->update([
                'category_name' => $validated['category_name'],
                'description'   => $validated['description'] ?? null,
            ]);

            DB::commit();

            flash('Category updated successfully.')->success();

            session()->flash('active_tab', 'questions');
            session()->flash('active_subtab', 'categories');

            return redirect()
                ->route('admin.checklist-management.rental-ready.index');

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
