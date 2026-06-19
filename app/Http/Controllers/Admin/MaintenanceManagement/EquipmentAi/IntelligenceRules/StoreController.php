<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\IntelligenceRules;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentIntelligenceRule;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'equipment_category_id' => 'required|exists:product_categories,id',
            'equipment_profile_id'  => 'nullable|exists:equipment_ai_profiles,id',
            'rule_type'             => 'required|in:suitability,limitation,substitution,scheduling,safety,delivery,productivity,terrain,customer_preference,application_use_case',
            'rule_name'             => 'required|string|max:255',
            'condition'             => 'required|string',
            'recommendation'        => 'required|string',
            'reason'                => 'required|string',
            'priority'              => 'integer|min:1|max:100',
            'confidence_score'      => 'numeric|min:0|max:1',
            'tags'                  => 'nullable|array',
            'tags.*'                => 'string|max:50',
            'source_type'           => 'required|in:admin,ai_generated,manufacturer,dealer,internal_experience',
            'is_active'             => 'boolean',
        ]);

        $data['created_by']        = auth()->id();
        $data['source_type']       = $data['source_type'] ?? 'admin';
        $data['approved_by_admin'] = false; // always starts unapproved
        $data['priority']          = $data['priority'] ?? 50;
        $data['confidence_score']  = $data['confidence_score'] ?? 0.70;
        $data['is_active']         = $data['is_active'] ?? true;

        EquipmentIntelligenceRule::create($data);

        return redirect()
            ->route('admin.maintenance-management.equipment-ai.intelligence-rules.index', [
                'category_id' => $data['equipment_category_id'],
            ])
            ->with('success', 'Intelligence rule created. Review and approve when ready.');
    }
}
