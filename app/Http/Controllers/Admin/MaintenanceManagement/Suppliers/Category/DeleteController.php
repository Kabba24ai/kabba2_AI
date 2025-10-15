<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\Category;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\SupplierCategory;

class DeleteController extends Controller
{
    public function __invoke(SupplierCategory $category)
    {
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully!',
        ]);
    }
}
