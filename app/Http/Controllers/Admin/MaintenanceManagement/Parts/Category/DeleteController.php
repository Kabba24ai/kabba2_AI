<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts\Category;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\PartCategory;

class DeleteController extends Controller
{
    public function __invoke(PartCategory $category)
    {
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully!',
        ]);
    }
}
