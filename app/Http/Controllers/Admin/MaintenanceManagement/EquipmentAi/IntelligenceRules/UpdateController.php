<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\IntelligenceRules;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentIntelligenceRule;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __invoke(Request $request, EquipmentIntelligenceRule $rule)
    {
        $data = $request->validate([
            'equipment_profile_id' => 'nullable|exists:equipment_ai_profiles,id',
            'rule_type'            => 'required|in:suitability,limitation,substitution,scheduling,safety,delivery,productivity,terrain,customer_preference,application_use_case',
            'rule_name'            => 'required|string|max:255',
            'condition'            => 'required|string',
            'recommendation'       => 'required|string',
            'reason'               => 'required|string',
            'priority'             => 'integer|min:1|max:100',
            'confidence_score'     => 'numeric|min:0|max:1',
            'tags'                 => 'nullable|array',
            'tags.*'               => 'string|max:50',
            'source_type'          => 'required|in:admin,ai_generated,manufacturer,dealer,internal_experience',
            'is_active'            => 'boolean',
        ]);

        $data['updated_by']      = auth()->id();
        $data['last_reviewed_at'] = now();

        // Editing an approved rule sets it back to pending for re-review
        if ($rule->approved_by_admin) {
            $data['approved_by_admin'] = false;
            $data['approved_by']       = null;
            $data['approved_at']       = null;
        }

        $rule->update($data);

        return redirect()
            ->route('admin.maintenance-management.equipment-ai.intelligence-rules.index', [
                'category_id' => $rule->equipment_category_id,
            ])
            ->with('success', 'Rule updated. Re-approval required.');
    }
}
