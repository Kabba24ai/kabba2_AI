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
        $status = $validated['status'] ?? null;

        $order = ['damaged', 'maintenance', 'rented', 'available'];
        $equipment = Equipment::with('productCategory', 'orderProduct')
            ->when($status, fn($query) => $query->where('current_status', $status))
            ->orderByRaw("FIELD(current_status, '" . implode("','", $order) . "')")
            ->get();

        // if ($equipment->isEmpty()) {
        //     return response()->json(
        //         [
        //             'success' => false,
        //             'message' => trans('messages.api.admin.v1.equipment.no_equipment_found'),
        //         ],
        //         JsonResponse::HTTP_NOT_FOUND
        //     );
        // }

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.equipment.equipment_found'),
            'equipment' => ListResource::collection($equipment),
        ]);
    }
}
