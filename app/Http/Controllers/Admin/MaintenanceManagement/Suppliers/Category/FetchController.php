<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\Category;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\SupplierCategory;
use Illuminate\Http\JsonResponse;

class FetchController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $categories = SupplierCategory::select('unique_id', 'name','id')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }
}
