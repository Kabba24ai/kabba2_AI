<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiProfile;

class IndexController extends Controller
{
    public function __invoke(string $uniqueId)
    {
        $profile = EquipmentAiProfile::with(['category', 'specifications' => fn ($q) => $q->orderByDesc('is_key_comparison')->orderBy('spec_key')])
            ->where('unique_id', $uniqueId)
            ->firstOrFail();

        return view('admin.maintenance_management.equipment_ai.specifications.index', compact('profile'));
    }
}
