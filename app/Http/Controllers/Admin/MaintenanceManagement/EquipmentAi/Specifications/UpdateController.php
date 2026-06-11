<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;
use App\Models\MaintenanceManagement\EquipmentCategoryComparisonKey;

class UpdateController extends Controller
{
    public function __invoke(Request $request, int $id)
    {
        $spec = EquipmentAiSpecification::findOrFail($id);

        $data = $request->validate([
            'spec_label'      => 'required|string|max:150',
            'spec_value'      => 'nullable|string|max:500',
            'spec_unit'       => 'nullable|string|max:50',
            'confidence_score'=> 'nullable|numeric|min:0|max:1',
            'source'          => 'nullable|string|max:100',
            'upgrade_exceeds_value' => 'nullable|boolean',
            'caution_if_exceeds_value' => 'nullable|boolean',
            'upgrade_is_below_value' => 'nullable|boolean',
            'caution_if_below_value' => 'nullable|boolean',
        ]);

        $criteriaFlags = [
            'upgrade_exceeds_value' => $request->boolean('upgrade_exceeds_value', false),
            'caution_if_exceeds_value' => $request->boolean('caution_if_exceeds_value', false),
            'upgrade_is_below_value' => $request->boolean('upgrade_is_below_value', false),
            'caution_if_below_value' => $request->boolean('caution_if_below_value', false),
        ];

        unset(
            $data['upgrade_exceeds_value'],
            $data['caution_if_exceeds_value'],
            $data['upgrade_is_below_value'],
            $data['caution_if_below_value']
        );

        $spec->update($data);

        if ($spec->is_key_comparison) {
            EquipmentCategoryComparisonKey::updateOrCreate(
                [
                    'category_id' => $spec->profile->category_id,
                    'spec_key' => $spec->spec_key,
                ],
                [
                    'display_label' => $spec->spec_label,
                    'importance_level' => 'medium',
                    'comparison_type' => 'informational_only',
                    'sort_order' => 0,
                    'is_required' => false,
                    ...$criteriaFlags,
                ]
            );
        }

        $profileUniqueId = $spec->profile->unique_id;

        return redirect()
            ->route('admin.maintenance-management.equipment-ai.profiles.specifications.index', $profileUniqueId)
            ->with('success', 'Specification updated.');
    }
}
