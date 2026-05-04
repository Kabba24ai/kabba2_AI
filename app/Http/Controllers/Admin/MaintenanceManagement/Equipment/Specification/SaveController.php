<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment\Specification;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentSpecification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaveController extends Controller
{
    public function __invoke(Request $request, string $uniqueId, int $specId): JsonResponse
    {
        $equipment = Equipment::where('unique_id', $uniqueId)->firstOrFail();

        $spec = EquipmentSpecification::where('id', $specId)
            ->where('equipment_id', $equipment->id)
            ->firstOrFail();

        $validated = $request->validate([
            'value'      => ['nullable', 'string', 'max:500'],
            'unit'       => ['nullable', 'string', 'max:50'],
            'source_url' => ['nullable', 'string', 'max:2000'],
        ]);

        $spec->update([
            'value'              => $validated['value'] ?? null,
            'unit'               => $validated['unit'] ?? null,
            'source_url'         => $validated['source_url'] ?? null,
            'is_manual_override' => true,
        ]);

        return response()->json([
            'success' => true,
            'spec'    => GenerateController::formatSpec($spec->fresh('approvedByUser')),
        ]);
    }
}
