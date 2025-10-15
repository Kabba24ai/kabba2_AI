<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceManagement\SupplierCategory;
use App\Http\Requests\Admin\MaintenanceManagement\Suppliers\Category\StoreRequest;

class UpdateController extends Controller
{
    public function __invoke(StoreRequest $request, SupplierCategory $category)
    {
        $data = $request->validated();
        
        $category->update([
            'name' => $data['name'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully!',
            'category' => $category,
        ]);
    }
}
