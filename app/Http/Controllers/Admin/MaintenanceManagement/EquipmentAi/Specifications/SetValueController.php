<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SetValueController extends Controller
{
    /**
     * Manually set a spec value for a specific profile from the matrix page.
     * Creates the row if it doesn't exist (placeholder → real value).
     *
     * POST /specifications/set-value
     * Body: { profile_id, spec_key, spec_label, spec_value, spec_unit? }
     */
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'profile_id' => 'required|exists:equipment_ai_profiles,id',
            'spec_key'   => 'required|string|max:100',
            'spec_label' => 'required|string|max:255',
            'spec_value' => 'required|string|max:500',
            'spec_unit'  => 'nullable|string|max:50',
        ]);

        $specKey = trim(strtolower((string) $data['spec_key']));
        $specKey = preg_replace('/[^a-z0-9_]/', '_', $specKey);
        $specKey = trim($specKey, '_');

        EquipmentAiSpecification::updateOrCreate(
            [
                'equipment_ai_profile_id' => (int) $data['profile_id'],
                'spec_key'                => $specKey,
            ],
            [
                'spec_label'       => trim($data['spec_label']),
                'spec_value'       => trim($data['spec_value']),
                'spec_unit'        => isset($data['spec_unit']) ? trim($data['spec_unit']) : null,
                'confidence_score' => 1.0,
                'source'           => 'manual',
            ]
        );

        return response()->json(['success' => true]);
    }
}
