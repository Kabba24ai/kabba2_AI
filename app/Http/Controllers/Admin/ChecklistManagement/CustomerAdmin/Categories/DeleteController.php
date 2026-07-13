<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Categories;

use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;
use App\Services\ChecklistManagement\CategoryCrudService;

class DeleteController extends Controller
{
    public function __construct(private CategoryCrudService $categoryCrudService)
    {
    }

    public function __invoke($unique_id)
    {
        try {
            $this->categoryCrudService->delete(CustomerAdminCategory::class, $unique_id);

            flash('Category deleted successfully.')->success();

            session()->flash('active_tab', 'questions');
            session()->flash('active_subtab', 'categories');

            return redirect()
                ->route('admin.checklist-management.customer-admin.index')
                ->with('success', 'Category deleted successfully.');

        } catch (\Throwable $e) {
            report($e);

            flash('Something went wrong while deleting the category.')->error();

            return redirect()
                ->back()
                ->withErrors(['error' => 'An error occurred while deleting the category.']);
        }
    }
}
