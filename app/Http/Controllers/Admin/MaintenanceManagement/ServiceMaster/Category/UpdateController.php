<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Category;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\Category\UpdateRequest;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceCategory;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, $id)
    {
        $category = ServiceCategory::findOrFail($id);

        $validated = $request->validated();

        $category->update($validated);

        return response()->json([
            'success' => true,
            'category' => $category->fresh(),
            'message' => 'Category updated successfully',
        ]);
    }
}
