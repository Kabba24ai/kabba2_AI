<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Category;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceCategory;

class DestroyController extends Controller
{
    public function __invoke($id)
    {
        $category = ServiceCategory::findOrFail($id);
        
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);
    }
}
