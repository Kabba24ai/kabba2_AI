<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize\CustomSpec;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiCustomSpecValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateValueController extends Controller
{
    /**
     * Manual override of a single custom spec value cell.
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'spec_value' => 'nullable|string|max:500',
        ]);

        $valueRow = EquipmentAiCustomSpecValue::findOrFail($id);

        $valueRow->update([
            'spec_value'        => $data['spec_value'] ?? null,
            'value_source'      => 'manual',
            'confirmed_by_user' => true,
            'last_updated_by'   => auth()->user()?->name ?? 'admin',
        ]);

        return response()->json([
            'success' => true,
            'value'   => $valueRow->spec_value,
            'source'  => $valueRow->value_source,
        ]);
    }
}
