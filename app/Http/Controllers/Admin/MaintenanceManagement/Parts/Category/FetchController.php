<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts\Category;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\PartCategory;
use Illuminate\Http\JsonResponse;

class FetchController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $categories = PartCategory::select('unique_id', 'name','id')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }
}
