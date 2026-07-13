<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Categories;

use App\Http\Controllers\Controller;

use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;

use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;
use App\Services\ChecklistManagement\CategoryCrudService;


// Request
use App\Http\Requests\Admin\ChecklistManagement\RentalReady\Categories\StoreRequest;


class StoreController extends Controller
{
    public function __construct(private CategoryCrudService $categoryCrudService)
    {
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        try {
            $this->categoryCrudService->store(
                RentalReadyChecklistCategory::class,
                [
                    'category_name' => $validated['category_name'],
                    'description'   => $validated['description'] ?? null,
                ],
                CustomerAdminCategory::class,
                !empty($validated['create_customer_folder']) && $validated['create_customer_folder'] == 1
            );

            flash('Category created successfully.')->success();

            session()->flash('active_tab', 'questions');
            session()->flash('active_subtab', 'categories');

           return redirect()
            ->route('admin.checklist-management.rental-ready.index');

        } catch (\Throwable $e) {

            report($e);

            flash('Something went wrong while creating the category.')->error();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while creating the category.']);

        }
    }
}
