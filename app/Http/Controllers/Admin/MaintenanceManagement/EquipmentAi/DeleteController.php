<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiProfile;

class DeleteController extends Controller
{
    public function __invoke(string $uniqueId)
    {
        $profile = EquipmentAiProfile::where('unique_id', $uniqueId)->firstOrFail();
        $categoryId = $profile->category_id;

        // Cascade deletes specs via FK cascadeOnDelete
        $profile->delete();

        return redirect()
            ->route('admin.maintenance-management.equipment-ai.index', ['category_id' => $categoryId])
            ->with('success', "AI profile for {$profile->make} {$profile->model} deleted.");
    }
}
