<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\IntelligenceRules;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentIntelligenceRule;
use Illuminate\Http\Request;

class DestroyController extends Controller
{
    public function __invoke(Request $request, EquipmentIntelligenceRule $rule)
    {
        $categoryId = $rule->equipment_category_id;

        // Soft delete preserves history
        $rule->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('admin.maintenance-management.equipment-ai.intelligence-rules.index', [
                'category_id' => $categoryId,
            ])
            ->with('success', 'Rule deleted.');
    }
}
