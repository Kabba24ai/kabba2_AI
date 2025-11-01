<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceManagement\PartCategory;
use App\Http\Requests\Admin\MaintenanceManagement\Parts\Category\StoreRequest;

class UpdateController extends Controller
{
    public function __invoke(StoreRequest $request, PartCategory $category)
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
