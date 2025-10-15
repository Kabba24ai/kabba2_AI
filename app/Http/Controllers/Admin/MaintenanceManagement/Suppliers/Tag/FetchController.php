<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\Tag;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\SupplierTag;
use Illuminate\Http\JsonResponse;

class FetchController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $tags = SupplierTag::select('unique_id', 'name','id')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'tags' => $tags,
        ]);
    }
}
