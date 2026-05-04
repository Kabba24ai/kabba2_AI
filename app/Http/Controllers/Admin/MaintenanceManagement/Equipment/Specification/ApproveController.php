<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment\Specification;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentSpecification;
use Illuminate\Http\JsonResponse;

class ApproveController extends Controller
{
    public function __invoke(string $uniqueId, int $specId): JsonResponse
    {
        $equipment = Equipment::where('unique_id', $uniqueId)->firstOrFail();

        $spec = EquipmentSpecification::where('id', $specId)
            ->where('equipment_id', $equipment->id)
            ->firstOrFail();

        $spec->update([
            'is_approved' => true,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'spec'    => GenerateController::formatSpec($spec->fresh('approvedByUser')),
        ]);
    }
}
