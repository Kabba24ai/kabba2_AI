<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;

class StoreController extends Controller
{
    public function __invoke(Request $request, string $uniqueId)
    {
        $profile = EquipmentAiProfile::where('unique_id', $uniqueId)->firstOrFail();

        $data = $request->validate([
            'spec_key'        => 'required|string|max:100',
            'spec_label'      => 'required|string|max:150',
            'spec_value'      => 'nullable|string|max:500',
            'spec_unit'       => 'nullable|string|max:50',
            'confidence_score'=> 'nullable|numeric|min:0|max:1',
            'source'          => 'nullable|string|max:100',
        ]);

        // Sanitise spec_key: lowercase, underscores only
        $data['spec_key'] = preg_replace('/[^a-z0-9_]/', '_', strtolower(trim($data['spec_key'])));
        $data['equipment_ai_profile_id'] = $profile->id;
        $data['source'] = $data['source'] ?? 'manual';
        $data['confidence_score'] = $data['confidence_score'] ?? 1.0;

        EquipmentAiSpecification::updateOrCreate(
            ['equipment_ai_profile_id' => $profile->id, 'spec_key' => $data['spec_key']],
            $data
        );

        return redirect()
            ->route('admin.maintenance-management.equipment-ai.profiles.specifications.index', $uniqueId)
            ->with('success', 'Specification saved.');
    }
}
