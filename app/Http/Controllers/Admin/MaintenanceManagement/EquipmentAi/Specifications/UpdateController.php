<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;

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
        ]);

        $spec->update($data);

        $profileUniqueId = $spec->profile->unique_id;

        return redirect()
            ->route('admin.maintenance-management.equipment-ai.profiles.specifications.index', $profileUniqueId)
            ->with('success', 'Specification updated.');
    }
}
