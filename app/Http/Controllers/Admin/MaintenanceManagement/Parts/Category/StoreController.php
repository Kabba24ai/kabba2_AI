<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts\Category;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Parts\Category\StoreRequest;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\PartCategory;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $data = $request->validated();
        
        $category = PartCategory::create([
            'name' =>   $data['name'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully!',
            'category' => $category,
        ]);

    }
}
