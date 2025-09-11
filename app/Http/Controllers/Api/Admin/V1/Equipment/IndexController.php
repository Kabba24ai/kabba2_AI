<?php

namespace App\Http\Controllers\Api\Admin\V1\Equipment;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Equipment\IndexRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\Equipment\ListResource;

// Model
use App\Models\MaintenanceManagement\Equipment;

class IndexController extends BaseController
{
    /**
     * Equipment List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(IndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $type = $validated['type'] ?? null;

        if($type === 'RentalReady'){
            $order = ['damaged', 'maintenance', 'rented', 'available'];
        }else{
            $order = ['available', 'rented', 'maintenance', 'damaged'];
        }

        $equipment = Equipment::with('productCategory', 'orderProduct')
            ->orderByRaw("FIELD(current_status, '" . implode("','", $order) . "')")
            ->get();

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.equipment.equipment_found'),
            'equipment' => ListResource::collection($equipment),
        ]);
    }
}
