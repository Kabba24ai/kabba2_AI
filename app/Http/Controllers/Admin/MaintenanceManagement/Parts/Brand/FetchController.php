<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts\Brand;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\PartBrand;
use Illuminate\Http\JsonResponse;

class FetchController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $brands = PartBrand::select('unique_id', 'name','id')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'brands' => $brands,
        ]);
    }
}
