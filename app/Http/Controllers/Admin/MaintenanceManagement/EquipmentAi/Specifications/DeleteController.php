<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;

class DeleteController extends Controller
{
    public function __invoke(int $id)
    {
        $spec = EquipmentAiSpecification::findOrFail($id);
        $profileUniqueId = $spec->profile->unique_id;
        $spec->delete();

        return redirect()
            ->route('admin.maintenance-management.equipment-ai.profiles.specifications.index', $profileUniqueId)
            ->with('success', 'Specification deleted.');
    }
}
