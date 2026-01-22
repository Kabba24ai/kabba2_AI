<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Category;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\Category\StoreRequest;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceCategory;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        $category = ServiceCategory::create($validated);

        return response()->json([
            'success' => true,
            'category' => $category,
            'message' => 'Category created successfully',
        ]);
    }
}
