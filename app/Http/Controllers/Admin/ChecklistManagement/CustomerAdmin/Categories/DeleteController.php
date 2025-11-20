<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Categories;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;

class DeleteController extends Controller
{
    public function __invoke($unique_id)
    {
        DB::beginTransaction();

        try {
            $category = CustomerAdminCategory::where('unique_id', $unique_id)->firstOrFail();
            $category->delete();

            DB::commit();

            flash('Category deleted successfully.')->success();

            session()->flash('active_tab', 'questions');
            session()->flash('active_subtab', 'categories');

            return redirect()
                ->route('admin.checklist-management.customer-admin.index')
                ->with('success', 'Category deleted successfully.');

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
