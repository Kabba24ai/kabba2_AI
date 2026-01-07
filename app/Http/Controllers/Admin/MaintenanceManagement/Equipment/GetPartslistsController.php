<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\PartsList;
use Illuminate\Http\JsonResponse;

class GetPartslistsController extends Controller
{
    public function __invoke($categoryId): JsonResponse
    {
        $lists = PartsList::where('category_id', $categoryId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => $lists
        ]);
    }
}
