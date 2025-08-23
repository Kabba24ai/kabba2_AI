<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Categories;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\DB;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;

// Request
use App\Http\Requests\Admin\ChecklistManagement\CustomerAdmin\Categories\StoreRequest;


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
            $category = CustomerAdminCategory::create([
                'category_name' => $validated['category_name'],
                'description'   => $validated['description'] ?? null,
            ]);

            DB::commit();

            flash('Category created successfully.')->success();

            session()->flash('active_tab', 'questions');
            session()->flash('active_subtab', 'categories');

           return redirect()
            ->route('admin.checklist-management.customer-admin.index')
            ->with('success', 'Category created successfully.');

        } catch (\Throwable $e) {

            DB::rollBack();
            report($e);

            flash('Something went wrong while creating the category.')->error();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while creating the category.']);

        }
    }
}
