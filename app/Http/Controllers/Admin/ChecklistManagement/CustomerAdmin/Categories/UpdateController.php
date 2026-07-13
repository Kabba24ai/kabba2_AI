<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Categories;

use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;
use App\Services\ChecklistManagement\CategoryCrudService;

// Request
use App\Http\Requests\Admin\ChecklistManagement\CustomerAdmin\Categories\UpdateRequest;

class UpdateController extends Controller
{
    public function __construct(private CategoryCrudService $categoryCrudService)
    {
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateRequest $request, $id)
    {
        $validated = $request->validated();

        try {
            $this->categoryCrudService->update(
                CustomerAdminCategory::class,
                $id,
                [
                    'category_name' => $validated['category_name'],
                    'description'   => $validated['description'] ?? null,
                ]
            );

            flash('Category updated successfully.')->success();

            session()->flash('active_tab', 'questions');
            session()->flash('active_subtab', 'categories');

            return redirect()
                ->route('admin.checklist-management.customer-admin.index')
                ->with('success', 'Category updated successfully.');

        } catch (\Throwable $e) {

            report($e);

            flash('Something went wrong while updating the category.')->error();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while updating the category.']);
        }
    }
}
