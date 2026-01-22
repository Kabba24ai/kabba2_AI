<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\Category;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Suppliers\Category\StoreRequest;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\SupplierCategory;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $data = $request->validated();
        
        $category = SupplierCategory::create([
            'name' =>   $data['name'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully!',
            'category' => $category,
        ]);

    }
}
