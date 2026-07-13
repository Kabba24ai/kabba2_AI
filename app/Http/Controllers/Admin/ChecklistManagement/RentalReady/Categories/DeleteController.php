<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Categories;

use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Services\ChecklistManagement\CategoryCrudService;

class DeleteController extends Controller
{
    public function __construct(private CategoryCrudService $categoryCrudService)
    {
    }

    public function __invoke($unique_id)
    {
        try {
            $this->categoryCrudService->delete(RentalReadyChecklistCategory::class, $unique_id);

            flash('Category deleted successfully.')->success();

            session()->flash('active_tab', 'questions');
            session()->flash('active_subtab', 'categories');

            return redirect()
                ->route('admin.checklist-management.rental-ready.index');

        } catch (\Throwable $e) {
            report($e);

            flash('Something went wrong while deleting the category.')->error();

            return redirect()
                ->back()
                ->withErrors(['error' => 'An error occurred while deleting the category.']);
        }
    }
}
