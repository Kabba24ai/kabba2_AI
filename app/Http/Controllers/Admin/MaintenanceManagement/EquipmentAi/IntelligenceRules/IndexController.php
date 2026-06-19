<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\IntelligenceRules;

use App\Enums\EquipmentAi\IntelligenceRuleType;
use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\MaintenanceManagement\EquipmentIntelligenceRule;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(['category_id' => 'required|exists:product_categories,id']);

        $categoryId  = (int) $request->input('category_id');
        $category    = ProductCategory::findOrFail($categoryId);
        $ruleType    = $request->input('rule_type', '');
        $profileId   = $request->input('profile_id', '');
        $status      = $request->input('status', '');  // 'approved', 'pending', ''

        $profiles = EquipmentAiProfile::where('category_id', $categoryId)
            ->orderBy('make')->orderBy('model')
            ->get(['id', 'make', 'model']);

        $query = EquipmentIntelligenceRule::with(['profile', 'approver', 'creator'])
            ->where('equipment_category_id', $categoryId)
            ->when($ruleType,  fn ($q) => $q->where('rule_type', $ruleType))
            ->when($profileId, fn ($q) => $q->where('equipment_profile_id', $profileId))
            ->when($status === 'approved', fn ($q) => $q->where('approved_by_admin', true))
            ->when($status === 'pending',  fn ($q) => $q->where('approved_by_admin', false))
            ->orderByDesc('priority')
            ->orderBy('rule_name');

        $rules = $query->get();

        return view('admin.maintenance_management.equipment_ai.intelligence_rules.index', [
            'category'   => $category,
            'rules'      => $rules,
            'profiles'   => $profiles,
            'ruleTypes'  => IntelligenceRuleType::cases(),
            'ruleType'   => $ruleType,
            'profileId'  => $profileId,
            'status'     => $status,
        ]);
    }
}
